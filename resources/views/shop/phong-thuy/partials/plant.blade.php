{{--
    Một "mẫu cây" trong lưới Cây hợp mệnh: ảnh 4:5 thật từ CSDL, tên, giá, một link
    hành động. Không thẻ, không badge (phong-thuy-la-ban-style.md mục 6.6).

    Tham số: $product (Product, nên eager-load variants), $lead (bool) cây đầu
    nhóm, chiếm hai cột.

    Nút mua theo quyền và loại cây (00-doc-truoc-khi-giao.md, quyết định 3):
    hết hàng -> chữ; có biến thể -> trang chi tiết; không có giá -> trang chi
    tiết; khách vãng lai -> đăng nhập; chưa xác minh -> xác minh email; còn
    lại -> form POST cart.add (JS chặn để thêm tại chỗ, tắt JS vẫn chạy).
--}}
@php
    $lead = $lead ?? false;
    $showUrl = route('shop.show', $product->id);
    if ($product->hasPriceRange()) {
        $price = 'Từ ' . number_format($product->variants->min('price'), 0, ',', '.') . 'đ';
    } elseif ($product->variants->isNotEmpty() && $product->variants->min('price') > 0) {
        $price = number_format($product->variants->min('price'), 0, ',', '.') . 'đ';
    } elseif ($product->base_price > 0) {
        $price = number_format($product->base_price, 0, ',', '.') . 'đ';
    } else {
        $price = null;
    }
    $user = auth()->user();
@endphp
<article class="cpt-plant{{ $lead ? ' cpt-plant--lead' : '' }}">
    <a href="{{ $showUrl }}" class="cpt-plant__media" tabindex="-1" aria-hidden="true">
        <img src="{{ $product->main_image ? asset('storage/' . $product->main_image) : asset('images/product-fallback.svg') }}" alt="" loading="lazy" decoding="async"
             onerror="this.onerror=null;this.src='{{ asset('images/product-fallback.svg') }}'">
    </a>
    <h3 class="cpt-plant__name"><a href="{{ $showUrl }}">{{ $product->name }}</a></h3>
    <p class="cpt-plant__price">{{ $price ?? 'Liên hệ giá' }}</p>
    @if(! $product->in_stock)
        <p class="cpt-plant__note">Hết hàng</p>
    @elseif($product->variants->isNotEmpty())
        <a href="{{ $showUrl }}" class="cpt-link">Chọn {{ \Illuminate\Support\Str::lower($product->effective_variant_label) }}</a>
    @elseif($product->base_price <= 0)
        <a href="{{ $showUrl }}" class="cpt-link">Xem chi tiết</a>
    @elseif(! $user)
        <a href="{{ route('login') }}" class="cpt-link" aria-label="Đăng nhập để mua {{ $product->name }}">Đăng nhập để mua</a>
    @elseif(! $user->hasVerifiedEmail())
        <a href="{{ route('verification.notice') }}" class="cpt-link" aria-label="Xác minh email để mua {{ $product->name }}">Xác minh email để mua</a>
    @else
        <form method="POST" action="{{ route('cart.add', $product->id) }}" class="cpt-plant__form" data-cpt-add>
            @csrf
            <button type="submit" class="cpt-link" aria-label="Thêm {{ $product->name }} vào giỏ">Thêm vào giỏ</button>
        </form>
    @endif
</article>
