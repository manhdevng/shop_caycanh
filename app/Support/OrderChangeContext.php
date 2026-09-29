<?php

namespace App\Support;

use Closure;

/**
 * Ngữ cảnh của một lần thay đổi trạng thái đơn hàng.
 *
 * Mọi chỗ trong dự án đều đổi trạng thái đơn bằng Eloquent `->update()`, nên
 * OrderObserver là nơi duy nhất ghi lịch sử. Nhưng Observer không biết ai vừa
 * đổi và vì sao. Lớp này là "biến toàn cục có kiểm soát": trước khi update,
 * gọi bên ngoài set ngữ cảnh (nguồn, ghi chú, người thao tác, thời điểm thật),
 * Observer đọc ra để ghi vào order_status_histories.
 *
 * Luôn ưu tiên run() thay vì set()/reset() thủ công, vì run() reset trong
 * finally nên ngữ cảnh không rò rỉ sang lần update kế tiếp khi có exception.
 *
 *     OrderChangeContext::run(['source' => 'admin', 'actor_id' => $id], function () use ($order) {
 *         $order->update(['shipping_status' => 'delivering']);
 *     });
 *
 * Khoá hợp lệ:
 * - source      : customer|admin|system|momo|ghn_webhook|ghn_sync|scheduler (mặc định 'system')
 * - note        : lý do huỷ, Reason từ GHN… (string|null)
 * - actor_id    : id user thao tác (int|null)
 * - occurred_at : thời điểm thật của sự kiện (Carbon|string|null, null = now)
 */
class OrderChangeContext
{
    /**
     * Giá trị mặc định khi không ai set ngữ cảnh — coi như hệ thống tự đổi.
     */
    private const DEFAULTS = [
        'source' => 'system',
        'note' => null,
        'actor_id' => null,
        'occurred_at' => null,
    ];

    /**
     * @var array<string, mixed>
     */
    private static array $context = self::DEFAULTS;

    /**
     * Chạy $fn với ngữ cảnh $ctx, luôn trả ngữ cảnh về mặc định sau đó.
     * Trả về đúng giá trị mà $fn trả về.
     */
    public static function run(array $ctx, Closure $fn): mixed
    {
        $previous = self::$context;

        self::set($ctx);

        try {
            return $fn();
        } finally {
            self::$context = $previous;
        }
    }

    /**
     * Ghi đè các khoá được truyền vào, giữ nguyên các khoá còn lại.
     */
    public static function set(array $ctx): void
    {
        foreach ($ctx as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                self::$context[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$context[$key] ?? $default;
    }

    /**
     * Toàn bộ ngữ cảnh hiện tại (tiện cho Observer lấy 1 lần).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$context;
    }

    public static function reset(): void
    {
        self::$context = self::DEFAULTS;
    }
}
