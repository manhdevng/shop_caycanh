<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Email/mật khẩu admin lấy từ .env (ADMIN_EMAIL, ADMIN_PASSWORD) qua config/shop.php,
     * không viết cứng trong code.
     */
    public function run(): void
    {
        $email = config('shop.admin.email');
        $password = config('shop.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Bo qua AdminUserSeeder: hay dat ADMIN_EMAIL va ADMIN_PASSWORD trong .env.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin User',
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );

        // Tài khoản admin không cần xác thực email mới vào được khu vực quản trị.
        if (! $admin->email_verified_at) {
            $admin->markEmailAsVerified();
        }
    }
}
