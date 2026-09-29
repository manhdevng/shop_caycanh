<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// G4: tự huỷ đơn MoMo/chuyển khoản quá hạn chưa thanh toán — xem
// app/Console/Commands/ExpireUnpaidOrders.php. Trên máy dev, lịch chỉ chạy
// khi có tiến trình `php artisan schedule:work` đang mở (production dùng cron
// thật gọi `php artisan schedule:run` mỗi phút).
Schedule::command('orders:expire-unpaid')->everyTenMinutes()->withoutOverlapping();

// Đồng bộ bù trạng thái vận chuyển GHN cho các đơn chưa ở trạng thái cuối.
// Cần thiết vì webhook GHN có thể bị lỡ (máy dev không có URL public, GHN
// retry 10 lần rồi bỏ) — xem app/Console/Commands/GhnSyncOrders.php.
Schedule::command('ghn:sync-orders')->everyThirtyMinutes()->withoutOverlapping();

// Tự chốt "Hoàn thành" cho đơn đã giao mà khách quên bấm "Đã nhận được hàng"
// — xem app/Console/Commands/AutoCompleteOrders.php và config('shop.auto_complete_days').
Schedule::command('orders:auto-complete')->dailyAt('02:00')->withoutOverlapping();
