<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Tạo tài khoản admin từ biến môi trường (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD)
     * qua config/shop.php — không viết cứng mật khẩu trong mã nguồn.
     *
     * Quy tắc an toàn (dùng được cả ở local lẫn Render):
     *  - Thiếu email/mật khẩu -> bỏ qua, không lỗi.
     *  - Email đã là admin -> GIỮ NGUYÊN tài khoản và mật khẩu (chạy lại seeder không reset mật khẩu).
     *  - Email đang thuộc tài khoản khách hàng -> DỪNG, không tự nâng quyền tài khoản thường.
     *  - Tạo mới -> mật khẩu tối thiểu 12 ký tự, email đánh dấu đã xác thực.
     */
    public function run(): void
    {
        $email = trim((string) config('shop.admin.email'));
        $password = (string) config('shop.admin.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('Bo qua AdminUserSeeder: hay dat ADMIN_EMAIL va ADMIN_PASSWORD.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set a valid ADMIN_EMAIL before running seeders.');
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->role !== 'admin') {
                throw new RuntimeException('ADMIN_EMAIL belongs to a non-admin account. Choose a different email.');
            }

            $this->command?->info('Admin already exists; existing account and password kept.');

            return;
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_PASSWORD to at least 12 characters before creating the admin.');
        }

        // 'role' không nằm trong $fillable -> dùng forceFill để gán tường minh.
        $admin = new User();
        $admin->forceFill([
            'name' => config('shop.admin.name') ?: 'Admin User',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            // Tài khoản do người vận hành tạo, không cần gửi email xác thực.
            'email_verified_at' => now(),
        ])->save();

        $this->command?->info('Admin created and verified. Sign in with the configured seed credentials.');
    }
}
