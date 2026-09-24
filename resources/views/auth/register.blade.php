@extends('layouts.shop')
@section('title', 'Đăng ký')
@section('content')

<section style="position:relative;min-height:calc(100vh - 76px);display:flex;align-items:center;justify-content:center;padding:48px 24px;overflow:hidden">
    <img src="{{ asset('images/auth-hero.jpg') }}" alt="" style="position:fixed;inset:0;width:100%;height:100%;object-fit:cover;z-index:0">
    <div style="position:fixed;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0.08) 0%, rgba(0,0,0,0.28) 100%);z-index:1"></div>

    <div class="auth-glass-card" style="position:relative;z-index:2;width:100%;max-width:440px;background:rgba(255,255,255,0.14);backdrop-filter:blur(22px) saturate(140%);-webkit-backdrop-filter:blur(22px) saturate(140%);border:1px solid rgba(255,255,255,0.38);border-radius:20px;padding:clamp(32px,5vw,48px);box-shadow:0 8px 32px rgba(0,0,0,0.28)">
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);letter-spacing:0.01em;text-transform:uppercase;color:#FFFFFF;margin:0 0 28px;text-align:center;text-shadow:0 2px 14px rgba(0,0,0,0.3)">Đăng ký tài khoản</h1>

        @if (session('error'))
            <div style="background:rgba(251,234,234,0.85);backdrop-filter:blur(8px);border:1px solid rgba(231,198,198,0.85);color:#B3261E;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div style="background:rgba(251,234,234,0.85);backdrop-filter:blur(8px);border:1px solid rgba(231,198,198,0.85);color:#B3261E;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div style="margin-bottom:18px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Họ tên</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <div style="margin-bottom:18px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <div style="margin-bottom:18px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Mật khẩu</label>
                <input type="password" name="password" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <div style="margin-bottom:24px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Nhập lại mật khẩu</label>
                <input type="password" name="password_confirmation" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <button type="submit" style="display:block;width:100%;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:14px;border:none;border-radius:999px;cursor:pointer;box-shadow:0 6px 18px rgba(92,35,35,0.45)">Đăng ký</button>
        </form>

        <p style="font-size:14px;color:rgba(255,255,255,0.85);margin:24px 0 0;text-align:center">
            Đã có tài khoản? <a href="{{ route('login') }}" style="color:#FFFFFF;font-weight:600;text-decoration:underline">Đăng nhập</a>
        </p>
    </div>
</section>

<style>
    .glass-input::placeholder { color: rgba(255,255,255,0.55); }
    .glass-input:focus { border-color: rgba(255,255,255,0.75) !important; background: rgba(255,255,255,0.18) !important; }
    .glass-input:-webkit-autofill { -webkit-text-fill-color:#FFFFFF; box-shadow: 0 0 0 1000px rgba(255,255,255,0.1) inset; transition: background-color 9999s ease-in-out 0s; }
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .auth-glass-card { background: rgba(28,28,26,0.72) !important; }
    }
</style>

@endsection
