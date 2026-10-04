@extends('layouts.app')

@section('title', 'Tồn kho · Cây Cảnh Shop')

@section('content')
@php
    $inputClass = 'w-full bg-white border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all';
    $tabUrl = fn (string $tab) => route('admin.inventory.index', array_merge(request()->except('tab', 'page'), ['tab' => $tab]));
    $tabHint = [
        'out' => 'Sản phẩm đang hiển thị bán nhưng tồn kho = 0 — khách không mua được.',
        'low' => 'Sản phẩm còn từ 1 đến '.$filters['low'].' đơn vị tồn kho.',
        'slow' => 'Còn hàng, đã đăng bán ít nhất '.$filters['days'].' ngày (trước '.$since->format('d/m/Y').') và không có đơn đã thu tiền nào từ ngày đó tới nay.',
    ];
    $emptyText = [
        'out' => 'Không có sản phẩm nào đang hết hàng.',
        'low' => 'Không có sản phẩm nào còn từ 1 đến '.$filters['low'].' đơn vị.',
        'slow' => 'Mọi sản phẩm còn hàng đều đã bán được trong '.$filters['days'].' ngày qua, hoặc mới đăng bán chưa đủ '.$filters['days'].' ngày.',
    ];
    $hasNarrowing = $filters['q'] !== '' || $filters['type'] || $filters['category'];
@endphp

<div class="bg-white rounded-[32px] p-5 sm:p-8 border border-green-border shadow-sm">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h2 class="text-4xl sm:text-5xl font-medium gloock text-text-primary tracking-tight">Tồn kho</h2>
            <p class="text-sm text-text-secondary mt-2">Chỉ tính sản phẩm đang hiển thị bán. "Đã bán" = đơn đã thu tiền (không tính COD chưa thu, đơn huỷ, hoàn tiền/hoàn hàng).</p>
        </div>
        <a href="{{ route('products.index') }}" class="px-5 py-3 bg-white text-text-secondary rounded-pill font-medium hover:bg-green-background transition-colors flex items-center gap-2 text-decoration-none border border-green-border text-sm">
            <i data-lucide="package" class="w-4 h-4"></i> Quản lý sản phẩm
        </a>
    </div>

    {{-- Tab ba danh sách, giữ nguyên bộ lọc khi đổi tab --}}
    <div class="flex flex-wrap gap-2 mb-6" role="tablist">
        @foreach($tabs as $tab => $label)
            @php $active = $filters['tab'] === $tab; @endphp
            <a href="{{ $tabUrl($tab) }}" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
               class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors inline-flex items-center gap-2 {{ $active ? 'bg-green-primary text-white border-green-border' : 'bg-white text-text-secondary border-green-border hover:bg-green-background' }}">
                {{ $label }}
                <span data-count="{{ $tab }}" class="mono text-xs px-2 py-0.5 rounded-pill {{ $active ? 'bg-white/25' : ($counts[$tab] > 0 && $tab === 'out' ? 'bg-red-100 text-red-700' : 'bg-[#f8f9f5]') }}">{{ $counts[$tab] }}</span>
            </a>
        @endforeach
    </div>

    <form action="{{ route('admin.inventory.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end bg-[#f8f9f5] border border-green-border/50 rounded-[24px] p-5 mb-4">
        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
        <div class="lg:col-span-2">
            <label for="inv-q" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Tên sản phẩm</label>
            <input id="inv-q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Tìm theo tên..." class="{{ $inputClass }}">
        </div>
        <div>
            <label for="inv-type" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Loại</label>
            <select id="inv-type" name="type" class="{{ $inputClass }}">
                <option value="">Tất cả</option>
                @foreach(\App\Models\Product::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="inv-category" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Danh mục</label>
            <select id="inv-category" name="category" class="{{ $inputClass }}">
                <option value="">Tất cả</option>
                @foreach($categories as $group)
                    @if($group->children->isNotEmpty())
                        <optgroup label="{{ $group->name }}">
                            @foreach($group->children as $child)
                                <option value="{{ $child->id }}" @selected($filters['category'] === $child->id)>{{ $child->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label for="inv-low" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Sắp hết khi ≤</label>
            <input id="inv-low" type="number" name="low" min="1" max="100" value="{{ $filters['low'] }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="inv-days" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Chưa bán (ngày)</label>
            <input id="inv-days" type="number" name="days" min="7" max="365" value="{{ $filters['days'] }}" class="{{ $inputClass }}">
        </div>
        <div class="flex flex-wrap gap-3 sm:col-span-2 lg:col-span-6">
            <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-full hover:bg-green-accent transition-all text-sm font-medium">Lọc</button>
            <a href="{{ route('admin.inventory.index', ['tab' => $filters['tab']]) }}" class="px-6 py-2 bg-white text-text-primary border border-green-border rounded-full hover:bg-green-background transition-all text-sm font-medium text-decoration-none">Mặc định</a>
        </div>
    </form>

    @if($filterErrors->isNotEmpty())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm" role="alert">
            <p class="font-medium mb-1">Một số bộ lọc không hợp lệ nên đang dùng giá trị mặc định:</p>
            <ul class="list-disc pl-5">
                @foreach($filterErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-sm text-text-secondary mb-4">
        <strong class="text-text-primary">{{ $tabs[$filters['tab']] }}:</strong> {{ $tabHint[$filters['tab']] }}
        Cột "Bán trong kỳ" tính từ {{ $since->format('d/m/Y') }} ({{ $filters['days'] }} ngày) tới nay.
    </p>

    {{-- Bảng cuộn ngang trong khung riêng để trang không tràn ở màn hẹp --}}
    <div class="overflow-x-auto -mx-1 px-1">
        <table class="w-full min-w-[720px] text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Sản phẩm</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Loại</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Tồn</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Bán gần nhất</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Bán trong kỳ</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đăng bán</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Sửa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors" data-product-id="{{ $product->id }}">
                        <td class="py-4 pr-4">
                            <div class="flex items-center gap-3">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt="" class="w-12 h-12 object-cover rounded-xl border border-green-border/30 flex-none" loading="lazy">
                                @else
                                    <div class="w-12 h-12 bg-green-background rounded-xl flex items-center justify-center text-green-border flex-none"><i data-lucide="image" class="w-5 h-5"></i></div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-medium text-text-primary">{{ $product->name }}</p>
                                    <p class="text-xs text-text-secondary mono truncate max-w-[260px]">{{ $product->categories->pluck('name')->implode(' · ') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 pr-4 text-sm text-text-secondary whitespace-nowrap">{{ \App\Models\Product::TYPES[$product->product_type] ?? $product->product_type }}</td>
                        <td class="py-4 pr-4 text-right mono font-semibold {{ (int) $product->stock <= 0 ? 'text-red-600' : ((int) $product->stock <= $filters['low'] ? 'text-amber-600' : 'text-text-primary') }}">{{ (int) $product->stock }}</td>
                        <td class="py-4 pr-4 text-sm whitespace-nowrap">
                            @if($product->last_sold_at)
                                @php $lastSold = \Illuminate\Support\Carbon::parse($product->last_sold_at); @endphp
                                {{ $lastSold->format('d/m/Y') }}
                                <span class="block text-xs text-text-secondary">{{ (int) $lastSold->diffInDays(now()) }} ngày trước</span>
                            @else
                                <span class="text-text-secondary italic">Chưa từng bán</span>
                            @endif
                        </td>
                        <td class="py-4 pr-4 text-right mono">{{ (int) ($product->period_qty ?? 0) }}</td>
                        <td class="py-4 pr-4 text-sm text-text-secondary whitespace-nowrap">{{ optional($product->created_at)->format('d/m/Y') }}</td>
                        <td class="py-4 text-right">
                            <a href="{{ route('products.edit', $product->id) }}" class="inline-flex p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa {{ $product->name }}">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-text-secondary">
                            {{ $hasNarrowing ? 'Không có sản phẩm nào khớp bộ lọc trong danh sách này.' : $emptyText[$filters['tab']] }}
                            @if($hasNarrowing)
                                <a href="{{ route('admin.inventory.index', ['tab' => $filters['tab'], 'low' => $filters['low'], 'days' => $filters['days']]) }}" class="text-green-700 underline ml-1">Bỏ lọc tên/loại/danh mục</a>
                            @endif
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
