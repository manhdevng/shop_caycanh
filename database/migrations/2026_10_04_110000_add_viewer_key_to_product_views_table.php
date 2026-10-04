<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mở rộng product_views để ghi nhận cả khách chưa đăng nhập (ProductViewTracker):
 *  - user_id nullable: lượt xem của khách không gắn tài khoản;
 *  - viewer_key: "u:<user_id>" cho người đăng nhập, "g:<HMAC session id>" cho khách
 *    (không lưu IP hay session id thô);
 *  - tracking_version: 1 = dữ liệu cũ (chỉ người đăng nhập, chưa chống đếm lặp),
 *    2 = ghi qua ProductViewTracker (có khách, chống đếm lặp 30 phút).
 * Dữ liệu cũ được điền viewer_key = "u:<user_id>" để đếm người xem duy nhất,
 * giữ nguyên tracking_version = 1 để giao diện phân biệt trước/sau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_views', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('viewer_key', 80)->nullable()->after('user_id');
            $table->unsignedTinyInteger('tracking_version')->default(1)->after('viewed_at');

            $table->index(['product_id', 'viewed_at']);
            $table->index(['viewer_key', 'product_id', 'viewed_at']);
            $table->index('viewed_at');
        });

        DB::table('product_views')->whereNull('viewer_key')->whereNotNull('user_id')
            ->distinct()->pluck('user_id')
            ->each(fn ($userId) => DB::table('product_views')
                ->whereNull('viewer_key')->where('user_id', $userId)
                ->update(['viewer_key' => 'u:'.$userId]));
    }

    public function down(): void
    {
        // Lượt xem của khách (user_id NULL) không thể tồn tại khi user_id bắt buộc trở lại.
        DB::table('product_views')->whereNull('user_id')->delete();

        Schema::table('product_views', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'viewed_at']);
            $table->dropIndex(['viewer_key', 'product_id', 'viewed_at']);
            $table->dropIndex(['viewed_at']);
            $table->dropColumn(['viewer_key', 'tracking_version']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
