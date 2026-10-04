<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Một dòng gán mùa cho sản phẩm (bảng product_seasons). Mã mùa hợp lệ nằm ở
 * Product::SEASONS; ghi qua Product::syncSeasons() để không lọt mã lạ.
 */
class ProductSeason extends Model
{
    protected $fillable = ['product_id', 'season'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
