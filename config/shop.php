<?php

/*
|--------------------------------------------------------------------------
| Thông tin cửa hàng
|--------------------------------------------------------------------------
|
| Nguồn duy nhất cho các thông tin liên hệ hiển thị ở footer (và bất cứ nơi
| nào cần sau này).
|
| QUY TẮC: mục nào chưa có thông tin thật thì để null, KHÔNG bịa và KHÔNG
| để chuỗi rỗng. View phải bọc mỗi mục trong @if(config('shop.<key>')) và
| bỏ hẳn khối đó khi null — một ô "Địa chỉ" trống hoặc một số điện thoại
| bịa còn tệ hơn là không hiện gì.
|
| Khi có thông tin thật: điền vào đây rồi chạy `php artisan config:clear`.
|
*/

return [
    // Tài khoản admin do AdminUserSeeder tạo — đặt trong .env, KHÔNG viết cứng ở đây.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin User'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'name' => 'Cây Cảnh Shop',
    'tagline' => 'Cửa hàng hoa và cây cảnh',

    // Chưa có thông tin thật -> null -> footer không render các mục này.
    'address' => null,
    'hotline' => null,

    'email' => 'hotro@caycanhshop.vn',

    /*
    |--------------------------------------------------------------------------
    | Tự động hoàn thành đơn đã giao
    |--------------------------------------------------------------------------
    |
    | Đơn ở trạng thái "delivered" mà khách không bấm "Đã nhận được hàng" sẽ
    | được lệnh `orders:auto-complete` tự chốt sau số ngày này (giống Shopee).
    | Xem app/Console/Commands/AutoCompleteOrders.php.
    |
    */
    'auto_complete_days' => (int) env('ORDER_AUTO_COMPLETE_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Đánh giá sản phẩm
    |--------------------------------------------------------------------------
    |
    | review_window_days : số ngày kể từ khi giao thành công mà khách còn được
    |                      viết / sửa đánh giá (Shopee dùng 30 ngày).
    | review_reward_points: điểm thưởng cho đánh giá "chất lượng" (có ảnh VÀ
    |                      nhận xét >= 50 ký tự), chỉ cộng 1 lần mỗi đánh giá.
    | review_max_images  : số ảnh tối đa mỗi đánh giá.
    |
    */
    'review_window_days' => (int) env('REVIEW_WINDOW_DAYS', 30),
    'review_reward_points' => (int) env('REVIEW_REWARD_POINTS', 20),
    'review_max_images' => (int) env('REVIEW_MAX_IMAGES', 5),

    'hours' => null,
    'facebook' => null,
    'instagram' => null,
];
