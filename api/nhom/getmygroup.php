<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (không cần quyền sự kiện cụ thể)
$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $nhom = lay_nhom_cua_toi($conn, $idTK, $idSk);
    echo json_encode([
        'status'  => 'success',
        'message' => $nhom ? 'Lấy nhóm thành công' : 'Bạn chưa có nhóm trong sự kiện này',
        'data'    => $nhom,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
