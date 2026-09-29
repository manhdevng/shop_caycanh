<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Notifications\ReviewReplied;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Kiểm duyệt đánh giá (admin): lọc / ẩn - hiện / phản hồi.
 *
 * Admin KHÔNG xoá đánh giá và KHÔNG sửa nội dung của khách — chỉ ẩn khỏi
 * trang sản phẩm (`is_hidden`) và trả lời công khai (`shop_reply`). Giữ
 * nguyên bản gốc để còn đối chiếu khi có khiếu nại.
 */
class AdminReviewController extends Controller
{
    /** Bộ lọc "có ảnh / không ảnh / bị ẩn"… đều là tuỳ chọn, mặc định không lọc. */
    public function index(Request $request)
    {
        $filters = [
            'rating' => $request->integer('rating') ?: null,
            'media' => $request->query('media'),        // 'co' | 'khong' | null
            'hidden' => $request->query('hidden'),      // 'an' | 'hien' | null
            'q' => trim((string) $request->query('q')),
        ];

        $reviews = Review::query()
            ->with(['product:id,name,main_image', 'user:id,name,email', 'images'])
            ->when($filters['rating'], fn ($q, $rating) => $q->where('rating', $rating))
            ->when($filters['media'] === 'co', fn ($q) => $q->whereHas('images'))
            ->when($filters['media'] === 'khong', fn ($q) => $q->whereDoesntHave('images'))
            ->when($filters['hidden'] === 'an', fn ($q) => $q->where('is_hidden', true))
            ->when($filters['hidden'] === 'hien', fn ($q) => $q->where('is_hidden', false))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$filters['q'].'%'));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'filters'));
    }

    /**
     * Ẩn / hiện một đánh giá. Ẩn rồi thì nó biến mất khỏi trang sản phẩm và
     * không còn được tính vào điểm trung bình (ShopController dùng
     * Review::visible()).
     */
    public function toggle(Review $review)
    {
        $review->update(['is_hidden' => ! $review->is_hidden]);

        return back()->with('success', $review->is_hidden
            ? 'Đã ẩn đánh giá #'.$review->id.' khỏi trang sản phẩm.'
            : 'Đã hiện lại đánh giá #'.$review->id.'.');
    }

    /**
     * Shop trả lời đánh giá (1 phản hồi cho mỗi đánh giá, sửa lại được bằng
     * cách gửi nội dung mới). Gửi thông báo cho khách ở chuông.
     */
    public function reply(Request $request, Review $review)
    {
        $validated = $request->validate([
            'shop_reply' => ['required', 'string', 'max:1000'],
        ], [
            'shop_reply.required' => 'Vui lòng nhập nội dung phản hồi.',
            'shop_reply.max' => 'Phản hồi không được vượt quá 1000 ký tự.',
        ]);

        $review->update([
            'shop_reply' => $validated['shop_reply'],
            'shop_replied_at' => now(),
        ]);

        $this->notifyCustomer($review);

        return back()->with('success', 'Đã gửi phản hồi cho đánh giá #'.$review->id.'.');
    }

    /**
     * Báo khách rằng shop vừa trả lời. Lỗi gửi thông báo chỉ ghi log — không
     * được phép làm hỏng thao tác phản hồi của admin.
     */
    private function notifyCustomer(Review $review): void
    {
        try {
            $review->loadMissing(['user', 'product']);

            $review->user?->notify(new ReviewReplied($review));
        } catch (\Throwable $e) {
            Log::error('Gửi thông báo phản hồi đánh giá thất bại: '.$e->getMessage(), [
                'review_id' => $review->id,
            ]);
        }
    }
}
