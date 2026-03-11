<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare(
        'SELECT ct.idCap, ct.tenCap, lc.tenLoaiCap
         FROM cap_tochuc ct
         INNER JOIN loaicap lc ON lc.idLoaiCap = ct.idLoaiCap
         ORDER BY lc.idLoaiCap ASC, ct.tenCap ASC'
    );
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy danh sách cấp tổ chức thành công',
        'data' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy danh sách cấp tổ chức',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
