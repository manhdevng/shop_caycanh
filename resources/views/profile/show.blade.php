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
            <div class="flex items-center gap-4">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="Ảnh đại diện của {{ $user->name }}" class="w-16 h-16 rounded-full object-cover border border-[#8C9680]/30">
                @else
                    <div class="w-16 h-16 rounded-full bg-[#CED1C3] border border-[#8C9680]/30 flex items-center justify-center text-xl font-semibold text-gray-700" role="img" aria-label="Chưa có ảnh đại diện">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <input type="file" name="avatar" id="avatar" accept="image/*" class="text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#CED1C3] file:text-gray-700 hover:file:bg-[#B6CC9D]">
            </div>
            @error('avatar') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-semibold mb-1">Họ và tên</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Nguyễn Văn A">
            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold mb-1">Email</label>
            <input type="text" id="email" value="{{ $user->email }}" disabled class="w-full border border-[#8C9680]/40 rounded-xl px-4 py-2.5 bg-[#F8F9F5] text-gray-500 cursor-not-allowed">
            <p class="text-xs text-gray-400 mt-1">Email không thể thay đổi.</p>
        </div>

        <div>
            <label for="phone" class="block text-sm font-semibold mb-1">Số điện thoại</label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="09xx xxx xxx">
            @error('phone') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="address" class="block text-sm font-semibold mb-1">Địa chỉ</label>
            <textarea name="address" id="address" rows="3" class="w-full border border-[#8C9680] rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#6B8E23]/40" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành...">{{ old('address', $user->address) }}</textarea>
            @error('address') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full bg-[#6B8E23] hover:bg-[#4A6B1F] text-white font-semibold py-3 rounded-full transition-colors">
            Lưu thay đổi
        </button>
    </form>

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
