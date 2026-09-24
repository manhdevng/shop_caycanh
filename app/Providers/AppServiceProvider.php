<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\NotificationFeed;
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
        });
    }
}
