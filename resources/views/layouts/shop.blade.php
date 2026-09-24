<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cây Cảnh Shop')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Anton', 'sans-serif'],
                        mono: ['"Space Mono"', 'ui-monospace', 'monospace'],
                    },
                },
            },
        };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:'Inter',system-ui,sans-serif;background:#FFFFFF;color:#1C1C1A;-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}
        a:hover{color:#5C2323}
        ::-webkit-scrollbar{height:6px;width:6px}
        ::-webkit-scrollbar-thumb{background:#E5E2DC;border-radius:3px}
        .placeholder-pattern{background:repeating-linear-gradient(135deg,#F7F4EF,#F7F4EF 10px,#EFEAE1 10px,#EFEAE1 20px)}

        #siteHeader{background:transparent;border-bottom:1px solid transparent;transition:background-color .25s ease, border-color .25s ease}
        #siteHeader.header-scrolled{background:#FFFFFF;border-bottom-color:#E5E2DC}
        #siteHeader .header-logo,#siteHeader .header-icon{color:#FFFFFF;transition:color .25s ease}
        #siteHeader.header-scrolled .header-logo,#siteHeader.header-scrolled .header-icon{color:#1C1C1A}
        .page-with-header-offset{padding-top:76px}

        /* ==== Ô tìm kiếm luôn hiển thị trong header (đọc được ở cả nền trong suốt lẫn header-scrolled) ==== */
        #siteHeader .site-search{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.45);transition:background-color .25s ease, border-color .25s ease}
        #siteHeader .site-search-input{color:#FFFFFF}
        #siteHeader .site-search-input::placeholder{color:rgba(255,255,255,0.72)}
        #siteHeader .site-search-icon{color:#FFFFFF;transition:color .25s ease}
        #siteHeader.header-scrolled .site-search{background:#F7F4EF;border-color:#E5E2DC}
        #siteHeader.header-scrolled .site-search-input{color:#1C1C1A}
        #siteHeader.header-scrolled .site-search-input::placeholder{color:#8A8680}
        #siteHeader.header-scrolled .site-search-icon{color:#8A8680}
        @media (max-width:900px){
            .site-search{flex-basis:100%;max-width:none;order:10}
        }

        /* ==== Mega-menu danh mục (Cây cảnh / Hoa) - dạng full-width chỉ chữ (kiểu Uniqlo) ==== */
        .nav-mega{position:relative}
        .nav-mega-trigger{background:none;border:none;padding:0;cursor:pointer;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase}
        /* Panel full-width, cố định ngay dưới header, đồng nhất kích thước dù mở scope nào */
        .nav-mega-panel{display:none;position:fixed;left:0;right:0;top:var(--header-h,68px);background:#FFFFFF;border-top:1px solid #E5E2DC;border-bottom:1px solid #E5E2DC;box-shadow:0 12px 24px rgba(0,0,0,0.06);z-index:110}
        .nav-mega.is-open .nav-mega-panel{display:block}
        .nav-mega-panel-inner{max-width:1400px;margin:0 auto;padding:28px 24px}
        .nav-mega-all{display:block;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #EFEAE1;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#5C2323}
        .nav-mega-grid{display:grid;grid-template-columns:repeat(4, minmax(0,1fr));gap:36px}
        .nav-mega-col{min-width:0}
        .nav-mega-heading{display:block;font-family:'Space Mono',monospace;font-size:10.5px;letter-spacing:0.1em;text-transform:uppercase;color:#8A8680;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #EFEAE1}
        .nav-mega-heading:hover{color:#5C2323}
        .nav-mega-tile{display:block;padding:9px 10px;border-radius:12px;transition:background-color .2s ease, color .2s ease}
        .nav-mega-tile:hover{background:#F7F4EF}
        .nav-mega-tile:hover .nav-mega-tile-name{color:#5C2323}
        .nav-mega-tile-body{display:flex;flex-direction:column;gap:4px;min-width:0}
        .nav-mega-tile-name{font-family:'Space Mono',monospace;font-size:12.5px;letter-spacing:0.04em;text-transform:uppercase;color:#2B2B28;line-height:1.45;transition:color .2s ease}
        .nav-mega-tile-count{font-family:'Space Mono',monospace;font-size:10px;color:#8A8680}
        @media (max-width:900px){
            /* Header có flex-wrap nên trên màn hẹp nó cao hơn 1 dòng -> neo panel theo
               chiều cao header thật (biến --header-h do JS đo), tránh panel che navbar. */
            .nav-mega-panel{max-height:70vh;overflow-y:auto}
            .nav-mega-panel-inner{padding:22px 20px}
            .nav-mega-grid{grid-template-columns:1fr !important;gap:24px}
        }
    </style>
@stack('styles')
</head>
<body class="bg-white text-[#1C1C1A]">

<!-- ==== Header chính ==== -->
@php
    $isHomeHero = isset($showFeatured) && $showFeatured;
@endphp
<header id="siteHeader" class="{{ $isHomeHero ? '' : 'header-scrolled' }}" style="position:fixed;top:0;left:0;width:100%;z-index:100">
    <div style="max-width:1400px;margin:0 auto;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
        <a href="{{ route('shop.index') }}" class="header-logo" style="flex:0 0 auto;font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.02em;text-transform:uppercase;white-space:nowrap">Cây Cảnh Shop</a>

        {{--
            ==== Mega-menu danh mục Cây cảnh / Hoa ====
            Thay cho icon danh mục cũ: 2 mục chữ nằm ngang cạnh logo, mỗi mục mở 1 panel
            full-width cố định ngay dưới header (kiểu Uniqlo), dạng lưới cột cố định
            (mỗi nhóm gốc = 1 cột), mỗi danh mục con chỉ hiển thị TEXT (không ảnh/icon)
            kèm số sản phẩm. $navCategories (view composer AppServiceProvider) chỉ còn
            nhóm gốc scope plant/flower — nhóm scope=both đã bị loại từ composer, không
            cần lọc lại.
        --}}
        @php
            $megaMenus = [
                'plant' => ['label' => 'Cây cảnh', 'panelId' => 'megaPanelPlant'],
                'flower' => ['label' => 'Hoa', 'panelId' => 'megaPanelFlower'],
            ];
        @endphp
        <nav aria-label="Danh mục sản phẩm" style="display:flex;align-items:center;gap:22px;flex:0 0 auto">
            <a href="{{ route('shop.bestSellers') }}" class="header-icon" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap;{{ request()->routeIs('shop.bestSellers') ? 'color:#5C2323' : '' }}">Bán chạy</a>
            @foreach($megaMenus as $scopeValue => $meta)
                @php $scopeGroups = $navCategories->where('scope', $scopeValue)->filter(fn ($g) => $g->children->isNotEmpty()); @endphp
                @if($scopeGroups->isNotEmpty())
                    <div class="nav-mega" data-mega-scope="{{ $scopeValue }}">
                        <button type="button" class="nav-mega-trigger header-icon" aria-expanded="false" aria-controls="{{ $meta['panelId'] }}">{{ $meta['label'] }}</button>
                        <div class="nav-mega-panel" id="{{ $meta['panelId'] }}">
                            <div class="nav-mega-panel-inner">
                                <a href="{{ route('shop.index', ['type' => $scopeValue]) }}" class="nav-mega-all">Tất cả {{ mb_strtolower($meta['label']) }}</a>
                                <div class="nav-mega-grid">
                                    @foreach($scopeGroups as $group)
                                        <div class="nav-mega-col">
                                            <a href="{{ route('shop.index', ['categories' => $group->children->pluck('id')->all()]) }}" class="nav-mega-heading">{{ $group->name }}</a>
                                            @foreach($group->children as $child)
                                                <a href="{{ route('shop.index', ['categories' => [$child->id], 'type' => $scopeValue]) }}" class="nav-mega-tile">
                                                    <span class="nav-mega-tile-body">
                                                        <span class="nav-mega-tile-name">{{ $child->name }}</span>
                                                        @if($child->products_count > 0)
                                                            <span class="nav-mega-tile-count">{{ $child->products_count }} sản phẩm</span>
                                                        @endif
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div style="display:flex;align-items:center;gap:22px;flex:1 1 auto;justify-content:flex-end;min-width:280px;flex-wrap:wrap">
            <form class="site-search" action="{{ route('shop.index') }}" method="GET" style="display:flex;align-items:center;gap:8px;border-radius:999px;padding:0 6px 0 16px;height:38px;flex:1 1 220px;max-width:320px;min-width:170px">
                <i data-lucide="search" class="site-search-icon" style="width:16px;height:16px;flex:none"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm cây, hoa..." aria-label="Tìm kiếm sản phẩm" class="site-search-input" style="flex:1 1 auto;min-width:0;border:none;background:transparent;outline:none;font-size:13px;font-family:inherit">
                <button type="submit" class="site-search-btn" style="flex:none;padding:7px 16px;border-radius:999px;background:#1C1C1A;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer">Tìm</button>
            </form>

            <div style="position:relative;display:inline-flex">
                <button type="button" id="accountToggle" aria-label="Tài khoản" class="header-icon" style="display:inline-flex;background:none;border:none;padding:0;cursor:pointer">
                    <i data-lucide="user" style="width:19px;height:19px"></i>
                </button>
                <div id="accountPanel" style="display:none;position:absolute;right:0;top:28px;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:12px;padding:10px;box-shadow:0 8px 24px rgba(0,0,0,0.08);min-width:200px;z-index:60">
                    @guest
                        <a href="{{ route('login') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Đăng nhập</a>
                        <a href="{{ route('register') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Đăng ký</a>
                    @else
                        <span style="display:block;padding:10px 12px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;white-space:nowrap">Xin chào, {{ Auth::user()->name }}</span>
                        <a href="{{ route('profile.show') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Hồ sơ của tôi</a>
                        <a href="{{ route('wishlist.index') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Yêu thích</a>
                        <a href="{{ route('vouchers.wallet') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Ví voucher</a>
                        <a href="{{ route('history.index') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Lịch sử</a>
                        <a href="{{ route('tickets.index') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Hỗ trợ của tôi</a>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Trang quản trị</a>
                        @else
                            <a href="{{ route('orders.history') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Đơn hàng của tôi</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" style="margin:0">
                            @csrf
                            <button type="submit" style="display:block;width:100%;text-align:left;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap;background:none;border:none;cursor:pointer">Đăng xuất</button>
                        </form>
                    @endguest
                </div>
            </div>

            <div style="position:relative;display:inline-flex">
                <button type="button" id="notificationToggle" aria-label="Thông báo" class="header-icon" style="position:relative;display:inline-flex;background:none;border:none;padding:0;cursor:pointer">
                    <i data-lucide="bell" style="width:19px;height:19px"></i>
                    <span id="notificationBadge" style="display:{{ $unreadNotificationCount > 0 ? 'block' : 'none' }};position:absolute;top:-4px;right:-4px;min-width:9px;height:9px;border-radius:999px;background:#5C2323;border:2px solid #FFFFFF"></span>
                </button>
                <div id="notificationPanel" style="display:none;position:absolute;right:0;top:28px;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;box-shadow:0 12px 32px rgba(0,0,0,0.12);width:340px;max-width:calc(100vw - 32px);max-height:420px;overflow-y:auto;z-index:60">
                    <div style="padding:14px 16px;border-bottom:1px solid #E5E2DC">
                        <span style="font-family:'Anton',sans-serif;font-size:15px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A">Thông báo</span>
                    </div>
                    @forelse($headerNotifications as $item)
                        @php
                            $typeBorderColors = [
                                'voucher' => '#5C2323',
                                'post' => '#7CA6D8',
                                'product' => '#4A6B1F',
                            ];
                            $borderColor = $typeBorderColors[$item['type']] ?? '#E5E2DC';
                        @endphp
                        <a href="{{ $item['url'] }}" style="display:flex;gap:10px;padding:12px 16px;border-left:3px solid {{ $borderColor }};border-bottom:1px solid #F1F0EC">
                            <span style="flex:none;font-size:18px;line-height:1">{{ $item['icon'] }}</span>
                            <span style="flex:1 1 auto;min-width:0">
                                <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;font-weight:600;color:#1C1C1A;line-height:1.4">{{ $item['title'] }}</span>
                                @if($item['description'])
                                    <span style="display:block;font-size:12px;color:#6B6B66;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['description'] }}</span>
                                @endif
                                <span style="display:block;font-family:'Space Mono',monospace;font-size:10.5px;color:#8A8680;margin-top:4px">{{ $item['created_at']->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <p style="text-align:center;color:#8A8680;font-size:13px;padding:32px 16px;margin:0">Chưa có thông báo nào.</p>
                    @endforelse
                    <a href="{{ route('notifications.index') }}" style="display:block;text-align:center;padding:12px 16px;font-family:'Space Mono',monospace;font-size:11.5px;letter-spacing:0.06em;text-transform:uppercase;color:#5C2323">Xem tất cả</a>
                </div>
            </div>

            <a href="{{ route('cart.index') }}" aria-label="Giỏ hàng" class="header-icon" style="position:relative;display:inline-flex;align-items:center;gap:5px">
                <i data-lucide="shopping-bag" style="width:19px;height:19px"></i>
                <span class="cart-count-badge" style="font-family:'Space Mono',monospace;font-size:12px;color:#5C2323">{{ count(session('cart', [])) }}</span>
            </a>

            @guest
                <a href="{{ route('login') }}" class="header-icon" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap">Đăng nhập</a>
                <a href="{{ route('register') }}" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:10px 22px;border-radius:999px;white-space:nowrap">Đăng ký</a>
            @endguest
        </div>
    </div>
</header>

<main id="mainContent" class="{{ $isHomeHero ? '' : 'page-with-header-offset' }}">
    @if (session('success'))
        <div style="max-width:1400px;margin:16px auto 0;padding:0 24px">
            <div style="background:#EEF3EA;border:1px solid #C9D8C0;color:#3F5B45;border-radius:12px;padding:12px 16px;font-size:13px">{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div style="max-width:1400px;margin:16px auto 0;padding:0 24px">
            <div style="background:#FBEAEA;border:1px solid #E7C6C6;color:#B3261E;border-radius:12px;padding:12px 16px;font-size:13px">{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</main>

<!-- ==== Footer ==== -->
<footer style="background:#FFFFFF;margin-top:64px">
    <div style="max-width:760px;margin:0 auto;padding:clamp(64px,8vw,80px) 24px 40px;text-align:center">
        <p style="font-size:clamp(22px,2.4vw,28px);line-height:1.6;color:#4A4A46;margin:0">&ldquo;Mỗi cây đều được tuyển chọn kỹ, đóng gói cẩn thận và giao tận tay bạn trên toàn quốc.&rdquo;</p>
    </div>
    <div style="display:flex;justify-content:center;align-items:center;gap:56px;flex-wrap:wrap;padding:0 24px 64px">
        <span style="font-family:'Space Mono',monospace;font-size:18px;font-weight:700;letter-spacing:0.04em;color:#B8B4AC">GHN</span>
        <span style="font-family:'Space Mono',monospace;font-size:18px;font-weight:700;letter-spacing:0.04em;color:#B8B4AC">MoMo</span>
    </div>
    <div style="border-top:1px solid #E5E2DC"></div>

    <div style="background:#F7F4EF;padding:clamp(56px,8vw,88px) 24px 40px">
        <div style="max-width:1400px;margin:0 auto;display:flex;gap:56px;flex-wrap:wrap">
            <div style="flex:3 1 420px">
                <h3 style="font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 12px">Đăng ký nhận tin</h3>
                <p style="font-size:14px;line-height:1.6;color:#6B6B66;max-width:420px;margin:0 0 20px">Nhận mẹo chăm sóc cây và cập nhật sản phẩm mới sớm nhất.</p>
                <div style="display:flex;gap:8px;max-width:420px;margin-bottom:44px">
                    <input type="text" placeholder="Email của bạn" style="flex:1 1 auto;min-width:0;padding:11px 16px;border:1px solid #E5E2DC;border-radius:999px;font-size:13px;font-family:inherit;background:#FFFFFF"/>
                    <button type="button" style="flex:none;padding:11px 22px;border-radius:999px;background:#1C1C1A;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase">Đăng ký</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:32px">
                    <div>
                        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Về chúng tôi</p>
                        <a href="{{ route('shop.bestSellers') }}" style="display:block;font-size:14px;color:{{ request()->routeIs('shop.bestSellers') ? '#5C2323' : '#6B6B66' }};margin-bottom:11px">Sản phẩm bán chạy</a>
                        <a href="{{ route('pages.show', 've-chung-toi') }}" style="display:block;font-size:14px;color:#6B6B66;margin-bottom:11px">Câu chuyện của chúng tôi</a>
                        <a href="#" style="display:block;font-size:14px;color:#6B6B66">Đánh giá khách hàng</a>
                    </div>
                    <div>
                        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Cẩm nang</p>
                        <a href="{{ route('posts.show', 'huong-dan-cham-soc-cay') }}" style="display:block;font-size:14px;color:#6B6B66;margin-bottom:11px">Hướng dẫn chăm sóc cây</a>
                        <a href="{{ route('posts.show', 'tu-van-chon-cay') }}" style="display:block;font-size:14px;color:#6B6B66;margin-bottom:11px">Tư vấn chọn cây</a>
                        <a href="{{ route('faq.index') }}" style="display:block;font-size:14px;color:{{ request()->routeIs('faq.index') ? '#5C2323' : '#6B6B66' }}">Câu hỏi thường gặp</a>
                    </div>
                    <div>
                        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Điều khoản</p>
                        <a href="{{ route('pages.show', 'van-chuyen-doi-tra') }}" style="display:block;font-size:14px;color:#6B6B66;margin-bottom:11px">Vận chuyển &amp; đổi trả</a>
                        <a href="{{ route('pages.show', 'chinh-sach-bao-hanh') }}" style="display:block;font-size:14px;color:#6B6B66">Chính sách bảo hành</a>
                    </div>
                </div>
            </div>

            <div style="flex:2 1 320px">
                <h3 style="font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 20px">Liên hệ</h3>
                <a href="{{ route('pages.show', 'lien-he') }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid #E5E2DC;border-radius:10px;padding:16px 18px;margin-bottom:14px;background:#FFFFFF">
                    <div>
                        <div style="font-family:'Space Mono',monospace;font-size:10.5px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin-bottom:4px">Email</div>
                        <div style="font-size:14px;color:#1C1C1A">hotro@caycanhshop.vn</div>
                    </div>
                    <span style="font-size:18px;color:#6B6B66">&rsaquo;</span>
                </a>
                @auth
                    <a href="{{ route('orders.history') }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid #E5E2DC;border-radius:10px;padding:16px 18px;background:#FFFFFF">
                        <div>
                            <div style="font-family:'Space Mono',monospace;font-size:10.5px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin-bottom:4px">Tài khoản của bạn</div>
                            <div style="font-size:14px;color:#1C1C1A">Quản lý đơn hàng và hỗ trợ</div>
                        </div>
                        <span style="font-size:18px;color:#6B6B66">&rsaquo;</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- ==== Dải "Thông tin cửa hàng" (nền tối, khép trang lại) ====
         Mỗi mục lấy từ config/shop.php: mục nào null thì không render cột đó
         (không để ô trống, không bịa dữ liệu). --}}
    <style>
        .footer-info-grid{display:grid;grid-template-columns:1.4fr repeat(3, minmax(0,1fr));gap:40px}
        @media (max-width:900px){
            .footer-info-grid{grid-template-columns:repeat(2, minmax(0,1fr))}
        }
        @media (max-width:560px){
            .footer-info-grid{grid-template-columns:1fr}
        }
    </style>
    <div style="background:#1C1C1A;padding:clamp(48px,7vw,72px) 24px clamp(32px,5vw,48px)">
        <div class="footer-info-grid" style="max-width:1400px;margin:0 auto">
            <div>
                <h3 style="font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.01em;text-transform:uppercase;color:#FFFFFF;margin:0 0 10px">{{ config('shop.name') }}</h3>
                @if(config('shop.tagline'))
                    <p style="font-size:14px;line-height:1.6;color:rgba(255,255,255,.7);max-width:280px;margin:0">{{ config('shop.tagline') }}</p>
                @endif
            </div>

            @if(config('shop.address'))
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 14px">Địa chỉ</p>
                    <p style="font-size:14px;line-height:1.6;color:rgba(255,255,255,.85);margin:0 0 8px">{{ config('shop.address') }}</p>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(config('shop.address')) }}" target="_blank" rel="noopener" style="font-size:13px;color:#FFFFFF;text-decoration:underline">Xem bản đồ</a>
                </div>
            @endif

            @if(config('shop.hotline'))
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 14px">Hotline / Zalo</p>
                    <a href="tel:{{ preg_replace('/\s+/', '', config('shop.hotline')) }}" style="font-size:14px;color:#FFFFFF">{{ config('shop.hotline') }}</a>
                </div>
            @endif

            @if(config('shop.email'))
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 14px">Email</p>
                    <a href="mailto:{{ config('shop.email') }}" style="font-size:14px;color:#FFFFFF">{{ config('shop.email') }}</a>
                </div>
            @endif

            @if(config('shop.hours'))
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 14px">Giờ mở cửa</p>
                    <p style="font-size:14px;line-height:1.6;color:rgba(255,255,255,.85);margin:0">{{ config('shop.hours') }}</p>
                </div>
            @endif

            @if(config('shop.facebook') || config('shop.instagram'))
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);margin:0 0 14px">Mạng xã hội</p>
                    {{-- Ghi chú: bản lucide@latest hiện tại đã bỏ toàn bộ icon thương hiệu (facebook/
                         instagram không còn trong bộ icon nạp qua CDN), dùng data-lucide="facebook" sẽ
                         ra thẻ rỗng vô hình. Vì vậy dán thẳng SVG nội tuyến bên dưới thay vì gọi lucide
                         — đây chính là 2 icon lucide "facebook"/"instagram" bản cũ trước khi bị gỡ, nên
                         vẫn khớp phong cách nét 24x24 stroke-width:2 đang dùng khắp trang. Đừng quay lại
                         thử data-lucide="facebook"/"instagram", nó sẽ không hiện gì cả. --}}
                    <div style="display:flex;align-items:center;gap:10px">
                        @if(config('shop.facebook'))
                            <a href="{{ config('shop.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook của {{ config('shop.name') }}" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:999px;border:1px solid rgba(255,255,255,.3);color:#FFFFFF">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                </svg>
                            </a>
                        @endif
                        @if(config('shop.instagram'))
                            <a href="{{ config('shop.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram của {{ config('shop.name') }}" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:999px;border:1px solid rgba(255,255,255,.3);color:#FFFFFF">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div style="background:#1C1C1A;border-top:1px solid rgba(255,255,255,.12)">
        <p style="max-width:1400px;margin:0 auto;padding:20px 24px;font-size:13px;color:rgba(255,255,255,.55)">&copy; {{ date('Y') }} {{ config('shop.name') }}. Đã đăng ký bản quyền.</p>
    </div>
</footer>

@auth
<!-- ==== Popup chat hỗ trợ khách hàng ==== -->
<button type="button" id="chatToggleBtn" aria-label="Mở chat hỗ trợ" style="position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;box-shadow:0 8px 24px rgba(0,0,0,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:200">
    <i data-lucide="message-circle" style="width:24px;height:24px"></i>
    <span id="chatUnreadBadge" style="display:none;position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#DC2626;color:#FFFFFF;font-size:11px;line-height:18px;text-align:center;font-family:'Space Mono',monospace;border:2px solid #FFFFFF"></span>
</button>

<div id="chatPopup" style="display:none;flex-direction:column;position:fixed;bottom:92px;right:24px;width:340px;max-width:calc(100vw - 32px);height:460px;max-height:calc(100vh - 120px);background:#FFFFFF;border:1px solid #E5E2DC;border-radius:24px;box-shadow:0 12px 32px rgba(0,0,0,0.18);overflow:hidden;z-index:200">
    <div style="padding:16px 18px;border-bottom:1px solid #E5E2DC;display:flex;align-items:center;justify-content:space-between;flex:none">
        <span style="font-family:'Anton',sans-serif;font-size:16px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A">Hỗ trợ trực tuyến</span>
        <button type="button" id="chatCloseBtn" aria-label="Đóng chat" style="background:none;border:none;cursor:pointer;color:#6B6B66;display:flex">
            <i data-lucide="x" style="width:18px;height:18px"></i>
        </button>
    </div>
    <div id="chatMessages" style="flex:1 1 auto;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px"></div>
    <form id="chatForm" style="display:flex;gap:8px;padding:12px;border-top:1px solid #E5E2DC;flex:none">
        <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." autocomplete="off" style="flex:1 1 auto;min-width:0;border:1px solid #E5E2DC;border-radius:999px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none">
        <button type="submit" aria-label="Gửi tin nhắn" style="flex:none;width:40px;height:40px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer">
            <i data-lucide="send" style="width:16px;height:16px"></i>
        </button>
    </form>
</div>
@endauth

<script>
    lucide.createIcons();

    // ==== Header trong suốt đè lên hero (chỉ trang chủ) / đo padding-top cho các trang khác ====
    const siteHeader = document.getElementById('siteHeader');
    const mainContent = document.getElementById('mainContent');
    const heroSection = document.getElementById('heroSection');
    const isNonHeroPage = mainContent && mainContent.classList.contains('page-with-header-offset');

    if (siteHeader && isNonHeroPage) {
        // Trang không có hero: header luôn nền trắng cố định, chỉ cần đo padding-top thực tế.
        mainContent.style.paddingTop = siteHeader.offsetHeight + 'px';
    }

    if (siteHeader && heroSection) {
        const toggleHeaderOnScroll = function () {
            const threshold = heroSection.offsetHeight * 0.9;
            if (window.scrollY > threshold) {
                siteHeader.classList.add('header-scrolled');
            } else {
                siteHeader.classList.remove('header-scrolled');
            }
        };
        window.addEventListener('scroll', toggleHeaderOnScroll, { passive: true });
        toggleHeaderOnScroll();
    }

    // ==== Mega-menu danh mục (Cây cảnh / Hoa) ====
    // Desktop: hover mở panel (có độ trễ khi rời chuột để không bị nháy) + click cũng mở/đóng được.
    // Mobile (<=900px): chỉ dùng click, panel co thành list dọc (xử lý bằng CSS ở media query).
    (function () {
        const megas = Array.from(document.querySelectorAll('.nav-mega'));
        let megaCloseTimer = null;

        function closeAllMegas() {
            megas.forEach(function (mega) {
                mega.classList.remove('is-open');
                const trigger = mega.querySelector('.nav-mega-trigger');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }

        // Đo chiều cao header thật -> panel mobile (position:fixed) neo đúng dưới header
        function syncHeaderHeight() {
            if (siteHeader) {
                document.documentElement.style.setProperty('--header-h', siteHeader.offsetHeight + 'px');
            }
        }
        syncHeaderHeight();
        window.addEventListener('resize', syncHeaderHeight);

        function openMega(mega) {
            closeAllMegas();
            syncHeaderHeight();
            mega.classList.add('is-open');
            const trigger = mega.querySelector('.nav-mega-trigger');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
        }

        megas.forEach(function (mega) {
            const trigger = mega.querySelector('.nav-mega-trigger');
            if (!trigger) return;

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                if (mega.classList.contains('is-open')) {
                    closeAllMegas();
                } else {
                    openMega(mega);
                }
            });

            mega.addEventListener('mouseenter', function () {
                if (window.innerWidth <= 900) return; // mobile chỉ dùng click
                clearTimeout(megaCloseTimer);
                openMega(mega);
            });
            mega.addEventListener('mouseleave', function () {
                if (window.innerWidth <= 900) return;
                megaCloseTimer = setTimeout(closeAllMegas, 150);
            });
        });

        document.addEventListener('click', function (e) {
            const clickedInsideMega = megas.some(function (mega) { return mega.contains(e.target); });
            if (!clickedInsideMega) closeAllMegas();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAllMegas();
        });

        // API cho trang khác gọi mở mega-menu theo scope ('plant' | 'flower')
        window.openNavMega = function (scope) {
            const mega = megas.find(function (m) { return m.dataset.megaScope === scope; });
            if (!mega) return false;
            openMega(mega);
            return true;
        };
    })();

    const accountToggle = document.getElementById('accountToggle');
    const accountPanel = document.getElementById('accountPanel');
    if (accountToggle && accountPanel) {
        accountToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            accountPanel.style.display = accountPanel.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', function (e) {
            if (!accountPanel.contains(e.target) && e.target !== accountToggle) {
                accountPanel.style.display = 'none';
            }
        });
    }

    // ==== Chuông thông báo (header) ====
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationToggle && notificationPanel) {
        let notificationMarkedSeen = false;

        notificationToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const willOpen = notificationPanel.style.display === 'none' || !notificationPanel.style.display;
            notificationPanel.style.display = willOpen ? 'block' : 'none';

            if (willOpen && !notificationMarkedSeen) {
                notificationMarkedSeen = true;
                @auth
                fetch('{{ route('notifications.mark-seen') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success && notificationBadge) {
                            notificationBadge.style.display = 'none';
                        }
                    })
                    .catch(() => {});
                @endauth
            }
        });
        document.addEventListener('click', function (e) {
            if (!notificationPanel.contains(e.target) && e.target !== notificationToggle) {
                notificationPanel.style.display = 'none';
            }
        });
    }

    // ==== Thêm giỏ hàng trực tiếp (không rời trang) ====
    function addToCart(productId, btn) {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '...';

        fetch(`/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
            .then(({ ok, data }) => {
                btn.disabled = false;
                btn.innerHTML = original;

                // 422 (thiếu phân loại / liên hệ giá) hoặc 404 (sản phẩm đã ẩn) — hiện lỗi
                // cho khách thay vì im lặng (P6, F9).
                if (!ok) {
                    showToast(data.message || 'Không thể thêm vào giỏ hàng.', true);
                    return;
                }

                document.querySelectorAll('.cart-count-badge').forEach(el => { el.textContent = data.cart_count; });

                showToast(data.message);
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = original;
                showToast('Không thể thêm vào giỏ hàng. Vui lòng thử lại.', true);
            });
    }

    function showToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;color:#fff;font-size:13px;padding:12px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);z-index:9999;transition:opacity .4s;background:' + (isError ? '#B3261E' : '#1C1C1A');
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; }, 2000);
        setTimeout(() => { toast.remove(); }, 2500);
    }

    // ==== Popup chat hỗ trợ khách hàng ====
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    const chatPopup = document.getElementById('chatPopup');
    const chatMessagesBox = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');

    if (chatToggleBtn && chatPopup) {
        const currentUserId = {{ Auth::check() ? Auth::id() : 'null' }};
        let chatPollingInterval = null;
        const chatUnreadBadge = document.getElementById('chatUnreadBadge');

        function chatEscapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ==== Chấm đỏ báo số tin nhắn chưa đọc (poll độc lập, chạy dù popup đóng) ====
        function renderChatUnreadBadge(count) {
            if (!chatUnreadBadge) return;
            if (!count || count <= 0) {
                chatUnreadBadge.style.display = 'none';
                return;
            }
            chatUnreadBadge.textContent = count > 9 ? '9+' : String(count);
            chatUnreadBadge.style.display = 'block';
        }

        function loadChatUnreadCount() {
            fetch('{{ route('chat.unreadCount') }}', {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => renderChatUnreadBadge(data && data.count))
                .catch(() => {});
        }

        loadChatUnreadCount();
        setInterval(loadChatUnreadCount, 10000);

        function chatIsOpen() {
            return chatPopup.style.display === 'flex';
        }

        function openChatPopup() {
            chatPopup.style.display = 'flex';
            loadMessages();
            if (!chatPollingInterval) {
                chatPollingInterval = setInterval(function () {
                    if (chatIsOpen()) {
                        loadMessages();
                    }
                }, 3000);
            }
        }

        function closeChatPopup() {
            chatPopup.style.display = 'none';
            if (chatPollingInterval) {
                clearInterval(chatPollingInterval);
                chatPollingInterval = null;
            }
        }

        chatToggleBtn.addEventListener('click', function () {
            if (chatIsOpen()) {
                closeChatPopup();
            } else {
                openChatPopup();
            }
        });

        if (chatCloseBtn) {
            chatCloseBtn.addEventListener('click', closeChatPopup);
        }

        function renderChatMessages(messages) {
            if (!chatMessagesBox) return;
            if (!messages.length) {
                chatMessagesBox.innerHTML = '<p style="margin:auto;text-align:center;font-size:13px;color:#6B6B66">Chưa có tin nhắn nào. Hãy bắt đầu trò chuyện!</p>';
                return;
            }
            chatMessagesBox.innerHTML = messages.map(function (msg) {
                const isMine = msg.sender_id == currentUserId;
                const bubbleStyle = isMine
                    ? 'align-self:flex-end;background:#5C2323;color:#FFFFFF;border-radius:16px 16px 4px 16px'
                    : 'align-self:flex-start;background:#F1F0EC;color:#1C1C1A;border-radius:16px 16px 16px 4px';
                return '<div style="max-width:80%;padding:9px 13px;font-size:13px;line-height:1.5;word-wrap:break-word;' + bubbleStyle + '">' + chatEscapeHtml(msg.content) + '</div>';
            }).join('');
            chatMessagesBox.scrollTop = chatMessagesBox.scrollHeight;
            lucide.createIcons();
        }

        function loadMessages() {
            fetch('{{ route('chat.messages') }}', {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => {
                    renderChatMessages(Array.isArray(data) ? data : []);
                    // Server đã tự đánh dấu đã đọc khi gọi API này -> cập nhật lại badge ngay.
                    loadChatUnreadCount();
                })
                .catch(() => showToast('Không thể tải tin nhắn. Vui lòng thử lại.', true));
        }

        if (chatForm) {
            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const content = chatInput.value.trim();
                if (!content) {
                    chatInput.focus();
                    return;
                }

                fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ content: content }),
                })
                    .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
                    .then(({ ok, data }) => {
                        if (!ok || data.error) {
                            showToast((data && data.error) || 'Gửi tin nhắn thất bại. Vui lòng thử lại.', true);
                            return;
                        }
                        chatInput.value = '';
                        loadMessages();
                    })
                    .catch(() => showToast('Gửi tin nhắn thất bại. Vui lòng thử lại.', true));
            });
        }
    }
</script>
@stack('scripts')
</body>
</html>
