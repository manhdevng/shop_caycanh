// Cấu hình riêng để chụp ảnh báo cáo trên server đang chạy sẵn (dữ liệu thật).
// Không tự bật server E2E, không đăng nhập sẵn bằng tài khoản mẫu.
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e/specs',
    testMatch: 'chup-bao-cao.spec.js',
    timeout: 60_000,
    workers: 1,
    retries: 0,
    use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.BAOCAO_URL || 'http://127.0.0.1:8000',
    },
    reporter: [['list']],
    outputDir: 'tests/e2e/artifacts-baocao',
});
