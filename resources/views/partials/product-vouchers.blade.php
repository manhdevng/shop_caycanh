{{--
    Partial: dải mã giảm giá gọn cho trang chi tiết sản phẩm (kiểu Shopee).

    Biến nhận vào khi include:
    - $productVouchers: Collection<App\Models\Voucher> mã đang còn hiệu lực áp
      được cho đúng sản phẩm này (toàn shop + gắn trực tiếp + gắn qua danh mục).

    Dùng: @include('partials.product-vouchers')
--}}
@if(($productVouchers ?? collect())->isNotEmpty())
    <div style="margin:0 0 24px;padding:14px 16px;border:1px dashed #E5E2DC;border-radius:12px;background:#F7F4EF">
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 10px">🎟 Mã giảm giá cho sản phẩm này</p>

        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            @foreach($productVouchers as $voucher)
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;border:1px solid #E5E2DC;border-radius:999px;padding:6px 6px 6px 12px;background:#FFFFFF">
                    <span style="font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.04em;color:#5C2323">{{ $voucher->code }}</span>
                    <span style="font-size:12px;color:#4A6B1F;white-space:nowrap">{{ $voucher->summary }}</span>

                    @auth
                        @if($voucher->isUsedBy(auth()->user()))
                            <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#A8A196;padding:5px 12px">Đã dùng</span>
                        @elseif($voucher->isSavedBy(auth()->user()))
                            <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#A8A196;padding:5px 12px">Đã lưu</span>
                        @else
                            <button type="button" data-code="{{ $voucher->code }}" onclick="saveVoucherCodeInline(this.dataset.code)" style="flex:none;padding:5px 14px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:10px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer;white-space:nowrap">Lưu</button>
                        @endif
                    @else
                        <a href="{{ route('login') }}" style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#5C2323;text-decoration:underline;white-space:nowrap">Đăng nhập để lưu</a>
                    @endauth
                </div>
            @endforeach

            <a href="{{ route('vouchers.browse') }}" style="font-size:12px;color:#6B6B66;text-decoration:underline;white-space:nowrap">Xem tất cả mã &rarr;</a>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                // Lưu mã giảm giá vào ví ngay từ trang chi tiết sản phẩm. Sau khi
                // lưu thành công, reload trang để cập nhật trạng thái nút (Lưu -> Đã lưu).
                function saveVoucherCodeInline(code) {
                    code = (code || '').trim();
                    if (!code) {
                        showToast('Vui lòng nhập mã giảm giá.', true);
                        return;
                    }

                    const formData = new FormData();
                    formData.append('code', code);

                    fetch("{{ route('voucher.save') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                throw new Error(data.message || 'Không thể lưu mã giảm giá.');
                            }
                            return data;
                        })
                        .then((data) => {
                            showToast(data.message || 'Lưu mã giảm giá thành công.');
                            location.reload();
                        })
                        .catch((error) => {
                            showToast(error.message || 'Không thể lưu mã giảm giá.', true);
                        });
                }
            </script>
        @endpush
    @endonce
@endif
