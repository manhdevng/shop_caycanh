<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cộng điểm thành viên + xếp hạng khi đơn hàng giao thành công (P2.2).
 *
 * Đây là NƠI DUY NHẤT chứa logic cộng điểm — được gọi từ cả
 * AdminOrderController::updateStatus() (admin thao tác tay) và
 * GHNWebhookController::applyStatus() (GHN báo tự động), hai luồng này có thể
 * cùng đưa một đơn về trạng thái "delivered" gần như đồng thời nên bắt buộc
 * phải khoá bản ghi (lockForUpdate) + cờ chống trùng (points_awarded).
 */
class LoyaltyService
{
    /**
     * Ngưỡng điểm ứng với từng hạng thành viên, xét theo thứ tự giảm dần
     * (điểm càng cao khớp hạng càng cao). Khoá là mốc điểm tối thiểu.
     */
    private const TIER_THRESHOLDS = [
        5000 => 'platinum',
        2000 => 'gold',
        500 => 'silver',
        0 => 'member',
    ];

    /** Số tiền (VNĐ) tương ứng 1 điểm tích luỹ. */
    private const VND_PER_POINT = 10000;

    /**
     * Suy ra hạng thành viên tương ứng với tổng điểm hiện có.
     */
    public static function tierFor(int $points): string
    {
        foreach (self::TIER_THRESHOLDS as $minPoints => $tier) {
            if ($points >= $minPoints) {
                return $tier;
            }
        }

        // Không bao giờ tới đây vì mốc 0 luôn khớp, nhưng vẫn trả về mặc định
        // an toàn để tránh hàm trả null trong mọi trường hợp.
        return 'member';
    }

    /**
     * Cộng điểm + cập nhật hạng cho chủ đơn hàng nếu đơn đã ở trạng thái
     * "delivered" và chưa từng được cộng điểm. Tự mở transaction + khoá bản
     * ghi riêng nên gọi an toàn dù caller đang ở trong transaction khác
     * (transaction lồng nhau của Laravel dùng savepoint).
     */
    public static function awardIfDelivered(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Khoá lại đơn hàng để đọc trạng thái mới nhất, tránh 2 luồng
            // (admin tay + webhook GHN) cùng đọc điều kiện lúc points_awarded
            // vẫn còn false rồi cùng cộng điểm.
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->shipping_status !== 'delivered' || $locked->points_awarded) {
                return;
            }

            $points = intdiv((int) $locked->total_price, self::VND_PER_POINT);

            if ($locked->user_id) {
                $user = User::whereKey($locked->user_id)->lockForUpdate()->first();

                if ($user) {
                    $user->points = $user->points + $points;
                    $user->tier = self::tierFor($user->points);
                    $user->save();
                }
            }

            $locked->points_awarded = true;
            $locked->save();
        });
    }
}
