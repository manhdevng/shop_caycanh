@extends('layouts.app')

@section('title', $product->name . ' · Cây Cảnh Shop')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">{{ $product->name }}</h2>
    <div class="flex items-center gap-2">
        <a href="{{ route('products.edit', $product->id) }}" class="px-5 py-2 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none flex items-center gap-2 text-sm">
            <i data-lucide="edit-2" class="w-4 h-4"></i> Sửa
        </a>
        <a href="{{ route('products.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Ảnh + trạng thái -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        @if($product->main_image)
            <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" class="w-full aspect-square object-cover rounded-2xl border border-green-border/50">
        @else
            <div class="w-full aspect-square bg-green-background rounded-2xl flex items-center justify-center text-green-border">
                <i data-lucide="image" class="w-10 h-10"></i>
            </div>
        @endif

        <div class="mt-4 flex items-center justify-between">
            @if($product->is_active)
                <span class="px-3 py-1 bg-green-primary border border-green-border/50 text-white rounded-pill text-xs mono font-semibold">Hiển thị</span>
            @else
                <span class="px-3 py-1 bg-gray-100 border border-gray-300 text-text-secondary rounded-pill text-xs mono font-semibold">Đã ẩn</span>
            @endif
            <span class="text-text-secondary text-xs mono">ID: {{ $product->id }}</span>
        </div>
    </div>

    <!-- Thông tin chi tiết -->
    <div class="md:col-span-2 bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="mb-6">
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Giá gốc</div>
            <div class="text-3xl font-medium gloock text-text-primary">
                @if($product->hasPriceRange())
                    Từ {{ number_format($product->variants->min('price'), 0, ',', '.') }} đ
                @elseif((float) $product->base_price <= 0)
                    Liên hệ giá
                @else
                    {{ number_format($product->base_price, 0, ',', '.') }} đ
                @endif
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Loại sản phẩm</div>
                <div class="text-text-primary font-medium">{{ \App\Models\Product::TYPES[$product->product_type] ?? $product->product_type }}</div>
            </div>

            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Nhãn hiển thị</div>
                <div class="text-text-primary font-medium">
                    {{ $product->badge ? (\App\Models\Product::BADGES[$product->badge] ?? $product->badge) : 'Tự động' }}
                </div>
            </div>

            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Tên nhóm lựa chọn</div>
                <div class="text-text-primary font-medium">{{ $product->effective_variant_label }}</div>
            </div>

            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Khối lượng đóng gói</div>
                <div class="text-text-primary font-medium">{{ $product->weight ?? 200 }} g</div>
            </div>
        </div>

        <div class="mb-6">
            <div class="text-sm font-semibold text-text-secondary mono mb-2">Loại cây</div>
            <div class="flex flex-wrap gap-2">
                @forelse($product->categories as $category)
                    <a href="{{ route('categories.show', $category->id) }}" class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-sm text-decoration-none hover:bg-green-primary hover:text-white transition-colors">
                        {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                    </a>
                @empty
                    <span class="text-text-secondary italic text-sm">Chưa gắn loại cây nào.</span>
                @endforelse
            </div>
        </div>

        <div class="mb-6">
            <div class="text-sm font-semibold text-text-secondary mono mb-2">Mô tả</div>
            <p class="text-text-primary whitespace-pre-line">{{ $product->description ?: 'Chưa có mô tả.' }}</p>
        </div>
    </div>
</div>

<!-- Phân loại / biến thể -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mt-6">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Phân loại cây</h3>

    @if($product->variants->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ảnh</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên phân loại</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Giá</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khối lượng</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Thứ tự</th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->variants as $variant)
                <tr class="border-b border-green-border/20">
                    <td class="py-3 pr-4">
                        @if($variant->image)
                            <img src="{{ asset('storage/' . $variant->image) }}" class="w-12 h-12 object-cover rounded-lg border border-green-border/30">
                        @else
                            <div class="w-12 h-12 bg-green-background rounded-lg"></div>
                        @endif
                    </td>
                    <td class="py-3 pr-4 text-text-primary font-medium">{{ $variant->variant_name }}</td>
                    <td class="py-3 pr-4 text-text-primary">{{ number_format($variant->price, 0, ',', '.') }} đ</td>
                    <td class="py-3 pr-4 text-text-primary">{{ $variant->weight ?? $product->weight ?? 200 }} g</td>
                    <td class="py-3 text-text-primary mono">{{ $variant->sort_order }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-text-secondary italic">Sản phẩm này không có phân loại — dùng giá gốc.</p>
    @endif
</div>
@endsection
