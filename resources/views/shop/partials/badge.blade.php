{{--
    Nhãn sản phẩm dùng chung cho mọi thẻ sản phẩm + trang chi tiết (D4, F14).
    Biến bắt buộc:
    - $product: instance App\Models\Product
    - $bestSellerIds: mảng int (có thể rỗng []) — id các sản phẩm bán chạy
      trong 30 ngày gần nhất, dùng cho chế độ nhãn Tự động.

    Style giữ nguyên y hệt nhãn "Mới" cũ từng viết tay trong shop/index.blade.php:
    nền oxblood #5C2323, chữ trắng, Space Mono, in hoa, bo góc nhỏ.
--}}
@php
    $badgeLabel = $product->displayBadge($bestSellerIds);
@endphp
@if($badgeLabel)
    <span style="position:absolute;top:10px;right:10px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;padding:4px 9px;border-radius:3px;z-index:1">{{ $badgeLabel }}</span>
@endif
