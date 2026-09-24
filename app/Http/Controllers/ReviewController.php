<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Gửi đánh giá (hoặc sửa đánh giá cũ nếu khách đã từng đánh giá sản phẩm này)
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.min' => 'Vui lòng chọn từ 1 đến 5 sao.',
            'rating.max' => 'Vui lòng chọn từ 1 đến 5 sao.',
            'comment.max' => 'Nhận xét không được vượt quá 1000 ký tự.',
        ]);

        // Chỉ cho phép đánh giá nếu người dùng đã mua sản phẩm này thành công
        // (đơn hàng của chính họ, trạng thái đã thanh toán hoặc đã đặt COD).
        $purchasedOrder = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'cod_ordered'])
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->latest('id')
            ->first();

        if (! $purchasedOrder) {
            $message = 'Bạn cần mua và nhận sản phẩm này trước khi có thể đánh giá.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('shop.show', $product)
                ->with('error', $message);
        }

        $product->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
                'order_id' => $purchasedOrder->id,
            ]
        );

        return redirect()->route('shop.show', $product)
            ->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
    }
}
