{{-- Khối I — Quà tặng. Cảnh ghim thứ ba (cuối) của trang.
     Hai cảnh xếp chồng: "Quà tặng ý nghĩa" rồi "Điểm nhấn không gian sống".
     Có chuyển động (.sc-home.motion-on): khối cao một màn hình, được GSAP ghim;
     ảnh cảnh 2 MỌC từ đáy lên phủ ảnh cảnh 1 (cùng động từ "Mọc" với lưới sản
     phẩm), chữ cảnh 1 rời lên trên, chữ cảnh 2 nhô lên thay chỗ (home-motion.js,
     hàm giftScene). Không chuyển động: chỉ hiện cảnh 1, sạch, bấm được. --}}
<section id="giftSection" style="border-top:1px solid #1C1C1A">
    <div class="gift-stage">
        <div class="sc-gift__panel sc-gift__panel--text" style="background:#F7F4EF;position:relative;overflow:hidden">
            <div class="gift-text" data-gift-text="0" style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:clamp(40px,6vw,72px);padding-top:calc(var(--sc-safe-top, 76px) + 24px)">
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#6B6B66;margin:0 0 18px">Quà tặng ý nghĩa</p>
                <h2 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);line-height:1.35;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 28px">Được thiết kế tỉ mỉ<br>Ấn tượng ngay khi mở ra</h2>
                <a href="{{ route('shop.index') }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#5C2323">Xem quà tặng &rarr;</a>
            </div>
            <div class="gift-text" data-gift-text="1" style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:clamp(40px,6vw,72px);padding-top:calc(var(--sc-safe-top, 76px) + 24px)">
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#6B6B66;margin:0 0 18px">Điểm nhấn không gian sống</p>
                <h2 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);line-height:1.35;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 28px">Nhỏ gọn, tinh tế<br>Tươi mới mọi góc bàn</h2>
                <a href="{{ route('shop.index') }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#5C2323">Xem quà tặng &rarr;</a>
            </div>
        </div>
        <div class="sc-gift__panel sc-gift__panel--img" style="position:relative;overflow:hidden">
            <img class="gift-img" data-gift-img="0" src="{{ asset('images/gift-plant.jpg') }}" alt="Quà tặng ý nghĩa - cây cảnh gói quà" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block">
            <img class="gift-img" data-gift-img="1" src="{{ asset('images/gift-table.jpg') }}" alt="Quà tặng ý nghĩa - chậu cây bàn làm việc" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block">
        </div>
    </div>
</section>

<style>
/* Tĩnh: hai panel bọc flex-wrap, cao tối thiểu theo nội dung. */
.sc-home #giftSection .gift-stage { display: flex; flex-wrap: wrap; min-height: clamp(480px, 55vw, 640px); }
.sc-home #giftSection .sc-gift__panel--text { flex: 2 1 320px; min-height: 420px; }
.sc-home #giftSection .sc-gift__panel--img { flex: 3 1 480px; min-height: 420px; }

/* Không có chuyển động (JS hỏng / giảm chuyển động): hai cảnh vốn xếp chồng
   tuyệt đối, hiện cả hai sẽ in đè hai tiêu đề lên nhau -> chỉ giữ cảnh 1. */
.sc-home:not(.motion-on) #giftSection [data-gift-text="1"],
.sc-home:not(.motion-on) #giftSection [data-gift-img="1"] { display: none !important; }

/* Có chuyển động: stage cao đúng một màn hình để ghim. Khung chữ của cả hai
   cảnh phủ kín panel, nên khung của cảnh đang ẩn sẽ chặn cú bấm vào cảnh bên
   dưới -> khung không nhận chuột, chỉ con bên trong nhận (con đang ẩn có
   visibility:hidden do autoAlpha nên cũng không nhận). */
.sc-home.motion-on #giftSection .gift-stage { height: 100vh; height: 100svh; min-height: 0; flex-wrap: nowrap; overflow: hidden; }
.sc-home.motion-on #giftSection .gift-text { pointer-events: none; }
.sc-home.motion-on #giftSection .gift-text > * { pointer-events: auto; }
.sc-home.motion-on #giftSection .gift-img { will-change: transform; clip-path: inset(var(--ct, 0%) 0% 0% 0%); }

/* Điện thoại: xếp dọc trong một màn hình — chữ 42% trên, ảnh phần còn lại. */
@media (max-width: 860px) {
    .sc-home.motion-on #giftSection .gift-stage { flex-direction: column; }
    .sc-home.motion-on #giftSection .sc-gift__panel--text { flex: 0 0 42%; min-height: 0; }
    .sc-home.motion-on #giftSection .sc-gift__panel--img { flex: 1 1 auto; min-height: 0; }
}
</style>
