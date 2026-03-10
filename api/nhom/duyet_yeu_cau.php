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
$idYeuCau  = (int) ($input['id_yeu_cau'] ?? 0);
$trangThai = (int) ($input['trang_thai'] ?? 0);

if ($idYeuCau <= 0 || !in_array($trangThai, [1, 2], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy idSK từ yêu cầu → nhóm
$ycCheck = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $idYeuCau);
if (!$ycCheck) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$nhomCheck = lay_nhom_theo_id($conn, (int) $ycCheck['idNhom']);
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
    $result = duyet_yeu_cau_nhom($conn, $idTK, $idYeuCau, $trangThai);
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
