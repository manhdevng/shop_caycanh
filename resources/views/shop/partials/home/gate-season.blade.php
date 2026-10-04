@php
    // T6: nút cảnh sân vườn trước đây mở nhóm "Cây cảnh sân vườn & ngoài
    // trời" — nhóm chưa có sản phẩm nào nên khách bấm vào ra trang trống. Nay
    // mở danh mục Ngoài trời thật mà hàng sản phẩm ngay dưới đang dùng
    // ($outdoorCategory + $outdoorNoun từ ShopController), và chỉ vẽ nút khi
    // danh mục đó còn hàng.
    $seasonHref = ($outdoorCategory ?? null) && ($outdoorProducts ?? collect())->isNotEmpty()
        ? \App\Http\Controllers\ShopController::catalogUrl(['categories' => [$outdoorCategory->id]])
        : null;
@endphp

<section class="sc-gate sc-gate--season">
    <div class="sc-gate-season__frame">
        <img src="{{ asset('images/back_flow-editorial.webp') }}" alt="Lối đi lát đá trong khu vườn xanh với hoa hồng nở" width="1535" height="1025" loading="lazy" decoding="async">
    </div>

    <div class="sc-scrim sc-scrim--left" aria-hidden="true"></div>

    <div class="sc-gate-season__copy">
        <p class="sc-gate-season__label">Cây sân vườn</p>
        <h2 class="sc-gate-season__title">Khu vườn nở theo mùa</h2>
        <p class="sc-gate-season__desc">Hồng leo, dâm bụt và những khóm hoa cam rực nắng bên lối đi lát đá. Cây sân vườn ưa sáng, bền với nắng mưa, cho khoảng sân nhà bạn đổi màu qua từng tháng.</p>
        @if($seasonHref)
            <a href="{{ $seasonHref }}" class="sc-home__view-all sc-home__view-all--inverse">Xem tất cả {{ $outdoorNoun ?? 'cây' }} ngoài trời <span aria-hidden="true">&rarr;</span></a>
        @endif
    </div>
</section>

<style>
/* Khối F — "Khu vườn nở theo mùa". Cảnh ghim thứ hai của trang.
   Tĩnh (không JS / giảm chuyển động): một dải ảnh 16:6, chữ bên trái trên
   scrim tối. Có chuyển động (.sc-home.motion-on, do home-motion.js gắn):
   khối cao đúng một màn hình và được ghim; khung ảnh giữ nguyên phương ngang,
   GSAP hé ảnh từ khe giữa sang hai bên và chữ hiện khi ảnh gần mở xong. Mọi
   kích thước đặt ở đây chứ không inline để giữ responsive. */
.sc-home .sc-gate--season {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 6;
    min-height: 320px;
    margin-top: clamp(56px, 8vw, 100px);
    overflow: hidden;
    background: #FFFFFF;
}
.sc-home.motion-on .sc-gate--season {
    aspect-ratio: auto;
    height: 100vh;
    height: 100svh;
}
.sc-home .sc-gate-season__frame {
    position: absolute;
    inset: 0;
    overflow: hidden;
}
.sc-home .sc-gate-season__frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* Khung ảnh hé bằng mặt nạ ngang; chuyển động tách lớp để giữ ảnh thẳng. */
.sc-home.motion-on .sc-gate-season__frame { will-change: clip-path; }
.sc-home.motion-on .sc-gate-season__frame img { will-change: transform; }

/* BẪY 1: .sc-scrim mặc định dựng từ --sc-canvas, mà .sc-home khai --sc-canvas
   là #FFFFFF (trang nền sáng) -> gradient trắng sau chữ trắng sẽ vô hình. Retint
   ngay trên scrim của khối này, đồng thời tự viết lại stop để tắt hẳn ở 45%
   bề ngang (không dùng stop 68% mặc định của .sc-scrim). */
.sc-home .sc-gate--season .sc-scrim {
    background: linear-gradient(90deg,
        rgba(28, 28, 26, .82) 0%,
        rgba(28, 28, 26, .55) 22%,
        transparent 45%);
}

/* Scrim là ANH EM của .sc-gate-season__copy (không phải con/::before) để
   chữ và scrim tắt/bật độc lập. Khối chữ căn giữa dọc bằng flexbox; GSAP chỉ
   dịch từng dòng con bên trong, không đụng transform của chính khối này. */
.sc-home .sc-gate-season__copy {
    position: absolute;
    z-index: 2;
    left: var(--sc-gutter, 24px);
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    max-width: min(40ch, 60%);
}

.sc-home .sc-gate-season__copy .sc-home__view-all { align-self: flex-start; }

.sc-home .sc-gate-season__label {
    margin: 0;
    font-family: 'Space Mono', monospace;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #FFFFFF;
}

.sc-home .sc-gate-season__title {
    margin: 0;
    font-family: 'Anton', sans-serif;
    font-size: clamp(28px, 4vw, 52px);
    text-transform: uppercase;
    line-height: 1.1;
    color: #FFFFFF;
}

.sc-home .sc-gate-season__desc {
    margin: 0;
    font-size: 16px;
    line-height: 1.6;
    max-width: 36ch;
    color: rgba(255, 255, 255, .82);
}

.sc-home .sc-gate-season__link {
    font-family: 'Space Mono', monospace;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #FFFFFF;
    text-decoration: underline;
    text-underline-offset: 4px;
    text-decoration-thickness: 1px;
    width: fit-content;
}

@media (max-width: 860px) {
    .sc-home .sc-gate--season { min-height: 460px; }
    .sc-home.motion-on .sc-gate--season { min-height: 0; }

    /* Chuyển scrim sang từ đáy lên, phủ ~60% chiều cao, phần trên dải hoa
       vẫn lộ rõ. */
    .sc-home .sc-gate--season .sc-scrim {
        background: linear-gradient(0deg,
            rgba(28, 28, 26, .85) 0%,
            rgba(28, 28, 26, .55) 35%,
            transparent 60%);
    }

    .sc-home .sc-gate-season__copy {
        left: 0;
        right: 0;
        top: auto;
        bottom: 0;
        max-width: none;
        padding: 0 var(--sc-gutter, 24px) clamp(24px, 6vw, 40px);
    }

    .sc-home .sc-gate-season__desc { max-width: none; }
    .sc-home .sc-gate-season__frame img { object-position: 58% center; }
}
</style>
