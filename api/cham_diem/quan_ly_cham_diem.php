<?php

/**
 * Quản lý chấm điểm - Logic xử lý nghiệp vụ
 *
 * Module này bao gồm:
 * - Phân công giám khảo chấm độc lập
 * - Tính toán tiến độ và IRR (Inter-Rater Reliability)
 * - Xét duyệt kết quả và xếp hạng
 *
 * Phụ thuộc (3 sub-modules):
 * - modules/score_analyzer.php   : Module 1 – tính điểm, % lệch, ma trận
 * - modules/statistical_test.php : Module 2 – T-test, ANOVA, p-value
 * - modules/warning_system.php   : Module 3 – cảnh báo tiêu chí & giám khảo
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

require_once __DIR__ . '/modules/score_analyzer.php';
require_once __DIR__ . '/modules/statistical_test.php';
require_once __DIR__ . '/modules/warning_system.php';
require_once __DIR__ . '/../su_kien/quan_ly_quy_che.php';

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
function cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi)
{
    $sql = "SELECT 
                sp.idSanPham,
                sp.tenSanPham AS tensanpham,
                sp.trangThai,
                n.maNhom AS manhom,
                ttn.tennhom,
                tkNT.tenTK as tenNhomTruong,
                CASE WHEN sv.tenSV IS NOT NULL THEN sv.tenSV ELSE gv.tenGV END as hoTenNhomTruong,
                spv.diemTrungBinh,
                spv.trangThai as trangThaiVongThi,
                spv.xepLoai,
                -- Đếm số GK CHÍNH đã phân công HOẶC đã chấm (không đếm trọng tài phúc khảo)
                -- LEFT JOIN phancong_doclap để bao gồm GK chấm trực tiếp qua phancongcham
                -- mà không có record phancong_doclap (COALESCE isTrongTai=0 → coi là GK chính)
                (SELECT COUNT(DISTINCT judgeId) FROM (
                    SELECT pd.idGV as judgeId FROM phancong_doclap pd 
                    WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1
                      AND pd.isTrongTai = 0
                    UNION
                    SELECT pcc.idGV as judgeId FROM phancongcham pcc 
                    INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham
                    LEFT JOIN phancong_doclap pd_f ON pd_f.idGV = pcc.idGV
                        AND pd_f.idVongThi = pcc.idVongThi
                        AND pd_f.idSanPham = ct.idSanPham
                    WHERE ct.idSanPham = sp.idSanPham AND pcc.idVongThi = :idVongThi4
                      AND COALESCE(pd_f.isTrongTai, 0) = 0
                ) as allJudges) as soGiamKhao,
                -- Đếm số GK CHÍNH đã chấm xong (không tính trọng tài phúc khảo)
                (SELECT COUNT(DISTINCT pcc.idGV) 
                 FROM phancongcham pcc 
                 INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                 LEFT JOIN phancong_doclap pd2 ON pd2.idGV = pcc.idGV AND pd2.idVongThi = pcc.idVongThi
                     AND pd2.idSanPham = ct.idSanPham
                 WHERE pcc.idSK = :idSK1 AND pcc.idVongThi = :idVongThi2
                   AND COALESCE(pd2.isTrongTai, 0) = 0
                ) as soGKDaCham
            FROM sanpham sp
            INNER JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            LEFT JOIN taikhoan tkNT ON n.idTruongNhom = tkNT.idTK
            LEFT JOIN sinhvien sv ON tkNT.idTK = sv.idTK
            LEFT JOIN giangvien gv ON tkNT.idTK = gv.idTK
            LEFT JOIN sanpham_vongthi spv ON sp.idSanPham = spv.idSanPham AND spv.idVongThi = :idVongThi3
            WHERE sp.idSK = :idSK2 
              AND sp.trangThai != 'BI_LOAI'
            ORDER BY sp.idSanPham ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSK1'       => $idSK,
        ':idSK2'       => $idSK,
        ':idVongThi1'  => $idVongThi,
        ':idVongThi4'  => $idVongThi,
        ':idVongThi2'  => $idVongThi,
        ':idVongThi3'  => $idVongThi,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách giảng viên có thể phân công chấm
 */
function cham_diem_lay_danh_sach_giang_vien($conn, $idSK = null)
{
    $sql = "SELECT 
                gv.idGV,
                gv.tenGV,
                tk.tenTK,
                k.tenKhoa,
                (SELECT COUNT(*) FROM phancong_doclap pd
                 INNER JOIN sanpham sp ON sp.idSanPham = pd.idSanPham
                 WHERE pd.idGV = gv.idGV AND (:idSK IS NULL OR sp.idSK = :idSK2)
                ) as soBaiDangCham
            FROM giangvien gv
            INNER JOIN taikhoan tk ON gv.idTK = tk.idTK
            LEFT JOIN khoa k ON gv.idKhoa = k.idKhoa
            WHERE tk.isActive = 1
            ORDER BY gv.tenGV ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy danh sách giám khảo đã phân công cho một sản phẩm
 * Sử dụng VIEW v_giam_khao_san_pham — thay thế UNION inline cũ (LOGIC-5)
 */
function cham_diem_lay_giam_khao_san_pham($conn, $idSanPham, $idVongThi)
{
    $sql = "SELECT
                v.idGV,
                v.tenGV,
                v.tenTK,
                v.nguon,
                v.isTrongTai,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM chamtieuchi ct
                        INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                        WHERE ct.idSanPham = :idSanPham1 AND pcc.idGV = v.idGV AND pcc.idVongThi = :idVongThi1
                    ) THEN 'Đã chấm'
                    ELSE 'Chưa chấm'
                END AS trangThaiCham,
                (SELECT AVG(ct.diem) FROM chamtieuchi ct
                 INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                 WHERE ct.idSanPham = :idSanPham2 AND pcc.idGV = v.idGV AND pcc.idVongThi = :idVongThi2
                ) AS diemTB
            FROM v_giam_khao_san_pham v
            WHERE v.idSanPham = :idSanPham3 AND v.idVongThi = :idVongThi3
            ORDER BY v.isTrongTai ASC, v.tenGV ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSanPham1' => $idSanPham,
        ':idVongThi1' => $idVongThi,
        ':idSanPham2' => $idSanPham,
        ':idVongThi2' => $idVongThi,
        ':idSanPham3' => $idSanPham,
        ':idVongThi3' => $idVongThi,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Phân công giám khảo chấm độc lập.
 *
 * Luồng đầy đủ (Sprint-1 fix):
 *  1. Kiểm tra đã phân công chưa
 *  2. Lấy idSK từ sanpham
 *  3. Lấy idBoTieuChi từ cauhinh_tieuchi_sk — báo lỗi nếu chưa cấu hình
 *  4. INSERT phancong_doclap  (theo dõi SP ↔ GV)
 *  5. INSERT phancongcham     (cấp quyền chấm + gắn bộ tiêu chí)
 *  6. Cập nhật sanpham_vongthi
 */
function cham_diem_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi)
{
    try {
        // 1. Kiểm tra trùng
        $sqlCheck = "SELECT 1 FROM phancong_doclap
                     WHERE idSanPham = :idSanPham AND idGV = :idGV AND idVongThi = :idVongThi";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Giám khảo này đã được phân công cho bài thi'];
        }

        // 2. Lấy idSK + idNhom từ sản phẩm
        $stmtSP = $conn->prepare("SELECT idSK, idNhom FROM sanpham WHERE idSanPham = :idSanPham AND trangThai != 'BI_LOAI' LIMIT 1");
        $stmtSP->execute([':idSanPham' => $idSanPham]);
        $spRow = $stmtSP->fetch(PDO::FETCH_ASSOC);
        if (!$spRow) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã bị xóa'];
        }
        $idSK   = (int) $spRow['idSK'];
        $idNhom = (int) $spRow['idNhom'];

        // 2.5. Kiểm tra xung đột GVHD — GV không được chấm nhóm mà họ đang hướng dẫn
        $stmtGVHD = $conn->prepare(
            "SELECT 1 FROM nhom_gvhd ng
             INNER JOIN giangvien gv ON gv.idTK = ng.idTK
             WHERE ng.idNhom = :idNhom AND gv.idGV = :idGV"
        );
        $stmtGVHD->execute([':idNhom' => $idNhom, ':idGV' => $idGV]);
        if ($stmtGVHD->fetch()) {
            return [
                'success' => false,
                'message' => 'Không thể phân công: Giảng viên này đang là GVHD của nhóm nộp sản phẩm này. '
                    . 'Quy tắc phản biện độc lập không cho phép GVHD chấm điểm nhóm của chính mình.',
            ];
        }

        // 3. Lấy bộ tiêu chí cấu hình cho vòng thi này
        $stmtBTC = $conn->prepare(
            "SELECT idBoTieuChi FROM cauhinh_tieuchi_sk
             WHERE idSK = :idSK AND idVongThi = :idVongThi LIMIT 1"
        );
        $stmtBTC->execute([':idSK' => $idSK, ':idVongThi' => $idVongThi]);
        $btcRow = $stmtBTC->fetch(PDO::FETCH_ASSOC);
        if (!$btcRow) {
            return [
                'success' => false,
                'message' => 'Vòng thi chưa được cấu hình bộ tiêu chí. '
                    . 'Vui lòng vào tab "Thiết lập bộ tiêu chí" để gán bộ tiêu chí cho vòng trước khi phân công.',
            ];
        }
        $idBoTieuChi = (int) $btcRow['idBoTieuChi'];

        $conn->beginTransaction();

        // 4. Ghi nhận phân công độc lập (SP ↔ GV)
        $sqlPD = "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi, isTrongTai)
                  VALUES (:idSanPham, :idGV, :idVongThi, 0)";
        $stmtPD = $conn->prepare($sqlPD);
        $stmtPD->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);

        // 4.5. Cấp vai trò GV_PHAN_BIEN (idVaiTro=2) trong sự kiện → bật tab scoring-gv
        //       Chỉ insert nếu chưa có record active để tránh duplicate.
        $stmtLookupTK = $conn->prepare("SELECT idTK FROM giangvien WHERE idGV = :idGV LIMIT 1");
        $stmtLookupTK->execute([':idGV' => $idGV]);
        $gvTKRow = $stmtLookupTK->fetch(PDO::FETCH_ASSOC);
        if ($gvTKRow) {
            $stmtTVS = $conn->prepare(
                "INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, nguonTao, isActive)
                 SELECT :idTK, :idSK, 2, 'PHANCONG_CHAM', 1
                 WHERE NOT EXISTS (
                     SELECT 1 FROM taikhoan_vaitro_sukien
                     WHERE idTK = :idTK2 AND idSK = :idSK2 AND idVaiTro = 2 AND isActive = 1
                 )"
            );
            $stmtTVS->execute([
                ':idTK'  => $gvTKRow['idTK'],
                ':idSK'  => $idSK,
                ':idTK2' => $gvTKRow['idTK'],
                ':idSK2' => $idSK,
            ]);
        }

        // 5. Cấp quyền chấm điểm — INSERT vào phancongcham nếu chưa có
        //    (GV có thể được phân công nhiều SP trong cùng vòng, nhưng chỉ cần 1 phancongcham/vòng)
        $stmtCheckPCC = $conn->prepare(
            "SELECT idPhanCongCham FROM phancongcham
             WHERE idGV = :idGV AND idSK = :idSK AND idVongThi = :idVongThi AND idBoTieuChi = :idBoTieuChi
             LIMIT 1"
        );
        $stmtCheckPCC->execute([
            ':idGV'        => $idGV,
            ':idSK'        => $idSK,
            ':idVongThi'   => $idVongThi,
            ':idBoTieuChi' => $idBoTieuChi,
        ]);
        if (!$stmtCheckPCC->fetch()) {
            $sqlPCC = "INSERT INTO phancongcham
                           (idGV, idSK, idVongThi, idBoTieuChi, trangThaiXacNhan, ngayXacNhan, isActive)
                       VALUES
                           (:idGV, :idSK, :idVongThi, :idBoTieuChi, 'Chờ chấm', '1000-01-01 00:00:00', 1)";
            $stmtPCC = $conn->prepare($sqlPCC);
            $stmtPCC->execute([
                ':idGV'        => $idGV,
                ':idSK'        => $idSK,
                ':idVongThi'   => $idVongThi,
                ':idBoTieuChi' => $idBoTieuChi,
            ]);
        }

        // 6. Cập nhật trạng thái sản phẩm vòng thi
        $sqlUpdateSPV = "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, trangThai, ngayCapNhat)
                         VALUES (:idSanPham, :idVongThi, 'Đã phân công', NOW())
                         ON DUPLICATE KEY UPDATE
                             trangThai    = IF(trangThai IS NULL OR trangThai = 'Đã nộp', 'Đã phân công', trangThai),
                             ngayCapNhat  = NOW()";
        $stmtSPV = $conn->prepare($sqlUpdateSPV);
        $stmtSPV->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);

        $conn->commit();
        return ['success' => true, 'message' => 'Phân công giám khảo thành công'];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Error in cham_diem_phan_cong_giam_khao: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi phân công'];
    }
}

/**
 * Gỡ phân công giám khảo
 */
function cham_diem_go_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi)
{
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

            // Thu hồi vai trò nếu GV không còn phân công bài nào trong sự kiện này
            $stmtGetSK = $conn->prepare("SELECT idSK FROM sanpham WHERE idSanPham = :idSanPham LIMIT 1");
            $stmtGetSK->execute([':idSanPham' => $idSanPham]);
            $skRow = $stmtGetSK->fetch(PDO::FETCH_ASSOC);
            if ($skRow) {
                $idSK_go = (int) $skRow['idSK'];
                $stmtConLai = $conn->prepare(
                    "SELECT COUNT(*) FROM phancong_doclap pd
                     INNER JOIN sanpham sp ON pd.idSanPham = sp.idSanPham
                     WHERE pd.idGV = :idGV AND sp.idSK = :idSK"
                );
                $stmtConLai->execute([':idGV' => $idGV, ':idSK' => $idSK_go]);
                if ((int) $stmtConLai->fetchColumn() === 0) {
                    $stmtGVTK = $conn->prepare("SELECT idTK FROM giangvien WHERE idGV = :idGV LIMIT 1");
                    $stmtGVTK->execute([':idGV' => $idGV]);
                    $gvTK = $stmtGVTK->fetch(PDO::FETCH_ASSOC);
                    if ($gvTK) {
                        $stmtRmRole = $conn->prepare(
                            "UPDATE taikhoan_vaitro_sukien SET isActive = 0
                             WHERE idTK = :idTK AND idSK = :idSK AND idVaiTro = 2
                               AND nguonTao = 'PHANCONG_CHAM'"
                        );
                        $stmtRmRole->execute([':idTK' => $gvTK['idTK'], ':idSK' => $idSK_go]);
                    }
                }
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
function cham_diem_moi_trong_tai($conn, $idSanPham, $idGV, $idVongThi)
{
    try {
        // Kiểm tra: GV này đang là GK chính của bài thi không? Không thể kiêm nhiệm 2 vai trò.
        $sqlCheckMain = "SELECT 1 FROM phancong_doclap
                         WHERE idSanPham = :idSanPham AND idGV = :idGV AND idVongThi = :idVongThi AND isTrongTai = 0";
        $stmtCheckMain = $conn->prepare($sqlCheckMain);
        $stmtCheckMain->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        if ($stmtCheckMain->fetch()) {
            return ['success' => false, 'message' => 'Giám khảo này đang là giám khảo chính của bài thi, không thể đồng thời làm Trọng tài phúc khảo'];
        }

        // Kiểm tra: GV này đã là trọng tài của bài thi này chưa?
        $sqlCheckTT = "SELECT 1 FROM phancong_doclap
                       WHERE idSanPham = :idSanPham AND idGV = :idGV AND idVongThi = :idVongThi AND isTrongTai = 1";
        $stmtCheckTT = $conn->prepare($sqlCheckTT);
        $stmtCheckTT->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        if ($stmtCheckTT->fetch()) {
            return ['success' => false, 'message' => 'Giám khảo này đã được mời làm Trọng tài phúc khảo cho bài thi này rồi'];
        }

        // Kiểm tra xung đột GVHD — trọng tài cũng không được là GVHD của nhóm nộp sản phẩm
        $stmtNhom = $conn->prepare("SELECT idNhom FROM sanpham WHERE idSanPham = :idSanPham LIMIT 1");
        $stmtNhom->execute([':idSanPham' => $idSanPham]);
        $nhomRow = $stmtNhom->fetch(PDO::FETCH_ASSOC);
        if ($nhomRow) {
            $stmtCheckGVHD = $conn->prepare(
                "SELECT 1 FROM nhom_gvhd ng
                 INNER JOIN giangvien gv ON gv.idTK = ng.idTK
                 WHERE ng.idNhom = :idNhom AND gv.idGV = :idGV"
            );
            $stmtCheckGVHD->execute([':idNhom' => $nhomRow['idNhom'], ':idGV' => $idGV]);
            if ($stmtCheckGVHD->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Không thể mời: Giảng viên này đang là GVHD của nhóm nộp sản phẩm này. '
                        . 'Quy tắc phản biện độc lập không cho phép GVHD làm trọng tài phúc khảo cho nhóm của chính mình.',
                ];
            }
        }

        $conn->beginTransaction();

        // 1. Ghi nhận phân công trọng tài vào bảng theo dõi độc lập. isTrongTai=1 = trọng tài phúc khảo
        $sqlInsert = "INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi, isTrongTai) VALUES (:idSanPham, :idGV, :idVongThi, 1)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':idSanPham' => $idSanPham, ':idGV' => $idGV, ':idVongThi' => $idVongThi]);

        // 1.5. Cấp vai trò GV_PHAN_BIEN (idVaiTro=2) cho trọng tài — để bật tab scoring-gv
        $stmtTTLookupSK = $conn->prepare(
            "SELECT sp.idSK FROM sanpham sp WHERE sp.idSanPham = :idSanPham LIMIT 1"
        );
        $stmtTTLookupSK->execute([':idSanPham' => $idSanPham]);
        $ttSKRow = $stmtTTLookupSK->fetch(PDO::FETCH_ASSOC);
        if ($ttSKRow) {
            $idSK_tt = (int) $ttSKRow['idSK'];
            $stmtLookupTK_tt = $conn->prepare("SELECT idTK FROM giangvien WHERE idGV = :idGV LIMIT 1");
            $stmtLookupTK_tt->execute([':idGV' => $idGV]);
            $gvTKRow_tt = $stmtLookupTK_tt->fetch(PDO::FETCH_ASSOC);
            if ($gvTKRow_tt) {
                $stmtTVS_tt = $conn->prepare(
                    "INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, nguonTao, isActive)
                     SELECT :idTK, :idSK, 2, 'PHANCONG_CHAM', 1
                     WHERE NOT EXISTS (
                         SELECT 1 FROM taikhoan_vaitro_sukien
                         WHERE idTK = :idTK2 AND idSK = :idSK2 AND idVaiTro = 2 AND isActive = 1
                     )"
                );
                $stmtTVS_tt->execute([
                    ':idTK'  => $gvTKRow_tt['idTK'],
                    ':idSK'  => $idSK_tt,
                    ':idTK2' => $gvTKRow_tt['idTK'],
                    ':idSK2' => $idSK_tt,
                ]);
            }
        }

        // 2. Cấp quyền chấm điểm thực sự: INSERT vào phancongcham
        //    Lấy idBoTieuChi và idSK từ một giám khảo chính đang phụ trách bài thi này
        //    (Phải JOIN với phancong_doclap để đảm bảo lấy đúng bộ tiêu chí của SP này,
        //     tránh lấy nhầm bộ tiêu chí của vòng thi khác không liên quan)
        $sqlGetBTC = "SELECT pcc.idBoTieuChi, pcc.idSK
                      FROM phancongcham pcc
                      INNER JOIN phancong_doclap pd ON pd.idGV = pcc.idGV
                          AND pd.idVongThi = pcc.idVongThi
                          AND pd.idSanPham = :idSanPham
                          AND pd.isTrongTai = 0
                      WHERE pcc.idVongThi = :idVongThi AND pcc.isActive = 1
                      LIMIT 1";
        $stmtGetBTC = $conn->prepare($sqlGetBTC);
        $stmtGetBTC->execute([':idVongThi' => $idVongThi, ':idSanPham' => $idSanPham]);
        $btcRow = $stmtGetBTC->fetch(PDO::FETCH_ASSOC);

        if ($btcRow) {
            // Thêm quyền chấm điểm cho trọng tài (tương tự giám khảo chính)
            $sqlPCC = "INSERT IGNORE INTO phancongcham
                           (idGV, idSK, idVongThi, idBoTieuChi, trangThaiXacNhan, ngayXacNhan, isActive)
                       VALUES
                           (:idGV, :idSK, :idVongThi, :idBoTieuChi, 'Đã xác nhận', NOW(), 1)";
            $stmtPCC = $conn->prepare($sqlPCC);
            $stmtPCC->execute([
                ':idGV'        => $idGV,
                ':idSK'        => $btcRow['idSK'],
                ':idVongThi'   => $idVongThi,
                ':idBoTieuChi' => $btcRow['idBoTieuChi'],
            ]);
        }

        // 3. Reset trạng thái bài thi về đang chấm để chờ điểm trọng tài
        $sqlReset = "UPDATE sanpham_vongthi
                     SET diemTrungBinh = NULL, trangThai = 'Đang chấm', xepLoai = NULL, ngayCapNhat = NOW()
                     WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
        $stmtReset = $conn->prepare($sqlReset);
        $stmtReset->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);

        $conn->commit();

        return [
            'success' => true,
            'message' => 'Đã mời Trọng tài thành công. Trọng tài có thể đăng nhập và chấm điểm ngay. Bài thi đã được reset về trạng thái đang chấm.',
            'data'    => ['phancongchamCreated' => !empty($btcRow)]
        ];
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('Error in cham_diem_moi_trong_tai: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi mời trọng tài: ' . $e->getMessage()];
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
function cham_diem_lay_thong_ke_tien_do($conn, $idSK, $idVongThi)
{
    // Tổng số sản phẩm cần chấm (loại trừ bị loại)
    $sqlTotal = "SELECT COUNT(*) as total FROM sanpham WHERE idSK = :idSK AND trangThai != 'BI_LOAI'";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->execute([':idSK' => $idSK]);
    $tongSanPham = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // Số sản phẩm có ít nhất 1 GK chính (isTrongTai=0) được phân công
    $sqlAssigned = "SELECT COUNT(DISTINCT pd.idSanPham) as total
                    FROM phancong_doclap pd
                    INNER JOIN sanpham sp ON pd.idSanPham = sp.idSanPham
                    WHERE sp.idSK = :idSK1 AND pd.idVongThi = :idVongThi1 AND pd.isTrongTai = 0";
    $stmtAssigned = $conn->prepare($sqlAssigned);
    $stmtAssigned->execute([':idSK1' => $idSK, ':idVongThi1' => $idVongThi]);
    $daPhanCong = $stmtAssigned->fetch(PDO::FETCH_ASSOC)['total'];

    // Số sản phẩm đã chấm xong (tất cả GK CHÍNH đã chấm — không chờ trọng tài)
    $sqlDone = "SELECT COUNT(*) as total FROM (
                    SELECT sp.idSanPham,
                           (SELECT COUNT(DISTINCT pd.idGV) FROM phancong_doclap pd 
                            WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1
                            AND pd.isTrongTai = 0) as soGKChinh,
                           (SELECT COUNT(DISTINCT pcc.idGV) 
                            FROM phancongcham pcc 
                            INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                            INNER JOIN phancong_doclap pd2 ON pd2.idGV = pcc.idGV AND pd2.idVongThi = pcc.idVongThi
                                AND pd2.idSanPham = ct.idSanPham AND pd2.isTrongTai = 0
                            WHERE pcc.idVongThi = :idVongThi2
                           ) as soGKChinhDaCham
                    FROM sanpham sp
                    WHERE sp.idSK = :idSK AND sp.trangThai != 'BI_LOAI'
                    HAVING soGKChinh > 0 AND soGKChinh = soGKChinhDaCham
                ) as sub";
    $stmtDone = $conn->prepare($sqlDone);
    $stmtDone->execute([':idSK' => $idSK, ':idVongThi1' => $idVongThi, ':idVongThi2' => $idVongThi]);
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
function cham_diem_lay_chi_tiet_diem($conn, $idSanPham, $idVongThi)
{
    // JOIN với phancong_doclap để lấy cờ isTrongTai chính xác từ DB
    // isTrongTai=0: giám khảo chính (từ cham_diem_phan_cong_giam_khao)
    // isTrongTai=1: trọng tài phúc khảo (từ cham_diem_moi_trong_tai)
    $sql = "SELECT 
                pcc.idGV,
                gv.tenGV,
                ct.idTieuChi,
                tc.noiDungTieuChi,
                ct.diem,
                ct.nhanXet,
                btc.diemToiDa,
                btc.tyTrong,
                COALESCE(pd.isTrongTai, 0) AS isTrongTai
            FROM chamtieuchi ct
            INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
            INNER JOIN giangvien gv ON pcc.idGV = gv.idGV
            INNER JOIN tieuchi tc ON ct.idTieuChi = tc.idTieuChi
            INNER JOIN botieuchi_tieuchi btc ON ct.idTieuChi = btc.idTieuChi AND pcc.idBoTieuChi = btc.idBoTieuChi
            LEFT JOIN phancong_doclap pd ON pd.idGV = pcc.idGV AND pd.idVongThi = pcc.idVongThi AND pd.idSanPham = ct.idSanPham
            WHERE ct.idSanPham = :idSanPham AND pcc.idVongThi = :idVongThi
            ORDER BY isTrongTai ASC, gv.tenGV, tc.idTieuChi";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổ chức dữ liệu theo giám khảo
    $byJudge = [];
    foreach ($results as $row) {
        $idGV = $row['idGV'];
        if (!isset($byJudge[$idGV])) {
            $byJudge[$idGV] = [
                'idGV'       => $idGV,
                'tenGV'      => $row['tenGV'],
                'isTrongTai' => (bool) $row['isTrongTai'],
                'chiTiet'    => [],
                'tongDiem'   => 0
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
 * Lấy danh sách trọng tài đã được phân công cho một sản phẩm
 * (kể cả chưa chấm điểm – chỉ cần có trong phancong_doclap với isTrongTai=1)
 */
function cham_diem_lay_trong_tai_phan_cong($conn, $idSanPham, $idVongThi)
{
    $sql = "SELECT pd.idGV, gv.tenGV, pd.isTrongTai
            FROM phancong_doclap pd
            INNER JOIN giangvien gv ON gv.idGV = pd.idGV
            WHERE pd.idSanPham = :idSanPham
              AND pd.idVongThi = :idVongThi
              AND pd.isTrongTai = 1
            ORDER BY pd.idGV ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Tính toán IRR – wrapper gọi Module 2 (statistical_test.php)
 * Giữ nguyên tên hàm để các code cũ không cần sửa.
 *
 * @param array $diemTheoGK Mảng điểm theo giám khảo: [[tc1,tc2,...], ...]
 * @return array Kết quả phân tích IRR
 */
function cham_diem_tinh_irr($diemTheoGK)
{
    return stat_test_irr($diemTheoGK);
}

/**
 * Paired T-test – wrapper gọi Module 2
 */
function cham_diem_paired_ttest($group1, $group2)
{
    return stat_test_paired_ttest(
        array_map('floatval', $group1),
        array_map('floatval', $group2)
    );
}

/**
 * One-way ANOVA – wrapper gọi Module 2
 */
function cham_diem_one_way_anova($groups)
{
    return stat_test_one_way_anova($groups);
}

/**
 * t → p-value – wrapper gọi Module 2
 */
function cham_diem_t_to_p($t, $df)
{
    return stat_test_t_to_p((float) $t, (int) $df);
}

/**
 * F → p-value – wrapper gọi Module 2
 * (kept for backward compatibility; the old inline code is replaced below)
 */
// NOTE: cham_diem_f_to_p is now supplied by stat_test_f_to_p through a shim
function cham_diem_f_to_p($f, $df1, $df2)
{
    return stat_test_f_to_p((float) $f, (int) $df1, (int) $df2);
}

/**
 * Batch-fetch toàn bộ điểm chấm của tất cả SP trong 1 vòng thi (1 query thay vì N)
 * Trả về: [ idSanPham => [ [...judge], ... ], ... ]
 */
function cham_diem_lay_chi_tiet_diem_batch($conn, $idSK, $idVongThi)
{
    $sql = "SELECT
                ct.idSanPham,
                pcc.idGV,
                gv.tenGV,
                ct.idTieuChi,
                tc.noiDungTieuChi,
                ct.diem,
                ct.nhanXet,
                btc.diemToiDa,
                COALESCE(pd.isTrongTai, 0) AS isTrongTai
            FROM chamtieuchi ct
            INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
            INNER JOIN giangvien gv     ON pcc.idGV           = gv.idGV
            INNER JOIN sanpham  sp     ON ct.idSanPham        = sp.idSanPham
            INNER JOIN tieuchi  tc     ON ct.idTieuChi        = tc.idTieuChi
            INNER JOIN botieuchi_tieuchi btc
                    ON btc.idTieuChi = ct.idTieuChi AND btc.idBoTieuChi = pcc.idBoTieuChi
            LEFT  JOIN phancong_doclap pd
                    ON pd.idGV      = pcc.idGV
                   AND pd.idVongThi = pcc.idVongThi
                   AND pd.idSanPham = ct.idSanPham
            WHERE sp.idSK = :idSK AND pcc.idVongThi = :idVongThi
            ORDER BY ct.idSanPham,
                     COALESCE(pd.isTrongTai, 0) ASC,
                     gv.tenGV,
                     tc.idTieuChi";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSK' => $idSK, ':idVongThi' => $idVongThi]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group: idSanPham -> idGV -> chi tiết
    $byProduct = [];
    foreach ($rows as $row) {
        $idSP = (int) $row['idSanPham'];
        $idGV = (int) $row['idGV'];

        if (!isset($byProduct[$idSP][$idGV])) {
            $byProduct[$idSP][$idGV] = [
                'idGV'       => $idGV,
                'tenGV'      => $row['tenGV'],
                'isTrongTai' => (bool) $row['isTrongTai'],
                'chiTiet'    => [],
                'tongDiem'   => 0.0,
            ];
        }
        $byProduct[$idSP][$idGV]['chiTiet'][] = [
            'idTieuChi'      => $row['idTieuChi'],
            'noiDungTieuChi' => $row['noiDungTieuChi'],
            'diem'           => (float) $row['diem'],
            'diemToiDa'      => (float) $row['diemToiDa'],
            'nhanXet'        => $row['nhanXet'],
        ];
        $byProduct[$idSP][$idGV]['tongDiem'] += (float) $row['diem'];
    }

    // Đảo key idGV -> indexed array
    foreach ($byProduct as &$judgeMap) {
        $judgeMap = array_values($judgeMap);
    }
    unset($judgeMap);

    return $byProduct;
}

/**
 * Lấy danh sách bài thi có cảnh báo độ lệch điểm
 * Sử dụng batch query (2 queries tổng cộng) — thay thế vòng lặp N+1 cũ (LOGIC-1)
 */
function cham_diem_lay_danh_sach_canh_bao($conn, $idSK, $idVongThi)
{
    $sanPhamList = cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi);

    // 1 query lấy toàn bộ điểm cho cả vòng thi
    $batchScores = cham_diem_lay_chi_tiet_diem_batch($conn, $idSK, $idVongThi);

    $canhBaoList = [];

    foreach ($sanPhamList as $sp) {
        // KHÔNG dùng soGKDaCham để skip sớm — soGKDaCham chỉ đếm GK có record trong
        // phancong_doclap, bỏ sót các GK chấm trực tiếp qua phancongcham. Thay vào đó
        // dùng count($chiTietChinh) bên dưới (dựa trên dữ liệu thực từ batch query).

        $chiTietDiem = $batchScores[(int) $sp['idSanPham']] ?? [];

        // Chỉ tính IRR trên GK chính (isTrongTai=0)
        $chiTietChinh = array_values(
            array_filter($chiTietDiem, fn($gk) => empty($gk['isTrongTai']))
        );
        if (count($chiTietChinh) < 2) continue;

        $diemTheoGK = array_map(
            fn($gk) => array_column($gk['chiTiet'], 'diem'),
            $chiTietChinh
        );
        $irr = cham_diem_tinh_irr($diemTheoGK);

        if ($irr['canhBao']) {
            $canhBaoList[] = [
                'idSanPham'  => $sp['idSanPham'],
                'tensanpham' => $sp['tensanpham'], // alias từ SQL: tenSanPham AS tensanpham
                'tennhom'    => $sp['tennhom'],
                'soGiamKhao' => $sp['soGiamKhao'],
                'irr'        => $irr,
            ];
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
function cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi)
{
    // Kiểm tra: có trọng tài phúc khảo đã nộp phiếu cho SP này không?
    // Nếu có → điểm của TT là phán quyết cuối cùng (binding arbitration), không lấy AVG GK chính.
    $sqlTT = "SELECT pcc.idPhanCongCham
              FROM phancong_doclap pd
              INNER JOIN phancongcham pcc ON pcc.idGV = pd.idGV
                  AND pcc.idVongThi = pd.idVongThi AND pcc.isActive = 1
              INNER JOIN sanpham sp ON sp.idSanPham = pd.idSanPham
              WHERE pd.idSanPham = :idSanPham
                AND pd.idVongThi = :idVongThi
                AND pd.isTrongTai = 1
                AND pd.trangThaiCham = 'Đã xác nhận'
                AND pcc.idSK = sp.idSK
              LIMIT 1";
    $stmtTT = $conn->prepare($sqlTT);
    $stmtTT->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    $ttRow = $stmtTT->fetch(PDO::FETCH_ASSOC);

    if ($ttRow) {
        // Dùng tổng điểm của TT làm điểm chốt
        $sqlDiemTT = "SELECT SUM(diem) as tongDiem
                      FROM chamtieuchi
                      WHERE idPhanCongCham = :idPCC AND idSanPham = :idSanPham";
        $stmtDiemTT = $conn->prepare($sqlDiemTT);
        $stmtDiemTT->execute([':idPCC' => $ttRow['idPhanCongCham'], ':idSanPham' => $idSanPham]);
        $diemTT = $stmtDiemTT->fetch(PDO::FETCH_ASSOC);
        return $diemTT['tongDiem'] !== null ? round((float) $diemTT['tongDiem'], 2) : null;
    }

    // Không có TT → lấy AVG tổng điểm từ GK chính (isTrongTai=0) như logic gốc
    $sql = "SELECT AVG(sub.tongDiem) as diemTB FROM (
                SELECT pcc.idGV, SUM(ct.diem) as tongDiem
                FROM chamtieuchi ct
                INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
                INNER JOIN phancong_doclap pd ON pd.idGV = pcc.idGV
                    AND pd.idVongThi = pcc.idVongThi
                    AND pd.idSanPham = ct.idSanPham
                    AND pd.isTrongTai = 0
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
function cham_diem_duyet_diem($conn, $idSanPham, $idVongThi, $diemChot, $trangThai = 'Đang xét')
{
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
function cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $diemChot, $idSK, $maNguCanh = 'DUYET_VONG_THI')
{
    try {
        $conn->beginTransaction();

        // 1. Lưu điểm chốt với trạng thái tạm thời
        cham_diem_duyet_diem($conn, $idSanPham, $idVongThi, $diemChot, 'Đang xét');

        // 2. Kiểm tra quy chế vòng thi (nếu có)
        $ketQuaQuyChe = cham_diem_kiem_tra_quy_che($conn, $idSanPham, $idVongThi, $idSK, $maNguCanh);
        $datQuyChe = !empty($ketQuaQuyChe['hopLe']);

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
            'message' => $datQuyChe ? 'Bài thi đã được duyệt thành công' : 'Bài thi không đạt quy chế, đã bị loại',
            'quyChe' => $ketQuaQuyChe,
        ];
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('Error in cham_diem_duyet_diem_voi_quyche: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi duyệt điểm'];
    }
}

/**
 * Kiểm tra quy chế vòng thi cho sản phẩm.
 * Dùng chung engine xet_duyet_quy_che_theo_ngucanh để tránh 2 luồng logic song song.
 */
function cham_diem_kiem_tra_quy_che($conn, $idSanPham, $idVongThi, $idSK, $maNguCanh = 'DUYET_VONG_THI')
{
    $idSanPham = (int) $idSanPham;
    $idVongThi = (int) $idVongThi;
    $idSK = (int) $idSK;

    if ($idSanPham <= 0 || $idVongThi <= 0 || $idSK <= 0) {
        return [
            'hopLe' => false,
            'message' => 'Thiếu dữ liệu để kiểm tra quy chế duyệt vòng',
            'tongQuyChe' => 0,
            'viPham' => [],
        ];
    }

    return xet_duyet_quy_che_theo_ngucanh(
        $conn,
        $idSK,
        $maNguCanh,
        [
            'idSanPham' => $idSanPham,
            'idVongThi' => $idVongThi,
        ]
    );
}

/**
 * Áp dụng hạn mức số bài "Đã duyệt" cho một vòng thi.
 * Giữ lại top điểm cao nhất, các bài vượt hạn mức sẽ chuyển thành "Bị loại".
 */
function cham_diem_ap_dung_han_muc_duyet($conn, $idVongThi, $hanMuc = 10)
{
    $idVongThi = (int) $idVongThi;
    $hanMuc = (int) $hanMuc;

    if (!$conn instanceof PDO || $idVongThi <= 0) {
        return [
            'applied' => false,
            'message' => 'Thiếu dữ liệu để áp dụng hạn mức duyệt',
            'hanMuc' => $hanMuc,
            'tongDaDuyet' => 0,
            'giuLai' => 0,
            'biLoaiBoiHanMuc' => 0,
        ];
    }

    if ($hanMuc < 0) {
        $hanMuc = 0;
    }

    try {
        $stmt = $conn->prepare(
            "SELECT idSanPham, diemTrungBinh, ngayCapNhat
             FROM sanpham_vongthi
             WHERE idVongThi = :idVongThi
               AND trangThai = 'Đã duyệt'
               AND diemTrungBinh IS NOT NULL
             ORDER BY diemTrungBinh DESC, ngayCapNhat ASC, idSanPham ASC"
        );
        $stmt->execute([':idVongThi' => $idVongThi]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $tongDaDuyet = count($rows);
        if ($tongDaDuyet <= $hanMuc) {
            return [
                'applied' => true,
                'message' => 'Không vượt hạn mức duyệt',
                'hanMuc' => $hanMuc,
                'tongDaDuyet' => $tongDaDuyet,
                'giuLai' => $tongDaDuyet,
                'biLoaiBoiHanMuc' => 0,
            ];
        }

        $keepIds = [];
        for ($i = 0; $i < $hanMuc && $i < $tongDaDuyet; $i++) {
            $keepIds[] = (int) ($rows[$i]['idSanPham'] ?? 0);
        }
        $keepIds = array_values(array_filter($keepIds, fn($id) => $id > 0));

        if (empty($keepIds)) {
            $stmtDropAll = $conn->prepare(
                "UPDATE sanpham_vongthi
                 SET trangThai = 'Bị loại', ngayCapNhat = NOW()
                 WHERE idVongThi = :idVongThi AND trangThai = 'Đã duyệt'"
            );
            $stmtDropAll->execute([':idVongThi' => $idVongThi]);
        } else {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $sqlDropOverflow =
                "UPDATE sanpham_vongthi
                 SET trangThai = 'Bị loại', ngayCapNhat = NOW()
                 WHERE idVongThi = ?
                   AND trangThai = 'Đã duyệt'
                   AND idSanPham NOT IN ({$placeholders})";

            $stmtDropOverflow = $conn->prepare($sqlDropOverflow);
            $stmtDropOverflow->execute(array_merge([$idVongThi], $keepIds));
        }

        $giuLai = min($tongDaDuyet, $hanMuc);
        return [
            'applied' => true,
            'message' => 'Đã áp dụng hạn mức duyệt theo vòng thi',
            'hanMuc' => $hanMuc,
            'tongDaDuyet' => $tongDaDuyet,
            'giuLai' => $giuLai,
            'biLoaiBoiHanMuc' => $tongDaDuyet - $giuLai,
        ];
    } catch (Throwable $exception) {
        error_log('Error in cham_diem_ap_dung_han_muc_duyet: ' . $exception->getMessage());
        return [
            'applied' => false,
            'message' => 'Lỗi hệ thống khi áp dụng hạn mức duyệt',
            'hanMuc' => $hanMuc,
            'tongDaDuyet' => 0,
            'giuLai' => 0,
            'biLoaiBoiHanMuc' => 0,
        ];
    }
}

/**
 * Đánh rớt bài thi thủ công
 */
function cham_diem_danh_rot_thu_cong($conn, $idSanPham, $idVongThi, $diemChot = null)
{
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
 * Hủy duyệt / Hủy loại — reset trangThai về NULL (trạng thái chờ xét lại)
 */
function cham_diem_huy_duyet($conn, $idSanPham, $idVongThi)
{
    try {
        $sql = "UPDATE sanpham_vongthi 
                SET trangThai = NULL, ngayCapNhat = NOW()
                WHERE idSanPham = :idSanPham AND idVongThi = :idVongThi";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':idSanPham' => $idSanPham, ':idVongThi' => $idVongThi]);
    } catch (Throwable $e) {
        error_log('Error in cham_diem_huy_duyet: ' . $e->getMessage());
        return false;
    }
}

/**
 * Lấy bảng xếp hạng (Bảng vàng)
 */
function cham_diem_lay_bang_xep_hang($conn, $idSK, $idVongThi)
{
    $sql = "SELECT 
                sp.idSanPham,
                sp.tenSanPham AS tensanpham,
                n.maNhom AS manhom,
                ttn.tennhom,
                spv.diemTrungBinh,
                spv.trangThai,
                spv.xepLoai,
                (SELECT GROUP_CONCAT(sv2.tenSV SEPARATOR ', ') 
                 FROM thanhviennhom tvn 
                 INNER JOIN sinhvien sv2 ON tvn.idTK = sv2.idTK 
                 WHERE tvn.idNhom = n.idNhom
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

    // Gán xếp hạng theo chuẩn Competition Ranking (standard)
    // Bài cùng điểm được cùng hạng, hạng tiếp theo bị nhảy
    // Ví dụ: 3 bài cùng #1 → hạng tiếp là #4 (không phải #2)
    $rank = 1;
    $i    = 0;
    $n    = count($results);
    while ($i < $n) {
        // Tìm nhóm cùng điểm
        $j = $i;
        while ($j < $n && $results[$j]['diemTrungBinh'] == $results[$i]['diemTrungBinh']) {
            $j++;
        }
        // Gán cùng hạng cho toàn bộ nhóm
        for ($k = $i; $k < $j; $k++) {
            $results[$k]['xepHang'] = $rank;
        }
        $rank += ($j - $i); // Nhảy qua số lượng bài đồng hạng
        $i = $j;
    }

    return $results;
}

/**
 * Lấy tất cả bài thi với trạng thái chi tiết
 */
function cham_diem_lay_tat_ca_bai_thi($conn, $idSK, $idVongThi)
{
    $sql = "SELECT 
                sp.idSanPham,
                sp.tenSanPham AS tensanpham,
                sp.trangThai as trangThaiSP,
                n.maNhom AS manhom,
                ttn.tennhom,
                spv.diemTrungBinh,
                spv.trangThai as trangThaiVongThi,
                spv.xepLoai,
                spv.ngayCapNhat,
                -- GK chính: ưu tiên phancong_doclap, nhưng fallback sang GK đã chấm qua
                -- phancongcham mà không có record phancong_doclap (COALESCE isTrongTai=0)
                (SELECT COUNT(DISTINCT judgeId) FROM (
                    SELECT pd.idGV as judgeId FROM phancong_doclap pd
                    WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi1 AND pd.isTrongTai = 0
                    UNION
                    SELECT pcc.idGV as judgeId FROM phancongcham pcc
                    INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham
                    LEFT JOIN phancong_doclap pd_f ON pd_f.idGV = pcc.idGV
                        AND pd_f.idVongThi = pcc.idVongThi
                        AND pd_f.idSanPham = ct.idSanPham
                    WHERE ct.idSanPham = sp.idSanPham AND pcc.idVongThi = :idVongThi1b
                      AND COALESCE(pd_f.isTrongTai, 0) = 0
                ) as allGK) as soGiamKhao,
                -- Trọng tài phúc khảo đã mời (isTrongTai=1)
                (SELECT COUNT(DISTINCT pd.idGV) FROM phancong_doclap pd 
                 WHERE pd.idSanPham = sp.idSanPham AND pd.idVongThi = :idVongThi_tt AND pd.isTrongTai = 1) as soTrongTai,
                -- GK chính đã chấm xong — LEFT JOIN để bao gồm GK chấm trực tiếp
                (SELECT COUNT(DISTINCT pcc.idGV) 
                 FROM phancongcham pcc 
                 INNER JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = sp.idSanPham
                 LEFT JOIN phancong_doclap pd2 ON pd2.idGV = pcc.idGV AND pd2.idVongThi = pcc.idVongThi
                     AND pd2.idSanPham = ct.idSanPham
                 WHERE pcc.idVongThi = :idVongThi2
                   AND COALESCE(pd2.isTrongTai, 0) = 0
                ) as soGKDaCham
            FROM sanpham sp
            INNER JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            LEFT JOIN sanpham_vongthi spv ON sp.idSanPham = spv.idSanPham AND spv.idVongThi = :idVongThi3
            WHERE sp.idSK = :idSK AND sp.trangThai != 'BI_LOAI'
            ORDER BY 
                CASE spv.trangThai
                    WHEN 'Đã duyệt' THEN 1
                    WHEN 'Đang xét' THEN 2
                    WHEN 'Bị loại' THEN 3
                    ELSE 4
                END,
                spv.diemTrungBinh DESC,
                sp.tenSanPham ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idSK' => $idSK,
        ':idVongThi1' => $idVongThi,
        ':idVongThi1b' => $idVongThi,
        ':idVongThi_tt' => $idVongThi,
        ':idVongThi2' => $idVongThi,
        ':idVongThi3' => $idVongThi
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
