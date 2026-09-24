@extends('layouts.app')

@section('title', $category->name . ' · Cây Cảnh Shop')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        @if($category->parent)
            <a href="{{ route('categories.show', $category->parent->id) }}" class="text-sm text-text-secondary hover:text-text-primary mono flex items-center gap-1 mb-2">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> {{ $category->parent->name }}
            </a>
        @endif
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">{{ $category->name }}</h2>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('categories.edit', $category->id) }}" class="px-5 py-2 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none flex items-center gap-2 text-sm">
            <i data-lucide="edit-2" class="w-4 h-4"></i> Sửa
        </a>
        <a href="{{ route('categories.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại danh sách
        </a>
    </div>
</div>

@if(!$category->parent)
<!-- Danh mục cha: hiển thị danh sách các loại cây con -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Các loại cây trong nhóm này</h3>

    @forelse($category->children as $child)
    <a href="{{ route('categories.show', $child->id) }}" class="flex items-center justify-between p-4 mb-2 rounded-2xl border border-green-border/30 hover:bg-green-background/20 transition-colors text-decoration-none">
        <div class="flex items-center gap-3">
            <i data-lucide="leaf" class="w-4 h-4 text-text-secondary"></i>
            <span class="text-text-primary font-medium">{{ $child->name }}</span>
        </div>
        <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-secondary rounded-pill text-xs mono">
            {{ $child->products->count() }} sản phẩm
        </span>
    </a>
    @empty
    <div class="py-8 text-center text-text-secondary italic">
        Nhóm này chưa có loại cây con nào. <a href="{{ route('categories.create') }}" class="text-green-700 underline">Thêm loại cây mới</a>.
    </div>
    @endforelse
</div>
@endif

<!-- Sản phẩm thuộc danh mục này -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">
        Sản phẩm {{ $category->parent ? 'thuộc loại "' . $category->name . '"' : 'trong nhóm này' }}
    </h3>

    @if($category->products->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($category->products as $product)
        <a href="{{ route('products.edit', $product->id) }}" class="block border border-green-border/30 rounded-2xl overflow-hidden hover:shadow-md transition-shadow text-decoration-none">
            @if($product->main_image)
                <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" class="w-full h-32 object-cover">
            @else
                <div class="w-full h-32 bg-green-background flex items-center justify-center text-green-border">
                    <i data-lucide="image" class="w-6 h-6"></i>
                </div>
            @endif
            <div class="p-3">
                <div class="text-text-primary font-medium text-sm">{{ $product->name }}</div>
                <div class="text-text-secondary text-xs mono mt-1">{{ number_format($product->base_price, 0, ',', '.') }} đ</div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="py-8 text-center text-text-secondary italic">
        Chưa có sản phẩm nào thuộc danh mục này.
        <a href="{{ route('products.create') }}" class="text-green-700 underline">Thêm sản phẩm mới</a>.
    </div>
    @endif
</div>
@endsection
