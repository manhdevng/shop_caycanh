<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminPageController extends Controller
{
    /**
     * 4 trang mặc định được seed sẵn — footer luôn link tới các slug này,
     * không cho phép admin xoá để tránh link chết.
     */
    const PROTECTED_SLUGS = [
        've-chung-toi',
        'lien-he',
        'van-chuyen-doi-tra',
        'chinh-sach-bao-hanh',
    ];

    public function index()
    {
        // Kể cả trang chưa publish — admin cần thấy toàn bộ để quản lý.
        $pages = Page::orderBy('title')->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        Page::create($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Đã tạo trang thành công.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $this->validateData($request, $page);

        // Trang mặc định: footer link cứng theo slug, đổi slug sẽ làm link chết.
        if (in_array($page->slug, self::PROTECTED_SLUGS, true) && $validated['slug'] !== $page->slug) {
            throw ValidationException::withMessages([
                'slug' => 'Không thể đổi đường dẫn của trang mặc định hệ thống (đang được liên kết ở footer).',
            ]);
        }

        $page->update($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Đã cập nhật trang.');
    }

    public function destroy(Page $page)
    {
        if (in_array($page->slug, self::PROTECTED_SLUGS, true)) {
            return back()->with('error', 'Không thể xoá trang mặc định của hệ thống.');
        }

        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Đã xoá trang.');
    }

    /**
     * Rule validate dùng chung cho store/update. is_published là checkbox —
     * khi không tick, trình duyệt không gửi field này lên nên phải tự suy
     * ra false thay vì để validate 'required'.
     */
    private function validateData(Request $request, ?Page $page = null): array
    {
        $uniqueSlugRule = 'unique:pages,slug' . ($page ? ',' . $page->id : '');

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $uniqueSlugRule,
            ],
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
        ], [
            'slug.required' => 'Vui lòng nhập đường dẫn (slug) cho trang.',
            'slug.regex' => 'Đường dẫn chỉ được chứa chữ thường, số và dấu gạch ngang (ví dụ: ve-chung-toi).',
            'slug.unique' => 'Đường dẫn này đã được sử dụng cho trang khác.',
            'title.required' => 'Vui lòng nhập tiêu đề trang.',
            'content.required' => 'Vui lòng nhập nội dung trang.',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        // Lọc HTML theo allowlist ngay khi lưu (chống stored XSS).
        $validated['content'] = HtmlSanitizer::clean($validated['content']);

        return $validated;
    }
}
