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

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom    = (int)    ($input['id_nhom']      ?? 0);
$tenNhom   = trim((string) ($input['ten_nhom'] ?? ''));
$moTa      = trim((string) ($input['mo_ta']    ?? ''));
$dangTuyen = isset($input['dang_tuyen']) ? ((int) $input['dang_tuyen'] === 1 ? 1 : 0) : 1;
$isActive  = isset($input['is_active']) ? (int) $input['is_active'] : null;

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tenNhom === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tên nhóm', 'data' => null], JSON_UNESCAPED_UNICODE);
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
    $result = cap_nhat_thong_tin_nhom($conn, $idTK, $idNhom, $tenNhom, $moTa, $dangTuyen, $isActive);

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
