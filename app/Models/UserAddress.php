<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'province_id',
        'province_name',
        'district_id',
        'district_name',
        'ward_code',
        'ward_name',
        'address',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'province_id' => 'integer',
            'district_id' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // "Số nhà, Phường, Quận, Tỉnh" — bỏ qua phần tên còn trống.
    public function fullAddress(): string
    {
        return collect([$this->address, $this->ward_name, $this->district_name, $this->province_name])
            ->filter()
            ->implode(', ');
    }

    // Đặt làm mặc định: bỏ cờ của các địa chỉ khác cùng khách (mỗi khách chỉ 1 mặc định).
    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::where('user_id', $this->user_id)->whereKeyNot($this->id)->update(['is_default' => false]);
            $this->update(['is_default' => true]);
        });
    }
}
