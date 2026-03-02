<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_vong_thi.php';

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

$idNguoiTao = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
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

$result = tao_vong_thi(
    $conn,
    $idNguoiTao,
    $input['id_sk'] ?? 0,
    $input['ten_vong'] ?? '',
    $input['mo_ta'] ?? '',
    $input['thu_tu'] ?? 1,
    $input['ngay_bat_dau'] ?? null,
    $input['ngay_ket_thuc'] ?? null
);

$success = isset($result['status']) && $result['status'] === true;
http_response_code($success ? 200 : 400);

echo json_encode([
    'status' => $success ? 'success' : 'error',
    'message' => $result['message'] ?? ($success ? 'Thành công' : 'Có lỗi xảy ra'),
    'data' => $result,
], JSON_UNESCAPED_UNICODE);
