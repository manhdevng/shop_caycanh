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
            $table->timestamp('delivered_at')->nullable()->after('shipping_status');
            $table->timestamp('completed_at')->nullable()->after('delivered_at');
            $table->timestamp('ghn_expected_delivery_at')->nullable()->after('completed_at');
            $table->timestamp('ghn_last_synced_at')->nullable()->after('ghn_expected_delivery_at');
        });

        $this->backfill();
    }

    /**
     * Backfill dữ liệu cũ một cách an toàn, có thể chạy lại nhiều lần
     * (idempotent) mà không tạo dữ liệu trùng lặp.
     */
    private function backfill(): void
    {
        // 1) Đơn đã giao (shipping_status = delivered) nhưng chưa có delivered_at
        //    thì lấy tạm updated_at làm mốc giao hàng.
        DB::table('orders')
            ->where('shipping_status', 'delivered')
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);

        // 2) Với mỗi đơn CHƯA có bất kỳ dòng lịch sử nào, tạo mốc "placed"
        //    (và mốc "delivered" nếu đơn đã giao). Xử lý theo lô 500 dòng.
        $ordersWithHistory = DB::table('order_status_histories')
            ->select('order_id')
            ->distinct()
            ->pluck('order_id');

        DB::table('orders')
            ->select('id', 'created_at', 'delivered_at', 'shipping_status')
            ->whereNotIn('id', $ordersWithHistory->isEmpty() ? [0] : $ordersWithHistory->all())
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                $now = now();
                $rows = [];

                foreach ($orders as $order) {
                    $rows[] = [
                        'order_id' => $order->id,
                        'field' => 'milestone',
                        'from_value' => null,
                        'to_value' => 'placed',
                        'source' => 'system',
                        'note' => null,
                        'actor_id' => null,
                        'occurred_at' => $order->created_at ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($order->shipping_status === 'delivered' && $order->delivered_at) {
                        $rows[] = [
                            'order_id' => $order->id,
                            'field' => 'shipping_status',
                            'from_value' => null,
                            'to_value' => 'delivered',
                            'source' => 'system',
                            'note' => null,
                            'actor_id' => null,
                            'occurred_at' => $order->delivered_at,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($rows)) {
                    DB::table('order_status_histories')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivered_at',
                'completed_at',
                'ghn_expected_delivery_at',
                'ghn_last_synced_at',
            ]);
        });
    }
};
