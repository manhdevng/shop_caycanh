<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductView;
use App\Services\SalesMetricService;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Phân tích hành vi (admin, chỉ đọc): bảng Hiệu quả sản phẩm đặt lượt xem cạnh
 * doanh số cùng kỳ. Không tính "tỷ lệ chuyển đổi" vì lượt xem (người đăng nhập
 * + phiên khách) và đơn mua không cùng tập đối tượng.
 */
class AdminAnalyticsController extends Controller
{
    public const RANGES = ['7' => '7 ngày', '30' => '30 ngày', '90' => '90 ngày', 'custom' => 'Khoảng ngày'];

    public const SORTS = [
        'views' => 'Lượt xem nhiều nhất',
        'revenue' => 'Doanh thu cao nhất',
        'qty' => 'Bán nhiều nhất',
        'stock_asc' => 'Tồn kho thấp nhất',
        'stock_desc' => 'Tồn kho cao nhất',
    ];

    public const ACTIVITIES = [
        '' => 'Tất cả sản phẩm',
        'viewed' => 'Có lượt xem',
        'viewed_unsold' => 'Có xem nhưng chưa bán',
        'sold' => 'Đã bán',
        'no_views' => 'Chưa có lượt xem',
    ];

    private const MAX_CUSTOM_DAYS = 366;

    private const PER_PAGE = 20;

    public function __construct(private readonly SalesMetricService $sales)
    {
    }

    /** @return array{0: array, 1: ReportPeriod, 2: \Illuminate\Support\MessageBag} */
    private function filters(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'date_from' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['plant', 'flower'])],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'activity' => ['nullable', Rule::in(array_keys(self::ACTIVITIES))],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'date_from.required_if' => 'Vui lòng chọn ngày bắt đầu.',
            'date_to.required_if' => 'Vui lòng chọn ngày kết thúc.',
            'date_to.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            '*.date_format' => 'Ngày không hợp lệ (định dạng năm-tháng-ngày).',
            'category.exists' => 'Danh mục không tồn tại.',
            '*.in' => 'Giá trị bộ lọc không hợp lệ.',
        ]);
        $validator->after(function ($validator) use ($request) {
            if ($request->query('range') !== 'custom' || $validator->errors()->isNotEmpty()) {
                return;
            }
            $days = (int) CarbonImmutable::parse($request->query('date_from'))->diffInDays(CarbonImmutable::parse($request->query('date_to'))) + 1;
            if ($days > self::MAX_CUSTOM_DAYS) {
                $validator->errors()->add('date_to', 'Khoảng ngày tối đa '.self::MAX_CUSTOM_DAYS.' ngày.');
            }
        });

        // Tham số sai: hiển thị lỗi và quay về mặc định, không redirect vòng trên trang GET.
        $errors = $validator->errors();
        $filters = $validator->fails() ? [] : array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '');
        $filters['range'] ??= '30';
        $filters['sort'] ??= 'views';

        if ($filters['range'] === 'custom') {
            $period = ReportPeriod::make('custom', $filters);
        } else {
            $today = CarbonImmutable::now()->startOfDay();
            $period = ReportPeriod::make('custom', [
                'date_from' => $today->subDays((int) $filters['range'] - 1)->toDateString(),
                'date_to' => $today->toDateString(),
            ]);
            unset($filters['date_from'], $filters['date_to']);
        }

        return [$filters, $period, $errors];
    }

    public function index(Request $request)
    {
        [$filters, $period, $filterErrors] = $this->filters($request);
        $from = $period->from;
        $until = $period->untilExclusive;

        // Lượt xem trong kỳ, tổng hợp ở DB theo sản phẩm.
        $views = DB::table('product_views')
            ->where('viewed_at', '>=', $from)->where('viewed_at', '<', $until)
            ->select('product_id')
            ->selectRaw('COUNT(*) as view_count')
            ->selectRaw('COUNT(DISTINCT viewer_key) as viewer_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN user_id IS NULL THEN viewer_key END) as guest_viewer_count')
            ->groupBy('product_id');

        // Doanh số cùng kỳ theo orders.created_at, đúng quy tắc SalesMetricService.
        $sales = DB::table('order_items')
            ->whereIn('order_id', $this->sales->qualifiedOrderIds($from, $until))
            ->select('product_id')
            ->selectRaw('COUNT(DISTINCT order_id) as order_count')
            ->selectRaw('SUM(quantity) as sold_qty')
            ->selectRaw('SUM(price * quantity) as revenue')
            ->groupBy('product_id');

        $query = DB::table('products')
            ->leftJoinSub($views, 'v', 'v.product_id', '=', 'products.id')
            ->leftJoinSub($sales, 's', 's.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', 'products.product_type', 'products.stock', 'products.is_active', 'products.deleted_at')
            ->selectRaw('COALESCE(v.view_count, 0) as view_count, COALESCE(v.viewer_count, 0) as viewer_count, COALESCE(v.guest_viewer_count, 0) as guest_viewer_count')
            ->selectRaw('COALESCE(s.order_count, 0) as order_count, COALESCE(s.sold_qty, 0) as sold_qty, COALESCE(s.revenue, 0) as revenue')
            // Sản phẩm đã xoá mềm chỉ hiện khi có hoạt động trong kỳ.
            ->where(fn ($query) => $query->whereNull('products.deleted_at')
                ->orWhereNotNull('v.product_id')->orWhereNotNull('s.product_id'));

        if (isset($filters['q'])) {
            $query->where('products.name', 'like', '%'.trim($filters['q']).'%');
        }
        if (isset($filters['type'])) {
            $query->where('products.product_type', $filters['type']);
        }
        if (isset($filters['category'])) {
            $categoryIds = Category::where('id', $filters['category'])->orWhere('parent_id', $filters['category'])->pluck('id');
            $query->whereExists(fn ($sub) => $sub->from('category_product')
                ->whereColumn('category_product.product_id', 'products.id')
                ->whereIn('category_product.category_id', $categoryIds));
        }
        match ($filters['activity'] ?? '') {
            'viewed' => $query->whereNotNull('v.product_id'),
            'viewed_unsold' => $query->whereNotNull('v.product_id')->whereNull('s.product_id'),
            'sold' => $query->whereNotNull('s.product_id'),
            'no_views' => $query->whereNull('v.product_id'),
            default => null,
        };
        match ($filters['sort']) {
            'revenue' => $query->orderByDesc('revenue')->orderByDesc('view_count'),
            'qty' => $query->orderByDesc('sold_qty')->orderByDesc('revenue'),
            'stock_asc' => $query->orderBy('products.stock'),
            'stock_desc' => $query->orderByDesc('products.stock'),
            default => $query->orderByDesc('view_count')->orderByDesc('viewer_count'),
        };
        $rows = $query->orderBy('products.id')->paginate(self::PER_PAGE)->withQueryString();

        // Danh mục của các sản phẩm trên trang hiện tại (một truy vấn).
        $categoryNames = DB::table('category_product')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereIn('category_product.product_id', $rows->pluck('id'))
            ->orderBy('categories.name')
            ->get(['category_product.product_id', 'categories.name'])
            ->groupBy('product_id')
            ->map(fn ($items) => $items->pluck('name')->all());

        $periodViews = DB::table('product_views')->where('viewed_at', '>=', $from)->where('viewed_at', '<', $until);
        $summary = (clone $periodViews)
            ->selectRaw('COUNT(*) as view_count, COUNT(DISTINCT viewer_key) as viewer_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN user_id IS NULL THEN viewer_key END) as guest_viewer_count')
            ->selectRaw('SUM(CASE WHEN tracking_version = ? THEN 1 ELSE 0 END) as legacy_view_count', [ProductView::TRACKING_LEGACY])
            ->first();
        $trackingSince = DB::table('product_views')->where('tracking_version', ProductView::TRACKING_DEDUPED)->min('viewed_at');

        // Khách hoạt động nhiều nhất cùng kỳ (chỉ tài khoản đăng nhập; khu vực admin).
        $mostActiveUsers = (clone $periodViews)
            ->join('users', 'users.id', '=', 'product_views.user_id')
            ->select('users.id as user_id', 'users.name as user_name', 'users.email as user_email')
            ->selectRaw('COUNT(*) as views_count, COUNT(DISTINCT product_views.product_id) as product_count')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('views_count')->orderBy('users.id')
            ->limit(10)->get();

        return view('admin.analytics.index', [
            'rows' => $rows,
            'categoryNames' => $categoryNames,
            'filters' => $filters,
            'filterErrors' => $filterErrors,
            'period' => $period,
            'summary' => $summary,
            'trackingSince' => $trackingSince ? CarbonImmutable::parse($trackingSince) : null,
            'mostActiveUsers' => $mostActiveUsers,
            'categories' => Category::orderBy('parent_id')->orderBy('name')->get(['id', 'name', 'parent_id']),
            'ranges' => self::RANGES,
            'sorts' => self::SORTS,
            'activities' => self::ACTIVITIES,
        ]);
    }
}
