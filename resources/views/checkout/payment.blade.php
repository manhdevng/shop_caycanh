@extends('layouts.shop')
@section('content')

<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl md:text-4xl mb-1" style="font-family:'Gloock',serif">Thanh toán đơn hàng</h1>
    <p class="text-gray-500 text-sm mb-6">Điền thông tin nhận hàng — phí vận chuyển được tính tự động qua Giao Hàng Nhanh (GHN).</p>

    <!-- Tóm tắt giỏ hàng -->
    <div class="bg-white border border-[#8C9680]/30 rounded-2xl p-5 mb-6">
        <h3 class="font-semibold text-gray-800 mb-3">Sản phẩm ({{ count($cart) }})</h3>
        <div class="space-y-2 mb-3">
            @foreach($cart as $item)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-700">{{ $item['name'] }} <span class="text-gray-400">× {{ $item['quantity'] }}</span></span>
                    <span class="text-gray-800">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }} đ</span>
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-[#8C9680]/20 text-sm font-semibold">
            <span>Tiền hàng</span>
            <span>{{ number_format($totalPrice, 0, ',', '.') }} đ</span>
        </div>
    </div>

    <!-- Mã giảm giá -->
    <div class="bg-white border border-[#8C9680]/30 rounded-2xl p-5 mb-6">
        <h3 class="font-semibold text-gray-800 mb-3">Mã giảm giá</h3>
        @if($voucher)
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="text-sm">
                    <span class="font-semibold text-[#4A6B1F]">{{ $voucher->code }}</span>
                    <span class="text-gray-500">— đã giảm {{ number_format($discountAmount, 0, ',', '.') }} đ</span>
                </div>
                <form method="POST" action="{{ route('voucher.remove') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Gỡ mã</button>
                </form>
            </div>
        @else
            @include('partials.voucher-list', ['availableVouchers' => $availableVouchers, 'savableVouchers' => $savableVouchers ?? collect(), 'subtotal' => $totalPrice])
            @error('code')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        @endif
    </div>

    <form method="POST" action="{{ route('orders.store') }}" id="checkout-form" class="bg-white border border-[#8C9680]/30 rounded-2xl p-6 md:p-8 space-y-5">
        @csrf
        <input type="hidden" id="total_price_input" name="total_price" value="{{ (int) $totalPrice }}">
        <input type="hidden" id="ghn_fee_input" name="ghn_fee" value="0">
        <input type="hidden" id="to_district_id_input" name="to_district_id" value="{{ old('to_district_id') }}">
        <input type="hidden" id="to_ward_code_input" name="to_ward_code" value="{{ old('to_ward_code') }}">

        <div>
            <label for="name" class="block text-sm font-semibold mb-1">Họ và tên người nhận</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Nguyễn Văn A" required>
            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-semibold mb-1">Số điện thoại</label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="09xx xxx xxx" required>
            @error('phone') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Tỉnh/Thành - Quận/Huyện - Phường/Xã (nạp trực tiếp từ GHN) -->
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label for="province_select" class="block text-sm font-semibold mb-1">Tỉnh/Thành</label>
                <select id="province_select" class="w-full border border-[#8C9680] rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40 text-sm">
                    <option value="">-- Đang tải... --</option>
                </select>
            </div>
            <div>
                <label for="district_select" class="block text-sm font-semibold mb-1">Quận/Huyện</label>
                <select id="district_select" disabled class="w-full border border-[#8C9680] rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40 text-sm disabled:bg-[#F8F9F5] disabled:text-gray-400">
                    <option value="">-- Chọn Tỉnh/Thành trước --</option>
                </select>
            </div>
            <div>
                <label for="ward_select" class="block text-sm font-semibold mb-1">Phường/Xã</label>
                <select id="ward_select" disabled class="w-full border border-[#8C9680] rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40 text-sm disabled:bg-[#F8F9F5] disabled:text-gray-400">
                    <option value="">-- Chọn Quận/Huyện trước --</option>
                </select>
            </div>
        </div>
        @error('to_district_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('to_ward_code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <div>
            <label for="address" class="block text-sm font-semibold mb-1">Địa chỉ cụ thể</label>
            <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Số nhà, tên đường..." required>
            @error('address') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold mb-3">Hình thức thanh toán</label>
            <div class="space-y-2">
                <label class="flex items-center gap-3 border border-[#8C9680]/40 rounded-xl px-4 py-3 cursor-pointer hover:bg-[#F8F9F5] has-[:checked]:border-[#6B8E23] has-[:checked]:bg-[#F8F9F5] transition-colors">
                    <input type="radio" name="payment_method" value="cod" checked class="w-4 h-4 accent-[#6B8E23]">
                    <span>💵 Thanh toán trực tiếp <span class="text-gray-500">(khi nhận hàng)</span></span>
                </label>
                <label class="flex items-center gap-3 border border-[#8C9680]/40 rounded-xl px-4 py-3 cursor-pointer hover:bg-[#F8F9F5] has-[:checked]:border-[#6B8E23] has-[:checked]:bg-[#F8F9F5] transition-colors">
                    <input type="radio" name="payment_method" value="momo" id="payment-momo" {{ old('payment_method') === 'momo' ? 'checked' : '' }} class="w-4 h-4 accent-[#6B8E23]">
                    <span>💳 Thanh toán qua <span class="font-semibold text-pink-600">MoMo</span> <span class="text-gray-500">(thẻ ATM nội địa, thẻ quốc tế hoặc ví MoMo)</span></span>
                </label>
                <label class="flex items-center gap-3 border border-[#8C9680]/40 rounded-xl px-4 py-3 cursor-pointer hover:bg-[#F8F9F5] has-[:checked]:border-[#6B8E23] has-[:checked]:bg-[#F8F9F5] transition-colors">
                    <input type="radio" name="payment_method" value="bank_transfer" id="payment-bank" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} class="w-4 h-4 accent-[#6B8E23]">
                    <span>🏦 Chuyển khoản ngân hàng <span class="text-gray-500">(giao hàng sau khi shop xác nhận đã nhận tiền)</span></span>
                </label>
            </div>
            @error('payment_method') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror

            <div id="momo-options" class="hidden mt-3">
                <!-- MoMo mở 1 trang RIÊNG cho mỗi loại thẻ, nên phải chọn trước loại thẻ -->
                <div class="grid sm:grid-cols-3 gap-2">
                    <label class="flex items-center gap-2 border border-pink-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-pink-50 has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 transition-colors text-sm">
                        <input type="radio" name="momo_card_type" value="atm" {{ old('momo_card_type', 'atm') === 'atm' ? 'checked' : '' }} class="w-4 h-4 accent-pink-600">
                        🏧 Thẻ ATM nội địa
                    </label>
                    <label class="flex items-center gap-2 border border-pink-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-pink-50 has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 transition-colors text-sm">
                        <input type="radio" name="momo_card_type" value="cc" {{ old('momo_card_type') === 'cc' ? 'checked' : '' }} class="w-4 h-4 accent-pink-600">
                        🌍 Thẻ quốc tế (Visa/Mastercard/JCB)
                    </label>
                    <label class="flex items-center gap-2 border border-pink-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-pink-50 has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 transition-colors text-sm">
                        <input type="radio" name="momo_card_type" value="wallet" {{ old('momo_card_type') === 'wallet' ? 'checked' : '' }} class="w-4 h-4 accent-pink-600">
                        📱 Ví MoMo (quét mã QR)
                    </label>
                </div>
                @error('momo_card_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            @php
                $bankInfo = config('services.bank');
            @endphp
            <div id="bank-options" class="hidden mt-3 border border-[#8C9680]/30 rounded-xl p-4 bg-[#F8F9F5] space-y-3">
                <p class="text-sm font-semibold text-gray-800">Thông tin tài khoản nhận chuyển khoản</p>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>Ngân hàng: <span class="font-medium">{{ $bankInfo['name'] ?? '—' }}</span></p>
                    <p>Số tài khoản: <span class="font-mono font-semibold text-[#4A6B1F] select-all">{{ $bankInfo['account_number'] ?? '—' }}</span></p>
                    <p>Chủ tài khoản: <span class="font-medium">{{ $bankInfo['account_name'] ?? '—' }}</span></p>
                    <p>Chi nhánh: <span class="font-medium">{{ $bankInfo['branch'] ?? '—' }}</span></p>
                </div>
                <p class="text-xs text-gray-500">Nội dung chuyển khoản gợi ý: <span class="font-mono">CCS &lt;Họ tên&gt; &lt;SĐT&gt;</span> để shop dễ đối soát.</p>
                <div>
                    <label for="transfer_ref" class="block text-sm font-semibold mb-1">Mã tham chiếu / nội dung chuyển khoản (không bắt buộc)</label>
                    <input type="text" name="transfer_ref" id="transfer_ref" maxlength="100" value="{{ old('transfer_ref') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="VD: CCS Nguyen Van A 0912345678">
                    @error('transfer_ref') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">Lưu ý: đơn hàng sẽ được giao sau khi shop xác nhận đã nhận được tiền chuyển khoản.</p>
            </div>
        </div>

        <!-- Phí vận chuyển GHN -->
        <div class="bg-[#F8F9F5] border border-[#8C9680]/20 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Tiền hàng</span>
                <span>{{ number_format($totalPrice, 0, ',', '.') }} đ</span>
            </div>
            @if($voucher && $discountAmount > 0)
                <div class="flex items-center justify-between text-sm text-[#4A6B1F]">
                    <span>Giảm giá ({{ $voucher->code }})</span>
                    <span id="discount_amount_text">- {{ number_format($discountAmount, 0, ',', '.') }} đ</span>
                </div>
            @endif
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Phí vận chuyển (GHN)</span>
                <span id="shipping_fee_text" class="font-medium">-- Chọn địa chỉ để tính phí --</span>
            </div>
            <div class="flex items-center justify-between text-base font-bold pt-2 border-t border-[#8C9680]/20">
                <span>Tổng cộng</span>
                <span id="final_total_text" class="text-[#4A6B1F]">{{ number_format(max(0, $totalPrice - $discountAmount), 0, ',', '.') }} đ</span>
            </div>
        </div>

        <button type="submit" class="w-full bg-[#6B8E23] hover:bg-[#4A6B1F] text-white font-semibold py-3 rounded-full transition-colors">
            Đặt hàng
        </button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const provinceSelect = document.getElementById('province_select');
    const districtSelect = document.getElementById('district_select');
    const wardSelect = document.getElementById('ward_select');
    const shippingFeeText = document.getElementById('shipping_fee_text');
    const finalTotalText = document.getElementById('final_total_text');
    const totalPriceInput = document.getElementById('total_price_input');
    const ghnFeeInput = document.getElementById('ghn_fee_input');
    const toDistrictIdInput = document.getElementById('to_district_id_input');
    const toWardCodeInput = document.getElementById('to_ward_code_input');

    const districtsUrl = "{{ route('locations.districts', ['provinceId' => '__PROVINCE__']) }}";
    const wardsUrl = "{{ route('locations.wards', ['districtId' => '__DISTRICT__']) }}";

    // Lấy tiền hàng an toàn từ input ẩn (chưa trừ giảm giá — total_price_input
    // được JS ghi đè lại bằng tổng cuối cùng ở updateTotals(), nên phải đọc giá
    // trị gốc NGAY LÚC NÀY, trước khi bị ghi đè).
    const subtotal = parseInt(totalPriceInput ? totalPriceInput.value : 0) || 0;
    // Số tiền giảm giá từ voucher đang áp dụng (0 nếu chưa áp dụng mã nào) —
    // truyền từ PHP để cộng/trừ đúng công thức mỗi khi JS tính lại phí ship.
    const discountAmount = {{ (int) $discountAmount }};

    // 1. Tải danh sách Tỉnh/Thành phố từ GHN
    fetch("{{ route('locations.provinces') }}")
        .then(res => res.json())
        .then(res => {
            if (res.data) {
                let options = '<option value="">-- Chọn Tỉnh/Thành --</option>';
                res.data.forEach(p => {
                    options += `<option value="${p.ProvinceID}">${p.ProvinceName}</option>`;
                });
                provinceSelect.innerHTML = options;
            } else {
                provinceSelect.innerHTML = '<option value="">-- Không tải được tỉnh/thành --</option>';
            }
        })
        .catch(err => {
            console.error("Lỗi load tỉnh thành:", err);
            provinceSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
        });

    // 2. Khi chọn Tỉnh -> Tải Quận/Huyện
    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        districtSelect.disabled = true;
        wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        wardSelect.disabled = true;
        toDistrictIdInput.value = '';
        toWardCodeInput.value = '';
        updateTotals(0);

        if (!this.value) return;

        fetch(districtsUrl.replace('__PROVINCE__', this.value))
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                    res.data.forEach(d => {
                        options += `<option value="${d.DistrictID}">${d.DistrictName}</option>`;
                    });
                    districtSelect.innerHTML = options;
                    districtSelect.disabled = false;
                } else {
                    districtSelect.innerHTML = '<option value="">-- Không tải được quận/huyện --</option>';
                }
            })
            .catch(err => {
                console.error("Lỗi load quận huyện:", err);
                districtSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
            });
    });

    // 3. Khi chọn Quận/Huyện -> Tải Phường/Xã
    districtSelect.addEventListener('change', function () {
        wardSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
        wardSelect.disabled = true;
        toDistrictIdInput.value = this.value;
        toWardCodeInput.value = '';
        updateTotals(0);

        if (!this.value) return;

        fetch(wardsUrl.replace('__DISTRICT__', this.value))
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    let options = '<option value="">-- Chọn Phường/Xã --</option>';
                    res.data.forEach(w => {
                        options += `<option value="${w.WardCode}">${w.WardName}</option>`;
                    });
                    wardSelect.innerHTML = options;
                    wardSelect.disabled = false;
                } else {
                    wardSelect.innerHTML = '<option value="">-- Không tải được phường/xã --</option>';
                }
            })
            .catch(err => {
                console.error("Lỗi load phường xã:", err);
                wardSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
            });
    });

    // 4. Khi chọn Phường/Xã -> Tính cước vận chuyển GHN
    wardSelect.addEventListener('change', function () {
        toWardCodeInput.value = this.value;

        if (!this.value || !districtSelect.value) return;

        shippingFeeText.innerText = 'Đang tính cước...';

        fetch("{{ route('locations.fee') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                to_district_id: districtSelect.value,
                to_ward_code: this.value
            })
        })
            .then(res => res.json())
            .then(res => {
                if (res.code === 200 && res.data) {
                    const fee = parseInt(res.data.total) || 0;
                    updateTotals(fee);
                } else {
                    shippingFeeText.innerText = 'Chưa hỗ trợ';
                    updateTotals(0);
                }
            })
            .catch(err => {
                console.error("Lỗi tính phí:", err);
                shippingFeeText.innerText = 'Lỗi tính phí';
                updateTotals(0);
            });
    });

    function updateTotals(fee) {
        shippingFeeText.innerText = new Intl.NumberFormat('vi-VN').format(fee) + ' VNĐ';
        // Tiền hàng - Giảm giá + Phí ship = Tổng thanh toán (không âm).
        const finalAmount = Math.max(0, subtotal - discountAmount + fee);
        finalTotalText.innerText = new Intl.NumberFormat('vi-VN').format(finalAmount) + ' VNĐ';
        if (totalPriceInput) {
            totalPriceInput.value = finalAmount;
        }
        if (ghnFeeInput) {
            ghnFeeInput.value = fee;
        }
    }

    // Hiện/ẩn khối chọn loại thẻ MoMo / thông tin chuyển khoản theo lựa chọn hình thức thanh toán
    const momoOptions = document.getElementById('momo-options');
    const bankOptions = document.getElementById('bank-options');

    function togglePaymentOptions() {
        const isMomo = document.getElementById('payment-momo').checked;
        const isBank = document.getElementById('payment-bank').checked;
        momoOptions.classList.toggle('hidden', !isMomo);
        bankOptions.classList.toggle('hidden', !isBank);
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', togglePaymentOptions);
    });

    // Đảm bảo đúng trạng thái hiện/ẩn khi tải lại trang do lỗi validate (old() giữ lựa chọn cũ)
    togglePaymentOptions();

    // Chặn submit khi chưa chọn đủ Tỉnh/Quận/Phường
    document.getElementById('checkout-form').addEventListener('submit', function (e) {
        if (!toDistrictIdInput.value || !toWardCodeInput.value) {
            e.preventDefault();
            alert('Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện và Phường/Xã.');
        }
    });
});
</script>
@endsection
