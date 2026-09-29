<?php

namespace App\Http\Controllers;

use App\Support\NotificationFeed;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Các tab hợp lệ của trang thông báo. */
    private const TABS = ['don-hang', 'khuyen-mai'];

    /**
     * Trang "Thông báo" đầy đủ, 2 tab:
     * - "don-hang": thông báo cá nhân về đơn hàng (bảng `notifications`).
     * - "khuyen-mai": feed chung (bài viết, voucher, sản phẩm mới).
     *
     * Chỉ tab khuyến mãi mới cập nhật notifications_last_seen_at — thông báo
     * đơn hàng có read_at riêng cho từng dòng, mở danh sách không có nghĩa là
     * khách đã đọc từng cái (khách phải bấm vào, xem open()).
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab');

        if (! in_array($tab, self::TABS, true)) {
            // Mặc định mở tab có việc cần xem: đơn hàng nếu đang có tin chưa đọc.
            $tab = NotificationFeed::unreadOrderCount($request->user()) > 0 ? 'don-hang' : 'khuyen-mai';
        }

        $promoItems = NotificationFeed::recentItems(30);

        $orderNotifications = $request->user()
            ? $request->user()->notifications()->latest()->paginate(15)
            : null;

        if ($tab === 'khuyen-mai' && $request->user()) {
            $user = $request->user();
            $user->notifications_last_seen_at = now();
            $user->save();
        }

        // $items: giữ nguyên tên biến cũ để view khuyến mãi hiện tại không vỡ
        // trong lúc blade-frontend chưa làm xong 2 tab.
        return view('notifications.index', [
            'tab' => $tab,
            'items' => $promoItems,
            'promoItems' => $promoItems,
            'orderNotifications' => $orderNotifications,
        ]);
    }

    /**
     * Số chưa đọc cho badge chuông — frontend poll 60 giây/lần.
     * total = đơn hàng chưa đọc + tin khuyến mãi mới; orders = riêng đơn hàng.
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'total' => NotificationFeed::unreadCount($user),
            'orders' => NotificationFeed::unreadOrderCount($user),
        ]);
    }

    /**
     * Mở 1 thông báo: đánh dấu đã đọc rồi chuyển tới trang đích trong
     * data.url.
     *
     * Chỉ tìm trong thông báo của CHÍNH user hiện tại (không dùng
     * DatabaseNotification::find() với id từ URL — id là uuid nhưng vẫn
     * không được phép đọc/đánh dấu thông báo của người khác), và chỉ chấp
     * nhận URL nội bộ bắt đầu bằng '/' để không biến route này thành bàn đạp
     * chuyển hướng ra ngoài (open redirect).
     */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return redirect()->route('notifications.index')
                ->with('error', 'Không tìm thấy thông báo này.');
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return redirect($url);
        }

        return redirect()->route('notifications.index', ['tab' => 'don-hang']);
    }

    /** Đánh dấu đã đọc toàn bộ thông báo cá nhân của user hiện tại. */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu đã đọc tất cả thông báo.');
    }

    /**
     * Đánh dấu đã xem feed khuyến mãi (gọi qua AJAX khi mở tab "Khuyến mãi"
     * trong chuông thông báo) — chỉ vào được khi đã đăng nhập (route auth).
     */
    public function markSeen(Request $request)
    {
        $user = auth()->user();
        $user->notifications_last_seen_at = now();
        $user->save();

        return response()->json(['success' => true]);
    }
}
