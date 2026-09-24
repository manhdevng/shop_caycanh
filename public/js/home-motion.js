/* ============================================================================
   home-motion.js — lớp chuyển động hữu cơ của trang chủ: "Mọc · Lay · Hướng sáng"
   ----------------------------------------------------------------------------
   Chạy bằng GSAP + ScrollTrigger, SONG SONG với ScrollCraft (không thay nó):
     - ScrollCraft vẫn giữ các khối ghim (sticky), cue chữ và --sc-sun.
     - File này chỉ lo những phần tử KHÔNG còn mang data-sc-reveal / data-sc-in,
       nên hai engine không bao giờ cùng điều khiển một phần tử.

   Ba động từ — mọi chuyển động ở đây phải là một trong ba:
     Mọc        vào từ gốc lên (transformOrigin đáy), không trượt ngang.
     Lay        có quán tính: scrub có độ trễ, lắc theo vận tốc cuộn rồi lắng lại.
     Hướng sáng ăn theo --sc-sun do home-boot ghi (xem scrollcraft-shop.css).

   Cổng an toàn: KHÔNG có CSS nào ẩn nội dung trước. Mọi trạng thái "chưa hiện"
   chỉ được gsap.set() khi GSAP đã nạp và người dùng không bật giảm chuyển động.
   JS lỗi / GSAP bị chặn -> trang hiện đủ, bấm được, như chưa có file này.

   Thời điểm chạy: thẻ script có defer, nạp SAU scrollcraft.js, nên listener
   DOMContentLoaded ở đây đăng ký sau listener của home-boot -> chạy sau
   ScrollCraft.mount(), lúc chiều cao các khối ghim đã được đặt xong.
   ========================================================================== */
(function () {
  'use strict';

  // Token chuyển động dùng chung — chỉnh ở đây, không rải số lẻ trong từng khối.
  var M = {
    grow: 'power3.out',      // Mọc: nhanh ở đầu, chậm dần như mầm đội đất
    unfurl: 'power3.inOut',  // Mọc (mặt nạ ảnh): êm hai đầu
    sway: 'sine.inOut',      // Lay khi đứng yên
    settle: 'elastic.out(1, 0.4)', // Lay: về chỗ sau khi dừng cuộn, biên độ nhỏ
    growDur: 1.1,
    stagger: 0.09,
    rise: 28,                // px, Mọc tối đa
    scrub: 1.2               // Lay: độ trễ giữa tay cuộn và chuyển động
  };

  function boot() {
    var root = document.querySelector('.sc-home');
    if (!root || !window.gsap || !window.ScrollTrigger) return;
    gsap.registerPlugin(ScrollTrigger);

    var mm = gsap.matchMedia();
    mm.add('(prefers-reduced-motion: no-preference)', function () {
      var lay = createLay();
      // Tạo theo thứ tự xuất hiện trên trang (trên -> dưới) để refresh đúng.
      gardenLeaves(root, lay);
      growGrids(root);
      vines(root, lay);
      return lay.kill;
    });

    // Ảnh/phông nạp xong làm đổi chiều cao trang -> đo lại vị trí trigger.
    if (document.readyState === 'complete') ScrollTrigger.refresh();
    else window.addEventListener('load', function () { ScrollTrigger.refresh(); }, { once: true });
  }

  /* --------------------------------------------------- Lay + Hướng sáng --
     Một ScrollTrigger phủ cả trang đọc hai thứ: tiến độ trang (nắng, 0 -> 1,
     cùng nghĩa với --sc-sun) và vận tốc cuộn. Mỗi lá đăng ký qua lay.add():
       góc = base + (nắng - 0.5) * sunTilt + k * độ lay theo vận tốc
     Lá nghiêng về phía nắng; cuộn nhanh thì lá bị kéo lệch, dừng tay 0.14s
     thì lắng về góc theo nắng bằng elastic nhỏ. Chỉ một listener cho mọi lá. */
  function createLay() {
    var items = [];
    var sun = 0.5;
    var settle = null;

    function target(it, v) { return it.base + (sun - 0.5) * it.sunTilt + it.k * v; }
    function push(v) { for (var i = 0; i < items.length; i++) items[i].to(target(items[i], v)); }

    var st = ScrollTrigger.create({
      start: 0,
      end: 'max',
      onUpdate: function (self) {
        sun = self.progress;
        push(gsap.utils.clamp(-10, 10, self.getVelocity() / -160));
        if (settle) settle.kill();
        settle = gsap.delayedCall(0.14, function () { push(0); });
      }
    });

    return {
      add: function (el, opts) {
        var it = {
          base: opts.base || 0,
          k: opts.k == null ? 1 : opts.k,
          sunTilt: opts.sunTilt == null ? 8 : opts.sunTilt,
          to: gsap.quickTo(el, 'rotation', { duration: 1.4, ease: M.settle })
        };
        sun = st.progress;
        gsap.set(el, { rotation: target(it, 0) });
        items.push(it);
      },
      kill: function () { if (settle) settle.kill(); }
    };
  }

  /* ------------------------------------------------------------ Vén lá --
     Gate-garden: .gg-leaf cắm cuống ở mép khung, CSS vẽ sẵn thế ĐÃ VÉN
     (data-rot). Ở đây kéo lá về thế che kín (data-from) rồi để cuộn vén
     dần. ScrollCraft vẫn ghim khung (sticky); trigger này chỉ đo cùng quãng
     ghim, không pin thêm. Thứ tự vén: lá gần trước, lá xa sau — giống tay
     gạt lớp lá ngoài cùng trước. Vén xong trước p ~ 0.42, đúng lúc chữ
     (cue 0.40) bắt đầu hiện. Điện thoại quãng ghim ngắn (span 1.2), nên bắt
     đầu vén sớm hơn, từ lúc khung còn đang trượt lên. */
  function gardenLeaves(root, lay) {
    var gate = root.querySelector('.sc-gate--garden');
    if (!gate) return;
    var leaves = gsap.utils.toArray(gate.querySelectorAll('.gg-leaf'));
    if (!leaves.length) return;
    var photo = gate.querySelector('.sc-gate__frame img');

    var tl = gsap.timeline({
      defaults: { ease: M.sway },
      scrollTrigger: {
        trigger: gate,
        start: function () { return window.innerWidth <= 860 ? 'top 35%' : 'top top'; },
        end: 'bottom bottom',
        scrub: M.scrub,
        invalidateOnRefresh: true
      }
    });

    if (photo) tl.fromTo(photo, { scale: 1.1 }, { scale: 1, duration: 0.45, ease: 'power1.out' }, 0);

    leaves.forEach(function (leaf) {
      var d = parseFloat(leaf.dataset.depth) || 1;
      tl.fromTo(leaf,
        { rotation: parseFloat(leaf.dataset.from), scale: 1 + 0.16 * d },
        { rotation: parseFloat(leaf.dataset.rot), scale: 1, duration: 0.3 },
        0.03 + 0.09 * (1 - d) / 0.6);
      var sway = leaf.querySelector('.gg-leaf__sway');
      if (sway) lay.add(sway, { k: 0.3 + 0.5 * d, sunTilt: 4 });
    });

    // Giữ tổng độ dài = 1 để vị trí trên timeline khớp tiến độ ghim.
    tl.set({}, {}, 1);
  }

  /* ---------------------------------------------------------- Thân dây --
     Khối cam kết: [data-vine] > .sc-rule__line + .sc-rule__leaf. Thân dài dần
     theo cuộn (scrub, có độ trễ), lá đi theo ngọn: nhú ra từ cuống ở đầu thân
     rồi được ngọn mang tới cuối. Ảnh lá bên trong đăng ký Lay để lay theo cuộn
     và nghiêng theo nắng — tách hai lớp để scrub (wrapper) và lay (ảnh) không
     tranh nhau cùng một thuộc tính rotation. */
  function vines(root, lay) {
    gsap.utils.toArray(root.querySelectorAll('[data-vine]')).forEach(function (rule) {
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
  function growGrids(root) {
    var cards = gsap.utils.toArray(root.querySelectorAll('[data-grow] > *'));
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
