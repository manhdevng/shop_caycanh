<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return view('cart.index', compact('cart', 'availableVouchers', 'savableVouchers'));
    }

    // Thêm sản phẩm vào giỏ hàng (gọi qua script trên trang chủ / trang chi tiết)
    public function add(Request $request, Product $product)
    {
        // Sản phẩm đã ẩn -> coi như không tồn tại, kể cả khi request gửi
        // thẳng không qua UI. Xem D3-P5, F9.
        abort_unless($product->is_active, 404);

        $quantity = max(1, (int) $request->input('quantity', 1));

        $hasVariants = $product->variants()->exists();
        $variant = null;

        if ($hasVariants) {
            // Sản phẩm có phân loại -> variant_id bắt buộc và phải thuộc
            // đúng sản phẩm này, không tin variant_id gửi lên mù quáng.
            $variant = $request->filled('variant_id')
                ? $product->variants()->find($request->input('variant_id'))
                : null;

            if (!$variant) {
                $message = 'Vui lòng chọn ' . mb_strtolower($product->effective_variant_label);

                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return back()->with('error', $message);
            }
        }

        $price = (float) ($variant->price ?? $product->base_price);

        if ($price <= 0) {
            $message = 'Sản phẩm liên hệ giá';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // Khoá giỏ hàng theo sản phẩm + phân loại (mỗi size là 1 dòng riêng)
        $key = $variant ? $product->id . '-' . $variant->id : (string) $product->id;

        $cart = session('cart', []);

        // Số lượng đã có trong giỏ (nếu có) cộng với số lượng muốn thêm
        // không được vượt quá tồn kho thật lấy từ DB (không tin số client gửi).
        $currentQuantity = $cart[$key]['quantity'] ?? 0;

        if (($currentQuantity + $quantity) > $product->stock) {
            $message = 'Chỉ còn ' . $product->stock . ' sản phẩm trong kho.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name . ($variant ? ' - ' . $variant->variant_name : ''),
                'quantity' => $quantity,
                'price' => $price,
                'weight' => (int) ($variant->weight ?? $product->weight ?? 200), // Dùng để tính phí vận chuyển GHN
                'image' => $variant->image ?? $product->main_image,
                'category' => optional($product->categories->first())->name,
            ];
        }

        session(['cart' => $cart]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm "' . $cart[$key]['name'] . '" vào giỏ hàng.',
                'cart_count' => count($cart),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Sản phẩm đã được thêm vào giỏ hàng.');
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
