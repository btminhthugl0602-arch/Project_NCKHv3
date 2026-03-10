<?php

require_once __DIR__ . '/../core/base.php';

function xac_thuc_quyen_bo_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien = 0): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return true;
    }

    if ($id_su_kien > 0) {
        return kiem_tra_bat_ky_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien, [
            'cauhinh_sukien',
            'cauhinh_vongthi',
        ]);
    }

    return false;
}

function _criteria_table_has_column($conn, string $table, string $column): bool
{
    try {
        $table = trim($table);
        $column = trim($column);

        if ($table === '' || $column === '') {
            return false;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            return false;
        }

        $stmt = $conn->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
        $stmt->execute([':column' => $column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        return false;
    }
}

function tao_tieu_chi($conn, $id_nguoi_tao, $noi_dung, $diem_toi_da = 10.00)
{
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $noi_dung = trim((string) $noi_dung);

    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_tao)) {
        return ['status' => false, 'message' => 'Không có quyền tạo tiêu chí'];
    }

    if ($noi_dung === '') {
        return ['status' => false, 'message' => 'Nội dung tiêu chí không được trống'];
    }

    $stmtCheck = $conn->prepare('SELECT idTieuChi FROM tieuchi WHERE noiDungTieuChi = :noiDung LIMIT 1');
    $stmtCheck->execute([':noiDung' => $noi_dung]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        return ['status' => false, 'message' => 'Tiêu chí đã tồn tại'];
    }

    $result = _insert_info($conn, 'tieuchi', ['noiDungTieuChi'], [$noi_dung]);
    if (!$result) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo tiêu chí'];
    }

    return [
        'status' => true,
        'message' => 'Đã tạo tiêu chí',
        'idTieuChi' => (int) $conn->lastInsertId(),
    ];
}

function tim_hoac_tao_tieu_chi_theo_noi_dung($conn, string $noi_dung): array
{
    $noi_dung = trim($noi_dung);
    if ($noi_dung === '') {
        return ['status' => false, 'message' => 'Nội dung tiêu chí trống'];
    }

    $stmt = $conn->prepare('SELECT idTieuChi FROM tieuchi WHERE noiDungTieuChi = :noiDung LIMIT 1');
    $stmt->execute([':noiDung' => $noi_dung]);
    $idTieuChi = (int) ($stmt->fetchColumn() ?: 0);

    if ($idTieuChi > 0) {
        return [
            'status' => true,
            'message' => 'Đã tái sử dụng tiêu chí có sẵn',
            'idTieuChi' => $idTieuChi,
            'isNew' => false,
        ];
    }

    $ok = _insert_info($conn, 'tieuchi', ['noiDungTieuChi'], [$noi_dung]);
    if (!$ok) {
        return ['status' => false, 'message' => 'Không thể tạo tiêu chí mới'];
    }

    return [
        'status' => true,
        'message' => 'Đã tạo tiêu chí mới',
        'idTieuChi' => (int) $conn->lastInsertId(),
        'isNew' => true,
    ];
}

function tao_bo_tieu_chi($conn, $id_nguoi_tao, $id_su_kien, $ten_bo, $mo_ta = '')
{
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_su_kien = (int) $id_su_kien;
    $ten_bo = trim((string) $ten_bo);
    $mo_ta = trim((string) $mo_ta);

    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_tao, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền tạo bộ tiêu chí'];
    }

    if ($id_su_kien <= 0 || !_is_exist($conn, 'sukien', 'idSK', $id_su_kien)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if ($ten_bo === '') {
        return ['status' => false, 'message' => 'Tên bộ tiêu chí không được trống'];
    }

    $result = _insert_info($conn, 'botieuchi', ['tenBoTieuChi', 'moTa'], [$ten_bo, $mo_ta]);
    if (!$result) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo bộ tiêu chí'];
    }

    return [
        'status' => true,
        'message' => 'Đã tạo bộ tiêu chí',
        'idBoTieuChi' => (int) $conn->lastInsertId(),
    ];
}

function them_tieu_chi_vao_bo($conn, $id_nguoi_thuc_hien, $id_bo, $id_tieu_chi, $ty_trong = 1.00, $diem_toi_da = null)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_bo = (int) $id_bo;
    $id_tieu_chi = (int) $id_tieu_chi;
    $ty_trong = (float) $ty_trong;
    $diem_toi_da = $diem_toi_da !== null && $diem_toi_da !== '' ? (float) $diem_toi_da : null;

    if ($id_bo <= 0 || $id_tieu_chi <= 0) {
        return ['status' => false, 'message' => 'ID bộ tiêu chí hoặc tiêu chí không hợp lệ'];
    }

    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien)) {
        return ['status' => false, 'message' => 'Không có quyền thao tác bộ tiêu chí'];
    }

    if (!_is_exist($conn, 'botieuchi', 'idBoTieuChi', $id_bo)) {
        return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
    }

    if (!_is_exist($conn, 'tieuchi', 'idTieuChi', $id_tieu_chi)) {
        return ['status' => false, 'message' => 'Tiêu chí không tồn tại'];
    }

    if ($ty_trong <= 0) {
        return ['status' => false, 'message' => 'Tỷ trọng phải lớn hơn 0'];
    }

    if ($diem_toi_da !== null && $diem_toi_da <= 0) {
        return ['status' => false, 'message' => 'Điểm tối đa phải lớn hơn 0'];
    }

    $stmtExist = $conn->prepare('SELECT 1 FROM botieuchi_tieuchi WHERE idBoTieuChi = :idBo AND idTieuChi = :idTieuChi LIMIT 1');
    $stmtExist->execute([
        ':idBo' => $id_bo,
        ':idTieuChi' => $id_tieu_chi,
    ]);
    $exists = (bool) $stmtExist->fetchColumn();

    if ($exists) {
        $fields = ['tyTrong'];
        $values = [$ty_trong];
        if ($diem_toi_da !== null) {
            $fields[] = 'diemToiDa';
            $values[] = $diem_toi_da;
        }

        $ok = _update_info(
            $conn,
            'botieuchi_tieuchi',
            $fields,
            $values,
            [
                'idBoTieuChi' => ['=', $id_bo, 'AND'],
                'idTieuChi' => ['=', $id_tieu_chi, ''],
            ]
        );

        return $ok
            ? ['status' => true, 'message' => 'Đã cập nhật tiêu chí trong bộ']
            : ['status' => false, 'message' => 'Lỗi cập nhật tiêu chí trong bộ'];
    }

    $insertFields = ['idBoTieuChi', 'idTieuChi', 'tyTrong'];
    $insertValues = [$id_bo, $id_tieu_chi, $ty_trong];
    if ($diem_toi_da !== null) {
        $insertFields[] = 'diemToiDa';
        $insertValues[] = $diem_toi_da;
    }

    $ok = _insert_info($conn, 'botieuchi_tieuchi', $insertFields, $insertValues);

    return $ok
        ? ['status' => true, 'message' => 'Đã thêm tiêu chí vào bộ']
        : ['status' => false, 'message' => 'Lỗi hệ thống khi thêm tiêu chí vào bộ'];
}

function gan_bo_tieu_chi_vao_vong($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_vong_thi, $id_bo)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_su_kien = (int) $id_su_kien;
    $id_vong_thi = (int) $id_vong_thi;
    $id_bo = (int) $id_bo;

    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền gán bộ tiêu chí cho vòng'];
    }

    if ($id_su_kien <= 0 || $id_vong_thi <= 0 || $id_bo <= 0) {
        return ['status' => false, 'message' => 'Thiếu dữ liệu đầu vào'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_su_kien)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    $stmtRound = $conn->prepare('SELECT idVongThi FROM vongthi WHERE idVongThi = :idVongThi AND idSK = :idSK LIMIT 1');
    $stmtRound->execute([
        ':idVongThi' => $id_vong_thi,
        ':idSK' => $id_su_kien,
    ]);
    if (!(int) $stmtRound->fetchColumn()) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại trong sự kiện'];
    }

    if (!_is_exist($conn, 'botieuchi', 'idBoTieuChi', $id_bo)) {
        return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
    }

    try {
        $stmt = $conn->prepare(
            'INSERT INTO cauhinh_tieuchi_sk (idSK, idVongThi, idBoTieuChi)
             VALUES (:idSK, :idVongThi, :idBo)
             ON DUPLICATE KEY UPDATE idBoTieuChi = VALUES(idBoTieuChi)'
        );
        $ok = $stmt->execute([
            ':idSK' => $id_su_kien,
            ':idVongThi' => $id_vong_thi,
            ':idBo' => $id_bo,
        ]);

        return $ok
            ? ['status' => true, 'message' => 'Đã gán bộ tiêu chí cho vòng thi']
            : ['status' => false, 'message' => 'Lỗi hệ thống khi gán bộ tiêu chí'];
    } catch (Throwable $exception) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi gán bộ tiêu chí'];
    }
}

function lay_chi_tiet_day_du_bo_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien, int $id_bo): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xem chi tiết bộ tiêu chí'];
    }

    if ($id_su_kien <= 0 || $id_bo <= 0) {
        return ['status' => false, 'message' => 'Thiếu id sự kiện hoặc id bộ tiêu chí'];
    }

    $stmtMaster = $conn->prepare(
        'SELECT b.idBoTieuChi, b.tenBoTieuChi, b.moTa
         FROM botieuchi b
         WHERE b.idBoTieuChi = :idBo
         LIMIT 1'
    );
    $stmtMaster->execute([':idBo' => $id_bo]);
    $master = $stmtMaster->fetch(PDO::FETCH_ASSOC);

    if (!$master) {
        return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
    }

    $stmtVong = $conn->prepare(
        'SELECT c.idVongThi, v.tenVongThi
         FROM cauhinh_tieuchi_sk c
         LEFT JOIN vongthi v ON c.idVongThi = v.idVongThi
         WHERE c.idSK = :idSK AND c.idBoTieuChi = :idBo
         ORDER BY v.thuTu ASC, c.idVongThi ASC'
    );
    $stmtVong->execute([
        ':idSK' => $id_su_kien,
        ':idBo' => $id_bo,
    ]);
    $vongApDung = $stmtVong->fetchAll(PDO::FETCH_ASSOC);

    $stmtDetails = $conn->prepare(
        'SELECT bt.idTieuChi, t.noiDungTieuChi, bt.diemToiDa, bt.tyTrong
         FROM botieuchi_tieuchi bt
         JOIN tieuchi t ON bt.idTieuChi = t.idTieuChi
         WHERE bt.idBoTieuChi = :idBo
         ORDER BY bt.idTieuChi ASC'
    );
    $stmtDetails->execute([':idBo' => $id_bo]);
    $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

    return [
        'status' => true,
        'message' => 'Lấy chi tiết bộ tiêu chí thành công',
        'data' => [
            'master' => [
                'idBoTieuChi' => (int) $master['idBoTieuChi'],
                'tenBoTieuChi' => $master['tenBoTieuChi'] ?? '',
                'moTa' => $master['moTa'] ?? '',
                'idVongThi' => isset($vongApDung[0]['idVongThi']) ? (int) $vongApDung[0]['idVongThi'] : 0,
                'danhSachVongThiApDung' => $vongApDung,
            ],
            'details' => $details,
        ],
    ];
}

function lay_danh_sach_vong_thi_theo_su_kien($conn, int $id_nguoi_thuc_hien, int $id_su_kien): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xem vòng thi'];
    }

    $stmt = $conn->prepare(
        'SELECT idVongThi, tenVongThi, thuTu, ngayBatDau, ngayKetThuc
         FROM vongthi
         WHERE idSK = :idSK
         ORDER BY thuTu ASC, idVongThi ASC'
    );
    $stmt->execute([':idSK' => $id_su_kien]);

    return [
        'status' => true,
        'message' => 'Lấy danh sách vòng thi thành công',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function lay_ngan_hang_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien = 0): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xem ngân hàng tiêu chí'];
    }

    $stmt = $conn->prepare(
        'SELECT idTieuChi, noiDungTieuChi
         FROM tieuchi
         ORDER BY noiDungTieuChi ASC'
    );
    $stmt->execute();

    return [
        'status' => true,
        'message' => 'Lấy ngân hàng tiêu chí thành công',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function lay_danh_sach_bo_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien = 0, bool $chi_theo_su_kien = false): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xem danh sách bộ tiêu chí'];
    }

    // Khi $chi_theo_su_kien = true: chỉ lấy bộ tiêu chí đã được gán vào vòng thi của sự kiện này
    if ($chi_theo_su_kien && $id_su_kien > 0) {
        $stmt = $conn->prepare(
            'SELECT DISTINCT b.idBoTieuChi, b.tenBoTieuChi, b.moTa
             FROM botieuchi b
             JOIN cauhinh_tieuchi_sk c ON b.idBoTieuChi = c.idBoTieuChi
             WHERE c.idSK = :idSK
             ORDER BY b.idBoTieuChi DESC'
        );
        $stmt->execute([':idSK' => $id_su_kien]);
    } else {
        $stmt = $conn->prepare(
            'SELECT idBoTieuChi, tenBoTieuChi, moTa
             FROM botieuchi
             ORDER BY idBoTieuChi DESC'
        );
        $stmt->execute();
    }

    return [
        'status' => true,
        'message' => 'Lấy danh sách bộ tiêu chí thành công',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function lay_ban_do_su_dung_bo_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xem bản đồ sử dụng'];
    }

    $usageMap = [];

    $stmtVong = $conn->prepare(
        "SELECT c.idBoTieuChi, c.idVongThi, CONCAT('Dùng chung cho: ', v.tenVongThi) AS noiDung, 'vong' AS loai
         FROM cauhinh_tieuchi_sk c
         JOIN vongthi v ON c.idVongThi = v.idVongThi
         WHERE c.idSK = :idSK"
    );
    $stmtVong->execute([':idSK' => $id_su_kien]);
    $vongRows = $stmtVong->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vongRows as $row) {
        $idBo = (int) ($row['idBoTieuChi'] ?? 0);
        if ($idBo <= 0) {
            continue;
        }
        $usageMap[$idBo][] = [
            'text'       => $row['noiDung'] ?? '',
            'loai'       => $row['loai'] ?? 'vong',
            'idVongThi'  => (int) ($row['idVongThi'] ?? 0),
        ];
    }

    if (_criteria_table_has_column($conn, 'tieuban', 'idBoTieuChi')) {
        $stmtTieuban = $conn->prepare(
            "SELECT idBoTieuChi, CONCAT('Tiểu ban: ', IFNULL(tenTieuBan, '')) AS noiDung, 'tieuban' AS loai
             FROM tieuban
             WHERE idSK = :idSK AND idBoTieuChi IS NOT NULL"
        );
        $stmtTieuban->execute([':idSK' => $id_su_kien]);
        $tieubanRows = $stmtTieuban->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tieubanRows as $row) {
            $idBo = (int) ($row['idBoTieuChi'] ?? 0);
            if ($idBo <= 0) {
                continue;
            }
            $usageMap[$idBo][] = [
                'text' => $row['noiDung'] ?? '',
                'loai' => $row['loai'] ?? 'tieuban',
            ];
        }
    }

    return [
        'status' => true,
        'message' => 'Lấy bản đồ sử dụng bộ tiêu chí thành công',
        'data' => $usageMap,
    ];
}

function luu_bo_tieu_chi_theo_su_kien($conn, int $id_nguoi_thuc_hien, int $id_su_kien, array $payload): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền lưu bộ tiêu chí'];
    }

    if ($id_su_kien <= 0 || !_is_exist($conn, 'sukien', 'idSK', $id_su_kien)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    $editId = (int) ($payload['edit_id'] ?? 0);
    $tenBo = trim((string) ($payload['tenBoTieuChi'] ?? ''));
    $moTaBo = trim((string) ($payload['moTa'] ?? ''));
    $idVongThi = (int) ($payload['idVongThi'] ?? 0);
    $danhSach = $payload['danh_sach_tieu_chi'] ?? [];

    if ($tenBo === '') {
        return ['status' => false, 'message' => 'Tên bộ tiêu chí không được để trống'];
    }

    if (!is_array($danhSach) || count($danhSach) === 0) {
        return ['status' => false, 'message' => 'Danh sách tiêu chí không được để trống'];
    }

    $criteriaRows = [];
    $seen = [];
    foreach ($danhSach as $item) {
        $noiDung = trim((string) ($item['noi_dung'] ?? ''));
        if ($noiDung === '') {
            continue;
        }

        $key = mb_strtolower($noiDung, 'UTF-8');
        if (isset($seen[$key])) {
            return ['status' => false, 'message' => 'Danh sách tiêu chí đang bị trùng nội dung'];
        }
        $seen[$key] = true;

        $tyTrongRaw = $item['ty_trong'] ?? 1;
        $tyTrong = is_numeric($tyTrongRaw) ? (float) $tyTrongRaw : 1.0;
        if ($tyTrong <= 0) {
            return ['status' => false, 'message' => 'Tỷ trọng phải lớn hơn 0'];
        }

        $diemRaw = $item['diem_toi_da'] ?? null;
        $diemToiDa = null;
        if ($diemRaw !== null && $diemRaw !== '') {
            if (!is_numeric($diemRaw)) {
                return ['status' => false, 'message' => 'Điểm tối đa phải là số'];
            }
            $diemToiDa = (float) $diemRaw;
            if ($diemToiDa <= 0) {
                return ['status' => false, 'message' => 'Điểm tối đa phải lớn hơn 0'];
            }
        }

        $criteriaRows[] = [
            'noi_dung' => $noiDung,
            'ty_trong' => $tyTrong,
            'diem_toi_da' => $diemToiDa,
        ];
    }

    if (count($criteriaRows) === 0) {
        return ['status' => false, 'message' => 'Không có tiêu chí hợp lệ để lưu'];
    }

    if ($idVongThi > 0) {
        $stmtVong = $conn->prepare('SELECT idVongThi FROM vongthi WHERE idVongThi = :idVongThi AND idSK = :idSK LIMIT 1');
        $stmtVong->execute([
            ':idVongThi' => $idVongThi,
            ':idSK' => $id_su_kien,
        ]);
        if (!(int) $stmtVong->fetchColumn()) {
            return ['status' => false, 'message' => 'Vòng thi không thuộc sự kiện hiện tại'];
        }
    }

    $idBoTieuChi = 0;
    $isUpdate = $editId > 0;

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối CSDL không hợp lệ'];
        }

        $conn->beginTransaction();

        if ($isUpdate) {
            if (!_is_exist($conn, 'botieuchi', 'idBoTieuChi', $editId)) {
                throw new RuntimeException('Bộ tiêu chí cần cập nhật không tồn tại');
            }

            $okUpdate = _update_info(
                $conn,
                'botieuchi',
                ['tenBoTieuChi', 'moTa'],
                [$tenBo, $moTaBo],
                ['idBoTieuChi' => ['=', $editId, '']]
            );

            if (!$okUpdate) {
                throw new RuntimeException('Không thể cập nhật thông tin bộ tiêu chí');
            }

            _delete_info(
                $conn,
                'cauhinh_tieuchi_sk',
                [
                    'idBoTieuChi' => ['=', $editId, 'AND'],
                    'idSK' => ['=', $id_su_kien, ''],
                ]
            );

            _delete_info($conn, 'botieuchi_tieuchi', ['idBoTieuChi' => ['=', $editId, '']]);

            $idBoTieuChi = $editId;
        } else {
            $create = tao_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien, $tenBo, $moTaBo);
            if (empty($create['status']) || empty($create['idBoTieuChi'])) {
                throw new RuntimeException($create['message'] ?? 'Không thể tạo bộ tiêu chí mới');
            }
            $idBoTieuChi = (int) $create['idBoTieuChi'];
        }

        if ($idVongThi > 0) {
            $stmtUpsert = $conn->prepare(
                'INSERT INTO cauhinh_tieuchi_sk (idSK, idVongThi, idBoTieuChi)
                 VALUES (:idSK, :idVongThi, :idBo)
                 ON DUPLICATE KEY UPDATE idBoTieuChi = VALUES(idBoTieuChi)'
            );

            $okUpsert = $stmtUpsert->execute([
                ':idSK' => $id_su_kien,
                ':idVongThi' => $idVongThi,
                ':idBo' => $idBoTieuChi,
            ]);

            if (!$okUpsert) {
                throw new RuntimeException('Không thể gán bộ tiêu chí vào vòng thi');
            }
        }

        foreach ($criteriaRows as $row) {
            $findOrCreate = tim_hoac_tao_tieu_chi_theo_noi_dung($conn, $row['noi_dung']);
            if (empty($findOrCreate['status']) || empty($findOrCreate['idTieuChi'])) {
                throw new RuntimeException($findOrCreate['message'] ?? 'Không thể khởi tạo/tái sử dụng tiêu chí');
            }

            $idTieuChi = (int) $findOrCreate['idTieuChi'];
            $insertFields = ['idBoTieuChi', 'idTieuChi', 'tyTrong'];
            $insertValues = [$idBoTieuChi, $idTieuChi, $row['ty_trong']];

            if ($row['diem_toi_da'] !== null) {
                $insertFields[] = 'diemToiDa';
                $insertValues[] = $row['diem_toi_da'];
            }

            $okInsert = _insert_info($conn, 'botieuchi_tieuchi', $insertFields, $insertValues);
            if (!$okInsert) {
                throw new RuntimeException('Không thể lưu cấu hình tiêu chí con');
            }
        }

        $conn->commit();

        return [
            'status' => true,
            'message' => $isUpdate ? 'Đã cập nhật bộ tiêu chí thành công' : 'Đã tạo bộ tiêu chí mới thành công',
            'data' => [
                'idBoTieuChi' => $idBoTieuChi,
                'mode' => $isUpdate ? 'update' : 'create',
                'soTieuChi' => count($criteriaRows),
                'idVongThi' => $idVongThi,
            ],
        ];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'status' => false,
            'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi lưu bộ tiêu chí',
        ];
    }
}

/**
 * Xóa bộ tiêu chí
 * - Kiểm tra quyền
 * - Kiểm tra bộ tiêu chí có đang được sử dụng không (trong cauhinh_tieuchi_sk hoặc tieuban)
 * - Nếu đang sử dụng, không cho xóa
 * - Nếu không, xóa bản ghi trong botieuchi_tieuchi trước, rồi xóa bộ tiêu chí
 */
/**
 * Gỡ bộ tiêu chí khỏi một vòng thi (xóa bản ghi cauhinh_tieuchi_sk)
 * Cảnh báo nếu đã có dữ liệu chấm nhưng vẫn cho phép gỡ
 */
function go_bo_tieu_chi_khoi_vong($conn, int $id_nguoi_thuc_hien, int $id_su_kien, int $id_bo, int $id_vong_thi): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền gỡ bộ tiêu chí'];
    }

    if ($id_bo <= 0 || $id_vong_thi <= 0) {
        return ['status' => false, 'message' => 'Thiếu thông tin hợp lệ'];
    }

    // Kiểm tra bản ghi tồn tại
    $stmtCheck = $conn->prepare(
        'SELECT COUNT(*) FROM cauhinh_tieuchi_sk WHERE idSK = :idSK AND idVongThi = :idVT AND idBoTieuChi = :idBo'
    );
    $stmtCheck->execute([':idSK' => $id_su_kien, ':idVT' => $id_vong_thi, ':idBo' => $id_bo]);
    if ((int) $stmtCheck->fetchColumn() === 0) {
        return ['status' => false, 'message' => 'Không tìm thấy cấu hình để gỡ'];
    }

    // Cảnh báo nếu đã có phân công chấm / chấm điểm liên quan
    $stmtCham = $conn->prepare(
        'SELECT COUNT(*) FROM chamtieuchi ct
         JOIN botieuchi_tieuchi bt ON ct.idTieuChi = bt.idTieuChi
         JOIN phancongcham pc ON ct.idPhanCongCham = pc.idPhanCongCham
         WHERE bt.idBoTieuChi = :idBo AND pc.idVongThi = :idVT AND pc.idSK = :idSK'
    );
    $stmtCham->execute([':idBo' => $id_bo, ':idVT' => $id_vong_thi, ':idSK' => $id_su_kien]);
    $soCham = (int) $stmtCham->fetchColumn();

    // Thực hiện gỡ
    $stmtDel = $conn->prepare(
        'DELETE FROM cauhinh_tieuchi_sk WHERE idSK = :idSK AND idVongThi = :idVT AND idBoTieuChi = :idBo'
    );
    $stmtDel->execute([':idSK' => $id_su_kien, ':idVT' => $id_vong_thi, ':idBo' => $id_bo]);

    $warnings = [];
    if ($soCham > 0) {
        $warnings[] = "Có {$soCham} lượt chấm điểm liên quan đã tồn tại. Dữ liệu chấm điểm không bị xóa.";
    }

    return [
        'status'   => true,
        'message'  => 'Gỡ bộ tiêu chí khỏi vòng thi thành công',
        'warnings' => $warnings,
    ];
}

function xoa_bo_tieu_chi($conn, int $id_nguoi_thuc_hien, int $id_su_kien, int $id_bo): array
{
    if (!xac_thuc_quyen_bo_tieu_chi($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền xóa bộ tiêu chí'];
    }

    if ($id_bo <= 0 || !_is_exist($conn, 'botieuchi', 'idBoTieuChi', $id_bo)) {
        return ['status' => false, 'message' => 'Bộ tiêu chí không tồn tại'];
    }

    // BTC chỉ được xóa bộ tiêu chí thuộc sự kiện của mình (có trong cauhinh_tieuchi_sk của sự kiện này)
    // Admin hệ thống (admin_criteria / admin_events) mới được xóa bộ bất kỳ
    $isAdmin = kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'admin_criteria')
        || kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'admin_events');

    if (!$isAdmin) {
        if ($id_su_kien <= 0) {
            return ['status' => false, 'message' => 'Thiếu thông tin sự kiện để xác minh quyền xóa'];
        }
        $stmtOwn = $conn->prepare(
            'SELECT COUNT(*) FROM cauhinh_tieuchi_sk WHERE idBoTieuChi = :idBo AND idSK = :idSK'
        );
        $stmtOwn->execute([':idBo' => $id_bo, ':idSK' => $id_su_kien]);
        if ((int) $stmtOwn->fetchColumn() === 0) {
            return ['status' => false, 'message' => 'Bạn chỉ có thể xóa bộ tiêu chí thuộc sự kiện của mình'];
        }
    }

    // Kiểm tra bộ tiêu chí có đang được sử dụng trong cauhinh_tieuchi_sk không
    $stmtCauHinh = $conn->prepare(
        'SELECT c.idVongThi, v.tenVongThi 
         FROM cauhinh_tieuchi_sk c
         LEFT JOIN vongthi v ON c.idVongThi = v.idVongThi
         WHERE c.idBoTieuChi = :idBo'
    );
    $stmtCauHinh->execute([':idBo' => $id_bo]);
    $vongThiSuDung = $stmtCauHinh->fetchAll(PDO::FETCH_ASSOC);

    // Kiểm tra bộ tiêu chí có đang được sử dụng trong tieuban không
    $tieubanSuDung = [];
    if (_criteria_table_has_column($conn, 'tieuban', 'idBoTieuChi')) {
        $stmtTieuBan = $conn->prepare(
            'SELECT idTieuBan, tenTieuBan 
             FROM tieuban 
             WHERE idBoTieuChi = :idBo'
        );
        $stmtTieuBan->execute([':idBo' => $id_bo]);
        $tieubanSuDung = $stmtTieuBan->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra có bản ghi chấm điểm nào đang sử dụng tiêu chí trong bộ này không
    $stmtChamDiem = $conn->prepare(
        'SELECT COUNT(*) FROM chamtieuchi ct
         JOIN botieuchi_tieuchi bt ON ct.idTieuChi = bt.idTieuChi
         WHERE bt.idBoTieuChi = :idBo'
    );
    $stmtChamDiem->execute([':idBo' => $id_bo]);
    $soChamDiem = (int) $stmtChamDiem->fetchColumn();

    // Nếu đang được sử dụng, trả về danh sách và không cho xóa
    $dangSuDung = [];
    if (!empty($vongThiSuDung)) {
        foreach ($vongThiSuDung as $v) {
            $dangSuDung[] = 'Vòng thi: ' . ($v['tenVongThi'] ?? 'ID ' . $v['idVongThi']);
        }
    }
    if (!empty($tieubanSuDung)) {
        foreach ($tieubanSuDung as $tb) {
            $dangSuDung[] = 'Tiểu ban: ' . ($tb['tenTieuBan'] ?? 'ID ' . $tb['idTieuBan']);
        }
    }
    if ($soChamDiem > 0) {
        $dangSuDung[] = "{$soChamDiem} lượt chấm điểm";
    }

    if (!empty($dangSuDung)) {
        return [
            'status' => false,
            'message' => 'Không thể xóa bộ tiêu chí đang được sử dụng',
            'hasRelatedData' => true,
            'relatedData' => $dangSuDung,
        ];
    }

    // Tiến hành xóa
    try {
        $conn->beginTransaction();

        // Xóa mapping tiêu chí con
        _delete_info($conn, 'botieuchi_tieuchi', ['idBoTieuChi' => ['=', $id_bo, '']]);

        // Xóa bộ tiêu chí
        $ok = _delete_info($conn, 'botieuchi', ['idBoTieuChi' => ['=', $id_bo, '']]);

        if (!$ok) {
            throw new RuntimeException('Không thể xóa bộ tiêu chí');
        }

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Đã xóa bộ tiêu chí thành công',
        ];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'status' => false,
            'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi xóa bộ tiêu chí',
        ];
    }
}
