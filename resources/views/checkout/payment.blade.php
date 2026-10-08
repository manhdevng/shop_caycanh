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
        <input type="hidden" id="ghn_fee_input" name="ghn_fee" value="">
        <input type="hidden" id="to_district_id_input" name="to_district_id" value="{{ old('to_district_id') }}">
        <input type="hidden" id="to_ward_code_input" name="to_ward_code" value="{{ old('to_ward_code') }}">

        <input type="hidden" id="address_id_input" name="address_id" value="{{ old('address_id') }}">
        <input type="hidden" id="province_id_input" name="province_id" value="{{ old('province_id') }}">
        <input type="hidden" id="province_name_input" name="province_name" value="{{ old('province_name') }}">
        <input type="hidden" id="district_name_input" name="district_name" value="{{ old('district_name') }}">
        <input type="hidden" id="ward_name_input" name="ward_name" value="{{ old('ward_name') }}">

        <!-- Địa chỉ nhận hàng (kiểu Shopee): hiện sẵn địa chỉ mặc định, bấm "Thay đổi" để chọn địa chỉ khác -->
        <div class="flex items-center justify-between gap-3">
            <h3 class="flex items-center gap-2 font-semibold text-[#4A6B1F]">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>
                Địa chỉ nhận hàng
            </h3>
            <button type="button" id="back_to_saved_btn" class="hidden text-sm font-semibold text-[#4A6B1F] hover:underline">← Chọn địa chỉ đã lưu</button>
        </div>

        <div id="saved_address_view" class="hidden -mt-2 border border-[#8C9680]/30 rounded-xl p-4 bg-[#F8F9F5]">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 space-y-1">
                    <p class="text-gray-800">
                        <span id="sa_name" class="font-semibold"></span>
                        <span class="text-gray-300 mx-1">|</span>
                        <span id="sa_phone" class="text-gray-600"></span>
                    </p>
                    <p id="sa_full" class="text-sm text-gray-600 break-words"></p>
                    <span id="sa_default" class="hidden inline-block text-xs text-[#6B8E23] border border-[#6B8E23] rounded px-1.5 py-0.5">Mặc định</span>
                    <span id="sa_past" class="hidden inline-block text-xs text-gray-500 border border-gray-300 rounded px-1.5 py-0.5"></span>
                </div>
                <button type="button" id="change_address_btn" class="shrink-0 text-sm font-semibold text-[#4A6B1F] hover:underline">Thay đổi</button>
            </div>
        </div>

        <div id="new_address_form" class="space-y-5">
            <div>
                <label for="name" class="block text-sm font-semibold mb-1">Họ và tên người nhận</label>
                <input type="text" name="name" id="name" value="{{ old('name', $addresses->isEmpty() ? (auth()->user()->name ?? '') : '') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Nguyễn Văn A" required>
                @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold mb-1">Số điện thoại</label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone', $addresses->isEmpty() ? (auth()->user()->phone ?? '') : '') }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="09xx xxx xxx" required>
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

            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="save_address" id="save_address_input" value="1" {{ (session()->hasOldInput() ? old('save_address') : true) ? 'checked' : '' }} class="w-4 h-4 accent-[#6B8E23]">
                    Lưu địa chỉ này vào sổ địa chỉ
                </label>
                @if($addresses->isNotEmpty())
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="set_default" value="1" {{ old('set_default') ? 'checked' : '' }} class="w-4 h-4 accent-[#6B8E23]">
                        Đặt làm địa chỉ mặc định
                    </label>
                @endif
            </div>
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

        {{-- Khoá tới khi tính được phí GHN (JS mở lại khi phí > 0). --}}
        <button type="submit" id="place_order_btn" disabled class="w-full bg-[#6B8E23] hover:bg-[#4A6B1F] text-white font-semibold py-3 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-[#6B8E23]">
            Đặt hàng
        </button>
    </form>
</div>

<!-- Hộp chọn địa chỉ (sổ địa chỉ) -->
<div id="address_modal" class="hidden fixed inset-0 z-50 bg-black/40 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="address_modal_title">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[85vh] flex flex-col shadow-xl">
        <div class="px-5 py-4 border-b border-[#8C9680]/20">
            <h3 id="address_modal_title" class="font-semibold text-gray-800">Địa chỉ của tôi</h3>
        </div>
        <div id="address_list" class="overflow-y-auto divide-y divide-[#8C9680]/20"></div>
        <div class="px-5 py-4 border-t border-[#8C9680]/20 flex flex-wrap items-center justify-between gap-3">
            <button type="button" id="add_address_btn" class="text-sm font-semibold text-[#4A6B1F] border border-[#6B8E23] rounded-full px-4 py-2 hover:bg-[#F8F9F5]">+ Thêm địa chỉ mới</button>
            <div class="flex gap-2">
                <button type="button" id="address_modal_cancel" class="text-sm px-4 py-2 rounded-full border border-[#8C9680]/40 hover:bg-[#F8F9F5]">Huỷ</button>
                <button type="button" id="address_modal_confirm" class="text-sm font-semibold text-white bg-[#6B8E23] hover:bg-[#4A6B1F] px-5 py-2 rounded-full">Xác nhận</button>
            </div>
        </div>
    </div>
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
    const placeOrderBtn = document.getElementById('place_order_btn');
    const addressIdInput = document.getElementById('address_id_input');
    const provinceIdInput = document.getElementById('province_id_input');
    const provinceNameInput = document.getElementById('province_name_input');
    const districtNameInput = document.getElementById('district_name_input');
    const wardNameInput = document.getElementById('ward_name_input');

    const districtsUrl = "{{ route('locations.districts', ['provinceId' => '__PROVINCE__']) }}";
    const wardsUrl = "{{ route('locations.wards', ['districtId' => '__DISTRICT__']) }}";

    // Lấy tiền hàng an toàn từ input ẩn (chưa trừ giảm giá — total_price_input
    // được JS ghi đè lại bằng tổng cuối cùng ở applyTotals(), nên phải đọc giá
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
        provinceIdInput.value = this.value;
        provinceNameInput.value = this.value ? this.options[this.selectedIndex].text : '';
        districtNameInput.value = '';
        wardNameInput.value = '';
        resetFee();

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
        districtNameInput.value = this.value ? this.options[this.selectedIndex].text : '';
        wardNameInput.value = '';
        resetFee();

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
        wardNameInput.value = this.value ? this.options[this.selectedIndex].text : '';

        if (!this.value || !districtSelect.value) {
            resetFee();
            return;
        }

        requestFee(districtSelect.value, this.value);
    });

    // Đánh số mỗi lần tính phí: khách đổi địa chỉ nhanh thì kết quả của lần
    // gọi cũ về sau không được ghi đè kết quả của lần mới.
    let feeRequestId = 0;

    function requestFee(districtId, wardCode) {
        const requestId = ++feeRequestId;
        applyTotals(0);
        setFeeText('Đang tính cước...', false);
        setOrderEnabled(false);

        fetch("{{ route('locations.fee') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                to_district_id: districtId,
                to_ward_code: wardCode
            })
        })
            .then(async res => {
                // Kiểm tra mã HTTP trước khi đọc JSON (419 = hết phiên/CSRF).
                if (res.status === 419) {
                    throw { message: 'Phiên đã hết hạn, vui lòng tải lại trang', plain: true };
                }
                if (!res.ok) {
                    let message = 'Lỗi máy chủ (' + res.status + ')';
                    try {
                        const body = await res.json();
                        if (body && body.message) message = body.message;
                    } catch (e) { /* body không phải JSON */ }
                    throw { message: message };
                }
                return res.json();
            })
            .then(res => {
                if (requestId !== feeRequestId) return;

                const fee = Number(res && res.data ? res.data.total : NaN);
                // Chỉ nhận là thành công khi GHN trả code 200 VÀ phí > 0.
                if (res && res.code === 200 && fee > 0) {
                    applyTotals(fee);
                    setFeeText(formatVnd(fee) + ' VNĐ', false);
                    setOrderEnabled(true);
                } else {
                    showFeeError((res && res.message) || 'GHN không trả về phí hợp lệ.');
                }
            })
            .catch(err => {
                if (requestId !== feeRequestId) return;
                console.error("Lỗi tính phí:", err);
                showFeeError((err && err.message) || 'Lỗi kết nối máy chủ.', err && err.plain);
            });
    }

    function formatVnd(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    // Chỉ tính và ghi tổng tiền + phí vào input ẩn, KHÔNG đụng dòng chữ phí ship
    // (dòng đó do setFeeText() lo) để thông báo lỗi không bị ghi đè thành "0 VNĐ".
    function applyTotals(fee) {
        // Tiền hàng - Giảm giá + Phí ship = Tổng thanh toán (không âm).
        const finalAmount = Math.max(0, subtotal - discountAmount + fee);
        finalTotalText.innerText = formatVnd(finalAmount) + ' VNĐ';
        if (totalPriceInput) {
            totalPriceInput.value = finalAmount;
        }
        if (ghnFeeInput) {
            // Chưa có phí hợp lệ thì để rỗng, không gửi 0.
            ghnFeeInput.value = fee > 0 ? fee : '';
        }
    }

    function setFeeText(text, isError) {
        shippingFeeText.innerText = text;
        shippingFeeText.classList.toggle('text-red-600', !!isError);
    }

    function setOrderEnabled(enabled) {
        placeOrderBtn.disabled = !enabled;
    }

    // Địa chỉ chưa đủ: huỷ kết quả đang chờ, xoá phí, khoá nút Đặt hàng.
    function resetFee() {
        feeRequestId++;
        applyTotals(0);
        setFeeText('-- Chọn địa chỉ để tính phí --', false);
        setOrderEnabled(false);
    }

    function showFeeError(message, plain) {
        applyTotals(0);
        setFeeText(plain ? message : 'Không tính được phí: ' + message, true);
        setOrderEnabled(false);
    }

    // Trang tải lại mà đã có sẵn địa chỉ (old() sau lỗi validate, hoặc trình
    // duyệt tự điền lại form) thì tính lại phí luôn, không bắt khách chọn lại.
    function recalcIfPrefilled() {
        const districtId = districtSelect.value || toDistrictIdInput.value;
        const wardCode = wardSelect.value || toWardCodeInput.value;
        if (districtId && wardCode) {
            toDistrictIdInput.value = districtId;
            toWardCodeInput.value = wardCode;
            requestFee(districtId, wardCode);
        }
    }

    // ===== Sổ địa chỉ (kiểu Shopee) =====
    let addresses = @json($addresses);
    // Địa chỉ ở các đơn đã đặt nhưng chưa có trong sổ — tick chọn là dùng được.
    const pastAddresses = @json($pastAddresses);
    let currentChoice = null; // 's<id>' = địa chỉ đã lưu, 'o<orderId>' = địa chỉ từ đơn cũ
    const oldAddressId = @json(old('address_id'));
    const hasOldInput = @json(session()->hasOldInput());
    const csrfToken = '{{ csrf_token() }}';
    const addressDefaultUrl = "{{ route('addresses.default', ['address' => '__ID__']) }}";
    const addressDestroyUrl = "{{ route('addresses.destroy', ['address' => '__ID__']) }}";

    const savedView = document.getElementById('saved_address_view');
    const newForm = document.getElementById('new_address_form');
    const backToSavedBtn = document.getElementById('back_to_saved_btn');
    const modal = document.getElementById('address_modal');
    const addressList = document.getElementById('address_list');
    const nameInput = document.getElementById('name');
    const phoneInput = document.getElementById('phone');
    const addressInput = document.getElementById('address');
    let modalChoiceId = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function findAddress(id) {
        return addresses.find(a => String(a.id) === String(id));
    }

    function defaultAddress() {
        return addresses.find(a => a.is_default) || addresses[0] || null;
    }

    function useChoice(key) {
        if (!key) return false;
        const saved = key[0] === 's' && findAddress(key.slice(1));
        if (saved) { useSavedAddress(saved); return true; }
        const past = key[0] === 'o' && pastAddresses.find(p => String(p.order_id) === key.slice(1));
        if (past) { usePastAddress(past); return true; }
        return false;
    }

    function fillSummary(a, defaultBadge, pastLabel) {
        document.getElementById('sa_name').textContent = a.name;
        document.getElementById('sa_phone').textContent = a.phone;
        document.getElementById('sa_full').textContent = a.full_address;
        document.getElementById('sa_default').classList.toggle('hidden', !defaultBadge);
        const pastBadge = document.getElementById('sa_past');
        pastBadge.textContent = pastLabel || '';
        pastBadge.classList.toggle('hidden', !pastLabel);

        savedView.classList.remove('hidden');
        newForm.classList.add('hidden');
        backToSavedBtn.classList.add('hidden');
    }

    // Dùng địa chỉ của 1 đơn đã đặt: gửi như địa chỉ mới (kèm tên Tỉnh/Quận/
    // Phường) và tự lưu vào sổ địa chỉ để lần sau có sẵn.
    function usePastAddress(p) {
        currentChoice = 'o' + p.order_id;
        addressIdInput.value = '';
        nameInput.value = p.name;
        phoneInput.value = p.phone;
        addressInput.value = p.address;
        toDistrictIdInput.value = p.district_id;
        toWardCodeInput.value = p.ward_code;
        provinceIdInput.value = p.province_id || '';
        provinceNameInput.value = p.province_name || '';
        districtNameInput.value = p.district_name || '';
        wardNameInput.value = p.ward_name || '';
        document.getElementById('save_address_input').checked = true;

        fillSummary(p, false, 'Đã dùng ở đơn #' + p.order_id);
        requestFee(p.district_id, p.ward_code);
    }

    // Dùng 1 địa chỉ đã lưu: điền sẵn các ô (để form hợp lệ — server vẫn lấy
    // lại dữ liệu từ DB theo address_id) rồi tính phí ship ngay.
    function useSavedAddress(address) {
        currentChoice = 's' + address.id;
        addressIdInput.value = address.id;
        nameInput.value = address.name;
        phoneInput.value = address.phone;
        addressInput.value = address.address;
        toDistrictIdInput.value = address.district_id;
        toWardCodeInput.value = address.ward_code;

        fillSummary(address, address.is_default, null);
        requestFee(address.district_id, address.ward_code);
    }

    // Nhập địa chỉ mới: xoá dữ liệu của địa chỉ đã chọn trước đó.
    function useNewAddressForm(clear) {
        currentChoice = null;
        addressIdInput.value = '';
        if (clear) {
            [nameInput, phoneInput, addressInput, toDistrictIdInput, toWardCodeInput,
                provinceIdInput, provinceNameInput, districtNameInput, wardNameInput].forEach(el => el.value = '');
            provinceSelect.value = '';
            districtSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành trước --</option>';
            districtSelect.disabled = true;
            wardSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện trước --</option>';
            wardSelect.disabled = true;
            resetFee();
        }

        savedView.classList.add('hidden');
        newForm.classList.remove('hidden');
        backToSavedBtn.classList.toggle('hidden', addresses.length === 0 && pastAddresses.length === 0);
    }

    function renderAddressList() {
        const savedHtml = addresses.map(a => `
            <div class="flex items-start gap-3 px-5 py-4">
                <input type="radio" name="address_choice" value="s${a.id}" id="address_choice_${a.id}" ${'s' + a.id === modalChoiceId ? 'checked' : ''} class="mt-1 w-4 h-4 accent-[#6B8E23]">
                <label for="address_choice_${a.id}" class="flex-1 min-w-0 cursor-pointer space-y-1">
                    <p class="text-gray-800"><span class="font-semibold">${escapeHtml(a.name)}</span><span class="text-gray-300 mx-1">|</span><span class="text-gray-600">${escapeHtml(a.phone)}</span></p>
                    <p class="text-sm text-gray-600 break-words">${escapeHtml(a.full_address)}</p>
                    ${a.is_default ? '<span class="inline-block text-xs text-[#6B8E23] border border-[#6B8E23] rounded px-1.5 py-0.5">Mặc định</span>' : ''}
                </label>
                <div class="shrink-0 flex flex-col items-end gap-1.5 text-xs">
                    ${a.is_default ? '' : `<button type="button" data-default="${a.id}" class="text-gray-600 border border-[#8C9680]/40 rounded px-2 py-1 hover:bg-[#F8F9F5]">Thiết lập mặc định</button>`}
                    <button type="button" data-delete="${a.id}" class="text-red-600 hover:underline">Xoá</button>
                </div>
            </div>
        `).join('');

        const pastHtml = pastAddresses.length ? `
            <p class="px-5 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Địa chỉ từ đơn hàng trước</p>
            ${pastAddresses.map(p => `
                <div class="flex items-start gap-3 px-5 py-4">
                    <input type="radio" name="address_choice" value="o${p.order_id}" id="past_choice_${p.order_id}" ${'o' + p.order_id === modalChoiceId ? 'checked' : ''} class="mt-1 w-4 h-4 accent-[#6B8E23]">
                    <label for="past_choice_${p.order_id}" class="flex-1 min-w-0 cursor-pointer space-y-1">
                        <p class="text-gray-800"><span class="font-semibold">${escapeHtml(p.name)}</span><span class="text-gray-300 mx-1">|</span><span class="text-gray-600">${escapeHtml(p.phone)}</span></p>
                        <p class="text-sm text-gray-600 break-words">${escapeHtml(p.full_address)}</p>
                        <span class="inline-block text-xs text-gray-500 border border-gray-300 rounded px-1.5 py-0.5">Đã dùng ở đơn #${p.order_id}</span>
                    </label>
                </div>
            `).join('')}` : '';

        addressList.innerHTML = (savedHtml + pastHtml) || '<p class="px-5 py-6 text-sm text-gray-500">Bạn chưa lưu địa chỉ nào.</p>';
    }

    function openModal() {
        modalChoiceId = currentChoice || (defaultAddress() ? 's' + defaultAddress().id : null);
        renderAddressList();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function sendAddressRequest(url, method) {
        const res = await fetch(url, {
            method: method,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        if (!res.ok) throw new Error('Lỗi máy chủ (' + res.status + ')');
        const body = await res.json();
        addresses = body.addresses || [];
    }

    addressList.addEventListener('change', e => {
        if (e.target.name === 'address_choice') modalChoiceId = e.target.value;
    });

    addressList.addEventListener('click', async e => {
        const defaultId = e.target.dataset.default;
        const deleteId = e.target.dataset.delete;
        if (!defaultId && !deleteId) return;

        try {
            if (defaultId) {
                await sendAddressRequest(addressDefaultUrl.replace('__ID__', defaultId), 'PATCH');
            } else {
                if (!confirm('Xoá địa chỉ này khỏi sổ địa chỉ?')) return;
                await sendAddressRequest(addressDestroyUrl.replace('__ID__', deleteId), 'DELETE');
                if (modalChoiceId === 's' + deleteId) modalChoiceId = defaultAddress() ? 's' + defaultAddress().id : null;
            }
        } catch (err) {
            alert(err.message || 'Không cập nhật được địa chỉ.');
            return;
        }

        renderAddressList();

        // Địa chỉ đang dùng bị xoá/đổi: cập nhật lại khung địa chỉ phía ngoài.
        if (addressIdInput.value) {
            const current = findAddress(addressIdInput.value);
            if (current) {
                useSavedAddress(current);
            } else if (defaultAddress()) {
                useSavedAddress(defaultAddress());
            } else {
                closeModal();
                useNewAddressForm(true);
            }
        }
    });

    document.getElementById('change_address_btn').addEventListener('click', openModal);
    document.getElementById('address_modal_cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    document.getElementById('address_modal_confirm').addEventListener('click', function () {
        useChoice(modalChoiceId);
        closeModal();
    });

    document.getElementById('add_address_btn').addEventListener('click', function () {
        closeModal();
        useNewAddressForm(true);
        nameInput.focus();
    });

    backToSavedBtn.addEventListener('click', function () {
        const chosen = defaultAddress();
        if (chosen) useSavedAddress(chosen);
        else if (pastAddresses[0]) usePastAddress(pastAddresses[0]);
    });

    // Khởi tạo: có sổ địa chỉ thì chọn sẵn địa chỉ mặc định (hoặc địa chỉ đã
    // chọn trước khi bị lỗi validate); khách đang nhập địa chỉ mới dở dang
    // (old input không có address_id) thì giữ nguyên form nhập.
    function initAddress() {
        const initial = oldAddressId ? findAddress(oldAddressId) : (hasOldInput ? null : defaultAddress());
        if (initial) {
            useSavedAddress(initial);
        } else if (!hasOldInput && pastAddresses[0]) {
            // Chưa có sổ địa chỉ: chọn sẵn địa chỉ của đơn gần nhất.
            usePastAddress(pastAddresses[0]);
        } else {
            useNewAddressForm(false);
            recalcIfPrefilled();
        }
    }

    initAddress();
    // Quay lại trang bằng nút Back (bfcache): kiểm tra lại cho chắc.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        if (!useChoice(currentChoice)) recalcIfPrefilled();
    });

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
            return;
        }
        // Chưa tính được phí GHN thì không cho gửi đơn (kể cả bấm Enter).
        if (!ghnFeeInput.value) {
            e.preventDefault();
            alert('Chưa tính được phí vận chuyển cho địa chỉ này.');
        }
    });
});
</script>
@endsection
