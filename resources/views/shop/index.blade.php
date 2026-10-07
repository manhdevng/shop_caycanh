@extends('layouts.shop')
@section('content')

@php
    // Chỉ lấy danh mục con của các nhóm gốc Cây/Hoa (scope plant/flower) —
    // bỏ hẳn các nhóm thuộc tính dùng chung (scope=both) khỏi khối danh mục
    // trang chủ và khỏi các nút lọc nhanh (F6, D1).
    $leafCategories = $categoryGroups
        ->whereIn('scope', ['plant', 'flower'])
        ->flatMap(fn ($g) => $g->children)
        // Danh mục chưa có sản phẩm đang bán thì không đưa ra làm nút lọc
        // (bấm vào chỉ ra trang trống) — trừ khi khách đang đứng ở đúng nó.
        ->filter(fn ($c) => $c->products_count > 0 || $activeCategories->contains('id', $c->id))
        ->values();
    // Nhóm gốc Cây cảnh / Hoa ("mục to", vd: Cây cảnh trong nhà, Hoa sự kiện)
    // — hiện thành thẻ trong lưới danh mục trang chủ. Hoa giờ đi theo NHÓM
    // giống cây, không còn lấy lẻ từng danh mục con. Nhóm chưa có sản phẩm
    // đang bán nào (active_products_count — đếm sản phẩm khác nhau, xem
    // ShopController::categoryGroupsWithCounts) thì ẩn, vì bấm vào ra trang trống.
    $plantGroups = $categoryGroups->where('scope', 'plant')
        ->filter(fn ($g) => $g->active_products_count > 0)
        ->values();
    $flowerGroups = $categoryGroups->where('scope', 'flower')
        ->filter(fn ($g) => $g->active_products_count > 0)
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
    <link rel="preload" as="image" href="{{ asset('images/hero-bonsai.webp') }}" fetchpriority="high">
    <link rel="stylesheet" href="{{ asset('vendor/scrollcraft/scrollcraft-shop.css') }}">
@endpush
@include('shop.partials.home.home-motion')

{{-- Hành trình trang chủ: hero -> danh mục -> sản phẩm nổi bật
     -> các khu cây, hoa -> quà tặng -> "Cây nào hợp mệnh bạn?" (la bàn
     phong thủy) -> cam kết -> lời mời cuối.
     Danh mục đứng thứ hai để khách chọn nhóm cây hoặc hoa ngay sau khi vào trang. --}}
<div class="sc-home">
@include('shop.partials.home.hero')

@include('shop.partials.home.categories')

@include('shop.partials.home.grid-featured')

@include('shop.partials.home.gate-garden')

@include('shop.partials.home.grid-indoor')

@include('shop.partials.home.grid-office')

@include('shop.partials.home.gate-season')

@include('shop.partials.home.grid-outdoor')

@include('shop.partials.home.grid-flowers')

@include('shop.partials.home.gift')

@include('shop.partials.home.phong-thuy-teaser')

@include('shop.partials.home.promises')

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
    {{-- Bộ lọc gom vào một khối mở/đóng. Trên điện thoại, ba nhóm lọc (sắp
         xếp + khoảng giá + loại + danh mục) xếp liền nhau đẩy sản phẩm đầu
         tiên xuống quá sâu, nên mặc định thu lại sau một nút bấm; desktop vẫn
         mở sẵn (CSS ép hiện, nút bấm bị ẩn). Dùng <details> thay vì tự viết
         JS: bàn phím và trình đọc màn hình hiểu sẵn trạng thái đóng/mở. --}}
    @php
        $activeFilterCount = count($activeIds)
            + (!empty($type) ? 1 : 0)
            + (request()->filled('price_min') || request()->filled('price_max') ? 1 : 0);
    @endphp
    <details class="sc-filters">
        <summary class="sc-filters__toggle">
            <span>Bộ lọc &amp; sắp xếp</span>
            @if($activeFilterCount > 0)
                <span class="sc-filters__count">{{ $activeFilterCount }} đang áp dụng</span>
            @endif
        </summary>
        <div class="sc-filters__body">
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
        </div>
    </details>

    <style>
        /* Desktop: bộ lọc luôn mở, không có nút bấm — giữ nguyên trải nghiệm cũ.
           Ghi đè hành vi mặc định của <details> (ẩn con khi chưa open) bằng
           display:block, các trình duyệt hiện nay đều cho phép. */
        .sc-filters__toggle { display: none; }
        .sc-filters .sc-filters__body { display: block; }
        @media (max-width: 860px) {
            .sc-filters { margin-bottom: 24px; border: 1px solid #E5E2DC; border-radius: 14px; }
            .sc-filters__toggle {
                display: flex; align-items: center; justify-content: space-between; gap: 12px;
                min-height: 48px; padding: 0 16px; cursor: pointer; list-style: none;
                font-family: 'Space Mono', monospace; font-size: 12px; letter-spacing: .05em;
                text-transform: uppercase; color: #1C1C1A;
            }
            .sc-filters__toggle::-webkit-details-marker { display: none; }
            .sc-filters__toggle::after { content: '+'; font-size: 18px; line-height: 1; color: #5C2323; }
            .sc-filters[open] .sc-filters__toggle::after { content: '\2212'; }
            .sc-filters[open] .sc-filters__toggle { border-bottom: 1px solid #E5E2DC; }
            .sc-filters__count { font-size: 10.5px; color: #5C2323; text-transform: none; letter-spacing: .02em; }
            .sc-filters:not([open]) .sc-filters__body { display: none; }
            .sc-filters .sc-filters__body { padding: 16px 16px 0; }
        }
    </style>

    <div style="display:grid;gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @forelse($products as $item)
            @include('shop.partials.product-card', [
                'product' => $item,
                'bestSellerIds' => $bestSellerIds,
                'wishlistedIds' => $wishlistedIds,
            ])
        @empty
            <div style="grid-column:1/-1;text-align:center;color:#8A8680;font-size:14px;padding:60px 0">
                Không có sản phẩm nào phù hợp. <a href="{{ route('shop.index') }}" style="color:#5C2323;text-decoration:underline">Xem tất cả sản phẩm</a>
            </div>
        @endforelse
    </div>

    @if($products->hasPages())
        <div class="sc-results-pager">
            <div class="sc-results-pager__progress">
                <p style="font-family:'Anton',sans-serif;font-size:34px;letter-spacing:0.01em;color:#1C1C1A;margin:0 0 14px">{{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} / {{ $products->total() }}</p>
                <div class="sc-results-pager__dashes" style="display:grid;grid-template-columns:repeat(24,minmax(0,1fr));gap:4px">
                    @php $filledDashes = (int) round(($products->currentPage() / max(1, $products->lastPage())) * 24); @endphp
                    @for($i = 0; $i < 24; $i++)
                        <span style="height:3px;background:{{ $i < $filledDashes ? '#5C2323' : '#E5E2DC' }};border-radius:2px"></span>
                    @endfor
                </div>
            </div>
            @include('shop.partials.pagination', ['paginator' => $products])
        </div>
        <style>
            .sc-results-pager { background:#F7F4EF; border-radius:16px; padding:36px 44px; margin-top:56px; display:flex; justify-content:space-between; align-items:center; gap:40px; flex-wrap:wrap; }
            .sc-results-pager__progress { flex: 1 1 240px; min-width: 0; max-width: 308px; }
            .sc-results-pager__dashes { max-width: 100%; }
            @media (max-width: 640px) { .sc-results-pager { padding: 24px; gap: 24px; } }
        </style>
    @endif
</section>

@endif

{{-- Câu hỏi thường gặp (nhóm "general") — hiển thị ở cả trang chủ và trang
     danh mục/tìm kiếm vì $faqs được controller tính sẵn không phụ thuộc
     $showFeatured (P3.1). --}}
@if($faqs->isNotEmpty())
{{-- Trang chủ: FAQ đứng ngay sau khối CTA nền be -> cần khoảng thở phía trên,
     nếu không tiêu đề dính sát mép khối be như bị đè. Trang danh mục đã có
     khoảng trống đáy của lưới sản phẩm nên giữ 0. --}}
<section style="max-width:900px;margin:0 auto;padding:{{ $showFeatured ? 'clamp(56px,7vw,96px)' : '0' }} 24px clamp(64px,8vw,100px)">
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

@include('shop.partials.wishlist-script')

@endsection
