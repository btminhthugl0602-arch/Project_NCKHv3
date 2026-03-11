<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_su_kien.php';

header('Content-Type: application/json; charset=utf-8');

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

// ── Auth ──────────────────────────────────────────────────
$idSk = (int)($input['id_su_kien'] ?? 0);
$actor = auth_require_quyen_su_kien($idSk, 'cauhinh_sukien');

$idNguoiThucHien = $actor['idTK'];

$result = btc_cap_nhat_su_kien(
    $conn,
    $idNguoiThucHien,
    $input['id_su_kien'] ?? 0,
    $input['ten_su_kien'] ?? '',
    $input['mo_ta'] ?? '',
    $input['id_cap'] ?? null,
    $input['ngay_mo_dk'] ?? null,
    $input['ngay_dong_dk'] ?? null,
    $input['ngay_bat_dau'] ?? null,
    $input['ngay_ket_thuc'] ?? null,
    $input['is_active'] ?? 1,
    isset($input['so_thanh_vien_toi_thieu']) ? (int)$input['so_thanh_vien_toi_thieu'] : null,
    isset($input['so_thanh_vien_toi_da'])    ? (int)$input['so_thanh_vien_toi_da']    : null,
    array_key_exists('so_gvhd_toi_da',      $input) ? ($input['so_gvhd_toi_da']      !== null ? (int)$input['so_gvhd_toi_da']      : null) : false,
    array_key_exists('so_nhom_toi_da_gvhd', $input) ? ($input['so_nhom_toi_da_gvhd'] !== null ? (int)$input['so_nhom_toi_da_gvhd'] : null) : false,
    isset($input['yeu_cau_co_gvhd'])         ? ((int)$input['yeu_cau_co_gvhd']         === 1 ? 1 : 0) : null,
    isset($input['cho_phep_gv_tao_nhom'])    ? ((int)$input['cho_phep_gv_tao_nhom']    === 1 ? 1 : 0) : null
);

$success = isset($result['status']) && $result['status'] === true;
http_response_code($success ? 200 : 400);

echo json_encode([
    'status' => $success ? 'success' : 'error',
    'message' => $result['message'] ?? ($success ? 'Thành công' : 'Có lỗi xảy ra'),
    'data' => $result,
], JSON_UNESCAPED_UNICODE);
