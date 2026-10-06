{{--
    Các nhóm cây hợp mệnh. Dùng chung cho SSR (index, element) và cho
    recommendations_html của API /api/phong-thuy/menh, nên chỉ phụ thuộc
    tham số dưới đây.

    Tham số:
      $element          (string) kim | thuy | moc | hoa | tho
      $recommendations  (array|Collection) ban_menh, tuong_sinh, trung_tinh là
                        collection Product (nên eager-load variants)
      $showWaitlist     (bool) hiện form nhận tin trong nhóm bản mệnh
--}}
@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $el = $lex['elements'][$element];
    $mother = $lex['elements'][$el['mother']];
    $groups = collect($recommendations ?? []);
    $own = collect($groups->get('ban_menh', []));
    $moth = collect($groups->get('tuong_sinh', []));
    $neutral = collect($groups->get('trung_tinh', []));
    $showWaitlist = $showWaitlist ?? false;
@endphp
<section id="cpt-cay" class="cpt-group" aria-labelledby="cpt-ban-menh" tabindex="-1">
    <div class="cpt-group__inner">
        <div class="cpt-group__head">
            <h2 id="cpt-ban-menh">Cây bản mệnh</h2>
            <span lang="zh-Hant" class="cpt-han cpt-group__han" aria-label="hành {{ $el['name'] }}">{{ $el['han'] }}</span>
        </div>
        <div class="cpt-grid">
            @foreach($own as $i => $product)
                @include('shop.phong-thuy.partials.plant', ['product' => $product, 'lead' => $loop->first])
            @endforeach
            @if($showWaitlist)
                @include('shop.phong-thuy.partials.waitlist', ['element' => $element])
            @endif
        </div>
    </div>
</section>

@if($moth->isNotEmpty())
    <section class="cpt-group" aria-labelledby="cpt-tuong-sinh">
        <div class="cpt-group__inner">
            <div class="cpt-group__head">
                <h2 id="cpt-tuong-sinh">Cây tương sinh: {{ $mother['name'] }} sinh {{ $el['name'] }}</h2>
                <span lang="zh-Hant" class="cpt-han cpt-group__han" aria-label="hành {{ $mother['name'] }}">{{ $mother['han'] }}</span>
            </div>
            <div class="cpt-grid">
                @foreach($moth as $product)
                    @include('shop.phong-thuy.partials.plant', ['product' => $product, 'lead' => $loop->first])
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($neutral->isNotEmpty())
    <section class="cpt-group" aria-labelledby="cpt-trung-tinh">
        <div class="cpt-group__inner">
            <div class="cpt-group__head">
                <h2 id="cpt-trung-tinh">Cây trung tính: không khắc mệnh {{ $el['name'] }}</h2>
            </div>
            <div class="cpt-grid">
                @foreach($neutral as $product)
                    @include('shop.phong-thuy.partials.plant', ['product' => $product, 'lead' => false])
                @endforeach
            </div>
        </div>
    </section>
@endif
