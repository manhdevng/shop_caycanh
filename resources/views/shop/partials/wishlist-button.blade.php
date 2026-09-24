{{--
    Nút trái tim yêu thích dùng chung cho thẻ sản phẩm (shop/index.blade.php)
    và trang chi tiết (shop/show.blade.php).
    Biến bắt buộc:
    - $product: instance App\Models\Product
    - $wishlistedIds: mảng int (có thể rỗng []) — id các sản phẩm user hiện tại
      đã yêu thích, tính 1 lần ở trang cha để tránh N+1 (xem shop/index.blade.php).

    Yêu cầu đặt trong 1 thẻ cha có position:relative (vd: div bọc ngoài ảnh sản
    phẩm) — nút tự định vị absolute góc dưới-phải để không đè lên nhãn "Mới"/
    "Hết hàng" vốn đã chiếm 2 góc trên.
    Cần hàm JS toggleWishlist(productId, btn) đã khai báo ở trang cha (script
    trong shop/index.blade.php hoặc @push('scripts') trong shop/show.blade.php).
--}}
@php
    $isWishlisted = in_array($product->id, $wishlistedIds ?? []);
@endphp
<button type="button"
        class="wishlist-toggle-btn"
        data-product-id="{{ $product->id }}"
        onclick="toggleWishlist({{ $product->id }}, this)"
        aria-label="{{ $isWishlisted ? 'Bỏ yêu thích' : 'Yêu thích' }} {{ $product->name }}"
        style="position:absolute;bottom:10px;right:10px;width:34px;height:34px;border-radius:999px;background:#FFFFFF;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.12);z-index:2">
    <svg class="wishlist-heart-icon" width="17" height="17" viewBox="0 0 24 24" fill="{{ $isWishlisted ? '#5C2323' : 'none' }}" stroke="#5C2323" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></svg>
</button>
