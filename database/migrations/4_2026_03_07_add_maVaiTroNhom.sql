-- ============================================================
-- Migration: Thêm cột maVaiTroNhom vào bảng vaitronhom
-- Lý do: Thay thế hardcode id số trong code bằng string constant
-- Ngày: 2026-03-07
-- ============================================================

-- Bước 1: Đảm bảo id là AUTO_INCREMENT và thêm cột maVaiTroNhom
ALTER TABLE `vaitronhom`
    MODIFY `id` INT NOT NULL AUTO_INCREMENT,
    ADD COLUMN `maVaiTroNhom` VARCHAR(50) NOT NULL DEFAULT '' AFTER `id`;

-- Bước 2: Gán maVaiTroNhom cho các bản ghi hiện có
UPDATE `vaitronhom` SET `maVaiTroNhom` = 'TRUONG_NHOM' WHERE `id` = 1;
UPDATE `vaitronhom` SET `maVaiTroNhom` = 'THANH_VIEN'  WHERE `id` = 2;

-- Bước 3: Thêm bản ghi GVHD còn thiếu (id=3 sẽ được auto_increment)
INSERT INTO `vaitronhom` (`maVaiTroNhom`, `tenvaitronhom`)
VALUES ('GVHD', 'Giảng viên hướng dẫn');

-- Bước 4: Thêm UNIQUE constraint để tránh trùng mã
ALTER TABLE `vaitronhom`
    ADD UNIQUE KEY `uq_maVaiTroNhom` (`maVaiTroNhom`);
