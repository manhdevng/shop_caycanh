{{-- Tab Bảng số liệu / Biểu đồ, giữ nguyên kỳ lọc. Biến: $period, $active ('index'|'charts'). --}}
@php
    $tabActive = 'bg-green-primary text-white border-green-border';
    $tabIdle = 'bg-white text-text-secondary border-green-border hover:bg-green-background';
@endphp
<div class="flex items-center gap-2 mb-8">
    <a href="{{ route('admin.reports.index', $period->query()) }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors {{ $active === 'index' ? $tabActive : $tabIdle }}">
        Bảng số liệu
    </a>
    <a href="{{ route('admin.reports.charts', $period->query()) }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors {{ $active === 'charts' ? $tabActive : $tabIdle }}">
        Biểu đồ
    </a>
</div>
