<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
$idTK = (int) ($_SESSION['user_id'] ?? 0);
if ($idTK <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom    = (int)    ($input['id_nhom']       ?? 0);
$idSk      = (int)    ($input['id_sk']         ?? 0);
$idChuDeSK = (int)    ($input['id_chu_de_sk']  ?? 0);
$tenDeTai  = trim((string) ($input['ten_de_tai'] ?? ''));
$moTa      = trim((string) ($input['mo_ta']      ?? ''));
$linkTL    = trim((string) ($input['link_tai_lieu'] ?? ''));

if ($idNhom <= 0 || $idSk <= 0 || $tenDeTai === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = nop_bai_nhom($conn, $idTK, $idNhom, $idSk, $tenDeTai, $moTa, $linkTL, $idChuDeSK);
    $httpCode = $result['status'] ? 200 : 400;
    http_response_code($httpCode);
    echo json_encode([
        'status'  => $result['status'] ? 'success' : 'error',
        'message' => $result['message'],
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}