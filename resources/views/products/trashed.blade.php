@extends('layouts.app')

@section('title', 'Thùng rác sản phẩm · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Thùng rác</h2>
        <a class="px-6 py-3 bg-white text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('products.index') }}">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Quay lại danh sách sản phẩm
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">ID</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đã xóa lúc</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ $product->id }}</td>
                    <td class="py-5 pr-4 font-medium text-text-primary text-lg">{{ $product->name }}</td>
                    <td class="py-5 pr-4 text-text-secondary text-sm">{{ $product->deleted_at->format('d/m/Y H:i') }}</td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('products.restore', $product->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-4 py-2 bg-green-primary border border-green-border text-white rounded-pill text-sm font-medium hover:bg-green-accent transition-colors flex items-center gap-1">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Khôi phục
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-12 text-center text-text-secondary">
                        Thùng rác trống.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection
