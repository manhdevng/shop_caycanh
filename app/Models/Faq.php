<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'placement',
        'sort_order',
        'is_published',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Vị trí hiển thị (faqs.placement).
    const PLACEMENT_GENERAL = 'general';
    const PLACEMENT_PRODUCT = 'product';

    /**
     * Nhãn tiếng Việt cho faqs.placement.
     */
    const PLACEMENT_LABELS = [
        'general' => 'Chung (trang chủ & trang Hỏi đáp)',
        'product' => 'Trang chi tiết sản phẩm',
    ];

    /**
     * Chỉ lấy FAQ đã publish.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Lọc theo vị trí hiển thị (general/product).
     */
    public function scopePlacement($query, string $placement)
    {
        return $query->where('placement', $placement);
    }
}
