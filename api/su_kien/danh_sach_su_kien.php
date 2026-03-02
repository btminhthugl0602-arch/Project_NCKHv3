<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';

header('Content-Type: application/json; charset=utf-8');

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
        'SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
                ct.tenCap, lc.tenLoaiCap
         FROM sukien sk
         LEFT JOIN cap_tochuc ct ON ct.idCap = sk.idCap
         LEFT JOIN loaicap lc ON lc.idLoaiCap = ct.idLoaiCap
         ORDER BY sk.idSK DESC
         LIMIT 50'
    );
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy danh sách sự kiện thành công',
        'data' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy danh sách sự kiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
