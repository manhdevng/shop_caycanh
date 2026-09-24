@extends('layouts.app')

@section('title', 'Đơn hàng · Cây Cảnh Shop')

@php
    // Nhãn tiếng Việt cho trạng thái đơn hàng (order.status) — controller chưa truyền
    // sẵn mảng này nên định nghĩa cục bộ để hiển thị, giống cách admin/orders/show.blade.php làm.
    $orderStatusLabels = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'paid_momo' => 'Đã thanh toán MoMo',
        'cod_ordered' => 'COD - Đã đặt',
        'cod_paid' => 'COD - Đã thu tiền',
        'awaiting_transfer' => 'Chờ chuyển khoản',
        'cancelled' => 'Đã hủy',
    ];
    $gatewayLabels = [
        'cod' => 'COD',
        'momo' => 'MoMo',
        'bank_transfer' => 'Chuyển khoản ngân hàng',
        'unknown' => 'Không xác định',
    ];
    $sortLabels = [
        'newest' => 'Mới nhất',
        'oldest' => 'Cũ nhất',
        'amount_desc' => 'Giá trị giảm dần',
        'amount_asc' => 'Giá trị tăng dần',
    ];

    // Nhóm trạng thái vận chuyển: các đơn còn ở nhóm này mới cho phép đổi trạng thái nhanh + hủy đơn.
    $pendingShippingStatuses = ['pending', 'not_shipped', 'processing', 'ready_to_pick', 'picking'];
@endphp

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Quản lý Đơn hàng</h2>
    </div>

    <!-- Thanh tab trạng thái vận chuyển -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
        @foreach($tabs as $tabKey => $tab)
            <a href="{{ route('orders.index', array_merge(request()->except(['tab', 'page']), ['tab' => $tabKey])) }}"
               class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors flex items-center gap-2 {{ $activeTab === $tabKey ? 'bg-green-primary text-white border-green-border' : 'bg-white text-text-secondary border-green-border hover:bg-green-background' }}">
                {{ $tab['label'] }}
                <span class="px-2 py-0.5 rounded-pill text-xs mono {{ $activeTab === $tabKey ? 'bg-white/20 text-white' : 'bg-green-background border border-green-border/50 text-text-secondary' }}">
                    {{ $tab['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- Bộ lọc -->
    <form action="{{ route('orders.index') }}" method="GET" class="flex flex-wrap items-end gap-4 mb-8 bg-[#f8f9f5] border border-green-border/30 rounded-2xl p-5">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Tìm kiếm</label>
            <input type="text" name="search" value="{{ old('search', $filters['search'] ?? '') }}" placeholder="Tên, SĐT, mã đơn, mã vận đơn..." class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary w-56">
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Trạng thái đơn</label>
            <select name="status" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                @foreach($orderStatusLabels as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Thanh toán</label>
            <select name="payment_status" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                @foreach($paymentLabels as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['payment_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Vận chuyển</label>
            <select name="shipping_status" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                @foreach($shippingLabels as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['shipping_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Cổng thanh toán</label>
            <select name="gateway" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                @foreach($gatewayLabels as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['gateway'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Từ ngày</label>
            <input type="date" name="date_from" value="{{ old('date_from', $filters['date_from'] ?? '') }}" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Đến ngày</label>
            <input type="date" name="date_to" value="{{ old('date_to', $filters['date_to'] ?? '') }}" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
            @error('date_to')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Sắp xếp</label>
            <select name="sort" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                @foreach($sortLabels as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['sort'] ?? 'newest') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-sm border border-green-border">
                <i data-lucide="filter" class="w-4 h-4"></i> Lọc
            </button>
            <a href="{{ route('orders.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
                Xóa lọc
            </a>
        </div>
    </form>

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
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Mã đơn</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khách hàng</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tổng tiền</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Thanh toán</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Vận chuyển</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ngày tạo</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="hover:text-green-primary transition-colors font-semibold">#{{ $order->id }}</a>
                        @if($order->ghn_order_code)
                            <div class="text-xs text-text-secondary mono mt-1">GHN: {{ $order->ghn_order_code }}</div>
                        @endif
                    </td>
                    <td class="py-5 pr-4 font-medium text-text-primary">
                        {{ $order->name ?: 'Khách vãng lai' }}
                        <div class="text-xs text-text-secondary font-normal mt-1">{{ $order->phone }}</div>
                    </td>
                    <td class="py-5 pr-4 text-text-primary font-medium">{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                    <td class="py-5 pr-4">
                        <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                        <div class="text-xs text-text-secondary mono mt-1 uppercase">{{ $gatewayLabels[$order->gateway] ?? $order->gateway }}</div>
                    </td>
                    <td class="py-5 pr-4">
                        <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                            {{ $shippingLabels[$order->shipping_status] ?? $order->shipping_status }}
                        </span>
                    </td>
                    <td class="py-5 pr-4 text-text-secondary text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors inline-flex" title="Xem chi tiết">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </a>

                            @if(in_array($order->shipping_status, $pendingShippingStatuses, true))
                                <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <select name="shipping_status" onchange="this.form.submit()" class="rounded-xl border-green-border/50 border px-3 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-xs text-text-primary">
                                        @foreach($shippingLabels as $value => $label)
                                            <option value="{{ $value }}" {{ $order->shipping_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>

                                <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?');">
                                    @csrf
                                    <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Hủy đơn">
                                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-text-secondary">
                        <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-4 text-green-border"></i>
                        Chưa có đơn hàng nào phù hợp.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
@endsection
