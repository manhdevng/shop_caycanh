/* ============================================================================
   home-motion.js — toàn bộ chuyển động cuộn của trang chủ: "Một lối đi xuyên vườn"
   ----------------------------------------------------------------------------
   GSAP + ScrollTrigger là engine DUY NHẤT của trang chủ (ScrollCraft JS không
   còn được nạp). Trang là một chuyến đi liền mạch, mỗi khối nối vào khối sau:

     Hero            chữ rời lên, ảnh áp sát, tối dần về màu nền lá của khối
                     danh mục -> hai khối hòa vào nhau, không có nhát cắt.
     Danh mục        ảnh lá nền trôi chậm hơn trang (chiều sâu).
     Vườn trong nhà  GHIM. Ba chiếc lá ở mép hé ra -> đọc -> tấm ảnh TRÔI ĐI
                     trên vòng cung sang trái, nền đổi sang màu khối kế tiếp.
     Lưới sản phẩm   Mọc từ đáy lên.
     Vườn ngoài trời GHIM. Tấm ảnh kế tiếp TRÔI TỚI trên cùng vòng cung đó từ
                     bên phải vào giữa; chữ hiện khi ảnh đã về chỗ.
     Cam kết         Thân dây mọc dài, lá thật ở ngọn.
     Quà tặng        GHIM. Ảnh cảnh 2 mọc từ đáy lên phủ cảnh 1, chữ đổi lượt.

   Hai cảnh ghim giữa trang nối nhau như hai thẻ của MỘT carousel: thẻ trước
   trôi khỏi cung, thẻ sau trôi vào — cùng ngôn ngữ với carousel danh mục ngay
   phía trên (xem arcSlot).

   Ba động từ cho mọi chuyển động: Mọc (vào từ gốc lên), Lay (quán tính, lắc
   theo vận tốc cuộn rồi lắng), Hướng sáng (--sc-sun theo tiến độ cả trang).

   Cổng an toàn: KHÔNG có CSS nào ẩn nội dung trước. `.motion-on` (gắn ở đây,
   chỉ khi GSAP chạy và người dùng không bật giảm chuyển động) mới đổi bố cục
   sang dạng ghim được; mọi trạng thái "chưa hiện" do gsap đặt. JS lỗi / giảm
   chuyển động -> trang tĩnh, đủ nội dung, bấm được.
   ========================================================================== */
(function () {
  'use strict';

  // Token chuyển động dùng chung — chỉnh ở đây, không rải số lẻ trong từng cảnh.
  var M = {
    grow: 'power3.out',            // Mọc: nhanh ở đầu, chậm dần như mầm đội đất
    unfurl: 'power3.inOut',        // Mọc (mặt nạ ảnh): êm hai đầu
    arc: 'power2.inOut',           // Trôi trên vòng cung: êm hai đầu, không giật
    sway: 'sine.inOut',            // Lay
    settle: 'elastic.out(1, 0.4)', // Lay: về chỗ sau khi dừng cuộn
    growDur: 1.1,
    stagger: 0.09,
    rise: 28,                      // px, Mọc tối đa
    scrub: 1.2                     // độ trễ giữa tay cuộn và cảnh — cả trang dùng chung
  };

  // Vị trí thẻ trên VÒNG CUNG — dùng lại đúng công thức của carousel danh mục
  // (category-arc.blade.php, "Arc Flow Carousel"): các thẻ nằm trên một cung
  // tròn bán kính rất lớn, tâm nằm sâu phía dưới màn hình. Trôi dọc cung đó thì
  // thẻ vừa xoay nhẹ, vừa dạt ngang, vừa hụp xuống một chút — mắt đọc ra ngay
  // là "thẻ kế tiếp của cùng một băng chuyền", khác hẳn kiểu khung thu/nở tại
  // chỗ như cánh cửa.
  //     x = R·sin θ     y = R·(1 − cos θ)     góc = θ
  // dir: -1 rời sang trái · +1 chờ sẵn bên phải. x/y là hàm để đo lại theo
  // chiều cao màn hình mỗi lần ScrollTrigger refresh (invalidateOnRefresh).
  function arcSlot(dir, env) {
    var deg = (env.mobile ? 7 : 10) * dir;
    var rad = deg * Math.PI / 180;
    var rMul = env.mobile ? 2.6 : 2.2;
    return {
      x: function () { return Math.round(window.innerHeight * rMul * Math.sin(rad)); },
      y: function () { return Math.round(window.innerHeight * rMul * (1 - Math.cos(rad))); },
      rotation: deg,
      scale: env.mobile ? 0.9 : 0.86,
      borderRadius: '20px'
    };
  }
  // Thẻ đang ở chính giữa cung: phẳng, tràn khung, không bo góc.
  var ARC_CENTER = { x: 0, y: 0, rotation: 0, scale: 1, borderRadius: '0px' };

  function boot() {
    var root = document.querySelector('.sc-home');
    if (!root || !window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);
    // Thanh địa chỉ điện thoại co giãn khi cuộn -> không đo lại cả trang mỗi lần.
    ScrollTrigger.config({ ignoreMobileResize: true });

    var mm = gsap.matchMedia();
    mm.add({
      motion: '(prefers-reduced-motion: no-preference)',
      mobile: '(max-width: 860px)'
    }, function (ctx) {
      if (!ctx.conditions.motion) return;

      // Gắn trước khi tạo trigger: các khối ghim đổi sang cao một màn hình.
      root.classList.add('motion-on');
      var env = { mobile: ctx.conditions.mobile, lay: createLay(root), ghost: createHeaderGhost() };

      // Tạo cảnh theo đúng thứ tự trên trang (trên -> dưới) để mỗi trigger
      // được đo SAU pin spacer của các khối ghim phía trên nó.
      var scenes = [
        ['#heroSection', heroScene],
        ['#catArcSection', categoriesScene],
        ['.sc-gate--garden', gardenScene],
        ['.sc-gate--season', seasonScene],
        ['#giftSection', giftScene]
      ];
      Array.prototype.forEach.call(root.children, function (block) {
        for (var i = 0; i < scenes.length; i++) {
          if (block.matches(scenes[i][0])) scenes[i][1](block, env);
        }
        if (block.querySelector('[data-grow]')) growGrid(block);
        if (block.querySelector('[data-vine]')) vines(block, env.lay);
      });

      // Trigger phủ cả trang tạo SAU CÙNG: 'max' đo khi mọi pin spacer đã có.
      env.lay.start();

      return function () {
        env.lay.kill();
        env.ghost.kill();
        root.classList.remove('motion-on');
      };
    });

    // Ảnh/phông nạp xong làm đổi chiều cao trang -> đo lại vị trí trigger.
    if (document.readyState === 'complete') ScrollTrigger.refresh();
    else window.addEventListener('load', function () { ScrollTrigger.refresh(); }, { once: true });
  }

  /* ------------------------------------------------------------ tiện ích -- */

  function extend(a, b) { var o = {}, k; for (k in a) o[k] = a[k]; for (k in b) o[k] = b[k]; return o; }

  /* ------------------------------------------------ Header mờ trong cảnh --
     Các cảnh tối chiếm trọn màn hình (danh mục, hai cảnh ghim) nằm ngay dưới
     header fixed: thanh nền TRẮNG của header cắt ngang đúng phần đang chuyển
     động. Khi một cảnh như vậy đang ở dưới header thì bỏ nền + viền, chỉ để
     lại chữ trắng — giao diện chính là cảnh, không phải thanh điều hướng.
     Dùng bộ đếm chứ không phải cờ bật/tắt: hai cảnh liền nhau có thể cùng
     active trong một nhịp cuộn, nếu dùng cờ thì cảnh ra sẽ tắt nhầm cảnh vào.
     CHỈ gắn cho cảnh nền TỐI — cảnh quà tặng nền kem (#F7F4EF) mà để chữ
     trắng thì không đọc được. */
  function createHeaderGhost() {
    var header = document.getElementById('siteHeader');
    var depth = 0;
    return {
      toggle: function (self) {
        if (!header) return;
        depth += self.isActive ? 1 : -1;
        if (depth < 0) depth = 0;
        header.classList.toggle('header-ghost', depth > 0);
      },
      kill: function () {
        if (header) header.classList.remove('header-ghost');
      }
    };
  }

  function pinDistance(vhMultiple) {
    return function () { return '+=' + Math.round(window.innerHeight * vhMultiple); };
  }

  // Màu nền mà khối kế tiếp mở ra (để cảnh trước "đổi màu" khớp vào nó).
  function nextBackground(block) {
    var n = block.nextElementSibling;
    while (n && (n.tagName === 'STYLE' || n.tagName === 'SCRIPT')) n = n.nextElementSibling;
    var candidates = n ? [n, n.firstElementChild] : [];
    for (var i = 0; i < candidates.length; i++) {
      var el = candidates[i];
      if (!el || (i > 0 && el.offsetWidth < n.offsetWidth)) continue;
      var bg = getComputedStyle(el).backgroundColor;
      if (bg && bg !== 'transparent' && bg !== 'rgba(0, 0, 0, 0)') return bg;
    }
    return '#FFFFFF';
  }

  /* --------------------------------------------------- Lay + Hướng sáng --
     Một ScrollTrigger phủ cả trang đọc tiến độ (nắng, 0 -> 1) và vận tốc cuộn.
     Nắng ghi ra --sc-sun trên .sc-home (ảnh sản phẩm .sc-leaf nghiêng theo,
     scrollcraft-shop.css). Mỗi lá thật đăng ký qua lay.add():
       góc = base + (nắng - 0.5) * sunTilt + k * độ lay theo vận tốc
     Cuộn nhanh thì lá bị kéo lệch, dừng tay 0.14s thì lắng về góc theo nắng
     bằng elastic nhỏ. quickTo dùng lại một tween cho mỗi lá, không tạo mới. */
  function createLay(root) {
    var items = [];
    var sun = 0.5;
    var written = -1;
    var settle = null;

    function target(it, v) { return it.base + (sun - 0.5) * it.sunTilt + it.k * v; }
    function push(v) { for (var i = 0; i < items.length; i++) items[i].to(target(items[i], v)); }
    function writeSun(p) {
      sun = p;
      // Ghi biến CSS làm mọi .sc-leaf tính lại style -> bỏ qua khi lệch rất nhỏ.
      if (Math.abs(p - written) < 0.002) return;
      written = p;
      root.style.setProperty('--sc-sun', p.toFixed(3));
    }

    return {
      add: function (el, opts) {
        var it = {
          base: opts.base || 0,
          k: opts.k == null ? 1 : opts.k,
          sunTilt: opts.sunTilt == null ? 8 : opts.sunTilt,
          to: gsap.quickTo(el, 'rotation', { duration: 1.4, ease: M.settle })
        };
        gsap.set(el, { rotation: target(it, 0) });
        items.push(it);
      },
      start: function () {
        var st = ScrollTrigger.create({
          start: 0,
          end: 'max',
          onUpdate: function (self) {
            writeSun(self.progress);
            push(gsap.utils.clamp(-10, 10, self.getVelocity() / -160));
            if (settle) settle.kill();
            settle = gsap.delayedCall(0.14, function () { push(0); });
          }
        });
        writeSun(st.progress);
        push(0);
      },
      kill: function () {
        if (settle) settle.kill();
        root.style.removeProperty('--sc-sun');
      }
    };
  }

  /* ---------------------------------------------------------------- Hero --
     Không ghim — hero là khoảnh khắc tĩnh mở đầu. Khi rời đi: chữ trôi lên và
     mờ, ảnh áp sát (scale), lớp veil tối dần về đúng màu nền #0A100B của khối
     danh mục -> đáy hero và đỉnh danh mục cùng một màu, không có nhát cắt. */
  function heroScene(hero) {
    var copy = hero.querySelector('.hero-copy');
    var veil = hero.querySelector('.hero-veil');
    var media = hero.querySelectorAll('video, canvas');
    gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: M.scrub }
    })
      .to(copy, { yPercent: -22, autoAlpha: 0, ease: 'power1.in' }, 0)
      .to(media, { scale: 1.12 }, 0)
      .to(veil, { opacity: 1 }, 0.3);
  }

  /* ------------------------------------------------------------ Danh mục --
     Ảnh lá nền trôi ngược một chút so với trang -> có chiều sâu, và là lớp lá
     đầu tiên của lối đi trước khi vào vườn. Carousel vòng cung tự chạy vòng
     rAF riêng (category-arc.blade.php), không đụng tới. */
  function categoriesScene(sec, env) {
    // Nền gần như đen: header phải mờ suốt quãng khối này nằm dưới nó.
    ScrollTrigger.create({ trigger: sec, start: 'top top', end: 'bottom top', onToggle: env.ghost.toggle });

    var bg = sec.querySelector('.cat-arc-bg');
    if (!bg) return;
    gsap.fromTo(bg, { yPercent: -6, scale: 1.14 }, {
      yPercent: 6, scale: 1.14, ease: 'none',
      scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: true }
    });
  }

  /* ------------------------------------------- Vườn trong nhà (GHIM) --
     Cảnh này trước đây có tám chiếc lá xoay 80-100° quanh cuống rồi văng ra
     khỏi màn hình, cộng dải nắng quét ngang — quá nhiều thứ động cùng lúc và
     góc xoay lớn tới mức lá trông như quạt giấy chứ không như lá thật. Nay chỉ
     còn BA chiếc ở mép, hé ra đúng ~26° (xem $gardenLeaves), đủ để thấy khung
     lá động mà vẫn tự nhiên.

     Timeline dài 1 đơn vị = cả quãng ghim, ba nhãn:
       part  0.00  ba lá hé ra, ảnh lùi từ 1.06 về 1
       read  0.38  scrim + chữ hiện
       leave 0.72  chữ mờ, lá mờ, tấm ảnh TRÔI ĐI trên vòng cung sang trái và
                   nền đổi sang màu khối kế tiếp -> thẻ sau (gate-season) trôi
                   tới từ bên phải, hai cảnh nối nhau như một carousel. */
  function gardenScene(gate, env) {
    var stage = gate.querySelector('.sc-gate__stage');
    var frame = gate.querySelector('.sc-gate__frame');
    var photo = frame ? frame.querySelector('img') : null;
    var copy = gate.querySelector('.sc-gate__copy');
    var plate = gate.querySelector('.sc-gate__plate');
    var leaves = gsap.utils.toArray(gate.querySelectorAll('.gg-leaf'));

    var tl = gsap.timeline({
      defaults: { ease: M.sway },
      scrollTrigger: {
        trigger: gate,
        start: 'top top',
        end: pinDistance(env.mobile ? 1.3 : 1.8),
        pin: true,
        scrub: M.scrub,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onToggle: env.ghost.toggle
      }
    });
    tl.addLabel('part', 0).addLabel('read', 0.38).addLabel('leave', 0.72);

    if (photo) tl.fromTo(photo, { scale: 1.06 }, { scale: 1, duration: 0.5, ease: 'power1.out' }, 'part');

    leaves.forEach(function (leaf) {
      var d = parseFloat(leaf.dataset.depth) || 1;
      tl.fromTo(leaf,
        { rotation: parseFloat(leaf.dataset.from) },
        { rotation: parseFloat(leaf.dataset.rot), duration: 0.42, ease: M.sway },
        0.02 + 0.06 * (1 - d));
      // Rời cảnh: chỉ mờ đi cùng tấm ảnh, không còn cú văng ngang.
      tl.to(leaf, { autoAlpha: 0, duration: 0.14, ease: 'power2.in' }, 'leave');
      var sway = leaf.querySelector('.gg-leaf__sway');
      if (sway) env.lay.add(sway, { k: 0.3 + 0.5 * d, sunTilt: 4 });
    });

    if (plate) {
      tl.fromTo(plate, { autoAlpha: 0 }, { autoAlpha: 0.9, duration: 0.14, ease: 'none' }, 'read-=0.08')
        .to(plate, { autoAlpha: 0, duration: 0.1, ease: 'none' }, 'leave');
    }
    if (copy) {
      tl.fromTo(copy, { autoAlpha: 0, y: M.rise }, { autoAlpha: 1, y: 0, duration: 0.14, ease: M.grow }, 'read')
        .to(copy, { autoAlpha: 0, y: -M.rise, duration: 0.1, ease: 'power2.in' }, 'leave');
    }
    if (frame) {
      tl.to(frame, extend(arcSlot(-1, env), { autoAlpha: 0, duration: 0.28, ease: M.arc }), 'leave+=0.02');
    }
    tl.to(stage, { backgroundColor: nextBackground(gate), duration: 0.2, ease: 'none' }, 'leave+=0.08');
    tl.set({}, {}, 1); // giữ tổng = 1 để nhãn khớp tiến độ ghim
  }

  /* ------------------------------------------ Vườn ngoài trời (GHIM) --
     Thẻ kế tiếp của cùng băng chuyền: tấm ảnh chờ sẵn bên PHẢI trên vòng cung
     (nghiêng, nhỏ, bo góc — đúng thế mà tấm ảnh trước vừa rời đi sang trái),
     rồi trôi về giữa, phẳng ra và tràn khung. Ảnh bên trong lùi nhẹ từ 1.12 về
     1 để lớp trong và lớp ngoài không dính cứng vào nhau. Về tới giữa thì
     scrim và từng dòng chữ hiện (Mọc). */
  function seasonScene(sec, env) {
    var frame = sec.querySelector('.sc-gate-season__frame');
    var img = frame ? frame.querySelector('img') : null;
    var scrim = sec.querySelector('.sc-scrim');
    var lines = sec.querySelectorAll('.sc-gate-season__copy > *');
    if (!frame) return;

    gsap.timeline({
      defaults: { ease: M.arc },
      scrollTrigger: {
        trigger: sec,
        start: 'top top',
        end: pinDistance(env.mobile ? 1.0 : 1.4),
        pin: true,
        scrub: M.scrub,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onToggle: env.ghost.toggle
      }
    })
      .addLabel('arrive', 0)
      .fromTo(frame, arcSlot(1, env), extend(ARC_CENTER, { duration: 0.45 }), 'arrive')
      .fromTo(img, { scale: 1.12 }, { scale: 1, duration: 0.5, ease: 'power2.out' }, 'arrive')
      .addLabel('read', 0.4)
      .fromTo(scrim, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.14, ease: 'none' }, 'read')
      .fromTo(lines, { autoAlpha: 0, y: M.rise }, { autoAlpha: 1, y: 0, duration: 0.14, stagger: 0.035, ease: M.grow }, 'read+=0.04')
      .set({}, {}, 1);
  }

  /* ------------------------------------------------- Quà tặng (GHIM) --
     Hai cảnh xếp chồng. Ảnh cảnh 2 mọc từ đáy lên phủ ảnh cảnh 1 (ảnh cũ áp
     sát nhẹ, như lùi ra sau); chữ cảnh 1 rời lên, chữ cảnh 2 nhô lên thay. */
  function giftScene(sec, env) {
    var t0 = sec.querySelectorAll('[data-gift-text="0"] > *');
    var t1 = sec.querySelectorAll('[data-gift-text="1"] > *');
    var i0 = sec.querySelector('[data-gift-img="0"]');
    var i1 = sec.querySelector('[data-gift-img="1"]');
    if (!i0 || !i1) return;

    gsap.timeline({
      defaults: { ease: M.arc },
      scrollTrigger: {
        trigger: sec,
        start: 'top top',
        end: pinDistance(env.mobile ? 1.1 : 1.6),
        pin: true,
        scrub: M.scrub,
        anticipatePin: 1,
        invalidateOnRefresh: true
      }
    })
      .addLabel('swap', 0.24)
      .fromTo(i1, { '--ct': '100%', scale: 1.12 }, { '--ct': '0%', scale: 1, duration: 0.4 }, 'swap')
      .to(i0, { scale: 1.08, duration: 0.4, ease: 'none' }, 'swap')
      .to(t0, { autoAlpha: 0, y: -M.rise, duration: 0.14, stagger: 0.03, ease: 'power2.in' }, 'swap')
      .fromTo(t1, { autoAlpha: 0, y: M.rise }, { autoAlpha: 1, y: 0, duration: 0.16, stagger: 0.04, ease: M.grow }, 'swap+=0.22')
      .set({}, {}, 1);
  }

  /* ---------------------------------------------------------- Thân dây --
     Khối cam kết: [data-vine] > .sc-rule__line + .sc-rule__leaf. Thân dài dần
     theo cuộn, lá đi theo ngọn: nhú ra từ cuống ở đầu thân rồi được ngọn mang
     tới cuối. Ảnh lá bên trong đăng ký Lay — tách hai lớp để scrub (wrapper)
     và lay (ảnh) không tranh nhau cùng thuộc tính rotation. */
  function vines(block, lay) {
    gsap.utils.toArray(block.querySelectorAll('[data-vine]')).forEach(function (rule) {
      var line = rule.querySelector('.sc-rule__line');
      var leaf = rule.querySelector('.sc-rule__leaf');
      var img = leaf ? leaf.querySelector('img') : null;
      var tl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: { trigger: rule, start: 'top 88%', end: 'top 45%', scrub: M.scrub, invalidateOnRefresh: true }
      });
      tl.fromTo(line, { scaleX: 0 }, { scaleX: 1, duration: 1 }, 0);
      if (leaf) {
        tl.fromTo(leaf, { x: function () { return -rule.offsetWidth; } }, { x: 0, duration: 1 }, 0)
          .fromTo(leaf, { scale: 0, rotation: -40 }, { scale: 1, rotation: 0, duration: 0.35, ease: M.grow }, 0.04);
      }
      if (img) lay.add(img, { base: -8 });
    });
  }

  /* ---------------------------------------------------------------- Mọc --
     Lưới sản phẩm: [data-grow] > thẻ. Ảnh (.sc-leaf) lộ ra từ đáy lên như mầm
     đội đất, ảnh bên trong co từ 1.08 về 1 quanh đáy; thẻ nhô lên M.rise px;
     chữ + nút hiện sau một nhịp. Chạy một lần — nội dung đã hiện thì không ẩn
     lại khi cuộn ngược (ẩn lại là lỗi, không phải hiệu ứng). */
  function growGrid(block) {
    var cards = gsap.utils.toArray(block.querySelectorAll('[data-grow] > *'));
    if (!cards.length) return;

    function parts(card) {
      var pic = card.querySelector('.sc-leaf');
      var img = pic ? pic.querySelector('img') : null;
      // Mọi thứ không phải ảnh: nút yêu thích (anh em của ảnh) + khối chữ/nút mua.
      var rest = [];
      if (pic) {
        for (var s = pic.nextElementSibling; s; s = s.nextElementSibling) rest.push(s);
      }
      for (var c = card.firstElementChild ? card.firstElementChild.nextElementSibling : null; c; c = c.nextElementSibling) rest.push(c);
      return { pic: pic, img: img, rest: rest };
    }

    cards.forEach(function (card) {
      var p = parts(card);
      gsap.set(card, { y: M.rise });
      if (p.pic) gsap.set(p.pic, { clipPath: 'inset(100% 0% 0% 0%)' });
      if (p.img) gsap.set(p.img, { scale: 1.08, transformOrigin: '50% 100%' });
      if (p.rest.length) gsap.set(p.rest, { autoAlpha: 0 });
    });

    function reveal(batch) {
      batch = batch.filter(function (c) { return !c._grown; });
      if (!batch.length) return;
      batch.forEach(function (c) { c._grown = true; });
      var tl = gsap.timeline();
      // Nhịp stagger tính theo THẺ, không theo từng dòng chữ: một loạt 8 thẻ
      // (nhảy anchor) vẫn hiện đủ chữ trong ~1.7s.
      batch.forEach(function (card, i) {
        var p = parts(card);
        var at = i * M.stagger;
        tl.to(card, { y: 0, duration: M.growDur, ease: M.grow, clearProps: 'transform' }, at);
        if (p.pic) tl.to(p.pic, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.0, ease: M.unfurl, clearProps: 'clipPath' }, at);
        if (p.img) tl.to(p.img, { scale: 1, duration: 1.6, ease: 'power2.out', clearProps: 'transform' }, at);
        if (p.rest.length) tl.to(p.rest, { autoAlpha: 1, duration: 0.6, ease: 'power1.out' }, at + 0.4);
      });
    }

    ScrollTrigger.batch(cards, {
      start: 'top 90%',
      once: true,
      onEnter: reveal,
      // Nhảy thẳng qua (anchor, phím End): vẫn phải hiện đủ.
      onLeave: reveal
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
