@extends('layouts.app')

@section('title', 'Sửa danh mục · Cây Cảnh Shop')

@php
    $parentsByScope = $parentCategories->groupBy('scope');
    $hasChildren = $category->children->isNotEmpty();
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Sửa Danh Mục</h2>
    <a href="{{ route('categories.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
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

<form action="{{ route('categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Tên danh mục <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                @error('name')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Ảnh danh mục</label>
                @if($category->image)
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-24 h-24 md:w-32 md:h-32 object-cover rounded-2xl shadow-sm border border-green-border/50">
                    </div>
                @endif
                <input type="file" name="image" accept="image/*" class="w-full rounded-xl border-green-border/50 border px-4 py-3 bg-[#f8f9f5] focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-sm text-text-secondary">
                <p class="text-xs text-text-secondary mt-2">Chọn ảnh mới nếu muốn thay ảnh danh mục hiện tại.</p>
                @error('image')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            @if($hasChildren)
                <!-- Nhóm gốc đang có danh mục con: không được gán cha khác (rule mới
                     guardAgainstReparentingWithChildren ở CategoryController). Ẩn hẳn
                     ô chọn danh mục cha để tránh admin gặp lỗi validate khó hiểu. -->
                <div class="p-4 bg-[#f8f9f5] border border-green-border/30 rounded-xl">
                    <p class="text-sm text-text-secondary">
                        Danh mục này đang có <strong>{{ $category->children->count() }}</strong> danh mục con,
                        không thể gán cho nó một danh mục cha khác.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Phạm vi <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-6">
                        @foreach(\App\Models\Category::SCOPES as $scopeValue => $scopeLabel)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="scope" value="{{ $scopeValue }}" class="w-5 h-5" {{ old('scope', $category->scope) === $scopeValue ? 'checked' : '' }}>
                                <span class="text-text-primary font-medium">{{ $scopeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Danh mục cha</label>
                    <select name="parent_id" id="parentIdSelect" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                        <option value="">— Đây là danh mục gốc (nhóm lớn) —</option>
                        @foreach(\App\Models\Category::SCOPES as $scopeValue => $scopeLabel)
                            @if(($parentsByScope[$scopeValue] ?? collect())->isNotEmpty())
                            <optgroup label="{{ $scopeLabel }}">
                                @foreach($parentsByScope[$scopeValue] as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                @endforeach
                            </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-text-secondary mt-2">Chọn một nhóm nếu đây là danh mục con. Để trống nếu đây là nhóm lớn.</p>
                </div>

                <div id="scopeField">
                    <label class="block text-sm font-semibold text-text-primary mb-2 mono">Phạm vi <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-6">
                        @foreach(\App\Models\Category::SCOPES as $scopeValue => $scopeLabel)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="scope" value="{{ $scopeValue }}" class="w-5 h-5" {{ old('scope', $category->scope) === $scopeValue ? 'checked' : '' }}>
                                <span class="text-text-primary font-medium">{{ $scopeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-text-secondary mt-2">Chỉ áp dụng cho nhóm danh mục gốc.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="flex justify-end gap-4 mt-6">
        <a href="{{ route('categories.index') }}" class="px-8 py-3 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none">Hủy bỏ</a>
        <button type="submit" class="px-10 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i> Cập nhật
        </button>
    </div>
</form>

@if(!$hasChildren)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const parentIdSelect = document.getElementById('parentIdSelect');
        const scopeField = document.getElementById('scopeField');

        function updateScopeVisibility() {
            if (parentIdSelect.value) {
                scopeField.classList.add('hidden');
            } else {
                scopeField.classList.remove('hidden');
            }
        }

        parentIdSelect.addEventListener('change', updateScopeVisibility);
        updateScopeVisibility();
    });
</script>
@endif
@endsection
