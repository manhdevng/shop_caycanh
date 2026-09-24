<?php

namespace App\Http\Controllers;

use App\Support\NotificationFeed;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Trang "Thông báo" đầy đủ — hiển thị feed gộp từ bài viết, voucher,
     * sản phẩm mới. Xem trang này coi như đã đọc hết, nên cập nhật luôn
     * mốc notifications_last_seen_at cho user (nếu đã đăng nhập).
     */
    public function index()
    {
        $items = NotificationFeed::recentItems(30);

        if (auth()->check()) {
            $user = auth()->user();
            $user->notifications_last_seen_at = now();
            $user->save();
        }

        return view('notifications.index', compact('items'));
    }

    /**
     * Đánh dấu đã xem toàn bộ thông báo (gọi qua AJAX khi mở chuông
     * thông báo trên header) — chỉ vào được khi đã đăng nhập (route auth).
     */
    public function markSeen(Request $request)
    {
        $user = auth()->user();
        $user->notifications_last_seen_at = now();
        $user->save();

        return response()->json(['success' => true]);
    }
}
