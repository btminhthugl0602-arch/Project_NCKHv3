<?php

require_once __DIR__ . '/../core/base.php';

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

    $allowedLoai = ['THAMGIA_SV', 'THAMGIA_GV', 'VONGTHI', 'SANPHAM', 'GIAITHUONG'];
    if (!in_array($loai_quy_che, $allowedLoai, true)) {
        return ['status' => false, 'message' => 'Loại quy chế không hợp lệ'];
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

        $conn->beginTransaction();

        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['DON', trim((string) $ten_dieu_kien), $mo_ta]
        );

        if (!$ok) {
            $conn->rollBack();
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
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi lưu giá trị điều kiện'];
        }

        $conn->commit();
        return ['status' => true, 'idDieuKien' => $id_dieu_kien, 'message' => 'Đã tạo điều kiện đơn'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
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

        $conn->beginTransaction();

        $ok = _insert_info(
            $conn,
            'dieukien',
            ['loaiDieuKien', 'tenDieuKien', 'moTa'],
            ['TOHOP', trim((string) $ten_to_hop), $mo_ta]
        );

        if (!$ok) {
            $conn->rollBack();
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
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi liên kết tổ hợp'];
        }

        $conn->commit();
        return ['status' => true, 'idDieuKien' => $id_to_hop, 'message' => 'Đã tạo tổ hợp điều kiện'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
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
    try {
        if (!$conn instanceof PDO) {
            return false;
        }

        $stmt = $conn->prepare(
            'SELECT dk.idDieuKienCuoi
             FROM quyche q
             JOIN quyche_dieukien dk ON q.idQuyChe = dk.idQuyChe
             WHERE q.idSK = :idSK AND q.loaiQuyChe = :loaiQuyChe
             LIMIT 1'
        );
        $stmt->execute([
            ':idSK' => (int) $idSK,
            ':loaiQuyChe' => strtoupper(trim((string) $loaiQuyChe)),
        ]);
        $id_dieu_kien = $stmt->fetchColumn();

        if ($id_dieu_kien === false) {
            return true;
        }

        return kiem_tra_dieu_kien($conn, (int) $id_dieu_kien, $id_doi_tuong);
    } catch (Throwable $exception) {
        return false;
    }
}

function kiem_tra_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong)
{
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien', 'idDieuKien', (int) $id_dieu_kien);
    if (!$dk) {
        return false;
    }

    if ($dk['loaiDieuKien'] === 'DON') {
        return kiem_tra_dieu_kien_don($conn, (int) $id_dieu_kien, $id_doi_tuong);
    }

    if ($dk['loaiDieuKien'] === 'TOHOP') {
        return kiem_tra_to_hop_dieu_kien($conn, (int) $id_dieu_kien, $id_doi_tuong);
    }

    return false;
}

function kiem_tra_to_hop_dieu_kien($conn, $id_dieu_kien, $id_doi_tuong)
{
    $to_hop = truy_van_mot_ban_ghi($conn, 'tohop_dieukien', 'idDieuKien', (int) $id_dieu_kien);
    if (!$to_hop) {
        return false;
    }

    $ket_qua_trai = kiem_tra_dieu_kien($conn, (int) $to_hop['idDieuKienTrai'], $id_doi_tuong);
    $ket_qua_phai = kiem_tra_dieu_kien($conn, (int) $to_hop['idDieuKienPhai'], $id_doi_tuong);

    $toan_tu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $to_hop['idToanTu']);
    if (!$toan_tu) {
        return false;
    }

    $ky_hieu = strtoupper((string) $toan_tu['kyHieu']);
    if ($ky_hieu === 'AND') {
        return $ket_qua_trai && $ket_qua_phai;
    }

    if ($ky_hieu === 'OR') {
        return $ket_qua_trai || $ket_qua_phai;
    }

    return false;
}

function kiem_tra_dieu_kien_don($conn, $id_dieu_kien, $id_doi_tuong)
{
    $dk = truy_van_mot_ban_ghi($conn, 'dieukien_don', 'idDieuKien', (int) $id_dieu_kien);
    if (!$dk) {
        return false;
    }

    $gia_tri_thuc_te = lay_du_lieu_dong($conn, (int) $dk['idThuocTinhKiemTra'], $id_doi_tuong);
    if ($gia_tri_thuc_te === null) {
        return false;
    }

    $gia_tri_so_sanh = $dk['giaTriSoSanh'];
    $toan_tu = truy_van_mot_ban_ghi($conn, 'toantu', 'idToanTu', (int) $dk['idToanTu']);
    if (!$toan_tu) {
        return false;
    }

    $ky_hieu = (string) $toan_tu['kyHieu'];

    if (is_numeric($gia_tri_thuc_te) && is_numeric($gia_tri_so_sanh)) {
        $gia_tri_thuc_te = (float) $gia_tri_thuc_te;
        $gia_tri_so_sanh = (float) $gia_tri_so_sanh;
    } else {
        $gia_tri_thuc_te = trim((string) $gia_tri_thuc_te);
        $gia_tri_so_sanh = trim((string) $gia_tri_so_sanh);
    }

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
    $loai_ap_dung = (string) $tt['loaiApDung'];

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
