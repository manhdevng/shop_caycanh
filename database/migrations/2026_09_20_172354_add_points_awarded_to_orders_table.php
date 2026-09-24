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
            // Cờ chống cộng điểm 2 lần cho cùng 1 đơn khi admin thao tác tay
            // và webhook GHN cùng có thể set shipping_status = delivered.
            $table->boolean('points_awarded')->default(false)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('points_awarded');
        });
    }
};
