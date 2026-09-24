<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Thêm khối lượng riêng (gram, để trống thì dùng products.weight) và
     * thứ tự hiển thị cho từng phân loại. Xem D2 trong
     * "Claude outputs/product-module-fix-plan.md".
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('weight')->nullable()->after('price');
            $table->unsignedInteger('sort_order')->default(0)->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['weight', 'sort_order']);
        });
    }
};
