-- ============================================================
-- MIGRATION: Tổng hợp thay đổi CSDL
-- Ngày: 2026-03-02
-- Bao gồm:
--   1. Bỏ loaitailieu, thêm cauhinh_form_sp + sanpham_truong
--   2. Chỉnh quyền hệ thống trong bảng quyen
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PHẦN 1: CẤU HÌNH FORM NỘP SẢN PHẨM
-- ============================================================

-- Bước 1: Xóa FK và cột cũ trong bảng sanpham
ALTER TABLE `sanpham`
  DROP FOREIGN KEY `sanpham_ibfk_1`,
  DROP KEY `idloaitailieu`,
  DROP COLUMN `idloaitailieu`,
  DROP COLUMN `moTataiLieu`;

-- Bước 2: Xóa bảng loaitailieu
DROP TABLE IF EXISTS `loaitailieu`;

-- Bước 3: Tạo bảng cauhinh_form_sp
CREATE TABLE `cauhinh_form_sp` (
  `idTruong`          int NOT NULL AUTO_INCREMENT,
  `idSK`              int NOT NULL                    COMMENT 'FK → sukien',
  `tenTruong`         varchar(200) NOT NULL           COMMENT 'Tên trường hiển thị, vd: File báo cáo có tác giả',
  `loaiTruong`        enum('file','url','text','textarea') NOT NULL COMMENT 'Loại input',
  `batBuoc`           tinyint NOT NULL DEFAULT '1'    COMMENT '1 = bắt buộc, 0 = tùy chọn',
  `dinhDangChoPhep`   varchar(200) DEFAULT NULL       COMMENT 'Vd: pdf,docx. Chỉ dùng khi loaiTruong=file. NULL = không giới hạn',
  `dungLuongToiDa`    int DEFAULT NULL                COMMENT 'KB. Chỉ dùng khi loaiTruong=file. NULL = không giới hạn',
  `thuTu`             int NOT NULL DEFAULT '0'        COMMENT 'Thứ tự hiển thị trên form, hỗ trợ kéo thả',
  `isActive`          tinyint NOT NULL DEFAULT '1'    COMMENT '1 = đang dùng, 0 = đã ẩn (có dữ liệu cũ)',
  `ngayTao`           datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idTruong`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `cauhinh_form_sp_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='BTC định nghĩa các trường cần nộp cho từng sự kiện';

-- Bước 4: Tạo bảng sanpham_truong
CREATE TABLE `sanpham_truong` (
  `idGiaTri`    int NOT NULL AUTO_INCREMENT,
  `idSanPham`   int NOT NULL                COMMENT 'FK → sanpham',
  `idTruong`    int NOT NULL                COMMENT 'FK → cauhinh_form_sp',
  `giaTri`      text NOT NULL               COMMENT 'Path file (uploads/sanpham/...) hoặc URL hoặc text tùy loaiTruong',
  `ngayNop`     datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idGiaTri`),
  UNIQUE KEY `unique_sanpham_truong` (`idSanPham`, `idTruong`),
  KEY `idTruong` (`idTruong`),
  CONSTRAINT `sanpham_truong_ibfk_1` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `sanpham_truong_ibfk_2` FOREIGN KEY (`idTruong`) REFERENCES `cauhinh_form_sp` (`idTruong`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Lưu giá trị từng trường khi nhóm nộp sản phẩm';

-- ============================================================
-- PHẦN 2: CHỈNH QUYỀN HỆ THỐNG
-- ============================================================

-- Bước 5: Xóa các quyền cũ không dùng nữa
-- Xóa taikhoan_quyen liên quan trước
DELETE FROM `taikhoan_quyen` WHERE `idQuyen` IN (34, 35, 36, 37);

-- Xóa vaitro_quyen liên quan
DELETE FROM `vaitro_quyen` WHERE `idQuyen` IN (34, 35, 36, 37);

-- Xóa quyền cũ
DELETE FROM `quyen` WHERE `idQuyen` IN (34, 35, 36, 37);

-- Bước 6: Thêm quyền mới với tên chuẩn
INSERT INTO `quyen` (`idQuyen`, `maQuyen`, `tenQuyen`, `moTa`, `idNhomQuyen`, `maQuyen_code`, `phamVi`) VALUES
(34, 'quan_ly_taikhoan', 'Quản lý tài khoản',    'Truy cập trang quản lý, tạo và sửa tài khoản',  6, 'quan_ly_taikhoan', 'HE_THONG'),
(35, 'xem_baocao',       'Xem báo cáo tổng hợp', 'Xem thống kê và báo cáo toàn hệ thống',         6, 'xem_baocao',       'HE_THONG');

-- Lưu ý: tao_su_kien (idQuyen=38) đã có sẵn trong schema, không cần insert lại

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- KIỂM TRA SAU MIGRATION
-- ============================================================

-- Kiểm tra bảng mới
-- SELECT * FROM cauhinh_form_sp LIMIT 5;
-- SELECT * FROM sanpham_truong LIMIT 5;

-- Kiểm tra sanpham không còn cột cũ
-- DESCRIBE sanpham;

-- Kiểm tra loaitailieu đã bị xóa
-- SHOW TABLES LIKE 'loaitailieu';

-- Kiểm tra quyền hệ thống
-- SELECT * FROM quyen WHERE phamVi = 'HE_THONG';