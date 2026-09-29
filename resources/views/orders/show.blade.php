@extends('layouts.shop')
@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <a href="{{ route('orders.history') }}" style="color:#8A8680">Đơn mua</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">#{{ $order->id }}</span>
</nav>

<section style="max-width:800px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,4vw,44px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Đơn hàng #{{ $order->id }}</h1>
</section>

<section style="max-width:800px;margin:0 auto;padding:0 20px clamp(64px,8vw,96px);min-width:0">
    <div style="background:#F7F5F0;border:1px solid #E5E2DC;border-radius:16px;padding:clamp(18px,4vw,28px);margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div>
            <p style="font-family:'Space Mono',monospace;font-size:11px;color:#8A8680;text-transform:uppercase;margin:0 0 8px">Trạng thái đơn hàng</p>
            <h2 style="font-family:'Anton',sans-serif;font-size:clamp(24px,5vw,34px);line-height:1.15;text-transform:uppercase;margin:0;color:#1C1C1A">{{ $order->customer_stage_label }}</h2>
            @if($order->ghn_expected_delivery_at && in_array($order->customerStage(), ['to_ship', 'shipping']))
                <p style="font-size:14px;color:#4A6B1F;margin:8px 0 0">Dự kiến giao {{ $order->ghn_expected_delivery_at->format('d/m/Y') }}</p>
            @endif
        </div>
        @if($order->canConfirmReceived())
            <button type="button" id="receivedOrderBtn" style="background:#4A6B1F;color:#FFFFFF;border:0;border-radius:999px;padding:12px 18px;font-size:13px;cursor:pointer">Đã nhận được hàng</button>
        @endif
    </div>
    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:24px 28px;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#EFEDE7;color:#1C1C1A">Thanh toán: {{ $order->status_label }}</span>
            <span style="display:inline-block;padding:5px 12px;border-radius:999px;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;background:#FFFFFF;border:1px solid #E5E2DC;color:#6B6B66">Vận chuyển: {{ $order->shipping_label }}</span>
        </div>

        @php
            $stage = $order->customerStage();
            $stepIndex = match ($stage) {
                'awaiting_payment' => 1,
                'to_ship' => 2,
                'shipping' => 4,
                'delivered', 'completed' => 5,
                default => 1,
            };
            $placedEvent = $timeline->first(fn ($event) => data_get($event, 'title') === 'Đơn hàng đã được đặt');
            $paidEvent = $timeline->first(fn ($event) => in_array(data_get($event, 'title'), ['Đã thanh toán', 'Đã thanh toán MoMo']));
            $pickedEvent = $timeline->first(fn ($event) => data_get($event, 'title') === 'Đã lấy hàng');
            $deliveringEvent = $timeline->first(fn ($event) => data_get($event, 'title') === 'Đang giao hàng');
            $deliveredEvent = $timeline->first(fn ($event) => data_get($event, 'title') === 'Giao hàng thành công');
            $completedEvent = $timeline->first(fn ($event) => data_get($event, 'title') === 'Đơn hàng hoàn thành');
            $steps = [
                ['label' => 'Đặt hàng', 'time' => data_get($placedEvent, 'time') ?? $order->created_at],
                ['label' => 'Xác nhận thanh toán', 'time' => data_get($paidEvent, 'time') ?? ($order->payment_method === 'cod' ? $order->created_at : null)],
                ['label' => 'Giao cho GHN', 'time' => data_get($pickedEvent, 'time')],
                ['label' => 'Đang giao', 'time' => data_get($deliveringEvent, 'time')],
                ['label' => $stage === 'completed' ? 'Hoàn thành' : 'Đã giao', 'time' => $stage === 'completed' ? data_get($completedEvent, 'time') : data_get($deliveredEvent, 'time')],
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:4px;margin:0 0 22px;min-width:0" aria-label="Tiến độ đơn hàng">
            @foreach($steps as $index => $step)
                <div style="min-width:0;text-align:center">
                    <span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;margin:0 auto 8px;border-radius:999px;background:{{ $index < $stepIndex ? '#4A6B1F' : '#E5E2DC' }};color:{{ $index < $stepIndex ? '#FFFFFF' : '#8A8680' }};font-family:'Space Mono',monospace;font-size:12px">{{ $index + 1 }}</span>
                    <span style="display:block;font-size:clamp(10px,2.5vw,12px);line-height:1.35;overflow-wrap:anywhere;color:{{ $index < $stepIndex ? '#4A6B1F' : '#8A8680' }}">{{ $step['label'] }}</span>
                    @if($step['time'])<time style="display:block;font-family:'Space Mono',monospace;font-size:9px;color:#8A8680;margin-top:5px;overflow-wrap:anywhere">{{ $step['time']->format('d/m H:i') }}</time>@endif
                </div>
            @endforeach
        </div>

        @if($order->canRetryMomo())
            <div style="background:#FDF2F8;border:1px solid #F4A8CF;border-radius:12px;padding:16px;margin-bottom:20px">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:10px">
                    <p style="font-size:14px;color:#D82D8B;margin:0">
                        @if($order->lastPaymentFailed())
                            Thanh toán MoMo trước đó không thành công.
                        @else
                            Đơn hàng đang chờ thanh toán qua MoMo.
                        @endif
                    </p>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'atm']) }}" class="hover:opacity-90" style="display:inline-flex;align-items:center;gap:4px;background:#D82D8B;color:#FFFFFF;font-size:13px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none">
                            🏧 Thẻ nội địa
                        </a>
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'cc']) }}" class="hover:bg-pink-50" style="display:inline-flex;align-items:center;gap:4px;background:#FFFFFF;border:1px solid #F4A8CF;color:#D82D8B;font-size:13px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none">
                            🌍 Thẻ quốc tế
                        </a>
                        <a href="{{ route('momo.pay', ['order' => $order, 'type' => 'wallet']) }}" class="hover:bg-pink-50" style="display:inline-flex;align-items:center;gap:4px;background:#FFFFFF;border:1px solid #F4A8CF;color:#D82D8B;font-size:13px;font-weight:600;padding:8px 16px;border-radius:999px;text-decoration:none">
                            📱 Ví MoMo
                        </a>
                    </div>
                </div>
                <form method="POST" action="{{ route('momo.checkStatus', $order) }}">
                    @csrf
                    <button type="submit" class="hover:underline" style="background:none;border:none;padding:0;color:#D82D8B;font-size:12.5px;text-decoration:underline;cursor:pointer;font-family:inherit">Kiểm tra lại trạng thái thanh toán</button>
                </form>
            </div>
        @endif

        @if($order->status === 'awaiting_transfer')
            @php $bankInfo = config('services.bank'); @endphp
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:16px;margin-bottom:20px">
                <p style="font-size:14px;font-weight:700;color:#B45309;margin:0 0 10px">⏳ Chờ chuyển khoản</p>
                <p style="font-size:13px;color:#6B6B66;margin:0 0 10px">Vui lòng chuyển khoản đúng số tiền và ghi rõ mã đơn hàng vào nội dung chuyển khoản. Đơn hàng sẽ được giao sau khi shop xác nhận đã nhận được tiền.</p>
                <div style="font-size:14px;color:#1C1C1A;line-height:1.7">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span>Ngân hàng: <strong>{{ $bankInfo['name'] ?? '—' }}</strong></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span>Số tài khoản: <strong style="font-family:'Space Mono',monospace" id="bankAccountNumber">{{ $bankInfo['account_number'] ?? '—' }}</strong></span>
                        @if($bankInfo['account_number'] ?? null)
                            <button type="button" class="js-copy-text" data-copy-target="bankAccountNumber" style="background:none;border:1px solid #FDE68A;border-radius:999px;padding:2px 10px;font-size:11px;color:#B45309;cursor:pointer;font-family:inherit">Sao chép</button>
                        @endif
                    </div>
                    <div>Chủ tài khoản: <strong>{{ $bankInfo['account_name'] ?? '—' }}</strong></div>
                    <div>Chi nhánh: <strong>{{ $bankInfo['branch'] ?? '—' }}</strong></div>
                    <div>Số tiền cần chuyển: <strong style="color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</strong></div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span>Nội dung chuyển khoản: <strong id="bankTransferContent">{{ $order->transferContent() }}</strong></span>
                        <button type="button" class="js-copy-text" data-copy-target="bankTransferContent" style="background:none;border:1px solid #FDE68A;border-radius:999px;padding:2px 10px;font-size:11px;color:#B45309;cursor:pointer;font-family:inherit">Sao chép</button>
                    </div>
                    @if($order->transfer_ref)
                        <div>Mã tham chiếu bạn đã nhập: <strong>{{ $order->transfer_ref }}</strong></div>
                    @endif
                </div>
            </div>
        @endif

        @if($order->canCustomerCancel())
            <div id="cancel-order" style="margin-bottom:20px">
                <button type="button" id="cancelOrderBtn" style="background:none;border:1px solid #DC2626;color:#DC2626;font-size:13px;font-weight:600;padding:8px 18px;border-radius:999px;cursor:pointer;font-family:inherit">Huỷ đơn hàng</button>
            </div>
        @endif

        <div id="tracking" style="border-top:1px solid #E5E2DC;padding-top:20px;margin-top:12px;scroll-margin-top:100px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px">
                <h3 style="font-family:'Anton',sans-serif;font-size:21px;text-transform:uppercase;margin:0">Thông tin vận chuyển</h3>
                @if($order->ghn_order_code)
                    <form method="POST" action="{{ route('orders.refreshTracking', $order) }}">@csrf<button type="submit" style="border:1px solid #E5E2DC;background:#FFFFFF;border-radius:999px;padding:8px 14px;font-size:12px;cursor:pointer">Cập nhật</button></form>
                @endif
            </div>
            <p style="font-size:13px;color:#8A8680;margin:0 0 8px">Đơn vị vận chuyển: <strong style="color:#1C1C1A">GHN</strong></p>
            @if($order->ghn_order_code)
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;font-size:13px">
                    <span>Mã vận đơn: <strong id="ghnOrderCode" style="font-family:'Space Mono',monospace;overflow-wrap:anywhere">{{ $order->ghn_order_code }}</strong></span>
                    <button type="button" class="js-copy-text" data-copy-target="ghnOrderCode" style="border:1px solid #E5E2DC;background:#FFFFFF;border-radius:999px;padding:5px 10px;font-size:11px;cursor:pointer">Sao chép</button>
                    @if($order->ghnTrackingUrl())<a href="{{ $order->ghnTrackingUrl() }}" target="_blank" rel="noopener noreferrer" style="color:#5C2323;text-decoration:underline">Tra cứu GHN ↗</a>@endif
                </div>
            @else
                <p style="font-size:13px;color:#8A8680">Đơn hàng chưa có mã vận đơn GHN.</p>
            @endif
            <ol style="list-style:none;padding:0;margin:0 0 20px;border-left:1px solid #E5E2DC">
                @forelse($timeline as $event)
                    <li style="position:relative;padding:0 0 18px 20px;margin-left:-5px;min-width:0">
                        <span style="position:absolute;left:0;top:5px;width:9px;height:9px;border-radius:999px;background:{{ $loop->first ? '#4A6B1F' : '#E5E2DC' }}"></span>
                        <strong style="display:block;font-size:13px;color:{{ $loop->first ? '#4A6B1F' : '#1C1C1A' }}">{{ data_get($event, 'title') }}</strong>
                        @if(data_get($event, 'note'))<span style="display:block;font-size:12px;color:#8A8680;overflow-wrap:anywhere;margin-top:3px">{{ data_get($event, 'note') }}</span>@endif
                        <time style="display:block;font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;margin-top:3px">{{ data_get($event, 'time')?->format('d/m/Y H:i') }}</time>
                    </li>
                @empty
                    <li style="padding-left:20px;font-size:13px;color:#8A8680">Chưa có cập nhật hành trình.</li>
                @endforelse
            </ol>
        </div>

        <p style="font-size:14px;color:#6B6B66;margin:0 0 6px"><span style="font-weight:600;color:#1C1C1A">Hình thức thanh toán:</span> {{ $order->payment_method_label }}</p>
        <p style="font-size:14px;color:#6B6B66;margin:0 0 6px"><span style="font-weight:600;color:#1C1C1A">Người nhận:</span> {{ $order->name }} — {{ $order->phone }}</p>
        <p style="font-size:14px;color:#6B6B66;margin:0"><span style="font-weight:600;color:#1C1C1A">Địa chỉ:</span> {{ $order->address }}</p>
    </div>

    <div style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;padding:clamp(16px,4vw,28px)">
        <h3 style="font-family:'Anton',sans-serif;font-size:21px;text-transform:uppercase;margin:0 0 16px">Sản phẩm</h3>
        @foreach($order->items as $item)
            <div style="display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid #E5E2DC;min-width:0">
                <a href="{{ $item->product ? route('shop.show', $item->product) : route('orders.show', $order) }}" style="flex:none;width:64px;height:64px;border-radius:10px;overflow:hidden;background:#F7F5F0">
                    @if($item->product?->main_image)<img src="{{ asset('storage/' . $item->product->main_image) }}" alt="{{ $item->product_name ?: $item->product->name }}" style="width:100%;height:100%;object-fit:cover">@else<span class="placeholder-pattern" style="display:flex;width:100%;height:100%;align-items:center;justify-content:center;color:#8A8680"><i data-lucide="sprout" style="width:22px;height:22px"></i></span>@endif
                </a>
                <div style="flex:1;min-width:0;overflow-wrap:anywhere">
                    <a href="{{ $item->product ? route('shop.show', $item->product) : route('orders.show', $order) }}" style="font-size:14px;font-weight:600">{{ $item->product_name ?: ($item->product?->name ?? 'Sản phẩm đã xoá') }}</a>
                    @if($item->variant_name)<div style="font-size:12px;color:#8A8680;margin-top:3px">Phân loại: {{ $item->variant_name }}</div>@endif
                    <div style="font-size:12px;color:#8A8680;margin-top:3px">Số lượng: {{ $item->quantity }}</div>
                    {{-- R4: trước đây trỏ sang trang sản phẩm (không đánh giá được nhiều
                         món trong 1 đơn) — nay dẫn thẳng tới trang đánh giá theo đơn. --}}
                    @if($order->canReview())<a href="{{ route('reviews.createForOrder', $order) }}" style="display:inline-block;color:#5C2323;font-size:12px;text-decoration:underline;margin-top:5px">Đánh giá</a>@endif
                </div>
                <span style="flex:none;font-size:13px;font-weight:600">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</span>
            </div>
        @endforeach
        <div style="display:grid;gap:10px;padding-top:18px;font-size:14px">
            <div style="display:flex;justify-content:space-between;gap:10px"><span style="color:#8A8680">Tạm tính</span><span>{{ number_format($order->subtotal(), 0, ',', '.') }} đ</span></div>
            <div style="display:flex;justify-content:space-between;gap:10px"><span style="color:#8A8680">Phí vận chuyển</span><span>{{ number_format($order->ghn_total_fee, 0, ',', '.') }} đ</span></div>
            <div style="display:flex;justify-content:space-between;gap:10px"><span style="color:#8A8680">Giảm giá @if($order->voucher)({{ $order->voucher->code }})@endif</span><span style="color:#5C2323">-{{ number_format($order->discount_amount ?? 0, 0, ',', '.') }} đ</span></div>
            <div style="display:flex;justify-content:space-between;gap:10px;border-top:1px solid #E5E2DC;padding-top:12px;font-weight:700;font-size:17px"><span>Tổng cộng</span><span style="color:#4A6B1F">{{ number_format($order->total_price, 0, ',', '.') }} đ</span></div>
        </div>
    </div>
</section>

@if($order->canConfirmReceived())
    <form id="receivedOrderForm" method="POST" action="{{ route('orders.confirmReceived', $order) }}" style="display:none">@csrf</form>
    <div id="receivedOrderModal" role="dialog" aria-modal="true" aria-labelledby="receivedOrderTitle" style="display:none;position:fixed;inset:0;background:rgba(28,28,26,.5);z-index:var(--z-modal,400);align-items:center;justify-content:center;padding:20px">
        <div style="background:#FFFFFF;border-radius:16px;max-width:380px;width:100%;padding:24px">
            <h3 id="receivedOrderTitle" style="font-family:'Anton',sans-serif;font-size:20px;text-transform:uppercase;margin:0 0 10px">Đã nhận được hàng?</h3>
            <p style="font-size:14px;color:#8A8680;margin:0 0 20px">Vui lòng kiểm tra sản phẩm trước khi xác nhận.</p>
            <div style="display:flex;justify-content:flex-end;gap:8px"><button type="button" id="receivedOrderDismiss" style="border:1px solid #E5E2DC;border-radius:999px;background:#FFFFFF;padding:9px 16px">Đóng</button><button type="button" id="receivedOrderConfirm" style="border:0;border-radius:999px;background:#4A6B1F;color:#FFFFFF;padding:9px 16px">Xác nhận</button></div>
        </div>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('receivedOrderModal');
            document.getElementById('receivedOrderBtn').addEventListener('click', function () { modal.style.display = 'flex'; });
            document.getElementById('receivedOrderDismiss').addEventListener('click', function () { modal.style.display = 'none'; });
            modal.addEventListener('click', function (event) { if (event.target === modal) modal.style.display = 'none'; });
            document.addEventListener('keydown', function (event) { if (event.key === 'Escape') modal.style.display = 'none'; });
            document.getElementById('receivedOrderConfirm').addEventListener('click', function () { document.getElementById('receivedOrderForm').submit(); });
        })();
    </script>
@endif

@if($order->canCustomerCancel())
    <form id="cancelOrderForm" method="POST" action="{{ route('orders.cancel', $order) }}" style="display:none">
        @csrf
    </form>
    <div id="cancelOrderModal" style="display:none;position:fixed;inset:0;background:rgba(28,28,26,0.5);z-index:var(--z-modal,400);align-items:center;justify-content:center;padding:20px">
        <div style="background:#FFFFFF;border-radius:16px;max-width:380px;width:100%;padding:24px">
            <h3 style="font-family:'Anton',sans-serif;font-size:18px;text-transform:uppercase;color:#1C1C1A;margin:0 0 10px">Huỷ đơn hàng #{{ $order->id }}?</h3>
            <p style="font-size:14px;color:#6B6B66;margin:0 0 20px">Đơn hàng sẽ được huỷ và không thể khôi phục. Bạn có chắc chắn muốn huỷ?</p>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" id="cancelOrderDismiss" style="background:none;border:1px solid #E5E2DC;color:#6B6B66;font-size:13px;font-weight:600;padding:8px 18px;border-radius:999px;cursor:pointer;font-family:inherit">Đóng</button>
                <button type="button" id="cancelOrderConfirm" style="background:#DC2626;border:none;color:#FFFFFF;font-size:13px;font-weight:600;padding:8px 18px;border-radius:999px;cursor:pointer;font-family:inherit">Huỷ đơn</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const openBtn = document.getElementById('cancelOrderBtn');
            const modal = document.getElementById('cancelOrderModal');
            const dismissBtn = document.getElementById('cancelOrderDismiss');
            const confirmBtn = document.getElementById('cancelOrderConfirm');
            const form = document.getElementById('cancelOrderForm');
            if (!openBtn || !modal) return;

            function openModal() { modal.style.display = 'flex'; }
            function closeModal() { modal.style.display = 'none'; }

            openBtn.addEventListener('click', openModal);
            dismissBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
            confirmBtn.addEventListener('click', function () { form.submit(); });
        })();
    </script>
@endif

<script>
    // Nút "Sao chép" cho thông tin chuyển khoản qua Clipboard API.
    document.querySelectorAll('.js-copy-text').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.copyTarget);
            if (!target) return;
            const text = target.textContent.trim();
            const original = btn.textContent;
            navigator.clipboard.writeText(text).then(function () {
                btn.textContent = 'Đã sao chép';
                setTimeout(function () { btn.textContent = original; }, 1600);
            }).catch(function () {
                showToast('Không thể sao chép. Vui lòng chọn và sao chép thủ công.', true);
            });
        });
    });
</script>
@endsection
