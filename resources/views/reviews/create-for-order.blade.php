@extends('layouts.shop')

@section('title', 'Đánh giá đơn hàng #' . $order->id . ' · Cây Cảnh Shop')

@section('content')
<nav aria-label="breadcrumb" style="max-width:900px;margin:0 auto;padding:18px 20px 0;font-size:13px;color:#8A8680">
    <a href="{{ route('shop.index') }}">Trang chủ</a> <span aria-hidden="true">&rsaquo;</span>
    <a href="{{ route('orders.history') }}">Đơn mua</a> <span aria-hidden="true">&rsaquo;</span>
    <a href="{{ route('orders.show', $order) }}">Đơn hàng #{{ $order->id }}</a> <span aria-hidden="true">&rsaquo;</span>
    <span style="color:#1C1C1A">Đánh giá</span>
</nav>

<section style="max-width:900px;margin:0 auto;padding:24px 20px 100px;min-width:0">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,4vw,42px);line-height:1.1;text-transform:uppercase;margin:0 0 8px">Đánh giá đơn hàng #{{ $order->id }}</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;color:#8A8680;margin:0 0 28px">Chọn số sao và chia sẻ cảm nhận cho từng sản phẩm bên dưới. Gửi một lần cho cả đơn.</p>

    <form id="review-order-form" method="POST" action="{{ route('reviews.storeForOrder', $order) }}" enctype="multipart/form-data">
        @csrf

        @forelse($lines as $line)
            @php
                $lineItem = $line['item'];
                $lineProduct = $line['product'];
                $lineReview = $line['review'];
                $lineCanReview = $line['can_review'];
                $lineCanEdit = $line['can_edit'];
                $showEditFields = $lineCanReview || ($lineReview && $lineCanEdit);
                $fieldsStartHidden = (bool) $lineReview;
                $starLabels = ['', 'Tệ', 'Không hài lòng', 'Bình thường', 'Hài lòng', 'Tuyệt vời'];
            @endphp

            <div class="review-line" style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:clamp(16px,4vw,22px);margin-bottom:16px;min-width:0">
                <div style="display:flex;gap:12px;margin-bottom:16px;min-width:0">
                    <div style="flex:none;width:64px;height:64px;border-radius:10px;overflow:hidden;background:#F7F5F0">
                        @if($lineProduct?->main_image)
                            <img src="{{ asset('storage/' . $lineProduct->main_image) }}" alt="{{ $lineItem->product_name ?: $lineProduct->name }}" style="width:100%;height:100%;object-fit:cover">
                        @else
                            <span class="placeholder-pattern" style="display:flex;width:100%;height:100%;align-items:center;justify-content:center;color:#8A8680"><i data-lucide="sprout" style="width:22px;height:22px"></i></span>
                        @endif
                    </div>
                    <div style="min-width:0;overflow-wrap:anywhere">
                        <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 4px">{{ $lineItem->product_name ?: ($lineProduct->name ?? 'Sản phẩm đã xoá') }}</p>
                        @if($lineItem->variant_name)
                            <p style="font-size:12px;color:#8A8680;margin:0">Phân loại: {{ $lineItem->variant_name }}</p>
                        @endif
                    </div>
                </div>

                @if($lineReview)
                    {{-- Dòng đã có đánh giá: hiện kết quả trước, "Sửa" (nếu còn quyền) mới lộ form. --}}
                    <div class="review-line__summary" id="summary-{{ $lineItem->id }}">
                        <p style="font-size:14px;color:#5C2323;letter-spacing:1px;margin:0 0 8px">{{ str_repeat('★', $lineReview->rating) }}{{ str_repeat('☆', 5 - $lineReview->rating) }}</p>
                        @if($lineReview->comment)
                            <p style="font-size:13.5px;line-height:1.7;color:#4A4A46;margin:0 0 10px;white-space:pre-line">{{ $lineReview->comment }}</p>
                        @endif
                        @if($lineReview->images->isNotEmpty())
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
                                @foreach($lineReview->images as $img)
                                    <img src="{{ asset('storage/' . $img->path) }}" alt="Ảnh đánh giá đã gửi" style="width:64px;height:64px;object-fit:cover;border-radius:8px">
                                @endforeach
                            </div>
                        @endif
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span style="display:inline-block;padding:5px 12px;border-radius:999px;background:#F7F4EF;color:#4A6B1F;font-family:'Space Mono',monospace;font-size:10px;text-transform:uppercase">✓ Đã đánh giá</span>
                            @if($lineCanEdit)
                                <button type="button" class="review-edit-btn" data-item-id="{{ $lineItem->id }}" style="padding:6px 14px;border-radius:999px;border:1px solid #5C2323;background:#FFFFFF;color:#5C2323;font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">Sửa</button>
                            @endif
                        </div>
                    </div>
                @endif

                @if($showEditFields)
                    <div class="review-line__edit-fields" id="edit-fields-{{ $lineItem->id }}" data-item-id="{{ $lineItem->id }}" data-max-images="{{ $maxImages }}" @if($fieldsStartHidden) hidden @endif>
                        <div class="review-star-picker" style="display:flex;align-items:center;gap:4px;margin-bottom:6px">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="review-star-btn" data-star="{{ $i }}" @if($fieldsStartHidden) disabled @endif style="font-size:26px;line-height:1;background:none;border:none;cursor:pointer;color:{{ ($lineReview->rating ?? 0) >= $i ? '#5C2323' : '#E5E2DC' }}" aria-label="{{ $i }} sao">★</button>
                            @endfor
                            <input type="hidden" name="reviews[{{ $lineItem->id }}][rating]" class="review-rating-input" value="{{ $lineReview->rating ?? '' }}" @if($fieldsStartHidden) disabled @endif>
                        </div>
                        <p class="review-star-label" style="font-size:12px;color:#8A8680;margin:0 0 14px;min-height:16px">{{ $lineReview && $lineReview->rating ? ($starLabels[$lineReview->rating] ?? '') : '' }}</p>
                        @error('reviews.' . $lineItem->id . '.rating')
                            <p style="font-size:12px;color:#B3261E;margin:-8px 0 12px">{{ $message }}</p>
                        @enderror

                        <textarea name="reviews[{{ $lineItem->id }}][comment]" class="review-comment-input" maxlength="1000" rows="4" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm này..." @if($fieldsStartHidden) disabled @endif style="width:100%;border:1px solid #E5E2DC;border-radius:10px;padding:12px 14px;font-family:inherit;font-size:13px">{{ $lineReview->comment ?? '' }}</textarea>
                        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:6px 0 6px">
                            <span style="font-size:11px;color:#8A8680">Viết ≥ 50 ký tự và thêm ảnh để nhận {{ config('shop.review_reward_points', 20) }} điểm</span>
                            <span class="review-char-count" style="font-size:11px;color:#8A8680;white-space:nowrap">{{ mb_strlen($lineReview->comment ?? '') }}/1000</span>
                        </div>
                        @error('reviews.' . $lineItem->id . '.comment')
                            <p style="font-size:12px;color:#B3261E;margin:0 0 10px">{{ $message }}</p>
                        @enderror

                        @if($lineReview && $lineReview->images->isNotEmpty())
                            <p style="font-size:11px;color:#8A8680;margin:14px 0 8px">Ảnh đã gửi — tích để gỡ:</p>
                            <div class="review-existing-images" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:6px">
                                @foreach($lineReview->images as $img)
                                    <div style="position:relative;width:72px;height:72px">
                                        <img src="{{ asset('storage/' . $img->path) }}" alt="Ảnh đánh giá cũ" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                                        <label style="position:absolute;top:2px;right:2px;width:20px;height:20px;background:rgba(255,255,255,.9);border-radius:4px;display:flex;align-items:center;justify-content:center">
                                            <input type="checkbox" name="reviews[{{ $lineItem->id }}][remove_images][]" value="{{ $img->id }}" @if($fieldsStartHidden) disabled @endif style="width:14px;height:14px;margin:0">
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="review-image-upload" data-item-id="{{ $lineItem->id }}" data-max-images="{{ $maxImages }}" style="margin:10px 0 14px">
                            <input type="file" name="reviews[{{ $lineItem->id }}][images][]" class="review-image-input" accept="image/png,image/jpeg,image/jpg,image/webp" multiple @if($fieldsStartHidden) disabled @endif style="display:none">
                            <button type="button" class="review-image-trigger" @if($fieldsStartHidden) disabled @endif style="padding:9px 16px;border-radius:999px;border:1px dashed #8A8680;background:#FFFFFF;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">+ Thêm ảnh (tối đa {{ $maxImages }})</button>
                            <div class="review-image-preview" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px"></div>
                        </div>
                        @error('reviews.' . $lineItem->id . '.images')
                            <p style="font-size:12px;color:#B3261E;margin:0 0 10px">{{ $message }}</p>
                        @enderror

                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#4A4A46;margin-bottom:16px">
                            <input type="checkbox" name="reviews[{{ $lineItem->id }}][is_anonymous]" value="1" class="review-anonymous-checkbox" @checked($lineReview->is_anonymous ?? false) @if($fieldsStartHidden) disabled @endif>
                            Ẩn danh (chỉ hiện chữ cái đầu và cuối tên của bạn)
                        </label>

                        @if($lineReview)
                            <button type="button" class="review-cancel-edit-btn" data-item-id="{{ $lineItem->id }}" style="padding:9px 16px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">Huỷ sửa</button>
                        @endif
                    </div>
                @elseif(!$lineReview)
                    <p style="font-size:13px;color:#8A8680;font-style:italic;margin:0">Dòng hàng này hiện không thể đánh giá (đã quá hạn hoặc chưa đủ điều kiện).</p>
                @endif
            </div>
        @empty
            <div style="text-align:center;padding:64px 20px;border:1px dashed #E5E2DC;border-radius:16px">
                <i data-lucide="package-open" style="width:36px;height:36px;color:#8A8680;margin:auto"></i>
                <p style="color:#8A8680;font-size:14px">Đơn hàng này chưa có sản phẩm nào để đánh giá.</p>
            </div>
        @endforelse

        @if($lines->isNotEmpty())
            <button type="submit" id="review-submit-btn" style="width:100%;padding:16px 20px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Gửi đánh giá</button>
        @endif
    </form>
</section>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('review-order-form');
    if (!form) return;

    const starLabels = ['', 'Tệ', 'Không hài lòng', 'Bình thường', 'Hài lòng', 'Tuyệt vời'];
    const MAX_FILE_BYTES = 2 * 1024 * 1024; // 2MB — chặn phía client, server vẫn kiểm tra lại.
    const fileStore = {};

    function syncFileInput(itemId) {
        const wrapper = form.querySelector('.review-image-upload[data-item-id="' + itemId + '"]');
        if (!wrapper) return;
        const input = wrapper.querySelector('.review-image-input');
        const dataTransfer = new DataTransfer();
        (fileStore[itemId] || []).forEach(function (file) { dataTransfer.items.add(file); });
        input.files = dataTransfer.files;
    }

    function renderPreview(itemId) {
        const wrapper = form.querySelector('.review-image-upload[data-item-id="' + itemId + '"]');
        if (!wrapper) return;
        const previewBox = wrapper.querySelector('.review-image-preview');
        previewBox.innerHTML = '';
        (fileStore[itemId] || []).forEach(function (file, index) {
            const url = URL.createObjectURL(file);
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;width:72px;height:72px';

            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Ảnh xem trước';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:8px';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'review-preview-remove-btn';
            removeBtn.dataset.itemId = itemId;
            removeBtn.dataset.index = String(index);
            removeBtn.setAttribute('aria-label', 'Xoá ảnh này');
            removeBtn.textContent = '✕';
            removeBtn.style.cssText = 'position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#5C2323;color:#fff;border:none;font-size:11px;cursor:pointer;line-height:1';

            wrap.appendChild(img);
            wrap.appendChild(removeBtn);
            previewBox.appendChild(wrap);
        });
    }

    function handleImageInputChange(input) {
        const wrapper = input.closest('.review-image-upload');
        const itemId = wrapper.dataset.itemId;
        const maxImages = parseInt(wrapper.dataset.maxImages, 10) || 5;
        if (!fileStore[itemId]) fileStore[itemId] = [];

        Array.from(input.files).forEach(function (file) {
            if (fileStore[itemId].length >= maxImages) {
                showToast('Chỉ được tải tối đa ' + maxImages + ' ảnh cho mỗi đánh giá.', true);
                return;
            }
            if (file.size > MAX_FILE_BYTES) {
                showToast('Ảnh "' + file.name + '" vượt quá 2MB, đã bỏ qua.', true);
                return;
            }
            fileStore[itemId].push(file);
        });

        syncFileInput(itemId);
        renderPreview(itemId);
    }

    function setFieldsEnabled(fields, enabled) {
        fields.querySelectorAll('input, textarea, button.review-star-btn, button.review-image-trigger').forEach(function (el) {
            el.disabled = !enabled;
        });
    }

    form.addEventListener('click', function (e) {
        const starBtn = e.target.closest('.review-star-btn');
        if (starBtn) {
            const value = parseInt(starBtn.dataset.star, 10);
            const picker = starBtn.closest('.review-star-picker');
            const fields = starBtn.closest('.review-line__edit-fields');
            const ratingInput = picker.querySelector('.review-rating-input');
            ratingInput.value = value;
            picker.querySelectorAll('.review-star-btn').forEach(function (b) {
                b.style.color = parseInt(b.dataset.star, 10) <= value ? '#5C2323' : '#E5E2DC';
            });
            const labelEl = fields ? fields.querySelector('.review-star-label') : null;
            if (labelEl) labelEl.textContent = starLabels[value] || '';
            return;
        }

        const trigger = e.target.closest('.review-image-trigger');
        if (trigger) {
            const wrapper = trigger.closest('.review-image-upload');
            const input = wrapper ? wrapper.querySelector('.review-image-input') : null;
            if (input) input.click();
            return;
        }

        const removeBtn = e.target.closest('.review-preview-remove-btn');
        if (removeBtn) {
            const itemId = removeBtn.dataset.itemId;
            const index = parseInt(removeBtn.dataset.index, 10);
            if (fileStore[itemId]) {
                fileStore[itemId].splice(index, 1);
                syncFileInput(itemId);
                renderPreview(itemId);
            }
            return;
        }

        const editBtn = e.target.closest('.review-edit-btn');
        if (editBtn) {
            const itemId = editBtn.dataset.itemId;
            const summary = document.getElementById('summary-' + itemId);
            const fields = document.getElementById('edit-fields-' + itemId);
            if (summary) summary.hidden = true;
            if (fields) {
                fields.hidden = false;
                setFieldsEnabled(fields, true);
            }
            return;
        }

        const cancelBtn = e.target.closest('.review-cancel-edit-btn');
        if (cancelBtn) {
            const itemId = cancelBtn.dataset.itemId;
            const summary = document.getElementById('summary-' + itemId);
            const fields = document.getElementById('edit-fields-' + itemId);
            if (fields) {
                fields.hidden = true;
                setFieldsEnabled(fields, false);
            }
            if (summary) summary.hidden = false;
            return;
        }
    });

    form.addEventListener('change', function (e) {
        if (e.target.classList.contains('review-image-input')) {
            handleImageInputChange(e.target);
        }
    });

    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('review-comment-input')) {
            const fields = e.target.closest('.review-line__edit-fields');
            const counter = fields ? fields.querySelector('.review-char-count') : null;
            if (counter) counter.textContent = e.target.value.length + '/1000';
        }
    });

    form.addEventListener('submit', function () {
        const submitBtn = document.getElementById('review-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang gửi...';
        }
    });
})();
</script>
@endpush
@endsection
