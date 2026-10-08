<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thời điểm sản phẩm của đơn đã được trả về giỏ hàng khi khách huỷ đơn
     * chưa thanh toán (CartService::restoreFromOrder()). Null = chưa trả;
     * có giá trị thì không trả thêm lần nữa (chống huỷ 2 lần / bấm đúp).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('cart_restored_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cart_restored_at');
        });
    }
};
