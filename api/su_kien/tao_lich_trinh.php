<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_to_chuc.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

$idSk  = (int) ($input['id_sk'] ?? 0);
$actor = auth_require_quyen_su_kien($idSk, 'cauhinh_sukien');

$result = tao_lich_trinh(
    $conn,
    $actor['idTK'],
    $idSk,
    $input['ten_hoat_dong']     ?? '',
    $input['loai_hoat_dong']    ?? 'HOAT_DONG',
    $input['thoi_gian_bat_dau'] ?? '',
    $input['thoi_gian_ket_thuc'] ?? null,
    $input['dia_diem']          ?? null,
    $input['vi_tri_lat']        ?? null,
    $input['vi_tri_lng']        ?? null,
    $input['id_vong_thi']       ?? null,
    $input['id_tieu_ban']       ?? null
);

$ok = $result['status'] ?? false;
http_response_code($ok ? 200 : 400);
echo json_encode([
    'status'  => $ok ? 'success' : 'error',
    'message' => $result['message'] ?? '',
    'data'    => $result,
], JSON_UNESCAPED_UNICODE);
