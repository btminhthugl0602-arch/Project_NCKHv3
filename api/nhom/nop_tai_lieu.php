<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$idNhom    = (int) ($_POST['id_nhom'] ?? 0);
$idVongThi = (int) ($_POST['id_vong_thi'] ?? 0);

if ($idNhom <= 0 || $idVongThi <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom hoặc id_vong_thi', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy idSK từ nhóm
$nhomCheck = lay_nhom_theo_id($conn, $idNhom);
if (!$nhomCheck) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$idSk = (int) $nhomCheck['idSK'];

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (quyền đã được gán qua role)
$actor = auth_require_login();
$idTK  = $actor['idTK'];

// Parse field values từ $_POST
$fieldValues = [];
foreach ($_POST as $key => $val) {
    if (str_starts_with($key, 'field_')) {
        $idField = (int) substr($key, 6);
        if ($idField > 0) {
            $fieldValues[$idField] = $val;
        }
    }
}

// Parse uploaded files từ $_FILES
$uploadedFiles = [];
foreach ($_FILES as $key => $fileInfo) {
    if (str_starts_with($key, 'file_')) {
        $idField = (int) substr($key, 5);
        if ($idField > 0 && $fileInfo['error'] === UPLOAD_ERR_OK) {
            $uploadedFiles[$idField] = $fileInfo;
        }
    }
}

try {
    $result = nop_tai_lieu_vong($conn, $idTK, $idNhom, $idVongThi, $fieldValues, $uploadedFiles);

    if ($result['status'] === true) {
        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
