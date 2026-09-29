<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\GHNShipmentSyncService;
use Illuminate\Console\Command;

/**
 * CHỈ DÙNG ĐỂ DEMO/KIỂM THỬ TRÊN SANDBOX — vận đơn GHN sandbox thường đứng
 * yên ở "ready_to_pick" (không có shipper thật đi lấy/giao hàng) nên không
 * thể chờ webhook thật để xem hết hành trình. Lệnh này giả lập GHN gọi
 * webhook báo đổi trạng thái, đi qua ĐÚNG GHNShipmentSyncService::apply()
 * (cùng luật chặn lùi giai đoạn, huỷ đơn, cộng điểm, chốt COD như webhook
 * thật) với source 'ghn_webhook' — không tạo luật riêng, không tự ý đổi
 * thẳng orders.shipping_status.
 *
 * Bị chặn cứng ngoài môi trường local: TUYỆT ĐỐI không được chạy nhầm ở
 * production (sẽ làm sai lệch trạng thái đơn hàng thật của khách).
 */
class GhnSimulateStatus extends Command
{
    protected $signature = 'ghn:simulate {order : ID đơn hàng nội bộ trong DB} {status : Mã trạng thái GHN cần giả lập, vd: picked, delivering, delivered, delivery_fail} {--reason= : Lý do kèm theo (dùng cho delivery_fail/return...)}';

    protected $description = '[DEV/SANDBOX] Giả lập webhook GHN báo đổi trạng thái vận đơn cho 1 đơn hàng để demo hành trình — CHỈ chạy được ở APP_ENV=local.';

    public function handle(GHNShipmentSyncService $sync): int
    {
        if (! app()->environment('local')) {
            $this->error('ghn:simulate bị chặn: lệnh giả lập trạng thái GHN chỉ được phép chạy ở môi trường local (demo sandbox).');

            return self::FAILURE;
        }

        $orderId = $this->argument('order');
        $order = Order::find($orderId);

        if (! $order) {
            $this->error("Không tìm thấy đơn hàng #{$orderId} trong DB.");

            return self::FAILURE;
        }

        $ghnStatus = (string) $this->argument('status');
        $reason = $this->option('reason');

        $this->line("Đơn #{$order->id}: shipping_status trước khi giả lập = {$order->shipping_status}");

        $changed = $sync->apply($order, $ghnStatus, now(), 'ghn_webhook', $reason);

        $order->refresh();

        $this->info(sprintf(
            'Đơn #%d: shipping_status sau khi giả lập "%s" = %s (%s).',
            $order->id,
            $ghnStatus,
            $order->shipping_status,
            $changed ? 'đã đổi trạng thái' : 'KHÔNG đổi (trùng trạng thái hiện tại, bị chặn lùi giai đoạn, hoặc mã GHN chưa được ánh xạ/thuộc nhóm ngoại lệ)'
        ));

        return self::SUCCESS;
    }
}
