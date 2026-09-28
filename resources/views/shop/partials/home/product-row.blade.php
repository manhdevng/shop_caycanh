{{--
    Một HÀNG sản phẩm trên trang chủ. Dùng chung cho cả bốn khối mua sắm (cây
    nổi bật, cây trong nhà, cây sân vườn, hoa) thay cho bốn bản chép tay gần
    giống nhau trước đây.

    Tham số:
      $rowProducts   (bắt buộc) Collection Product — rỗng thì cả khối tự ẩn
      $rowTitle      (bắt buộc) tiêu đề
      $rowKicker     dòng nhãn nhỏ phía trên tiêu đề
      $rowNote       một dòng giải thích dưới tiêu đề
      $rowHref       + $rowLinkText: nút "Xem tất cả ..." (bỏ trống thì không vẽ)
      $rowId         id để neo anchor
      $rowBg         màu nền khối (mặc định trắng)
      $rowRanked     true -> đánh số #1..#N lên ảnh (khối bán chạy)
      $rowSoldCounts mảng product_id => số đã bán

    Lưới: 2 cột ở mobile; desktop mở tối đa 4 cột nhưng không bao giờ nhiều hơn
    số sản phẩm thật, để hàng chỉ có 2 cây không bị kéo giãn thành hai thẻ
    khổng lồ.
--}}
@php
    $rowKicker = $rowKicker ?? null;
    $rowNote = $rowNote ?? null;
    $rowHref = $rowHref ?? null;
    $rowLinkText = $rowLinkText ?? null;
    $rowId = $rowId ?? null;
    $rowBg = $rowBg ?? '#FFFFFF';
    $rowRanked = $rowRanked ?? false;
    $rowSoldCounts = $rowSoldCounts ?? [];
    $rowCols = max(2, min(4, $rowProducts->count()));
@endphp
@if($rowProducts->isNotEmpty())
<section @if($rowId) id="{{ $rowId }}" @endif class="sc-row" style="background:{{ $rowBg }}">
    <div class="sc-row__inner">
        <div class="sc-row__head">
            <div>
                @if($rowKicker)
                    <p class="sc-row__kicker">{{ $rowKicker }}</p>
                @endif
                <h2 class="sc-row__title">{{ $rowTitle }}</h2>
                @if($rowNote)
                    <p class="sc-row__note">{{ $rowNote }}</p>
                @endif
            </div>
            @if($rowHref && $rowLinkText)
                <a href="{{ $rowHref }}" class="sc-home__view-all">{{ $rowLinkText }} <span aria-hidden="true">&rarr;</span></a>
            @endif
        </div>

        <div data-grow class="sc-row__grid" style="--sc-row-cols:{{ $rowCols }}">
            @foreach($rowProducts as $rowProduct)
                @include('shop.partials.product-card', [
                    'product' => $rowProduct,
                    'bestSellerIds' => $bestSellerIds ?? [],
                    'wishlistedIds' => $wishlistedIds ?? [],
                    'rank' => $rowRanked ? $loop->iteration : null,
                    'soldCount' => $rowSoldCounts[$rowProduct->id] ?? null,
                ])
            @endforeach
        </div>
    </div>
</section>
@endif

@once
<style>
.sc-home .sc-row__inner { max-width: 1400px; margin: 0 auto; padding: clamp(36px, 5vw, 64px) var(--sc-gutter, 24px); }
.sc-home .sc-row__head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 26px; }
.sc-home .sc-row__kicker { margin: 0 0 10px; font-family: 'Space Mono', monospace; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #5C2323; }
.sc-home .sc-row__title { margin: 0; font-family: 'Anton', sans-serif; font-size: clamp(22px, 3vw, 30px); letter-spacing: .01em; text-transform: uppercase; color: #1C1C1A; }
.sc-home .sc-row__note { margin: 8px 0 0; font-size: 14px; color: #6B6B66; }
.sc-home .sc-row__grid { display: grid; grid-template-columns: repeat(var(--sc-row-cols, 4), minmax(0, 1fr)); gap: 32px 24px; }
@media (max-width: 860px) {
    .sc-home .sc-row__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px 16px; }
}
</style>
@endonce
