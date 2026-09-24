{{--
    home-boot.blade.php — hạ tầng scroll-craft cho trang chủ (Việc #1).

    Chạy đúng 4 việc theo thứ tự (PLAN.md mục 5.5):
      1. Đọc data-sc-span-reduced / data-sc-span-mobile, ghi đè vào data-sc-span
         TRƯỚC khi mount (reduced ưu tiên cao nhất).
      2. Thêm class .sc-engine-on lên .sc-home — cổng an toàn cho CSS.
      3. ScrollCraft.mount('.sc-home') trong try/catch; catch thì gỡ class ở
         bước 2 ra, console.warn một dòng — trang trở lại trạng thái tĩnh đầy
         đủ, mọi thứ hiện và bấm được. Không có window.ScrollCraft (script
         chặn/404) thì không thêm class, không mount, thoát im lặng.
      4. Signature move "Hướng sáng": ghi --sc-sun (0 -> 1) lên .sc-home theo
         tiến độ cuộn của CẢ TRANG. Nghe scroll passive, gom bằng một
         requestAnimationFrame (không tạo vòng rAF chạy liên tục — trang đã có
         2 vòng rAF khác: hero-pixel và category-arc, cộng vòng rAF của chính
         engine là 3). Bỏ qua khi document.hidden hoặc khi lệch < 0.002.
         prefers-reduced-motion: reduce -> không gắn listener, --sc-sun giữ
         mặc định 0.5 khai trên .sc-home (scrollcraft-shop.css).

    Thời điểm chạy: thẻ <script> nạp `scrollcraft.js` có `defer`, nên nó thực
    thi SAU khi parser xong tài liệu, ngay trước `DOMContentLoaded`. Script
    inline dưới đây KHÔNG có `defer` nên chạy ngay khi parser đi qua nó — tức
    là TRƯỚC engine. Vì vậy toàn bộ 4 bước được gói trong `boot()` và chỉ chạy
    sau khi `DOMContentLoaded` đã bắn (hoặc ngay lập tức nếu tài liệu đã xong
    parse), để `window.ScrollCraft` chắc chắn đã tồn tại trước khi kiểm tra.
--}}
@push('scripts')
<script>
(function () {
    function boot() {
        var root = document.querySelector('.sc-home');
        if (!root) return;

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mobile = window.matchMedia('(max-width: 860px)').matches;

        // 1) Ghi đè span theo thiết bị — trước mount. Reduced ưu tiên cao nhất.
        root.querySelectorAll('[data-sc-act]').forEach(function (el) {
            if (reduced && el.hasAttribute('data-sc-span-reduced')) {
                el.setAttribute('data-sc-span', el.getAttribute('data-sc-span-reduced'));
            } else if (mobile && el.hasAttribute('data-sc-span-mobile')) {
                el.setAttribute('data-sc-span', el.getAttribute('data-sc-span-mobile'));
            }
        });

        // 2) + 3) Mount có cổng an toàn.
        if (window.ScrollCraft && typeof window.ScrollCraft.mount === 'function') {
            root.classList.add('sc-engine-on');
            try {
                window.ScrollCraft.mount('.sc-home');
            } catch (e) {
                root.classList.remove('sc-engine-on');
                console.warn('[scrollcraft] mount() lỗi, đã tắt hiệu ứng cuộn, trang chuyển về trạng thái tĩnh:', e);
            }
        }
        // else: window.ScrollCraft không tồn tại -> không thêm class, không mount, thoát im lặng.

        // 4) Signature move "Hướng sáng".
        if (!reduced) {
            var ticking = false;
            var lastSun = null;

            function clamp01(n) {
                return Math.max(0, Math.min(1, n));
            }

            function computeSun() {
                var max = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
                return clamp01(window.scrollY / max);
            }

            function writeSun() {
                ticking = false;
                if (document.hidden) return;
                var sun = computeSun();
                if (lastSun !== null && Math.abs(sun - lastSun) < 0.002) return;
                lastSun = sun;
                root.style.setProperty('--sc-sun', String(sun));
            }

            function onScroll() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(writeSun);
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            writeSun();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
@endpush
