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
$idSk      = (int) ($input['id_sk']      ?? 0);
$idYeuCau  = (int) ($input['id_yeu_cau'] ?? 0);
$trangThai = (int) ($input['trang_thai'] ?? 0);

if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idYeuCau <= 0 || !in_array($trangThai, [1, 2], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_nhom($idSk, 'xem_nhom');
$idTK  = $actor['idTK'];

// Logic chi tiết (là chủ nhóm hay người được mời) xử lý trong duyet_yeu_cau_nhom()
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
