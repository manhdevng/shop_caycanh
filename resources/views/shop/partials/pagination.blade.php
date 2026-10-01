{{--
    Phân trang dùng chung cho trang danh sách sản phẩm (shop.index, best-sellers).
    Hiện: ‹  1  …  4  5  6  …  12  ›
      - <= 7 trang: hiện đủ tất cả số trang
      - nhiều hơn: luôn có trang đầu, trang cuối, trang hiện tại ±1, chỗ trống thay bằng "…"
    Dùng: @include('shop.partials.pagination', ['paginator' => $products])
--}}
@if($paginator->hasPages())
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = $last <= 7
        ? range(1, $last)
        : collect([1, 2, $current - 1, $current, $current + 1, $last - 1, $last])
            ->filter(fn ($p) => $p >= 1 && $p <= $last)
            ->unique()->sort()->values()->all();
    $previous = 0;
@endphp

@once
<style>
    .sc-pager { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .sc-pager__item {
        min-width: 40px; height: 40px; padding: 0 12px; border-radius: 999px;
        border: 1px solid #E5E2DC; background: #FFFFFF; color: #1C1C1A;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Mono', monospace; font-size: 13px; line-height: 1;
        text-decoration: none; transition: background .2s ease, color .2s ease, border-color .2s ease;
    }
    a.sc-pager__item:hover { border-color: #5C2323; color: #5C2323; }
    .sc-pager__item--active { background: #5C2323; border-color: #5C2323; color: #FFFFFF; cursor: default; }
    .sc-pager__item--disabled { color: #C7C3BB; cursor: default; }
    .sc-pager__arrow { font-size: 18px; padding: 0; }
    .sc-pager__gap { min-width: 24px; text-align: center; color: #8A8680; font-family: 'Space Mono', monospace; }
    a.sc-pager__item:focus-visible { outline: 2px solid #5C2323; outline-offset: 2px; }
    @media (max-width: 480px) { .sc-pager__item { min-width: 34px; height: 34px; padding: 0 8px; font-size: 12px; } }
</style>
@endonce

<nav class="sc-pager" aria-label="Phân trang sản phẩm">
    @if($paginator->onFirstPage())
        <span class="sc-pager__item sc-pager__arrow sc-pager__item--disabled" aria-hidden="true">&lsaquo;</span>
    @else
        <a class="sc-pager__item sc-pager__arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">&lsaquo;</a>
    @endif

    @foreach($pages as $page)
        @if($previous && $page - $previous > 1)
            <span class="sc-pager__gap" aria-hidden="true">&hellip;</span>
        @endif

        @if($page === $current)
            <span class="sc-pager__item sc-pager__item--active" aria-current="page">{{ $page }}</span>
        @else
            <a class="sc-pager__item" href="{{ $paginator->url($page) }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
        @endif
        @php $previous = $page; @endphp
    @endforeach

    @if($paginator->hasMorePages())
        <a class="sc-pager__item sc-pager__arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">&rsaquo;</a>
    @else
        <span class="sc-pager__item sc-pager__arrow sc-pager__item--disabled" aria-hidden="true">&rsaquo;</span>
    @endif
</nav>
@endif
