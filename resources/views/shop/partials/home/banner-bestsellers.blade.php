{{-- Dải CTA "Sản phẩm bán chạy" — chỉ ở trang chủ mặc định (không lọc/tìm kiếm).
     Controller index() không truyền $products bán chạy/$soldCounts nên không thể
     vẽ lưới sản phẩm ở đây; dùng banner dẫn sang route riêng shop.bestSellers. --}}
<section data-sc-act="flow" style="max-width:1400px;margin:0 auto;padding:clamp(56px,8vw,100px) 24px 0">
    <div style="background:#F7F4EF;border-radius:20px;padding:clamp(32px,5vw,52px);display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap">
        <div style="max-width:560px">
            <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#5C2323;margin:0 0 12px">Được yêu thích nhất</p>
            <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,3vw,30px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 12px">Sản phẩm bán chạy</h2>
            <p style="font-size:14px;line-height:1.6;color:#6B6B66;margin:0">Khám phá những mẫu cây và hoa được khách hàng mua nhiều nhất trong 30 ngày gần đây.</p>
        </div>
        <a href="{{ route('shop.bestSellers') }}" style="display:inline-block;flex:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:16px 32px;border-radius:999px;white-space:nowrap">Xem sản phẩm bán chạy &rarr;</a>
    </div>
</section>
