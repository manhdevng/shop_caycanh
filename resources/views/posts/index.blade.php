@extends('layouts.shop')

@section('title', 'Cẩm nang trồng cây · Cây Cảnh Shop')

@section('content')

<nav aria-label="breadcrumb" style="max-width:1100px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Cẩm nang</span>
</nav>

<section style="max-width:1100px;margin:0 auto;padding:20px 24px 24px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(28px,4.5vw,46px);line-height:1.15;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Cẩm nang trồng cây</h1>
    <p style="font-size:15px;color:#6B6B66;margin:10px 0 0">Chia sẻ kinh nghiệm chăm sóc và lựa chọn cây cảnh phù hợp cho không gian sống của bạn.</p>
</section>

<section style="max-width:1100px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    @if ($posts->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($posts as $post)
                <a href="{{ route('posts.show', $post->slug) }}" style="display:block;text-decoration:none;color:inherit;background:#fff;border:1px solid #E3E1D9;border-radius:20px;padding:22px;transition:box-shadow .2s" class="hover:shadow-md">
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0 0 10px">
                        {{ optional($post->published_at)->format('d/m/Y') }}
                    </p>
                    <h2 style="font-family:'Anton',sans-serif;font-size:19px;text-transform:uppercase;letter-spacing:0.01em;color:#1C1C1A;margin:0 0 10px;line-height:1.3">
                        {{ $post->title }}
                    </h2>
                    <p style="font-size:14px;color:#6B6B66;line-height:1.7;margin:0 0 14px">
                        {{ $post->excerpt ?? Str::limit(strip_tags($post->content), 120) }}
                    </p>
                    <span style="font-size:13px;color:#5C2323;font-weight:600">Đọc tiếp →</span>
                </a>
            @endforeach
        </div>
    @else
        <div style="text-align:center;padding:64px 24px;background:#fff;border:1px solid #E3E1D9;border-radius:20px;color:#6B6B66">
            Chưa có bài viết nào trong cẩm nang. Vui lòng quay lại sau.
        </div>
    @endif

    @if ($posts->hasPages())
        <div style="margin-top:32px">{{ $posts->links() }}</div>
    @endif
</section>

@endsection
