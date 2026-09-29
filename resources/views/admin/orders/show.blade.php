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
                <div class="text-text-primary mono" style="overflow-wrap:anywhere">{{ $order->ghn_order_code }}</div>
                <form method="POST" action="{{ route('admin.orders.syncGhn', $order) }}" class="mt-3">@csrf<button type="submit" style="border:1px solid #E5E2DC;border-radius:999px;padding:8px 14px;background:#FFFFFF;color:#4A6B1F;font-size:12px;cursor:pointer">Đồng bộ GHN</button></form>
            </div>
            @elseif($order->canRetryGhn())
            <div class="sm:col-span-2">
                <div class="text-sm font-semibold text-text-secondary mono mb-1">Vận đơn GHN</div>
                <div class="text-amber-700 text-sm mb-2">Đơn hàng chưa tạo được vận đơn GHN.</div>
                <form method="POST" action="{{ route('admin.orders.retryGhn', $order) }}">
                    @csrf
                    <button type="submit" class="px-5 py-2 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors text-sm border border-green-border">
                        Tạo lại vận đơn GHN
                    </button>
                </form>
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

        <div class="pt-4 border-t border-green-border/20 mb-5">
            <p style="font-size:12px;line-height:1.5;color:#5C2323;margin:0 0 12px">Trạng thái vận chuyển do GHN cập nhật tự động — chỉ đổi tay khi có ngoại lệ.</p>
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" style="display:grid;gap:10px">
                @csrf
                @method('PATCH')
                <label for="shipping_status" style="font-family:'Space Mono',monospace;font-size:11px;color:#8A8680">Trạng thái vận chuyển</label>
                <select id="shipping_status" name="shipping_status" style="width:100%;min-width:0;border:1px solid #E5E2DC;border-radius:12px;padding:10px;background:#FFFFFF;font-size:13px">
                    @foreach(\App\Models\Order::SHIPPING_LABELS as $value => $label)
                        @if($value !== 'cancelled')<option value="{{ $value }}" @selected(old('shipping_status', $order->shipping_status) === $value)>{{ $label }}</option>@endif
                    @endforeach
                </select>
                <label for="status_note" style="font-family:'Space Mono',monospace;font-size:11px;color:#8A8680">Ghi chú bắt buộc</label>
                <textarea id="status_note" name="note" required maxlength="255" rows="3" style="width:100%;min-width:0;border:1px solid #E5E2DC;border-radius:12px;padding:10px;font-size:13px" placeholder="Lý do cập nhật trạng thái">{{ old('note') }}</textarea>
                @error('note')<p style="font-size:12px;color:#5C2323;margin:0">{{ $message }}</p>@enderror
                <button type="submit" style="background:#5C2323;color:#FFFFFF;border:0;border-radius:999px;padding:10px 16px;font-size:12px;cursor:pointer">Cập nhật trạng thái</button>
            </form>
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
                            <a href="{{ route('products.show', $item->product->id) }}" class="hover:text-green-primary transition-colors">{{ $item->product_name ?: $item->product->name }}</a>
                        @else
                            <span class="text-text-secondary italic">{{ $item->product_name ?: 'Sản phẩm đã bị xóa' }}</span>
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
                <span class="text-text-primary font-medium">{{ number_format($order->subtotal(), 0, ',', '.') }}đ</span>
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

<section style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:clamp(18px,4vw,28px);margin-bottom:24px;min-width:0">
    <h3 style="font-family:'Anton',sans-serif;font-size:22px;text-transform:uppercase;margin:0 0 20px">Lịch sử trạng thái</h3>
    @php
        $sourceLabels = ['customer' => 'Khách hàng', 'admin' => 'Admin', 'system' => 'Hệ thống', 'momo' => 'MoMo', 'ghn_webhook' => 'GHN webhook', 'ghn_sync' => 'GHN đồng bộ', 'scheduler' => 'Hệ thống tự động'];
    @endphp
    <ol style="list-style:none;padding:0;margin:0;border-left:1px solid #E5E2DC">
        @forelse($order->statusHistories->sortByDesc('occurred_at') as $history)
            <li style="position:relative;padding:0 0 20px 20px;margin-left:-5px;overflow-wrap:anywhere">
                <span style="position:absolute;left:0;top:5px;width:9px;height:9px;border-radius:999px;background:{{ $loop->first ? '#4A6B1F' : '#E5E2DC' }}"></span>
                <strong style="display:block;font-size:14px;color:#1C1C1A">{{ $history->field === 'shipping_status' ? (\App\Models\Order::SHIPPING_LABELS[$history->to_value] ?? $history->to_value) : ($history->field === 'status' ? (\App\Models\Order::STATUS_LABELS[$history->to_value] ?? $history->to_value) : ($history->to_value === 'placed' ? 'Đã đặt hàng' : ($history->to_value === 'completed' ? 'Hoàn thành' : $history->to_value))) }}</strong>
                <time style="display:block;font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;margin-top:4px">{{ $history->occurred_at?->format('d/m/Y H:i') }} · {{ $sourceLabels[$history->source] ?? $history->source }}@if($history->actor) · {{ $history->actor->name }}@endif</time>
                @if($history->note)<p style="font-size:13px;color:#8A8680;margin:5px 0 0">{{ $history->note }}</p>@endif
            </li>
        @empty
            <li style="padding-left:20px;color:#8A8680;font-size:13px">Chưa có lịch sử trạng thái.</li>
        @endforelse
    </ol>
</section>

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
