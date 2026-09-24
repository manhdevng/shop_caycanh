@extends('layouts.app')

@section('title', 'Sửa mã giảm giá · Cây Cảnh Shop')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Sửa Mã Giảm Giá</h2>
    <a href="{{ route('admin.vouchers.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại
    </a>
</div>

@if ($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-6">
    <strong>Có lỗi xảy ra:</strong>
    <ul class="mt-2 list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.vouchers.update', $voucher) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Mã giảm giá <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code', $voucher->code) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5] uppercase" required>
                <p class="text-xs text-text-secondary mt-1">Chỉ chữ in hoa, số, gạch ngang/gạch dưới. Sẽ tự động viết hoa.</p>
                @error('code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Loại giảm giá <span class="text-red-500">*</span></label>
                <select name="discount_type" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                    <option value="percent" @selected(old('discount_type', $voucher->discount_type) === 'percent')>Phần trăm (%)</option>
                    <option value="fixed" @selected(old('discount_type', $voucher->discount_type) === 'fixed')>Số tiền cố định (đ)</option>
                </select>
                @error('discount_type')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Giá trị giảm <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="discount_value" value="{{ old('discount_value', $voucher->discount_value) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                @error('discount_value')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Phạm vi áp dụng <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4 mb-3">
                    <label class="inline-flex items-center gap-2 text-sm text-text-primary">
                        <input type="radio" name="scope_type" value="all" class="scope-type-radio w-4 h-4" {{ old('scope_type', $voucher->scope_type ?? 'all') === 'all' ? 'checked' : '' }}>
                        Toàn bộ sản phẩm
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-text-primary">
                        <input type="radio" name="scope_type" value="products" class="scope-type-radio w-4 h-4" {{ old('scope_type', $voucher->scope_type ?? 'all') === 'products' ? 'checked' : '' }}>
                        Chỉ một số sản phẩm
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-text-primary">
                        <input type="radio" name="scope_type" value="categories" class="scope-type-radio w-4 h-4" {{ old('scope_type', $voucher->scope_type ?? 'all') === 'categories' ? 'checked' : '' }}>
                        Chỉ một số danh mục
                    </label>
                </div>
                @error('scope_type')
                    <p class="text-xs text-red-600 mt-1 mb-2">{{ $message }}</p>
                @enderror

                <div id="scope-products-box" style="display:none" class="mb-3">
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Chọn sản phẩm áp dụng</label>
                    <select name="product_ids[]" multiple size="6" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                        @foreach(($allProducts ?? collect()) as $p)
                            <option value="{{ $p->id }}" @selected(in_array($p->id, old('product_ids', $selectedProductIds ?? [])))>{{ $p->name }}@if($p->deleted_at) (đã xoá)@elseif(!$p->is_active) (ngừng bán)@endif</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-text-secondary mt-1">Giữ Ctrl/Cmd để chọn nhiều sản phẩm. Sản phẩm "(ngừng bán)" / "(đã xoá)" chỉ hiện vì đang được gán cho mã này — bỏ chọn nếu muốn gỡ khỏi phạm vi.</p>
                    @error('product_ids')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('product_ids.*')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="scope-categories-box" style="display:none">
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Chọn danh mục áp dụng</label>
                    <select name="category_ids[]" multiple size="6" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                        @foreach(($allCategories ?? collect()) as $c)
                            <option value="{{ $c->id }}" @selected(in_array($c->id, old('category_ids', $selectedCategoryIds ?? [])))>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-text-secondary mt-1">Giữ Ctrl/Cmd để chọn nhiều danh mục.</p>
                    @error('category_ids')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('category_ids.*')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Đơn hàng tối thiểu</label>
                <input type="number" step="1" min="0" name="min_order_amount" value="{{ old('min_order_amount', $voucher->min_order_amount) }}" placeholder="Để trống nếu không yêu cầu" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                @error('min_order_amount')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Giảm tối đa</label>
                <input type="number" step="1" min="0" name="max_discount_amount" value="{{ old('max_discount_amount', $voucher->max_discount_amount) }}" placeholder="Để trống nếu không giới hạn" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                <p class="text-xs text-text-secondary mt-1">Chỉ áp dụng khi loại giảm giá là phần trăm (%).</p>
                @error('max_discount_amount')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Giới hạn lượt dùng</label>
                <input type="number" step="1" min="1" name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit) }}" placeholder="Để trống = không giới hạn" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                @error('usage_limit')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Bắt đầu từ</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $voucher->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                @error('starts_at')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Hết hạn</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $voucher->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                @error('expires_at')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2 md:col-span-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $voucher->is_active) ? 'checked' : '' }} class="w-4 h-4">
                <label for="is_active" class="text-sm font-semibold text-text-primary mono">Kích hoạt mã giảm giá này (cho phép sử dụng ngay)</label>
                @error('is_active')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2 px-4 py-3 bg-green-background/40 border border-green-border/40 rounded-xl text-sm text-text-secondary mono">
                Đã sử dụng: <span class="font-semibold text-text-primary">{{ $voucher->used_count }}</span> lần
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4 mt-6">
        <a href="{{ route('admin.vouchers.index') }}" class="px-8 py-3 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none">Hủy bỏ</a>
        <button type="submit" class="px-10 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i> Cập nhật
        </button>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        function toggleScopeBoxes() {
            const checked = document.querySelector('input[name="scope_type"]:checked');
            const value = checked ? checked.value : 'all';
            const productsBox = document.getElementById('scope-products-box');
            const categoriesBox = document.getElementById('scope-categories-box');
            if (productsBox) productsBox.style.display = (value === 'products') ? 'block' : 'none';
            if (categoriesBox) categoriesBox.style.display = (value === 'categories') ? 'block' : 'none';
        }

        document.querySelectorAll('.scope-type-radio').forEach(function (radio) {
            radio.addEventListener('change', toggleScopeBoxes);
        });

        toggleScopeBoxes();
    })();
</script>
@endpush
@endsection
