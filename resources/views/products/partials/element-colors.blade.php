{{--
    Màu chủ đạo của cây (config/phong_thuy.php → colors) trong form thêm/sửa
    sản phẩm. Đặt trong #elementsBlock, TRƯỚC các ô chọn elements[].
    Chọn màu thì element-suggest tự tick hành tương ứng (màu thắng gợi ý theo tên).

    Tham số: $selectedColors (array) mã màu đang chọn.
--}}
@php $selectedColors = $selectedColors ?? []; @endphp
<div class="mb-3">
    <span class="block text-xs text-text-secondary mb-2">Màu chủ đạo của cây (lá, hoa) — chọn 1–2 màu nổi bật nhất:</span>
    <div class="flex flex-wrap gap-2">
        @foreach(config('phong_thuy.colors', []) as $colorCode => $color)
            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-pill border border-green-border/50 bg-white cursor-pointer text-xs text-text-primary">
                <input type="checkbox" name="feng_shui_colors[]" value="{{ $colorCode }}" data-element="{{ $color['element'] }}"
                       class="w-4 h-4 rounded border-green-border/50 focus:ring-green-primary" {{ in_array($colorCode, $selectedColors, true) ? 'checked' : '' }}>
                {{ $color['label'] }}
                <span class="text-text-secondary">→ {{ \App\Models\Product::ELEMENTS[$color['element']] }}</span>
            </label>
        @endforeach
    </div>
    @error('feng_shui_colors')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
    @error('feng_shui_colors.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
</div>
