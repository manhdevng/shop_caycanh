<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * Huỷ đơn hàng — ĐÂY LÀ NƠI DUY NHẤT đưa đơn về status "cancelled".
 *
 * Được gọi từ AdminOrderController::cancel() (admin huỷ tay),
 * AdminOrderController::rejectTransfer() (admin từ chối chuyển khoản) và
 * GHNWebhookController::applyStatus() (GHN báo huỷ vận đơn). Các luồng này có
 * thể chạy đồng thời trên cùng một đơn nên bắt buộc khoá bản ghi
 * (lockForUpdate) và kiểm tra lại trạng thái trên dòng đã khoá — tránh hoàn
 * kho/trả voucher 2 lần.
 *
 * Service KHÔNG gọi GHN: việc huỷ vận đơn (gọi mạng ngoài) do caller tự làm
 * SAU khi transaction đã commit để không giữ khoá DB trong lúc chờ.
 */
class OrderCancellationService
{
    /**
     * Các trạng thái vận chuyển mà admin KHÔNG được huỷ tay: hàng đã rời kho
     * (đang giao), đã giao xong, đang/đã hoàn hàng, hoặc đã huỷ trước đó.
     */
    public const ADMIN_BLOCKED_SHIPPING_STATUSES = [
        'delivering', 'picked', 'storing', 'transporting', 'sorting', 'delivered', 'cancelled',
        'return', 'returning', 'returned', 'return_transporting', 'return_sorting',
    ];

    // Trạng thái giao dịch chưa thu tiền -> chỉ cần chuyển sang 'cancelled'.
    private const UNPAID_PAYMENT_STATUSES = ['pending', 'initiated', 'failed'];

    /**
     * Luật "admin có được huỷ đơn này không" (chỉ xét trạng thái vận chuyển).
     */
    public function canAdminCancel(Order $order): bool
    {
        return $order->status !== 'cancelled'
            && ! in_array($order->shipping_status, self::ADMIN_BLOCKED_SHIPPING_STATUSES, true);
    }

    /**
     * Huỷ đơn trong một transaction: khoá đơn, kiểm tra lại điều kiện trên
     * dòng đã khoá, hoàn kho, trả lượt dùng voucher, đồng bộ giao dịch thanh
     * toán rồi set status/shipping_status = cancelled.
     *
     * - $reason: lý do huỷ, ghi vào message của payment_transactions.
     * - $enforceAdminRules: false cho webhook GHN (GHN đã xác nhận vận đơn bị
     *   huỷ nên không áp luật chặn của admin) — chỉ còn chặn "đã huỷ rồi".
     * - $guard: điều kiện bổ sung của caller, nhận đơn đã khoá, trả về chuỗi
     *   lỗi để dừng hoặc null để cho huỷ tiếp.
     *
     * Tự mở transaction nên gọi an toàn cả khi caller đang ở trong transaction
     * khác (transaction lồng nhau của Laravel dùng savepoint).
     *
     * @return array{success: bool, message: string}
     */
    public function cancel(Order $order, string $reason, bool $enforceAdminRules = true, ?callable $guard = null): array
    {
        return DB::transaction(function () use ($order, $reason, $enforceAdminRules, $guard) {
            // Đọc lại đơn + khoá dòng: mọi kiểm tra phía dưới phải dựa trên
            // dòng đã khoá, không dựa vào model route-binding (có thể đã cũ).
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
            }

            if ($guard !== null && ($error = $guard($locked)) !== null) {
                return ['success' => false, 'message' => $error];
            }

            // Đã huỷ rồi -> không làm gì nữa, đảm bảo không hoàn kho 2 lần.
            if ($locked->status === 'cancelled') {
                return ['success' => false, 'message' => 'Đơn hàng đã được hủy trước đó.'];
            }

            if ($enforceAdminRules && ! $this->canAdminCancel($locked)) {
                return ['success' => false, 'message' => 'Không thể hủy đơn đang giao, đang hoàn hàng hoặc đã hoàn tất/đã hủy.'];
            }

            $locked->loadMissing('items');

            // Hoàn lại tồn kho cho từng sản phẩm trong đơn.
            foreach ($locked->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', (int) $item->quantity);
                }
            }

            // Trả lại lượt dùng voucher (nếu đơn có áp mã) — hoàn tác đúng
            // thao tác store() đã làm lúc tạo đơn: giảm used_count (không để
            // âm) và gỡ dấu "đã dùng" khỏi ví khách để mã có thể dùng lại.
            if ($locked->voucher_id) {
                Voucher::whereKey($locked->voucher_id)->where('used_count', '>', 0)->decrement('used_count');

                DB::table('user_voucher')
                    ->where('voucher_id', $locked->voucher_id)
                    ->where('order_id', $locked->id)
                    ->update(['used_at' => null, 'order_id' => null, 'updated_at' => now()]);
            }

            $this->syncPaymentTransaction($locked->id, $reason);

            $locked->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);

            return ['success' => true, 'message' => 'Đã hủy đơn hàng.'];
        });
    }

    /**
     * Lấy đúng dòng payment_transactions mà AdminOrderController::index() sẽ
     * hiển thị cho đơn này — dùng CÙNG thứ tự ưu tiên với subquery $paymentId
     * ở đó (ưu tiên dòng có status thuộc paid/refund_pending/refunded, nếu
     * không có thì lấy dòng mới nhất theo id) để không cập nhật nhầm dòng
     * khác dòng admin đang nhìn thấy. Luôn gọi bên trong DB::transaction()
     * đang giữ khoá đơn hàng nên khoá thêm dòng này (lockForUpdate) cho an
     * toàn. Trả về null nếu đơn không có dòng transaction nào (dữ liệu
     * cũ/bất thường) — caller PHẢI tự kiểm tra null.
     */
    public function latestPaymentTransactionFor(int $orderId): ?PaymentTransaction
    {
        return PaymentTransaction::where('order_id', $orderId)
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    // Đồng bộ giao dịch thanh toán khi huỷ đơn: đã thu tiền -> chờ hoàn tiền;
    // chưa thu -> huỷ; đang/đã hoàn tiền thì giữ nguyên.
    private function syncPaymentTransaction(int $orderId, string $reason): void
    {
        $transaction = $this->latestPaymentTransactionFor($orderId);

        if (! $transaction) {
            return;
        }

        if ($transaction->status === 'paid') {
            $transaction->update([
                'status' => 'refund_pending',
                'message' => 'Đơn hàng đã bị hủy, cần hoàn tiền cho khách. Lý do: ' . $reason,
            ]);
        } elseif (in_array($transaction->status, self::UNPAID_PAYMENT_STATUSES, true)) {
            $transaction->update([
                'status' => 'cancelled',
                'message' => $reason,
            ]);
        }
    }
}
