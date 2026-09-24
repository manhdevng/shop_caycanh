<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tồn kho được quản lý ở CẤP SẢN PHẨM (không phải cấp phân loại/variant —
     * cột stock của product_variants đã bị xoá chủ đích ở migration
     * 2026_08_20_014303). Mặc định 0 để admin phải nhập số lượng thật trước
     * khi cho phép bán.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
