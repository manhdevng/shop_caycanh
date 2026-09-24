<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Danh sách bài viết cẩm nang đã xuất bản (public).
     */
    public function index(Request $request)
    {
        $posts = Post::where('is_published', true)
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('posts.index', compact('posts'));
    }

    /**
     * Chi tiết 1 bài viết theo slug — chỉ hiển thị bài đã xuất bản.
     */
    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }
}
