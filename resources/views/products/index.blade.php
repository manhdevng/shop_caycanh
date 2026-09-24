@extends('layouts.app')

@section('title', 'Sản phẩm · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Quản lý Sản phẩm</h2>
        <div class="flex items-center gap-2">
            <a class="px-5 py-3 bg-white text-text-secondary rounded-pill font-medium hover:bg-green-background transition-colors flex items-center gap-2 text-decoration-none border border-green-border text-sm" href="{{ route('products.trashed') }}">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Thùng rác
            </a>
            <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('products.create') }}">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                Thêm sản phẩm mới
            </a>
        </div>
    </div>

    <!-- Tab lọc Tất cả / Cây cảnh / Hoa + ô tìm theo tên -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2">
            @php
                $typeTabs = ['' => 'Tất cả'] + \App\Models\Product::TYPES;
            @endphp
            @foreach($typeTabs as $typeValue => $typeLabel)
                <a href="{{ route('products.index', array_filter(array_merge(request()->except('type', 'page'), ['type' => $typeValue]))) }}"
                   class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors {{ (string) $type === (string) $typeValue ? 'bg-green-primary text-white border-green-border' : 'bg-white text-text-secondary border-green-border hover:bg-green-background' }}">
                    {{ $typeLabel }}
                </a>
            @endforeach
        </div>

        <form action="{{ route('products.index') }}" method="GET" class="flex items-center gap-2">
            @if($type)
                <input type="hidden" name="type" value="{{ $type }}">
            @endif
            <input type="text" name="q" value="{{ old('q', $search ?? request('q')) }}" placeholder="Tìm theo tên sản phẩm..." class="rounded-xl border-green-border/50 border px-4 py-2 bg-[#f8f9f5] focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-sm text-text-primary w-64">
            <button type="submit" class="px-4 py-2 bg-white border border-green-border rounded-pill text-text-secondary hover:text-text-primary hover:bg-green-background transition-colors">
                <i data-lucide="search" class="w-4 h-4"></i>
            </button>
        </form>
    </div>

    <!-- Menu danh mục cây cảnh: bấm mở ra, click 1 loại cây là lọc & hiện ảnh sản phẩm ngay -->
    <div class="mb-8">
        <details class="relative group/menu" id="categoryMenu">
            <summary class="list-none cursor-pointer select-none w-fit px-6 py-3 bg-[#f8f9f5] border border-green-border/50 rounded-pill flex items-center gap-2 font-semibold text-text-primary hover:border-green-primary transition-colors mono text-sm [&::-webkit-details-marker]:hidden">
                <i data-lucide="leaf" class="w-4 h-4"></i>
                Danh mục cây cảnh
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform group-open/menu:rotate-180"></i>
            </summary>

            <div class="absolute z-20 mt-2 w-[720px] max-w-[90vw] bg-white border border-green-border rounded-[24px] shadow-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse($categories as $parent)
                <div>
                    <h4 class="font-semibold text-text-primary mb-3 pb-2 border-b border-green-border/20 text-sm mono uppercase tracking-wider">{{ $parent->name }}</h4>
                    <div class="space-y-1">
                        @forelse($parent->children as $child)
                            <a href="{{ route('products.index', ['categories' => [$child->id]]) }}"
                               class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl hover:bg-green-background transition-colors text-decoration-none {{ in_array($child->id, request('categories', [])) ? 'bg-green-primary/40 font-semibold' : '' }}">
                                <span class="text-text-primary text-sm">{{ $child->name }}</span>
                                <span class="text-text-secondary text-xs mono">{{ $child->products_count }}</span>
                            </a>
                        @empty
                            <p class="text-text-secondary text-xs italic px-3">Chưa có loại cây nào.</p>
                        @endforelse
                    </div>
                </div>
                @empty
                <p class="text-text-secondary text-sm italic md:col-span-3">
                    Chưa có danh mục nào. <a href="{{ route('categories.create') }}" class="text-green-700 underline">Thêm danh mục</a>.
                </p>
                @endforelse
            </div>
        </details>

        <!-- Chip hiển thị loại cây đang lọc -->
        @if($activeCategories->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 mt-4">
            <span class="text-sm text-text-secondary mono">Đang lọc:</span>
            @foreach($activeCategories as $cat)
                <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-primary/50 border border-green-border text-text-primary rounded-pill text-sm">
                    {{ $cat->name }}
                    <a href="{{ route('products.index') }}" title="Xóa lọc" class="text-text-secondary hover:text-red-600 text-decoration-none leading-none">✕</a>
                </span>
            @endforeach
            <a href="{{ route('products.index') }}" class="text-sm text-red-500 hover:text-red-700 text-decoration-none ml-2">Xóa tất cả lọc</a>
        </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">ID</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Hình ảnh</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Loại</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Giá</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ $product->id }}</td>
                    <td class="py-5 pr-4">
                        @if($product->main_image)
                            <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" class="w-14 h-14 object-cover rounded-xl border border-green-border/30">
                        @else
                            <div class="w-14 h-14 bg-green-background rounded-xl flex items-center justify-center text-green-border">
                                <i data-lucide="image" class="w-5 h-5"></i>
                            </div>
                        @endif
                    </td>
                    <td class="py-5 pr-4 font-medium text-text-primary text-lg">
                        {{ $product->name }}
                        @php
                            $badgeLabel = ($product->badge && $product->badge !== 'none') ? (\App\Models\Product::BADGES[$product->badge] ?? null) : null;
                        @endphp
                        @if($badgeLabel)
                            <span class="inline-block ml-2 align-middle px-2 py-0.5 bg-green-primary text-white rounded-pill text-xs mono">{{ $badgeLabel }}</span>
                        @endif
                        <div class="text-sm text-text-secondary font-normal mt-1 flex flex-wrap gap-1">
                            @foreach($product->categories as $category)
                                <span class="px-2 py-0.5 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="py-5 pr-4">
                        <span class="inline-block whitespace-nowrap px-2 py-0.5 bg-[#f8f9f5] border border-green-border/50 text-text-secondary rounded-pill text-xs mono">{{ \App\Models\Product::TYPES[$product->product_type] ?? $product->product_type }}</span>
                    </td>
                    <td class="py-5 pr-4 text-text-primary font-medium">
                        @if($product->hasPriceRange())
                            Từ {{ number_format($product->variants->min('price'), 0, ',', '.') }} đ
                        @elseif((float) $product->base_price <= 0)
                            Liên hệ giá
                        @else
                            {{ number_format($product->base_price, 0, ',', '.') }} đ
                        @endif
                    </td>
                    <td class="py-5 pr-4">
                        @if($product->is_active)
                            <span class="inline-block whitespace-nowrap px-3 py-1 bg-green-primary border border-green-border/50 text-white rounded-pill text-xs mono font-semibold">Hiển thị</span>
                        @else
                            <span class="inline-block whitespace-nowrap px-3 py-1 bg-gray-100 border border-gray-300 text-text-secondary rounded-pill text-xs mono font-semibold">Đã ẩn</span>
                        @endif
                    </td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('products.show', $product->id) }}" class="p-2 text-text-secondary hover:text-green-600 hover:bg-green-50 rounded-full transition-colors" title="Xem">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </a>
                            <a href="{{ route('products.edit', $product->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Chuyển sản phẩm này vào thùng rác? Bạn có thể khôi phục lại sau.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xóa">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-text-secondary">
                        Chưa có sản phẩm nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>

<script>
    // Đóng menu "Danh mục cây cảnh" khi click ra ngoài
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('categoryMenu');
        if (menu && menu.open && !menu.contains(e.target)) {
            menu.open = false;
        }
    });
</script>
@endsection
