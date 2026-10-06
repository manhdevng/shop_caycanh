{{-- Font và CSS riêng của trang Cây hợp mệnh; chỉ nạp ở trang này (đặt trong @push('styles')). --}}
@php $lex = require resource_path('views/shop/phong-thuy/lexicon.php'); @endphp
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="{{ $lex['fontsHref'] }}">
<link rel="stylesheet" href="{{ $lex['hanFontsHref'] }}">
<link rel="stylesheet" href="{{ asset('css/phong-thuy.css') }}?v={{ @filemtime(public_path('css/phong-thuy.css')) }}">
<script>document.documentElement.classList.add('cpt-js');</script>
