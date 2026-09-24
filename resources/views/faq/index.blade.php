@extends('layouts.shop')
@section('content')

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Câu hỏi thường gặp</span>
</nav>

<section style="max-width:900px;margin:0 auto;padding:20px 24px 0">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,48px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Câu hỏi thường gặp</h1>
    <p style="font-size:16px;line-height:1.6;color:#6B6B66;max-width:640px;margin:0 0 40px">Tổng hợp các câu hỏi khách hàng thường thắc mắc về cây cảnh, chăm sóc, vận chuyển và bảo hành. Nếu chưa tìm thấy câu trả lời phù hợp, đừng ngần ngại liên hệ với chúng tôi.</p>
</section>

<section style="max-width:900px;margin:0 auto;padding:0 24px clamp(64px,8vw,100px)">
    @if($faqs->isNotEmpty())
        <div style="border-top:1px solid #E5E2DC">
            @foreach($faqs as $faq)
                <details style="border-bottom:1px solid #E5E2DC">
                    <summary style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 0;cursor:pointer;font-size:15px;font-weight:500;color:#1C1C1A;list-style:none">{{ $faq->question }}</summary>
                    <p style="font-size:14px;line-height:1.7;color:#6B6B66;margin:0 0 20px;max-width:640px">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>
    @else
        <div style="border:1px dashed #E5E2DC;border-radius:16px;padding:56px 24px;text-align:center">
            <p style="font-size:15px;color:#6B6B66;margin:0 0 20px">Hiện chưa có câu hỏi thường gặp nào được đăng tải.</p>
            <a href="{{ route('shop.index') }}" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:14px 30px;border-radius:999px">Về trang chủ</a>
        </div>
    @endif

    <div style="margin-top:48px;padding:28px 32px;background:#F7F4EF;border-radius:16px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
        <p style="font-size:14px;color:#6B6B66;margin:0">Bạn vẫn còn thắc mắc? Đội ngũ hỗ trợ luôn sẵn sàng giúp bạn.</p>
        <a href="{{ route('tickets.index') }}" style="display:inline-block;flex:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;background:#1C1C1A;color:#FFFFFF;padding:14px 28px;border-radius:999px;white-space:nowrap">Liên hệ hỗ trợ &rarr;</a>
    </div>
</section>

@endsection
