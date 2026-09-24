<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\MomoController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GHNWebhookController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\AdminFaqController;
use App\Http\Controllers\Admin\FinanceController;

// ----------------------------------------------------
// Đăng ký / Đăng nhập / Đăng xuất (public)
// ----------------------------------------------------
Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Đăng nhập bằng Google (P5.2) — public, không cần auth
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('social.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('social.google.callback');

// ----------------------------------------------------
// Xác thực Email (bắt buộc đăng nhập nhưng KHÔNG bắt buộc verified,
// vì đây chính là trang mà người dùng chưa verify sẽ bị đưa tới)
// ----------------------------------------------------
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('shop.index')->with('success', 'Xác thực email thành công! Chào mừng bạn.');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('success', 'Đã gửi lại email xác thực!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// ----------------------------------------------------
// Webhook/callback bên thứ ba (MoMo) — KHÔNG dùng middleware 'auth' vì
// MoMo gọi thẳng từ máy chủ của họ, không mang theo session của người dùng.
// Route POST /momo/ipn được loại trừ CSRF trong bootstrap/app.php.
// ----------------------------------------------------
Route::post('/momo/ipn', [MomoController::class, 'ipn'])->name('momo.ipn');
Route::get('/momo/callback', [MomoController::class, 'callback'])->name('momo.callback');

// GHN gọi thẳng từ máy chủ của họ báo cập nhật trạng thái vận đơn — không có session người dùng.
// Route POST /ghn/webhook được loại trừ CSRF trong bootstrap/app.php.
Route::post('/ghn/webhook', [GHNWebhookController::class, 'handle'])->name('ghn.webhook');

// ----------------------------------------------------
// Khu vực CÔNG KHAI — cửa hàng (KHÔNG cần đăng nhập)
// Khách vãng lai phải xem được trang chủ và trang chi tiết sản phẩm
// trước khi quyết định đăng ký; chỉ các hành động cần danh tính
// (giỏ hàng, thanh toán, đánh giá) mới nằm sau 'auth'.
// ----------------------------------------------------
Route::get('/', [ShopController::class, 'index'])->name('shop.index');
Route::get('/san-pham/{product}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/ban-chay', [ShopController::class, 'bestSellers'])->name('shop.bestSellers');
Route::get('/trang/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/cam-nang', [PostController::class, 'index'])->name('posts.index');
Route::get('/cam-nang/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('/san-ma-giam-gia', [VoucherController::class, 'browse'])->name('vouchers.browse');
Route::get('/hoi-dap', [FaqController::class, 'index'])->name('faq.index');

// ----------------------------------------------------
// Khu vực KHÁCH HÀNG (cần đăng nhập + đã xác thực email)
// ----------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    // Đánh giá sản phẩm: bắt buộc đăng nhập (giữ nguyên trong nhóm này)
    Route::post('/san-pham/{product}/danh-gia', [ReviewController::class, 'store'])->name('reviews.store');

    // Hồ sơ cá nhân
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Giỏ hàng
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');

    // Danh sách yêu thích
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    // Lịch sử duyệt / mua hàng (P1.1)
    Route::get('/lich-su', [HistoryController::class, 'index'])->name('history.index');

    // Ticket hỗ trợ (P3.3)
    Route::get('/ho-tro', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/ho-tro/tao', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/ho-tro', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/ho-tro/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/ho-tro/{ticket}/tra-loi', [TicketController::class, 'reply'])->name('tickets.reply');

    // Mã giảm giá (áp dụng ở giỏ hàng/checkout)
    Route::post('/voucher/apply', [VoucherController::class, 'apply'])->name('voucher.apply');
    Route::post('/voucher/remove', [VoucherController::class, 'remove'])->name('voucher.remove');
    Route::post('/voucher/save', [VoucherController::class, 'save'])->name('voucher.save');
    Route::get('/vi-voucher', [VoucherController::class, 'wallet'])->name('vouchers.wallet');

    // Thông báo (chuông header) — đánh dấu đã xem
    Route::post('/thong-bao/danh-dau-da-xem', [NotificationController::class, 'markSeen'])->name('notifications.mark-seen');

    // Thanh toán / Đặt hàng (tính phí ship qua GHN)
    Route::get('/checkout', [OrderController::class, 'index'])->name('checkout');
    Route::post('/checkout', [OrderController::class, 'store'])->name('orders.store');

    // Đơn hàng của tôi
    Route::get('/orders', [OrderController::class, 'orderHistory'])->name('orders.history');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Thanh toán MoMo (thẻ nội địa / thẻ quốc tế / ví MoMo)
    Route::get('/orders/{order}/start-momo', [MomoController::class, 'start'])->name('momo.start');
    Route::get('/orders/{order}/pay-momo-again', [MomoController::class, 'payAgain'])->name('momo.pay');
    Route::post('/orders/{order}/momo/check-status', [MomoController::class, 'checkStatus'])->name('momo.checkStatus');

    // Tra cứu địa chỉ + tính phí ship GHN (dùng bởi trang checkout qua AJAX)
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/provinces', [OrderController::class, 'getProvinces'])->name('provinces');
        Route::get('/districts/{provinceId}', [OrderController::class, 'getDistricts'])->name('districts');
        Route::get('/wards/{districtId}', [OrderController::class, 'getWards'])->name('wards');
        Route::post('/calculate-fee', [OrderController::class, 'getShippingFee'])->name('fee');
    });

    // Chat trực tiếp với Admin
    Route::post('/chat/send', [UserChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/messages', [UserChatController::class, 'getMessages'])->name('chat.messages');
    Route::get('/chat/unread-count', [UserChatController::class, 'unreadCount'])->name('chat.unreadCount');
});

// ----------------------------------------------------
// Khu vực ADMIN (cần đăng nhập + đã xác thực email + role admin)
// Giữ nguyên toàn bộ tên route cũ (products.*, categories.*...),
// chỉ bọc thêm middleware + đổi prefix URL sang /admin/...
// ----------------------------------------------------
Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Đổi thứ tự hiển thị danh mục (lên/xuống) — đổi sort_order với anh em
    // liền kề cùng cấp. Phải đặt TRƯỚC Route::resource('categories') để
    // không bị nuốt bởi route show/{category}.
    Route::patch('/categories/{category}/move/{direction}', [CategoryController::class, 'move'])
        ->whereIn('direction', ['up', 'down'])
        ->name('categories.move');

    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);

    // Thùng rác sản phẩm (soft delete)
    Route::get('/products-trashed', [ProductController::class, 'trashed'])->name('products.trashed');
    Route::patch('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.forceDelete');

    // Thêm route cho các mục mới trên Sidebar
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('admin.orders.cancel');

    // Đối soát chuyển khoản ngân hàng (C4.1). Chỉ áp dụng cho đơn đang ở
    // trạng thái 'awaiting_transfer'; hai method này ĐÃ tồn tại thật trong
    // AdminOrderController (đã kiểm chứng bằng method_exists) — trước đây
    // dự án từng có route confirmTransfer trỏ vào method không tồn tại gây 500.
    Route::post('/orders/{order}/confirm-transfer', [AdminOrderController::class, 'confirmTransfer'])->name('admin.orders.confirmTransfer');
    Route::post('/orders/{order}/reject-transfer', [AdminOrderController::class, 'rejectTransfer'])->name('admin.orders.rejectTransfer');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/home-features', [SettingsController::class, 'updateHomeFeatures'])->name('settings.home-features.update');

    // Chat với khách hàng
    Route::get('/chat/users', [AdminChatController::class, 'getUsers'])->name('admin.chat.users');
    Route::get('/chat/unread-count', [AdminChatController::class, 'unreadCount'])->name('admin.chat.unreadCount');
    Route::get('/chat/search', [AdminChatController::class, 'search'])->name('admin.chat.search');
    Route::get('/chat/messages/{userId}', [AdminChatController::class, 'getMessages'])->name('admin.chat.messages');
    Route::post('/chat/send', [AdminChatController::class, 'send'])->name('admin.chat.send');

    // Báo cáo doanh thu
    Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/charts', [AdminReportController::class, 'charts'])->name('admin.reports.charts');

    // Quản lý người dùng
    Route::resource('users', AdminUserController::class)->names('admin.users');

    // CMS trang tĩnh (P4.1)
    Route::resource('pages', AdminPageController::class)->names('admin.pages');

    // Mã giảm giá / Voucher (P2.1)
    Route::resource('vouchers', AdminVoucherController::class)->names('admin.vouchers');

    // Blog / Cẩm nang (P4.2)
    Route::resource('posts', AdminPostController::class)->names('admin.posts');

    // FAQ động (P3.1)
    Route::resource('faqs', AdminFaqController::class)->names('admin.faqs');

    // Ticket hỗ trợ (P3.3)
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('admin.tickets.show');
    Route::post('/tickets/{ticket}/tra-loi', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
    Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('admin.tickets.updateStatus');

    // Phân tích hành vi người dùng (P6.1)
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('admin.analytics.index');
});

// ----------------------------------------------------
// Báo cáo giao dịch thanh toán - Finance
// ----------------------------------------------------
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finance/transactions', [FinanceController::class, 'transactions'])->name('finance.transactions');
    Route::patch('/finance/{order}/status', [FinanceController::class, 'updateStatus'])->name('finance.update-status');
});
