<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Thêm loại sản phẩm (Cây/Hoa), nhãn hiển thị và tên nhóm lựa chọn
     * (phân loại) cho sản phẩm. Xem D1, D2, D4 trong
     * "Claude outputs/product-module-fix-plan.md".
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Loại sản phẩm: 'plant' (Cây cảnh) | 'flower' (Hoa).
            $table->string('product_type', 20)->default('plant')->after('name')->index();

            // Nhãn hiển thị: null = tự động, hoặc 'new'|'bestseller'|'limited'|'gift'|'none'.
            $table->string('badge', 20)->nullable()->after('is_active');

            // Tên nhóm lựa chọn hiển thị cho khách (vd: "Kích cỡ", "Số bông").
            // Để trống thì dùng mặc định theo loại sản phẩm.
            $table->string('variant_label', 50)->nullable()->after('badge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_type']);
            $table->dropColumn(['product_type', 'badge', 'variant_label']);
        });
    }
};
