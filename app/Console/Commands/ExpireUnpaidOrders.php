<?php

namespace App\Console\Commands;

use App\Http\Controllers\User\MomoController;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\GHNOrderService;
use App\Services\MomoService;
use App\Services\OrderCancellationService;
use App\Support\OrderChangeContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * G4: đơn MoMo "pending" hoặc chuyển khoản "awaiting_transfer" không bao giờ
 * tự hết hạn -> giữ kho/lượt voucher vĩnh viễn nếu khách bỏ ngang thanh
 * toán. Lệnh này tự huỷ các đơn quá hạn cấu hình trong config/orders.php.
 *
 * Trước khi huỷ đơn MoMo, LUÔN hỏi lại MoMo (queryTransaction) đề phòng MoMo
 * đã báo thanh toán thành công mà IPN/callback không tới được server (ví dụ
 * chạy dev trên localhost) — khi đó hoàn tất thanh toán thay vì huỷ oan đơn
 * khách đã trả tiền.
 *
 * Chạy định kỳ qua Laravel Scheduler (xem routes/console.php) — trên máy dev
 * PHẢI có tiến trình `php artisan schedule:work` đang chạy thì lịch mới thực
 * thi (không giống production dùng cron thật).
 */
class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--dry-run : Chỉ liệt kê đơn quá hạn, không huỷ hay đổi dữ liệu}';

    protected $description = 'Tự huỷ đơn MoMo/chuyển khoản quá hạn chưa thanh toán (hoàn kho, trả voucher)';

    public function handle(
        OrderCancellationService $cancellation,
        GHNOrderService $ghnOrderService,
        MomoService $momo,
        MomoController $momoController,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $momoCutoff = Carbon::now()->subMinutes((int) config('orders.momo_ttl_minutes', 120));
        $bankCutoff = Carbon::now()->subHours((int) config('orders.bank_ttl_hours', 48));

        $expiredMomoOrders = Order::where('status', 'pending')
            ->where('payment_method', Order::PAYMENT_MOMO)
            ->where('created_at', '<=', $momoCutoff)
            ->get();

        $expiredBankOrders = Order::where('status', 'awaiting_transfer')
            ->where('payment_method', Order::PAYMENT_BANK_TRANSFER)
            ->where('created_at', '<=', $bankCutoff)
            ->get();

        $cancelledCount = 0;
        $completedCount = 0;
        $skippedCount = 0;

        foreach ($expiredMomoOrders as $order) {
            $this->line("MoMo #{$order->id} quá hạn thanh toán (tạo lúc {$order->created_at}).");

            if ($dryRun) {
                continue;
            }

            if ($this->momoAlreadyPaid($order, $momo, $ghnOrderService, $momoController)) {
                $completedCount++;
                $this->info('  -> MoMo báo đã thanh toán, hoàn tất thanh toán thay vì huỷ.');

                continue;
            }

            if ($this->cancelExpired($order, $cancellation, $ghnOrderService, 'Hệ thống tự huỷ: quá hạn thanh toán MoMo.',
                fn (Order $locked) => $locked->status === 'pending' && $locked->payment_method === Order::PAYMENT_MOMO
                    ? null
                    : 'Đơn hàng đã đổi trạng thái, bỏ qua.')) {
                $cancelledCount++;
            } else {
                $skippedCount++;
            }
        }

        foreach ($expiredBankOrders as $order) {
            $this->line("Chuyển khoản #{$order->id} quá hạn chờ đối soát (tạo lúc {$order->created_at}).");

            if ($dryRun) {
                continue;
            }

            if ($this->cancelExpired($order, $cancellation, $ghnOrderService, 'Hệ thống tự huỷ: quá hạn chờ chuyển khoản.',
                fn (Order $locked) => $locked->status === 'awaiting_transfer' ? null : 'Đơn hàng đã đổi trạng thái, bỏ qua.')) {
                $cancelledCount++;
            } else {
                $skippedCount++;
            }
        }

        $total = $expiredMomoOrders->count() + $expiredBankOrders->count();

        if ($dryRun) {
            $this->info("(--dry-run) {$total} đơn quá hạn, chưa huỷ/đổi dữ liệu nào.");
        } else {
            $this->info("Xong: {$cancelledCount} đơn bị huỷ, {$completedCount} đơn hoàn tất thanh toán, {$skippedCount} đơn bỏ qua (đã đổi trạng thái từ lúc quét).");
        }

        return self::SUCCESS;
    }

    // Hỏi lại MoMo cho giao dịch gần nhất của đơn; nếu MoMo báo đã thanh toán
    // thành công thì hoàn tất thanh toán (dùng chung MomoController::completePayment())
    // và trả về true để caller không huỷ đơn này nữa.
    private function momoAlreadyPaid(Order $order, MomoService $momo, GHNOrderService $ghnOrderService, MomoController $momoController): bool
    {
        $transaction = PaymentTransaction::where('order_id', $order->id)
            ->where('gateway', 'momo')
            ->where('status', '!=', 'paid')
            ->whereNotNull('gateway_order_id')
            ->latest('id')
            ->first();

        if (! $transaction) {
            return false;
        }

        $result = $momo->queryTransaction($transaction->gateway_order_id);
        $result['orderId'] = $result['orderId'] ?? $transaction->gateway_order_id;

        if (! $momo->isSuccessful($result)) {
            return false;
        }

        $momoController->completePayment($result, $ghnOrderService, $momo);

        return true;
    }

    // Huỷ 1 đơn quá hạn qua OrderCancellationService (khoá dòng + kiểm tra lại
    // điều kiện trên dòng đã khoá qua $guard), rồi huỷ vận đơn GHN nếu có
    // (luôn null với các đơn quá hạn vì chưa từng tạo vận đơn, giữ lại để
    // nhất quán với các luồng huỷ khác).
    private function cancelExpired(Order $order, OrderCancellationService $cancellation, GHNOrderService $ghnOrderService, string $reason, \Closure $guard): bool
    {
        // Nguồn 'scheduler' để lịch sử đơn phân biệt rõ "hệ thống tự huỷ vì
        // quá hạn" với "khách/admin bấm huỷ".
        $result = OrderChangeContext::run([
            'source' => 'scheduler',
            'note' => $reason,
        ], fn () => $cancellation->cancel($order, $reason, guard: $guard));

        if (! $result['success']) {
            $this->warn("  -> Bỏ qua: {$result['message']}");

            return false;
        }

        $ghnOrderService->cancelForOrder($order);
        $this->info('  -> Đã huỷ.');

        return true;
    }
}
