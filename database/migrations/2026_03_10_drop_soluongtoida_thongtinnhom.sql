-- Migration: Drop soluongtoida from thongtinnhom
-- Lý do: soThanhVienToiDa giờ lấy từ bảng sukien, không cần lưu ở thongtinnhom nữa
-- Ngày: 2026-03-10

ALTER TABLE `thongtinnhom` DROP COLUMN `soluongtoida`;
