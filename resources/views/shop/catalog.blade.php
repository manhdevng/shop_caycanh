@extends('layouts.shop')
@section('content')

{{--
    T8 — Trang "Tất cả sản phẩm" (/cua-hang, ShopController@catalog).
    Bộ lọc là MỘT form GET: đổi trang giữ nguyên mọi bộ lọc nhờ
    paginate()->withQueryString() ở controller. Lọc mùa / bán chạy / quà tặng
    chỉ hiện khi có dữ liệu thật ($seasonOptions, $hasBestSellers,
    $giftProductCount). Chưa có lọc "Đang giảm giá" — xem ShopController::catalog.
--}}
@php
    $wishlistedIds = auth()->check() ? auth()->user()->wishlistedProducts->pluck('id')->all() : [];

    // Nhóm danh mục còn hàng, mỗi nhóm chỉ giữ danh mục con còn hàng (hoặc
    // đang được chọn, để khách bỏ chọn được).
    $selectedCategoryIds = $filters['categories'];
    $filterGroups = $categoryGroups
        ->map(function ($group) use ($selectedCategoryIds) {
            $group->setRelation('children', $group->children
                ->filter(fn ($c) => $c->products_count > 0 || in_array($c->id, $selectedCategoryIds, true))
                ->values());
            return $group;
        })
        ->filter(fn ($group) => $group->children->isNotEmpty())
        ->values();

    $activeFilterCount = count($selectedCategoryIds)
        + ($filters['type'] ? 1 : 0)
        + ($filters['q'] !== '' ? 1 : 0)
        + ($filters['price_min'] !== null || $filters['price_max'] !== null ? 1 : 0)
        + ($filters['season'] ? 1 : 0)
        + ($filters['bestseller'] ? 1 : 0)
        + ($filters['gift'] ? 1 : 0);

    $resetUrl = \App\Http\Controllers\ShopController::catalogUrl();
@endphp

<nav aria-label="breadcrumb" class="sc-cat__crumb">
    <a href="{{ route('shop.index') }}">Trang chủ</a>
    <span aria-hidden="true">&rsaquo;</span>
    <span class="is-current">Tất cả sản phẩm</span>
</nav>

<section class="sc-cat__head">
    <h1>Tất cả sản phẩm</h1>
    <p class="sc-cat__count">{{ $products->total() }} sản phẩm{{ $activeFilterCount > 0 ? ' phù hợp' : '' }}</p>
</section>

<div class="sc-cat">
    {{-- Mặc định mở (không JS vẫn lọc được); script cuối trang thu lại trên
         màn hẹp khi chưa áp dụng bộ lọc nào. --}}
    <details class="sc-cat__filters" open data-collapse-mobile="{{ $activeFilterCount > 0 ? '0' : '1' }}">
        <summary class="sc-cat__toggle">
            <span>Bộ lọc &amp; sắp xếp</span>
            @if($activeFilterCount > 0)
                <span class="sc-cat__badge">{{ $activeFilterCount }} đang áp dụng</span>
            @endif
        </summary>

        <form method="GET" action="{{ $resetUrl }}" class="sc-cat__form">
            <label class="sc-cat__field">
                <span class="sc-cat__label">Tìm theo tên</span>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Vd: kim ngân, hoa hồng" class="sc-cat__input">
            </label>

            <label class="sc-cat__field">
                <span class="sc-cat__label">Sắp xếp</span>
                <select name="sort" class="sc-cat__input">
                    <option value="featured" @selected($filters['sort'] === 'featured')>{{ $filters['bestseller'] ? 'Bán chạy nhất' : 'Mới nhất' }}</option>
                    <option value="price-asc" @selected($filters['sort'] === 'price-asc')>Giá thấp đến cao</option>
                    <option value="price-desc" @selected($filters['sort'] === 'price-desc')>Giá cao đến thấp</option>
                    <option value="name-asc" @selected($filters['sort'] === 'name-asc')>Theo tên A-Z</option>
                </select>
            </label>

            <fieldset class="sc-cat__field">
                <legend class="sc-cat__label">Loại</legend>
                <div class="sc-cat__chips">
                    @foreach(['' => 'Tất cả', 'plant' => 'Cây cảnh', 'flower' => 'Hoa'] as $value => $label)
                        <label class="sc-cat__chip">
                            <input type="radio" name="type" value="{{ $value }}" @checked(($filters['type'] ?? '') === $value)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="sc-cat__field">
                <legend class="sc-cat__label">Khoảng giá (đ)</legend>
                <div class="sc-cat__price">
                    <input type="number" name="price_min" min="0" step="1000" inputmode="numeric" value="{{ old('price_min', $filters['price_min']) }}" placeholder="Từ" aria-label="Giá từ" class="sc-cat__input @error('price_min') is-invalid @enderror">
                    <span aria-hidden="true">&ndash;</span>
                    <input type="number" name="price_max" min="0" step="1000" inputmode="numeric" value="{{ old('price_max', $filters['price_max']) }}" placeholder="Đến" aria-label="Giá đến" class="sc-cat__input @error('price_max') is-invalid @enderror">
                </div>
                @error('price_min')<span class="sc-cat__error">{{ $message }}</span>@enderror
                @error('price_max')<span class="sc-cat__error">{{ $message }}</span>@enderror
            </fieldset>

            @if(!empty($seasonOptions))
                <label class="sc-cat__field">
                    <span class="sc-cat__label">Mùa vụ</span>
                    <select name="season" class="sc-cat__input">
                        <option value="">Mọi mùa</option>
                        @foreach($seasonOptions as $code => $label)
                            <option value="{{ $code }}" @selected($filters['season'] === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            @if($hasBestSellers || $giftProductCount > 0)
                <fieldset class="sc-cat__field">
                    <legend class="sc-cat__label">Đặc biệt</legend>
                    @if($hasBestSellers)
                        <label class="sc-cat__check">
                            <input type="checkbox" name="bestseller" value="1" @checked($filters['bestseller'])>
                            <span>Bán chạy 30 ngày qua</span>
                        </label>
                    @endif
                    @if($giftProductCount > 0)
                        <label class="sc-cat__check">
                            <input type="checkbox" name="gift" value="1" @checked($filters['gift'])>
                            <span>Quà tặng ({{ $giftProductCount }})</span>
                        </label>
                    @endif
                </fieldset>
            @endif

            @foreach($filterGroups as $group)
                <fieldset class="sc-cat__field">
                    <legend class="sc-cat__label">{{ $group->name }}</legend>
                    @foreach($group->children as $child)
                        <label class="sc-cat__check">
                            <input type="checkbox" name="categories[]" value="{{ $child->id }}" @checked(in_array($child->id, $selectedCategoryIds, true))>
                            <span>{{ $child->name }} <small>({{ $child->products_count }})</small></span>
                        </label>
                    @endforeach
                </fieldset>
            @endforeach

            <div class="sc-cat__actions">
                <button type="submit" class="sc-cat__btn sc-cat__btn--filled">Áp dụng</button>
                @if($activeFilterCount > 0 || $filters['sort'] !== 'featured')
                    <a href="{{ $resetUrl }}" class="sc-cat__btn">Xóa bộ lọc</a>
                @endif
            </div>
        </form>
    </details>

    <div class="sc-cat__main">
        <div class="sc-cat__grid">
            @forelse($products as $item)
                @include('shop.partials.product-card', [
                    'product' => $item,
                    'bestSellerIds' => $bestSellerIds,
                    'wishlistedIds' => $wishlistedIds,
                ])
            @empty
                <div class="sc-cat__empty">
                    <p>Không có sản phẩm nào phù hợp với bộ lọc đang chọn.</p>
                    <a href="{{ $resetUrl }}" class="sc-cat__btn sc-cat__btn--filled">Xem tất cả sản phẩm</a>
                </div>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="sc-cat__pager">
                <p>{{ $products->firstItem() }}&ndash;{{ $products->lastItem() }} / {{ $products->total() }}</p>
                @include('shop.partials.pagination', ['paginator' => $products])
            </div>
        @endif
    </div>
</div>

<style>
.sc-cat__crumb { max-width: 1400px; margin: 0 auto; padding: 18px 24px 0; font-size: 13px; color: #8A8680; display: flex; gap: 8px; flex-wrap: wrap; }
.sc-cat__crumb a { color: #8A8680; }
.sc-cat__crumb .is-current { color: #1C1C1A; }
.sc-cat__head { max-width: 1400px; margin: 0 auto; padding: 20px 24px 32px; }
.sc-cat__head h1 { font-family: 'Anton', sans-serif; font-size: clamp(32px, 5vw, 54px); line-height: 1.1; letter-spacing: .01em; text-transform: uppercase; color: #1C1C1A; margin: 0 0 12px; }
.sc-cat__count { font-family: 'Space Mono', monospace; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: #8A8680; margin: 0; }

.sc-cat { max-width: 1400px; margin: 0 auto; padding: 0 24px clamp(64px, 8vw, 96px); display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 40px; align-items: start; }
.sc-cat__toggle { display: none; }
.sc-cat__filters .sc-cat__form { display: flex; flex-direction: column; gap: 22px; }
.sc-cat__field { border: 0; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.sc-cat__label { font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: #5C2323; padding: 0; margin-bottom: 2px; }
.sc-cat__input { width: 100%; min-height: 42px; font-size: 14px; color: #1C1C1A; background: #FFFFFF; border: 1px solid #E5E2DC; border-radius: 10px; padding: 9px 12px; box-sizing: border-box; }
.sc-cat__input.is-invalid { border-color: #B3261E; }
.sc-cat__error { color: #B3261E; font-size: 12px; }
.sc-cat__price { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px; color: #8A8680; }
.sc-cat__chips { display: flex; flex-wrap: wrap; gap: 8px; }
.sc-cat__chip input { position: absolute; opacity: 0; pointer-events: none; }
.sc-cat__chip span { display: inline-flex; align-items: center; min-height: 38px; padding: 0 14px; border: 1px solid #E5E2DC; border-radius: 999px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: #1C1C1A; background: #FFFFFF; cursor: pointer; }
.sc-cat__chip input:checked + span { background: #5C2323; border-color: #5C2323; color: #FFFFFF; }
.sc-cat__chip input:focus-visible + span { outline: 3px solid #B88A62; outline-offset: 2px; }
.sc-cat__check { display: flex; align-items: flex-start; gap: 10px; min-height: 30px; font-size: 14px; color: #1C1C1A; cursor: pointer; }
.sc-cat__check input { width: 18px; height: 18px; margin: 1px 0 0; accent-color: #5C2323; flex: none; }
.sc-cat__check small { color: #8A8680; font-size: 12px; }
.sc-cat__actions { display: flex; gap: 10px; flex-wrap: wrap; position: sticky; bottom: 0; background: #FFFFFF; padding: 10px 0; }
.sc-cat__btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 20px; border: 1px solid #5C2323; border-radius: 999px; background: #FFFFFF; color: #5C2323; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; cursor: pointer; text-decoration: none; }
.sc-cat__btn--filled { background: #5C2323; color: #FFFFFF; }
.sc-cat__btn:focus-visible { outline: 3px solid #B88A62; outline-offset: 3px; }

.sc-cat__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px 20px; }
.sc-cat__grid .sc-card { display: flex; flex-direction: column; min-width: 0; }
.sc-cat__grid .sc-card > :last-child { margin-top: auto; }
.sc-cat__empty { grid-column: 1 / -1; text-align: center; color: #8A8680; font-size: 14px; padding: 60px 24px; border: 1px dashed #E5E2DC; border-radius: 16px; }
.sc-cat__empty p { margin: 0 0 18px; }
.sc-cat__pager { background: #F7F4EF; border-radius: 16px; padding: 24px 28px; margin-top: 48px; display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap; }
.sc-cat__pager p { font-family: 'Anton', sans-serif; font-size: 26px; color: #1C1C1A; margin: 0; }

@media (min-width: 1200px) { .sc-cat__grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

/* Màn hẹp: bộ lọc thu vào một nút mở/đóng để sản phẩm đầu tiên không bị đẩy quá sâu. */
@media (max-width: 960px) {
    .sc-cat { grid-template-columns: minmax(0, 1fr); gap: 24px; }
    .sc-cat__filters { border: 1px solid #E5E2DC; border-radius: 14px; }
    .sc-cat__toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 48px; padding: 0 16px; cursor: pointer; list-style: none; font-family: 'Space Mono', monospace; font-size: 12px; letter-spacing: .05em; text-transform: uppercase; color: #1C1C1A; }
    .sc-cat__toggle::-webkit-details-marker { display: none; }
    .sc-cat__toggle::after { content: '+'; font-size: 18px; line-height: 1; color: #5C2323; }
    .sc-cat__filters[open] .sc-cat__toggle::after { content: '\2212'; }
    .sc-cat__filters[open] .sc-cat__toggle { border-bottom: 1px solid #E5E2DC; }
    .sc-cat__badge { font-size: 10.5px; color: #5C2323; text-transform: none; letter-spacing: .02em; }
    .sc-cat__filters .sc-cat__form { padding: 16px; }
    .sc-cat__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px 14px; }
}
/* Desktop: bộ lọc luôn mở ở cột trái, không cần nút. */
@media (min-width: 961px) {
    .sc-cat__filters { position: sticky; top: calc(var(--header-h, 76px) + 16px); max-height: calc(100vh - var(--header-h, 76px) - 32px); overflow-y: auto; }
}
</style>

<script>
(function () {
    var filters = document.querySelector('.sc-cat__filters');
    if (!filters) return;
    var desktop = window.matchMedia('(min-width: 961px)');
    if (!desktop.matches && filters.dataset.collapseMobile === '1') filters.open = false;
    // Desktop không có nút đóng/mở -> luôn giữ mở kể cả khi xoay/đổi cỡ cửa sổ.
    desktop.addEventListener('change', function (e) { if (e.matches) filters.open = true; });
})();
</script>

@include('shop.partials.wishlist-script')

@endsection
