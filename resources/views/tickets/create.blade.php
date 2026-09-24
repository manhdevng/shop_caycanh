@extends('layouts.shop')

@section('title', 'Tạo yêu cầu hỗ trợ · Cây Cảnh Shop')

@section('content')

<section style="max-width:700px;margin:0 auto;padding:32px 24px 96px">
    <nav aria-label="breadcrumb" style="font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
        <span>&rsaquo;</span>
        <a href="{{ route('tickets.index') }}" style="color:#8A8680">Hỗ trợ của tôi</a>
        <span>&rsaquo;</span>
        <span style="color:#1C1C1A">Tạo yêu cầu mới</span>
    </nav>

    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(26px,4vw,38px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 24px">Tạo yêu cầu hỗ trợ mới</h1>

    @if($errors->any())
        <div style="background:#FBEAEA;border:1px solid #E7C6C6;color:#B3261E;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px">
            <strong>Có lỗi xảy ra:</strong>
            <ul style="margin:8px 0 0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tickets.store') }}" method="POST" style="background:#FFFFFF;border:1px solid #E5E2DC;border-radius:20px;padding:28px">
        @csrf

        <div style="margin-bottom:20px">
            <label for="subject" style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin-bottom:8px">Chủ đề <span style="color:#B3261E">*</span></label>
            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" placeholder="VD: Đơn hàng chưa giao đến" required
                   style="width:100%;box-sizing:border-box;padding:12px 16px;border:1px solid #E5E2DC;border-radius:10px;font-size:14px;font-family:inherit;outline:none">
            @error('subject')
                <p style="margin:6px 0 0;font-size:12px;color:#B3261E">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:24px">
            <label for="message" style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin-bottom:8px">Nội dung <span style="color:#B3261E">*</span></label>
            <textarea id="message" name="message" rows="7" placeholder="Mô tả chi tiết vấn đề của bạn..." required
                      style="width:100%;box-sizing:border-box;padding:12px 16px;border:1px solid #E5E2DC;border-radius:10px;font-size:14px;font-family:inherit;outline:none;resize:vertical">{{ old('message') }}</textarea>
            @error('message')
                <p style="margin:6px 0 0;font-size:12px;color:#B3261E">{{ $message }}</p>
            @enderror
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px">
            <a href="{{ route('tickets.index') }}" style="padding:12px 24px;border-radius:999px;border:1px solid #E5E2DC;color:#1C1C1A;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase">Hủy bỏ</a>
            <button type="submit" style="padding:12px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer">Gửi yêu cầu</button>
        </div>
    </form>
</section>

@endsection
