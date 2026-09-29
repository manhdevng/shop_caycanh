<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Support\OrderChangeContext;
use Illuminate\Console\Command;

/**
 * Tự chốt "Hoàn thành" cho các đơn GHN đã giao mà khách quên bấm "Đã nhận
 * được hàng" — giống Shopee tự hoàn thành đơn sau vài ngày.
 *
 * Mốc hoàn thành là cột orders.completed_at (KHÔNG thêm giá trị mới vào
 * orders.status) nên không ảnh hưởng PAID_OR_COD_STATUSES, LoyaltyService hay
 * báo cáo tài chính. Việc ghi history + báo khách do OrderObserver lo.
 *
 * Chạy định kỳ qua scheduler; trên máy dev phải có `php artisan schedule:work`.
 */
class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete {--dry-run : Chỉ liệt kê đơn đủ điều kiện, không đổi dữ liệu}';

    protected $description = 'Tự hoàn thành đơn đã giao quá hạn mà khách chưa bấm "Đã nhận được hàng"';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = max(1, (int) config('shop.auto_complete_days', 3));
        $cutoff = now()->subDays($days);

        $orders = Order::query()
            ->where('shipping_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNull('completed_at')
            ->where('delivered_at', '<=', $cutoff)
            ->orderBy('delivered_at')
            ->get();

        if ($orders->isEmpty()) {
            $this->info("Không có đơn nào đã giao quá {$days} ngày cần tự hoàn thành.");

            return self::SUCCESS;
        }

        $done = 0;

        foreach ($orders as $order) {
            $this->line("Đơn #{$order->id} đã giao lúc {$order->delivered_at}.");

            if ($dryRun) {
                continue;
            }

            OrderChangeContext::run([
                'source' => 'scheduler',
                'note' => "Hệ thống tự hoàn thành sau {$days} ngày kể từ khi giao thành công.",
            ], function () use ($order) {
                $order->update(['completed_at' => now()]);
            });

            $done++;
            $this->info('  -> Đã hoàn thành.');
        }

        if ($dryRun) {
            $this->info("(--dry-run) {$orders->count()} đơn đủ điều kiện, chưa đổi dữ liệu nào.");
        } else {
            $this->info("Xong: {$done} đơn được tự hoàn thành.");
        }

        return self::SUCCESS;
    }
}
