<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bổ sung thông tin liên hệ/hồ sơ cho người dùng: số điện thoại, địa chỉ
     * giao hàng mặc định và ảnh đại diện (đường dẫn tương đối trong
     * storage/app/public/avatars, không lưu ảnh nhị phân trong DB).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('role');
            $table->string('address', 255)->nullable()->after('phone');
            $table->string('avatar', 255)->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'avatar']);
        });
    }
};
