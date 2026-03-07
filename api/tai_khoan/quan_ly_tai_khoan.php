<?php

require_once __DIR__ . '/../core/base.php';

function lay_ban_ghi_theo_khoa($conn, $bang, $cot_khoa, $gia_tri)
{
    return truy_van_mot_ban_ghi($conn, $bang, $cot_khoa, $gia_tri);
}

function lay_id_quyen_theo_ma($conn, $ma_quyen)
{
    return anh_xa_ma_quyen($conn, $ma_quyen);
}

function co_quyen_quan_ly_tai_khoan($conn, int $id_nguoi_thuc_hien): bool
{
    return kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'quan_ly_tai_khoan');
}

function tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop)
{
    $id_tai_khoan = (int) $id_tai_khoan;
    $id_lop = (int) $id_lop;
    $ma_so_sinh_vien = trim((string) $ma_so_sinh_vien);
    $ho_ten = trim((string) $ho_ten);

    if ($ma_so_sinh_vien === '') {
        throw new Exception('Mã sinh viên không được để trống');
    }

    if ($ho_ten === '') {
        throw new Exception('Họ tên sinh viên không được để trống');
    }

    if (kiem_tra_ton_tai_ban_ghi($conn, 'sinhvien', 'MSV', $ma_so_sinh_vien)) {
        throw new Exception('Mã sinh viên đã tồn tại');
    }

    $lop = lay_ban_ghi_theo_khoa($conn, 'lop', 'idLop', $id_lop);
    if (!$lop) {
        throw new Exception('Lớp không tồn tại');
    }

    $id_khoa = (int) $lop['idKhoa'];

    $result = _insert_info(
        $conn,
        'sinhvien',
        ['idTK', 'tenSV', 'MSV', 'idLop', 'idKhoa'],
        [$id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_lop, $id_khoa]
    );

    if (!$result) {
        throw new Exception('Không thể tạo hồ sơ sinh viên');
    }
}

function tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_khoa)
{
    $id_tai_khoan = (int) $id_tai_khoan;
    $id_khoa = (int) $id_khoa;
    $ho_ten = trim((string) $ho_ten);

    if ($ho_ten === '') {
        throw new Exception('Họ tên giảng viên không được để trống');
    }

    if ($id_khoa > 0 && !_is_exist($conn, 'khoa', 'idKhoa', $id_khoa)) {
        throw new Exception('Khoa không tồn tại');
    }

    $result = _insert_info(
        $conn,
        'giangvien',
        ['idTK', 'tenGV', 'idKhoa'],
        [$id_tai_khoan, $ho_ten, $id_khoa > 0 ? $id_khoa : null]
    );

    if (!$result) {
        throw new Exception('Không thể tạo hồ sơ giảng viên');
    }
}

function admin_tao_tai_khoan(
    $conn,
    $id_nguoi_thuc_hien,
    $ten_dang_nhap,
    $mat_khau,
    $id_loai_tai_khoan,
    $ho_ten,
    $id_don_vi,
    $ma_so_sinh_vien = ''
) {
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_loai_tai_khoan = (int) $id_loai_tai_khoan;
    $id_don_vi = (int) $id_don_vi;
    $ten_dang_nhap = trim((string) $ten_dang_nhap);
    $mat_khau = (string) $mat_khau;

    if (!co_quyen_quan_ly_tai_khoan($conn, $id_nguoi_thuc_hien)) {
        return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
    }

    if ($ten_dang_nhap === '' || $mat_khau === '') {
        return ['status' => false, 'message' => 'Tên đăng nhập và mật khẩu không được để trống'];
    }

    if (!in_array($id_loai_tai_khoan, [1, 2, 3], true)) {
        return ['status' => false, 'message' => 'Loại tài khoản không hợp lệ'];
    }

    if (kiem_tra_ton_tai_ban_ghi($conn, 'taikhoan', 'tenTK', $ten_dang_nhap)) {
        return ['status' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $mat_khau_ma_hoa = password_hash($mat_khau, PASSWORD_DEFAULT);

        $res = _insert_info(
            $conn,
            'taikhoan',
            ['tenTK', 'matKhau', 'idLoaiTK', 'isActive'],
            [$ten_dang_nhap, $mat_khau_ma_hoa, $id_loai_tai_khoan, 1]
        );

        if (!$res) {
            throw new Exception('Lỗi tạo tài khoản chính');
        }

        $id_tai_khoan = (int) $conn->lastInsertId();

        if ($id_loai_tai_khoan === 3) {
            tao_tai_khoan_sinh_vien($conn, $id_tai_khoan, $ho_ten, $ma_so_sinh_vien, $id_don_vi);
        } elseif ($id_loai_tai_khoan === 2) {
            tao_tai_khoan_giang_vien($conn, $id_tai_khoan, $ho_ten, $id_don_vi);
        }

        $conn->commit();
        return ['status' => true, 'message' => 'Tạo tài khoản thành công', 'idTK' => $id_tai_khoan];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return ['status' => false, 'message' => $exception->getMessage()];
    }
}

function admin_khoa_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_tai_khoan = (int) $id_tai_khoan;

    if (!co_quyen_quan_ly_tai_khoan($conn, $id_nguoi_thuc_hien)) {
        return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
    }

    if ($id_nguoi_thuc_hien === $id_tai_khoan) {
        return ['status' => false, 'message' => 'Không thể tự khóa tài khoản'];
    }

    if (!_is_exist($conn, 'taikhoan', 'idTK', $id_tai_khoan)) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }

    $result = _update_info($conn, 'taikhoan', ['isActive'], [0], ['idTK' => ['=', $id_tai_khoan, '']]);

    return $result
        ? ['status' => true, 'message' => 'Đã khóa tài khoản']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function upsert_quyen_cho_tai_khoan($conn, int $id_tai_khoan, int $id_quyen): bool
{
    $exists = _select_info($conn, 'taikhoan_quyen', [], [
        'WHERE' => [
            'idTK', '=', $id_tai_khoan, 'AND',
            'idQuyen', '=', $id_quyen, '',
        ],
        'LIMIT' => [1],
    ]);

    if (!empty($exists)) {
        return _update_info(
            $conn,
            'taikhoan_quyen',
            ['isActive', 'thoiGianBatDau', 'thoiGianKetThuc'],
            [1, date('Y-m-d H:i:s'), null],
            ['idTK' => ['=', $id_tai_khoan, 'AND'], 'idQuyen' => ['=', $id_quyen, '']]
        );
    }

    return _insert_info(
        $conn,
        'taikhoan_quyen',
        ['idTK', 'idQuyen', 'isActive', 'thoiGianBatDau'],
        [$id_tai_khoan, $id_quyen, 1, date('Y-m-d H:i:s')]
    );
}

function admin_gan_quyen_cho_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $ma_quyen)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_tai_khoan = (int) $id_tai_khoan;

    if (!co_quyen_quan_ly_tai_khoan($conn, $id_nguoi_thuc_hien)) {
        return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
    }

    if (!_is_exist($conn, 'taikhoan', 'idTK', $id_tai_khoan)) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }

    $id_quyen = lay_id_quyen_theo_ma($conn, $ma_quyen);
    if (!$id_quyen) {
        return ['status' => false, 'message' => 'Mã quyền không hợp lệ'];
    }

    $ok = upsert_quyen_cho_tai_khoan($conn, $id_tai_khoan, (int) $id_quyen);

    return $ok
        ? ['status' => true, 'message' => 'Gán quyền thành công']
        : ['status' => false, 'message' => 'Gán quyền thất bại'];
}

function admin_cap_nhat_quyen_tai_khoan($conn, $id_nguoi_thuc_hien, $id_tai_khoan, $danh_sach_ma_quyen)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_tai_khoan = (int) $id_tai_khoan;

    if (!co_quyen_quan_ly_tai_khoan($conn, $id_nguoi_thuc_hien)) {
        return ['status' => false, 'message' => 'Không đủ quyền thao tác'];
    }

    if (!_is_exist($conn, 'taikhoan', 'idTK', $id_tai_khoan)) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }

    $danh_sach_ma_quyen = is_array($danh_sach_ma_quyen) ? $danh_sach_ma_quyen : [];

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $disableOk = _update_info(
            $conn,
            'taikhoan_quyen',
            ['isActive', 'thoiGianKetThuc'],
            [0, date('Y-m-d H:i:s')],
            ['idTK' => ['=', $id_tai_khoan, '']]
        );

        if ($disableOk === false) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không thể cập nhật quyền hiện tại'];
        }

        foreach ($danh_sach_ma_quyen as $ma_quyen) {
            $id_quyen = lay_id_quyen_theo_ma($conn, (string) $ma_quyen);
            if (!$id_quyen) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Mã quyền không hợp lệ: ' . $ma_quyen];
            }

            if (!upsert_quyen_cho_tai_khoan($conn, $id_tai_khoan, (int) $id_quyen)) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Không thể gán quyền: ' . $ma_quyen];
            }
        }

        $conn->commit();
        return ['status' => true, 'message' => 'Cập nhật quyền thành công'];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return ['status' => false, 'message' => $exception->getMessage()];
    }
}
