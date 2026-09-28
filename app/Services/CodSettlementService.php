<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

/**
 * G6: đơn COD được GHN báo/admin đánh dấu "delivered" (đã giao thành công)
 * nhưng chưa từng đồng bộ giao dịch thanh toán sang "paid" -> doanh thu COD
 * không được Admin\FinanceController tính vào báo cáo (chỉ tính
 * payment_status = 'paid'). Đây là NƠI DUY NHẤT chứa logic đồng bộ này —
 * được gọi từ cả GHNWebhookController::applyStatus() (GHN báo tự động) và
 * AdminOrderController::updateStatus() (admin thao tác tay), hai luồng này
 * có thể cùng đưa một đơn về "delivered" gần như đồng thời nên bắt buộc phải
 * khoá bản ghi (lockForUpdate) + tự kiểm tra idempotent.
 */
class CodSettlementService
{
    /**
     * Đồng bộ giao dịch COD sang "paid" nếu đơn đã "delivered" và là đơn COD
     * (payment_method = 'cod' hoặc giao dịch gần nhất có gateway = 'cod').
     * Tự mở transaction + khoá bản ghi riêng nên gọi an toàn dù caller đang ở
     * trong transaction khác (transaction lồng nhau của Laravel dùng
     * savepoint). Idempotent: gọi nhiều lần không tạo thêm thay đổi.
     */
    public static function settleIfDelivered(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->shipping_status !== 'delivered') {
                return;
            }

            // Cùng cách chọn dòng ưu tiên với
            // OrderCancellationService::latestPaymentTransactionFor() /
            // Admin\FinanceController::PAYMENT_PRIORITY — ưu tiên dòng đã
            // thu/hoàn tiền, không để lần thử mới hơn che mất.
            $payment = PaymentTransaction::where('order_id', $locked->id)
                ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $isCod = $locked->payment_method === Order::PAYMENT_COD || ($payment && $payment->gateway === 'cod');

            if (! $isCod) {
                return;
            }

            // Đã 'paid' rồi (webhook gọi lại lần 2, hoặc admin bấm 2 lần) -> không làm gì thêm.
            if ($payment && $payment->status === 'paid') {
                return;
            }

            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? now(),
                    'message' => 'Đã thu tiền khi giao hàng thành công (COD).',
                ]);
            } else {
                // Đơn cũ chưa từng có payment_transactions -> tạo mới.
                PaymentTransaction::create([
                    'order_id' => $locked->id,
                    'gateway' => 'cod',
                    'amount' => $locked->total_price,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'message' => 'Đã thu tiền khi giao hàng thành công (COD).',
                ]);
            }

            if ($locked->status !== 'cod_paid') {
                $locked->update(['status' => 'cod_paid']);
            }
        });
    }
}
