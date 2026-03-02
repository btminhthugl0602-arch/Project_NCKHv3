<?php

require_once __DIR__ . '/../core/base.php';

function co_quyen_to_chuc_su_kien($conn, int $id_tk, int $id_sk): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'admin_events')) {
        return true;
    }

    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'tao_su_kien')) {
        return true;
    }

    return kiem_tra_quyen_su_kien($conn, $id_tk, $id_sk, 'cauhinh_sukien');
}

function tao_lich_trinh($conn, $id_nguoi_tao, $id_sk, $ten_hoat_dong, $thoi_gian, $dia_diem, $id_vong_thi = null)
{
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_sk = (int) $id_sk;
    $id_vong_thi = ($id_vong_thi !== null && (int) $id_vong_thi > 0) ? (int) $id_vong_thi : null;
    $ten_hoat_dong = trim((string) $ten_hoat_dong);

    if ($id_nguoi_tao <= 0 || $id_sk <= 0 || $ten_hoat_dong === '' || empty($thoi_gian)) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_tao, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if ($id_vong_thi !== null) {
        $vong_thi = truy_van_mot_ban_ghi($conn, 'vongthi', 'idVongThi', $id_vong_thi);
        if (!$vong_thi || (int) $vong_thi['idSK'] !== $id_sk) {
            return ['status' => false, 'message' => 'Vòng thi không thuộc sự kiện'];
        }
    }

    $result = _insert_info(
        $conn,
        'lichtrinh',
        ['idSK', 'idVongThi', 'tenHoatDong', 'thoiGian', 'diaDiem'],
        [$id_sk, $id_vong_thi, $ten_hoat_dong, $thoi_gian, $dia_diem]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã thêm lịch trình', 'idLichTrinh' => (int) $conn->lastInsertId()]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function ghi_nhan_diem_danh(
    $conn,
    $id_nguoi_check,
    $id_nhom,
    $id_tk_sv,
    $trang_thai_hien_dien,
    $ghi_chu = '',
    $id_phien_dd = null,
    $phuong_thuc = 'Manual'
) {
    $id_nguoi_check = (int) $id_nguoi_check;
    $id_nhom = (int) $id_nhom;
    $id_tk_sv = (int) $id_tk_sv;
    $id_phien_dd = ($id_phien_dd !== null && (int) $id_phien_dd > 0) ? (int) $id_phien_dd : null;

    if ($id_nhom <= 0 || $id_tk_sv <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    if (!$nhom) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại'];
    }

    $id_sk = (int) ($nhom['idSK'] ?? 0);
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_check, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền điểm danh'];
    }

    if ($id_phien_dd !== null && !_is_exist($conn, 'phien_diemdanh', 'idPhienDD', $id_phien_dd)) {
        return ['status' => false, 'message' => 'Phiên điểm danh không tồn tại'];
    }

    $allowedMethods = ['QR', 'GPS', 'Manual', 'NFC'];
    if (!in_array($phuong_thuc, $allowedMethods, true)) {
        $phuong_thuc = 'Manual';
    }

    $result = _insert_info(
        $conn,
        'diemdanh',
        ['idNhom', 'idTK', 'thoiGianDiemDanh', 'hienDien', 'ghiChu', 'idPhienDD', 'phuongThuc'],
        [$id_nhom, $id_tk_sv, date('Y-m-d H:i:s'), $trang_thai_hien_dien ? 1 : 0, $ghi_chu, $id_phien_dd, $phuong_thuc]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã điểm danh']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function them_thanh_vien_btc($conn, $id_admin, $id_sk, $id_tk_can_bo, $chuc_vu = 'BTC')
{
    $id_admin = (int) $id_admin;
    $id_sk = (int) $id_sk;
    $id_tk_can_bo = (int) $id_tk_can_bo;

    if ($id_admin <= 0 || $id_sk <= 0 || $id_tk_can_bo <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!co_quyen_to_chuc_su_kien($conn, $id_admin, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk) || !_is_exist($conn, 'taikhoan', 'idTK', $id_tk_can_bo)) {
        return ['status' => false, 'message' => 'Sự kiện hoặc tài khoản không tồn tại'];
    }

    $id_vai_tro_sk_btc = lay_id_vai_tro_su_kien_mac_dinh($conn, $id_sk, 1);
    if ($id_vai_tro_sk_btc <= 0) {
        return ['status' => false, 'message' => 'Không tìm thấy vai trò BTC mặc định của sự kiện'];
    }

    $exists = _select_info($conn, 'taikhoan_vaitro_sukien', ['id'], [
        'WHERE' => [
            'idTK', '=', $id_tk_can_bo, 'AND',
            'idSK', '=', $id_sk, 'AND',
            'idVaiTroSK', '=', $id_vai_tro_sk_btc, 'AND',
            'isActive', '=', 1, '',
        ],
        'LIMIT' => [1],
    ]);

    if (!empty($exists)) {
        return ['status' => true, 'message' => 'Tài khoản đã là thành viên BTC'];
    }

    $result = _insert_info(
        $conn,
        'taikhoan_vaitro_sukien',
        ['idTK', 'idSK', 'idVaiTroSK', 'idVaiTroGoc', 'nguonTao', 'idNguoiCap', 'isActive'],
        [$id_tk_can_bo, $id_sk, $id_vai_tro_sk_btc, 1, 'BTC_THEM', $id_admin, 1]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Không thêm được thành viên BTC'];
    }

    return [
        'status' => true,
        'message' => 'Đã thêm cán bộ vào BTC',
        'vaiTro' => $chuc_vu,
    ];
}
