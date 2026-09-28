<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đã nhận chuyển khoản — Đơn hàng #{{ $order->id }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, Helvetica, sans-serif; color:#333333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e0e0e0;">
                    <tr>
                        <td style="background-color:#5C2323; padding:20px 32px;">
                            <h1 style="margin:0; font-size:20px; color:#ffffff;">Cây Cảnh Shop</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="font-size:16px; margin:0 0 12px;">
                                Xin chào <strong>{{ $order->name }}</strong>,
                            </p>
                            <p style="font-size:14px; line-height:1.6; margin:0 0 16px;">
                                Chúng tôi đã nhận được khoản chuyển khoản cho đơn hàng <strong>#{{ $order->id }}</strong>. Đơn hàng của bạn sẽ được chuẩn bị và giao trong thời gian sớm nhất.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Mã đơn hàng:</td>
                                    <td style="padding:4px 0; text-align:right; font-weight:bold;">#{{ $order->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0; color:#666666;">Nội dung chuyển khoản:</td>
                                    <td style="padding:4px 0; text-align:right;">{{ $order->transferContent() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; font-size:16px; font-weight:bold;">Số tiền đã nhận:</td>
                                    <td style="padding:8px 0; text-align:right; font-size:16px; font-weight:bold; color:#4A6B1F;">{{ number_format($order->total_price, 0, ',', '.') }}đ</td>
                                </tr>
                            </table>

                            <p style="font-size:14px; line-height:1.6; margin:0 0 8px;">
                                Bạn có thể xem chi tiết đơn hàng tại đường dẫn dưới đây (yêu cầu đăng nhập tài khoản của bạn):
                            </p>
                            <p style="font-size:14px; margin:0 0 24px; word-break:break-all;">
                                <a href="{{ route('orders.show', $order) }}" style="color:#5C2323;">{{ route('orders.show', $order) }}</a>
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
