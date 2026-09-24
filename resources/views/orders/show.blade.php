@extends('layouts.shop')
@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <a href="{{ route('orders.history') }}" style="color:#8A8680">Đơn hàng của tôi</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">#{{ $order->id }}</span>
</nav>

<section style="max-width:800px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,4vw,44px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Đơn hàng #{{ $order->id }}</h1>
</section>

<section style="max-width:800px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:24px 28px;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#EFEDE7;color:#1C1C1A">Thanh toán: {{ $order->status_label }}</span>
            <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#FFFFFF;border:1px solid #E5E2DC;color:#6B6B66">Vận chuyển: {{ $order->shipping_label }}</span>
        </div>

        @php
            $shippingCancelledStates = ['cancelled'];
            $shippingReturnStates = ['return', 'returning', 'returned', 'return_transporting', 'return_sorting'];
            $currentStep = null;

            if (in_array($order->shipping_status, ['pending', 'not_shipped', 'processing'])) {
                $currentStep = 1;
            } elseif (in_array($order->shipping_status, ['ready_to_pick', 'picking', 'picked'])) {
                $currentStep = 2;
            } elseif (in_array($order->shipping_status, ['storing', 'transporting', 'sorting', 'delivering'])) {
                $currentStep = 3;
            } elseif ($order->shipping_status === 'delivered') {
                $currentStep = 4;
            }

            $steps = ['Đang xử lý', 'Đã đóng gói', 'Đang vận chuyển', 'Đã giao'];
        @endphp

        @if(in_array($order->shipping_status, $shippingCancelledStates))
            <div style="border-radius:12px;background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;font-size:14px;font-weight:600;padding:12px 16px;margin-bottom:20px">
                Đơn hàng đã bị huỷ
            </div>
        @elseif(in_array($order->shipping_status, $shippingReturnStates))
            <div style="border-radius:12px;background:#FFFBEB;border:1px solid #FDE68A;color:#B45309;font-size:14px;font-weight:600;padding:12px 16px;margin-bottom:20px">
                {{ $order->shipping_label }}
            </div>
        @elseif($currentStep)
            <div style="margin-bottom:20px">
                <div style="display:flex;align-items:center">
                    @foreach($steps as $index => $stepLabel)
                        @php $stepNumber = $index + 1; @endphp
                        <div style="display:flex;align-items:center;{{ $loop->last ? '' : 'flex:1' }}">
                            <div style="display:flex;flex-direction:column;align-items:center">
                                <div style="width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;{{ $stepNumber <= $currentStep ? 'background:#4A6B1F;color:#FFFFFF' : 'background:#EFEDE7;color:#8A8680' }}">
                                    {{ $stepNumber }}
                                </div>
                                <span style="margin-top:6px;font-size:11px;text-align:center;width:80px;{{ $stepNumber <= $currentStep ? 'color:#4A6B1F;font-weight:600' : 'color:#8A8680' }}">
                                    {{ $stepLabel }}
                                </span>
                            </div>
                            @if(!$loop->last)
                                <div style="flex:1;height:2px;margin:0 4px 18px;{{ $stepNumber < $currentStep ? 'background:#4A6B1F' : 'background:#E5E2DC' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(in_array($order->status, ['pending', 'payment_failed']))
            <div style="background:#FDF2F8;border:1px solid #F4A8CF;border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <p style="font-size:14px;color:#D82D8B;margin:0">
                    @if($order->status === 'payment_failed')
                        Thanh toán MoMo trước đó không thành công.
                    @else
                        Đơn hàng đang chờ thanh toán qua MoMo.
                    @endif
                </p>
                <div style="display:flex;align-items:center;gap:8px">
                    <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'atm']) }}" class="hover:opacity-90" style="display:inline-flex;align-items:center;gap:4px;background:#D82D8B;color:#FFFFFF;font-size:13px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none">
                        🏧 Thẻ nội địa
                    </a>
                    <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'cc']) }}" class="hover:bg-pink-50" style="display:inline-flex;align-items:center;gap:4px;background:#FFFFFF;border:1px solid #F4A8CF;color:#D82D8B;font-size:13px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none">
                        🌍 Thẻ quốc tế
                    </a>
                </div>
            </div>
        @endif

        @if($order->status === 'awaiting_transfer')
            @php $bankInfo = config('services.bank'); @endphp
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px;margin-bottom:20px">
                <p style="font-size:14px;font-weight:700;color:#B45309;margin:0 0 10px">⏳ Chờ chuyển khoản</p>
                <p style="font-size:13px;color:#6B6B66;margin:0 0 10px">Vui lòng chuyển khoản đúng số tiền và ghi rõ mã đơn hàng vào nội dung chuyển khoản. Đơn hàng sẽ được giao sau khi shop xác nhận đã nhận được tiền.</p>
                <div style="font-size:14px;color:#1C1C1A;line-height:1.7">
                    <div>Ngân hàng: <strong>{{ $bankInfo['name'] ?? '—' }}</strong></div>
                    <div>Số tài khoản: <strong style="font-family:'Space Mono',monospace">{{ $bankInfo['account_number'] ?? '—' }}</strong></div>
                    <div>Chủ tài khoản: <strong>{{ $bankInfo['account_name'] ?? '—' }}</strong></div>
                    <div>Chi nhánh: <strong>{{ $bankInfo['branch'] ?? '—' }}</strong></div>
                    <div>Số tiền cần chuyển: <strong style="color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</strong></div>
                    <div>Nội dung chuyển khoản: <strong>CCS {{ $order->id }}</strong></div>
                    @if($order->transfer_ref)
                        <div>Mã tham chiếu bạn đã nhập: <strong>{{ $order->transfer_ref }}</strong></div>
                    @endif
                </div>
            </div>
        @endif

        @if($order->ghn_order_code)
            <p style="font-size:14px;color:#6B6B66;margin:0 0 6px"><span style="font-weight:600;color:#1C1C1A">Mã vận đơn GHN:</span> {{ $order->ghn_order_code }}</p>
        @else
            <p style="font-size:14px;color:#B45309;margin:0 0 6px">Đơn hàng chưa tạo được vận đơn GHN — vui lòng liên hệ để được hỗ trợ.</p>
        @endif

        <p style="font-size:14px;color:#6B6B66;margin:0 0 6px"><span style="font-weight:600;color:#1C1C1A">Hình thức thanh toán:</span> {{ $order->payment_method_label }}</p>
        <p style="font-size:14px;color:#6B6B66;margin:0 0 6px"><span style="font-weight:600;color:#1C1C1A">Người nhận:</span> {{ $order->name }} — {{ $order->phone }}</p>
        <p style="font-size:14px;color:#6B6B66;margin:0"><span style="font-weight:600;color:#1C1C1A">Địa chỉ:</span> {{ $order->address }}</p>
    </div>

    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:24px 28px">
        <h3 style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;font-weight:700;margin:0 0 16px">Sản phẩm</h3>
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
            @foreach($order->items as $item)
                <div style="display:flex;align-items:flex-start;justify-content:space-between;font-size:14px;gap:12px">
                    <div style="color:#1C1C1A">
                        <span>{{ $item->product->name ?? 'Sản phẩm đã bị xoá' }} <span style="color:#8A8680">× {{ $item->quantity }}</span></span>
                        @if($item->variant_name)
                            <div style="font-size:12px;color:#8A8680;margin-top:2px">Phân loại: {{ $item->variant_name }}</div>
                        @endif
                    </div>
                    <span style="color:#1C1C1A;white-space:nowrap">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</span>
                </div>
            @endforeach
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:14px;padding-top:12px;border-top:1px solid #E5E2DC">
            <span style="color:#6B6B66">Phí vận chuyển</span>
            <span style="color:#1C1C1A">{{ number_format($order->ghn_total_fee, 0, ',', '.') }} đ</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:16px;font-weight:700;padding-top:10px;margin-top:6px;border-top:1px solid #E5E2DC">
            <span style="color:#1C1C1A">Tổng cộng</span>
            <span style="color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</span>
        </div>
    </div>
</section>
@endsection
