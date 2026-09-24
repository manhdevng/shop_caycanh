@extends('layouts.app')

@section('title', 'Cài đặt · Cây Cảnh Shop')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Cài đặt Hệ thống</h2>
</div>

@if (session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-2xl p-4 mb-6">
    {{ session('success') }}
</div>
@endif

@if ($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-6">
    <strong>Có lỗi xảy ra:</strong>
    <ul class="mt-2 list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-2 flex items-center gap-3">
        <i data-lucide="image" class="w-6 h-6 text-green-border"></i>
        Ảnh/video khối giới thiệu trang chủ
    </h3>
    <p class="text-sm text-text-secondary mb-8">Đổi ảnh hoặc video cho 3 mục hiển thị ở trang chủ (bên dưới danh sách sản phẩm). Bỏ trống nếu muốn giữ nguyên khối placeholder mặc định.</p>

    <form action="{{ route('settings.home-features.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($homeFeatures as $slug => $feature)
                <div class="bg-[#f8f9f5] rounded-2xl border border-green-border/30 p-5">
                    <h4 class="font-semibold text-text-primary mb-4 text-sm">{{ $feature->title }}</h4>

                    <div class="mb-4">
                        @if($feature->media_path)
                            @if($feature->isVideo())
                                <video src="{{ $feature->media_url }}" class="w-full aspect-square object-cover rounded-xl border border-green-border/50" muted loop playsinline controls></video>
                            @else
                                <img src="{{ $feature->media_url }}" class="w-full aspect-square object-cover rounded-xl border border-green-border/50" alt="{{ $feature->title }}">
                            @endif
                        @else
                            <div class="w-full aspect-square rounded-xl border border-dashed border-green-border/50 flex items-center justify-center text-xs text-text-secondary text-center px-2">
                                Chưa có ảnh/video<br>(đang dùng placeholder mặc định)
                            </div>
                        @endif
                    </div>

                    <label class="block text-xs font-semibold text-text-primary mb-1 mono">Loại nội dung</label>
                    <select name="media_type[{{ $slug }}]" class="w-full rounded-xl border-green-border/50 border px-3 py-2 mb-3 text-sm bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none">
                        <option value="image" {{ $feature->media_type === 'image' ? 'selected' : '' }}>Ảnh</option>
                        <option value="video" {{ $feature->media_type === 'video' ? 'selected' : '' }}>Video</option>
                    </select>

                    <label class="block text-xs font-semibold text-text-primary mb-1 mono">Thay ảnh/video mới</label>
                    <input type="file" name="media[{{ $slug }}]" accept="image/*,video/*" class="w-full rounded-xl border-green-border/50 border px-3 py-2 text-xs text-text-secondary bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none">
                </div>
            @endforeach
        </div>

        <div class="mt-8 pt-6 border-t border-green-border/20 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-[#5C2323] text-white rounded-pill font-semibold hover:opacity-90 transition-all flex items-center gap-2 text-sm">
                <i data-lucide="save" class="w-4 h-4"></i> Lưu thay đổi
            </button>
        </div>
    </form>
</div>
@endsection
