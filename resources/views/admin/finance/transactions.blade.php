@extends('layouts.app')

@section('title', 'Giao dịch thanh toán · Cây Cảnh Shop')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-4xl font-display text-text-primary tracking-tight">Giao dịch thanh toán</h2>
        <div class="flex items-center gap-2 text-sm text-text-secondary mt-2">
            <a href="{{ route('admin.finance.index') }}" class="hover:text-green-primary transition-colors">Thống kê</a>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span>Chi tiết</span>
        </div>
    </div>
</div>

<!-- Lọc -->
<div class="bg-white/40 backdrop-blur-xl border border-white/20 p-5 rounded-[24px] shadow-sm mb-6">
    <form action="{{ route('admin.finance.transactions') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="lg:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã đơn, SĐT..." class="w-full bg-white border border-green-border rounded-full px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
        </div>
        <div>
            <select name="payment_status" class="w-full bg-white border border-green-border rounded-full px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
                <option value="">Trạng thái</option>
                @foreach($statuses as $key => $name)
                    <option value="{{ $key }}" {{ request('payment_status') == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="sort" class="w-full bg-white border border-green-border rounded-full px-4 py-2 text-sm outline-none focus:border-green-primary transition-all">
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                <option value="amount_desc" {{ request('sort') == 'amount_desc' ? 'selected' : '' }}>Tiền giảm dần</option>
                <option value="amount_asc" {{ request('sort') == 'amount_asc' ? 'selected' : '' }}>Tiền tăng dần</option>
            </select>
        </div>
        <div class="lg:col-span-2 flex gap-2">
            <button type="submit" class="flex-1 py-2 bg-green-primary text-white rounded-full hover:bg-green-accent transition-all text-sm font-medium">Lọc</button>
            <a href="{{ route('admin.finance.transactions') }}" class="px-4 py-2 bg-white text-text-primary border border-green-border rounded-full hover:bg-green-background transition-all text-sm font-medium flex items-center justify-center">Xóa</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-[24px] border border-green-border shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-green-background/50 border-b border-green-border">
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Đơn hàng</th>
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Khách hàng</th>
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Số tiền</th>
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Kênh & Trạng thái</th>
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Ngày cập nhật</th>
                    <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Thao tác COD</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/50">
                @forelse($orders as $order)
                <tr class="hover:bg-green-background/20 transition-colors">
                    <td class="py-4 px-6">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="font-mono text-sm font-medium text-green-primary hover:underline block">#{{ $order->id }}</a>
                        <div class="text-xs text-text-secondary mt-1">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="text-sm font-medium text-text-primary">{{ $order->name }}</div>
                        <div class="text-xs text-text-secondary">{{ $order->phone }}</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="text-sm font-medium text-text-primary">{{ number_format($order->total_price) }} ₫</div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="flex flex-col gap-1 items-start">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-background text-text-primary border border-green-border">
                                {{ $methods[$order->gateway] ?? 'Khác' }}
                            </span>
                            @php
                                $statusColor = match($order->payment_status) {
                                    'paid' => 'bg-green-100 text-green-700 border-green-200',
                                    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'failed', 'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                    'refund_pending' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'refunded' => 'bg-gray-100 text-gray-700 border-gray-200',
                                    default => 'bg-blue-100 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusColor }}">
                                {{ $statuses[$order->payment_status] ?? $order->payment_status }}
                            </span>
                        </div>
                    </td>
                    <td class="py-4 px-6">
                        <div class="text-sm text-text-primary">{{ $order->paid_at ? \Carbon\Carbon::parse($order->paid_at)->format('d/m/Y H:i') : '-' }}</div>
                    </td>
                    <td class="py-4 px-6 text-right">
                        @if(($order->gateway === 'cod' || in_array($order->status, ['cod_ordered', 'cod_paid'])) && isset($codTransitions[$order->payment_status]) && count($codTransitions[$order->payment_status]) > 0)
                            <form action="{{ route('admin.finance.update-status', $order->id) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                                <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                                <input type="hidden" name="current_payment_id" value="{{ $order->payment_id ?? 0 }}">
                                
                                <select name="payment_status" class="text-sm border border-green-border rounded-lg px-2 py-1 outline-none focus:border-green-primary bg-white min-w-[140px]">
                                    @foreach($codTransitions[$order->payment_status] as $nextStatus)
                                        <option value="{{ $nextStatus }}" {{ $order->payment_status === $nextStatus ? 'selected' : '' }}>
                                            {{ $statuses[$nextStatus] ?? $nextStatus }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="p-1.5 bg-green-primary text-white rounded-lg hover:bg-green-accent transition-colors" title="Cập nhật">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-text-secondary">Không tìm thấy giao dịch nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="p-4 border-t border-green-border">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
