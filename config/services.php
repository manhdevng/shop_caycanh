<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Thông tin tài khoản ngân hàng hiển thị ở trang checkout khi khách chọn
    // hình thức "Chuyển khoản ngân hàng" (C4.1). Không phải bí mật như khóa
    // MoMo, nhưng vẫn để trong .env để mỗi môi trường khai số tài khoản riêng.
    'bank' => [
        'name'           => env('BANK_NAME', 'Vietcombank'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', ''),
        'account_name'   => env('BANK_ACCOUNT_NAME', ''),
        'branch'         => env('BANK_BRANCH', ''),
    ],

    'ghn' => [
        'base_url' => env('GHN_BASE_URL'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => env('GHN_SHOP_ID'),
        'verify_ssl' => env('GHN_VERIFY_SSL', true),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
        'from_ward_code' => env('GHN_FROM_WARD_CODE'),
        'webhook_token' => env('GHN_WEBHOOK_TOKEN'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'verify_ssl' => env('MOMO_VERIFY_SSL', true),
        // Để trống -> MomoService tự dùng route('momo.callback') / route('momo.ipn').
        'redirect_url' => env('MOMO_REDIRECT_URL'),
        'ipn_url' => env('MOMO_IPN_URL'),
    ],

];
