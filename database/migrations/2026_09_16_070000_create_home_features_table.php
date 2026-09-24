<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Bảng lưu ảnh/video cho 3 mục giới thiệu trên trang chủ (khối
     * "Cây được tuyển chọn" / "Dịch vụ tận tâm" / "Chất liệu cao cấp").
     * Trước đây 3 mục này chỉ là khối placeholder cứng trong Blade — giờ
     * quản trị viên có thể tự đổi ảnh/video ở trang /admin/settings mà
     * không cần sửa code.
     *
     * Lưu ý (F16): file này ĐÃ CHẠY trên MySQL thật (batch trước) bằng SQL
     * thuần (AUTO_INCREMENT). Đã viết lại bằng Schema builder chuẩn Laravel
     * để `php artisan test` chạy được trên SQLite `:memory:`. Việc sửa file
     * này KHÔNG làm migration chạy lại trên DB đã có (Laravel chỉ chạy các
     * migration chưa có trong bảng `migrations`).
     */
    public function up(): void
    {
        if (! Schema::hasTable('home_features')) {
            Schema::create('home_features', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('media_type', 20)->default('image');
                $table->string('media_path')->nullable();
                $table->timestamps();
            });
        }

        $now = Carbon::now();

        $defaults = [
            ['slug' => 'curated', 'title' => 'Cây được tuyển chọn'],
            ['slug' => 'service', 'title' => 'Dịch vụ tận tâm'],
            ['slug' => 'materials', 'title' => 'Chất liệu cao cấp'],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('home_features')->where('slug', $row['slug'])->exists();
            if (! $exists) {
                DB::table('home_features')->insert([
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'media_type' => 'image',
                    'media_path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_features');
    }
};
