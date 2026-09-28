{{--
    Thẻ sản phẩm dùng CHUNG cho mọi nơi hiển thị lưới hàng: trang chủ (cây nổi
    bật, cây trong nhà, cây sân vườn, hoa), trang danh mục/tìm kiếm, trang bán
    chạy, sản phẩm liên quan.

    Trước đây mỗi nơi tự chép lại cùng một đoạn markup (5 bản), nên một lỗi
    phải sửa 5 lần — và thực tế đã có lỗi: KHÔNG chỗ nào kiểm tra `in_stock`
    trước khi vẽ nút "Thêm vào giỏ", nên hàng hết vẫn mời khách bỏ vào giỏ y
    như hàng còn. Gom về một file để trạng thái nút chỉ còn một nguồn đúng.

    Tham số:
      $product        (bắt buộc) Product, nên đã eager-load categories + variants
      $bestSellerIds  (mảng, mặc định []) để tính nhãn "Bán chạy" tự động
      $wishlistedIds  (mảng, mặc định []) id sản phẩm user đã thích
      $rank           (int|null) số thứ hạng hiện ở góc trên-trái (khối bán chạy)
      $soldCount      (int|null) số đã bán, chỉ hiện khi > 0

    Bản đồ bốn góc của ảnh (đừng thêm nhãn chồng lên các vị trí đã có chủ):
      trên-trái  : thứ hạng #1..#N (nếu có $rank)
      trên-phải  : badge dùng chung (Mới / Bán chạy / Quà tặng...)
      dưới-trái  : "Hết hàng"
      dưới-phải  : nút yêu thích
--}}
@php
    $bestSellerIds = $bestSellerIds ?? [];
    $wishlistedIds = $wishlistedIds ?? [];
    $rank = $rank ?? null;
    $soldCount = $soldCount ?? null;

    // Giá hiển thị: có >=2 phân loại khác giá -> "Từ X₫"; ngược lại base_price.
    // base_price = 0 -> null, nơi hiển thị đổi thành "Liên hệ giá".
    if ($product->hasPriceRange()) {
        $cardPrice = 'Từ ' . number_format($product->variants->min('price'), 0, ',', '.') . '₫';
    } elseif ($product->base_price > 0) {
        $cardPrice = number_format($product->base_price, 0, ',', '.') . '₫';
    } else {
        $cardPrice = null;
    }

    // Một dòng nhận diện ngắn: số lựa chọn nếu có phân loại, nếu không thì
    // danh mục đầu tiên. Không bịa thêm thông số chăm cây — model chưa có.
    $variantCount = $product->variants->count();
    $cardSpec = $variantCount > 0
        ? $variantCount . ' lựa chọn ' . \Illuminate\Support\Str::lower($product->effective_variant_label)
        : optional($product->categories->first())->name;

    $cardBtn = 'display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;font-family:\'Space Mono\',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase';
@endphp
<div class="sc-card">
    <div style="position:relative">
        <a href="{{ route('shop.show', $product->id) }}" class="sc-leaf sc-card__media" style="position:relative;display:block;aspect-ratio:1/1;overflow:hidden;background:#F7F4EF">
            @if($rank)
                <span style="position:absolute;top:10px;left:10px;background:#1C1C1A;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;padding:4px 9px;border-radius:999px;z-index:1">#{{ $rank }}</span>
            @endif
            @include('shop.partials.badge', ['product' => $product, 'bestSellerIds' => $rank ? [] : $bestSellerIds])
            @unless($product->in_stock)
                <span style="position:absolute;bottom:10px;left:10px;background:#6B7280;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;padding:4px 9px;border-radius:3px;z-index:1">Hết hàng</span>
            @endunless
            @if($product->main_image)
                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}"
                     loading="lazy" decoding="async"
                     style="width:100%;height:100%;object-fit:cover;display:block">
            @else
                <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                    <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $product->name }}</span>
                </div>
            @endif
        </a>
        @include('shop.partials.wishlist-button', ['product' => $product, 'wishlistedIds' => $wishlistedIds])
    </div>

    <a href="{{ route('shop.show', $product->id) }}" class="sc-card__name" style="display:block;font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $product->name }}</a>
    @if($cardSpec)
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ $cardSpec }}</p>
    @endif
    @if($soldCount)
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;color:#5C2323;margin:0 0 8px">Đã bán {{ $soldCount }}</p>
    @endif
    <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 10px">
        @if($cardPrice)
            {{ $cardPrice }}
        @else
            <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
        @endif
    </p>

    {{-- Thứ tự kiểm tra có chủ ý: hết hàng chặn TRƯỚC mọi nhánh mua, vì sản
         phẩm hết hàng mà vẫn hiện "Thêm vào giỏ"/"Chọn kích cỡ" là mời khách
         vào một luồng sẽ hỏng ở bước sau. --}}
    @unless($product->in_stock)
        <span style="{{ $cardBtn }};background:#F1F0EC;color:#8A8680;border:1px solid #E5E2DC;cursor:not-allowed">Hết hàng</span>
    @elseif($product->variants->isNotEmpty())
        <a href="{{ route('shop.show', $product->id) }}" style="{{ $cardBtn }};background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A">Chọn {{ $product->effective_variant_label }}</a>
    @elseif($product->base_price <= 0)
        <a href="{{ route('shop.show', $product->id) }}" style="{{ $cardBtn }};background:#FFFFFF;color:#8A8680;border:1px solid #E5E2DC">Liên hệ</a>
    @else
        <button type="button" onclick="addToCart({{ $product->id }}, this)" style="{{ $cardBtn }};background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;cursor:pointer">Thêm vào giỏ</button>
    @endunless
</div>
