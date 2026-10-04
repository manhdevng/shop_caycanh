<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\SalesMetricService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * T7: theo dõi tồn kho — hết hàng, sắp hết, lâu chưa bán (route
 * admin.inventory.index, GET /admin/inventory).
 *
 * "Đã bán" lấy từ SalesMetricService (đơn đã thu tiền; không tính COD mới
 * đặt, đơn huỷ, hoàn tiền/hoàn hàng). Ba danh sách tách bạch: hàng hết không
 * bị tính vào "lâu chưa bán", hàng mới nhập chưa đủ số ngày ngưỡng cũng không.
 */
class AdminInventoryController extends Controller
{
    public const TABS = [
        'out' => 'Hết hàng',
        'low' => 'Sắp hết',
        'slow' => 'Lâu chưa bán',
    ];

    public const DEFAULT_LOW_THRESHOLD = 5;

    public const DEFAULT_SLOW_DAYS = 60;

    public function index(Request $request, SalesMetricService $sales)
    {
        // Trang GET: tham số sai không redirect (dễ vòng lặp/mất ngữ cảnh) mà
        // bỏ đúng tham số sai, dùng mặc định và báo lỗi ngay trên trang.
        $validator = Validator::make($request->query(), [
            'tab' => ['nullable', 'in:'.implode(',', array_keys(self::TABS))],
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(Product::TYPES))],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'low' => ['nullable', 'integer', 'min:1', 'max:100'],
            'days' => ['nullable', 'integer', 'min:7', 'max:365'],
        ], [
            'low.min' => 'Ngưỡng sắp hết phải từ 1 đến 100.',
            'low.max' => 'Ngưỡng sắp hết phải từ 1 đến 100.',
            'low.integer' => 'Ngưỡng sắp hết phải là số nguyên.',
            'days.min' => 'Số ngày chưa bán phải từ 7 đến 365.',
            'days.max' => 'Số ngày chưa bán phải từ 7 đến 365.',
            'days.integer' => 'Số ngày chưa bán phải là số nguyên.',
        ]);
        $validated = Arr::except($validator->valid(), array_keys($validator->errors()->messages()));

        $filters = [
            'tab' => $validated['tab'] ?? 'out',
            'q' => trim((string) ($validated['q'] ?? '')),
            'type' => $validated['type'] ?? null,
            'category' => isset($validated['category']) ? (int) $validated['category'] : null,
            'low' => (int) ($validated['low'] ?? self::DEFAULT_LOW_THRESHOLD),
            'days' => (int) ($validated['days'] ?? self::DEFAULT_SLOW_DAYS),
        ];

        // Mốc "lâu chưa bán": sản phẩm tạo trước mốc này và không có lần bán
        // hợp lệ nào từ mốc này tới nay. Số bán trong kỳ cũng tính từ mốc này.
        $since = now()->subDays($filters['days']);

        $counts = [];
        foreach (array_keys(self::TABS) as $tab) {
            $counts[$tab] = $this->tabQuery($this->baseQuery($filters), $tab, $filters, $since, $sales)->count();
        }

        $query = $this->tabQuery($this->baseQuery($filters), $filters['tab'], $filters, $since, $sales);

        // Lần bán gần nhất (mọi thời điểm) + số lượng bán trong kỳ — gom sẵn
        // bằng subquery nối vào, không truy vấn lại cho từng dòng.
        $products = $query
            ->leftJoinSub($this->lastSaleSub($sales), 'last_sale', 'last_sale.product_id', '=', 'products.id')
            ->leftJoinSub($sales->productSales($since), 'period_sale', 'period_sale.product_id', '=', 'products.id')
            ->select('products.*', 'last_sale.last_sold_at', 'period_sale.total_qty as period_qty')
            ->with('categories')
            ->tap(fn (Builder $q) => $this->applyOrder($q, $filters['tab']))
            ->paginate(20)
            ->withQueryString();

        $categories = Category::whereNull('parent_id')->with('children')->ordered()->get();

        return view('admin.inventory.index', [
            'products' => $products,
            'filters' => $filters,
            'counts' => $counts,
            'categories' => $categories,
            'since' => $since,
            'tabs' => self::TABS,
            'filterErrors' => $validator->errors(),
        ]);
    }

    /** Sản phẩm đang bán (is_active) theo bộ lọc tên/loại/danh mục. */
    private function baseQuery(array $filters): Builder
    {
        return Product::query()
            ->where('products.is_active', true)
            ->when($filters['q'] !== '', fn ($q) => $q->where('products.name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['type'], fn ($q) => $q->where('products.product_type', $filters['type']))
            ->when($filters['category'], fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->where('categories.id', $filters['category'])
            ));
    }

    private function tabQuery(Builder $query, string $tab, array $filters, $since, SalesMetricService $sales): Builder
    {
        return match ($tab) {
            'out' => $query->where('products.stock', '<=', 0),
            'low' => $query->where('products.stock', '>', 0)->where('products.stock', '<=', $filters['low']),
            'slow' => $query->where('products.stock', '>', 0)
                ->where('products.created_at', '<=', $since)
                ->whereNotIn('products.id', $sales->productSales($since)->select('order_items.product_id')),
        };
    }

    /** product_id, last_sold_at: ngày tạo đơn đã bán gần nhất của từng sản phẩm. */
    private function lastSaleSub(SalesMetricService $sales)
    {
        return $sales->qualifiedOrders()
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id')
            ->selectRaw('MAX(orders.created_at) as last_sold_at')
            ->toBase();
    }

    private function applyOrder(Builder $query, string $tab): void
    {
        match ($tab) {
            'low' => $query->orderBy('products.stock')->orderBy('products.name'),
            // Chưa từng bán lên đầu, rồi tới hàng bán lần cuối lâu nhất.
            'slow' => $query->orderByRaw('CASE WHEN last_sale.last_sold_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('last_sale.last_sold_at')
                ->orderBy('products.created_at'),
            default => $query->orderBy('products.name'),
        };

        $query->orderBy('products.id');
    }
}
