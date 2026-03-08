<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk  = isset($_GET['id_sk'])  ? (int) $_GET['id_sk'] : 0;
$tatCa = isset($_GET['tat_ca']) && (int) $_GET['tat_ca'] === 1;

if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
$idTK = (int) ($_SESSION['idTK'] ?? 0);
if ($idTK <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Chưa đăng nhập', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $loiMoi = lay_loi_moi($conn, $idTK, $idSk, $tatCa);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Lấy lời mời thành công',
        'data'    => $loiMoi,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}