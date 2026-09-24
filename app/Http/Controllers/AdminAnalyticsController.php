<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    /**
     * Trang phân tích hành vi người dùng cho admin (P6.1).
     * Chỉ đọc dữ liệu (product_views, orders, order_items) - không ghi gì.
     */
    public function index()
    {
        $since30Days = Carbon::now()->subDays(30);

        // 1) Top 10 sản phẩm được xem nhiều nhất trong 30 ngày gần nhất.
        $topViewedRows = DB::table('product_views')
            ->join('products', 'products.id', '=', 'product_views.product_id')
            ->where('product_views.viewed_at', '>=', $since30Days)
            ->select('product_views.product_id')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('product_views.product_id')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        // Nạp model Product tương ứng (tránh N+1: 1 query duy nhất).
        $productsById = Product::withTrashed()
            ->whereIn('id', $topViewedRows->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $topViewedProducts = $topViewedRows->map(fn ($row) => (object) [
            'product' => $productsById->get($row->product_id),
            'views_count' => (int) $row->views_count,
        ])->filter(fn ($row) => $row->product !== null)->values();

        // 2) Lượt xem theo ngày, 30 ngày gần nhất, điền đủ ngày (kể cả 0 lượt xem)
        // - theo đúng style dailyRevenue()/charts() của AdminReportController.
        $dailyViewsRaw = DB::table('product_views')
            ->where('viewed_at', '>=', $since30Days)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views_count')
            ->groupByRaw('DATE(viewed_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $startDay = Carbon::now()->startOfDay()->subDays(29);
        $dailyViewsLabels = [];
        $dailyViewsData = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $dailyViewsLabels[] = $date;
            $dailyViewsData[] = (int) ($dailyViewsRaw->get($date)?->views_count ?? 0);
        }
        $dailyViews = [
            'labels' => $dailyViewsLabels,
            'data' => $dailyViewsData,
        ];

        // 3) Tỷ lệ chuyển đổi xem -> mua theo sản phẩm (chỉ sp có >= 3 lượt xem, mọi thời gian).
        $viewCounts = DB::table('product_views')
            ->select('product_id')
            ->selectRaw('COUNT(*) as view_count')
            ->groupBy('product_id')
            ->having('view_count', '>=', 3)
            ->get()
            ->keyBy('product_id');

        $purchaseCounts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['paid', 'cod_ordered'])
            ->select('order_items.product_id')
            ->selectRaw('COUNT(*) as purchase_count')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        $conversionProductIds = $viewCounts->keys();
        $conversionProductsById = Product::withTrashed()
            ->whereIn('id', $conversionProductIds)
            ->get()
            ->keyBy('id');

        $topConversionProducts = $viewCounts->map(function ($row, $productId) use ($purchaseCounts, $conversionProductsById) {
            $viewCount = (int) $row->view_count;
            $purchaseCount = (int) ($purchaseCounts->get($productId)?->purchase_count ?? 0);

            return (object) [
                'product' => $conversionProductsById->get($productId),
                'view_count' => $viewCount,
                'purchase_count' => $purchaseCount,
                'conversion_rate' => $viewCount > 0 ? $purchaseCount / $viewCount : 0,
            ];
        })
            ->filter(fn ($row) => $row->product !== null)
            ->sortByDesc('conversion_rate')
            ->take(10)
            ->values();

        // 4) Top 10 user hoạt động nhiều nhất (số lượt xem sản phẩm trong 30 ngày).
        $mostActiveUsers = DB::table('product_views')
            ->join('users', 'users.id', '=', 'product_views.user_id')
            ->where('product_views.viewed_at', '>=', $since30Days)
            ->select('users.id as user_id', 'users.name as user_name', 'users.email as user_email')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        return view('admin.analytics.index', compact(
            'topViewedProducts', 'dailyViews', 'topConversionProducts', 'mostActiveUsers'
        ));
    }
}
