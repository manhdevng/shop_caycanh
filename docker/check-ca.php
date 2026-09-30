<?php

/**
 * Kiểm tra chứng chỉ CA của Aiven trước khi Laravel kết nối MySQL.
 * Gọi từ docker/entrypoint.sh với quyền www-data:  php docker/check-ca.php
 * Mục đích: báo lỗi rõ ràng ngay lúc khởi động (dán thiếu dòng BEGIN/END,
 * dán nhầm Service URI, file rỗng, ...) thay vì lỗi PDO khó hiểu về sau.
 */

declare(strict_types=1);

$path = (string) getenv('MYSQL_ATTR_SSL_CA');

$fail = static function (string $message): never {
    fwrite(STDERR, "[check-ca] {$message}\n");
    exit(1);
};

if ($path === '' || ! is_file($path) || ! is_readable($path)) {
    $fail("Không đọc được file CA tại '{$path}'.");
}

$pem = (string) file_get_contents($path);

if (! str_contains($pem, '-----BEGIN CERTIFICATE-----') || ! str_contains($pem, '-----END CERTIFICATE-----')) {
    $fail('File CA không đúng định dạng PEM (thiếu dòng BEGIN/END CERTIFICATE). Hãy dán lại đầy đủ nội dung ca.pem từ Aiven.');
}

$cert = @openssl_x509_read($pem);
if ($cert === false) {
    $fail('Nội dung ca.pem không phải chứng chỉ X.509 hợp lệ.');
}

$info = openssl_x509_parse($cert) ?: [];
$validTo = (int) ($info['validTo_time_t'] ?? 0);

if ($validTo > 0 && $validTo < time()) {
    $fail('Chứng chỉ CA đã hết hạn ('.gmdate('Y-m-d', $validTo).'). Tải lại ca.pem từ Aiven.');
}

$subject = $info['subject']['CN'] ?? ($info['name'] ?? 'unknown');
echo '[check-ca] MySQL CA OK: '.$subject.' (hết hạn '.gmdate('Y-m-d', $validTo).")\n";
