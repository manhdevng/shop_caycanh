@extends('layouts.app')

@section('title', 'Dashboard · Cây Cảnh Shop')

@section('content')
<div class="mb-8">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Dashboard</h2>
    <p class="text-text-secondary mt-2">Xin chào, {{ Auth::user()->name }} 👋</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-[24px] p-6 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i data-lucide="package" class="w-5 h-5 text-text-secondary"></i>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng sản phẩm</span>
        </div>
        <div class="text-4xl font-medium gloock text-text-primary">{{ $totalProducts }}</div>
    </div>

    <div class="bg-white rounded-[24px] p-6 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i data-lucide="eye" class="w-5 h-5 text-text-secondary"></i>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Đang hiển thị</span>
        </div>
        <div class="text-4xl font-medium gloock text-text-primary">{{ $activeProducts }}</div>
    </div>

    <div class="bg-white rounded-[24px] p-6 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i data-lucide="folder-tree" class="w-5 h-5 text-text-secondary"></i>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Nhóm danh mục</span>
        </div>
        <div class="text-4xl font-medium gloock text-text-primary">{{ $totalCategories }}</div>
    </div>
</div>

<div class="flex flex-wrap gap-3 mt-8">
    <a href="{{ route('products.index') }}" class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border">
        <i data-lucide="package" class="w-4 h-4"></i> Quản lý sản phẩm
    </a>
    <a href="{{ route('categories.index') }}" class="px-6 py-3 bg-white text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors flex items-center gap-2 text-decoration-none border border-green-border">
        <i data-lucide="folder-tree" class="w-4 h-4"></i> Quản lý danh mục
    </a>
</div>
@endsection
