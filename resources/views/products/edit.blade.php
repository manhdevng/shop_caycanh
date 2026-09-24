@extends('layouts.app')

@section('title', 'Sửa sản phẩm · Cây Cảnh Shop')

@php
    $oldProductType = old('product_type', $product->product_type);
    $oldPricingMode = old('pricing_mode', $product->variants->isNotEmpty() ? 'variants' : 'single');

    // TC06: nếu validate lỗi, phải giữ lại TOÀN BỘ các dòng phân loại đã nhập
    // (không chỉ dòng đầu) từ old('variants'); nếu không có lỗi thì hiện phân
    // loại hiện có của sản phẩm.
    $oldVariantsInput = old('variants');
    if (is_array($oldVariantsInput)) {
        $variantRows = $oldVariantsInput;
    } else {
        $variantRows = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'price' => $variant->price,
                'weight' => $variant->weight,
                'sort_order' => $variant->sort_order,
                'image_url' => $variant->image ? asset('storage/' . $variant->image) : null,
            ];
        })->values()->toArray();
    }
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Sửa Sản Phẩm: {{ $product->name }}</h2>
    <a href="{{ route('products.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
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

<datalist id="variantLabelOptions">
    @foreach(\App\Models\Product::VARIANT_LABELS as $labelOption)
        <option value="{{ $labelOption }}">
    @endforeach
</datalist>
<datalist id="variantNameOptions"></datalist>

<form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Block 1: Loại sản phẩm -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6 flex items-center gap-3">
            <span class="bg-green-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold">1</span>
            Loại sản phẩm
        </h3>

        <div class="flex flex-wrap gap-6">
            @foreach(\App\Models\Product::TYPES as $typeValue => $typeLabel)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="product_type" value="{{ $typeValue }}" class="w-5 h-5" {{ $oldProductType === $typeValue ? 'checked' : '' }}>
                    <span class="text-text-primary font-medium">{{ $typeLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Block 2: Thông tin cơ bản -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-8 flex items-center gap-3">
            <span class="bg-green-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold">2</span>
            Thông tin cơ bản
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Tên sản phẩm <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" value="{{ old('name', $product->name) }}" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Khối lượng đóng gói (g) <span class="text-red-500">*</span></label>
                <input type="number" name="weight" min="1" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" value="{{ old('weight', $product->weight ?? 200) }}" required>
                <p class="text-xs text-text-secondary mt-2">Dùng để tính phí ship GHN.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Tồn kho <span class="text-red-500">*</span></label>
                <input type="number" name="stock" min="0" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" value="{{ old('stock', $product->stock ?? 0) }}" required>
                @error('stock')
                    <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Ảnh đại diện chính</label>
                <input type="file" name="main_image" class="w-full rounded-xl border-green-border/50 border px-4 py-3 bg-[#f8f9f5] focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-sm text-text-secondary" accept="image/*" id="mainImageInput">
                <div class="mt-4 {{ $product->main_image ? '' : 'hidden' }}" id="mainImagePreviewContainer">
                    <img id="mainImagePreview" src="{{ $product->main_image ? asset('storage/' . $product->main_image) : '' }}" class="w-40 h-40 object-cover rounded-2xl shadow-sm border border-green-border/50">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Mô tả sản phẩm</label>
                <textarea name="description" rows="4" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Block 3: Danh mục & thuộc tính -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6 flex items-center gap-3">
            <span class="bg-green-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold">3</span>
            Danh mục & thuộc tính lọc
        </h3>
        <p class="text-sm text-text-secondary mb-4">Chỉ hiện các nhóm phù hợp với loại sản phẩm đang chọn ở trên.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-[#f8f9f5] p-5 rounded-2xl border border-green-border/30" id="categoryGroups">
            @foreach($categories as $group)
                <div class="category-group" data-scope="{{ $group->scope }}">
                    <h4 class="font-semibold text-text-primary mb-3 pb-2 border-b border-green-border/20 text-sm">{{ $group->name }}</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($group->children as $childCategory)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="categories[]" value="{{ $childCategory->id }}" class="w-4 h-4 rounded border-green-border/50 focus:ring-green-primary" {{ in_array($childCategory->id, old('categories', $selectedCategories)) ? 'checked' : '' }}>
                                <span class="text-text-secondary group-hover:text-text-primary transition-colors text-sm">{{ $childCategory->name }}</span>
                            </label>
                        @empty
                            <p class="text-text-secondary text-xs italic">Chưa có danh mục con nào.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Block 4: Giá & phân loại -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6 flex items-center gap-3">
            <span class="bg-green-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold">4</span>
            Giá & phân loại
        </h3>

        <div class="flex flex-wrap gap-6 mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="pricing_mode" value="single" class="w-5 h-5" {{ $oldPricingMode === 'single' ? 'checked' : '' }}>
                <span class="text-text-primary font-medium">Một giá duy nhất</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="pricing_mode" value="variants" class="w-5 h-5" {{ $oldPricingMode === 'variants' ? 'checked' : '' }}>
                <span class="text-text-primary font-medium">Nhiều phân loại</span>
            </label>
        </div>

        <!-- Một giá -->
        <div id="singlePriceBlock">
            <label class="block text-sm font-semibold text-text-primary mb-2 mono">Giá bán (đ) <span class="text-red-500">*</span></label>
            <input type="number" name="base_price" min="0" class="w-full md:w-64 rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" value="{{ old('base_price', $product->base_price) }}">
            <p class="text-xs text-text-secondary mt-2">Để 0 nếu muốn hiện "Liên hệ giá".</p>
        </div>

        <!-- Nhiều phân loại -->
        <div id="variantsBlock">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Tên nhóm lựa chọn (VD: Kích cỡ, Số bông)</label>
                <input type="text" name="variant_label" list="variantLabelOptions" value="{{ old('variant_label', $product->variant_label) }}" placeholder="Để trống dùng mặc định theo loại sản phẩm" class="w-full md:w-96 rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
            </div>

            <div class="flex justify-between items-center mb-4">
                <label class="block text-base font-semibold text-text-primary mono">Bảng phân loại</label>
                <button type="button" id="addVariantBtn" class="px-4 py-2 bg-white text-text-primary rounded-pill font-semibold hover:bg-green-background transition-colors flex items-center gap-2 text-sm border border-green-border shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> Thêm phân loại
                </button>
            </div>

            <div class="overflow-x-auto border border-green-border/50 rounded-2xl bg-white shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse" id="variantsTable">
                    <thead>
                        <tr class="border-b border-green-border/50 bg-[#f8f9f5]">
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên phân loại</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider w-36">Giá (đ)</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider w-32">Khối lượng (g)</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider w-44">Ảnh</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider w-24">Thứ tự</th>
                            <th class="py-3 px-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="variantsContainer" class="divide-y divide-green-border/20">
                        @foreach($variantRows as $index => $row)
                        <tr class="group variant-row hover:bg-green-background/30 transition-colors" id="variant-row-{{ $index }}">
                            @if(!empty($row['id']))
                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $row['id'] }}">
                            @endif
                            <td class="py-3 px-4">
                                <input type="text" name="variants[{{ $index }}][variant_name]" list="variantNameOptions" value="{{ $row['variant_name'] ?? '' }}" placeholder="VD: Size M" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary" required>
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" name="variants[{{ $index }}][price]" min="1000" value="{{ isset($row['price']) ? (is_numeric($row['price']) ? floor($row['price']) : $row['price']) : '' }}" placeholder="vd: 150000" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary" required>
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" name="variants[{{ $index }}][weight]" min="1" value="{{ $row['weight'] ?? '' }}" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                            </td>
                            <td class="py-3 px-4">
                                @if(!empty($row['image_url']))
                                    <img src="{{ $row['image_url'] }}" class="w-12 h-12 object-cover rounded-lg shadow-sm mb-2 border border-green-border/30">
                                @endif
                                <input type="file" name="variants[{{ $index }}][image]" class="text-xs w-full text-text-secondary" accept="image/*">
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" name="variants[{{ $index }}][sort_order]" min="0" value="{{ $row['sort_order'] ?? $index }}" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button type="button" class="text-text-secondary hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors remove-variant-btn">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="noVariantsMsg" class="text-center py-8 text-text-secondary italic mono text-sm {{ count($variantRows) > 0 ? 'hidden' : '' }}">Chưa có phân loại nào được thêm.</div>
            </div>
        </div>
    </div>

    <!-- Block 5: Hiển thị -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6 flex items-center gap-3">
            <span class="bg-green-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold">5</span>
            Hiển thị
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" class="w-5 h-5 rounded border-green-border/50 focus:ring-green-primary" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm font-semibold text-text-primary cursor-pointer mono">Hiển thị bán ngay</label>
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Nhãn hiển thị</label>
                <select name="badge" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                    <option value="" {{ old('badge', $product->badge) === null || old('badge', $product->badge) === '' ? 'selected' : '' }}>Tự động</option>
                    @foreach(\App\Models\Product::BADGES as $badgeValue => $badgeLabel)
                        <option value="{{ $badgeValue }}" {{ old('badge', $product->badge) === $badgeValue ? 'selected' : '' }}>{{ $badgeLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4 mb-12">
        <a href="{{ route('products.index') }}" class="px-8 py-3 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none">Hủy bỏ</a>
        <button type="submit" class="px-10 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i> Cập nhật sản phẩm
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Main Image Preview
        const mainImageInput = document.getElementById('mainImageInput');
        const mainImagePreviewContainer = document.getElementById('mainImagePreviewContainer');
        const mainImagePreview = document.getElementById('mainImagePreview');

        mainImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    mainImagePreview.src = e.target.result;
                    mainImagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // ==== Loại sản phẩm: ẩn/hiện nhóm danh mục theo scope (D1, TC05) ====
        function updateCategoryGroups() {
            const checked = document.querySelector('input[name="product_type"]:checked');
            const type = checked ? checked.value : 'plant';

            document.querySelectorAll('.category-group').forEach(function(group) {
                const scope = group.dataset.scope;
                if (scope === type || scope === 'both') {
                    group.classList.remove('hidden');
                } else {
                    group.classList.add('hidden');
                    group.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
                        cb.checked = false;
                    });
                }
            });
        }

        // ==== Gợi ý tên phân loại theo loại sản phẩm (D2) ====
        const variantNameSuggestions = {
            plant: ['Mini (10–20cm)', 'S (20–40cm)', 'M (40–70cm)', 'L (70–120cm)', 'XL (trên 120cm)', 'Chậu nhựa', 'Chậu gốm', 'Chậu sứ trắng', 'Chậu xi măng'],
            flower: ['5 bông', '10 bông', '20 bông', '50 bông', 'Bó nhỏ', 'Bó vừa', 'Bó lớn'],
        };

        function updateVariantNameOptions() {
            const checked = document.querySelector('input[name="product_type"]:checked');
            const type = checked ? checked.value : 'plant';
            const datalist = document.getElementById('variantNameOptions');
            datalist.innerHTML = '';
            (variantNameSuggestions[type] || []).forEach(function(name) {
                const opt = document.createElement('option');
                opt.value = name;
                datalist.appendChild(opt);
            });
        }

        document.querySelectorAll('input[name="product_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateCategoryGroups();
                updateVariantNameOptions();
            });
        });

        // Chạy lần đầu khi tải trang — phải hiện đúng nhóm/loại theo dữ liệu hiện có
        updateCategoryGroups();
        updateVariantNameOptions();

        // ==== Cách bán: ẩn/hiện giá gốc / bảng phân loại (D3-P4) ====
        function updatePricingMode() {
            const checked = document.querySelector('input[name="pricing_mode"]:checked');
            const mode = checked ? checked.value : 'single';
            const singleBlock = document.getElementById('singlePriceBlock');
            const variantsBlock = document.getElementById('variantsBlock');

            if (mode === 'single') {
                singleBlock.classList.remove('hidden');
                variantsBlock.classList.add('hidden');
            } else {
                singleBlock.classList.add('hidden');
                variantsBlock.classList.remove('hidden');
            }
        }

        document.querySelectorAll('input[name="pricing_mode"]').forEach(function(radio) {
            radio.addEventListener('change', updatePricingMode);
        });

        updatePricingMode();

        // ==== Dynamic Variants ====
        const variantsContainer = document.getElementById('variantsContainer');
        const addVariantBtn = document.getElementById('addVariantBtn');
        const noVariantsMsg = document.getElementById('noVariantsMsg');
        let variantIndex = {{ count($variantRows) > 0 ? max(array_keys($variantRows)) + 1 : 0 }};

        function updateVariantsMessage() {
            if (variantsContainer.children.length === 0) {
                noVariantsMsg.classList.remove('hidden');
                variantsContainer.classList.add('hidden');
            } else {
                noVariantsMsg.classList.add('hidden');
                variantsContainer.classList.remove('hidden');
            }
        }

        addVariantBtn.addEventListener('click', function() {
            const rowCount = variantsContainer.querySelectorAll('.variant-row').length;
            const html = `
                <tr class="group variant-row hover:bg-green-background/30 transition-colors" id="variant-row-${variantIndex}">
                    <td class="py-3 px-4">
                        <input type="text" name="variants[${variantIndex}][variant_name]" list="variantNameOptions" placeholder="VD: Size M" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary" required>
                    </td>
                    <td class="py-3 px-4">
                        <input type="number" name="variants[${variantIndex}][price]" min="1000" placeholder="vd: 150000" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary" required>
                    </td>
                    <td class="py-3 px-4">
                        <input type="number" name="variants[${variantIndex}][weight]" min="1" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                    </td>
                    <td class="py-3 px-4">
                        <input type="file" name="variants[${variantIndex}][image]" class="text-xs w-full text-text-secondary" accept="image/*">
                    </td>
                    <td class="py-3 px-4">
                        <input type="number" name="variants[${variantIndex}][sort_order]" min="0" value="${rowCount}" class="w-full rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                    </td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" class="text-text-secondary hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors remove-variant-btn">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `;

            variantsContainer.insertAdjacentHTML('beforeend', html);
            variantIndex++;
            lucide.createIcons();
            updateVariantsMessage();
        });

        variantsContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-variant-btn');
            if (btn) {
                btn.closest('tr').remove();
                updateVariantsMessage();
            }
        });

        updateVariantsMessage();

    });
</script>
@endsection
