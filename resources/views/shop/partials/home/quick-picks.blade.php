{{--
    Khối B — Lối chọn nhanh, nằm ngay dưới hero.

    Mục đích: sau khi biết đây bán cây, khách chọn được ngay "cây cho chỗ nào"
    mà không phải mở menu. $quickPicks do ShopController dựng từ danh mục THẬT
    và chỉ giữ nhóm còn hàng (xem ghi chú ở đó) — nhóm rỗng bị loại hẳn nên
    không có thẻ nào bấm vào ra trang trống.

    Ảnh thẻ mượn ảnh của một sản phẩm thật thuộc đúng nhóm đó: các danh mục
    không gian chưa có ảnh riêng, và dùng ảnh minh hoạ chung sẽ là gán ảnh cho
    thứ không tồn tại.

    Lưới 4 cột desktop / 2 cột mobile — không dùng carousel tự chạy, để khách
    không phải chờ đúng thẻ trôi qua và để đi bằng bàn phím được ngay.
--}}
@if($quickPicks->isNotEmpty())
<section class="sc-picks" aria-labelledby="quickPicksTitle">
    <div class="sc-picks__inner">
        <div class="sc-picks__head">
            <h2 id="quickPicksTitle" class="sc-picks__title">Chọn cây theo không gian</h2>
            <a href="{{ route('shop.index', ['type' => 'plant']) }}" class="sc-home__view-all">Xem tất cả cây cảnh <span aria-hidden="true">&rarr;</span></a>
        </div>

        <ul class="sc-picks__grid" data-picks>
            @foreach($quickPicks as $pick)
                <li>
                    <a href="{{ $pick['href'] }}" class="sc-pick">
                        <span class="sc-pick__media">
                            @if($pick['image'])
                                <img src="{{ asset('storage/' . $pick['image']) }}" alt="" width="300" height="300" loading="lazy" decoding="async">
                            @else
                                <span class="placeholder-pattern" style="display:block;width:100%;height:100%"></span>
                            @endif
                        </span>
                        <span class="sc-pick__body">
                            <span class="sc-pick__name">{{ $pick['label'] }}</span>
                            <span class="sc-pick__count">{{ $pick['count'] }} sản phẩm</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

<style>
.sc-home .sc-picks { background: #FFFFFF; }
.sc-home .sc-picks__inner { max-width: 1400px; margin: 0 auto; padding: clamp(32px, 4.5vw, 56px) var(--sc-gutter, 24px) clamp(8px, 2vw, 16px); }
.sc-home .sc-picks__head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.sc-home .sc-picks__title { margin: 0; font-family: 'Anton', sans-serif; font-size: clamp(20px, 2.6vw, 28px); letter-spacing: .01em; text-transform: uppercase; color: #1C1C1A; }
.sc-home .sc-picks__grid { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }

.sc-home .sc-pick { display: flex; align-items: center; gap: 14px; padding: 10px; border: 1px solid #E5E2DC; border-radius: 16px; background: #FFFFFF; transition: border-color .2s ease, box-shadow .2s ease; }
.sc-home .sc-pick:hover { border-color: #5C2323; box-shadow: 0 10px 24px -16px rgba(28,28,26,.5); color: #1C1C1A; }
.sc-home .sc-pick:focus-visible { outline: 2px solid #5C2323; outline-offset: 2px; }
.sc-home .sc-pick__media { flex: 0 0 auto; width: 64px; height: 64px; border-radius: 10px; overflow: hidden; background: #F7F4EF; }
.sc-home .sc-pick__media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sc-home .sc-pick__body { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.sc-home .sc-pick__name { font-size: 15px; font-weight: 600; color: #1C1C1A; line-height: 1.3; }
.sc-home .sc-pick__count { font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .05em; color: #6B6B66; }

/* Mobile: 2 cột nhưng GIỮ thẻ nằm ngang (ảnh nhỏ bên trái) thay vì xếp dọc
   ảnh vuông to. Xếp dọc làm dải này cao thêm ~270px, đủ để đẩy lưới hàng đầu
   tiên từ 1,26 xuống quá mốc 1,5 màn hình cuộn mà kế hoạch đặt ra. */
@media (max-width: 860px) {
    .sc-home .sc-picks__inner { padding-top: clamp(24px, 6vw, 36px); }
    .sc-home .sc-picks__head { margin-bottom: 14px; }
    .sc-home .sc-picks__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .sc-home .sc-pick { gap: 10px; padding: 8px; border-radius: 12px; }
    .sc-home .sc-pick__media { width: 52px; height: 52px; border-radius: 8px; }
    .sc-home .sc-pick__name { font-size: 13.5px; }
    .sc-home .sc-pick__count { font-size: 10px; }
}
</style>
