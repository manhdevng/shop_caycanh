<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        // Toàn bộ nhóm gốc gom theo scope: Cây cảnh / Hoa / Dùng chung — dùng
        // cho trang danh mục chia 3 khu ở Phase 4 (D1, TC17). Sắp theo
        // sort_order (thứ tự hiển thị thủ công), children() đã tự sắp sẵn.
        $allRootCategories = Category::whereNull('parent_id')
            ->withCount('products')
            ->with(['children' => function ($query) {
                $query->withCount('products');
            }])
            ->ordered()
            ->get();

        $categoryGroups = [
            'plant' => $allRootCategories->where('scope', 'plant')->values(),
            'flower' => $allRootCategories->where('scope', 'flower')->values(),
            'both' => $allRootCategories->where('scope', 'both')->values(),
        ];

        // Tổng số sản phẩm KHÁC NHAU của mỗi nhóm gốc (gắn trực tiếp vào nhóm
        // HOẶC vào bất kỳ danh mục con nào) — tính bằng đúng 1 query để tránh
        // N+1 khi lặp qua từng nhóm/con trong view.
        $groupProductTotals = DB::table('category_product as cp')
            ->join('categories as c', 'c.id', '=', 'cp.category_id')
            ->selectRaw('COALESCE(c.parent_id, c.id) as group_id, COUNT(DISTINCT cp.product_id) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        // Số liệu tổng quan hiển thị đầu trang quản trị danh mục.
        $childrenCount = $allRootCategories->sum(fn ($group) => $group->children->count());
        $emptyChildren = $allRootCategories->sum(function ($group) {
            return $group->children->filter(fn ($child) => $child->products_count === 0)->count();
        });

        $plantAndFlowerGroups = $allRootCategories->whereIn('scope', ['plant', 'flower']);

        $missingImage = $plantAndFlowerGroups->filter(fn ($group) => empty($group->image))->count();
        $emptyGroups = $plantAndFlowerGroups->filter(fn ($group) => $group->children->isEmpty())->count();

        // Sản phẩm đang bán nhưng chưa gắn vào bất kỳ danh mục cây/hoa nào
        // (không tính scope 'both', vì đó là thuộc tính lọc dùng chung).
        $unassignedProducts = Product::where('is_active', true)
            ->whereDoesntHave('categories', function ($q) {
                $q->whereIn('scope', ['plant', 'flower'])
                    ->orWhereHas('parent', fn ($p) => $p->whereIn('scope', ['plant', 'flower']));
            })->count();

        $summary = [
            'groups' => $allRootCategories->count(),
            'children' => $childrenCount,
            'emptyChildren' => $emptyChildren,
            'missingImage' => $missingImage,
            'emptyGroups' => $emptyGroups,
            'unassignedProducts' => $unassignedProducts,
        ];

        return view('categories.index', compact('categoryGroups', 'groupProductTotals', 'summary'));
    }

    public function create(Request $request)
    {
        // Danh sách danh mục gốc để chọn làm "danh mục cha" (nếu muốn tạo danh
        // mục con). Chỉ lấy nhóm gốc — hệ thống chỉ hỗ trợ 2 cấp, nếu liệt kê
        // cả danh mục con thì người dùng có thể chọn nhầm và bị
        // guardAgainstThirdLevel() chặn ở bước lưu.
        $parentCategories = Category::whereNull('parent_id')->ordered()->get();

        // Cho phép mở form với danh mục cha được chọn sẵn (vd bấm "Thêm danh
        // mục con" từ một nhóm cụ thể) — chỉ chấp nhận nếu id đó thật sự là
        // một nhóm gốc đang tồn tại, tránh preselect sai dữ liệu.
        $preselectedParentId = null;
        $requestedParentId = $request->query('parent_id');
        if ($requestedParentId !== null) {
            $requestedParent = Category::whereNull('parent_id')->find($requestedParentId);
            if ($requestedParent) {
                $preselectedParentId = $requestedParent->id;
            }
        }

        return view('categories.create', compact('parentCategories', 'preselectedParentId'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ];

        $messages = [];

        // scope chỉ bắt buộc khi tạo NHÓM GỐC (không chọn danh mục cha).
        // Danh mục con lấy phạm vi theo nhóm cha (Category::effectiveScope()).
        if (!$request->filled('parent_id')) {
            $rules['scope'] = 'required|in:' . implode(',', array_keys(Category::SCOPES));
            $messages['scope.required'] = 'Vui lòng chọn phạm vi (Cây cảnh / Hoa / Dùng chung) cho nhóm danh mục gốc.';
        }

        $validated = $request->validate($rules, $messages);

        if ($request->filled('parent_id')) {
            $this->guardAgainstThirdLevel((int) $request->parent_id);
            // Danh mục con không có ý nghĩa scope riêng — bỏ qua nếu client cố gửi kèm.
            unset($validated['scope']);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        // Danh mục mới luôn xếp CUỐI cùng cấp: "cùng cấp" là cùng parent_id;
        // với nhóm gốc (parent_id null) còn giới hạn thêm cùng scope để 3 khu
        // Cây/Hoa/Dùng chung có thứ tự độc lập với nhau.
        $siblingQuery = Category::query();
        if ($request->filled('parent_id')) {
            $siblingQuery->where('parent_id', $request->parent_id);
        } else {
            $siblingQuery->whereNull('parent_id')->where('scope', $validated['scope']);
        }
        $validated['sort_order'] = (int) $siblingQuery->max('sort_order') + 1;

        Category::create($validated);
        return redirect()->route('categories.index')
                        ->with('success', 'Thêm danh mục thành công.');
    }

    public function show(Category $category)
    {
        // Nạp danh mục con và sản phẩm thuộc danh mục (bao gồm cả danh mục con nếu là danh mục cha)
        $category->load(['parent', 'children.products', 'products']);

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        // Không cho phép chọn chính nó hoặc danh mục con của nó làm danh mục cha
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get();

        return view('categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value == $category->id) {
                        $fail('Danh mục không thể là danh mục cha của chính nó.');
                    }
                },
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
        ];

        $messages = [];

        if (!$request->filled('parent_id')) {
            $rules['scope'] = 'required|in:' . implode(',', array_keys(Category::SCOPES));
            $messages['scope.required'] = 'Vui lòng chọn phạm vi (Cây cảnh / Hoa / Dùng chung) cho nhóm danh mục gốc.';
        }

        $validated = $request->validate($rules, $messages);

        if ($request->filled('parent_id')) {
            $this->guardAgainstThirdLevel((int) $request->parent_id);
            $this->guardAgainstReparentingWithChildren($category);
            unset($validated['scope']);
        }

        // Phạm vi hiệu lực MỚI của danh mục sau khi lưu: danh mục con lấy
        // theo nhóm cha mới, nhóm gốc lấy scope mới gửi lên. Chặn nếu việc
        // đổi phạm vi làm các sản phẩm đang gắn bị lệch product_type.
        $newParent = $request->filled('parent_id') ? Category::find($request->parent_id) : null;
        $newScope = $newParent ? $newParent->scope : $validated['scope'];
        $this->guardAgainstScopeMismatch($category, $newScope, $newParent ? 'parent_id' : 'scope');

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        } else {
            // Không có ảnh mới upload — giữ nguyên ảnh hiện tại, không ghi đè bằng null.
            unset($validated['image']);
        }

        $category->update($validated);
        return redirect()->route('categories.index')
                        ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function destroy(Category $category)
    {
        // An toàn dữ liệu: KHÔNG cho xoá cascade âm thầm. Chỉ xoá được khi
        // danh mục đang trống hoàn toàn (không còn con, không còn sản phẩm),
        // buộc quản trị viên chủ động chuyển/xoá trước.
        $childrenCount = $category->children()->count();
        if ($childrenCount > 0) {
            return redirect()->route('categories.index')->with(
                'error',
                "Không thể xoá \"{$category->name}\": nhóm này còn {$childrenCount} danh mục con. Hãy chuyển hoặc xoá các danh mục con trước."
            );
        }

        $productsCount = $category->products()->count();
        if ($productsCount > 0) {
            return redirect()->route('categories.index')->with(
                'error',
                "Không thể xoá \"{$category->name}\": danh mục đang có {$productsCount} sản phẩm, hãy chuyển sản phẩm sang danh mục khác trước."
            );
        }

        $categoryName = $category->name;
        $categoryImage = $category->image;

        DB::transaction(function () use ($category) {
            $category->delete();
        });

        // Chỉ xoá file ảnh SAU KHI transaction xoá bản ghi đã thành công —
        // tránh mất ảnh mồ côi nếu việc xoá bản ghi DB thất bại giữa chừng.
        if ($categoryImage) {
            Storage::disk('public')->delete($categoryImage);
        }

        return redirect()->route('categories.index')->with('success', "Đã xoá danh mục \"{$categoryName}\".");
    }

    /**
     * Đổi thứ tự hiển thị của một danh mục lên/xuống trong nhóm anh em cùng
     * cấp (dùng nút mũi tên trong bảng quản trị danh mục — không cần trang
     * riêng để kéo-thả).
     */
    public function move(Category $category, string $direction)
    {
        // Anh em liền kề: cùng parent_id; nếu là nhóm gốc thì còn phải cùng
        // scope (3 khu Cây/Hoa/Dùng chung sắp xếp độc lập với nhau).
        $siblingsQuery = Category::where('id', '!=', $category->id);
        if ($category->parent_id === null) {
            $siblingsQuery->whereNull('parent_id')->where('scope', $category->scope);
        } else {
            $siblingsQuery->where('parent_id', $category->parent_id);
        }

        if ($direction === 'up') {
            // Phần tử liền TRƯỚC: (sort_order, id) nhỏ hơn, lấy lớn nhất trong số đó.
            $sibling = (clone $siblingsQuery)
                ->where(function ($q) use ($category) {
                    $q->where('sort_order', '<', $category->sort_order)
                        ->orWhere(function ($q2) use ($category) {
                            $q2->where('sort_order', $category->sort_order)
                                ->where('id', '<', $category->id);
                        });
                })
                ->orderByDesc('sort_order')
                ->orderByDesc('id')
                ->first();
        } else {
            // Phần tử liền SAU: (sort_order, id) lớn hơn, lấy nhỏ nhất trong số đó.
            $sibling = (clone $siblingsQuery)
                ->where(function ($q) use ($category) {
                    $q->where('sort_order', '>', $category->sort_order)
                        ->orWhere(function ($q2) use ($category) {
                            $q2->where('sort_order', $category->sort_order)
                                ->where('id', '>', $category->id);
                        });
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        if (!$sibling) {
            // Đã ở đầu/cuối danh sách, không có gì để đổi chỗ.
            return back();
        }

        DB::transaction(function () use ($category, $sibling, $siblingsQuery) {
            if ($category->sort_order === $sibling->sort_order) {
                // sort_order của cả nhóm anh em có thể trùng nhau (vd cùng =
                // 0 từ dữ liệu cũ) khiến hoán đổi thông thường không đổi gì.
                // Gán lại sort_order tuần tự (0, 1, 2, ...) theo đúng thứ tự
                // hiện tại (sort_order, id) cho toàn bộ nhóm anh em + chính
                // nó trước, sau đó nạp lại 2 bản ghi cần hoán đổi với giá trị mới.
                $ordered = (clone $siblingsQuery)
                    ->orWhere('id', $category->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                foreach ($ordered->values() as $index => $item) {
                    $item->update(['sort_order' => $index]);
                }

                $category->refresh();
                $sibling->refresh();
            }

            $categoryOrder = $category->sort_order;
            $siblingOrder = $sibling->sort_order;

            $category->update(['sort_order' => $siblingOrder]);
            $sibling->update(['sort_order' => $categoryOrder]);
        });

        return back()->with('success', 'Đã cập nhật thứ tự hiển thị.');
    }

    /**
     * Chặn tạo cấu trúc 3 cấp: danh mục cha được chọn phải là một nhóm gốc
     * (parent_id null của chính nó). Hệ thống chỉ hỗ trợ 2 cấp cha/con — nếu
     * ai đó tự sửa HTML để gửi parent_id là một danh mục con (đã thuộc về 1
     * nhóm gốc khác), việc lưu sẽ tạo ra cháu (cấp 3) và bị chặn ở đây.
     */
    private function guardAgainstThirdLevel(int $parentId): void
    {
        $parent = Category::find($parentId);

        if ($parent && $parent->parent_id !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Danh mục cha phải là một nhóm gốc. Hệ thống chỉ hỗ trợ tối đa 2 cấp danh mục (nhóm gốc → danh mục con).',
            ]);
        }
    }

    /**
     * Chặn đổi phạm vi (đổi scope nhóm gốc, chuyển con sang nhóm cha khác
     * scope, đổi gốc ↔ con) khi các sản phẩm đang gắn sẽ bị lệch phạm vi so
     * với product_type của chúng — cùng quy tắc với
     * ProductController::validateCategoryScope(). Đổi sang 'both' luôn hợp lệ.
     */
    private function guardAgainstScopeMismatch(Category $category, string $newScope, string $errorField): void
    {
        if ($newScope === 'both') {
            return;
        }

        // Phạm vi không đổi thì không kiểm tra lại — tránh chặn việc sửa
        // tên/ảnh chỉ vì dữ liệu cũ đã lệch sẵn từ trước.
        if ($newScope === $category->effectiveScope()) {
            return;
        }

        // Nhóm gốc: các danh mục con kế thừa scope của nó nên cũng bị ảnh
        // hưởng, phải kiểm tra cả sản phẩm gắn vào con.
        $categoryIds = [$category->id];
        if ($category->parent_id === null) {
            $categoryIds = array_merge($categoryIds, $category->children()->pluck('id')->all());
        }

        $mismatchQuery = Product::whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->where('product_type', '!=', $newScope);

        $mismatchCount = (clone $mismatchQuery)->count();
        if ($mismatchCount === 0) {
            return;
        }

        $sampleNames = $mismatchQuery->orderBy('name')->limit(5)->pluck('name')->all();
        $sampleText = '"' . implode('", "', $sampleNames) . '"' . ($mismatchCount > 5 ? ', ...' : '');
        $scopeLabel = Category::SCOPES[$newScope] ?? $newScope;

        throw \Illuminate\Validation\ValidationException::withMessages([
            $errorField => "Không thể đổi phạm vi danh mục sang \"{$scopeLabel}\": có {$mismatchCount} sản phẩm đang gắn (vào danh mục này hoặc danh mục con) không thuộc loại \"{$scopeLabel}\" — {$sampleText}. Hãy chuyển các sản phẩm này sang danh mục khác trước.",
        ]);
    }

    /**
     * Chặn chiều còn lại của cấu trúc 3 cấp: một nhóm gốc ĐANG CÓ danh mục
     * con không được gán cho nó một danh mục cha (dù cha đó là nhóm gốc hợp
     * lệ), vì các con hiện có của nó sẽ biến thành cháu (cấp 3) của cha mới.
     */
    private function guardAgainstReparentingWithChildren(Category $category): void
    {
        if ($category->children()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Không thể gán danh mục cha cho một nhóm đang có danh mục con — các danh mục con của nó sẽ trở thành cấp 3, hệ thống chỉ hỗ trợ tối đa 2 cấp.',
            ]);
        }
    }
}
