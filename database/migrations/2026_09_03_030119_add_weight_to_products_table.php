<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Khối lượng ước tính (gram) — dùng để tính phí vận chuyển GHN. Mặc định 200g nếu admin chưa nhập.
            $table->unsignedInteger('weight')->default(200)->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
