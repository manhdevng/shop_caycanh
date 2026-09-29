<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một lượt bấm "Hữu ích" của khách cho một đánh giá (bảng
 * review_helpful_votes, unique(review_id, user_id) nên mỗi khách chỉ bấm
 * được 1 lần cho mỗi đánh giá).
 *
 * Số đếm hiển thị nằm ở `reviews.helpful_count` (denormalised) để danh sách
 * đánh giá không phải COUNT() từng dòng; hai nơi này được cập nhật cùng nhau
 * trong 1 transaction — xem ReviewController::toggleHelpful().
 */
class ReviewHelpfulVote extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
