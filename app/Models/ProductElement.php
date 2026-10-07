<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Một dòng gán hành phong thủy cho sản phẩm (bảng product_elements). Mã hành
 * hợp lệ nằm ở Product::ELEMENTS; ghi qua Product::syncElements() để không
 * lọt mã lạ.
 */
class ProductElement extends Model
{
    protected $fillable = ['product_id', 'element'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
