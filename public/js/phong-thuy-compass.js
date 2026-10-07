/*
 * Cây hợp mệnh — la bàn ngũ hành và luồng xem mệnh (JS thuần, không thư viện).
 *
 * SVG do Blade vẽ sẵn (shop/phong-thuy/partials/compass.blade.php); file này chỉ
 * xoay các nhóm [data-ring], [data-needle] và đổi độ đậm nét. Góc đích lấy từ
 * result của API (can_index, chi_index, cycle_index, element), không tự tính mệnh.
 *
 * Nhịp (phong-thuy-la-ban-style.md mục 7), tính từ lúc có dữ liệu API:
 *   0–200 ms     mờ nét (bắt đầu ngay khi bấm)
 *   0–1600 ms    vòng A thuận ≥540° tới −chi×30°
 *   0–1350 ms    vòng B thuận ≥540° tới −cycle×6°
 *   0–1450 ms    vòng C ngược ≥300° tới −can×36°
 *   1200–2100 ms kim rung +18° −6° +2° rồi dừng
 *   1400–1700 ms vạch năm;  1700–2300 ms cung tương sinh, nút hành
 *   2200 ms      hiện kết quả, đóng dấu;  2600 ms aria-live, focus, cuộn (mobile)
 * Trong lúc chờ API, vòng quay đều; lỗi thì về nghỉ trong 600 ms.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-cpt]');
    if (!root) return;

    var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    function reduced() { return !!(motionQuery && motionQuery.matches); }
    function clamp(x) { return Math.max(0, Math.min(1, x)); }
    function now() { return performance.now(); }

    // cubic-bezier(.16,1,.3,1) — giải x(u)=t bằng Newton rồi trả y(u).
    var ease = (function (x1, y1, x2, y2) {
        function bx(u) { return ((1 - 3 * x2 + 3 * x1) * u + (3 * x2 - 6 * x1)) * u * u + 3 * x1 * u; }
        function by(u) { return ((1 - 3 * y2 + 3 * y1) * u + (3 * y2 - 6 * y1)) * u * u + 3 * y1 * u; }
        function dbx(u) { return (3 * (1 - 3 * x2 + 3 * x1) * u + 2 * (3 * x2 - 6 * x1)) * u + 3 * x1; }
        return function (t) {
            var u = t;
            for (var i = 0; i < 8; i++) {
                var d = dbx(u);
                if (Math.abs(d) < 1e-6) break;
                u = clamp(u - (bx(u) - t) / d);
            }
            return by(u);
        };
    })(0.16, 1, 0.3, 1);
    function inOutQuad(t) { return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }

    // ===== Hình học (khớp compass.blade.php) =====
    var R = 220, GAP = 12;
    function pt(r, deg) { var t = deg * Math.PI / 180; return [r * Math.sin(t), -r * Math.cos(t)]; }
    function fx(n) { return n.toFixed(2); }
    function arcD(a1, a2) {
        var A = pt(R, a1), B = pt(R, a2);
        return 'M' + fx(A[0]) + ' ' + fx(A[1]) + ' A' + R + ' ' + R + ' 0 0 1 ' + fx(B[0]) + ' ' + fx(B[1]);
    }
    function chord(p1, p2, cut) {
        var P = pt(R, p1 * 72), Q = pt(R, p2 * 72), dx = Q[0] - P[0], dy = Q[1] - P[1], L = Math.hypot(dx, dy);
        return { x1: fx(P[0] + dx / L * cut), y1: fx(P[1] + dy / L * cut), x2: fx(Q[0] - dx / L * cut), y2: fx(Q[1] - dy / L * cut) };
    }
    function near(theta, from) {
        while (theta - from > 180) theta -= 360;
        while (theta - from < -180) theta += 360;
        return theta;
    }

    // ===== Đường cong chuyển động =====
    function smooth(x) { x = clamp(x); return x * x * (3 - 2 * x); }

    // Chờ API: vận tốc đi từ v0 (đang "thở") lên vw theo smoothstep trong T ms.
    // Vị trí = tích phân đúng của vận tốc nên góc và vận tốc đều liên tục.
    function waitRing(t, v0, vw, T) {
        var dv = vw - v0, x = Math.min(t, T) / T;
        var integ = x < 1 ? T * (x * x * x - x * x * x * x / 2) : T * 0.5 + (t - T);
        return { p: v0 * t + dv * integ, v: v0 + dv * smooth(t / T) };
    }

    // Kết quả: vận tốc đầu = vận tốc đang quay (v0), tăng mượt trong RAMP ms lên nhịp
    // của cubic-bezier(.16,1,.3,1) rồi giảm tốc mềm tới đích:
    //   v(t) = (1 - r(t))·v0 + r(t)·c·E'(t),   r = smoothstep(t / RAMP)
    // c chọn để tổng quãng đường đúng bằng D. Bảng F[i] = ∫ r·E' tính sẵn một lần.
    var RAMP = 140, STEPS = 320;
    function makeRing(p0, pT, v0, T) {
        var F = [0], e0 = 0, i, e1, K;
        for (i = 0; i < STEPS; i++) {
            e1 = ease((i + 1) / STEPS);
            F.push(F[i] + smooth((i + 0.5) / STEPS * T / RAMP) * (e1 - e0));
            e0 = e1;
        }
        K = F[STEPS];
        return { p0: p0, D: pT - p0, v0: v0, T: T, F: F, c: (pT - p0 - v0 * RAMP / 2) / K };
    }
    function ringAt(R, t) {
        if (t >= R.T) return R.p0 + R.D;
        var u = clamp(t / R.T) * STEPS, i = Math.min(STEPS - 1, Math.floor(u));
        var f = R.F[i] + (R.F[i + 1] - R.F[i]) * (u - i);
        var x = clamp(t / RAMP), held = t < RAMP ? t - RAMP * (x * x * x - x * x * x * x / 2) : RAMP / 2;
        return R.p0 + R.v0 * held + R.c * f;
    }

    // ===== La bàn =====
    var BREATH_A = 360 / 240000, BREATH_C = -360 / 300000; // °/ms khi "thở"
    var WAIT_A = 0.3, WAIT_B = 0.3, WAIT_C = -0.24;         // °/ms khi chờ API
    var WAIT_RAMP = 300;

    function Compass(svg) {
        this.svg = svg;
        // Cache phần tử một lần; mỗi mục nhớ giá trị đã ghi để chỉ ghi DOM khi đổi.
        var self = this;
        function item(sel) { var el = svg.querySelector(sel); return el ? { el: el, w: {} } : null; }
        function items(sel) { return Array.prototype.map.call(svg.querySelectorAll(sel), function (el) { return { el: el, w: {} }; }); }
        this.el = {
            a: item('[data-ring=a]'), b: item('[data-ring=b]'), c: item('[data-ring=c]'), n: item('[data-needle]'),
            tick: item('[data-tick]'), tickLabel: item('[data-tick-label]'),
            hlArc: item('[data-hl-arc]'), hlStar: item('[data-hl-star]'),
            fade: items('[data-arc],[data-star]'),
            nodes: items('[data-node]').map(function (o) {
                o.p = +o.el.getAttribute('data-node');
                o.circle = { el: o.el.querySelector('circle'), w: {} };
                var h = o.el.querySelector('[data-han]');
                o.han = h ? { el: h, w: {} } : null;
                return o;
            })
        };
        this.arcLen = this.el.hlArc ? (+this.el.hlArc.el.getAttribute('stroke-dasharray') || 0) : 0;
        var hl = +svg.getAttribute('data-hl'), idx = +svg.getAttribute('data-idx');
        this.hl = hl;
        this.V = {
            a: +svg.getAttribute('data-a') || 0, b: +svg.getAttribute('data-b') || 0,
            c: +svg.getAttribute('data-c') || 0, n: +svg.getAttribute('data-n') || 180,
            dim: hl >= 0 ? 1 : 0, lit: hl >= 0 ? 1 : 0, tick: idx >= 0 ? 1 : 0
        };
        // 'rest' thở chậm; 'still' (trang theo hành) vẫn thở; 'result' đứng yên.
        this.mode = idx >= 0 ? 'result' : 'rest';
        this.anim = null;
        this.vel = { a: 0, b: 0, c: 0 }; // vận tốc (°/ms) của lần frame gần nhất khi đang chờ
        this.raf = 0;
        this.visible = true;  // la bàn đang trong khung nhìn
        this.last = now();
        this.loop = this.loop.bind(this);
        // Ngừng "thở" khi la bàn ra khỏi khung nhìn hoặc tab ẩn (anim vẫn chạy bình thường).
        if (window.IntersectionObserver) {
            new IntersectionObserver(function (entries) {
                self.visible = entries[entries.length - 1].isIntersecting;
                self.kick();
            }).observe(svg);
        }
        document.addEventListener('visibilitychange', function () { self.kick(); });
        this.kick();
    }
    Compass.prototype.breathing = function () {
        return this.mode !== 'result' && !this.anim && !reduced() && this.visible && !document.hidden;
    };
    Compass.prototype.kick = function () {
        // kick() đặt lại last nên khi quay lại không có bước nhảy góc.
        if (!this.raf && (this.anim || this.breathing())) { this.last = now(); this.raf = requestAnimationFrame(this.loop); }
    };
    Compass.prototype.loop = function (t) {
        this.raf = 0;
        this.frame(t);
        this.kick();
    };
    // Vận tốc quay hiện tại của từng vòng (°/ms), để chuyển pha không giật.
    Compass.prototype.curVel = function () {
        var A = this.anim;
        if (A && A.type === 'wait') return { a: this.vel.a, b: this.vel.b, c: this.vel.c };
        if (!A && this.mode !== 'result' && !reduced()) return { a: BREATH_A, b: 0, c: BREATH_C };
        return { a: 0, b: 0, c: 0 };
    };
    Compass.prototype.setHighlight = function (pos, label) {
        this.hl = pos;
        var p = pos >= 0 ? pos : 1, E = this.el;
        if (E.hlArc) E.hlArc.el.setAttribute('d', arcD(p * 72 - 72 + GAP, p * 72 - GAP));
        if (E.hlStar) { var s = chord((p + 3) % 5, p, 44); ['x1', 'y1', 'x2', 'y2'].forEach(function (k) { E.hlStar.el.setAttribute(k, s[k]); }); }
        if (E.tickLabel && label != null) E.tickLabel.el.textContent = label;
    };
    Compass.prototype.targets = function (r) {
        return { a: -r.chi * 30, b: -r.idx * 6, c: -r.can * 36, n: r.pos * 72 };
    };
    Compass.prototype.setFinal = function (r) {
        var t = this.targets(r);
        this.anim = null;
        this.setHighlight(r.pos, r.canChi);
        Object.assign(this.V, { a: t.a, b: t.b, c: t.c, n: near(t.n, 180), dim: 1, tick: 1, lit: 1 });
        this.mode = 'result';
        this.apply();
    };
    Compass.prototype.setRest = function () {
        this.anim = null;
        Object.assign(this.V, { a: 0, b: 0, c: 0, n: 180, dim: 0, tick: 0, lit: 0 });
        this.mode = 'rest';
        this.apply();
        this.kick();
    };
    // Bấm "Xem mệnh": tăng tốc mượt từ vận tốc "thở" lên nhịp chờ, rồi quay đều cho tới khi API trả về.
    Compass.prototype.startWaiting = function () {
        var V = this.V, v = this.curVel();
        this.mode = 'anim';
        this.vel = v;
        this.anim = { type: 'wait', t0: now(), a0: V.a, b0: V.b, c0: V.c, v0: v, dim0: V.dim, lit0: V.lit, tick0: V.tick };
        this.kick();
    };
    // Bắt đầu quay ra kết quả từ trạng thái hiện tại (đang chờ, đang nghỉ hoặc đã ở kết quả cũ).
    Compass.prototype.startResult = function (r) {
        var t = this.targets(r), V = this.V, v = this.curVel();
        function up(base, min) { return base + 360 * Math.ceil((min - base) / 360); }
        function down(base, max) { return base + 360 * Math.floor((max - base) / 360); }
        this.setHighlight(r.pos, r.canChi);
        var n0 = V.n, th = near(t.n, n0), s = Math.sign(th - n0) || 1;
        this.mode = 'anim';
        this.anim = {
            type: 'result', t0: now(), dim0: V.dim, lit0: V.lit, tick0: V.tick,
            A: makeRing(V.a, up(t.a, V.a + 540), v.a, 1600),
            B: makeRing(V.b, up(t.b, V.b + 540), v.b, 1350),
            C: makeRing(V.c, down(t.c, V.c - 300), v.c, 1450),
            // Kim giữ nguyên chỗ cho tới 1200 ms rồi rung quanh đích. Khi phát lại kết quả SSR,
            // kim đã nằm sẵn ở đích nên chỉ rung tại chỗ (không bị kéo về hướng Nam rồi quay lại).
            nk: [[0, n0], [400, th + 18 * s], [600, th - 6 * s], [750, th + 2 * s], [900, th]]
        };
        this.kick();
    };
    // Về nghỉ trong 600 ms. forward=true (lỗi): quay tiếp chiều đang quay tới vòng tròn gần nhất.
    Compass.prototype.startReset = function (forward) {
        var V = this.V;
        this.mode = 'anim';
        this.anim = {
            type: 'reset', t0: now(), a0: V.a, b0: V.b, c0: V.c, n0: V.n, dim0: V.dim, tick0: V.tick, lit0: V.lit,
            aT: forward ? Math.ceil(V.a / 360) * 360 : Math.round(V.a / 360) * 360,
            bT: forward ? Math.ceil(V.b / 360) * 360 : Math.round(V.b / 360) * 360,
            cT: forward ? Math.floor(V.c / 360) * 360 : Math.round(V.c / 360) * 360,
            nT: near(180, V.n)
        };
        this.kick();
    };
    Compass.prototype.frame = function (time) {
        var dt = Math.min(64, time - this.last), V = this.V, A = this.anim;
        this.last = time;
        if (A) {
            var t = Math.max(0, time - A.t0);
            if (A.type === 'wait') {
                var ra = waitRing(t, A.v0.a, WAIT_A, WAIT_RAMP), rb = waitRing(t, A.v0.b, WAIT_B, WAIT_RAMP),
                    rc = waitRing(t, A.v0.c, WAIT_C, WAIT_RAMP);
                V.a = A.a0 + ra.p; V.b = A.b0 + rb.p; V.c = A.c0 + rc.p;
                this.vel = { a: ra.v, b: rb.v, c: rc.v };
                var f = clamp(t / 200);
                V.dim = A.dim0 + (1 - A.dim0) * f;
                V.lit = A.lit0 * (1 - f); V.tick = A.tick0 * (1 - f);
            } else if (A.type === 'result') {
                V.a = ringAt(A.A, t);
                V.b = ringAt(A.B, t);
                V.c = ringAt(A.C, t);
                V.dim = A.dim0 + (1 - A.dim0) * clamp(t / 200);
                var nt = t - 1200, k = A.nk;
                if (nt <= 0) V.n = k[0][1];
                else if (nt >= 900) V.n = k[4][1];
                else for (var i = 1; i < k.length; i++) {
                    if (nt <= k[i][0]) {
                        var g = (nt - k[i - 1][0]) / (k[i][0] - k[i - 1][0]);
                        V.n = k[i - 1][1] + (k[i][1] - k[i - 1][1]) * inOutQuad(g);
                        break;
                    }
                }
                // Nếu đang sáng sẵn (phát lại) thì tắt mượt trong 200 ms rồi mới sáng lại đúng nhịp.
                var off = 1 - clamp(t / 200);
                V.tick = Math.max(A.tick0 * off, clamp((t - 1400) / 300));
                V.lit = Math.max(A.lit0 * off, clamp((t - 1700) / 600));
                if (t >= 2600) { this.anim = null; this.mode = 'result'; this.vel = { a: 0, b: 0, c: 0 }; }
            } else if (A.type === 'reset') {
                var e = ease(clamp(t / 600));
                V.a = A.a0 + (A.aT - A.a0) * e; V.b = A.b0 + (A.bT - A.b0) * e; V.c = A.c0 + (A.cT - A.c0) * e;
                V.n = A.n0 + (A.nT - A.n0) * e;
                V.dim = A.dim0 * (1 - e); V.tick = A.tick0 * (1 - e); V.lit = A.lit0 * (1 - e);
                if (t >= 600) { this.anim = null; this.mode = 'rest'; V.a = V.b = V.c = 0; V.n = 180; }
            }
        } else if (this.breathing()) {
            V.a += dt * BREATH_A;
            V.c += dt * BREATH_C;
        }
        this.apply();
    };
    // Ghi attribute chỉ khi chuỗi đổi so với lần ghi trước.
    function put(o, k, v) {
        if (!o) return;
        v = String(v);
        if (o.w[k] !== v) { o.w[k] = v; o.el.setAttribute(k, v); }
    }
    Compass.prototype.apply = function () {
        var V = this.V, E = this.el;
        put(E.a, 'transform', 'rotate(' + V.a.toFixed(3) + ')');
        put(E.b, 'transform', 'rotate(' + V.b.toFixed(3) + ')');
        put(E.c, 'transform', 'rotate(' + V.c.toFixed(3) + ')');
        put(E.n, 'transform', 'rotate(' + V.n.toFixed(3) + ')');
        put(E.tick, 'y2', (-388 + 16 * V.tick).toFixed(2));
        put(E.tick, 'opacity', V.tick > 0 ? 1 : 0);
        put(E.tickLabel, 'opacity', V.tick.toFixed(3));

        var own = this.hl, mother = own >= 0 ? (own + 4) % 5 : -1;
        var lit = own >= 0 ? V.lit : 0, base = 1 - 0.62 * V.dim, upv = base + (1 - base) * lit;
        E.nodes.forEach(function (g) {
            var p = g.p, isOwn = p === own && lit > 0, isMom = p === mother && lit > 0;
            put(g, 'opacity', (p === own || p === mother) ? upv.toFixed(3) : base.toFixed(3));
            put(g.circle, 'fill', isOwn ? '#1e211e' : '#f4e6cd');
            put(g.circle, 'stroke-width', isMom ? 2 : 1);
            put(g.han, 'fill', isOwn ? '#f4e6cd' : '#1e211e');
        });
        E.fade.forEach(function (o) { put(o, 'opacity', base.toFixed(3)); });
        if (E.hlArc) {
            put(E.hlArc, 'stroke-dashoffset', (this.arcLen * (1 - lit)).toFixed(2));
            put(E.hlArc, 'opacity', lit > 0 ? 1 : 0);
        }
        put(E.hlStar, 'opacity', lit.toFixed(3));
    };

    var svg = root.querySelector('[data-compass]:not([data-mini="1"])');
    var compass = svg ? new Compass(svg) : null;
    if (motionQuery && compass) {
        var onMotion = function () { compass.kick(); };
        if (motionQuery.addEventListener) motionQuery.addEventListener('change', onMotion);
        else if (motionQuery.addListener) motionQuery.addListener(onMotion);
    }

    // ===== Chữ hiển thị (bản JSON của lexicon.php) =====
    var lexEl = document.getElementById('cpt-lexicon');
    var LEX = null;
    try { LEX = lexEl ? JSON.parse(lexEl.textContent) : null; } catch (e) { LEX = null; }

    // Đổi result của API thành chuỗi hiển thị — cùng quy tắc với $present trong lexicon.php.
    function present(res) {
        if (!LEX || !res || typeof res.cycle_index !== 'number') return null;
        var idx = ((res.cycle_index % 60) + 60) % 60, nap = LEX.nap[Math.floor(idx / 2)];
        var el = LEX.elements[res.element] ? res.element : nap[1], E = LEX.elements[el];
        var rel = res.relations || {};
        var mother = rel.tuong_sinh || rel.mother, avoid = rel.ky || rel.avoid;
        if (typeof mother !== 'string' || !LEX.elements[mother]) mother = E.mother;
        if (typeof avoid !== 'string' || !LEX.elements[avoid]) avoid = E.avoid;
        var can = typeof res.can_index === 'number' ? res.can_index : idx % 10;
        var chi = typeof res.chi_index === 'number' ? res.chi_index : idx % 12;
        var canChi = res.can_chi || (LEX.can[can] + ' ' + LEX.chi[chi]);
        var ly = +res.lunar_year || 0;
        return {
            idx: idx, can: can, chi: chi, pos: LEX.order.indexOf(el), canChi: canChi, lunarYear: ly || '',
            name: res.nap_am || nap[0], han: nap[2] || '', gloss: res.gloss || nap[3],
            el: el, elName: res.element_label || E.name, elHan: E.han, advice: res.advice || E.advice,
            motherName: LEX.elements[mother].name, motherHan: LEX.elements[mother].han,
            avoidName: LEX.elements[avoid].name, avoidHan: LEX.elements[avoid].han,
            note: 'note' in res ? (res.note || '') : (res.before_tet && ly ? 'Bạn sinh trước Tết ' + (ly + 1) + ' nên tính theo năm ' + canChi + '.' : '')
        };
    }

    // ===== Trang tra cứu =====
    var form = root.querySelector('[data-cpt-form]');
    var formPanel = root.querySelector('[data-cpt-form-panel]');
    var resultPanel = root.querySelector('[data-cpt-result]');
    var list = root.querySelector('[data-cpt-list]');
    var live = root.querySelector('[data-cpt-live]');
    var arrow = root.querySelector('[data-cpt-arrow]');
    var timers = [];
    function later(ms, fn) { timers.push(setTimeout(fn, ms)); }
    function clearTimers() { timers.forEach(clearTimeout); timers = []; }
    function say(msg) { if (live) live.textContent = msg; }
    function isWide() { return window.innerWidth >= 1024; }
    function headerOffset() {
        var h = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-h'));
        return (isNaN(h) ? 76 : h) + 16;
    }

    function fillResult(r) {
        if (!resultPanel) return;
        resultPanel.querySelectorAll('[data-r]').forEach(function (node) {
            var v = r[node.getAttribute('data-r')];
            node.textContent = v == null ? '' : v;
            if (node.getAttribute('data-r') === 'han' || node.getAttribute('data-r') === 'note') node.hidden = !v;
        });
    }
    function setArrow(toList) {
        if (!arrow) return;
        arrow.setAttribute('href', toList ? '#cpt-cay' : '#cpt-theo-menh');
        arrow.setAttribute('aria-label', toList ? 'Xuống danh sách cây hợp mệnh' : 'Xuống phần xem cây theo mệnh');
    }
    function stamp() {
        var seal = resultPanel && resultPanel.querySelector('[data-cpt-seal]');
        if (!seal || reduced()) return;
        seal.classList.remove('is-stamping');
        void seal.getBoundingClientRect();
        seal.classList.add('is-stamping');
    }
    function announce(r) {
        say('Mệnh của bạn: ' + r.name + ', hành ' + r.elName + '.');
        if (!resultPanel) return;
        var wide = isWide();
        resultPanel.focus({ preventScroll: true });
        if (!wide) {
            var top = resultPanel.getBoundingClientRect().top + window.scrollY - headerOffset();
            window.scrollTo({ top: Math.max(0, top), behavior: reduced() ? 'auto' : 'smooth' });
        }
    }

    if (form && compass) {
        var inputs = { d: form.querySelector('[name=ngay]'), m: form.querySelector('[name=thang]'), y: form.querySelector('[name=nam]') };
        var hint = form.querySelector('[data-cpt-hint]');
        var errBox = form.querySelector('[data-cpt-error]');
        var btn = form.querySelector('[data-cpt-submit]');
        var maxYear = +root.getAttribute('data-max-year') || new Date().getFullYear();
        var api = root.getAttribute('data-api');
        var HINT = 'Nếu bạn sinh tháng 1 hoặc đầu tháng 2, nhập đủ ngày để kết quả chính xác.';
        var NET = 'Chưa xem được mệnh. Kiểm tra kết nối rồi bấm Xem mệnh lần nữa.';
        var busy = false;

        var showError = function (msg, kind) {
            errBox.innerHTML = '';
            var dateBad = kind === 'date', yearBad = kind === 'year';
            inputs.d.setAttribute('aria-invalid', dateBad ? 'true' : 'false');
            inputs.m.setAttribute('aria-invalid', dateBad ? 'true' : 'false');
            inputs.y.setAttribute('aria-invalid', yearBad ? 'true' : 'false');
            if (!msg) return;
            var p = document.createElement('p'), x = document.createElement('span'), t = document.createElement('span');
            p.className = 'cpt-error'; p.setAttribute('role', 'alert');
            x.className = 'cpt-error__x'; x.setAttribute('aria-hidden', 'true'); x.textContent = '✕';
            t.textContent = msg;
            p.appendChild(x); p.appendChild(t);
            errBox.appendChild(p);
        };
        var syncHint = function () { hint.textContent = (!inputs.d.value || !inputs.m.value) ? HINT : ''; };
        var setBusy = function (on) {
            busy = on;
            btn.textContent = on ? 'Đang xoay la bàn…' : 'Xem mệnh';
            btn.setAttribute('aria-disabled', on ? 'true' : 'false');
            btn.classList.toggle('is-on', on);
        };

        [['d', 2], ['m', 2], ['y', 4]].forEach(function (pair) {
            var input = inputs[pair[0]];
            input.addEventListener('input', function () {
                var clean = input.value.replace(/\D/g, '').slice(0, pair[1]);
                if (clean !== input.value) input.value = clean;
                showError(null, null);
                syncHint();
            });
        });

        var validate = function (d, m, y) {
            var Y = +y;
            var yearMsg = 'Năm sinh cần đủ 4 chữ số, từ 1920 đến ' + maxYear + '.';
            if (!/^\d{4}$/.test(y) || Y < 1920 || Y > maxYear) return { msg: yearMsg, kind: 'year' };
            if (!d && !m) return null;
            if (!d || !m) return { msg: 'Nhập cả ngày và tháng, hoặc để trống cả hai.', kind: 'date' };
            var D = +d, M = +m;
            if (!(M >= 1 && M <= 12)) return { msg: 'Không có tháng ' + m + '. Kiểm tra lại ngày và tháng.', kind: 'date' };
            var dt = new Date(Y, M - 1, D);
            if (!(D >= 1) || dt.getDate() !== D || dt.getMonth() !== M - 1) return { msg: 'Ngày ' + D + ' tháng ' + M + ' không có thật. Kiểm tra lại ngày và tháng.', kind: 'date' };
            if (dt > new Date()) return { msg: 'Ngày sinh không thể ở tương lai. Kiểm tra lại ngày, tháng và năm.', kind: 'date' };
            return null;
        };

        // Lỗi (mạng, 422, 419...): la bàn về nghỉ trong 600 ms rồi hiện lời nhắn.
        var fail = function (msg, kind) {
            clearTimers();
            if (reduced()) compass.setRest(); else compass.startReset(true);
            later(reduced() ? 0 : 600, function () {
                setBusy(false);
                showError(msg, kind);
                say(msg);
            });
        };

        var reveal = function (r, html) {
            fillResult(r);
            if (formPanel) formPanel.hidden = true;
            resultPanel.hidden = false;
            root.removeAttribute('data-replay');
            if (list && html != null) list.innerHTML = html;
            setArrow(true);
            stamp();
        };

        var finish = function (r, html) {
            setBusy(false);
            if (reduced()) {
                compass.setFinal(r);
                reveal(r, html);
                announce(r);
                return;
            }
            compass.startResult(r);
            later(2200, function () { reveal(r, html); });
            later(2600, function () { announce(r); });
        };

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;
            var d = inputs.d.value.trim(), m = inputs.m.value.trim(), y = inputs.y.value.trim();
            var bad = validate(d, m, y);
            if (bad) { showError(bad.msg, bad.kind); say(bad.msg); return; }
            showError(null, null);
            clearTimers();
            setBusy(true);
            say('Đang xoay la bàn…');
            if (!reduced()) compass.startWaiting();

            var body = new FormData(form);
            var token = document.querySelector('meta[name="csrf-token"]');
            // API treo quá 15 s thì coi như lỗi mạng để khách gửi lại được.
            var ctrl = window.AbortController ? new AbortController() : null;
            if (ctrl) later(15000, function () { ctrl.abort(); });
            fetch(api, {
                method: 'POST',
                signal: ctrl ? ctrl.signal : undefined,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '' },
                body: body,
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) { return { status: res.status, ok: res.ok, data: data }; });
            }).then(function (out) {
                if (out.ok) {
                    var r = present(out.data && out.data.result);
                    if (!r || r.pos < 0) return fail(NET, 'net');
                    return finish(r, out.data.recommendations_html || '');
                }
                if (out.status === 422) {
                    var errs = (out.data && out.data.errors) || {};
                    if (errs.nam) return fail(errs.nam[0], 'year');
                    var dm = errs.ngay || errs.thang;
                    if (dm) return fail(dm[0], 'date');
                    var first = Object.keys(errs)[0];
                    return fail(first ? errs[first][0] : (out.data.message || NET), 'date');
                }
                if (out.status === 419) return fail('Trang đã mở quá lâu. Tải lại trang rồi bấm Xem mệnh lần nữa.', 'net');
                if (out.status === 429) return fail('Bạn xem hơi nhiều lần liền. Đợi một phút rồi thử lại.', 'net');
                return fail(NET, 'net');
            }).catch(function () { fail(NET, 'net'); });
        });

        // "Xem với ngày sinh khác": la bàn về nghỉ, câu nhập liệu hiện lại, con trỏ ở ô ngày.
        root.addEventListener('click', function (e) {
            var again = e.target.closest('[data-cpt-again]');
            if (!again) return;
            e.preventDefault();
            clearTimers();
            setBusy(false);
            if (reduced()) compass.setRest(); else compass.startReset(false);
            resultPanel.hidden = true;
            if (formPanel) formPanel.hidden = false;
            if (list) list.innerHTML = '';
            root.removeAttribute('data-replay');
            setArrow(false);
            say('');
            syncHint();
            inputs.d.focus();
        });

        // Kết quả SSR (vd. gửi từ khối trang chủ): phát lại chuỗi la bàn từ dữ liệu server đã tính.
        var readingEl = document.getElementById('cpt-reading');
        if (readingEl && root.hasAttribute('data-replay')) {
            var seed = null;
            try { seed = JSON.parse(readingEl.textContent); } catch (err) { seed = null; }
            var rr = seed && resultPanel ? {
                idx: seed.idx, can: seed.can, chi: seed.chi, pos: LEX ? LEX.order.indexOf(seed.el) : -1,
                canChi: seed.canChi, name: seed.name, elName: seed.elName
            } : null;
            if (!rr || rr.pos < 0 || reduced()) {
                root.removeAttribute('data-replay');
            } else {
                // SVG đã được Blade vẽ ở trạng thái cuối: không bật về nghỉ, quay tiếp từ góc hiện tại
                // tới cùng đích (startResult cộng ≥540°); đèn sáng tắt mượt rồi sáng lại đúng nhịp.
                compass.startResult(rr);
                later(2200, function () { root.removeAttribute('data-replay'); stamp(); });
                later(2600, function () { announce(rr); });
            }
        }
    }

    // ===== Thêm vào giỏ tại chỗ (form POST cart.add; tắt JS vẫn chạy) =====
    root.addEventListener('submit', function (e) {
        var f = e.target.closest('[data-cpt-add]');
        if (!f) return;
        e.preventDefault();
        var b = f.querySelector('button');
        if (!b || b.disabled) return;
        var label = b.textContent;
        b.disabled = true;
        b.textContent = 'Đang thêm…';
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch(f.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: new FormData(f),
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok, status: res.status, data: data }; });
        }).then(function (out) {
            if (out.status === 401) { window.location.href = '/login'; return; }
            if (!out.ok) {
                b.disabled = false; b.textContent = label;
                if (window.showToast) window.showToast(out.data.message || 'Không thể thêm vào giỏ hàng.', true);
                return;
            }
            if (out.data.cart_count != null) {
                document.querySelectorAll('.cart-count-badge').forEach(function (el) { el.textContent = out.data.cart_count; });
            }
            b.textContent = 'Đã thêm vào giỏ';
            var name = (b.getAttribute('aria-label') || '').replace(/^Thêm /, '').replace(/ vào giỏ$/, '');
            if (name) b.setAttribute('aria-label', 'Đã thêm ' + name + ' vào giỏ');
            if (window.showToast && out.data.message) window.showToast(out.data.message);
        }).catch(function () {
            b.disabled = false; b.textContent = label;
            if (window.showToast) window.showToast('Không thể thêm vào giỏ hàng. Vui lòng thử lại.', true);
        });
    });

    // ===== Nhận tin khi ít cây =====
    root.addEventListener('submit', function (e) {
        var f = e.target.closest('[data-cpt-waitlist]');
        if (!f) return;
        e.preventDefault();
        var input = f.querySelector('[name=email]'), b = f.querySelector('button[type=submit]'), msg = f.querySelector('[role=status]');
        if (!input || !b || b.disabled) return;
        var bad = function (text) { input.setAttribute('aria-invalid', 'true'); msg.textContent = text; b.disabled = false; };
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim())) return bad('Email chưa đúng. Kiểm tra lại giúp chúng tôi.');
        b.disabled = true;
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch(f.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '' },
            body: new FormData(f),
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok, status: res.status, data: data }; });
        }).then(function (out) {
            if (out.ok) {
                input.setAttribute('aria-invalid', 'false');
                input.readOnly = true;
                b.textContent = 'Đã nhận';
                b.classList.add('is-on');
                msg.textContent = (out.data && out.data.message) || f.getAttribute('data-done');
                return;
            }
            if (out.status === 422) {
                var errs = (out.data && out.data.errors) || {};
                return bad((errs.email && errs.email[0]) || out.data.message || 'Email chưa đúng. Kiểm tra lại giúp chúng tôi.');
            }
            if (out.status === 429) return bad('Bạn gửi hơi nhiều lần. Thử lại sau ít phút.');
            bad('Chưa gửi được. Kiểm tra kết nối rồi thử lại.');
        }).catch(function () { bad('Chưa gửi được. Kiểm tra kết nối rồi thử lại.'); });
    });
})();
