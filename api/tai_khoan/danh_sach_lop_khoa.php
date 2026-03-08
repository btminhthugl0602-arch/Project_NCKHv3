<?php
/**
 * API: Danh sách lớp + khoa (dùng cho dropdown modal tạo tài khoản)
 * GET /api/tai_khoan/danh_sach_lop_khoa.php
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';


header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmtLop = $conn->query('SELECT idLop, maLop, tenLop, idKhoa FROM lop ORDER BY maLop ASC');
    $dsLop   = $stmtLop->fetchAll(PDO::FETCH_ASSOC);

    $stmtKhoa = $conn->query('SELECT idKhoa, maKhoa, tenKhoa FROM khoa ORDER BY maKhoa ASC');
    $dsKhoa   = $stmtKhoa->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'  => 'success',
        'message' => 'OK',
        'data'    => ['dsLop' => $dsLop, 'dsKhoa' => $dsKhoa],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
