<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Models\OrderStatusHistory;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\GHNShipmentSyncService;
use App\Services\MomoService;
use App\Services\OrderCancellationService;
use App\Support\OrderChangeContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // ==========================================
    // 1. CÁC VIEW HIỂN THỊ ĐƠN HÀNG & THANH TOÁN
    // ==========================================

    public function index(Request $request, GHNService $ghn)
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
        if (! empty($voucherSession['id'])) {
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

        // Sổ địa chỉ (kiểu Shopee): địa chỉ mặc định được chọn sẵn ở trang thanh toán.
        $addresses = $user
            ? $user->addresses()->get()->map(fn (UserAddress $a) => UserAddressController::toArray($a))->values()
            : collect();

        $pastAddresses = $user ? $this->pastOrderAddresses($user->id, $addresses, $ghn) : collect();

        return view('checkout.payment', compact('cart', 'totalPrice', 'voucher', 'discountAmount', 'availableVouchers', 'savableVouchers', 'addresses', 'pastAddresses'));
    }

    // Đặt hàng: tạo Order + OrderItem từ giỏ hàng trong session, sau đó tạo vận đơn bên GHN.
    public function store(Request $request, GHNService $ghn)
    {
        // G10: chặn double-submit — bấm "Đặt hàng" 2 lần liên tiếp (double
        // click, double-tap trên mobile...) có thể khiến 2 request cùng đọc
        // session giỏ hàng và cùng tạo đơn trước khi request đầu xoá giỏ
        // hàng/commit xong. Các khoá DB (lockForUpdate) trong storeLocked()
        // chỉ chống ÂM KHO, không chống việc tạo 2 đơn trùng từ cùng 1 giỏ.
        $lock = Cache::lock('checkout:user:'.Auth::id(), 15);

        if (! $lock->get()) {
            return back()->with('error', 'Đơn hàng đang được xử lý, vui lòng không bấm nhiều lần.');
        }

        try {
            return $this->storeLocked($request, $ghn);
        } finally {
            $lock->release();
        }
    }

    private function storeLocked(Request $request, GHNService $ghn)
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

        // Chọn địa chỉ có sẵn trong sổ: lấy dữ liệu từ DB (của chính khách
        // này), không tin các ô name/phone/address client gửi kèm.
        $savedAddress = $request->filled('address_id')
            ? UserAddress::where('user_id', Auth::id())->find($request->input('address_id'))
            : null;

        if ($savedAddress) {
            $request->merge([
                'name' => $savedAddress->name,
                'phone' => $savedAddress->phone,
                'address' => $savedAddress->address,
                'to_district_id' => $savedAddress->district_id,
                'to_ward_code' => $savedAddress->ward_code,
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
            'payment_method' => 'required|in:cod,momo,bank_transfer',
            'momo_card_type' => 'nullable|in:atm,cc,wallet',
            'transfer_ref' => 'nullable|string|max:100',
            'province_id' => 'nullable|integer',
            'province_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'ward_name' => 'nullable|string|max:255',
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

        // G11: chặn sớm nếu tổng tiền ngoài giới hạn MoMo chấp nhận cho tham
        // số "amount" của API tạo thanh toán (xem config/services.php ->
        // momo.min_amount/max_amount) — tránh tạo đơn "pending" rác vì MoMo
        // chắc chắn từ chối tạo payUrl.
        if ($request->payment_method === 'momo') {
            $momoMin = (int) config('services.momo.min_amount');
            $momoMax = (int) config('services.momo.max_amount');

            if ($totalPrice < $momoMin || $totalPrice > $momoMax) {
                return back()->withInput()->with('error', 'Với hình thức thanh toán MoMo, tổng tiền đơn hàng phải từ '
                    .number_format($momoMin, 0, ',', '.').'đ đến '.number_format($momoMax, 0, ',', '.').'đ. Vui lòng chọn hình thức thanh toán khác.');
            }
        }

        // Voucher (nếu có) chỉ mới được lưu tạm trong session ở bước
        // VoucherController::apply() — KHÔNG tin discount_amount đã tính sẵn
        // ở đó, phải tính lại từ voucher thật trong transaction bên dưới.
        $voucherSession = session('voucher');

        try {
            $order = DB::transaction(function () use ($request, $cart, $totalPrice, $shippingFee, $voucherSession) {
                $discountAmount = 0;
                $voucherId = null;

                $user = Auth::user();

                if (! empty($voucherSession['id'])) {
                    // Khoá bản ghi voucher (SELECT ... FOR UPDATE) trước khi kiểm
                    // tra + tăng used_count, để 2 khách cùng dùng nốt lượt cuối
                    // cùng lúc phải xếp hàng chờ nhau thay vì cùng vượt usage_limit.
                    $voucher = Voucher::where('id', $voucherSession['id'])->lockForUpdate()->first();

                    if (! $voucher) {
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
                // COD: chờ shop xác nhận rồi mới giao GHN (AdminOrderController::confirmOrder()).
                $shippingStatus = match ($request->payment_method) {
                    'bank_transfer' => 'pending',
                    'cod' => 'awaiting_confirmation',
                    default => 'not_shipped',
                };

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

                    if (! $product) {
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
                    if (! empty($item['variant_id'])) {
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

        if (! $savedAddress && $request->boolean('save_address')) {
            $this->saveAddressFromOrder($request, $ghn);
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
                ->with('order_just_placed', true)
                ->with('success', 'Đặt hàng thành công! Vui lòng chuyển khoản theo thông tin ngân hàng và chờ xác nhận. Mã đơn hàng #'.$order->id.'.');
        }

        // ==== Thanh toán khi nhận hàng (COD): đơn ở "awaiting_confirmation",
        // CHƯA tạo vận đơn GHN — shop xác nhận đơn ở trang admin
        // (AdminOrderController::confirmOrder()) rồi mới giao cho GHN. ====
        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'cod',
            'amount' => $order->total_price,
            'status' => 'pending',
            'message' => 'Thanh toán khi nhận hàng',
        ]);

        // Gửi email xác nhận đơn hàng — xem chú thích ở nhánh MoMo phía trên.
        $this->sendOrderConfirmationEmail($order);

        return redirect()->route('orders.show', $order)
            ->with('order_just_placed', true)
            ->with('success', 'Đặt hàng thành công! Mã đơn hàng #'.$order->id.'. Shop sẽ xác nhận đơn và giao cho đơn vị vận chuyển.');
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
            Log::error('Gửi email xác nhận đơn hàng thất bại: '.$e->getMessage(), ['order_id' => $order->id]);
        }
    }

    /**
     * Trang "Đơn mua" — danh sách đơn của khách, lọc theo tab giống Shopee.
     * Tab lạ (gõ tay trên URL) bị ép về 'tat-ca' thay vì báo lỗi 404.
     */
    public function orderHistory(Request $request)
    {
        $tab = (string) $request->query('tab', 'tat-ca');

        if (! array_key_exists($tab, Order::TABS)) {
            $tab = 'tat-ca';
        }

        $orders = Order::where('user_id', Auth::id())
            ->forTab($tab)
            ->with('items.product', 'paymentTransactions')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('orders.history', [
            'orders' => $orders,
            'tab' => $tab,
            'tabCounts' => $this->tabCounts(),
        ]);
    }

    /**
     * Số đơn của từng tab để hiện badge trên thanh tab. Mỗi tab là 1 count
     * nhẹ trên cùng một index (user_id) — rẻ hơn và dễ đọc hơn một câu SQL
     * CASE WHEN khổng lồ phải đồng bộ tay với scopeForTab().
     *
     * @return array<string, int>
     */
    private function tabCounts(): array
    {
        $counts = [];

        foreach (array_keys(Order::TABS) as $key) {
            $counts[$key] = Order::where('user_id', Auth::id())->forTab($key)->count();
        }

        return $counts;
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $order->load('items.product', 'paymentTransactions', 'statusHistories', 'voucher');

        return view('orders.show', [
            'order' => $order,
            'timeline' => $this->buildTimeline($order),
        ]);
    }

    /**
     * Hành trình đơn hàng để vẽ timeline dọc: gộp các mốc shipping_status và
     * milestone (placed/completed) thành một danh sách {time, title, note,
     * source}, MỚI NHẤT TRƯỚC.
     *
     * Bỏ qua field 'status' (chờ thanh toán -> đã thanh toán...) trừ mốc
     * 'paid': khách quan tâm hành trình gói hàng, các chuyển dịch nội bộ của
     * trạng thái thanh toán chỉ làm timeline rối.
     *
     * @return Collection<int, array{time: \Illuminate\Support\Carbon, title: string, note: ?string, source: string}>
     */
    private function buildTimeline(Order $order): Collection
    {
        $milestoneLabels = [
            'placed' => 'Đơn hàng đã được đặt',
            'completed' => 'Đơn hàng hoàn thành',
        ];

        return $order->statusHistories
            ->filter(function (OrderStatusHistory $history) {
                if ($history->field === OrderStatusHistory::FIELD_STATUS) {
                    return in_array($history->to_value, ['paid', 'cancelled'], true);
                }

                return true;
            })
            ->map(function (OrderStatusHistory $history) use ($milestoneLabels) {
                $title = match ($history->field) {
                    OrderStatusHistory::FIELD_SHIPPING_STATUS => Order::SHIPPING_LABELS[$history->to_value] ?? $history->to_value,
                    OrderStatusHistory::FIELD_STATUS => Order::STATUS_LABELS[$history->to_value] ?? $history->to_value,
                    default => $milestoneLabels[$history->to_value] ?? $history->to_value,
                };

                return [
                    'time' => $history->occurred_at,
                    'title' => $title,
                    'note' => $history->note,
                    'source' => $history->source,
                ];
            })
            ->sortByDesc('time')
            ->values();
    }

    /**
     * Khách bấm "Đã nhận được hàng" — chốt mốc hoàn thành. Chỉ chủ đơn mới
     * được bấm, và chỉ khi GHN đã báo giao thành công (canConfirmReceived()).
     */
    public function confirmReceived(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $order->canConfirmReceived()) {
            return back()->with('error', 'Đơn hàng chưa được giao xong hoặc bạn đã xác nhận trước đó.');
        }

        OrderChangeContext::run([
            'source' => 'customer',
            'actor_id' => Auth::id(),
            'note' => 'Khách xác nhận đã nhận được hàng.',
        ], function () use ($order) {
            $order->update(['completed_at' => now()]);
        });

        return back()->with('success', 'Cảm ơn bạn! Đơn hàng đã hoàn thành, mời bạn đánh giá sản phẩm.');
    }

    /**
     * Nút "Cập nhật" ở khối theo dõi vận chuyển — hỏi thẳng GHN trạng thái
     * mới nhất. Cần thiết vì máy dev chạy 127.0.0.1 nên GHN không gọi webhook
     * tới được (lỗi L2 trong kế hoạch). Route có throttle để không ai bấm
     * liên tục làm ta bị GHN chặn.
     */
    public function refreshTracking(Order $order, GHNShipmentSyncService $sync)
    {
        if ($order->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        if (blank($order->ghn_order_code)) {
            return back()->with('error', 'Đơn hàng chưa có mã vận đơn GHN để tra cứu.');
        }

        $result = $sync->syncFromDetail($order);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Không tra cứu được trạng thái vận đơn, vui lòng thử lại sau.');
        }

        return back()->with('success', ($result['changed'] ?? false)
            ? 'Đã cập nhật trạng thái vận chuyển mới nhất từ GHN.'
            : 'Trạng thái vận chuyển đã là mới nhất.');
    }

    // C3/G5: khách tự huỷ đơn hàng chưa thanh toán/chưa vào giai đoạn giao hàng.
    public function cancel(
        Order $order,
        OrderCancellationService $cancellation,
        GHNOrderService $ghnOrderService,
        MomoService $momo,
        MomoController $momoController,
    ) {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // Đơn MoMo còn giao dịch đang chờ (initiated) -> hỏi lại MoMo trước
        // khi huỷ: nếu MoMo báo đã thanh toán thì hoàn tất thanh toán thay vì
        // huỷ (tránh trường hợp khách vừa trả tiền xong lại bị huỷ đơn).
        if ($order->payment_method === Order::PAYMENT_MOMO) {
            $transaction = PaymentTransaction::where('order_id', $order->id)
                ->where('gateway', 'momo')
                ->where('status', 'initiated')
                ->whereNotNull('gateway_order_id')
                ->latest('id')
                ->first();

            if ($transaction) {
                $result = $momo->queryTransaction($transaction->gateway_order_id);
                $result['orderId'] = $result['orderId'] ?? $transaction->gateway_order_id;

                if ($momo->isSuccessful($result)) {
                    $momoController->completePayment($result, $ghnOrderService, $momo);

                    return back()->with('error', 'MoMo báo đơn hàng này đã được thanh toán nên không thể huỷ. Vui lòng kiểm tra lại đơn hàng.');
                }
            }
        }

        // Bọc ngữ cảnh để OrderObserver ghi đúng "ai huỷ" vào lịch sử đơn.
        $result = OrderChangeContext::run([
            'source' => 'customer',
            'actor_id' => Auth::id(),
            'note' => 'Khách huỷ đơn',
        ], fn () => $cancellation->cancel(
            $order,
            'Khách hàng tự huỷ đơn.',
            guard: fn (Order $locked) => $locked->canCustomerCancel() ? null : 'Đơn hàng không thể huỷ ở trạng thái hiện tại.',
        ));

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        // Huỷ vận đơn GHN sau khi phần dữ liệu nội bộ đã huỷ thành công.
        // cancelForOrder() không bao giờ ném exception, tự bỏ qua nếu đơn
        // chưa có mã vận đơn (mirror AdminOrderController::cancel()).
        $ghnResult = $ghnOrderService->cancelForOrder($order);

        if (! $ghnResult['success'] && empty($ghnResult['skipped'])) {
            Log::warning('Huỷ đơn hàng #'.$order->id.' (khách tự huỷ) thành công nhưng huỷ vận đơn GHN thất bại', [
                'order_id' => $order->id,
                'ghn_order_code' => $order->ghn_order_code,
                'message' => $ghnResult['message'] ?? null,
                'error' => $ghnResult['error'] ?? null,
            ]);
        }

        return back()->with('success', 'Đã hủy đơn hàng.');
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

        $validator = Validator::make($request->all(), [
            'to_district_id' => 'required|integer|min:1',
            'to_ward_code' => 'required|string',
        ], [
            'to_district_id.*' => 'Quận/Huyện không hợp lệ.',
            'to_ward_code.*' => 'Phường/Xã không hợp lệ.',
        ]);

        // Trả cùng dạng {code, message} như GHN để JS checkout xử lý một kiểu.
        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => $validator->errors()->first()], 422);
        }

        $res = $this->fetchGhnFee($cart, (int) $request->to_district_id, (string) $request->to_ward_code, $ghn);

        // Lỗi thì luôn có message (ưu tiên message thật của GHN) để hiện cho khách.
        if (($res['code'] ?? null) !== 200) {
            $res['message'] = $res['message'] ?? 'Không tính được phí vận chuyển.';
        }

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

            if (! $product || ! $product->is_active) {
                $removed = true;

                continue;
            }

            $variant = null;
            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::find($item['variant_id']);

                if (! $variant || $variant->product_id !== $product->id) {
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
    /**
     * Địa chỉ nhận hàng ở các đơn khách từng đặt mà CHƯA có trong sổ địa chỉ —
     * hiện ở hộp "Địa chỉ của tôi" để lần sau chỉ cần tick chọn. Đơn cũ chỉ
     * lưu mã GHN nên tra lại tên Tỉnh/Quận/Phường qua GHN (có cache).
     */
    private function pastOrderAddresses(int $userId, Collection $savedAddresses, GHNService $ghn): Collection
    {
        $key = fn ($name, $phone, $districtId, $wardCode, $address) => mb_strtolower(implode('|', [trim($name), trim($phone), (int) $districtId, trim($wardCode), trim($address)]));

        $savedKeys = $savedAddresses->map(fn ($a) => $key($a['name'], $a['phone'], $a['district_id'], $a['ward_code'], $a['address']))->all();

        return Order::where('user_id', $userId)
            ->whereNotNull('to_district_id')
            ->whereNotNull('to_ward_code')
            ->latest('id')
            ->limit(30)
            ->get(['id', 'name', 'phone', 'address', 'to_district_id', 'to_ward_code'])
            ->unique(fn (Order $o) => $key($o->name, $o->phone, $o->to_district_id, $o->to_ward_code, $o->address))
            ->reject(fn (Order $o) => in_array($key($o->name, $o->phone, $o->to_district_id, $o->to_ward_code, $o->address), $savedKeys, true))
            ->take(5)
            ->map(function (Order $o) use ($ghn) {
                $names = $ghn->locationNames((int) $o->to_district_id, (string) $o->to_ward_code) ?? [];

                return [
                    'order_id' => $o->id,
                    'name' => $o->name,
                    'phone' => $o->phone,
                    'address' => $o->address,
                    'district_id' => (int) $o->to_district_id,
                    'ward_code' => (string) $o->to_ward_code,
                    'province_id' => $names['province_id'] ?? null,
                    'province_name' => $names['province_name'] ?? '',
                    'district_name' => $names['district_name'] ?? '',
                    'ward_name' => $names['ward_name'] ?? '',
                    'full_address' => collect([$o->address, $names['ward_name'] ?? null, $names['district_name'] ?? null, $names['province_name'] ?? null])->filter()->implode(', '),
                ];
            })
            ->values();
    }

    /**
     * Lưu địa chỉ vừa nhập ở trang thanh toán vào sổ địa chỉ của khách. Trùng
     * hệt 1 địa chỉ đã có thì dùng lại bản cũ. Địa chỉ đầu tiên, hoặc khi khách
     * tick "Đặt làm mặc định", trở thành địa chỉ mặc định.
     */
    private function saveAddressFromOrder(Request $request, GHNService $ghn): void
    {
        $user = Auth::user();

        // Thiếu tên Tỉnh/Quận/Phường (vd. chọn lại địa chỉ đơn cũ khi chưa tra
        // được tên) thì thử tra lại từ cache danh mục GHN.
        $names = $request->filled('ward_name') ? null : $ghn->locationNames((int) $request->to_district_id, (string) $request->to_ward_code);

        $address = UserAddress::firstOrCreate([
            'user_id' => $user->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'district_id' => (int) $request->to_district_id,
            'ward_code' => (string) $request->to_ward_code,
            'address' => $request->address,
        ], [
            'province_id' => $request->input('province_id') ?: ($names['province_id'] ?? null),
            'province_name' => $request->input('province_name') ?: ($names['province_name'] ?? null),
            'district_name' => $request->input('district_name') ?: ($names['district_name'] ?? null),
            'ward_name' => $request->input('ward_name') ?: ($names['ward_name'] ?? null),
        ]);

        $hasOtherDefault = UserAddress::where('user_id', $user->id)->whereKeyNot($address->id)->where('is_default', true)->exists();

        if ($request->boolean('set_default') || ! $hasOtherDefault) {
            $address->makeDefault();
        }
    }

    private function fetchGhnFee(array $cart, int $toDistrictId, string $toWardCode, GHNService $ghn): array
    {
        $cart = $this->revalidateCart($cart)['cart'];

        $totalWeight = 0;

        foreach ($cart as $item) {
            // Mỗi dòng tối thiểu 1g: weight 0/âm trong DB không được kéo tổng về 0.
            $totalWeight += max(1, ((int) ($item['weight'] ?? 200)) * (int) $item['quantity']);
        }

        $payload = [
            'service_type_id' => 2, // Gói chuẩn E-commerce
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'weight' => $totalWeight > 0 ? $totalWeight : 300,
            'length' => 15,
            'width' => 15,
            'height' => 10,
        ];

        // Có phường gửi thì gửi kèm để GHN tính đúng tuyến.
        $fromWardCode = (string) config('services.ghn.from_ward_code');
        if ($fromWardCode !== '') {
            $payload['from_ward_code'] = $fromWardCode;
        }

        $response = $ghn->calculateFee($payload);

        // GHN báo thành công nhưng không có phí hợp lệ: ghi lại để tra cứu.
        if (($response['code'] ?? null) === 200 && (int) ($response['data']['total'] ?? 0) <= 0) {
            Log::warning('GHN trả code 200 nhưng phí vận chuyển thiếu hoặc <= 0', [
                'payload' => $payload,
                'response' => $response,
            ]);
        }

        return $response;
    }
}
