{{--
    Gợi ý hành phong thủy theo tên cây trong form thêm/sửa sản phẩm.
    Đặt bên trong #elementsBlock, sau các ô chọn elements[].

    Cùng bộ từ khóa và quy tắc khớp với PhongThuyService::suggestElements
    (config/phong_thuy.php → element_keywords). Chỉ tự tick khi lúc mở form
    chưa có hành nào được chọn và admin chưa tự bấm ô hành nào — không ghi đè
    lựa chọn tay. Admin vẫn phải bấm Lưu.
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
    var hint = block.querySelector('[data-element-hint]');
    var auto = !boxes.some(function (b) { return b.checked; });

    function norm(s) { return (s || '').normalize('NFC').toLowerCase().replace(/\s+/g, ' ').trim(); }
    function esc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function suggest(name) {
        var text = norm(name), found = [];
        Object.keys(LABELS).forEach(function (code) {
            var hit = (KEYWORDS[code] || []).some(function (kw) {
                return new RegExp('(?<!\\p{L})' + esc(norm(kw)) + '(?!\\p{L})', 'u').test(text);
            });
            if (hit) found.push(code);
        });
        return found;
    }

    function apply() {
        if (!auto) return;
        var codes = suggest(nameInput.value);
        boxes.forEach(function (b) { b.checked = codes.indexOf(b.value) !== -1; });
        if (!norm(nameInput.value)) { hint.hidden = true; return; }
        hint.hidden = false;
        hint.textContent = codes.length
            ? 'Đã tự chọn theo tên cây: ' + codes.map(function (c) { return LABELS[c]; }).join(', ') + '. Kiểm tra lại trước khi lưu.'
            : 'Chưa có gợi ý cho tên này — chọn hành bằng tay hoặc để trống.';
    }

    boxes.forEach(function (b) {
        b.addEventListener('change', function () { auto = false; hint.hidden = true; });
    });
    nameInput.addEventListener('input', apply);
    apply();
})();
</script>
