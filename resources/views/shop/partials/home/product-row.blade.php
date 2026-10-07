{{--
    Một HÀNG sản phẩm trên trang chủ. Dùng chung cho cả bốn khối mua sắm (cây
    nổi bật, cây trong nhà, cây sân vườn, hoa) thay cho bốn bản chép tay gần
    giống nhau trước đây.

    Tham số:
      $rowProducts   (bắt buộc) Collection Product — rỗng thì cả khối tự ẩn
      $rowTitle      (bắt buộc) tiêu đề
      $rowKicker     dòng nhãn nhỏ phía trên tiêu đề
      $rowNote       một dòng giải thích dưới tiêu đề
      $rowHref       + $rowLinkText: nút "Xem tất cả ..." (bỏ trống thì không vẽ)
      $rowId         id để neo anchor
      $rowBg         màu nền khối (mặc định trắng)
      $rowRanked     true -> đánh số #1..#N lên ảnh (khối bán chạy)
      $rowSoldCounts mảng product_id => số đã bán

    Hàng là CAROUSEL NGANG (tối đa ShopController::HOME_ROW_LIMIT thẻ): track
    flex cuộn ngang có scroll-snap, mỗi thẻ rộng 1/4 hàng ở desktop (≥1024px),
    1/3 ở tablet, ~1/2 ở mobile (hiện 2 thẻ và ló mép thẻ thứ 3 để khách biết
    còn hàng để vuốt). Bề rộng thẻ cố định nên hàng ít thẻ chỉ canh trái, thẻ
    không nở to hơn các hàng khác.

    Hai nút ‹ › cạnh "Xem tất cả" cuộn đúng một khung nhìn. Nút được vẽ với
    `hidden` và chỉ hiện khi JS thấy track thật sự tràn — không JS hoặc hàng
    vừa đủ chỗ thì không có nút thừa, mobile vẫn vuốt tay bình thường.
--}}
@php
    $rowKicker = $rowKicker ?? null;
    $rowNote = $rowNote ?? null;
    $rowHref = $rowHref ?? null;
    $rowLinkText = $rowLinkText ?? null;
    $rowId = $rowId ?? null;
    $rowBg = $rowBg ?? '#FFFFFF';
    $rowRanked = $rowRanked ?? false;
    $rowSoldCounts = $rowSoldCounts ?? [];
@endphp
@if($rowProducts->isNotEmpty())
<section @if($rowId) id="{{ $rowId }}" @endif class="sc-row" style="background:{{ $rowBg }}" data-row-carousel>
    <div class="sc-row__inner">
        <div class="sc-row__head">
            <div>
                @if($rowKicker)
                    <p class="sc-row__kicker">{{ $rowKicker }}</p>
                @endif
                <h2 class="sc-row__title">{{ $rowTitle }}</h2>
                @if($rowNote)
                    <p class="sc-row__note">{{ $rowNote }}</p>
                @endif
            </div>
            <div class="sc-row__actions">
                @if($rowHref && $rowLinkText)
                    <a href="{{ $rowHref }}" class="sc-home__view-all">{{ $rowLinkText }} <span aria-hidden="true">&rarr;</span></a>
                @endif
                <div class="sc-row__nav" data-row-nav hidden>
                    <button type="button" class="sc-row__arrow" data-row-prev aria-label="Xem sản phẩm trước"><span aria-hidden="true">&lsaquo;</span></button>
                    <button type="button" class="sc-row__arrow" data-row-next aria-label="Xem sản phẩm tiếp theo"><span aria-hidden="true">&rsaquo;</span></button>
                </div>
            </div>
        </div>

        <div data-grow data-row-track class="sc-row__track" tabindex="0" role="region" aria-label="{{ $rowTitle }}">
            @foreach($rowProducts as $rowProduct)
                @include('shop.partials.product-card', [
                    'product' => $rowProduct,
                    'bestSellerIds' => $bestSellerIds ?? [],
                    'wishlistedIds' => $wishlistedIds ?? [],
                    'rank' => $rowRanked ? $loop->iteration : null,
                    'soldCount' => $rowSoldCounts[$rowProduct->id] ?? null,
                    'cardClass' => 'sc-row__item',
                ])
            @endforeach
        </div>
    </div>
</section>
@endif

@once
<style>
.sc-home .sc-row__inner { max-width: 1400px; margin: 0 auto; padding: calc(var(--header-h, 76px) + 24px) var(--sc-gutter, 24px) clamp(36px, 5vw, 64px); }
.sc-home .sc-row__head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 26px; }
.sc-home .sc-row__kicker { margin: 0 0 10px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5C2323; }
.sc-home .sc-row__title { margin: 0; font-family: 'Anton', sans-serif; font-size: clamp(22px, 3vw, 30px); letter-spacing: .01em; text-transform: uppercase; color: #1C1C1A; }
.sc-home .sc-row__note { margin: 8px 0 0; font-size: 14px; color: #6B6B66; }
.sc-home .sc-row__actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.sc-home .sc-row__nav { display: flex; gap: 8px; }
.sc-home .sc-row__nav[hidden] { display: none; }
.sc-home .sc-row__arrow {
    display: inline-flex; align-items: center; justify-content: center;
    width: 42px; height: 42px; padding: 0 0 3px;
    border: 1px solid #1C1C1A; border-radius: 50%; background: #FFFFFF; color: #1C1C1A;
    font-family: 'Space Mono', monospace; font-size: 22px; line-height: 1; cursor: pointer;
    transition: color .2s ease, background .2s ease, border-color .2s ease, opacity .2s ease;
}
.sc-home .sc-row__arrow:hover:not(:disabled) { color: #FFFFFF; background: #5C2323; border-color: #5C2323; }
.sc-home .sc-row__arrow:focus-visible { outline: 3px solid #B88A62; outline-offset: 3px; }
.sc-home .sc-row__arrow:disabled { opacity: .3; cursor: default; }

/* Track: --row-gap và --card-w đổi theo màn hình; thẻ không co giãn (flex: 0 0). */
.sc-home .sc-row__track {
    --row-gap: 24px;
    --card-w: calc((100% - 3 * var(--row-gap)) / 4);
    display: flex; gap: var(--row-gap);
    overflow-x: auto; overflow-y: hidden;
    scroll-snap-type: x mandatory; scroll-behavior: auto;
    overscroll-behavior-x: contain; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    /* Chừa chỗ cho viền focus của thẻ khỏi bị overflow cắt. */
    padding: 4px; margin: -4px; scroll-padding-inline: 4px;
}
.sc-home .sc-row__track::-webkit-scrollbar { display: none; }
.sc-home .sc-row__track:focus-visible { outline: 3px solid #B88A62; outline-offset: 2px; }
.sc-home .sc-row__item { flex: 0 0 var(--card-w); scroll-snap-align: start; display: flex; flex-direction: column; min-width: 0; }
.sc-home .sc-row__item > :last-child { margin-top: auto; }
@media (max-width: 1023px) {
    .sc-home .sc-row__track { --row-gap: 20px; --card-w: calc((100% - 2 * var(--row-gap)) / 3); }
}
@media (max-width: 640px) {
    /* 2 thẻ + ló ~1/4 thẻ thứ ba: báo hiệu vuốt được. */
    .sc-home .sc-row__track { --row-gap: 14px; --card-w: calc((100% - 2 * var(--row-gap)) / 2.25); }
    .sc-home .sc-row__arrow { width: 38px; height: 38px; font-size: 20px; }
}
</style>
<script>
(function () {
    var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function setup(row) {
        var track = row.querySelector('[data-row-track]');
        var nav = row.querySelector('[data-row-nav]');
        if (!track || !nav) return;
        var prev = nav.querySelector('[data-row-prev]');
        var next = nav.querySelector('[data-row-next]');

        function update() {
            var max = track.scrollWidth - track.clientWidth;
            // Không có gì để cuộn -> ẩn hẳn cả hai nút.
            nav.hidden = max <= 1;
            prev.disabled = track.scrollLeft <= 1;
            next.disabled = track.scrollLeft >= max - 1;
        }

        function go(dir) {
            track.scrollBy({
                left: dir * track.clientWidth,
                behavior: reduceMotion && reduceMotion.matches ? 'auto' : 'smooth'
            });
        }

        prev.addEventListener('click', function () { go(-1); });
        next.addEventListener('click', function () { go(1); });
        track.addEventListener('keydown', function (e) {
            if (e.target !== track) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
        });

        var ticking = false;
        track.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () { ticking = false; update(); });
        }, { passive: true });
        window.addEventListener('resize', update);
        // Ảnh lazy-load / font về muộn có thể đổi scrollWidth.
        window.addEventListener('load', update, { once: true });
        update();
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-row-carousel]'), setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
@endonce
