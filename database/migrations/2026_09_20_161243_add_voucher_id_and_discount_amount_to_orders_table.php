<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Voucher đã áp dụng cho đơn; xoá voucher không được xoá đơn hàng -> nullOnDelete.
            $table->foreignId('voucher_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            // Số tiền thực tế đã giảm, chốt tại thời điểm đặt hàng (snapshot), không phụ thuộc voucher sau này.
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['voucher_id']);
            $table->dropColumn(['voucher_id', 'discount_amount']);
        });
    }
};
