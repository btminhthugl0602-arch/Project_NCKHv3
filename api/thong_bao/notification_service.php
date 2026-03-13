<?php

if (!defined('_AUTHEN')) {
    die('Truy cap khong hop le');
}

$notificationConfigFile = __DIR__ . '/../../configs/config.php';
if (file_exists($notificationConfigFile)) {
    require_once $notificationConfigFile;
}

function notification_feature_enabled(string $cluster): bool
{
    $cluster = strtolower(trim($cluster));

    if ($cluster === 'event') {
        return defined('NOTIFICATION_FLAG_EVENT_CLUSTER') ? (bool) NOTIFICATION_FLAG_EVENT_CLUSTER : false;
    }

    if ($cluster === 'group') {
        return defined('NOTIFICATION_FLAG_GROUP_CLUSTER') ? (bool) NOTIFICATION_FLAG_GROUP_CLUSTER : false;
    }

    if ($cluster === 'scoring') {
        return defined('NOTIFICATION_FLAG_SCORING_CLUSTER') ? (bool) NOTIFICATION_FLAG_SCORING_CLUSTER : false;
    }

    return false;
}

/**
 * Phase 0 - JSON contract helper cho module thong bao.
 */
function notification_api_response(string $status, string $message, $data = null, int $httpCode = 200, array $meta = []): void
{
    http_response_code($httpCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $baseMeta = [
        'contract' => 'notification.v1',
        'timestamp' => date('c'),
    ];

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'meta' => array_merge($baseMeta, $meta),
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Phase 0 - Chuan hoa payload event thong bao.
 */
function notification_normalize_event_payload(array $payload): array
{
    $loaiThongBao = strtoupper(trim((string) ($payload['loaiThongBao'] ?? 'HE_THONG')));
    $phamVi = strtoupper(trim((string) ($payload['phamVi'] ?? 'CA_NHAN')));

    $normalized = [
        'tieuDe' => trim((string) ($payload['tieuDe'] ?? 'Thong bao he thong')),
        'noiDung' => trim((string) ($payload['noiDung'] ?? '')),
        'loaiThongBao' => $loaiThongBao,
        'phamVi' => $phamVi,
        'idSK' => isset($payload['idSK']) ? (int) $payload['idSK'] : null,
        'idDoiTuong' => isset($payload['idDoiTuong']) ? (int) $payload['idDoiTuong'] : null,
        'loaiDoiTuong' => isset($payload['loaiDoiTuong']) ? strtoupper(trim((string) $payload['loaiDoiTuong'])) : null,
        'nguoiGui' => isset($payload['nguoiGui']) ? (int) $payload['nguoiGui'] : 0,
        'recipients' => is_array($payload['recipients'] ?? null) ? $payload['recipients'] : [],
        'recipientGroups' => is_array($payload['recipientGroups'] ?? null) ? $payload['recipientGroups'] : [],
    ];

    return $normalized;
}

function notification_validate_event_payload(array $payload): array
{
    $errors = [];

    $allowLoaiThongBao = ['SU_KIEN', 'HE_THONG', 'NHOM', 'CA_NHAN'];
    $allowPhamVi = ['CA_NHAN', 'NHOM_NGUOI', 'TAT_CA'];
    $allowLoaiDoiTuong = ['NHOM', 'YEUCAU', 'SANPHAM'];

    if ($payload['tieuDe'] === '') {
        $errors[] = 'tieuDe khong duoc de trong';
    }
    if (!in_array($payload['loaiThongBao'], $allowLoaiThongBao, true)) {
        $errors[] = 'loaiThongBao khong hop le';
    }
    if (!in_array($payload['phamVi'], $allowPhamVi, true)) {
        $errors[] = 'phamVi khong hop le';
    }
    if ($payload['nguoiGui'] <= 0) {
        $errors[] = 'nguoiGui khong hop le';
    }
    if ($payload['loaiDoiTuong'] !== null && !in_array($payload['loaiDoiTuong'], $allowLoaiDoiTuong, true)) {
        $errors[] = 'loaiDoiTuong khong hop le';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Phase 0 - Dong goi quy tac recipient tai mot noi duy nhat.
 */
function notification_resolve_recipients(PDO $conn, array $payload): array
{
    $resolved = [];

    foreach ($payload['recipients'] as $idTK) {
        $idTK = (int) $idTK;
        if ($idTK > 0) {
            $resolved[] = $idTK;
        }
    }

    foreach ($payload['recipientGroups'] as $group) {
        $loaiNhom = strtoupper(trim((string) ($group['loaiNhom'] ?? '')));
        $idNhom = isset($group['idNhom']) ? (int) $group['idNhom'] : null;
        $idVaiTro = isset($group['idVaiTro']) ? (int) $group['idVaiTro'] : null;

        if ($loaiNhom === 'GV') {
            $stmt = $conn->query("SELECT idTK FROM taikhoan WHERE idLoaiTK = 2 AND isActive = 1");
            $resolved = array_merge($resolved, array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
            continue;
        }

        if ($loaiNhom === 'SV') {
            $stmt = $conn->query("SELECT idTK FROM taikhoan WHERE idLoaiTK = 3 AND isActive = 1");
            $resolved = array_merge($resolved, array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
            continue;
        }

        if ($loaiNhom === 'SU_KIEN' && $idNhom !== null && $idNhom > 0) {
            if ($idVaiTro !== null && $idVaiTro > 0) {
                $stmt = $conn->prepare(
                    "SELECT DISTINCT idTK
                     FROM taikhoan_vaitro_sukien
                     WHERE idSK = :idSK AND idVaiTro = :idVaiTro AND isActive = 1"
                );
                $stmt->execute([':idSK' => $idNhom, ':idVaiTro' => $idVaiTro]);
            } else {
                $stmt = $conn->prepare(
                    "SELECT DISTINCT idTK
                     FROM taikhoan_vaitro_sukien
                     WHERE idSK = :idSK AND isActive = 1"
                );
                $stmt->execute([':idSK' => $idNhom]);
            }
            $resolved = array_merge($resolved, array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        }
    }

    $resolved = array_values(array_unique(array_filter($resolved, function ($id) {
        return (int) $id > 0;
    })));

    return $resolved;
}

/**
 * Phase 1 - Tao notification trung tam.
 */
function create_notification(PDO $conn, array $payload): array
{
    $eventPayload = notification_normalize_event_payload($payload);
    $validation = notification_validate_event_payload($eventPayload);

    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => 'Payload thong bao khong hop le',
            'errors' => $validation['errors'],
        ];
    }

    try {
        $ownTransaction = !$conn->inTransaction();
        if ($ownTransaction) {
            $conn->beginTransaction();
        }

        $stmt = $conn->prepare(
            "INSERT INTO thongbao
                (tieuDe, noiDung, loaiThongBao, phamVi, idSK, idDoiTuong, loaiDoiTuong, nguoiGui)
             VALUES
                (:tieuDe, :noiDung, :loaiThongBao, :phamVi, :idSK, :idDoiTuong, :loaiDoiTuong, :nguoiGui)"
        );
        $stmt->execute([
            ':tieuDe' => $eventPayload['tieuDe'],
            ':noiDung' => $eventPayload['noiDung'] !== '' ? $eventPayload['noiDung'] : null,
            ':loaiThongBao' => $eventPayload['loaiThongBao'],
            ':phamVi' => $eventPayload['phamVi'],
            ':idSK' => ($eventPayload['idSK'] !== null && $eventPayload['idSK'] > 0) ? $eventPayload['idSK'] : null,
            ':idDoiTuong' => ($eventPayload['idDoiTuong'] !== null && $eventPayload['idDoiTuong'] > 0) ? $eventPayload['idDoiTuong'] : null,
            ':loaiDoiTuong' => $eventPayload['loaiDoiTuong'],
            ':nguoiGui' => $eventPayload['nguoiGui'],
        ]);

        $idThongBao = (int) $conn->lastInsertId();
        $resolvedRecipients = [];

        if ($eventPayload['phamVi'] === 'CA_NHAN' || $eventPayload['phamVi'] === 'NHOM_NGUOI') {
            $resolvedRecipients = notification_resolve_recipients($conn, $eventPayload);

            if (!empty($eventPayload['recipientGroups'])) {
                $stmtGroup = $conn->prepare(
                    "INSERT INTO thongbao_nhom_nhan (idThongBao, loaiNhom, idNhom, idVaiTro)
                     VALUES (:idThongBao, :loaiNhom, :idNhom, :idVaiTro)"
                );
                foreach ($eventPayload['recipientGroups'] as $group) {
                    $loaiNhom = strtoupper(trim((string) ($group['loaiNhom'] ?? '')));
                    if ($loaiNhom === '') {
                        continue;
                    }
                    $stmtGroup->execute([
                        ':idThongBao' => $idThongBao,
                        ':loaiNhom' => $loaiNhom,
                        ':idNhom' => isset($group['idNhom']) ? (int) $group['idNhom'] : null,
                        ':idVaiTro' => isset($group['idVaiTro']) ? (int) $group['idVaiTro'] : null,
                    ]);
                }
            }

            if (!empty($resolvedRecipients)) {
                $stmtRecipient = $conn->prepare(
                    "INSERT IGNORE INTO thongbao_ca_nhan (idThongBao, idTK) VALUES (:idThongBao, :idTK)"
                );
                foreach ($resolvedRecipients as $idTK) {
                    $stmtRecipient->execute([':idThongBao' => $idThongBao, ':idTK' => $idTK]);
                }
            }
        }

        if ($ownTransaction) {
            $conn->commit();
        }

        return [
            'success' => true,
            'idThongBao' => $idThongBao,
            'resolvedRecipients' => $resolvedRecipients,
            'payload' => $eventPayload,
        ];
    } catch (Throwable $e) {
        if ($ownTransaction && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('create_notification error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Khong the tao thong bao',
        ];
    }
}

function dispatch_personal(PDO $conn, array $payload): array
{
    $payload['phamVi'] = 'CA_NHAN';
    return create_notification($conn, $payload);
}

function dispatch_group(PDO $conn, array $payload): array
{
    $payload['phamVi'] = 'NHOM_NGUOI';
    return create_notification($conn, $payload);
}

function dispatch_broadcast(PDO $conn, array $payload): array
{
    $payload['phamVi'] = 'TAT_CA';
    return create_notification($conn, $payload);
}

function mark_read(PDO $conn, int $idThongBao, int $idTK): bool
{
    if ($idThongBao <= 0 || $idTK <= 0) {
        return false;
    }
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO thongbao_da_doc (idThongBao, idTK) VALUES (:idThongBao, :idTK)"
    );
    return $stmt->execute([':idThongBao' => $idThongBao, ':idTK' => $idTK]);
}

function list_inbox(PDO $conn, int $idTK, array $options = []): array
{
    $idSK = isset($options['idSK']) ? (int) $options['idSK'] : 0;
    $chiLayChuaDoc = isset($options['chiLayChuaDoc']) ? (bool) $options['chiLayChuaDoc'] : true;
    $gioiHan = isset($options['limit']) ? max(1, (int) $options['limit']) : 50;
    $includeBroadcast = isset($options['includeBroadcast']) ? (bool) $options['includeBroadcast'] : false;

    $where = [];
    $params = [':idTK' => $idTK, ':idTK2' => $idTK];

    if ($includeBroadcast) {
        $where[] = "(tb.phamVi = 'TAT_CA' OR tcn.idTK IS NOT NULL)";
    } else {
        $where[] = 'tcn.idTK IS NOT NULL';
    }

    if ($chiLayChuaDoc) {
        $where[] = 'tdd.idThongBao IS NULL';
    }

    if ($idSK > 0) {
        $where[] = 'tb.idSK = :idSK';
        $params[':idSK'] = $idSK;
    }

    $sql = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao, tb.phamVi,
                   tb.idSK, tb.idDoiTuong, tb.loaiDoiTuong, tb.ngayGui,
                   CASE WHEN tdd.idThongBao IS NULL THEN 0 ELSE 1 END AS daDoc
            FROM thongbao tb
            LEFT JOIN thongbao_ca_nhan tcn ON tcn.idThongBao = tb.idThongBao AND tcn.idTK = :idTK
            LEFT JOIN thongbao_da_doc tdd ON tdd.idThongBao = tb.idThongBao AND tdd.idTK = :idTK2
            WHERE " . implode(' AND ', $where) . "
            ORDER BY tb.ngayGui DESC
            LIMIT " . $gioiHan;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_unread_notifications(PDO $conn, int $idTK, array $options = []): int
{
    $idSK = isset($options['idSK']) ? (int) $options['idSK'] : 0;
    $includeBroadcast = isset($options['includeBroadcast']) ? (bool) $options['includeBroadcast'] : true;

    $where = [];
    $params = [':idTK' => $idTK, ':idTK2' => $idTK];

    if ($includeBroadcast) {
        $where[] = "(tb.phamVi = 'TAT_CA' OR tcn.idTK IS NOT NULL)";
    } else {
        $where[] = 'tcn.idTK IS NOT NULL';
    }

    $where[] = 'tdd.idThongBao IS NULL';

    if ($idSK > 0) {
        $where[] = 'tb.idSK = :idSK';
        $params[':idSK'] = $idSK;
    }

    $sql = "SELECT COUNT(DISTINCT tb.idThongBao)
            FROM thongbao tb
            LEFT JOIN thongbao_ca_nhan tcn ON tcn.idThongBao = tb.idThongBao AND tcn.idTK = :idTK
            LEFT JOIN thongbao_da_doc tdd ON tdd.idThongBao = tb.idThongBao AND tdd.idTK = :idTK2
            WHERE " . implode(' AND ', $where);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function mark_all_read(PDO $conn, int $idTK, array $options = []): int
{
    $idSK = isset($options['idSK']) ? (int) $options['idSK'] : 0;
    $includeBroadcast = isset($options['includeBroadcast']) ? (bool) $options['includeBroadcast'] : true;

    if ($idTK <= 0) {
        return 0;
    }

    $where = [];
    $params = [':idTK' => $idTK, ':idTK2' => $idTK];

    if ($includeBroadcast) {
        $where[] = "(tb.phamVi = 'TAT_CA' OR tcn.idTK IS NOT NULL)";
    } else {
        $where[] = 'tcn.idTK IS NOT NULL';
    }

    $where[] = 'tdd.idThongBao IS NULL';

    if ($idSK > 0) {
        $where[] = 'tb.idSK = :idSK';
        $params[':idSK'] = $idSK;
    }

    $sql = "INSERT IGNORE INTO thongbao_da_doc (idThongBao, idTK)
            SELECT DISTINCT tb.idThongBao, :idTK2
            FROM thongbao tb
            LEFT JOIN thongbao_ca_nhan tcn ON tcn.idThongBao = tb.idThongBao AND tcn.idTK = :idTK
            LEFT JOIN thongbao_da_doc tdd ON tdd.idThongBao = tb.idThongBao AND tdd.idTK = :idTK2
            WHERE " . implode(' AND ', $where);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->rowCount();
}
