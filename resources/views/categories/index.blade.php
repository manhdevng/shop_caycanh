@extends('layouts.app')

@section('title', 'Danh mục · Cây Cảnh Shop')

@php
    // Hai khu vực hiển thị trên tab "Danh mục sản phẩm"
    $productSections = [
        'plant' => ['label' => 'Cây cảnh', 'icon' => 'leaf'],
        'flower' => ['label' => 'Hoa', 'icon' => 'flower-2'],
    ];
@endphp

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">

    {{-- 1. Banner thông báo --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-300 text-green-700 rounded-2xl px-5 py-4">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-300 text-red-700 rounded-2xl px-5 py-4">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-300 text-red-700 rounded-2xl px-5 py-4">
        <div class="flex items-center gap-3 mb-2">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
            <span class="text-sm font-semibold">Có lỗi xảy ra:</span>
        </div>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-8">
        <h2 class="text-4xl md:text-5xl font-medium gloock text-text-primary tracking-tight">Quản lý Danh mục</h2>
        <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 w-fit text-decoration-none border border-green-border" href="{{ route('categories.create') }}">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            Thêm danh mục mới
        </a>
    </div>

    {{-- 2. Thanh tóm tắt --}}
    @php
        $emptyChildrenAlert = ($summary['emptyChildren'] ?? 0) > 0;
        $unassignedAlert = ($summary['unassignedProducts'] ?? 0) > 0;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-green-border rounded-2xl p-5">
            <p class="mono text-3xl font-bold text-text-primary">{{ $summary['groups'] ?? 0 }}</p>
            <p class="text-sm text-text-secondary mt-1">Nhóm danh mục</p>
        </div>
        <div class="bg-white border border-green-border rounded-2xl p-5">
            <p class="mono text-3xl font-bold text-text-primary">{{ $summary['children'] ?? 0 }}</p>
            <p class="text-sm text-text-secondary mt-1">Danh mục con</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border {{ $emptyChildrenAlert ? 'border-amber-300 bg-amber-50' : 'border-green-border' }}">
            <p class="mono text-3xl font-bold {{ $emptyChildrenAlert ? 'text-amber-600' : 'text-text-primary' }}">{{ $summary['emptyChildren'] ?? 0 }}</p>
            <p class="text-sm text-text-secondary mt-1">Danh mục chưa có sản phẩm</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border {{ $unassignedAlert ? 'border-red-300 bg-red-50' : 'border-green-border' }}">
            <p class="mono text-3xl font-bold {{ $unassignedAlert ? 'text-red-600' : 'text-text-primary' }}">{{ $summary['unassignedProducts'] ?? 0 }}</p>
            <p class="text-sm text-text-secondary mt-1">Sản phẩm chưa gắn danh mục</p>
        </div>
    </div>

    {{-- Cảnh báo ảnh hưởng trực tiếp tới trang chủ: nhóm thiếu ảnh sẽ thành ô
         trống trên carousel, nhóm chưa có danh mục con thì bị ẩn khỏi shop. --}}
    @if(($summary['missingImage'] ?? 0) > 0 || ($summary['emptyGroups'] ?? 0) > 0)
    <div class="mb-8 flex flex-wrap items-center gap-x-4 gap-y-2 bg-amber-50 border border-amber-300 text-amber-700 rounded-2xl px-5 py-3 text-sm">
        <span class="flex items-center gap-2 font-medium">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i> Ảnh hưởng tới trang chủ:
        </span>
        @if(($summary['missingImage'] ?? 0) > 0)
        <span>{{ $summary['missingImage'] }} nhóm Cây/Hoa <strong>chưa có ảnh</strong> — thẻ trên trang chủ sẽ bị trống.</span>
        @endif
        @if(($summary['emptyGroups'] ?? 0) > 0)
        <span>{{ $summary['emptyGroups'] }} nhóm <strong>chưa có danh mục con</strong> — đang bị ẩn khỏi trang chủ và mega-menu.</span>
        @endif
    </div>
    @endif

    {{-- 3. Hai tab --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <button type="button" id="tabBtnDanhMuc" onclick="switchCategoryTab('danh-muc')" class="px-5 py-2.5 rounded-pill font-medium text-sm transition-colors flex items-center gap-2">
            <i data-lucide="layout-grid" class="w-4 h-4"></i> Danh mục sản phẩm
        </button>
        <button type="button" id="tabBtnThuocTinh" onclick="switchCategoryTab('thuoc-tinh')" class="px-5 py-2.5 rounded-pill font-medium text-sm transition-colors flex items-center gap-2">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Thuộc tính lọc
        </button>
    </div>

    {{-- === TAB 1: DANH MỤC SẢN PHẨM === --}}
    <div id="tabContentDanhMuc">
        @foreach($productSections as $scopeKey => $section)
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-medium gloock text-text-primary flex items-center gap-2">
                    <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5 text-green-primary"></i>
                    {{ $section['label'] }}
                </h3>
                @if(($categoryGroups[$scopeKey] ?? collect())->isNotEmpty())
                <button type="button" data-toggle-scope="{{ $scopeKey }}" data-state="open" onclick="toggleAllDetails('{{ $scopeKey }}', this)" class="px-3 py-1.5 bg-white border border-green-border rounded-pill text-xs font-medium text-text-secondary hover:bg-green-background transition-colors flex items-center gap-1.5">
                    <i data-lucide="chevrons-down-up" class="w-3.5 h-3.5"></i> Thu gọn tất cả
                </button>
                @endif
            </div>

            @forelse(($categoryGroups[$scopeKey] ?? collect()) as $parent)
                @php
                    $parentChildCount = $parent->children->count();
                    $parentDeleteBlocked = $parentChildCount > 0 || $parent->products_count > 0;
                    if ($parentChildCount > 0) {
                        $parentDeleteReason = "Nhóm còn {$parentChildCount} danh mục con — hãy chuyển hoặc xoá danh mục con trước";
                    } elseif ($parent->products_count > 0) {
                        $parentDeleteReason = "Nhóm đang có {$parent->products_count} sản phẩm gắn trực tiếp — hãy chuyển sản phẩm sang danh mục khác trước";
                    } else {
                        $parentDeleteReason = null;
                    }
                @endphp
                <details data-scope="{{ $scopeKey }}" open class="cat-details mb-4 border border-green-border/40 rounded-2xl overflow-hidden group/cat">
                    <summary class="list-none cursor-pointer select-none flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-5 bg-[#f8f9f5] hover:bg-green-background/20 transition-colors [&::-webkit-details-marker]:hidden">
                        <div class="flex flex-wrap items-center gap-3">
                            <i data-lucide="chevron-right" class="w-4 h-4 text-text-secondary transition-transform group-open/cat:rotate-90 flex-shrink-0"></i>

                            @if($parent->image)
                                <img src="{{ asset('storage/' . $parent->image) }}" alt="{{ $parent->name }}" class="w-10 h-10 rounded-lg object-cover border border-green-border/40 flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg border border-dashed border-green-border bg-green-background flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="image-off" class="w-4 h-4 text-text-secondary/50"></i>
                                </div>
                            @endif

                            <span class="font-semibold text-text-primary text-lg">{{ $parent->name }}</span>

                            <span class="px-2 py-0.5 bg-white border border-green-border/50 text-text-secondary rounded-pill text-xs mono">
                                {{ $parentChildCount }} danh mục con
                            </span>
                            <span class="px-2 py-0.5 bg-white border border-green-border/50 text-text-secondary rounded-pill text-xs mono">
                                {{ $groupProductTotals[$parent->id] ?? 0 }} sản phẩm
                            </span>

                            <span class="text-xs text-text-secondary italic hidden sm:inline">Ảnh này hiện trên trang chủ</span>
                            @if(!$parent->image)
                            <span class="px-2 py-0.5 bg-amber-50 border border-amber-300 text-amber-600 rounded-pill text-xs font-medium">Thiếu ảnh trang chủ</span>
                            @endif

                            @if($parent->children->isEmpty())
                            <span class="px-2 py-0.5 bg-gray-100 border border-gray-300 text-gray-500 rounded-pill text-xs">Nhóm trống, đang ẩn ngoài shop</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-1 w-full md:w-auto" onclick="event.stopPropagation()">
                            @if(!$loop->first)
                            <form action="{{ route('categories.move', [$parent->id, 'up']) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển lên trên">
                                    <i data-lucide="arrow-up" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                            @if(!$loop->last)
                            <form action="{{ route('categories.move', [$parent->id, 'down']) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển xuống dưới">
                                    <i data-lucide="arrow-down" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif

                            <a href="{{ route('categories.create') }}?parent_id={{ $parent->id }}" class="px-3 py-1.5 bg-white border border-green-border rounded-pill text-xs font-medium text-text-primary hover:bg-green-background transition-colors flex items-center gap-1">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Thêm danh mục con
                            </a>

                            <a href="{{ route('categories.show', $parent->id) }}" class="p-2 text-text-secondary hover:text-green-600 hover:bg-green-50 rounded-full transition-colors" title="Xem">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('categories.edit', $parent->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </a>

                            @if($parentDeleteBlocked)
                            <button type="button" disabled title="{{ $parentDeleteReason }}" class="p-2 text-text-secondary opacity-40 cursor-not-allowed rounded-full">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            @else
                            <form action="{{ route('categories.destroy', $parent->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá danh mục &quot;{{ $parent->name }}&quot;? Thao tác này không thể hoàn tác.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xoá">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </summary>

                    {{-- Danh mục con --}}
                    @if($parent->children->isNotEmpty())
                    <div class="divide-y divide-green-border/20 border-t border-green-border/30">
                        @foreach($parent->children as $child)
                            @php
                                $childDeleteBlocked = $child->products_count > 0;
                                $childDeleteReason = $childDeleteBlocked
                                    ? "Danh mục đang có {$child->products_count} sản phẩm — hãy chuyển sản phẩm sang danh mục khác trước"
                                    : null;
                            @endphp
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-5 py-3 pl-12 hover:bg-green-background/10 transition-colors cursor-pointer"
                                 onclick="window.location='{{ route('categories.show', $child->id) }}'">
                                <div class="flex flex-wrap items-center gap-3">
                                    <i data-lucide="corner-down-right" class="w-4 h-4 text-text-secondary/60 flex-shrink-0"></i>

                                    @if($child->image)
                                        <img src="{{ asset('storage/' . $child->image) }}" alt="{{ $child->name }}" class="w-8 h-8 rounded-lg object-cover border border-green-border/40 flex-shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-lg border border-dashed border-green-border bg-green-background flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="image-off" class="w-3.5 h-3.5 text-text-secondary/50"></i>
                                        </div>
                                    @endif

                                    <span class="text-text-primary">{{ $child->name }}</span>

                                    @if($child->products_count > 0)
                                    <span class="px-2 py-0.5 bg-green-background border border-green-border/30 text-text-secondary rounded-pill text-xs mono">
                                        {{ $child->products_count }} sản phẩm
                                    </span>
                                    @else
                                    <span class="px-2 py-0.5 bg-amber-50 border border-amber-300 text-amber-600 rounded-pill text-xs font-medium">
                                        Chưa có sản phẩm
                                    </span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-1 w-full md:w-auto" onclick="event.stopPropagation()">
                                    @if(!$loop->first)
                                    <form action="{{ route('categories.move', [$child->id, 'up']) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển lên trên">
                                            <i data-lucide="arrow-up" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @if(!$loop->last)
                                    <form action="{{ route('categories.move', [$child->id, 'down']) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển xuống dưới">
                                            <i data-lucide="arrow-down" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('categories.show', $child->id) }}" class="p-2 text-text-secondary hover:text-green-600 hover:bg-green-50 rounded-full transition-colors" title="Xem">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('categories.edit', $child->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    @if($childDeleteBlocked)
                                    <button type="button" disabled title="{{ $childDeleteReason }}" class="p-2 text-text-secondary opacity-40 cursor-not-allowed rounded-full">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    @else
                                    <form action="{{ route('categories.destroy', $child->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá danh mục &quot;{{ $child->name }}&quot;? Thao tác này không thể hoàn tác.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xoá">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                    <div class="px-5 py-4 pl-12 border-t border-green-border/30 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-text-secondary text-sm italic">Chưa có danh mục con nào trong nhóm này.</span>
                        <a href="{{ route('categories.create') }}?parent_id={{ $parent->id }}" class="px-3 py-1.5 bg-white border border-green-border rounded-pill text-xs font-medium text-text-primary hover:bg-green-background transition-colors flex items-center gap-1">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Thêm danh mục con
                        </a>
                    </div>
                    @endif
                </details>
            @empty
            <div class="py-6 text-center text-text-secondary text-sm italic border border-dashed border-green-border/40 rounded-2xl flex flex-col items-center gap-3">
                <span>Chưa có nhóm danh mục nào thuộc "{{ $section['label'] }}".</span>
                <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-green-primary text-white rounded-pill text-sm font-medium hover:bg-green-accent transition-colors">Tạo nhóm mới</a>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>

    {{-- === TAB 2: THUỘC TÍNH LỌC === --}}
    <div id="tabContentThuocTinh" class="hidden">
        <div class="mb-6 bg-green-background rounded-2xl px-5 py-4 text-sm text-text-secondary flex items-start gap-2">
            <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span>Thuộc tính dùng để lọc sản phẩm (ví dụ: Ưa nắng), không hiện trên menu và trang chủ.</span>
        </div>

        @forelse(($categoryGroups['both'] ?? collect()) as $parent)
            @php
                $parentChildCount = $parent->children->count();
                $parentDeleteBlocked = $parentChildCount > 0 || $parent->products_count > 0;
                if ($parentChildCount > 0) {
                    $parentDeleteReason = "Nhóm còn {$parentChildCount} danh mục con — hãy chuyển hoặc xoá danh mục con trước";
                } elseif ($parent->products_count > 0) {
                    $parentDeleteReason = "Nhóm đang có {$parent->products_count} sản phẩm gắn trực tiếp — hãy chuyển sản phẩm sang danh mục khác trước";
                } else {
                    $parentDeleteReason = null;
                }
            @endphp
            <div class="mb-6 border border-green-border/40 rounded-2xl p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <h4 class="gloock text-lg text-text-primary flex items-center gap-2 flex-wrap">
                        <i data-lucide="tag" class="w-4 h-4 text-green-primary"></i>
                        {{ $parent->name }}
                        <span class="px-2 py-0.5 bg-white border border-green-border/50 text-text-secondary rounded-pill text-xs mono">
                            {{ $groupProductTotals[$parent->id] ?? 0 }} sản phẩm
                        </span>
                    </h4>
                    <div class="flex flex-wrap items-center gap-1 w-full md:w-auto">
                        @if(!$loop->first)
                        <form action="{{ route('categories.move', [$parent->id, 'up']) }}" method="POST" class="inline-block">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển lên trên">
                                <i data-lucide="arrow-up" class="w-4 h-4"></i>
                            </button>
                        </form>
                        @endif
                        @if(!$loop->last)
                        <form action="{{ route('categories.move', [$parent->id, 'down']) }}" method="POST" class="inline-block">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Chuyển xuống dưới">
                                <i data-lucide="arrow-down" class="w-4 h-4"></i>
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('categories.edit', $parent->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </a>
                        @if($parentDeleteBlocked)
                        <button type="button" disabled title="{{ $parentDeleteReason }}" class="p-2 text-text-secondary opacity-40 cursor-not-allowed rounded-full">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        @else
                        <form action="{{ route('categories.destroy', $parent->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá danh mục &quot;{{ $parent->name }}&quot;? Thao tác này không thể hoàn tác.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xoá">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('categories.create') }}?parent_id={{ $parent->id }}" class="px-3 py-1.5 bg-white border border-green-border rounded-pill text-xs font-medium text-text-primary hover:bg-green-background transition-colors flex items-center gap-1">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i> Thêm thuộc tính
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @forelse($parent->children as $child)
                        @php
                            $childDeleteBlocked = $child->products_count > 0;
                            $childDeleteReason = $childDeleteBlocked
                                ? "Danh mục đang có {$child->products_count} sản phẩm — hãy chuyển sản phẩm sang danh mục khác trước"
                                : null;
                        @endphp
                        <div class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-pill border border-green-border bg-white text-sm">
                            <span class="text-text-primary">{{ $child->name }}</span>
                            @if($child->products_count > 0)
                            <span class="px-1.5 py-0.5 bg-green-background text-text-secondary rounded-pill text-xs mono">{{ $child->products_count }}</span>
                            @else
                            <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 border border-amber-300 rounded-pill text-xs">Chưa có sản phẩm</span>
                            @endif
                            <a href="{{ route('categories.edit', $child->id) }}" class="p-1 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            </a>
                            @if($childDeleteBlocked)
                            <button type="button" disabled title="{{ $childDeleteReason }}" class="p-1 text-text-secondary opacity-40 cursor-not-allowed rounded-full">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                            @else
                            <form action="{{ route('categories.destroy', $child->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá thuộc tính &quot;{{ $child->name }}&quot;? Thao tác này không thể hoàn tác.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xoá">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-text-secondary italic">
                            Chưa có thuộc tính con nào.
                            <a href="{{ route('categories.create') }}?parent_id={{ $parent->id }}" class="text-green-primary font-medium">+ Thêm thuộc tính</a>
                        </p>
                    @endforelse
                </div>
            </div>
        @empty
        <div class="py-6 text-center text-text-secondary text-sm italic border border-dashed border-green-border/40 rounded-2xl flex flex-col items-center gap-3">
            <span>Chưa có nhóm thuộc tính nào.</span>
            <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-green-primary text-white rounded-pill text-sm font-medium hover:bg-green-accent transition-colors">Tạo nhóm mới</a>
        </div>
        @endforelse
    </div>
</div>

<script>
    // Nạp lại icon Lucide mỗi khi 1 khối danh mục được xổ/thu gọn
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('details.cat-details').forEach(function (el) {
            el.addEventListener('toggle', function () {
                lucide.createIcons();
            });
        });

        // Khôi phục tab đang chọn theo #hash trên URL
        var initialTab = (location.hash === '#thuoc-tinh') ? 'thuoc-tinh' : 'danh-muc';
        switchCategoryTab(initialTab, false);
    });

    // Chuyển đổi giữa 2 tab: "Danh mục sản phẩm" và "Thuộc tính lọc"
    function switchCategoryTab(tab, updateHash) {
        updateHash = updateHash === undefined ? true : updateHash;

        var btnDanhMuc = document.getElementById('tabBtnDanhMuc');
        var btnThuocTinh = document.getElementById('tabBtnThuocTinh');
        var contentDanhMuc = document.getElementById('tabContentDanhMuc');
        var contentThuocTinh = document.getElementById('tabContentThuocTinh');

        var activeClasses = ['bg-green-primary', 'text-white'];
        var inactiveClasses = ['bg-white', 'border', 'border-green-border', 'text-text-primary'];

        if (tab === 'thuoc-tinh') {
            contentDanhMuc.classList.add('hidden');
            contentThuocTinh.classList.remove('hidden');
            btnThuocTinh.classList.add(...activeClasses);
            btnThuocTinh.classList.remove(...inactiveClasses);
            btnDanhMuc.classList.remove(...activeClasses);
            btnDanhMuc.classList.add(...inactiveClasses);
            if (updateHash) history.replaceState(null, '', '#thuoc-tinh');
        } else {
            contentThuocTinh.classList.add('hidden');
            contentDanhMuc.classList.remove('hidden');
            btnDanhMuc.classList.add(...activeClasses);
            btnDanhMuc.classList.remove(...inactiveClasses);
            btnThuocTinh.classList.remove(...activeClasses);
            btnThuocTinh.classList.add(...inactiveClasses);
            if (updateHash) history.replaceState(null, '', '#danh-muc');
        }

        lucide.createIcons();
    }

    // Thu gọn / mở tất cả các nhóm (details) trong một khu vực (Cây cảnh / Hoa)
    function toggleAllDetails(scope, btn) {
        var detailsList = document.querySelectorAll('details[data-scope="' + scope + '"]');
        var anyClosed = Array.prototype.some.call(detailsList, function (d) { return !d.open; });

        detailsList.forEach(function (d) { d.open = anyClosed; });

        if (anyClosed) {
            btn.dataset.state = 'open';
            btn.innerHTML = '<i data-lucide="chevrons-down-up" class="w-3.5 h-3.5"></i> Thu gọn tất cả';
        } else {
            btn.dataset.state = 'closed';
            btn.innerHTML = '<i data-lucide="chevrons-up-down" class="w-3.5 h-3.5"></i> Mở tất cả';
        }

        lucide.createIcons();
    }
</script>
@endsection
