<?php
/**
 * Session Helper
 * Cung cấp $session_user cho các API endpoint.
 * File này được require sau base.php (đã có $conn và session).
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// Đảm bảo session đang chạy
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Biến $session_user dùng chung cho các endpoint
$session_user = [
    'idTK'     => isset($_SESSION['idTK'])     ? (int) $_SESSION['idTK']     : 0,
    'idLoaiTK' => isset($_SESSION['idLoaiTK']) ? (int) $_SESSION['idLoaiTK'] : 0,
    'hoTen'    => isset($_SESSION['hoTen'])    ? (string) $_SESSION['hoTen'] : '',
];
