@php
    $categoryCards = $plantGroups
        ->concat($flowerGroups)
        ->map(fn ($group) => [
            'href' => \App\Http\Controllers\ShopController::catalogUrl(['categories' => $group->children->pluck('id')->all()]),
            'image' => $group->image ? asset('storage/' . $group->image) : null,
            'name' => $group->name,
            // Số sản phẩm KHÁC NHAU của cả nhóm — cộng products_count từng
            // danh mục con sẽ đếm hai lần cây nằm ở hai danh mục con.
            'count' => $group->active_products_count,
            'type' => $group->scope === 'flower' ? 'Hoa' : 'Cây cảnh',
        ]);
@endphp

<section id="catArcSection" class="cat-arc-section" aria-labelledby="catArcTitle">
    <img class="cat-arc-bg" src="{{ asset('images/bg-la-monstera.webp') }}" alt="" loading="lazy" decoding="async">
    <div class="cat-arc-bg-veil" aria-hidden="true"></div>

    <div class="cat-arc-content">
        <header class="cat-arc-head">
            <p class="cat-arc-kicker">Khám phá theo nhu cầu</p>
            <h2 id="catArcTitle" class="cat-arc-title">Danh mục cây cảnh &amp; hoa</h2>
        </header>

        {{-- Thanh trượt danh mục: kéo/vuốt ngang hoặc bấm mũi tên ‹ › --}}
        <div class="cat-arc-slider">
        <button type="button" class="cat-arc-nav cat-arc-nav--prev" data-cat-prev aria-label="Danh mục trước" aria-controls="catArcTrack">&lsaquo;</button>
        <div class="cat-arc-grid" id="catArcTrack" data-cat-track tabindex="0" aria-label="Danh sách danh mục, dùng phím mũi tên trái/phải để xem thêm">
            @foreach($categoryCards as $card)
                <a href="{{ $card['href'] }}" class="cat-arc-card">
                    @if($card['image'])
                        <img src="{{ $card['image'] }}" alt="" loading="lazy" decoding="async">
                    @else
                        <span class="cat-arc-placeholder" aria-hidden="true"></span>
                    @endif
                    <span class="cat-arc-shade" aria-hidden="true"></span>
                    <span class="cat-arc-card-copy">
                        <span class="cat-arc-tag">{{ $card['type'] }}</span>
                        <strong class="cat-arc-name">{{ $card['name'] }}</strong>
                        <span class="cat-arc-count">{{ $card['count'] }} sản phẩm <span aria-hidden="true">&rarr;</span></span>
                    </span>
                </a>
            @endforeach
        </div>
        <button type="button" class="cat-arc-nav cat-arc-nav--next" data-cat-next aria-label="Danh mục tiếp theo" aria-controls="catArcTrack">&rsaquo;</button>
        </div>

        <div class="cat-arc-foot">
            <a href="{{ route('shop.index', ['type' => 'plant']) }}" data-open-mega="plant" class="cat-arc-more">Xem thêm cây cảnh</a>
            @if($flowerGroups->isNotEmpty())
                <a href="{{ route('shop.index', ['type' => 'flower']) }}" data-open-mega="flower" class="cat-arc-more">Xem thêm hoa</a>
            @endif
        </div>
    </div>
</section>

<style>
.cat-arc-section { position: relative; width: 100%; overflow: hidden; isolation: isolate; background: #0A100B; }
.cat-arc-bg, .cat-arc-bg-veil { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.cat-arc-bg { object-fit: cover; }
.cat-arc-bg-veil { background: linear-gradient(180deg, rgba(10,16,11,.85), rgba(10,16,11,.64) 50%, rgba(10,16,11,.9)); }
.cat-arc-content { position: relative; z-index: 1; max-width: 1400px; margin: 0 auto; padding: calc(var(--header-h, 76px) + 32px) var(--sc-gutter, 24px) clamp(48px, 6vw, 80px); }
.cat-arc-head { margin-bottom: clamp(24px, 3vw, 38px); text-align: center; }
.cat-arc-kicker { margin: 0 0 10px; color: rgba(255,255,255,.7); font: 11px 'Space Mono', monospace; letter-spacing: .14em; text-transform: uppercase; }
.cat-arc-title { margin: 0; color: #FFFFFF; font: clamp(26px, 3.6vw, 44px)/1.1 'Anton', sans-serif; letter-spacing: .01em; text-transform: uppercase; }
/* Thanh trượt: --cat-per-view = số thẻ thấy cùng lúc (desktop 3, tablet 2, điện thoại ~1.5) */
.cat-arc-slider { position: relative; }
.cat-arc-grid { --cat-gap: 16px; --cat-per-view: 3; display: flex; gap: var(--cat-gap); overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; overscroll-behavior-x: contain; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.cat-arc-grid::-webkit-scrollbar { display: none; }
.cat-arc-grid:focus-visible { outline: 3px solid #FFFFFF; outline-offset: 4px; border-radius: 12px; }
.cat-arc-nav { position: absolute; top: 50%; z-index: 2; width: 46px; height: 46px; margin-top: -23px; border: 0; border-radius: 999px; background: rgba(255,255,255,.92); color: #1C1C1A; font: 26px/1 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 6px 18px rgba(0,0,0,.28); transition: opacity .2s ease, transform .2s ease, background .2s ease; }
.cat-arc-nav:hover { background: #FFFFFF; transform: scale(1.06); }
.cat-arc-nav:focus-visible { outline: 3px solid #FFFFFF; outline-offset: 3px; }
.cat-arc-nav--prev { left: -12px; }
.cat-arc-nav--next { right: -12px; }
.cat-arc-nav[disabled] { opacity: 0; pointer-events: none; }
.cat-arc-card { position: relative; display: block; flex: 0 0 calc((100% - (var(--cat-per-view) - 1) * var(--cat-gap)) / var(--cat-per-view)); min-width: 0; scroll-snap-align: start; aspect-ratio: .78; overflow: hidden; border-radius: 12px; background: #26352B; color: #FFFFFF; }
.cat-arc-card > img, .cat-arc-placeholder, .cat-arc-shade { position: absolute; inset: 0; width: 100%; height: 100%; }
.cat-arc-card > img { display: block; object-fit: cover; transition: transform .4s ease; }
.cat-arc-card:hover > img { transform: scale(1.04); }
.cat-arc-placeholder { background: repeating-linear-gradient(135deg, #26352B, #26352B 14px, #35483A 14px, #35483A 28px); }
.cat-arc-shade { background: linear-gradient(180deg, transparent 32%, rgba(0,0,0,.22) 55%, rgba(0,0,0,.82) 100%); }
.cat-arc-card-copy { position: absolute; inset: auto 0 0; display: grid; gap: 7px; padding: clamp(12px, 1.5vw, 20px); }
.cat-arc-tag { width: fit-content; padding: 4px 8px; border: 1px solid rgba(255,255,255,.65); border-radius: 999px; font: 9px 'Space Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }
.cat-arc-name { font: clamp(17px, 1.8vw, 24px)/1.15 'Anton', sans-serif; text-transform: uppercase; }
.cat-arc-count { color: rgba(255,255,255,.85); font: 10px 'Space Mono', monospace; letter-spacing: .04em; text-transform: uppercase; }
.cat-arc-card:focus-visible, .cat-arc-more:focus-visible { outline: 3px solid #FFFFFF; outline-offset: 3px; }
.cat-arc-foot { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: clamp(24px, 3vw, 36px); }
.cat-arc-more { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 22px; border: 1px solid rgba(255,255,255,.7); border-radius: 999px; color: #FFFFFF; font: 11px 'Space Mono', monospace; letter-spacing: .05em; text-transform: uppercase; transition: background .2s ease, color .2s ease; }
.cat-arc-more:hover { background: #FFFFFF; color: #1C1C1A; }
@media (max-width: 900px) { .cat-arc-grid { --cat-per-view: 2; } }
@media (max-width: 640px) {
    .cat-arc-content { padding-top: calc(var(--header-h, 76px) + 24px); }
    .cat-arc-grid { --cat-gap: 10px; --cat-per-view: 1.5; }
    .cat-arc-nav { width: 38px; height: 38px; margin-top: -19px; font-size: 22px; }
    .cat-arc-nav--prev { left: -6px; }
    .cat-arc-nav--next { right: -6px; }
    .cat-arc-card { aspect-ratio: .74; }
    .cat-arc-card-copy { gap: 5px; padding: 12px; }
    .cat-arc-name { font-size: 16px; }
    .cat-arc-count { font-size: 9px; }
    .cat-arc-foot { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cat-arc-more { padding-inline: 8px; font-size: 10px; text-align: center; }
}
@media (prefers-reduced-motion: reduce) { .cat-arc-card > img, .cat-arc-more, .cat-arc-nav { transition: none; } .cat-arc-grid { scroll-behavior: auto; } }
</style>

<script>
// ==== Mũi tên ‹ › cho thanh trượt danh mục ====
// Mỗi lần bấm trượt đúng 1 thẻ; mũi tên tự ẩn khi đã ở đầu/cuối danh sách.
(function () {
    const section = document.getElementById('catArcSection');
    if (!section) return;
    const track = section.querySelector('[data-cat-track]');
    const prev = section.querySelector('[data-cat-prev]');
    const next = section.querySelector('[data-cat-next]');
    if (!track || !prev || !next) return;

    function stepSize() {
        const card = track.querySelector('.cat-arc-card');
        if (!card) return track.clientWidth;
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        return card.getBoundingClientRect().width + gap;
    }

    function update() {
        const max = track.scrollWidth - track.clientWidth;
        prev.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft >= max - 2;
    }

    prev.addEventListener('click', function () { track.scrollBy({ left: -stepSize() }); });
    next.addEventListener('click', function () { track.scrollBy({ left: stepSize() }); });
    track.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { e.preventDefault(); track.scrollBy({ left: stepSize() }); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); track.scrollBy({ left: -stepSize() }); }
    });
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    window.addEventListener('load', update);
    update();
})();
</script>
