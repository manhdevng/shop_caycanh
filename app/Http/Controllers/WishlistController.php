<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Danh sách sản phẩm yêu thích của user hiện tại, phân trang giống ShopController::index()
    public function index(Request $request)
    {
        $products = $request->user()
            ->wishlistedProducts()
            ->with(['categories', 'variants'])
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('wishlist.index', compact('products'));
    }

    // Bật/tắt yêu thích 1 sản phẩm cho user hiện tại
    public function toggle(Request $request, Product $product)
    {
        // Sản phẩm đã ẩn/xoá mềm -> coi như không tồn tại (giống CartController::add()).
        abort_unless($product->is_active, 404);

        $userId = $request->user()->id;

        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
            $message = 'Đã bỏ "' . $product->name . '" khỏi danh sách yêu thích.';
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            $liked = true;
            $message = 'Đã thêm "' . $product->name . '" vào danh sách yêu thích.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
