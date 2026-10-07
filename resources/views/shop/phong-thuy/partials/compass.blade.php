{{--
    La bàn ngũ hành (SVG, viewBox 1000×1000, tâm 500,500) theo La Ban.dc.html.

    Tham số:
      $mini     (bool, mặc định false) bản nhỏ cho trang chủ: chỉ vòng Địa Chi,
                năm nút hành và sợi chỉ đỏ; không quay.
      $reading  (array|null) kết quả đã trình bày (lexicon 'present'). Có thì
                vẽ sẵn trạng thái cuối: vòng xoay tới Can/Chi/năm, kim chỉ hành.
      $element  (string|null) chỉ tô hành (trang chia sẻ menh-{element}),
                không có năm cá nhân.
      $id       (string) id gốc cho phần tử SVG.

    public/js/phong-thuy-compass.js đọc các data-* bên dưới để chạy chuyển động;
    khi tắt JS, SVG này đã đúng trạng thái.
--}}
@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $mini = $mini ?? false;
    $reading = $reading ?? null;
    $element = $reading['el'] ?? ($element ?? null);
    $id = $id ?? 'cpt-la-ban';

    $f = fn ($n) => number_format($n, 2, '.', '');
    $pt = function ($r, $deg) { $t = deg2rad($deg); return [$r * sin($t), -$r * cos($t)]; };
    $R = 220;
    $arcD = function ($a1, $a2) use ($pt, $R, $f) {
        [$ax, $ay] = $pt($R, $a1); [$bx, $by] = $pt($R, $a2);
        return "M{$f($ax)} {$f($ay)} A{$R} {$R} 0 0 1 {$f($bx)} {$f($by)}";
    };
    $chord = function ($p1, $p2, $cut) use ($pt, $R, $f) {
        [$px, $py] = $pt($R, $p1 * 72); [$qx, $qy] = $pt($R, $p2 * 72);
        $dx = $qx - $px; $dy = $qy - $py; $L = hypot($dx, $dy);
        return ['x1' => $f($px + $dx / $L * $cut), 'y1' => $f($py + $dy / $L * $cut), 'x2' => $f($qx - $dx / $L * $cut), 'y2' => $f($qy - $dy / $L * $cut)];
    };
    $gap = 12;
    $arcLen = number_format(2 * M_PI * $R * (72 - 2 * $gap) / 360, 1, '.', '');

    $own = $element !== null ? array_search($element, $lex['order'], true) : false;
    $own = $own === false ? -1 : $own;
    $motherPos = $own >= 0 ? ($own + 4) % 5 : -1;
    $lit = $own >= 0;
    $base = $lit ? 0.38 : 1;
    $hasYear = $reading !== null;

    $rot = [
        'a' => $hasYear ? -$reading['chi'] * 30 : 0,
        'b' => $hasYear ? -$reading['idx'] * 6 : 0,
        'c' => $hasYear ? -$reading['can'] * 36 : 0,
    ];
    // Kim nghỉ chỉ hướng Nam (180°); có hành thì chỉ vào nút hành, lấy góc gần 180 nhất.
    $needle = 180;
    if ($own >= 0) {
        $needle = $own * 72;
        while ($needle - 180 > 180) { $needle -= 360; }
        while ($needle - 180 < -180) { $needle += 360; }
    }
    $hlPos = $own >= 0 ? $own : 1;

    if ($hasYear) {
        $aria = 'La bàn ngũ hành dừng ở năm ' . $reading['canChi'] . ', kim chỉ hành ' . $reading['elName'];
    } elseif ($own >= 0) {
        $aria = 'La bàn ngũ hành, kim chỉ hành ' . $lex['elements'][$element]['name'];
    } else {
        $aria = 'La bàn ngũ hành: vòng 12 Địa Chi, 60 Hoa Giáp, 10 Thiên Can và năm hành tương sinh, tương khắc';
    }
@endphp
<svg id="{{ $id }}" class="cpt-compass{{ $mini ? ' cpt-compass--mini' : '' }}" viewBox="0 0 1000 1000" role="img" aria-label="{{ $aria }}"
     data-compass data-mini="{{ $mini ? 1 : 0 }}" data-hl="{{ $own }}" data-idx="{{ $hasYear ? $reading['idx'] : -1 }}"
     data-a="{{ $rot['a'] }}" data-b="{{ $rot['b'] }}" data-c="{{ $rot['c'] }}" data-n="{{ $needle }}">
    <g transform="translate(500 500)">
        <circle r="470" fill="#f4e6cd"></circle>
        <g data-ring="a" transform="rotate({{ $rot['a'] }})">
            <circle r="441" fill="none" stroke="#000" stroke-width="58"></circle>
            @foreach($lex['chi'] as $i => $vi)
                <g transform="rotate({{ $i * 30 }})">
                    <line x1="0" y1="-412" x2="0" y2="-470" stroke="#f4e6cd" stroke-width="1" transform="rotate(15)"></line>
                    <text class="cpt-svg-han" y="-447" fill="#f4e6cd" font-weight="500" font-size="28" text-anchor="middle" dominant-baseline="central">{{ $lex['chiHan'][$i] }}</text>
                    <text class="cpt-svg-vi" y="-422" fill="#f4e6cd" font-size="12" text-anchor="middle" dominant-baseline="central">{{ $vi }}</text>
                </g>
            @endforeach
        </g>
        @unless($mini)
            <g data-ring="b" transform="rotate({{ $rot['b'] }})">
                @for($i = 0; $i < 60; $i++)
                    <line x1="0" y1="-404" x2="0" y2="{{ $i % 5 === 0 ? -376 : -388 }}" stroke="rgba(30,33,30,.38)" stroke-width="1" transform="rotate({{ $i * 6 }})"></line>
                @endfor
            </g>
            <g data-ring="c" transform="rotate({{ $rot['c'] }})">
                <circle r="340" fill="none" stroke="#edddc3" stroke-width="48"></circle>
                <circle r="364" fill="none" stroke="#1e211e" stroke-width="1"></circle>
                <circle r="316" fill="none" stroke="#1e211e" stroke-width="1"></circle>
                @foreach($lex['can'] as $i => $vi)
                    <g transform="rotate({{ $i * 36 }})">
                        <text class="cpt-svg-han" y="-347" fill="#1e211e" font-weight="500" font-size="24" text-anchor="middle" dominant-baseline="central">{{ $lex['canHan'][$i] }}</text>
                        <text class="cpt-svg-vi" y="-325" fill="#1e211e" font-size="11" text-anchor="middle" dominant-baseline="central">{{ $vi }}</text>
                    </g>
                @endforeach
            </g>
            <circle r="300" fill="none" stroke="#1e211e" stroke-width="1"></circle>
            @for($p = 0; $p < 5; $p++)
                @php $s = $chord(($p + 3) % 5, $p, 44); @endphp
                <line data-star="{{ $p }}" x1="{{ $s['x1'] }}" y1="{{ $s['y1'] }}" x2="{{ $s['x2'] }}" y2="{{ $s['y2'] }}" stroke="#1e211e" stroke-opacity="0.38" stroke-width="1" stroke-dasharray="6 6" opacity="{{ $base }}"></line>
            @endfor
            @php $hs = $chord(($hlPos + 3) % 5, $hlPos, 44); @endphp
            <line data-hl-star x1="{{ $hs['x1'] }}" y1="{{ $hs['y1'] }}" x2="{{ $hs['x2'] }}" y2="{{ $hs['y2'] }}" stroke="#1e211e" stroke-width="1.5" stroke-dasharray="6 6" opacity="{{ $lit ? 1 : 0 }}"></line>
            @for($p = 0; $p < 5; $p++)
                <path data-arc="{{ $p }}" d="{{ $arcD($p * 72 - 72 + $gap, $p * 72 - $gap) }}" fill="none" stroke="#1e211e" stroke-width="1" opacity="{{ $base }}"></path>
            @endfor
            <path data-hl-arc d="{{ $arcD($hlPos * 72 - 72 + $gap, $hlPos * 72 - $gap) }}" fill="none" stroke="#1e211e" stroke-width="2.5" stroke-dasharray="{{ $arcLen }}" stroke-dashoffset="{{ $lit ? 0 : $arcLen }}" opacity="{{ $lit ? 1 : 0 }}"></path>
        @endunless
        <line x1="0" y1="-486" x2="0" y2="486" stroke="#9C2F23" stroke-opacity="0.9" stroke-width="1"></line>
        <line x1="-486" y1="0" x2="486" y2="0" stroke="#9C2F23" stroke-opacity="0.9" stroke-width="1"></line>
        @unless($mini)
            <line data-tick x1="0" y1="-404" x2="0" y2="{{ $hasYear ? -372 : -388 }}" stroke="#1e211e" stroke-width="2" opacity="{{ $hasYear ? 1 : 0 }}"></line>
            <text data-tick-label class="cpt-svg-vi" x="10" y="-384" fill="#1e211e" font-weight="500" font-size="14" opacity="{{ $hasYear ? 1 : 0 }}">{{ $hasYear ? $reading['canChi'] : '' }}</text>
        @endunless
        @foreach($lex['order'] as $p => $key)
            @php
                [$nx, $ny] = $pt($R, $p * 72);
                $isOwn = $lit && $p === $own;
                $isMother = $lit && $p === $motherPos;
            @endphp
            <g data-node="{{ $p }}" transform="translate({{ $f($nx) }} {{ $f($ny) }})" opacity="{{ ($isOwn || $isMother) ? 1 : $base }}">
                <circle r="34" fill="{{ $isOwn ? '#1e211e' : '#f4e6cd' }}" stroke="#1e211e" stroke-width="{{ $isMother ? 2 : 1 }}"></circle>
                <text data-han class="cpt-svg-han" y="1" fill="{{ $isOwn ? '#f4e6cd' : '#1e211e' }}" font-weight="700" font-size="44" text-anchor="middle" dominant-baseline="central">{{ $lex['elements'][$key]['han'] }}</text>
                <text class="cpt-svg-vi" y="54" fill="#1e211e" font-size="13" text-anchor="middle" dominant-baseline="central">{{ $lex['elements'][$key]['name'] }}</text>
            </g>
        @endforeach
        @unless($mini)
            <g data-needle transform="rotate({{ $needle }})">
                <line x1="0" y1="8" x2="0" y2="-250" stroke="#9C2F23" stroke-width="2"></line>
                <path d="M0 -262 L-5 -244 L5 -244 Z" fill="#9C2F23"></path>
                <path d="M0 14 L7 28 L0 42 L-7 28 Z" fill="#9C2F23"></path>
                <circle r="6" fill="#9C2F23"></circle>
            </g>
        @endunless
    </g>
</svg>
