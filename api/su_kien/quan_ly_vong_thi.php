<?php

require_once __DIR__ . '/../core/base.php';

function co_quyen_quan_ly_vong_thi($conn, int $id_nguoi_thuc_hien, int $id_sk): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'admin_events')) {
        return true;
    }

    if (kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return true;
    }

    return kiem_tra_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_sk, 'cauhinh_vongthi')
        || kiem_tra_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_sk, 'cauhinh_sukien');
}

function tao_vong_thi($conn, $id_nguoi_tao, $id_sk, $ten_vong, $mo_ta, $thu_tu, $ngay_bd, $ngay_kt)
{
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_sk = (int) $id_sk;
    $thu_tu = max(1, (int) $thu_tu);
    $ten_vong = trim((string) $ten_vong);

    if ($id_nguoi_tao <= 0 || $id_sk <= 0 || $ten_vong === '') {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_tao, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền cấu hình vòng thi'];
    }

    $ngay_bd = !empty($ngay_bd) ? $ngay_bd : null;
    $ngay_kt = !empty($ngay_kt) ? $ngay_kt : null;

    if ($ngay_bd !== null && $ngay_kt !== null && strtotime((string) $ngay_bd) > strtotime((string) $ngay_kt)) {
        return ['status' => false, 'message' => 'Ngày bắt đầu không được lớn hơn ngày kết thúc'];
    }

    $result = _insert_info(
        $conn,
        'vongthi',
        ['idSK', 'tenVongThi', 'moTa', 'thuTu', 'ngayBatDau', 'ngayKetThuc'],
        [$id_sk, $ten_vong, $mo_ta, $thu_tu, $ngay_bd, $ngay_kt]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo vòng thi'];
    }

    return [
        'status' => true,
        'message' => 'Đã tạo vòng thi',
        'idVongThi' => (int) $conn->lastInsertId(),
    ];
}

function cap_nhat_vong_thi($conn, $id_nguoi_sua, $id_vong_thi, $ten_vong, $mo_ta, $ngay_bd, $ngay_kt, $thu_tu = null)
{
    $id_nguoi_sua = (int) $id_nguoi_sua;
    $id_vong_thi = (int) $id_vong_thi;
    $ten_vong = trim((string) $ten_vong);

    if ($id_nguoi_sua <= 0 || $id_vong_thi <= 0 || $ten_vong === '') {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $vong_thi = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
    if (!$vong_thi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_sua, (int) $vong_thi['idSK'])) {
        return ['status' => false, 'message' => 'Không có quyền cập nhật vòng thi'];
    }

    $ngay_bd = !empty($ngay_bd) ? $ngay_bd : null;
    $ngay_kt = !empty($ngay_kt) ? $ngay_kt : null;

    if ($ngay_bd !== null && $ngay_kt !== null && strtotime((string) $ngay_bd) > strtotime((string) $ngay_kt)) {
        return ['status' => false, 'message' => 'Ngày bắt đầu không được lớn hơn ngày kết thúc'];
    }

    $fields = ['tenVongThi', 'moTa', 'ngayBatDau', 'ngayKetThuc'];
    $values = [$ten_vong, $mo_ta, $ngay_bd, $ngay_kt];

    if ($thu_tu !== null) {
        $fields[] = 'thuTu';
        $values[] = max(1, (int) $thu_tu);
    }

    $conditions = ['idVongThi' => ['=', $id_vong_thi, '']];

    $result = _update_info($conn, 'vongthi', $fields, $values, $conditions);

    return $result
        ? ['status' => true, 'message' => 'Cập nhật vòng thi thành công']
        : ['status' => false, 'message' => 'Lỗi cập nhật vòng thi'];
}

function lay_ds_vong_thi($conn, $id_sk)
{
    $id_sk = (int) $id_sk;
    if ($id_sk <= 0) {
        return [];
    }

    $conditions = [
        'WHERE' => ['idSK', '=', $id_sk, ''],
        'ORDER BY' => ['thuTu', 'ASC'],
    ];

    $data = _select_info($conn, 'vongthi', [], $conditions);
    return is_array($data) ? $data : [];
}

function lay_chi_tiet_vong_thi($conn, $id_vong_thi)
{
    $id_vong_thi = (int) $id_vong_thi;
    if ($id_vong_thi <= 0) {
        return null;
    }

    $conditions = [
        'WHERE' => ['idVongThi', '=', $id_vong_thi, ''],
        'LIMIT' => [1],
    ];

    $data = _select_info($conn, 'vongthi', [], $conditions);
    return (!empty($data) && is_array($data)) ? $data[0] : null;
}

function xoa_vong_thi($conn, $id_nguoi_xoa, $id_vong_thi)
{
    $id_nguoi_xoa = (int) $id_nguoi_xoa;
    $id_vong_thi = (int) $id_vong_thi;

    if ($id_nguoi_xoa <= 0 || $id_vong_thi <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $vong_thi = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
    if (!$vong_thi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_xoa, (int) $vong_thi['idSK'])) {
        return ['status' => false, 'message' => 'Không có quyền xóa vòng thi'];
    }

    $conditions = ['idVongThi' => ['=', $id_vong_thi, '']];
    $result = _delete_info($conn, 'vongthi', $conditions);

    return $result
        ? ['status' => true, 'message' => 'Đã xóa vòng thi']
        : ['status' => false, 'message' => 'Không thể xóa vòng thi (có thể đang có dữ liệu liên quan)'];
}
