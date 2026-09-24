<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Người tạo đơn; xoá user thì xoá đơn.
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            $table->decimal('total_price', 15, 2); // Tổng tiền khách phải thanh toán: tiền hàng + phí ship.
            $table->string('status')->default('pending'); // Trạng thái thanh toán/đơn hàng: pending, paid, cod_ordered.
            $table->string('shipping_status')->default('not_shipped'); // Trạng thái GHN: pending, ready_to_pick, delivering, delivered...
            $table->string('ghn_order_code')->nullable()->index(); // Mã vận đơn GHN trả về sau khi tạo đơn thành công.
            $table->integer('ghn_total_fee')->default(0); // Phí vận chuyển GHN, tính bằng VNĐ.
            $table->integer('to_district_id')->nullable(); // Mã quận/huyện GHN của địa chỉ người nhận.
            $table->string('to_ward_code')->nullable(); // Mã phường/xã GHN của địa chỉ người nhận.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
