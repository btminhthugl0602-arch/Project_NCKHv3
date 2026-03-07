<?php
/**
 * Homepage — Redirect theo trạng thái đăng nhập
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['idTK'])) {
    header('Location: /dashboard');
} else {
    header('Location: /views/dang_nhap.php');
}
exit;
