@extends('layouts.shop')
@section('title', 'Hồ sơ của tôi')
@section('content')

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl md:text-4xl mb-6" style="font-family:'Gloock',serif">Hồ sơ của tôi</h1>

    <section style="margin-bottom:32px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px"><h2 style="font-family:'Anton',sans-serif;font-size:24px;text-transform:uppercase;margin:0">Đơn mua</h2><a href="{{ route('orders.history') }}" style="font-size:12px;color:#5C2323;text-decoration:underline">Xem tất cả</a></div>
        @php
            $orderStats = [
                ['tab' => 'cho-thanh-toan', 'label' => 'Chờ thanh toán', 'icon' => 'credit-card', 'count' => data_get($orderStageCounts, 'cho-thanh-toan', 0)],
                ['tab' => 'cho-lay-hang', 'label' => 'Chờ lấy hàng', 'icon' => 'package', 'count' => data_get($orderStageCounts, 'cho-lay-hang', 0)],
                ['tab' => 'dang-giao', 'label' => 'Đang giao', 'icon' => 'truck', 'count' => data_get($orderStageCounts, 'dang-giao', 0)],
                ['tab' => 'da-giao', 'label' => 'Chờ đánh giá', 'icon' => 'star', 'count' => data_get($orderStageCounts, 'cho-danh-gia', 0)],
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px">
            @foreach($orderStats as $stat)
                <a href="{{ route('orders.history', ['tab' => $stat['tab']]) }}" style="display:flex;align-items:center;gap:10px;min-width:0;border:1px solid #E5E2DC;border-radius:16px;padding:14px;background:#FFFFFF">
                    <i data-lucide="{{ $stat['icon'] }}" style="width:20px;height:20px;flex:none;color:#4A6B1F"></i>
                    <span style="min-width:0"><strong style="display:block;font-family:'Anton',sans-serif;font-size:22px;line-height:1">{{ $stat['count'] }}</strong><span style="display:block;font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;overflow-wrap:anywhere">{{ $stat['label'] }}</span></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Điểm thành viên & hạng (P2.2) --}}
    @php
        $tierLabels = [
            'member' => 'Thành viên',
            'silver' => 'Bạc',
            'gold' => 'Vàng',
            'platinum' => 'Bạch kim',
        ];
        $tierColors = [
            'member' => 'bg-[#E5E2DC] text-[#1C1C1A]',
            'silver' => 'bg-slate-200 text-slate-800',
            'gold' => 'bg-amber-200 text-amber-900',
            'platinum' => 'bg-indigo-200 text-indigo-900',
        ];
        $tierKey = $user->tier ?? 'member';
    @endphp
    <div class="bg-white border border-[#8C9680]/30 rounded-2xl p-6 md:p-8 mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="text-sm text-gray-500 mb-1">Điểm tích lũy</p>
            <p class="text-2xl font-semibold">{{ number_format($user->points ?? 0) }} điểm</p>
        </div>
        <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $tierColors[$tierKey] ?? $tierColors['member'] }}">
            Hạng {{ $tierLabels[$tierKey] ?? 'Thành viên' }}
        </span>
    </div>

    {{-- Thông báo thành công/lỗi chung đã được layout (layouts/shop) tự hiển thị
         (xem session('success')/session('error') trong main content). Ở đây chỉ
         cần hiển thị lỗi validate theo từng trường bằng @error bên dưới mỗi ô. --}}

    {{-- ==== Form 1: Cập nhật hồ sơ ==== --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-white border border-[#8C9680]/30 rounded-2xl p-6 md:p-8 space-y-5 mb-8">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-semibold mb-2">Ảnh đại diện</label>
            <div class="flex flex-wrap items-center gap-4">
                @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="Ảnh đại diện của {{ $user->name }}" class="w-16 h-16 rounded-full object-cover border border-[#8C9680]/30">
                @else
                    <div class="w-16 h-16 rounded-full bg-[#CED1C3] border border-[#8C9680]/30 flex items-center justify-center text-xl font-semibold text-gray-700" role="img" aria-label="Chưa có ảnh đại diện">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <input type="file" name="avatar" id="avatar" accept="image/*" class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#CED1C3] file:text-gray-700 hover:file:bg-[#B6CC9D]" style="max-width:100%;min-width:0">
            </div>
            @error('avatar') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-semibold mb-1">Họ và tên</label>
            <input type="text" name="name" id="name" value="{{ old('address_form_id') === null ? old('name', $user->name) : $user->name }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Nguyễn Văn A">
            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold mb-1">Email</label>
            <input type="text" id="email" value="{{ $user->email }}" disabled class="w-full border border-[#8C9680]/40 rounded-xl px-4 py-2.5 bg-[#F8F9F5] text-gray-500 cursor-not-allowed">
            <p class="text-xs text-gray-400 mt-1">Email không thể thay đổi.</p>
        </div>

        <div>
            <label for="phone" class="block text-sm font-semibold mb-1">Số điện thoại</label>
            <input type="tel" name="phone" id="phone" value="{{ old('address_form_id') === null ? old('phone', $user->phone) : $user->phone }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="09xx xxx xxx">
            @error('phone') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <p class="text-sm text-gray-500">Địa chỉ nhận hàng được quản lý ở mục <a href="#so-dia-chi" class="font-semibold text-[#4A6B1F] hover:underline">Sổ địa chỉ</a> bên dưới.</p>

        <button type="submit" class="w-full bg-[#6B8E23] hover:bg-[#4A6B1F] text-white font-semibold py-3 rounded-full transition-colors">
            Lưu thay đổi
        </button>
    </form>

    {{-- ==== Sổ địa chỉ nhận hàng (kiểu Shopee) ==== --}}
    @php
        $addressErrors = $errors->getBag('address');
        $inputClass = 'w-full border border-[#8C9680] rounded-xl px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40';
        $selectClass = 'w-full border border-[#8C9680] rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40 disabled:bg-[#F8F9F5] disabled:text-gray-400';
    @endphp
    <section id="so-dia-chi" class="bg-white border border-[#8C9680]/30 rounded-2xl p-6 md:p-8 mb-8 scroll-mt-24">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Sổ địa chỉ</h2>
                <p class="text-sm text-gray-500">Địa chỉ <span class="font-semibold text-[#4A6B1F]">mặc định</span> sẽ được điền sẵn khi bạn thanh toán (bạn vẫn đổi được địa chỉ khác lúc đặt hàng).</p>
            </div>
            <button type="button" id="addr_add_btn" class="shrink-0 text-sm font-semibold text-[#4A6B1F] border border-[#6B8E23] rounded-full px-4 py-2 hover:bg-[#F8F9F5]">+ Thêm địa chỉ mới</button>
        </div>

        <div class="divide-y divide-[#8C9680]/20">
            @forelse($addresses as $savedAddress)
                <div class="flex flex-wrap items-start justify-between gap-4 py-4">
                    <div class="min-w-0 space-y-1">
                        <p class="text-gray-800">
                            <span class="font-semibold">{{ $savedAddress->name }}</span>
                            <span class="text-gray-300 mx-1">|</span>
                            <span class="text-gray-600">{{ $savedAddress->phone }}</span>
                        </p>
                        <p class="text-sm text-gray-600 break-words">{{ $savedAddress->fullAddress() }}</p>
                        @if($savedAddress->is_default)
                            <span class="inline-block text-xs text-[#6B8E23] border border-[#6B8E23] rounded px-1.5 py-0.5">Mặc định</span>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-2 text-sm">
                        <div class="flex items-center gap-3">
                            <button type="button" class="addr-edit-btn font-semibold text-[#4A6B1F] hover:underline" data-address="{{ json_encode(\App\Http\Controllers\User\UserAddressController::toArray($savedAddress)) }}">Sửa</button>
                            <form method="POST" action="{{ route('addresses.destroy', $savedAddress) }}" onsubmit="return confirm('Xoá địa chỉ này khỏi sổ địa chỉ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Xoá</button>
                            </form>
                        </div>
                        @unless($savedAddress->is_default)
                            <form method="POST" action="{{ route('addresses.default', $savedAddress) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs text-gray-600 border border-[#8C9680]/40 rounded px-2 py-1 hover:bg-[#F8F9F5]">Thiết lập mặc định</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <p class="py-4 text-sm text-gray-500">Bạn chưa lưu địa chỉ nào. Thêm địa chỉ để lần sau đặt hàng không phải nhập lại.</p>
            @endforelse
        </div>

        <form method="POST" id="addr_form" action="{{ route('addresses.store') }}" class="hidden mt-4 border border-[#8C9680]/30 rounded-xl p-5 bg-[#F8F9F5] space-y-4">
            @csrf
            <input type="hidden" name="_method" id="addr_method" value="PUT" disabled>
            <input type="hidden" name="address_form_id" id="addr_form_id" value="{{ old('address_form_id') }}">
            <input type="hidden" name="province_id" id="addr_province_id" value="{{ old('province_id') }}">
            <input type="hidden" name="province_name" id="addr_province_name" value="{{ old('province_name') }}">
            <input type="hidden" name="district_id" id="addr_district_id" value="{{ old('district_id') }}">
            <input type="hidden" name="district_name" id="addr_district_name" value="{{ old('district_name') }}">
            <input type="hidden" name="ward_code" id="addr_ward_code" value="{{ old('ward_code') }}">
            <input type="hidden" name="ward_name" id="addr_ward_name" value="{{ old('ward_name') }}">

            <h3 id="addr_form_title" class="font-semibold text-gray-800">Thêm địa chỉ mới</h3>

            @if($addressErrors->any())
                <ul class="text-sm text-red-600 list-disc pl-5 space-y-0.5">
                    @foreach($addressErrors->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="addr_name" class="block text-sm font-semibold mb-1">Họ và tên người nhận</label>
                    <input type="text" name="name" id="addr_name" value="{{ old('address_form_id') !== null ? old('name') : '' }}" class="{{ $inputClass }}" placeholder="Nguyễn Văn A" required>
                </div>
                <div>
                    <label for="addr_phone" class="block text-sm font-semibold mb-1">Số điện thoại</label>
                    <input type="tel" name="phone" id="addr_phone" value="{{ old('address_form_id') !== null ? old('phone') : '' }}" class="{{ $inputClass }}" placeholder="09xx xxx xxx" required>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-3">
                <div>
                    <label for="addr_province" class="block text-sm font-semibold mb-1">Tỉnh/Thành</label>
                    <select id="addr_province" class="{{ $selectClass }}"><option value="">-- Đang tải... --</option></select>
                </div>
                <div>
                    <label for="addr_district" class="block text-sm font-semibold mb-1">Quận/Huyện</label>
                    <select id="addr_district" disabled class="{{ $selectClass }}"><option value="">-- Chọn Tỉnh/Thành trước --</option></select>
                </div>
                <div>
                    <label for="addr_ward" class="block text-sm font-semibold mb-1">Phường/Xã</label>
                    <select id="addr_ward" disabled class="{{ $selectClass }}"><option value="">-- Chọn Quận/Huyện trước --</option></select>
                </div>
            </div>

            <div>
                <label for="addr_detail" class="block text-sm font-semibold mb-1">Địa chỉ cụ thể</label>
                <input type="text" name="address" id="addr_detail" value="{{ old('address_form_id') !== null ? old('address') : '' }}" class="{{ $inputClass }}" placeholder="Số nhà, tên đường..." required>
            </div>

            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="is_default" id="addr_is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="w-4 h-4 accent-[#6B8E23]">
                Đặt làm địa chỉ mặc định
            </label>

            <div class="flex justify-end gap-2">
                <button type="button" id="addr_cancel_btn" class="text-sm px-4 py-2 rounded-full border border-[#8C9680]/40 bg-white hover:bg-[#F8F9F5]">Huỷ</button>
                <button type="submit" class="text-sm font-semibold text-white bg-[#6B8E23] hover:bg-[#4A6B1F] px-5 py-2 rounded-full">Lưu địa chỉ</button>
            </div>
        </form>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('addr_form');
        const methodInput = document.getElementById('addr_method');
        const formIdInput = document.getElementById('addr_form_id');
        const title = document.getElementById('addr_form_title');
        const provinceSelect = document.getElementById('addr_province');
        const districtSelect = document.getElementById('addr_district');
        const wardSelect = document.getElementById('addr_ward');
        const field = id => document.getElementById(id);
        const storeUrl = "{{ route('addresses.store') }}";
        const updateUrl = "{{ route('addresses.update', ['address' => '__ID__']) }}";
        const provincesUrl = "{{ route('locations.provinces') }}";
        const districtsUrl = "{{ route('locations.districts', ['provinceId' => '__ID__']) }}";
        const wardsUrl = "{{ route('locations.wards', ['districtId' => '__ID__']) }}";

        // Đổ options vào select từ API GHN rồi chọn sẵn giá trị (nếu có).
        async function fill(select, url, valueKey, textKey, placeholder, selected) {
            select.disabled = true;
            select.innerHTML = '<option value="">-- Đang tải... --</option>';
            try {
                const res = await fetch(url).then(r => r.json());
                if (!res.data) throw new Error();
                select.innerHTML = `<option value="">-- ${placeholder} --</option>` + res.data
                    .map(item => `<option value="${item[valueKey]}">${item[textKey]}</option>`).join('');
                select.disabled = false;
                if (selected) select.value = String(selected);
            } catch (e) {
                select.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
            }
        }

        function resetSelect(select, text) {
            select.innerHTML = `<option value="">-- ${text} --</option>`;
            select.disabled = true;
        }

        function syncHidden(select, idField, nameField) {
            field(idField).value = select.value;
            field(nameField).value = select.value ? select.options[select.selectedIndex].text : '';
        }

        provinceSelect.addEventListener('change', async function () {
            syncHidden(provinceSelect, 'addr_province_id', 'addr_province_name');
            syncHidden({ value: '' }, 'addr_district_id', 'addr_district_name');
            syncHidden({ value: '' }, 'addr_ward_code', 'addr_ward_name');
            resetSelect(wardSelect, 'Chọn Quận/Huyện trước');
            if (!this.value) return resetSelect(districtSelect, 'Chọn Tỉnh/Thành trước');
            await fill(districtSelect, districtsUrl.replace('__ID__', this.value), 'DistrictID', 'DistrictName', 'Chọn Quận/Huyện');
        });

        districtSelect.addEventListener('change', async function () {
            syncHidden(districtSelect, 'addr_district_id', 'addr_district_name');
            syncHidden({ value: '' }, 'addr_ward_code', 'addr_ward_name');
            if (!this.value) return resetSelect(wardSelect, 'Chọn Quận/Huyện trước');
            await fill(wardSelect, wardsUrl.replace('__ID__', this.value), 'WardCode', 'WardName', 'Chọn Phường/Xã');
        });

        wardSelect.addEventListener('change', function () {
            syncHidden(wardSelect, 'addr_ward_code', 'addr_ward_name');
        });

        // Mở form: address = null là thêm mới, có address là sửa (chọn sẵn Tỉnh/Quận/Phường).
        async function openForm(address) {
            const editing = address && address.id;
            form.action = editing ? updateUrl.replace('__ID__', address.id) : storeUrl;
            methodInput.disabled = !editing;
            formIdInput.value = editing ? address.id : 'new';
            title.textContent = editing ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới';

            const a = address || {};
            field('addr_name').value = a.name || '';
            field('addr_phone').value = a.phone || '';
            field('addr_detail').value = a.address || '';
            field('addr_is_default').checked = !!a.is_default;
            field('addr_province_id').value = a.province_id || '';
            field('addr_province_name').value = a.province_name || '';
            field('addr_district_id').value = a.district_id || '';
            field('addr_district_name').value = a.district_name || '';
            field('addr_ward_code').value = a.ward_code || '';
            field('addr_ward_name').value = a.ward_name || '';

            form.classList.remove('hidden');
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            await loadSelects(a);
        }

        async function loadSelects(a) {
            resetSelect(districtSelect, 'Chọn Tỉnh/Thành trước');
            resetSelect(wardSelect, 'Chọn Quận/Huyện trước');
            await fill(provinceSelect, provincesUrl, 'ProvinceID', 'ProvinceName', 'Chọn Tỉnh/Thành', a.province_id);
            if (a.province_id) await fill(districtSelect, districtsUrl.replace('__ID__', a.province_id), 'DistrictID', 'DistrictName', 'Chọn Quận/Huyện', a.district_id);
            if (a.district_id) await fill(wardSelect, wardsUrl.replace('__ID__', a.district_id), 'WardCode', 'WardName', 'Chọn Phường/Xã', a.ward_code);
        }

        document.getElementById('addr_add_btn').addEventListener('click', () => openForm(null));
        document.getElementById('addr_cancel_btn').addEventListener('click', () => form.classList.add('hidden'));
        document.querySelectorAll('.addr-edit-btn').forEach(btn => {
            btn.addEventListener('click', () => openForm(JSON.parse(btn.dataset.address)));
        });

        // Chặn gửi khi chưa chọn đủ Tỉnh/Quận/Phường.
        form.addEventListener('submit', function (e) {
            if (!field('addr_province_id').value || !field('addr_district_id').value || !field('addr_ward_code').value) {
                e.preventDefault();
                alert('Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện và Phường/Xã.');
            }
        });

        // Lưu lỗi validate: mở lại form với dữ liệu khách vừa nhập.
        @if($addressErrors->any())
            (function () {
                const oldId = @json(old('address_form_id'));
                const editing = oldId && oldId !== 'new';
                form.action = editing ? updateUrl.replace('__ID__', oldId) : storeUrl;
                methodInput.disabled = !editing;
                title.textContent = editing ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới';
                form.classList.remove('hidden');
                form.scrollIntoView({ block: 'center' });
                loadSelects({
                    province_id: @json(old('province_id')),
                    district_id: @json(old('district_id')),
                    ward_code: @json(old('ward_code')),
                });
            })();
        @endif
    });
    </script>

    {{-- ==== Form 2: Đổi mật khẩu ==== --}}
    <form method="POST" action="{{ route('profile.password') }}" class="bg-white border border-[#8C9680]/30 rounded-2xl p-6 md:p-8 space-y-5">
        @csrf
        @method('PUT')

        <h2 class="text-xl font-semibold text-gray-800">Đổi mật khẩu</h2>

        <div>
            <label for="current_password" class="block text-sm font-semibold mb-1">Mật khẩu hiện tại</label>
            <input type="password" name="current_password" id="current_password" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40">
            @error('current_password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold mb-1">Mật khẩu mới</label>
            <input type="password" name="password" id="password" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40">
            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold mb-1">Nhập lại mật khẩu mới</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40">
            @error('password_confirmation') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full bg-[#5C2323] hover:opacity-90 text-white font-semibold py-3 rounded-full transition-colors">
            Đổi mật khẩu
        </button>
    </form>
</div>
@endsection
