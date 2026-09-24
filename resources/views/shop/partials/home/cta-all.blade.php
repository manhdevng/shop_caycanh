{{-- Khối "Xem tất cả" — đứng giữa khối quà tặng (pin) và FAQ.
     Act flow, không pin. Đứng ngay sau một act "pin" (#giftSection) nên theo
     devices.md mục 8, padding-top phải giảm so với padding-bottom để tránh
     cộng dồn khoảng trống với phần đuôi của khối pin liền trước. --}}
<section style="background:#F7F4EF;padding:clamp(40px,5vw,72px) 24px clamp(64px,9vw,120px);text-align:center">
    <div style="max-width:720px;margin:0 auto">
        <p style="font-family:'Space Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:.12em;color:#5C2323;margin:0 0 16px">Chưa tìm được cây ưng ý?</p>
        <h2 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.6vw,42px);line-height:1.2;text-transform:uppercase;color:#1C1C1A;margin:0 0 18px">Cả khu vườn đang chờ bạn</h2>
        <p style="font-size:15px;line-height:1.6;color:#6B6B66;max-width:52ch;margin:0 auto 32px">Hơn cả những gì bạn vừa lướt qua. Xem toàn bộ cây cảnh và hoa đang có tại cửa hàng, lọc theo giá, loại cây và nơi đặt.</p>
        <div style="display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap" class="cta-all-buttons">
            <a href="{{ route('shop.index', ['type' => 'plant']) }}" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;text-transform:uppercase;letter-spacing:.08em;padding:16px 32px;border-radius:999px;background:#5C2323;color:#FFFFFF">Xem tất cả cây cảnh &rarr;</a>
            <a href="{{ route('shop.index', ['type' => 'flower']) }}" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;text-transform:uppercase;letter-spacing:.08em;padding:16px 32px;border-radius:999px;background:transparent;color:#1C1C1A;border:1px solid #1C1C1A">Xem tất cả hoa &rarr;</a>
        </div>
    </div>
</section>

<style>
/* Điện thoại: hai nút xếp dọc, rộng hết khung — dễ bấm hơn trên màn hình hẹp. */
@media (max-width: 640px) {
    .cta-all-buttons { flex-direction: column; align-items: stretch; }
    .cta-all-buttons a { text-align: center; }
}
</style>
