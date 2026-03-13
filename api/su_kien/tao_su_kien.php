<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_su_kien.php';

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

$idNguoiTao = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;

if ($idNguoiTao <= 0 && isset($input['id_nguoi_tao'])) {
    $idNguoiTao = (int) $input['id_nguoi_tao'];
}

if ($idNguoiTao <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập hoặc thiếu thông tin người tạo',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = btc_tao_su_kien(
    $conn,
    $idNguoiTao,
    $input['ten_su_kien'] ?? '',
    $input['mo_ta'] ?? '',
    $input['id_cap'] ?? null,
    $input['ngay_mo_dk'] ?? null,
    $input['ngay_dong_dk'] ?? null,
    $input['ngay_bat_dau'] ?? null,
    $input['ngay_ket_thuc'] ?? null,
    $input['is_active'] ?? 1,
    isset($input['co_gvhd_theo_su_kien']) ? ((int) $input['co_gvhd_theo_su_kien'] === 1 ? 1 : 0) : 1
);

$success = isset($result['status']) && $result['status'] === true;

http_response_code($success ? 200 : 400);
echo json_encode([
    'status' => $success ? 'success' : 'error',
    'message' => $result['message'] ?? ($success ? 'Thành công' : 'Có lỗi xảy ra'),
    'data' => $result,
], JSON_UNESCAPED_UNICODE);
