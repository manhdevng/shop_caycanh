{{--
    Form nhận tin khi mệnh có ít cây (trạng thái "few" của v2). POST
    /cay-phong-thuy/nhan-tin với email + element. JS gửi bằng fetch (Accept:
    application/json) và chờ 2xx { message? } hoặc 422 { errors.email }; tắt
    JS thì form POST thường, controller redirect back kèm
    session('waitlist_message') hoặc lỗi 'email'.

    Tham số: $element (string) kim | thuy | moc | hoa | tho
--}}
@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $elName = $lex['elements'][$element]['name'];
    $done = session('waitlist_message');
    $emailError = isset($errors) ? $errors->first('email') : null;
@endphp
<div class="cpt-waitlist">
    <p class="cpt-waitlist__lead">Mệnh {{ $elName }} hiện có ít cây hợp. Để lại email, chúng tôi báo khi có thêm.</p>
    <form method="POST" action="{{ url('/cay-phong-thuy/nhan-tin') }}" novalidate data-cpt-waitlist
          data-done="Đã lưu. Chúng tôi sẽ báo khi có thêm cây mệnh {{ $elName }}.">
        @csrf
        <input type="hidden" name="element" value="{{ $element }}">
        <p class="cpt-sentence">
            Email của tôi là
            <label for="cpt-email" class="cpt-sr">Email</label>
            <input id="cpt-email" name="email" type="email" autocomplete="email" placeholder="email" class="cpt-blank cpt-blank--email"
                   value="{{ old('email') }}" aria-invalid="{{ $emailError ? 'true' : 'false' }}" aria-describedby="cpt-email-tb" @if($done) readonly @endif>.
        </p>
        <button type="submit" class="cpt-btn{{ $done ? ' is-on' : '' }}" @if($done) disabled @endif>{{ $done ? 'Đã nhận' : 'Nhận tin' }}</button>
        <p id="cpt-email-tb" role="status" class="cpt-waitlist__msg">{{ $done ? $done : $emailError }}</p>
    </form>
</div>
