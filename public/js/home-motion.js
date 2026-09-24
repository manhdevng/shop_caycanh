/* ============================================================================
   home-motion.js — toàn bộ chuyển động cuộn của trang chủ: "Một lối đi xuyên vườn"
   ----------------------------------------------------------------------------
   GSAP + ScrollTrigger là engine DUY NHẤT của trang chủ (ScrollCraft JS không
   còn được nạp). Trang là một chuyến đi liền mạch, mỗi khối nối vào khối sau:

     Hero            chữ rời lên, ảnh áp sát, tối dần về màu nền lá của khối
                     danh mục -> hai khối hòa vào nhau, không có nhát cắt.
     Danh mục        ảnh lá nền trôi chậm hơn trang (chiều sâu).
     Vườn trong nhà  GHIM. Vén lá -> đọc -> căn phòng thu lại thành CỬA VÒM,
                     nền đổi sang màu khối kế tiếp -> bước ra khỏi phòng.
     Lưới sản phẩm   Mọc từ đáy lên.
     Vườn ngoài trời GHIM. Cửa vòm (cùng mô-típ vừa khép lại ở trên) mở rộng ra
                     tràn màn hình -> bước qua cửa ra vườn; chữ hiện khi cửa mở hết.
     Cam kết         Thân dây mọc dài, lá thật ở ngọn.
     Quà tặng        GHIM. Ảnh cảnh 2 mọc từ đáy lên phủ cảnh 1, chữ đổi lượt.

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
    open: 'power2.inOut',          // cửa vòm mở / khép
    sway: 'sine.inOut',            // Lay
    settle: 'elastic.out(1, 0.4)', // Lay: về chỗ sau khi dừng cuộn
    growDur: 1.1,
    stagger: 0.09,
    rise: 28,                      // px, Mọc tối đa
    scrub: 1.2                     // độ trễ giữa tay cuộn và cảnh — cả trang dùng chung
  };

  // Cửa vòm mở hết. Khung (.sc-gate__frame, .sc-gate-season__frame, ảnh quà
  // tặng) dựng clip-path trong CSS từ các biến --ct/--cs/--cr; GSAP chỉ nội
  // suy các biến số này. Không animate thẳng chuỗi clip-path vì trình duyệt tự
  // rút gọn "inset(0% 0% 0% 0% round 0px ...)" thành "inset(0%)" -> số lượng
  // số hai đầu lệch nhau, GSAP không nội suy được và nhảy cóc ở cuối.
  var OPEN = { '--ct': '0%', '--cs': '0%', '--cr': '0px' };

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
      var env = { mobile: ctx.conditions.mobile, lay: createLay(root) };

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
        root.classList.remove('motion-on');
      };
    });

    // Ảnh/phông nạp xong làm đổi chiều cao trang -> đo lại vị trí trigger.
    if (document.readyState === 'complete') ScrollTrigger.refresh();
    else window.addEventListener('load', function () { ScrollTrigger.refresh(); }, { once: true });
  }

  /* ------------------------------------------------------------ tiện ích -- */

  // Khung cửa vòm: rộng wFrac bề ngang, đỉnh ở topFrac chiều cao, chân chạm
  // đáy khung, bo tròn nửa bề rộng. Bán kính là hàm để đo lại khi đổi cỡ màn
  // hình (invalidateOnRefresh).
  function arch(el, wFrac, topFrac) {
    return {
      '--ct': (topFrac * 100).toFixed(2) + '%',
      '--cs': ((1 - wFrac) / 2 * 100).toFixed(2) + '%',
      '--cr': function () { return Math.round(el.offsetWidth * wFrac / 2) + 'px'; }
    };
  }
  function extend(a, b) { var o = {}, k; for (k in a) o[k] = a[k]; for (k in b) o[k] = b[k]; return o; }

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
  function categoriesScene(sec) {
    var bg = sec.querySelector('.cat-arc-bg');
    if (!bg) return;
    gsap.fromTo(bg, { yPercent: -6, scale: 1.14 }, {
      yPercent: 6, scale: 1.14, ease: 'none',
      scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: true }
    });
  }

  /* ------------------------------------------- Vườn trong nhà (GHIM) --
     .gg-leaf cắm cuống ở mép khung, CSS vẽ sẵn thế ĐÃ VÉN (data-rot). Timeline
     dài 1 đơn vị = cả quãng ghim, chia nhãn:
       part  0.00  lá vén (lá gần trước, lá xa sau), ảnh lùi từ 1.1 về 1
       read  0.40  scrim + chữ hiện, dải nắng quét qua (--sc-p)
       leave 0.74  chữ + lá rời đi, căn phòng thu thành cửa vòm, nền đổi sang
                   màu khối kế tiếp -> khi thả ghim, khối sau nối liền. */
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
        end: pinDistance(env.mobile ? 1.6 : 2.4),
        pin: true,
        scrub: M.scrub,
        anticipatePin: 1,
        invalidateOnRefresh: true
      }
    });
    tl.addLabel('part', 0).addLabel('read', 0.4).addLabel('leave', 0.74);

    // --sc-p lái dải nắng (CSS calc trong gate-garden.blade.php).
    tl.fromTo(gate, { '--sc-p': 0 }, { '--sc-p': 1, ease: 'none', duration: 1 }, 0);
    if (photo) tl.fromTo(photo, { scale: 1.1 }, { scale: 1, duration: 0.45, ease: 'power1.out' }, 'part');

    leaves.forEach(function (leaf) {
      var d = parseFloat(leaf.dataset.depth) || 1;
      var side = parseFloat(getComputedStyle(leaf).getPropertyValue('--ax')) < 50 ? -1 : 1;
      tl.fromTo(leaf,
        { rotation: parseFloat(leaf.dataset.from), scale: 1 + 0.16 * d },
        { rotation: parseFloat(leaf.dataset.rot), scale: 1, duration: 0.3 },
        0.03 + 0.09 * (1 - d) / 0.6);
      // Rời cảnh: lá gần đi trước, dạt hẳn ra ngoài mép.
      tl.to(leaf, { x: side * window.innerWidth * (0.2 + 0.2 * d), autoAlpha: 0, duration: 0.16, ease: 'power2.in' },
        'leave+=' + (0.06 * (1 - d)).toFixed(3));
      var sway = leaf.querySelector('.gg-leaf__sway');
      if (sway) env.lay.add(sway, { k: 0.3 + 0.5 * d, sunTilt: 4 });
    });

    if (plate) {
      tl.fromTo(plate, { autoAlpha: 0 }, { autoAlpha: 0.9, duration: 0.14, ease: 'none' }, 'read-=0.08')
        .to(plate, { autoAlpha: 0, duration: 0.1, ease: 'none' }, 'leave');
    }
    if (copy) {
      tl.fromTo(copy, { autoAlpha: 0, y: M.rise }, { autoAlpha: 1, y: 0, duration: 0.12, ease: M.grow }, 'read')
        .to(copy, { autoAlpha: 0, y: -M.rise, duration: 0.1, ease: 'power2.in' }, 'leave');
    }
    if (frame) {
      tl.fromTo(frame, OPEN,
        extend(arch(stage, env.mobile ? 0.62 : 0.34, env.mobile ? 0.2 : 0.14), { duration: 0.24, ease: M.open, immediateRender: false }),
        'leave+=0.02');
    }
    tl.to(stage, { backgroundColor: nextBackground(gate), duration: 0.2, ease: 'none' }, 'leave+=0.06');
    tl.set({}, {}, 1); // giữ tổng = 1 để nhãn khớp tiến độ ghim
  }

  /* ------------------------------------------ Vườn ngoài trời (GHIM) --
     Mở đầu là cửa vòm nhỏ trên nền trắng — tiếp mô-típ căn phòng vừa khép lại
     ở gate-garden. Cửa mở rộng tràn màn hình, ảnh lùi từ 1.25 về 1 như bước
     qua ngưỡng cửa; khi cửa mở hết, scrim và từng dòng chữ hiện (Mọc). */
  function seasonScene(sec, env) {
    var frame = sec.querySelector('.sc-gate-season__frame');
    var img = frame ? frame.querySelector('img') : null;
    var scrim = sec.querySelector('.sc-scrim');
    var lines = sec.querySelectorAll('.sc-gate-season__copy > *');
    if (!frame) return;

    gsap.timeline({
      defaults: { ease: M.open },
      scrollTrigger: {
        trigger: sec,
        start: 'top top',
        end: pinDistance(env.mobile ? 1.2 : 1.8),
        pin: true,
        scrub: M.scrub,
        anticipatePin: 1,
        invalidateOnRefresh: true
      }
    })
      .addLabel('open', 0)
      .fromTo(frame, arch(sec, env.mobile ? 0.62 : 0.34, env.mobile ? 0.26 : 0.2), extend(OPEN, { duration: 0.45 }), 'open')
      .fromTo(img, { scale: 1.25 }, { scale: 1, duration: 0.5, ease: 'power2.out' }, 'open')
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
      defaults: { ease: M.open },
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
