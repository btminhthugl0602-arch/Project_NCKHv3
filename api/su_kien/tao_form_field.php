<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_form_field.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$idSK      = (int) ($input['id_sk'] ?? 0);
$idVongThi = isset($input['id_vong_thi']) && $input['id_vong_thi'] !== null
    ? (int) $input['id_vong_thi']
    : null;

if ($idSK <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = tao_form_field($conn, $idTK, $idSK, $idVongThi, $input);
    if ($result['status'] === true) {
        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => ['idField' => $result['idField']]], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
