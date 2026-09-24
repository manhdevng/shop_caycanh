@extends('layouts.app')

@section('title', 'Chi tiết người dùng · Cây Cảnh Shop')

@php
    $roleLabels = [
        'admin' => 'Quản trị viên',
        'customer' => 'Khách hàng',
    ];
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Người dùng #{{ $user->id }}</h2>
    <a href="{{ route('admin.users.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại danh sách
    </a>
</div>

<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Thông tin cơ bản</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Họ tên</div>
            <div class="text-text-primary font-medium">{{ $user->name }}</div>
        </div>

        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Email</div>
            <div class="text-text-primary font-medium">{{ $user->email }}</div>
        </div>

        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Vai trò</div>
            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                {{ $roleLabels[$user->role] ?? $user->role }}
            </span>
        </div>

        @if($user->created_at)
        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Ngày tạo</div>
            <div class="text-text-primary text-sm">{{ $user->created_at->format('d/m/Y H:i') }}</div>
        </div>
        @endif
    </div>

    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-green-border/20">
        <a href="{{ route('admin.users.edit', $user->id) }}" class="px-8 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2 text-decoration-none">
            <i data-lucide="edit-2" class="w-5 h-5"></i> Sửa thông tin
        </a>
    </div>
</div>
@endsection
