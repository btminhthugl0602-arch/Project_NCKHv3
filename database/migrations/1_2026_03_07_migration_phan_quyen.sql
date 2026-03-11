-- =============================================================
-- MIGRATION: Refactor phân quyền hệ thống NCKH
-- Database : mon-nckh
-- Server   : MySQL 8.4.7  |  PHP 8.1.34
-- Created  : 2026-03-06
-- =============================================================
--
-- LƯU Ý KHI IMPORT VÀO phpMyAdmin:
--   - Import toàn bộ file một lần (Import > chọn file .sql)
--   - KHÔNG chạy từng phần riêng lẻ
--   - DDL (ALTER/DROP TABLE) KHÔNG rollback được trong MySQL.
--     Nếu bị lỗi giữa chừng, kiểm tra phần nào đã chạy thành
--     công rồi chạy tiếp từ phần còn lại.
--   - FK_CHECKS = 0 chỉ tắt kiểm tra constraint, KHÔNG kích
--     hoạt CASCADE. Vì vậy script này xóa bảng con TRƯỚC khi
--     xóa bảng cha ở mọi bước.
--
-- THỨ TỰ THỰC THI:
--   P1  Dọn bảng legacy (bantochuc, phanconbtc, quyentaosk,
--       vaitro_quyen_sk)
--   P2  Bảng vaitro: thêm maVaiTro, đổi tên idvatro, thêm 2 role
--   P3  Bảng giangvien: thêm hocHam
--   P4  Bảng quyen: xóa idNhomQuyen + maQuyen_code, chuẩn hóa
--       tên, xóa 2 quyền admin.events/criteria, thêm 3 quyền mới
--   P5  Bảng vaitro_quyen: cập nhật mapping quyền
--   P6  Bảng taikhoan_vaitro_sukien: thêm idVaiTro, fix ENUM,
--       bỏ idVaiTroSK + idVaiTroGoc
--   P7  Bảng vaitro_sukien: thu gọn còn (idSK, idVaiTro)
--   P8  Bật lại FK_CHECKS
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- =============================================================
-- PHẦN 1: XÓA CÁC BẢNG LEGACY
-- Xóa bảng con trước, bảng cha sau (vì FK_CHECKS=0 không cascade)
-- =============================================================

-- phanconbtc tham chiếu cả bantochuc lẫn vaitro → xóa trước
DROP TABLE IF EXISTS `phanconbtc`;
DROP TABLE IF EXISTS `bantochuc`;

-- vaitro_quyen_sk tham chiếu vaitro_sukien + quyen → xóa trước
DROP TABLE IF EXISTS `vaitro_quyen_sk`;

-- quyentaosk tham chiếu giangvien + loaicap
DROP TABLE IF EXISTS `quyentaosk`;

-- nhom_quyen sẽ xóa ở cuối P4 sau khi drop FK + cột từ quyen


-- =============================================================
-- PHẦN 2: BẢNG vaitro
-- =============================================================

-- 2.1 Drop FK từ các bảng đang trỏ vào vaitro.idvatro
--     (phải drop trước khi đổi tên cột)
ALTER TABLE `vaitro_quyen`
    DROP FOREIGN KEY `vaitro_quyen_ibfk_1`;

ALTER TABLE `vaitro_sukien`
    DROP FOREIGN KEY `vaitro_sukien_ibfk_vt`;

-- 2.2 Thêm cột maVaiTro (NULL tạm, sẽ fill data rồi mới NOT NULL)
ALTER TABLE `vaitro`
    ADD COLUMN `maVaiTro` VARCHAR(50) NULL AFTER `idvatro`;

-- 2.3 Fill maVaiTro cho 4 role hiện có (DML → bọc transaction)
START TRANSACTION;
UPDATE `vaitro` SET `maVaiTro` = 'BTC'            WHERE `idvatro` = 1;
UPDATE `vaitro` SET `maVaiTro` = 'GV_PHAN_BIEN'  WHERE `idvatro` = 2;
UPDATE `vaitro` SET `maVaiTro` = 'GV_HUONG_DAN'  WHERE `idvatro` = 3;
UPDATE `vaitro` SET `maVaiTro` = 'THAM_GIA'      WHERE `idvatro` = 4;
COMMIT;

-- 2.4 Thêm 2 role mới (DML → bọc transaction)
START TRANSACTION;
INSERT INTO `vaitro` (`idvatro`, `maVaiTro`, `tenvaitro`, `mota`) VALUES
(5, 'GV_CHAM_DOCLAP',  'GV Chấm độc lập',
    'Giảng viên chấm điểm độc lập theo phân công'),
(6, 'GV_CHAM_TIEUBAN', 'GV Chấm tiểu ban',
    'Giảng viên chấm điểm trong tiểu ban');
COMMIT;

-- 2.5 Đặt NOT NULL + UNIQUE sau khi data đầy đủ
ALTER TABLE `vaitro`
    MODIFY COLUMN `maVaiTro` VARCHAR(50) NOT NULL,
    ADD UNIQUE KEY `uq_vaitro_maVaiTro` (`maVaiTro`);

-- 2.6 Đổi tên idvatro → idVaiTro (MySQL 8.0+ syntax)
ALTER TABLE `vaitro`
    RENAME COLUMN `idvatro` TO `idVaiTro`;

-- 2.7 Tạo lại FK sau khi đổi tên cột
--     (vaitro_sukien_ibfk_vt sẽ tạo lại ở P7 sau khi restructure xong)
ALTER TABLE `vaitro_quyen`
    ADD CONSTRAINT `vaitro_quyen_ibfk_1`
        FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`)
        ON DELETE CASCADE ON UPDATE CASCADE;


-- =============================================================
-- PHẦN 3: BẢNG giangvien — Thêm hocHam
-- =============================================================

ALTER TABLE `giangvien`
    ADD COLUMN `hocHam`
        ENUM('Cu_nhan','Tha_si','Tien_si','Pho_giao_su','Giao_su')
        NULL COMMENT 'Học hàm/học vị giảng viên'
    AFTER `idKhoa`;


-- =============================================================
-- PHẦN 4: BẢNG quyen
-- =============================================================

-- 4.1 Drop FK → nhom_quyen và index liên quan
ALTER TABLE `quyen`
    DROP FOREIGN KEY `quyen_ibfk_nhomquyen`,
    DROP KEY `idNhomQuyen`;

-- 4.2 Drop 2 cột dư thừa
ALTER TABLE `quyen`
    DROP COLUMN `idNhomQuyen`,
    DROP COLUMN `maQuyen_code`;

-- 4.3 Xóa bảng con trước khi xóa quyen (FK_CHECKS=0 không cascade)
--     taikhoan_quyen: admin (idTK=1) có idQuyen 35 và 36
START TRANSACTION;
DELETE FROM `taikhoan_quyen` WHERE `idQuyen` IN (35, 36);
COMMIT;

--     vaitro_quyen: không có dòng nào trỏ vào 35, 36
--     nhưng xóa tường minh để chắc chắn
START TRANSACTION;
DELETE FROM `vaitro_quyen` WHERE `idQuyen` IN (35, 36);
COMMIT;

-- 4.4 Xóa 2 quyền admin.events (35) và admin.criteria (36)
--     (bảng con đã sạch ở bước 4.3)
START TRANSACTION;
DELETE FROM `quyen` WHERE `idQuyen` IN (35, 36);
COMMIT;

-- 4.5 Đổi tên maQuyen cho 2 quyền cũ còn lại
START TRANSACTION;
UPDATE `quyen`
    SET `maQuyen`  = 'quan_ly_tai_khoan',
        `tenQuyen` = 'Quản lý tài khoản'
    WHERE `idQuyen` = 34;

UPDATE `quyen`
    SET `maQuyen`  = 'xem_thong_ke',
        `tenQuyen` = 'Xem thống kê hệ thống'
    WHERE `idQuyen` = 37;
COMMIT;

-- 4.6 Thêm 3 quyền mới
START TRANSACTION;
INSERT INTO `quyen` (`maQuyen`, `tenQuyen`, `moTa`, `phamVi`) VALUES
('quan_ly_diemdanh',
    'Quản lý điểm danh',
    'Mở/đóng phiên điểm danh trong sự kiện',
    'SU_KIEN'),
('duyet_diem',
    'Duyệt và chốt điểm',
    'Xem xét và phê duyệt điểm chấm của vòng thi',
    'SU_KIEN'),
('quan_ly_tieuban',
    'Quản lý tiểu ban',
    'Thêm/bớt giảng viên vào tiểu ban chấm điểm',
    'SU_KIEN');
COMMIT;

-- 4.7 Xóa nhom_quyen (không còn FK từ quyen trỏ vào)
DROP TABLE IF EXISTS `nhom_quyen`;


-- =============================================================
-- PHẦN 5: BẢNG vaitro_quyen — Cập nhật mapping
-- =============================================================

START TRANSACTION;

-- 5.1 Thêm 3 quyền mới cho BTC (idVaiTro = 1)
INSERT IGNORE INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`)
SELECT 1, `idQuyen` FROM `quyen`
WHERE `maQuyen` IN ('quan_ly_diemdanh', 'duyet_diem', 'quan_ly_tieuban');

-- 5.2 Thêm quyền cho GV_CHAM_DOCLAP (idVaiTro = 5)
INSERT IGNORE INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`)
SELECT 5, `idQuyen` FROM `quyen`
WHERE `maQuyen` IN ('nhap_diem', 'xem_bai_phan_cong', 'xem_ketqua_sauCB');

-- 5.3 Thêm quyền cho GV_CHAM_TIEUBAN (idVaiTro = 6)
INSERT IGNORE INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`)
SELECT 6, `idQuyen` FROM `quyen`
WHERE `maQuyen` IN ('nhap_diem', 'xem_bai_phan_cong', 'xem_ketqua_sauCB');

COMMIT;


-- =============================================================
-- PHẦN 6: BẢNG taikhoan_vaitro_sukien
-- =============================================================

-- 6.1 Thêm cột idVaiTro (NULL tạm để populate data trước)
ALTER TABLE `taikhoan_vaitro_sukien`
    ADD COLUMN `idVaiTro` INT NULL
        COMMENT 'FK → vaitro.idVaiTro, thay thế idVaiTroSK'
    AFTER `idSK`;

-- 6.2 Populate idVaiTro từ vaitro_sukien.idVaiTroGoc
--     JOIN qua idVaiTroSK (cột vẫn còn tồn tại ở bước này)
START TRANSACTION;
UPDATE `taikhoan_vaitro_sukien` tvs
INNER JOIN `vaitro_sukien` vs
    ON vs.`idVaiTroSK` = tvs.`idVaiTroSK`
SET tvs.`idVaiTro` = vs.`idVaiTroGoc`;
COMMIT;

-- 6.3 Đặt NOT NULL sau khi data đầy đủ
ALTER TABLE `taikhoan_vaitro_sukien`
    MODIFY COLUMN `idVaiTro` INT NOT NULL;

-- 6.4 Thêm FK idVaiTro → vaitro
ALTER TABLE `taikhoan_vaitro_sukien`
    ADD CONSTRAINT `tvs_ibfk_vaitro`
        FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`)
        ON DELETE RESTRICT ON UPDATE CASCADE;

-- 6.5 Fix ENUM: đổi QUAT_NHOM → QUA_NHOM
--     (Không có dòng nào dùng QUAT_NHOM trong data hiện tại)
ALTER TABLE `taikhoan_vaitro_sukien`
    MODIFY COLUMN `nguonTao`
        ENUM('BTC_THEM','PHANCONG_CHAM','QUA_NHOM','DANG_KY')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
        COMMENT 'BTC_THEM: BTC thêm | PHANCONG_CHAM: qua phân công chấm | QUA_NHOM: GV vào nhóm | DANG_KY: SV tự đăng ký';

-- 6.6 Drop FK cũ trỏ vào vaitro_sukien.idVaiTroSK
ALTER TABLE `taikhoan_vaitro_sukien`
    DROP FOREIGN KEY `tvs_ibfk_vts`;

-- 6.7 Drop UNIQUE KEY và index liên quan đến idVaiTroSK
ALTER TABLE `taikhoan_vaitro_sukien`
    DROP KEY `uq_tk_sk_vaitro`,
    DROP KEY `idVaiTroSK`;

-- 6.8 Drop 2 cột cũ
ALTER TABLE `taikhoan_vaitro_sukien`
    DROP COLUMN `idVaiTroSK`,
    DROP COLUMN `idVaiTroGoc`;

-- 6.9 Tạo lại UNIQUE KEY và thêm index mới
ALTER TABLE `taikhoan_vaitro_sukien`
    ADD UNIQUE KEY `uq_tk_sk_vaitro` (`idTK`, `idSK`, `idVaiTro`),
    ADD KEY `idx_tvs_vaitro` (`idVaiTro`);


-- =============================================================
-- PHẦN 7: BẢNG vaitro_sukien — Thu gọn thành bảng mapping
-- =============================================================

-- 7.1 Thêm cột idVaiTro (NULL tạm)
ALTER TABLE `vaitro_sukien`
    ADD COLUMN `idVaiTro` INT NULL
        COMMENT 'FK → vaitro.idVaiTro'
    AFTER `idSK`;

-- 7.2 Populate idVaiTro từ idVaiTroGoc
START TRANSACTION;
UPDATE `vaitro_sukien`
SET `idVaiTro` = `idVaiTroGoc`
WHERE `idVaiTroGoc` IS NOT NULL;
COMMIT;

-- 7.3 Đặt NOT NULL
ALTER TABLE `vaitro_sukien`
    MODIFY COLUMN `idVaiTro` INT NOT NULL;

-- 7.4 Drop KEY idVaiTroGoc (index) trước khi drop cột
ALTER TABLE `vaitro_sukien`
    DROP KEY `idVaiTroGoc`;

-- 7.5 Drop các cột không còn cần
ALTER TABLE `vaitro_sukien`
    DROP COLUMN `tenVaiTro`,
    DROP COLUMN `moTa`,
    DROP COLUMN `isSystem`,
    DROP COLUMN `isActive`,
    DROP COLUMN `idVaiTroGoc`;

-- 7.6 Đổi PK: idVaiTroSK (AUTO_INCREMENT) → composite (idSK, idVaiTro)
--     Bước a: Bỏ AUTO_INCREMENT trước khi drop PK
ALTER TABLE `vaitro_sukien`
    MODIFY COLUMN `idVaiTroSK` INT NOT NULL;
--     Bước b: Drop PK cũ
ALTER TABLE `vaitro_sukien`
    DROP PRIMARY KEY;
--     Bước c: Drop cột idVaiTroSK
ALTER TABLE `vaitro_sukien`
    DROP COLUMN `idVaiTroSK`;
--     Bước d: Tạo composite PK
ALTER TABLE `vaitro_sukien`
    ADD PRIMARY KEY (`idSK`, `idVaiTro`);

-- 7.7 Thêm FK idVaiTro → vaitro
ALTER TABLE `vaitro_sukien`
    ADD CONSTRAINT `vaitro_sukien_ibfk_vaitro`
        FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`)
        ON DELETE RESTRICT ON UPDATE CASCADE;

-- (FK idSK → sukien đã có sẵn: vaitro_sukien_ibfk_sk, giữ nguyên)


-- =============================================================
-- PHẦN 8: BẬT LẠI FK CHECKS
-- =============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================
-- VERIFY — Chạy thủ công để kiểm tra kết quả sau migration
-- =============================================================
-- SELECT idVaiTro, maVaiTro, tenvaitro FROM vaitro ORDER BY idVaiTro;
-- SELECT idQuyen, maQuyen, tenQuyen, phamVi FROM quyen ORDER BY phamVi, idQuyen;
-- SELECT v.maVaiTro, q.maQuyen FROM vaitro_quyen vq
--   JOIN vaitro v ON v.idVaiTro = vq.idVaiTro
--   JOIN quyen q ON q.idQuyen = vq.idQuyen
--   ORDER BY v.maVaiTro, q.maQuyen;
-- DESCRIBE vaitro_sukien;
-- DESCRIBE taikhoan_vaitro_sukien;
