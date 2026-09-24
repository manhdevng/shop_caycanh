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
        Schema::table('vouchers', function (Blueprint $table) {
            // 'all' (toàn shop) | 'products' (sản phẩm cụ thể) | 'categories' (danh mục cụ thể).
            // Mặc định 'all' để 3 mã hiện có giữ nguyên hành vi áp toàn shop.
            $table->string('scope_type')->default('all')->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('scope_type');
        });
    }
};
