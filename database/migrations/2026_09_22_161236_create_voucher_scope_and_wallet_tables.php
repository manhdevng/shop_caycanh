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
        // Mã áp dụng cho sản phẩm cụ thể (scope_type = 'products').
        Schema::create('product_voucher', function (Blueprint $table) {
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['voucher_id', 'product_id']);
        });

        // Mã áp dụng cho danh mục cụ thể (scope_type = 'categories').
        Schema::create('category_voucher', function (Blueprint $table) {
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['voucher_id', 'category_id']);
        });

        // Ví voucher của khách hàng: mã đã lưu, đã dùng hay chưa, dùng cho đơn nào.
        Schema::create('user_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->timestamp('saved_at');
            $table->timestamp('used_at')->nullable(); // null = đã lưu nhưng chưa dùng.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'voucher_id']); // Mỗi khách chỉ 1 bản ghi cho mỗi mã -> thực thi luật "1 lần/khách".
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_voucher');
        Schema::dropIfExists('category_voucher');
        Schema::dropIfExists('product_voucher');
    }
};
