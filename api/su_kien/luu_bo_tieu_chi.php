<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('tao_su_kien');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($input)) {
    $input = [];
}

$idSk = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idUser <= 0 && isset($input['id_nguoi_thuc_hien'])) {
    $idUser = (int) $input['id_nguoi_thuc_hien'];
}

if ($idUser <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = luu_bo_tieu_chi_theo_su_kien($conn, $idUser, $idSk, $input);
    if (empty($result['status'])) {
        throw new RuntimeException($result['message'] ?? 'Không thể lưu bộ tiêu chí');
    }

    echo json_encode([
        'status' => 'success',
        'message' => $result['message'] ?? 'Lưu bộ tiêu chí thành công',
        'data' => $result['data'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi lưu bộ tiêu chí',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
