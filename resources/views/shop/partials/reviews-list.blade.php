{{--
    Partial: danh sách đánh giá của 1 sản phẩm — trả về đúng HTML (không có
    <html>/layout), được shop/show.blade.php nạp bằng fetch() rồi chèn
    innerHTML vào #reviews-list (không reload trang khi đổi bộ lọc/trang).

    QUAN TRỌNG: partial này được chèn bằng innerHTML nên KHÔNG được đặt
    <script> ở đây — trình duyệt không chạy script chèn qua innerHTML. Mọi
    hành vi (mở lightbox, bấm Hữu ích, chuyển trang) đều xử lý bằng event
    delegation ở shop/show.blade.php, dựa trên data-* của các phần tử dưới đây.

    Biến nhận vào (mục 4.2 hợp đồng):
    - $reviews: LengthAwarePaginator<Review> (paginate 6), đã eager-load
      user, images; mỗi $review có thêm thuộc tính ảo voted_by_me (bool) —
      khách hiện tại đã bấm Hữu ích cho đánh giá này chưa.
    - $product: Product — sản phẩm đang xem (không dùng trực tiếp trong
      partial này nhưng được truyền sẵn nếu cần mở rộng sau).
    - $filter: string — mã bộ lọc đang áp (?loc=), chỉ dùng để tuỳ biến
      thông điệp rỗng cho khớp bộ lọc khách đang chọn.
--}}
@php
    $reviewEmptyMessages = [
        'tat-ca' => 'Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên!',
        '5-sao' => 'Chưa có đánh giá 5 sao nào.',
        '4-sao' => 'Chưa có đánh giá 4 sao nào.',
        '3-sao' => 'Chưa có đánh giá 3 sao nào.',
        '2-sao' => 'Chưa có đánh giá 2 sao nào.',
        '1-sao' => 'Chưa có đánh giá 1 sao nào.',
        'co-binh-luan' => 'Chưa có đánh giá nào kèm bình luận.',
        'co-hinh-anh' => 'Chưa có đánh giá nào kèm hình ảnh.',
    ];
@endphp

@forelse($reviews as $review)
    <article style="border-bottom:1px solid #E5E2DC;padding:22px 0">
        <div style="display:flex;gap:12px;align-items:flex-start">
            <div style="flex:none;width:40px;height:40px;border-radius:50%;background:#F7F4EF;color:#5C2323;display:flex;align-items:center;justify-content:center;font-family:'Space Mono',monospace;font-size:15px;font-weight:700;overflow:hidden">
                {{-- Ẩn danh thì không lộ cả ảnh đại diện thật, chỉ giữ chữ cái đầu che tên (mục 3.B2). --}}
                @if(!$review->is_anonymous && optional($review->user)->avatar)
                    <img src="{{ asset('storage/' . $review->user->avatar) }}" alt="{{ $review->display_name }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    {{ mb_strtoupper(mb_substr($review->display_name, 0, 1)) }}
                @endif
            </div>

            <div style="flex:1;min-width:0">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:16px;flex-wrap:wrap;margin-bottom:6px">
                    <span style="font-size:14px;font-weight:600;color:#1C1C1A">{{ $review->display_name }}</span>
                </div>

                <p style="font-size:13px;color:#5C2323;letter-spacing:1px;margin:0 0 6px">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>

                <p style="font-size:12px;color:#8A8680;margin:0 0 10px">
                    {{ $review->created_at->format('d/m/Y H:i') }}@if($review->variant_name) | Phân loại hàng: {{ $review->variant_name }}@endif
                </p>

                @if($review->comment)
                    <p style="font-size:14px;line-height:1.7;color:#4A4A46;margin:0 0 12px;white-space:pre-line">{{ $review->comment }}</p>
                @endif

                @if($review->images->isNotEmpty())
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
                        @foreach($review->images as $image)
                            <button type="button" class="review-thumb" data-group="{{ $review->id }}" data-full="{{ asset('storage/' . $image->path) }}" style="width:72px;height:72px;padding:0;border:none;border-radius:8px;overflow:hidden;cursor:zoom-in;background:#F7F4EF">
                                <img src="{{ asset('storage/' . $image->path) }}" alt="Ảnh đánh giá của {{ $review->display_name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                            </button>
                        @endforeach
                    </div>
                @endif

                <button type="button" class="review-helpful-btn" data-review-id="{{ $review->id }}" data-voted="{{ $review->voted_by_me ? '1' : '0' }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;border:1px solid {{ $review->voted_by_me ? '#5C2323' : '#E5E2DC' }};background:#FFFFFF;color:{{ $review->voted_by_me ? '#5C2323' : '#1C1C1A' }};font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">
                    👍 Hữu ích (<span class="review-helpful-count">{{ $review->helpful_count }}</span>)
                </button>

                @if($review->shop_reply)
                    <div style="margin-top:14px;padding:12px 14px;border-radius:10px;background:#F7F4EF">
                        <p style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.05em;text-transform:uppercase;color:#5C2323;margin:0 0 6px">Phản hồi của Cây Cảnh Shop</p>
                        <p style="font-size:13px;line-height:1.6;color:#4A4A46;margin:0;white-space:pre-line">{{ $review->shop_reply }}</p>
                        @if($review->shop_replied_at)
                            <p style="font-size:11px;color:#8A8680;margin:6px 0 0">{{ $review->shop_replied_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </article>
@empty
    <p style="color:#8A8680;font-size:14px;font-style:italic">{{ $reviewEmptyMessages[$filter] ?? $reviewEmptyMessages['tat-ca'] }}</p>
@endforelse

@if($reviews->hasPages())
    <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-top:20px">
        @if(!$reviews->onFirstPage())
            <a href="#" data-page="{{ $reviews->currentPage() - 1 }}" style="padding:8px 16px;border-radius:999px;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;color:#1C1C1A">&larr; Trước</a>
        @endif

        <span style="font-family:'Space Mono',monospace;font-size:11px;color:#8A8680">Trang {{ $reviews->currentPage() }}/{{ $reviews->lastPage() }}</span>

        @if($reviews->hasMorePages())
            <a href="#" data-page="{{ $reviews->currentPage() + 1 }}" style="padding:8px 16px;border-radius:999px;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;color:#1C1C1A">Sau &rarr;</a>
        @endif
    </div>
@endif
