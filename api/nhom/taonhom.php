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
$tenNhom   = trim((string) ($input['ten_nhom']   ?? ''));
$moTa      = trim((string) ($input['mo_ta']      ?? ''));
$soLuong   = (int)    ($input['so_luong_toi_da'] ?? 5);
$dangTuyen = isset($input['dang_tuyen']) ? ((int)$input['dang_tuyen'] === 1 ? 1 : 0) : 1;

if ($idSk <= 0 || $tenNhom === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_nhom($idSk, 'tao_nhom');
$idTK  = $actor['idTK'];

try {
    $result = tao_nhom_moi($conn, $idTK, $idSk, $tenNhom, $moTa, $soLuong, $dangTuyen);

    if ($result['status'] === true) {
        echo json_encode([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => ['idnhom' => $result['idnhom'], 'manhom' => $result['manhom']],
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
