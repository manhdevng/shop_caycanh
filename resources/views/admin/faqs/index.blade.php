@extends('layouts.app')

@section('title', 'Câu hỏi thường gặp (FAQ) · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Câu hỏi thường gặp (FAQ)</h2>
        <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('admin.faqs.create') }}">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Thêm FAQ
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
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Câu hỏi</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Vị trí hiển thị</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Thứ tự</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($faqs as $faq)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4 font-medium text-text-primary max-w-xs">{{ $faq->question }}</td>
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ \App\Models\Faq::PLACEMENT_LABELS[$faq->placement] ?? $faq->placement }}</td>
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ $faq->sort_order }}</td>
                    <td class="py-5 pr-4">
                        @if($faq->is_published)
                            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">Đã xuất bản</span>
                        @else
                            <span class="px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-pill text-xs mono font-semibold">Bản nháp</span>
                        @endif
                    </td>
                    <td class="py-5 text-right relative">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>

                            <input type="checkbox" id="del-faq-toggle-{{ $faq->id }}" class="peer/faq{{ $faq->id }} hidden">
                            <label for="del-faq-toggle-{{ $faq->id }}" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors cursor-pointer" title="Xóa">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </label>

                            <div class="hidden peer-checked/faq{{ $faq->id }}:flex absolute right-0 top-full mt-2 z-10 flex-col gap-3 w-72 p-4 bg-white border border-red-200 rounded-2xl shadow-lg text-left">
                                <p class="text-sm text-red-700">Bạn chắc chắn muốn xóa câu hỏi <span class="font-semibold">&ldquo;{{ Illuminate\Support\Str::limit($faq->question, 40) }}&rdquo;</span>? Hành động này không thể hoàn tác.</p>
                                <div class="flex items-center gap-3">
                                    <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-pill font-medium hover:bg-red-700 transition-colors text-sm">
                                            Xóa
                                        </button>
                                    </form>
                                    <label for="del-faq-toggle-{{ $faq->id }}" class="text-sm text-text-secondary hover:text-text-primary cursor-pointer">Hủy</label>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-text-secondary">
                        Chưa có câu hỏi thường gặp nào. Hãy thêm câu hỏi đầu tiên.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($faqs->hasPages())
        <div class="mt-6">{{ $faqs->links() }}</div>
    @endif
</div>
@endsection
