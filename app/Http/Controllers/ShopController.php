<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Product;
use App\Models\Category;
use App\Models\HomeFeature;
use App\Models\Order;
use App\Models\ProductSeason;
use App\Models\Review;
use App\Services\ProductViewTracker;
use App\Services\SalesMetricService;
use App\Support\VoucherPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /**
     * Số sản phẩm tối đa của MỖI hàng trên trang chủ. Hàng là carousel ngang
     * (product-row.blade.php) hiện 4 thẻ một lúc, phần còn lại xem bằng mũi tên.
     */
    public const HOME_ROW_LIMIT = 12;

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request, false);
        $categoryIds = $filters['categories'];
        $type = $filters['type'];
        $search = $filters['q'];
        $sort = $filters['sort'];

        $products = $this->filteredProductQuery($filters)
            ->paginate(12)
            ->withQueryString();

        $activeCategories = Category::whereIn('id', $categoryIds)->get();

        // Toàn bộ nhóm danh mục (kèm số sản phẩm mỗi danh mục con) — dùng cho
        // dải "Danh mục nổi bật" và sidebar bộ lọc ở trang khách hàng.
        $categoryGroups = $this->categoryGroupsWithCounts();

        // Một query bán chạy duy nhất cho cả request: lấy đủ cho hàng bán chạy
        // của trang chủ, còn nhãn "Bán chạy" trên thẻ vẫn chỉ dành cho top 8.
        $homeBestSellerIds = $this->bestSellerIds(max(8, self::HOME_ROW_LIMIT));
        $bestSellerIds = array_slice($homeBestSellerIds, 0, 8);

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
        $newestPlants = collect();
        $newestFlowers = collect();
        $homeFeatures = collect();
        $homeBestSellers = collect();
        $homeSoldCounts = [];
        $indoorCategory = null;
        $outdoorCategory = null;
        $outdoorNoun = 'cây';
        $indoorProducts = collect();
        $outdoorProducts = collect();
        $homeFeatured = collect();
        $homeFeaturedIsBestSeller = false;
        $giftProductCount = 0;

        if ($showFeatured) {
            // Tách hàng Cây/Hoa bằng cột products.product_type — KHÔNG còn tìm
            // theo tên nhóm danh mục (F5): đổi tên nhóm không còn làm hỏng
            // trang chủ. Xem D1.
            $newestPlants = Product::where('is_active', true)
                ->plants()
                ->with(['categories', 'variants'])
                ->latest()
                ->take(self::HOME_ROW_LIMIT)
                ->get();

            // Ảnh/video khối giới thiệu (Cây được tuyển chọn / Dịch vụ tận tâm /
            // Chất liệu cao cấp) — quản trị viên tự đổi ở /admin/settings.
            $homeFeatures = HomeFeature::orderBy('id')->get()->keyBy('slug');

            // Khối "Bán chạy" ở trang chủ: dùng lại $homeBestSellerIds đã tính
            // sẵn ở trên (cùng điều kiện với nhãn "Bán chạy" trên mỗi thẻ sản
            // phẩm) thay vì gọi lại bestSellerIds() — tránh chạy trùng một
            // query tổng hợp order_items/orders lần thứ hai trong cùng request.
            if (!empty($homeBestSellerIds)) {
                $homeBestSellers = $this->orderByIdList(
                    Product::where('is_active', true)
                        ->whereIn('id', $homeBestSellerIds)
                        ->with(['categories', 'variants']),
                    $homeBestSellerIds
                )
                    ->take(self::HOME_ROW_LIMIT)
                    ->get();
            }

            // Chỉ tính số lượng đã bán cho ĐÚNG các sản phẩm thật sự hiển thị
            // ở trang chủ (tối đa HOME_ROW_LIMIT), không phải mọi id bán chạy
            // — vì $bestSellerIds có thể chứa id sản phẩm đã bị tắt is_active
            // hoặc đã xoá (không nằm trong $homeBestSellers), tính thừa cho
            // các id đó là lãng phí và không dùng tới.
            $homeSoldCounts = $this->soldCountsFor($homeBestSellers->pluck('id')->all());

            // ==== Cây theo KHÔNG GIAN sống (đợt C) ====
            // Lấy theo danh mục THẬT chứ không đoán theo tên nhóm cây: nhóm
            // "Cây cảnh sân vườn & ngoài trời" hiện chưa có sản phẩm nào, nếu
            // trỏ link theo nhóm đó thì khách bấm vào ra trang trống. Danh mục
            // "Trong nhà (Indoor)" / "Ngoài trời (Outdoor)" mới là nơi có hàng.
            // orderBy('id') để bản ghi danh mục con (id nhỏ) thắng nhóm gốc có
            // tên chứa cùng cụm từ.
            $findCategory = fn (string $needle) => Category::whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($needle) . '%'])
                ->orderBy('id')
                ->first();

            $inCategory = fn (Category $category) => Product::where('is_active', true)
                ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id));

            // $excludeIds: cửa hàng hiện chỉ có hơn chục sản phẩm, nếu mỗi hàng
            // đều lấy "mới nhất" thì cùng một cây xuất hiện 3-4 lần trên trang.
            // Ưu tiên những cây CHƯA khoe ở phía trên để mỗi hàng nói một điều
            // mới; hàng nay dài tới HOME_ROW_LIMIT thẻ nên lưới đầu có thể ôm
            // hết cây của danh mục — khi đó bù lại bằng chính các cây đã khoe
            // (cùng quy tắc với hàng hoa, T4) thay vì để cả hàng biến mất.
            $productsInCategory = function (?Category $category, int $take, array $excludeIds = []) use ($inCategory) {
                if (! $category) {
                    return collect();
                }

                $query = fn () => $inCategory($category)
                    ->with(['categories', 'variants'])
                    ->latest();

                $products = $query()
                    ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
                    ->take($take)
                    ->get();

                if ($products->count() < $take && $excludeIds) {
                    $products = $products->concat(
                        $query()->whereIn('id', $excludeIds)->take($take - $products->count())->get()
                    )->values();
                }

                return $products;
            };

            $indoorCategory = $findCategory('trong nhà');
            $outdoorCategory = $findCategory('ngoài trời');

            // Lưới mua sắm ĐẦU TIÊN phải luôn là một lưới hàng mua được: có số
            // liệu bán chạy thì dùng, chưa có thì rơi về cây mới về — không rơi
            // về banner, vì banner không mua được gì.
            $homeFeatured = $homeBestSellers->isNotEmpty()
                ? $homeBestSellers
                : Product::where('is_active', true)
                    ->plants()
                    ->whereNotNull('main_image')
                    ->with(['categories', 'variants'])
                    ->latest()
                    ->take(self::HOME_ROW_LIMIT)
                    ->get();
            $homeFeaturedIsBestSeller = $homeBestSellers->isNotEmpty();

            // Các hàng phía dưới bỏ cây đã xuất hiện ở lưới đầu tiên.
            $usedIds = $homeFeatured->pluck('id')->all();
            $indoorProducts = $productsInCategory($indoorCategory, self::HOME_ROW_LIMIT, $usedIds);
            $outdoorProducts = $productsInCategory($outdoorCategory, self::HOME_ROW_LIMIT, $usedIds);

            // T5: hàng "ngoài trời" phải gọi đúng tên thứ đang bán trong danh
            // mục đó (hiện toàn là hoa) — đọc product_type thật của cả danh
            // mục (nút "Xem tất cả" mở cả danh mục, không chỉ 3 thẻ xem trước).
            if ($outdoorCategory) {
                $outdoorTypes = $inCategory($outdoorCategory)->distinct()->pluck('product_type')->all();
                $outdoorNoun = match (true) {
                    $outdoorTypes === ['flower'] => 'hoa',
                    in_array('flower', $outdoorTypes, true) => 'cây & hoa',
                    default => 'cây',
                };
            }

            // T4: "Hoa mới nhập" — ưu tiên hoa CHƯA đứng ở hàng ngoài trời,
            // nhưng chọn đủ từ tập còn lại TRƯỚC khi giới hạn HOME_ROW_LIMIT (bản cũ cắt 4
            // rồi mới loại nên hàng hụt còn 1). Kho hoa ít thì cho phép hoa
            // ngoài trời xuất hiện lại ở đây — hai hàng khác mục đích; trong
            // cùng một hàng không bao giờ lặp.
            $flowerQuery = fn () => Product::where('is_active', true)
                ->flowers()
                ->with(['categories', 'variants'])
                ->latest();
            $outdoorIds = $outdoorProducts->pluck('id')->all();
            $newestFlowers = $flowerQuery()
                ->when($outdoorIds, fn ($q) => $q->whereNotIn('id', $outdoorIds))
                ->take(self::HOME_ROW_LIMIT)
                ->get();
            if ($newestFlowers->count() < self::HOME_ROW_LIMIT && $outdoorIds) {
                $newestFlowers = $newestFlowers->concat(
                    $flowerQuery()
                        ->whereIn('id', $outdoorIds)
                        ->take(self::HOME_ROW_LIMIT - $newestFlowers->count())
                        ->get()
                )->values();
            }

            // T6: nút "Xem quà tặng" chỉ giữ lời hứa đó khi có sản phẩm thật
            // được admin gắn nhãn Quà tặng; không thì đổi lời nút.
            $giftProductCount = Product::where('is_active', true)->where('badge', 'gift')->count();
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
            'newestPlants',
            'newestFlowers',
            'homeFeatures',
            'homeBestSellers',
            'homeSoldCounts',
            'homeFeatured',
            'homeFeaturedIsBestSeller',
            'indoorCategory',
            'outdoorCategory',
            'outdoorNoun',
            'indoorProducts',
            'outdoorProducts',
            'giftProductCount',
            'faqs'
        ));
    }

    /**
     * T8: trang danh sách toàn bộ sản phẩm có lọc (route shop.catalog,
     * GET /cua-hang). Dùng chung validatedFilters()/filteredProductQuery()
     * với index() nên hai trang lọc giống hệt nhau; trang này thêm lọc mùa,
     * bán chạy và quà tặng — mỗi bộ lọc chỉ hiện khi có dữ liệu thật.
     *
     * Chưa có bộ lọc "Đang giảm giá": hệ thống chưa có giá giảm riêng của sản
     * phẩm được áp dụng xuyên suốt thẻ/giỏ/thanh toán (chỉ có voucher chung).
     */
    public function catalog(Request $request)
    {
        $filters = $this->validatedFilters($request, true);

        $bestSellerIds = $this->bestSellerIds();
        $bestSellerListIds = $this->bestSellerIds(60);
        $giftProductCount = Product::where('is_active', true)->where('badge', 'gift')->count();

        // Bộ lọc không còn dữ liệu (vd link cũ ?gift=1 khi đã hết quà tặng,
        // ?season= khi chưa gán mùa) thì bỏ qua thay vì trả trang rỗng.
        if (empty($bestSellerListIds)) {
            $filters['bestseller'] = false;
        }
        if ($giftProductCount === 0) {
            $filters['gift'] = false;
        }

        // Chỉ đưa ra lựa chọn mùa đã có sản phẩm đang bán được gán. all_year
        // khớp mọi mùa cụ thể (xem Product::scopeInSeason) nên có all_year là
        // mọi mùa đều có kết quả.
        $assignedSeasons = ProductSeason::query()
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->distinct()
            ->pluck('season')
            ->all();
        $seasonOptions = collect(Product::SEASONS)
            ->filter(fn ($label, $code) => in_array($code, $assignedSeasons, true)
                || ($code !== 'all_year' && in_array('all_year', $assignedSeasons, true)))
            ->all();

        if ($filters['season'] !== null && ! array_key_exists($filters['season'], $seasonOptions)) {
            $filters['season'] = null;
        }

        $products = $this->filteredProductQuery($filters, $bestSellerListIds)
            ->paginate(12)
            ->withQueryString();

        $categoryGroups = $this->categoryGroupsWithCounts();

        return view('shop.catalog', [
            'products' => $products,
            'filters' => $filters,
            'categoryGroups' => $categoryGroups,
            'bestSellerIds' => $bestSellerIds,
            'hasBestSellers' => !empty($bestSellerListIds),
            'giftProductCount' => $giftProductCount,
            'seasonOptions' => $seasonOptions,
        ]);
    }

    /**
     * URL tới trang danh sách sản phẩm (shop.catalog) — dùng ở các nút
     * "Xem tất cả" của trang chủ.
     *
     * @param  array<string, mixed>  $params
     */
    public static function catalogUrl(array $params = []): string
    {
        return route('shop.catalog', $params);
    }

    /**
     * Đọc + kiểm tra bộ lọc danh sách sản phẩm từ query string. $extended
     * bật các bộ lọc chỉ trang /cua-hang có (mùa, bán chạy, quà tặng).
     *
     * @return array{categories: array<int>, type: ?string, q: string, price_min: ?string, price_max: ?string, sort: string, season: ?string, bestseller: bool, gift: bool}
     */
    private function validatedFilters(Request $request, bool $extended): array
    {
        $request->validate([
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
        ], [
            'price_min.numeric' => 'Giá tối thiểu không hợp lệ.',
            'price_max.numeric' => 'Giá tối đa không hợp lệ.',
        ]);

        $type = $request->input('type');
        $sort = $request->input('sort', 'featured');
        $season = $request->input('season');

        return [
            'categories' => array_values(array_filter(
                array_map('intval', (array) $request->input('categories', []))
            )),
            'type' => in_array($type, ['plant', 'flower'], true) ? $type : null,
            'q' => trim((string) $request->input('q')),
            'price_min' => $request->filled('price_min') ? (string) $request->input('price_min') : null,
            'price_max' => $request->filled('price_max') ? (string) $request->input('price_max') : null,
            'sort' => in_array($sort, ['featured', 'price-asc', 'price-desc', 'name-asc'], true) ? $sort : 'featured',
            'season' => $extended && is_string($season) && array_key_exists($season, Product::SEASONS) ? $season : null,
            'bestseller' => $extended && $request->boolean('bestseller'),
            'gift' => $extended && $request->boolean('gift'),
        ];
    }

    /**
     * Truy vấn sản phẩm đang bán theo bộ lọc của validatedFilters().
     *
     * @param  array<int>  $bestSellerListIds  thứ hạng bán chạy, chỉ cần khi lọc bestseller
     */
    private function filteredProductQuery(array $filters, array $bestSellerListIds = []): Builder
    {
        $query = Product::where('is_active', true)->with(['categories', 'variants']);

        if (!empty($filters['categories'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('categories.id', $filters['categories']);
            });
        }

        // Lọc theo loại sản phẩm Cây/Hoa qua cột product_type (D1, F5).
        if ($filters['type'] !== null) {
            $query->where('product_type', $filters['type']);
        }

        $search = $filters['q'];
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

        // Lọc/sắp theo giá HIỂN THỊ THỰC TẾ trên thẻ sản phẩm (khớp với
        // shop.partials.product-card):
        //   - Sản phẩm có >=2 phân loại giá khác nhau -> giá thấp nhất trong
        //     các phân loại, hiển thị "Từ X₫".
        //   - Ngược lại -> products.base_price.
        // Dùng subquery tương quan để tính đúng giá này ngay trong SQL, tránh
        // lọc sai trên base_price khi giá thật đến từ variant.
        $effectivePriceExpr = '(CASE WHEN ('
            . 'SELECT COUNT(DISTINCT pv1.price) FROM product_variants pv1 WHERE pv1.product_id = products.id'
            . ') >= 2 THEN ('
            . 'SELECT MIN(pv2.price) FROM product_variants pv2 WHERE pv2.product_id = products.id'
            . ') ELSE products.base_price END)';

        if ($filters['price_min'] !== null) {
            $query->whereRaw($effectivePriceExpr . ' >= ?', [$filters['price_min']]);
        }
        if ($filters['price_max'] !== null) {
            $query->whereRaw($effectivePriceExpr . ' <= ?', [$filters['price_max']]);
        }

        if ($filters['season'] !== null) {
            $query->inSeason($filters['season']);
        }

        // Quà tặng = sản phẩm admin gắn nhãn "Quà tặng" thật, không suy đoán.
        if ($filters['gift']) {
            $query->where('badge', 'gift');
        }

        if ($filters['bestseller']) {
            $query->whereIn('id', $bestSellerListIds ?: [0]);
        }

        // Sắp xếp hiển thị (chỉ ảnh hưởng thứ tự, không đổi tập kết quả).
        // Đang lọc bán chạy + "Đề xuất" -> giữ thứ hạng bán chạy.
        match (true) {
            $filters['sort'] === 'price-asc' => $query->orderByRaw($effectivePriceExpr . ' asc'),
            $filters['sort'] === 'price-desc' => $query->orderByRaw($effectivePriceExpr . ' desc'),
            $filters['sort'] === 'name-asc' => $query->orderBy('name', 'asc'),
            $filters['bestseller'] => $this->orderByIdList($query, $bestSellerListIds),
            default => $query->latest(),
        };

        return $query->orderBy('id', 'desc');
    }

    /**
     * Nhóm danh mục gốc kèm danh mục con (products_count = số sản phẩm đang
     * bán của từng con) và active_products_count trên mỗi nhóm = số sản phẩm
     * đang bán KHÁC NHAU trong các danh mục con (một cây thuộc hai danh mục
     * con chỉ tính một lần) — đúng tập mà thẻ nhóm mở ra khi bấm.
     */
    private function categoryGroupsWithCounts(): EloquentCollection
    {
        $categoryGroups = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount(['products' => function ($q) {
                    $q->where('is_active', true);
                }]);
            }])
            ->ordered()
            ->get();

        $groupCounts = DB::table('category_product')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereNotNull('categories.parent_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('categories.parent_id')
            ->selectRaw('categories.parent_id AS group_id, COUNT(DISTINCT category_product.product_id) AS total')
            ->pluck('total', 'group_id');

        foreach ($categoryGroups as $group) {
            $group->setAttribute('active_products_count', (int) ($groupCounts[$group->id] ?? 0));
        }

        return $categoryGroups;
    }

    /**
     * Sắp theo đúng thứ tự $ids (thứ hạng bán chạy). Dùng CASE thay cho
     * FIELD() của MySQL để chạy được cả trên SQLite (test).
     *
     * @param  array<int>  $ids
     */
    private function orderByIdList(Builder $query, array $ids): Builder
    {
        if (empty($ids)) {
            return $query;
        }

        $cases = collect(array_values($ids))
            ->map(fn ($id, $i) => 'WHEN ' . (int) $id . ' THEN ' . $i)
            ->implode(' ');

        return $query->orderByRaw('CASE products.id ' . $cases . ' ELSE ' . count($ids) . ' END');
    }

    public function show(Request $request, Product $product, ProductViewTracker $viewTracker)
    {
        abort_unless($product->is_active, 404);

        // T9: một điểm ghi lượt xem duy nhất — người đăng nhập và khách (phiên
        // ẩn danh), chống đếm lặp 30 phút, không ghi admin/bot, không bao giờ
        // làm vỡ trang. Lịch sử "đã xem gần đây" vẫn đọc theo user_id.
        $viewTracker->record($product, $request);

        $product->load(['categories', 'variants']);

        $user = auth()->user();

        // Số liệu tổng quan khối đánh giá (điểm trung bình, số đánh giá mỗi
        // mức sao, số có bình luận / có ảnh) — 3 truy vấn gọn thay cho việc
        // nạp toàn bộ đánh giá vào bộ nhớ. DANH SÁCH đánh giá không còn load
        // ở đây nữa: nó được tải bằng AJAX qua ReviewController@index để lọc
        // và phân trang không phải nạp lại cả trang sản phẩm.
        $reviewStats = $this->reviewStatsFor($product);
        $reviewsCount = $reviewStats['count'];
        $averageRating = $reviewStats['avg'];

        // R1: điều kiện cũ (`status ∈ paid, cod_ordered`) không cần hàng đã
        // giao và bỏ sót `cod_paid` -> lệch với ReviewController. Giờ dùng
        // đúng một nguồn luật: Order::canReview() + reviewableLines().
        $pendingReviewOrderId = null;

        if ($user !== null) {
            $candidateOrders = Order::query()
                ->where('user_id', $user->id)
                ->whereIn('status', Order::PAID_OR_COD_STATUSES)
                ->where('shipping_status', 'delivered')
                ->whereHas('items', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->with('items.product')
                ->latest('id')
                ->get();

            foreach ($candidateOrders as $candidate) {
                if (! $candidate->canReview()) {
                    continue;
                }

                // Đơn còn dòng hàng CỦA SẢN PHẨM NÀY chưa đánh giá -> đây là
                // đơn để nút "Viết đánh giá" dẫn tới.
                $hasPendingLine = $candidate->reviewableLines($user)->contains(
                    fn (array $line) => $line['can_review'] && $line['item']->product_id === $product->id
                );

                if ($hasPendingLine) {
                    $pendingReviewOrderId = $candidate->id;
                    break;
                }
            }
        }

        $canReview = $pendingReviewOrderId !== null;

        // Giá sau voucher (mục 3.A). Tính theo phân loại đầu tiên (đúng cái
        // view hiển thị mặc định) ở số lượng 1; JS dùng $voucherMatrix để đổi
        // giá ngay khi khách chọn phân loại khác mà không gọi lại server.
        $defaultUnitPrice = (float) ($product->variants->first()->price ?? $product->base_price);
        $bestVoucher = VoucherPricing::bestFor($product, $defaultUnitPrice, 1, $user);
        $voucherMatrix = VoucherPricing::variantMatrix($product, $user);
        $voucherCandidates = VoucherPricing::candidatesFor($product, $user);

        $bestSellerIds = $this->bestSellerIds();

        // Mã giảm giá áp dụng được cho sản phẩm này (toàn shop + gắn trực
        // tiếp + gắn qua danh mục nó thuộc về) — hiển thị ngay tại trang chi
        // tiết sản phẩm để khách biết mà "săn mã".
        // Dải mã hiện ở trang sản phẩm: dùng lại danh sách ứng viên đã lọc
        // "mã khách đã dùng" ở trên thay vì truy vấn lại lần nữa.
        $productVouchers = $voucherCandidates;

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
            'reviewsCount',
            'averageRating',
            'reviewStats',
            'canReview',
            'pendingReviewOrderId',
            'bestSellerIds',
            'productVouchers',
            'bestVoucher',
            'voucherMatrix',
            'voucherCandidates',
            'faqs'
        ));
    }

    /**
     * Số liệu khối "ĐÁNH GIÁ SẢN PHẨM": điểm trung bình, tổng số, số đánh giá
     * theo từng mức sao, số có bình luận, số có ảnh. Chỉ tính đánh giá KHÁCH
     * NHÌN THẤY ĐƯỢC (scopeVisible — admin đã ẩn thì không tính vào điểm).
     *
     * 1 truy vấn group by rating (ra luôn avg + tổng + đếm từng sao) + 2 truy
     * vấn đếm, thay vì nạp toàn bộ bảng reviews lên PHP như trước.
     *
     * @return array{avg: ?float, count: int, by_star: array<int, int>, with_comment: int, with_media: int}
     */
    private function reviewStatsFor(Product $product): array
    {
        $rows = Review::visible()
            ->where('product_id', $product->id)
            ->groupBy('rating')
            ->selectRaw('rating, COUNT(*) AS total')
            ->pluck('total', 'rating');

        $byStar = [];
        $count = 0;
        $sum = 0;

        foreach ([5, 4, 3, 2, 1] as $star) {
            $total = (int) ($rows[$star] ?? 0);
            $byStar[$star] = $total;
            $count += $total;
            $sum += $total * $star;
        }

        return [
            'avg' => $count > 0 ? round($sum / $count, 1) : null,
            'count' => $count,
            'by_star' => $byStar,
            'with_comment' => Review::visible()
                ->where('product_id', $product->id)
                ->whereNotNull('comment')
                ->where('comment', '!=', '')
                ->count(),
            'with_media' => Review::visible()
                ->where('product_id', $product->id)
                ->whereHas('images')
                ->count(),
        ];
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
        // $listIds (ORDER BY SUM(quantity) DESC).
        $products = $this->orderByIdList(
            Product::where('is_active', true)
                ->whereIn('id', $listIds)
                ->with(['categories', 'variants']),
            $listIds
        )
            ->paginate(12)
            ->withQueryString();

        return view('shop.best-sellers', compact('products', 'bestSellerIds', 'soldCounts'));
    }

    /**
     * Top N (mặc định 8) sản phẩm bán chạy nhất trong 30 ngày gần nhất (tính
     * cả hôm nay), dùng cho nhãn "Bán chạy" tự động (D4), khối "Cây bán chạy"
     * ở trang chủ và trang /ban-chay. Điều kiện "đơn đã bán" lấy từ
     * SalesMetricService — cùng nguồn với báo cáo doanh số: tính paid và COD
     * đã thu (cod_paid); không tính COD mới đặt, đơn huỷ, hoàn tiền/hoàn hàng.
     *
     * @param int $limit Số lượng id tối đa cần lấy (mặc định 8 để không đổi
     *                    hành vi nhãn "Bán chạy" ở index()/show()). Trang
     *                    bestSellers() truyền hạn mức rộng hơn (vd 60) để có
     *                    đủ dữ liệu cho phân trang thật sự.
     * @return array<int>
     */
    private function bestSellerIds(int $limit = 8): array
    {
        return $this->productSalesLast30Days()
            ->orderByDesc('total_qty')
            ->orderBy('order_items.product_id')
            ->limit($limit)
            ->pluck('order_items.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Tổng số lượng đã bán (cùng khung 30 ngày + quy tắc đơn đã bán như
     * bestSellerIds()) của từng sản phẩm trong $ids.
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

        return $this->productSalesLast30Days()
            ->whereIn('order_items.product_id', $ids)
            ->pluck('total_qty', 'order_items.product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * Khung "30 ngày qua" dùng chung cho nhãn trang chủ và /ban-chay:
     * [đầu ngày cách đây 29 ngày, đầu ngày mai).
     */
    private function productSalesLast30Days(): QueryBuilder
    {
        $today = now()->startOfDay();

        return app(SalesMetricService::class)->productSales($today->copy()->subDays(29), $today->copy()->addDay());
    }
}
