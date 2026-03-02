<?php

require_once __DIR__ . '/../core/base.php';

function lay_nhom_theo_id($conn, int $id_nhom): ?array
{
    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    return $nhom ?: null;
}

function la_truong_nhom($conn, int $id_tk, int $id_nhom): bool
{
    $rows = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => [
            'idnhom', '=', $id_nhom, 'AND',
            'idtk', '=', $id_tk, 'AND',
            'idvaitronhom', '=', 1, 'AND',
            'trangthai', '=', 1, '',
        ],
        'LIMIT' => [1],
    ]);

    return !empty($rows);
}

function so_thanh_vien_hien_tai($conn, int $id_nhom): int
{
    if (!$conn instanceof PDO) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT COUNT(*) FROM thanhviennhom WHERE idnhom = :idnhom AND trangthai = 1');
    $stmt->execute([':idnhom' => $id_nhom]);
    return (int) $stmt->fetchColumn();
}

function kiem_tra_sv_co_nhom($conn, $id_tk, $id_sk)
{
    $id_tk = (int) $id_tk;
    $id_sk = (int) $id_sk;

    if (!$conn instanceof PDO) {
        return false;
    }

    $sql = 'SELECT 1
            FROM thanhviennhom tv
            JOIN nhom n ON n.idnhom = tv.idnhom
            WHERE tv.idtk = :idtk
              AND n.idSK = :idSK
              AND tv.trangthai = 1
              AND n.isActive = 1
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idtk' => $id_tk,
        ':idSK' => $id_sk,
    ]);

    return (bool) $stmt->fetchColumn();
}

function tao_nhom_moi($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa)
{
    $idTK = (int) $idTK;
    $idSK = (int) $idSK;
    $tenNhom = trim((string) $tenNhom);
    $soLuongToiDa = max(1, (int) $soLuongToiDa);

    if ($idTK <= 0 || $idSK <= 0 || $tenNhom === '') {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $tai_khoan = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    if (!$tai_khoan || (int) $tai_khoan['idLoaiTK'] !== 3) {
        return ['status' => false, 'message' => 'Chỉ sinh viên mới được tạo nhóm'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $idSK)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if (kiem_tra_sv_co_nhom($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn đã tham gia một nhóm trong sự kiện này rồi'];
    }

    $maNhom = 'GRP_' . $idSK . '_' . time();

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $okNhom = _insert_info(
            $conn,
            'nhom',
            ['idSK', 'idChuNhom', 'manhom', 'ngaytao', 'isActive'],
            [$idSK, $idTK, $maNhom, date('Y-m-d H:i:s'), 1]
        );

        if (!$okNhom) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi tạo nhóm'];
        }

        $idNhomMoi = (int) $conn->lastInsertId();

        $okThongTin = _insert_info(
            $conn,
            'thongtinnhom',
            ['idnhom', 'tennhom', 'mota', 'soluongtoida', 'dangtuyen'],
            [$idNhomMoi, $tenNhom, $moTa, $soLuongToiDa, 1]
        );

        if (!$okThongTin) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi lưu thông tin nhóm'];
        }

        $okThanhVien = _insert_info(
            $conn,
            'thanhviennhom',
            ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
            [$idNhomMoi, $idTK, 1, 1, date('Y-m-d H:i:s')]
        );

        if (!$okThanhVien) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi thêm trưởng nhóm'];
        }

        $conn->commit();

        return ['status' => true, 'message' => 'Tạo nhóm thành công', 'idnhom' => $idNhomMoi, 'manhom' => $maNhom];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo nhóm'];
    }
}

function gui_yeu_cau_nhom($conn, $id_nhom, $id_tk_doi_phuong, $chieu_moi, $loi_nhan = '')
{
    $id_nhom = (int) $id_nhom;
    $id_tk_doi_phuong = (int) $id_tk_doi_phuong;
    $chieu_moi = ((int) $chieu_moi === 1) ? 1 : 0;

    if ($id_nhom <= 0 || $id_tk_doi_phuong <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $nhom = lay_nhom_theo_id($conn, $id_nhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    $doi_phuong = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_tk_doi_phuong);
    if (!$doi_phuong || (int) $doi_phuong['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Tài khoản đối phương không hợp lệ'];
    }

    $kt_thanhvien = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => [
            'idnhom', '=', $id_nhom, 'AND',
            'idtk', '=', $id_tk_doi_phuong, 'AND',
            'trangthai', '=', 1, '',
        ],
        'LIMIT' => [1],
    ]);

    if (!empty($kt_thanhvien)) {
        return ['status' => false, 'message' => 'Người này đã là thành viên của nhóm'];
    }

    if ((int) $doi_phuong['idLoaiTK'] === 3 && kiem_tra_sv_co_nhom($conn, $id_tk_doi_phuong, (int) $nhom['idSK'])) {
        return ['status' => false, 'message' => 'Sinh viên đã thuộc nhóm khác trong sự kiện'];
    }

    $kt_yeucau = _select_info($conn, 'yeucau_thamgia', [], [
        'WHERE' => [
            'idNhom', '=', $id_nhom, 'AND',
            'idTK', '=', $id_tk_doi_phuong, 'AND',
            'trangThai', '=', 0, '',
        ],
        'LIMIT' => [1],
    ]);

    if (!empty($kt_yeucau)) {
        return ['status' => false, 'message' => 'Đang có yêu cầu chờ xử lý'];
    }

    if ($chieu_moi === 0 && (int) $doi_phuong['idLoaiTK'] === 2) {
        $kt_gv = _select_info($conn, 'thanhviennhom', [], [
            'WHERE' => [
                'idnhom', '=', $id_nhom, 'AND',
                'idvaitronhom', '=', 3, 'AND',
                'trangthai', '=', 1, '',
            ],
            'LIMIT' => [1],
        ]);

        if (!empty($kt_gv)) {
            return ['status' => false, 'message' => 'Nhóm đã có giảng viên hướng dẫn'];
        }
    }

    $res = _insert_info(
        $conn,
        'yeucau_thamgia',
        ['idNhom', 'idTK', 'ChieuMoi', 'loiNhan', 'trangThai', 'ngayGui'],
        [$id_nhom, $id_tk_doi_phuong, $chieu_moi, trim((string) $loi_nhan), 0, date('Y-m-d H:i:s')]
    );

    return $res
        ? ['status' => true, 'message' => 'Gửi yêu cầu thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function duyet_yeu_cau_nhom($conn, $id_nguoi_duyet, $id_yeu_cau, $trang_thai_moi)
{
    $id_nguoi_duyet = (int) $id_nguoi_duyet;
    $id_yeu_cau = (int) $id_yeu_cau;
    $trang_thai_moi = (int) $trang_thai_moi;

    if (!in_array($trang_thai_moi, [1, 2], true)) {
        return ['status' => false, 'message' => 'Trạng thái duyệt không hợp lệ'];
    }

    $yc = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $id_yeu_cau);
    if (!$yc) {
        return ['status' => false, 'message' => 'Yêu cầu không tồn tại'];
    }

    if ((int) $yc['trangThai'] !== 0) {
        return ['status' => false, 'message' => 'Yêu cầu này đã được xử lý'];
    }

    $id_nhom = (int) $yc['idNhom'];
    $id_tk_yeu_cau = (int) $yc['idTK'];
    $nhom = lay_nhom_theo_id($conn, $id_nhom);

    if (!$nhom) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại'];
    }

    if ((int) $yc['ChieuMoi'] === 1) {
        if (!la_truong_nhom($conn, $id_nguoi_duyet, $id_nhom)) {
            return ['status' => false, 'message' => 'Chỉ trưởng nhóm mới được duyệt yêu cầu này'];
        }
    } else {
        if ($id_tk_yeu_cau !== $id_nguoi_duyet) {
            return ['status' => false, 'message' => 'Bạn không phải người được mời'];
        }
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $okUpdate = _update_info(
            $conn,
            'yeucau_thamgia',
            ['trangThai', 'ngayPhanHoi'],
            [$trang_thai_moi, date('Y-m-d H:i:s')],
            ['idYeuCau' => ['=', $id_yeu_cau, '']]
        );

        if (!$okUpdate) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không cập nhật được yêu cầu'];
        }

        if ($trang_thai_moi === 1) {
            $user_join = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_tk_yeu_cau);
            if (!$user_join) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Tài khoản yêu cầu không tồn tại'];
            }

            if ((int) $user_join['idLoaiTK'] === 3) {
                $thong_tin_nhom = _select_info($conn, 'thongtinnhom', ['soluongtoida'], [
                    'WHERE' => ['idnhom', '=', $id_nhom, ''],
                    'LIMIT' => [1],
                ]);
                $so_luong_toi_da = !empty($thong_tin_nhom) ? (int) $thong_tin_nhom[0]['soluongtoida'] : 5;
                if (so_thanh_vien_hien_tai($conn, $id_nhom) >= $so_luong_toi_da) {
                    $conn->rollBack();
                    return ['status' => false, 'message' => 'Nhóm đã đủ số lượng thành viên tối đa'];
                }

                if (kiem_tra_sv_co_nhom($conn, $id_tk_yeu_cau, (int) $nhom['idSK'])) {
                    $conn->rollBack();
                    return ['status' => false, 'message' => 'Sinh viên đã thuộc nhóm khác trong sự kiện'];
                }
            }

            $vai_tro = ((int) $user_join['idLoaiTK'] === 2) ? 3 : 2;

            $existing = _select_info($conn, 'thanhviennhom', [], [
                'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_yeu_cau, ''],
                'LIMIT' => [1],
            ]);

            if (!empty($existing)) {
                $okMember = _update_info(
                    $conn,
                    'thanhviennhom',
                    ['idvaitronhom', 'trangthai', 'ngaythamgia'],
                    [$vai_tro, 1, date('Y-m-d H:i:s')],
                    ['idnhom' => ['=', $id_nhom, 'AND'], 'idtk' => ['=', $id_tk_yeu_cau, '']]
                );
            } else {
                $okMember = _insert_info(
                    $conn,
                    'thanhviennhom',
                    ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
                    [$id_nhom, $id_tk_yeu_cau, $vai_tro, 1, date('Y-m-d H:i:s')]
                );
            }

            if (!$okMember) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi thêm thành viên'];
            }

            if ($vai_tro === 2) {
                _update_info(
                    $conn,
                    'thanhviennhom',
                    ['trangthai'],
                    [0],
                    ['idtk' => ['=', $id_tk_yeu_cau, 'AND'], 'idnhom' => ['!=', $id_nhom, 'AND'], 'trangthai' => ['=', 1, '']]
                );
            }
        }

        $conn->commit();
        return ['status' => true, 'message' => ($trang_thai_moi === 1 ? 'Đã chấp nhận' : 'Đã từ chối')];
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi duyệt yêu cầu'];
    }
}

function roi_nhom($conn, $id_nguoi_thuc_hien, $id_nhom, $id_tk_bi_xoa)
{
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_nhom = (int) $id_nhom;
    $id_tk_bi_xoa = (int) $id_tk_bi_xoa;

    $nhom = lay_nhom_theo_id($conn, $id_nhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại'];
    }

    if ($id_nguoi_thuc_hien !== $id_tk_bi_xoa && !la_truong_nhom($conn, $id_nguoi_thuc_hien, $id_nhom)) {
        return ['status' => false, 'message' => 'Bạn không có quyền loại thành viên khỏi nhóm'];
    }

    $tv = _select_info($conn, 'thanhviennhom', [], [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk_bi_xoa, 'AND', 'trangthai', '=', 1, ''],
        'LIMIT' => [1],
    ]);

    if (empty($tv)) {
        return ['status' => false, 'message' => 'Thành viên không tồn tại trong nhóm'];
    }

    if ((int) $tv[0]['idvaitronhom'] === 1) {
        return ['status' => false, 'message' => 'Trưởng nhóm không thể rời nhóm. Hãy chuyển quyền trước'];
    }

    $result = _update_info(
        $conn,
        'thanhviennhom',
        ['trangthai'],
        [0],
        ['idnhom' => ['=', $id_nhom, 'AND'], 'idtk' => ['=', $id_tk_bi_xoa, '']]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã cập nhật trạng thái rời nhóm']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

function tim_kiem_giang_vien($conn, $keyword)
{
    if (!$conn instanceof PDO) {
        return [];
    }

    $keyword = '%' . trim((string) $keyword) . '%';

    $sql = 'SELECT tk.idTK, gv.tenGV, gv.idKhoa
            FROM taikhoan tk
            JOIN giangvien gv ON tk.idTK = gv.idTK
            WHERE tk.idLoaiTK = 2
              AND tk.isActive = 1
              AND gv.tenGV LIKE :keyword
            LIMIT 10';

    $stmt = $conn->prepare($sql);
    $stmt->execute([':keyword' => $keyword]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tim_kiem_sinh_vien($conn, $keyword)
{
    if (!$conn instanceof PDO) {
        return [];
    }

    $keyword = '%' . trim((string) $keyword) . '%';

    $sql = 'SELECT tk.idTK, sv.tenSV, sv.MSV, l.tenLop
            FROM taikhoan tk
            JOIN sinhvien sv ON tk.idTK = sv.idTK
            LEFT JOIN lop l ON sv.idLop = l.idLop
            WHERE tk.idLoaiTK = 3
              AND tk.isActive = 1
              AND (sv.tenSV LIKE :keyword OR sv.MSV LIKE :keyword2)
            LIMIT 10';

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':keyword' => $keyword,
        ':keyword2' => $keyword,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
