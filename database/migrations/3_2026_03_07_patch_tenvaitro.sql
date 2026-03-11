-- =============================================================
-- PATCH: Chuẩn hóa tenvaitro để hiển thị trên UI
-- Database : mon-nckh
-- Created  : 2026-03-06
-- =============================================================

START TRANSACTION;

UPDATE `vaitro` SET `tenvaitro` = 'Ban tổ chức'             WHERE `idVaiTro` = 1;
UPDATE `vaitro` SET `tenvaitro` = 'Giảng viên phản biện'   WHERE `idVaiTro` = 2;
UPDATE `vaitro` SET `tenvaitro` = 'Giảng viên hướng dẫn'   WHERE `idVaiTro` = 3;
UPDATE `vaitro` SET `tenvaitro` = 'Sinh viên tham gia'      WHERE `idVaiTro` = 4;
-- idVaiTro 5, 6 đã có tên đúng từ migration trước, không cần update

COMMIT;
