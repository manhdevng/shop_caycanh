<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Lọc HTML do admin nhập (nội dung trang tĩnh / bài viết) theo allowlist
 * trước khi lưu và trước khi render bằng {!! !!} — phòng thủ nhiều lớp
 * chống stored XSS (script, onerror=..., javascript: URL...).
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::purifier()->purify($html);
    }

    /**
     * Khởi tạo HTMLPurifier 1 lần cho mỗi request. Danh sách thẻ khớp với
     * các thẻ HTML cơ bản mà form admin gợi ý (textarea nhập HTML thô,
     * không dùng rich-text editor).
     */
    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u',
            'h2', 'h3', 'h4',
            'ul', 'ol', 'li',
            'a[href|title|target]',
            'img[src|alt|width|height]',
            'blockquote',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'span', 'hr',
        ]));
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        // Cho phép mở link ở tab mới; HTMLPurifier tự thêm rel="noopener noreferrer".
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        // Cache definition vào storage để tránh ghi vào thư mục vendor.
        $cacheDir = storage_path('app/purifier');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            $config->set('Cache.SerializerPath', $cacheDir);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
