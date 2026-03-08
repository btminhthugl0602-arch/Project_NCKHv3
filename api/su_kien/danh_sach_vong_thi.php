<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
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

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai tham số id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idNguoiThucHien = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idNguoiThucHien <= 0 && isset($_GET['id_nguoi_thuc_hien'])) {
    $idNguoiThucHien = (int) $_GET['id_nguoi_thuc_hien'];
}

if ($idNguoiThucHien > 0 && !co_quyen_quan_ly_vong_thi($conn, $idNguoiThucHien, $idSk)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Không có quyền xem cấu hình vòng thi của sự kiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
