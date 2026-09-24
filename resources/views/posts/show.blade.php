@extends('layouts.shop')

@section('title', $post->title . ' · Cây Cảnh Shop')

@push('scripts')
<style>
    .page-content h2,.page-content h3{font-family:'Anton',sans-serif;text-transform:uppercase;letter-spacing:0.01em;color:#1C1C1A;margin:28px 0 12px}
    .page-content h2{font-size:22px}
    .page-content h3{font-size:18px}
    .page-content p{margin:0 0 16px}
    .page-content ul,.page-content ol{margin:0 0 16px;padding-left:22px}
    .page-content li{margin-bottom:8px}
    .page-content a{color:#5C2323;text-decoration:underline}
    .page-content img{max-width:100%;border-radius:12px;margin:16px 0}
</style>
@endpush

@section('content')

<nav aria-label="breadcrumb" style="max-width:900px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <a href="{{ route('posts.index') }}" style="color:#8A8680">Cẩm nang</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">{{ $post->title }}</span>
</nav>

<section style="max-width:900px;margin:0 auto;padding:20px 24px 0">
    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 14px">
        {{ optional($post->published_at)->format('d/m/Y H:i') }}
    </p>
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,4.5vw,46px);line-height:1.15;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">{{ $post->title }}</h1>
</section>

<section style="max-width:900px;margin:0 auto;padding:24px 24px clamp(64px,8vw,96px)">
    <div class="page-content" style="font-size:15px;line-height:1.8;color:#2B2B28">
        {!! $post->content !!}
    </div>

    <a href="{{ route('posts.index') }}" style="display:inline-flex;align-items:center;gap:6px;margin-top:32px;font-size:14px;color:#5C2323;font-weight:600;text-decoration:none">
        ← Quay lại Cẩm nang
    </a>
</section>

@endsection
