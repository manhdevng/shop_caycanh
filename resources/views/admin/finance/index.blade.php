@extends('layouts.app')

@section('title', 'Thống kê tài chính · Cây Cảnh Shop')

@section('content')
<div class="mb-8 relative z-10">
    <h2 class="text-4xl font-display text-text-primary tracking-tight">Thống kê tài chính</h2>
</div>

<!-- Forms filter -->
<div class="bg-white/40 backdrop-blur-xl border border-white/20 p-6 rounded-[24px] shadow-sm mb-8">
    <form action="{{ route('admin.finance.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-text-secondary uppercase mb-1">Tìm kiếm</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã, tên, SĐT..." class="w-full bg-white/50 border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
        </div>
        <div>
            <label class="block text-xs font-semibold text-text-secondary uppercase mb-1">Từ ngày</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-white/50 border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
        </div>
        <div>
            <label class="block text-xs font-semibold text-text-secondary uppercase mb-1">Đến ngày</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-white/50 border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
        </div>
        <div>
            <label class="block text-xs font-semibold text-text-secondary uppercase mb-1">Kênh thanh toán</label>
            <select name="gateway" class="w-full bg-white/50 border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
                <option value="">Tất cả</option>
                @foreach($methods as $key => $name)
                    <option value="{{ $key }}" {{ request('gateway') == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end gap-3 mt-2">
            <a href="{{ route('admin.finance.index') }}" class="px-6 py-2 bg-white text-text-primary border border-green-border rounded-full hover:bg-green-background transition-all text-sm font-medium">Làm mới</a>
            <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-full hover:bg-green-accent transition-all text-sm font-medium">Lọc dữ liệu</button>
        </div>
    </form>
</div>

<!-- Thống kê tổng quan -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white/80 backdrop-blur-xl rounded-[24px] p-6 border border-white/40 shadow-sm flex flex-col justify-between relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-primary/5 to-transparent pointer-events-none"></div>
        <div class="flex items-center gap-3 mb-4">
            <i data-lucide="calculator" class="w-5 h-5 text-green-primary"></i>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng đơn hàng</span>
        </div>
        <div class="text-4xl font-display text-text-primary">{{ number_format($summary->order_count ?? 0) }}</div>
    </div>
    <div class="bg-white/80 backdrop-blur-xl rounded-[24px] p-6 border border-white/40 shadow-sm flex flex-col justify-between relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-primary/5 to-transparent pointer-events-none"></div>
        <div class="flex items-center gap-3 mb-4">
            <i data-lucide="circle-dollar-sign" class="w-5 h-5 text-green-primary"></i>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng doanh thu</span>
            <span class="text-xs text-text-secondary">(chỉ tính đơn đã thanh toán)</span>
        </div>
        <div class="text-4xl font-display text-text-primary">{{ number_format($summary->total_amount ?? 0) }} ₫</div>
        <div class="text-xs text-text-secondary mt-1">từ {{ number_format($summary->paid_count ?? 0) }} đơn đã thanh toán</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Báo cáo theo kênh -->
    <div class="bg-white/60 backdrop-blur-md rounded-[24px] border border-white/40 p-6 shadow-sm">
        <h3 class="text-lg font-display mb-1 flex items-center gap-2 text-text-primary"><i data-lucide="pie-chart" class="w-5 h-5"></i> Theo kênh thanh toán</h3>
        <p class="text-xs text-text-secondary mb-4">Doanh thu chỉ tính đơn đã thanh toán; số đơn gồm mọi trạng thái.</p>
        <div class="space-y-4">
            @foreach($methods as $key => $name)
                @php 
                    $stat = $methodTotals->get($key); 
                    $count = $stat ? $stat->order_count : 0;
                    // Doanh thu theo kênh = chỉ đơn đã thanh toán (khớp với "Tổng doanh thu").
                    $amount = $stat ? $stat->paid_amount : 0;
                    $paidCount = $stat ? $stat->paid_count : 0;
                    $dotColor = match($key) {
                        'momo' => 'bg-[#A50064]',
                        'bank_transfer' => 'bg-blue-500',
                        'unknown' => 'bg-gray-400',
                        default => 'bg-green-primary',
                    };
                @endphp
                <div class="flex items-center justify-between p-4 rounded-[16px] bg-white/40 border border-white/20">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full {{ $dotColor }}"></span>
                        <span class="font-medium text-text-primary">{{ $name }}</span>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-semibold text-text-primary">{{ number_format($amount) }} ₫</div>
                        <div class="text-xs text-text-secondary">{{ number_format($paidCount) }}/{{ number_format($count) }} đơn đã thanh toán</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Báo cáo theo trạng thái -->
    <div class="bg-white/60 backdrop-blur-md rounded-[24px] border border-white/40 p-6 shadow-sm">
        <h3 class="text-lg font-display mb-4 flex items-center gap-2 text-text-primary"><i data-lucide="activity" class="w-5 h-5"></i> Theo trạng thái</h3>
        <div class="space-y-3">
            @foreach($statuses as $key => $name)
                @php 
                    $stat = $statusTotals->get($key); 
                    if (!$stat) continue;
                @endphp
                <div class="flex items-center justify-between py-2 border-b border-green-border/50 last:border-0">
                    <span class="text-sm text-text-secondary">{{ $name }}</span>
                    <div class="text-right">
                        <div class="font-mono text-sm font-medium text-text-primary">{{ number_format($stat->order_count) }} đơn</div>
                        <div class="text-xs text-text-secondary">Giá trị đơn: {{ number_format($stat->total_amount ?? 0) }} ₫</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-8 text-right">
    <a href="{{ route('admin.finance.transactions') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-green-primary text-white rounded-full font-medium hover:bg-green-accent transition-colors shadow-sm">
        Xem chi tiết giao dịch <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
</div>
@endsection
