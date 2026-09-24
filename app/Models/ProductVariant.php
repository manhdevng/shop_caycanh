<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'price',
        'image',
        'weight',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
