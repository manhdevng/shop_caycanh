<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Thời hạn tự huỷ đơn chưa thanh toán (G4)
    |--------------------------------------------------------------------------
    |
    | Đơn MoMo còn "pending" quá momo_ttl_minutes phút, hoặc đơn chuyển khoản
    | còn "awaiting_transfer" quá bank_ttl_hours giờ, sẽ bị lệnh
    | `orders:expire-unpaid` tự huỷ (hoàn kho, trả voucher) để không giữ kho
    | vĩnh viễn. Xem app/Console/Commands/ExpireUnpaidOrders.php.
    |
    */

    'momo_ttl_minutes' => (int) env('ORDERS_MOMO_TTL_MINUTES', 120),
    'bank_ttl_hours' => (int) env('ORDERS_BANK_TTL_HOURS', 48),

];
