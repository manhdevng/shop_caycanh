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
      // Tạo theo thứ tự xuất hiện trên trang (trên -> dưới) để refresh đúng.
      growGrids(root);
    });

    // Ảnh/phông nạp xong làm đổi chiều cao trang -> đo lại vị trí trigger.
    if (document.readyState === 'complete') ScrollTrigger.refresh();
    else window.addEventListener('load', function () { ScrollTrigger.refresh(); }, { once: true });
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
      var pics = [], imgs = [], rest = [];
      batch.forEach(function (card) {
        var p = parts(card);
        if (p.pic) pics.push(p.pic);
        if (p.img) imgs.push(p.img);
        rest = rest.concat(p.rest);
      });
      gsap.timeline()
        .to(batch, { y: 0, duration: M.growDur, ease: M.grow, stagger: M.stagger, clearProps: 'transform' }, 0)
        .to(pics, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.0, ease: M.unfurl, stagger: M.stagger, clearProps: 'clipPath' }, 0)
        .to(imgs, { scale: 1, duration: 1.6, ease: 'power2.out', stagger: M.stagger, clearProps: 'transform' }, 0)
        .to(rest, { autoAlpha: 1, duration: 0.6, ease: 'power1.out', stagger: M.stagger / 2 }, 0.4);
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
