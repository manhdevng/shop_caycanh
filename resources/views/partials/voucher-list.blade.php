{{--
    Partial dùng chung: danh sách mã giảm giá, tham khảo UX
    Shopee/TikTok Shop — mô hình "lưu mã vào ví trước, rồi mới dùng".

    Biến nhận vào khi include:
    - $availableVouchers: Collection<App\Models\Voucher> mã ĐÃ LƯU trong ví + chưa dùng (nhóm nút "Dùng")
    - $savableVouchers (tuỳ chọn): Collection<App\Models\Voucher> mã còn hiệu lực nhưng CHƯA LƯU (nhóm nút "Lưu")
    - $subtotal: số tiền hiện tại của giỏ hàng/đơn hàng (int|float)

    Dùng: @include('partials.voucher-list', ['availableVouchers' => $availableVouchers, 'savableVouchers' => $savableVouchers, 'subtotal' => $total])
--}}
@php
    $savableVouchers = $savableVouchers ?? collect();
@endphp
@if($availableVouchers->isNotEmpty() || $savableVouchers->isNotEmpty() || auth()->check())
    <div style="margin-bottom:20px">
        {{-- Khối A: mã đã lưu trong ví --}}
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 10px">🎟 Mã của bạn</p>

        @if($availableVouchers->isNotEmpty())
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach($availableVouchers as $voucher)
                    @php
                        $minAmount = (float) ($voucher->min_order_amount ?? 0);
                        $eligible = $subtotal >= $minAmount;
                        $scopeLabel = $voucher->scope_type === 'products' ? 'Chỉ một số sản phẩm' : ($voucher->scope_type === 'categories' ? 'Chỉ một số danh mục' : null);
                    @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px dashed {{ $eligible ? '#5C2323' : '#E5E2DC' }};border-radius:12px;padding:12px 14px;background:{{ $eligible ? '#FFFFFF' : '#F7F4EF' }};opacity:{{ $eligible ? '1' : '0.6' }}">
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                                <span style="font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.04em;color:#5C2323;background:#F7F4EF;border:1px solid #E5E2DC;border-radius:6px;padding:2px 8px">{{ $voucher->code }}</span>
                                <span style="font-size:13px;font-weight:600;color:#4A6B1F">{{ $voucher->summary }}</span>
                                @if($scopeLabel)
                                    <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#8A8680;background:#F0EDE6;border:1px solid #E5E2DC;border-radius:999px;padding:2px 8px">{{ $scopeLabel }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#8A8680">
                                @if($voucher->min_order_amount)
                                    Đơn tối thiểu {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ
                                @else
                                    Không giới hạn giá trị đơn hàng
                                @endif
                                @if($voucher->expires_at)
                                    <span>&middot; HSD {{ $voucher->expires_at->format('d/m/Y') }}</span>
                                @endif
                            </div>
                            @if(!$eligible)
                                <div style="font-size:12px;color:#B3261E;margin-top:4px">
                                    @if($voucher->scope_type === 'all')
                                        Mua thêm {{ number_format($minAmount - $subtotal, 0, ',', '.') }}đ để dùng mã này
                                    @else
                                        Chưa đủ điều kiện với sản phẩm áp dụng trong giỏ
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($eligible)
                            <button type="button" data-code="{{ $voucher->code }}" onclick="applyVoucherCode(this.dataset.code)" style="flex:none;padding:8px 16px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer;white-space:nowrap">Dùng mã</button>
                        @else
                            <button type="button" disabled style="flex:none;padding:8px 16px;border-radius:999px;background:#E5E2DC;color:#A8A196;border:none;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:not-allowed;white-space:nowrap">Chưa đủ điều kiện</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div style="font-size:13px;color:#6B6B66;margin-bottom:4px">
                @auth
                    Bạn chưa lưu mã nào. Xem <a href="{{ route('vouchers.browse') }}" style="color:#5C2323;text-decoration:underline">Săn mã giảm giá</a>
                @else
                    <a href="{{ route('login') }}" style="color:#5C2323;text-decoration:underline">Đăng nhập</a> để xem và dùng mã giảm giá đã lưu.
                @endauth
            </div>
        @endif

        {{-- Khối B: mã còn hiệu lực nhưng chưa lưu --}}
        @if($savableVouchers->isNotEmpty())
            <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:18px 0 10px">🏷 Mã có thể lưu</p>

            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach($savableVouchers as $voucher)
                    @php
                        $minAmount = (float) ($voucher->min_order_amount ?? 0);
                        $scopeLabel = $voucher->scope_type === 'products' ? 'Chỉ một số sản phẩm' : ($voucher->scope_type === 'categories' ? 'Chỉ một số danh mục' : null);
                    @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px dashed #E5E2DC;border-radius:12px;padding:12px 14px;background:#FFFFFF">
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                                <span style="font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.04em;color:#5C2323;background:#F7F4EF;border:1px solid #E5E2DC;border-radius:6px;padding:2px 8px">{{ $voucher->code }}</span>
                                <span style="font-size:13px;font-weight:600;color:#4A6B1F">{{ $voucher->summary }}</span>
                                @if($scopeLabel)
                                    <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#8A8680;background:#F0EDE6;border:1px solid #E5E2DC;border-radius:999px;padding:2px 8px">{{ $scopeLabel }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#8A8680">
                                @if($voucher->min_order_amount)
                                    Đơn tối thiểu {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ
                                @else
                                    Không giới hạn giá trị đơn hàng
                                @endif
                                @if($voucher->expires_at)
                                    <span>&middot; HSD {{ $voucher->expires_at->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>

                        @auth
                            <button type="button" data-code="{{ $voucher->code }}" onclick="saveVoucherCode(this.dataset.code)" style="flex:none;padding:8px 16px;border-radius:999px;background:#FFFFFF;color:#5C2323;border:1px solid #5C2323;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer;white-space:nowrap">Lưu</button>
                        @else
                            <a href="{{ route('login') }}" style="flex:none;padding:8px 16px;border-radius:999px;background:#FFFFFF;color:#5C2323;border:1px solid #5C2323;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;text-decoration:none;white-space:nowrap">Đăng nhập để lưu</a>
                        @endauth
                    </div>
                @endforeach
            </div>
        @endif

        <div style="margin-top:10px">
            <a href="javascript:void(0)" onclick="document.getElementById('manualVoucherBox').style.display='flex';this.style.display='none'" style="font-size:12px;color:#6B6B66;text-decoration:underline">Có mã khác? Nhập tại đây</a>
            <div id="manualVoucherBox" style="display:none;gap:8px;margin-top:8px">
                <input type="text" id="manualVoucherInput" placeholder="Nhập mã giảm giá" style="flex:1 1 auto;min-width:0;padding:9px 12px;border:1px solid #E5E2DC;border-radius:8px;font-size:13px;font-family:inherit">
                <button type="button" onclick="applyVoucherCode(document.getElementById('manualVoucherInput').value)" style="flex:none;padding:9px 16px;border-radius:8px;background:#1C1C1A;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Áp dụng</button>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                // Áp dụng mã giảm giá qua AJAX (dùng chung cho danh sách mã gợi ý
                // và ô "nhập mã khác"). Sau khi thành công, reload trang để server
                // tính lại chính xác tổng tiền/giảm giá/phí ship — KHÔNG tự tính
                // lại bằng JS vì logic ở trang thanh toán khá phức tạp.
                function applyVoucherCode(code) {
                    code = (code || '').trim();
                    if (!code) {
                        showToast('Vui lòng nhập mã giảm giá.', true);
                        return;
                    }

                    const formData = new FormData();
                    formData.append('code', code);

                    fetch("{{ route('voucher.apply') }}", {
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
                                throw new Error(data.message || 'Không thể áp dụng mã giảm giá.');
                            }
                            return data;
                        })
                        .then((data) => {
                            showToast(data.message || 'Áp dụng mã giảm giá thành công.');
                            location.reload();
                        })
                        .catch((error) => {
                            showToast(error.message || 'Không thể áp dụng mã giảm giá.', true);
                        });
                }

                // Lưu mã giảm giá vào ví (chưa áp dụng ngay). Sau khi lưu thành
                // công, reload trang để mã chuyển từ nhóm "Mã có thể lưu" sang
                // "Mã của bạn".
                function saveVoucherCode(code) {
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
