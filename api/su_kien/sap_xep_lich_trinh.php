<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_to_chuc.php';

header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

$idSk  = (int) ($input['id_sk'] ?? 0);
$actor = auth_require_quyen_su_kien($idSk, 'cauhinh_sukien');

$items = $input['items'] ?? [];
if (!is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu items'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = sap_xep_lich_trinh($conn, $actor['idTK'], $idSk, $items);

$ok = $result['status'] ?? false;
http_response_code($ok ? 200 : 400);
echo json_encode([
    'status'  => $ok ? 'success' : 'error',
    'message' => $result['message'] ?? '',
    'data'    => $result,
], JSON_UNESCAPED_UNICODE);
