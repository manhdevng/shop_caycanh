<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Collection;

/**
 * Tổng hợp feed thông báo cho khách hàng: gộp bài viết mới, mã giảm giá
 * khả dụng và sản phẩm mới thành 1 danh sách chuẩn hoá, dùng cho chuông
 * thông báo (header) và trang "Thông báo" đầy đủ.
 */
class NotificationFeed
{
    /**
     * Lấy $limit item mới nhất (theo created_at) đã gộp từ 3 nguồn: posts,
     * vouchers, products. Mỗi nguồn được giới hạn $limit bản ghi trước khi
     * gộp để tránh 1 nguồn áp đảo toàn bộ feed, rồi chỉ cắt còn $limit item
     * SAU KHI đã gộp + sắp xếp.
     *
     * LƯU Ý: KHÔNG cache kết quả này bằng Cache::remember() dù hàm chạy qua
     * View Composer trên MỌI trang (layouts.shop). Dự án bật
     * config('cache.serializable_classes') = false (mặc định bảo mật của
     * Laravel chống PHP Object Injection) — driver cache 'database' khi đó
     * gọi unserialize($value, ['allowed_classes' => false]), khiến MỌI
     * object trong dữ liệu cache (Collection, Carbon...) bị hạ cấp thành
     * __PHP_Incomplete_Class ngay khi đọc lại từ bảng cache ở request kế
     * tiếp -> TypeError, sập toàn site. Vì đây là cấu hình bảo mật chủ đích
     * (không được nới lỏng ở đây), giải pháp là không cache Collection/Carbon
     * qua driver này: chạy trực tiếp 3 truy vấn (đều có limit nhỏ, tối đa
     * $limit dòng mỗi nguồn) mỗi lần gọi.
     *
     * @return Collection<int, array{type: string, icon: string, title: string, description: ?string, url: string, created_at: \Illuminate\Support\Carbon}>
     */
    public static function recentItems(int $limit = 30): Collection
    {
        $posts = Post::where('is_published', true)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(function (Post $post) {
                return [
                    'type' => 'post',
                    'icon' => '📰',
                    'title' => $post->title,
                    'description' => $post->excerpt,
                    'url' => route('posts.show', $post->slug),
                    'created_at' => $post->published_at,
                ];
            });

        $vouchers = Voucher::available()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Voucher $voucher) {
                return [
                    'type' => 'voucher',
                    'icon' => '🎟️',
                    'title' => 'Mã ' . $voucher->code,
                    'description' => $voucher->summary,
                    'url' => route('vouchers.browse'),
                    'created_at' => $voucher->created_at,
                ];
            });

        $products = Product::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                return [
                    'type' => 'product',
                    'icon' => '🌱',
                    'title' => 'Sản phẩm mới: ' . $product->name,
                    'description' => null,
                    'url' => route('shop.show', $product->id),
                    'created_at' => $product->created_at,
                ];
            });

        return $posts->concat($vouchers)
            ->concat($products)
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);
    }

    /**
     * Đếm số thông báo mới kể từ lần cuối user mở chuông thông báo. Nếu
     * chưa từng xem (notifications_last_seen_at = null), tính từ thời điểm
     * đăng ký tài khoản (created_at) — tránh badge hiển thị số khổng lồ vô
     * nghĩa với tài khoản cũ.
     */
    public static function unreadCount(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        $baseline = $user->notifications_last_seen_at ?? $user->created_at;

        return self::recentItems(50)
            ->filter(fn (array $item) => $item['created_at'] !== null && $item['created_at']->gt($baseline))
            ->count();
    }
}
