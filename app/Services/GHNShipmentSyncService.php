<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Support\OrderChangeContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "Bộ não" duy nhất áp trạng thái GHN vào một Order — tách ra khỏi
 * GHNWebhookController để 3 nơi dùng chung một luật:
 * - GHNWebhookController::handle() (webhook thật, server-to-server).
 * - Lệnh `ghn:sync-orders` / `orders.refreshTracking` /
 *   `admin.orders.syncGhn` (đồng bộ bù qua GHNService::orderDetail()).
 * - Lệnh `ghn:simulate` (demo sandbox, xem docblock GhnSimulateStatus).
 *
 * Giữ nguyên các luật đã có trong GHNWebhookController::applyStatus() cũ:
 * khoá bản ghi (lockForUpdate) + transaction, chặn lùi giai đoạn theo
 * Order::SHIPPING_STAGE_GROUPS, huỷ đơn qua OrderCancellationService (không
 * áp luật chặn của admin vì GHN đã xác nhận), cộng điểm thành viên, chốt
 * giao dịch COD. Mọi lần đổi orders.shipping_status đều được bọc trong
 * OrderChangeContext::run() để OrderObserver (app/Observers, do
 * laravel-backend phụ trách) ghi đúng nguồn/lý do/thời điểm vào
 * order_status_histories.
 */
class GHNShipmentSyncService
{
    /**
     * Bảng ánh xạ trạng thái GHN sang giá trị nội bộ dùng trong
     * Order::SHIPPING_LABELS. ĐÂY LÀ NƠI DUY NHẤT chứa quy tắc map (chuyển
     * từ GHNWebhookController cũ về đây) — cần chỉnh khi GHN đổi định dạng
     * thật thì chỉ sửa ở đây.
     *
     * Bổ sung so với bản cũ: delivery_fail (giao không thành công — vẫn nằm
     * trong nhóm giai đoạn "đang giao", GHN có thể thử giao lại), và 2 mã
     * COD-liên-quan money_collect_picking/money_collect_delivering (GHN vẫn
     * đang ở bước lấy/giao hàng, chỉ kèm thêm thông tin thu hộ).
     *
     * Các mã GHN không có mặt ở đây ngoài EXCEPTION_STATUSES bên dưới
     * (nếu có) cố ý KHÔNG được map vì ý nghĩa nghiệp vụ chưa rõ ràng — sẽ chỉ
     * log lại, không tự ý đoán trạng thái.
     */
    public const STATUS_MAP = [
        'ready_to_pick' => 'ready_to_pick',
        'picking' => 'picking',
        'money_collect_picking' => 'picking',
        'picked' => 'picked',
        'storing' => 'storing',
        'transporting' => 'transporting',
        'sorting' => 'sorting',
        'delivering' => 'delivering',
        'money_collect_delivering' => 'delivering',
        'delivery_fail' => 'delivery_fail',
        'delivered' => 'delivered',
        'waiting_to_return' => 'return',
        'return' => 'return',
        'return_transporting' => 'return_transporting',
        'return_sorting' => 'return_sorting',
        'returning' => 'returning',
        'return_fail' => 'returning',
        'returned' => 'returned',
        'cancel' => 'cancelled',
    ];

    /**
     * Mã GHN báo sự cố mà hệ thống KHÔNG tự đoán được nên xử lý ra sao
     * (hàng thất lạc, hư hỏng, hoặc một ngoại lệ chung chung khác) — cố ý
     * KHÔNG đổi shipping_status, chỉ ghi lại một mốc lịch sử "milestone" và
     * cảnh báo để admin chủ động vào xử lý tay (liên hệ GHN, làm việc với
     * khách, quyết định hoàn/huỷ...).
     */
    public const EXCEPTION_STATUSES = ['exception', 'damage', 'lost'];

    /** Trạng thái nội bộ coi là "đã huỷ" -> huỷ đơn qua OrderCancellationService. */
    private const CANCELLED_STATUSES = ['cancelled'];

    public function __construct(
        private GHNService $ghn,
        private OrderCancellationService $cancellation,
    ) {
    }

    /**
     * Áp 1 sự kiện trạng thái GHN vào 1 đơn hàng.
     *
     * @param  Order  $order  Đơn hàng (không cần khoá sẵn — hàm tự
     *                        lockForUpdate() lại đơn theo id trong transaction
     *                        riêng, an toàn khi gọi lồng trong transaction
     *                        khác của caller).
     * @param  string  $ghnStatus  Mã trạng thái thô do GHN gửi (vd:
     *                             "delivering", "delivered"...).
     * @param  Carbon|null  $occurredAt  Thời điểm THẬT của sự kiện (GHN
     *                                   `Time` khi là webhook, `updated_date`
     *                                   khi là log[] từ orderDetail); null =
     *                                   dùng thời điểm hiện tại.
     * @param  string  $source  'ghn_webhook'|'ghn_sync' (khớp
     *                          OrderStatusHistory::SOURCE_LABELS).
     * @param  string|null  $reason  Lý do GHN gửi kèm (trường `Reason`),
     *                               dùng làm `note` của lịch sử.
     * @return bool  true nếu shipping_status của đơn THẬT SỰ thay đổi.
     */
    public function apply(Order $order, string $ghnStatus, ?Carbon $occurredAt, string $source, ?string $reason = null): bool
    {
        if (in_array($ghnStatus, self::EXCEPTION_STATUSES, true)) {
            $this->recordException($order, $ghnStatus, $source, $reason, $occurredAt);

            return false;
        }

        $internalStatus = self::STATUS_MAP[$ghnStatus] ?? null;

        if ($internalStatus === null) {
            Log::warning('GHN: trạng thái chưa được ánh xạ, bỏ qua', [
                'order_id' => $order->id,
                'ghn_status' => $ghnStatus,
            ]);

            return false;
        }

        return DB::transaction(function () use ($order, $ghnStatus, $internalStatus, $occurredAt, $source, $reason) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                Log::warning('GHN: không tìm thấy đơn hàng để áp trạng thái', [
                    'order_id' => $order->id,
                    'ghn_status' => $ghnStatus,
                ]);

                return false;
            }

            // Idempotent: GHN có thể gọi lại webhook nhiều lần / đồng bộ bù
            // đọc lại cùng 1 mốc lịch sử -> không đổi gì nếu trạng thái không
            // đổi (tránh ghi lịch sử trùng, tránh cộng điểm/chốt COD lặp).
            if ($locked->shipping_status === $internalStatus) {
                return false;
            }

            $currentStage = Order::SHIPPING_STAGE_GROUPS[$locked->shipping_status] ?? null;
            $newStage = Order::SHIPPING_STAGE_GROUPS[$internalStatus] ?? null;

            // Cùng luật với AdminOrderController::updateStatus(): chỉ chặn
            // lùi khi cả hai trạng thái đều nằm trong nhóm mốc tiến trình
            // thông thường. Trạng thái ngoại lệ (huỷ, hoàn hàng...) luôn được
            // phép vì có thể xảy ra bất kỳ lúc nào.
            if ($currentStage !== null && $newStage !== null && $newStage < $currentStage) {
                Log::warning('GHN: bỏ qua vì trạng thái mới lùi về giai đoạn trước đó', [
                    'order_id' => $locked->id,
                    'current' => $locked->shipping_status,
                    'incoming' => $internalStatus,
                ]);

                return false;
            }

            $before = $locked->shipping_status;

            OrderChangeContext::run([
                'source' => $source,
                'note' => $reason,
                'occurred_at' => $occurredAt,
            ], function () use ($locked, $internalStatus, $reason) {
                if (in_array($internalStatus, self::CANCELLED_STATUSES, true)) {
                    // Đơn bị huỷ phía GHN -> huỷ đơn qua service để không "kẹt"
                    // đơn (đã shipping_status=cancelled nhưng chưa hoàn kho).
                    // Không áp luật chặn của admin vì GHN đã xác nhận vận đơn
                    // bị huỷ; service tự bỏ qua nếu đơn đã huỷ trước đó (không
                    // hoàn kho 2 lần). Đọc lại đơn sau khi service cập nhật để
                    // phần phía dưới không ghi đè bằng dữ liệu cũ.
                    $this->cancellation->cancel($locked, $reason ?: 'GHN báo hủy vận đơn.', enforceAdminRules: false);
                    $locked->refresh();
                }

                // Đơn đã huỷ trước đó nhưng shipping_status chưa khớp (hoặc
                // trạng thái không phải huỷ) -> chỉ cập nhật shipping_status.
                if ($locked->shipping_status !== $internalStatus) {
                    $locked->update(['shipping_status' => $internalStatus]);
                }
            });

            $locked->refresh();
            $changed = $locked->shipping_status !== $before;

            // Cộng điểm thành viên + chốt giao dịch COD (nếu đủ điều kiện) —
            // cả hai service tự kiểm tra idempotent (points_awarded / status
            // đã 'paid'), an toàn khi gọi lồng trong transaction hiện tại
            // (savepoint).
            LoyaltyService::awardIfDelivered($locked);
            CodSettlementService::settleIfDelivered($locked);

            Log::info('GHN: đã cập nhật trạng thái vận chuyển', [
                'order_id' => $locked->id,
                'ghn_status' => $ghnStatus,
                'shipping_status' => $internalStatus,
                'source' => $source,
            ]);

            return $changed;
        });
    }

    /**
     * Đồng bộ bù: gọi GHNService::orderDetail(), duyệt mảng hành trình
     * `log[]` theo thời gian tăng dần, áp từng mốc qua apply() (source
     * 'ghn_sync') — bù cho webhook bị lỡ (ví dụ máy dev không nhận được
     * webhook thật). Không bao giờ throw ra ngoài; luôn trả đúng 3 khoá
     * ['ok' => bool, 'changed' => bool, 'message' => string] để controller
     * gọi (orders.refreshTracking / admin.orders.syncGhn) chỉ cần
     * back()->with(...).
     */
    public function syncFromDetail(Order $order): array
    {
        $ghnOrderCode = trim((string) ($order->ghn_order_code ?? ''));

        if ($ghnOrderCode === '') {
            return [
                'ok' => false,
                'changed' => false,
                'message' => 'Đơn hàng chưa có mã vận đơn GHN để đồng bộ.',
            ];
        }

        try {
            $response = $this->ghn->orderDetail($ghnOrderCode);

            if (($response['code'] ?? null) !== 200 || empty($response['data'])) {
                Log::warning('GHN orderDetail thất bại khi đồng bộ', [
                    'order_id' => $order->id,
                    'ghn_order_code' => $ghnOrderCode,
                    'response' => $response,
                ]);

                return [
                    'ok' => false,
                    'changed' => false,
                    'message' => 'Không lấy được thông tin vận đơn từ GHN. Vui lòng thử lại sau.',
                ];
            }

            $data = $response['data'];

            $log = collect($data['log'] ?? [])
                ->filter(fn ($entry) => ! empty($entry['status']))
                ->sortBy(fn ($entry) => $entry['updated_date'] ?? '')
                ->values();

            // Một vài phản hồi sandbox không kèm log[] (vận đơn còn đứng ở
            // ready_to_pick) -> vẫn thử áp status hiện tại của data để không
            // bỏ lỡ trường hợp GHN chỉ trả mỗi field này.
            if ($log->isEmpty() && ! empty($data['status'])) {
                $log = collect([[
                    'status' => $data['status'],
                    'updated_date' => $data['updated_date'] ?? null,
                ]]);
            }

            $changed = false;

            foreach ($log as $entry) {
                $ghnStatus = (string) $entry['status'];
                $occurredAt = $this->parseUpdatedDate($entry['updated_date'] ?? null);

                // Đọc lại Order mỗi vòng để apply() luôn so sánh với trạng
                // thái mới nhất (tránh đè lên bằng bản $order đã cũ trong bộ
                // nhớ khi log[] có nhiều mốc liên tiếp).
                $order->refresh();

                if ($this->apply($order, $ghnStatus, $occurredAt, 'ghn_sync')) {
                    $changed = true;
                }
            }

            $order->refresh();
            $order->forceFill(['ghn_last_synced_at' => now()])->saveQuietly();

            return [
                'ok' => true,
                'changed' => $changed,
                'message' => $changed
                    ? 'Đã đồng bộ trạng thái mới nhất từ GHN.'
                    : 'Đã kiểm tra với GHN, trạng thái vận chuyển không đổi.',
            ];
        } catch (Throwable $e) {
            Log::error('Lỗi khi đồng bộ trạng thái vận chuyển từ GHN', [
                'order_id' => $order->id,
                'ghn_order_code' => $ghnOrderCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'changed' => false,
                'message' => 'Không thể đồng bộ trạng thái từ GHN lúc này. Vui lòng thử lại sau.',
            ];
        }
    }

    // exception/damage/lost: KHÔNG đổi shipping_status (nghiệp vụ chưa rõ
    // nên xử lý tự động ra sao) — chỉ ghi 1 dòng lịch sử field='milestone'
    // để hiện trên timeline + cảnh báo admin vào xử lý tay. Ghi bằng
    // insertOrIgnore (không dùng Eloquent create()) để khớp đúng unique key
    // chống trùng của bảng (osh_dedupe_unique) khi GHN gọi lại nhiều lần.
    private function recordException(Order $order, string $ghnStatus, string $source, ?string $reason, ?Carbon $occurredAt): void
    {
        $now = now();

        DB::table('order_status_histories')->insertOrIgnore([[
            'order_id' => $order->id,
            'field' => OrderStatusHistory::FIELD_MILESTONE,
            'from_value' => null,
            'to_value' => $ghnStatus,
            'source' => $source,
            'note' => $reason,
            'actor_id' => null,
            'occurred_at' => ($occurredAt ?? $now),
            'created_at' => $now,
            'updated_at' => $now,
        ]]);

        Log::warning('GHN báo trạng thái ngoại lệ, cần admin xử lý tay', [
            'order_id' => $order->id,
            'ghn_status' => $ghnStatus,
            'reason' => $reason,
        ]);
    }

    private function parseUpdatedDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable $e) {
            return null;
        }
    }
}
