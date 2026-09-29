@extends('layouts.shop')

@section('title', 'Đơn mua · Cây Cảnh Shop')
@section('content')
<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 20px 0;font-size:13px;color:#8A8680">
    <a href="{{ route('shop.index') }}">Trang chủ</a> <span aria-hidden="true">›</span> <span style="color:#1C1C1A">Đơn mua</span>
</nav>
<section style="max-width:1000px;margin:0 auto;padding:24px 20px 80px;min-width:0">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;text-transform:uppercase;margin:0 0 8px">Đơn mua</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;color:#8A8680;margin:0 0 24px">Theo dõi mọi đơn hàng của bạn tại đây.</p>

    <nav aria-label="Lọc đơn mua" style="display:flex;gap:24px;overflow-x:auto;max-width:100%;border-bottom:1px solid #E5E2DC;margin-bottom:24px;scrollbar-width:thin">
        @foreach(\App\Models\Order::TABS as $tabKey => $tabLabel)
            <a href="{{ route('orders.history', ['tab' => $tabKey]) }}" aria-current="{{ $tab === $tabKey ? 'page' : 'false' }}" style="display:inline-flex;align-items:center;gap:6px;flex:none;white-space:nowrap;padding:10px 0;border-bottom:3px solid {{ $tab === $tabKey ? '#5C2323' : 'transparent' }};font-family:'Space Mono',monospace;font-size:11px;color:{{ $tab === $tabKey ? '#5C2323' : '#8A8680' }};text-transform:uppercase">
                {{ $tabLabel }} <span style="border-radius:999px;background:#F7F5F0;padding:2px 7px;color:#1C1C1A">{{ $tabCounts[$tabKey] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div style="display:flex;flex-direction:column;gap:16px">
        @forelse($orders as $order)
            @php $firstItem = $order->items->first(); @endphp
            <article style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:clamp(16px,4vw,24px);min-width:0">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;border-bottom:1px solid #E5E2DC;padding-bottom:14px">
                    <div>
                        <a href="{{ route('orders.show', $order) }}" style="font-family:'Anton',sans-serif;font-size:20px">Đơn hàng #{{ $order->id }}</a>
                        <div style="font-family:'Space Mono',monospace;font-size:11px;color:#8A8680;margin-top:3px">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <span style="border-radius:999px;padding:6px 12px;background:#F7F5F0;color:#4A6B1F;font-family:'Space Mono',monospace;font-size:11px;text-transform:uppercase">{{ $order->customer_stage_label }}</span>
                </div>
                @if($firstItem)
                    <div style="display:flex;gap:14px;padding:18px 0;min-width:0">
                        <a href="{{ $firstItem->product ? route('shop.show', $firstItem->product) : route('orders.show', $order) }}" style="flex:none;width:76px;height:76px;border-radius:12px;overflow:hidden;background:#F7F5F0">
                            @if($firstItem->product?->main_image)
                                <img src="{{ asset('storage/' . $firstItem->product->main_image) }}" alt="{{ $firstItem->product_name ?: $firstItem->product->name }}" style="width:100%;height:100%;object-fit:cover">
                            @else
                                <span class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#8A8680"><i data-lucide="sprout" style="width:24px;height:24px"></i></span>
                            @endif
                        </a>
                        <div style="min-width:0;overflow-wrap:anywhere">
                            <a href="{{ $firstItem->product ? route('shop.show', $firstItem->product) : route('orders.show', $order) }}" style="font-size:15px;font-weight:600">{{ $firstItem->product_name ?: ($firstItem->product?->name ?? 'Sản phẩm đã xoá') }}</a>
                            @if($firstItem->variant_name)<div style="font-size:12px;color:#8A8680;margin-top:5px">Phân loại: {{ $firstItem->variant_name }}</div>@endif
                            <div style="font-size:12px;color:#8A8680;margin-top:5px">Số lượng: {{ $firstItem->quantity }}</div>
                            @if($order->items->count() > 1)<div style="font-size:12px;color:#8A8680;margin-top:5px">và {{ $order->items->count() - 1 }} sản phẩm khác</div>@endif
                        </div>
                    </div>
                @endif
                <div style="display:flex;justify-content:flex-end;gap:8px;align-items:baseline;border-top:1px solid #E5E2DC;padding-top:14px;margin-bottom:14px">
                    <span style="font-size:13px;color:#8A8680">Tổng tiền</span><strong style="font-size:18px;color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</strong>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap">
                    @if($order->canRetryMomo())
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'atm']) }}" style="padding:9px 13px;border-radius:999px;background:#D82D8B;color:#FFFFFF;font-size:12px">🏧 Nội địa</a>
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'cc']) }}" style="padding:9px 13px;border-radius:999px;border:1px solid #F4A8CF;color:#D82D8B;font-size:12px">🌍 Quốc tế</a>
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'wallet']) }}" style="padding:9px 13px;border-radius:999px;border:1px solid #F4A8CF;color:#D82D8B;font-size:12px">📱 Ví MoMo</a>
                    @endif
                    @if($order->canCustomerCancel())<a href="{{ route('orders.show', $order) }}#cancel-order" style="padding:9px 14px;border:1px solid #5C2323;border-radius:999px;color:#5C2323;font-size:12px">Huỷ đơn</a>@endif
                    <a href="{{ route('orders.show', $order) }}#tracking" style="padding:9px 14px;border:1px solid #E5E2DC;border-radius:999px;font-size:12px">Theo dõi</a>
                    @if($order->canConfirmReceived())<button type="button" class="js-confirm-received" data-form="confirm-received-{{ $order->id }}" style="padding:9px 14px;background:#4A6B1F;color:#FFFFFF;border:0;border-radius:999px;font-size:12px;cursor:pointer">Đã nhận được hàng</button><form id="confirm-received-{{ $order->id }}" method="POST" action="{{ route('orders.confirmReceived', $order) }}" style="display:none">@csrf</form>@endif
                    @if($order->canReview() && $firstItem?->product)<a href="{{ route('shop.show', $firstItem->product) }}" style="padding:9px 14px;border:1px solid #E5E2DC;border-radius:999px;font-size:12px">Đánh giá</a>@endif
                    @if(in_array($order->customerStage(), ['delivered', 'completed']) && $firstItem?->product)<a href="{{ route('shop.show', $firstItem->product) }}" style="padding:9px 14px;background:#5C2323;color:#FFFFFF;border-radius:999px;font-size:12px">Mua lại</a>@endif
                </div>
            </article>
        @empty
            <div style="text-align:center;padding:64px 20px;border:1px dashed #E5E2DC;border-radius:16px">
                <i data-lucide="package-open" style="width:36px;height:36px;color:#8A8680;margin:auto"></i>
                <p style="color:#8A8680;font-size:14px">{{ $tab === 'tat-ca' ? 'Bạn chưa có đơn hàng nào.' : 'Chưa có đơn hàng ở trạng thái này.' }}</p>
                <a href="{{ $tab === 'tat-ca' ? route('shop.index') : route('orders.history') }}" style="display:inline-block;padding:12px 22px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:11px">{{ $tab === 'tat-ca' ? 'Bắt đầu mua sắm' : 'Xem tất cả đơn' }}</a>
            </div>
        @endforelse
    </div>
    @if($orders->hasPages())<div style="margin-top:32px">{{ $orders->appends(['tab' => $tab])->links() }}</div>@endif
</section>
<div id="receivedModal" role="dialog" aria-modal="true" aria-labelledby="receivedModalTitle" style="display:none;position:fixed;inset:0;z-index:var(--z-modal,400);background:rgba(28,28,26,.5);align-items:center;justify-content:center;padding:20px">
    <div style="width:100%;max-width:380px;background:#FFFFFF;border-radius:16px;padding:24px">
        <h2 id="receivedModalTitle" style="font-family:'Anton',sans-serif;font-size:21px;margin:0 0 10px">Xác nhận đã nhận hàng?</h2>
        <p style="font-size:14px;color:#8A8680;margin:0 0 20px">Hãy xác nhận sau khi bạn đã kiểm tra sản phẩm.</p>
        <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap"><button type="button" class="js-received-close" style="padding:10px 16px;border:1px solid #E5E2DC;border-radius:999px;background:#FFFFFF">Đóng</button><button type="button" id="receivedModalSubmit" style="padding:10px 16px;border:0;border-radius:999px;background:#4A6B1F;color:#FFFFFF">Xác nhận</button></div>
    </div>
</div>
@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('receivedModal');
        let selectedForm = null;
        document.querySelectorAll('.js-confirm-received').forEach(function (button) {
            button.addEventListener('click', function () { selectedForm = document.getElementById(button.dataset.form); modal.style.display = 'flex'; });
        });
        function closeModal() { modal.style.display = 'none'; selectedForm = null; }
        modal.querySelector('.js-received-close').addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeModal(); });
        document.getElementById('receivedModalSubmit').addEventListener('click', function () { if (selectedForm) selectedForm.submit(); });
    })();
</script>
@endpush
@endsection
