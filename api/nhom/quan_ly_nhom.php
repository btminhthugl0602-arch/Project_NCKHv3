<?php

require_once __DIR__ . '/../core/base.php';

function lay_nhom_theo_id($conn, int $id_nhom): ?array
{
    $nhom = truy_van_mot_ban_ghi($conn, 'nhom', 'idNhom', $id_nhom);
    return $nhom ?: null;
}

/**
 * Kiểm tra user có phải chủ nhóm không.
 * Nguồn sự thật duy nhất: nhom.idChuNhom
 */
function la_chu_nhom(PDO $conn, int $idTK, int $idNhom): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM nhom WHERE idNhom = :idNhom AND idChuNhom = :idTK AND isActive = 1 LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idTK' => $idTK]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Kiểm tra user có phải trưởng nhóm không.
 * Nguồn sự thật duy nhất: nhom.idTruongNhom
 */
function la_truong_nhom(PDO $conn, int $idTK, int $idNhom): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM nhom WHERE idNhom = :idNhom AND idTruongNhom = :idTK AND isActive = 1 LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idTK' => $idTK]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Kiểm tra user có phải thành viên SV của nhóm không.
 * Check bảng thanhviennhom (chỉ chứa SV đã confirmed).
 */
function la_thanh_vien_sv(PDO $conn, int $idTK, int $idNhom): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM thanhviennhom WHERE idNhom = :idNhom AND idTK = :idTK LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idTK' => $idTK]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Kiểm tra user có phải GVHD của nhóm không.
 */
function la_gvhd_nhom(PDO $conn, int $idTK, int $idNhom): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM nhom_gvhd WHERE idNhom = :idNhom AND idTK = :idTK LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idTK' => $idTK]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Đếm số thành viên SV trong nhóm.
 */
function so_thanh_vien_sv(PDO $conn, int $idNhom): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM thanhviennhom WHERE idNhom = :idNhom');
    $stmt->execute([':idNhom' => $idNhom]);
    return (int) $stmt->fetchColumn();
}

// Backward-compatible alias cho code cũ chưa migrate
function so_thanh_vien_hien_tai($conn, int $id_nhom): int
{
    return so_thanh_vien_sv($conn, $id_nhom);
}

/**
 * Đếm số GVHD trong nhóm.
 */
function so_gvhd_nhom(PDO $conn, int $idNhom): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM nhom_gvhd WHERE idNhom = :idNhom');
    $stmt->execute([':idNhom' => $idNhom]);
    return (int) $stmt->fetchColumn();
}

/**
 * Kiểm tra SV đã có nhóm trong sự kiện chưa.
 * Check cả thanhviennhom, idChuNhom, idTruongNhom.
 */
function kiem_tra_sv_co_nhom(PDO $conn, int $idTK, int $idSK): bool
{
    // Check trong thanhviennhom
    $stmt = $conn->prepare(
        'SELECT 1 FROM thanhviennhom tv
         JOIN nhom n ON n.idNhom = tv.idNhom
         WHERE tv.idTK = :idTK AND n.idSK = :idSK AND n.isActive = 1
         LIMIT 1'
    );
    $stmt->execute([':idTK' => $idTK, ':idSK' => $idSK]);
    if ($stmt->fetchColumn()) return true;

    // Check là Chủ nhóm hoặc Trưởng nhóm
    $stmt2 = $conn->prepare(
        'SELECT 1 FROM nhom
         WHERE idSK = :idSK AND isActive = 1
           AND (idChuNhom = :idTK1 OR idTruongNhom = :idTK2)
         LIMIT 1'
    );
    $stmt2->execute([':idSK' => $idSK, ':idTK1' => $idTK, ':idTK2' => $idTK]);
    return (bool) $stmt2->fetchColumn();
}

/**
 * Đếm số nhóm GV đang hướng dẫn trong SK.
 * Bao gồm nhóm GV là GVHD (nhom_gvhd) + nhóm GV là Chủ nhóm.
 */
function so_nhom_gv_huong_dan(PDO $conn, int $idTK, int $idSK): int
{
    // Đếm nhóm GV là GVHD
    $stmt1 = $conn->prepare(
        'SELECT COUNT(*) FROM nhom_gvhd g
         JOIN nhom n ON n.idNhom = g.idNhom
         WHERE g.idTK = :idTK AND n.idSK = :idSK AND n.isActive = 1'
    );
    $stmt1->execute([':idTK' => $idTK, ':idSK' => $idSK]);
    $count = (int) $stmt1->fetchColumn();

    // Cộng thêm nhóm GV là Chủ nhóm (mà chưa tính ở trên)
    $stmt2 = $conn->prepare(
        'SELECT COUNT(*) FROM nhom n
         WHERE n.idChuNhom = :idTK AND n.idSK = :idSK AND n.isActive = 1
           AND n.idNhom NOT IN (
               SELECT g2.idNhom FROM nhom_gvhd g2 WHERE g2.idTK = :idTK2
           )'
    );
    $stmt2->execute([':idTK' => $idTK, ':idSK' => $idSK, ':idTK2' => $idTK]);
    $count += (int) $stmt2->fetchColumn();

    return $count;
}

/**
 * Tạo nhóm mới trong sự kiện.
 * SV tạo → idChuNhom = idTruongNhom = idTK, INSERT thanhviennhom
 * GV tạo → idChuNhom = idTK, idTruongNhom = NULL, INSERT nhom_gvhd
 */
function tao_nhom_moi(PDO $conn, int $idTK, int $idSK, string $tenNhom, string $moTa): array
{
    $tenNhom = trim($tenNhom);
    $moTa = trim($moTa);

    // 1. Validate input
    if ($idTK <= 0 || $idSK <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }
    $tenNhomLen = mb_strlen($tenNhom);
    if ($tenNhomLen < 3 || $tenNhomLen > 100) {
        return ['status' => false, 'message' => 'Tên nhóm phải từ 3 đến 100 ký tự'];
    }

    // 2. Lấy sự kiện, check active
    $sukien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $idSK);
    if (!$sukien || (int)$sukien['isActive'] !== 1 || (int)$sukien['isDeleted'] !== 0) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại hoặc đã ngừng hoạt động'];
    }

    // 3. Lấy tài khoản, xác định loại TK
    $taiKhoan = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    if (!$taiKhoan) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }
    $loaiTK = (int) $taiKhoan['idLoaiTK'];

    // 4. Chỉ GV (2) hoặc SV (3) mới được tạo nhóm
    if ($loaiTK !== 2 && $loaiTK !== 3) {
        return ['status' => false, 'message' => 'Loại tài khoản không được phép tạo nhóm'];
    }

    // 5. Nếu GV: check choPhepGVTaoNhom
    if ($loaiTK === 2) {
        if ((int)$sukien['choPhepGVTaoNhom'] !== 1) {
            return ['status' => false, 'message' => 'Sự kiện không cho phép GV tạo nhóm'];
        }
        // 6. Check giới hạn số nhóm GV hướng dẫn
        $soNhomToiDaGVHD = $sukien['soNhomToiDaGVHD'];
        if ($soNhomToiDaGVHD !== null && so_nhom_gv_huong_dan($conn, $idTK, $idSK) >= (int)$soNhomToiDaGVHD) {
            return ['status' => false, 'message' => "Bạn đã đạt giới hạn hướng dẫn {$soNhomToiDaGVHD} nhóm trong sự kiện này"];
        }
    }

    // 7. Nếu SV: check đã có nhóm chưa
    if ($loaiTK === 3) {
        if (kiem_tra_sv_co_nhom($conn, $idTK, $idSK)) {
            return ['status' => false, 'message' => 'Bạn đã tham gia một nhóm trong sự kiện này rồi'];
        }
    }

    // 8. Check tên nhóm unique trong SK
    $stmtUnique = $conn->prepare(
        'SELECT 1 FROM nhom n
         JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         WHERE tn.tennhom = :tenNhom AND n.idSK = :idSK AND n.isActive = 1
         LIMIT 1'
    );
    $stmtUnique->execute([':tenNhom' => $tenNhom, ':idSK' => $idSK]);
    if ($stmtUnique->fetchColumn()) {
        return ['status' => false, 'message' => 'Tên nhóm đã tồn tại trong sự kiện này'];
    }

    // 9. Generate maNhom
    $maNhom = 'GRP_' . $idSK . '_' . substr(uniqid(), -8);

    // 10. Transaction
    try {
        $conn->beginTransaction();

        // INSERT nhom
        if ($loaiTK === 3) {
            // SV: idChuNhom = idTruongNhom = idTK
            $okNhom = _insert_info(
                $conn,
                'nhom',
                ['idSK', 'idChuNhom', 'idTruongNhom', 'maNhom', 'ngayTao', 'isActive'],
                [$idSK, $idTK, $idTK, $maNhom, date('Y-m-d H:i:s'), 1]
            );
        } else {
            // GV: idChuNhom = idTK, idTruongNhom = NULL
            $okNhom = _insert_info(
                $conn,
                'nhom',
                ['idSK', 'idChuNhom', 'maNhom', 'ngayTao', 'isActive'],
                [$idSK, $idTK, $maNhom, date('Y-m-d H:i:s'), 1]
            );
        }

        if (!$okNhom) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi tạo nhóm'];
        }

        $idNhomMoi = (int) $conn->lastInsertId();

        // INSERT thongtinnhom
        $okThongTin = _insert_info(
            $conn,
            'thongtinnhom',
            ['idnhom', 'tennhom', 'mota', 'dangtuyen'],
            [$idNhomMoi, $tenNhom, $moTa, 1]
        );
        if (!$okThongTin) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi lưu thông tin nhóm'];
        }

        // INSERT thành viên tương ứng
        if ($loaiTK === 3) {
            // SV → INSERT thanhviennhom
            $okMember = _insert_info(
                $conn,
                'thanhviennhom',
                ['idNhom', 'idTK', 'ngayThamGia'],
                [$idNhomMoi, $idTK, date('Y-m-d H:i:s')]
            );
        } else {
            // GV → INSERT nhom_gvhd
            $okMember = _insert_info(
                $conn,
                'nhom_gvhd',
                ['idNhom', 'idTK', 'ngayThamGia'],
                [$idNhomMoi, $idTK, date('Y-m-d H:i:s')]
            );
        }

        if (!$okMember) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi thêm người tạo nhóm'];
        }

        $conn->commit();
        return ['status' => true, 'idNhom' => $idNhomMoi, 'maNhom' => $maNhom];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo nhóm'];
    }
}

/**
 * Gửi yêu cầu tham gia nhóm hoặc mời thành viên.
 * @param string $loaiYeuCau 'SV' | 'GVHD'
 * @param int $chieuMoi 0=nhóm mời, 1=tự xin vào
 */
function gui_yeu_cau_nhom(PDO $conn, int $idTKThucHien, int $idNhom, int $idTKDoiPhuong, int $chieuMoi, string $loaiYeuCau, string $loiNhan = ''): array
{
    // 1. Validate loaiYeuCau, chieuMoi
    if (!in_array($loaiYeuCau, ['SV', 'GVHD'], true)) {
        return ['status' => false, 'message' => 'loaiYeuCau không hợp lệ. Chỉ chấp nhận: SV, GVHD'];
    }
    if (!in_array($chieuMoi, [0, 1], true)) {
        return ['status' => false, 'message' => 'chieuMoi phải là 0 hoặc 1'];
    }

    // 2. Lấy nhóm, check active
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    // 3. Lấy tài khoản đối phương
    $taikhoanDoiPhuong = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTKDoiPhuong);
    if (!$taikhoanDoiPhuong || (int) $taikhoanDoiPhuong['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Tài khoản đối phương không hợp lệ'];
    }

    // 4. Lấy sự kiện và thông tin nhóm
    $idSK = (int) $nhom['idSK'];
    $sukien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $idSK);
    if (!$sukien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }
    $thongtinnhom = _select_info($conn, 'thongtinnhom', [], [
        'WHERE' => ['idnhom', '=', $idNhom, ''],
        'LIMIT' => [1],
    ]);
    $dangTuyen = !empty($thongtinnhom) ? (int) $thongtinnhom[0]['dangtuyen'] : 0;

    // 5. Validate quyền gửi
    if ($chieuMoi === 0) {
        // Nhóm mời: chỉ Chủ nhóm
        if (!la_chu_nhom($conn, $idTKThucHien, $idNhom)) {
            return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được mời thành viên'];
        }
    } else {
        // Tự xin vào: người gửi phải chính là đối phương
        if ($idTKThucHien !== $idTKDoiPhuong) {
            return ['status' => false, 'message' => 'Bạn chỉ có thể gửi yêu cầu cho chính mình'];
        }
    }

    // 6. Validate đối tượng
    $loaiTKDoiPhuong = (int) $taikhoanDoiPhuong['idLoaiTK'];
    if ($loaiYeuCau === 'SV' && $loaiTKDoiPhuong !== 3) {
        return ['status' => false, 'message' => 'Loại yêu cầu SV nhưng đối phương không phải sinh viên'];
    }
    if ($loaiYeuCau === 'GVHD' && $loaiTKDoiPhuong !== 2) {
        return ['status' => false, 'message' => 'Loại yêu cầu GVHD nhưng đối phương không phải giảng viên'];
    }

    // 7. Validate trạng thái hiện tại: chưa là thành viên
    if ($loaiYeuCau === 'SV') {
        if (la_thanh_vien_sv($conn, $idTKDoiPhuong, $idNhom)) {
            return ['status' => false, 'message' => 'Người này đã là thành viên của nhóm'];
        }
    } else {
        if (la_gvhd_nhom($conn, $idTKDoiPhuong, $idNhom)) {
            return ['status' => false, 'message' => 'GV này đã là GVHD của nhóm'];
        }
    }

    // 8. Check chưa có yêu cầu pending
    $stmt = $conn->prepare(
        'SELECT 1 FROM yeucau_thamgia WHERE idNhom = :idNhom AND idTK = :idTK AND trangThai = 0 LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idTK' => $idTKDoiPhuong]);
    if ($stmt->fetchColumn()) {
        return ['status' => false, 'message' => 'Đang có yêu cầu chờ xử lý'];
    }

    // 9. Validate config SK
    if ($loaiYeuCau === 'SV') {
        // Chỉ check dangTuyen khi SV tự xin vào, không áp dụng khi chủ nhóm chủ động mời
        if ($chieuMoi === 1 && $dangTuyen !== 1) {
            return ['status' => false, 'message' => 'Nhóm hiện không mở tuyển thành viên'];
        }
        if (so_thanh_vien_sv($conn, $idNhom) >= (int) $sukien['soThanhVienToiDa']) {
            return ['status' => false, 'message' => 'Nhóm đã đủ số lượng thành viên tối đa'];
        }
        if (kiem_tra_sv_co_nhom($conn, $idTKDoiPhuong, $idSK)) {
            return ['status' => false, 'message' => 'Sinh viên đã thuộc nhóm khác trong sự kiện'];
        }
    } else {
        // GVHD
        $soGVHDToiDa = $sukien['soGVHDToiDa'];
        if ($soGVHDToiDa !== null && so_gvhd_nhom($conn, $idNhom) >= (int) $soGVHDToiDa) {
            return ['status' => false, 'message' => 'Nhóm đã đủ số lượng GVHD tối đa'];
        }
        $soNhomToiDaGVHD = $sukien['soNhomToiDaGVHD'];
        if ($soNhomToiDaGVHD !== null && so_nhom_gv_huong_dan($conn, $idTKDoiPhuong, $idSK) >= (int) $soNhomToiDaGVHD) {
            return ['status' => false, 'message' => 'GV đã đạt giới hạn hướng dẫn nhóm trong sự kiện này'];
        }
    }

    // 10. Insert yêu cầu
    $res = _insert_info(
        $conn,
        'yeucau_thamgia',
        ['idNhom', 'idTK', 'ChieuMoi', 'loaiYeuCau', 'loiNhan', 'trangThai', 'ngayGui'],
        [$idNhom, $idTKDoiPhuong, $chieuMoi, $loaiYeuCau, trim($loiNhan), 0, date('Y-m-d H:i:s')]
    );

    return $res
        ? ['status' => true, 'message' => 'Gửi yêu cầu thành công']
        : ['status' => false, 'message' => 'Lỗi hệ thống khi gửi yêu cầu'];
}

/**
 * Duyệt yêu cầu tham gia nhóm.
 * $trangThaiMoi: 1=chấp nhận, 2=từ chối
 */
function duyet_yeu_cau_nhom(PDO $conn, int $idNguoiDuyet, int $idYeuCau, int $trangThaiMoi): array
{
    if (!in_array($trangThaiMoi, [1, 2], true)) {
        return ['status' => false, 'message' => 'Trạng thái duyệt không hợp lệ'];
    }

    // 1. Lấy yêu cầu, check chưa xử lý
    $yc = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $idYeuCau);
    if (!$yc) {
        return ['status' => false, 'message' => 'Yêu cầu không tồn tại'];
    }
    if ((int) $yc['trangThai'] !== 0) {
        return ['status' => false, 'message' => 'Yêu cầu này đã được xử lý'];
    }

    $idNhom = (int) $yc['idNhom'];
    $idTKYeuCau = (int) $yc['idTK'];
    $chieuMoi = (int) $yc['ChieuMoi'];
    $loaiYeuCau = $yc['loaiYeuCau'] ?? 'SV';

    // 2. Lấy nhóm, sự kiện
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }
    $idSK = (int) $nhom['idSK'];
    $sukien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $idSK);
    if (!$sukien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // 3. Xác định quyền duyệt
    if ($chieuMoi === 0) {
        // Nhóm mời người: chỉ người được mời mới phản hồi
        if ($idNguoiDuyet !== $idTKYeuCau) {
            return ['status' => false, 'message' => 'Bạn không phải người được mời'];
        }
    } else {
        // Người xin vào: chỉ Chủ nhóm duyệt
        if (!la_chu_nhom($conn, $idNguoiDuyet, $idNhom)) {
            return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được duyệt yêu cầu này'];
        }
    }

    // Từ chối: chỉ cần update trạng thái
    if ($trangThaiMoi === 2) {
        $ok = _update_info(
            $conn,
            'yeucau_thamgia',
            ['trangThai', 'ngayPhanHoi'],
            [2, date('Y-m-d H:i:s')],
            ['idYeuCau' => ['=', $idYeuCau, '']]
        );
        return $ok
            ? ['status' => true, 'message' => 'Đã từ chối yêu cầu']
            : ['status' => false, 'message' => 'Lỗi khi cập nhật yêu cầu'];
    }

    // Chấp nhận: cần transaction
    try {
        $conn->beginTransaction();

        // Update trạng thái yêu cầu
        $okUpdate = _update_info(
            $conn,
            'yeucau_thamgia',
            ['trangThai', 'ngayPhanHoi'],
            [1, date('Y-m-d H:i:s')],
            ['idYeuCau' => ['=', $idYeuCau, '']]
        );
        if (!$okUpdate) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không cập nhật được yêu cầu'];
        }

        if ($loaiYeuCau === 'SV') {
            // Re-check config SK
            $thongtinnhom = _select_info($conn, 'thongtinnhom', [], [
                'WHERE' => ['idnhom', '=', $idNhom, ''],
                'LIMIT' => [1],
            ]);
            $dangTuyen = !empty($thongtinnhom) ? (int) $thongtinnhom[0]['dangtuyen'] : 0;
            if ($dangTuyen !== 1) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Nhóm hiện không mở tuyển thành viên'];
            }
            if (so_thanh_vien_sv($conn, $idNhom) >= (int) $sukien['soThanhVienToiDa']) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Nhóm đã đủ số lượng thành viên tối đa'];
            }
            if (kiem_tra_sv_co_nhom($conn, $idTKYeuCau, $idSK)) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Sinh viên đã thuộc nhóm khác trong sự kiện'];
            }

            // INSERT thanhviennhom
            $okMember = _insert_info(
                $conn,
                'thanhviennhom',
                ['idNhom', 'idTK', 'ngayThamGia'],
                [$idNhom, $idTKYeuCau, date('Y-m-d H:i:s')]
            );
            if (!$okMember) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi thêm thành viên'];
            }

            // Cleanup: hủy tất cả pending khác của SV này trong cùng SK
            $stmtCleanup = $conn->prepare(
                'UPDATE yeucau_thamgia yc
                 JOIN nhom n ON n.idNhom = yc.idNhom
                 SET yc.trangThai = 2, yc.ngayPhanHoi = :now
                 WHERE yc.idTK = :idTK AND yc.trangThai = 0 AND yc.idYeuCau != :idYeuCau
                   AND n.idSK = :idSK'
            );
            $stmtCleanup->execute([
                ':now' => date('Y-m-d H:i:s'),
                ':idTK' => $idTKYeuCau,
                ':idYeuCau' => $idYeuCau,
                ':idSK' => $idSK,
            ]);
        } elseif ($loaiYeuCau === 'GVHD') {
            // Re-check config SK
            $soGVHDToiDa = $sukien['soGVHDToiDa'];
            if ($soGVHDToiDa !== null && so_gvhd_nhom($conn, $idNhom) >= (int) $soGVHDToiDa) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Nhóm đã đủ số lượng GVHD tối đa'];
            }
            $soNhomToiDaGVHD = $sukien['soNhomToiDaGVHD'];
            if ($soNhomToiDaGVHD !== null && so_nhom_gv_huong_dan($conn, $idTKYeuCau, $idSK) >= (int) $soNhomToiDaGVHD) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'GV đã đạt giới hạn hướng dẫn nhóm trong sự kiện'];
            }

            // INSERT nhom_gvhd
            $okGVHD = _insert_info(
                $conn,
                'nhom_gvhd',
                ['idNhom', 'idTK', 'ngayThamGia'],
                [$idNhom, $idTKYeuCau, date('Y-m-d H:i:s')]
            );
            if (!$okGVHD) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi thêm GVHD vào nhóm'];
            }

            // INSERT taikhoan_vaitro_sukien (nếu chưa có)
            $stmtVaiTro = $conn->prepare("SELECT idVaiTro FROM vaitro WHERE maVaiTro = 'GV_HUONG_DAN' LIMIT 1");
            $stmtVaiTro->execute();
            $idVaiTroGVHD = (int) $stmtVaiTro->fetchColumn();

            if ($idVaiTroGVHD > 0) {
                // Check chưa có bản ghi active
                $stmtCheckVT = $conn->prepare(
                    'SELECT 1 FROM taikhoan_vaitro_sukien
                     WHERE idTK = :idTK AND idSK = :idSK AND idVaiTro = :idVaiTro AND isActive = 1
                     LIMIT 1'
                );
                $stmtCheckVT->execute([':idTK' => $idTKYeuCau, ':idSK' => $idSK, ':idVaiTro' => $idVaiTroGVHD]);
                if (!$stmtCheckVT->fetchColumn()) {
                    _insert_info(
                        $conn,
                        'taikhoan_vaitro_sukien',
                        ['idTK', 'idSK', 'idVaiTro', 'nguonTao', 'idNguoiCap', 'isActive'],
                        [$idTKYeuCau, $idSK, $idVaiTroGVHD, 'QUA_NHOM', $idNguoiDuyet, 1]
                    );
                }
            }
        }

        $conn->commit();
        return ['status' => true, 'message' => 'Đã chấp nhận yêu cầu'];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi duyệt yêu cầu'];
    }
}

/**
 * Rời nhóm hoặc kick thành viên.
 * Tự rời = idNguoiThucHien === idTKBiXoa
 * Kick = idNguoiThucHien !== idTKBiXoa (phải là Chủ nhóm)
 */
function roi_nhom(PDO $conn, int $idNguoiThucHien, int $idNhom, int $idTKBiXoa): array
{
    // 1. Lấy nhóm, check active
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    $idSK = (int) $nhom['idSK'];
    $idChuNhom = (int) $nhom['idChuNhom'];
    $idTruongNhom = $nhom['idTruongNhom'] !== null ? (int) $nhom['idTruongNhom'] : null;

    // 2. Xác định loại tài khoản bị xóa
    $tkBiXoa = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTKBiXoa);
    if (!$tkBiXoa) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }
    $loaiTKBiXoa = (int) $tkBiXoa['idLoaiTK'];
    $tuRoi = ($idNguoiThucHien === $idTKBiXoa);

    // 3. Kiểm tra quyền
    if (!$tuRoi && !la_chu_nhom($conn, $idNguoiThucHien, $idNhom)) {
        return ['status' => false, 'message' => 'Bạn không có quyền loại thành viên khỏi nhóm'];
    }

    // 4. Chặn các trường hợp đặc biệt
    if ($idTKBiXoa === $idChuNhom) {
        return ['status' => false, 'message' => 'Chủ nhóm không thể rời nhóm. Hãy nhượng quyền trước'];
    }

    if ($idTruongNhom !== null && $idTKBiXoa === $idTruongNhom) {
        if ($tuRoi) {
            return ['status' => false, 'message' => 'Trưởng nhóm không thể tự rời. Chủ nhóm cần đổi Trưởng nhóm trước'];
        }
        // Chủ nhóm kick Trưởng nhóm → cho phép, sẽ SET idTruongNhom=NULL
    }

    // 5. Transaction
    try {
        $conn->beginTransaction();

        if ($loaiTKBiXoa === 3) {
            // SV: check có trong thanhviennhom
            if (!la_thanh_vien_sv($conn, $idTKBiXoa, $idNhom)) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Sinh viên không phải thành viên của nhóm'];
            }

            // DELETE FROM thanhviennhom
            $okDel = _delete_info($conn, 'thanhviennhom', [
                'idNhom' => ['=', $idNhom, 'AND'],
                'idTK' => ['=', $idTKBiXoa, ''],
            ]);
            if (!$okDel) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi khi xóa thành viên khỏi nhóm'];
            }

            // Nếu bị kick là Trưởng nhóm → set NULL
            if ($idTruongNhom !== null && $idTKBiXoa === $idTruongNhom) {
                $stmtTN = $conn->prepare('UPDATE nhom SET idTruongNhom = NULL WHERE idNhom = :idNhom');
                $stmtTN->execute([':idNhom' => $idNhom]);
            }
        } elseif ($loaiTKBiXoa === 2) {
            // GV: check có trong nhom_gvhd
            if (!la_gvhd_nhom($conn, $idTKBiXoa, $idNhom)) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'GV không phải GVHD của nhóm'];
            }

            // DELETE FROM nhom_gvhd
            $okDel = _delete_info($conn, 'nhom_gvhd', [
                'idNhom' => ['=', $idNhom, 'AND'],
                'idTK' => ['=', $idTKBiXoa, ''],
            ]);
            if (!$okDel) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi khi xóa GVHD khỏi nhóm'];
            }

            // Deactivate taikhoan_vaitro_sukien nguonTao=QUA_NHOM
            $stmtDeact = $conn->prepare(
                'UPDATE taikhoan_vaitro_sukien SET isActive = 0
                 WHERE idTK = :idTK AND idSK = :idSK AND nguonTao = :nguonTao AND isActive = 1'
            );
            $stmtDeact->execute([':idTK' => $idTKBiXoa, ':idSK' => $idSK, ':nguonTao' => 'QUA_NHOM']);
        } else {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Loại tài khoản không hợp lệ'];
        }

        // Cleanup pending requests
        $stmtClean = $conn->prepare(
            'UPDATE yeucau_thamgia SET trangThai = 2
             WHERE idNhom = :idNhom AND idTK = :idTK AND trangThai = 0'
        );
        $stmtClean->execute([':idNhom' => $idNhom, ':idTK' => $idTKBiXoa]);

        $conn->commit();
        return ['status' => true, 'message' => $tuRoi ? 'Đã rời nhóm thành công' : 'Đã loại thành viên khỏi nhóm'];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi xử lý rời nhóm'];
    }
}

// ==========================================
// HÀM TÌM KIẾM
// ==========================================

function tim_kiem_giang_vien(PDO $conn, string $keyword, int $idSK): array
{
    $keyword = trim($keyword);

    if ($keyword === '') {
        $sql = 'SELECT
                    tk.idTK, gv.tenGV, gv.idKhoa,
                    (SELECT 1 FROM taikhoan_vaitro_sukien tvs
                     WHERE tvs.idTK = tk.idTK AND tvs.idSK = :idSK AND tvs.isActive = 1
                     LIMIT 1) AS da_dang_ky_sk,
                    (SELECT COUNT(*) FROM nhom_gvhd g2
                     JOIN nhom n2 ON g2.idNhom = n2.idNhom
                     WHERE g2.idTK = tk.idTK AND n2.idSK = :idSK2 AND n2.isActive = 1
                    ) AS so_nhom_dang_huong_dan
                FROM taikhoan tk
                JOIN giangvien gv ON tk.idTK = gv.idTK
                WHERE tk.idLoaiTK = 2 AND tk.isActive = 1
                ORDER BY gv.tenGV ASC
                LIMIT 20';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK]);
    } else {
        $kw = '%' . $keyword . '%';
        $sql = 'SELECT
                    tk.idTK, gv.tenGV, gv.idKhoa,
                    (SELECT 1 FROM taikhoan_vaitro_sukien tvs
                     WHERE tvs.idTK = tk.idTK AND tvs.idSK = :idSK AND tvs.isActive = 1
                     LIMIT 1) AS da_dang_ky_sk,
                    (SELECT COUNT(*) FROM nhom_gvhd g2
                     JOIN nhom n2 ON g2.idNhom = n2.idNhom
                     WHERE g2.idTK = tk.idTK AND n2.idSK = :idSK2 AND n2.isActive = 1
                    ) AS so_nhom_dang_huong_dan
                FROM taikhoan tk
                JOIN giangvien gv ON tk.idTK = gv.idTK
                WHERE tk.idLoaiTK = 2 AND tk.isActive = 1
                  AND gv.tenGV LIKE :kw
                ORDER BY gv.tenGV ASC
                LIMIT 10';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK, ':kw' => $kw]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tim_kiem_sinh_vien(PDO $conn, string $keyword, int $idSK): array
{
    $keyword = trim($keyword);

    if ($keyword === '') {
        $sql = 'SELECT
                    tk.idTK, sv.tenSV, sv.MSV, l.tenLop,
                    (SELECT 1 FROM taikhoan_vaitro_sukien tvs
                     WHERE tvs.idTK = tk.idTK AND tvs.idSK = :idSK AND tvs.isActive = 1
                     LIMIT 1) AS da_dang_ky_sk,
                    (SELECT 1 FROM thanhviennhom tv2
                     JOIN nhom n2 ON tv2.idNhom = n2.idNhom
                     WHERE tv2.idTK = tk.idTK AND n2.idSK = :idSK2 AND n2.isActive = 1
                     LIMIT 1) AS da_co_nhom
                FROM taikhoan tk
                JOIN sinhvien sv ON tk.idTK = sv.idTK
                LEFT JOIN lop l ON sv.idLop = l.idLop
                WHERE tk.idLoaiTK = 3 AND tk.isActive = 1
                ORDER BY sv.tenSV ASC
                LIMIT 20';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK]);
    } else {
        $kw = '%' . $keyword . '%';
        $sql = 'SELECT
                    tk.idTK, sv.tenSV, sv.MSV, l.tenLop,
                    (SELECT 1 FROM taikhoan_vaitro_sukien tvs
                     WHERE tvs.idTK = tk.idTK AND tvs.idSK = :idSK AND tvs.isActive = 1
                     LIMIT 1) AS da_dang_ky_sk,
                    (SELECT 1 FROM thanhviennhom tv2
                     JOIN nhom n2 ON tv2.idNhom = n2.idNhom
                     WHERE tv2.idTK = tk.idTK AND n2.idSK = :idSK2 AND n2.isActive = 1
                     LIMIT 1) AS da_co_nhom
                FROM taikhoan tk
                JOIN sinhvien sv ON tk.idTK = sv.idTK
                LEFT JOIN lop l ON sv.idLop = l.idLop
                WHERE tk.idLoaiTK = 3 AND tk.isActive = 1
                  AND (sv.tenSV LIKE :kw OR sv.MSV LIKE :kw2)
                ORDER BY sv.tenSV ASC
                LIMIT 10';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK, ':kw' => $kw, ':kw2' => $kw]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================
// HÀM TRUY VẤN DỮ LIỆU NHÓM
// ==========================================

/**
 * Kiểm tra user (SV hoặc GV) có nhóm trong sự kiện không.
 */
function kiem_tra_user_co_nhom(PDO $conn, int $idTK, int $idSK): bool
{
    // Lấy loại tài khoản
    $tk = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    if (!$tk) return false;
    $loaiTK = (int) $tk['idLoaiTK'];

    if ($loaiTK === 3) {
        // SV: check thanhviennhom hoặc idChuNhom/idTruongNhom
        return kiem_tra_sv_co_nhom($conn, $idTK, $idSK);
    } elseif ($loaiTK === 2) {
        // GV: check nhom_gvhd hoặc idChuNhom
        $stmt = $conn->prepare(
            'SELECT 1 FROM nhom_gvhd g
             JOIN nhom n ON g.idNhom = n.idNhom
             WHERE g.idTK = :idTK AND n.idSK = :idSK AND n.isActive = 1
             LIMIT 1'
        );
        $stmt->execute([':idTK' => $idTK, ':idSK' => $idSK]);
        if ($stmt->fetchColumn()) return true;

        $stmt2 = $conn->prepare(
            'SELECT 1 FROM nhom WHERE idChuNhom = :idTK AND idSK = :idSK AND isActive = 1 LIMIT 1'
        );
        $stmt2->execute([':idTK' => $idTK, ':idSK' => $idSK]);
        return (bool) $stmt2->fetchColumn();
    }

    return false;
}

/**
 * Lấy tất cả nhóm trong sự kiện. Dùng JOIN, không correlated subquery.
 */
function lay_tat_ca_nhom(PDO $conn, int $idSK): array
{
    $stmt = $conn->prepare(
        "SELECT
            n.idNhom as idnhom,
            n.maNhom as manhom,
            n.ngayTao,
            n.isActive,
            n.idChuNhom,
            n.idTruongNhom,
            tn.tennhom,
            tn.mota,
            tn.dangtuyen,
            sk.soThanhVienToiDa as soluongtoida,
            CASE WHEN tk_chu.idLoaiTK = 3 THEN sv_chu.tenSV
                 WHEN tk_chu.idLoaiTK = 2 THEN gv_chu.tenGV END AS ten_chu_nhom,
            CASE WHEN tk_truong.idLoaiTK = 3 THEN sv_truong.tenSV END AS ten_truong_nhom,
            (SELECT COUNT(*) FROM thanhviennhom WHERE idNhom = n.idNhom) AS so_thanh_vien,
            (SELECT COUNT(*) FROM nhom_gvhd WHERE idNhom = n.idNhom) AS so_gvhd
        FROM nhom n
        LEFT JOIN thongtinnhom tn        ON tn.idnhom = n.idNhom
        LEFT JOIN sukien sk              ON sk.idSK = n.idSK
        LEFT JOIN taikhoan tk_chu        ON tk_chu.idTK = n.idChuNhom
        LEFT JOIN sinhvien sv_chu        ON sv_chu.idTK = n.idChuNhom
        LEFT JOIN giangvien gv_chu       ON gv_chu.idTK = n.idChuNhom
        LEFT JOIN taikhoan tk_truong     ON tk_truong.idTK = n.idTruongNhom
        LEFT JOIN sinhvien sv_truong     ON sv_truong.idTK = n.idTruongNhom
        WHERE n.idSK = :idSK AND n.isActive = 1
        ORDER BY n.ngayTao DESC"
    );
    $stmt->execute([':idSK' => $idSK]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy nhóm của user (SV hoặc GV) trong sự kiện.
 * Dùng UNION để handle cả SV lẫn GV.
 */
function lay_nhom_cua_toi(PDO $conn, int $idTK, int $idSK): ?array
{
    // Tìm idNhom user liên quan
    $stmtFind = $conn->prepare(
        'SELECT n.idNhom FROM nhom n
         JOIN thanhviennhom tv ON tv.idNhom = n.idNhom AND tv.idTK = :idTK1
         WHERE n.idSK = :idSK1 AND n.isActive = 1
         UNION
         SELECT n.idNhom FROM nhom n
         JOIN nhom_gvhd g ON g.idNhom = n.idNhom AND g.idTK = :idTK2
         WHERE n.idSK = :idSK2 AND n.isActive = 1
         UNION
         SELECT idNhom FROM nhom
         WHERE idChuNhom = :idTK3 AND idSK = :idSK3 AND isActive = 1
         LIMIT 1'
    );
    $stmtFind->execute([
        ':idTK1' => $idTK,
        ':idSK1' => $idSK,
        ':idTK2' => $idTK,
        ':idSK2' => $idSK,
        ':idTK3' => $idTK,
        ':idSK3' => $idSK,
    ]);
    $row = $stmtFind->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    $idNhom = (int) $row['idNhom'];

    // Lấy thông tin nhóm đầy đủ
    $stmtNhom = $conn->prepare(
        'SELECT n.idNhom, n.maNhom, n.ngayTao, n.isActive,
                n.idChuNhom, n.idTruongNhom,
                tn.tennhom, tn.mota, tn.dangtuyen,
                sk.soThanhVienToiDa,
                sk.yeuCauCoGVHD,
                sk.soGVHDToiDa
         FROM nhom n
         LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         LEFT JOIN sukien sk ON sk.idSK = n.idSK
         WHERE n.idNhom = :idNhom'
    );
    $stmtNhom->execute([':idNhom' => $idNhom]);
    $nhom = $stmtNhom->fetch(PDO::FETCH_ASSOC);
    if (!$nhom) return null;

    $nhom['is_chu_nhom'] = ($idTK === (int) $nhom['idChuNhom']);
    $nhom['is_truong_nhom'] = ($nhom['idTruongNhom'] !== null && $idTK === (int) $nhom['idTruongNhom']);
    $nhom['so_thanh_vien_toi_da'] = $nhom['soThanhVienToiDa'] !== null ? (int) $nhom['soThanhVienToiDa'] : null;
    $nhom['yeu_cau_co_gvhd'] = isset($nhom['yeuCauCoGVHD']) ? (int) $nhom['yeuCauCoGVHD'] : 0;
    $nhom['so_gvhd_toi_da'] = isset($nhom['soGVHDToiDa']) ? (int) $nhom['soGVHDToiDa'] : null;
    unset($nhom['soThanhVienToiDa'], $nhom['yeuCauCoGVHD'], $nhom['soGVHDToiDa']);

    // Lấy thành viên SV
    $stmtSV = $conn->prepare(
        'SELECT tv.idTK, sv.tenSV AS ten, sv.MSV, l.tenLop, tv.ngayThamGia
         FROM thanhviennhom tv
         JOIN sinhvien sv ON sv.idTK = tv.idTK
         LEFT JOIN lop l ON sv.idLop = l.idLop
         WHERE tv.idNhom = :idNhom
         ORDER BY tv.ngayThamGia ASC'
    );
    $stmtSV->execute([':idNhom' => $idNhom]);
    $nhom['thanh_vien_sv'] = $stmtSV->fetchAll(PDO::FETCH_ASSOC);

    // Lấy GVHD
    $stmtGV = $conn->prepare(
        'SELECT g.idTK, gv.tenGV AS ten, g.ngayThamGia
         FROM nhom_gvhd g
         JOIN giangvien gv ON gv.idTK = g.idTK
         WHERE g.idNhom = :idNhom
         ORDER BY g.ngayThamGia ASC'
    );
    $stmtGV->execute([':idNhom' => $idNhom]);
    $nhom['gvhd'] = $stmtGV->fetchAll(PDO::FETCH_ASSOC);

    // Yêu cầu chờ: chỉ trả nếu user là Chủ nhóm
    $nhom['yeu_cau_cho'] = [];
    if ($nhom['is_chu_nhom']) {
        $stmtYC = $conn->prepare(
            'SELECT yc.idYeuCau, yc.idTK, yc.ChieuMoi, yc.loaiYeuCau,
                    yc.loiNhan, yc.ngayGui,
                    CASE WHEN tk.idLoaiTK = 3 THEN sv.tenSV
                         WHEN tk.idLoaiTK = 2 THEN gv.tenGV END AS ten
             FROM yeucau_thamgia yc
             LEFT JOIN taikhoan tk ON yc.idTK = tk.idTK
             LEFT JOIN sinhvien sv ON tk.idTK = sv.idTK
             LEFT JOIN giangvien gv ON tk.idTK = gv.idTK
             WHERE yc.idNhom = :idNhom
               AND yc.trangThai = 0
               AND yc.ChieuMoi = 1
             ORDER BY yc.ngayGui DESC'
        );
        $stmtYC->execute([':idNhom' => $idNhom]);
        $nhom['yeu_cau_cho'] = $stmtYC->fetchAll(PDO::FETCH_ASSOC);
    }

    // Gộp thanh_vien_sv + gvhd thành mảng thống nhất cho frontend
    $nhom['thanh_vien'] = _merge_thanh_vien(
        $nhom['thanh_vien_sv'],
        $nhom['gvhd'],
        $nhom['idTruongNhom'] !== null ? (int) $nhom['idTruongNhom'] : null
    );

    return $nhom;
}

/**
 * Lấy yêu cầu của user trong sự kiện: lời mời đến và yêu cầu gửi đi.
 */
function lay_yeu_cau_cua_toi(PDO $conn, int $idTK, int $idSK): array
{
    // Lời mời đến: nhóm mời user (ChieuMoi=0), chỉ pending
    $stmtMoi = $conn->prepare(
        'SELECT yc.idYeuCau, yc.idNhom, yc.loaiYeuCau, yc.loiNhan, yc.ngayGui,
                tn.tennhom, n.maNhom,
                COUNT(tv.idTK) AS so_thanh_vien_sv
         FROM yeucau_thamgia yc
         JOIN nhom n ON yc.idNhom = n.idNhom
         LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         LEFT JOIN thanhviennhom tv ON tv.idNhom = n.idNhom
         WHERE yc.idTK = :idTK
           AND yc.ChieuMoi = 0
           AND n.idSK = :idSK
           AND yc.trangThai = 0
         GROUP BY yc.idYeuCau, yc.idNhom, yc.loaiYeuCau,
                  yc.loiNhan, yc.ngayGui, tn.tennhom, n.maNhom
         ORDER BY yc.ngayGui DESC'
    );
    $stmtMoi->execute([':idTK' => $idTK, ':idSK' => $idSK]);
    $loiMoiDen = $stmtMoi->fetchAll(PDO::FETCH_ASSOC);

    // Yêu cầu gửi đi: user tự xin vào (ChieuMoi=1), trả cả lịch sử
    $stmtGui = $conn->prepare(
        'SELECT yc.idYeuCau, yc.idNhom, yc.loaiYeuCau, yc.loiNhan,
                yc.trangThai, yc.ngayGui, yc.ngayPhanHoi,
                tn.tennhom, n.maNhom
         FROM yeucau_thamgia yc
         JOIN nhom n ON yc.idNhom = n.idNhom
         LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         WHERE yc.idTK = :idTK
           AND yc.ChieuMoi = 1
           AND n.idSK = :idSK
         ORDER BY yc.ngayGui DESC'
    );
    $stmtGui->execute([':idTK' => $idTK, ':idSK' => $idSK]);
    $yeuCauGuiDi = $stmtGui->fetchAll(PDO::FETCH_ASSOC);

    return [
        'loi_moi_den' => $loiMoiDen,
        'yeu_cau_gui_di' => $yeuCauGuiDi,
    ];
}

/**
 * Lấy chi tiết nhóm. Check quyền xem: chủ nhóm, thành viên SV, hoặc GVHD.
 */
function lay_chi_tiet_nhom(PDO $conn, int $idNhom, int $idTKNguoiXem): ?array
{
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) return null;

    // Auth check: cho phép xem nếu là chủ nhóm, thành viên SV, hoặc GVHD
    $duocXem = ($idTKNguoiXem === (int) $nhom['idChuNhom'])
        || la_thanh_vien_sv($conn, $idTKNguoiXem, $idNhom)
        || la_gvhd_nhom($conn, $idTKNguoiXem, $idNhom);

    if (!$duocXem) {
        return ['_forbidden' => true];
    }

    // Lấy thông tin nhóm đầy đủ
    $stmtNhom = $conn->prepare(
        'SELECT n.idNhom, n.maNhom, n.ngayTao, n.isActive,
                n.idChuNhom, n.idTruongNhom,
                tn.tennhom, tn.mota, tn.dangtuyen,
                sk.soThanhVienToiDa
         FROM nhom n
         LEFT JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         LEFT JOIN sukien sk ON sk.idSK = n.idSK
         WHERE n.idNhom = :idNhom'
    );
    $stmtNhom->execute([':idNhom' => $idNhom]);
    $data = $stmtNhom->fetch(PDO::FETCH_ASSOC);
    if (!$data) return null;

    $data['so_thanh_vien_toi_da'] = $data['soThanhVienToiDa'] !== null ? (int) $data['soThanhVienToiDa'] : null;
    unset($data['soThanhVienToiDa']);

    // Thành viên SV
    $stmtSV = $conn->prepare(
        'SELECT tv.idTK, sv.tenSV AS ten, sv.MSV, l.tenLop, tv.ngayThamGia
         FROM thanhviennhom tv
         JOIN sinhvien sv ON sv.idTK = tv.idTK
         LEFT JOIN lop l ON sv.idLop = l.idLop
         WHERE tv.idNhom = :idNhom
         ORDER BY tv.ngayThamGia ASC'
    );
    $stmtSV->execute([':idNhom' => $idNhom]);
    $data['thanh_vien_sv'] = $stmtSV->fetchAll(PDO::FETCH_ASSOC);

    // GVHD
    $stmtGV = $conn->prepare(
        'SELECT g.idTK, gv.tenGV AS ten, g.ngayThamGia
         FROM nhom_gvhd g
         JOIN giangvien gv ON gv.idTK = g.idTK
         WHERE g.idNhom = :idNhom
         ORDER BY g.ngayThamGia ASC'
    );
    $stmtGV->execute([':idNhom' => $idNhom]);
    $data['gvhd'] = $stmtGV->fetchAll(PDO::FETCH_ASSOC);

    // Gộp thanh_vien_sv + gvhd thành mảng thống nhất cho frontend
    $data['thanh_vien'] = _merge_thanh_vien(
        $data['thanh_vien_sv'],
        $data['gvhd'],
        $data['idTruongNhom'] !== null ? (int) $data['idTruongNhom'] : null
    );

    return $data;
}

// ==========================================
// HÀM SẢN PHẨM & NỘP TÀI LIỆU
// ==========================================

/**
 * Gộp thanh_vien_sv + gvhd thành mảng thống nhất `thanh_vien`
 * với các key frontend cần: idtk, ten, msv_ma, idvaitronhom
 *   idvaitronhom: 1 = Trưởng nhóm, 2 = Thành viên, 3 = GVHD
 */
function _merge_thanh_vien(array $svList, array $gvhdList, ?int $idTruongNhom): array
{
    $result = [];
    foreach ($svList as $sv) {
        $idTK = (int) $sv['idTK'];
        $result[] = [
            'idtk'          => $idTK,
            'ten'           => $sv['ten'],
            'msv_ma'        => $sv['MSV'] ?? null,
            'tenLop'        => $sv['tenLop'] ?? null,
            'ngayThamGia'   => $sv['ngayThamGia'] ?? null,
            'idvaitronhom'  => ($idTruongNhom !== null && $idTK === $idTruongNhom) ? 1 : 2,
        ];
    }
    foreach ($gvhdList as $gv) {
        $result[] = [
            'idtk'          => (int) $gv['idTK'],
            'ten'           => $gv['ten'],
            'msv_ma'        => null,
            'ngayThamGia'   => $gv['ngayThamGia'] ?? null,
            'idvaitronhom'  => 3,
        ];
    }
    return $result;
}

/**
 * Tạo hoặc cập nhật sản phẩm của nhóm trong SK.
 * Chỉ Trưởng nhóm mới được thực hiện.
 */
function tao_hoac_cap_nhat_san_pham(PDO $conn, int $idTK, int $idNhom, string $tenSanPham, ?int $idChuDeSK, array $fieldValues = []): array
{
    $tenSanPham = trim($tenSanPham);
    if ($tenSanPham === '' || mb_strlen($tenSanPham) > 200) {
        return ['status' => false, 'message' => 'Tên sản phẩm không được rỗng và tối đa 200 ký tự'];
    }

    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    if ($idTK !== (int) $nhom['idTruongNhom']) {
        return ['status' => false, 'message' => 'Chỉ Trưởng nhóm mới được quản lý sản phẩm'];
    }

    $idSK = (int) $nhom['idSK'];
    $sukien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $idSK);
    if (!$sukien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // Check yêu cầu có GVHD
    if ((int) ($sukien['yeuCauCoGVHD'] ?? 0) === 1) {
        if (so_gvhd_nhom($conn, $idNhom) === 0) {
            return ['status' => false, 'message' => 'Sự kiện yêu cầu nhóm phải có GVHD trước khi tạo sản phẩm'];
        }
    }

    // Validate field values theo form mặc định SK
    $formFields = lay_form_mac_dinh_sk($conn, $idSK);
    foreach ($formFields as $field) {
        $idField  = (int) $field['idField'];
        $batBuoc  = (bool) $field['batBuoc'];
        $val      = trim((string) ($fieldValues[$idField] ?? ''));
        if ($batBuoc && $val === '') {
            return ['status' => false, 'message' => "Trường '{$field['tenTruong']}' là bắt buộc"];
        }
        if ($field['kieuTruong'] === 'URL' && $val !== '' && !filter_var($val, FILTER_VALIDATE_URL)) {
            return ['status' => false, 'message' => "Trường '{$field['tenTruong']}' phải là URL hợp lệ"];
        }
    }

    // Check đã có sản phẩm chưa
    $stmt = $conn->prepare('SELECT idSanPham FROM sanpham WHERE idNhom = :idNhom AND idSK = :idSK LIMIT 1');
    $stmt->execute([':idNhom' => $idNhom, ':idSK' => $idSK]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        $conn->beginTransaction();

        if ($existing) {
            $okUpdate = $conn->prepare('UPDATE sanpham SET tenSanPham = :ten, idChuDeSK = :chuDe WHERE idSanPham = :id');
            $okUpdate->execute([':ten' => $tenSanPham, ':chuDe' => $idChuDeSK, ':id' => $existing['idSanPham']]);
            $idSanPham = (int) $existing['idSanPham'];
        } else {
            $ok = _insert_info(
                $conn,
                'sanpham',
                ['idNhom', 'idSK', 'idChuDeSK', 'tenSanPham', 'trangThai', 'ngayTao'],
                [$idNhom, $idSK, $idChuDeSK, $tenSanPham, 'CHO_DUYET', date('Y-m-d H:i:s')]
            );
            if (!$ok) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Lỗi khi tạo sản phẩm'];
            }
            $idSanPham = (int) $conn->lastInsertId();
        }

        // Lưu field values (idVongThi = NULL = form mặc định SK)
        if (!empty($formFields) && !empty($fieldValues)) {
            $stmtFV = $conn->prepare(
                'INSERT INTO sanpham_field_value (idSanPham, idField, idVongThi, giaTriText)
                 VALUES (?, ?, NULL, ?)
                 ON DUPLICATE KEY UPDATE
                     giaTriText = VALUES(giaTriText),
                     ngayNop = CURRENT_TIMESTAMP'
            );
            foreach ($formFields as $field) {
                $idField = (int) $field['idField'];
                $val     = trim((string) ($fieldValues[$idField] ?? ''));
                // Chỉ lưu nếu có giá trị hoặc field đã tồn tại (để clear)
                $stmtFV->execute([$idSanPham, $idField, $val]);
            }
        }

        $conn->commit();
        return [
            'status'    => true,
            'message'   => $existing ? 'Cập nhật sản phẩm thành công' : 'Tạo sản phẩm thành công',
            'idSanPham' => $idSanPham,
        ];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('tao_hoac_cap_nhat_san_pham: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi lưu sản phẩm'];
    }
}

/**
 * Nộp tài liệu cho một vòng thi.
 * Chỉ Trưởng nhóm mới được thực hiện.
 */
function nop_tai_lieu_vong(PDO $conn, int $idTK, int $idNhom, int $idVongThi, array $fieldValues, array $uploadedFiles): array
{
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    if ($idTK !== (int) $nhom['idTruongNhom']) {
        return ['status' => false, 'message' => 'Chỉ Trưởng nhóm mới được nộp tài liệu'];
    }

    $idSK = (int) $nhom['idSK'];

    // Lấy vòng thi
    $vongthi = truy_van_mot_ban_ghi($conn, 'vongthi', 'idVongThi', $idVongThi);
    if (!$vongthi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    // Check deadline (thủ công hoặc theo thời gian)
    if ((int)($vongthi['dongNopThuCong'] ?? 0) === 1) {
        return ['status' => false, 'message' => 'Vòng thi đã đóng nộp bài'];
    }
    if (!empty($vongthi['thoiGianDongNop']) && strtotime($vongthi['thoiGianDongNop']) <= time()) {
        return ['status' => false, 'message' => 'Đã quá hạn nộp bài'];
    }

    // Lấy form fields
    $formFields = lay_form_vong_thi($conn, $idVongThi);
    if (empty($formFields)) {
        return ['status' => false, 'message' => 'Vòng thi này không yêu cầu nộp tài liệu'];
    }

    // Check sản phẩm đã tồn tại
    $stmt = $conn->prepare('SELECT idSanPham FROM sanpham WHERE idNhom = :idNhom AND idSK = :idSK LIMIT 1');
    $stmt->execute([':idNhom' => $idNhom, ':idSK' => $idSK]);
    $sanpham = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sanpham) {
        return ['status' => false, 'message' => 'Vui lòng tạo sản phẩm trước'];
    }

    // Validate từng field
    foreach ($formFields as $field) {
        $idField = (int) $field['idField'];
        $kieuTruong = $field['kieuTruong'];
        $batBuoc = (bool) $field['batBuoc'];
        $cauHinh = json_decode($field['cauHinhJson'] ?? '{}', true) ?: [];

        if ($kieuTruong === 'FILE') {
            $hasFile = isset($uploadedFiles[$idField]) && $uploadedFiles[$idField]['size'] > 0;
            if ($batBuoc && !$hasFile) {
                return ['status' => false, 'message' => "Field '{$field['tenTruong']}' là bắt buộc"];
            }
            if ($hasFile) {
                $accept = $cauHinh['accept'] ?? '';
                if ($accept !== '') {
                    $allowedExts = array_map('trim', explode(',', $accept));
                    $ext = strtolower(pathinfo($uploadedFiles[$idField]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExts, true)) {
                        return ['status' => false, 'message' => "Định dạng file không hợp lệ cho '{$field['tenTruong']}'. Chấp nhận: $accept"];
                    }
                }
                $maxKB = (int) ($cauHinh['maxSizeKB'] ?? 0);
                if ($maxKB > 0 && $uploadedFiles[$idField]['size'] > $maxKB * 1024) {
                    return ['status' => false, 'message' => "File '{$field['tenTruong']}' quá lớn. Tối đa {$maxKB}KB"];
                }
            }
        } else {
            $val = trim((string) ($fieldValues[$idField] ?? ''));
            if ($batBuoc && $val === '') {
                return ['status' => false, 'message' => "Field '{$field['tenTruong']}' là bắt buộc"];
            }
            if ($kieuTruong === 'URL' && $val !== '' && !filter_var($val, FILTER_VALIDATE_URL)) {
                return ['status' => false, 'message' => "Field '{$field['tenTruong']}' phải là URL hợp lệ"];
            }
        }
    }

    // Upload files và lưu DB
    $conn->beginTransaction();
    try {
        foreach ($formFields as $field) {
            $idField = (int) $field['idField'];
            $kieuTruong = $field['kieuTruong'];
            $giaTriText = null;
            $duongDanFile = null;

            if ($kieuTruong === 'FILE' && isset($uploadedFiles[$idField]) && $uploadedFiles[$idField]['size'] > 0) {
                $uploadDir = __DIR__ . "/../../uploads/sanpham/{$idSK}/{$idNhom}/{$idVongThi}/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($uploadedFiles[$idField]['name'], PATHINFO_EXTENSION));
                $tenFileMoi = $idField . '_' . time() . '.' . $ext;
                $fullPath = $uploadDir . $tenFileMoi;
                if (!move_uploaded_file($uploadedFiles[$idField]['tmp_name'], $fullPath)) {
                    $conn->rollBack();
                    return ['status' => false, 'message' => 'Lỗi upload file'];
                }
                $duongDanFile = "/uploads/sanpham/{$idSK}/{$idNhom}/{$idVongThi}/{$tenFileMoi}";
            } elseif ($kieuTruong !== 'FILE') {
                $giaTriText = trim((string) ($fieldValues[$idField] ?? ''));
            } else {
                continue;
            }

            $sql = "INSERT INTO sanpham_field_value (idSanPham, idField, idVongThi, giaTriText, duongDanFile)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        idVongThi    = VALUES(idVongThi),
                        giaTriText   = VALUES(giaTriText),
                        duongDanFile = VALUES(duongDanFile),
                        ngayNop = CURRENT_TIMESTAMP";
            $stmtInsert = $conn->prepare($sql);
            $stmtInsert->execute([$sanpham['idSanPham'], $idField, $idVongThi, $giaTriText, $duongDanFile]);
        }
        $conn->commit();
        return ['status' => true, 'message' => 'Nộp tài liệu thành công'];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['status' => false, 'message' => 'Lỗi hệ thống khi nộp tài liệu'];
    }
}

/**
 * Lấy sản phẩm của nhóm trong SK.
 */
function lay_san_pham_nhom(PDO $conn, int $idNhom, int $idSK): ?array
{
    $stmt = $conn->prepare(
        'SELECT sp.*, c.tenChuDe
         FROM sanpham sp
         LEFT JOIN chude_sukien cs ON cs.idChuDeSK = sp.idChuDeSK
         LEFT JOIN chude c ON c.idChuDe = cs.idchude
         WHERE sp.idNhom = :idNhom AND sp.idSK = :idSK
         LIMIT 1'
    );
    $stmt->execute([':idNhom' => $idNhom, ':idSK' => $idSK]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Lấy tài liệu đã nộp cho một vòng thi.
 */
function lay_tai_lieu_da_nop(PDO $conn, int $idSanPham, int $idVongThi): array
{
    $stmt = $conn->prepare(
        'SELECT sfv.*, ff.tenTruong, ff.kieuTruong, ff.thuTu
         FROM sanpham_field_value sfv
         JOIN form_field ff ON ff.idField = sfv.idField
         WHERE sfv.idSanPham = :idSanPham
           AND ff.idVongThi = :idVongThi
         ORDER BY ff.thuTu ASC'
    );
    $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy form fields của một vòng thi.
 */
function lay_form_vong_thi(PDO $conn, int $idVongThi): array
{
    $stmt = $conn->prepare(
        'SELECT * FROM form_field
         WHERE idVongThi = :idVongThi AND isActive = 1
         ORDER BY thuTu ASC'
    );
    $stmt->execute([':idVongThi' => $idVongThi]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy form mặc định của sự kiện (idVongThi IS NULL).
 */
function lay_form_mac_dinh_sk(PDO $conn, int $idSK): array
{
    $stmt = $conn->prepare(
        'SELECT * FROM form_field
         WHERE idSK = :idSK AND idVongThi IS NULL AND isActive = 1
         ORDER BY thuTu ASC'
    );
    $stmt->execute([':idSK' => $idSK]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy giá trị form mặc định SK đã nộp của 1 sản phẩm.
 */
function lay_field_value_mac_dinh(PDO $conn, int $idSanPham): array
{
    $stmt = $conn->prepare(
        'SELECT sfv.*, ff.tenTruong, ff.kieuTruong, ff.thuTu
         FROM sanpham_field_value sfv
         JOIN form_field ff ON ff.idField = sfv.idField
         WHERE sfv.idSanPham = :idSanPham
           AND sfv.idVongThi IS NULL
         ORDER BY ff.thuTu ASC'
    );
    $stmt->execute([':idSanPham' => $idSanPham]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cập nhật thông tin nhóm. Chỉ Chủ nhóm mới được thực hiện.
 */
function cap_nhat_thong_tin_nhom(PDO $conn, int $idTK, int $idNhom, string $tenNhom, string $moTa, int $dangTuyen, ?int $isActive = null): array
{
    // 1. Check quyền chủ nhóm
    if (!la_chu_nhom($conn, $idTK, $idNhom)) {
        return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được cập nhật thông tin nhóm'];
    }

    // 2. Lấy nhóm, check active
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    // 3. Validate tên nhóm
    $tenNhom = trim($tenNhom);
    $tenNhomLen = mb_strlen($tenNhom);
    if ($tenNhomLen < 3 || $tenNhomLen > 100) {
        return ['status' => false, 'message' => 'Tên nhóm phải từ 3 đến 100 ký tự'];
    }

    // 4. Check tên unique trong SK (loại trừ chính nhóm này)
    $idSK = (int) $nhom['idSK'];
    $stmtUnique = $conn->prepare(
        'SELECT 1 FROM nhom n
         JOIN thongtinnhom tn ON tn.idnhom = n.idNhom
         WHERE n.idSK = :idSK AND tn.tennhom = :tenNhom AND n.isActive = 1 AND n.idNhom != :idNhom
         LIMIT 1'
    );
    $stmtUnique->execute([':idSK' => $idSK, ':tenNhom' => $tenNhom, ':idNhom' => $idNhom]);
    if ($stmtUnique->fetchColumn()) {
        return ['status' => false, 'message' => 'Tên nhóm đã tồn tại trong sự kiện này'];
    }

    // 5. UPDATE thongtinnhom
    $ok = _update_info(
        $conn,
        'thongtinnhom',
        ['tennhom', 'mota', 'dangtuyen'],
        [$tenNhom, $moTa, $dangTuyen],
        ['idnhom' => ['=', $idNhom, '']]
    );

    if (!$ok) {
        return ['status' => false, 'message' => 'Lỗi khi cập nhật thông tin nhóm'];
    }

    // 6. Nếu isActive !== null: cập nhật trạng thái nhóm
    if ($isActive !== null) {
        $okActive = _update_info(
            $conn,
            'nhom',
            ['isActive'],
            [(int) $isActive],
            ['idNhom' => ['=', $idNhom, '']]
        );
        if (!$okActive) {
            return ['status' => false, 'message' => 'Lỗi khi cập nhật trạng thái nhóm'];
        }
    }

    return ['status' => true, 'message' => 'Cập nhật thành công'];
}

/**
 * Nhượng quyền nhóm: chủ nhóm hoặc trưởng nhóm.
 * @param string $action 'chu_nhom' | 'truong_nhom'
 */
function nhuong_quyen_nhom(PDO $conn, int $idTK, int $idNhom, string $action, int $idNguoiNhan): array
{
    // Validate chung
    if (!la_chu_nhom($conn, $idTK, $idNhom)) {
        return ['status' => false, 'message' => 'Chỉ chủ nhóm mới được nhượng quyền'];
    }
    if ($idNguoiNhan === $idTK) {
        return ['status' => false, 'message' => 'Không thể nhượng quyền cho chính mình'];
    }

    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom || (int) $nhom['isActive'] !== 1) {
        return ['status' => false, 'message' => 'Nhóm không tồn tại hoặc đã ngừng hoạt động'];
    }

    if ($action === 'truong_nhom') {
        // Trưởng nhóm phải là SV thành viên của nhóm
        if (!la_thanh_vien_sv($conn, $idNguoiNhan, $idNhom)) {
            return ['status' => false, 'message' => 'Trưởng nhóm phải là SV thành viên của nhóm'];
        }
        $ok = _update_info(
            $conn,
            'nhom',
            ['idTruongNhom'],
            [$idNguoiNhan],
            ['idNhom' => ['=', $idNhom, '']]
        );
        return $ok
            ? ['status' => true, 'message' => 'Đã chuyển quyền trưởng nhóm thành công']
            : ['status' => false, 'message' => 'Lỗi khi chuyển quyền trưởng nhóm'];
    }

    if ($action === 'chu_nhom') {
        $taiKhoanNguoiNhan = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idNguoiNhan);
        if (!$taiKhoanNguoiNhan) {
            return ['status' => false, 'message' => 'Tài khoản người nhận không tồn tại'];
        }
        $loaiTKNhan = (int) $taiKhoanNguoiNhan['idLoaiTK'];

        if ($loaiTKNhan === 3) {
            // SV nhận Chủ nhóm
            if (!la_thanh_vien_sv($conn, $idNguoiNhan, $idNhom)) {
                return ['status' => false, 'message' => 'SV phải là thành viên của nhóm để nhận quyền chủ nhóm'];
            }
            // Nếu nhóm đang có Trưởng nhóm khác → giữ nguyên idTruongNhom
            // Nếu không có hoặc Trưởng nhóm chính là người nhận → set idTruongNhom = idNguoiNhan
            $truongNhomHienTai = $nhom['idTruongNhom'];
            if ($truongNhomHienTai !== null && (int)$truongNhomHienTai !== $idNguoiNhan) {
                // Giữ nguyên idTruongNhom, chỉ đổi idChuNhom
                $ok = _update_info(
                    $conn,
                    'nhom',
                    ['idChuNhom'],
                    [$idNguoiNhan],
                    ['idNhom' => ['=', $idNhom, '']]
                );
            } else {
                // SV nhận Chủ nhóm → tự động thành Trưởng nhóm luôn
                $ok = _update_info(
                    $conn,
                    'nhom',
                    ['idChuNhom', 'idTruongNhom'],
                    [$idNguoiNhan, $idNguoiNhan],
                    ['idNhom' => ['=', $idNhom, '']]
                );
            }
            return $ok
                ? ['status' => true, 'message' => 'Đã chuyển quyền chủ nhóm thành công']
                : ['status' => false, 'message' => 'Lỗi khi chuyển quyền chủ nhóm'];
        }

        if ($loaiTKNhan === 2) {
            // GV nhận Chủ nhóm
            if (!la_gvhd_nhom($conn, $idNguoiNhan, $idNhom)) {
                return ['status' => false, 'message' => 'GV phải là GVHD của nhóm trước khi nhận quyền chủ nhóm'];
            }
            // Giữ nguyên idTruongNhom
            $ok = _update_info(
                $conn,
                'nhom',
                ['idChuNhom'],
                [$idNguoiNhan],
                ['idNhom' => ['=', $idNhom, '']]
            );
            return $ok
                ? ['status' => true, 'message' => 'Đã chuyển quyền chủ nhóm thành công']
                : ['status' => false, 'message' => 'Lỗi khi chuyển quyền chủ nhóm'];
        }

        return ['status' => false, 'message' => 'Loại tài khoản người nhận không hợp lệ'];
    }

    return ['status' => false, 'message' => 'Action không hợp lệ. Chỉ chấp nhận: chu_nhom, truong_nhom'];
}

/**
 * Kiểm tra user có phải thành viên active của nhóm (SV hoặc GVHD).
 */
function kiem_tra_thanh_vien_nhom(PDO $conn, int $idTK, int $idNhom): bool
{
    if ($idTK <= 0 || $idNhom <= 0) return false;
    return la_thanh_vien_sv($conn, $idTK, $idNhom) || la_gvhd_nhom($conn, $idTK, $idNhom);
}
