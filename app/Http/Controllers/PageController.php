<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    /**
     * Hiển thị 1 trang tĩnh (Về chúng tôi, Liên hệ, ...) cho khách truy cập
     * công khai theo slug. Chỉ hiện trang đã publish — chưa publish hoặc
     * không tồn tại đều trả 404 (không lộ slug nháp cho khách).
     */
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
