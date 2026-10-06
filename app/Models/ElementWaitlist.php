<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Email đăng ký nhận tin khi có thêm cây hợp một hành. Unique theo
 * (email, element). Không lưu ngày sinh.
 */
class ElementWaitlist extends Model
{
    protected $fillable = ['email', 'element'];
}
