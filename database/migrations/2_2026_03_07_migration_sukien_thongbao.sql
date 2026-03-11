-- =============================================================
-- MIGRATION: Module Sự Kiện & Thông Báo
-- Database : mon-nckh
-- Server   : MySQL 8.4.7
-- Created  : 2026-03-06
-- =============================================================
--
-- LƯU Ý:
--   - Import toàn bộ file một lần trong phpMyAdmin
--   - FK_CHECKS = 0 chỉ tắt kiểm tra, KHÔNG cascade
--   - DDL không rollback được — nếu lỗi giữa chừng, kiểm tra
--     phần đã chạy rồi chạy tiếp từ phần còn lại
--
-- THỨ TỰ:
--   P1  sukien       — thêm 4 cột mới
--   P2  lichtrinh    — restructure, rename cột, xóa 2 cột cũ
--   P3  phien_diemdanh — tạo mới
--   P4  diemdanh     — sửa ENUM phuongThuc
--   P5  chude        — thêm idNguoiTao
--   P6  audit_log    — tạo mới
--   P7  thongbao     — thiết kế lại hoàn toàn
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- =============================================================
-- PHẦN 1: BẢNG sukien — Thêm 4 cột
-- =============================================================

ALTER TABLE `sukien`
    ADD COLUMN `cheDoDangKySV`    ENUM('MO','CO_DIEU_KIEN') NOT NULL DEFAULT 'MO'
        COMMENT 'Chế độ đăng ký dành cho Sinh viên'
        AFTER `isActive`,
    ADD COLUMN `cheDoDangKyGV`    ENUM('MO','CO_DIEU_KIEN') NOT NULL DEFAULT 'MO'
        COMMENT 'Chế độ đăng ký dành cho Giảng viên'
        AFTER `cheDoDangKySV`,
    ADD COLUMN `soNhomToiDaGVHD`  INT NULL
        COMMENT 'Số nhóm tối đa 1 GVHD hướng dẫn. NULL = không giới hạn'
        AFTER `cheDoDangKyGV`,
    ADD COLUMN `isDeleted`        TINYINT NOT NULL DEFAULT 0
        COMMENT 'Xóa mềm. Tách biệt hoàn toàn với isActive'
        AFTER `soNhomToiDaGVHD`;


-- =============================================================
-- PHẦN 2: BẢNG lichtrinh — Restructure
-- =============================================================

-- 2.1 Xóa diemdanh trước (có FK → lichtrinh)
--     Đã thống nhất bỏ data cũ
--     Dùng DELETE thay TRUNCATE vì TRUNCATE luôn kiểm tra FK
--     constraint tồn tại dù FK_CHECKS=0
DELETE FROM `diemdanh`;

-- 2.2 Xóa xacnhan_thamgia (cũng có FK → lichtrinh)
DELETE FROM `xacnhan_thamgia`;

-- 2.3 Xóa lichtrinh
DELETE FROM `lichtrinh`;

-- 2.4 Rename thoiGian → thoiGianBatDau
ALTER TABLE `lichtrinh`
    RENAME COLUMN `thoiGian` TO `thoiGianBatDau`;

-- 2.5 Thêm các cột mới
ALTER TABLE `lichtrinh`
    ADD COLUMN `thoiGianKetThuc` DATETIME NULL
        COMMENT 'Thời điểm kết thúc hoạt động'
        AFTER `thoiGianBatDau`,
    ADD COLUMN `idTieuBan`       INT NULL
        COMMENT 'NULL = hoạt động chung cả SK; có giá trị = riêng tiểu ban'
        AFTER `idVongThi`,
    ADD COLUMN `loaiHoatDong`    ENUM('HOAT_DONG','DIEM_DANH','NGHI','KHAC') NOT NULL DEFAULT 'HOAT_DONG'
        COMMENT 'Phân loại để render UI và validate nghiệp vụ'
        AFTER `tenHoatDong`,
    ADD COLUMN `thuTu`           INT NOT NULL DEFAULT 0
        COMMENT 'Thứ tự hiển thị trong lịch trình'
        AFTER `loaiHoatDong`;

-- 2.6 Thêm FK idTieuBan → tieuban
ALTER TABLE `lichtrinh`
    ADD CONSTRAINT `lichtrinh_ibfk_tieuban`
        FOREIGN KEY (`idTieuBan`) REFERENCES `tieuban` (`idTieuBan`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 2.7 Drop 2 cột cũ (đã migrate sang phien_diemdanh, không còn cần)
ALTER TABLE `lichtrinh`
    DROP COLUMN `thoiGianMoDiemDanh`,
    DROP COLUMN `thoiGianDongDiemDanh`,
    DROP COLUMN `banKinhDiemDanh`;


-- =============================================================
-- PHẦN 3: BẢNG phien_diemdanh — Tạo mới
-- =============================================================

CREATE TABLE `phien_diemdanh` (
    `idPhienDD`     INT NOT NULL AUTO_INCREMENT,
    `idLichTrinh`   INT NOT NULL            COMMENT 'FK → lichtrinh. Chỉ hợp lệ khi loaiHoatDong = DIEM_DANH',
    `viTriLat`      DECIMAL(10,7) NULL      COMMENT 'Tọa độ GPS tâm điểm danh do BTC cài đặt',
    `viTriLng`      DECIMAL(10,7) NULL,
    `banKinh`       INT NOT NULL DEFAULT 150 COMMENT 'Bán kính hợp lệ (mét) để SV điểm danh GPS',
    `thoiGianMo`    DATETIME NOT NULL       COMMENT 'Thời điểm BTC mở phiên',
    `thoiGianDong`  DATETIME NULL           COMMENT 'Thời điểm đóng. NULL = chưa đóng',
    `trangThai`     ENUM('CHUAN_BI','DANG_MO','DA_DONG') NOT NULL DEFAULT 'CHUAN_BI',
    PRIMARY KEY (`idPhienDD`),
    KEY `idx_phiendd_lichtrinh` (`idLichTrinh`),
    CONSTRAINT `phiendd_ibfk_lichtrinh`
        FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Phiên điểm danh GPS/QR. 1 lịch trình DIEM_DANH có thể có nhiều phiên.';


-- =============================================================
-- PHẦN 4: BẢNG diemdanh — Sửa ENUM phuongThuc
-- Bỏ Manual, thêm THU_CONG, giữ NFC
-- Data đã TRUNCATE ở P2 nên đổi ENUM trực tiếp
-- =============================================================

ALTER TABLE `diemdanh`
    MODIFY COLUMN `phuongThuc`
        ENUM('QR','GPS','THU_CONG','NFC') NOT NULL DEFAULT 'QR'
        COMMENT 'Cách điểm danh được thực hiện';


-- =============================================================
-- PHẦN 5: BẢNG chude — Thêm idNguoiTao
-- =============================================================

ALTER TABLE `chude`
    ADD COLUMN `idNguoiTao` INT NULL
        COMMENT 'NULL = Admin tạo ngân hàng chủ đề; có giá trị = BTC tạo từ sự kiện'
        AFTER `isActive`;

ALTER TABLE `chude`
    ADD CONSTRAINT `chude_ibfk_nguoitao`
        FOREIGN KEY (`idNguoiTao`) REFERENCES `taikhoan` (`idTK`)
        ON DELETE SET NULL ON UPDATE CASCADE;


-- =============================================================
-- PHẦN 6: BẢNG audit_log — Tạo mới
-- Lưu ý: ghi trong transaction RIÊNG (không chung với thao tác
-- chính) để tránh bị rollback theo khi thao tác thất bại.
-- =============================================================

CREATE TABLE `audit_log` (
    `idLog`               INT NOT NULL AUTO_INCREMENT,
    `idTK`                INT NULL                COMMENT 'Tài khoản thực hiện. NULL nếu hệ thống tự chạy',
    `hanhDong`            ENUM('CREATE','UPDATE','DELETE') NOT NULL,
    `bangDuLieu`          VARCHAR(50) NOT NULL    COMMENT 'Tên bảng bị tác động',
    `idDoiTuong`          INT NOT NULL            COMMENT 'ID bản ghi bị tác động',
    `duLieuCu`            JSON NULL               COMMENT 'Snapshot trước thay đổi. NULL nếu CREATE',
    `duLieuMoi`           JSON NULL               COMMENT 'Snapshot sau thay đổi. NULL nếu DELETE',
    `trangThaiThaoTac`    ENUM('THANH_CONG','THAT_BAI') NOT NULL
                          COMMENT 'Kết quả thao tác. Ghi cả khi thất bại để có dấu vết đầy đủ',
    `thoiGian`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idLog`),
    KEY `idx_audit_bang`      (`bangDuLieu`),
    KEY `idx_audit_doi_tuong` (`bangDuLieu`, `idDoiTuong`),
    KEY `idx_audit_tk`        (`idTK`),
    CONSTRAINT `audit_log_ibfk_tk`
        FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ghi lại mọi thao tác quan trọng. Luôn ghi trong transaction riêng.';


-- =============================================================
-- PHẦN 7: MODULE THÔNG BÁO — Thiết kế lại hoàn toàn
-- thongbao + thongbao_nguoinhan đều RỖNG → DROP và CREATE lại
-- =============================================================

-- 7.1 Drop bảng con trước (FK_CHECKS=0 không cascade)
DROP TABLE IF EXISTS `thongbao_nguoinhan`;

-- 7.2 Drop và tạo lại thongbao
DROP TABLE IF EXISTS `thongbao`;

CREATE TABLE `thongbao` (
    `idThongBao`    INT NOT NULL AUTO_INCREMENT,
    `tieuDe`        VARCHAR(200) NOT NULL        COMMENT 'Tăng từ 50 → 200 ký tự',
    `noiDung`       TEXT NULL                    COMMENT 'NULL cho phép — một số TB chỉ cần tiêu đề',
    `loaiThongBao`  ENUM('SU_KIEN','HE_THONG','NHOM','CA_NHAN') NOT NULL
                    COMMENT 'Đổi từ VARCHAR tự do → ENUM',
    `phamVi`        ENUM('CA_NHAN','NHOM_NGUOI','TAT_CA') NOT NULL
                    COMMENT 'CA_NHAN: dùng thongbao_ca_nhan | NHOM_NGUOI: dùng thongbao_nhom_nhan | TAT_CA: không insert bảng phụ',
    `idSK`          INT NULL                     COMMENT 'NULL nếu không gắn sự kiện cụ thể',
    `idDoiTuong`    INT NULL                     COMMENT 'idYeuCau / idNhom / idSanPham tùy loaiDoiTuong',
    `loaiDoiTuong`  ENUM('NHOM','YEUCAU','SANPHAM') NULL
                    COMMENT 'Cho biết idDoiTuong là gì. NULL khi idDoiTuong NULL',
    `nguoiGui`      INT NOT NULL                 COMMENT 'Luôn có giá trị — mọi TB đều do người kích hoạt',
    `ngayGui`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idThongBao`),
    KEY `idx_tb_sk`       (`idSK`),
    KEY `idx_tb_nguoigui` (`nguoiGui`),
    CONSTRAINT `thongbao_ibfk_sk`
        FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `thongbao_ibfk_nguoigui`
        FOREIGN KEY (`nguoiGui`) REFERENCES `taikhoan` (`idTK`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Bảng trung tâm thông báo. phamVi quyết định bảng phụ nào được dùng.';

-- 7.3 Tạo thongbao_ca_nhan
--     Dùng khi phamVi = CA_NHAN. Insert ngay khi gửi.
CREATE TABLE `thongbao_ca_nhan` (
    `idThongBao`    INT NOT NULL    COMMENT 'FK → thongbao',
    `idTK`          INT NOT NULL    COMMENT 'FK → taikhoan — người nhận cụ thể',
    PRIMARY KEY (`idThongBao`, `idTK`),
    KEY `idx_tbcn_tk` (`idTK`),
    CONSTRAINT `tbcn_ibfk_thongbao`
        FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `tbcn_ibfk_tk`
        FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Người nhận cụ thể khi phamVi = CA_NHAN.';

-- 7.4 Tạo thongbao_nhom_nhan
--     Dùng khi phamVi = NHOM_NGUOI. 1 bản ghi mô tả nhóm nhận.
--     CHECK: idNhom NOT NULL khi loaiNhom IN (SU_KIEN, TIEU_BAN)
--            idNhom NULL     khi loaiNhom IN (GV, SV)
CREATE TABLE `thongbao_nhom_nhan` (
    `idThongBao`    INT NOT NULL,
    `loaiNhom`      ENUM('SU_KIEN','TIEU_BAN','GV','SV') NOT NULL
                    COMMENT 'Loại nhóm nhận thông báo',
    `idNhom`        INT NULL
                    COMMENT 'NULL khi loaiNhom=GV/SV (toàn hệ thống); có giá trị khi SU_KIEN/TIEU_BAN',
    `idVaiTro`      INT NULL
                    COMMENT 'NULL = tất cả vai trò; có giá trị = lọc theo vai trò trong sự kiện',
    PRIMARY KEY (`idThongBao`, `loaiNhom`),
    KEY `idx_tbnn_vaitro` (`idVaiTro`),
    CONSTRAINT `tbnn_ibfk_thongbao`
        FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `tbnn_ibfk_vaitro`
        FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    -- CHECK: loaiNhom SU_KIEN/TIEU_BAN bắt buộc có idNhom
    --        loaiNhom GV/SV bắt buộc NULL idNhom
    CONSTRAINT `chk_tbnn_idnhom`
        CHECK (
            (loaiNhom IN ('SU_KIEN','TIEU_BAN') AND idNhom IS NOT NULL)
            OR
            (loaiNhom IN ('GV','SV') AND idNhom IS NULL)
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Nhóm nhận khi phamVi = NHOM_NGUOI. 1 bản ghi thay cho N bản ghi người nhận.';

-- 7.5 Tạo thongbao_da_doc
--     Không insert trước. Chỉ insert khi user click vào thông báo.
--     Dùng INSERT IGNORE để tránh duplicate key.
CREATE TABLE `thongbao_da_doc` (
    `idThongBao`    INT NOT NULL,
    `idTK`          INT NOT NULL    COMMENT 'Người đã đọc',
    `thoiGianDoc`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idThongBao`, `idTK`),
    KEY `idx_tbdd_tk` (`idTK`),
    CONSTRAINT `tbdd_ibfk_thongbao`
        FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `tbdd_ibfk_tk`
        FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Track trạng thái đọc. Insert khi user click — dùng INSERT IGNORE tránh duplicate.';


-- =============================================================
-- PHẦN 8: BẬT LẠI FK CHECKS
-- =============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================
-- VERIFY — Chạy thủ công để kiểm tra sau migration
-- =============================================================
-- DESCRIBE sukien;
-- DESCRIBE lichtrinh;
-- SHOW CREATE TABLE phien_diemdanh;
-- SHOW CREATE TABLE audit_log;
-- SHOW CREATE TABLE thongbao;
-- SHOW CREATE TABLE thongbao_nhom_nhan;
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS
--   WHERE TABLE_NAME = 'diemdanh' AND COLUMN_NAME = 'phuongThuc';
