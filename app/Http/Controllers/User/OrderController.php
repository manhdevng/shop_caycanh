<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Voucher;
use App\Services\GHNService;
use App\Services\GHNOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    // ==========================================
    // 1. CÁC VIEW HIỂN THỊ ĐƠN HÀNG & THANH TOÁN
    // ==========================================

    public function index(Request $request)
    {
        $cart = session('cart', []);

        // Nếu form ở trang giỏ hàng gửi lên items[] (đã tick chọn sản phẩm)
        // thì chỉ lấy đúng các sản phẩm đó để thanh toán, và ghi nhớ lựa
        // chọn vào session để store() và getShippingFee() dùng lại. Nếu
        // KHÔNG có items[] (ví dụ vào từ "Mua ngay") thì dùng nguyên giỏ
        // hàng như trước, đồng thời xoá lựa chọn cũ (nếu có) để tránh dính
        // lựa chọn của lần thanh toán trước.
        if ($request->has('items')) {
            $cart = collect($cart)->only($request->query('items', []))->all();

            if (empty($cart)) {
                return redirect()->route('cart.index')->with('error', 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.');
            }

            session(['checkout_selected_items' => array_keys($cart)]);
        } else {
            session()->forget('checkout_selected_items');
        }

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        $totalPrice = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);

        // Hiển thị voucher đang áp dụng (nếu có) trên trang thanh toán — luôn
        // kiểm tra lại điều kiện với tổng tiền giỏ hàng HIỆN TẠI (giỏ có thể
        // đã đổi từ lúc áp mã), không hiển thị số giảm đã lưu sẵn trong
        // session nếu không còn hợp lệ. Giá trị thật sự dùng khi đặt hàng vẫn
        // được tính lại một lần nữa trong store().
        $voucher = null;
        $discountAmount = 0;
        $user = Auth::user();

        $voucherSession = session('voucher');
        if (!empty($voucherSession['id'])) {
            $voucherModel = Voucher::find($voucherSession['id']);
            // $cart ở đây ĐÃ được lọc theo checkbox chọn sản phẩm (nếu có)
            // ngay từ đầu hàm — không tự đọc lại session('cart') đầy đủ.
            $voucherError = $voucherModel ? $voucherModel->validationErrorForCart($cart, $user) : 'Mã giảm giá không còn tồn tại.';

            if ($voucherError !== null) {
                session()->forget('voucher');
            } else {
                $voucher = $voucherModel;
                $discountAmount = $voucherModel->calculateDiscountAmount((float) $voucherModel->eligibleSubtotal($cart));
            }
        }

        // /checkout là nơi để DÙNG mã: chỉ hiện các mã user đã lưu vào ví và
        // CHƯA dùng. Mã chưa lưu được liệt kê riêng ở $savableVouchers để
        // view hiện mục "Lưu thêm mã". Khách chưa đăng nhập: không có ví nên
        // $availableVouchers rỗng, $savableVouchers là toàn bộ mã còn hiệu lực.
        if ($user !== null) {
            $availableVouchers = Voucher::available()
                ->whereHas('savedByUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereNull('user_voucher.used_at');
                })
                ->get();

            $savedVoucherIds = DB::table('user_voucher')->where('user_id', $user->id)->pluck('voucher_id');

            $savableVouchers = Voucher::available()
                ->when($savedVoucherIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $savedVoucherIds))
                ->get();
        } else {
            $availableVouchers = collect();
            $savableVouchers = Voucher::available()->get();
        }

        return view('checkout.payment', compact('cart', 'totalPrice', 'voucher', 'discountAmount', 'availableVouchers', 'savableVouchers'));
    }

    // Đặt hàng: tạo Order + OrderItem từ giỏ hàng trong session, sau đó tạo vận đơn bên GHN.
    public function store(Request $request, GHNOrderService $ghnOrderService, GHNService $ghn)
    {
        // Giỏ hàng thật đầy đủ trong session, dùng để merge lại khi ghi đè
        // session('cart') bên dưới — không được làm mất các sản phẩm không
        // nằm trong lần thanh toán này. $selectedIds là danh sách khoá đã
        // được chọn ở index() (null nghĩa là luồng "Mua ngay"/không lọc,
        // dùng nguyên $fullCart).
        $fullCart = session('cart', []);
        $selectedIds = session('checkout_selected_items');
        $cart = $selectedIds !== null
            ? collect($fullCart)->only($selectedIds)->all()
            : $fullCart;

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        // Tính lại đơn giá + khối lượng của TỪNG dòng giỏ hàng từ DB — không
        // tin dữ liệu giá/khối lượng đã chốt trong session (D3-P7, F12).
        $revalidation = $this->revalidateCart($cart);

        if ($revalidation['removed']) {
            session(['cart' => $this->mergeRevalidatedCart($fullCart, $cart, $revalidation['cart'])]);

            return redirect()->route('cart.index')->with('error', 'Một số sản phẩm trong giỏ hàng không còn khả dụng (đã bị ẩn, xoá, hoặc phân loại đã bị xoá) nên đã được gỡ khỏi giỏ hàng. Vui lòng kiểm tra lại.');
        }

        // Kiểm tra tồn kho lần cuối (trước khi vào transaction): chặn tạo
        // đơn ngay nếu số lượng đặt đã vượt tồn kho hiện có.
        if ($revalidation['stockError'] !== null) {
            session(['cart' => $this->mergeRevalidatedCart($fullCart, $cart, $revalidation['cart'])]);

            return redirect()->route('cart.index')->with('error', $revalidation['stockError']);
        }

        if ($revalidation['priceChanged']) {
            session(['cart' => $this->mergeRevalidatedCart($fullCart, $cart, $revalidation['cart'])]);

            return redirect()->route('checkout')->with('error', 'Giá một số sản phẩm đã thay đổi, vui lòng kiểm tra lại.');
        }

        $cart = $revalidation['cart'];

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
            'payment_method' => 'required|in:cod,momo,bank_transfer',
            'momo_card_type' => 'nullable|in:atm,cc,wallet',
            'transfer_ref' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Vui lòng nhập họ tên người nhận.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'address.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'to_district_id.required' => 'Vui lòng chọn Quận/Huyện.',
            'to_ward_code.required' => 'Vui lòng chọn Phường/Xã.',
        ]);

        // Tiền hàng tính lại từ session (không tin số liệu client gửi lên).
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);

        // Phí ship do server tự gọi lại GHN để tính — không tin giá trị "ghn_fee" client gửi lên.
        $ghnFeeResponse = $this->fetchGhnFee($cart, (int) $request->to_district_id, (string) $request->to_ward_code, $ghn);

        if (($ghnFeeResponse['code'] ?? null) !== 200 || ! isset($ghnFeeResponse['data']['total'])) {
            return back()->withInput()->with('error', 'Không thể tính phí vận chuyển cho địa chỉ này. Vui lòng kiểm tra lại địa chỉ hoặc thử lại sau.');
        }

        $shippingFee = (int) $ghnFeeResponse['data']['total'];
        $totalPrice = $subtotal + $shippingFee;

        // Voucher (nếu có) chỉ mới được lưu tạm trong session ở bước
        // VoucherController::apply() — KHÔNG tin discount_amount đã tính sẵn
        // ở đó, phải tính lại từ voucher thật trong transaction bên dưới.
        $voucherSession = session('voucher');

        try {
            $order = DB::transaction(function () use ($request, $cart, $totalPrice, $shippingFee, $subtotal, $voucherSession) {
                $discountAmount = 0;
                $voucherId = null;

                $user = Auth::user();

                if (!empty($voucherSession['id'])) {
                    // Khoá bản ghi voucher (SELECT ... FOR UPDATE) trước khi kiểm
                    // tra + tăng used_count, để 2 khách cùng dùng nốt lượt cuối
                    // cùng lúc phải xếp hàng chờ nhau thay vì cùng vượt usage_limit.
                    $voucher = Voucher::where('id', $voucherSession['id'])->lockForUpdate()->first();

                    if (!$voucher) {
                        throw new \RuntimeException('Mã giảm giá không còn tồn tại. Vui lòng gỡ mã và thử lại.');
                    }

                    // Re-validate toàn bộ điều kiện NGAY TRONG transaction (đã
                    // khoá dòng) — $cart ở đây là tập đã lọc theo checkbox +
                    // đã revalidate ở ngoài transaction — nếu voucher vừa hết
                    // hạn/hết lượt/đã dùng kể từ lúc apply() thì chặn đặt hàng
                    // luôn, không âm thầm bỏ qua giảm giá.
                    $voucherError = $voucher->validationErrorForCart($cart, $user);

                    if ($voucherError !== null) {
                        throw new \RuntimeException($voucherError);
                    }

                    $discountAmount = $voucher->calculateDiscountAmount((float) $voucher->eligibleSubtotal($cart));
                    $voucherId = $voucher->id;

                    $voucher->increment('used_count');
                }

                $finalTotal = $totalPrice - $discountAmount;

                // Trạng thái đơn + vận chuyển ban đầu phụ thuộc hình thức
                // thanh toán: momo chờ IPN, cod đã "đặt" (chưa thu tiền),
                // bank_transfer chờ admin đối soát tiền vào tài khoản (chưa
                // tạo vận đơn GHN — xem đoạn xử lý riêng bên dưới).
                $status = match ($request->payment_method) {
                    'momo' => 'pending',
                    'bank_transfer' => 'awaiting_transfer',
                    default => 'cod_ordered',
                };
                $shippingStatus = $request->payment_method === 'bank_transfer' ? 'pending' : 'not_shipped';

                $order = Order::create([
                    'user_id' => Auth::id(),
                    'name' => $request->name,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'total_price' => $finalTotal,
                    'voucher_id' => $voucherId,
                    'discount_amount' => $discountAmount,
                    'status' => $status,
                    'shipping_status' => $shippingStatus,
                    'payment_method' => $request->payment_method,
                    'transfer_ref' => $request->payment_method === 'bank_transfer' ? $request->transfer_ref : null,
                    'ghn_total_fee' => $shippingFee,
                    'to_district_id' => $request->to_district_id,
                    'to_ward_code' => $request->to_ward_code,
                ]);

                // Đánh dấu mã đã được DÙNG trong ví của khách (mỗi khách chỉ
                // dùng mỗi mã đúng 1 lần — hàng rào cuối là unique(user_id,
                // voucher_id) ở DB). Đặt sau khi đã biết $order->id, vẫn
                // trong cùng transaction để rollback đồng bộ nếu có lỗi.
                if ($voucherId !== null && $user !== null) {
                    DB::table('user_voucher')
                        ->where('user_id', $user->id)
                        ->where('voucher_id', $voucherId)
                        ->update([
                            'used_at' => now(),
                            'order_id' => $order->id,
                            'updated_at' => now(),
                        ]);
                }

                foreach ($cart as $item) {
                    // Khoá dòng sản phẩm (SELECT ... FOR UPDATE) trước khi kiểm
                    // tra và trừ kho, để 2 request đặt hàng đồng thời cho cùng
                    // 1 sản phẩm phải xếp hàng chờ nhau thay vì cùng đọc thấy
                    // tồn kho cũ rồi cùng trừ, gây âm kho (race condition).
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                    if (!$product) {
                        throw new \RuntimeException('Một sản phẩm trong giỏ hàng không còn tồn tại. Vui lòng kiểm tra lại giỏ hàng.');
                    }

                    $requestedQty = (int) $item['quantity'];

                    // Kiểm tra lại tồn kho NGAY TRONG transaction (sau khi đã
                    // khoá dòng): chống trường hợp request khác vừa mua trước
                    // và làm tồn kho không còn đủ kể từ lúc revalidateCart()
                    // chạy ở ngoài transaction.
                    if ($requestedQty > (int) $product->stock) {
                        throw new \RuntimeException("Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} trong kho.");
                    }

                    // Trừ kho ngay trong transaction đang giữ khoá dòng này —
                    // không thể xuống âm vì đã kiểm tra ở trên trong cùng khoá.
                    $product->decrement('stock', $requestedQty);

                    // Snapshot tên phân loại NGAY TRƯỚC khi tạo order item (lấy
                    // thẳng từ DB, không dùng tên đã lưu trong session) để đảm
                    // bảo đúng nhất tại thời điểm đặt hàng. Xem D3-P8.
                    $variantName = null;
                    if (!empty($item['variant_id'])) {
                        $variantName = optional(ProductVariant::find($item['variant_id']))->variant_name;
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_name' => $variantName,
                        // Snapshot tên sản phẩm thật tại thời điểm đặt hàng —
                        // không tham chiếu tới sản phẩm có thể bị đổi tên/xoá
                        // sau này.
                        'product_name' => $product->name,
                        'quantity' => $requestedQty,
                        'price' => $item['price'],
                    ]);
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Chỉ xoá đúng các sản phẩm vừa đặt (khoá của $cart — tập đã lọc và
        // revalidate thành công) khỏi giỏ hàng thật, giữ nguyên các sản
        // phẩm khác không nằm trong lần thanh toán này.
        session(['cart' => collect(session('cart', []))->except(array_keys($cart))->all()]);
        session()->forget('checkout_selected_items');
        session()->forget('voucher');

        // ==== Thanh toán MoMo: chưa tạo vận đơn GHN vội — chờ MomoController xác nhận đã thanh toán ====
        if ($request->payment_method === 'momo') {
            // Ghi chú: PaymentTransaction(pending) cho MoMo sẽ được tạo tại MomoController::start()

            // Gửi email xác nhận đơn hàng — nằm NGOÀI DB::transaction() ở
            // trên (đã commit xong) nên nếu gửi email lỗi (SMTP timeout,
            // v.v.) sẽ KHÔNG rollback đơn hàng hay chặn redirect, chỉ ghi log.
            $this->sendOrderConfirmationEmail($order);

            return redirect()->route('momo.start', ['order' => $order, 'type' => $request->input('momo_card_type', 'atm')]);
        }

        // ==== Chuyển khoản ngân hàng: đơn ở trạng thái "awaiting_transfer",
        // KHÔNG tạo vận đơn GHN ở bước này — chờ admin đối soát tiền vào tài
        // khoản rồi xác nhận qua AdminOrderController::confirmTransfer(),
        // lúc đó mới tạo vận đơn (giống nhánh MoMo đã thanh toán). Kho đã
        // được trừ ngay ở trên (trong transaction) để tránh bán vượt tồn
        // trong lúc chờ tiền về. ====
        if ($request->payment_method === 'bank_transfer') {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway' => 'bank_transfer',
                'amount' => $order->total_price,
                'status' => 'pending',
                'message' => 'Chờ xác nhận chuyển khoản ngân hàng',
            ]);

            // Gửi email xác nhận đơn hàng — xem chú thích ở nhánh MoMo phía trên.
            $this->sendOrderConfirmationEmail($order);

            return redirect()->route('orders.show', $order)
                ->with('success', 'Đặt hàng thành công! Vui lòng chuyển khoản theo thông tin ngân hàng và chờ xác nhận. Mã đơn hàng #' . $order->id . '.');
        }

        // ==== Thanh toán khi nhận hàng (COD): tạo vận đơn GHN ngay lập tức ====
        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'cod',
            'amount' => $order->total_price,
            'status' => 'pending',
            'message' => 'Thanh toán khi nhận hàng',
        ]);

        // 'items.variant' thêm vào để GHNOrderService (Phase 3) lấy được
        // khối lượng/tên riêng của phân loại đã mua khi tạo vận đơn. Xem D3-P8.
        $order->load(['items.product', 'items.variant']);
        $ghnResponse = $ghnOrderService->create($order, isPaid: false);

        if (($ghnResponse['code'] ?? null) === 200 && isset($ghnResponse['data']['order_code'])) {
            $order->update([
                'ghn_order_code' => $ghnResponse['data']['order_code'],
                'ghn_total_fee' => $ghnResponse['data']['total_fee'] ?? $shippingFee,
                'shipping_status' => 'ready_to_pick',
            ]);
        } else {
            Log::warning('GHN createOrder failed for order #' . $order->id, ['response' => $ghnResponse]);
        }

        // Gửi email xác nhận đơn hàng — xem chú thích ở nhánh MoMo phía trên.
        $this->sendOrderConfirmationEmail($order);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Đặt hàng thành công! Mã đơn hàng #' . $order->id . '.');
    }

    /**
     * Gửi email xác nhận đơn hàng cho khách sau khi đơn đã được tạo thành
     * công (đã commit DB). Bắt buộc gọi hàm này SAU khi DB::transaction() đã
     * hoàn tất — lỗi gửi email (SMTP down, sai cấu hình, v.v.) chỉ được ghi
     * log, KHÔNG được rollback đơn hàng hay chặn luồng redirect của khách.
     */
    private function sendOrderConfirmationEmail(Order $order): void
    {
        try {
            $order->loadMissing(['items', 'latestPaymentTransaction', 'user']);

            if (! $order->user || ! $order->user->email) {
                Log::warning('Không gửi được email xác nhận đơn hàng: tài khoản không có email.', ['order_id' => $order->id]);

                return;
            }

            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            Log::error('Gửi email xác nhận đơn hàng thất bại: ' . $e->getMessage(), ['order_id' => $order->id]);
        }
    }

    public function orderHistory()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with('items.product')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('orders.history', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $order->load('items.product');

        return view('orders.show', compact('order'));
    }

    // ==========================================
    // 2. AJAX LOCATION & TÍNH PHÍ GHN
    // ==========================================

    public function getProvinces(GHNService $ghn)
    {
        return response()->json($ghn->getProvinces());
    }

    public function getDistricts(int $provinceId, GHNService $ghn)
    {
        return response()->json($ghn->getDistricts($provinceId));
    }

    public function getWards(int $districtId, GHNService $ghn)
    {
        return response()->json($ghn->getWards($districtId));
    }

    public function getShippingFee(Request $request, GHNService $ghn)
    {
        // Nếu đang có lựa chọn sản phẩm thanh toán từ index(), chỉ tính phí
        // ship trên đúng tập sản phẩm đó — để khớp với phí sẽ tính lại
        // trong store(). Nếu không (luồng "Mua ngay") thì dùng nguyên giỏ.
        $cart = session()->has('checkout_selected_items')
            ? collect(session('cart', []))->only(session('checkout_selected_items'))->all()
            : session('cart', []);

        $res = $this->fetchGhnFee($cart, (int) $request->to_district_id, (string) $request->to_ward_code, $ghn);

        return response()->json($res);
    }

    /**
     * Ghép lại session('cart') sau khi revalidate một SUBSET (tập sản phẩm
     * đang thanh toán) — chỉ cập nhật đúng các khoá nằm trong subset đó,
     * giữ nguyên mọi sản phẩm khác trong giỏ hàng thật ($fullCart). Bắt
     * buộc dùng hàm này thay vì gán thẳng session(['cart' => $revalidatedSubset])
     * để tránh xoá mất sản phẩm không nằm trong lần thanh toán này khi
     * revalidateCart() phát hiện lỗi (hết hàng, giá đổi, sản phẩm bị gỡ).
     *
     * @param  array  $fullCart  Toàn bộ giỏ hàng thật trong session trước khi lọc.
     * @param  array  $selectedCart  Tập sản phẩm đang thanh toán (đầu vào của revalidateCart()).
     * @param  array  $revalidatedSubset  Kết quả revalidateCart()['cart'] tương ứng với $selectedCart.
     */
    private function mergeRevalidatedCart(array $fullCart, array $selectedCart, array $revalidatedSubset): array
    {
        $merged = $fullCart;

        foreach (array_keys($selectedCart) as $key) {
            unset($merged[$key]);
        }

        return $merged + $revalidatedSubset;
    }

    /**
     * Tính lại từ DB cho từng dòng giỏ hàng trong session: sản phẩm còn tồn
     * tại + đang is_active; nếu dòng có variant_id thì phân loại đó phải còn
     * tồn tại và đúng thuộc sản phẩm. Dòng không hợp lệ bị loại khỏi giỏ.
     * Nếu giá hiện tại (variant hoặc base_price) khác giá đã chốt trong
     * session thì cập nhật lại và báo hiệu để gọi nơi dùng cảnh báo khách,
     * TUYỆT ĐỐI không tạo đơn với giá cũ mà khách chưa biết. Xem D3-P7, F12.
     * Đồng thời kiểm tra tồn kho lần cuối: nếu số lượng đặt của bất kỳ dòng
     * nào vượt quá tồn kho hiện tại của sản phẩm thì trả về thông báo lỗi
     * tiếng Việt trong 'stockError' để chặn tạo đơn ngay ở store().
     *
     * @return array{cart: array, removed: bool, priceChanged: bool, stockError: ?string}
     */
    private function revalidateCart(array $cart): array
    {
        $updatedCart = [];
        $removed = false;
        $priceChanged = false;
        $stockError = null;

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id'] ?? null);

            if (!$product || !$product->is_active) {
                $removed = true;
                continue;
            }

            $variant = null;
            if (!empty($item['variant_id'])) {
                $variant = ProductVariant::find($item['variant_id']);

                if (!$variant || $variant->product_id !== $product->id) {
                    $removed = true;
                    continue;
                }
            }

            // Kiểm tra tồn kho lần cuối trước khi cho phép đặt hàng: số
            // lượng đặt không được vượt quá tồn kho hiện tại của sản phẩm.
            $requestedQty = (int) ($item['quantity'] ?? 0);
            if ($stockError === null && $requestedQty > (int) $product->stock) {
                $stockError = "Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} trong kho.";
            }

            $currentPrice = (float) ($variant->price ?? $product->base_price);
            $currentWeight = (int) ($variant->weight ?? $product->weight ?? 200);

            if (abs((float) ($item['price'] ?? 0) - $currentPrice) > 0.009) {
                $priceChanged = true;
            }

            $item['price'] = $currentPrice;
            $item['weight'] = $currentWeight;

            $updatedCart[$key] = $item;
        }

        return [
            'cart' => $updatedCart,
            'removed' => $removed,
            'priceChanged' => $priceChanged,
            'stockError' => $stockError,
        ];
    }

    // Gọi GHN tính phí ship dựa trên giỏ hàng thật trong session + địa chỉ nhận hàng — dùng chung cho AJAX xem trước phí và khi tạo đơn thật.
    // Tự revalidate lại khối lượng từ DB (D3-P7): khi gọi từ store() giỏ đã
    // được revalidate trước đó nên vô hại khi revalidate lại; khi gọi từ
    // getShippingFee() (AJAX xem trước phí) đảm bảo khối lượng dùng để ước
    // tính luôn khớp với dữ liệu hiện tại của sản phẩm/phân loại.
    private function fetchGhnFee(array $cart, int $toDistrictId, string $toWardCode, GHNService $ghn): array
    {
        $cart = $this->revalidateCart($cart)['cart'];

        $totalWeight = 0;

        foreach ($cart as $item) {
            $totalWeight += ((int) ($item['weight'] ?? 200)) * (int) $item['quantity'];
        }

        return $ghn->calculateFee([
            'service_type_id' => 2, // Gói chuẩn E-commerce
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'weight' => $totalWeight > 0 ? $totalWeight : 300,
            'length' => 15,
            'width' => 15,
            'height' => 10,
        ]);
    }
}
