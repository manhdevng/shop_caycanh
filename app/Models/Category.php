<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'scope', 'image', 'sort_order'];

    /**
     * Phạm vi của nhóm danh mục gốc. Chỉ có ý nghĩa ở nhóm gốc (parent_id
     * null); danh mục con lấy theo nhóm cha, xem effectiveScope(). Xem D1.
     */
    const SCOPES = [
        'plant' => 'Cây cảnh',
        'flower' => 'Hoa',
        'both' => 'Dùng chung',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        // Danh mục con luôn trả về theo đúng thứ tự hiển thị (sort_order, id)
        // để menu/trang danh mục không cần orderBy lại ở nơi gọi.
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Sắp xếp theo thứ tự hiển thị thủ công (sort_order), id làm tie-break
     * khi sort_order bằng nhau.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Phạm vi thực sự áp dụng cho danh mục này: danh mục con lấy theo scope
     * của nhóm cha; nhóm gốc lấy scope của chính nó. Xem D1.
     */
    public function effectiveScope(): string
    {
        if ($this->parent_id !== null) {
            return $this->parent?->scope ?? $this->scope;
        }

        return $this->scope;
    }
}
