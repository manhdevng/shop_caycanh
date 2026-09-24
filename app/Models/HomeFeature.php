<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeFeature extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'media_type',
        'media_path',
    ];

    /**
     * URL đầy đủ tới ảnh/video đã upload (hoặc null nếu chưa có, để
     * Blade dùng khối placeholder mặc định).
     */
    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? asset('storage/' . $this->media_path) : null;
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }
}
