<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bỏ trường tồn kho (stock) ở cấp phân loại cây: các cửa hàng bán cây
     * thực tế không đếm số lượng chính xác từng chậu. Việc còn/hết hàng
     * được quản lý qua cờ "is_active" ở cấp sản phẩm.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->integer('stock')->default(0);
        });
    }
};
