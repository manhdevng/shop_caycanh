{{--
    Cây hợp mệnh — /cay-phong-thuy (GET, nghỉ) và POST /cay-phong-thuy/tra-cuu (SSR kết quả).

    Biến:
      $result           (array|null) result của PhongThuyService; null ở trạng thái nghỉ
      $recommendations  (array|null) ban_menh, tuong_sinh, trung_tinh (collection Product)
      $showWaitlist     (bool) mệnh ít cây -> hiện form nhận tin
      $input            (array|null, tuỳ chọn) ngay/thang/nam vừa gửi, chỉ để điền lại form
                        trong chính response này; mặc định đọc old()
    Lỗi validation đọc từ $errors (ngay, thang, nam).

    Có JS: phong-thuy-compass.js chặn submit, gọi POST /api/phong-thuy/menh và chạy la bàn.
--}}
@extends('layouts.shop')

@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $reading = ($lex['present'])($result ?? null);
    $input = $input ?? [];
    $val = fn ($k) => $input[$k] ?? old($k);
    $yearError = $errors->first('nam');
    $dateError = $errors->first('ngay') ?: $errors->first('thang');
    $formError = $yearError ?: ($dateError ?: $errors->first());
    $dayHintOn = blank($val('ngay')) || blank($val('thang'));

    $jsLex = [
        'elements' => collect($lex['elements'])->map(fn ($e) => ['name' => $e['name'], 'han' => $e['han'], 'mother' => $e['mother'], 'avoid' => $e['avoid'], 'advice' => $e['advice']]),
        'order' => $lex['order'],
        'can' => $lex['can'],
        'chi' => $lex['chi'],
        'nap' => collect($lex['nap'])->map(fn ($n) => [$n[0], $n[1], $n[2], $n[3]]),
    ];
@endphp

@section('title', $reading ? $reading['name'] . ' · Cây hợp mệnh — Cây Cảnh Shop' : 'Cây hợp mệnh — Cây Cảnh Shop')

@push('styles')
    @include('shop.phong-thuy.partials.head')
@endpush

@section('content')
<div class="cpt" data-cpt
     data-api="{{ url('/api/phong-thuy/menh') }}"
     data-max-year="{{ now()->year }}"
     @if($reading) data-replay @endif>

    <section class="cpt-hero" aria-label="Xem mệnh chọn cây">
        <div class="cpt-hero__grid">
            <div class="cpt-hero__compass">
                <div class="cpt-hero__compass-box">
                    @include('shop.phong-thuy.partials.compass', ['reading' => $reading, 'id' => 'cpt-la-ban'])
                </div>
            </div>

            <div class="cpt-hero__left">
                <div class="cpt-panel" data-cpt-form-panel @if($reading) hidden @endif>
                    <h1 class="cpt-title" id="cpt-title">Cây nào hợp mệnh bạn?</h1>
                    <form class="cpt-form" method="POST" action="{{ url('/cay-phong-thuy/tra-cuu') }}" novalidate data-cpt-form>
                        @csrf
                        <p class="cpt-sentence">
                            Tôi sinh ngày
                            <label for="cpt-ngay" class="cpt-sr">Ngày sinh</label>
                            <input id="cpt-ngay" name="ngay" class="cpt-blank cpt-blank--ngay" inputmode="numeric" autocomplete="bday-day" maxlength="2" placeholder="ngày"
                                   value="{{ $val('ngay') }}" aria-invalid="{{ $dateError ? 'true' : 'false' }}" aria-describedby="cpt-loi cpt-goi-y">
                            tháng
                            <label for="cpt-thang" class="cpt-sr">Tháng sinh</label>
                            <input id="cpt-thang" name="thang" class="cpt-blank cpt-blank--thang" inputmode="numeric" autocomplete="bday-month" maxlength="2" placeholder="tháng"
                                   value="{{ $val('thang') }}" aria-invalid="{{ $dateError ? 'true' : 'false' }}" aria-describedby="cpt-loi cpt-goi-y">
                            năm
                            <label for="cpt-nam" class="cpt-sr">Năm sinh</label>
                            <input id="cpt-nam" name="nam" class="cpt-blank cpt-blank--nam" inputmode="numeric" autocomplete="bday-year" maxlength="4" placeholder="năm"
                                   value="{{ $val('nam') }}" aria-invalid="{{ $yearError ? 'true' : 'false' }}" aria-describedby="cpt-loi">.
                        </p>
                        <p id="cpt-goi-y" class="cpt-hint" data-cpt-hint>@if($dayHintOn)Nếu bạn sinh tháng 1 hoặc đầu tháng 2, nhập đủ ngày để kết quả chính xác.@endif</p>
                        <div id="cpt-loi" data-cpt-error>
                            @if($formError)
                                <p role="alert" class="cpt-error"><span aria-hidden="true" class="cpt-error__x">✕</span><span>{{ $formError }}</span></p>
                            @endif
                        </div>
                        <div class="cpt-actions">
                            <button type="submit" class="cpt-btn" data-cpt-submit>Xem mệnh</button>
                            <p class="cpt-caption">Không lưu ngày sinh của bạn.</p>
                        </div>
                    </form>
                </div>

                @include('shop.phong-thuy.partials.result', ['reading' => $reading])

                <a href="{{ $reading ? '#cpt-cay' : '#cpt-theo-menh' }}" class="cpt-arrow" data-cpt-arrow
                   aria-label="{{ $reading ? 'Xuống danh sách cây hợp mệnh' : 'Xuống phần xem cây theo mệnh' }}">↓</a>
                <p class="cpt-sr" aria-live="polite" data-cpt-live></p>
            </div>
        </div>
    </section>

    <div data-cpt-list>
        @if($reading)
            @include('shop.phong-thuy.partials.product-grid', [
                'element' => $reading['el'],
                'recommendations' => $recommendations ?? [],
                'showWaitlist' => $showWaitlist ?? false,
            ])
        @endif
    </div>

    @include('shop.phong-thuy.partials.band', ['current' => null])
</div>
<script type="application/json" id="cpt-lexicon">{!! json_encode($jsLex, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@if($reading)
    <script type="application/json" id="cpt-reading">{!! json_encode(['idx' => $reading['idx'], 'can' => $reading['can'], 'chi' => $reading['chi'], 'canChi' => $reading['canChi'], 'el' => $reading['el'], 'name' => $reading['name'], 'elName' => $reading['elName']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
@endsection

@push('scripts')
    <script src="{{ asset('js/phong-thuy-compass.js') }}?v={{ @filemtime(public_path('js/phong-thuy-compass.js')) }}" defer></script>
@endpush
