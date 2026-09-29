<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\ReviewImage;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Đánh giá sản phẩm kiểu Shopee: đánh giá theo TỪNG DÒNG HÀNG trong đơn, có
 * ảnh, ẩn danh, bình chọn "Hữu ích" và phản hồi của shop.
 *
 * Luật nghiệp vụ (mục 3.B2 của kế hoạch) nằm tập trung ở Order::canReview()
 * / Order::reviewableLines() / Review::canBeEditedBy() — controller chỉ điều
 * phối, không tự định nghĩa lại điều kiện.
 */
class ReviewController extends Controller
{
    /** Các giá trị hợp lệ của tham số lọc ?loc=. */
    private const FILTERS = [
        'tat-ca', '5-sao', '4-sao', '3-sao', '2-sao', '1-sao', 'co-binh-luan', 'co-hinh-anh',
    ];

    /**
     * Danh sách đánh giá của 1 sản phẩm — trả về PARTIAL HTML để trang sản
     * phẩm nạp bằng AJAX (đổi bộ lọc / sang trang mà không tải lại cả trang).
     *
     * Công khai: khách chưa đăng nhập cũng xem được.
     */
    public function index(Request $request, Product $product)
    {
        $filter = (string) $request->query('loc', 'tat-ca');

        if (! in_array($filter, self::FILTERS, true)) {
            $filter = 'tat-ca';
        }

        $reviews = Review::visible()
            ->where('product_id', $product->id)
            ->with(['user:id,name,avatar', 'images'])
            ->when(str_ends_with($filter, '-sao'), function ($q) use ($filter) {
                $q->where('rating', (int) substr($filter, 0, 1));
            })
            ->when($filter === 'co-binh-luan', function ($q) {
                $q->whereNotNull('comment')->where('comment', '!=', '');
            })
            ->when($filter === 'co-hinh-anh', function ($q) {
                $q->whereHas('images');
            })
            ->latest('id')
            ->paginate(6)
            ->withQueryString();

        $this->markVotedByMe($reviews->getCollection(), $request->user()?->id);

        return response()->view('shop.partials.reviews-list', [
            'reviews' => $reviews,
            'product' => $product,
            'filter' => $filter,
        ]);
    }

    /**
     * Đánh dấu đánh giá nào user hiện tại ĐÃ bấm "Hữu ích" — 1 truy vấn cho
     * cả trang thay vì hỏi từng dòng (N+1).
     *
     * @param  \Illuminate\Support\Collection<int, Review>  $reviews
     */
    private function markVotedByMe($reviews, ?int $userId): void
    {
        if ($reviews->isEmpty()) {
            return;
        }

        $votedIds = $userId === null
            ? collect()
            : ReviewHelpfulVote::where('user_id', $userId)
                ->whereIn('review_id', $reviews->pluck('id'))
                ->pluck('review_id')
                ->flip();

        $reviews->each(function (Review $review) use ($votedIds) {
            $review->voted_by_me = $votedIds->has($review->id);
        });
    }

    /**
     * Trang "Đánh giá đơn hàng" — liệt kê từng dòng hàng của đơn kèm trạng
     * thái đánh giá. Chỉ chủ đơn vào được.
     */
    public function createForOrder(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);

        if (! $order->canReview()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Đơn hàng này chưa giao xong hoặc đã quá hạn đánh giá.');
        }

        return view('reviews.create-for-order', [
            'order' => $order,
            'lines' => $order->reviewableLines($request->user()),
            'maxImages' => (int) config('shop.review_max_images', 5),
        ]);
    }

    /**
     * Lưu đánh giá cho nhiều dòng hàng trong 1 lần gửi.
     *
     * Cấu trúc form (mục 4.3): reviews[<order_item_id>][rating|comment|
     * is_anonymous|images[]|remove_images[]]. Dòng không chọn sao = khách bỏ
     * qua, KHÔNG báo lỗi.
     */
    public function storeForOrder(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);

        if (! $order->canReview()) {
            return back()->with('error', 'Đơn hàng này chưa giao xong hoặc đã quá hạn đánh giá.');
        }

        $user = $request->user();
        $lines = $order->reviewableLines($user)->keyBy(fn (array $line) => $line['item']->id);
        $maxImages = (int) config('shop.review_max_images', 5);

        // Chỉ giữ lại các dòng khách thật sự chấm sao, và chỉ những dòng
        // thuộc đơn này (bỏ qua order_item_id lạ gửi kèm).
        $submitted = collect($request->input('reviews', []))
            ->filter(fn ($data, $itemId) => $lines->has((int) $itemId) && filled($data['rating'] ?? null));

        if ($submitted->isEmpty()) {
            return back()->with('error', 'Vui lòng chọn số sao cho ít nhất một sản phẩm.');
        }

        // Trình duyệt vẫn gửi ô <input type="file"> rỗng, PHP biến nó thành
        // một entry UPLOAD_ERR_NO_FILE. Nếu để nguyên, validator coi đó là
        // "file tải lên hỏng" và chặn luôn việc SỬA đánh giá mà không đổi ảnh
        // (lỗi khách gặp: "The reviews.X.images.0 failed to upload").
        $cleanFiles = $this->prunedImageFiles($request);

        $rules = [];
        $messages = [];

        foreach ($submitted->keys() as $itemId) {
            $rules["reviews.$itemId.rating"] = ['required', 'integer', 'min:1', 'max:5'];
            $rules["reviews.$itemId.comment"] = ['nullable', 'string', 'max:1000'];
            $rules["reviews.$itemId.is_anonymous"] = ['nullable', 'boolean'];
            $rules["reviews.$itemId.images"] = ['nullable', 'array', 'max:'.$maxImages];
            $rules["reviews.$itemId.images.*"] = [
                'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:max_width=4000,max_height=4000',
            ];
            $rules["reviews.$itemId.remove_images"] = ['nullable', 'array'];
            $rules["reviews.$itemId.remove_images.*"] = ['integer'];

            $messages["reviews.$itemId.rating.required"] = 'Vui lòng chọn số sao.';
            $messages["reviews.$itemId.rating.min"] = 'Vui lòng chọn từ 1 đến 5 sao.';
            $messages["reviews.$itemId.rating.max"] = 'Vui lòng chọn từ 1 đến 5 sao.';
            $messages["reviews.$itemId.comment.max"] = 'Nhận xét không được vượt quá 1000 ký tự.';
            $messages["reviews.$itemId.images.max"] = "Mỗi đánh giá chỉ được tối đa {$maxImages} ảnh.";
            $messages["reviews.$itemId.images.*.image"] = 'Tệp tải lên phải là hình ảnh.';
            $messages["reviews.$itemId.images.*.mimes"] = 'Ảnh phải có định dạng jpg, jpeg, png hoặc webp.';
            $messages["reviews.$itemId.images.*.max"] = 'Mỗi ảnh không được vượt quá 2MB.';
            $messages["reviews.$itemId.images.*.dimensions"] = 'Ảnh quá lớn (tối đa 4000x4000 điểm ảnh).';
        }

        // Validate trên dữ liệu ĐÃ DỌN, không dùng $request->validate():
        // Request::allFiles() cache kết quả chuyển đổi ngay lần đọc đầu tiên
        // nên sửa thẳng vào $request->files sẽ không có tác dụng.
        Validator::make(
            array_replace_recursive($request->input(), ['reviews' => $cleanFiles]),
            $rules,
            $messages
        )->validate();

        // Tổng số ảnh sau khi sửa (ảnh cũ giữ lại + ảnh mới) không được vượt
        // hạn mức. Rule 'max' ở trên chỉ đếm ảnh MỚI nên không bắt được
        // trường hợp khách đã có 4 ảnh rồi tải thêm 3.
        $this->validateTotalImageCount($cleanFiles, $submitted, $lines, $maxImages);

        // File ảnh cũ bị thay/gỡ — chỉ xoá khỏi ổ đĩa SAU KHI transaction đã
        // commit, để rollback không làm mất ảnh của đánh giá vẫn còn sống.
        $pathsToDelete = [];
        // Ngược lại: ảnh MỚI được ghi ra đĩa ngay trong transaction. Nếu
        // transaction rollback thì dòng DB biến mất nhưng file vẫn nằm lại —
        // theo dõi để tự dọn, không để rác tích tụ trong storage.
        $writtenPaths = [];
        $savedCount = 0;
        $rewardedPoints = 0;

        try {
            DB::transaction(function () use ($submitted, $lines, $order, $user, $cleanFiles, $maxImages, &$pathsToDelete, &$writtenPaths, &$savedCount, &$rewardedPoints) {
                foreach ($submitted as $itemId => $data) {
                    $line = $lines->get((int) $itemId);
                    $existing = $line['review'];

                    // Đã đánh giá rồi mà không còn quyền sửa -> bỏ qua im lặng
                    // (khách có thể mở 2 tab, tab cũ gửi lại dữ liệu cũ).
                    if ($existing !== null && ! $line['can_edit']) {
                        continue;
                    }

                    $review = $existing ?? new Review([
                        'product_id' => $line['item']->product_id,
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'order_item_id' => $line['item']->id,
                        'variant_name' => $line['item']->variant_name,
                    ]);

                    $review->rating = (int) $data['rating'];
                    $review->comment = $data['comment'] ?? null;
                    $review->is_anonymous = (bool) ($data['is_anonymous'] ?? false);

                    // Sửa: đánh dấu đã dùng quyền sửa (mỗi đánh giá sửa 1 lần).
                    if ($existing !== null) {
                        $review->edited_at = now();
                    }

                    $review->save();

                    $pathsToDelete = array_merge(
                        $pathsToDelete,
                        $this->syncImages($cleanFiles, $review, (int) $itemId, $data, $maxImages, $writtenPaths)
                    );

                    $rewardedPoints += $this->awardPointsIfQualified($review, $user);
                    $savedCount++;
                }
            });
        } catch (\Throwable $e) {
            // Transaction đã rollback -> xoá luôn ảnh vừa ghi ra đĩa.
            foreach ($writtenPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        // Xoá file ngoài transaction: thao tác trên ổ đĩa không rollback được.
        foreach ($pathsToDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        if ($savedCount === 0) {
            return back()->with('error', 'Các sản phẩm bạn chọn đều đã được đánh giá và không còn sửa được.');
        }

        $message = 'Cảm ơn bạn đã đánh giá '.$savedCount.' sản phẩm!';

        if ($rewardedPoints > 0) {
            $message .= ' Bạn được cộng '.$rewardedPoints.' điểm cho đánh giá có ảnh và nhận xét chi tiết.';
        }

        return redirect()->route('orders.show', $order)->with('success', $message);
    }

    /**
     * Lưu ảnh mới, gỡ ảnh cũ bị bỏ chọn. Trả về danh sách path cần xoá khỏi
     * ổ đĩa (caller xoá sau khi commit).
     *
     * Ảnh lưu vào `reviews/{review_id}/` trên disk `public` với TÊN NGẪU
     * NHIÊN do Laravel sinh — không bao giờ dùng tên file gốc của khách.
     *
     * @return array<int, string>
     */
    private function syncImages(array $cleanFiles, Review $review, int $itemId, array $data, int $maxImages, array &$writtenPaths): array
    {
        $toDelete = [];

        // 1) Gỡ những ảnh khách tích chọn bỏ (chỉ ảnh thuộc đúng đánh giá này
        //    — không tin id gửi lên).
        $removeIds = array_filter(array_map('intval', $data['remove_images'] ?? []));

        if ($removeIds !== []) {
            $removing = ReviewImage::where('review_id', $review->id)->whereIn('id', $removeIds)->get();

            foreach ($removing as $image) {
                $toDelete[] = $image->path;
                $image->delete();
            }
        }

        $files = $cleanFiles[$itemId]['images'] ?? [];

        if ($files === []) {
            return $toDelete;
        }

        // 2) Ảnh mới được CỘNG THÊM vào những ảnh cũ khách giữ lại, không xoá
        //    trắng. Form có ô tích "gỡ ảnh" cho từng ảnh (remove_images[]),
        //    nên xoá hết ảnh cũ mỗi lần tải thêm sẽ khiến ô tích đó vô nghĩa
        //    và làm khách mất ảnh họ muốn giữ.
        $kept = ReviewImage::where('review_id', $review->id)->count();
        $room = max(0, $maxImages - $kept);

        if ($room === 0) {
            return $toDelete;
        }

        // sort_order chạy tiếp sau ảnh cũ để thứ tự hiển thị ổn định.
        $sortOrder = (int) ReviewImage::where('review_id', $review->id)->max('sort_order') + 1;

        foreach (array_slice($files, 0, $room) as $file) {
            $path = $file->store('reviews/'.$review->id, 'public');

            if ($path === false) {
                Log::warning('Lưu ảnh đánh giá thất bại', ['review_id' => $review->id]);

                continue;
            }

            $writtenPaths[] = $path;

            ReviewImage::create([
                'review_id' => $review->id,
                'path' => $path,
                'sort_order' => $sortOrder++,
            ]);
        }

        return $toDelete;
    }

    /**
     * Ảnh THẬT SỰ được tải lên, gom theo order_item_id:
     * `[<order_item_id> => ['images' => [UploadedFile, ...]]]`.
     *
     * Đã loại bỏ ô <input type="file"> rỗng (UPLOAD_ERR_NO_FILE) và file
     * hỏng, nên cả validator lẫn phần lưu ảnh đều chỉ thấy file hợp lệ.
     * Dòng nào không có ảnh hợp lệ thì KHÔNG có khoá 'images' — nhờ vậy rule
     * `reviews.X.images.*` không chạy và khách sửa đánh giá mà không đổi ảnh
     * sẽ không bị chặn.
     *
     * @return array<int, array{images: array<int, UploadedFile>}>
     */
    private function prunedImageFiles(Request $request): array
    {
        $files = $request->file('reviews');

        if (! is_array($files)) {
            return [];
        }

        $clean = [];

        foreach ($files as $itemId => $fields) {
            $images = $fields['images'] ?? null;

            if ($images === null) {
                continue;
            }

            $images = $images instanceof UploadedFile ? [$images] : (array) $images;

            $valid = array_values(array_filter(
                $images,
                fn ($file) => $file instanceof UploadedFile && $file->isValid()
            ));

            if ($valid !== []) {
                $clean[(int) $itemId] = ['images' => $valid];
            }
        }

        return $clean;
    }

    /**
     * Chặn trường hợp tổng ảnh (cũ giữ lại + mới) vượt hạn mức, với thông
     * báo tiếng Việt nói rõ còn được thêm bao nhiêu ảnh.
     *
     * @param  \Illuminate\Support\Collection  $submitted
     * @param  \Illuminate\Support\Collection  $lines
     */
    private function validateTotalImageCount(array $cleanFiles, $submitted, $lines, int $maxImages): void
    {
        $errors = [];

        foreach ($submitted as $itemId => $data) {
            $new = count($cleanFiles[(int) $itemId]['images'] ?? []);

            if ($new === 0) {
                continue;
            }

            $review = $lines->get((int) $itemId)['review'] ?? null;

            if ($review === null) {
                continue;
            }

            $removeIds = array_filter(array_map('intval', $data['remove_images'] ?? []));
            $kept = $review->images->count() - count(array_intersect(
                $review->images->pluck('id')->all(),
                $removeIds
            ));

            if ($kept + $new > $maxImages) {
                $room = max(0, $maxImages - $kept);
                $errors["reviews.$itemId.images"] = $room === 0
                    ? "Đánh giá này đã đủ {$maxImages} ảnh. Hãy gỡ bớt ảnh cũ trước khi thêm ảnh mới."
                    : "Đánh giá này chỉ còn thêm được {$room} ảnh (tối đa {$maxImages} ảnh).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Cộng điểm cho đánh giá "chất lượng" (có ảnh VÀ nhận xét >= 50 ký tự),
     * mỗi đánh giá chỉ 1 lần nhờ cờ `points_awarded`.
     *
     * @return int số điểm vừa cộng (0 nếu không đủ điều kiện)
     */
    private function awardPointsIfQualified(Review $review, $user): int
    {
        if ($review->points_awarded) {
            return 0;
        }

        // Ảnh vừa được tạo trong cùng request -> nạp lại quan hệ cho chắc.
        $review->load('images');

        if (! $review->qualifiesForReward()) {
            return 0;
        }

        $points = (int) config('shop.review_reward_points', 20);

        if ($points <= 0) {
            return 0;
        }

        $review->forceFill(['points_awarded' => true])->save();

        $user->points = (int) $user->points + $points;
        $user->tier = LoyaltyService::tierFor((int) $user->points);
        $user->save();

        return $points;
    }

    /**
     * Bấm / bỏ bấm "Hữu ích". Trả JSON {count, voted} cho nút cập nhật tại chỗ.
     *
     * Không cho tự bấm cho đánh giá của chính mình (tránh tự thổi số).
     */
    public function toggleHelpful(Request $request, Review $review)
    {
        $user = $request->user();

        if ($review->user_id === $user->id) {
            return response()->json([
                'message' => 'Bạn không thể bình chọn cho đánh giá của chính mình.',
            ], 422);
        }

        if ($review->is_hidden) {
            return response()->json(['message' => 'Đánh giá này không còn hiển thị.'], 404);
        }

        // Đếm và cờ vote phải đổi cùng nhau, nếu không `helpful_count` sẽ
        // trôi khỏi số dòng thật trong review_helpful_votes.
        $voted = DB::transaction(function () use ($review, $user) {
            $locked = Review::whereKey($review->id)->lockForUpdate()->first();
            $existing = ReviewHelpfulVote::where('review_id', $review->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $locked->decrement('helpful_count');

                return false;
            }

            ReviewHelpfulVote::create(['review_id' => $review->id, 'user_id' => $user->id]);
            $locked->increment('helpful_count');

            return true;
        });

        return response()->json([
            'count' => (int) $review->fresh()->helpful_count,
            'voted' => $voted,
        ]);
    }

    /**
     * Route cũ `POST /san-pham/{product}/danh-gia` — giữ tên để link/form cũ
     * không chết, nhưng đánh giá giờ làm theo ĐƠN HÀNG nên chỉ chuyển hướng
     * sang trang đánh giá của đơn đủ điều kiện gần nhất.
     */
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->whereIn('status', Order::PAID_OR_COD_STATUSES)
            ->where('shipping_status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->with('items.product')
            ->latest('id')
            ->get();

        foreach ($orders as $order) {
            if ($order->canReview() && $order->hasPendingReviews($user)) {
                return redirect()->route('reviews.createForOrder', $order);
            }
        }

        return redirect()->route('shop.show', $product)
            ->with('error', 'Bạn cần mua và nhận sản phẩm này trước khi có thể đánh giá.');
    }

    /** Chỉ chủ đơn mới xem/gửi được đánh giá của đơn đó. */
    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()?->id, 403);
    }
}
