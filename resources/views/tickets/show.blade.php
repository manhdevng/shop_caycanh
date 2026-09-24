@extends('layouts.shop')

@section('title', $ticket->subject . ' · Hỗ trợ · Cây Cảnh Shop')

@section('content')

@php
    $statusMap = [
        'open' => ['label' => 'Đang mở', 'bg' => '#FBEAEA', 'border' => '#E7C6C6', 'color' => '#B3261E'],
        'answered' => ['label' => 'Đã trả lời', 'bg' => '#EEF3EA', 'border' => '#C9D8C0', 'color' => '#3F5B45'],
        'closed' => ['label' => 'Đã đóng', 'bg' => '#F1F0EC', 'border' => '#E5E2DC', 'color' => '#6B6B66'],
    ];
    $statusInfo = $statusMap[$ticket->status] ?? $statusMap['open'];
@endphp

<section style="max-width:760px;margin:0 auto;padding:32px 24px 96px">
    <nav aria-label="breadcrumb" style="font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
        <span>&rsaquo;</span>
        <a href="{{ route('tickets.index') }}" style="color:#8A8680">Hỗ trợ của tôi</a>
        <span>&rsaquo;</span>
        <span style="color:#1C1C1A">#{{ $ticket->id }}</span>
    </nav>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px">
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(24px,4vw,34px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">{{ $ticket->subject }}</h1>
        <span style="flex:none;padding:7px 16px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;background:{{ $statusInfo['bg'] }};border:1px solid {{ $statusInfo['border'] }};color:{{ $statusInfo['color'] }}">{{ $statusInfo['label'] }}</span>
    </div>

    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:20px;padding:20px;margin-bottom:24px;display:flex;flex-direction:column;gap:14px">
        @forelse($ticket->replies as $reply)
            @php $isAdminReply = $reply->user && $reply->user->role === 'admin'; @endphp
            <div style="display:flex;{{ $isAdminReply ? 'justify-content:flex-start' : 'justify-content:flex-end' }}">
                <div style="max-width:80%;padding:12px 16px;border-radius:16px;{{ $isAdminReply ? 'background:#F1F0EC;color:#1C1C1A;border-bottom-left-radius:4px' : 'background:#5C2323;color:#FFFFFF;border-bottom-right-radius:4px' }}">
                    <p style="margin:0 0 6px;font-family:'Space Mono',monospace;font-size:10.5px;letter-spacing:0.05em;text-transform:uppercase;{{ $isAdminReply ? 'color:#6B6B66' : 'color:rgba(255,255,255,0.75)' }}">
                        {{ $isAdminReply ? 'Admin' : ($reply->user->name ?? 'Bạn') }} &middot; {{ $reply->created_at->format('d/m/Y H:i') }}
                    </p>
                    <p style="margin:0;font-size:14px;line-height:1.6;white-space:pre-line">{{ $reply->message }}</p>
                </div>
            </div>
        @empty
            <p style="text-align:center;color:#8A8680;font-size:13px;padding:24px 0">Chưa có tin nhắn nào trong yêu cầu hỗ trợ này.</p>
        @endforelse
    </div>

    @if($ticket->status === 'closed')
        <div style="background:#F1F0EC;border:1px solid #E5E2DC;color:#6B6B66;border-radius:14px;padding:16px 20px;font-size:14px;text-align:center">
            Ticket đã đóng, không thể trả lời thêm.
        </div>
    @else
        <form action="{{ route('tickets.reply', $ticket) }}" method="POST" style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:20px;padding:20px">
            @csrf
            <label for="message" style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin-bottom:8px">Trả lời</label>
            <textarea id="message" name="message" rows="4" placeholder="Nhập nội dung trả lời..." required
                      style="width:100%;box-sizing:border-box;padding:12px 16px;border:1px solid #E5E2DC;border-radius:10px;font-size:14px;font-family:inherit;outline:none;resize:vertical">{{ old('message') }}</textarea>
            @error('message')
                <p style="margin:6px 0 0;font-size:12px;color:#B3261E">{{ $message }}</p>
            @enderror
            <div style="display:flex;justify-content:flex-end;margin-top:14px">
                <button type="submit" style="padding:12px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Gửi trả lời</button>
            </div>
        </form>
    @endif
</section>

@endsection
