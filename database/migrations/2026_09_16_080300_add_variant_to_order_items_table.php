<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lưu lại phân loại đã mua trong từng dòng đơn hàng: khoá ngoại tới
     * product_variants (nullOnDelete để không chặn việc xoá phân loại sau
     * này) và snapshot tên phân loại lúc đặt hàng. Xem D2/D3-P8/F11/F13
     * trong "Claude outputs/product-module-fix-plan.md".
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            // Snapshot tên phân loại lúc đặt hàng, để vẫn hiện được ngay
            // cả khi phân loại gốc đã bị xoá (variant_id -> null).
            $table->string('variant_name')->nullable()->after('variant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Phải xoá khoá ngoại TRƯỚC KHI xoá cột — thứ tự sai sẽ lỗi trên MySQL.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'variant_name']);
        });
    }
};
