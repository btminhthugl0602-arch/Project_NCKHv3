<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom      = (int)    ($input['id_nhom']       ?? 0);
$action      = trim((string) ($input['action']   ?? ''));
$idNguoiNhan = (int)    ($input['id_nguoi_nhan'] ?? 0);

// Validate input
if ($idNhom <= 0 || $idNguoiNhan <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom hoặc id_nguoi_nhan', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($action, ['chu_nhom', 'truong_nhom'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ. Chỉ chấp nhận: chu_nhom, truong_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy id_sk từ nhóm trong DB (tránh giả mạo)
$nhom = lay_nhom_theo_id($conn, $idNhom);
if (!$nhom) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$idSk = (int) $nhom['idSK'];

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (quyền đã được gán qua role)
$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $result = nhuong_quyen_nhom($conn, $idTK, $idNhom, $action, $idNguoiNhan);

    if ($result['status'] === true) {
        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
