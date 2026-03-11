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

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom     = (int) ($input['id_nhom'] ?? 0);
$tenSanPham = trim((string) ($input['ten_san_pham'] ?? ''));
$idChuDeSK  = isset($input['id_chu_de_sk']) && $input['id_chu_de_sk'] !== null
    ? (int) $input['id_chu_de_sk']
    : null;

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

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (quyền đã được gán qua role)
$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $result = tao_hoac_cap_nhat_san_pham($conn, $idTK, $idNhom, $tenSanPham, $idChuDeSK);

    if ($result['status'] === true) {
        echo json_encode([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => ['idSanPham' => $result['idSanPham']],
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
