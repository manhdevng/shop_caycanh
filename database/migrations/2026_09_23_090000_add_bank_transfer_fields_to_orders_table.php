<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('cod')->after('total_price');
            $table->string('transfer_ref', 100)->nullable()->after('payment_method');
            $table->timestamp('transfer_confirmed_at')->nullable()->after('transfer_ref');
            $table->unsignedBigInteger('transfer_confirmed_by')->nullable()->after('transfer_confirmed_at');

            $table->foreign('transfer_confirmed_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // Backfill dữ liệu hiện có dựa trên status
        DB::statement("UPDATE orders SET payment_method = 'cod' WHERE status IN ('cod_ordered','cod_paid')");
        DB::statement("UPDATE orders SET payment_method = 'momo' WHERE status IN ('pending','paid','paid_momo')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['transfer_confirmed_by']);
            $table->dropColumn([
                'payment_method',
                'transfer_ref',
                'transfer_confirmed_at',
                'transfer_confirmed_by',
            ]);
        });
    }
};
