{{--
    Khối kết quả ở cột trái. Luôn được render: khi $reading null thì ẩn và để
    trống, phong-thuy-compass.js điền các [data-r] từ JSON của API.
    Tham số: $reading (array|null) từ lexicon 'present'.
--}}
@php $r = $reading ?? null; @endphp
<div class="cpt-result" data-cpt-result tabindex="-1" @unless($r) hidden @endunless>
    <div class="cpt-seal" data-cpt-seal aria-hidden="true">
        <div class="cpt-seal__bleed"></div>
        <div class="cpt-seal__face"><span lang="zh-Hant" data-r="elHan">{{ $r['elHan'] ?? '' }}</span></div>
    </div>
    <p class="cpt-result__year"><span data-r="canChi">{{ $r['canChi'] ?? '' }}</span> · năm âm lịch <span data-r="lunarYear">{{ $r['lunarYear'] ?? '' }}</span></p>
    <div class="cpt-result__name">
        <span lang="zh-Hant" class="cpt-result__han" data-r="han" @if(empty($r['han'])) hidden @endif>{{ $r['han'] ?? '' }}</span>
        <h1 class="cpt-result__title" id="cpt-ket-qua" data-r="name">{{ $r['name'] ?? '' }}</h1>
    </div>
    <div class="cpt-result__text">
        <p class="cpt-result__gloss"><span data-r="gloss">{{ $r['gloss'] ?? '' }}</span> <span data-r="advice">{{ $r['advice'] ?? '' }}</span></p>
        <p class="cpt-result__note" data-r="note" @if(empty($r['note'])) hidden @endif>{{ $r['note'] ?? '' }}</p>
    </div>
    <dl class="cpt-table">
        <dt>Hợp</dt>
        <dd><span data-r="elName">{{ $r['elName'] ?? '' }}</span> · <span data-r="motherName">{{ $r['motherName'] ?? '' }}</span> (<span lang="zh-Hant" class="cpt-han" data-r="motherHan">{{ $r['motherHan'] ?? '' }}</span> sinh <span lang="zh-Hant" class="cpt-han" data-r="elHan">{{ $r['elHan'] ?? '' }}</span>)</dd>
        <dt>Nên tránh</dt>
        <dd><span data-r="avoidName">{{ $r['avoidName'] ?? '' }}</span> (<span lang="zh-Hant" class="cpt-han" data-r="avoidHan">{{ $r['avoidHan'] ?? '' }}</span> khắc <span lang="zh-Hant" class="cpt-han" data-r="elHan">{{ $r['elHan'] ?? '' }}</span>)</dd>
    </dl>
    <a href="{{ url('/cay-phong-thuy') }}" class="cpt-link" data-cpt-again>Xem với ngày sinh khác</a>
</div>
