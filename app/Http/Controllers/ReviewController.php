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

        // Chỉ cho phép đánh giá khi khách đã mua VÀ đã NHẬN được sản phẩm:
        // đơn của chính họ, đã trả tiền/COD (PAID_OR_COD_STATUSES) và GHN đã
        // báo giao thành công.
        //
        // Sửa lỗi L12: điều kiện cũ là whereIn('status', ['paid','cod_ordered'])
        // nên đơn COD bị CodSettlementService đổi sang 'cod_paid' sau khi giao
        // lại KHÔNG đánh giá được (đúng lúc đáng ra được đánh giá nhất), trong
        // khi đơn vừa đặt chưa nhận hàng thì lại đánh giá được.
        $purchasedOrder = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', Order::PAID_OR_COD_STATUSES)
            ->where('shipping_status', 'delivered')
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
