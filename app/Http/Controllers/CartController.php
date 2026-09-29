<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CartService;
use App\Support\VoucherPricing;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    // Hiển thị giỏ hàng
    public function index()
    {
        $cart = session('cart', []);

        // Trang giỏ hàng cũng phân biệt 2 nhóm mã giống hệt
        // User\OrderController::index(): $availableVouchers là mã đã lưu vào
        // ví và CHƯA dùng (nhóm để bấm "Dùng"); $savableVouchers là mã còn
        // hiệu lực nhưng CHƯA lưu (nhóm để bấm "Lưu"). Khách chưa đăng nhập
        // không có ví nên $availableVouchers rỗng, $savableVouchers là toàn
        // bộ mã còn hiệu lực.
        $user = Auth::user();

        if ($user !== null) {
            $availableVouchers = Voucher::available()
                ->with(['products:id,name', 'categories:id,name'])
                ->whereHas('savedByUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereNull('user_voucher.used_at');
                })
                ->get();

            $savedVoucherIds = DB::table('user_voucher')->where('user_id', $user->id)->pluck('voucher_id');

            $savableVouchers = Voucher::available()
                ->with(['products:id,name', 'categories:id,name'])
                ->when($savedVoucherIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $savedVoucherIds))
                ->get();
        } else {
            $availableVouchers = collect();
            $savableVouchers = Voucher::available()->with(['products:id,name', 'categories:id,name'])->get();
        }

        // L14: giỏ trống sau khi mua xong thì khách không biết xem đơn ở đâu.
        // Đếm số đơn đang trong quá trình (chưa huỷ, chưa hoàn thành) để view
        // hiện lối tắt "Theo dõi N đơn đang xử lý" thay vì chỉ có nút "Tiếp
        // tục mua sắm".
        $pendingOrderCount = $user === null ? 0 : Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->whereNull('completed_at')
            ->count();

        return view('cart.index', compact('cart', 'availableVouchers', 'savableVouchers', 'pendingOrderCount'));
    }

    // Thêm sản phẩm vào giỏ hàng (gọi qua script trên trang chủ / trang chi tiết).
    // Toàn bộ luật kiểm tra (còn bán, phân loại, giá, tồn kho) nằm trong
    // CartService để nút "Mua ngay"/"Mua với voucher" dùng chung — xem
    // app/Services/CartService.php.
    public function add(Request $request, Product $product, CartService $cartService)
    {
        // Sản phẩm đã ẩn -> coi như không tồn tại, kể cả khi request gửi
        // thẳng không qua UI. Xem D3-P5, F9.
        abort_unless($product->is_active, 404);

        try {
            $result = $cartService->putItem(
                $product,
                $request->filled('variant_id') ? (int) $request->input('variant_id') : null,
                (int) $request->input('quantity', 1),
                CartService::MODE_ADD,
            );
        } catch (ValidationException $e) {
            return $this->cartError($request, collect($e->errors())->flatten()->first());
        } catch (DomainException $e) {
            return $this->cartError($request, $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm "'.$result['item']['name'].'" vào giỏ hàng.',
                'cart_count' => $result['cart_count'],
                'cart_key' => $result['key'],
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Sản phẩm đã được thêm vào giỏ hàng.');
    }

    /**
     * "Mua ngay" và "Mua với voucher" — một endpoint, phân biệt bằng
     * `with_voucher`.
     *
     * Sửa 2 lỗi của nút "Mua ngay" cũ:
     * - V1: trước đây thêm vào giỏ rồi mở /checkout trống tay -> thanh toán
     *   CẢ GIỎ, kể cả hàng cũ. Giờ redirect kèm ?items[]=<cart_key> để
     *   checkout chỉ lấy đúng dòng này.
     * - V2: trước đây cộng dồn số lượng (giỏ có 2, bấm mua ngay 1 -> 3). Giờ
     *   dùng CartService::MODE_SET, đặt đúng số khách vừa chọn.
     *
     * Với `with_voucher=1`: mã giảm được tính LẠI Ở SERVER
     * (VoucherPricing::bestFor) — không nhận số tiền hay mã nào từ trình
     * duyệt, vì giá hiển thị có thể đã cũ hoặc bị sửa.
     */
    public function buyNow(Request $request, Product $product, CartService $cartService)
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'with_voucher' => ['nullable', 'boolean'],
        ], [
            'quantity.min' => 'Số lượng phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng quá lớn.',
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $withVoucher = (bool) ($validated['with_voucher'] ?? false);

        try {
            $result = $cartService->putItem(
                $product,
                isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
                $quantity,
                CartService::MODE_SET,
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $key = $result['key'];
        $line = [$key => $result['item']];
        $user = $request->user();

        if ($withVoucher) {
            $this->applyBestVoucherForLine($product, $result['item'], $line, $user);
        } else {
            // Nút "Mua ngay" thường: mã đang áp trong session có thể không hợp
            // lệ với RIÊNG dòng hàng này (ví dụ mã chỉ áp cho danh mục khác)
            // -> gỡ ra, để checkout không hiện giảm giá rồi lại bị từ chối.
            $this->forgetVoucherIfInvalidFor($line, $user);
        }

        return redirect()->route('checkout', ['items' => [$key]]);
    }

    /**
     * Tìm mã tốt nhất cho đúng dòng hàng vừa chọn, tự lưu vào ví rồi ghi vào
     * session('voucher') cùng cấu trúc với VoucherController@apply (checkout
     * đọc chung khoá này).
     *
     * Không tìm được mã hợp lệ thì KHÔNG chặn khách mua — chỉ flash lời nhắc.
     */
    private function applyBestVoucherForLine(Product $product, array $item, array $line, ?User $user): void
    {
        $best = VoucherPricing::bestFor($product, (float) $item['price'], (int) $item['quantity'], $user);

        if ($best === null) {
            $this->forgetVoucherIfInvalidFor($line, $user);
            session()->flash('error', 'Mã giảm giá không còn áp dụng cho sản phẩm này, bạn vẫn có thể thanh toán.');

            return;
        }

        $voucher = $best['voucher'];

        // Tự lưu mã vào ví giúp khách (giống VoucherController@apply): khách
        // bấm 1 nút chứ không phải "Lưu" rồi "Dùng" 2 lần.
        if ($user !== null) {
            DB::table('user_voucher')->insertOrIgnore([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'saved_at' => now(),
                'used_at' => null,
                'order_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Kiểm tra lần cuối bằng đúng hàm mà checkout dùng, để không bao giờ
        // xảy ra chuyện áp được ở đây nhưng bị từ chối ở trang thanh toán.
        $error = $voucher->validationErrorForCart($line, $user);

        if ($error !== null) {
            $this->forgetVoucherIfInvalidFor($line, $user);
            session()->flash('error', $error.' Bạn vẫn có thể thanh toán đơn này.');

            return;
        }

        session(['voucher' => [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'discount_amount' => $voucher->calculateDiscountAmount((float) $voucher->eligibleSubtotal($line)),
        ]]);

        session()->flash('success', 'Đã áp mã "'.$voucher->code.'", bạn được giảm '
            .number_format($best['discount'], 0, ',', '.').'đ.');
    }

    /** Gỡ mã đang áp trong session nếu nó không dùng được cho $line. */
    private function forgetVoucherIfInvalidFor(array $line, ?User $user): void
    {
        $applied = session('voucher');

        if (! is_array($applied) || empty($applied['id'])) {
            return;
        }

        $voucher = Voucher::find($applied['id']);

        if ($voucher === null || $voucher->validationErrorForCart($line, $user) !== null) {
            session()->forget('voucher');
        }
    }

    /** Trả lỗi giỏ hàng theo đúng định dạng mà caller mong đợi (JSON hay redirect). */
    private function cartError(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    // Cập nhật số lượng của 1 dòng trong giỏ hàng
    public function update(Request $request, string $id)
    {
        $cart = session('cart', []);

        if (!isset($cart[$id])) {
            $message = 'Sản phẩm không có trong giỏ hàng.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 404);
            }

            return back()->with('error', $message);
        }

        // Định dạng số lượng phải là số nguyên hợp lệ
        $request->validate([
            'quantity' => ['required', 'integer'],
        ], [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng không hợp lệ.',
        ]);

        $product = Product::find($cart[$id]['product_id']);

        // Sản phẩm đã bị xoá/ẩn khỏi hệ thống -> dọn khỏi giỏ luôn
        if (!$product || !$product->is_active) {
            unset($cart[$id]);
            session(['cart' => $cart]);

            $message = 'Sản phẩm không còn tồn tại.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 404);
            }

            return back()->with('error', $message);
        }

        $quantity = (int) $request->input('quantity');

        // Số lượng phải nằm trong khoảng 1..stock, dùng tồn kho thật từ DB
        if ($quantity <= 0) {
            $message = 'Số lượng phải lớn hơn 0.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($quantity > $product->stock) {
            $message = 'Chỉ còn ' . $product->stock . ' sản phẩm trong kho.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $cart[$id]['quantity'] = $quantity;
        session(['cart' => $cart]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'quantity' => $quantity,
                'cart_count' => count($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã cập nhật số lượng sản phẩm.');
    }

    // Xoá sản phẩm khỏi giỏ hàng
    public function remove(Request $request, string $id)
    {
        $cart = session('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session(['cart' => $cart]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'cart_count' => count($cart)]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã xoá sản phẩm khỏi giỏ hàng.');
    }
}
