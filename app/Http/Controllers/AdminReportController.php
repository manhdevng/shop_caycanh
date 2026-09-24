<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    private function paidOrders(): Builder
    {
        $paymentStatus = DB::table('payment_transactions')->select('status')
            ->whereColumn('order_id', 'orders.id')
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')->limit(1);

        return Order::query()->where('orders.created_at', '<=', now())
            ->where('orders.status', '!=', 'cancelled')
            // Loại cả đơn đã huỷ vận chuyển và MỌI trạng thái thuộc luồng hoàn hàng
            // GHN (returning, return_transporting, ...), không chỉ 'return'/'returned'.
            ->whereNotIn('orders.shipping_status', array_merge(['cancelled'], Order::SHIPPING_RETURN_STATUSES))
            ->where(function (Builder $query) use ($paymentStatus) {
                $query->where($paymentStatus, 'paid')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('paymentTransactions')
                            ->whereIn('orders.status', ['paid', 'cod_paid', 'paid_momo']);
                    });
            });
    }

    /**
     * Doanh thu theo nhóm danh mục gốc.
     *
     * Mỗi order_item chỉ được tính vào ĐÚNG MỘT nhóm: nhóm gốc (parent_id NULL)
     * có scope 'plant' hoặc 'flower' mà sản phẩm thuộc về (trực tiếp hoặc qua
     * danh mục con). Nhóm 'both' là tag lọc dùng chung nên bị bỏ qua. Nếu sản
     * phẩm thuộc nhiều nhóm gốc hợp lệ thì lấy nhóm có id nhỏ nhất để kết quả
     * ổn định. Trước đây join thẳng category_product nên sản phẩm gắn nhiều
     * danh mục bị cộng doanh thu nhiều lần (tổng biểu đồ tròn > doanh thu thật).
     *
     * Sản phẩm không thuộc nhóm plant/flower nào (hoặc đã bị xoá hẳn) được gom
     * vào mục "Chưa phân loại" (category_id = null) để tổng các dòng luôn bằng
     * tổng doanh thu order_items của đơn đã thanh toán.
     */
    private function categoryRevenue(): Collection
    {
        $productRoot = DB::table('category_product as cp')
            ->join('categories as c', 'c.id', '=', 'cp.category_id')
            ->join('categories as root', 'root.id', '=', DB::raw('COALESCE(c.parent_id, c.id)'))
            ->whereNull('root.parent_id')
            ->whereIn('root.scope', ['plant', 'flower'])
            ->select('cp.product_id')
            ->selectRaw('MIN(root.id) as root_id')
            ->groupBy('cp.product_id');

        return DB::table('order_items')
            ->leftJoinSub($productRoot, 'product_root', 'product_root.product_id', '=', 'order_items.product_id')
            ->leftJoin('categories as root_category', 'root_category.id', '=', 'product_root.root_id')
            ->whereIn('order_items.order_id', $this->paidOrders()->select('orders.id'))
            ->select('root_category.id as category_id', 'root_category.name as category_name')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue, SUM(order_items.quantity) as total_qty')
            ->groupBy('root_category.id', 'root_category.name')
            ->orderByDesc('total_revenue')->get();
    }

    private function topProducts(): Collection
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('order_items.order_id', $this->paidOrders()->select('orders.id'))
            ->select('products.id as product_id', 'products.name as product_name')
            ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.quantity * order_items.price) as total_revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)->get();
    }

    private function dailyRevenue(): Collection
    {
        return $this->paidOrders()
            ->selectRaw('DATE(orders.created_at) as date, SUM(total_price) as total_revenue, COUNT(*) as order_count')
            ->groupByRaw('DATE(orders.created_at)')->orderBy('date')->get();
    }

    private function periodRevenue(Collection $days, string $period): Collection
    {
        return $days->groupBy(fn ($day) => substr($day->date, 0, $period === 'month' ? 7 : 4))
            ->map(fn (Collection $rows, $key) => (object) [
                'period' => (string) $key,
                'total_revenue' => $rows->sum('total_revenue'),
                'order_count' => $rows->sum('order_count'),
            ])->values();
    }

    public function index()
    {
        $categoryRevenue = $this->categoryRevenue();
        $totalOrders = Order::where('created_at', '<=', now())->count();
        $totalCustomers = DB::table('users')->where('role', '!=', 'admin')->count();
        $revenueByDate = $this->dailyRevenue();
        $revenueByMonth = $this->periodRevenue($revenueByDate, 'month');
        $revenueByYear = $this->periodRevenue($revenueByDate, 'year');
        $totalRevenue = $revenueByDate->sum('total_revenue');
        $topProducts = $this->topProducts();

        return view('admin.reports.index', compact(
            'categoryRevenue', 'totalOrders', 'totalCustomers', 'totalRevenue',
            'revenueByDate', 'revenueByMonth', 'revenueByYear', 'topProducts'
        ));
    }

    public function charts()
    {
        $categories = $this->categoryRevenue();
        $catLabels = $categories->map(fn ($row) => $row->category_id === null
            ? 'Chưa phân loại'
            : ($row->category_name ?? 'Danh mục #'.$row->category_id))->all();
        $catRevenue = $categories->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $daily = $this->dailyRevenue();
        $byDate = $daily->keyBy('date');
        $byMonth = $this->periodRevenue($daily, 'month')->keyBy('period');
        $byYear = $this->periodRevenue($daily, 'year');
        $startDay = Carbon::now()->startOfDay()->subDays(29);
        $startMonth = Carbon::now()->startOfMonth()->subMonths(11);
        $revDateLabels = $revDateData = $revMonthLabels = $revMonthData = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $revDateLabels[] = $date;
            $revDateData[] = (float) ($byDate->get($date)?->total_revenue ?? 0);
        }
        for ($i = 0; $i < 12; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $revMonthLabels[] = $month->format('m/Y');
            $revMonthData[] = (float) ($byMonth->get($month->format('Y-m'))?->total_revenue ?? 0);
        }
        $revYearLabels = $byYear->pluck('period')->all();
        $revYearData = $byYear->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $gateway = DB::table('payment_transactions')->select('gateway')
            ->whereColumn('order_id', 'orders.id')->where('status', 'paid')->orderByDesc('id')->limit(1);
        $paid = $this->paidOrders()->select('orders.total_price')->selectSub($gateway, 'gateway')
            ->selectRaw("CASE WHEN orders.status = 'cod_paid' THEN 'cod' ELSE 'momo' END as legacy_gateway");
        $methodRevenue = DB::query()->fromSub($paid, 'paid_orders')
            ->selectRaw('COALESCE(gateway, legacy_gateway) as method, SUM(total_price) as revenue')
            ->groupByRaw('COALESCE(gateway, legacy_gateway)')->pluck('revenue', 'method');
        // Trước đây bỏ sót "bank_transfer" khỏi biểu đồ khiến doanh thu chuyển
        // khoản ngân hàng biến mất hoàn toàn dù vẫn được tính trong tổng
        // doanh thu ở trang bảng số liệu — nay liệt kê đủ cả 3 phương thức.
        $paymentMethodLabels = ['MoMo', 'COD', 'Chuyển khoản ngân hàng'];
        $paymentMethodRevenue = [
            (float) $methodRevenue->get('momo', 0),
            (float) $methodRevenue->get('cod', 0),
            (float) $methodRevenue->get('bank_transfer', 0),
        ];
        $topProducts = $this->topProducts();

        return view('admin.reports.charts', compact(
            'catLabels', 'catRevenue', 'revDateLabels', 'revDateData',
            'revMonthLabels', 'revMonthData', 'revYearLabels', 'revYearData',
            'paymentMethodLabels', 'paymentMethodRevenue', 'topProducts'
        ));
    }
}
