<?php
/**
 * API: Lấy danh sách chủ đề của một sự kiện
 * GET /api/su_kien/get_chude_sk.php?id_sk=X
 */
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';

header('Content-Type: application/json; charset=utf-8');

$idSk = (int) ($_GET['id_sk'] ?? 0);
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare(
        'SELECT cs.idChuDeSK, cd.tenChuDe, cs.moTa
         FROM chude_sukien cs
         JOIN chude cd ON cs.idchude = cd.idChuDe
         WHERE cs.idSK = :idSk AND cs.isActive = 1
         ORDER BY cs.idChuDeSK ASC'
    );
    $stmt->execute([':idSk' => $idSk]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $list], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}