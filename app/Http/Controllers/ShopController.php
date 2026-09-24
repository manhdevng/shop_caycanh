<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Product;
use App\Models\Category;
use App\Models\HomeFeature;
use App\Models\Order;
use App\Models\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)->with(['categories', 'variants']);

        $categoryIds = array_filter($request->input('categories', []));
        if (!empty($categoryIds)) {
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Lọc theo loại sản phẩm Cây/Hoa qua cột product_type (D1, F5). Dùng
        // chung route shop.index hiện có, không thêm route mới.
        $type = $request->input('type');
        if (in_array($type, ['plant', 'flower'], true)) {
            $query->where('product_type', $type);
        }

        $search = trim((string) $request->input('q'));
        if ($search !== '') {
            // C1.5: mở rộng tìm kiếm ngoài tên/mô tả sản phẩm sang: nhãn phân
            // loại (product_variants.variant_name), tên danh mục, và loại cây/hoa (map từ
            // khóa tiếng Việt sang products.product_type). Dùng orWhereHas
            // (EXISTS) thay vì join để không nhân bản dòng kết quả. Toàn bộ
            // vẫn nằm trong 1 closure -> giữ nguyên AND với các bộ lọc khác
            // (giá, danh mục, loại, ...).
            $searchLower = mb_strtolower($search);
            $query->where(function ($q) use ($search, $searchLower) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('variants', function ($v) use ($search) {
                        // Bảng product_variants dùng cột variant_name (không
                        // có cột "label") để lưu nhãn phân loại, vd "Chậu S".
                        $v->where('variant_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('categories', function ($c) use ($search) {
                        $c->where('categories.name', 'like', '%' . $search . '%');
                    });

                if (str_contains($searchLower, 'cây') || str_contains($searchLower, 'cay')) {
                    $q->orWhere('product_type', 'plant');
                }
                if (str_contains($searchLower, 'hoa')) {
                    $q->orWhere('product_type', 'flower');
                }
            });
        }

        // Lọc theo khoảng giá HIỂN THỊ THỰC TẾ trên thẻ sản phẩm (khớp với
        // $priceLineFor trong resources/views/shop/index.blade.php):
        //   - Sản phẩm có >=2 phân loại giá khác nhau -> giá thấp nhất trong
        //     các phân loại (products.variants.min('price')), hiển thị "Từ X₫".
        //   - Ngược lại -> products.base_price.
        // Dùng subquery tương quan để tính đúng giá này ngay trong SQL, tránh
        // lọc sai trên base_price khi giá thật đến từ variant.
        $effectivePriceExpr = '(CASE WHEN ('
            . 'SELECT COUNT(DISTINCT pv1.price) FROM product_variants pv1 WHERE pv1.product_id = products.id'
            . ') >= 2 THEN ('
            . 'SELECT MIN(pv2.price) FROM product_variants pv2 WHERE pv2.product_id = products.id'
            . ') ELSE products.base_price END)';

        $priceFilters = $request->validate([
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
        ], [
            'price_min.numeric' => 'Giá tối thiểu không hợp lệ.',
            'price_max.numeric' => 'Giá tối đa không hợp lệ.',
        ]);

        if ($request->filled('price_min')) {
            $query->whereRaw($effectivePriceExpr . ' >= ?', [$priceFilters['price_min']]);
        }
        if ($request->filled('price_max')) {
            $query->whereRaw($effectivePriceExpr . ' <= ?', [$priceFilters['price_max']]);
        }

        // Sắp xếp hiển thị (chỉ ảnh hưởng thứ tự, không đổi tập kết quả)
        $sort = $request->input('sort', 'featured');
        match ($sort) {
            'price-asc' => $query->orderBy('base_price', 'asc'),
            'price-desc' => $query->orderBy('base_price', 'desc'),
            'name-asc' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $activeCategories = Category::whereIn('id', $categoryIds)->get();

        // Toàn bộ nhóm danh mục (kèm số sản phẩm mỗi danh mục con) — dùng cho
        // dải "Danh mục nổi bật" và sidebar bộ lọc ở trang khách hàng.
        $categoryGroups = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount(['products' => function ($q) {
                    $q->where('is_active', true);
                }]);
            }])
            ->ordered()
            ->get();

        $bestSellerIds = $this->bestSellerIds();

        // Chỉ hiện khối "nổi bật" (banner + cây mới nhập + hoa mới nhập) ở trang
        // mặc định, không hiện khi đang lọc theo danh mục, tìm kiếm, hoặc đang
        // lọc theo loại Cây/Hoa (?type=) — nếu không, link "Tất cả" ở menu
        // header sẽ không lọc được gì vì trang chủ luôn hiện đủ cả 2 hàng.
        // Sau khi validate price_min/price_max thất bại, Laravel redirect về
        // URL trước đó (thường không còn query string) và chỉ flash lỗi vào
        // session — nếu không kiểm tra thêm ở đây, trang sẽ quay lại nhánh
        // "nổi bật" (không có form lọc giá) nên lỗi không bao giờ hiển thị được.
        $priceErrorBag = session('errors');
        $hasPriceError = $priceErrorBag
            && $priceErrorBag->any()
            && ($priceErrorBag->has('price_min') || $priceErrorBag->has('price_max'));

        $showFeatured = $activeCategories->isEmpty()
            && $search === ''
            && $type === null
            && !$request->filled('price_min')
            && !$request->filled('price_max')
            && !$hasPriceError;
        $featuredProduct = null;
        $newestPlants = collect();
        $newestFlowers = collect();
        $homeFeatures = collect();
        $homeBestSellers = collect();
        $homeSoldCounts = [];

        if ($showFeatured) {
            $featuredProduct = Product::where('is_active', true)
                ->whereNotNull('main_image')
                ->with(['categories', 'variants'])
                ->latest()
                ->first();

            // Tách hàng Cây/Hoa bằng cột products.product_type — KHÔNG còn tìm
            // theo tên nhóm danh mục (F5): đổi tên nhóm không còn làm hỏng
            // trang chủ. Xem D1.
            $newestPlants = Product::where('is_active', true)
                ->plants()
                ->when($featuredProduct, fn ($q) => $q->where('id', '!=', $featuredProduct->id))
                ->with(['categories', 'variants'])
                ->latest()
                ->take(4)
                ->get();

            $newestFlowers = Product::where('is_active', true)
                ->flowers()
                ->when($featuredProduct, fn ($q) => $q->where('id', '!=', $featuredProduct->id))
                ->with(['categories', 'variants'])
                ->latest()
                ->take(4)
                ->get();

            // Ảnh/video khối giới thiệu (Cây được tuyển chọn / Dịch vụ tận tâm /
            // Chất liệu cao cấp) — quản trị viên tự đổi ở /admin/settings.
            $homeFeatures = HomeFeature::orderBy('id')->get()->keyBy('slug');

            // Khối "Bán chạy" ở trang chủ: dùng lại $bestSellerIds đã tính sẵn
            // ở trên (top 8, cùng điều kiện với nhãn "Bán chạy" trên mỗi thẻ
            // sản phẩm) thay vì gọi lại bestSellerIds() — tránh chạy trùng
            // một query tổng hợp order_items/orders lần thứ hai trong cùng
            // một request. orderByRaw('FIELD(id, ...)') sẽ lỗi cú pháp SQL nếu
            // danh sách id rỗng, nên chỉ query khi $bestSellerIds không rỗng.
            if (!empty($bestSellerIds)) {
                $homeBestSellers = Product::where('is_active', true)
                    ->whereIn('id', $bestSellerIds)
                    ->with(['categories', 'variants'])
                    ->orderByRaw('FIELD(id, ' . implode(',', $bestSellerIds) . ')')
                    ->take(4)
                    ->get();
            }

            // Chỉ tính số lượng đã bán cho ĐÚNG các sản phẩm thật sự hiển thị
            // ở trang chủ (tối đa 4), không phải cả 8 id trong $bestSellerIds
            // — vì $bestSellerIds có thể chứa id sản phẩm đã bị tắt is_active
            // hoặc đã xoá (không nằm trong $homeBestSellers), tính thừa cho
            // các id đó là lãng phí và không dùng tới.
            $homeSoldCounts = $this->soldCountsFor($homeBestSellers->pluck('id')->all());
        }

        // FAQ hiển thị ở trang chủ (nhóm "general") — P3.1.
        $faqs = Faq::published()
            ->placement(Faq::PLACEMENT_GENERAL)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('shop.index', compact(
            'products',
            'activeCategories',
            'categoryGroups',
            'search',
            'sort',
            'type',
            'bestSellerIds',
            'showFeatured',
            'featuredProduct',
            'newestPlants',
            'newestFlowers',
            'homeFeatures',
            'homeBestSellers',
            'homeSoldCounts',
            'faqs'
        ));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        // Ghi lại lượt xem cho user đã đăng nhập — dùng để hiển thị "Sản
        // phẩm đã xem gần đây" (P1.1). Khách chưa đăng nhập không ghi log.
        // Bọc try/catch để lỗi ghi log (nếu có) không làm vỡ trang xem sản
        // phẩm — thao tác insert cơ bản, không kỳ vọng lỗi xảy ra.
        if (auth()->check()) {
            try {
                ProductView::create([
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                ]);
            } catch (\Throwable $e) {
                // Bỏ qua lỗi ghi log lượt xem, không ảnh hưởng trải nghiệm xem sản phẩm.
            }
        }

        $product->load(['categories', 'variants']);

        $reviews = $product->reviews()->with('user')->latest()->get();
        $reviewsCount = $reviews->count();
        $averageRating = $reviewsCount > 0 ? round($reviews->avg('rating'), 1) : null;
        $myReview = auth()->check()
            ? $reviews->firstWhere('user_id', auth()->id())
            : null;

        // Chỉ cho phép hiện form đánh giá khi khách đã mua sản phẩm này thành
        // công (điều kiện phải khớp với ReviewController::store()).
        $canReview = auth()->check()
            && Order::query()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['paid', 'cod_ordered'])
                ->whereHas('items', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->exists();

        $bestSellerIds = $this->bestSellerIds();

        // Mã giảm giá áp dụng được cho sản phẩm này (toàn shop + gắn trực
        // tiếp + gắn qua danh mục nó thuộc về) — hiển thị ngay tại trang chi
        // tiết sản phẩm để khách biết mà "săn mã".
        $productVouchers = $product->availableVouchers();

        $relatedLimit = 4;
        $categoryIds = $product->categories->pluck('id');

        // Sản phẩm liên quan mặc định (khách chưa đăng nhập, hoặc dùng làm
        // fallback/lấp đầy cho gợi ý cá nhân hóa bên dưới): cùng danh mục với
        // sản phẩm đang xem, còn hoạt động, khác sản phẩm hiện tại.
        $sameCategoryQuery = function () use ($product, $categoryIds) {
            return Product::where('is_active', true)
                ->where('id', '!=', $product->id)
                ->when($categoryIds->isNotEmpty(), function ($q) use ($categoryIds) {
                    $q->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds);
                    });
                })
                ->with('variants')
                ->latest();
        };

        if (!auth()->check()) {
            // Khách chưa đăng nhập: giữ nguyên logic cũ.
            $relatedProducts = $sameCategoryQuery()->take($relatedLimit)->get();
        } else {
            // P1.2: gợi ý cá nhân hóa dựa trên danh mục mà user quan tâm
            // nhiều nhất, tổng hợp từ lịch sử xem (P1.1) và lịch sử mua.
            $userId = auth()->id();

            // 30 sản phẩm được xem gần đây nhất -> danh mục tương ứng. Loại
            // trừ chính sản phẩm đang xem (vừa được ghi log ở trên) để danh
            // mục của nó không áp đảo kết quả gợi ý — nếu không, xem bất kỳ
            // sản phẩm nào cũng tự động "thích" luôn danh mục của chính nó.
            $recentlyViewedProductIds = DB::table('product_views')
                ->where('user_id', $userId)
                ->where('product_id', '!=', $product->id)
                ->orderByDesc('viewed_at')
                ->orderByDesc('id')
                ->limit(30)
                ->pluck('product_id');

            $viewedCategoryIds = DB::table('category_product')
                ->whereIn('product_id', $recentlyViewedProductIds)
                ->pluck('category_id');

            // Toàn bộ sản phẩm đã mua thành công (qua các đơn hàng của user)
            // -> danh mục. Chỉ tính đơn đã thật sự mua (paid, cod_ordered),
            // khớp điều kiện "đã mua" đang dùng ở $canReview/bestSellerIds()
            // phía trên, không tính đơn pending/cancelled. Cũng loại trừ sản
            // phẩm đang xem vì cùng lý do ở trên.
            $purchasedProductIds = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', $userId)
                ->whereIn('orders.status', ['paid', 'cod_ordered'])
                ->where('order_items.product_id', '!=', $product->id)
                ->pluck('order_items.product_id');

            $purchasedCategoryIds = DB::table('category_product')
                ->whereIn('product_id', $purchasedProductIds)
                ->pluck('category_id');

            // Gộp 2 nguồn, đếm tần suất, lấy top 5 danh mục được quan tâm nhất.
            $preferredCategoryIds = $viewedCategoryIds
                ->merge($purchasedCategoryIds)
                ->countBy()
                ->sortDesc()
                ->take(5)
                ->keys();

            if ($preferredCategoryIds->isEmpty()) {
                // Chưa có lịch sử xem/mua nào -> fallback về logic cũ.
                $relatedProducts = $sameCategoryQuery()->take($relatedLimit)->get();
            } else {
                // Duyệt TỪNG danh mục theo đúng thứ tự ưu tiên (nhiều lượt
                // xem/mua nhất trước) thay vì lọc gộp rồi sort theo ngày —
                // nếu không, danh mục có nhiều sản phẩm mới sẽ áp đảo danh
                // mục mà user thực sự quan tâm nhiều hơn nhưng ít hàng mới.
                $relatedProducts = new \Illuminate\Database\Eloquent\Collection();
                foreach ($preferredCategoryIds as $categoryId) {
                    if ($relatedProducts->count() >= $relatedLimit) {
                        break;
                    }
                    $needed = $relatedLimit - $relatedProducts->count();
                    $excludeIds = $relatedProducts->pluck('id')->push($product->id);

                    $fromCategory = Product::where('is_active', true)
                        ->whereNotIn('id', $excludeIds)
                        ->whereHas('categories', function ($q) use ($categoryId) {
                            $q->where('categories.id', $categoryId);
                        })
                        ->with('variants')
                        ->latest()
                        ->take($needed)
                        ->get();

                    $relatedProducts = $relatedProducts->concat($fromCategory);
                }

                // Danh mục ưa thích có ít sản phẩm -> lấp đầy thêm bằng logic
                // cũ (cùng danh mục với sản phẩm đang xem), không trùng lặp.
                if ($relatedProducts->count() < $relatedLimit) {
                    $excludeIds = $relatedProducts->pluck('id')->push($product->id);
                    $fillCount = $relatedLimit - $relatedProducts->count();

                    $filler = $sameCategoryQuery()
                        ->whereNotIn('id', $excludeIds)
                        ->take($fillCount)
                        ->get();

                    $relatedProducts = $relatedProducts->concat($filler);
                }
            }
        }

        // FAQ hiển thị ở trang chi tiết sản phẩm (nhóm "product") — P3.1.
        $faqs = Faq::published()
            ->placement(Faq::PLACEMENT_PRODUCT)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('shop.show', compact(
            'product',
            'relatedProducts',
            'reviews',
            'reviewsCount',
            'averageRating',
            'myReview',
            'canReview',
            'bestSellerIds',
            'productVouchers',
            'faqs'
        ));
    }

    /**
     * C1.2: trang danh sách đầy đủ (có phân trang) các sản phẩm bán chạy,
     * dùng lại đúng điều kiện/khung thời gian của bestSellerIds() để nhất
     * quán với nhãn "Bán chạy" đang hiển thị ở index()/show().
     */
    public function bestSellers(Request $request)
    {
        // Nhãn "Bán chạy" gắn trên mỗi thẻ sản phẩm PHẢI luôn dựa theo top 8
        // giống hệt index()/show(), không phụ thuộc hạn mức hiển thị của
        // riêng trang danh sách này — nếu không, mọi sản phẩm trên trang đều
        // sẽ bị gắn nhãn (vì tất cả đều nằm trong danh sách hiển thị), khiến
        // nhãn mất ý nghĩa.
        $bestSellerIds = $this->bestSellerIds();

        // Hạn mức rộng hơn CHỈ để lấy đủ sản phẩm cho trang danh sách có
        // phân trang thật sự (khác hẳn $bestSellerIds dùng cho nhãn ở trên).
        $listIds = $this->bestSellerIds(60);

        if (empty($listIds)) {
            // Fallback bắt buộc: chưa có đơn nào đủ điều kiện trong 30 ngày
            // gần nhất -> hiển thị sản phẩm mới nhất để trang không trống.
            $products = Product::where('is_active', true)
                ->with(['categories', 'variants'])
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $soldCounts = [];

            return view('shop.best-sellers', compact('products', 'bestSellerIds', 'soldCounts'));
        }

        // Tổng số lượng đã bán của từng sản phẩm ĐANG HIỂN THỊ trên trang
        // (theo $listIds, không chỉ top 8), tính trên đúng điều kiện (trạng
        // thái đơn + khung 30 ngày) như bestSellerIds().
        $soldCounts = $this->soldCountsFor($listIds);

        // Giữ đúng thứ tự "bán chạy nhất trước" đã được tính sẵn trong
        // $listIds (ORDER BY SUM(quantity) DESC) bằng FIELD(). $listIds
        // chắc chắn không rỗng ở đây (đã return sớm ở nhánh fallback phía
        // trên), nên implode() không thể sinh ra FIELD(id, ) rỗng gây lỗi SQL.
        $products = Product::where('is_active', true)
            ->whereIn('id', $listIds)
            ->with(['categories', 'variants'])
            ->orderByRaw('FIELD(id, ' . implode(',', $listIds) . ')')
            ->paginate(12)
            ->withQueryString();

        return view('shop.best-sellers', compact('products', 'bestSellerIds', 'soldCounts'));
    }

    /**
     * Top N (mặc định 8) sản phẩm bán chạy nhất trong 30 ngày gần nhất, dùng
     * cho nhãn "Bán chạy" tự động (D4) khi gọi không tham số. Trạng thái đơn
     * hàng thật đã xác nhận trong User/OrderController::store()
     * (`cod_ordered`, `pending` cho MoMo) và User/MomoController::completePayment()
     * (chuyển 'pending' -> 'paid' sau khi thanh toán MoMo thành công) — nên
     * đơn "đã thật sự bán được hàng" ứng với status IN ('paid', 'cod_ordered').
     *
     * @param int $limit Số lượng id tối đa cần lấy (mặc định 8 để không đổi
     *                    hành vi nhãn "Bán chạy" ở index()/show()). Trang
     *                    bestSellers() truyền hạn mức rộng hơn (vd 60) để có
     *                    đủ dữ liệu cho phân trang thật sự.
     * @return array<int>
     */
    private function bestSellerIds(int $limit = 8): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['paid', 'cod_ordered'])
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit($limit)
            ->pluck('order_items.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Tổng số lượng đã bán (trong 30 ngày gần nhất, chỉ tính đơn đã thật sự
     * bán được hàng — status IN ('paid', 'cod_ordered')) của từng sản phẩm
     * trong danh sách $ids truyền vào. Dùng CHUNG cho cả bestSellers() (tính
     * cho toàn bộ sản phẩm hiển thị trên trang /ban-chay) và index() (tính
     * cho các sản phẩm bán chạy hiển thị ở trang chủ), để không lặp lại cùng
     * một điều kiện query ở nhiều nơi.
     *
     * @param array<int> $ids Danh sách product_id cần tính. Trả về [] ngay
     *                        nếu rỗng để tránh whereIn() với mảng rỗng.
     * @return array<int, int> Mảng product_id => tổng quantity đã bán.
     */
    private function soldCountsFor(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', ['paid', 'cod_ordered'])
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->whereIn('order_items.product_id', $ids)
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->pluck('total_qty', 'order_items.product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }
}
