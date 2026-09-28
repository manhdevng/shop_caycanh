{{--
    Khối A — Màn hình đầu.

    Trước đây hero chỉ có một câu chung chung ("Mang thiên nhiên vào không gian
    sống") trên nền video, nên khách mở trang không biết đây bán gì; dữ liệu
    $featuredProduct controller đã lấy sẵn thì không hề dùng tới. Nay:
      - Chữ nói thẳng mặt hàng: "Cây cảnh cho nhà và sân vườn".
      - Bên phải là MỘT CÂY ĐANG BÁN THẬT ($heroProduct do ShopController chọn:
        cây, còn hàng, có ảnh, có giá) kèm tên + giá + nút vào đúng trang sản
        phẩm đó. Không có cây phù hợp thì cả khối thẻ ẩn đi, hai nút vẫn dẫn
        tới danh mục cây — không bịa sản phẩm.
      - Nền dither (hero-pixel) giữ nguyên làm lớp không khí, không còn là nội
        dung chính. Video chỉ là nguồn dự phòng khi ảnh dither lỗi, nên
        preload="metadata" thay vì "auto" (file ~12MB).

    Ảnh cây ở đây là ảnh LCP: không lazy, fetchpriority="high", có width/height
    để không nhảy bố cục.
--}}
@php
    $heroPlantHref = route('shop.index', ['type' => 'plant']);
    $heroPrice = null;
    if ($heroProduct) {
        if ($heroProduct->hasPriceRange()) {
            $heroPrice = 'Từ ' . number_format($heroProduct->variants->min('price'), 0, ',', '.') . '₫';
        } elseif ($heroProduct->base_price > 0) {
            $heroPrice = number_format($heroProduct->base_price, 0, ',', '.') . '₫';
        }
    }
@endphp
<section id="heroSection" class="sc-hero">
    <video class="sc-hero__video" src="{{ asset('videos/hero.mp4') }}" autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>
    @include('shop.partials.hero-pixel')
    <div class="sc-hero__shade" aria-hidden="true"></div>
    <div class="hero-veil" aria-hidden="true"></div>
    <div class="sc-hero__fade" aria-hidden="true"></div>

    <div class="sc-hero__inner">
        <div class="hero-copy sc-hero__copy">
            <p class="sc-hero__kicker">Cây cảnh &middot; Hoa &middot; Quà tặng</p>
            <h1 class="sc-hero__title">Cây cảnh cho nhà và sân vườn</h1>
            <p class="sc-hero__desc">Cây được tuyển chọn tại vườn, đóng gói giữ nguyên bầu đất và giao tận nhà trên toàn quốc.</p>
            <div class="sc-hero__actions">
                @if($heroProduct)
                    <a href="{{ route('shop.show', $heroProduct->id) }}" class="sc-hero__btn sc-hero__btn--primary">Xem cây này</a>
                @endif
                <a href="{{ $heroPlantHref }}" class="sc-hero__btn sc-hero__btn--ghost">Xem tất cả cây cảnh</a>
            </div>
        </div>

        @if($heroProduct)
            {{-- Thẻ cây thật: ảnh đứng thẳng, sắc nét ngay từ đầu, không nghiêng,
                 không bay vào. GSAP chỉ cho nó trôi rất nhẹ theo cuộn. --}}
            <a href="{{ route('shop.show', $heroProduct->id) }}" class="sc-hero__card" data-hero-card>
                <span class="sc-hero__card-media">
                    @if($heroProduct->main_image)
                        <img src="{{ asset('storage/' . $heroProduct->main_image) }}"
                             alt="{{ $heroProduct->name }}"
                             width="600" height="600" fetchpriority="high" decoding="async">
                    @else
                        <span class="placeholder-pattern" style="display:block;width:100%;height:100%"></span>
                    @endif
                </span>
                <span class="sc-hero__card-body">
                    <span class="sc-hero__card-tag">Đang bán</span>
                    <span class="sc-hero__card-name">{{ $heroProduct->name }}</span>
                    @if($heroPrice)
                        <span class="sc-hero__card-price">{{ $heroPrice }}</span>
                    @else
                        <span class="sc-hero__card-price sc-hero__card-price--muted">Liên hệ giá</span>
                    @endif
                    <span class="sc-hero__card-cta">Xem chi tiết &rarr;</span>
                </span>
            </a>
        @endif
    </div>
</section>

<style>
/* Hero cao tối đa ~0,86 màn hình (kế hoạch: 0,8-0,9) để lưới hàng đầu tiên
   lọt vào trong 1,5 màn hình cuộn. svh để thanh địa chỉ điện thoại co giãn
   không làm đổi chiều cao. */
.sc-home .sc-hero {
    position: relative;
    width: 100%;
    min-height: 86vh;
    min-height: 86svh;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.sc-home .sc-hero__video,
.sc-home #heroPixelCanvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
}
.sc-home .sc-hero__shade { position: absolute; inset: 0; z-index: 1; background: linear-gradient(90deg, rgba(10,16,11,.72) 0%, rgba(10,16,11,.46) 48%, rgba(10,16,11,.30) 100%); pointer-events: none; }
.sc-home .hero-veil { position: absolute; inset: 0; z-index: 1; background: #0A100B; opacity: 0; pointer-events: none; }
.sc-home .sc-hero__fade { position: absolute; left: 0; right: 0; bottom: 0; height: 140px; z-index: 1; background: linear-gradient(180deg, transparent 0%, #0A100B 100%); pointer-events: none; }

.sc-home .sc-hero__inner {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: calc(var(--header-h, 76px) + 24px) var(--sc-gutter, 24px) 48px;
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
    align-items: center;
    gap: clamp(24px, 5vw, 64px);
}
.sc-home .sc-hero__copy { max-width: 34rem; }
.sc-home .sc-hero__kicker { margin: 0 0 16px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.82); }
.sc-home .sc-hero__title { margin: 0 0 16px; font-family: 'Anton', sans-serif; font-size: clamp(34px, 5vw, 60px); line-height: 1.08; letter-spacing: .01em; text-transform: uppercase; color: #FFFFFF; }
.sc-home .sc-hero__desc { margin: 0 0 28px; font-size: 16px; line-height: 1.6; color: rgba(255,255,255,.85); max-width: 30rem; }
.sc-home .sc-hero__actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sc-home .sc-hero__btn { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 28px; border-radius: 999px; font-family: 'Space Mono', monospace; font-size: 12.5px; letter-spacing: .07em; text-transform: uppercase; }
.sc-home .sc-hero__btn--primary { background: #5C2323; color: #FFFFFF; }
.sc-home .sc-hero__btn--primary:hover { background: #4A1C1C; color: #FFFFFF; }
.sc-home .sc-hero__btn--ghost { background: transparent; color: #FFFFFF; border: 1px solid rgba(255,255,255,.55); }
.sc-home .sc-hero__btn--ghost:hover { background: rgba(255,255,255,.12); color: #FFFFFF; }

/* Thẻ cây thật — nền trắng để ảnh cây tách hẳn khỏi lớp dither tối phía sau. */
.sc-home .sc-hero__card { display: block; justify-self: end; width: min(340px, 100%); background: #FFFFFF; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px -24px rgba(0,0,0,.65); color: #1C1C1A; }
.sc-home .sc-hero__card:hover { color: #1C1C1A; }
.sc-home .sc-hero__card-media { display: block; aspect-ratio: 1 / 1; background: #F7F4EF; }
.sc-home .sc-hero__card-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sc-home .sc-hero__card-body { display: block; padding: 16px 18px 18px; }
.sc-home .sc-hero__card-tag { display: block; font-family: 'Space Mono', monospace; font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: #5C2323; margin-bottom: 8px; }
.sc-home .sc-hero__card-name { display: block; font-size: 16px; font-weight: 600; line-height: 1.35; margin-bottom: 6px; }
.sc-home .sc-hero__card-price { display: block; font-size: 17px; font-weight: 700; }
.sc-home .sc-hero__card-price--muted { font-size: 13px; font-weight: 400; font-style: italic; color: #8A8680; }
.sc-home .sc-hero__card-cta { display: block; margin-top: 12px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: #5C2323; }

/* Mobile: chữ trên, thẻ cây nằm ngang bên dưới — cả hai vẫn nằm trong màn
   hình đầu. Thẻ nằm ngang (ảnh 104px bên trái) tiết kiệm chiều cao hơn nhiều
   so với thẻ dọc, nên hai nút mua không bị đẩy xuống dưới nếp gấp. */
@media (max-width: 860px) {
    .sc-home .sc-hero { min-height: 88svh; }
    .sc-home .sc-hero__inner { grid-template-columns: 1fr; gap: 22px; padding-bottom: 36px; }
    .sc-home .sc-hero__shade { background: linear-gradient(180deg, rgba(10,16,11,.62) 0%, rgba(10,16,11,.48) 55%, rgba(10,16,11,.72) 100%); }
    .sc-home .sc-hero__title { font-size: clamp(28px, 8vw, 40px); }
    .sc-home .sc-hero__desc { margin-bottom: 20px; font-size: 15px; }
    .sc-home .sc-hero__actions { gap: 10px; }
    .sc-home .sc-hero__btn { flex: 1 1 auto; min-width: 0; padding: 0 18px; font-size: 11.5px; }
    .sc-home .sc-hero__card { justify-self: stretch; width: 100%; display: flex; align-items: center; gap: 14px; border-radius: 16px; padding: 10px; }
    .sc-home .sc-hero__card-media { flex: 0 0 auto; width: 104px; border-radius: 10px; overflow: hidden; }
    .sc-home .sc-hero__card-body { flex: 1 1 auto; min-width: 0; padding: 0 6px 0 0; }
    .sc-home .sc-hero__card-tag { margin-bottom: 4px; }
    .sc-home .sc-hero__card-name { font-size: 15px; margin-bottom: 4px; }
    .sc-home .sc-hero__card-price { font-size: 16px; }
    .sc-home .sc-hero__card-cta { margin-top: 6px; }
}
</style>
