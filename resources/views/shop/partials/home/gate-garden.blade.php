{{-- Khối C — "Một góc xanh cho mỗi căn phòng". Cảnh ghim thứ nhất.

     Bản trước có TÁM chiếc lá xoay 80-100° quanh cuống rồi văng hẳn ra khỏi
     màn hình, cộng một dải nắng quét ngang: quá nhiều thứ động cùng lúc (rối
     mắt) và góc xoay lớn tới mức lá trông như nan quạt giấy chứ không như lá
     thật. Nay rút còn BA chiếc ở mép khung, mỗi chiếc chỉ hé ra ~26° quanh
     cuống — vừa đủ thấy khung lá sống, vẫn tự nhiên. Dải nắng đã bỏ hẳn.

     Cuối cảnh, tấm ảnh mờ dần trên cung sang trái. Cảnh sân vườn kế tiếp mở
     từ khe giữa sang hai bên, vẫn giữ ảnh đứng thẳng. Toàn bộ do home-motion.js
     điều khiển.

     CSS dưới đây vẽ lá ở trạng thái ĐÃ HÉ. JS chỉ kéo lá về thế khép khi
     chuyển động được phép, nên không JS / giảm chuyển động -> ảnh và chữ hiện
     đủ, lá nằm yên ở mép như một khung. --}}
@php
    $indoorGroup = $plantGroups->first(fn ($g) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($g->name), 'trong nhà'));
    $indoorHref = $indoorGroup
        ? \App\Http\Controllers\ShopController::catalogUrl(['categories' => $indoorGroup->children->pluck('id')->all()])
        : \App\Http\Controllers\ShopController::catalogUrl(['type' => 'plant']);

    // Ba chiếc lá khung. ax/ay: điểm cắm cuống theo % khung. rot: góc lúc đã
    // hé (tĩnh, cũng là thế mặc định khi không có JS). from: góc lúc khép —
    // lệch khỏi rot đúng 26° về phía giữa khung (lá trái +, lá phải −), nhỏ
    // vừa đủ để mắt đọc ra là lá đang hé chứ không phải quạt đang xoè.
    // w: rộng theo vmin. depth: 0.55 xa · 1 gần (chỉ còn hai lớp cho gọn).
    // ox: vị trí cuống theo % ngang ảnh (README trong thư mục foliage); ảnh
    // lật ngang thì cuống đổi phía -> ox = 100 - ox.
    $gardenLeaves = [
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => true,  'ax' => 103, 'ay' => 58,  'w' => 46, 'rot' => 22,  'from' => -4,  'depth' => .55],
        ['src' => 'monstera', 'ox' => 65.4, 'flip' => false, 'ax' => 1,   'ay' => 108, 'w' => 54, 'rot' => -16, 'from' => 10,  'depth' => 1],
        ['src' => 'la-gan',   'ox' => 48.9, 'flip' => true,  'ax' => 99,  'ay' => 108, 'w' => 52, 'rot' => 16,  'from' => -10, 'depth' => 1],
    ];
@endphp
<section class="sc-gate sc-gate--garden">
    <div class="sc-gate__stage">
        <div class="sc-gate__frame">
            <img src="{{ asset('images/lifestyle-hero.webp') }}"
                 alt="Phòng khách ngập nắng với những chậu cây lưỡi hổ, phát tài núi và sung lá vĩ cầm đặt quanh sofa, tạo thành một khu vườn trong nhà."
                 width="2720" height="1414">
        </div>
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
        <div class="sc-gate__copy">
            <p class="sc-gate__label">Cây trong nhà</p>
            <h2 class="sc-gate__title">Một góc xanh cho mỗi căn phòng</h2>
            <p class="sc-gate__desc">Lưỡi hổ bên cửa kính, phát tài cạnh sofa, sung lá vĩ cầm nơi góc tường. Những loại cây ưa bóng râm, ít cần chăm, giữ cho căn nhà trong lành và dịu lại sau một ngày dài.</p>
            <a href="{{ $indoorHref }}" class="sc-home__view-all sc-home__view-all--inverse">Xem tất cả cây trong nhà <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</section>

<style>
    /* Nền cổng mang tiếp màu tối của khối B — nhát cắt cứng, không nội suy. */
    .sc-home .sc-gate--garden { background: var(--sc-ground-dark); }

    .sc-home .sc-gate__stage {
        position: relative;
        height: 100vh;
        height: 100svh;
        overflow: clip;
        background: var(--sc-ground-dark);
    }

    .sc-home .sc-gate__frame {
        position: absolute;
        inset: 0;
        overflow: hidden;
    }
    /* Rời cảnh: tấm ảnh trôi trên vòng cung (transform + border-radius do GSAP
       lái, home-motion.js/arcSlot). Chỉ transform/opacity nên không gây
       reflow; will-change báo trước cho trình duyệt tách lớp. */
    .sc-home.motion-on .sc-gate__frame { will-change: transform; }
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

    /* Tấm scrim của khối chữ. Là anh em của .sc-gate__copy (không phải
       ::before của nó): GSAP ẩn/hiện khối chữ bằng autoAlpha (visibility),
       mà visibility:hidden ẩn luôn pseudo-element — scrim phải sống riêng để
       không tắt theo chữ.
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
        /* Không JS / giảm chuyển động: scrim hiện sẵn để chữ trắng vẫn đủ
           tương phản. Có chuyển động thì GSAP ghi đè opacity inline, cho scrim
           lên trước chữ một nhịp rồi nhạt cùng lúc chữ nhạt (gardenScene). */
        opacity: .9;
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
