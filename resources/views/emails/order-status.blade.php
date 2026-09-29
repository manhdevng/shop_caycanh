<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }}</title></head>
<body style="margin:0;padding:0;background:#F7F5F0;color:#1C1C1A;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;background:#F7F5F0"><tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:100%;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;overflow:hidden">
            <tr><td style="padding:22px 28px;background:#4A6B1F;color:#FFFFFF;font-size:21px;font-weight:bold">Cây Cảnh Shop</td></tr>
            <tr><td style="padding:28px">
                <p style="font-size:14px;line-height:1.6;margin:0 0 14px">Xin chào {{ $order->name }},</p>
                <h1 style="font-size:22px;line-height:1.3;margin:0 0 12px;color:#1C1C1A">{{ $title }}</h1>
                <p style="font-size:15px;line-height:1.6;color:#6B6B66;margin:0 0 24px">{{ $messageText }}</p>
                <p style="margin:0 0 24px"><a href="{{ $url }}" style="display:inline-block;background:#5C2323;color:#FFFFFF;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:13px;font-weight:bold">Xem đơn hàng</a></p>
                <p style="font-size:12px;color:#8A8680;line-height:1.5;margin:0">Cảm ơn bạn đã mua sắm tại Cây Cảnh Shop.</p>
            </td></tr>
            <tr><td style="padding:16px 28px;background:#F7F5F0;color:#8A8680;text-align:center;font-size:11px">© {{ date('Y') }} Cây Cảnh Shop · Email được gửi tự động</td></tr>
        </table>
    </td></tr></table>
</body>
</html>
