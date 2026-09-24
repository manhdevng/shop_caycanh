@extends('layouts.app')

@section('title', 'Sửa câu hỏi thường gặp · Cây Cảnh Shop')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Sửa FAQ</h2>
    <a href="{{ route('admin.faqs.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại
    </a>
</div>

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

<form action="{{ route('admin.faqs.update', $faq) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Câu hỏi <span class="text-red-500">*</span></label>
                <input type="text" name="question" value="{{ old('question', $faq->question) }}" maxlength="255" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                @error('question')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Câu trả lời <span class="text-red-500">*</span></label>
                <textarea name="answer" rows="6" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">{{ old('answer', $faq->answer) }}</textarea>
                @error('answer')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Vị trí hiển thị <span class="text-red-500">*</span></label>
                    <select name="placement" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                        @foreach(\App\Models\Faq::PLACEMENT_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ old('placement', $faq->placement) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('placement')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                    <p class="text-xs text-text-secondary mt-1">Số nhỏ hơn hiển thị trước.</p>
                    @error('sort_order')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $faq->is_published) ? 'checked' : '' }} class="w-4 h-4">
                <label for="is_published" class="text-sm font-semibold text-text-primary mono">Xuất bản câu hỏi này (hiển thị công khai)</label>
                @error('is_published')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4 mt-6">
        <a href="{{ route('admin.faqs.index') }}" class="px-8 py-3 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none">Hủy bỏ</a>
        <button type="submit" class="px-10 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i> Cập nhật
        </button>
    </div>
</form>
@endsection
