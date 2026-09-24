@extends('layouts.shop')
@section('title', 'Đăng nhập')
@section('content')

<section style="position:relative;min-height:calc(100vh - 76px);display:flex;align-items:center;justify-content:center;padding:48px 24px;overflow:hidden">
    <img src="{{ asset('images/auth-hero.jpg') }}" alt="" style="position:fixed;inset:0;width:100%;height:100%;object-fit:cover;z-index:0">
    <div style="position:fixed;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0.08) 0%, rgba(0,0,0,0.28) 100%);z-index:1"></div>

    <div class="auth-glass-card" style="position:relative;z-index:2;width:100%;max-width:440px;background:rgba(255,255,255,0.14);backdrop-filter:blur(22px) saturate(140%);-webkit-backdrop-filter:blur(22px) saturate(140%);border:1px solid rgba(255,255,255,0.38);border-radius:20px;padding:clamp(32px,5vw,48px);box-shadow:0 8px 32px rgba(0,0,0,0.28)">
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(26px,3.4vw,38px);letter-spacing:0.01em;text-transform:uppercase;color:#FFFFFF;margin:0 0 28px;text-align:center;text-shadow:0 2px 14px rgba(0,0,0,0.3)">Đăng nhập</h1>

        @if (session('success'))
            <div style="background:rgba(238,243,234,0.85);backdrop-filter:blur(8px);border:1px solid rgba(201,216,192,0.85);color:#3F5B45;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="background:rgba(251,234,234,0.85);backdrop-filter:blur(8px);border:1px solid rgba(231,198,198,0.85);color:#B3261E;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:20px">{{ session('error') }}</div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div style="margin-bottom:18px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <div style="margin-bottom:24px">
                <label style="display:block;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:8px">Mật khẩu</label>
                <input type="password" name="password" required class="glass-input" style="width:100%;border:1px solid rgba(255,255,255,0.4);border-radius:12px;padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;background:rgba(255,255,255,0.1);color:#FFFFFF;outline:none">
            </div>
            <button type="submit" style="display:block;width:100%;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:14px;border:none;border-radius:999px;cursor:pointer;box-shadow:0 6px 18px rgba(92,35,35,0.45)">Đăng nhập</button>
        </form>

        <div style="display:flex;align-items:center;gap:14px;margin:24px 0">
            <span style="flex:1 1 auto;height:1px;background:rgba(255,255,255,0.35)"></span>
            <span style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:rgba(255,255,255,0.75)">hoặc</span>
            <span style="flex:1 1 auto;height:1px;background:rgba(255,255,255,0.35)"></span>
        </div>

        <a href="{{ route('social.google.redirect') }}" style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;box-sizing:border-box;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.04em;background:rgba(255,255,255,0.08);color:#FFFFFF;padding:13px;border:1px solid rgba(255,255,255,0.55);border-radius:999px;text-decoration:none">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
            </svg>
            Đăng nhập bằng Google
        </a>

        <p style="font-size:14px;color:rgba(255,255,255,0.85);margin:24px 0 0;text-align:center">
            Chưa có tài khoản? <a href="{{ route('register') }}" style="color:#FFFFFF;font-weight:600;text-decoration:underline">Đăng ký</a>
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
