{{--
    Hero "Herbarium" — hiệu ứng dither kiểu Herbarium (21st.dev) bằng Canvas 2D thuần.
    Không cần React / WebGL / thư viện ngoài.

    Dựa trên preset Herbarium (dither, nền blur, contrast 115, grayscale 28, tint #d8b46a soft-light 38%,
    vignette, bloom) nhưng chỉnh cho NÉT hơn:
      - ô nhỏ hơn (cellSize 8), nền gần như không blur, bloom nhẹ
      - vẽ theo devicePixelRatio -> không bị nhoè trên màn Retina
    Chuyển động: KHÔNG có sóng. Chỉ các chấm lấp lánh ngẫu nhiên (twinkle), từng chấm sáng lên rồi tắt.

    Nguồn hình (ưu tiên theo thứ tự):
      1) Ảnh cây cảnh public/images/hero-bonsai.webp (đổi trong data-image của thẻ canvas)
         -> video bị tắt hẳn để đỡ tốn băng thông.
      2) Không có ảnh -> dùng video hero.mp4 làm nguồn.
    JS lỗi / không hỗ trợ canvas -> video hiện bình thường.
    Bật "giảm chuyển động" -> vẫn vẽ hiệu ứng nhưng đứng yên.  Hero ra khỏi màn hình -> dừng vẽ.
--}}
<canvas id="heroPixelCanvas" aria-hidden="true" data-image="{{ asset('images/hero-bonsai.webp') }}"
        style="position:absolute;inset:0;width:100%;height:100%;display:block;z-index:0;opacity:0;transition:opacity .8s ease"></canvas>

@push('scripts')
<script>
(function () {
    const section = document.getElementById('heroSection');
    const canvas  = document.getElementById('heroPixelCanvas');
    const video   = section ? section.querySelector('video') : null;
    if (!section || !canvas || !video || !canvas.getContext) return;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ===== Thông số (chỉnh ở đây) =====
    const P = {
        cellSize: 8,            // càng nhỏ càng nét (preset gốc: 13)
        bgBlur: 3,              // độ mờ lớp nền (preset gốc: 12)
        bgOpacity: 90,
        brightness: 0, contrast: 115, saturation: 100, grayscale: 28,
        tint: [0xd8, 0xb4, 0x6a], tintOpacity: 38,          // soft-light
        vignette: 40, bloom: 18,
        twinkleAmount: 3,       // % số chấm đang lấp lánh cùng lúc (0 = đứng yên)
        twinkleSpeed: 1,        // tốc độ lấp lánh
        imageScale: 1.0,        // phóng to/thu nhỏ cây trong khung (1 = vừa chiều cao)
        imageOffsetY: 0,        // dịch cây lên/xuống, theo tỉ lệ chiều cao (vd 0.05)
    };

    const ctx  = canvas.getContext('2d', { alpha: false });
    const mk = () => document.createElement('canvas');
    const src  = mk(), sctx = src.getContext('2d', { willReadFrequently: true });  // lưới lấy mẫu
    const bg   = mk(), bgx  = bg.getContext('2d');                                  // nền
    const glow = mk(), gctx = glow.getContext('2d');                                // bloom
    const base = mk(), bctx = base.getContext('2d', { alpha: false });              // khung tĩnh đã vẽ xong
    const edge = mk(), ectx = edge.getContext('2d');                                // màu lấp 2 bên ảnh

    const SUB = 2;          // mỗi ô chia 2x2 ô con
    const BAYER = [0, 8, 2, 10, 12, 4, 14, 6, 3, 11, 1, 9, 15, 7, 13, 5].map(v => (v + 0.5) / 16);

    let source = video, isImage = false;
    const srcW  = () => isImage ? source.naturalWidth : source.videoWidth;
    const srcH  = () => isImage ? source.naturalHeight : source.videoHeight;
    const ready = () => isImage ? source.complete && source.naturalWidth > 0 : source.readyState >= 2;

    let W = 0, H = 0, dpr = 1, sub = 0, cols = 0, rows = 0, dot = 1;
    let dots = null, dotCount = 0, baseReady = false;
    let running = false, visible = true, started = false, lastT = 0;

    function resize() {
        dpr = Math.min(2, window.devicePixelRatio || 1);
        W = Math.round(section.clientWidth * dpr);
        H = Math.round(section.clientHeight * dpr);
        const cellSize = (section.clientWidth < 640 ? 6 : P.cellSize) * dpr;
        sub = cellSize / SUB;
        dot = Math.max(1, Math.round(sub - dpr));           // khe hở 1px (CSS) giữa các chấm
        canvas.width = base.width = W;
        canvas.height = base.height = H;
        cols = Math.ceil(W / sub); rows = Math.ceil(H / sub);
        src.width = cols; src.height = rows;
        bg.width  = Math.max(1, Math.round(W / (P.bgBlur * dpr)));
        bg.height = Math.max(1, Math.round(H / (P.bgBlur * dpr)));
        glow.width = Math.max(1, Math.round(W / 24)); glow.height = Math.max(1, Math.round(H / 24));
        dots = new Float32Array(cols * rows * 5);           // x, y, r, g, b của từng chấm sáng
        baseReady = false;
    }

    function drawSource(c, w, h) {
        const vw = srcW(), vh = srcH();
        if (isImage) {
            // Ảnh: hiện trọn cây (contain), 2 bên lấp bằng mép ảnh kéo giãn cho liền mạch
            // mép ảnh thu về 1x8 pixel rồi kéo giãn -> dải màu mịn, không sọc
            edge.width = 2; edge.height = 8;
            ectx.imageSmoothingEnabled = true;
            ectx.drawImage(source, 0, 0, Math.max(1, vw * 0.04), vh, 0, 0, 1, 8);
            ectx.drawImage(source, vw * 0.96, 0, Math.max(1, vw * 0.04), vh, 1, 0, 1, 8);
            c.imageSmoothingEnabled = true;
            c.drawImage(edge, 0, 0, 1, 8, 0, 0, w / 2, h);
            c.drawImage(edge, 1, 0, 1, 8, w / 2, 0, w / 2, h);
            const s = Math.min(w / vw, h / vh) * P.imageScale;
            const dw = vw * s, dh = vh * s;
            c.drawImage(source, (w - dw) / 2, (h - dh) / 2 + h * P.imageOffsetY, dw, dh);
            return;
        }
        const s = Math.max(w / vw, h / vh), dw = vw * s, dh = vh * s;
        c.drawImage(source, (w - dw) / 2, (h - dh) / 2, dw, dh);
    }

    function softLight(b, s) {
        if (s <= 0.5) return b - (1 - 2 * s) * b * (1 - b);
        const d = b <= 0.25 ? ((16 * b - 12) * b + 4) * b : Math.sqrt(b);
        return b + (2 * s - 1) * (d - b);
    }

    // brightness -> contrast -> saturation -> grayscale -> tint (soft-light)
    const cK = P.contrast / 100, sK = P.saturation / 100, gK = P.grayscale / 100, tK = P.tintOpacity / 100;
    const tr = P.tint[0] / 255, tg = P.tint[1] / 255, tb = P.tint[2] / 255, bK = P.brightness / 100;
    function adjust(d) {
        for (let i = 0; i < d.length; i += 4) {
            let r = d[i] / 255 + bK, g = d[i + 1] / 255 + bK, b = d[i + 2] / 255 + bK;
            r = (r - 0.5) * cK + 0.5; g = (g - 0.5) * cK + 0.5; b = (b - 0.5) * cK + 0.5;
            let l = 0.299 * r + 0.587 * g + 0.114 * b;
            r = l + (r - l) * sK; g = l + (g - l) * sK; b = l + (b - l) * sK;
            l = 0.299 * r + 0.587 * g + 0.114 * b;
            r += (l - r) * gK; g += (l - g) * gK; b += (l - b) * gK;
            r = Math.min(1, Math.max(0, r)); g = Math.min(1, Math.max(0, g)); b = Math.min(1, Math.max(0, b));
            r += (softLight(r, tr) - r) * tK; g += (softLight(g, tg) - g) * tK; b += (softLight(b, tb) - b) * tK;
            d[i] = r * 255; d[i + 1] = g * 255; d[i + 2] = b * 255;
        }
    }

    // Vẽ toàn bộ khung tĩnh vào "base": nền + lưới chấm dither + bloom + vignette.
    // Ảnh tĩnh chỉ vẽ 1 lần; video vẽ lại mỗi khung.
    function buildBase() {
        drawSource(sctx, cols, rows);
        let img;
        try { img = sctx.getImageData(0, 0, cols, rows); }
        catch (e) { return false; }                         // nguồn khác domain không CORS
        adjust(img.data);
        sctx.putImageData(img, 0, 0);
        const d = img.data;

        // Nền
        bgx.imageSmoothingEnabled = true;
        bgx.drawImage(src, 0, 0, bg.width, bg.height);
        bctx.fillStyle = '#14120e';
        bctx.fillRect(0, 0, W, H);
        bctx.globalAlpha = P.bgOpacity / 100;
        bctx.imageSmoothingEnabled = true;
        bctx.drawImage(bg, 0, 0, W, H);
        bctx.globalAlpha = 1;

        // Lưới chấm dither
        const lift = isImage ? 1.0 : 1.35, gain = isImage ? 1.12 : 1.45;
        const add = isImage ? [10, 8, 4] : [28, 22, 12];
        dotCount = 0;
        for (let y = 0; y < rows; y++) {
            for (let x = 0; x < cols; x++) {
                const i = (y * cols + x) * 4;
                const lum = Math.min(1, (0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]) / 255 * lift);
                if (lum <= BAYER[(y & 3) * 4 + (x & 3)]) continue;
                const r = Math.min(255, d[i] * gain + add[0]) | 0;
                const g = Math.min(255, d[i + 1] * gain + add[1]) | 0;
                const b = Math.min(255, d[i + 2] * gain + add[2]) | 0;
                const px = Math.round(x * sub), py = Math.round(y * sub);
                bctx.fillStyle = 'rgb(' + r + ',' + g + ',' + b + ')';
                bctx.fillRect(px, py, dot, dot);
                const k = dotCount++ * 5;
                dots[k] = px; dots[k + 1] = py; dots[k + 2] = r; dots[k + 3] = g; dots[k + 4] = b;
            }
        }

        // Bloom nhẹ
        if (P.bloom > 0) {
            gctx.imageSmoothingEnabled = true;
            gctx.drawImage(base, 0, 0, glow.width, glow.height);
            bctx.globalCompositeOperation = 'screen';
            bctx.globalAlpha = P.bloom / 100 * 0.7;
            bctx.drawImage(glow, 0, 0, W, H);
            bctx.globalAlpha = 1;
            bctx.globalCompositeOperation = 'source-over';
        }

        // Vignette
        const rad = Math.hypot(W, H) / 2;
        const vg = bctx.createRadialGradient(W / 2, H / 2, rad * 0.4, W / 2, H / 2, rad);
        vg.addColorStop(0, 'rgba(0,0,0,0)');
        vg.addColorStop(1, 'rgba(0,0,0,' + (P.vignette / 100 * 0.85).toFixed(2) + ')');
        bctx.fillStyle = vg;
        bctx.fillRect(0, 0, W, H);
        return true;
    }

    // Lấp lánh: mỗi chấm có pha riêng (hash), chỉ sáng lên trong một khoảnh khắc ngắn
    // -> các điểm sáng xuất hiện rải rác, ngẫu nhiên, không tạo thành sóng.
    function drawTwinkle(t) {
        if (P.twinkleAmount <= 0) return;
        const time = t / 1000 * P.twinkleSpeed;
        const on = P.twinkleAmount / 100;                   // tỉ lệ thời gian mỗi chấm được sáng
        for (let n = 0; n < dotCount; n++) {
            const h = Math.imul(n + 1, 2654435761) >>> 0;
            const period = 2.5 + (h % 1000) / 400;          // 2.5s .. 5s, mỗi chấm một nhịp
            const phase = ((time + (h >>> 10) % 1000 / 1000 * period) % period) / period; // 0..1
            if (phase > on) continue;
            const a = Math.sin(phase / on * Math.PI);       // sáng dần rồi tắt dần
            const k = n * 5;
            const r = dots[k + 2], g = dots[k + 3], b = dots[k + 4];
            ctx.fillStyle = 'rgba(' + Math.min(255, r + 90) + ',' + Math.min(255, g + 80) + ',' + Math.min(255, b + 60) + ',' + (a * 0.9).toFixed(2) + ')';
            ctx.fillRect(dots[k], dots[k + 1], dot, dot);
        }
    }

    function frame(t) {
        if (!running) return;
        requestAnimationFrame(frame);
        if (t - lastT < 33) return;                         // ~30fps
        lastT = t;
        if (!ready()) return;

        if (!isImage || !baseReady) {
            if (!buildBase()) { stop(); return; }
            baseReady = true;
        }
        ctx.drawImage(base, 0, 0);
        drawTwinkle(t);

        if (!started) {
            started = true;
            canvas.style.opacity = '1';
            video.style.opacity = '0';
        }
        if (reduceMotion && isImage) stop();                // giảm chuyển động: vẽ 1 khung rồi thôi
    }

    function start() { if (!running && visible) { running = true; requestAnimationFrame(frame); } }
    function stop()  { running = false; }

    new IntersectionObserver(function (e) {
        visible = e[0].isIntersecting;
        visible ? start() : stop();
    }).observe(section);

    let rt;
    window.addEventListener('resize', function () {
        clearTimeout(rt);
        rt = setTimeout(function () { resize(); stop(); start(); }, 150);
    });

    function useVideo() {
        if (reduceMotion) return;                           // giảm chuyển động + không có ảnh -> để video gốc
        resize();
        if (video.readyState >= 2) start();
        else video.addEventListener('loadeddata', start, { once: true });
    }

    const imageUrl = canvas.dataset.image;
    if (imageUrl) {
        const img = new Image();
        img.decoding = 'async';
        img.onload = function () {
            source = img; isImage = true;
            video.pause();                                  // có ảnh -> tắt video, ngừng tải
            video.removeAttribute('src'); video.load();
            resize(); start();
        };
        img.onerror = useVideo;
        img.src = imageUrl;
    } else {
        useVideo();
    }
})();
</script>
@endpush
