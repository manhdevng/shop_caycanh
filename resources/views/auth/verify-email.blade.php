<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác thực Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Gloock&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#CED1C3] min-h-screen flex items-center justify-center font-sans" style="font-family:'Inter',sans-serif">
    <div class="bg-white rounded-[32px] p-10 w-full max-w-md border border-[#8C9680] shadow-sm text-center">
        <h2 class="text-3xl mb-4" style="font-family:'Gloock',serif">Xác thực Email</h2>
        <p class="text-gray-600 mb-6">Cảm ơn bạn đã đăng ký! Trước khi bắt đầu, vui lòng kiểm tra hộp thư email và nhấn vào liên kết xác thực chúng tôi vừa gửi.</p>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-4 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-4 text-sm">{{ session('message') }}</div>
        @endif

        <p class="text-sm text-gray-600 mb-4">Không nhận được email? Bấm nút bên dưới để gửi lại.</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="w-full bg-[#B6CC9D] hover:bg-[#A1B887] rounded-full py-3 font-semibold transition-colors">
                Gửi lại email xác thực
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-gray-500 underline">Đăng xuất</button>
        </form>
    </div>
</body>
</html>
