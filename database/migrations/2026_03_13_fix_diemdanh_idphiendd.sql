-- Migration: Thay idLichTrinh bằng idPhienDD trong bảng diemdanh
-- Lý do: diemdanh cần liên kết với phiên cụ thể (phien_diemdanh),
--         không chỉ buổi hoạt động (lichtrinh). idLichTrinh có thể
--         derive từ phien_diemdanh qua JOIN khi cần.
-- Date: 2026-03-13

-- 1. Xóa FK constraint trước (bắt buộc trước khi drop index)
ALTER TABLE `diemdanh`
  DROP FOREIGN KEY `diemdanh_ibfk_3`;

-- 2. Xóa indexes cũ liên quan idLichTrinh
ALTER TABLE `diemdanh`
  DROP KEY `idx_dd_lich`,
  DROP KEY `idx_dd_tk_lich`;

-- 3. Xóa cột idLichTrinh
ALTER TABLE `diemdanh`
  DROP COLUMN `idLichTrinh`;

-- 4. Thêm cột idPhienDD
ALTER TABLE `diemdanh`
  ADD COLUMN `idPhienDD` int DEFAULT NULL COMMENT 'Liên kết phiên điểm danh cụ thể' AFTER `ghiChu`;

-- 5. Thêm FK và indexes mới
ALTER TABLE `diemdanh`
  ADD CONSTRAINT `diemdanh_ibfk_3` FOREIGN KEY (`idPhienDD`) REFERENCES `phien_diemdanh` (`idPhienDD`) ON DELETE SET NULL ON UPDATE RESTRICT,
  ADD KEY `idx_dd_phien` (`idPhienDD`),
  ADD KEY `idx_dd_tk_phien` (`idTK`, `idPhienDD`);
