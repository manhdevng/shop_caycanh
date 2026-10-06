{{--
    Khối "Cây nào hợp mệnh bạn?" ở trang chủ (Khoi Trang Chu v2), thay khối lời hứa 3 cột.
    La bàn nhỏ đứng yên; form POST năm sinh tới /cay-phong-thuy/tra-cuu để năm
    không nằm trong URL. Trang kết quả tự phát lại chuỗi la bàn.
    Lỗi năm sinh: khi bật JS, phong-thuy-teaser.js chặn submit và báo lỗi ngay
    trong [data-ktc2-error] trên trang chủ. Khi tắt JS mà năm sai, POST
    /cay-phong-thuy/tra-cuu render trang /cay-phong-thuy kèm lỗi + năm đã nhập
    (HTTP 422), không redirect và không lưu ngày sinh vào session.
--}}
@php
    $ptLex = require resource_path('views/shop/phong-thuy/lexicon.php');
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ $ptLex['fontsHref'] }}">
    <link rel="stylesheet" href="{{ $ptLex['hanFontsHref'] }}">
    <link rel="stylesheet" href="{{ asset('css/phong-thuy-teaser.css') }}?v={{ @filemtime(public_path('css/phong-thuy-teaser.css')) }}">
@endpush
<section class="ktc2" aria-labelledby="ktc2-title">
    <div class="ktc2__inner">
        <div class="ktc2__compass">
            @include('shop.phong-thuy.partials.compass', ['mini' => true, 'id' => 'ktc2-la-ban'])
        </div>
        <div class="ktc2__body">
            <h2 id="ktc2-title" class="ktc2__title">Cây nào hợp mệnh bạn?</h2>
            <form method="POST" action="{{ url('/cay-phong-thuy/tra-cuu') }}" novalidate class="ktc2__form" data-ktc2-form data-max-year="{{ now()->year }}">
                @csrf
                <p class="ktc2__sentence">
                    Tôi sinh năm
                    <label for="ktc2-nam" class="ktc2__sr">Năm sinh</label>
                    <input id="ktc2-nam" name="nam" class="ktc2__blank" inputmode="numeric" autocomplete="bday-year" maxlength="4" placeholder="năm"
                           aria-invalid="false" aria-describedby="ktc2-loi">.
                </p>
                <button type="submit" class="ktc2__btn">Xem cây hợp mệnh</button>
                <div id="ktc2-loi" data-ktc2-error></div>
            </form>
        </div>
    </div>
</section>
@push('scripts')
    <script src="{{ asset('js/phong-thuy-teaser.js') }}?v={{ @filemtime(public_path('js/phong-thuy-teaser.js')) }}" defer></script>
@endpush
