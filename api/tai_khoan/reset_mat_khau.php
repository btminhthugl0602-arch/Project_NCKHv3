<?php

/**
 * API: Admin đặt lại mật khẩu tài khoản
 * POST /api/tai_khoan/reset_mat_khau.php
 * Body: { "id_tai_khoan": X, "mat_khau_moi": "...", "xac_nhan_mat_khau": "..." }
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_tai_khoan.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$body            = json_decode(file_get_contents('php://input'), true) ?? [];
$idTK            = isset($body['id_tai_khoan'])      ? (int)    $body['id_tai_khoan']      : 0;
$matKhauMoi      = isset($body['mat_khau_moi'])      ? (string) $body['mat_khau_moi']      : '';
$xacNhanMatKhau  = isset($body['xac_nhan_mat_khau']) ? (string) $body['xac_nhan_mat_khau'] : '';
$idNguoiTH       = (int) ($_SESSION['idTK'] ?? 0);

if ($idTK <= 0 || $matKhauMoi === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tham số không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($matKhauMoi !== $xacNhanMatKhau) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu xác nhận không khớp', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = admin_reset_mat_khau($conn, $idNguoiTH, $idTK, $matKhauMoi);

http_response_code($result['status'] ? 200 : 403);
echo json_encode([
    'status'  => $result['status'] ? 'success' : 'error',
    'message' => $result['message'],
    'data'    => null,
], JSON_UNESCAPED_UNICODE);
