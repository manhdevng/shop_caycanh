{{--
    Bản Việt hoá của mẫu thư thông báo mặc định Laravel
    (vendor/laravel/framework/src/Illuminate/Notifications/resources/views/email.blade.php).
    Dùng cho mọi MailMessage: thư xác nhận email, thông báo trạng thái đơn hàng...
--}}
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# Rất tiếc!
@else
# Xin chào!
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Trân trọng,<br>
{{ config('shop.name', config('app.name')) }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
Nếu bạn không bấm được nút "{{ $actionText }}", hãy sao chép đường link dưới đây và dán vào trình duyệt:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
