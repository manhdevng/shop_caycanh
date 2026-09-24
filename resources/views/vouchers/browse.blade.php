@extends('layouts.shop')

@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Săn mã giảm giá</span>
</nav>

<section style="max-width:900px;margin:0 auto;padding:20px 24px 16px;text-align:center">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 10px">Săn mã giảm giá</h1>
    <p style="font-size:14px;color:#6B6B66;margin:0">Tổng hợp toàn bộ mã giảm giá đang áp dụng tại Cây Cảnh Shop 🔥 — bấm "Lưu" để cất vào ví, rồi bấm "Dùng" khi mua sắm.</p>
    @auth
        <p style="margin:10px 0 0"><a href="{{ route('vouchers.wallet') }}" style="font-size:13px;color:#5C2323;text-decoration:underline">Xem ví voucher của tôi &rarr;</a></p>
    @endauth
</section>

<section style="max-width:900px;margin:0 auto;padding:20px 24px clamp(64px,8vw,96px)">
    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:20px;padding:28px 24px">
        <h2 style="font-family:'Anton',sans-serif;font-size:20px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 18px">Mã giảm giá đang khả dụng</h2>

        @php
            $user = auth()->user();
            $saved = $vouchers->filter(fn($v) => $v->isSavedBy($user));
            $unsaved = $vouchers->reject(fn($v) => $v->isSavedBy($user));
        @endphp
        @include('partials.voucher-list', ['availableVouchers' => $saved, 'savableVouchers' => $unsaved, 'subtotal' => $subtotal])

        @if($vouchers->isEmpty())
            <div style="text-align:center;padding:60px 0">
                <div style="font-size:40px;margin-bottom:16px">🎟️</div>
                <p style="font-size:15px;color:#6B6B66;margin:0">Hiện chưa có mã giảm giá nào, quay lại sau nhé!</p>
            </div>
        @endif
    </div>

    <div style="text-align:center;margin-top:24px">
        <a href="{{ route('shop.index') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none">Tiếp tục mua sắm</a>
    </div>
</section>
@endsection
