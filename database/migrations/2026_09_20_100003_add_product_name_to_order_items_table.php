<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Snapshot tên sản phẩm tại thời điểm đặt hàng — cùng kiểu dữ liệu với
     * variant_name đã có (varchar 255, nullable) — để hoá đơn vẫn hiển thị
     * đúng tên ngay cả khi sản phẩm gốc bị đổi tên hoặc xoá mềm sau này.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
};
