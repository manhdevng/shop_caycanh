<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sổ địa chỉ nhận hàng của khách (giống Shopee): mỗi khách có nhiều địa
     * chỉ, đúng 1 địa chỉ is_default được chọn sẵn ở trang thanh toán. Lưu cả
     * mã GHN (district_id/ward_code) để tính phí ship ngay, và tên Tỉnh/Quận/
     * Phường để hiển thị mà không phải gọi lại GHN.
     */
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->integer('province_id')->nullable();
            $table->string('province_name')->nullable();
            $table->integer('district_id');
            $table->string('district_name')->nullable();
            $table->string('ward_code', 20);
            $table->string('ward_name')->nullable();
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
