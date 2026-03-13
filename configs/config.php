<?php
/**
 * configs/config.php
 * Cấu hình bí mật cho ứng dụng.
 * !! KHÔNG commit file này lên git !!
 *
 * Đổi NCKH_SECRET_KEY thành một chuỗi bất kỳ, dài, khó đoán.
 * Chuỗi này dùng để ký HMAC token QR điểm danh.
 * Sau khi đổi, tất cả QR cũ sẽ hết hạn (bình thường).
 */

if (!defined('NCKH_SECRET_KEY')) {
    define('NCKH_SECRET_KEY', 'nckh_dev_secret_2024');
    // ↑ Thay chuỗi trên trước khi deploy, ví dụ: 'x7Km2qL9nR4wV8pT3hY6bN1eA5dC0fJ'
}
