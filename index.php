<?php
/**
 * Homepage / Dashboard
 * Redirect to dashboard or login page
 */

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Location: views/dashboard.php');
// Kiểm tra đăng nhập
// if (isset($_SESSION['user_id'])) {
//     // Đã đăng nhập, chuyển đến dashboard
//     header('Location: dashboard');
//     exit();
// } else {
//     // Chưa đăng nhập, chuyển đến trang đăng nhập
//     header('Location: views/sign-in.php');
//     exit();
// }
