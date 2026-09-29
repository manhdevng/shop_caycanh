<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * In ra URL webhook để gửi GHN đăng ký — nhưng GHI RA FILE chứ không in
 * token lên màn hình/terminal history.
 *
 * GHN chỉ gọi được URL public, nên khi dev trên 127.0.0.1 phải chạy tunnel
 * (cloudflared / ngrok) rồi dùng domain của tunnel làm {--base}.
 *
 *     php artisan ghn:webhook-url --base=https://abcd-1234.trycloudflare.com
 *
 * File kết quả nằm trong storage/app/private/ (đã gitignore) để không lọt
 * token vào commit.
 */
class GhnWebhookUrl extends Command
{
    protected $signature = 'ghn:webhook-url
        {--base= : Domain public của tunnel, vd https://abcd.trycloudflare.com (mặc định lấy APP_URL)}
        {--show : In thẳng URL kèm token ra màn hình — CHỈ dùng khi không ai nhìn màn hình}';

    protected $description = 'Sinh URL webhook GHN kèm token, ghi vào storage/app/private/ghn-webhook-url.txt (không in token ra màn hình)';

    private const OUTPUT_PATH = 'ghn-webhook-url.txt';

    public function handle(): int
    {
        $token = config('services.ghn.webhook_token');

        if (blank($token)) {
            $this->error('Thiếu GHN_WEBHOOK_TOKEN trong .env. Thêm một chuỗi ngẫu nhiên 40 ký tự rồi chạy lại.');

            return self::FAILURE;
        }

        $base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');

        if (! str_starts_with($base, 'http')) {
            $this->error('--base phải là URL đầy đủ, ví dụ https://abcd.trycloudflare.com');

            return self::FAILURE;
        }

        $url = $base.'/ghn/webhook?token='.$token;

        Storage::disk('local')->put(self::OUTPUT_PATH, $url.PHP_EOL);

        if (str_contains($base, '127.0.0.1') || str_contains($base, 'localhost')) {
            $this->warn('Cảnh báo: URL đang trỏ về localhost — GHN ở ngoài Internet KHÔNG gọi tới được.');
            $this->warn('Chạy tunnel trước (ví dụ: cloudflared tunnel --url http://localhost:8000) rồi chạy lại với --base=<domain tunnel>.');
        }

        if ($this->option('show')) {
            $this->line($url);
        } else {
            $this->info('Đã ghi URL webhook (kèm token) vào: storage/app/private/'.self::OUTPUT_PATH);
            $this->line('Domain: '.$base.'/ghn/webhook?token=<đã ẩn>');
            $this->line('Mở file đó rồi gửi URL cho GHN kèm: Client ID, môi trường (Staging/Production), tên shop.');
        }

        return self::SUCCESS;
    }
}
