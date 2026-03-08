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
$idSk   = isset($_GET['id_sk'])   ? (int) $_GET['id_sk']   : 0;

if ($idNhom <= 0 || $idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom hoặc id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth lớp 1: có quyền xem_nhom trong sự kiện ───────────
$actor = auth_require_quyen_nhom($idSk, 'xem_nhom');
$idTK  = $actor['idTK'];

// ── Auth lớp 2: phải là thành viên nhóm đó ────────────────
$isThanhVien = kiem_tra_thanh_vien_nhom($conn, $idTK, $idNhom);
if (!$isThanhVien) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Bạn không phải thành viên của nhóm này', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $nhom = lay_chi_tiet_nhom($conn, $idNhom);
    if (!$nhom) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
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
