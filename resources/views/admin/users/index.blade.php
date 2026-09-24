@extends('layouts.app')

@section('title', 'Người dùng · Cây Cảnh Shop')

@php
    $roleLabels = [
        'admin' => 'Quản trị viên',
        'customer' => 'Khách hàng',
    ];
@endphp

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Quản lý Người dùng</h2>
        <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('admin.users.create') }}">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            Thêm người dùng
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

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">ID</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Họ tên</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Email</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Vai trò</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ $user->id }}</td>
                    <td class="py-5 pr-4 font-medium text-text-primary">{{ $user->name }}</td>
                    <td class="py-5 pr-4 text-text-secondary">{{ $user->email }}</td>
                    <td class="py-5 pr-4">
                        <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                            {{ $roleLabels[$user->role] ?? $user->role }}
                        </span>
                    </td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="p-2 text-text-secondary hover:text-green-600 hover:bg-green-50 rounded-full transition-colors" title="Xem">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xóa">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-text-secondary">
                        Chưa có người dùng nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
