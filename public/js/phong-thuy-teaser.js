/*
 * Khối Cây hợp mệnh ở trang chủ: kiểm tra năm sinh trước khi POST tới trang tra cứu.
 * Tắt JS thì server validate và render trang /cay-phong-thuy kèm lỗi (422).
 */
(function () {
    'use strict';
    var form = document.querySelector('[data-ktc2-form]');
    if (!form) return;
    var input = form.querySelector('[name=nam]');
    var box = form.querySelector('[data-ktc2-error]');
    var maxYear = +form.getAttribute('data-max-year') || new Date().getFullYear();

    function show(msg) {
        box.innerHTML = '';
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        if (!msg) return;
        var p = document.createElement('p'), x = document.createElement('span'), t = document.createElement('span');
        p.className = 'ktc2__error'; p.setAttribute('role', 'alert');
        x.setAttribute('aria-hidden', 'true'); x.textContent = '✕';
        t.textContent = msg;
        p.appendChild(x); p.appendChild(t);
        box.appendChild(p);
    }

    input.addEventListener('input', function () {
        var clean = input.value.replace(/\D/g, '').slice(0, 4);
        if (clean !== input.value) input.value = clean;
        show(null);
    });

    form.addEventListener('submit', function (e) {
        var y = input.value.trim(), Y = +y;
        if (!/^\d{4}$/.test(y) || Y < 1920 || Y > maxYear) {
            e.preventDefault();
            show('Năm sinh cần đủ 4 chữ số, từ 1920 đến ' + maxYear + '.');
            input.focus();
        }
    });
})();
