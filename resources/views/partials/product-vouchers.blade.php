{{--
    Partial: dải mã giảm giá gọn cho trang chi tiết sản phẩm (kiểu Shopee).

    Biến nhận vào khi include:
    - $productVouchers: Collection<App\Models\Voucher> mã đang còn hiệu lực áp
      được cho đúng sản phẩm này (toàn shop + gắn trực tiếp + gắn qua danh mục).
    - $bestVoucher: ?array (['voucher' => Voucher, ...]) — mã đang cho giá tốt
      nhất ở phân loại mặc định (qty=1), dùng để đánh dấu ★. Có thể không có
      (null) khi không mã nào áp được — khi đó không mã nào được đánh dấu.

    Mỗi Voucher dùng thêm 2 accessor (mục 4.4): remaining_uses (?int — null =
    không giới hạn lượt) và expires_soon (bool — còn dưới 24h thì đếm ngược).

    Dùng: @include('partials.product-vouchers')
--}}
@if(($productVouchers ?? collect())->isNotEmpty())
    <div style="margin:0 0 24px;padding:14px 16px;border:1px dashed #E5E2DC;border-radius:12px;background:#F7F4EF">
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 10px">🎟 Mã giảm giá cho sản phẩm này</p>

        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            @php $bestVoucherId = optional($bestVoucher['voucher'] ?? null)->id; @endphp
            @foreach($productVouchers as $voucher)
                <div class="pv-voucher-chip" data-expires-soon="{{ $voucher->expires_soon ? '1' : '0' }}" data-expires-at="{{ optional($voucher->expires_at)->toIso8601String() }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;max-width:100%;min-width:0;border:1px solid {{ $voucher->id === $bestVoucherId ? '#5C2323' : '#E5E2DC' }};border-radius:20px;padding:6px 6px 6px 12px;background:#FFFFFF">
                    @if($voucher->id === $bestVoucherId)
                        <span title="Mã tốt nhất cho sản phẩm này" aria-label="Mã tốt nhất" style="color:#5C2323;font-size:13px">★</span>
                    @endif
                    <span style="min-width:0;overflow-wrap:anywhere;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.04em;color:#5C2323">{{ $voucher->code }}</span>
                    <span style="font-size:12px;color:#4A6B1F;white-space:nowrap">{{ $voucher->summary }}</span>

                    @if(!is_null($voucher->remaining_uses))
                        <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.03em;color:#8A8680;white-space:nowrap">Còn {{ $voucher->remaining_uses }} lượt</span>
                    @endif

                    @if($voucher->expires_soon)
                        <span class="pv-voucher-countdown" style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.03em;color:#5C2323;white-space:nowrap">Hết hạn sau --:--:--</span>
                    @endif

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

                // Đếm ngược "Hết hạn sau hh:mm:ss" cho mã sắp hết hạn (< 24h,
                // đánh dấu sẵn bằng expires_soon ở BE). Về 0 thì ẩn hẳn mã đó
                // đi vì lúc đó mã chắc chắn đã hết hạn, không nên mời khách lưu.
                (function () {
                    function pad(n) { return String(n).padStart(2, '0'); }

                    function tick() {
                        var chips = document.querySelectorAll('.pv-voucher-chip[data-expires-soon="1"]');
                        chips.forEach(function (chip) {
                            var expiresAt = chip.dataset.expiresAt;
                            var label = chip.querySelector('.pv-voucher-countdown');
                            if (!expiresAt || !label) return;

                            var diffMs = new Date(expiresAt).getTime() - Date.now();
                            if (diffMs <= 0) {
                                chip.style.display = 'none';
                                return;
                            }

                            var totalSeconds = Math.floor(diffMs / 1000);
                            var hh = Math.floor(totalSeconds / 3600);
                            var mm = Math.floor((totalSeconds % 3600) / 60);
                            var ss = totalSeconds % 60;
                            label.textContent = 'Hết hạn sau ' + pad(hh) + ':' + pad(mm) + ':' + pad(ss);
                        });
                    }

                    tick();
                    setInterval(tick, 1000);
                })();
            </script>
        @endpush
    @endonce
@endif
