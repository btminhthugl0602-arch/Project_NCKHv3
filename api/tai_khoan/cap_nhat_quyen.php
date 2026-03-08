<?php
/**
 * API: Cập nhật quyền hệ thống cho tài khoản
 * POST /api/tai_khoan/cap_nhat_quyen.php
 * Body: { "id_tai_khoan": X, "danh_sach_ma_quyen": ["quan_ly_tai_khoan", ...] }
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_tai_khoan.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('quan_ly_tai_khoan');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$idTK      = isset($body['id_tai_khoan']) ? (int) $body['id_tai_khoan'] : 0;
$dsQuyen   = isset($body['danh_sach_ma_quyen']) && is_array($body['danh_sach_ma_quyen'])
             ? $body['danh_sach_ma_quyen']
             : [];
$idNguoiTH = (int) ($_SESSION['idTK'] ?? 0);

if ($idTK <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_tai_khoan', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = admin_cap_nhat_quyen_tai_khoan($conn, $idNguoiTH, $idTK, $dsQuyen);

http_response_code($result['status'] ? 200 : 403);
echo json_encode([
    'status'  => $result['status'] ? 'success' : 'error',
    'message' => $result['message'],
    'data'    => null,
], JSON_UNESCAPED_UNICODE);
