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

<section class="sc-gate sc-gate--season" data-sc-act="flow" style="width:100%;aspect-ratio:16/6;position:relative;margin-top:clamp(56px,8vw,100px);overflow:hidden">
    <img src="{{ asset('images/back_flow.webp') }}" alt="Lối đi lát đá giữa khóm hồng leo và hoa cam trong vườn" data-sc-parallax="0.6" style="position:absolute;left:0;top:-40px;width:100%;height:calc(100% + 80px);object-fit:cover;display:block">

    <div class="sc-scrim sc-scrim--left" aria-hidden="true"></div>

    <div class="sc-gate-season__copy">
        <p class="sc-gate-season__label">Cây sân vườn</p>
        <h2 class="sc-gate-season__title">Khu vườn nở theo mùa</h2>
        <p class="sc-gate-season__desc">Hồng leo, dâm bụt và những khóm hoa cam rực nắng bên lối đi lát đá. Cây sân vườn ưa sáng, bền với nắng mưa, cho khoảng sân nhà bạn đổi màu qua từng tháng.</p>
        <a href="{{ $seasonHref }}" class="sc-gate-season__link">Xem cây sân vườn &rarr;</a>
    </div>
</section>

<style>
/* Khối F — cổng nghỉ. KHÔNG pin, KHÔNG cắt, chỉ ảnh trôi 60px sau khung đứng
   yên (BẪY 6.5: ảnh cao dư 80px / lệch -40px mỗi đầu để không hở mép trắng
   khi parallax kéo). Cổng có chữ tĩnh (đứng yên, không parallax) nói về cây
   sân vườn, đặt bên trái, phía trên một scrim tối phủ ~45% bề ngang — lối đi
   lát đá và khóm hồng bên phải vẫn lộ rõ. */
/* min-height khai báo ở đây, KHÔNG phải inline style trên <section>: inline
   style thắng mọi rule stylesheet dù không có !important, nên nếu để
   min-height trong style="" thì @media (max-width:860px) bên dưới sẽ
   không bao giờ ghi đè được -> điện thoại luôn kẹt ở 320px. */
.sc-home .sc-gate--season { overflow: hidden; min-height: 320px; }

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

/* BẪY 2: scrim là ANH EM của .sc-gate-season__copy (cả hai đều anh em của
   <img data-sc-parallax>), không phải con/::before — chữ không trôi theo ảnh. */
/* Chữ đứng yên (đã bỏ data-sc-in: fade-trượt-lên trên mọi tiêu đề là kiểu
   mặc định, chuyển động của khối này nằm ở ảnh parallax phía sau). Căn giữa
   dọc bằng flexbox (top/bottom:0 + justify-content:center), không dùng
   transform — nếu sau này gắn lại data-sc-in, engine sẽ ghi đè transform
   của chính phần tử này. */
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
