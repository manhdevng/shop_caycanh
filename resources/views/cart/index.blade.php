@extends('layouts.shop')
@section('content')

@php $total = 0; @endphp

<section style="max-width:1100px;margin:0 auto;padding:clamp(40px,6vw,56px) 24px clamp(64px,8vw,96px)">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,3.4vw,40px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 36px">Giỏ hàng của bạn</h1>

    @if(count($cart) > 0)
        <form method="GET" action="{{ route('checkout') }}">
            <div style="display:grid;grid-template-columns:24px minmax(0,2.4fr) 1fr 0.8fr 0.9fr 0.9fr 32px;gap:16px;align-items:center;padding-bottom:14px;border-bottom:1px solid #E5E2DC" class="hidden md:grid">
                <input type="checkbox" id="check-all" checked>
                <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680">Sản phẩm</span>
                <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680">Danh mục</span>
                <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680">Số lượng</span>
                <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;text-align:right">Đơn giá</span>
                <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;text-align:right">Thành tiền</span>
                <span></span>
            </div>

            @foreach($cart as $id => $item)
                @php $lineTotal = $item['price'] * $item['quantity']; $total += $lineTotal; @endphp
                <div style="display:grid;grid-template-columns:24px minmax(0,2.4fr) 1fr 0.8fr 0.9fr 0.9fr 32px;gap:16px;align-items:center;padding:18px 0;border-bottom:1px solid #E5E2DC" class="cart-row">
                    <input type="checkbox" name="items[]" value="{{ $id }}" checked class="cart-item-check" data-line-total="{{ $lineTotal }}">
                    <div style="display:flex;align-items:center;gap:14px;min-width:0">
                        <div class="placeholder-pattern" style="width:64px;height:64px;flex:0 0 auto;display:flex;align-items:center;justify-content:center;border-radius:4px;overflow:hidden">
                            @if(!empty($item['image']))
                                <img src="{{ asset('storage/' . $item['image']) }}" style="width:100%;height:100%;object-fit:cover">
                            @else
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:8px;color:#A8A196">ẢNH</span>
                            @endif
                        </div>
                        <span style="font-size:14.5px;color:#1C1C1A;min-width:0">{{ $item['name'] }}</span>
                    </div>
                    <span style="font-size:13px;color:#8A8680">{{ $item['category'] ?? '—' }}</span>
                    <div style="display:flex;align-items:center;gap:6px">
                        <input type="number" id="qty-input-{{ $id }}" value="{{ $item['quantity'] }}" min="1" @if(!empty($item['stock'])) max="{{ $item['stock'] }}" @endif style="width:56px;padding:6px 8px;border:1px solid #E5E2DC;border-radius:4px;font-size:13px;color:#1C1C1A">
                        <button type="button" onclick="updateCartQuantity('{{ $id }}')" style="padding:6px 10px;border:1px solid #5C2323;border-radius:999px;background:none;color:#5C2323;font-family:'Space Mono',monospace;font-size:10px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;white-space:nowrap">Cập nhật</button>
                    </div>
                    <span style="font-size:14px;color:#1C1C1A;text-align:right;white-space:nowrap">{{ number_format($item['price'], 0, ',', '.') }}&#8363;</span>
                    <span style="font-size:14.5px;font-weight:700;color:#1C1C1A;text-align:right;white-space:nowrap">{{ number_format($lineTotal, 0, ',', '.') }}&#8363;</span>
                    <button type="button" onclick="removeFromCart('{{ $id }}')" aria-label="Xoá" style="width:32px;height:32px;border:none;background:none;cursor:pointer;color:#8A8680;font-size:15px">🗑</button>
                </div>
            @endforeach

            @include('partials.voucher-list', ['availableVouchers' => $availableVouchers, 'savableVouchers' => $savableVouchers ?? collect(), 'subtotal' => $total])

            <div style="display:flex;justify-content:flex-end;margin-top:32px">
                <div style="width:100%;max-width:320px;text-align:right">
                    <div style="display:flex;justify-content:space-between;margin-bottom:20px">
                        <span style="font-size:15px;color:#1C1C1A">Tạm tính</span>
                        <span id="cart-subtotal" style="font-size:18px;font-weight:700;color:#1C1C1A">{{ number_format($total, 0, ',', '.') }}&#8363;</span>
                    </div>
                    <button type="submit" style="width:100%;padding:16px 20px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:13px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Tiến hành thanh toán</button>
                    <a href="{{ route('shop.index') }}" style="display:block;margin-top:14px;font-size:13px;color:#6B6B66">&larr; Tiếp tục mua sắm</a>
                </div>
            </div>
        </form>

        <script>
            function recalcSubtotal() {
                let total = 0;
                document.querySelectorAll('.cart-item-check').forEach(cb => {
                    if (cb.checked) {
                        total += Number(cb.dataset.lineTotal) || 0;
                    }
                });

                const formatted = Math.round(total).toLocaleString('vi-VN');
                document.getElementById('cart-subtotal').textContent = formatted + '₫';
            }

            document.getElementById('check-all').addEventListener('change', function () {
                document.querySelectorAll('.cart-item-check').forEach(cb => cb.checked = this.checked);
                recalcSubtotal();
            });

            document.querySelectorAll('.cart-item-check').forEach(cb => {
                cb.addEventListener('change', recalcSubtotal);
            });

            document.querySelector('form').addEventListener('submit', function (event) {
                const anyChecked = Array.from(document.querySelectorAll('.cart-item-check')).some(cb => cb.checked);

                if (!anyChecked) {
                    event.preventDefault();
                    showToast('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.', true);
                }
            });

            recalcSubtotal();

            function removeFromCart(id) {
                if (!confirm('Xoá sản phẩm này khỏi giỏ hàng?')) return;

                fetch(`/cart/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                }).then(() => location.reload());
            }

            function updateCartQuantity(id) {
                const input = document.getElementById(`qty-input-${id}`);
                const quantity = parseInt(input.value, 10);

                if (!quantity || quantity < 1) {
                    showToast('Số lượng không hợp lệ.', true);
                    return;
                }

                fetch(`/cart/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ quantity: quantity }),
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || 'Không thể cập nhật số lượng.');
                        }
                        return data;
                    })
                    .then(() => {
                        location.reload();
                    })
                    .catch((error) => {
                        showToast(error.message || 'Không thể cập nhật số lượng.', true);
                    });
            }
        </script>
    @else
        <div style="text-align:center;padding:80px 0">
            <div style="font-size:40px;margin-bottom:16px">🌿</div>
            <p style="font-size:15px;color:#6B6B66;margin:0 0 24px">Giỏ hàng của bạn đang trống.</p>
            <a href="{{ route('shop.index') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Tiếp tục mua sắm</a>
        </div>
    @endif
</section>
@endsection
