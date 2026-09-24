<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with('categories');

        if ($request->has('categories')) {
            $categoryIds = array_filter($request->categories);
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    $query->whereHas('categories', function($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
                    });
                }
            }
        }

        // Lọc theo loại sản phẩm (Cây/Hoa) — tab "Tất cả / Cây cảnh / Hoa" ở admin.
        $type = $request->input('type');
        if (in_array($type, ['plant', 'flower'], true)) {
            $query->where('product_type', $type);
        }

        // Tìm theo tên sản phẩm.
        $search = trim((string) $request->input('q'));
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $products = $query->paginate(20)->withQueryString();

        // Danh mục cho menu lọc: nhóm cha + loại cây con, kèm số lượng sản phẩm mỗi loại
        $categories = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount('products');
            }])
            ->get();

        // Danh sách loại cây đang được chọn lọc (để hiển thị dạng "chip" + tô sáng trong menu)
        $activeCategoryIds = array_filter($request->input('categories', []));
        $activeCategories = Category::whereIn('id', $activeCategoryIds)->get();

        return view('products.index', compact('products', 'categories', 'activeCategories', 'type', 'search'));
    }

    public function create()
    {
        // Nhóm danh mục gốc kèm scope (plant/flower/both) và danh mục con —
        // view dùng để ẩn/hiện nhóm theo loại sản phẩm đang chọn (D1).
        $categories = Category::whereNull('parent_id')->with('children')->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validator = $this->buildValidator($request);

        $validator->after(function ($validator) use ($request) {
            $this->validateCategoryScope($validator, $request);
        });

        $validated = $validator->validate();

        try {
            DB::beginTransaction();

            $data = $this->extractProductData($request);

            $product = Product::create($data);

            if ($request->has('categories')) {
                $product->categories()->attach($request->categories);
            }

            if ($request->input('pricing_mode') === 'variants') {
                foreach ($request->input('variants', []) as $index => $variantData) {
                    $variantRecord = [
                        'variant_name' => $variantData['variant_name'],
                        'price' => $variantData['price'],
                        'weight' => $variantData['weight'] ?? null,
                        'sort_order' => $variantData['sort_order'] ?? 0,
                    ];

                    if ($request->hasFile("variants.{$index}.image")) {
                        $variantRecord['image'] = $request->file("variants.{$index}.image")->store('products/variants', 'public');
                    }

                    $product->variants()->create($variantRecord);
                }

                $this->syncBasePriceFromVariants($product);
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Thêm sản phẩm thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi thêm sản phẩm: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Product $product)
    {
        $product->load(['categories.parent', 'variants']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        $selectedCategories = $product->categories->pluck('id')->toArray();
        $product->load('variants');

        return view('products.edit', compact('product', 'categories', 'selectedCategories'));
    }

    public function update(Request $request, Product $product)
    {
        $validator = $this->buildValidator($request, $product);

        $validator->after(function ($validator) use ($request) {
            $this->validateCategoryScope($validator, $request);
        });

        $validated = $validator->validate();

        try {
            DB::beginTransaction();

            $data = $this->extractProductData($request, $product);

            if ($request->hasFile('main_image')) {
                if ($product->main_image) {
                    Storage::disk('public')->delete($product->main_image);
                }
                $data['main_image'] = $request->file('main_image')->store('products', 'public');
            }

            $product->update($data);

            // 'categories' is required (min 1) by validation above, always sync.
            $product->categories()->sync($request->categories);

            if ($request->input('pricing_mode') === 'variants') {
                $this->syncVariants($request, $product);
                $this->syncBasePriceFromVariants($product);
            } else {
                // "Một giá": xoá toàn bộ phân loại cũ (và ảnh của chúng) —
                // base_price đã lấy trực tiếp từ request ở extractProductData().
                $this->deleteVariants($product->variants()->get());
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Cập nhật sản phẩm thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật sản phẩm: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Product $product)
    {
        try {
            // Soft delete: chỉ đánh dấu deleted_at, KHÔNG xóa ảnh/variants
            // để có thể khôi phục sản phẩm sau này (xem restore()).
            // Không có chức năng xóa vĩnh viễn: order_items.product_id có FK
            // cascade nên xóa cứng sẽ làm mất dòng hàng trong các đơn cũ.
            $product->delete();

            return redirect()->route('products.index')
                ->with('success', 'Đã chuyển sản phẩm vào thùng rác. Có thể khôi phục bất cứ lúc nào.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Danh sách sản phẩm đã xóa (thùng rác).
     */
    public function trashed()
    {
        $products = Product::onlyTrashed()->with('categories')->paginate(20);
        return view('products.trashed', compact('products'));
    }

    /**
     * Khôi phục sản phẩm đã xóa mềm.
     */
    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('products.trashed')->with('success', 'Đã khôi phục sản phẩm.');
    }

    /**
     * Rule validate dùng chung cho store/update. Rule của base_price/variants
     * thay đổi tuỳ pricing_mode (D3-P3, P4).
     */
    private function buildValidator(Request $request, ?Product $product = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'product_type' => 'required|in:' . implode(',', array_keys(Product::TYPES)),
            'pricing_mode' => 'required|in:single,variants',
            'weight' => 'nullable|integer|min:1',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'badge' => 'nullable|in:' . implode(',', array_keys(Product::BADGES)),
            'variant_label' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
        ];

        $messages = [
            'categories.required' => 'Vui lòng chọn ít nhất một kiểu cây/kiểu hoa.',
            'categories.min' => 'Vui lòng chọn ít nhất một kiểu cây/kiểu hoa.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
        ];

        if ($request->input('pricing_mode') === 'variants') {
            $rules['variants'] = 'required|array|min:1';
            if ($product) {
                $rules['variants.*.id'] = 'nullable|exists:product_variants,id';
            }
            $rules['variants.*.variant_name'] = 'required|string|max:255';
            $rules['variants.*.price'] = 'required|numeric|min:1000';
            $rules['variants.*.weight'] = 'nullable|integer|min:1';
            $rules['variants.*.sort_order'] = 'nullable|integer|min:0';
            $rules['variants.*.image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192';

            $messages['variants.required'] = 'Vui lòng thêm ít nhất một phân loại.';
            $messages['variants.min'] = 'Vui lòng thêm ít nhất một phân loại.';
            $messages['variants.*.variant_name.required'] = 'Vui lòng nhập tên cho từng phân loại.';
            $messages['variants.*.price.required'] = 'Vui lòng nhập giá cho từng phân loại.';
            $messages['variants.*.price.min'] = 'Giá phân loại phải từ 1.000đ trở lên.';
        } else {
            $rules['base_price'] = 'required|numeric|min:0';
        }

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Kiểm tra server-side: product_type hợp lệ + mọi danh mục đã chọn thuộc
     * đúng phạm vi (scope) của loại sản phẩm, và có ít nhất 1 danh mục thuộc
     * đúng loại (không chỉ toàn danh mục "both"). Xem D1, TC03/TC04.
     */
    private function validateCategoryScope($validator, Request $request): void
    {
        $productType = $request->input('product_type');
        $categoryIds = array_filter((array) $request->input('categories', []));

        if (empty($categoryIds) || !in_array($productType, array_keys(Product::TYPES), true)) {
            // Lỗi required/in đã được rule cơ bản báo, không cần lặp lại ở đây.
            return;
        }

        $scopes = DB::table('categories as c')
            ->leftJoin('categories as root', 'c.parent_id', '=', 'root.id')
            ->whereIn('c.id', $categoryIds)
            ->select('c.id', DB::raw('COALESCE(root.scope, c.scope) as effective_scope'))
            ->get();

        $hasExactType = $scopes->contains(fn ($row) => $row->effective_scope === $productType);
        $hasWrongScope = $scopes->contains(fn ($row) => !in_array($row->effective_scope, [$productType, 'both'], true));

        if (!$hasExactType || $hasWrongScope) {
            $validator->errors()->add('categories', 'Vui lòng chọn ít nhất một kiểu cây/kiểu hoa.');
        }
    }

    /**
     * Rút dữ liệu sản phẩm từ request (không gồm categories/variants/ảnh —
     * ảnh xử lý riêng vì cần xoá ảnh cũ trước khi ghi đè lúc update).
     */
    private function extractProductData(Request $request, ?Product $product = null): array
    {
        $data = [
            'name' => $request->input('name'),
            'product_type' => $request->input('product_type'),
            'description' => $request->input('description'),
            'is_active' => $request->has('is_active'),
            'badge' => $request->filled('badge') ? $request->input('badge') : null,
            'variant_label' => $request->filled('variant_label') ? $request->input('variant_label') : null,
            'weight' => $request->filled('weight') ? (int) $request->input('weight') : 200,
            'stock' => (int) $request->input('stock', 0),
        ];

        // pricing_mode = 'variants': base_price sẽ được server tính lại =
        // MIN(giá phân loại) sau khi lưu phân loại (syncBasePriceFromVariants).
        // Ở đây tạm giữ giá trị hiện có (update) hoặc 0 (store) để tránh lỗi
        // NOT NULL; giá trị đúng được ghi đè ngay sau đó trong cùng transaction.
        if ($request->input('pricing_mode') === 'single') {
            $data['base_price'] = $request->input('base_price');
        } elseif ($product) {
            $data['base_price'] = $product->base_price;
        } else {
            $data['base_price'] = 0;
        }

        if ($request->hasFile('main_image') && !$product) {
            $data['main_image'] = $request->file('main_image')->store('products', 'public');
        }

        return $data;
    }

    /**
     * Server tự gán base_price = MIN(giá phân loại) — bỏ qua giá client gửi.
     * Xem D3-P1.
     */
    private function syncBasePriceFromVariants(Product $product): void
    {
        $minPrice = $product->variants()->min('price');

        if ($minPrice !== null) {
            $product->update(['base_price' => $minPrice]);
        }
    }

    /**
     * Đồng bộ phân loại khi update: cập nhật/tạo mới theo dữ liệu submit,
     * xoá các phân loại không còn trong form (kèm ảnh) — giữ nguyên luồng
     * xoá ảnh đã có, chỉ thêm weight/sort_order.
     */
    private function syncVariants(Request $request, Product $product): void
    {
        $submittedVariantIds = [];

        foreach ($request->input('variants', []) as $index => $variantData) {
            $variantRecord = [
                'variant_name' => $variantData['variant_name'],
                'price' => $variantData['price'],
                'weight' => $variantData['weight'] ?? null,
                'sort_order' => $variantData['sort_order'] ?? 0,
            ];

            if (!empty($variantData['id'])) {
                // Update existing variant
                $variantModel = $product->variants()->find($variantData['id']);

                if ($request->hasFile("variants.{$index}.image")) {
                    if ($variantModel->image) {
                        Storage::disk('public')->delete($variantModel->image);
                    }
                    $variantRecord['image'] = $request->file("variants.{$index}.image")->store('products/variants', 'public');
                }

                $variantModel->update($variantRecord);
                $submittedVariantIds[] = $variantModel->id;
            } else {
                // Create new variant
                if ($request->hasFile("variants.{$index}.image")) {
                    $variantRecord['image'] = $request->file("variants.{$index}.image")->store('products/variants', 'public');
                }
                $newVariant = $product->variants()->create($variantRecord);
                $submittedVariantIds[] = $newVariant->id;
            }
        }

        // Delete removed variants
        $variantsToDelete = $product->variants()->whereNotIn('id', $submittedVariantIds)->get();
        $this->deleteVariants($variantsToDelete);
    }

    /**
     * Xoá 1 tập phân loại kèm ảnh của chúng. `order_items.variant_id` dùng
     * nullOnDelete nên đơn cũ vẫn giữ được variant_name snapshot (F13/TC14).
     */
    private function deleteVariants(iterable $variants): void
    {
        foreach ($variants as $variant) {
            if ($variant->image) {
                Storage::disk('public')->delete($variant->image);
            }
            $variant->delete();
        }
    }
}
