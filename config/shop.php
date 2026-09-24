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
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'name' => 'Cây Cảnh Shop',
    'tagline' => 'Cửa hàng hoa và cây cảnh',

    // Chưa có thông tin thật -> null -> footer không render các mục này.
    'address' => null,
    'hotline' => null,

    'email' => 'hotro@caycanhshop.vn',

    'hours' => null,
    'facebook' => null,
    'instagram' => null,
];
