-- ============================================================
-- Migration: Fix laChuNhom data cho nhóm hiện tại
-- Date: 2026-03-10
-- Mô tả:
--   Cột laChuNhom đã tồn tại trong thanhviennhom nhưng toàn bộ = 0.
--   Migration này set laChuNhom = 1 cho người được gán làm
--   idnhomtruong trong bảng nhom (người tạo/quản lý nhóm).
-- ============================================================

-- Cập nhật laChuNhom = 1 cho chủ nhóm dựa trên nhom.idnhomtruong
UPDATE thanhviennhom tv
JOIN nhom n ON n.idnhom = tv.idnhom
SET tv.laChuNhom = 1
WHERE tv.idtk = n.idnhomtruong
  AND tv.trangthai = 1;
