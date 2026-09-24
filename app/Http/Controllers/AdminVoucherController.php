<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminVoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::with(['products:id,name', 'categories:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $allProducts = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $allCategories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.vouchers.create', compact('allProducts', 'allCategories'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        // Tạo voucher + gắn phạm vi trong cùng 1 transaction: lỗi giữa chừng
        // không để lại voucher thiếu phạm vi (vd scope 'products' nhưng trống).
        DB::transaction(function () use ($validated, $request) {
            $voucher = Voucher::create($validated);
            $this->syncScopeRelations($voucher, $request);
        });

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Đã tạo mã giảm giá.');
    }

    public function edit(Voucher $voucher)
    {
        // Sản phẩm ĐANG gắn với voucher (kể cả đã ngừng bán hoặc đã xoá mềm)
        // phải luôn có mặt trong form — nếu không, lưu form sẽ sync() mất
        // chúng khỏi phạm vi áp dụng mà admin không hề chủ động bỏ chọn.
        $attachedProducts = $voucher->products()->withTrashed()->get(['products.id', 'products.name', 'products.is_active', 'products.deleted_at']);

        $allProducts = Product::where('is_active', true)
            ->get(['id', 'name', 'is_active', 'deleted_at'])
            ->concat($attachedProducts)
            ->unique('id')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $allCategories = Category::orderBy('name')->get(['id', 'name']);
        $selectedProductIds = $attachedProducts->pluck('id')->all();
        $selectedCategoryIds = $voucher->categories->pluck('id')->all();

        return view('admin.vouchers.edit', compact(
            'voucher',
            'allProducts',
            'allCategories',
            'selectedProductIds',
            'selectedCategoryIds'
        ));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $this->validateData($request, $voucher);

        DB::transaction(function () use ($voucher, $validated, $request) {
            $voucher->update($validated);
            $this->syncScopeRelations($voucher, $request);
        });

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Đã cập nhật mã giảm giá.');
    }

    public function destroy(Voucher $voucher)
    {
        $usedCount = $voucher->used_count;

        $voucher->delete();

        $message = 'Đã xoá mã giảm giá.';

        // FK orders.voucher_id là nullOnDelete nên xoá voucher không ảnh hưởng
        // đơn cũ, nhưng vẫn cảnh báo admin nếu mã đã từng được dùng.
        if ($usedCount > 0) {
            $message .= " (mã đã được dùng {$usedCount} lần, đơn cũ không bị ảnh hưởng)";
        }

        return redirect()->route('admin.vouchers.index')
            ->with('success', $message);
    }

    /**
     * Rule validate dùng chung cho store/update. used_count KHÔNG nằm trong
     * form/validate — không cho admin sửa tay để giữ đúng bằng chứng dùng
     * thực tế, tránh phá idempotency của OrderController::store()/VoucherController.
     */
    private function validateData(Request $request, ?Voucher $voucher = null): array
    {
        // Ép uppercase code trước khi đưa vào mảng validate (kể cả unique check).
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $uniqueCodeRule = 'unique:vouchers,code' . ($voucher ? ',' . $voucher->id : '');

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                $uniqueCodeRule,
            ],
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                Rule::when($request->input('discount_type') === 'percent', ['max:100']),
            ],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'scope_type' => 'required|in:all,products,categories',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.regex' => 'Mã giảm giá chỉ được chứa chữ hoa, số, gạch ngang và gạch dưới (ví dụ: WELCOME10).',
            'code.unique' => 'Mã giảm giá này đã tồn tại.',
            'discount_type.required' => 'Vui lòng chọn loại giảm giá.',
            'discount_type.in' => 'Loại giảm giá không hợp lệ.',
            'discount_value.required' => 'Vui lòng nhập giá trị giảm giá.',
            'discount_value.numeric' => 'Giá trị giảm giá phải là số.',
            'discount_value.min' => 'Giá trị giảm giá phải lớn hơn 0.',
            'discount_value.max' => 'Giá trị giảm giá theo phần trăm không được vượt quá 100.',
            'min_order_amount.numeric' => 'Giá trị đơn hàng tối thiểu phải là số.',
            'min_order_amount.min' => 'Giá trị đơn hàng tối thiểu không được âm.',
            'max_discount_amount.numeric' => 'Số tiền giảm tối đa phải là số.',
            'max_discount_amount.min' => 'Số tiền giảm tối đa không được âm.',
            'starts_at.date' => 'Ngày bắt đầu không hợp lệ.',
            'expires_at.date' => 'Ngày hết hạn không hợp lệ.',
            'expires_at.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày bắt đầu.',
            'usage_limit.integer' => 'Giới hạn số lần sử dụng phải là số nguyên.',
            'usage_limit.min' => 'Giới hạn số lần sử dụng phải lớn hơn hoặc bằng 1.',
            'scope_type.required' => 'Vui lòng chọn phạm vi áp dụng.',
            'scope_type.in' => 'Phạm vi áp dụng không hợp lệ.',
            'product_ids.array' => 'Danh sách sản phẩm không hợp lệ.',
            'product_ids.*.integer' => 'Sản phẩm được chọn không hợp lệ.',
            'product_ids.*.exists' => 'Sản phẩm được chọn không tồn tại.',
            'category_ids.array' => 'Danh sách danh mục không hợp lệ.',
            'category_ids.*.integer' => 'Danh mục được chọn không hợp lệ.',
            'category_ids.*.exists' => 'Danh mục được chọn không tồn tại.',
        ]);

        // Ràng buộc nghiệp vụ bổ sung theo scope_type: phải chọn ít nhất 1
        // sản phẩm/danh mục tương ứng, không thể validate bằng rule tĩnh vì
        // phụ thuộc giá trị của field khác đã được validate ở trên.
        if ($validated['scope_type'] === 'products' && empty($request->input('product_ids'))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'product_ids' => 'Vui lòng chọn ít nhất 1 sản phẩm áp dụng.',
            ]);
        }

        if ($validated['scope_type'] === 'categories' && empty($request->input('category_ids'))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'category_ids' => 'Vui lòng chọn ít nhất 1 danh mục áp dụng.',
            ]);
        }

        // Không cho đặt giới hạn lượt dùng thấp hơn số lượt đã dùng thực tế —
        // mã sẽ ở trạng thái "vượt giới hạn" vô nghĩa, số liệu khó hiểu.
        if ($voucher && isset($validated['usage_limit']) && (int) $validated['usage_limit'] < (int) $voucher->used_count) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'usage_limit' => "Giới hạn lượt dùng phải lớn hơn hoặc bằng số lượt đã dùng ({$voucher->used_count} lần).",
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * Đồng bộ quan hệ products()/categories() của voucher theo scope_type
     * hiện tại. Khi admin đổi phạm vi từ loại này sang loại khác, quan hệ
     * của loại cũ PHẢI được dọn sạch (detach) để không còn dữ liệu rác gây
     * tính sai phạm vi áp dụng mã giảm giá (Voucher::eligibleSubtotal()).
     */
    private function syncScopeRelations(Voucher $voucher, Request $request): void
    {
        if ($voucher->scope_type === 'products') {
            $voucher->products()->sync($request->input('product_ids', []));
            $voucher->categories()->detach();
        } elseif ($voucher->scope_type === 'categories') {
            $voucher->categories()->sync($request->input('category_ids', []));
            $voucher->products()->detach();
        } else {
            // 'all'
            $voucher->products()->detach();
            $voucher->categories()->detach();
        }
    }
}
