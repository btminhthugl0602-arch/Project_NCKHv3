<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_to_chuc.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk      = (int) ($_GET['id_sk']       ?? 0);
$idPhienDD = (int) ($_GET['id_phien_dd'] ?? 0);

$actor = auth_require_quyen_su_kien($idSk, 'cauhinh_sukien');

if ($idPhienDD <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_phien_dd'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = lay_danh_sach_diem_danh($conn, $idPhienDD, $idSk);

echo json_encode([
    'status' => 'success',
    'data'   => $data,
], JSON_UNESCAPED_UNICODE);
