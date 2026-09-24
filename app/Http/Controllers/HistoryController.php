<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * Trang "Lịch sử của tôi": sản phẩm đã xem gần đây + sản phẩm đã mua,
     * chỉ tính cho user hiện tại (P1.1).
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // Sản phẩm đã xem gần đây: distinct theo sản phẩm, sắp theo lần xem
        // mới nhất giảm dần. Số dòng log của dự án này không lớn nên lấy
        // toàn bộ lượt xem đã sắp xếp rồi loại trùng ở tầng ứng dụng cho đơn
        // giản, thay vì viết subquery MAX(viewed_at) phức tạp.
        $recentlyViewed = ProductView::where('user_id', $userId)
            ->whereHas('product') // sản phẩm đã bị xoá cứng thì bỏ qua
            ->orderByDesc('viewed_at')
            ->with('product.variants')
            ->get()
            ->unique('product_id')
            ->take(20)
            ->pluck('product')
            ->values();

        // Sản phẩm đã mua: distinct theo sản phẩm, sắp theo đơn hàng gần
        // nhất. Chỉ lấy order_items có product_id còn tồn tại thật trong
        // bảng products (whereHas('product') — Product::withTrashed() nên
        // sản phẩm xoá mềm vẫn tính, chỉ sản phẩm xoá cứng mới bị loại).
        $purchasedProducts = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereHas('product')
            ->with(['product.variants', 'order'])
            ->get()
            ->sortByDesc(fn ($item) => $item->order->created_at)
            ->unique('product_id')
            ->take(20)
            ->pluck('product')
            ->values();

        return view('history.index', compact('recentlyViewed', 'purchasedProducts'));
    }
}
