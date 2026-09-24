{{--
    home-motion.blade.php — nạp toàn bộ chuyển động cuộn của trang chủ
    (public/js/home-motion.js), chạy bằng GSAP + ScrollTrigger — engine DUY
    NHẤT của trang chủ (ScrollCraft JS không còn được nạp ở đây; file CSS của
    nó vẫn dùng cho .sc-leaf / .sc-scrim).

    Ba script đều defer nên chạy theo đúng thứ tự dưới đây, trước
    DOMContentLoaded. GSAP tự host trong public/vendor/gsap (bản 3.15.0),
    không phụ thuộc CDN.
--}}
@push('scripts')
    <script src="{{ asset('vendor/gsap/gsap.min.js') }}" defer></script>
    <script src="{{ asset('vendor/gsap/ScrollTrigger.min.js') }}" defer></script>
    <script src="{{ asset('js/home-motion.js') }}?v={{ filemtime(public_path('js/home-motion.js')) }}" defer></script>
@endpush
