<?php

/**
 * API: Tìm kiếm sinh viên hoặc giảng viên để mời vào nhóm
 * GET /api/nhom/tim_kiem_user.php?loai=sv&q=keyword&id_sk=1
 */
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

$idSk    = isset($_GET['id_sk'])   ? (int) $_GET['id_sk'] : 0;
$keyword = trim((string) ($_GET['keyword'] ?? ''));
$loai    = trim((string) ($_GET['loai']    ?? ''));

if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_nhom($idSk, 'xem_nhom');

if (!in_array($loai, ['sv', 'gv'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tham số loai phải là sv hoặc gv', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Keyword < 2 ký tự và không rỗng → trả mảng rỗng
if ($keyword !== '' && mb_strlen($keyword) < 2) {
    echo json_encode(['status' => 'success', 'message' => 'Từ khoá quá ngắn', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = ($loai === 'sv')
        ? tim_kiem_sinh_vien($conn, $keyword, $idSk)
        : tim_kiem_giang_vien($conn, $keyword, $idSk);
    echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
