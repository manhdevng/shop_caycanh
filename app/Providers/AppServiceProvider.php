<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\NotificationFeed;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Thư xác nhận email khi đăng ký — viết bằng tiếng Việt thay cho mẫu
        // tiếng Anh mặc định của Laravel. $url là link có chữ ký, hết hạn sau
        // config('auth.verification.expire', 60) phút.
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $shop = config('shop.name', 'Cây Cảnh Shop');
            $minutes = config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Xác nhận email – '.$shop)
                ->greeting('Xin chào '.($notifiable->name ?: 'bạn').'!')
                ->line('Cảm ơn bạn đã đăng ký tài khoản tại '.$shop.'.')
                ->line('Vui lòng bấm nút bên dưới để xác nhận địa chỉ email của bạn.')
                ->action('Xác nhận email', $url)
                ->line('Link có hiệu lực trong '.$minutes.' phút. Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.')
                ->salutation('Trân trọng,'."\n\n".$shop);
        });

        // Menu "Danh mục cây cảnh / Hoa" trên navbar — dùng chung cho mọi
        // trang khách hàng (layouts.shop). Chỉ lấy nhóm gốc scope
        // plant/flower (Phase 4 chia 2 cột theo scope); nhóm scope='both' là
        // thuộc tính lọc dùng chung, không phải danh mục sản phẩm nên không
        // đưa vào menu điều hướng chính. Xem D1.
        View::composer('layouts.shop', function ($view) {
            $view->with('navCategories', Category::whereNull('parent_id')
                ->whereIn('scope', ['plant', 'flower'])
                ->with(['children' => function ($q) {
                    $q->withCount(['products' => function ($q) {
                        $q->where('is_active', true);
                    }]);
                }])
                ->ordered()
                ->get());

            // Feed thông báo (chuông header) — dùng chung cho mọi trang
            // khách hàng. headerNotifications chỉ lấy 8 item mới nhất để
            // hiển thị dropdown; unreadNotificationCount là số badge.
            $view->with('headerNotifications', NotificationFeed::recentItems(8));
            $view->with('unreadNotificationCount', auth()->check() ? NotificationFeed::unreadCount(auth()->user()) : 0);

            // Tab "Đơn hàng" của chuông: thông báo CÁ NHÂN về đơn của user
            // (bảng notifications). $unreadOrderCount quyết định tab nào mở
            // mặc định khi bấm chuông.
            $user = auth()->user();
            $view->with('orderNotifications', NotificationFeed::orderNotifications($user, 5));
            $view->with('unreadOrderCount', NotificationFeed::unreadOrderCount($user));
        });
    }
}
