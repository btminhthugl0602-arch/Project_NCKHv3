<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_su_kien.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
// Guest được phép đọc thông tin tổng quan sự kiện công khai
$_isGuestRequest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!$_isGuestRequest) {
    $actor = auth_require_login();
} else {
    $actor = ['idTK' => 0, 'idLoaiTK' => 0, 'hoTen' => 'Khách'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai tham số id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $detail = btc_lay_chi_tiet_su_kien($conn, $idSk);

    if (!$detail) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không tìm thấy sự kiện',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy chi tiết sự kiện thành công',
        'data' => $detail,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy chi tiết sự kiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
