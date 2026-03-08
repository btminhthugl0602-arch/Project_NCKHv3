<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_quy_che.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
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

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai tham số id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$loaiQuyCheRaw = strtoupper(trim((string) ($_GET['loai_quy_che'] ?? '')));
$normalizeLoai = [
    'THAMGIA' => 'THAMGIA_SV',
    'THAMGIA_SV' => 'THAMGIA_SV',
    'THAMGIA_GV' => 'THAMGIA_GV',
    'VONGTHI' => 'VONGTHI',
    'SANPHAM' => 'SANPHAM',
    'GIAITHUONG' => 'GIAITHUONG',
];
$loaiQuyChe = $loaiQuyCheRaw !== '' ? ($normalizeLoai[$loaiQuyCheRaw] ?? '') : '';
if ($loaiQuyCheRaw !== '' && $loaiQuyChe === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'loai_quy_che không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idUser > 0 && !xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Không có quyền xem danh sách quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($loaiQuyChe !== '') {
        $stmt = $conn->prepare(
            'SELECT q.idQuyChe, q.idSK, q.tenQuyChe, q.moTa, q.loaiQuyChe, qd.idDieuKienCuoi
             FROM quyche q
             LEFT JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
             WHERE q.idSK = :idSK AND q.loaiQuyChe = :loaiQuyChe
             ORDER BY q.idQuyChe DESC'
        );
        $stmt->execute([
            ':idSK' => $idSk,
            ':loaiQuyChe' => $loaiQuyChe,
        ]);
    } else {
        $stmt = $conn->prepare(
            'SELECT q.idQuyChe, q.idSK, q.tenQuyChe, q.moTa, q.loaiQuyChe, qd.idDieuKienCuoi
             FROM quyche q
             LEFT JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
             WHERE q.idSK = :idSK
             ORDER BY q.idQuyChe DESC'
        );
        $stmt->execute([':idSK' => $idSk]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy danh sách quy chế thành công',
        'data' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy danh sách quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
