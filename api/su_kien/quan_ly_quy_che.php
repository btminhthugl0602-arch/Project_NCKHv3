<?php

require_once __DIR__ . '/../core/base.php';

function bang_ton_tai($conn, $tableName)
{
    if (!$conn instanceof PDO) {
        return false;
    }

    $tableName = trim((string) $tableName);
    if ($tableName === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tableName)) {
        return false;
    }

    try {
        $stmt = $conn->prepare('SHOW TABLES LIKE :tableName');
        $stmt->execute([':tableName' => $tableName]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function ghi_log_quy_che(string $event, array $payload = [])
{
    $record = array_merge([
        'module' => 'quy_che',
        'event' => $event,
        'timestamp' => date('c'),
    ], $payload);

    error_log(json_encode($record, JSON_UNESCAPED_UNICODE));
}

function chuan_hoa_ma_ngu_canh($value)
{
    $normalized = strtoupper(trim((string) $value));
    if ($normalized === '') {
        return '';
    }

    $normalized = preg_replace('/\s+/', '_', $normalized);
    $normalized = preg_replace('/[^A-Z0-9_]/', '', $normalized);
    return trim((string) $normalized, '_');
}

function chuan_hoa_loai_ap_dung($loaiApDung, $bangDuLieu = '')
{
    $loai = strtoupper(trim((string) $loaiApDung));
    if ($loai === 'THAMGIA') {
        $bang = strtolower(trim((string) $bangDuLieu));
        if ($bang === 'giangvien') {
            return 'THAMGIA_GV';
        }
        return 'THAMGIA_SV';
    }
    return $loai;
}

function lay_danh_muc_ngu_canh_ap_dung($conn)
{
    if (!$conn instanceof PDO || !bang_ton_tai($conn, 'quyche_danhmuc_ngucanh')) {
        return [];
    }

    try {
        $stmt = $conn->prepare('SELECT maNguCanh FROM quyche_danhmuc_ngucanh WHERE isHeThong = 1 OR isHeThong IS NULL');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $clean = chuan_hoa_ma_ngu_canh($row);
            if ($clean !== '') {
                $result[$clean] = true;
            }
        }
        return array_keys($result);
    } catch (Throwable $exception) {
        return [];
    }
}

function lay_danh_muc_loai_quy_che()
{
    return [
        ['maLoai' => 'THAMGIA_SV', 'tenLoai' => 'Tham gia sinh vien'],
        ['maLoai' => 'THAMGIA_GV', 'tenLoai' => 'Tham gia giang vien'],
        ['maLoai' => 'VONGTHI', 'tenLoai' => 'Duyet vong thi'],
        ['maLoai' => 'SANPHAM', 'tenLoai' => 'Xu ly san pham'],
        ['maLoai' => 'GIAITHUONG', 'tenLoai' => 'Xet giai thuong'],
        ['maLoai' => 'TUY_CHINH', 'tenLoai' => 'Tuy chinh'],
    ];
}

function loai_quy_che_hop_le($value)
{
    $loai = strtoupper(trim((string) $value));
    if ($loai === '') {
        return false;
    }

    $allowed = [];
    foreach (lay_danh_muc_loai_quy_che() as $item) {
        $code = strtoupper(trim((string) ($item['maLoai'] ?? '')));
        if ($code !== '') {
            $allowed[$code] = true;
        }
    }

    return isset($allowed[$loai]);
}

function lay_bo_loc_loai_ap_dung_theo_loai_quy_che($loaiQuyChe)
{
    $loai = strtoupper(trim((string) $loaiQuyChe));
    if ($loai === 'THAMGIA') {
        return ['THAMGIA_SV', 'THAMGIA_GV'];
    }

    if ($loai === 'TUY_CHINH' || $loai === '') {
        return [];
    }

    if (!loai_quy_che_hop_le($loai)) {
        return [];
    }

    return [$loai];
}

function lay_bo_loc_loai_ap_dung_theo_ngu_canh(array $danhSachNguCanh)
{
    $map = [
        'DANG_KY_THAM_GIA_SV' => ['THAMGIA_SV'],
        'DANG_KY_THAM_GIA_GV' => ['THAMGIA_GV'],
        'DUYET_VONG_THI' => ['VONGTHI'],
        'DUYET_VONG_THI_HANG_LOAT' => ['VONGTHI'],
        'XET_GIAI_THUONG' => ['GIAITHUONG'],
        'NOP_SAN_PHAM' => ['SANPHAM'],
        'NOP_TAI_LIEU_VONG_THI' => ['VONGTHI'],
    ];

    $result = [];
    foreach ($danhSachNguCanh as $maNguCanh) {
        $clean = chuan_hoa_ma_ngu_canh($maNguCanh);
        if ($clean === '') {
            continue;
        }

        if (!isset($map[$clean])) {
            continue;
        }

        foreach ($map[$clean] as $loaiApDung) {
            $normalized = strtoupper(trim((string) $loaiApDung));
            if ($normalized !== '') {
                $result[$normalized] = true;
            }
        }
    }

    return array_keys($result);
}

function lay_whitelist_goi_y_quy_che()
{
    return [
        'sinhvien' => ['GPA', 'DRL'],
        'sanpham_vongthi' => ['diemTrungBinh', 'xepLoai', 'trangThai'],
        'ketqua' => ['diemTongKet', 'xepHang', 'idGiaiThuong'],
    ];
}

function cot_ton_tai_trong_bang($conn, $tableName, $columnName)
{
    if (!$conn instanceof PDO) {
        return false;
    }

    $tableName = trim((string) $tableName);
    $columnName = trim((string) $columnName);

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tableName) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $columnName)) {
        return false;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND COLUMN_NAME = :columnName'
        );
        $stmt->execute([
            ':tableName' => $tableName,
            ':columnName' => $columnName,
        ]);

        return ((int) $stmt->fetchColumn()) > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function cot_du_lieu_an_toan_cho_goi_y($conn, $bangDuLieu, $tenTruongDL)
{
    $bang = trim((string) $bangDuLieu);
    $truong = trim((string) $tenTruongDL);

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $bang) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $truong)) {
        return false;
    }

    $whitelist = lay_whitelist_goi_y_quy_che();
    if (!isset($whitelist[$bang])) {
        return false;
    }

    if (!in_array($truong, $whitelist[$bang], true)) {
        return false;
    }

    return cot_ton_tai_trong_bang($conn, $bang, $truong);
}

function xac_thuc_quyen_quy_che($conn, $id_user, $id_su_kien = 0)
{
    $id_user     = (int) $id_user;
    $id_su_kien  = (int) $id_su_kien;

    if (kiem_tra_quyen_he_thong($conn, $id_user, 'tao_su_kien')) {
        return true;
    }

    if ($id_su_kien > 0) {
        return kiem_tra_quyen_su_kien($conn, $id_user, $id_su_kien, 'cauhinh_sukien');
    }

    return false;
}

function tao_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien, $ten_quy_che, $loai_quy_che, $mo_ta = '')
{
    $id_su_kien = (int) $id_su_kien;
    $ten_quy_che = trim((string) $ten_quy_che);
    $loai_quy_che = strtoupper(trim((string) $loai_quy_che));

    if (!xac_thuc_quyen_quy_che($conn, (int) $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo quy chế'];
    }

    if ($id_su_kien <= 0 || $ten_quy_che === '') {
        return ['status' => false, 'message' => 'Dữ liệu quy chế không hợp lệ'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_su_kien)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if ($loai_quy_che === '') {
        $loai_quy_che = 'TUY_CHINH';
    }

    $result = _insert_info(
        $conn,
        'quyche',
        ['idSK', 'tenQuyChe', 'moTa', 'loaiQuyChe'],
        [$id_su_kien, $ten_quy_che, $mo_ta, $loai_quy_che]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Không tạo được quy chế'];
    }

    return [
        'status' => true,
        'idQuyChe' => (int) $conn->lastInsertId(),
        'message' => 'Đã tạo quy chế',
    ];
}

function gan_ngucanh_ap_dung_cho_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_quy_che, array $danh_sach_ngu_canh)
{
    if (!xac_thuc_quyen_quy_che($conn, (int) $id_nguoi_thuc_hien, (int) $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền gán ngữ cảnh áp dụng'];
    }

    if (!bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
        return ['status' => false, 'message' => 'Thiếu bảng quyche_ngucanh_apdung. Vui lòng chạy migration mới nhất'];
    }

    $id_quy_che = (int) $id_quy_che;
    if ($id_quy_che <= 0) {
        return ['status' => false, 'message' => 'id_quy_che không hợp lệ'];
    }

    $normalized = [];
    foreach ($danh_sach_ngu_canh as $ma) {
        $clean = chuan_hoa_ma_ngu_canh($ma);
        if ($clean !== '') {
            $normalized[$clean] = true;
        }
    }

    if (empty($normalized)) {
        return ['status' => false, 'message' => 'Quy chế phải có ít nhất 1 ngữ cảnh áp dụng'];
    }

    $danhMucNguCanh = lay_danh_muc_ngu_canh_ap_dung($conn);
    if (!empty($danhMucNguCanh)) {
        $allowedSet = array_fill_keys($danhMucNguCanh, true);
        foreach (array_keys($normalized) as $maNguCanh) {
            if (!isset($allowedSet[$maNguCanh])) {
                return ['status' => false, 'message' => 'Ngữ cảnh áp dụng không hợp lệ: ' . $maNguCanh];
            }
        }
    }

    try {
        $ownsTransaction = false;
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $ownsTransaction = true;
        }

        $stmtDelete = $conn->prepare('DELETE FROM quyche_ngucanh_apdung WHERE idQuyChe = :idQuyChe');
        $stmtDelete->execute([':idQuyChe' => $id_quy_che]);

        $stmtInsert = $conn->prepare(
            'INSERT INTO quyche_ngucanh_apdung (idQuyChe, maNguCanh)
             VALUES (:idQuyChe, :maNguCanh)'
        );

        foreach (array_keys($normalized) as $maNguCanh) {
            $stmtInsert->execute([
                ':idQuyChe' => $id_quy_che,
                ':maNguCanh' => $maNguCanh,
            ]);
        }

        if ($ownsTransaction) {
            $conn->commit();
        }
        return ['status' => true, 'message' => 'Đã gán ngữ cảnh áp dụng'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && !empty($ownsTransaction) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi khi gán ngữ cảnh áp dụng'];
    }
}

function lay_ngucanh_ap_dung_theo_quy_che($conn, $id_quy_che)
{
    if (!$conn instanceof PDO || !bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
        return [];
    }

    try {
        $stmt = $conn->prepare(
            'SELECT maNguCanh
             FROM quyche_ngucanh_apdung
             WHERE idQuyChe = :idQuyChe
             ORDER BY maNguCanh ASC'
        );
        $stmt->execute([':idQuyChe' => (int) $id_quy_che]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        return array_values(array_filter(array_map(function ($item) {
            return chuan_hoa_ma_ngu_canh($item);
        }, is_array($rows) ? $rows : [])));
    } catch (Throwable $exception) {
        return [];
    }
}

function tao_dieu_kien_don(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_dieu_kien,
    $id_thuoc_tinh,
    $id_toan_tu,
    $gia_tri_so_sanh,
    $mo_ta = ''
) {
    if (!xac_thuc_quyen_quy_che($conn, (int) $id_nguoi_thuc_hien, (int) $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo điều kiện'];
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $ownsTransaction = false;
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $ownsTransaction = true;
        }

        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['DON', trim((string) $ten_dieu_kien), $mo_ta]
        );

        if (!$ok) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['status' => false, 'message' => 'Lỗi tạo điều kiện'];
        }

        $id_dieu_kien = (int) $conn->lastInsertId();

        $ok = _insert_info(
            $conn,
            'dieukien_don',
            ['idDieuKien', 'idThuocTinhKiemTra', 'idToanTu', 'giaTriSoSanh'],
            [$id_dieu_kien, (int) $id_thuoc_tinh, (int) $id_toan_tu, (string) $gia_tri_so_sanh]
        );

        if (!$ok) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['status' => false, 'message' => 'Lỗi lưu giá trị điều kiện'];
        }

        if ($ownsTransaction) {
            $conn->commit();
        }
        return ['status' => true, 'idDieuKien' => $id_dieu_kien, 'message' => 'Đã tạo điều kiện đơn'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && !empty($ownsTransaction) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo điều kiện'];
    }
}

function tao_to_hop_dieu_kien(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $id_dieu_kien_trai,
    $id_toan_tu_logic,
    $id_dieu_kien_phai,
    $ten_to_hop,
    $mo_ta = ''
) {
    if (!xac_thuc_quyen_quy_che($conn, (int) $id_nguoi_thuc_hien, (int) $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền tạo tổ hợp'];
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $ownsTransaction = false;
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $ownsTransaction = true;
        }

        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['TOHOP', trim((string) $ten_to_hop), $mo_ta]
        );

        if (!$ok) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['status' => false, 'message' => 'Lỗi tạo tổ hợp điều kiện'];
        }

        $id_to_hop = (int) $conn->lastInsertId();

        $ok = _insert_info(
            $conn,
            'tohop_dieukien',
            ['idDieuKien', 'idDieuKienTrai', 'idDieuKienPhai', 'idToanTu'],
            [$id_to_hop, (int) $id_dieu_kien_trai, (int) $id_dieu_kien_phai, (int) $id_toan_tu_logic]
        );

        if (!$ok) {
            if ($ownsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['status' => false, 'message' => 'Lỗi liên kết tổ hợp'];
        }

        if ($ownsTransaction) {
            $conn->commit();
        }
        return ['status' => true, 'idDieuKien' => $id_to_hop, 'message' => 'Đã tạo tổ hợp điều kiện'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && !empty($ownsTransaction) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo tổ hợp'];
    }
}

function gan_dieu_kien_cho_quy_che($conn, $id_nguoi_thuc_hien, $id_su_kien, $id_quy_che, $id_dieu_kien_cuoi)
{
    if (!xac_thuc_quyen_quy_che($conn, (int) $id_nguoi_thuc_hien, (int) $id_su_kien)) {
        return ['status' => false, 'message' => 'Không đủ quyền gán quy chế'];
    }

    $id_quy_che = (int) $id_quy_che;
    $id_dieu_kien_cuoi = (int) $id_dieu_kien_cuoi;

    if ($id_quy_che <= 0 || $id_dieu_kien_cuoi <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $exists = _is_exist($conn, 'quyche_dieukien', 'idQuyChe', $id_quy_che);

    if ($exists) {
        $ok = _update_info(
            $conn,
            'quyche_dieukien',
            ['idDieuKienCuoi'],
            [$id_dieu_kien_cuoi],
            ['idQuyChe' => ['=', $id_quy_che, '']]
        );
    } else {
        $ok = _insert_info(
            $conn,
            'quyche_dieukien',
            ['idQuyChe', 'idDieuKienCuoi'],
            [$id_quy_che, $id_dieu_kien_cuoi]
        );
    }

    return $ok
        ? ['status' => true, 'message' => 'Đã gán điều kiện cho quy chế']
        : ['status' => false, 'message' => 'Không gán được điều kiện'];
}

function xet_duyet_quy_che_su_kien($conn, $idSK, $loaiQuyChe, $id_doi_tuong)
{
    $result = xet_duyet_quy_che_theo_ngucanh($conn, $idSK, $loaiQuyChe, $id_doi_tuong);
    return !empty($result['hopLe']);
}

function xet_duyet_quy_che_theo_ngucanh($conn, $idSK, $maNguCanh, $id_doi_tuong)
{
    $startedAt = microtime(true);
    try {
        if (!$conn instanceof PDO) {
            return [
                'hopLe' => false,
                'message' => 'Kết nối cơ sở dữ liệu không hợp lệ',
                'tongQuyChe' => 0,
                'viPham' => [],
            ];
        }

        $idSK = (int) $idSK;
        $maNguCanh = chuan_hoa_ma_ngu_canh($maNguCanh);
        if ($idSK <= 0 || $maNguCanh === '') {
            return [
                'hopLe' => false,
                'message' => 'Thiếu idSK hoặc mã ngữ cảnh áp dụng',
                'tongQuyChe' => 0,
                'viPham' => [],
            ];
        }

        $rules = [];

        if (bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
            $stmt = $conn->prepare(
                'SELECT DISTINCT q.idQuyChe, q.tenQuyChe, qd.idDieuKienCuoi
                 FROM quyche q
                 JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
                 LEFT JOIN quyche_ngucanh_apdung n ON n.idQuyChe = q.idQuyChe
                 WHERE q.idSK = :idSK
                   AND (n.maNguCanh = :maNguCanh OR UPPER(q.loaiQuyChe) = :maNguCanh)
                 ORDER BY q.idQuyChe ASC'
            );
            $stmt->execute([
                ':idSK' => $idSK,
                ':maNguCanh' => $maNguCanh,
            ]);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fallback legacy: nếu thiếu mapping ngữ cảnh, vẫn thử theo loaiQuyChe cũ
            // để tránh bỏ lọt quy chế (đặc biệt nhóm THAMGIA).
            if (empty($rules)) {
                $legacyLoaiMap = [
                    'DANG_KY_THAM_GIA_SV' => ['THAMGIA_SV', 'THAMGIA'],
                    'DANG_KY_THAM_GIA_GV' => ['THAMGIA_GV', 'THAMGIA'],
                    'DUYET_VONG_THI' => ['VONGTHI'],
                    'DUYET_VONG_THI_HANG_LOAT' => ['VONGTHI'],
                    'NOP_SAN_PHAM' => ['SANPHAM'],
                    'NOP_TAI_LIEU_VONG_THI' => ['VONGTHI'],
                    'XET_GIAI_THUONG' => ['GIAITHUONG'],
                ];

                $fallbackLoai = $legacyLoaiMap[$maNguCanh] ?? [$maNguCanh];
                $fallbackLoai = array_values(array_unique(array_filter(array_map('strtoupper', $fallbackLoai))));

                if (!empty($fallbackLoai)) {
                    $placeholders = [];
                    $params = [':idSK' => $idSK];
                    foreach ($fallbackLoai as $i => $code) {
                        $ph = ':loai' . $i;
                        $placeholders[] = $ph;
                        $params[$ph] = $code;
                    }

                    $stmtLegacy = $conn->prepare(
                        'SELECT DISTINCT q.idQuyChe, q.tenQuyChe, qd.idDieuKienCuoi
                         FROM quyche q
                         JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
                         WHERE q.idSK = :idSK
                           AND UPPER(q.loaiQuyChe) IN (' . implode(',', $placeholders) . ')
                         ORDER BY q.idQuyChe ASC'
                    );
                    $stmtLegacy->execute($params);
                    $rules = $stmtLegacy->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }
        } else {
            $stmt = $conn->prepare(
                'SELECT q.idQuyChe, q.tenQuyChe, qd.idDieuKienCuoi
                 FROM quyche q
                 JOIN quyche_dieukien qd ON q.idQuyChe = qd.idQuyChe
                 WHERE q.idSK = :idSK AND UPPER(q.loaiQuyChe) = :loaiQuyChe
                 ORDER BY q.idQuyChe ASC'
            );
            $stmt->execute([
                ':idSK' => $idSK,
                ':loaiQuyChe' => $maNguCanh,
            ]);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        if (empty($rules)) {
            ghi_log_quy_che('evaluate_rules', [
                'status' => 'success',
                'idSK' => $idSK,
                'maNguCanh' => $maNguCanh,
                'tongQuyChe' => 0,
                'viPhamCount' => 0,
                'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
            return [
                'hopLe' => true,
                'message' => 'Không có quy chế áp dụng cho ngữ cảnh này',
                'tongQuyChe' => 0,
                'viPham' => [],
            ];
        }

        $viPham = [];
        foreach ($rules as $rule) {
            $danhGia = kiem_tra_dieu_kien_chi_tiet($conn, (int) ($rule['idDieuKienCuoi'] ?? 0), $id_doi_tuong);
            if (empty($danhGia['hopLe'])) {
                $viPham[] = [
                    'idQuyChe' => (int) ($rule['idQuyChe'] ?? 0),
                    'tenQuyChe' => (string) ($rule['tenQuyChe'] ?? ''),
                    'chiTiet' => is_array($danhGia['chiTiet'] ?? null) ? $danhGia['chiTiet'] : [],
                ];
            }
        }

        ghi_log_quy_che('evaluate_rules', [
            'status' => empty($viPham) ? 'success' : 'failed_rules',
            'idSK' => $idSK,
            'maNguCanh' => $maNguCanh,
            'tongQuyChe' => count($rules),
            'viPhamCount' => count($viPham),
            'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return [
            'hopLe' => empty($viPham),
            'message' => empty($viPham) ? 'Đạt toàn bộ quy chế' : 'Không đạt một số quy chế',
            'tongQuyChe' => count($rules),
            'viPham' => $viPham,
        ];
    } catch (Throwable $exception) {
        ghi_log_quy_che('evaluate_rules', [
            'status' => 'error',
            'idSK' => (int) $idSK,
            'maNguCanh' => chuan_hoa_ma_ngu_canh($maNguCanh),
            'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
            'error' => $exception->getMessage(),
        ]);
        return [
            'hopLe' => false,
            'message' => 'Lỗi hệ thống khi xét duyệt quy chế',
            'tongQuyChe' => 0,
            'viPham' => [],
        ];
    }
}

function kiem_tra_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong)
{
    $ketQua = kiem_tra_dieu_kien_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong);
    return !empty($ketQua['hopLe']);
}

function kiem_tra_dieu_kien_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong, $depth = 0)
{
    if ($depth > 32) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'lyDo' => 'Vượt quá độ sâu cây điều kiện',
            ]],
        ];
    }

    $dk = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', (int) $id_dieu_kien);
    if (!$dk) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'lyDo' => 'Không tìm thấy điều kiện',
            ]],
        ];
    }

    if ($dk['loaiDieuKien'] === 'DON') {
        return kiem_tra_dieu_kien_don_chi_tiet($conn, (int) $id_dieu_kien, $id_doi_tuong);
    }

    if ($dk['loaiDieuKien'] === 'TOHOP') {
        return kiem_tra_to_hop_dieu_kien_chi_tiet($conn, (int) $id_dieu_kien, $id_doi_tuong, $depth + 1);
    }

    return [
        'hopLe' => false,
        'chiTiet' => [[
            'idDieuKien' => (int) $id_dieu_kien,
            'lyDo' => 'Loại điều kiện không hợp lệ',
        ]],
    ];
}

function kiem_tra_to_hop_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong)
{
    $ketQua = kiem_tra_to_hop_dieu_kien_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong);
    return !empty($ketQua['hopLe']);
}

function kiem_tra_to_hop_dieu_kien_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong, $depth = 0)
{
    $to_hop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', (int) $id_dieu_kien);
    if (!$to_hop) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'lyDo' => 'Không tìm thấy tổ hợp điều kiện',
            ]],
        ];
    }

    $ket_qua_trai = kiem_tra_dieu_kien_chi_tiet($conn, (int) $to_hop['idDieuKienTrai'], $id_doi_tuong, $depth + 1);
    $ket_qua_phai = kiem_tra_dieu_kien_chi_tiet($conn, (int) $to_hop['idDieuKienPhai'], $id_doi_tuong, $depth + 1);

    $toan_tu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $to_hop['idToanTu']);
    if (!$toan_tu) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'lyDo' => 'Không tìm thấy toán tử logic của tổ hợp điều kiện',
            ]],
        ];
    }

    $ky_hieu = strtoupper((string) $toan_tu['kyHieu']);
    if ($ky_hieu === 'AND') {
        $hopLe = !empty($ket_qua_trai['hopLe']) && !empty($ket_qua_phai['hopLe']);
        if ($hopLe) {
            return ['hopLe' => true, 'chiTiet' => []];
        }

        return [
            'hopLe' => false,
            'chiTiet' => array_values(array_merge(
                !empty($ket_qua_trai['hopLe']) ? [] : (array) ($ket_qua_trai['chiTiet'] ?? []),
                !empty($ket_qua_phai['hopLe']) ? [] : (array) ($ket_qua_phai['chiTiet'] ?? [])
            )),
        ];
    }

    if ($ky_hieu === 'OR') {
        $hopLe = !empty($ket_qua_trai['hopLe']) || !empty($ket_qua_phai['hopLe']);
        if ($hopLe) {
            return ['hopLe' => true, 'chiTiet' => []];
        }

        return [
            'hopLe' => false,
            'chiTiet' => array_values(array_merge(
                (array) ($ket_qua_trai['chiTiet'] ?? []),
                (array) ($ket_qua_phai['chiTiet'] ?? [])
            )),
        ];
    }

    return [
        'hopLe' => false,
        'chiTiet' => [[
            'idDieuKien' => (int) $id_dieu_kien,
            'lyDo' => 'Toán tử logic không được hỗ trợ',
        ]],
    ];
}

function kiem_tra_dieu_kien_don($conn, $id_dieu_kien, $id_doi_tuong)
{
    $ketQua = kiem_tra_dieu_kien_don_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong);
    return !empty($ketQua['hopLe']);
}

function so_sanh_dieu_kien($gia_tri_thuc_te, $gia_tri_so_sanh, $ky_hieu)
{
    switch ($ky_hieu) {
        case '=':
            return $gia_tri_thuc_te == $gia_tri_so_sanh;
        case '>':
            return $gia_tri_thuc_te > $gia_tri_so_sanh;
        case '<':
            return $gia_tri_thuc_te < $gia_tri_so_sanh;
        case '>=':
            return $gia_tri_thuc_te >= $gia_tri_so_sanh;
        case '<=':
            return $gia_tri_thuc_te <= $gia_tri_so_sanh;
        case '!=':
        case '<>':
            return $gia_tri_thuc_te != $gia_tri_so_sanh;
        default:
            return false;
    }
}

function kiem_tra_dieu_kien_don_chi_tiet($conn, $id_dieu_kien, $id_doi_tuong)
{
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien_don', 'idDieuKien', (int) $id_dieu_kien);
    if (!$dk) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'lyDo' => 'Không tìm thấy điều kiện đơn',
            ]],
        ];
    }

    $thuocTinh = truy_van_mot_ban_ghi($conn, 'thuoctinh_kiemtra', 'idThuocTinhKiemTra', (int) $dk['idThuocTinhKiemTra']);
    $toanTu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $dk['idToanTu']);
    if (!$thuocTinh || !$toanTu) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'lyDo' => 'Thiếu metadata thuộc tính hoặc toán tử',
            ]],
        ];
    }

    $gia_tri_thuc_te = lay_du_lieu_dong($conn, (int) $dk['idThuocTinhKiemTra'], $id_doi_tuong);
    if ($gia_tri_thuc_te === null) {
        return [
            'hopLe' => false,
            'chiTiet' => [[
                'idDieuKien' => (int) $id_dieu_kien,
                'tenThuocTinh' => (string) ($thuocTinh['tenThuocTinh'] ?? ''),
                'tenTruongDL' => (string) ($thuocTinh['tenTruongDL'] ?? ''),
                'toanTu' => (string) ($toanTu['kyHieu'] ?? ''),
                'giaTriKyVong' => $dk['giaTriSoSanh'],
                'giaTriThucTe' => null,
                'lyDo' => 'Không truy xuất được dữ liệu thực tế',
            ]],
        ];
    }

    $gia_tri_so_sanh = $dk['giaTriSoSanh'];
    $ky_hieu = (string) $toanTu['kyHieu'];

    if (is_numeric($gia_tri_thuc_te) && is_numeric($gia_tri_so_sanh)) {
        $gia_tri_thuc_te = (float) $gia_tri_thuc_te;
        $gia_tri_so_sanh = (float) $gia_tri_so_sanh;
    } else {
        $gia_tri_thuc_te = trim((string) $gia_tri_thuc_te);
        $gia_tri_so_sanh = trim((string) $gia_tri_so_sanh);
    }

    $hopLe = so_sanh_dieu_kien($gia_tri_thuc_te, $gia_tri_so_sanh, $ky_hieu);

    if ($hopLe) {
        return ['hopLe' => true, 'chiTiet' => []];
    }

    return [
        'hopLe' => false,
        'chiTiet' => [[
            'idDieuKien' => (int) $id_dieu_kien,
            'tenThuocTinh' => (string) ($thuocTinh['tenThuocTinh'] ?? ''),
            'tenTruongDL' => (string) ($thuocTinh['tenTruongDL'] ?? ''),
            'toanTu' => $ky_hieu,
            'giaTriKyVong' => $gia_tri_so_sanh,
            'giaTriThucTe' => $gia_tri_thuc_te,
            'lyDo' => 'Không thỏa điều kiện đơn',
        ]],
    ];
}

function lay_du_lieu_dong($conn, $idThuocTinh, $id_doi_tuong)
{
    if (!$conn instanceof PDO) {
        return null;
    }

    $tt = truy_van_mot_ban_ghi($conn, 'thuoctinh_kiemtra', 'idThuocTinhKiemTra', (int) $idThuocTinh);
    if (!$tt) {
        return null;
    }

    $truong = (string) $tt['tenTruongDL'];
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $truong)) {
        return null;
    }

    $bang = (string) $tt['bangDuLieu'];
    $loai_ap_dung = chuan_hoa_loai_ap_dung($tt['loaiApDung'] ?? '', $bang);

    $sql = '';
    $params = [];

    if ($loai_ap_dung === 'THAMGIA_SV' && $bang === 'sinhvien') {
        $sql = "SELECT {$truong} FROM sinhvien WHERE idTK = :id LIMIT 1";
        $params[':id'] = (int) $id_doi_tuong;
    } elseif ($loai_ap_dung === 'THAMGIA_GV' && $bang === 'giangvien') {
        $sql = "SELECT {$truong} FROM giangvien WHERE idTK = :id LIMIT 1";
        $params[':id'] = (int) $id_doi_tuong;
    } elseif ($loai_ap_dung === 'SANPHAM' && $bang === 'sanpham') {
        $sql = "SELECT {$truong} FROM sanpham WHERE idSanPham = :id LIMIT 1";
        $params[':id'] = (int) $id_doi_tuong;
    } elseif ($loai_ap_dung === 'VONGTHI' && $bang === 'sanpham_vongthi') {
        if (!is_array($id_doi_tuong) || !isset($id_doi_tuong['idSanPham'], $id_doi_tuong['idVongThi'])) {
            return null;
        }

        $sql = "SELECT {$truong} FROM sanpham_vongthi WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi LIMIT 1";
        $params[':idSanPham'] = (int) $id_doi_tuong['idSanPham'];
        $params[':idVongThi'] = (int) $id_doi_tuong['idVongThi'];
    } elseif ($loai_ap_dung === 'GIAITHUONG' && $bang === 'ketqua') {
        $sql = "SELECT {$truong} FROM ketqua WHERE idNhom = :idNhom LIMIT 1";
        $params[':idNhom'] = (int) $id_doi_tuong;
    } else {
        return null;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();

    return $value !== false ? $value : null;
}
