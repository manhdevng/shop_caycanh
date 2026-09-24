<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nhận webhook GHN báo cập nhật trạng thái vận đơn (server-to-server, không
 * có session người dùng — KHÔNG dùng auth()). Đây là kênh cập nhật tự động
 * bổ sung cho AdminOrderController::updateStatus() (admin tự tay đổi).
 *
 * Xác thực nguồn gốc request: GHN không ký HMAC như MoMo. Cơ chế xác thực ở
 * đây dựa trên một token bí mật do TA tự đặt khi khai báo Webhook URL trên
 * trang quản trị GHN (dạng .../ghn/webhook?token=xxxxx), so khớp với
 * config('services.ghn.webhook_token'). Nếu GHN thực tế cung cấp cơ chế khác
 * (ví dụ header ký riêng), chỉ cần sửa verifyRequest() — phần còn lại không
 * đổi.
 */
class GHNWebhookController extends Controller
{
    /**
     * Bảng ánh xạ trạng thái GHN (theo tài liệu GHN Webhook API) sang giá trị
     * nội bộ đang dùng trong Order::SHIPPING_LABELS. ĐÂY LÀ NƠI DUY NHẤT chứa
     * quy tắc map — cần chỉnh khi GHN đổi định dạng thật thì chỉ sửa ở đây.
     *
     * Các mã GHN không có mặt ở đây (exception, damage, lost, delivery_fail,
     * money_collect_picking, money_collect_delivering...) cố ý KHÔNG được map
     * vì ý nghĩa nghiệp vụ chưa rõ ràng/cần admin can thiệp thủ công — webhook
     * sẽ chỉ log lại, không tự ý đoán trạng thái.
     */
    private const GHN_STATUS_MAP = [
        'ready_to_pick' => 'ready_to_pick',
        'picking' => 'picking',
        'picked' => 'picked',
        'storing' => 'storing',
        'transporting' => 'transporting',
        'sorting' => 'sorting',
        'delivering' => 'delivering',
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

    // Các trạng thái nội bộ coi là "đã hủy" -> đồng bộ luôn orders.status +
    // hoàn tồn kho, mirror đúng hành vi AdminOrderController::cancel().
    private const CANCELLED_STATUSES = ['cancelled'];

    public function handle(Request $request)
    {
        $payload = $request->all();

        // Không log giá trị token để tránh lộ bí mật dùng để xác thực webhook.
        Log::info('GHN webhook received', [
            'order_code' => $this->extractOrderCode($payload),
            'status' => $this->extractStatus($payload),
        ]);

        if (! $this->verifyRequest($request)) {
            Log::warning('GHN webhook rejected: token không hợp lệ hoặc chưa cấu hình', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $ghnOrderCode = $this->extractOrderCode($payload);
        $ghnStatus = $this->extractStatus($payload);

        if (! $ghnOrderCode || ! $ghnStatus) {
            Log::warning('GHN webhook thiếu OrderCode hoặc Status, bỏ qua', ['payload' => $payload]);

            return response()->json(['message' => 'Received']);
        }

        $internalStatus = self::GHN_STATUS_MAP[$ghnStatus] ?? null;

        if ($internalStatus === null) {
            Log::warning('GHN webhook: trạng thái GHN chưa được ánh xạ, cần kiểm tra thủ công', [
                'ghn_order_code' => $ghnOrderCode,
                'ghn_status' => $ghnStatus,
            ]);

            return response()->json(['message' => 'Received']);
        }

        $this->applyStatus($ghnOrderCode, $ghnStatus, $internalStatus);

        return response()->json(['message' => 'Received']);
    }

    // Xác thực request thực sự đến từ GHN qua token bí mật tự cấu hình
    // (query string ?token=... hoặc header X-GHN-Webhook-Token — GHN webhook
    // không hỗ trợ header tuỳ biến trên URL cấu hình sẵn nên query string là
    // lựa chọn thực tế nhất). Không hardcode secret — luôn đọc qua config().
    private function verifyRequest(Request $request): bool
    {
        $expected = config('services.ghn.webhook_token');

        if (empty($expected)) {
            // Chưa cấu hình secret -> không thể xác thực nguồn gốc request,
            // từ chối theo nguyên tắc "không xác thực được thì không tin".
            return false;
        }

        $provided = $request->query('token') ?? $request->header('X-GHN-Webhook-Token');

        return is_string($provided) && hash_equals((string) $expected, $provided);
    }

    // GHN thực tế dùng field "OrderCode"; giữ vài biến thể tên khoá phòng khi
    // payload thật khác tài liệu tham khảo (đây là điểm duy nhất cần sửa).
    private function extractOrderCode(array $payload): ?string
    {
        $value = $payload['OrderCode'] ?? $payload['order_code'] ?? $payload['OrderId'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    private function extractStatus(array $payload): ?string
    {
        $value = $payload['Status'] ?? $payload['status'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    // Tìm đơn hàng + khoá bản ghi, đối chiếu luật chuyển trạng thái (dùng
    // chung Order::SHIPPING_STAGE_GROUPS với AdminOrderController), rồi cập
    // nhật. Bọc trong transaction + lockForUpdate để idempotent khi GHN gọi
    // lại webhook nhiều lần cho cùng một sự kiện.
    private function applyStatus(string $ghnOrderCode, string $ghnStatus, string $internalStatus): void
    {
        DB::transaction(function () use ($ghnOrderCode, $ghnStatus, $internalStatus) {
            $order = Order::where('ghn_order_code', $ghnOrderCode)->lockForUpdate()->first();

            if (! $order) {
                Log::warning('GHN webhook: không tìm thấy đơn hàng khớp ghn_order_code', [
                    'ghn_order_code' => $ghnOrderCode,
                    'ghn_status' => $ghnStatus,
                ]);

                return;
            }

            // Idempotent: nếu trạng thái không đổi thì không ghi lại (tránh
            // vô hiệu hoá lịch sử/tránh update trùng khi GHN gọi lại).
            if ($order->shipping_status === $internalStatus) {
                return;
            }

            $currentStage = Order::SHIPPING_STAGE_GROUPS[$order->shipping_status] ?? null;
            $newStage = Order::SHIPPING_STAGE_GROUPS[$internalStatus] ?? null;

            // Cùng luật với AdminOrderController::updateStatus(): chỉ chặn
            // lùi khi cả hai trạng thái đều nằm trong nhóm mốc tiến trình
            // thông thường. Trạng thái ngoại lệ (huỷ, hoàn hàng...) luôn được
            // phép vì có thể xảy ra bất kỳ lúc nào.
            if ($currentStage !== null && $newStage !== null && $newStage < $currentStage) {
                Log::warning('GHN webhook: bỏ qua vì trạng thái mới lùi về giai đoạn trước đó', [
                    'order_id' => $order->id,
                    'current' => $order->shipping_status,
                    'incoming' => $internalStatus,
                ]);

                return;
            }

            $updates = ['shipping_status' => $internalStatus];

            if (in_array($internalStatus, self::CANCELLED_STATUSES, true)
                && ! in_array($order->status, ['cancelled'], true)) {
                // Đơn bị huỷ phía GHN (ví dụ huỷ trên app/web GHN) -> đồng bộ
                // orders.status + hoàn tồn kho, mirror đúng hành vi của
                // AdminOrderController::cancel() để không "kẹt" đơn (đã
                // shipping_status=cancelled nhưng chưa hoàn kho, và nút huỷ
                // tay của admin sẽ không còn cho huỷ lại vì đã ở trạng thái
                // cancelled).
                $updates['status'] = 'cancelled';

                $order->loadMissing('items');
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', (int) $item->quantity);
                    }
                }
            }

            $order->update($updates);

            // Cộng điểm thành viên (nếu đủ điều kiện) ngay khi GHN báo đơn đã
            // giao thành công — service tự kiểm tra idempotent (points_awarded),
            // an toàn khi gọi lồng trong transaction hiện tại (savepoint).
            LoyaltyService::awardIfDelivered($order);

            Log::info('GHN webhook: đã cập nhật trạng thái vận chuyển', [
                'order_id' => $order->id,
                'ghn_order_code' => $ghnOrderCode,
                'ghn_status' => $ghnStatus,
                'shipping_status' => $internalStatus,
            ]);
        });
    }
}
