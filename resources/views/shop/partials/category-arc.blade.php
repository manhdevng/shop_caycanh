{{--
    Danh mục dạng "Arc Flow Carousel" (21st.dev, Hyperiux) — viết lại bằng JS thuần, không React / GSAP.

    - Các thẻ danh mục xếp trên một vòng cung lớn, kéo (chuột / cảm ứng), vuốt trackpad ngang, phím ← →,
      hoặc bấm nút ‹ › để xoay. Thả tay có quán tính. Tự trôi chậm, dừng khi rê chuột vào.
    - Thẻ gần thẻ đang được kéo đi trước, thẻ xa đi theo trễ hơn -> chuyển động "gợn" ra hai bên.
    - Bấm vào thẻ -> mở danh mục (kéo thì không tính là bấm).
    - Dữ liệu: nhóm cây cảnh ($plantGroups) + nhóm hoa ($flowerGroups), lấy từ shop/index.blade.php.
      Mỗi thẻ là một NHÓM (mục to); bấm thẻ = lọc theo toàn bộ danh mục con của nhóm.
    - Chỉnh thông số trong object O ở đầu script.
--}}
@php
    $catArcItems = collect();
    foreach ($plantGroups as $group) {
        $catArcItems->push([
            'href'  => route('shop.index', ['categories' => $group->children->pluck('id')->all()]),
            'src'   => $group->image ? asset('storage/' . $group->image) : null,
            'title' => $group->name,
            'desc'  => $group->children->sum('products_count') . ' sản phẩm',
            'tag'   => 'Cây cảnh',
        ]);
    }
    foreach ($flowerGroups as $group) {
        $catArcItems->push([
            'href'  => route('shop.index', ['categories' => $group->children->pluck('id')->all()]),
            'src'   => $group->image ? asset('storage/' . $group->image) : null,
            'title' => $group->name,
            'desc'  => $group->children->sum('products_count') . ' sản phẩm',
            'tag'   => 'Hoa',
        ]);
    }
@endphp

<section id="catArcSection" class="cat-arc-section" data-sc-act="flow" aria-labelledby="catArcTitle">
    <img class="cat-arc-bg" src="{{ asset('images/bg-la-monstera.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
    <div class="cat-arc-bg-veil" aria-hidden="true"></div>

    <div class="cat-arc-head">
        <p class="cat-arc-kicker">Khám phá theo nhu cầu</p>
        <h2 id="catArcTitle" class="cat-arc-title">Danh mục cây cảnh &amp; hoa</h2>
        <div class="cat-arc-nav">
            <button type="button" id="catArcPrev" class="cat-arc-btn" aria-label="Danh mục trước">‹</button>
            <span class="cat-arc-hint">Kéo để xem thêm</span>
            <button type="button" id="catArcNext" class="cat-arc-btn" aria-label="Danh mục tiếp theo">›</button>
        </div>
    </div>

    <div id="catArcStage" class="cat-arc-stage" tabindex="0" role="region" aria-label="Danh mục, kéo ngang để xem"></div>

    <div class="cat-arc-foot">
        <a href="{{ route('shop.index', ['type' => 'plant']) }}" data-open-mega="plant" class="cat-arc-more">Xem thêm cây cảnh</a>
        @if($flowerGroups->isNotEmpty())
            <a href="{{ route('shop.index', ['type' => 'flower']) }}" data-open-mega="flower" class="cat-arc-more">Xem thêm hoa</a>
        @endif
    </div>

    {{-- Không có JS: vẫn hiện danh sách link bình thường --}}
    <noscript>
        <ul class="cat-arc-fallback">
            @foreach($catArcItems as $it)
                <li><a href="{{ $it['href'] }}">{{ $it['title'] }} — {{ $it['desc'] }}</a></li>
            @endforeach
        </ul>
    </noscript>

    <script type="application/json" id="catArcData">@json($catArcItems->values())</script>
</section>

<style>
    .cat-arc-section{position:relative;width:100%;height:clamp(600px,92vh,860px);background:#0A100B;overflow:hidden;user-select:none;-webkit-user-select:none}
    .cat-arc-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;pointer-events:none;display:block}
    .cat-arc-bg-veil{position:absolute;inset:0;z-index:0;pointer-events:none;background:linear-gradient(180deg,rgba(10,16,11,.88) 0%,rgba(10,16,11,.62) 45%,rgba(10,16,11,.86) 100%)}
    .cat-arc-head{position:absolute;top:clamp(36px,6vh,64px);left:0;right:0;z-index:5;text-align:center;padding:0 24px;pointer-events:none}
    .cat-arc-kicker{font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 10px}
    .cat-arc-title{font-family:'Anton',sans-serif;font-size:clamp(26px,3.6vw,44px);letter-spacing:.01em;text-transform:uppercase;color:#FFFFFF;margin:0}
    .cat-arc-nav{display:inline-flex;align-items:center;gap:16px;margin-top:18px;pointer-events:auto}
    .cat-arc-hint{font-family:'Space Mono',monospace;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.45)}
    .cat-arc-btn{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.3);background:transparent;color:#FFFFFF;font-size:18px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .25s ease,border-color .25s ease,color .25s ease}
    .cat-arc-btn:hover{background:#FFFFFF;color:#1C1C1A;border-color:#FFFFFF}
    .cat-arc-stage{position:absolute;inset:0;z-index:1;cursor:grab;outline:none;touch-action:pan-y}
    .cat-arc-stage.is-dragging{cursor:grabbing}
    /* Stage có tabindex=0 và lái được bằng phím ← → nên nó là control bàn phím
       thật, bắt buộc phải có chỉ báo focus nhìn thấy được. `box-shadow: inset`
       không dùng được ở đây: các thẻ .cat-arc-card nằm trong stage, z-index cao
       hơn và phủ kín khung nên chúng che mất viền. Dùng một lớp ::after phủ lên
       trên, pointer-events:none để không chặn thao tác kéo. Màu trắng đặc trên
       nền #1C1C1A cho tương phản ~17:1, vượt xa mức 3:1 mà taste.md yêu cầu cho
       chỉ báo focus. Chỉ hiện với :focus-visible nên bấm chuột để kéo không kích
       hoạt. */
    .cat-arc-stage:focus-visible::after{content:'';position:absolute;inset:3px;border:2px solid #FFFFFF;border-radius:6px;pointer-events:none;z-index:50}
    .cat-arc-card{position:absolute;top:0;left:0;will-change:transform;visibility:hidden}
    .cat-arc-inner{position:relative;display:block;width:100%;height:100%;overflow:hidden;border-radius:10px;background:#2A2A27;opacity:0;box-shadow:0 18px 40px -12px rgba(0,0,0,.55),0 2px 6px rgba(0,0,0,.2);-webkit-user-drag:none;color:#FFFFFF;text-decoration:none}
    .cat-arc-inner img{display:block;width:100%;height:100%;object-fit:cover;pointer-events:none;-webkit-user-drag:none;transition:transform .6s ease}
    .cat-arc-card:hover .cat-arc-inner img{transform:scale(1.05)}
    .cat-arc-ph{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;text-align:center;font-family:'Space Mono',monospace;font-size:11px;text-transform:uppercase;color:rgba(255,255,255,.5);background:repeating-linear-gradient(135deg,rgba(28,28,26,.72),rgba(28,28,26,.72) 10px,rgba(50,50,46,.72) 10px,rgba(50,50,46,.72) 20px)}
    .cat-arc-shade{position:absolute;inset:0;pointer-events:none;background:linear-gradient(180deg,rgba(0,0,0,0) 45%,rgba(0,0,0,.35) 70%,rgba(0,0,0,.72) 100%);transition:opacity .3s ease}
    .cat-arc-text{position:absolute;left:0;right:0;bottom:0;padding:16px;pointer-events:none}
    .cat-arc-tag{display:inline-block;font-family:'Space Mono',monospace;font-size:9px;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.75);border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:3px 8px;margin-bottom:8px}
    .cat-arc-name{font-family:'Anton',sans-serif;font-size:clamp(16px,1.5vw,22px);line-height:1.15;text-transform:uppercase;letter-spacing:.01em;margin:0}
    .cat-arc-desc{font-family:'Space Mono',monospace;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.7);margin:6px 0 0;max-height:0;opacity:0;overflow:hidden;transition:max-height .3s ease,opacity .3s ease}
    .cat-arc-card:hover .cat-arc-desc,.cat-arc-inner:focus-visible .cat-arc-desc{max-height:20px;opacity:1}
    .cat-arc-foot{position:absolute;left:0;right:0;bottom:clamp(24px,4vh,40px);z-index:5;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;padding:0 24px;pointer-events:none}
    .cat-arc-foot a{pointer-events:auto}
    .cat-arc-more{display:inline-block;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:.06em;text-transform:uppercase;border:1px solid rgba(255,255,255,.7);color:#FFFFFF;padding:12px 26px;border-radius:999px;background:transparent;text-decoration:none;transition:background .25s ease,color .25s ease}
    .cat-arc-more:hover{background:#FFFFFF;color:#1C1C1A}
    .cat-arc-fallback{position:relative;z-index:6;list-style:none;margin:180px auto 0;padding:0 24px;max-width:600px;color:#FFFFFF}
    .cat-arc-fallback a{color:#FFFFFF}
    @media (max-width:640px){.cat-arc-section{height:620px}.cat-arc-bg{object-position:50% 50%}.cat-arc-name{font-size:17px}.cat-arc-text{padding:12px}.cat-arc-more{padding:10px 20px;font-size:11px}}
    @media (prefers-reduced-motion: reduce){.cat-arc-inner img,.cat-arc-desc{transition:none}}
</style>

<script>
(function () {
    const section = document.getElementById('catArcSection');
    const stage   = document.getElementById('catArcStage');
    const dataEl  = document.getElementById('catArcData');
    if (!section || !stage || !dataEl) return;

    let items = [];
    try { items = JSON.parse(dataEl.textContent) || []; } catch (e) { items = []; }
    const total = items.length;
    if (!total) { section.style.display = 'none'; return; }

    // ===== Thông số (tương ứng props của ArcFlowCarousel) =====
    const O = {
        radiusRatio: 0.85,      // bán kính vòng cung / chiều rộng khung. Lớn hơn = cung phẳng hơn
        cardRatio: 0.21,        // bề rộng thẻ / chiều rộng khung
        minCardWidth: 150,
        maxCardWidth: 300,
        cardAspect: 0.66,       // rộng / cao của thẻ
        overlap: -0.04,         // 0 = sát nhau, âm = có khe, dương = chồng lên nhau
        arcOffset: 0.55,        // tâm thẻ giữa nằm ở bao nhiêu % chiều cao khung
        smoothing: 5.5,         // tốc độ bắt kịp
        dragSensitivity: 1.2,
        momentum: 1,            // độ văng khi thả tay
        snap: false,            // true = luôn dừng đúng giữa một thẻ
        wheel: 'horizontal',    // 'horizontal' | 'both' | 'off' — mặc định không chặn cuộn dọc trang
        autoRotateSpeed: 0.12,  // tự trôi (rad/s), 0 = tắt
        pauseOnHover: true,
    };
    const DRAG_SMOOTHING = 14, VELOCITY_WINDOW = 90, MAX_FLICK = 9;
    const STAGGER_LAG = 0.85, MIN_FOLLOW = 0.6, DRAG_THRESHOLD = 6;

    const clamp = (min, max, v) => Math.min(max, Math.max(min, v));
    const snapTo = (step, v) => Math.round(v / step) * step;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let L = { radius: 900, cardWidth: 220, cardHeight: 330, step: 0.14, centerX: 0, centerY: 0, maxAngle: 1 };
    let slotCount = 0, cards = [], inners = [], offsets = [];
    let current = 0, target = 0;
    let reveal = reduceMotion ? 1 : 0, revealStart = 0;
    let hovered = false, visible = false, rafId = 0, lastTime = 0, wheelTimer = 0;

    // ----- Tạo thẻ -----
    function makeCard(item) {
        const card = document.createElement('div');
        card.className = 'cat-arc-card';
        const a = document.createElement('a');
        a.className = 'cat-arc-inner';
        a.href = item.href;
        a.draggable = false;
        a.setAttribute('aria-label', item.title + ' — ' + item.desc);
        if (item.src) {
            const img = document.createElement('img');
            img.src = item.src; img.alt = item.title; img.draggable = false;
            img.loading = 'lazy'; img.decoding = 'async';
            a.appendChild(img);
        } else {
            const ph = document.createElement('div');
            ph.className = 'cat-arc-ph'; ph.textContent = item.title;
            a.appendChild(ph);
        }
        const shade = document.createElement('div'); shade.className = 'cat-arc-shade';
        const text = document.createElement('div'); text.className = 'cat-arc-text';
        if (item.tag) { const t = document.createElement('span'); t.className = 'cat-arc-tag'; t.textContent = item.tag; text.appendChild(t); }
        const n = document.createElement('p'); n.className = 'cat-arc-name'; n.textContent = item.title;
        const d = document.createElement('p'); d.className = 'cat-arc-desc'; d.textContent = item.desc;
        text.appendChild(n); text.appendChild(d);
        a.appendChild(shade); a.appendChild(text);
        card.appendChild(a);
        return { card, inner: a };
    }

    function buildSlots(n) {
        stage.textContent = '';
        cards = []; inners = [];
        const frag = document.createDocumentFragment();
        for (let i = 0; i < n; i++) {
            const { card, inner } = makeCard(items[i % total]);
            // Chỉ bộ thẻ đầu tiên nhận Tab, các bản lặp để trang trí -> không đọc lặp
            if (i >= total) { inner.tabIndex = -1; card.setAttribute('aria-hidden', 'true'); }
            if (reveal >= 1) inner.style.opacity = '1';
            cards.push(card); inners.push(inner); frag.appendChild(card);
        }
        stage.appendChild(frag);
        slotCount = n;
        offsets = new Array(n).fill(current);
    }

    // ----- Đo kích thước -----
    function measure() {
        const w = stage.offsetWidth, h = stage.offsetHeight;
        if (!w || !h) return;
        // Điện thoại: thẻ to hơn (~một nửa màn hình) cho dễ đọc
        const cardWidth = w < 640 ? clamp(160, 230, w * 0.5) : clamp(O.minCardWidth, O.maxCardWidth, w * O.cardRatio);
        const cardHeight = cardWidth / O.cardAspect;
        const radius = Math.max(w * O.radiusRatio, cardWidth * 4.2);
        const step = (cardWidth * (1 - clamp(-0.5, 0.85, O.overlap))) / radius;
        const centerX = w / 2;
        const centerY = h * O.arcOffset + radius;
        const reach = Math.min(1, (w / 2 + cardWidth * 1.2) / radius);
        const maxAngle = Math.asin(reach) + 0.12;
        L = { radius, cardWidth, cardHeight, step, centerX, centerY, maxAngle };

        const needed = Math.ceil((maxAngle * 2) / step) + 2;
        const next = Math.max(total, Math.ceil(needed / total) * total);
        if (next !== slotCount) buildSlots(next);
        draw(1);
    }

    // ----- Vẽ -----
    function draw(dt) {
        const { radius, cardWidth, cardHeight, step, centerX, centerY, maxAngle } = L;
        const span = slotCount * step, half = span / 2;
        const rate = dragging || reduceMotion ? DRAG_SMOOTHING : O.smoothing;

        for (let i = 0; i < slotCount; i++) {
            const card = cards[i];
            if (reduceMotion) {
                offsets[i] = current;
            } else {
                // Thẻ gần thẻ đang active bắt kịp nhanh, thẻ xa trễ hơn -> gợn ra hai bên
                let rank = (i * step - offsets[i]) % span;
                if (rank < -half) rank += span; else if (rank >= half) rank -= span;
                const far = clamp(0, 1, Math.abs(rank) / maxAngle);
                const follow = Math.max(rate * (1 - far * STAGGER_LAG), rate * MIN_FOLLOW);
                offsets[i] += (current - offsets[i]) * (1 - Math.exp(-follow * dt));
            }

            let angle = (i * step - offsets[i]) % span;
            if (angle < -half) angle += span; else if (angle >= half) angle -= span;

            if (Math.abs(angle) > maxAngle) {
                if (card.style.visibility !== 'hidden') card.style.visibility = 'hidden';
                continue;
            }
            if (card.style.visibility !== 'visible') card.style.visibility = 'visible';

            const x = centerX + radius * Math.sin(angle) - cardWidth / 2;
            const y = centerY - radius * Math.cos(angle) - cardHeight / 2;
            card.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0) rotate(' + angle + 'rad)';
            card.style.width = cardWidth + 'px';
            card.style.height = cardHeight + 'px';
            card.style.zIndex = String(Math.round((angle + half) * 1000));

            const inner = inners[i];
            if (reveal < 1) {
                // Hiện dần từ giữa ra hai bên
                const delay = Math.min(1, Math.abs(angle) / maxAngle) * 0.45;
                const p = clamp(0, 1, (reveal - delay) / (1 - delay || 1));
                const e = 1 - Math.pow(1 - p, 3);
                inner.style.opacity = String(e);
                inner.style.transform = 'translate3d(0,' + (1 - e) * cardHeight * 0.35 + 'px,0)';
            } else if (inner.style.opacity !== '1') {
                inner.style.opacity = '1';
                inner.style.transform = 'translate3d(0,0,0)';
            }
        }
    }

    function tick(now) {
        rafId = requestAnimationFrame(tick);
        const dt = Math.min(now - (lastTime || now), 50) / 1000;
        lastTime = now;

        const rate = dragging || reduceMotion ? DRAG_SMOOTHING : O.smoothing;
        const delta = target - current;
        current += delta * (1 - Math.exp(-rate * dt));
        if (Math.abs(delta) < 0.00002) current = target;

        if (reveal < 1) {
            if (!revealStart) revealStart = now;
            reveal = Math.min(1, (now - revealStart) / 1100);
        }
        if (O.autoRotateSpeed && !reduceMotion && !dragging && !(O.pauseOnHover && hovered)) {
            target += O.autoRotateSpeed * dt;
        }
        draw(dt);
    }
    function start() { if (!rafId) { lastTime = 0; rafId = requestAnimationFrame(tick); } }
    function stop()  { cancelAnimationFrame(rafId); rafId = 0; }

    // ----- Kéo + văng -----
    let dragging = false, pressed = false, moved = false, pointerId = null, downX = 0, lastX = 0, samples = [];
    function pushSample() {
        const t = performance.now();
        samples.push({ t, v: target });
        while (samples.length > 2 && t - samples[0].t > VELOCITY_WINDOW) samples.shift();
    }
    stage.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        pressed = true; moved = false; pointerId = e.pointerId;
        downX = lastX = e.clientX;
        samples = [{ t: performance.now(), v: target }];
    });
    stage.addEventListener('pointermove', function (e) {
        if (!pressed || e.pointerId !== pointerId) return;
        if (!dragging) {
            // Chỉ coi là kéo khi đã di > vài px, để bấm vào thẻ vẫn mở được link
            if (Math.abs(e.clientX - downX) < DRAG_THRESHOLD) return;
            dragging = true; moved = true;
            target = current;                   // chặn cú văng cũ
            try { stage.setPointerCapture(e.pointerId); } catch (_) {}
            stage.classList.add('is-dragging');
        }
        const dx = e.clientX - lastX;
        lastX = e.clientX;
        target -= (dx * O.dragSensitivity) / L.radius;
        pushSample();
    });
    function endDrag(e) {
        if (!pressed || e.pointerId !== pointerId) return;
        pressed = false; pointerId = null;
        if (!dragging) return;
        dragging = false;
        stage.classList.remove('is-dragging');
        try { if (stage.hasPointerCapture(e.pointerId)) stage.releasePointerCapture(e.pointerId); } catch (_) {}

        pushSample();
        let projected = target;
        if (!reduceMotion) {
            const first = samples[0], last = samples[samples.length - 1];
            const dt = last && first ? (last.t - first.t) / 1000 : 0;
            if (dt > 0.008) {
                const velocity = (last.v - first.v) / dt;
                projected = target + clamp(-MAX_FLICK, MAX_FLICK, velocity / O.smoothing) * O.momentum;
            }
        }
        target = O.snap ? snapTo(L.step, projected) : projected;
        samples = [];
    }
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    // Vừa kéo xong thì không mở link
    stage.addEventListener('click', function (e) {
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
    }, true);
    stage.addEventListener('dragstart', function (e) { e.preventDefault(); });

    stage.addEventListener('wheel', function (e) {
        if (O.wheel === 'off') return;
        const horizontal = Math.abs(e.deltaX) > Math.abs(e.deltaY);
        if (O.wheel === 'horizontal' && !horizontal) return;
        e.preventDefault();
        target += ((horizontal ? e.deltaX : e.deltaY) * O.dragSensitivity) / L.radius;
        if (O.snap) {
            clearTimeout(wheelTimer);
            wheelTimer = setTimeout(function () { target = snapTo(L.step, target); }, 140);
        }
    }, { passive: false });

    function nudge(dir) { target = snapTo(L.step, target) + dir * L.step; }
    stage.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { e.preventDefault(); nudge(1); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); nudge(-1); }
    });
    document.getElementById('catArcNext')?.addEventListener('click', function () { nudge(1); });
    document.getElementById('catArcPrev')?.addEventListener('click', function () { nudge(-1); });

    stage.addEventListener('pointerenter', function () { hovered = true; });
    stage.addEventListener('pointerleave', function () { hovered = false; });
    // Tab vào một thẻ -> xoay thẻ đó về giữa
    stage.addEventListener('focusin', function (e) {
        const idx = inners.indexOf(e.target);
        if (idx < 0) return;
        const span = slotCount * L.step;
        let d = (idx * L.step - target) % span;
        if (d < -span / 2) d += span; else if (d >= span / 2) d -= span;
        target += d;
    });

    // ----- Khởi động -----
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(measure).observe(stage);
    else window.addEventListener('resize', measure);
    measure();

    // Chỉ chạy khi khu vực nằm trong màn hình; hiệu ứng hiện dần bắt đầu khi lần đầu cuộn tới
    new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
        visible ? start() : stop();
    }, { threshold: 0.15 }).observe(section);
})();
</script>
