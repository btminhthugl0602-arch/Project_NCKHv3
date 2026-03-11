<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$idBo = isset($_GET['id_bo']) ? (int) $_GET['id_bo'] : 0;

if ($idSk <= 0 || $idBo <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu id_sk hoặc id_bo',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idUser <= 0 && isset($_GET['id_nguoi_thuc_hien'])) {
    $idUser = (int) $_GET['id_nguoi_thuc_hien'];
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
    $result = lay_chi_tiet_day_du_bo_tieu_chi($conn, $idUser, $idSk, $idBo);
    if (empty($result['status'])) {
        throw new RuntimeException($result['message'] ?? 'Không thể lấy chi tiết bộ tiêu chí');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy chi tiết bộ tiêu chí thành công',
        'data' => $result['data'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi lấy chi tiết bộ tiêu chí',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
