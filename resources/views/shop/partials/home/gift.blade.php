<!-- KHÔNG được thêm lại overflow:hidden vào <section id="giftSection"> bên dưới.
     Lý do: section này có data-sc-act="pin" + data-sc-span="1.6", nghĩa là
     [data-sc-stage] bên trong phải dùng position:sticky để "dính" lại màn hình
     trong suốt chiều cao span (1.6 * 100vh) rồi mới nhả ra. Đặt overflow:hidden
     (hay overflow:auto/scroll/clip) lên chính section cha biến nó thành một
     "scroll container" mới — mà sticky chỉ hoạt động so với scroll container
     GẦN NHẤT của nó. Kết quả: stage dính vào chính section 1440px cao thay vì
     dính vào viewport, nên nó không bao giờ thực sự pin — nó chỉ nằm y nguyên
     ở đầu section (cao 900px) và toàn bộ phần span còn lại (~539px ở 1440x900)
     biến thành một dải nền trắng trống trơn ngay trước "Câu hỏi thường gặp".
     Đã đo và xác nhận: gỡ overflow:hidden khỏi section thì stage lấp đầy đúng
     viewport khi cuộn qua, không có gì tràn ra ngoài vì [data-sc-stage] đã tự
     mang overflow:clip qua class .sc-stage (engine gắn lúc mount, khai trong
     scrollcraft-shop.css) — hai ảnh/panel quà tặng vẫn bị cắt gọn trong khung
     như thiết kế. Nếu sau này thấy overflow:hidden "thừa" và định dọn dẹp:
     đừng — đó chính là nguyên nhân của lỗi khoảng trắng, không phải phần thừa. -->
<section id="giftSection" data-sc-act="pin" data-sc-span="1.6" data-sc-span-mobile="1.15" data-sc-span-reduced="1.1" style="border-top:1px solid #1C1C1A">
    <div data-sc-stage style="display:flex;flex-wrap:wrap">
        <div class="sc-gift__panel sc-gift__panel--text" style="background:#F7F4EF;position:relative;overflow:hidden">
            <div class="gift-text" data-gift-text="0" data-sc-cue="0 0.52 0" style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:clamp(40px,6vw,72px);padding-top:calc(var(--sc-safe-top, 76px) + 24px)">
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#6B6B66;margin:0 0 18px">Quà tặng ý nghĩa</p>
                <h2 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);line-height:1.35;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 28px">Được thiết kế tỉ mỉ<br>Ấn tượng ngay khi mở ra</h2>
                <a href="{{ route('shop.index') }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#5C2323">Xem quà tặng &rarr;</a>
            </div>
            <div class="gift-text" data-gift-text="1" data-sc-cue="0.42" style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:clamp(40px,6vw,72px);padding-top:calc(var(--sc-safe-top, 76px) + 24px)">
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#6B6B66;margin:0 0 18px">Điểm nhấn không gian sống</p>
                <h2 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);line-height:1.35;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 28px">Nhỏ gọn, tinh tế<br>Tươi mới mọi góc bàn</h2>
                <a href="{{ route('shop.index') }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#5C2323">Xem quà tặng &rarr;</a>
            </div>
        </div>
        <div class="sc-gift__panel sc-gift__panel--img" style="position:relative;overflow:hidden">
            <img class="gift-img" data-gift-img="0" data-sc-cue="0 0.52 0" src="{{ asset('images/gift-plant.jpg') }}" alt="Quà tặng ý nghĩa - cây cảnh gói quà" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block">
            <img class="gift-img" data-gift-img="1" data-sc-cue="0.42" src="{{ asset('images/gift-table.jpg') }}" alt="Quà tặng ý nghĩa - chậu cây bàn làm việc" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block">
        </div>
    </div>
</section>

<style>
/* Khối I — khối kết. Chiều dài của .sc-stage giờ do class .sc-stage (JS thêm
   khi mount) quyết định: sticky, 100vh. Hai div panel bên dưới chỉ còn khai tỉ
   lệ flex — chiều cao thật lấy từ stage cha (align-items:stretch mặc định). */
.sc-home #giftSection [data-sc-stage] { min-height: clamp(480px, 55vw, 640px); }
.sc-home #giftSection .sc-gift__panel--text { flex: 2 1 320px; min-height: 420px; }
.sc-home #giftSection .sc-gift__panel--img { flex: 3 1 480px; min-height: 420px; }

/* Hai trạng thái quà tặng do cuộn điều khiển: engine ghi opacity qua
   data-sc-cue (không còn transition/translateX thủ công — tránh tranh
   transform với cue và tránh độ trễ nhìn thấy được từ `transition`).

   Nút "Xem quà tặng" của panel đang mờ KHÔNG bấm được: việc này do CHÍNH
   ENGINE lo, nó ghi thẳng `style.pointerEvents` lên mỗi phần tử có cue theo
   trạng thái hiện/ẩn thật (scrollcraft.js dòng 794, 886, 933). Vì là inline
   style nên nó thắng mọi quy tắc trong file này. Bản dựng đầu có thêm một cơ
   chế `.is-live` tự viết (nghe scroll + rAF + ngưỡng 0.47) để làm đúng việc
   đó — thừa và chậm hơn: đo thật thấy `pointer-events` của engine bám theo
   opacity chính xác ở mọi mốc, còn `.is-live` trễ một nhịp ở p≈0.60. Đã gỡ,
   bớt luôn một listener scroll và một vòng rAF khỏi trang. */

/* Dự phòng khi engine KHÔNG chạy (JS bị chặn, file 404, mount() văng lỗi).
   Hai trạng thái được thiết kế để xếp chồng và chéo mờ, nên `position:absolute;
   inset:0` khai vô điều kiện. Quy tắc ẩn cue ([data-sc-cue]{opacity:0}) lại nằm
   sau cổng .sc-engine-on — cố ý, vì lưới sản phẩm không được phép biến mất khi
   JS hỏng. Hệ quả: không có engine thì cả hai trạng thái cùng hiện ở opacity 1,
   trùng khít toạ độ, hai tiêu đề Anton in đè lên nhau thành chữ không đọc được.
   Không có engine -> quay về đúng một bố cục sạch: chỉ trạng thái 0, bấm được. */
.sc-home:not(.sc-engine-on) #giftSection [data-gift-text="1"],
.sc-home:not(.sc-engine-on) #giftSection [data-gift-img="1"] { display: none !important; }

/* Điện thoại ≤860px: bố cục riêng — xếp dọc thay vì bọc flex-wrap tràn cột,
   để hai tấm cùng vừa trong một .sc-stage cao 100svh, không tấm nào bị
   overflow:clip cắt mất.

   GATE BẮT BUỘC sau .sc-engine-on: bố cục dọc này chỉ đúng khi JS đã gắn
   class .sc-stage cho [data-sc-stage] (mount() chạy được) -> stage có
   height:100vh cố định nên flex:0 0 42% phân giải được. Không có engine thì
   [data-sc-stage] không có chiều cao ép buộc nào; nếu min-height:0 áp dụng ở
   đây, hai panel con (đều position:absolute, không đóng góp chiều cao) làm
   cả khối co về 0 — khối I biến mất trên điện thoại đúng lúc JS hỏng, phản
   lại nguyên tắc "JS hỏng thì trang vẫn bán được hàng". Không có engine ->
   giữ nguyên min-height 420px/420px + flex-wrap mặc định ở trên, khối I cao
   ít nhất ~840px (hai tấm xếp chồng theo wrap tự nhiên), đọc được, bấm được. */
@media (max-width: 860px) {
    .sc-home.sc-engine-on #giftSection [data-sc-stage] { flex-wrap: nowrap; flex-direction: column; min-height: 0; }
    .sc-home.sc-engine-on #giftSection .sc-gift__panel--text { flex: 0 0 42%; min-height: 0; }
    .sc-home.sc-engine-on #giftSection .sc-gift__panel--img { flex: 1 1 auto; min-height: 0; }
}
</style>
