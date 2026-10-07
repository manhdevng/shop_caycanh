<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Màu chủ đạo của cây (mã trong config('phong_thuy.colors')), admin chọn ở
     * form sản phẩm. Dùng để suy ra hành phong thủy theo màu — ưu tiên hơn
     * đoán theo tên. Null = chưa khai báo màu.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('feng_shui_colors')->nullable()->after('variant_label');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('feng_shui_colors');
        });
    }
};
