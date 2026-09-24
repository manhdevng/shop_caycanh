@extends('layouts.shop')

@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Đơn hàng của tôi</span>
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 8px">Đơn hàng của tôi</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $orders->total() }} đơn hàng</p>
</section>

<section style="max-width:1400px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    <div style="display:flex;flex-direction:column;gap:16px">
        @forelse($orders as $order)
            <div class="relative transition-all hover:shadow-lg hover:-translate-y-0.5" style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:24px 28px">
                <a href="{{ route('orders.show', $order) }}" class="absolute inset-0" style="text-decoration:none" aria-label="Xem đơn hàng #{{ $order->id }}"></a>

                <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px">
                    <span style="font-family:'Anton',sans-serif;font-size:20px;letter-spacing:0.01em;color:#1C1C1A">Đơn hàng #{{ $order->id }}</span>
                    <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px">
                    <span style="font-size:14px;color:#6B6B66">{{ $order->items->count() }} sản phẩm</span>
                    <span style="font-size:18px;font-weight:700;color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</span>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#EFEDE7;color:#1C1C1A">{{ $order->status_label }}</span>
                        <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#FFFFFF;border:1px solid #E5E2DC;color:#6B6B66">{{ $order->shipping_label }}</span>
                    </div>

                    @if(in_array($order->status, ['pending', 'payment_failed']))
                        <div class="relative z-10" style="display:flex;align-items:center;gap:8px">
                            <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'atm']) }}" class="hover:opacity-90" style="display:inline-flex;align-items:center;gap:4px;background:#D82D8B;color:#FFFFFF;font-size:12px;font-weight:600;padding:8px 14px;border-radius:999px;text-decoration:none">
                                🏧 Nội địa
                            </a>
                            <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'cc']) }}" class="hover:bg-pink-50" style="display:inline-flex;align-items:center;gap:4px;background:#FFFFFF;border:1px solid #F4A8CF;color:#D82D8B;font-size:12px;font-weight:600;padding:8px 14px;border-radius:999px;text-decoration:none">
                                🌍 Quốc tế
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:80px 0;border:1px dashed #E5E2DC;border-radius:16px">
                <div style="font-size:40px;margin-bottom:16px">📦</div>
                <p style="font-size:15px;color:#6B6B66;margin:0 0 24px">Bạn chưa có đơn hàng nào.</p>
                <a href="{{ route('shop.index') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none">Bắt đầu mua sắm</a>
            </div>
        @endforelse
    </div>

    @if($orders->hasPages())
        <div style="margin-top:40px">{{ $orders->links() }}</div>
    @endif
</section>
@endsection
