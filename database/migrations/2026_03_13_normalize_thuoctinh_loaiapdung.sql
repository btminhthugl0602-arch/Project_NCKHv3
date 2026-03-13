-- Phase 1: Normalize loaiApDung values to match evaluator contexts

UPDATE `thuoctinh_kiemtra`
SET `loaiApDung` = 'THAMGIA_SV'
WHERE UPPER(TRIM(`loaiApDung`)) = 'THAMGIA'
  AND LOWER(TRIM(`bangDuLieu`)) = 'sinhvien';

UPDATE `thuoctinh_kiemtra`
SET `loaiApDung` = 'THAMGIA_GV'
WHERE UPPER(TRIM(`loaiApDung`)) = 'THAMGIA'
  AND LOWER(TRIM(`bangDuLieu`)) = 'giangvien';

UPDATE `thuoctinh_kiemtra`
SET `loaiApDung` = 'THAMGIA_SV'
WHERE UPPER(TRIM(`loaiApDung`)) = 'THAMGIA';

ALTER TABLE `thuoctinh_kiemtra`
MODIFY `loaiApDung` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA_SV';

ALTER TABLE `quyche`
MODIFY `loaiQuyChe` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TUY_CHINH';

UPDATE `quyche`
SET `loaiQuyChe` = 'TUY_CHINH'
WHERE TRIM(COALESCE(`loaiQuyChe`, '')) = '';
