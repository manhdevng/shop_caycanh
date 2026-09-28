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
