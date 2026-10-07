{{--
    Gợi ý hành phong thủy trong form thêm/sửa sản phẩm.
    Đặt bên trong #elementsBlock, sau các ô chọn elements[] và màu chủ đạo
    (products.partials.element-colors).

    Cùng quy tắc với PhongThuyService::suggestForProduct:
      - Admin bấm chọn MÀU -> hành được tick lại theo màu (config phong_thuy.colors).
        Đây là thao tác chủ động nên được phép ghi đè ô hành đang tick.
      - Chưa chọn màu nào -> đoán theo TÊN (element_keywords, cụm dài thắng
        cụm ngắn), nhưng chỉ khi lúc mở form chưa có hành nào được chọn và
        admin chưa tự bấm ô hành nào — không ghi đè lựa chọn tay.
    Admin vẫn phải bấm Lưu.
--}}
<p class="text-xs mt-2 text-amber-800" data-element-hint hidden></p>
<script>
(function () {
    var block = document.getElementById('elementsBlock');
    var nameInput = document.querySelector('input[name="name"]');
    if (!block || !nameInput) return;

    var KEYWORDS = @json(config('phong_thuy.element_keywords', []));
    var LABELS = @json(\App\Models\Product::ELEMENTS);
    var boxes = Array.prototype.slice.call(block.querySelectorAll('input[name="elements[]"]'));
    var colorBoxes = Array.prototype.slice.call(block.querySelectorAll('input[name="feng_shui_colors[]"]'));
    var hint = block.querySelector('[data-element-hint]');
    var auto = !boxes.some(function (b) { return b.checked; });

    function norm(s) { return (s || '').normalize('NFC').toLowerCase().replace(/\s+/g, ' ').trim(); }
    function esc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    // Cụm dài thắng cụm ngắn lồng trong nó ("cau vàng" che "cau") — giống
    // PhongThuyService::suggestElements.
    function suggest(name) {
        var text = norm(name), matches = [], taken = [], found = {};
        Object.keys(KEYWORDS).forEach(function (code) {
            (KEYWORDS[code] || []).forEach(function (kw) {
                var re = new RegExp('(?<!\\p{L})' + esc(norm(kw)) + '(?!\\p{L})', 'gu'), m;
                while ((m = re.exec(text))) matches.push({ code: code, start: m.index, end: m.index + m[0].length });
            });
        });
        matches.sort(function (a, b) { return (b.end - b.start) - (a.end - a.start) || a.start - b.start; });
        matches.forEach(function (m) {
            var overlaps = taken.some(function (t) { return m.start < t[1] && t[0] < m.end; });
            if (overlaps) return;
            taken.push([m.start, m.end]);
            found[m.code] = true;
        });
        return Object.keys(LABELS).filter(function (code) { return found[code]; });
    }

    function fromColors() {
        var picked = {};
        colorBoxes.forEach(function (c) { if (c.checked) picked[c.dataset.element] = true; });
        return Object.keys(LABELS).filter(function (code) { return picked[code]; });
    }

    function tick(codes) {
        boxes.forEach(function (b) { b.checked = codes.indexOf(b.value) !== -1; });
    }

    function labels(codes) {
        return codes.map(function (c) { return LABELS[c]; }).join(', ');
    }

    function apply() {
        if (!auto || fromColors().length) return;
        var codes = suggest(nameInput.value);
        tick(codes);
        if (!norm(nameInput.value)) { hint.hidden = true; return; }
        hint.hidden = false;
        hint.textContent = codes.length
            ? 'Đã tự chọn theo tên cây: ' + labels(codes) + '. Kiểm tra lại trước khi lưu.'
            : 'Chưa có gợi ý cho tên này — chọn màu chủ đạo, chọn hành bằng tay hoặc để trống.';
    }

    function applyColors() {
        var codes = fromColors();
        if (!codes.length) { apply(); return; }
        tick(codes);
        hint.hidden = false;
        hint.textContent = 'Đã tự chọn theo màu chủ đạo: ' + labels(codes) + '. Có thể chỉnh tay trước khi lưu.';
    }

    boxes.forEach(function (b) {
        b.addEventListener('change', function () { auto = false; hint.hidden = true; });
    });
    colorBoxes.forEach(function (c) { c.addEventListener('change', applyColors); });
    nameInput.addEventListener('input', apply);
    apply();
})();
</script>
