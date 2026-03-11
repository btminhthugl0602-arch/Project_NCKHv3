-- Migration: Thêm cột isTrongTai vào bảng phancong_doclap
-- Mục đích: Phân biệt giám khảo chính (được gán qua cham_diem_phan_cong_giam_khao)
--           với trọng tài phúc khảo (được mời qua cham_diem_moi_trong_tai)
-- Trước đây cả 2 loại đều dùng chung bảng phancong_doclap mà không có flag phân biệt,
-- khiến isTrongTai detection trong PHP bị sai (dùng CASE WHEN IS NOT NULL).
--
-- QUAN TRỌNG: Cột isTrongTai đã tồn tại trong DB (được thêm từ trước).
--             Bỏ qua bước ALTER TABLE nếu chạy lại migration này.
-- PHP code đã được fix: COALESCE(pd.isTrongTai, 0) thay vì CASE WHEN pd.idGV IS NOT NULL
-- Date: 2026-03-10

-- Bước 1: Thêm cột isTrongTai (bỏ qua nếu đã có)
-- ALTER TABLE `phancong_doclap`
--     ADD COLUMN `isTrongTai` TINYINT NOT NULL DEFAULT 0
--     COMMENT '0 = Giám khảo chính thức (phan_cong_giam_khao), 1 = Trọng tài phúc khảo (moi_trong_tai)'
--     AFTER `idVongThi`;

-- Bước 2: Sửa GV 1 cho sanpham 991, vong 999
-- GV 1 được thêm vào phancong_doclap (991, 1, 999) với tư cách trọng tài (moi_trong_tai scenario)
-- nhưng migration 2026_03_10_trong_tai_phancongcham.sql backfill -> thêm vào phancongcham
-- => isTrongTai hiện là 0 (sai). Cần sửa về 1.
UPDATE `phancong_doclap`
SET isTrongTai = 1
WHERE idSanPham = 991 AND idGV = 1 AND idVongThi = 999;

-- Kết quả sau migration này:
-- phancong_doclap(991, 1, 999)   => isTrongTai=1 (trọng tài thực sự cho SP 991) ✓
-- phancong_doclap(991, 902, 999) => isTrongTai=0 (GK chính - test data, đã chấm) ✓  
-- phancong_doclap(992, 3, 999)   => isTrongTai=1 (trọng tài, chưa có phancongcham, chưa chấm)
-- Tất cả entries SK 1, SK 11, SK 500 => isTrongTai=0 (GK chính, đã đúng) ✓

-- [TÙY CHỌN] Dọn dẹp test data bẩn trong SK 999:
-- GV 902 cho sanpham 991: là GK chính nhưng có entry trong phancong_doclap (test data từ thủ công)
-- Nếu muốn xóa bỏ entry sai này:
-- DELETE FROM phancong_doclap WHERE idSanPham = 991 AND idGV = 902 AND idVongThi = 999;

-- Lưu ý GV 3 (sanpham 992, isTrongTai=1): trọng tài này CHƯA có phancongcham
-- cần BTC chạy flow "Mời trọng tài" từ UI để cấp quyền chấm điểm.
