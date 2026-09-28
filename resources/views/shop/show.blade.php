@extends('layouts.shop')
@section('content')

@php
    $gallery = collect([$product->main_image])
        ->merge($product->variants->pluck('image'))
        ->filter()
        ->unique()
        ->values();
    // Phân loại đầu tiên (đã sắp theo sort_order rồi price trong quan hệ
    // Product::variants()) là lựa chọn mặc định ở trang chi tiết (D2, F8).
    $firstVariant = $product->variants->first();
    $displayPrice = $firstVariant->price ?? $product->base_price;
    $isContactPrice = $product->variants->isEmpty() && $product->base_price <= 0;
    $starsFilled = $averageRating ? (int) round($averageRating) : 0;
    $relatedProducts->loadCount('reviews')->loadAvg('reviews', 'rating');

    // Chỉ 1 sản phẩm cần kiểm tra ở trang chi tiết -> không phải N+1, chỉ 1
    // truy vấn duy nhất để tải quan hệ wishlistedProducts (nếu đã đăng nhập).
    $wishlistedIds = (auth()->check() && auth()->user()->wishlistedProducts->contains($product->id))
        ? [$product->id]
        : [];
@endphp

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    @if($product->categories->isNotEmpty())
        <a href="{{ route('shop.index', ['categories' => [$product->categories->first()->id]]) }}" style="color:#8A8680">{{ $product->categories->first()->name }}</a>
        <span>&rsaquo;</span>
    @endif
    <span style="color:#1C1C1A">{{ $product->name }}</span>
</nav>

<section class="sc-pd sc-pd--main" style="max-width:1400px;margin:0 auto;padding:24px 24px 0">

    <div class="sc-pd__gallery{{ $gallery->count() > 1 ? ' sc-pd__gallery--multi' : '' }}">
        @if($gallery->count() > 1)
        <div class="sc-pd__thumbs">
            @foreach($gallery as $image)
                <button type="button" onclick="switchMainImage('{{ asset('storage/' . $image) }}', this)" class="thumb-btn" style="width:76px;height:76px;padding:0;border-radius:8px;overflow:hidden;border:{{ $loop->first ? '2px solid #5C2323' : '1px solid #E5E2DC' }};cursor:pointer">
                    <img src="{{ asset('storage/' . $image) }}" style="width:100%;height:100%;object-fit:cover;display:block">
                </button>
            @endforeach
        </div>
        @endif

        <div>
            <div id="main-image-box" class="placeholder-pattern" style="position:relative;aspect-ratio:1/1;border-radius:10px;overflow:hidden">
                @include('shop.partials.badge', ['product' => $product, 'bestSellerIds' => $bestSellerIds])
                @if($gallery->isNotEmpty())
                    <img id="main-image" src="{{ asset('storage/' . $gallery->first()) }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                @else
                    <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:ui-monospace,Menlo,monospace;font-size:12px;color:#A8A196">{{ $product->name }}</span>
                @endif
                @include('shop.partials.wishlist-button', ['product' => $product, 'wishlistedIds' => $wishlistedIds])
            </div>
        </div>
    </div>

    <div>
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,3vw,40px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 12px">{{ $product->name }}</h1>
        <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 14px">{{ $firstVariant->variant_name ?? optional($product->categories->first())->name }}</p>
        <p style="font-size:13px;color:#6B6B66;margin:0 0 18px">
            <span style="color:#5C2323;letter-spacing:1px">{{ str_repeat('★', $starsFilled) }}{{ str_repeat('☆', 5 - $starsFilled) }}</span>
            &nbsp;
            @if($averageRating)
                {{ $averageRating }} ({{ $reviewsCount }} đánh giá)
            @else
                Chưa có đánh giá
            @endif
        </p>
        <p id="price-display" style="margin:0 0 26px;display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
            @if($displayPrice > 0)
                <span style="font-size:28px;font-weight:700;color:#1C1C1A">{{ number_format($displayPrice, 0, ',', '.') }}&#8363;</span>
                @if($product->product_type === 'plant')
                    <span style="font-size:13px;color:#8A8680">&mdash; Cây kèm chậu</span>
                @endif
            @else
                <span style="font-size:18px;color:#8A8680;font-style:italic">Liên hệ để biết giá</span>
            @endif

            @if($product->stock == 0)
                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide" style="background:#F3F4F6;color:#6B7280;border:1px solid #D1D5DB">Hết hàng</span>
            @elseif($product->stock <= 5)
                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A">Sắp hết (còn {{ $product->stock }})</span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide" style="background:#DCFCE7;color:#166534;border:1px solid #BBF7D0">Còn hàng</span>
            @endif
        </p>

        @include('partials.product-vouchers')

        @if($product->variants->isNotEmpty())
        <div style="margin-bottom:26px">
            <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin:0 0 12px">{{ $product->effective_variant_label }}:</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap" id="variant-options">
                @foreach($product->variants as $variant)
                    <button type="button" onclick="selectVariant(this, {{ $variant->id }}, {{ $variant->price }})" data-price="{{ $variant->price }}" style="min-width:56px;padding:11px 14px;border-radius:8px;background:{{ $loop->first ? '#1C1C1A' : '#FFFFFF' }};color:{{ $loop->first ? '#FFFFFF' : '#1C1C1A' }};border:1px solid {{ $loop->first ? '#1C1C1A' : '#E5E2DC' }};font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.03em;cursor:pointer" class="variant-btn">{{ $variant->variant_name }}</button>
                @endforeach
            </div>
            <input type="hidden" id="selected-variant-id" value="{{ $firstVariant->id ?? '' }}">
        </div>
        @endif

        <div style="margin-bottom:16px">
            <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin:0 0 8px">Số lượng</p>
            <div style="display:inline-flex;align-items:stretch;border:1px solid #E5E2DC;border-radius:6px;width:144px;height:48px">
                <button type="button" onclick="stepQty(-1)" aria-label="Giảm" style="flex:0 0 48px;background:none;border:none;border-right:1px solid #E5E2DC;font-size:16px;color:#1C1C1A;cursor:pointer">&minus;</button>
                <input type="number" id="qty-input" value="1" min="1" max="{{ $product->stock }}" style="flex:1 1 auto;min-width:0;border:none;text-align:center;font-family:'Inter',sans-serif;font-size:14px;color:#1C1C1A;background:transparent">
                <button type="button" onclick="stepQty(1)" aria-label="Tăng" style="flex:0 0 48px;background:none;border:none;border-left:1px solid #E5E2DC;font-size:16px;color:#1C1C1A;cursor:pointer">+</button>
            </div>
        </div>

        <div id="buy-actions" style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
            @if($isContactPrice)
                {{-- Sản phẩm liên hệ giá (base_price <= 0, không có phân loại) — không cho thêm vào giỏ (P2, F9) --}}
                <button type="button" disabled style="width:100%;padding:17px 20px;border-radius:999px;background:#E5E2DC;color:#8A8680;border:none;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;cursor:not-allowed">Liên hệ giá</button>
            @else
                <button type="button" id="add-to-cart-btn" onclick="addProductToCart({{ $product->id }})" @disabled($product->stock == 0) class="{{ $product->stock == 0 ? 'opacity-50 cursor-not-allowed' : '' }}" style="width:100%;padding:17px 20px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Thêm vào giỏ hàng</button>
                <button type="button" id="buy-now-btn" onclick="buyNow({{ $product->id }})" @disabled($product->stock == 0) class="{{ $product->stock == 0 ? 'opacity-50 cursor-not-allowed' : '' }}" style="width:100%;padding:15px 20px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Mua ngay</button>
            @endif
        </div>

        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
            <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66">Chia sẻ:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('shop.show', $product)) }}" target="_blank" rel="noopener noreferrer" aria-label="Chia sẻ sản phẩm lên Facebook" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:#FFFFFF;border:1px solid #E5E2DC;color:#1C1C1A">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z"/></svg>
            </a>
            <button type="button" id="copy-link-btn" onclick="copyProductLink()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                <span id="copy-link-text">Sao chép liên kết</span>
            </button>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:6px 18px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.02em;color:#6B6B66;margin-bottom:30px">
            <span>+ Giao hàng toàn quốc</span><span>+ Bảo hành 45 ngày</span><span>+ Đổi trả trong 3 ngày</span>
        </div>

        <div style="border-top:1px solid #E5E2DC">
            <details style="border-bottom:1px solid #E5E2DC">
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 2px;cursor:pointer;font-size:14px;font-weight:500;color:#1C1C1A;list-style:none">Mô tả sản phẩm</summary>
                <p style="font-size:13.5px;line-height:1.7;color:#6B6B66;margin:0 0 18px;padding:0 2px;white-space:pre-line">{{ $product->description ?: 'Người bán chưa cập nhật mô tả chi tiết cho sản phẩm này.' }}</p>
            </details>
            <details style="border-bottom:1px solid #E5E2DC">
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 2px;cursor:pointer;font-size:14px;font-weight:500;color:#1C1C1A;list-style:none">Vận chuyển</summary>
                <p style="font-size:13.5px;line-height:1.7;color:#6B6B66;margin:0 0 18px;padding:0 2px">Đơn hàng được đóng gói cẩn thận trong bầu đất/chậu chống sốc và giao qua đơn vị vận chuyển liên kết. Thời gian giao hàng dự kiến 2&ndash;5 ngày làm việc tùy khu vực. Phí vận chuyển được tính cụ thể ở bước thanh toán.</p>
            </details>
            <details style="border-bottom:1px solid #E5E2DC">
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 2px;cursor:pointer;font-size:14px;font-weight:500;color:#1C1C1A;list-style:none">Bảo hành</summary>
                <p style="font-size:13.5px;line-height:1.7;color:#6B6B66;margin:0 0 18px;padding:0 2px">Nếu cây bị dập, gãy hoặc hư hại do quá trình vận chuyển, vui lòng liên hệ trong vòng 3 ngày kể từ khi nhận hàng kèm hình ảnh để được đổi cây mới hoặc hoàn tiền.</p>
            </details>
        </div>
    </div>
</section>

@if($relatedProducts->isNotEmpty())
<section style="max-width:1400px;margin:0 auto;padding:clamp(64px,7vw,88px) 24px 0">
    <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,2.6vw,30px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 28px">Sản phẩm đi kèm phù hợp</h2>
    <div style="display:grid;gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @foreach($relatedProducts as $related)
            @php
                $relatedHasPriceRange = $related->hasPriceRange();
                $relatedPriceLine = $relatedHasPriceRange
                    ? 'Từ ' . number_format($related->variants->min('price'), 0, ',', '.') . '₫'
                    : ($related->base_price > 0 ? number_format($related->base_price, 0, ',', '.') . '₫' : null);
            @endphp
            <div>
                <a href="{{ route('shop.show', $related->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                    @include('shop.partials.badge', ['product' => $related, 'bestSellerIds' => $bestSellerIds])
                    @if($related->main_image)
                        <img src="{{ asset('storage/' . $related->main_image) }}" alt="{{ $related->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                    @else
                        <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                            <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $related->name }}</span>
                        </div>
                    @endif
                </a>
                <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $related->name }}</p>
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ optional($related->categories->first())->name }}</p>
                <p style="margin:0 0 14px;font-size:15px;font-weight:600;color:#1C1C1A">
                    @if($relatedPriceLine)
                        {{ $relatedPriceLine }}
                    @else
                        <span style="font-size:13px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                    @endif
                </p>
                {{-- P6/F9: sản phẩm có phân loại không được gọi addToCart(id) trực tiếp (thiếu
                     variant_id sẽ bị CartController từ chối 422) — chuyển thành link chọn phân
                     loại tại trang chi tiết; liên hệ giá thì không cho thêm vào giỏ. --}}
                @if($related->variants->isNotEmpty())
                    <a href="{{ route('shop.show', $related->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Chọn {{ $related->effective_variant_label }}</a>
                @elseif($related->base_price <= 0)
                    <a href="{{ route('shop.show', $related->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#8A8680;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Liên hệ</a>
                @else
                    <button type="button" onclick="addToCart({{ $related->id }}, this)" style="width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Thêm vào giỏ</button>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif

<div style="max-width:1400px;margin:clamp(64px,7vw,88px) auto 0;padding:0 24px">
    <div style="border-top:1px solid #E5E2DC"></div>
</div>

<section class="sc-pd sc-pd--split" style="max-width:1400px;margin:0 auto;padding:clamp(64px,7vw,88px) 24px 0">
    <div>
        <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,2.6vw,28px);line-height:1.2;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Đánh giá từ khách hàng</h2>
        <p style="font-size:15px;color:#5C2323;letter-spacing:1px;margin:0 0 10px">
            {{ str_repeat('★', $starsFilled) }}{{ str_repeat('☆', 5 - $starsFilled) }}
            <span style="font-size:13px;color:#6B6B66;letter-spacing:normal">{{ $averageRating ?? '—' }} / 5</span>
        </p>

        <div style="margin-top:24px">
            @auth
                @if($canReview)
                    <h3 style="font-size:14px;font-weight:600;color:#1C1C1A;margin:0 0 12px">{{ $myReview ? 'Sửa đánh giá của bạn' : 'Viết đánh giá của bạn' }}</h3>
                    <form action="{{ route('reviews.store', $product) }}" method="POST">
                        @csrf
                        <div style="display:flex;align-items:center;gap:4px;margin-bottom:12px" id="star-picker">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" onclick="setRating({{ $i }})" data-star="{{ $i }}" class="star-picker-btn" style="font-size:22px;line-height:1;background:none;border:none;cursor:pointer;color:{{ ($myReview->rating ?? 0) >= $i ? '#5C2323' : '#E5E2DC' }}" aria-label="{{ $i }} sao">★</button>
                            @endfor
                            <input type="hidden" name="rating" id="rating-input" value="{{ $myReview->rating ?? '' }}">
                        </div>
                        @error('rating')
                            <p style="font-size:13px;color:#B3261E;margin:0 0 10px">{{ $message }}</p>
                        @enderror
                        <textarea name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ cảm nhận của bạn về cây này (không bắt buộc)..." style="width:100%;border:1px solid #E5E2DC;border-radius:10px;padding:12px 14px;font-family:inherit;font-size:13px;margin-bottom:10px">{{ old('comment', $myReview->comment ?? '') }}</textarea>
                        @error('comment')
                            <p style="font-size:13px;color:#B3261E;margin:0 0 10px">{{ $message }}</p>
                        @enderror
                        <button type="submit" style="padding:12px 24px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">{{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
                    </form>
                @else
                    <p style="font-size:13px;color:#6B6B66;background:#F3F4F6;border-radius:10px;padding:14px 16px;margin:0">Chỉ khách đã mua sản phẩm này mới có thể đánh giá.</p>
                @endif
            @else
                <p style="font-size:13px;color:#6B6B66;background:#F3F4F6;border-radius:10px;padding:14px 16px;margin:0">Chỉ khách đã mua sản phẩm này mới có thể đánh giá. <a href="{{ route('login') }}" style="color:#5C2323;text-decoration:underline">Đăng nhập</a> để đánh giá sau khi mua hàng.</p>
            @endauth
        </div>
    </div>
    <div>
        @forelse($reviews as $review)
            <div style="border-bottom:1px solid #E5E2DC;padding:22px 0">
                <p style="font-size:13px;color:#5C2323;letter-spacing:1px;margin:0 0 10px">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:16px;flex-wrap:wrap;margin-bottom:10px">
                    <span style="font-size:14px;font-weight:600;color:#1C1C1A">{{ $review->user->name }} <span style="font-family:'Space Mono',monospace;font-size:10px;font-weight:400;color:#5C2323;text-transform:uppercase;margin-left:6px">✓ Đã mua hàng</span></span>
                    <span style="font-size:12px;color:#8A8680">{{ $review->created_at->format('d/m/Y') }}</span>
                </div>
                @if($review->comment)
                    <p style="font-size:14px;line-height:1.7;color:#4A4A46;margin:0">{{ $review->comment }}</p>
                @endif
            </div>
        @empty
            <p style="color:#8A8680;font-size:14px;font-style:italic">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên!</p>
        @endforelse
    </div>
</section>

@if($faqs->isNotEmpty())
<section class="sc-pd sc-pd--split" style="max-width:1400px;margin:0 auto;padding:clamp(64px,7vw,88px) 24px clamp(64px,8vw,96px)">
    <div>
        <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,2.6vw,28px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 18px">Câu hỏi thường gặp</h2>
        <span style="display:inline-block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;border:1px solid #E5E2DC;border-radius:999px;padding:8px 16px">Kiến thức cơ bản</span>
    </div>
    <div style="border-top:1px solid #E5E2DC">
        @foreach($faqs as $faq)
            <details style="border-bottom:1px solid #E5E2DC">
                <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 0;cursor:pointer;font-size:15px;font-weight:500;color:#1C1C1A;list-style:none">{{ $faq->question }}</summary>
                <p style="font-size:14px;line-height:1.7;color:#6B6B66;margin:0 0 20px;max-width:640px">{{ $faq->answer }}</p>
            </details>
        @endforeach
    </div>
</section>
@endif

<style>
/* Bố cục trang chi tiết — viết mobile-first.

   Trước đây ba khối này đặt grid-template-columns THẲNG trong thuộc tính
   style kèm class Tailwind "md:grid-cols-2". Inline style luôn thắng class,
   nên breakpoint không bao giờ có tác dụng: ở 390px trang vẫn chia hai cột,
   cột phải hẹp tới mức câu hỏi "Cây có kèm chậu không?" vỡ thành mỗi dòng
   một từ. Nay một cột là mặc định, hai cột chỉ bật từ 861px trở lên. */
.sc-pd { display: grid; gap: 32px; align-items: start; }
@media (min-width: 861px) {
    .sc-pd { gap: 56px; }
    .sc-pd--main { grid-template-columns: minmax(0, 1.15fr) minmax(280px, 1fr); }
    .sc-pd--split { grid-template-columns: minmax(220px, 0.8fr) minmax(0, 1.4fr); }
}

/* Thư viện ảnh: mobile để ảnh lớn lên trước, dải thumbnail cuộn ngang bên
   dưới (dải dọc 76px ở mobile ăn mất 1/5 bề ngang màn hình). */
.sc-pd__gallery { display: grid; gap: 14px; }
.sc-pd__thumbs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
.sc-pd__gallery--multi .sc-pd__thumbs { order: 2; }
.sc-pd__thumbs .thumb-btn { flex: 0 0 auto; }
@media (min-width: 861px) {
    .sc-pd__gallery--multi { grid-template-columns: 76px minmax(0, 1fr); }
    .sc-pd__gallery--multi .sc-pd__thumbs { order: 0; flex-direction: column; overflow-x: visible; }
}
</style>

{{-- ==== Thanh mua dính đáy (chỉ mobile) ====
     Trang chi tiết trên điện thoại dài (ảnh, mô tả, đánh giá, hỏi đáp, sản
     phẩm liên quan): cuộn qua nút mua gốc là khách mất luôn đường mua, phải
     vuốt ngược lên tìm. Thanh này hiện khi nút gốc rời khỏi màn hình và biến
     mất khi nút gốc quay lại — không đè lên nút thật, không nhân đôi CTA.
     Giá trong thanh bám theo phân loại đang chọn (xem selectVariant).
     Hết hàng / liên hệ giá thì không dựng thanh: không mời một luồng sẽ hỏng. --}}
@if(!$isContactPrice && $product->stock > 0)
<div id="sticky-buy" class="sc-sticky-buy" hidden>
    <div class="sc-sticky-buy__info">
        <span class="sc-sticky-buy__name">{{ $product->name }}</span>
        <span id="sticky-buy-price" class="sc-sticky-buy__price">{{ number_format($displayPrice, 0, ',', '.') }}&#8363;</span>
    </div>
    <button type="button" onclick="addProductToCart({{ $product->id }})" class="sc-sticky-buy__btn">Thêm vào giỏ</button>
</div>

<style>
.sc-sticky-buy { display: none; }
@media (max-width: 860px) {
    .sc-sticky-buy {
        position: fixed;
        left: 0; right: 0; bottom: 0;
        z-index: var(--z-chat, 200);
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px var(--sc-gutter, 16px) calc(10px + env(safe-area-inset-bottom));
        background: #FFFFFF;
        border-top: 1px solid #E5E2DC;
        box-shadow: 0 -8px 24px -18px rgba(0,0,0,.5);
    }
    .sc-sticky-buy[hidden] { display: none; }
    .sc-sticky-buy__info { display: flex; flex-direction: column; min-width: 0; flex: 1 1 auto; }
    .sc-sticky-buy__name { font-size: 12px; color: #6B6B66; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sc-sticky-buy__price { font-size: 16px; font-weight: 700; color: #1C1C1A; }
    .sc-sticky-buy__btn {
        flex: 0 0 auto; min-height: 44px; padding: 0 20px; border: none; border-radius: 999px;
        background: #5C2323; color: #FFFFFF; cursor: pointer;
        font-family: 'Space Mono', monospace; font-size: 12px; letter-spacing: .05em; text-transform: uppercase;
    }
    /* Nút chat nổi và thanh mua cùng bám đáy phải -> đẩy nút chat lên trên
       thanh, nếu không nó đè đúng chỗ nút "Thêm vào giỏ". */
    body:has(#sticky-buy:not([hidden])) #chatToggleBtn { bottom: 88px; }
}
</style>
@endif

@push('scripts')
@if(!$isContactPrice && $product->stock > 0)
<script>
    // Thanh mua đáy chỉ hiện khi khối nút mua gốc đã trôi lên QUÁ mép trên
    // màn hình. Khách chưa cuộn tới đó thì không hiện, vì lúc ấy nút thật vẫn
    // đang nằm ngay trước mắt.
    //
    // Cố tình KHÔNG dùng IntersectionObserver ở đây: observer chỉ gọi lại khi
    // trạng thái giao nhau ĐỔI, mà khối nút mua lúc tải trang nằm dưới nếp gấp
    // (không giao) và sau khi cuộn qua thì nằm trên màn hình (cũng không giao)
    // — cùng một trạng thái, nên với cú nhảy anchor hoặc trình duyệt khôi phục
    // vị trí cuộn cũ, callback không bao giờ chạy và thanh không bao giờ hiện.
    // Đo thẳng vị trí trong một listener passive + rAF thì đúng ở mọi trường hợp.
    (function () {
        var bar = document.getElementById('sticky-buy');
        var anchor = document.getElementById('buy-actions');
        if (!bar || !anchor) return;
        var queued = false;

        function apply() {
            queued = false;
            bar.hidden = anchor.getBoundingClientRect().bottom > 0;
        }
        function onScroll() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(apply);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        apply();
    })();
</script>
@endif
<script>
    // ==== Bật/tắt yêu thích trực tiếp trên trang chi tiết (không reload) ====
    function toggleWishlist(productId, btn) {
        btn.disabled = true;

        const urlTemplate = "{{ route('wishlist.toggle', ['product' => '__ID__']) }}";

        fetch(urlTemplate.replace('__ID__', productId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(res => {
                if (!res.ok) throw new Error('request-failed');
                return res.json();
            })
            .then(data => {
                btn.disabled = false;
                const icon = btn.querySelector('.wishlist-heart-icon');
                if (icon) icon.setAttribute('fill', data.liked ? '#5C2323' : 'none');
                btn.setAttribute('aria-label', (data.liked ? 'Bỏ yêu thích' : 'Yêu thích'));
                if (typeof showToast === 'function') {
                    showToast(data.message);
                }
            })
            .catch(() => {
                // Chưa đăng nhập (401) hoặc lỗi khác -> chuyển hướng đăng nhập thay vì im lặng thất bại.
                window.location.href = "{{ route('login') }}";
            });
    }

    // ==== Sao chép liên kết sản phẩm để chia sẻ ====
    function copyProductLink() {
        const link = "{{ route('shop.show', $product) }}";
        const textEl = document.getElementById('copy-link-text');
        const original = textEl ? textEl.textContent : '';

        function showCopied() {
            if (!textEl) return;
            textEl.textContent = 'Đã sao chép!';
            setTimeout(() => { textEl.textContent = original; }, 2000);
        }

        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(showCopied).catch(() => {
                    try { window.prompt('Sao chép liên kết sản phẩm:', link); } catch (e) {}
                });
            } else {
                try { window.prompt('Sao chép liên kết sản phẩm:', link); } catch (e) {}
            }
        } catch (e) {
            // Không làm vỡ trang nếu trình duyệt chặn clipboard/prompt.
        }
    }

    function switchMainImage(src, btn) {
        document.getElementById('main-image').src = src;
        document.querySelectorAll('.thumb-btn').forEach(b => b.style.border = '1px solid #E5E2DC');
        btn.style.border = '2px solid #5C2323';
    }

    function setRating(value) {
        document.getElementById('rating-input').value = value;
        document.querySelectorAll('.star-picker-btn').forEach(btn => {
            const starValue = parseInt(btn.dataset.star, 10);
            btn.style.color = starValue <= value ? '#5C2323' : '#E5E2DC';
        });
    }

    function selectVariant(btn, variantId, price) {
        document.getElementById('selected-variant-id').value = variantId;
        const priceEl = document.querySelector('#price-display span');
        if (priceEl) { priceEl.textContent = Number(price).toLocaleString('vi-VN') + '₫'; }
        // Thanh mua dính ở đáy (mobile) phải đổi giá theo đúng phân loại vừa
        // chọn — nếu không, khách chọn chậu L mà thanh dưới vẫn báo giá chậu S.
        const stickyPrice = document.getElementById('sticky-buy-price');
        if (stickyPrice) { stickyPrice.textContent = Number(price).toLocaleString('vi-VN') + '₫'; }

        document.querySelectorAll('.variant-btn').forEach(b => {
            b.style.background = '#FFFFFF';
            b.style.color = '#1C1C1A';
            b.style.border = '1px solid #E5E2DC';
        });
        btn.style.background = '#1C1C1A';
        btn.style.color = '#FFFFFF';
        btn.style.border = '1px solid #1C1C1A';
    }

    function stepQty(delta) {
        const input = document.getElementById('qty-input');
        const next = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
        input.value = next;
    }

    function addProductToCart(productId, redirectAfter = false) {
        return new Promise((resolve) => {
            const btn = document.getElementById('add-to-cart-btn');
            const original = btn.innerHTML;
            const variantIdInput = document.getElementById('selected-variant-id');
            const quantity = Math.max(1, parseInt(document.getElementById('qty-input').value, 10) || 1);

            btn.disabled = true;
            btn.innerHTML = 'Đang thêm...';

            fetch(`/cart/add/${productId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    variant_id: variantIdInput ? variantIdInput.value : null,
                    quantity: quantity,
                }),
            })
                .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
                .then(({ ok, data }) => {
                    btn.disabled = false;
                    btn.innerHTML = original;

                    // Lỗi 404/422 (sản phẩm ẩn, thiếu phân loại, liên hệ giá...) — hiện thông
                    // báo lỗi thay vì im lặng coi như thêm giỏ thành công (P6, F9).
                    if (!ok) {
                        showToast(data.message || 'Không thể thêm vào giỏ hàng.', true);
                        resolve(null);
                        return;
                    }

                    document.querySelectorAll('.cart-count-badge').forEach(el => { el.textContent = data.cart_count; });

                    if (!redirectAfter) {
                        showToast(data.message);
                    }
                    resolve(data);
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = original;
                    showToast('Không thể thêm vào giỏ hàng. Vui lòng thử lại.', true);
                    resolve(null);
                });
        });
    }

    function buyNow(productId) {
        const btn = document.getElementById('buy-now-btn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Đang xử lý...';

        addProductToCart(productId, true).then((data) => {
            if (data) {
                window.location.href = "{{ route('checkout') }}";
            } else {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    }

    @if($errors->has('rating') || $errors->has('comment'))
        document.querySelector('section h2') && document.querySelectorAll('h2').forEach(h => { if (h.textContent.includes('Đánh giá')) h.scrollIntoView({ behavior: 'smooth' }); });
    @endif
</script>
@endpush
@endsection
