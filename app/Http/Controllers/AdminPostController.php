<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;

class AdminPostController extends Controller
{
    public function index()
    {
        // Kể cả bài chưa publish — admin cần thấy toàn bộ để quản lý.
        $posts = Post::orderByDesc('created_at')->paginate(20);

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.posts.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        // author_id luôn gán theo người đang đăng nhập, không cho nhập tay.
        $validated['author_id'] = auth()->id();

        if ($validated['is_published'] && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        Post::create($validated);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã tạo bài viết.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $this->validateData($request, $post);

        if ($validated['is_published'] && empty($post->published_at) && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã xoá bài viết.');
    }

    /**
     * Rule validate dùng chung cho store/update. is_published là checkbox —
     * khi không tick, trình duyệt không gửi field này lên nên phải tự suy
     * ra false thay vì để validate 'required'.
     */
    private function validateData(Request $request, ?Post $post = null): array
    {
        $uniqueSlugRule = 'unique:posts,slug' . ($post ? ',' . $post->id : '');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $uniqueSlugRule,
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'is_published' => 'nullable|boolean',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'slug.required' => 'Vui lòng nhập đường dẫn (slug) cho bài viết.',
            'slug.regex' => 'Đường dẫn chỉ được chứa chữ thường, số và dấu gạch ngang (ví dụ: huong-dan-cham-soc-cay).',
            'slug.unique' => 'Đường dẫn này đã được sử dụng cho bài viết khác.',
            'excerpt.max' => 'Mô tả ngắn không được vượt quá 500 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        // Lọc HTML theo allowlist ngay khi lưu (chống stored XSS).
        $validated['content'] = HtmlSanitizer::clean($validated['content']);

        return $validated;
    }
}
