-- Pha 1: Chuan hoa contract GVHD theo su kien
-- Muc tieu: bo sung coGVHDTheoSuKien lam nguon su that bat/tat luong GVHD o cap su kien.
-- Luu y: dat mac dinh = 1 de giu nguyen hanh vi hien tai cho du lieu cu.

-- Pha 1: Chuan hoa contract GVHD theo su kien
ALTER TABLE sukien
    ADD COLUMN coGVHDTheoSuKien TINYINT NOT NULL DEFAULT 1
    COMMENT 'Bat/tat luong GVHD theo su kien: 0=khong, 1=co'
    AFTER choPhepGVTaoNhom;

UPDATE sukien
SET coGVHDTheoSuKien = CASE
    WHEN coGVHDTheoSuKien IN (0, 1) THEN coGVHDTheoSuKien
    ELSE 1
END;