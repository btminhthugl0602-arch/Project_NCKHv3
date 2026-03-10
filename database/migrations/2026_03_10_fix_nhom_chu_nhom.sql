-- ============================================================
-- Migration: Thêm cột `laChuNhom` vào bảng `thanhviennhom`
-- Áp dụng cho: nckh-14.sql (database nckh)
-- Tham chiếu từ: schema.sql
-- Ngày tạo: 2026-03-10
-- ============================================================

-- Bước 1: Thêm cột laChuNhom
ALTER TABLE `thanhviennhom`
  ADD COLUMN `laChuNhom` tinyint(1) NOT NULL DEFAULT '0'
  AFTER `ngaythamgia`;

-- Bước 2: Cập nhật dữ liệu hiện có
-- Đánh dấu laChuNhom = 1 cho các thành viên có vai trò TRUONG_NHOM (idvaitronhom = 1)
UPDATE `thanhviennhom`
SET `laChuNhom` = 1
WHERE `idvaitronhom` = 1;

-- ============================================================
-- Kiểm tra kết quả sau migrate
-- SELECT idnhom, idtk, idvaitronhom, trangthai, laChuNhom
-- FROM thanhviennhom
-- ORDER BY idnhom;
-- ============================================================