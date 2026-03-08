<?php

require_once __DIR__ . '/../core/base.php';

function lay_nhom_theo_id($conn, int $id_nhom): ?array
{
    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idnhom', $id_nhom);
    return $nhom ?: null;
}

function la_truong_nhom($conn, int $id_tk, int $id_nhom): bool
{
    if (!$conn instanceof PDO) return false;

    $stmt = $conn->prepare("
        SELECT 1
        FROM thanhviennhom tv
        JOIN vaitronhom v ON v.id = tv.idvaitronhom
        WHERE tv.idnhom = :idNhom
          AND tv.idtk = :idTK
          AND tv.trangthai = 1
          AND v.maVaiTroNhom = 'TRUONG_NHOM'
        LIMIT 1
    ");
    $stmt->execute([':idNhom' => $id_nhom, ':idTK' => $id_tk]);
    return (bool) $stmt->fetchColumn();
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

function tao_nhom_moi($conn, $idTK, $idSK, $tenNhom, $moTa, $soLuongToiDa, $dangTuyen = 1)
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
            [$idNhomMoi, $tenNhom, $moTa, $soLuongToiDa, (int)$dangTuyen]
        );

        if (!$okThongTin) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi lưu thông tin nhóm'];
        }

        $stmtVT = $conn->prepare("SELECT id FROM vaitronhom WHERE maVaiTroNhom = 'TRUONG_NHOM' LIMIT 1");
        $stmtVT->execute();
        $idVaiTroTruongNhom = (int) $stmtVT->fetchColumn();
        if ($idVaiTroTruongNhom <= 0) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không tìm thấy vai trò Trưởng nhóm'];
        }

        $okThanhVien = _insert_info(
            $conn,
            'thanhviennhom',
            ['idnhom', 'idtk', 'idvaitronhom', 'trangthai', 'ngaythamgia'],
            [$idNhomMoi, $idTK, $idVaiTroTruongNhom, 1, date('Y-m-d H:i:s')]
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
        $stmtGV = $conn->prepare("
            SELECT 1
            FROM thanhviennhom tv
            JOIN vaitronhom v ON v.id = tv.idvaitronhom
            WHERE tv.idnhom = :idNhom
              AND tv.trangthai = 1
              AND v.maVaiTroNhom = 'GVHD'
            LIMIT 1
        ");
        $stmtGV->execute([':idNhom' => $id_nhom]);

        if ((bool) $stmtGV->fetchColumn()) {
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

            $maNhomVaiTro = ((int) $user_join['idLoaiTK'] === 2) ? 'GVHD' : 'THANH_VIEN';
            $stmtVT = $conn->prepare("SELECT id FROM vaitronhom WHERE maVaiTroNhom = ? LIMIT 1");
            $stmtVT->execute([$maNhomVaiTro]);
            $vai_tro = (int) $stmtVT->fetchColumn();
            if ($vai_tro <= 0) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Không tìm thấy vai trò phù hợp'];
            }

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

    if (!$conn instanceof PDO) {
        return ['status' => false, 'message' => 'Kết nối không hợp lệ'];
    }

    $stmtVT = $conn->prepare("SELECT id FROM vaitronhom WHERE maVaiTroNhom = 'TRUONG_NHOM' LIMIT 1");
    $stmtVT->execute();
    $idVaiTroTruongNhom = (int) $stmtVT->fetchColumn();

    if ($idVaiTroTruongNhom > 0 && (int) $tv[0]['idvaitronhom'] === $idVaiTroTruongNhom) {
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

// ==========================================
// HÀM TRUY VẤN DỮ LIỆU NHÓM
// ==========================================

function lay_tat_ca_nhom($conn, int $id_sk): array
{
    if (!$conn instanceof PDO) return [];

    $stmt = $conn->prepare(
        'SELECT
            n.idnhom, n.idSK, n.manhom, n.ngaytao,
            tn.tennhom, tn.mota, tn.soluongtoida, tn.dangtuyen,
            (
                SELECT COUNT(*)
                FROM thanhviennhom tv2
                WHERE tv2.idnhom = n.idnhom AND tv2.trangthai = 1
            ) AS so_thanh_vien,
            (
                SELECT CASE WHEN tk2.idLoaiTK = 3 THEN sv.tenSV
                            WHEN tk2.idLoaiTK = 2 THEN gv.tenGV END
                FROM thanhviennhom tv3
                JOIN vaitronhom vtn ON vtn.id = tv3.idvaitronhom
                LEFT JOIN taikhoan tk2 ON tv3.idtk = tk2.idTK
                LEFT JOIN sinhvien sv  ON tk2.idTK = sv.idTK
                LEFT JOIN giangvien gv ON tk2.idTK = gv.idTK
                WHERE tv3.idnhom = n.idnhom AND vtn.maVaiTroNhom = 'TRUONG_NHOM' AND tv3.trangthai = 1
                LIMIT 1
            ) AS ten_truong_nhom
        FROM nhom n
        LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idnhom
        WHERE n.idSK = :idSK AND n.isActive = 1
        ORDER BY n.ngaytao DESC'
    );

function lay_nhom_cua_toi($conn, int $id_tk, int $id_sk): ?array
{
    if (!$conn instanceof PDO) return null;

    // Tìm nhóm user đang tham gia
    $stmt = $conn->prepare(
        'SELECT
            n.idnhom, n.idSK, n.manhom, n.ngaytao,
            tn.tennhom, tn.mota, tn.soluongtoida, tn.dangtuyen,
            tv.idvaitronhom AS my_role
        FROM thanhviennhom tv
        JOIN nhom n           ON tv.idnhom = n.idnhom
        LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idnhom
        WHERE tv.idtk = :idTK AND n.idSK = :idSK AND tv.trangthai = 1 AND n.isActive = 1
        LIMIT 1'
    );
    $stmt->execute([':idTK' => $id_tk, ':idSK' => $id_sk]);
    $nhom = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nhom) return null;

    $id_nhom = (int) $nhom['idnhom'];

    // Lấy thành viên
    $stmtTV = $conn->prepare(
        'SELECT
            tv.idtk, tv.idvaitronhom, tv.ngaythamgia,
            CASE WHEN tk.idLoaiTK = 3 THEN sv.tenSV
                 WHEN tk.idLoaiTK = 2 THEN gv.tenGV END AS ten,
            CASE WHEN tk.idLoaiTK = 3 THEN sv.MSV END AS msv_ma,
            l.tenLop
        FROM thanhviennhom tv
        LEFT JOIN taikhoan tk  ON tv.idtk = tk.idTK
        LEFT JOIN sinhvien sv  ON tk.idTK = sv.idTK
        LEFT JOIN lop l        ON sv.idLop = l.idLop
        LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
        WHERE tv.idnhom = :idNhom AND tv.trangthai = 1
        ORDER BY tv.idvaitronhom ASC, tv.ngaythamgia ASC'
    );
    $stmtTV->execute([':idNhom' => $id_nhom]);
    $nhom['thanh_vien'] = $stmtTV->fetchAll(PDO::FETCH_ASSOC);

    // Yêu cầu chờ duyệt (chỉ trưởng nhóm mới cần)
    $nhom['yeu_cau_cho'] = [];
    if ((int) $nhom['my_role'] === 1) {
        $stmtYC = $conn->prepare(
            'SELECT
                yc.idYeuCau, yc.idTK, yc.loiNhan, yc.ngayGui,
                CASE WHEN tk.idLoaiTK = 3 THEN sv.tenSV
                     WHEN tk.idLoaiTK = 2 THEN gv.tenGV END AS ten
            FROM yeucau_thamgia yc
            LEFT JOIN taikhoan tk  ON yc.idTK = tk.idTK
            LEFT JOIN sinhvien sv  ON tk.idTK = sv.idTK
            LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
            WHERE yc.idNhom = :idNhom AND yc.ChieuMoi = 1 AND yc.trangThai = 0
            ORDER BY yc.ngayGui DESC'
        );
        $stmtYC->execute([':idNhom' => $id_nhom]);
        $nhom['yeu_cau_cho'] = $stmtYC->fetchAll(PDO::FETCH_ASSOC);
    }

    return $nhom;
}

function lay_loi_moi($conn, int $id_tk, int $id_sk, bool $tat_ca = false): array
{
    if (!$conn instanceof PDO) return [];

    $whereStatus = $tat_ca ? '' : 'AND yc.trangThai = 0';

    $stmt = $conn->prepare(
        "SELECT
            yc.idYeuCau, yc.idNhom, yc.loiNhan, yc.trangThai, yc.ngayGui, yc.ngayPhanHoi,
            tn.tennhom, n.manhom, tn.mota, tn.soluongtoida, tn.dangtuyen,
            (
                SELECT COUNT(*) FROM thanhviennhom tv2
                WHERE tv2.idnhom = n.idnhom AND tv2.trangthai = 1
            ) AS so_thanh_vien,
            (
                SELECT CASE WHEN tk2.idLoaiTK = 3 THEN sv.tenSV
                            WHEN tk2.idLoaiTK = 2 THEN gv.tenGV END
                FROM thanhviennhom tv3
                JOIN vaitronhom vtn ON vtn.id = tv3.idvaitronhom
                LEFT JOIN taikhoan tk2 ON tv3.idtk = tk2.idTK
                LEFT JOIN sinhvien sv  ON tk2.idTK = sv.idTK
                LEFT JOIN giangvien gv ON tk2.idTK = gv.idTK
                WHERE tv3.idnhom = n.idnhom AND vtn.maVaiTroNhom = 'TRUONG_NHOM' AND tv3.trangthai = 1
                LIMIT 1
            ) AS ten_truong_nhom
        FROM yeucau_thamgia yc
        JOIN nhom n               ON yc.idNhom = n.idnhom
        LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idnhom
        WHERE yc.idTK = :idTK AND yc.ChieuMoi = 0 AND n.idSK = :idSK $whereStatus
        ORDER BY yc.ngayGui DESC"
    );
    $stmt->execute([':idTK' => $id_tk, ':idSK' => $id_sk]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function lay_chi_tiet_nhom($conn, int $id_nhom): ?array
{
    // Dùng lại hàm đã có
    $nhom = lay_nhom_theo_id($conn, $id_nhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) return null;

    // Thông tin nhóm từ thongtinnhom — dùng _select_info đã có trong base.php
    $thongTin = _select_info($conn, 'thongtinnhom', [], [
        'WHERE' => ['idnhom', '=', $id_nhom, ''],
        'LIMIT' => [1],
    ]);
    if ($thongTin) $nhom = array_merge($nhom, $thongTin[0]);

    // Thành viên
    if (!$conn instanceof PDO) return $nhom;
    $stmt = $conn->prepare(
        'SELECT
            tv.idtk, tv.idvaitronhom, tv.ngaythamgia,
            CASE WHEN tk.idLoaiTK = 3 THEN sv.tenSV
                 WHEN tk.idLoaiTK = 2 THEN gv.tenGV END AS ten,
            CASE WHEN tk.idLoaiTK = 3 THEN sv.MSV END AS msv_ma
        FROM thanhviennhom tv
        LEFT JOIN taikhoan tk  ON tv.idtk = tk.idTK
        LEFT JOIN sinhvien sv  ON tk.idTK = sv.idTK
        LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
        WHERE tv.idnhom = :idNhom AND tv.trangthai = 1
        ORDER BY tv.idvaitronhom ASC'
    );
    $stmt->execute([':idNhom' => $id_nhom]);
    $nhom['thanh_vien'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $nhom;
}

function nop_bai_nhom($conn, int $id_tk, int $id_nhom, int $id_sk, string $ten_de_tai, string $mo_ta = '', string $link_tl = ''): array
{
    $ten_de_tai = trim($ten_de_tai);
    if ($ten_de_tai === '') return ['status' => false, 'message' => 'Tên đề tài không được để trống'];

    // Dùng _select_info để kiểm tra user có trong nhóm không
    $tv = _select_info($conn, 'thanhviennhom', ['idnhom'], [
        'WHERE' => ['idnhom', '=', $id_nhom, 'AND', 'idtk', '=', $id_tk, 'AND', 'trangthai', '=', 1, ''],
        'LIMIT' => [1],
    ]);
    if (empty($tv)) return ['status' => false, 'message' => 'Bạn không phải thành viên của nhóm này'];

    // Dùng _insert_info để lưu
    $ok = _insert_info(
        $conn, 'sanpham',
        ['idNhom', 'idSK', 'tenSanPham', 'moTa', 'linkTaiLieu', 'TrangThai', 'isActive', 'NgayTao'],
        [$id_nhom, $id_sk, $ten_de_tai, $mo_ta, $link_tl, 'Chờ duyệt', 1, date('Y-m-d H:i:s')]
    );

    return $ok
        ? ['status' => true, 'message' => 'Nộp bài thành công! Sản phẩm đang chờ duyệt.']
        : ['status' => false, 'message' => 'Lỗi khi lưu bài nộp'];
}
/**
 * Kiểm tra user có phải thành viên active của nhóm cụ thể không.
 * Dùng để gate getchitietnhom.
 */
function kiem_tra_thanh_vien_nhom($conn, int $idTK, int $idNhom): bool
{
    if (!$conn instanceof PDO || $idTK <= 0 || $idNhom <= 0) return false;

    $sql = 'SELECT 1
            FROM thanhviennhom
            WHERE idtk   = :idtk
              AND idnhom  = :idnhom
              AND trangthai = 1
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    $stmt->execute([':idtk' => $idTK, ':idnhom' => $idNhom]);
    return (bool) $stmt->fetchColumn();
}
