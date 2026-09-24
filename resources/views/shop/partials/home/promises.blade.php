<section style="background:#FFFFFF;padding:clamp(56px,8vw,120px) 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:40px">
            @php
                $featureBullets = [
                    'curated' => ['Chọn từ vườn ươm uy tín', 'Kiểm tra kỹ trước khi giao', 'Cam kết đúng kích thước'],
                    'service' => ['Giao hàng toàn quốc', 'Đổi trả trong 3 ngày', 'Bảo hành cây dài hạn'],
                    'materials' => ['Chậu gốm cao cấp', 'Đất trồng hữu cơ', 'Đóng gói chống sốc'],
                ];
                $featurePlaceholderLabels = [
                    'curated' => 'ẢNH VƯỜN ƯƠM',
                    'service' => 'ẢNH GIAO HÀNG',
                    'materials' => 'ẢNH CHẬU &amp; ĐẤT TRỒNG',
                ];
                $featureTitles = [
                    'curated' => 'Cây được tuyển chọn',
                    'service' => 'Dịch vụ tận tâm',
                    'materials' => 'Chất liệu cao cấp',
                ];
                // Lá ở đầu thân dây mỗi cột: ảnh thật đã tách nền, cuống chĩa
                // xuống. ox = vị trí cuống theo % chiều ngang ảnh — lá xoay
                // quanh đúng điểm này (xem public/images/foliage/README.md).
                $vineLeaves = [
                    'curated' => ['src' => 'la-gan', 'ox' => 48.9],
                    'service' => ['src' => 'monstera', 'ox' => 65.4],
                    'materials' => ['src' => 'la-gan', 'ox' => 48.9],
                ];
            @endphp
            @foreach(['curated', 'service', 'materials'] as $i => $slug)
                @php $feature = $homeFeatures->get($slug); @endphp
                <div style="text-align:center">
                    @php $leaf = $vineLeaves[$slug]; @endphp
                    <span class="sc-rule" data-vine aria-hidden="true">
                        <i class="sc-rule__line"></i>
                        <span class="sc-rule__leaf" style="--ox:{{ $leaf['ox'] }}%;--oxf:{{ $leaf['ox'] / 100 }}">
                            <img src="{{ asset('images/foliage/' . $leaf['src'] . '-160.webp') }}" alt="" width="160" height="{{ $leaf['src'] === 'monstera' ? 250 : 286 }}" loading="lazy" decoding="async">
                        </span>
                    </span>
                    <div>
                        <h3 style="font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 20px">{{ $feature->title ?? $featureTitles[$slug] }}</h3>
                        @if($feature && $feature->media_path)
                            <div style="aspect-ratio:1/1;border-radius:12px;overflow:hidden;margin-bottom:20px">
                                @if($feature->isVideo())
                                    <video src="{{ $feature->media_url }}" autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover;display:block"></video>
                                @else
                                    <img src="{{ $feature->media_url }}" alt="{{ $feature->title }}" style="width:100%;height:100%;object-fit:cover;display:block">
                                @endif
                            </div>
                        @else
                            <div class="placeholder-pattern" style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;margin-bottom:20px">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196">{!! $featurePlaceholderLabels[$slug] !!}</span>
                            </div>
                        @endif
                        <ul style="list-style:none;margin:0;padding:0;text-align:left;color:#6B6B66;font-size:14px;line-height:1.9">
                            @foreach($featureBullets[$slug] as $bullet)
                                <li>&mdash; {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
/* Khối H — thân dây mọc. Đường kẻ trên đầu mỗi cột là một thân dây: khi cuộn
   tới, thân dài dần từ trái sang phải (scrub, có độ trễ) và mang theo một
   chiếc lá thật ở ngọn; lá nhú ra từ cuống rồi lay theo tốc độ cuộn
   (public/js/home-motion.js, hàm vines). Nội dung cột đứng yên — chuyển động
   của khối nằm ở thân dây, không nâng chữ lên nữa.

   Trạng thái tĩnh (không JS / giảm chuyển động): thân dài đủ, lá đứng ở ngọn,
   hơi nghiêng — vẫn là một hình hoàn chỉnh, không có gì chờ được hiện ra. */
.sc-home .sc-rule {
    position: relative;
    display: block;
    height: 1px;
    width: 100%;
    margin: 44px 0 24px;
}
.sc-home .sc-rule__line {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #C9D8C0, #3F5B45);
    transform-origin: 0 50%;
}
.sc-home .sc-rule__leaf {
    position: absolute;
    bottom: 0;
    left: 100%;
    width: 34px;
    margin-left: calc(-34px * var(--oxf)); /* đặt cuống lá đúng ngọn thân */
    transform-origin: var(--ox) 100%;
    pointer-events: none;
}
.sc-home .sc-rule__leaf img {
    display: block;
    width: 100%;
    height: auto;
    transform-origin: var(--ox) 100%;
    transform: rotate(-8deg);
    filter: drop-shadow(0 3px 3px rgba(28, 28, 26, .18));
}
</style>
