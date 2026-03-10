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
$idSk      = (int)    ($input['id_sk']          ?? 0);
$idNhom    = (int)    ($input['id_nhom']         ?? 0);
$tenDeTai  = trim((string) ($input['ten_de_tai'] ?? ''));
$moTa      = trim((string) ($input['mo_ta']      ?? ''));
$linkTL    = trim((string) ($input['link_tai_lieu'] ?? ''));

if ($idSk <= 0 || $idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk hoặc id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tenDeTai === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tên đề tài', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_nhom($idSk, 'xem_nhom');
$idTK  = $actor['idTK'];

try {
    $result = nop_bai_nhom($conn, $idTK, $idNhom, $idSk, $tenDeTai, $moTa, $linkTL);

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
