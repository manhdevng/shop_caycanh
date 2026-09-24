<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị · Cây Cảnh Shop')</title>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        green: {
                            primary: '#5C2323',
                            secondary: '#000000',
                            accent: '#7A3030',
                            background: '#F7F4EF',
                            surface: '#191C21',
                            border: '#E5E2DC',
                        },
                        text: {
                            primary: '#1C1C1A',
                            secondary: '#4B5563',
                        }
                    },
                    fontFamily: {
                        display: ['Anton', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                        mono: ['"Space Mono"', 'monospace'],
                    },
                    borderRadius: {
                        'card': '32px',
                        'control': '31px',
                        'pill': '9999px',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F7F4EF; /* Cream background */
            color: #1C1C1A; /* Text primary */
        }
        h1, h2, h3, h4, h5, h6, .gloock {
            font-family: 'Anton', sans-serif;
            text-transform: uppercase;
        }
        .mono {
            font-family: 'Space Mono', monospace;
        }
        /* Checkbox/radio dùng màu oxblood thay vì xanh dương mặc định của
           trình duyệt (F2, TC08). */
        input[type=checkbox], input[type=radio] {
            accent-color: #5C2323;
        }
        /* Tắt viền focus xanh dương mặc định trên <summary> (dùng cho các
           khối <details>/<summary> thu gọn như menu lọc danh mục, cây danh
           mục...) và thay bằng viền oxblood khi điều hướng bàn phím (F2,
           TC08). */
        summary {
            outline: none;
        }
        summary:focus-visible {
            outline: 2px solid #5C2323;
            outline-offset: 2px;
        }
    </style>
</head>
<body>

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-[260px] shrink-0 bg-black/60 backdrop-blur-xl border-r border-white/10 flex flex-col">
        <!-- Logo -->
        <div class="p-6 shrink-0">
            <h1 class="text-white text-2xl font-display flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-green-primary flex items-center justify-center text-white font-bold text-lg">A</span>
                Admin Portal
            </h1>
        </div>

        <!-- Navigation: cuộn riêng khi menu dài hơn màn hình để khối Đăng xuất
             bên dưới luôn hiển thị (sidebar cao cố định h-screen, không cuộn). -->
        <nav class="flex-1 min-h-0 overflow-y-auto px-4 space-y-2 mt-4 pb-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.dashboard') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('products.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="package" class="w-5 h-5"></i> Sản phẩm
            </a>
            <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('categories.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="folder-tree" class="w-5 h-5"></i> Danh mục
            </a>
            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('orders.index') || request()->routeIs('admin.orders.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="package-open" class="w-5 h-5"></i> Đơn hàng
            </a>
            <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('settings.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="settings" class="w-5 h-5"></i> Cài đặt
            </a>
            <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.reports.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i> Báo cáo
            </a>
            <a href="{{ route('admin.finance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.finance.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="circle-dollar-sign" class="w-5 h-5"></i> Tài chính
            </a>
            <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.pages.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="file-text" class="w-5 h-5"></i> Trang tĩnh (CMS)
            </a>
            <a href="{{ route('admin.faqs.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.faqs.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="help-circle" class="w-5 h-5"></i> FAQ
            </a>
            <a href="{{ route('admin.vouchers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.vouchers.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="tag" class="w-5 h-5"></i> Mã giảm giá
            </a>
            <a href="{{ route('admin.posts.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.posts.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="rss" class="w-5 h-5"></i> Blog (CMS)
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.tickets.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="life-buoy" class="w-5 h-5"></i> Ticket hỗ trợ
            </a>
            <a href="{{ route('admin.analytics.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.analytics.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="activity" class="w-5 h-5"></i> Phân tích hành vi
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-full {{ request()->routeIs('admin.users.*') ? 'text-white bg-white/10 border border-white/20' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition-all duration-300 text-sm font-medium no-underline">
                <i data-lucide="users" class="w-5 h-5"></i> Người dùng
            </a>
        </nav>

        <!-- User info + Đăng xuất (luôn ghim ở đáy sidebar) -->
        <div class="p-4 border-t border-white/10 shrink-0">
            <div class="text-white/60 text-xs mb-2 px-2">{{ Auth::user()->name ?? '' }}</div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-full text-white/70 hover:text-white hover:bg-white/10 transition-all text-sm font-medium">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto">
        <div class="p-8">
            @yield('content')
        </div>
    </main>
</div>

<!-- ==== Popup chat với khách hàng ==== -->
<button type="button" id="chatToggleBtn" aria-label="Mở chat hỗ trợ khách hàng" class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-green-primary text-white flex items-center justify-center shadow-lg hover:bg-green-accent transition-colors">
    <i data-lucide="message-circle" class="w-6 h-6"></i>
    <span id="chatUnreadBadge" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] leading-[18px] text-center font-mono border-2 border-white"></span>
</button>

<div id="chatPopup" class="hidden fixed bottom-24 right-6 z-50 w-[560px] max-w-[calc(100vw-32px)] h-[480px] max-h-[calc(100vh-120px)] bg-white border border-green-border rounded-2xl shadow-2xl overflow-hidden">
    <!-- Danh sách khách hàng -->
    <div class="w-[200px] h-full border-r border-green-border flex flex-col overflow-hidden">
        <div class="px-4 py-3 border-b border-green-border flex-none">
            <span class="font-display text-sm text-text-primary">Khách hàng</span>
        </div>
        <div class="px-3 py-2 border-b border-green-border flex-none">
            <div class="relative">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                <input type="text" id="chatUserSearchInput" placeholder="Tìm khách hàng để nhắn tin..." autocomplete="off" class="w-full border border-green-border rounded-full pl-8 pr-3 py-1.5 text-xs outline-none">
            </div>
        </div>
        <div id="chatUserListLabel" class="hidden px-4 pt-2 text-[10px] uppercase tracking-wide text-text-secondary font-mono flex-none">Kết quả tìm kiếm</div>
        <div id="chatUserList" class="flex-1 overflow-y-auto"></div>
    </div>

    <!-- Khung chat -->
    <div class="flex-1 h-full flex flex-col overflow-hidden">
        <div class="px-4 py-3 border-b border-green-border flex-none flex items-center justify-between">
            <span id="chatActiveUserName" class="font-display text-sm text-text-primary">Chọn một khách hàng</span>
            <button type="button" id="chatCloseBtn" aria-label="Đóng chat" class="text-text-secondary hover:text-text-primary">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 flex flex-col gap-2"></div>
        <form id="chatForm" class="flex-none flex gap-2 p-3 border-t border-green-border">
            <input type="text" id="chatInput" placeholder="Nhập tin nhắn trả lời..." autocomplete="off" class="flex-1 min-w-0 border border-green-border rounded-full px-4 py-2 text-sm outline-none">
            <button type="submit" aria-label="Gửi tin nhắn" class="flex-none w-10 h-10 rounded-full bg-green-primary text-white flex items-center justify-center">
                <i data-lucide="send" class="w-4 h-4"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // Khởi tạo icon Lucide
    lucide.createIcons();

    // ==== Banner thông báo flash (thay thế Toastr) ====
    function showAdminToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;color:#fff;font-size:13px;padding:12px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);z-index:9999;transition:opacity .4s;font-family:"Inter",sans-serif;background:' + (isError ? '#B3261E' : '#1C1C1A');
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; }, 2500);
        setTimeout(() => { toast.remove(); }, 3000);
    }

    @if (session('success'))
        showAdminToast(@json(session('success')));
    @endif

    @if (session('error'))
        showAdminToast(@json(session('error')), true);
    @endif

    // ==== Popup chat với khách hàng ====
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    const chatPopup = document.getElementById('chatPopup');
    const chatUserList = document.getElementById('chatUserList');
    const chatUserListLabel = document.getElementById('chatUserListLabel');
    const chatUserSearchInput = document.getElementById('chatUserSearchInput');
    const chatMessagesBox = document.getElementById('chatMessages');
    const chatActiveUserName = document.getElementById('chatActiveUserName');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatUnreadBadge = document.getElementById('chatUnreadBadge');

    // ==== Chấm đỏ báo tổng số tin nhắn khách chưa đọc (poll độc lập, chạy dù popup đóng) ====
    function renderChatUnreadBadge(count) {
        if (!chatUnreadBadge) return;
        if (!count || count <= 0) {
            chatUnreadBadge.classList.add('hidden');
            return;
        }
        chatUnreadBadge.textContent = count > 9 ? '9+' : String(count);
        chatUnreadBadge.classList.remove('hidden');
    }

    function loadChatUnreadCount() {
        fetch('{{ route('admin.chat.unreadCount') }}', {
            headers: { 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(data => renderChatUnreadBadge(data && data.count))
            .catch(() => {});
    }

    if (chatToggleBtn) {
        loadChatUnreadCount();
        setInterval(loadChatUnreadCount, 10000);
    }

    if (chatToggleBtn && chatPopup) {
        const currentAdminId = {{ Auth::check() ? Auth::id() : 'null' }};
        let chatPollingInterval = null;
        let chatSearchDebounceTimer = null;
        let currentChatUserId = null;

        function chatEscapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function chatIsOpen() {
            return !chatPopup.classList.contains('hidden');
        }

        function isSearchingUsers() {
            return chatUserSearchInput && chatUserSearchInput.value.trim().length > 0;
        }

        // Làm mới danh sách bên trái đúng theo chế độ đang xem (đang tìm kiếm hay danh sách hội thoại)
        function refreshUserListPreservingMode() {
            if (isSearchingUsers()) {
                searchUsers(chatUserSearchInput.value.trim());
            } else {
                loadUsers();
            }
        }

        function openChatPopup() {
            chatPopup.classList.remove('hidden');
            chatPopup.classList.add('flex');
            refreshUserListPreservingMode();
            if (!chatPollingInterval) {
                chatPollingInterval = setInterval(function () {
                    if (chatIsOpen() && currentChatUserId) {
                        loadMessages(currentChatUserId);
                    }
                }, 3000);
            }
        }

        function closeChatPopup() {
            chatPopup.classList.add('hidden');
            chatPopup.classList.remove('flex');
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

        // isSearchResult: true khi danh sách đến từ admin.chat.search (khách bất kỳ, có thể
        // chưa từng chat -> không có unread_count/last_message_at) -> ẩn badge chưa đọc.
        function renderChatUsers(users, isSearchResult) {
            if (!chatUserList) return;
            if (chatUserListLabel) {
                chatUserListLabel.classList.toggle('hidden', !isSearchResult);
            }
            if (!users.length) {
                chatUserList.innerHTML = '<p class="text-xs text-text-secondary p-4 text-center">' +
                    (isSearchResult ? 'Không tìm thấy khách hàng nào.' : 'Chưa có khách hàng nào nhắn tin.') +
                    '</p>';
                return;
            }
            chatUserList.innerHTML = users.map(function (user) {
                const isActive = user.id == currentChatUserId;
                const unreadCount = user.unread_count || 0;
                const hasUnread = !isSearchResult && unreadCount > 0;
                const activeClass = isActive
                    ? 'bg-green-primary/10 text-green-primary border-l-4 border-green-primary'
                    : 'text-text-primary border-l-4 border-transparent hover:bg-green-background';
                const nameWeightClass = (isActive || hasUnread) ? 'font-semibold' : 'font-normal';
                const badgeHtml = hasUnread
                    ? '<span class="flex-none min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] leading-[18px] text-center font-mono">' + (unreadCount > 9 ? '9+' : unreadCount) + '</span>'
                    : '';
                return '<button type="button" class="chat-user-item w-full flex items-center justify-between gap-2 text-left px-4 py-3 text-sm truncate ' + activeClass + '" data-user-id="' + user.id + '" data-user-name="' + chatEscapeHtml(user.name) + '">' +
                    '<span class="truncate ' + nameWeightClass + '">' + chatEscapeHtml(user.name) + '</span>' + badgeHtml +
                    '</button>';
            }).join('');
        }

        function loadUsers() {
            fetch('{{ route('admin.chat.users') }}', {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => renderChatUsers(Array.isArray(data) ? data : [], false))
                .catch(() => showAdminToast('Không thể tải danh sách khách hàng.', true));
        }

        function searchUsers(query) {
            fetch('{{ route('admin.chat.search') }}?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => renderChatUsers(Array.isArray(data) ? data : [], true))
                .catch(() => showAdminToast('Không thể tìm kiếm khách hàng.', true));
        }

        if (chatUserSearchInput) {
            chatUserSearchInput.addEventListener('input', function () {
                clearTimeout(chatSearchDebounceTimer);
                const query = chatUserSearchInput.value.trim();
                chatSearchDebounceTimer = setTimeout(function () {
                    if (query.length > 0) {
                        searchUsers(query);
                    } else {
                        loadUsers();
                    }
                }, 300);
            });
        }

        if (chatUserList) {
            chatUserList.addEventListener('click', function (e) {
                const item = e.target.closest('.chat-user-item');
                if (!item) return;
                currentChatUserId = parseInt(item.dataset.userId, 10);
                chatActiveUserName.textContent = item.dataset.userName;
                // loadMessages() khiến server tự đánh dấu tin đã đọc -> chỉ sau khi nó
                // hoàn tất mới làm mới lại danh sách/badge để phản ánh đúng trạng thái mới.
                loadMessages(currentChatUserId).then(function () {
                    refreshUserListPreservingMode();
                    loadChatUnreadCount();
                });
            });
        }

        function renderChatMessages(messages) {
            if (!chatMessagesBox) return;
            if (!messages.length) {
                chatMessagesBox.innerHTML = '<p class="m-auto text-center text-sm text-text-secondary">Chưa có tin nhắn nào.</p>';
                return;
            }
            chatMessagesBox.innerHTML = messages.map(function (msg) {
                const isMine = msg.sender_id == currentAdminId;
                const bubbleClass = isMine
                    ? 'self-end bg-green-primary text-white rounded-2xl rounded-br-md'
                    : 'self-start bg-green-background text-text-primary rounded-2xl rounded-bl-md';
                return '<div class="max-w-[80%] px-3 py-2 text-sm leading-relaxed break-words ' + bubbleClass + '">' + chatEscapeHtml(msg.content) + '</div>';
            }).join('');
            chatMessagesBox.scrollTop = chatMessagesBox.scrollHeight;
            lucide.createIcons();
        }

        function loadMessages(userId) {
            return fetch('{{ url('/admin/chat/messages') }}/' + userId, {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => res.json())
                .then(data => renderChatMessages(Array.isArray(data) ? data : []))
                .catch(() => showAdminToast('Không thể tải tin nhắn.', true));
        }

        if (chatForm) {
            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const content = chatInput.value.trim();
                if (!content) {
                    chatInput.focus();
                    return;
                }
                if (!currentChatUserId) {
                    showAdminToast('Vui lòng chọn một khách hàng để trả lời.', true);
                    return;
                }

                fetch('{{ route('admin.chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ receiver_id: currentChatUserId, content: content }),
                })
                    .then(res => res.json().then(data => ({ ok: res.ok, data: data })))
                    .then(({ ok, data }) => {
                        if (!ok || data.error) {
                            showAdminToast((data && data.error) || 'Gửi tin nhắn thất bại. Vui lòng thử lại.', true);
                            return;
                        }
                        chatInput.value = '';
                        loadMessages(currentChatUserId);
                    })
                    .catch(() => showAdminToast('Gửi tin nhắn thất bại. Vui lòng thử lại.', true));
            });
        }
    }
</script>
@stack('scripts')
</body>
</html>
