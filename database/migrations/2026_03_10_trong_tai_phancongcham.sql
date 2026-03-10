-- ============================================================
-- Migration: 2026_03_10_trong_tai_phancongcham.sql
--
-- Mục tiêu:
--   Khi mời Trọng tài phúc khảo (add_3rd_judge), ứng dụng giờ
--   sẽ tự INSERT một hàng vào phancongcham để cấp quyền chấm
--   điểm thực sự cho trọng tài (không chỉ ghi nhận vào
--   phancong_doclap như trước).
--
--   Migration này không thay đổi schema; chỉ đảm bảo dữ liệu
--   sạch và thêm index hỗ trợ truy vấn tra cứu nhanh hơn.
-- ============================================================

-- 1. Đảm bảo UNIQUE constraint tồn tại trên phancong_doclap
--    (thường đã có do composite PK, nhưng thêm rõ ràng để tránh duplicate)
-- ALTER TABLE phancong_doclap
--   ADD UNIQUE KEY IF NOT EXISTS uq_doclap (idSanPham, idGV, idVongThi);

-- 2. Index hỗ trợ tra cứu phancongcham để lấy idBoTieuChi nhanh
--    khi mời trọng tài (dùng trong cham_diem_moi_trong_tai)
-- 2. Index hỗ trợ tra cứu phancongcham để lấy idBoTieuChi nhanh
-- Lưu ý: Nếu index này đã tồn tại, hãy bỏ qua câu lệnh này để tránh lỗi "Duplicate key name"
CREATE INDEX idx_pcc_vongthi_active 
    ON phancongcham (idVongThi, isActive);

-- 3. Cấp quyền chấm điểm cho các trọng tài đã được mời
INSERT IGNORE INTO phancongcham
    (idGV, idSK, idVongThi, idBoTieuChi, trangThaiXacNhan, ngayXacNhan, isActive)
SELECT
    pd.idGV,
    ref.idSK,
    pd.idVongThi,
    ref.idBoTieuChi,
    'Đã xác nhận',
    NOW(),
    1
FROM phancong_doclap pd
-- Chỉ xử lý các GV CHƯA có quyền chấm trong vòng thi này
LEFT JOIN phancongcham existing_pcc
    ON existing_pcc.idGV       = pd.idGV
   AND existing_pcc.idVongThi  = pd.idVongThi
   AND existing_pcc.isActive   = 1
-- Lấy thông tin idSK + idBoTieuChi từ một phân công hiện có (sử dụng MIN() để vượt qua ONLY_FULL_GROUP_BY)
INNER JOIN (
    SELECT 
        idVongThi, 
        MIN(idSK) AS idSK, 
        MIN(idBoTieuChi) AS idBoTieuChi
    FROM phancongcham
    WHERE isActive = 1
    GROUP BY idVongThi
) ref ON ref.idVongThi = pd.idVongThi
WHERE existing_pcc.idPhanCongCham IS NULL;

-- ============================================================
-- Ghi chú:
--   - Sau khi chạy migration này, tất cả trọng tài đã được mời
--     (có trong phancong_doclap nhưng không có trong phancongcham)
--     sẽ được cấp quyền chấm điểm.
--   - Đối với các trọng tài mới mời sau ngày này, code PHP trong
--     cham_diem_moi_trong_tai() đã tự động xử lý.
-- ============================================================
