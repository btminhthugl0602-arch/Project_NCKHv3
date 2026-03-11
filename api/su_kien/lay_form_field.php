<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_form_field.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

$idSK      = (int) ($_GET['id_sk'] ?? 0);
$mode      = trim($_GET['mode'] ?? 'tong_quan'); // tong_quan | fields
$idVongThi = isset($_GET['id_vong_thi']) && $_GET['id_vong_thi'] !== ''
    ? (int) $_GET['id_vong_thi']
    : null;

if ($idSK <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xem cấu hình tài liệu', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mode === 'tong_quan') {
    $data = lay_tong_quan_form_sk($conn, $idSK);
    echo json_encode(['status' => 'success', 'message' => '', 'data' => $data], JSON_UNESCAPED_UNICODE);
} else {
    // mode = 'fields' — trả về danh sách field của 1 vòng (hoặc form mặc định SK)
    $fields = lay_form_fields($conn, $idSK, $idVongThi);
    echo json_encode(['status' => 'success', 'message' => '', 'data' => $fields], JSON_UNESCAPED_UNICODE);
}
