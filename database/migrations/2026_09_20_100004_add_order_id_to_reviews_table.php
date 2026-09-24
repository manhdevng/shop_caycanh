<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Gắn đánh giá với đơn hàng cụ thể đã mua (để sau này có thể chỉ cho
     * phép review khi đã nhận hàng). nullOnDelete: nếu đơn hàng bị xoá,
     * đánh giá vẫn giữ lại (chỉ mất liên kết) — không cascade xoá đánh giá.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->after('user_id')
                ->constrained('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Phải xoá khoá ngoại TRƯỚC KHI xoá cột — thứ tự sai sẽ lỗi trên MySQL.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
