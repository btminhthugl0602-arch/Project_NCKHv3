<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_quy_che.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

function send_rule_error(int $statusCode, string $message, $data = null): void
{
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function to_int_list(array $values): array
{
    $result = [];
    foreach ($values as $value) {
        $value = (int) $value;
        if ($value > 0) {
            $result[$value] = true;
        }
    }
    return array_keys($result);
}

function fetch_rows_by_id_list(PDO $conn, string $sqlPrefix, string $idColumn, array $ids): array
{
    $ids = to_int_list($ids);
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare($sqlPrefix . " WHERE {$idColumn} IN ({$placeholders})");
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function preload_rule_tree_maps(PDO $conn, int $rootId): array
{
    $dieuKienMap = [];
    $toHopMap = [];
    $queue = [$rootId];

    while (!empty($queue)) {
        $currentIds = to_int_list($queue);
        $queue = [];

        $pending = [];
        foreach ($currentIds as $id) {
            if (!isset($dieuKienMap[$id])) {
                $pending[] = $id;
            }
        }

        if (empty($pending)) {
            continue;
        }

        $rows = fetch_rows_by_id_list(
            $conn,
            'SELECT idDieuKien, loaiDieuKien FROM dieukien',
            'idDieuKien',
            $pending
        );

        foreach ($rows as $row) {
            $idDieuKien = (int) ($row['idDieuKien'] ?? 0);
            if ($idDieuKien > 0) {
                $dieuKienMap[$idDieuKien] = $row;
            }
        }

        $toHopIds = [];
        foreach ($pending as $idDieuKien) {
            $node = $dieuKienMap[$idDieuKien] ?? null;
            if ($node && strtoupper((string) ($node['loaiDieuKien'] ?? '')) === 'TOHOP') {
                $toHopIds[] = $idDieuKien;
            }
        }

        if (!empty($toHopIds)) {
            $toHopRows = fetch_rows_by_id_list(
                $conn,
                'SELECT idDieuKien, idDieuKienTrai, idDieuKienPhai, idToanTu FROM tohop_dieukien',
                'idDieuKien',
                $toHopIds
            );

            foreach ($toHopRows as $toHopRow) {
                $idDieuKien = (int) ($toHopRow['idDieuKien'] ?? 0);
                if ($idDieuKien <= 0) {
                    continue;
                }

                $toHopMap[$idDieuKien] = $toHopRow;
                $queue[] = (int) ($toHopRow['idDieuKienTrai'] ?? 0);
                $queue[] = (int) ($toHopRow['idDieuKienPhai'] ?? 0);
            }
        }
    }

    $donIds = [];
    foreach ($dieuKienMap as $idDieuKien => $node) {
        if (strtoupper((string) ($node['loaiDieuKien'] ?? '')) === 'DON') {
            $donIds[] = (int) $idDieuKien;
        }
    }

    $donMap = [];
    $thuocTinhIds = [];
    $toanTuIds = [];

    if (!empty($donIds)) {
        $donRows = fetch_rows_by_id_list(
            $conn,
            'SELECT idDieuKien, idThuocTinhKiemTra, idToanTu, giaTriSoSanh FROM dieukien_don',
            'idDieuKien',
            $donIds
        );

        foreach ($donRows as $donRow) {
            $idDieuKien = (int) ($donRow['idDieuKien'] ?? 0);
            if ($idDieuKien <= 0) {
                continue;
            }

            $donMap[$idDieuKien] = $donRow;
            $thuocTinhIds[] = (int) ($donRow['idThuocTinhKiemTra'] ?? 0);
            $toanTuIds[] = (int) ($donRow['idToanTu'] ?? 0);
        }
    }

    foreach ($toHopMap as $toHopRow) {
        $toanTuIds[] = (int) ($toHopRow['idToanTu'] ?? 0);
    }

    $thuocTinhMap = [];
    $toanTuMap = [];

    $thuocTinhRows = fetch_rows_by_id_list(
        $conn,
        'SELECT idThuocTinhKiemTra, tenThuocTinh FROM thuoctinh_kiemtra',
        'idThuocTinhKiemTra',
        $thuocTinhIds
    );
    foreach ($thuocTinhRows as $row) {
        $id = (int) ($row['idThuocTinhKiemTra'] ?? 0);
        if ($id > 0) {
            $thuocTinhMap[$id] = $row;
        }
    }

    $toanTuRows = fetch_rows_by_id_list(
        $conn,
        'SELECT idToanTu, kyHieu FROM toantu',
        'idToanTu',
        $toanTuIds
    );
    foreach ($toanTuRows as $row) {
        $id = (int) ($row['idToanTu'] ?? 0);
        if ($id > 0) {
            $toanTuMap[$id] = $row;
        }
    }

    return [
        'dieuKienMap' => $dieuKienMap,
        'donMap' => $donMap,
        'toHopMap' => $toHopMap,
        'thuocTinhMap' => $thuocTinhMap,
        'toanTuMap' => $toanTuMap,
    ];
}

function build_ast_from_preload(int $idDieuKien, array $preload): ?array
{
    $dieuKienMap = $preload['dieuKienMap'] ?? [];
    $donMap = $preload['donMap'] ?? [];
    $toHopMap = $preload['toHopMap'] ?? [];
    $thuocTinhMap = $preload['thuocTinhMap'] ?? [];
    $toanTuMap = $preload['toanTuMap'] ?? [];

    if (!isset($dieuKienMap[$idDieuKien])) {
        return null;
    }

    $node = $dieuKienMap[$idDieuKien];
    $loaiDieuKien = strtoupper((string) ($node['loaiDieuKien'] ?? ''));

    if ($loaiDieuKien === 'DON') {
        if (!isset($donMap[$idDieuKien])) {
            return null;
        }

        $don = $donMap[$idDieuKien];
        $idThuocTinh = (int) ($don['idThuocTinhKiemTra'] ?? 0);
        $idToanTu = (int) ($don['idToanTu'] ?? 0);

        return [
            'type' => 'rule',
            'idDieuKien' => $idDieuKien,
            'idThuocTinhKiemTra' => $idThuocTinh,
            'idToanTu' => $idToanTu,
            'giaTriSoSanh' => (string) ($don['giaTriSoSanh'] ?? ''),
            'label' => [
                'thuocTinh' => (string) (($thuocTinhMap[$idThuocTinh]['tenThuocTinh'] ?? '') ?: ''),
                'toanTu' => (string) (($toanTuMap[$idToanTu]['kyHieu'] ?? '') ?: ''),
            ],
        ];
    }

    if ($loaiDieuKien === 'TOHOP') {
        if (!isset($toHopMap[$idDieuKien])) {
            return null;
        }

        $toHop = $toHopMap[$idDieuKien];
        $leftId = (int) ($toHop['idDieuKienTrai'] ?? 0);
        $rightId = (int) ($toHop['idDieuKienPhai'] ?? 0);
        $idToanTu = (int) ($toHop['idToanTu'] ?? 0);

        $leftNode = $leftId > 0 ? build_ast_from_preload($leftId, $preload) : null;
        $rightNode = $rightId > 0 ? build_ast_from_preload($rightId, $preload) : null;
        if (!$leftNode || !$rightNode) {
            return null;
        }

        return [
            'type' => 'group',
            'idDieuKien' => $idDieuKien,
            'operator' => strtoupper((string) (($toanTuMap[$idToanTu]['kyHieu'] ?? '') ?: 'AND')),
            'children' => [$leftNode, $rightNode],
        ];
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_rule_error(422, 'Phương thức không hợp lệ');
}

$idQuyChe = isset($_GET['id_quy_che']) ? (int) $_GET['id_quy_che'] : 0;
$idSkRequest = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idQuyChe <= 0) {
    send_rule_error(422, 'Thiếu hoặc sai tham số id_quy_che');
}

try {
    $startTime = microtime(true);
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
        send_rule_error(422, 'Không tìm thấy quy chế');
    }

    $idSk = (int) ($row['idSK'] ?? 0);
    if ($idSkRequest > 0 && $idSkRequest !== $idSk) {
        send_rule_error(403, 'Quy chế không thuộc sự kiện hiện tại');
    }

    $idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
    if ($idUser > 0 && !xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
        send_rule_error(403, 'Không có quyền xem chi tiết quy chế');
    }

    $idDieuKienCuoi = (int) ($row['idDieuKienCuoi'] ?? 0);
    $preload = $idDieuKienCuoi > 0 ? preload_rule_tree_maps($conn, $idDieuKienCuoi) : [];
    $ast = $idDieuKienCuoi > 0 ? build_ast_from_preload($idDieuKienCuoi, $preload) : null;
    $nguCanhApDung = lay_ngucanh_ap_dung_theo_quy_che($conn, (int) $row['idQuyChe']);

    $durationMs = (int) round((microtime(true) - $startTime) * 1000);
    error_log(json_encode([
        'module' => 'quy_che',
        'event' => 'chi_tiet_quy_che',
        'idQuyChe' => (int) $row['idQuyChe'],
        'idSK' => $idSk,
        'idDieuKienCuoi' => $idDieuKienCuoi,
        'tongNode' => count($preload['dieuKienMap'] ?? []),
        'durationMs' => $durationMs,
        'status' => 'success',
    ], JSON_UNESCAPED_UNICODE));

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy chi tiết quy chế thành công',
        'data' => [
            'idQuyChe' => (int) $row['idQuyChe'],
            'idSK' => $idSk,
            'tenQuyChe' => $row['tenQuyChe'] ?? '',
            'moTa' => $row['moTa'] ?? '',
            'loaiQuyChe' => $row['loaiQuyChe'] ?? '',
            'nguCanhApDung' => $nguCanhApDung,
            'idDieuKienCuoi' => $idDieuKienCuoi,
            'ast' => $ast,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log(json_encode([
        'module' => 'quy_che',
        'event' => 'chi_tiet_quy_che',
        'status' => 'error',
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE));
    send_rule_error(500, 'Lỗi hệ thống khi lấy chi tiết quy chế');
}
