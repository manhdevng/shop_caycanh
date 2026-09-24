@extends('layouts.shop')

@section('title', 'Hỗ trợ của tôi · Cây Cảnh Shop')

@section('content')

<section style="max-width:900px;margin:0 auto;padding:32px 24px 96px">
    <nav aria-label="breadcrumb" style="font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
        <span>&rsaquo;</span>
        <span style="color:#1C1C1A">Hỗ trợ của tôi</span>
    </nav>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:28px">
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(26px,4vw,38px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Yêu cầu hỗ trợ của tôi</h1>
        <a href="{{ route('tickets.create') }}" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap">
            <i data-lucide="plus-circle" style="width:16px;height:16px"></i>
            Tạo yêu cầu hỗ trợ mới
        </a>
    </div>

    @if($tickets->isEmpty())
        <div style="text-align:center;padding:64px 24px;background:#F7F4EF;border:1px solid #E5E2DC;border-radius:20px;color:#6B6B66">
            <p style="margin:0 0 16px;font-size:14px">Bạn chưa có yêu cầu hỗ trợ nào.</p>
            <a href="{{ route('tickets.create') }}" style="color:#5C2323;font-weight:600;text-decoration:underline">Tạo yêu cầu hỗ trợ đầu tiên</a>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px">
            @foreach($tickets as $ticket)
                @php
                    $statusMap = [
                        'open' => ['label' => 'Đang mở', 'bg' => '#FBEAEA', 'border' => '#E7C6C6', 'color' => '#B3261E'],
                        'answered' => ['label' => 'Đã trả lời', 'bg' => '#EEF3EA', 'border' => '#C9D8C0', 'color' => '#3F5B45'],
                        'closed' => ['label' => 'Đã đóng', 'bg' => '#F1F0EC', 'border' => '#E5E2DC', 'color' => '#6B6B66'],
                    ];
                    $statusInfo = $statusMap[$ticket->status] ?? $statusMap['open'];
                @endphp
                <a href="{{ route('tickets.show', $ticket) }}" style="position:relative;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:18px 22px;transition:box-shadow .2s ease">
                    <div style="min-width:0;flex:1 1 260px">
                        <p style="margin:0 0 4px;font-size:15px;font-weight:600;color:#1C1C1A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ticket->subject }}</p>
                        <p style="margin:0;font-size:12px;color:#8A8680">Tạo lúc {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <span style="flex:none;padding:6px 14px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;background:{{ $statusInfo['bg'] }};border:1px solid {{ $statusInfo['border'] }};color:{{ $statusInfo['color'] }}">{{ $statusInfo['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div style="margin-top:24px">{{ $tickets->links() }}</div>
    @endif
</section>

@endsection
