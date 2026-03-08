<?php
/**
 * API: Tìm kiếm sinh viên hoặc giảng viên
 * GET /api/nhom/tim_kiem.php?loai=sv&q=keyword
 * GET /api/nhom/tim_kiem.php?loai=gv&q=keyword
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

$q    = trim((string) ($_GET['q']    ?? ''));
$loai = trim((string) ($_GET['loai'] ?? ''));

if (!in_array($loai, ['sv', 'gv'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tham số loai phải là sv hoặc gv', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Cho phép q rỗng → trả về top 20 để hiện danh sách khi mở modal

try {
    $data = ($loai === 'sv')
        ? tim_kiem_sinh_vien($conn, $q)
        : tim_kiem_giang_vien($conn, $q);

    echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}