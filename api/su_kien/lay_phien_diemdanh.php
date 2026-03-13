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

$actor     = auth_require_login();
$idPhienDD = (int) ($_GET['id_phien_dd'] ?? 0);
$idSk      = (int) ($_GET['id_sk']       ?? 0);

if ($idPhienDD <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_phien_dd'], JSON_UNESCAPED_UNICODE);
    exit;
}

$phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $idPhienDD);
if (!$phien) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Phiên không tồn tại'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lazy check + stats
$phien = kiem_tra_tu_dong_phien($conn, $phien);

if ($idSk > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN tvs.id IS NOT NULL THEN 1 ELSE 0 END) AS chinh_thuc,
                   SUM(CASE WHEN tvs.id IS NULL THEN 1 ELSE 0 END) AS khan_gia
            FROM diemdanh dd
            LEFT JOIN taikhoan_vaitro_sukien tvs
                   ON tvs.idTK = dd.idTK AND tvs.idSK = ? AND tvs.isActive = 1
            WHERE dd.idPhienDD = ?
        ");
        $stmt->execute([$idSk, $idPhienDD]);
        $stats = $stmt->fetch();
        $phien['stats'] = [
            'total'      => (int) ($stats['total']      ?? 0),
            'chinh_thuc' => (int) ($stats['chinh_thuc'] ?? 0),
            'khan_gia'   => (int) ($stats['khan_gia']   ?? 0),
        ];
    } catch (Throwable $e) {
        $phien['stats'] = ['total' => 0, 'chinh_thuc' => 0, 'khan_gia' => 0];
    }
}

// Chỉ trả tokenQR khi BTC hỏi (có cauhinh_sukien) và phiên DANG_MO
if ($phien['trangThai'] === 'DANG_MO' && $idSk > 0 && co_quyen_to_chuc_su_kien($conn, $actor['idTK'], $idSk)) {
    $phien['tokenQR'] = tao_token_qr($idPhienDD, $phien['thoiGianMo']);
}

echo json_encode([
    'status' => 'success',
    'data'   => $phien,
], JSON_UNESCAPED_UNICODE);
