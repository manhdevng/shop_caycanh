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

        /* ==== Thứ tự lớp (z-index) thống nhất toàn layout shop ====
           nội dung 1-10 -> section pin 20 -> header 100 -> mega menu 110 -> chat 200 -> toast 300 -> modal 400 */
        :root{--z-pin:20;--z-header:100;--z-mega:110;--z-chat:200;--z-toast:300;--z-modal:400;--header-h:76px}

        #siteHeader{background:transparent;border-bottom:1px solid transparent;transition:background-color .25s ease, border-color .25s ease}
        #siteHeader.header-scrolled{background:#FFFFFF;border-bottom-color:#E5E2DC}
        #siteHeader .header-logo,#siteHeader .header-icon{color:#FFFFFF;transition:color .25s ease}
        #siteHeader.header-scrolled .header-logo,#siteHeader.header-scrolled .header-icon{color:#1C1C1A}
        #siteHeader:not(.header-scrolled) .header-logo,
        #siteHeader:not(.header-scrolled) .header-icon,
        #siteHeader:not(.header-scrolled) .nav-mega-trigger{filter:drop-shadow(0 1px 4px rgba(0,0,0,.6))}
        /* var(--header-h) do JS đo thật (syncHeaderHeight) và cập nhật cả khi resize/header
           xuống 2 dòng ở mobile -> không còn lệch cứng như hằng số 76px trước đây (V7). */
        .page-with-header-offset{padding-top:var(--header-h,76px)}

        /* Header trong suốt trên hero và các cảnh nền tối của trang chủ. */
        #siteHeader.header-ghost,
        #siteHeader.header-ghost.header-scrolled{background:transparent;border-bottom-color:transparent}
        #siteHeader.header-ghost.header-scrolled .header-logo,
        #siteHeader.header-ghost.header-scrolled .header-icon{color:#FFFFFF}
        #siteHeader.header-ghost.header-scrolled .site-search{background:rgba(255,255,255,0.15);border-color:rgba(255,255,255,0.45)}
        #siteHeader.header-ghost.header-scrolled .site-search-input{color:#FFFFFF}
        #siteHeader.header-ghost.header-scrolled .site-search-input::placeholder{color:rgba(255,255,255,0.72)}
        #siteHeader.header-ghost.header-scrolled .site-search-icon{color:#FFFFFF}

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
        @media (max-width:640px){
            #siteHeader .header-inner{display:grid !important;grid-template-columns:minmax(0,1fr) auto;gap:9px 12px;padding:10px 16px !important}
            #siteHeader .header-logo{grid-column:1;grid-row:1;font-size:20px !important}
            #siteHeader .header-nav{grid-column:1 / -1;grid-row:2;gap:18px !important;min-width:0}
            #siteHeader .header-actions{display:contents !important}
            #siteHeader .header-tools{grid-column:2;grid-row:1;gap:14px;justify-self:end}
            #siteHeader .site-search{grid-column:1 / -1;grid-row:3;width:100%;height:36px !important;min-width:0 !important;max-width:none !important;flex:none !important}
            #siteHeader .header-auth{display:none !important}
            #notificationPanel{position:fixed !important;left:16px;right:16px;top:var(--header-h,76px) !important;width:auto !important;max-width:none !important;max-height:min(420px,calc(100dvh - var(--header-h,76px) - 16px)) !important}
        }

        /* ==== Mega-menu danh mục (Cây cảnh / Hoa) - dạng full-width chỉ chữ (kiểu Uniqlo) ==== */
        .nav-mega{position:relative}
        .nav-mega-trigger{background:none;border:none;padding:0;cursor:pointer;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase}
        /* Panel full-width, cố định ngay dưới header, đồng nhất kích thước dù mở scope nào */
        .nav-mega-panel{display:none;position:fixed;left:0;right:0;top:var(--header-h,68px);background:#FFFFFF;border-top:1px solid #E5E2DC;border-bottom:1px solid #E5E2DC;box-shadow:0 12px 24px rgba(0,0,0,0.06);z-index:var(--z-mega,110)}
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

        /* Popup chat trên mobile: full chiều rộng (trừ lề), neo trên nút chat, chiều cao
           giới hạn theo dvh để không bị bàn phím ảo che ô nhập (V10). */
        @media (max-width:640px){
            .chat-popup{inset:auto 8px 84px 8px !important;width:auto !important;max-width:none !important;height:min(480px,70dvh) !important;max-height:70dvh !important}
        }
    </style>
@stack('styles')
</head>
<body class="bg-white text-[#1C1C1A]">

<!-- ==== Header chính ==== -->
@php
    $isHomeHero = isset($showFeatured) && $showFeatured;
@endphp
<header id="siteHeader" class="{{ $isHomeHero ? '' : 'header-scrolled' }}" style="position:fixed;top:0;left:0;width:100%;z-index:var(--z-header,100)">
    <div class="header-inner" style="max-width:1400px;margin:0 auto;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
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
        <nav class="header-nav" aria-label="Danh mục sản phẩm" style="display:flex;align-items:center;gap:22px;flex:0 0 auto">
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

        <div class="header-actions" style="display:flex;align-items:center;gap:22px;flex:1 1 auto;justify-content:flex-end;min-width:280px;flex-wrap:wrap">
            <form class="site-search" action="{{ route('shop.index') }}" method="GET" style="display:flex;align-items:center;gap:8px;border-radius:999px;padding:0 6px 0 16px;height:38px;flex:1 1 220px;max-width:320px;min-width:170px">
                <i data-lucide="search" class="site-search-icon" style="width:16px;height:16px;flex:none"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm cây, hoa..." aria-label="Tìm kiếm sản phẩm" class="site-search-input" style="flex:1 1 auto;min-width:0;border:none;background:transparent;outline:none;font-size:13px;font-family:inherit">
                <button type="submit" class="site-search-btn" style="flex:none;padding:7px 16px;border-radius:999px;background:#1C1C1A;color:#FFFFFF;border:none;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer">Tìm</button>
            </form>

            <div class="header-tools" style="display:flex;align-items:center;gap:22px">
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
                        <a href="{{ route('orders.history') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Đơn mua</a>
                        <a href="{{ route('history.purchased') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Sản phẩm đã mua</a>
                        <a href="{{ route('history.index') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Đã xem gần đây</a>
                        <a href="{{ route('tickets.index') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Hỗ trợ của tôi</a>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" style="display:block;padding:10px 12px;border-radius:8px;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#2B2B28;white-space:nowrap">Trang quản trị</a>
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
                    <span id="notificationBadge" style="display:{{ $unreadNotificationCount > 0 ? 'block' : 'none' }};position:absolute;top:-9px;right:-12px;min-width:20px;height:20px;padding:0 4px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;line-height:20px;text-align:center;border:1px solid #FFFFFF">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                </button>
                <div id="notificationPanel" style="display:none;position:absolute;right:0;top:28px;background:#FFFFFF;border:1px solid #E5E2DC;border-radius:16px;box-shadow:0 12px 32px rgba(0,0,0,0.12);width:340px;max-width:calc(100vw - 32px);max-height:420px;overflow:hidden;z-index:60">
                    <div style="padding:14px 16px;border-bottom:1px solid #E5E2DC;font-family:'Anton',sans-serif;font-size:16px">Thông báo</div>
                    <div role="tablist" aria-label="Loại thông báo" style="display:flex;border-bottom:1px solid #E5E2DC">
                        <button type="button" class="notification-tab" data-notification-tab="orders" aria-selected="{{ $unreadOrderCount > 0 ? 'true' : 'false' }}" style="flex:1;border:0;border-bottom:2px solid {{ $unreadOrderCount > 0 ? '#5C2323' : 'transparent' }};background:#FFFFFF;padding:11px;font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">Đơn hàng</button>
                        <button type="button" class="notification-tab" data-notification-tab="promo" aria-selected="{{ $unreadOrderCount > 0 ? 'false' : 'true' }}" style="flex:1;border:0;border-bottom:2px solid {{ $unreadOrderCount > 0 ? 'transparent' : '#5C2323' }};background:#FFFFFF;padding:11px;font-family:'Space Mono',monospace;font-size:11px;cursor:pointer">Khuyến mãi</button>
                    </div>
                    <div id="notificationOrders" style="display:{{ $unreadOrderCount > 0 ? 'block' : 'none' }};max-height:310px;overflow-y:auto">
                        @forelse($orderNotifications as $n)
                            <a href="{{ route('notifications.open', $n->id) }}" style="display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid #E5E2DC;background:{{ $n->read_at ? '#FFFFFF' : '#F7F5F0' }}">
                                <i data-lucide="{{ data_get($n->data, 'icon', 'package') }}" style="width:18px;height:18px;flex:none;color:#4A6B1F"></i>
                                <span style="min-width:0;flex:1;overflow-wrap:anywhere">
                                    <strong style="display:block;font-size:13px;color:#1C1C1A">{{ data_get($n->data, 'title', 'Cập nhật đơn hàng') }}</strong>
                                    <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:12px;color:#8A8680;margin-top:3px">{{ data_get($n->data, 'message') }}</span>
                                    <time style="display:block;font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;margin-top:5px">{{ $n->created_at->diffForHumans() }}</time>
                                </span>
                            </a>
                        @empty
                            <p style="text-align:center;color:#8A8680;font-size:13px;padding:28px 16px;margin:0">Chưa có thông báo đơn hàng.</p>
                        @endforelse
                        <a href="{{ route('notifications.index', ['tab' => 'don-hang']) }}" style="display:block;text-align:center;padding:12px;font-family:'Space Mono',monospace;font-size:11px;color:#5C2323">Xem tất cả đơn hàng</a>
                    </div>
                    <div id="notificationPromos" style="display:{{ $unreadOrderCount > 0 ? 'none' : 'block' }};max-height:310px;overflow-y:auto">
                        @forelse($headerNotifications as $item)
                            <a href="{{ $item['url'] }}" style="display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid #E5E2DC">
                                <span style="flex:none;font-size:18px">{{ $item['icon'] }}</span>
                                <span style="min-width:0;flex:1;overflow-wrap:anywhere">
                                    <strong style="display:block;font-size:13px;color:#1C1C1A">{{ $item['title'] }}</strong>
                                    @if($item['description'])<span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:12px;color:#8A8680;margin-top:3px">{{ $item['description'] }}</span>@endif
                                    <time style="display:block;font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;margin-top:5px">{{ $item['created_at']->diffForHumans() }}</time>
                                </span>
                            </a>
                        @empty
                            <p style="text-align:center;color:#8A8680;font-size:13px;padding:28px 16px;margin:0">Chưa có thông báo khuyến mãi.</p>
                        @endforelse
                        <a href="{{ route('notifications.index', ['tab' => 'khuyen-mai']) }}" style="display:block;text-align:center;padding:12px;font-family:'Space Mono',monospace;font-size:11px;color:#5C2323">Xem tất cả khuyến mãi</a>
                    </div>
                </div>
            </div>

            <a href="{{ route('cart.index') }}" aria-label="Giỏ hàng" class="header-icon" style="position:relative;display:inline-flex;align-items:center;gap:5px">
                <i data-lucide="shopping-bag" style="width:19px;height:19px"></i>
                <span class="cart-count-badge" style="font-family:'Space Mono',monospace;font-size:12px;color:#5C2323">{{ count(session('cart', [])) }}</span>
            </a>
            </div>

            @guest
                <a href="{{ route('login') }}" class="header-icon header-auth" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap">Đăng nhập</a>
                <a href="{{ route('register') }}" class="header-auth" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:10px 22px;border-radius:999px;white-space:nowrap">Đăng ký</a>
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
<footer class="shop-footer">
    <section class="shop-footer__intro" aria-labelledby="shopFooterTitle">
        <div class="shop-footer__intro-inner">
            <div class="shop-footer__intro-copy">
                <p class="shop-footer__eyebrow">Một khoảng xanh cho ngôi nhà</p>
                <h2 id="shopFooterTitle">Chọn cây hợp nhà,<br>chăm xanh mỗi ngày.</h2>
                <p>Cây cảnh được tuyển chọn kỹ, đóng gói cẩn thận và giao đến tận tay bạn trên toàn quốc.</p>
                <a class="shop-footer__button" href="{{ route('shop.index', ['type' => 'plant']) }}">Khám phá cây cảnh <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="shop-footer__assurances" aria-label="Thông tin dịch vụ">
                <span><b>01</b> Cây được tuyển chọn</span>
                <span><b>02</b> Giao hàng toàn quốc</span>
                <span><b>03</b> Cẩm nang chăm cây</span>
            </div>
        </div>
    </section>

    <div class="shop-footer__body">
        <div class="shop-footer__grid">
            <div class="shop-footer__brand">
                <a class="shop-footer__wordmark" href="{{ route('shop.index') }}">{{ config('shop.name') }}</a>
                @if(config('shop.tagline'))
                    <p>{{ config('shop.tagline') }}</p>
                @endif
                <div class="shop-footer__payments" aria-label="Phương thức giao hàng và thanh toán">
                    <span>Giao hàng GHN</span>
                    <span>MoMo</span>
                    <span>COD</span>
                </div>
            </div>

            <nav class="shop-footer__column" aria-label="Mua sắm">
                <h3>Mua sắm</h3>
                <a href="{{ route('shop.index', ['type' => 'plant']) }}">Tất cả cây cảnh</a>
                <a href="{{ route('shop.index', ['type' => 'flower']) }}">Hoa tươi &amp; hoa sự kiện</a>
                <a href="{{ route('shop.bestSellers') }}">Sản phẩm bán chạy</a>
            </nav>

            <nav class="shop-footer__column" aria-label="Cẩm nang và hỗ trợ">
                <h3>Cẩm nang &amp; hỗ trợ</h3>
                <a href="{{ route('posts.show', 'huong-dan-cham-soc-cay') }}">Hướng dẫn chăm sóc cây</a>
                <a href="{{ route('posts.show', 'tu-van-chon-cay') }}">Tư vấn chọn cây</a>
                <a href="{{ route('faq.index') }}">Câu hỏi thường gặp</a>
                <a href="{{ route('pages.show', 'van-chuyen-doi-tra') }}">Vận chuyển &amp; đổi trả</a>
                <a href="{{ route('pages.show', 'chinh-sach-bao-hanh') }}">Chính sách bảo hành</a>
            </nav>

            <div class="shop-footer__column shop-footer__contact">
                <h3>Liên hệ</h3>
                <a href="{{ route('pages.show', 'lien-he') }}">Trang liên hệ <span aria-hidden="true">&rarr;</span></a>
                @if(config('shop.email'))
                    <a href="mailto:{{ config('shop.email') }}">{{ config('shop.email') }}</a>
                @endif
                @if(config('shop.hotline'))
                    <a href="tel:{{ preg_replace('/\s+/', '', config('shop.hotline')) }}">{{ config('shop.hotline') }} <span class="shop-footer__meta">Hotline / Zalo</span></a>
                @endif
                @if(config('shop.address'))
                    <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode(config('shop.address')) }}" target="_blank" rel="noopener noreferrer">{{ config('shop.address') }} <span class="shop-footer__meta">Xem bản đồ &rarr;</span></a>
                @endif
                @if(config('shop.hours'))
                    <p><span class="shop-footer__meta">Giờ mở cửa</span>{{ config('shop.hours') }}</p>
                @endif
                @if(config('shop.facebook') || config('shop.instagram'))
                    <div class="shop-footer__socials" aria-label="Mạng xã hội">
                        @if(config('shop.facebook'))
                            <a href="{{ config('shop.facebook') }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                        @endif
                        @if(config('shop.instagram'))
                            <a href="{{ config('shop.instagram') }}" target="_blank" rel="noopener noreferrer">Instagram</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="shop-footer__bottom">
        <p>&copy; {{ date('Y') }} {{ config('shop.name') }}. Đã đăng ký bản quyền.</p>
        <a href="{{ route('pages.show', 've-chung-toi') }}">Câu chuyện của chúng tôi</a>
    </div>
</footer>

<style>
    .shop-footer { margin-top: clamp(48px, 7vw, 88px); color: #F8F4ED; background: #1C1C1A; }
    .shop-footer__intro { position: relative; overflow: hidden; background: #26352B; }
    .shop-footer__intro::after { content: ''; position: absolute; width: 420px; height: 420px; right: -130px; top: -240px; border: 1px solid rgba(255,255,255,.1); border-radius: 50%; box-shadow: 0 0 0 34px rgba(255,255,255,.025), 0 0 0 68px rgba(255,255,255,.02); pointer-events: none; }
    .shop-footer__intro-inner { position: relative; z-index: 1; max-width: 1400px; margin: 0 auto; padding: clamp(44px, 6vw, 76px) 24px 30px; display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .6fr); align-items: end; gap: 48px; }
    .shop-footer__eyebrow { margin: 0 0 14px; color: #D8B58D; font: 11px 'Space Mono', monospace; letter-spacing: .12em; text-transform: uppercase; }
    .shop-footer__intro h2 { margin: 0; color: #FFFFFF; font: clamp(34px, 5vw, 64px)/1.02 'Anton', sans-serif; letter-spacing: .01em; text-transform: uppercase; }
    .shop-footer__intro-copy > p:not(.shop-footer__eyebrow) { max-width: 48ch; margin: 18px 0 24px; color: rgba(255,255,255,.72); font-size: 15px; line-height: 1.65; }
    .shop-footer__button { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; gap: 12px; padding: 0 20px; border-radius: 999px; color: #1C1C1A; background: #F8F4ED; font: 11px 'Space Mono', monospace; letter-spacing: .05em; text-decoration: none; text-transform: uppercase; transition: transform .2s ease, background .2s ease; }
    .shop-footer__button:hover { transform: translateY(-2px); background: #FFFFFF; }
    .shop-footer__button:focus-visible, .shop-footer a:focus-visible { outline: 3px solid #D8B58D; outline-offset: 4px; }
    .shop-footer__assurances { display: grid; gap: 0; border-top: 1px solid rgba(255,255,255,.2); }
    .shop-footer__assurances span { display: flex; align-items: center; gap: 16px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,.2); color: rgba(255,255,255,.82); font-size: 13px; }
    .shop-footer__assurances b { color: #D8B58D; font: 10px 'Space Mono', monospace; }
    .shop-footer__body { padding: clamp(44px, 6vw, 72px) 24px; background: #F7F4EF; color: #1C1C1A; }
    .shop-footer__grid { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: 1.15fr .8fr 1.1fr 1fr; gap: clamp(28px, 4vw, 56px); }
    .shop-footer__wordmark { display: inline-block; color: #1C1C1A; font: 25px 'Anton', sans-serif; letter-spacing: .01em; text-decoration: none; text-transform: uppercase; }
    .shop-footer__brand > p { max-width: 28ch; margin: 10px 0 22px; color: #6B6B66; font-size: 14px; line-height: 1.6; }
    .shop-footer__payments { display: flex; flex-wrap: wrap; gap: 7px; }
    .shop-footer__payments span { padding: 7px 9px; border: 1px solid #E2DDD3; border-radius: 5px; color: #6B6B66; font: 9px 'Space Mono', monospace; letter-spacing: .03em; text-transform: uppercase; }
    .shop-footer__column { display: flex; flex-direction: column; align-items: flex-start; gap: 12px; }
    .shop-footer__column h3 { margin: 3px 0 7px; color: #5C2323; font: 10px 'Space Mono', monospace; letter-spacing: .1em; text-transform: uppercase; }
    .shop-footer__column a { color: #5D5B55; font-size: 13px; line-height: 1.45; text-decoration: none; transition: color .18s ease; }
    .shop-footer__column a:hover { color: #5C2323; text-decoration: underline; text-underline-offset: 3px; }
    .shop-footer__contact a { overflow-wrap: anywhere; }
    .shop-footer__contact p { display: flex; flex-direction: column; gap: 5px; margin: 0; color: #5D5B55; font-size: 13px; line-height: 1.45; }
    .shop-footer__meta { display: block; color: #8A8680; font: 9px 'Space Mono', monospace; letter-spacing: .07em; text-transform: uppercase; }
    .shop-footer__socials { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 4px; }
    .shop-footer__bottom { display: flex; max-width: 1400px; min-height: 64px; align-items: center; justify-content: space-between; gap: 16px; margin: 0 auto; padding: 14px 24px; border-top: 1px solid rgba(255,255,255,.12); color: rgba(255,255,255,.55); }
    .shop-footer__bottom p { margin: 0; font-size: 12px; }
    .shop-footer__bottom a { color: rgba(255,255,255,.72); font-size: 12px; text-decoration: none; }
    .shop-footer__bottom a:hover { color: #FFFFFF; text-decoration: underline; text-underline-offset: 3px; }
    @media (max-width: 960px) {
        .shop-footer__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 38px; }
    }
    @media (max-width: 640px) {
        .shop-footer__intro-inner { grid-template-columns: 1fr; gap: 34px; }
        .shop-footer__intro::after { width: 300px; height: 300px; right: -170px; top: -140px; }
        .shop-footer__grid { grid-template-columns: 1fr 1fr; gap: 34px 22px; }
        .shop-footer__brand, .shop-footer__contact { grid-column: 1 / -1; }
        .shop-footer__bottom { align-items: flex-start; flex-direction: column; justify-content: center; gap: 8px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .shop-footer__button, .shop-footer__column a { transition: none; }
        .shop-footer__button:hover { transform: none; }
    }
</style>

@auth
<!-- ==== Popup chat hỗ trợ khách hàng ==== -->
<button type="button" id="chatToggleBtn" aria-label="Mở chat hỗ trợ" style="position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:999px;background:#5C2323;color:#FFFFFF;border:none;box-shadow:0 8px 24px rgba(0,0,0,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:var(--z-chat,200)">
    <i data-lucide="message-circle" style="width:24px;height:24px"></i>
    <span id="chatUnreadBadge" style="display:none;position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#DC2626;color:#FFFFFF;font-size:11px;line-height:18px;text-align:center;font-family:'Space Mono',monospace;border:2px solid #FFFFFF"></span>
</button>

<div id="chatPopup" class="chat-popup" style="display:none;flex-direction:column;position:fixed;bottom:92px;right:24px;width:340px;max-width:calc(100vw - 32px);height:460px;max-height:calc(100vh - 120px);background:#FFFFFF;border:1px solid #E5E2DC;border-radius:24px;box-shadow:0 12px 32px rgba(0,0,0,0.18);overflow:hidden;z-index:var(--z-chat,200)">
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
    // Trang không có hero (header luôn nền trắng cố định) dùng class .page-with-header-offset,
    // padding-top của nó đọc biến --header-h (do syncHeaderHeight() đo + cập nhật cả khi resize,
    // xem IIFE mega-menu bên dưới) thay vì set inline 1 lần duy nhất như trước (V7: lệch khi
    // header xuống 2 dòng ở mobile rồi resize/xoay ngang mà không đo lại).

    if (siteHeader && heroSection) {
        const categorySection = document.getElementById('catArcSection');
        const toggleHeaderOnScroll = function () {
            const threshold = categorySection
                ? categorySection.getBoundingClientRect().bottom + window.scrollY - siteHeader.offsetHeight
                : heroSection.offsetHeight * 0.9;
            const pastDarkScenes = window.scrollY >= threshold;
            siteHeader.classList.toggle('header-scrolled', pastDarkScenes);
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
                const headerHeight = siteHeader.offsetHeight + 'px';
                document.documentElement.style.setProperty('--header-h', headerHeight);
                document.documentElement.style.setProperty('--sc-safe-top', headerHeight);
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
        const tabs = document.querySelectorAll('.notification-tab');
        function renderNotificationBadge(count) {
            if (!notificationBadge) return;
            notificationBadge.style.display = count > 0 ? 'block' : 'none';
            notificationBadge.textContent = count > 9 ? '9+' : String(count);
        }
        function markPromosSeen() {
            if (notificationMarkedSeen) return;
            notificationMarkedSeen = true;
            @auth
            fetch('{{ route('notifications.mark-seen') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            }).then(function () { loadNotificationCount(); }).catch(function () {});
            @endauth
        }
        function selectNotificationTab(name) {
            document.getElementById('notificationOrders').style.display = name === 'orders' ? 'block' : 'none';
            document.getElementById('notificationPromos').style.display = name === 'promo' ? 'block' : 'none';
            tabs.forEach(function (tab) {
                const active = tab.dataset.notificationTab === name;
                tab.style.borderBottomColor = active ? '#5C2323' : 'transparent';
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (name === 'promo') markPromosSeen();
        }
        tabs.forEach(function (tab) { tab.addEventListener('click', function () { selectNotificationTab(tab.dataset.notificationTab); }); });
        notificationToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            notificationPanel.style.display = notificationPanel.style.display === 'none' ? 'block' : 'none';
            if (notificationPanel.style.display === 'block' && document.getElementById('notificationPromos').style.display === 'block') markPromosSeen();
        });
        document.addEventListener('click', function (event) {
            if (!notificationPanel.contains(event.target) && !notificationToggle.contains(event.target)) notificationPanel.style.display = 'none';
        });
        @auth
        function loadNotificationCount() {
            if (document.hidden) return;
            fetch('{{ route('notifications.unreadCount') }}', { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (data) { renderNotificationBadge(Number(data.total) || 0); })
                .catch(function () {});
        }
        setInterval(loadNotificationCount, 60000);
        @endauth
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

    // ==== Toast: neo trên-phải dưới header (không đè nút chat góc dưới-phải, V1) ====
    // Nhiều toast liên tiếp xếp chồng theo cột (gap) thay vì đè lên nhau.
    function getToastContainer() {
        let box = document.getElementById('toastContainer');
        if (!box) {
            box = document.createElement('div');
            box.id = 'toastContainer';
            box.style.cssText = 'position:fixed;top:calc(var(--header-h,76px) + 12px);right:24px;display:flex;flex-direction:column;gap:10px;z-index:var(--z-toast,300);max-width:calc(100vw - 32px)';
            document.body.appendChild(box);
        }
        return box;
    }

    function showToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.style.cssText = 'color:#fff;font-size:13px;padding:12px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);transition:opacity .4s;background:' + (isError ? '#B3261E' : '#1C1C1A');
        toast.textContent = message;
        getToastContainer().appendChild(toast);
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
