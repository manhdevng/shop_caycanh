<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Review;
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

    /**
     * Trang "Sản phẩm đã mua": gom order_items của khách theo sản phẩm, mỗi
     * sản phẩm 1 dòng kèm lần mua gần nhất, số lần mua, đơn nguồn và cờ "chưa
     * đánh giá" để hiện nút Đánh giá / Mua lại.
     *
     * Chỉ tính đơn KHÔNG bị huỷ — sản phẩm trong đơn đã huỷ thì khách chưa
     * từng nhận, không coi là "đã mua".
     *
     * @return \Illuminate\View\View
     */
    public function purchased(Request $request)
    {
        $userId = $request->user()->id;

        $items = OrderItem::whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', '!=', 'cancelled');
            })
            ->whereHas('product')
            ->with(['product.variants', 'order'])
            ->get();

        // Sản phẩm khách đã tự đánh giá rồi -> không mời đánh giá lại nữa.
        $reviewedProductIds = Review::where('user_id', $userId)
            ->pluck('product_id')
            ->flip();

        $purchased = $items
            ->groupBy('product_id')
            ->map(function ($group) use ($reviewedProductIds) {
                // Đơn gần nhất chứa sản phẩm này (theo thời điểm đặt).
                $latestItem = $group->sortByDesc(fn (OrderItem $item) => $item->order->created_at)->first();
                $lastOrder = $latestItem->order;

                // Chỉ mời đánh giá khi đã NHẬN được hàng — cùng luật với
                // Order::canReview() và ReviewController@store.
                $deliveredOrder = $group
                    ->filter(fn (OrderItem $item) => $item->order->canReview())
                    ->sortByDesc(fn (OrderItem $item) => $item->order->created_at)
                    ->first();

                return [
                    'product' => $latestItem->product,
                    'last_order' => $lastOrder,
                    'last_purchased_at' => $lastOrder->created_at,
                    'times' => $group->count(),
                    'can_review' => $deliveredOrder !== null && ! $reviewedProductIds->has($latestItem->product_id),
                ];
            })
            ->sortByDesc('last_purchased_at')
            ->values();

        return view('history.purchased', compact('purchased'));
    }
}
