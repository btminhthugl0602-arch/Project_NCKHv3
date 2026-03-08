<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$input     = json_decode(file_get_contents('php://input') ?: '{}', true) ?? [];
$idUser    = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
$idSK      = isset($input['id_sk'])       ? (int) $input['id_sk']       : 0;
$idBo      = isset($input['id_bo'])       ? (int) $input['id_bo']       : 0;
$idVongThi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;

if ($idUser === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Chưa xác thực']);
    exit;
}

if ($idSK <= 0 || $idBo <= 0 || $idVongThi <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu tham số hợp lệ (id_sk, id_bo, id_vong_thi)']);
    exit;
}

$result = go_bo_tieu_chi_khoi_vong($conn, $idUser, $idSK, $idBo, $idVongThi);

if ($result['status']) {
    echo json_encode([
        'status'   => 'success',
        'message'  => $result['message'],
        'warnings' => $result['warnings'] ?? [],
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $result['message'],
    ]);
}
