@extends('layouts.shop')
@section('content')

@php
    $scopeLabelFor = function ($voucher) {
        if ($voucher->scope_type === 'products') return 'Chỉ một số sản phẩm';
        if ($voucher->scope_type === 'categories') return 'Chỉ một số danh mục';
        return null;
    };
@endphp

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Ví voucher của tôi</span>
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 8px">Ví voucher của tôi</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $usable->count() }} mã đang dùng được</p>
</section>

<section style="max-width:1000px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">

    @if($usable->isEmpty() && $used->isEmpty() && $expired->isEmpty())
        <div style="text-align:center;padding:80px 0;border:1px dashed #E5E2DC;border-radius:16px">
            <div style="font-size:40px;margin-bottom:16px">🎟</div>
            <p style="font-size:15px;color:#6B6B66;margin:0 0 24px">Ví của bạn chưa có mã nào.</p>
            <a href="{{ route('vouchers.browse') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none">Săn mã giảm giá</a>
        </div>
    @else

        @if($usable->isNotEmpty())
        <div style="margin-bottom:36px">
            <h2 style="font-family:'Anton',sans-serif;font-size:20px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 14px">Đang dùng được</h2>
            <div style="display:flex;flex-direction:column;gap:12px">
                @foreach($usable as $voucher)
                    @php
                        $minAmount = (float) ($voucher->min_order_amount ?? 0);
                        $eligible = $subtotal >= $minAmount;
                        $scopeLabel = $scopeLabelFor($voucher);
                    @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px dashed #5C2323;border-radius:12px;padding:16px 18px;background:#FFFFFF">
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                                <span style="font-family:'Space Mono',monospace;font-size:13px;font-weight:700;letter-spacing:0.04em;color:#5C2323;background:#F7F4EF;border:1px solid #E5E2DC;border-radius:6px;padding:3px 10px">{{ $voucher->code }}</span>
                                <span style="font-size:14px;font-weight:600;color:#4A6B1F">{{ $voucher->summary }}</span>
                                @if($scopeLabel)
                                    <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#8A8680;background:#F0EDE6;border:1px solid #E5E2DC;border-radius:999px;padding:2px 8px">{{ $scopeLabel }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#8A8680">
                                @if($voucher->min_order_amount)
                                    Đơn tối thiểu {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ
                                @else
                                    Không giới hạn giá trị đơn hàng
                                @endif
                                @if($voucher->expires_at)
                                    <span>&middot; HSD {{ $voucher->expires_at->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </div>

                        @if($eligible)
                            <a href="{{ route('cart.index') }}" style="flex:none;padding:9px 18px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;text-decoration:none;white-space:nowrap">Dùng ngay</a>
                        @else
                            <span style="flex:none;padding:9px 18px;border-radius:999px;background:#E5E2DC;color:#A8A196;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;white-space:nowrap">Chưa đủ điều kiện</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($used->isNotEmpty())
        <div style="margin-bottom:36px">
            <h2 style="font-family:'Anton',sans-serif;font-size:20px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 14px">Đã sử dụng</h2>
            <div style="display:flex;flex-direction:column;gap:12px">
                @foreach($used as $voucher)
                    @php $scopeLabel = $scopeLabelFor($voucher); @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px dashed #E5E2DC;border-radius:12px;padding:16px 18px;background:#F7F4EF;opacity:0.55">
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                                <span style="font-family:'Space Mono',monospace;font-size:13px;font-weight:700;letter-spacing:0.04em;color:#5C2323;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:6px;padding:3px 10px">{{ $voucher->code }}</span>
                                <span style="font-size:14px;font-weight:600;color:#4A6B1F">{{ $voucher->summary }}</span>
                                @if($scopeLabel)
                                    <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#8A8680;background:#F0EDE6;border:1px solid #E5E2DC;border-radius:999px;padding:2px 8px">{{ $scopeLabel }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#8A8680">
                                Đã sử dụng
                                @if($voucher->pivot->used_at)
                                    lúc {{ \Illuminate\Support\Carbon::parse($voucher->pivot->used_at)->format('d/m/Y') }}
                                @endif
                                @if($voucher->pivot->order_id)
                                    &middot; <a href="{{ route('orders.show', $voucher->pivot->order_id) }}" style="color:#5C2323;text-decoration:underline">Xem đơn #{{ $voucher->pivot->order_id }}</a>
                                @endif
                            </div>
                        </div>
                        <span style="flex:none;padding:9px 18px;border-radius:999px;background:#E5E2DC;color:#6B6B66;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;white-space:nowrap">Đã sử dụng</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($expired->isNotEmpty())
        <div style="margin-bottom:36px">
            <h2 style="font-family:'Anton',sans-serif;font-size:20px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 14px">Hết hạn</h2>
            <div style="display:flex;flex-direction:column;gap:12px">
                @foreach($expired as $voucher)
                    @php $scopeLabel = $scopeLabelFor($voucher); @endphp
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px dashed #E5E2DC;border-radius:12px;padding:16px 18px;background:#F7F4EF;opacity:0.55">
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                                <span style="font-family:'Space Mono',monospace;font-size:13px;font-weight:700;letter-spacing:0.04em;color:#5C2323;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:6px;padding:3px 10px">{{ $voucher->code }}</span>
                                <span style="font-size:14px;font-weight:600;color:#4A6B1F">{{ $voucher->summary }}</span>
                                @if($scopeLabel)
                                    <span style="font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;color:#8A8680;background:#F0EDE6;border:1px solid #E5E2DC;border-radius:999px;padding:2px 8px">{{ $scopeLabel }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:#8A8680">
                                @if($voucher->expires_at)
                                    Hết hạn {{ $voucher->expires_at->format('d/m/Y') }}
                                @else
                                    Đã hết lượt sử dụng
                                @endif
                            </div>
                        </div>
                        <span style="flex:none;padding:9px 18px;border-radius:999px;background:#E5E2DC;color:#6B6B66;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;white-space:nowrap">Hết hạn</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    @endif
</section>
@endsection
