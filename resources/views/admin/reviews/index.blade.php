@extends('layouts.app')

@section('title', 'Đánh giá · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Đánh Giá Sản Phẩm</h2>
    </div>

    {{--
        GHI CHÚ CHO BACKEND (AdminReviewController@index): $filters là mảng
        các giá trị lọc đang áp dụng, dùng đúng 4 khoá dưới đây để form giữ
        lại lựa chọn sau khi submit — 'star' (chuỗi '1'..'5' hoặc rỗng),
        'has_image' ('1' hoặc rỗng), 'hidden' ('1' ẩn / '0' hiện / rỗng =
        tất cả), 'q' (chuỗi tìm theo tên sản phẩm).
    --}}
    <form action="{{ route('admin.reviews.index') }}" method="GET" class="flex flex-wrap items-end gap-4 mb-8 bg-[#f8f9f5] border border-green-border/30 rounded-2xl p-5">
        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Tìm sản phẩm</label>
            <input type="text" name="q" value="{{ old('q', $filters['q'] ?? '') }}" placeholder="Tên sản phẩm..." class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary w-56">
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Số sao</label>
            <select name="star" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                @for($star = 5; $star >= 1; $star--)
                    <option value="{{ $star }}" {{ (string) ($filters['star'] ?? '') === (string) $star ? 'selected' : '' }}>{{ $star }} sao</option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Hình ảnh</label>
            <select name="has_image" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                <option value="1" {{ ($filters['has_image'] ?? '') === '1' ? 'selected' : '' }}>Có hình ảnh</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-text-primary mb-2 mono uppercase tracking-wider">Trạng thái</label>
            <select name="hidden" class="rounded-xl border-green-border/50 border px-4 py-2 bg-white focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none text-sm text-text-primary">
                <option value="">Tất cả</option>
                <option value="0" {{ ($filters['hidden'] ?? '') === '0' ? 'selected' : '' }}>Đang hiện</option>
                <option value="1" {{ ($filters['hidden'] ?? '') === '1' ? 'selected' : '' }}>Đã ẩn</option>
            </select>
        </div>

        <button type="submit" class="px-6 py-2.5 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors text-sm">Lọc</button>
        @if(array_filter($filters ?? []))
            <a href="{{ route('admin.reviews.index') }}" class="px-6 py-2.5 border border-green-border rounded-pill font-medium text-text-secondary hover:bg-green-background transition-colors text-sm text-decoration-none">Xoá lọc</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Sản phẩm</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khách hàng</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Sao</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Nội dung</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ảnh</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr class="border-b border-green-border/20 align-top hover:bg-green-background/20 transition-colors duration-200">
                        <td class="py-5 pr-4 max-w-[180px]">
                            <div class="font-medium text-text-primary text-sm">{{ $review->product->name ?? 'Sản phẩm đã xoá' }}</div>
                            @if($review->variant_name)
                                <div class="text-text-secondary text-xs mt-1">{{ $review->variant_name }}</div>
                            @endif
                            <div class="text-text-secondary text-xs mono mt-1">{{ $review->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="py-5 pr-4 text-sm text-text-primary">
                            {{ $review->display_name }}
                            @if($review->is_anonymous)
                                <span class="block text-xs text-text-secondary mt-1">(ẩn danh)</span>
                            @endif
                        </td>
                        <td class="py-5 pr-4 text-sm" style="color:#5C2323;letter-spacing:1px;white-space:nowrap">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                        <td class="py-5 pr-4 text-sm text-text-secondary max-w-[260px]">
                            {{ \Illuminate\Support\Str::limit($review->comment, 140) ?: '—' }}
                            @if($review->shop_reply)
                                <div class="mt-2 px-3 py-2 rounded-xl bg-green-background/60 text-xs text-text-primary">
                                    <span class="font-semibold">Đã phản hồi:</span> {{ \Illuminate\Support\Str::limit($review->shop_reply, 100) }}
                                </div>
                            @endif
                        </td>
                        <td class="py-5 pr-4">
                            @if($review->images->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5" style="max-width:140px">
                                    @foreach($review->images as $img)
                                        <img src="{{ asset('storage/' . $img->path) }}" alt="Ảnh đánh giá" class="w-10 h-10 object-cover rounded-lg">
                                    @endforeach
                                </div>
                            @else
                                <span class="text-text-secondary text-xs">—</span>
                            @endif
                        </td>
                        <td class="py-5 pr-4">
                            @if($review->is_hidden)
                                <span class="px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-pill text-xs mono font-semibold">Đã ẩn</span>
                            @else
                                <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">Đang hiện</span>
                            @endif
                        </td>
                        <td class="py-5 text-right" style="min-width:220px">
                            <div class="flex flex-col items-end gap-2">
                                <form action="{{ route('admin.reviews.toggle', $review) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-4 py-1.5 border border-green-border rounded-pill text-xs font-medium text-text-primary hover:bg-green-background transition-colors">
                                        {{ $review->is_hidden ? 'Hiện lại' : 'Ẩn đánh giá' }}
                                    </button>
                                </form>

                                <form action="{{ route('admin.reviews.reply', $review) }}" method="POST" class="w-full flex flex-col items-end gap-1.5">
                                    @csrf
                                    <textarea name="shop_reply" rows="2" placeholder="Viết phản hồi..." class="w-full min-w-[200px] rounded-xl border border-green-border/50 px-3 py-2 text-xs text-text-primary focus:ring-2 focus:ring-green-primary focus:border-green-primary outline-none">{{ old('shop_reply', $review->shop_reply) }}</textarea>
                                    @error('shop_reply')
                                        <span class="text-red-600 text-xs">{{ $message }}</span>
                                    @enderror
                                    <button type="submit" class="px-4 py-1.5 bg-green-primary text-white rounded-pill text-xs font-medium hover:bg-green-accent transition-colors">
                                        {{ $review->shop_reply ? 'Cập nhật phản hồi' : 'Gửi phản hồi' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-text-secondary">
                            Chưa có đánh giá nào phù hợp bộ lọc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
        <div class="mt-6">{{ $reviews->appends($filters ?? [])->links() }}</div>
    @endif
</div>
@endsection
