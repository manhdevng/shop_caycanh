@extends('layouts.shop')

@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Thông báo</span>
</nav>

<section style="max-width:900px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 8px">Thông báo</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $items->count() }} thông báo gần đây</p>
</section>

<section style="max-width:900px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">

    @if($items->isNotEmpty())
        {{-- Thanh lọc dạng tab/pill — lọc phía client bằng JS, không gọi lại server --}}
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            <button type="button" class="notif-filter-tab" data-filter="all" onclick="filterNotifications('all', this)" style="padding:8px 18px;border-radius:999px;border:1px solid #5C2323;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Tất cả</button>
            <button type="button" class="notif-filter-tab" data-filter="post" onclick="filterNotifications('post', this)" style="padding:8px 18px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Tin tức</button>
            <button type="button" class="notif-filter-tab" data-filter="voucher" onclick="filterNotifications('voucher', this)" style="padding:8px 18px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Mã giảm giá</button>
            <button type="button" class="notif-filter-tab" data-filter="product" onclick="filterNotifications('product', this)" style="padding:8px 18px;border-radius:999px;border:1px solid #E5E2DC;background:#FFFFFF;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Sản phẩm mới</button>
        </div>

        <div style="text-align:right;margin-bottom:14px">
            <a href="{{ route('vouchers.browse') }}" style="font-size:12px;color:#5C2323;font-weight:700;text-decoration:underline">Xem tất cả mã giảm giá &rarr;</a>
        </div>

        <div id="notif-list" style="display:flex;flex-direction:column;gap:12px">
            @foreach($items as $item)
                <div class="notif-card" data-type="{{ $item['type'] }}" style="display:flex;align-items:flex-start;gap:14px;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:18px 20px">
                    <div style="flex:none;font-size:26px;line-height:1">{{ $item['icon'] }}</div>
                    <div style="min-width:0;flex:1 1 auto">
                        <a href="{{ $item['url'] }}" style="font-size:15px;font-weight:700;color:#1C1C1A;text-decoration:none">{{ $item['title'] }}</a>
                        @if(!empty($item['description']))
                            <p style="font-size:13px;color:#6B6B66;margin:6px 0 0">{{ $item['description'] }}</p>
                        @endif
                        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;color:#8A8680;margin:8px 0 0">{{ $item['created_at']->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:24px">
            <a href="{{ route('vouchers.browse') }}" style="display:inline-block;padding:12px 24px;border-radius:999px;background:#FFFFFF;border:1px solid #5C2323;color:#5C2323;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none">Xem tất cả mã giảm giá &rarr;</a>
        </div>

        <p id="notif-empty-filter" style="display:none;text-align:center;padding:60px 0;color:#6B6B66;font-size:14px">Không có thông báo nào thuộc mục này.</p>
    @else
        <div style="text-align:center;padding:80px 0;border:1px dashed #E5E2DC;border-radius:16px">
            <div style="font-size:40px;margin-bottom:16px">🔔</div>
            <p style="font-size:15px;color:#6B6B66;margin:0 0 24px">Chưa có thông báo nào.</p>
            <a href="{{ route('shop.index') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none">Về trang chủ</a>
        </div>
    @endif
</section>

@push('scripts')
<script>
    // Lọc thông báo theo loại hoàn toàn phía client — không gọi lại server.
    function filterNotifications(type, btn) {
        document.querySelectorAll('.notif-filter-tab').forEach(function (tab) {
            const active = tab === btn;
            tab.style.background = active ? '#5C2323' : '#FFFFFF';
            tab.style.color = active ? '#FFFFFF' : '#1C1C1A';
            tab.style.borderColor = active ? '#5C2323' : '#E5E2DC';
        });

        let visibleCount = 0;
        document.querySelectorAll('.notif-card').forEach(function (card) {
            const show = type === 'all' || card.dataset.type === type;
            card.style.display = show ? 'flex' : 'none';
            if (show) visibleCount++;
        });

        const emptyMsg = document.getElementById('notif-empty-filter');
        if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
</script>
@endpush
@endsection
