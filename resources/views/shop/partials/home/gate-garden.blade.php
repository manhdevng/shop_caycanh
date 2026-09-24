{{-- Khối C — Cổng "Vén lá vào vườn". Đỉnh cảm xúc của cả trang chủ, và là
     khối DUY NHẤT được phép rậm rạp.
     Tám chiếc lá thật (ảnh tách nền, public/images/foliage) cắm cuống ở mép
     khung. Lúc khung vừa ghim, lá chĩa vào giữa, che kín căn phòng; cuộn tới
     đâu lá xoay quanh cuống vén ra hai bên tới đó (public/js/home-motion.js,
     hàm gardenLeaves — ScrollCraft vẫn giữ việc ghim). Lá gần xoay nhiều và
     sáng hơn lá xa. Vén xong, lá còn lại ở mép làm khung, lay theo tốc độ
     cuộn và nghiêng theo nắng. Sau đó dải nắng mềm quét ngang tự lái từ
     --sc-p; chữ chỉ hiện khi lá đã vén gần hết (cue mở ở p=0.40), đặt lên mảng
     tường bê tông tối giữa ảnh, có một tấm scrim riêng (không phải ::before,
     không lồng trong khối chữ) để giữ tương phản trên nền ảnh sáng.

     CSS dưới đây vẽ lá ở trạng thái ĐÃ VÉN. JS chỉ kéo lá về thế che kín khi
     chuyển động được phép, nên không JS / giảm chuyển động -> ảnh và chữ hiện
     đủ, lá nằm yên ở mép như một khung. --}}
@php
    $indoorGroup = $plantGroups->first(fn ($g) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($g->name), 'trong nhà'));
    $indoorHref = $indoorGroup
        ? route('shop.index', ['categories' => $indoorGroup->children->pluck('id')->all()])
        : route('shop.index', ['type' => 'plant']);

    // Lá vén. ax/ay: điểm cắm cuống theo % khung. rot: góc lúc đã vén (tĩnh).
    // from: góc lúc che kín. w: rộng theo vmin. depth: 0.4 xa · 0.7 giữa · 1 gần.
    // ox: vị trí cuống theo % ngang ảnh (README trong thư mục foliage); ảnh
    // lật ngang thì cuống đổi phía -> ox = 100 - ox.
    $gardenLeaves = [
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => false, 'ax' => -2,  'ay' => 16,  'w' => 50, 'rot' => 194,  'from' => 112,  'depth' => .4],
        ['src' => 'la-gan',   'ox' => 48.9, 'flip' => true,  'ax' => 102, 'ay' => 10,  'w' => 44, 'rot' => -196, 'from' => -118, 'depth' => .4],
        ['src' => 'la-gan',   'ox' => 48.9, 'flip' => false, 'ax' => 46,  'ay' => -8,  'w' => 42, 'rot' => 262,  'from' => 178,  'depth' => .4],
        ['src' => 'la-gan',   'ox' => 48.9, 'flip' => false, 'ax' => -3,  'ay' => 58,  'w' => 44, 'rot' => -14,  'from' => 76,   'depth' => .7],
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => true,  'ax' => 103, 'ay' => 62,  'w' => 50, 'rot' => 22,   'from' => -78,  'depth' => .7],
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => false, 'ax' => 1,   'ay' => 108, 'w' => 56, 'rot' => -16,  'from' => 52,   'depth' => 1],
        ['src' => 'la-gan',   'ox' => 48.9, 'flip' => true,  'ax' => 99,  'ay' => 108, 'w' => 54, 'rot' => 16,   'from' => -48,  'depth' => 1],
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => false, 'ax' => 58,  'ay' => 118, 'w' => 56, 'rot' => 86,   'from' => -4,   'depth' => 1],
    ];
@endphp
<section class="sc-gate sc-gate--garden"
         data-sc-act="pin" data-sc-span="2.2"
         data-sc-span-mobile="1.2" data-sc-span-reduced="1.0"
         data-sc-dwell="0.3">
    <div data-sc-stage class="sc-gate__stage">
        <div class="sc-gate__frame">
            <img src="{{ asset('images/lifestyle-hero.webp') }}"
                 alt="Phòng khách ngập nắng với những chậu cây lưỡi hổ, phát tài núi và sung lá vĩ cầm đặt quanh sofa, tạo thành một khu vườn trong nhà."
                 width="2720" height="1414">
        </div>
        <div class="sc-gate__beam" aria-hidden="true"></div>
        <div class="sc-gate__leaves" aria-hidden="true">
            @foreach($gardenLeaves as $leaf)
                @php $ox = $leaf['flip'] ? 100 - $leaf['ox'] : $leaf['ox']; @endphp
                <span class="gg-leaf{{ $leaf['flip'] ? ' gg-leaf--flip' : '' }}"
                      data-from="{{ $leaf['from'] }}" data-rot="{{ $leaf['rot'] }}" data-depth="{{ $leaf['depth'] }}"
                      style="--ax:{{ $leaf['ax'] }}%;--ay:{{ $leaf['ay'] }}%;--w:{{ $leaf['w'] }}vmin;--rot:{{ $leaf['rot'] }}deg;--ox:{{ $ox }}%;--oxf:{{ $ox / 100 }};--shade:{{ 0.5 + 0.5 * $leaf['depth'] }}">
                    <span class="gg-leaf__sway">
                        <img src="{{ asset('images/foliage/' . $leaf['src'] . '-600.webp') }}"
                             srcset="{{ asset('images/foliage/' . $leaf['src'] . '-600.webp') }} 600w, {{ asset('images/foliage/' . $leaf['src'] . '-1200.webp') }} 1200w"
                             sizes="(max-width: 860px) {{ round($leaf['w'] * 1.7) }}vmin, {{ $leaf['w'] }}vmin" alt="" loading="lazy" decoding="async">
                    </span>
                </span>
            @endforeach
        </div>
        <div class="sc-gate__plate" aria-hidden="true"></div>
        <div class="sc-gate__copy" data-sc-cue="0.40 1 0.22 0.06">
            <p class="sc-gate__label">Cây trong nhà</p>
            <h2 class="sc-gate__title">Một góc xanh cho mỗi căn phòng</h2>
            <p class="sc-gate__desc">Lưỡi hổ bên cửa kính, phát tài cạnh sofa, sung lá vĩ cầm nơi góc tường. Những loại cây ưa bóng râm, ít cần chăm, giữ cho căn nhà trong lành và dịu lại sau một ngày dài.</p>
            <a href="{{ $indoorHref }}" class="sc-gate__link">Xem cây để trong nhà &rarr;</a>
        </div>
    </div>
</section>

<style>
    /* Nền cổng mang tiếp màu tối của khối B — nhát cắt cứng, không nội suy. */
    .sc-home .sc-gate--garden { background: var(--sc-ground-dark); }

    /* Khớp lại mechanics của .sc-stage (infra) ngay trên class dự án, để
       khung tối + khe sáng hoạt động đúng cả khi JS chưa kịp gắn class
       "sc-stage" (engine tự thêm khi mount) lẫn sau khi đã gắn — hai đằng
       cùng giá trị nên không có xung đột cascade. */
    .sc-home .sc-gate__stage {
        position: sticky;
        top: 0;
        height: 100vh;
        height: 100svh;
        overflow: clip;
        background: var(--sc-ground-dark);
    }

    .sc-home .sc-gate__frame {
        position: absolute;
        inset: 0;
    }
    .sc-home .sc-gate__frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 50% 46%;
        display: block;
    }

    /* Lá vén — trạng thái ĐÃ VÉN (xem ghi chú đầu file). Bọc 3 lớp để các
       lực không giành nhau một thuộc tính transform:
         .gg-leaf        vị trí + góc vén (GSAP scrub lái lớp này)
         .gg-leaf__sway  lay theo tốc độ cuộn + nghiêng theo nắng (GSAP quickTo)
         img             chỉ lật ngang tĩnh cho lá phía phải
       Đáy .gg-leaf đặt đúng điểm cắm cuống (bottom), mép trái lùi đúng phần
       ngang tới cuống (oxf * w), nên xoay quanh "var(--ox) 100%" = xoay quanh
       cuống. Độ sâu = độ sáng: lá xa tối hơn (--shade), không dùng blur vì blur
       trên ảnh lớn đang chuyển động rất tốn. */
    .sc-home .sc-gate__leaves {
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
    }
    .sc-home .sc-gate--garden { --leaf-k: 1; }
    .sc-home .gg-leaf {
        position: absolute;
        left: calc(var(--ax) - var(--w) * var(--leaf-k) * var(--oxf));
        bottom: calc(100% - var(--ay));
        width: calc(var(--w) * var(--leaf-k));
        transform-origin: var(--ox) 100%;
        transform: rotate(var(--rot));
    }
    .sc-home .gg-leaf__sway {
        display: block;
        transform-origin: var(--ox) 100%;
    }
    .sc-home .gg-leaf img {
        display: block;
        width: 100%;
        height: auto;
        filter: brightness(var(--shade));
    }
    .sc-home .gg-leaf--flip img { transform: scaleX(-1); }

    /* Dải nắng — tự lái hoàn toàn từ --sc-p do engine ghi trên chính
       [data-sc-act], kế thừa xuống phần tử con này. Chỉ animate
       transform/opacity, mềm, ấm, không phải neon glow. */
    .sc-home .sc-gate__beam {
        position: absolute;
        inset: -10% -35%;
        pointer-events: none;
        z-index: 1;
        mix-blend-mode: soft-light;
        background: linear-gradient(100deg,
            transparent 0%,
            transparent 38%,
            rgba(255, 214, 150, .95) 50%,
            transparent 62%,
            transparent 100%);
        transform: translateX(calc((var(--sc-p, 0.5) - 0.5) * 150%));
        /* Trapezoid mềm: hai đường dốc tuyến tính clamp 0..1 nhân với nhau.
           Dốc VÀO nhanh (0.45 -> đầy ở ~0.617). Dốc RA chậm và dài, chạm 0
           đúng tại p=1.0 (không phải 0.85) — act đỉnh phải dính hình ảnh
           chuyển động cho tới hết quãng pin, không để lại quãng cuộn chết
           ở đuôi act. Đỉnh phẳng ~0.617-0.65 ở biên độ tối đa 0.35. */
        opacity: calc(
            min(1, max(0, (var(--sc-p, 0.5) - 0.45) * 6))
            * min(1, max(0, (1 - var(--sc-p, 0.5)) * 2.857))
            * 0.35
        );
    }

    /* Tấm scrim của khối chữ. BẮT BUỘC là anh em của .sc-gate__copy (không
       phải ::before của nó, không lồng bên trong): engine ẩn mọi phần tử
       mang [data-sc-cue] cùng con cháu bằng visibility:hidden khi đo
       contrast, và visibility:hidden ẩn luôn pseudo-element — scrim gắn vào
       khối chữ sẽ biến mất đúng lúc bị đo, khiến contrast luôn tính trên ảnh
       thô. Đặt riêng phần tử này ngoài .sc-gate__copy để nó không bao giờ bị
       ẩn cùng chữ.
       .sc-scrim của trang này (scrollcraft-shop.css) build từ --sc-canvas,
       mà .sc-home khai --sc-canvas:#FFFFFF (nền sáng của cả trang) -> nếu
       dùng nguyên .sc-scrim ở đây sẽ ra vệt TRẮNG sau chữ TRẮNG. Nên viết hẳn
       gradient tối riêng, không mượn .sc-scrim. Chỉ phủ quanh vùng chữ (khớp
       với khung 30-60% ngang / chữ đặt ở đó), không inset:0 cả ảnh — vùng đó
       vốn đã tối (mảng tường bê tông), scrim chỉ cần đủ nhẹ để đạt tương phản.

       Các class __plate/__copy/__label/__title/__desc/__link đều được scope
       thêm .sc-gate--garden (thay vì chỉ .sc-home .sc-gate__xxx): tiền tố
       "sc-gate__" là tên gốc dùng chung cho CẢ HAI cổng trên trang
       (.sc-gate--garden ở khối C và .sc-gate--season ở khối F), nên nếu để
       trần thì các rule position/kích thước/kiểu chữ này sẽ áp luôn lên bất
       kỳ phần tử nào lỡ mang cùng class name ở khối F trong tương lai — một
       cái bẫy va chạm im lặng. .sc-gate__stage/__frame/__beam giữ nguyên
       không scope vì đã tồn tại từ trước và khối F không đụng tới chúng. */
    .sc-home .sc-gate--garden .sc-gate__plate {
        position: absolute;
        left: 28%;
        top: 0;
        width: 40%;
        height: 100%;
        z-index: 3;
        pointer-events: none;
        background: radial-gradient(ellipse 100% 70% at 30% 30%,
            rgba(10, 10, 8, .58) 0%,
            rgba(10, 10, 8, .34) 45%,
            rgba(10, 10, 8, 0) 78%);
        /* Lên trước chữ một nhịp trong cùng act "pin" (--sc-p do engine ghi
           trên [data-sc-act], kế thừa xuống mọi con): plate đã đầy trước khi
           chữ (cue mở ở p=0.40) chạm opacity tối đa (~p=0.53), và không tắt
           trước chữ — giữ nguyên suốt phần đọc rồi nhạt cùng lúc chữ nhạt. */
        opacity: calc(min(1, max(0, (var(--sc-p, 0) - 0.30) * 5)) * 0.9);
    }

    .sc-home .sc-gate--garden .sc-gate__copy {
        position: absolute;
        left: 30%;
        top: max(12%, calc(var(--sc-safe-top, 76px) + 16px));
        width: 30%;
        z-index: 4;
        text-align: left;
    }
    .sc-home .sc-gate--garden .sc-gate__label {
        margin: 0 0 10px;
        font-family: 'Space Mono', monospace;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: #FFFFFF;
    }
    .sc-home .sc-gate--garden .sc-gate__title {
        margin: 0 0 14px;
        font-family: 'Anton', sans-serif;
        font-size: clamp(28px, 4vw, 52px);
        line-height: 1.1;
        text-transform: uppercase;
        color: #FFFFFF;
    }
    .sc-home .sc-gate--garden .sc-gate__desc {
        margin: 0 0 18px;
        max-width: 36ch;
        font-size: 16px;
        line-height: 1.6;
        color: rgba(255, 255, 255, .82);
    }
    .sc-home .sc-gate--garden .sc-gate__link {
        display: inline-block;
        font-family: 'Space Mono', monospace;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #FFFFFF;
        text-decoration: underline;
        text-underline-offset: 4px;
        text-decoration-thickness: 1px;
    }

    @media (prefers-reduced-motion: reduce) {
        /* Engine tắt clip-path (ảnh hiện nguyên) và home-boot hạ span xuống
           1.0. Dải nắng đứng im giữa ảnh sẽ trông như lỗi — ẩn hẳn. */
        .sc-home .sc-gate__beam { display: none; }

        /* Chữ phải hiện sẵn, đứng yên, đọc và bấm được ngay. Engine vẫn lái
           opacity theo cue kể cả khi reduced (chỉ bỏ transform), và còn ghi
           thẳng style.pointerEvents="none" (inline) lên phần tử có cue khi
           cue chưa mở -> phải !important để thắng inline style đó. */
        .sc-home .sc-gate--garden .sc-gate__copy { opacity: 1 !important; transform: none !important; pointer-events: auto !important; }
        .sc-home .sc-gate--garden .sc-gate__plate { opacity: .62; }
    }

    @media (max-width: 860px) {
        /* Ảnh 2720px rộng cắt trong khung dọc chỉ còn ~24% chiều rộng gốc.
           Lấy phần chậu cây lưỡi hổ + phát tài cạnh sofa (khoảng 24%-48%
           chiều rộng gốc) thay vì mảng tường tối giữa ảnh. Chiều cao ảnh
           không bị cắt thêm (cover khớp đúng theo chiều cao), nên giữ
           100svh, không cần hạ. */
        .sc-home .sc-gate__frame img { object-position: 36% center; }

        /* Màn dọc: vmin = bề ngang (~390px), lá theo vmin quá nhỏ để che kín
           khung -> phóng lá lên. Ảnh 1200w vẫn đủ nét ở cỡ này. */
        .sc-home .sc-gate--garden { --leaf-k: 1.7; }

        /* Mảng tường tối không còn trong khung dọc -> đưa khối chữ xuống đáy
           ảnh, tràn ngang theo gutter, plate đổi thành dải gradient từ đáy
           lên. Cỡ chữ tự co theo clamp() đã khai ở trên. */
        .sc-home .sc-gate--garden .sc-gate__plate {
            left: 0;
            top: auto;
            bottom: 0;
            width: 100%;
            height: 62%;
            background: linear-gradient(180deg,
                rgba(10, 10, 8, 0) 0%,
                rgba(10, 10, 8, .55) 55%,
                rgba(10, 10, 8, .82) 100%);
        }
        .sc-home .sc-gate--garden .sc-gate__copy {
            left: 0;
            right: 0;
            top: auto;
            bottom: 0;
            width: auto;
            padding: 0 var(--sc-gutter, 24px) clamp(28px, 7vw, 48px);
        }
        .sc-home .sc-gate--garden .sc-gate__desc { max-width: none; }
    }
</style>
