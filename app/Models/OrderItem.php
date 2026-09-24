<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'price', 'variant_id', 'variant_name', 'product_name',
    ];

    // Một mục sản phẩm thuộc về một đơn hàng
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Một mục sản phẩm liên kết với 1 sản phẩm. Dùng withTrashed() vì
    // Product dùng SoftDeletes — đơn hàng cũ có thể trỏ tới sản phẩm đã bị
    // xóa mềm, nếu không sẽ mất luôn thông tin sản phẩm khi hiển thị lại.
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    // Phân loại đã mua (có thể null nếu sản phẩm không có phân loại, hoặc
    // phân loại đã bị xoá sau đó — xem variant_name để hiện tên snapshot).
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
