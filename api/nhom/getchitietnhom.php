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

$idNhom = isset($_GET['id_nhom']) ? (int) $_GET['id_nhom'] : 0;

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy idSK từ nhóm
$nhomCheck = lay_nhom_theo_id($conn, $idNhom);
if (!$nhomCheck) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$idSk = (int) $nhomCheck['idSK'];

// ── Auth: chỉ yêu cầu đăng nhập ───────────
$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $nhom = lay_chi_tiet_nhom($conn, $idNhom, $idTK);
    if (!$nhom) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($nhom['_forbidden']) && $nhom['_forbidden']) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xem nhóm này', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'status'  => 'success',
        'message' => 'Lấy chi tiết nhóm thành công',
        'data'    => $nhom,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
