<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Xác nhận đơn hàng #{{ $order->id }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif; color:#333333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e0e0e0;">
                    <tr>
                        <td style="background-color:#2f7d32; padding:20px 32px;">
                            <h1 style="margin:0; font-size:20px; color:#ffffff;">Cây Cảnh Shop</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="font-size:16px; margin:0 0 12px;">
                                Xin chào <strong>{{ $order->name }}</strong>,
                            </p>
                            <p style="font-size:14px; line-height:1.6; margin:0 0 16px;">
                                Cảm ơn bạn đã đặt hàng tại Cây Cảnh Shop. Đơn hàng của bạn đã được ghi nhận thành công với thông tin chi tiết như sau:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; margin-bottom:16px;">
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Mã đơn hàng:</td>
                                    <td style="padding:4px 0; text-align:right; font-weight:bold;">#{{ $order->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Ngày đặt hàng:</td>
                                    <td style="padding:4px 0; text-align:right;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Trạng thái:</td>
                                    <td style="padding:4px 0; text-align:right;">{{ $order->status_label }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Phương thức thanh toán:</td>
                                    <td style="padding:4px 0; text-align:right;">
                                        @if(optional($order->latestPaymentTransaction)->gateway === 'momo')
                                            Ví MoMo
                                        @else
                                            Thanh toán khi nhận hàng (COD)
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <h3 style="font-size:15px; margin:0 0 8px; border-bottom:1px solid #e0e0e0; padding-bottom:8px;">Chi tiết sản phẩm</h3>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; border-collapse:collapse; margin-bottom:16px;">
                                <thead>
                                    <tr style="background-color:#f0f0f0;">
                                        <td style="padding:8px; text-align:left;">Sản phẩm</td>
                                        <td style="padding:8px; text-align:center;">SL</td>
                                        <td style="padding:8px; text-align:right;">Đơn giá</td>
                                        <td style="padding:8px; text-align:right;">Thành tiền</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr style="border-bottom:1px solid #f0f0f0;">
                                            <td style="padding:8px;">
                                                {{ $item->product_name }}
                                                @if($item->variant_name)
                                                    <br><span style="color:#888888; font-size:12px;">Phân loại: {{ $item->variant_name }}</span>
                                                @endif
                                            </td>
                                            <td style="padding:8px; text-align:center;">{{ $item->quantity }}</td>
                                            <td style="padding:8px; text-align:right;">{{ number_format($item->price, 0, ',', '.') }}đ</td>
                                            <td style="padding:8px; text-align:right;">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; margin-bottom:24px;">
                                @if($order->discount_amount > 0)
                                    <tr>
                                        <td style="padding:4px 0; color:#666666;">Giảm giá:</td>
                                        <td style="padding:4px 0; text-align:right; color:#c0392b;">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:8px 0; font-size:16px; font-weight:bold;">Tổng cộng:</td>
                                    <td style="padding:8px 0; text-align:right; font-size:16px; font-weight:bold; color:#2f7d32;">{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                                </tr>
                            </table>

                            <h3 style="font-size:15px; margin:0 0 8px; border-bottom:1px solid #e0e0e0; padding-bottom:8px;">Địa chỉ giao hàng</h3>
                            <p style="font-size:14px; line-height:1.6; margin:0 0 24px;">
                                {{ $order->name }}<br>
                                {{ $order->phone }}<br>
                                {{ $order->address }}
                            </p>

                            <p style="font-size:14px; line-height:1.6; margin:0 0 8px;">
                                Bạn có thể xem chi tiết đơn hàng tại đường dẫn dưới đây (yêu cầu đăng nhập tài khoản của bạn):
                            </p>
                            <p style="font-size:14px; margin:0 0 24px; word-break:break-all;">
                                <a href="{{ route('orders.show', $order) }}" style="color:#2f7d32;">{{ route('orders.show', $order) }}</a>
                            </p>

                            <p style="font-size:13px; color:#888888; margin:0;">
                                Cảm ơn bạn đã tin tưởng và ủng hộ Cây Cảnh Shop. Nếu có thắc mắc, vui lòng liên hệ với chúng tôi.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f0f0f0; padding:16px 32px; font-size:12px; color:#999999; text-align:center;">
                            © {{ date('Y') }} Cây Cảnh Shop. Email này được gửi tự động, vui lòng không trả lời trực tiếp.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
