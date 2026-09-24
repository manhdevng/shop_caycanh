@php
    // Tìm nhóm cây cảnh có tên chứa "sân vườn" (không phân biệt hoa/thường)
    // trong $plantGroups đã được shop/index.blade.php truyền sẵn xuống include
    // này. Không query Eloquent mới ở đây.
    $seasonGroup = collect($plantGroups ?? [])->first(function ($g) {
        return \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($g->name), 'sân vườn');
    });

    $seasonHref = $seasonGroup
        ? route('shop.index', ['categories' => $seasonGroup->children->pluck('id')->all()])
        : route('shop.index', ['type' => 'plant']);
@endphp

<section class="sc-gate sc-gate--season">
    <div class="sc-gate-season__frame">
        <img src="{{ asset('images/back_flow.webp') }}" alt="Lối đi lát đá giữa khóm hồng leo và hoa cam trong vườn" loading="lazy" decoding="async">
    </div>

    <div class="sc-scrim sc-scrim--left" aria-hidden="true"></div>

    <div class="sc-gate-season__copy">
        <p class="sc-gate-season__label">Cây sân vườn</p>
        <h2 class="sc-gate-season__title">Khu vườn nở theo mùa</h2>
        <p class="sc-gate-season__desc">Hồng leo, dâm bụt và những khóm hoa cam rực nắng bên lối đi lát đá. Cây sân vườn ưa sáng, bền với nắng mưa, cho khoảng sân nhà bạn đổi màu qua từng tháng.</p>
        <a href="{{ $seasonHref }}" class="sc-gate-season__link">Xem cây sân vườn &rarr;</a>
    </div>
</section>

<style>
/* Khối F — "Mở cửa ra vườn". Cảnh ghim thứ hai của trang.
   Tĩnh (không JS / giảm chuyển động): một dải ảnh 16:6, chữ bên trái trên
   scrim tối. Có chuyển động (.sc-home.motion-on, do home-motion.js gắn):
   khối cao đúng một màn hình và được ghim; ảnh bắt đầu là một khung CỬA VÒM
   nhỏ đứng trên nền trắng — cùng mô-típ với căn phòng ở gate-garden vừa thu
   lại thành cửa vòm — rồi mở rộng ra tràn màn hình như bước qua cửa ra vườn;
   chữ hiện khi cửa đã mở hết (hàm seasonScene).
   Mọi kích thước đặt ở đây chứ không inline, vì inline style thắng mọi rule
   stylesheet — .motion-on và @media bên dưới sẽ không ghi đè được. */
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
.sc-home.motion-on .sc-gate-season__frame {
    clip-path: inset(var(--ct, 0%) var(--cs, 0%) 0% var(--cs, 0%) round var(--cr, 0px) var(--cr, 0px) 0px 0px);
}
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
}
</style>
