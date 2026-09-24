{{--
    home-motion.blade.php — nạp lớp chuyển động hữu cơ "Mọc · Lay · Hướng sáng"
    (public/js/home-motion.js) chạy bằng GSAP + ScrollTrigger.

    Phải include SAU home-boot: cả ba script đều defer nên chạy theo thứ tự
    xuất hiện, sau scrollcraft.js — xem giải thích thời điểm chạy ở đầu
    home-motion.js. GSAP tự host trong public/vendor/gsap (bản 3.15.0) giống
    cách trang tự host ScrollCraft, không phụ thuộc CDN.
--}}
@push('scripts')
    <script src="{{ asset('vendor/gsap/gsap.min.js') }}" defer></script>
    <script src="{{ asset('vendor/gsap/ScrollTrigger.min.js') }}" defer></script>
    <script src="{{ asset('js/home-motion.js') }}?v={{ filemtime(public_path('js/home-motion.js')) }}" defer></script>
@endpush
