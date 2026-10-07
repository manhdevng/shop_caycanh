{{--
    Trang chia sẻ theo hành — GET /cay-phong-thuy/menh-{element}.
    URL chỉ chứa hành nên trang hiện thông tin chung của hành và cây, không
    giả vờ biết Can Chi/Nạp Âm cá nhân.

    Biến:
      $element          (string) kim | thuy | moc | hoa | tho
      $recommendations  (array) ban_menh, tuong_sinh, trung_tinh (collection Product)
      $showWaitlist     (bool)
--}}
@extends('layouts.shop')

@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $el = $lex['elements'][$element];
    $mother = $lex['elements'][$el['mother']];
    $avoid = $lex['elements'][$el['avoid']];
@endphp

@section('title', 'Cây hợp mệnh ' . $el['name'] . ' — Cây Cảnh Shop')

@push('styles')
    @include('shop.phong-thuy.partials.head')
@endpush

@section('content')
<div class="cpt" data-cpt>
    <section class="cpt-hero" aria-label="Cây hợp mệnh {{ $el['name'] }}">
        <div class="cpt-hero__grid">
            <div class="cpt-hero__compass">
                <div class="cpt-hero__compass-box">
                    @include('shop.phong-thuy.partials.compass', ['element' => $element, 'id' => 'cpt-la-ban'])
                </div>
            </div>

            <div class="cpt-hero__left">
                <div class="cpt-result">
                    <div class="cpt-seal" aria-hidden="true">
                        <div class="cpt-seal__bleed"></div>
                        <div class="cpt-seal__face"><span lang="zh-Hant">{{ $el['han'] }}</span></div>
                    </div>
                    <p class="cpt-result__year">Gợi ý chung cho người mệnh {{ $el['name'] }}</p>
                    <div class="cpt-result__name">
                        <span lang="zh-Hant" class="cpt-result__han">{{ $el['han'] }}</span>
                        <h1 class="cpt-result__title">Mệnh {{ $el['name'] }}</h1>
                    </div>
                    <div class="cpt-result__text">
                        <p class="cpt-result__gloss">{{ $el['about'] }} {{ $el['advice'] }}</p>
                    </div>
                    <dl class="cpt-table">
                        <dt>Hợp</dt>
                        <dd>{{ $el['name'] }} · {{ $mother['name'] }} (<span lang="zh-Hant" class="cpt-han">{{ $mother['han'] }}</span> sinh <span lang="zh-Hant" class="cpt-han">{{ $el['han'] }}</span>)</dd>
                        <dt>Nên tránh</dt>
                        <dd>{{ $avoid['name'] }} (<span lang="zh-Hant" class="cpt-han">{{ $avoid['han'] }}</span> khắc <span lang="zh-Hant" class="cpt-han">{{ $el['han'] }}</span>)</dd>
                    </dl>
                    <div class="cpt-share-cta">
                        <p>Muốn biết Nạp Âm và năm Can Chi của riêng bạn? Nhập ngày sinh, la bàn sẽ chỉ hành của bạn.</p>
                        <a href="{{ url('/cay-phong-thuy') }}" class="cpt-btn">Xem mệnh theo ngày sinh</a>
                    </div>
                </div>
                <a href="#cpt-cay" class="cpt-arrow" aria-label="Xuống danh sách cây hợp mệnh {{ $el['name'] }}">↓</a>
            </div>
        </div>
    </section>

    @include('shop.phong-thuy.partials.product-grid', [
        'element' => $element,
        'recommendations' => $recommendations ?? [],
        'showWaitlist' => $showWaitlist ?? false,
    ])

    @include('shop.phong-thuy.partials.band', ['current' => $element])
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/phong-thuy-compass.js') }}?v={{ @filemtime(public_path('js/phong-thuy-compass.js')) }}" defer></script>
@endpush
