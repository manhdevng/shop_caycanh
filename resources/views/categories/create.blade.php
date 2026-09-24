@extends('layouts.app')

@section('title', 'Thêm danh mục · Cây Cảnh Shop')

@php
    $parentsByScope = $parentCategories->groupBy('scope');
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Thêm Danh Mục Mới</h2>
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

<form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Tên danh mục <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Cây ăn quả, Cây cảnh, Kiểu loại cây..." class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]" required>
                @error('name')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Ảnh danh mục</label>
                <input type="file" name="image" accept="image/*" class="w-full rounded-xl border-green-border/50 border px-4 py-3 bg-[#f8f9f5] focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-sm text-text-secondary">
                <p class="text-xs text-text-secondary mt-2">Ảnh sẽ hiển thị thay cho hình chữ mặc định ở trang chủ.</p>
                @error('image')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Danh mục cha</label>
                <select name="parent_id" id="parentIdSelect" class="w-full rounded-xl border-green-border/50 border px-4 py-3 focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none transition-all text-text-primary bg-[#f8f9f5]">
                    <option value="">— Đây là danh mục gốc (nhóm lớn) —</option>
                    @foreach(\App\Models\Category::SCOPES as $scopeValue => $scopeLabel)
                        @if(($parentsByScope[$scopeValue] ?? collect())->isNotEmpty())
                        <optgroup label="{{ $scopeLabel }}">
                            @foreach($parentsByScope[$scopeValue] as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $preselectedParentId) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                            @endforeach
                        </optgroup>
                        @endif
                    @endforeach
                </select>
                <p class="text-xs text-text-secondary mt-2">Chọn một nhóm nếu đây là danh mục con (VD: "Cây ăn quả" thuộc nhóm "Kiểu loại cây"). Để trống nếu muốn tạo một nhóm lớn mới.</p>
                @if($preselectedParentId)
                <p class="text-xs text-text-secondary mt-2 bg-green-background rounded-lg px-3 py-2">Đang thêm danh mục con vào nhóm đã chọn sẵn.</p>
                @endif
            </div>

            <div id="scopeField">
                <label class="block text-sm font-semibold text-text-primary mb-2 mono">Phạm vi <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-6">
                    @foreach(\App\Models\Category::SCOPES as $scopeValue => $scopeLabel)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="scope" value="{{ $scopeValue }}" class="w-5 h-5" {{ old('scope') === $scopeValue ? 'checked' : '' }}>
                            <span class="text-text-primary font-medium">{{ $scopeLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-text-secondary mt-2">Chỉ áp dụng cho nhóm danh mục gốc — chọn phạm vi để quyết định nhóm này dùng cho Cây cảnh, Hoa, hay dùng chung cho cả hai.</p>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-4 mt-6">
        <a href="{{ route('categories.index') }}" class="px-8 py-3 bg-white border border-green-border text-text-primary rounded-pill font-medium hover:bg-green-background transition-colors text-decoration-none">Hủy bỏ</a>
        <button type="submit" class="px-10 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-all flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i> Lưu danh mục
        </button>
    </div>
</form>

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
@endsection
