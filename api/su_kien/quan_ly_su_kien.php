<?php

require_once __DIR__ . '/../core/base.php';

function co_quyen_quan_ly_su_kien($conn, int $id_tk, int $id_sk = 0): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'admin_events')) {
        return true;
    }

    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'tao_su_kien')) {
        return true;
    }

    if ($id_sk > 0) {
        return kiem_tra_quyen_su_kien($conn, $id_tk, $id_sk, 'cauhinh_sukien');
    }

    return false;
}

function btc_tao_su_kien(
    $conn,
    $id_nguoi_tao,
    $ten_su_kien,
    $mo_ta,
    $id_cap,
    $ngay_mo_dk = null,
    $ngay_dong_dk = null,
    $ngay_bat_dau = null,
    $ngay_ket_thuc = null,
    $is_active = 1
) {
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_cap = ($id_cap !== null && (int) $id_cap > 0) ? (int) $id_cap : null;
    $is_active = ((int) $is_active === 1) ? 1 : 0;
    $ten_su_kien = trim((string) $ten_su_kien);

    if (!co_quyen_quan_ly_su_kien($conn, $id_nguoi_tao)) {
        return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
    }

    if ($ten_su_kien === '') {
        return ['status' => false, 'message' => 'Tên sự kiện không được để trống'];
    }

    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    if ($ngay_mo_dk !== null && $ngay_dong_dk !== null && strtotime((string) $ngay_mo_dk) > strtotime((string) $ngay_dong_dk)) {
        return ['status' => false, 'message' => 'Ngày mở đăng ký phải trước ngày đóng đăng ký'];
    }

    if ($ngay_bat_dau !== null && $ngay_ket_thuc !== null && strtotime((string) $ngay_bat_dau) > strtotime((string) $ngay_ket_thuc)) {
        return ['status' => false, 'message' => 'Ngày bắt đầu phải trước ngày kết thúc'];
    }

    if ($id_cap !== null && !_is_exist($conn, 'cap_tochuc', 'idCap', $id_cap)) {
        return ['status' => false, 'message' => 'Cấp tổ chức không tồn tại'];
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $fields = ['tenSK', 'moTa', 'nguoiTao', 'isActive'];
        $values = [$ten_su_kien, $mo_ta, $id_nguoi_tao, $is_active];

        if ($id_cap !== null) {
            $fields[] = 'idCap';
            $values[] = $id_cap;
        }

        $dateFields = [
            'ngayMoDangKy' => $ngay_mo_dk,
            'ngayDongDangKy' => $ngay_dong_dk,
            'ngayBatDau' => $ngay_bat_dau,
            'ngayKetThuc' => $ngay_ket_thuc,
        ];

        foreach ($dateFields as $column => $value) {
            if ($value !== null) {
                $fields[] = $column;
                $values[] = $value;
            }
        }

        $created = _insert_info($conn, 'sukien', $fields, $values);
        if (!$created) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
        }

        $id_sk = (int) $conn->lastInsertId();
        $id_vai_tro_sk_btc = lay_id_vai_tro_su_kien_mac_dinh($conn, $id_sk, 1);

        if ($id_vai_tro_sk_btc <= 0) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không tìm thấy vai trò BTC mặc định của sự kiện'];
        }

        $existsBtcRole = _select_info($conn, 'taikhoan_vaitro_sukien', ['id'], [
            'WHERE' => [
                'idTK', '=', $id_nguoi_tao, 'AND',
                'idSK', '=', $id_sk, 'AND',
                'idVaiTroSK', '=', $id_vai_tro_sk_btc, 'AND',
                'isActive', '=', 1, '',
            ],
            'LIMIT' => [1],
        ]);

        if (empty($existsBtcRole)) {
            $assigned = _insert_info(
                $conn,
                'taikhoan_vaitro_sukien',
                ['idTK', 'idSK', 'idVaiTroSK', 'idVaiTroGoc', 'nguonTao', 'idNguoiCap', 'isActive'],
                [$id_nguoi_tao, $id_sk, $id_vai_tro_sk_btc, 1, 'BTC_THEM', $id_nguoi_tao, 1]
            );

            if (!$assigned) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Tạo sự kiện thành công nhưng gán vai trò BTC thất bại'];
            }
        }

        if ($is_active === 1) {
            _gui_thong_bao_su_kien_moi($conn, $id_sk, $ten_su_kien, $id_nguoi_tao);
        }

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Đã khởi tạo sự kiện',
            'idSK' => $id_sk,
        ];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
    }
}

function _gui_thong_bao_su_kien_moi($conn, int $id_sk, string $ten_su_kien, int $id_nguoi_tao): void
{
    if (!$conn instanceof PDO) {
        return;
    }

    $created = _insert_info(
        $conn,
        'thongbao',
        ['idSK', 'tieuDe', 'noiDung', 'loaiThongBao', 'nguoiGui', 'isPublic'],
        [
            $id_sk,
            'Sự kiện mới: ' . $ten_su_kien,
            'Sự kiện "' . $ten_su_kien . '" vừa được công bố. Hãy xem chi tiết và đăng ký tham gia!',
            'su_kien_moi',
            $id_nguoi_tao,
            1,
        ]
    );

    if (!$created) {
        return;
    }

    $id_thong_bao = (int) $conn->lastInsertId();

    $stmt = $conn->prepare(
        'SELECT idTK FROM taikhoan WHERE isActive = 1 AND idLoaiTK IN (2, 3) AND idTK != :idNguoiTao'
    );
    $stmt->execute([':idNguoiTao' => $id_nguoi_tao]);
    $nguoi_nhan = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($nguoi_nhan)) {
        return;
    }

    $insertStmt = $conn->prepare(
        'INSERT INTO thongbao_nguoinhan (idThongBao, idTK, daDoc) VALUES (:idThongBao, :idTK, 0)'
    );

    foreach ($nguoi_nhan as $id_tk) {
        $insertStmt->execute([
            ':idThongBao' => $id_thong_bao,
            ':idTK' => (int) $id_tk,
        ]);
    }
}

function btc_cap_nhat_su_kien(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_su_kien,
    $mo_ta,
    $id_cap,
    $ngay_mo_dk,
    $ngay_dong_dk,
    $ngay_bat_dau,
    $ngay_ket_thuc,
    $is_active
) {
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_su_kien = (int) $id_su_kien;
    $id_cap = ($id_cap !== null && (int) $id_cap > 0) ? (int) $id_cap : null;
    $is_active = ((int) $is_active === 1) ? 1 : 0;
    $ten_su_kien = trim((string) $ten_su_kien);

    if ($id_su_kien <= 0 || $ten_su_kien === '') {
        return ['status' => false, 'message' => 'Dữ liệu cập nhật không hợp lệ'];
    }

    if (!co_quyen_quan_ly_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền cập nhật sự kiện'];
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if ($id_cap !== null && !_is_exist($conn, 'cap_tochuc', 'idCap', $id_cap)) {
        return ['status' => false, 'message' => 'Cấp tổ chức không tồn tại'];
    }

    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    if ($ngay_mo_dk !== null && $ngay_dong_dk !== null && strtotime((string) $ngay_mo_dk) > strtotime((string) $ngay_dong_dk)) {
        return ['status' => false, 'message' => 'Ngày mở đăng ký phải trước ngày đóng đăng ký'];
    }

    if ($ngay_bat_dau !== null && $ngay_ket_thuc !== null && strtotime((string) $ngay_bat_dau) > strtotime((string) $ngay_ket_thuc)) {
        return ['status' => false, 'message' => 'Ngày bắt đầu phải trước ngày kết thúc'];
    }

    $fields = ['tenSK', 'moTa', 'idCap', 'ngayMoDangKy', 'ngayDongDangKy', 'ngayBatDau', 'ngayKetThuc', 'isActive'];
    $values = [$ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active];

    $updated = _update_info($conn, 'sukien', $fields, $values, ['idSK' => ['=', $id_su_kien, '']]);

    return $updated
        ? ['status' => true, 'message' => 'Cập nhật sự kiện thành công']
        : ['status' => false, 'message' => 'Lỗi cập nhật sự kiện'];
}

function btc_lay_chi_tiet_su_kien($conn, $id_su_kien)
{
    $id_su_kien = (int) $id_su_kien;
    if ($id_su_kien <= 0) {
        return null;
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return null;
    }

    $cap = !empty($su_kien['idCap']) ? truy_van_mot_ban_ghi($conn, 'cap_tochuc', 'idCap', (int) $su_kien['idCap']) : null;
    $loai_cap = (!empty($cap) && !empty($cap['idLoaiCap'])) ? truy_van_mot_ban_ghi($conn, 'loaicap', 'idLoaiCap', (int) $cap['idLoaiCap']) : null;
    $nguoi_tao = !empty($su_kien['nguoiTao']) ? truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', (int) $su_kien['nguoiTao']) : null;

    $su_kien['tenCap'] = $cap['tenCap'] ?? null;
    $su_kien['tenLoaiCap'] = $loai_cap['tenLoaiCap'] ?? null;
    $su_kien['nguoiTaoTen'] = $nguoi_tao['tenTK'] ?? null;

    return $su_kien;
}
