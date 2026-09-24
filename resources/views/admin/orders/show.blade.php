@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng #' . $order->id . ' · Cây Cảnh Shop')

@php
    $statusLabels = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'cod_ordered' => 'COD - Đã đặt',
        'awaiting_transfer' => 'Chờ chuyển khoản',
    ];
    $shippingStatusLabels = [
        'not_shipped' => 'Chưa giao vận',
        'ready_to_pick' => 'Chờ lấy hàng',
        'delivering' => 'Đang giao',
        'delivered' => 'Đã giao',
    ];
    $itemsSubtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
@endphp

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Đơn hàng #{{ $order->id }}</h2>
    <a href="{{ route('orders.index') }}" class="px-5 py-2 text-text-secondary hover:text-text-primary border border-transparent hover:border-green-border bg-transparent hover:bg-white rounded-pill flex items-center gap-2 transition-all text-decoration-none text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Quay lại danh sách
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Thông tin người nhận -->
    <div class="md:col-span-2 bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Thông tin người nhận</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Khách hàng</div>
                <div class="text-text-primary font-medium">{{ $order->user->name ?? $order->name ?? 'Khách vãng lai' }}</div>
                @if($order->user && $order->user->email)
                    <div class="text-text-secondary text-sm mt-1">{{ $order->user->email }}</div>
                @endif
            </div>

            <div>
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Người nhận</div>
                <div class="text-text-primary font-medium">{{ $order->name }}</div>
                <div class="text-text-secondary text-sm mt-1">{{ $order->phone }}</div>
            </div>

            <div class="sm:col-span-2">
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Địa chỉ giao hàng</div>
                <div class="text-text-primary">{{ $order->address }}</div>
                <div class="text-text-secondary text-xs mono mt-1">
                    Mã quận/huyện GHN: {{ $order->to_district_id ?? '—' }} &middot; Mã phường/xã GHN: {{ $order->to_ward_code ?? '—' }}
                </div>
            </div>

            @if($order->ghn_order_code)
            <div class="sm:col-span-2">
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Mã vận đơn GHN</div>
                <div class="text-text-primary mono">{{ $order->ghn_order_code }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Trạng thái -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Trạng thái</h3>

        <div class="mb-5">
            <div class="text-sm font-semibold text-text-secondary mono mb-2">Thanh toán</div>
            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
        </div>

        <div class="mb-5">
            <div class="text-sm font-semibold text-text-secondary mono mb-2">Vận chuyển</div>
            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                {{ $shippingStatusLabels[$order->shipping_status] ?? $order->shipping_status }}
            </span>
        </div>

        <div class="mb-5">
            <div class="text-sm font-semibold text-text-secondary mono mb-2">Hình thức thanh toán</div>
            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                {{ $order->payment_method_label }}
            </span>
        </div>

        <div class="pt-4 border-t border-green-border/20">
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Ngày tạo</div>
            <div class="text-text-primary text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

@if($order->status === 'awaiting_transfer' || $order->payment_method === 'bank_transfer')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Đối soát chuyển khoản</h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Số tiền cần đối soát</div>
            <div class="text-text-primary font-bold text-lg">{{ number_format($order->total_price, 0, ',', '.') }}đ</div>
        </div>
        <div>
            <div class="text-sm font-semibold text-text-secondary mono mb-1">Mã tham chiếu khách nhập</div>
            <div class="text-text-primary">{{ $order->transfer_ref ?: 'Khách không nhập' }}</div>
        </div>
    </div>

    @if($order->transfer_confirmed_at)
        <div class="px-5 py-4 bg-green-background border border-green-border/50 text-text-primary rounded-2xl text-sm">
            Đã xác nhận nhận tiền bởi <span class="font-semibold">{{ $order->transferConfirmedBy?->name ?? 'Không rõ' }}</span>
            lúc {{ $order->transfer_confirmed_at->format('d/m/Y H:i') }}.
        </div>
    @elseif($order->status === 'awaiting_transfer')
        <div>
            <input type="checkbox" id="reject-transfer-toggle" class="peer hidden">

            <div class="flex flex-col sm:flex-row gap-4">
                <form method="POST" action="{{ route('admin.orders.confirmTransfer', $order) }}" class="flex-1 space-y-3">
                    @csrf
                    <div>
                        <label for="admin_note_confirm" class="block text-xs font-semibold text-text-secondary mono uppercase tracking-wider mb-1">Ghi chú (không bắt buộc)</label>
                        <textarea name="admin_note" id="admin_note_confirm" maxlength="255" rows="2" class="w-full rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary" placeholder="Ví dụ: đã kiểm tra sao kê ngân hàng lúc 10h"></textarea>
                        @error('admin_note') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors text-sm border border-green-border">
                        Xác nhận đã nhận tiền
                    </button>
                </form>

                <div class="flex-1">
                    <label for="reject-transfer-toggle" class="inline-block cursor-pointer px-6 py-2 bg-red-50 text-red-600 border border-red-200 rounded-pill font-medium hover:bg-red-100 transition-colors text-sm">
                        Từ chối
                    </label>

                    <div class="hidden peer-checked:block mt-4 p-4 bg-red-50 border border-red-200 rounded-2xl">
                        <p class="text-sm text-red-700 mb-3">Bạn chắc chắn muốn từ chối chuyển khoản này? Đơn hàng sẽ bị <span class="font-semibold">HỦY</span> và hàng sẽ được hoàn lại kho. Hành động này không thể hoàn tác.</p>
                        <form method="POST" action="{{ route('admin.orders.rejectTransfer', $order) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="admin_note_reject" class="block text-xs font-semibold text-text-secondary mono uppercase tracking-wider mb-1">Lý do từ chối (không bắt buộc)</label>
                                <textarea name="admin_note" id="admin_note_reject" maxlength="255" rows="2" class="w-full rounded-xl border-red-200 border px-4 py-2 bg-white focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none text-sm text-text-primary" placeholder="Ví dụ: không thấy tiền về sau 48h"></textarea>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-pill font-medium hover:bg-red-700 transition-colors text-sm">
                                    Tôi chắc chắn, từ chối đơn
                                </button>
                                <label for="reject-transfer-toggle" class="text-sm text-text-secondary hover:text-text-primary cursor-pointer">Quay lại</label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endif

<!-- Danh sách sản phẩm -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-6">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Sản phẩm trong đơn</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Sản phẩm</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đơn giá</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Số lượng</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                <tr class="border-b border-green-border/20">
                    <td class="py-4 pr-4 text-text-primary font-medium">
                        @if($item->product)
                            <a href="{{ route('products.show', $item->product->id) }}" class="hover:text-green-primary transition-colors">{{ $item->product->name }}</a>
                        @else
                            <span class="text-text-secondary italic">Sản phẩm đã bị xóa</span>
                        @endif
                        @if($item->variant_name)
                            <div class="text-xs text-text-secondary font-normal mt-1">Phân loại: {{ $item->variant_name }}</div>
                        @endif
                    </td>
                    <td class="py-4 pr-4 text-text-primary">{{ number_format($item->price, 0, ',', '.') }}đ</td>
                    <td class="py-4 pr-4 text-text-primary mono">{{ $item->quantity }}</td>
                    <td class="py-4 text-text-primary font-medium text-right">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-text-secondary italic">Đơn hàng này không có sản phẩm nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-end">
        <div class="w-full sm:w-80 space-y-2">
            <div class="flex justify-between text-sm text-text-secondary">
                <span>Tạm tính (tiền hàng)</span>
                <span class="text-text-primary font-medium">{{ number_format($itemsSubtotal, 0, ',', '.') }}đ</span>
            </div>
            <div class="flex justify-between text-sm text-text-secondary">
                <span>Phí vận chuyển (GHN)</span>
                <span class="text-text-primary font-medium">{{ number_format($order->ghn_total_fee, 0, ',', '.') }}đ</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-green-border/30 text-base">
                <span class="font-semibold text-text-primary mono uppercase text-sm tracking-wider">Tổng cộng</span>
                <span class="text-text-primary font-bold text-lg">{{ number_format($order->total_price, 0, ',', '.') }}đ</span>
            </div>
        </div>
    </div>
</div>

<!-- Lịch sử giao dịch thanh toán -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Giao dịch thanh toán</h3>

    @if($order->paymentTransactions->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Cổng thanh toán</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Số tiền</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Thông điệp</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Thời gian thanh toán</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->paymentTransactions as $transaction)
                <tr class="border-b border-green-border/20">
                    <td class="py-4 pr-4 text-text-primary font-medium mono uppercase text-sm">{{ $transaction->gateway }}</td>
                    <td class="py-4 pr-4 text-text-primary">{{ number_format($transaction->amount, 0, ',', '.') }}đ</td>
                    <td class="py-4 pr-4">
                        <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">
                            {{ $transaction->status }}
                        </span>
                    </td>
                    <td class="py-4 pr-4 text-text-secondary text-sm">{{ $transaction->message ?: '—' }}</td>
                    <td class="py-4 text-text-secondary text-sm">{{ $transaction->paid_at ? $transaction->paid_at->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-text-secondary italic">Chưa có giao dịch thanh toán.</p>
    @endif
</div>
@endsection
