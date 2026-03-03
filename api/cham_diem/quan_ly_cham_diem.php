<?php
/**
 * Quản lý chấm điểm - Logic xử lý nghiệp vụ
 * 
 * Module này bao gồm:
 * - Phân công giám khảo chấm độc lập
 * - Tính toán tiến độ và IRR (Inter-Rater Reliability)
 * - Xét duyệt kết quả và xếp hạng
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

/**
 * ==========================================
 * PHẦN 1: QUẢN LÝ PHÂN CÔNG GIÁM KHẢO
 * ==========================================
 */

/**
 * Lấy danh sách sản phẩm cần chấm của vòng thi
 * 
 * Đếm giám khảo từ 2 nguồn:
 * - phancong_doclap: Phân công chính thức
 * - chamtieuchi: Giám khảo đã chấm điểm (bao gồm cả legacy data)
 */
function cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi) {
    $sql = "SELECT 
                sp.idSanPham,
                sp.tensanpham,
                sp.TrangThai,
                n.manhom,
                ttn.tennhom,
                tkNT.tenTK as tenNhomTruong,
                CASE WHEN sv.tenSV IS NOT NULL THEN sv.tenSV ELSE gv.tenGV END as hoTenNhomTruong,
                spv.diemTrungBinh,
                spv.trangThai as trangThaiVongThi,
                spv.xepLoai,
                -- Đếm số GK đã phân công HOẶC đã chấm (union)
                (SELECT COUNT(DISTINCT judgeId) FROM (
                    SELECT pd.idGV as judgeId FROM phancong_doclap pd 
                    WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1
                    UNION
                    SELECT pcc.idGV as judgeId FROM phancongcham pcc 
                    INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham 
                    WHERE ct.idSanPham = sp.idSanPham AND pcc.idVongThi = :idVongThi4
                ) as allJudges) as soGiamKhao,
                -- Đếm số GK đã chấm xong
                (SELECT COUNT(DISTINCT pcc.idGV) 
                 FROM phancongcham pcc 
                 INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                 WHERE pcc.idSK = :idSK1 AND pcc.idVongThi = :idVongThi2
                ) as soGKDaCham
            FROM sanpham sp
            INNER JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            LEFT JOIN taikhoan tkNT ON n.idnhomtruong = tkNT.idTK
            LEFT JOIN sinhvien sv ON tkNT.idTK = sv.idTK
            LEFT JOIN giangvien gv ON tkNT.idTK = gv.idTK
            LEFT JOIN sanpham_vongthi spv ON sp.idSanPham = spv.idSanPham AND spv.idVongThi = :idVongThi3
            WHERE sp.idSK = :idSK2 
              AND sp.isActive = 1
            ORDER BY sp.idSanPham ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSK1' => $idSK,
        ':idSK2' => $idSK,
        ':idVongThi1' => $idVongThi,
        ':idVongThi2' => $idVongThi,
        ':idVongThi3' => $idVongThi,
        ':idVongThi4' => $idVongThi
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách giảng viên có thể phân công chấm
 */
function cham_diem_lay_danh_sach_giang_vien($conn, $idSK = null) {
    $sql = "SELECT 
                gv.idGV,
                gv.tenGV,
                tk.tenTK,
                k.tenKhoa,
                (SELECT COUNT(*) FROM phancong_doclap pd WHERE pd.idGV = gv.idGV) as soBaiDangCham
            FROM giangvien gv
            INNER JOIN taikhoan tk ON gv.idTK = tk.idTK
            LEFT JOIN khoa k ON gv.idKhoa = k.idKhoa
            WHERE tk.isActive = 1
            ORDER BY gv.tenGV ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách giám khảo đã phân công cho một sản phẩm
 * Bao gồm cả giám khảo từ phancong_doclap và từ chamtieuchi (legacy)
 */
function cham_diem_lay_giam_khao_san_pham($conn, $idSanPham, $idVongThi) {
    // Union giám khảo từ phancong_doclap và từ chamtieuchi
    $sql = "SELECT 
                allGK.idGV,
                gv.tenGV,
                tk.tenTK,
                allGK.nguon,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM chamtieuchi ct 
                        INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                        WHERE ct.idSanPham = :idSanPham1 AND pcc.idGV = allGK.idGV AND pcc.idVongThi = :idVongThi1
                    ) THEN 'Đã chấm'
                    ELSE 'Chưa chấm'
                END as trangThaiCham,
                (SELECT AVG(ct.diem) FROM chamtieuchi ct 
                 INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                 WHERE ct.idSanPham = :idSanPham2 AND pcc.idGV = allGK.idGV AND pcc.idVongThi = :idVongThi2
                ) as diemTB
            FROM (
                -- Từ phancong_doclap (phân công chính thức)
                SELECT pd.idGV, 'phancong' as nguon
                FROM phancong_doclap pd 
                WHERE pd.idSanPham = :idSanPham3 AND pd.idVongThi = :idVongThi3
                UNION
                -- Từ chamtieuchi (đã chấm điểm, có thể không có trong phancong_doclap)
                SELECT DISTINCT pcc.idGV, 'chamtieuchi' as nguon
                FROM chamtieuchi ct
                INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                WHERE ct.idSanPham = :idSanPham4 AND pcc.idVongThi = :idVongThi4
            ) as allGK
            INNER JOIN giangvien gv ON allGK.idGV = gv.idGV
            INNER JOIN taikhoan tk ON gv.idTK = tk.idTK
            ORDER BY gv.tenGV ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSanPham1' => $idSanPham, ':idVongThi1' => $idVongThi,
        ':idSanPham2' => $idSanPham, ':idVongThi2' => $idVongThi,
        ':idSanPham3' => $idSanPham, ':idVongThi3' => $idVongThi,
        ':idSanPham4' => $idSanPham, ':idVongThi4' => $idVongThi
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Phân công giám khảo chấm độc lập
 */
function cham_diem_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi) {
    try {
        // Kiểm tra xem đã phân công chưa
        $sqlCheck = "SELECT 1 FROM phancong_doclap WHERE idSanPham = :idSanPham AND idGV = :idGV AND idVongThi = :idVongThi";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Giám khảo này đã được phân công cho bài thi'];
        }
        
        // INSERT IGNORE để tránh lỗi nếu vô tình bấm 2 lần
        $sql = "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES (:idSanPham, :idGV, :idVongThi)";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        
        if ($result) {
            // Cập nhật trạng thái sản phẩm vòng thi nếu chưa có
            $sqlUpdateSPV = "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, trangThai, ngayCapNhat) 
                             VALUES (:idSanPham, :idVongThi, 'Đã phân công', NOW())
                             ON DUPLICATE KEY UPDATE trangThai = IF(trangThai = 'Đã nộp' OR trangThai IS NULL, 'Đã phân công', trangThai), ngayCapNhat = NOW()";
            $stmtSPV = $conn->prepare($sqlUpdateSPV);
            $stmtSPV->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
            
            return ['success' => true, 'message' => 'Phân công giám khảo thành công'];
        }
        
        return ['success' => false, 'message' => 'Không thể phân công giám khảo'];
    } catch (Throwable $e) {
        error_log('Error in cham_diem_phan_cong_giam_khao: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi phân công'];
    }
}

/**
 * Gỡ phân công giám khảo
 */
function cham_diem_go_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi) {
    try {
        // Kiểm tra xem giám khảo đã chấm điểm chưa
        $sqlCheck = "SELECT 1 FROM chamtieuchi ct 
                     INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                     WHERE ct.idSanPham = :idSanPham AND pcc.idGV = :idGV AND pcc.idVongThi = :idVongThi";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Không thể gỡ phân công vì giám khảo đã chấm điểm'];
        }
        
        $sql = "DELETE FROM phancong_doclap WHERE idSanPham = :idSanPham AND idGV = :idGV AND idVongThi = :idVongThi";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Kiểm tra nếu không còn ai phân công thì đổi trạng thái về chờ
            $sqlCountPC = "SELECT COUNT(*) as count FROM phancong_doclap WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
            $stmtCount = $conn->prepare($sqlCountPC);
            $stmtCount->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
            $count = $stmtCount->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                $sqlUpdateSPV = "UPDATE sanpham_vongthi SET trangThai = 'Đã nộp', ngayCapNhat = NOW() 
                                 WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
                $stmtSPV = $conn->prepare($sqlUpdateSPV);
                $stmtSPV->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
            }
            
            return ['success' => true, 'message' => 'Gỡ phân công thành công'];
        }
        
        return ['success' => false, 'message' => 'Không tìm thấy phân công để gỡ'];
    } catch (Throwable $e) {
        error_log('Error in cham_diem_go_phan_cong_giam_khao: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi gỡ phân công'];
    }
}

/**
 * Mời giám khảo thứ 3 (Trọng tài phúc khảo)
 */
function cham_diem_moi_trong_tai($conn, $idSanPham, $idGV, $idVongThi) {
    try {
        $conn->beginTransaction();
        
        // 1. Thêm phân công mới
        $sqlInsert = "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi) VALUES (:idSanPham, :idGV, :idVongThi)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        
        // 2. Reset trạng thái - Xóa điểm chốt và trạng thái cũ
        $sqlReset = "UPDATE sanpham_vongthi SET diemTrungBinh = NULL, trangThai = 'Đang chấm', xepLoai = NULL, ngayCapNhat = NOW()
                     WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
        $stmtReset = $conn->prepare($sqlReset);
        $stmtReset->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Đã mời Trọng tài thành công. Bài thi đã được reset về trạng thái đang chấm.'];
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('Error in cham_diem_moi_trong_tai: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi mời trọng tài'];
    }
}

/**
 * ==========================================
 * PHẦN 2: TIẾN ĐỘ & KIỂM ĐỊNH IRR
 * ==========================================
 */

/**
 * Lấy thống kê tiến độ chấm điểm của vòng thi
 */
function cham_diem_lay_thong_ke_tien_do($conn, $idSK, $idVongThi) {
    // Tổng số sản phẩm cần chấm
    $sqlTotal = "SELECT COUNT(*) as total FROM sanpham WHERE idSK = :idSK AND isActive = 1";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->execute([':idSK' => $idSK]);
    $tongSanPham = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Số sản phẩm đã phân công HOẶC đã có điểm chấm (union)
    $sqlAssigned = "SELECT COUNT(DISTINCT spId) as total FROM (
                        SELECT pd.idSanPham as spId FROM phancong_doclap pd
                        INNER JOIN sanpham sp ON pd.idSanPham = sp.idSanPham
                        WHERE sp.idSK = :idSK1 AND pd.idVongThi = :idVongThi1
                        UNION
                        SELECT ct.idSanPham as spId FROM chamtieuchi ct
                        INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                        INNER JOIN sanpham sp ON ct.idSanPham = sp.idSanPham
                        WHERE sp.idSK = :idSK2 AND pcc.idVongThi = :idVongThi2
                    ) as assignedProducts";
    $stmtAssigned = $conn->prepare($sqlAssigned);
    $stmtAssigned->execute([':idSK1' => $idSK, ':idVongThi1' => $idVongThi, ':idSK2' => $idSK, ':idVongThi2' => $idVongThi]);
    $daPhanCong = $stmtAssigned->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Số sản phẩm đã chấm xong (tất cả GK đã chấm)
    $sqlDone = "SELECT COUNT(*) as total FROM (
                    SELECT sp.idSanPham,
                           (SELECT COUNT(DISTINCT judgeId) FROM (
                               SELECT pd.idGV as judgeId FROM phancong_doclap pd 
                               WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1
                               UNION
                               SELECT pcc.idGV as judgeId FROM phancongcham pcc 
                               INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham 
                               WHERE ct.idSanPham = sp.idSanPham AND pcc.idVongThi = :idVongThi1b
                           ) as allJudges) as soGK,
                           (SELECT COUNT(DISTINCT pcc.idGV) 
                            FROM phancongcham pcc 
                            INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                            WHERE pcc.idVongThi = :idVongThi2
                           ) as soGKDaCham
                    FROM sanpham sp
                    WHERE sp.idSK = :idSK AND sp.isActive = 1
                    HAVING soGK > 0 AND soGK = soGKDaCham
                ) as sub";
    $stmtDone = $conn->prepare($sqlDone);
    $stmtDone->execute([':idSK' => $idSK, ':idVongThi1' => $idVongThi, ':idVongThi1b' => $idVongThi, ':idVongThi2' => $idVongThi]);
    $daChamXong = $stmtDone->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Số sản phẩm đã duyệt
    $sqlApproved = "SELECT COUNT(*) as total FROM sanpham_vongthi spv
                    INNER JOIN sanpham sp ON spv.idSanPham = sp.idSanPham
                    WHERE sp.idSK = :idSK AND spv.idVongThi = :idVongThi AND spv.trangThai = 'Đã duyệt'";
    $stmtApproved = $conn->prepare($sqlApproved);
    $stmtApproved->execute([':idSK' => $idSK, ':idVongThi' => $idVongThi]);
    $daDuyet = $stmtApproved->fetch(PDO::FETCH_ASSOC)['total'];
    
    return [
        'tongSanPham' => (int) $tongSanPham,
        'daPhanCong' => (int) $daPhanCong,
        'daChamXong' => (int) $daChamXong,
        'daDuyet' => (int) $daDuyet,
        'phanTramPhanCong' => $tongSanPham > 0 ? round(($daPhanCong / $tongSanPham) * 100, 1) : 0,
        'phanTramChamXong' => $tongSanPham > 0 ? round(($daChamXong / $tongSanPham) * 100, 1) : 0,
        'phanTramDuyet' => $tongSanPham > 0 ? round(($daDuyet / $tongSanPham) * 100, 1) : 0
    ];
}

/**
 * Lấy chi tiết điểm chấm của sản phẩm theo từng giám khảo
 */
function cham_diem_lay_chi_tiet_diem($conn, $idSanPham, $idVongThi) {
    $sql = "SELECT 
                pcc.idGV,
                gv.tenGV,
                ct.idTieuChi,
                tc.noiDungTieuChi,
                ct.diem,
                ct.nhanXet,
                btc.diemToiDa,
                btc.tyTrong
            FROM chamtieuchi ct
            INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
            INNER JOIN giangvien gv ON pcc.idGV = gv.idGV
            INNER JOIN tieuchi tc ON ct.idTieuChi = tc.idTieuChi
            INNER JOIN botieuchi_tieuchi btc ON ct.idTieuChi = btc.idTieuChi AND pcc.idBoTieuChi = btc.idBoTieuChi
            WHERE ct.idSanPham = :idSanPham AND pcc.idVongThi = :idVongThi
            ORDER BY gv.tenGV, tc.idTieuChi";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tổ chức dữ liệu theo giám khảo
    $byJudge = [];
    foreach ($results as $row) {
        $idGV = $row['idGV'];
        if (!isset($byJudge[$idGV])) {
            $byJudge[$idGV] = [
                'idGV' => $idGV,
                'tenGV' => $row['tenGV'],
                'chiTiet' => [],
                'tongDiem' => 0
            ];
        }
        $byJudge[$idGV]['chiTiet'][] = [
            'idTieuChi' => $row['idTieuChi'],
            'noiDungTieuChi' => $row['noiDungTieuChi'],
            'diem' => (float) $row['diem'],
            'diemToiDa' => (float) $row['diemToiDa'],
            'nhanXet' => $row['nhanXet']
        ];
        $byJudge[$idGV]['tongDiem'] += (float) $row['diem'];
    }
    
    return array_values($byJudge);
}

/**
 * Tính toán IRR (Inter-Rater Reliability) với T-test hoặc ANOVA
 * 
 * @param array $diemTheoGK Mảng điểm theo giám khảo: [[gk1_tc1, gk1_tc2,...], [gk2_tc1, gk2_tc2,...], ...]
 * @return array Kết quả phân tích IRR
 */
function cham_diem_tinh_irr($diemTheoGK) {
    $n = count($diemTheoGK); // Số giám khảo
    
    if ($n < 2) {
        return [
            'phuongPhap' => 'N/A',
            'pValue' => null,
            'statistic' => null,
            'ketLuan' => 'Cần ít nhất 2 giám khảo để tính IRR',
            'canhBao' => false
        ];
    }
    
    // Tính điểm trung bình của mỗi giám khảo
    $diemTBTheoGK = [];
    foreach ($diemTheoGK as $index => $diem) {
        if (is_array($diem) && count($diem) > 0) {
            $diemTBTheoGK[$index] = array_sum($diem) / count($diem);
        }
    }
    
    // Tính điểm trung bình chung
    $tongDiem = array_sum($diemTBTheoGK);
    $diemTBChung = $n > 0 ? $tongDiem / $n : 0;
    
    // Tính độ lệch max-min
    $maxDiem = max($diemTBTheoGK);
    $minDiem = min($diemTBTheoGK);
    $doLechMaxMin = $maxDiem - $minDiem;
    
    // Kiểm tra cảnh báo: Độ lệch > 30% điểm trung bình
    $nguongCanhBao = $diemTBChung * 0.30;
    $canhBao = $doLechMaxMin >= $nguongCanhBao;
    
    if ($n === 2) {
        // Paired T-test cho 2 giám khảo
        $result = cham_diem_paired_ttest($diemTheoGK[0], $diemTheoGK[1]);
        return [
            'phuongPhap' => 'Paired T-test',
            'pValue' => $result['pValue'],
            'statistic' => $result['tStatistic'],
            'ketLuan' => $result['pValue'] < 0.05 
                ? 'Có sự khác biệt có ý nghĩa thống kê giữa 2 giám khảo (p < 0.05)' 
                : 'Không có sự khác biệt có ý nghĩa thống kê (p ≥ 0.05)',
            'canhBao' => $canhBao || ($result['pValue'] < 0.05),
            'doLechMaxMin' => round($doLechMaxMin, 2),
            'diemTBChung' => round($diemTBChung, 2),
            'diemTBTheoGK' => array_map(fn($d) => round($d, 2), $diemTBTheoGK)
        ];
    } else {
        // One-way ANOVA cho >= 3 giám khảo
        $result = cham_diem_one_way_anova($diemTheoGK);
        return [
            'phuongPhap' => 'One-way ANOVA',
            'pValue' => $result['pValue'],
            'statistic' => $result['fStatistic'],
            'ketLuan' => $result['pValue'] < 0.05 
                ? 'Có sự khác biệt có ý nghĩa thống kê giữa các giám khảo (p < 0.05)' 
                : 'Không có sự khác biệt có ý nghĩa thống kê (p ≥ 0.05)',
            'canhBao' => $canhBao || ($result['pValue'] < 0.05),
            'doLechMaxMin' => round($doLechMaxMin, 2),
            'diemTBChung' => round($diemTBChung, 2),
            'diemTBTheoGK' => array_map(fn($d) => round($d, 2), $diemTBTheoGK)
        ];
    }
}

/**
 * Paired T-test (Kiểm định T bắt cặp)
 */
function cham_diem_paired_ttest($group1, $group2) {
    $n = min(count($group1), count($group2));
    if ($n < 2) {
        return ['tStatistic' => 0, 'pValue' => 1];
    }
    
    // Tính chênh lệch từng cặp
    $differences = [];
    for ($i = 0; $i < $n; $i++) {
        $differences[] = $group1[$i] - $group2[$i];
    }
    
    // Tính trung bình và độ lệch chuẩn của chênh lệch
    $meanDiff = array_sum($differences) / $n;
    
    $sumSquaredDiff = 0;
    foreach ($differences as $d) {
        $sumSquaredDiff += pow($d - $meanDiff, 2);
    }
    $sdDiff = sqrt($sumSquaredDiff / ($n - 1));
    
    // Tính t-statistic
    if ($sdDiff == 0) {
        return ['tStatistic' => 0, 'pValue' => 1];
    }
    
    $tStatistic = $meanDiff / ($sdDiff / sqrt($n));
    
    // Tính p-value xấp xỉ (sử dụng normal approximation cho n lớn)
    $df = $n - 1;
    $pValue = cham_diem_t_to_p($tStatistic, $df);
    
    return [
        'tStatistic' => round($tStatistic, 4),
        'pValue' => round($pValue, 4)
    ];
}

/**
 * One-way ANOVA (Phân tích phương sai một yếu tố)
 */
function cham_diem_one_way_anova($groups) {
    $k = count($groups); // Số nhóm
    if ($k < 2) {
        return ['fStatistic' => 0, 'pValue' => 1];
    }
    
    // Tính tổng và trung bình chung
    $allValues = [];
    $groupMeans = [];
    $groupSizes = [];
    
    foreach ($groups as $i => $group) {
        $groupSizes[$i] = count($group);
        $groupMeans[$i] = $groupSizes[$i] > 0 ? array_sum($group) / $groupSizes[$i] : 0;
        $allValues = array_merge($allValues, $group);
    }
    
    $n = count($allValues);
    if ($n < 3) {
        return ['fStatistic' => 0, 'pValue' => 1];
    }
    
    $grandMean = array_sum($allValues) / $n;
    
    // Tính SSB (Sum of Squares Between groups)
    $ssb = 0;
    foreach ($groups as $i => $group) {
        $ssb += $groupSizes[$i] * pow($groupMeans[$i] - $grandMean, 2);
    }
    
    // Tính SSW (Sum of Squares Within groups)
    $ssw = 0;
    foreach ($groups as $i => $group) {
        foreach ($group as $value) {
            $ssw += pow($value - $groupMeans[$i], 2);
        }
    }
    
    // Degrees of freedom
    $dfBetween = $k - 1;
    $dfWithin = $n - $k;
    
    if ($dfWithin <= 0 || $ssw == 0) {
        return ['fStatistic' => 0, 'pValue' => 1];
    }
    
    // Mean squares
    $msBetween = $ssb / $dfBetween;
    $msWithin = $ssw / $dfWithin;
    
    // F-statistic
    $fStatistic = $msWithin > 0 ? $msBetween / $msWithin : 0;
    
    // Tính p-value xấp xỉ
    $pValue = cham_diem_f_to_p($fStatistic, $dfBetween, $dfWithin);
    
    return [
        'fStatistic' => round($fStatistic, 4),
        'pValue' => round($pValue, 4)
    ];
}

/**
 * Chuyển đổi t-statistic sang p-value (xấp xỉ)
 */
function cham_diem_t_to_p($t, $df) {
    // Sử dụng xấp xỉ normal cho df lớn
    if ($df > 30) {
        $z = abs($t);
        // Approximation của CDF normal
        $p = 1 / (1 + exp(-0.07056 * pow($z, 3) - 1.5976 * $z));
        return 2 * (1 - $p); // Two-tailed test
    }
    
    // Bảng tra cứu đơn giản cho df nhỏ
    $criticalValues = [
        1 => [6.314, 12.706, 63.657],
        2 => [2.920, 4.303, 9.925],
        3 => [2.353, 3.182, 5.841],
        4 => [2.132, 2.776, 4.604],
        5 => [2.015, 2.571, 4.032],
        10 => [1.812, 2.228, 3.169],
        20 => [1.725, 2.086, 2.845],
        30 => [1.697, 2.042, 2.750]
    ];
    
    $absT = abs($t);
    $dfKey = min(array_keys($criticalValues), function($a, $b) use ($df) {
        return abs($a - $df) - abs($b - $df);
    });
    
    if ($absT < $criticalValues[$dfKey][0]) return 0.20;
    if ($absT < $criticalValues[$dfKey][1]) return 0.10;
    if ($absT < $criticalValues[$dfKey][2]) return 0.05;
    return 0.01;
}

/**
 * Chuyển đổi F-statistic sang p-value (xấp xỉ)
 */
function cham_diem_f_to_p($f, $df1, $df2) {
    if ($f <= 0) return 1;
    
    // Xấp xỉ đơn giản dựa trên critical values
    // F-critical values at α=0.05 cho df1=1-10, df2=10-100
    if ($df1 >= 1 && $df2 >= 10) {
        $fCritical005 = 4.0 - ($df2 - 10) * 0.02; // Xấp xỉ
        $fCritical001 = 7.5 - ($df2 - 10) * 0.05;
        
        if ($f < $fCritical005) return 0.10;
        if ($f < $fCritical001) return 0.05;
        return 0.01;
    }
    
    return 0.05; // Default
}

/**
 * Lấy danh sách bài thi có cảnh báo độ lệch điểm
 */
function cham_diem_lay_danh_sach_canh_bao($conn, $idSK, $idVongThi) {
    $sanPhamList = cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi);
    $canhBaoList = [];
    
    foreach ($sanPhamList as $sp) {
        if ($sp['soGKDaCham'] >= 2) {
            $chiTietDiem = cham_diem_lay_chi_tiet_diem($conn, $sp['idSanPham'], $idVongThi);
            
            if (count($chiTietDiem) >= 2) {
                // Chuẩn bị mảng điểm theo giám khảo
                $diemTheoGK = [];
                foreach ($chiTietDiem as $gk) {
                    $diemTheoGK[] = array_column($gk['chiTiet'], 'diem');
                }
                
                $irr = cham_diem_tinh_irr($diemTheoGK);
                
                if ($irr['canhBao']) {
                    $canhBaoList[] = [
                        'idSanPham' => $sp['idSanPham'],
                        'tensanpham' => $sp['tensanpham'],
                        'tennhom' => $sp['tennhom'],
                        'soGiamKhao' => $sp['soGiamKhao'],
                        'irr' => $irr
                    ];
                }
            }
        }
    }
    
    return $canhBaoList;
}

/**
 * ==========================================
 * PHẦN 3: XÉT KẾT QUẢ & XẾP HẠNG
 * ==========================================
 */

/**
 * Tính điểm trung bình của sản phẩm từ tất cả giám khảo
 */
function cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi) {
    $sql = "SELECT AVG(sub.tongDiem) as diemTB FROM (
                SELECT pcc.idGV, SUM(ct.diem) as tongDiem
                FROM chamtieuchi ct
                INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                WHERE ct.idSanPham = :idSanPham AND pcc.idVongThi = :idVongThi
                GROUP BY pcc.idGV
            ) as sub";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['diemTB'] !== null ? round((float) $result['diemTB'], 2) : null;
}

/**
 * Duyệt và chốt điểm sản phẩm
 */
function cham_diem_duyet_diem($conn, $idSanPham, $idVongThi, $diemChot, $trangThai = 'Đang xét') {
    try {
        $sql = "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai, ngayCapNhat)
                VALUES (:idSanPham, :idVongThi, :diemChot, :trangThai, NOW())
                ON DUPLICATE KEY UPDATE 
                    diemTrungBinh = :diemChot2, 
                    trangThai = :trangThai2, 
                    ngayCapNhat = NOW()";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':idSanPham' => $idSanPham,
            ':idVongThi' => $idVongThi,
            ':diemChot' => $diemChot,
            ':trangThai' => $trangThai,
            ':diemChot2' => $diemChot,
            ':trangThai2' => $trangThai
        ]);
        
        return $result;
    } catch (Throwable $e) {
        error_log('Error in cham_diem_duyet_diem: ' . $e->getMessage());
        return false;
    }
}

/**
 * Duyệt điểm với kiểm tra quy chế
 */
function cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $diemChot, $idSK) {
    try {
        $conn->beginTransaction();
        
        // 1. Lưu điểm chốt với trạng thái tạm thời
        cham_diem_duyet_diem($conn, $idSanPham, $idVongThi, $diemChot, 'Đang xét');
        
        // 2. Kiểm tra quy chế vòng thi (nếu có)
        $datQuyChe = cham_diem_kiem_tra_quy_che($conn, $idSanPham, $idVongThi, $idSK);
        
        // 3. Cập nhật trạng thái cuối
        $trangThaiCuoi = $datQuyChe ? 'Đã duyệt' : 'Bị loại';
        
        $sqlUpdate = "UPDATE sanpham_vongthi SET trangThai = :trangThai, ngayCapNhat = NOW()
                      WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':trangThai' => $trangThaiCuoi,
            ':idSanPham' => $idSanPham,
            ':idVongThi' => $idVongThi
        ]);
        
        $conn->commit();
        
        return [
            'success' => true,
            'trangThai' => $trangThaiCuoi,
            'message' => $datQuyChe ? 'Bài thi đã được duyệt thành công' : 'Bài thi không đạt quy chế, đã bị loại'
        ];
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('Error in cham_diem_duyet_diem_voi_quyche: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi duyệt điểm'];
    }
}

/**
 * Kiểm tra quy chế vòng thi cho sản phẩm
 */
function cham_diem_kiem_tra_quy_che($conn, $idSanPham, $idVongThi, $idSK) {
    // Lấy quy chế loại VONGTHI của sự kiện
    $sqlQC = "SELECT qc.idQuyChe FROM quyche qc WHERE qc.idSK = :idSK AND qc.loaiQuyChe = 'VONGTHI' LIMIT 1";
    $stmtQC = $conn->prepare($sqlQC);
    $stmtQC->execute([':idSK' => $idSK]);
    $quyche = $stmtQC->fetch(PDO::FETCH_ASSOC);
    
    if (!$quyche) {
        // Không có quy chế => mặc định đạt
        return true;
    }
    
    // TODO: Gọi động cơ quy chế để kiểm tra
    // Hiện tại return true, cần tích hợp với hệ thống quy chế có sẵn
    return true;
}

/**
 * Đánh rớt bài thi thủ công
 */
function cham_diem_danh_rot_thu_cong($conn, $idSanPham, $idVongThi, $diemChot = null) {
    try {
        // Nếu không có điểm chốt, tính điểm trung bình
        if ($diemChot === null) {
            $diemChot = cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi);
        }
        
        $sql = "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai, ngayCapNhat)
                VALUES (:idSanPham, :idVongThi, :diemChot, 'Bị loại', NOW())
                ON DUPLICATE KEY UPDATE 
                    diemTrungBinh = :diemChot2, 
                    trangThai = 'Bị loại', 
                    ngayCapNhat = NOW()";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':idSanPham' => $idSanPham,
            ':idVongThi' => $idVongThi,
            ':diemChot' => $diemChot,
            ':diemChot2' => $diemChot
        ]);
        
        return ['success' => $result, 'message' => $result ? 'Đã đánh rớt bài thi thủ công' : 'Lỗi khi đánh rớt'];
    } catch (Throwable $e) {
        error_log('Error in cham_diem_danh_rot_thu_cong: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống'];
    }
}

/**
 * Lấy bảng xếp hạng (Bảng vàng)
 */
function cham_diem_lay_bang_xep_hang($conn, $idSK, $idVongThi) {
    $sql = "SELECT 
                sp.idSanPham,
                sp.tensanpham,
                n.manhom,
                ttn.tennhom,
                spv.diemTrungBinh,
                spv.trangThai,
                spv.xepLoai,
                (SELECT GROUP_CONCAT(sv2.tenSV SEPARATOR ', ') 
                 FROM thanhviennhom tvn 
                 INNER JOIN sinhvien sv2 ON tvn.idtk = sv2.idTK 
                 WHERE tvn.idnhom = n.idnhom AND tvn.trangthai = 1
                ) as thanhVien
            FROM sanpham_vongthi spv
            INNER JOIN sanpham sp ON spv.idSanPham = sp.idSanPham
            INNER JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            WHERE sp.idSK = :idSK 
              AND spv.idVongThi = :idVongThi 
              AND spv.trangThai = 'Đã duyệt'
              AND spv.diemTrungBinh IS NOT NULL
            ORDER BY spv.diemTrungBinh DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSK' => $idSK, ':idVongThi' => $idVongThi]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gán xếp hạng
    $rank = 1;
    foreach ($results as &$row) {
        $row['xepHang'] = $rank;
        $rank++;
    }
    
    return $results;
}

/**
 * Lấy tất cả bài thi với trạng thái chi tiết
 */
function cham_diem_lay_tat_ca_bai_thi($conn, $idSK, $idVongThi) {
    $sql = "SELECT 
                sp.idSanPham,
                sp.tensanpham,
                sp.TrangThai as trangThaiSP,
                n.manhom,
                ttn.tennhom,
                spv.diemTrungBinh,
                spv.trangThai as trangThaiVongThi,
                spv.xepLoai,
                spv.ngayCapNhat,
                -- Đếm số GK đã phân công HOẶC đã chấm (union)
                (SELECT COUNT(DISTINCT judgeId) FROM (
                    SELECT pd.idGV as judgeId FROM phancong_doclap pd 
                    WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1
                    UNION
                    SELECT pcc.idGV as judgeId FROM phancongcham pcc 
                    INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham 
                    WHERE ct.idSanPham = sp.idSanPham AND pcc.idVongThi = :idVongThi4
                ) as allJudges) as soGiamKhao,
                (SELECT COUNT(DISTINCT pcc.idGV) 
                 FROM phancongcham pcc 
                 INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                 WHERE pcc.idVongThi = :idVongThi2
                ) as soGKDaCham
            FROM sanpham sp
            INNER JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            LEFT JOIN sanpham_vongthi spv ON sp.idSanPham = spv.idSanPham AND spv.idVongThi = :idVongThi3
            WHERE sp.idSK = :idSK AND sp.isActive = 1
            ORDER BY 
                CASE spv.trangThai
                    WHEN 'Đã duyệt' THEN 1
                    WHEN 'Đang xét' THEN 2
                    WHEN 'Bị loại' THEN 3
                    ELSE 4
                END,
                spv.diemTrungBinh DESC,
                sp.tensanpham ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSK' => $idSK,
        ':idVongThi1' => $idVongThi,
        ':idVongThi2' => $idVongThi,
        ':idVongThi3' => $idVongThi,
        ':idVongThi4' => $idVongThi
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
