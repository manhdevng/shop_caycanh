<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SalesMetricService;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo doanh thu. Mọi số liệu trên cả hai tab (bảng số liệu, biểu đồ) dùng
 * CÙNG một kỳ lọc ReportPeriod và CÙNG quy tắc đơn đã bán SalesMetricService.
 */
class AdminReportController extends Controller
{
    /** Kỳ dài hơn số ngày này thì biểu đồ theo ngày được thay bằng ghi chú. */
    private const MAX_DAILY_POINTS = 366;

    public function __construct(private readonly SalesMetricService $sales)
    {
    }

    private function paidOrderIds(ReportPeriod $period)
    {
        return $this->sales->qualifiedOrderIds($period->from, $period->untilExclusive);
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
    private function categoryRevenue(ReportPeriod $period): Collection
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
            ->whereIn('order_items.order_id', $this->paidOrderIds($period))
            ->select('root_category.id as category_id', 'root_category.name as category_name')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue, SUM(order_items.quantity) as total_qty')
            ->groupBy('root_category.id', 'root_category.name')
            ->orderByDesc('total_revenue')->get();
    }

    private function topProducts(ReportPeriod $period): Collection
    {
        return DB::query()
            ->fromSub($this->sales->productSales($period->from, $period->untilExclusive), 'sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->select('products.id as product_id', 'products.name as product_name', 'sales.total_qty', 'sales.total_revenue')
            ->orderByDesc('sales.total_qty')->orderByDesc('sales.total_revenue')->orderBy('products.id')
            ->limit(10)->get();
    }

    private function dailyRevenue(ReportPeriod $period): Collection
    {
        return $this->sales->qualifiedOrders($period->from, $period->untilExclusive)
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

    /**
     * Khoảng vẽ biểu đồ [start, endExclusive): theo kỳ lọc, cắt ở hết hôm nay
     * (không vẽ ngày tương lai). Kỳ "toàn thời gian" bắt đầu từ ngày có doanh
     * thu đầu tiên. Trả null khi không có gì để vẽ.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function chartRange(ReportPeriod $period, Collection $daily): ?array
    {
        $tomorrow = CarbonImmutable::now()->startOfDay()->addDay();
        $start = $period->from ?? ($daily->isEmpty() ? null : CarbonImmutable::parse($daily->first()->date)->startOfDay());
        $end = $period->untilExclusive && $period->untilExclusive->lessThan($tomorrow) ? $period->untilExclusive : $tomorrow;

        return $start && $start->lessThan($end) ? [$start, $end] : null;
    }

    public function index(Request $request)
    {
        $period = ReportPeriod::fromRequest($request);

        $categoryRevenue = $this->categoryRevenue($period);
        $totalOrders = Order::query()->where('created_at', '<=', now())
            ->when($period->from, fn ($query) => $query->where('created_at', '>=', $period->from))
            ->when($period->untilExclusive, fn ($query) => $query->where('created_at', '<', $period->untilExclusive))
            ->count();
        // Kỳ "toàn thời gian" hiển thị tổng khách; kỳ có giới hạn hiển thị khách đăng ký mới trong kỳ.
        $totalCustomers = DB::table('users')->where('role', '!=', 'admin')
            ->when($period->from, fn ($query) => $query->where('created_at', '>=', $period->from))
            ->when($period->untilExclusive, fn ($query) => $query->where('created_at', '<', $period->untilExclusive))
            ->count();
        $revenueByDate = $this->dailyRevenue($period);
        $revenueByMonth = $this->periodRevenue($revenueByDate, 'month');
        $revenueByYear = $this->periodRevenue($revenueByDate, 'year');
        $totalRevenue = $revenueByDate->sum('total_revenue');
        $paidOrderCount = $revenueByDate->sum('order_count');
        $topProducts = $this->topProducts($period);

        return view('admin.reports.index', compact(
            'period', 'categoryRevenue', 'totalOrders', 'totalCustomers', 'totalRevenue', 'paidOrderCount',
            'revenueByDate', 'revenueByMonth', 'revenueByYear', 'topProducts'
        ));
    }

    public function charts(Request $request)
    {
        $period = ReportPeriod::fromRequest($request);

        $categories = $this->categoryRevenue($period);
        $catLabels = $categories->map(fn ($row) => $row->category_id === null
            ? 'Chưa phân loại'
            : ($row->category_name ?? 'Danh mục #'.$row->category_id))->all();
        $catRevenue = $categories->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $daily = $this->dailyRevenue($period);
        $byDate = $daily->keyBy('date');
        $byMonth = $this->periodRevenue($daily, 'month')->keyBy('period');
        $byYear = $this->periodRevenue($daily, 'year')->keyBy('period');
        $revDateLabels = $revDateData = $revMonthLabels = $revMonthData = $revYearLabels = $revYearData = [];
        $dailyTooLong = false;

        if ($range = $this->chartRange($period, $daily)) {
            [$start, $end] = $range;
            $dailyTooLong = $start->diffInDays($end) > self::MAX_DAILY_POINTS;
            if (! $dailyTooLong) {
                for ($day = $start; $day->lessThan($end); $day = $day->addDay()) {
                    $revDateLabels[] = $day->format('d/m/Y');
                    $revDateData[] = (float) ($byDate->get($day->toDateString())?->total_revenue ?? 0);
                }
            }
            for ($month = $start->startOfMonth(); $month->lessThan($end); $month = $month->addMonthNoOverflow()) {
                $revMonthLabels[] = $month->format('m/Y');
                $revMonthData[] = (float) ($byMonth->get($month->format('Y-m'))?->total_revenue ?? 0);
            }
            for ($year = $start->year; $year <= $end->subDay()->year; $year++) {
                $revYearLabels[] = (string) $year;
                $revYearData[] = (float) ($byYear->get((string) $year)?->total_revenue ?? 0);
            }
        }

        $gateway = DB::table('payment_transactions')->select('gateway')
            ->whereColumn('order_id', 'orders.id')->where('status', 'paid')->orderByDesc('id')->limit(1);
        $paid = $this->sales->qualifiedOrders($period->from, $period->untilExclusive)
            ->select('orders.total_price')->selectSub($gateway, 'gateway')
            ->selectRaw("CASE WHEN orders.status = 'cod_paid' THEN 'cod' ELSE 'momo' END as legacy_gateway");
        $methodRevenue = DB::query()->fromSub($paid, 'paid_orders')
            ->selectRaw('COALESCE(gateway, legacy_gateway) as method, SUM(total_price) as revenue')
            ->groupByRaw('COALESCE(gateway, legacy_gateway)')->pluck('revenue', 'method');
        // Liệt kê đủ cả 3 phương thức để doanh thu chuyển khoản không biến mất khỏi biểu đồ.
        $paymentMethodLabels = ['MoMo', 'COD', 'Chuyển khoản ngân hàng'];
        $paymentMethodRevenue = [
            (float) $methodRevenue->get('momo', 0),
            (float) $methodRevenue->get('cod', 0),
            (float) $methodRevenue->get('bank_transfer', 0),
        ];
        $totalRevenue = (float) $daily->sum('total_revenue');
        $topProducts = $this->topProducts($period);

        return view('admin.reports.charts', compact(
            'period', 'totalRevenue', 'catLabels', 'catRevenue', 'revDateLabels', 'revDateData', 'dailyTooLong',
            'revMonthLabels', 'revMonthData', 'revYearLabels', 'revYearData',
            'paymentMethodLabels', 'paymentMethodRevenue', 'topProducts'
        ));
    }
}
