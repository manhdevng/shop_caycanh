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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã giảm giá; ứng dụng tự chuẩn hoá hoa/thường khi tra cứu.
            $table->string('discount_type'); // 'percent' hoặc 'fixed'.
            $table->decimal('discount_value', 15, 2); // Giá trị giảm: % hoặc số tiền tuỳ discount_type.
            $table->decimal('min_order_amount', 15, 2)->nullable(); // Đơn tối thiểu để áp dụng; null = không giới hạn.
            $table->decimal('max_discount_amount', 15, 2)->nullable(); // Trần giảm giá khi discount_type = percent; null = không giới hạn.
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); // null = không giới hạn lượt dùng.
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
