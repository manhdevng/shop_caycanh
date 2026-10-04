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
            <a href="{{ \App\Http\Controllers\ShopController::catalogUrl(['type' => 'plant']) }}" class="sc-home__view-all sc-home__view-all--filled">Xem tất cả cây cảnh <span aria-hidden="true">&rarr;</span></a>
            <a href="{{ \App\Http\Controllers\ShopController::catalogUrl(['type' => 'flower']) }}" class="sc-home__view-all">Xem tất cả hoa <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</section>

<style>
.sc-home__view-all {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 42px;
    padding: 10px 15px;
    border: 1px solid #5C2323;
    border-radius: 999px;
    color: #5C2323;
    background: #FFFFFF;
    font-family: 'Space Mono', monospace;
    font-size: 10px;
    line-height: 1.3;
    letter-spacing: .05em;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
    transition: color .2s ease, background .2s ease, border-color .2s ease, transform .2s ease;
}
.sc-home__view-all:hover { color: #FFFFFF; background: #5C2323; transform: translateY(-1px); }
.sc-home__view-all:focus-visible { outline: 3px solid #B88A62; outline-offset: 3px; }
.sc-home__view-all--filled { color: #FFFFFF; background: #5C2323; }
.sc-home__view-all--filled:hover { color: #FFFFFF; background: #3F1717; border-color: #3F1717; }
.sc-home__view-all--inverse { color: #5C2323; background: #FFFFFF; border-color: #FFFFFF; }
.sc-home__view-all--inverse:hover { color: #FFFFFF; background: #5C2323; border-color: #5C2323; }
@media (prefers-reduced-motion: reduce) {
    .sc-home__view-all { transition: none; }
    .sc-home__view-all:hover { transform: none; }
}
/* Điện thoại: hai nút xếp dọc, rộng hết khung — dễ bấm hơn trên màn hình hẹp. */
@media (max-width: 640px) {
    .cta-all-buttons { flex-direction: column; align-items: stretch; }
    .cta-all-buttons a { text-align: center; }
}
</style>
