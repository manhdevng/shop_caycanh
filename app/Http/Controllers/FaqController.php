<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    /**
     * Trang Hỏi đáp công khai — chỉ hiện FAQ nhóm "general" (chung), đã
     * publish, sắp theo sort_order rồi id để cùng sort_order không nhảy
     * lung tung.
     */
    public function index()
    {
        $faqs = Faq::published()
            ->placement(Faq::PLACEMENT_GENERAL)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('faq.index', compact('faqs'));
    }
}
