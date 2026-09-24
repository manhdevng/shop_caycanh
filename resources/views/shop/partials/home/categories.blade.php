@if($plantGroups->isNotEmpty() || $flowerGroups->isNotEmpty())
@include('shop.partials.category-arc')

<script>
// ==== Nút "Xem thêm danh mục" -> mở mega-menu trên header thay vì chuyển trang ====
(function () {
    document.querySelectorAll('[data-open-mega]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Không có API mega-menu (VD: layout khác) -> để thẻ <a> điều hướng bình thường
            if (typeof window.openNavMega !== 'function') return;

            e.preventDefault();
            const scope = link.dataset.openMega;
            const targetHref = link.href;

            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Chờ cuộn lên đầu trang xong (header chuyển sang trong suốt) rồi mới mở panel
            setTimeout(function () {
                const opened = window.openNavMega(scope);
                if (!opened) {
                    window.location.href = targetHref;
                }
            }, 450);
        });
    });
})();
</script>
@endif
