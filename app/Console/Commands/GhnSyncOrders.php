<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\GHNShipmentSyncService;
use Illuminate\Console\Command;

/**
 * Đồng bộ bù trạng thái vận chuyển GHN cho các đơn đang trên đường đi —
 * bù cho webhook bị lỡ (máy dev chạy 127.0.0.1 GHN không gọi tới được,
 * webhook lỗi mạng tạm thời...). Chạy định kỳ qua
 * Schedule::command('ghn:sync-orders') (orchestrator khai báo trong
 * routes/console.php, xem hợp đồng mục 4.2 của kế hoạch).
 *
 * Không xử lý xong 1 đơn thì dừng cả lệnh: mỗi đơn được đồng bộ độc lập qua
 * GHNShipmentSyncService::syncFromDetail() (tự bắt lỗi mạng/GHN, không throw
 * ra ngoài), nghỉ 200ms giữa các lần gọi để không dồn dập request lên GHN.
 */
class GhnSyncOrders extends Command
{
    protected $signature = 'ghn:sync-orders {--order= : Chỉ đồng bộ đúng 1 đơn theo id nội bộ (bỏ qua bộ lọc mặc định)}';

    protected $description = 'Đồng bộ bù trạng thái vận chuyển GHN cho các đơn có mã vận đơn, chưa ở trạng thái cuối (delivered/returned/cancelled).';

    /**
     * Các shipping_status coi là "đã kết thúc hành trình" — không cần đồng
     * bộ nữa (khớp Order::SHIPPING_STAGE_GROUPS + luồng hoàn hàng đã hoàn
     * tất/huỷ).
     */
    private const FINAL_SHIPPING_STATUSES = ['delivered', 'returned', 'cancelled'];

    public function handle(GHNShipmentSyncService $sync): int
    {
        $query = Order::query()
            ->whereNotNull('ghn_order_code')
            ->where('ghn_order_code', '!=', '');

        $orderOption = $this->option('order');

        if ($orderOption !== null) {
            $query->whereKey($orderOption);
        } else {
            $query->whereNotIn('shipping_status', self::FINAL_SHIPPING_STATUSES)
                // "nulls first": ghn_last_synced_at IS NOT NULL -> 0 (null) trước, 1 (đã có) sau.
                ->orderByRaw('ghn_last_synced_at IS NOT NULL')
                ->orderBy('ghn_last_synced_at', 'asc')
                ->limit(50);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info($orderOption !== null
                ? "Không tìm thấy đơn #{$orderOption} có mã vận đơn GHN."
                : 'Không có đơn nào cần đồng bộ lúc này.');

            return self::SUCCESS;
        }

        $this->info('Đồng bộ '.$orders->count().' đơn hàng...');

        $rows = [];
        $total = $orders->count();

        foreach ($orders as $index => $order) {
            $result = $sync->syncFromDetail($order);

            $rows[] = [
                $order->id,
                $order->ghn_order_code,
                $result['ok'] ? 'OK' : 'Lỗi',
                $result['changed'] ? 'Có' : 'Không',
                $result['message'],
            ];

            // Nghỉ giữa các lần gọi GHN, không nghỉ sau đơn cuối cùng.
            if ($index < $total - 1) {
                usleep(200000);
            }
        }

        $this->table(['Đơn', 'Mã vận đơn GHN', 'Kết quả', 'Đổi trạng thái', 'Ghi chú'], $rows);

        return self::SUCCESS;
    }
}
