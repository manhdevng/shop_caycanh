@extends('layouts.shop')
@section('content')

@php
    // Chỉ lấy danh mục con của các nhóm gốc Cây/Hoa (scope plant/flower) —
    // bỏ hẳn các nhóm thuộc tính dùng chung (scope=both) khỏi khối danh mục
    // trang chủ và khỏi các nút lọc nhanh (F6, D1).
    $leafCategories = $categoryGroups
        ->whereIn('scope', ['plant', 'flower'])
        ->flatMap(fn ($g) => $g->children)
        ->values();
    // Nhóm gốc Cây cảnh / Hoa ("mục to", vd: Cây cảnh trong nhà, Hoa sự kiện)
    // — hiện thành thẻ trên carousel danh mục trang chủ. Hoa giờ đi theo NHÓM
    // giống cây, không còn lấy lẻ từng danh mục con. Nhóm chưa có danh mục con
    // thì ẩn, vì bấm vào sẽ ra trang trống.
    $plantGroups = $categoryGroups->where('scope', 'plant')
        ->filter(fn ($g) => $g->children->isNotEmpty())
        ->values();
    $flowerGroups = $categoryGroups->where('scope', 'flower')
        ->filter(fn ($g) => $g->children->isNotEmpty())
        ->values();
    $currentCategory = $activeCategories->first();
    // Bấm vào một nhóm = lọc theo toàn bộ danh mục con của nhóm đó -> tiêu đề
    // và breadcrumb phải là tên nhóm, không phải tên danh mục con đầu tiên.
    if ($activeCategories->count() > 1) {
        $activeParentIds = $activeCategories->pluck('parent_id')->unique();
        if ($activeParentIds->count() === 1 && $activeParentIds->first() !== null) {
            $currentCategory = $categoryGroups->firstWhere('id', $activeParentIds->first()) ?? $currentCategory;
        }
    }
    $activeIds = $activeCategories->pluck('id')->all();

    // Giá + dòng mô tả nhỏ của thẻ sản phẩm (D2, F8): sản phẩm có ≥2 phân
    // loại khác giá -> "Từ X₫" + "N lựa chọn {tên nhóm lựa chọn}"; ngược lại
    // dùng base_price như cũ (0 -> "Liên hệ giá" xử lý riêng ở nơi hiển thị).
    $priceLineFor = function ($product) {
        if ($product->hasPriceRange()) {
            return 'Từ ' . number_format($product->variants->min('price'), 0, ',', '.') . '₫';
        }
        if ($product->base_price > 0) {
            return number_format($product->base_price, 0, ',', '.') . '₫';
        }
        return null;
    };

    $specLineFor = function ($product) {
        $count = $product->variants->count();
        if ($count > 0) {
            return $count . ' lựa chọn ' . \Illuminate\Support\Str::lower($product->effective_variant_label);
        }
        return optional($product->categories->first())->name;
    };

    // Danh sách id sản phẩm đã yêu thích của user hiện tại — tính 1 LẦN duy nhất
    // ở đây (không phải trong mỗi lần lặp thẻ sản phẩm) để tránh N+1 query khi
    // vẽ nút trái tim trên từng thẻ sản phẩm bên dưới.
    $wishlistedIds = auth()->check() ? auth()->user()->wishlistedProducts->pluck('id')->all() : [];
@endphp

@if($showFeatured)
{{-- ==================== TRANG CHỦ ==================== --}}

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/scrollcraft/scrollcraft-shop.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('vendor/scrollcraft/scrollcraft.js') }}" defer></script>
@endpush
@include('shop.partials.home.home-boot')
@include('shop.partials.home.home-motion')

<div class="sc-home">
@include('shop.partials.home.hero')

@include('shop.partials.home.categories')

@include('shop.partials.home.gate-garden')

@include('shop.partials.home.grid-bestsellers')

@include('shop.partials.home.grid-plants')

@include('shop.partials.home.gate-season')

@include('shop.partials.home.grid-flowers')

@include('shop.partials.home.promises')

@include('shop.partials.home.gift')

@include('shop.partials.home.cta-all')
</div>

@else
{{-- ==================== DANH MỤC / TÌM KIẾM (lưới sản phẩm có lọc) ==================== --}}

@php
    $heroTitle = $search !== '' ? 'Kết quả tìm kiếm: "' . $search . '"' : ($currentCategory->name ?? 'Tất cả sản phẩm');
    $heroTagline = $currentCategory
        ? 'Khám phá các mẫu ' . mb_strtolower($currentCategory->name) . ' đang có tại cửa hàng.'
        : 'Khám phá toàn bộ các loại cây cảnh trong cửa hàng của chúng tôi.';
@endphp

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Tất cả sản phẩm</a>
    @if($currentCategory)
        <span>&rsaquo;</span>
        <span style="color:#1C1C1A">{{ $currentCategory->name }}</span>
    @endif
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 44px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">{{ $heroTitle }}</h1>
    <p style="font-size:16px;line-height:1.6;color:#6B6B66;max-width:560px;margin:0 0 14px">{{ $heroTagline }}</p>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $products->total() }} sản phẩm</p>
</section>

<section style="max-width:1400px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    <form method="GET" action="{{ route('shop.index') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:32px">
        @foreach($activeIds as $id)
            <input type="hidden" name="categories[]" value="{{ $id }}">
        @endforeach
        @if($search !== '')
            <input type="hidden" name="q" value="{{ $search }}">
        @endif
        @if(!empty($type))
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        <select name="sort" onchange="this.form.submit()" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;color:#1C1C1A;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:999px;padding:10px 18px;cursor:pointer">
            <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>Sắp xếp: Đề xuất</option>
            <option value="price-asc" {{ $sort === 'price-asc' ? 'selected' : '' }}>Giá thấp đến cao</option>
            <option value="price-desc" {{ $sort === 'price-desc' ? 'selected' : '' }}>Giá cao đến thấp</option>
            <option value="name-asc" {{ $sort === 'name-asc' ? 'selected' : '' }}>Theo tên A-Z</option>
        </select>

        {{-- Lọc theo khoảng giá — giữ nguyên các điều kiện khác nhờ input ẩn ở trên --}}
        <div style="display:flex;flex-direction:column;gap:4px">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <input type="number" name="price_min" min="0" step="1000" inputmode="numeric" value="{{ old('price_min', request('price_min')) }}" placeholder="Giá từ (đ)" style="width:130px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.02em;color:#1C1C1A;background:#FFFFFF;border:1px solid {{ $errors->has('price_min') ? '#B3261E' : '#E5E2DC' }};border-radius:999px;padding:10px 16px">
                <span style="color:#8A8680;font-size:12px">&ndash;</span>
                <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" value="{{ old('price_max', request('price_max')) }}" placeholder="Giá đến (đ)" style="width:130px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.02em;color:#1C1C1A;background:#FFFFFF;border:1px solid {{ $errors->has('price_max') ? '#B3261E' : '#E5E2DC' }};border-radius:999px;padding:10px 16px">
                <button type="submit" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;color:#FFFFFF;background:#5C2323;border:1px solid #5C2323;border-radius:999px;padding:10px 18px;cursor:pointer;white-space:nowrap">Áp dụng</button>
            </div>
            @error('price_min')
                <span style="color:#B3261E;font-size:12px;font-family:'Space Mono',monospace">{{ $message }}</span>
            @enderror
            @error('price_max')
                <span style="color:#B3261E;font-size:12px;font-family:'Space Mono',monospace">{{ $message }}</span>
            @enderror
        </div>
    </form>

    {{-- Lọc theo loại Cây cảnh / Hoa (D1, F5) — dùng chung route shop.index, giữ nguyên $sort/$q đang chọn --}}
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        @foreach(['' => 'Tất cả loại', 'plant' => 'Cây cảnh', 'flower' => 'Hoa'] as $typeValue => $typeLabel)
            @php $typeActive = ($type ?? '') === $typeValue; @endphp
            <a href="{{ route('shop.index', array_filter(['type' => $typeValue ?: null, 'sort' => $sort, 'q' => $search !== '' ? $search : null])) }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;padding:9px 16px;border-radius:999px;background:{{ $typeActive ? '#5C2323' : '#FFFFFF' }};color:{{ $typeActive ? '#FFFFFF' : '#1C1C1A' }};border:1px solid {{ $typeActive ? '#5C2323' : '#E5E2DC' }};white-space:nowrap;display:inline-block">{{ $typeLabel }}</a>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 32px">
        @foreach($leafCategories as $cat)
            @php $active = in_array($cat->id, $activeIds); @endphp
            <a href="{{ route('shop.index', ['categories' => [$cat->id], 'sort' => $sort, 'type' => $type ?? null]) }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;padding:10px 18px;border-radius:999px;background:{{ $active ? '#1C1C1A' : '#FFFFFF' }};color:{{ $active ? '#FFFFFF' : '#1C1C1A' }};border:1px solid {{ $active ? '#1C1C1A' : '#E5E2DC' }};white-space:nowrap;display:inline-block">{{ $cat->name }}</a>
        @endforeach
        <a href="{{ route('shop.index', ['sort' => $sort, 'type' => $type ?? null]) }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;padding:10px 18px;border-radius:999px;background:{{ empty($activeIds) ? '#1C1C1A' : '#FFFFFF' }};color:{{ empty($activeIds) ? '#FFFFFF' : '#1C1C1A' }};border:1px solid {{ empty($activeIds) ? '#1C1C1A' : '#E5E2DC' }};white-space:nowrap;display:inline-block">Tất cả bộ lọc</a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @forelse($products as $item)
            <div>
                <div style="position:relative">
                    <a href="{{ route('shop.show', $item->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                        @include('shop.partials.badge', ['product' => $item, 'bestSellerIds' => $bestSellerIds])
                        @unless($item->in_stock)
                            <span style="position:absolute;top:10px;left:10px;background:#6B7280;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;padding:4px 9px;border-radius:3px;z-index:1">Hết hàng</span>
                        @endunless
                        @if($item->main_image)
                            <img src="{{ asset('storage/' . $item->main_image) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $item->name }}</span>
                            </div>
                        @endif
                    </a>
                    @include('shop.partials.wishlist-button', ['product' => $item, 'wishlistedIds' => $wishlistedIds])
                </div>
                <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $item->name }}</p>
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ $specLineFor($item) }}</p>
                <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 10px">
                    @if($priceLineFor($item))
                        {{ $priceLineFor($item) }}
                    @else
                        <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                    @endif
                </p>
                @if($item->variants->isNotEmpty())
                    <a href="{{ route('shop.show', $item->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Chọn {{ $item->effective_variant_label }}</a>
                @elseif($item->base_price <= 0)
                    <a href="{{ route('shop.show', $item->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#8A8680;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Liên hệ</a>
                @else
                    <button type="button" onclick="addToCart({{ $item->id }}, this)" style="width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Thêm vào giỏ</button>
                @endif
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;color:#8A8680;font-size:14px;padding:60px 0">
                Không có sản phẩm nào phù hợp. <a href="{{ route('shop.index') }}" style="color:#5C2323;text-decoration:underline">Xem tất cả sản phẩm</a>
            </div>
        @endforelse
    </div>

    @if($products->hasPages())
        <div style="background:#F7F4EF;border-radius:16px;padding:36px 44px;margin-top:56px;display:flex;justify-content:space-between;align-items:center;gap:40px;flex-wrap:wrap">
            <div>
                <p style="font-family:'Anton',sans-serif;font-size:34px;letter-spacing:0.01em;color:#1C1C1A;margin:0 0 14px">{{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} / {{ $products->total() }}</p>
                <div style="display:flex;gap:4px">
                    @php $filledDashes = (int) round(($products->currentPage() / max(1, $products->lastPage())) * 24); @endphp
                    @for($i = 0; $i < 24; $i++)
                        <span style="width:9px;height:3px;background:{{ $i < $filledDashes ? '#5C2323' : '#E5E2DC' }};border-radius:2px"></span>
                    @endfor
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <a href="{{ $products->previousPageUrl() ?? '#' }}" aria-label="Trang trước" style="width:40px;height:40px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:{{ $products->onFirstPage() ? '#C7C3BB' : '#1C1C1A' }};font-size:16px;display:flex;align-items:center;justify-content:center;pointer-events:{{ $products->onFirstPage() ? 'none' : 'auto' }}">&lsaquo;</a>
                <a href="{{ $products->nextPageUrl() ?? '#' }}" aria-label="Trang sau" style="width:40px;height:40px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:{{ $products->hasMorePages() ? '#1C1C1A' : '#C7C3BB' }};font-size:16px;display:flex;align-items:center;justify-content:center;pointer-events:{{ $products->hasMorePages() ? 'auto' : 'none' }}">&rsaquo;</a>
            </div>
        </div>
    @endif
</section>

@endif

{{-- Câu hỏi thường gặp (nhóm "general") — hiển thị ở cả trang chủ và trang
     danh mục/tìm kiếm vì $faqs được controller tính sẵn không phụ thuộc
     $showFeatured (P3.1). --}}
@if($faqs->isNotEmpty())
<section style="max-width:900px;margin:0 auto;padding:0 24px clamp(64px,8vw,100px)">
    <h2 style="font-family:'Anton',sans-serif;font-size:clamp(20px,2.6vw,26px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 24px">Câu hỏi thường gặp</h2>
    <div style="border-top:1px solid #E5E2DC">
        @foreach($faqs as $faq)
            <details style="border-bottom:1px solid #E5E2DC">
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 0;cursor:pointer;font-size:15px;font-weight:500;color:#1C1C1A;list-style:none">{{ $faq->question }}</summary>
                <p style="font-size:14px;line-height:1.7;color:#6B6B66;margin:0 0 20px;max-width:640px">{{ $faq->answer }}</p>
            </details>
        @endforeach
    </div>
</section>
@endif

<script>
    // ==== Bật/tắt yêu thích trực tiếp trên thẻ sản phẩm (không reload) ====
    function toggleWishlist(productId, btn) {
        btn.disabled = true;

        const urlTemplate = "{{ route('wishlist.toggle', ['product' => '__ID__']) }}";

        fetch(urlTemplate.replace('__ID__', productId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(res => {
                if (!res.ok) throw new Error('request-failed');
                return res.json();
            })
            .then(data => {
                btn.disabled = false;
                const icon = btn.querySelector('.wishlist-heart-icon');
                if (icon) icon.setAttribute('fill', data.liked ? '#5C2323' : 'none');
                btn.setAttribute('aria-label', (data.liked ? 'Bỏ yêu thích' : 'Yêu thích'));
                if (typeof showToast === 'function') {
                    showToast(data.message);
                }
            })
            .catch(() => {
                // Chưa đăng nhập (401) hoặc lỗi khác -> chuyển hướng đăng nhập thay vì im lặng thất bại.
                window.location.href = "{{ route('login') }}";
            });
    }
</script>

@endsection
