{{--
    Khối A — Màn hình đầu.

    Màn hình đầu nói rõ mặt hàng và dẫn thẳng tới danh sách cây cảnh:
      - Chữ nói thẳng mặt hàng: "Cây cảnh cho nhà và sân vườn".
      - Lời giới thiệu và nút khám phá đứng riêng trên nền hero, không có thẻ
        sản phẩm nổi ở cạnh phải.
      - Nền dither (hero-pixel) giữ nguyên làm lớp không khí, không phải nội
        dung chính. Video chỉ là nguồn dự phòng khi ảnh dither lỗi, nên
        preload="metadata" thay vì "auto" (file ~12MB).
--}}
@php
    $heroPlantHref = route('shop.index', ['type' => 'plant']);
@endphp
<section id="heroSection" class="sc-hero" data-header-theme="dark">
    {{-- Video chỉ là nguồn dự phòng: KHÔNG gắn src sẵn (autoplay sẽ bắt trình duyệt tải ~2MB
         tranh băng thông với ảnh cây pixel + GSAP). hero-pixel chỉ gắn src khi ảnh dither lỗi. --}}
    <video class="sc-hero__video" data-src="{{ asset('videos/hero.mp4') }}" muted loop playsinline preload="none" aria-hidden="true"></video>
    <noscript><video class="sc-hero__video" src="{{ asset('videos/hero.mp4') }}" autoplay muted loop playsinline aria-hidden="true"></video></noscript>
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
                <a href="{{ $heroPlantHref }}" class="sc-hero__btn sc-hero__btn--primary">Khám phá cây cảnh</a>
            </div>
        </div>
    </div>
</section>

<style>
/* Header cố định đè lên Hero; danh mục bắt đầu sau một màn hình trọn vẹn. */
.sc-home .sc-hero {
    position: relative;
    width: 100%;
    min-height: 100vh;
    min-height: 100svh;
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
    display: block;
}
.sc-home .sc-hero__copy { max-width: 42rem; }
.sc-home .sc-hero__kicker { margin: 0 0 16px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.82); }
.sc-home .sc-hero__title { margin: 0 0 16px; font-family: 'Anton', sans-serif; font-size: clamp(34px, 5vw, 60px); line-height: 1.08; letter-spacing: .01em; text-transform: uppercase; color: #FFFFFF; }
.sc-home .sc-hero__desc { margin: 0 0 28px; font-size: 16px; line-height: 1.6; color: rgba(255,255,255,.85); max-width: 30rem; }
.sc-home .sc-hero__actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sc-home .sc-hero__btn { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 28px; border-radius: 999px; font-family: 'Space Mono', monospace; font-size: 12.5px; letter-spacing: .07em; text-transform: uppercase; }
.sc-home .sc-hero__btn--primary { background: #5C2323; color: #FFFFFF; }
.sc-home .sc-hero__btn--primary:hover { background: #4A1C1C; color: #FFFFFF; }
/* Mobile: lời giới thiệu nằm gọn trên nền hero. */
@media (max-width: 860px) {
    .sc-home .sc-hero__inner { padding-bottom: 36px; }
    .sc-home .sc-hero__shade { background: linear-gradient(180deg, rgba(10,16,11,.62) 0%, rgba(10,16,11,.48) 55%, rgba(10,16,11,.72) 100%); }
    .sc-home .sc-hero__title { font-size: clamp(28px, 8vw, 40px); }
    .sc-home .sc-hero__desc { margin-bottom: 20px; font-size: 15px; }
    .sc-home .sc-hero__actions { gap: 10px; }
    .sc-home .sc-hero__btn { min-width: 0; padding: 0 18px; font-size: 11.5px; }
}
</style>
