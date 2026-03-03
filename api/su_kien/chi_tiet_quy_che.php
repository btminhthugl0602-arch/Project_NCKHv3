<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_quy_che.php';

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

$idQuyChe = isset($_GET['id_quy_che']) ? (int) $_GET['id_quy_che'] : 0;
$idSkRequest = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idQuyChe <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai tham số id_quy_che',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function fetch_ast_node($conn, int $idDieuKien): ?array
{
    $dieuKien = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', $idDieuKien);
    if (!$dieuKien) {
        return null;
    }

    if (($dieuKien['loaiDieuKien'] ?? '') === 'DON') {
        $don = truy_van_mot_ban_ghi($conn, 'dieukien_don', 'idDieuKien', $idDieuKien);
        if (!$don) {
            return null;
        }

        $thuocTinh = truy_van_mot_ban_ghi($conn, 'thuoctinh_kiemtra', 'idThuocTinhKiemTra', (int) $don['idThuocTinhKiemTra']);
        $toanTu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $don['idToanTu']);

        return [
            'type' => 'rule',
            'idDieuKien' => $idDieuKien,
            'idThuocTinhKiemTra' => (int) $don['idThuocTinhKiemTra'],
            'idToanTu' => (int) $don['idToanTu'],
            'giaTriSoSanh' => (string) ($don['giaTriSoSanh'] ?? ''),
            'label' => [
                'thuocTinh' => $thuocTinh['tenThuocTinh'] ?? '',
                'toanTu' => $toanTu['kyHieu'] ?? '',
            ],
        ];
    }

    if (($dieuKien['loaiDieuKien'] ?? '') === 'TOHOP') {
        $tohop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', $idDieuKien);
        if (!$tohop) {
            return null;
        }

        $toanTu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $tohop['idToanTu']);
        $leftNode = fetch_ast_node($conn, (int) $tohop['idDieuKienTrai']);
        $rightNode = fetch_ast_node($conn, (int) $tohop['idDieuKienPhai']);

        if (!$leftNode || !$rightNode) {
            return null;
        }

        return [
            'type' => 'group',
            'idDieuKien' => $idDieuKien,
            'operator' => strtoupper((string) ($toanTu['kyHieu'] ?? 'AND')),
            'children' => [$leftNode, $rightNode],
        ];
    }

    return null;
}

try {
    $stmt = $conn->prepare(
        'SELECT q.idQuyChe, q.idSK, q.tenQuyChe, q.moTa, q.loaiQuyChe, qd.idDieuKienCuoi
         FROM quyche q
         LEFT JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
         WHERE q.idQuyChe = :idQuyChe
         LIMIT 1'
    );
    $stmt->execute([':idQuyChe' => $idQuyChe]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không tìm thấy quy chế',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idSk = (int) ($row['idSK'] ?? 0);
    if ($idSkRequest > 0 && $idSkRequest !== $idSk) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Quy chế không thuộc sự kiện hiện tại',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idUser = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($idUser > 0 && !xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không có quyền xem chi tiết quy chế',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idDieuKienCuoi = (int) ($row['idDieuKienCuoi'] ?? 0);
    $ast = $idDieuKienCuoi > 0 ? fetch_ast_node($conn, $idDieuKienCuoi) : null;

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy chi tiết quy chế thành công',
        'data' => [
            'idQuyChe' => (int) $row['idQuyChe'],
            'idSK' => $idSk,
            'tenQuyChe' => $row['tenQuyChe'] ?? '',
            'moTa' => $row['moTa'] ?? '',
            'loaiQuyChe' => $row['loaiQuyChe'] ?? '',
            'idDieuKienCuoi' => $idDieuKienCuoi,
            'ast' => $ast,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy chi tiết quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
