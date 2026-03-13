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

$loaiQuyChe = strtoupper(trim((string) ($_GET['loai_quy_che'] ?? '')));
$maNguCanh = chuan_hoa_ma_ngu_canh($_GET['ma_ngu_canh'] ?? '');

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
    $hasContextTable = bang_ton_tai($conn, 'quyche_ngucanh_apdung');

    if ($hasContextTable) {
        $where = ['q.idSK = :idSK'];
        $params = [':idSK' => $idSk];

        if ($loaiQuyChe !== '') {
            $where[] = 'UPPER(q.loaiQuyChe) = :loaiQuyChe';
            $params[':loaiQuyChe'] = $loaiQuyChe;
        }

        if ($maNguCanh !== '') {
            $where[] = 'EXISTS (
                SELECT 1 FROM quyche_ngucanh_apdung nx
                WHERE nx.idQuyChe = q.idQuyChe AND nx.maNguCanh = :maNguCanh
            )';
            $params[':maNguCanh'] = $maNguCanh;
        }

        $stmt = $conn->prepare(
            'SELECT
                q.idQuyChe,
                q.idSK,
                q.tenQuyChe,
                q.moTa,
                q.loaiQuyChe,
                qd.idDieuKienCuoi,
                GROUP_CONCAT(DISTINCT n.maNguCanh ORDER BY n.maNguCanh SEPARATOR ",") AS nguCanhApDungRaw
             FROM quyche q
             LEFT JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
             LEFT JOIN quyche_ngucanh_apdung n ON n.idQuyChe = q.idQuyChe
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY q.idQuyChe, q.idSK, q.tenQuyChe, q.moTa, q.loaiQuyChe, qd.idDieuKienCuoi
             ORDER BY q.idQuyChe DESC'
        );
        $stmt->execute($params);
    } else {
        $where = ['q.idSK = :idSK'];
        $params = [':idSK' => $idSk];
        if ($loaiQuyChe !== '') {
            $where[] = 'UPPER(q.loaiQuyChe) = :loaiQuyChe';
            $params[':loaiQuyChe'] = $loaiQuyChe;
        }

        $stmt = $conn->prepare(
            'SELECT q.idQuyChe, q.idSK, q.tenQuyChe, q.moTa, q.loaiQuyChe, qd.idDieuKienCuoi
             FROM quyche q
             LEFT JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY q.idQuyChe DESC'
        );
        $stmt->execute($params);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rows = array_map(function ($row) {
        $raw = trim((string) ($row['nguCanhApDungRaw'] ?? ''));
        $row['nguCanhApDung'] = $raw !== '' ? array_values(array_filter(array_map('trim', explode(',', $raw)))) : [];
        unset($row['nguCanhApDungRaw']);
        return $row;
    }, is_array($rows) ? $rows : []);

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
