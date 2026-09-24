@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id . ' · Cây Cảnh Shop')

@php
    $statusClasses = [
        'open' => 'bg-red-50 border-red-200 text-red-700',
        'answered' => 'bg-green-background border-green-border/50 text-text-primary',
        'closed' => 'bg-gray-100 border-gray-200 text-gray-500',
    ];
    $statusLabels = [
        'open' => 'Đang mở',
        'answered' => 'Đã trả lời',
        'closed' => 'Đã đóng',
    ];
    $statusClass = $statusClasses[$ticket->status] ?? $statusClasses['open'];
    $statusLabel = $statusLabels[$ticket->status] ?? $ticket->status;
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center flex-wrap gap-4">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Ticket #{{ $ticket->id }}</h2>
    <a href="{{ route('admin.tickets.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại danh sách
    </a>
</div>

@if (session('success'))
    <div class="mb-6 px-5 py-4 bg-green-background border border-green-border/50 text-text-primary rounded-2xl text-sm">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-6 px-5 py-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Thông tin khách hàng -->
    <div class="md:col-span-2 bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Chủ đề</h3>
        <p class="text-text-primary font-medium mb-4">{{ $ticket->subject }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Khách hàng</div>
                <div class="text-text-primary font-medium">{{ $ticket->user->name ?? 'Người dùng đã xoá' }}</div>
                @if($ticket->user && $ticket->user->email)
                    <div class="text-text-secondary text-sm mt-1">{{ $ticket->user->email }}</div>
                @endif
            </div>
            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Ngày tạo</div>
                <div class="text-text-primary text-sm">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <!-- Trạng thái -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Trạng thái</h3>

        <div class="mb-5">
            <span class="px-3 py-1 border rounded-pill text-xs mono font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>

        <form action="{{ route('admin.tickets.updateStatus', $ticket) }}" method="POST">
            @csrf
            @method('PATCH')
            <label for="status" class="block text-sm font-semibold text-text-secondary mono mb-2">Đổi trạng thái</label>
            <select name="status" id="status" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5] mb-4">
                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Đang mở</option>
                <option value="answered" {{ $ticket->status === 'answered' ? 'selected' : '' }}>Đã trả lời</option>
                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Đã đóng</option>
            </select>
            @error('status')
                <p class="text-xs text-red-600 mb-3">{{ $message }}</p>
            @enderror
            <button type="submit" class="w-full px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center justify-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i> Cập nhật trạng thái
            </button>
        </form>
    </div>
</div>

<!-- Hội thoại -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Hội thoại</h3>

    <div class="flex flex-col gap-3 mb-6">
        @forelse($ticket->replies as $reply)
            @php $isAdminReply = $reply->user && $reply->user->role === 'admin'; @endphp
            <div class="flex {{ $isAdminReply ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] px-4 py-3 rounded-2xl {{ $isAdminReply ? 'bg-green-primary text-white rounded-br-md' : 'bg-green-background text-text-primary rounded-bl-md' }}">
                    <p class="mono text-[10.5px] uppercase tracking-wider mb-1 {{ $isAdminReply ? 'text-white/75' : 'text-text-secondary' }}">
                        {{ $isAdminReply ? 'Admin' : ($reply->user->name ?? 'Khách hàng') }} &middot; {{ $reply->created_at->format('d/m/Y H:i') }}
                    </p>
                    <p class="text-sm leading-relaxed whitespace-pre-line m-0">{{ $reply->message }}</p>
                </div>
            </div>
        @empty
            <p class="text-center text-text-secondary italic py-6">Chưa có tin nhắn nào trong ticket này.</p>
        @endforelse
    </div>

    @if($ticket->status === 'closed')
        <div class="px-5 py-4 bg-gray-100 border border-gray-200 text-gray-500 rounded-2xl text-sm text-center">
            Ticket đã đóng, không thể trả lời thêm. Vui lòng mở lại trạng thái nếu cần tiếp tục hỗ trợ.
        </div>
    @else
        <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST">
            @csrf
            <label for="message" class="block text-sm font-semibold text-text-primary mb-2 mono">Trả lời khách hàng</label>
            <textarea name="message" id="message" rows="4" placeholder="Nhập nội dung trả lời..." required maxlength="5000"
                      class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">{{ old('message') }}</textarea>
            @error('message')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
            <div class="flex justify-end mt-4">
                <button type="submit" class="px-8 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Gửi trả lời
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
