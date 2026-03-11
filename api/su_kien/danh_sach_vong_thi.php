<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_vong_thi.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Đọc idSk trước để truyền vào auth guard
$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

// ── Auth: cho phép BTC và các vai trò liên quan chấm điểm xem danh sách vòng thi ──
$actor = auth_require_bat_ky_quyen_su_kien($idSk, [
    'cauhinh_sukien', 'cauhinh_vongthi', 'phan_cong_cham', 'duyet_diem', 'nhap_diem',
]);

try {
    $rows = lay_ds_vong_thi($conn, $idSk);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy danh sách vòng thi thành công',
        'data' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy danh sách vòng thi',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
