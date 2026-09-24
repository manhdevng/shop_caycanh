@extends('layouts.app')

@section('title', 'Blog (CMS) · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Blog (CMS)</h2>
        <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('admin.posts.create') }}">
            <i data-lucide="file-plus" class="w-5 h-5"></i>
            Viết bài mới
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
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tiêu đề</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ngày xuất bản</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4">
                        <div class="font-medium text-text-primary">{{ $post->title }}</div>
                        <div class="text-text-secondary mono text-xs mt-1">/cam-nang/{{ $post->slug }}</div>
                    </td>
                    <td class="py-5 pr-4">
                        @if($post->is_published)
                            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">Đã xuất bản</span>
                        @else
                            <span class="px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-pill text-xs mono font-semibold">Bản nháp</span>
                        @endif
                    </td>
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">
                        {{ $post->published_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>
                            <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">
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
                    <td colspan="4" class="py-12 text-center text-text-secondary">
                        Chưa có bài viết nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($posts->hasPages())
        <div class="mt-6">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
