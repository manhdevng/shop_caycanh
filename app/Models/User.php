<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'points', 'tier', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'notifications_last_seen_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists');
    }

    public function productViews()
    {
        return $this->hasMany(ProductView::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Ví voucher của user: các mã đã lưu (bảng user_voucher). Pivot mang
     * theo saved_at, used_at (null = chưa dùng), order_id.
     */
    public function savedVouchers()
    {
        return $this->belongsToMany(Voucher::class, 'user_voucher')
            ->withPivot(['saved_at', 'used_at', 'order_id'])
            ->withTimestamps();
    }
}
