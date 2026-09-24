<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ProductView;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\Voucher;
use App\Services\LoyaltyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder demo (P2.1 kèm theo) — KHÔNG chạy tự động cùng DatabaseSeeder/migrate.
 * Chạy thủ công: php artisan db:seed --class=DemoDataSeeder
 *
 * Toàn bộ thao tác dùng firstOrCreate/updateOrCreate/updateOrInsert nên chạy
 * lại nhiều lần vẫn an toàn, không sinh dữ liệu trùng lặp.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVouchers();
        $this->seedProductViews();
        $this->seedWishlists();
        $this->seedSupportTicket();
        $this->awardLoyaltyForOrderNine();
    }

    /**
     * 3 voucher demo dùng cho luồng áp mã ở trang giỏ hàng/checkout.
     */
    private function seedVouchers(): void
    {
        Voucher::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_type' => 'percent',
                'discount_value' => 10,
                'min_order_amount' => null,
                'max_discount_amount' => 50000,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'is_active' => true,
            ]
        );

        Voucher::firstOrCreate(
            ['code' => 'FREESHIP30'],
            [
                'discount_type' => 'fixed',
                'discount_value' => 30000,
                'min_order_amount' => 300000,
                'max_discount_amount' => null,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'used_count' => 0,
                'is_active' => true,
            ]
        );

        Voucher::firstOrCreate(
            ['code' => 'SUMMER50'],
            [
                'discount_type' => 'percent',
                'discount_value' => 50,
                'min_order_amount' => 500000,
                'max_discount_amount' => 200000,
                'starts_at' => null,
                'expires_at' => now()->addDays(30),
                'usage_limit' => null,
                'used_count' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Lịch sử xem sản phẩm demo cho 3 user thật trên các sản phẩm thật có sẵn
     * trong DB (dùng cho tính năng gợi ý "đã xem gần đây" nếu có).
     */
    private function seedProductViews(): void
    {
        $viewsByUser = [
            4 => [21, 26, 27],
            8 => [22, 23],
            13 => [25, 26, 23],
        ];

        foreach ($viewsByUser as $userId => $productIds) {
            foreach ($productIds as $productId) {
                ProductView::firstOrCreate(
                    ['user_id' => $userId, 'product_id' => $productId],
                    ['viewed_at' => now()->subDays(rand(1, 25))->subHours(rand(0, 23))]
                );
            }
        }
    }

    /**
     * Danh sách yêu thích demo. Bảng wishlists có unique(user_id, product_id)
     * ở DB nên dùng updateOrInsert qua query builder cho gọn (không cần thêm
     * cột nào ngoài fillable của model).
     */
    private function seedWishlists(): void
    {
        $wishlist = [
            [4, 21],
            [4, 26],
            [8, 22],
        ];

        foreach ($wishlist as [$userId, $productId]) {
            DB::table('wishlists')->updateOrInsert(
                ['user_id' => $userId, 'product_id' => $productId],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * 1 ticket hỗ trợ demo của user 4 + reply đầu từ chính user 4, sau đó nếu
     * có tài khoản admin thật thì thêm 1 reply tư vấn từ admin và chuyển
     * trạng thái ticket sang 'answered'.
     */
    private function seedSupportTicket(): void
    {
        $subject = 'Cây bị vàng lá sau khi mua, phải xử lý sao ạ?';

        $ticket = Ticket::firstOrCreate(
            ['user_id' => 4, 'subject' => $subject],
            ['status' => 'open']
        );

        TicketReply::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'user_id' => 4,
                'message' => 'Em mới mua cây kim ngân được 3 ngày mà lá đã vàng gần hết, không biết do tưới quá nhiều nước hay do vận chuyển ạ?',
            ]
        );

        $admin = User::where('role', 'admin')->first();

        if ($admin) {
            TicketReply::firstOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    'user_id' => $admin->id,
                    'message' => 'Chào bạn, hiện tượng vàng lá sau vận chuyển là bình thường do cây bị sốc môi trường, bạn giảm tưới nước và để nơi có ánh sáng nhẹ trong 1 tuần nhé.',
                ]
            );

            if ($ticket->status !== 'answered') {
                $ticket->status = 'answered';
                $ticket->save();
            }
        }
    }

    /**
     * Kích hoạt luồng cộng điểm thật cho đơn hàng #9: chuyển shipping_status
     * sang 'delivered' rồi gọi đúng LoyaltyService::awardIfDelivered() —
     * KHÔNG tự viết lại logic cộng điểm ở đây để giữ đúng khoá idempotent.
     */
    private function awardLoyaltyForOrderNine(): void
    {
        $order = Order::find(9);

        if (! $order) {
            return;
        }

        if ($order->shipping_status !== 'delivered') {
            $order->shipping_status = 'delivered';
            $order->save();
        }

        LoyaltyService::awardIfDelivered($order);
    }
}
