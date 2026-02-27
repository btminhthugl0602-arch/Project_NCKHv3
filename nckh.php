-- ============================================================
-- CSDL HOÀN CHỈNH: nckh
-- Đã tích hợp Migration Luồng 2 — QUA_NHOM
-- Phiên bản: 25/02/2026
-- Chỉ cần import thẳng vào phpMyAdmin
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- TẠO DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS `nckh`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `nckh`;

-- ============================================================
-- BẢNG: botieuchi
-- ============================================================
CREATE TABLE `botieuchi` (
  `idBoTieuChi` int NOT NULL AUTO_INCREMENT,
  `tenBoTieuChi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`idBoTieuChi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5;

INSERT INTO `botieuchi` VALUES
(1,'Bộ tiêu chuẩn NCKH 2026','Áp dụng cho vòng chung kết'),
(2,'Bộ tiêu chuẩn NCKH 2026 (Bản sao)','Áp dụng cho vòng chung kết'),
(3,'Bộ tiêu chuẩn NCKH 2026 (Bản sao) (Bản sao)','Áp dụng cho vòng chung kết'),
(4,'Bộ tiêu chuẩn NCKH 2026 (Bản sao)','Áp dụng cho vòng chung kết');

-- ============================================================
-- BẢNG: loaicap
-- ============================================================
CREATE TABLE `loaicap` (
  `idLoaiCap` int NOT NULL AUTO_INCREMENT,
  `tenLoaiCap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idLoaiCap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3;

INSERT INTO `loaicap` VALUES
(1,'Cấp Khoa'),
(2,'Cấp Trường');

-- ============================================================
-- BẢNG: cap_tochuc
-- ============================================================
CREATE TABLE `cap_tochuc` (
  `idCap` int NOT NULL AUTO_INCREMENT,
  `idLoaiCap` int NOT NULL,
  `tenCap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idCap`),
  KEY `idLoaiCap` (`idLoaiCap`),
  CONSTRAINT `cap_tochuc_ibfk_1` FOREIGN KEY (`idLoaiCap`) REFERENCES `loaicap` (`idLoaiCap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=2;

INSERT INTO `cap_tochuc` VALUES
(1,1,'Khoa Công nghệ thông tin');

-- ============================================================
-- BẢNG: khoa
-- ============================================================
CREATE TABLE `khoa` (
  `idKhoa` int NOT NULL AUTO_INCREMENT,
  `maKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idKhoa`),
  UNIQUE KEY `maKhoa` (`maKhoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3;

INSERT INTO `khoa` VALUES
(1,'CNTT','Công nghệ thông tin'),
(2,'KT','Kinh tế');

-- ============================================================
-- BẢNG: loaitaikhoan
-- ============================================================
CREATE TABLE `loaitaikhoan` (
  `idLoaiTK` int NOT NULL AUTO_INCREMENT,
  `tenLoaiTK` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idLoaiTK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=4;

INSERT INTO `loaitaikhoan` VALUES
(1,'Quản trị viên'),
(2,'Giảng viên'),
(3,'Sinh viên');

-- ============================================================
-- BẢNG: taikhoan
-- ============================================================
CREATE TABLE `taikhoan` (
  `idTK` int NOT NULL AUTO_INCREMENT,
  `tenTK` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `matKhau` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idLoaiTK` int NOT NULL,
  `isActive` tinyint DEFAULT '1',
  `ngayTao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idTK`),
  UNIQUE KEY `tenTK` (`tenTK`),
  KEY `idLoaiTK` (`idLoaiTK`),
  CONSTRAINT `taikhoan_ibfk_1` FOREIGN KEY (`idLoaiTK`) REFERENCES `loaitaikhoan` (`idLoaiTK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=15;

INSERT INTO `taikhoan` VALUES
(1,'admin','123456',1,1,'2026-02-11 22:11:22'),
(2,'gv_minh','123456',2,1,'2026-02-11 22:11:22'),
(3,'gv_huong','123456',2,1,'2026-02-11 22:11:22'),
(4,'sv_tung','123456',3,1,'2026-02-11 22:11:22'),
(5,'sv_mai','123456',3,1,'2026-02-11 22:11:22'),
(6,'sv_nam','123456',3,1,'2026-02-11 22:11:22'),
(7,'gv_lan','123456',2,1,'2026-02-21 14:41:11'),
(8,'son','123456',3,1,'2026-02-22 23:11:07'),
(9,'gv_khanh','123456',2,1,'2026-02-23 15:36:21'),
(10,'gv_them','123456',2,1,'2026-02-23 15:38:35'),
(11,'gv_long','123456',2,1,'2026-02-23 16:08:05'),
(12,'gv_binh','123456',2,1,'2026-02-23 17:51:13'),
(13,'gv_moi','123456',2,1,'2026-02-23 17:53:27'),
(14,'gv_moi1','123456',2,1,'2026-02-23 17:53:45');

-- ============================================================
-- BẢNG: giangvien
-- ============================================================
CREATE TABLE `giangvien` (
  `idGV` int NOT NULL AUTO_INCREMENT,
  `idTK` int NOT NULL,
  `tenGV` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL,
  `gioiTinh` tinyint DEFAULT '0',
  `hocHam` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Học hàm/học vị. Ví dụ: Tiến sĩ, Thạc sĩ, Giáo sư...',
  PRIMARY KEY (`idGV`),
  UNIQUE KEY `idTK` (`idTK`),
  KEY `idKhoa` (`idKhoa`),
  CONSTRAINT `giangvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  CONSTRAINT `giangvien_ibfk_2` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=8;

INSERT INTO `giangvien` VALUES
(1,2,'Nguyễn Văn Minh',1,1,NULL),
(2,3,'Trần Thị Hương',1,0,NULL),
(3,7,'TS. Phạm Thị Lan',1,0,NULL),
(4,9,'GV Khánh',NULL,0,NULL),
(5,10,'',NULL,0,NULL),
(6,13,'GV Mới',NULL,0,NULL),
(7,14,'GV Siêu mới',1,1,NULL);

-- ============================================================
-- BẢNG: lop
-- ============================================================
CREATE TABLE `lop` (
  `idLop` int NOT NULL AUTO_INCREMENT,
  `maLop` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenLop` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL,
  PRIMARY KEY (`idLop`),
  UNIQUE KEY `maLop` (`maLop`),
  KEY `idKhoa` (`idKhoa`),
  CONSTRAINT `lop_ibfk_1` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=4;

INSERT INTO `lop` VALUES
(1,'64PM1','K64 Phần mềm 1',1),
(2,'64PM2','K64 Phần mềm 2',1),
(3,'64KT1','K64 Kinh tế 1',2);

-- ============================================================
-- BẢNG: sinhvien
-- ============================================================
CREATE TABLE `sinhvien` (
  `idSV` int NOT NULL AUTO_INCREMENT,
  `idTK` int NOT NULL,
  `tenSV` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `MSV` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `GPA` decimal(4,2) DEFAULT '0.00',
  `DRL` int DEFAULT '0',
  `idLop` int DEFAULT NULL,
  `idKhoa` int DEFAULT NULL,
  PRIMARY KEY (`idSV`),
  UNIQUE KEY `idTK` (`idTK`),
  UNIQUE KEY `MSV` (`MSV`),
  KEY `idLop` (`idLop`),
  KEY `idKhoa` (`idKhoa`),
  CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  CONSTRAINT `sinhvien_ibfk_2` FOREIGN KEY (`idLop`) REFERENCES `lop` (`idLop`),
  CONSTRAINT `sinhvien_ibfk_3` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5;

INSERT INTO `sinhvien` VALUES
(1,4,'Nguyễn Thanh Tùng','SV001',3.60,90,1,1),
(2,5,'Lê Thị Mai','SV002',3.20,85,1,1),
(3,6,'Hoàng Văn Nam','SV003',2.80,70,2,1),
(4,8,'Trần Văn Sơn','Hello',4.00,100,1,1);

-- ============================================================
-- BẢNG: nienkhoa
-- ============================================================
CREATE TABLE `nienkhoa` (
  `idNienKhoa` int NOT NULL AUTO_INCREMENT,
  `maNienKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenNienKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idNienKhoa`),
  UNIQUE KEY `maNienKhoa` (`maNienKhoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG: sukien
-- [MIGRATION] Thêm cột soNhomToiDaGVHD
-- ============================================================
CREATE TABLE `sukien` (
  `idSK` int NOT NULL AUTO_INCREMENT,
  `tenSK` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `idCap` int DEFAULT NULL,
  `nguoiTao` int NOT NULL,
  `ngayMoDangKy` datetime DEFAULT NULL,
  `ngayDongDangKy` datetime DEFAULT NULL,
  `ngayBatDau` datetime DEFAULT NULL,
  `ngayKetThuc` datetime DEFAULT NULL,
  `isActive` tinyint DEFAULT '1',
  `cheDoDangKySV` enum('MO','CO_DIEU_KIEN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MO' COMMENT 'MO: ai cung dang ky | CO_DIEU_KIEN: phai qua quy che THAMGIA_SV',
  `cheDoDangKyGV` enum('MO','CO_DIEU_KIEN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MO' COMMENT 'MO: GV nao cung dang ky | CO_DIEU_KIEN: phai qua quy che THAMGIA_GV',
  `soNhomToiDaGVHD` int DEFAULT NULL COMMENT 'Số nhóm tối đa 1 GV có thể hướng dẫn trong SK. NULL = không giới hạn',
  PRIMARY KEY (`idSK`),
  KEY `idCap` (`idCap`),
  KEY `nguoiTao` (`nguoiTao`),
  CONSTRAINT `sukien_ibfk_1` FOREIGN KEY (`idCap`) REFERENCES `cap_tochuc` (`idCap`),
  CONSTRAINT `sukien_ibfk_2` FOREIGN KEY (`nguoiTao`) REFERENCES `taikhoan` (`idTK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=505;

INSERT INTO `sukien` VALUES
(1,'Nghiên cứu khoa học sinh viên CNTT 2026','Cuộc thi tìm kiếm ý tưởng công nghệ mới',1,2,'2026-01-01 00:00:00','2026-02-28 00:00:00','2026-03-15 00:00:00','2026-05-20 00:00:00',1,'MO','MO',NULL),
(11,'tin hoc dai hoc','thdh',1,1,'2026-02-19 00:00:00','2026-02-28 00:00:00','2026-02-19 00:00:00','2026-02-28 00:00:00',1,'MO','MO',NULL),
(21,'sự kiện tài năng tin học','',1,1,'2026-02-23 15:20:00','2026-02-28 15:20:00','2026-02-23 15:20:00','2026-02-28 15:21:00',1,'MO','MO',NULL),
(23,'sự kiện giảng viên tạo 2','',1,2,'2026-02-23 15:26:00','2026-03-08 15:26:00','2026-02-23 15:26:00','2026-03-08 15:26:00',1,'MO','MO',NULL),
(24,'sukien 2','',1,2,'2026-02-23 15:30:00','2026-03-07 15:30:00','2026-02-23 15:30:00','2026-03-08 15:30:00',1,'MO','MO',NULL),
(500,'Hackathon Sinh viên Công nghệ 2026','Sự kiện demo full dữ liệu: Nhóm, Bài nộp, Chấm điểm',1,2,'2026-02-01 00:00:00','2026-02-20 00:00:00','2026-02-25 00:00:00','2026-03-30 00:00:00',1,'MO','MO',NULL),
(501,'gv Minh tạo','',1,7,'2026-02-23 16:05:00','2026-03-08 16:05:00','2026-02-23 16:05:00','2026-03-08 16:05:00',1,'MO','MO',NULL),
(502,'a','',NULL,1,NULL,NULL,NULL,NULL,0,'MO','MO',NULL),
(503,'TEST HACKATHON Nháp','Sự kiện demo full dữ liệu: Nhóm, Bài nộp, Chấm điểm',1,2,'2026-02-24 21:08:00','2026-02-26 21:08:00','2026-02-25 21:08:00','2026-02-28 21:08:00',1,'MO','MO',NULL),
(504,'a','a',1,1,NULL,NULL,NULL,NULL,1,'MO','MO',NULL);

-- ============================================================
-- BẢNG: vongthi
-- ============================================================
CREATE TABLE `vongthi` (
  `idVongThi` int NOT NULL AUTO_INCREMENT,
  `idSK` int NOT NULL,
  `tenVongThi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `thuTu` int DEFAULT '1',
  `ngayBatDau` datetime DEFAULT NULL,
  `ngayKetThuc` datetime DEFAULT NULL,
  `thoiGianDongNop` datetime DEFAULT NULL COMMENT 'Deadline nộp sản phẩm. NULL = chưa đặt',
  `dongNopThuCong` tinyint DEFAULT '0' COMMENT '1 = BTC bấm đóng thủ công',
  PRIMARY KEY (`idVongThi`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `vongthi_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=501;

INSERT INTO `vongthi` VALUES
(1,1,'Vòng Sơ Loại','Nộp báo cáo tóm tắt',1,'2026-03-15 00:00:00','2026-03-20 00:00:00',NULL,0),
(2,1,'Vòng Chung Kết','Bảo vệ trước hội đồng',2,'2026-05-15 00:00:00','2026-05-20 00:00:00',NULL,0),
(3,11,'vong 1','',1,'2026-02-19 14:48:00','2026-02-22 23:48:00',NULL,0),
(4,11,'vong 2','',2,'2026-02-28 22:01:00','2026-03-08 22:01:00',NULL,0),
(500,500,'Vòng Sơ Loại Hackathon','Nộp mã nguồn và tài liệu',1,'2026-02-25 00:00:00','2026-03-10 00:00:00',NULL,0);

-- ============================================================
-- BẢNG: vaitronhom
-- [MIGRATION] Đảm bảo id=3 'Giảng viên hướng dẫn' tồn tại
-- ============================================================
CREATE TABLE `vaitronhom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenvaitronhom` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=4;

INSERT INTO `vaitronhom` VALUES
(1,'Trưởng nhóm'),
(2,'Thành viên'),
(3,'Giảng viên hướng dẫn');

-- ============================================================
-- BẢNG: nhom
-- [MIGRATION] Đổi tên cột idnhomtruong → idChuNhom
-- ============================================================
CREATE TABLE `nhom` (
  `idnhom` int NOT NULL AUTO_INCREMENT,
  `idSK` int DEFAULT NULL,
  `idChuNhom` int DEFAULT NULL COMMENT 'Người sở hữu nhóm — GV nếu GV tạo, SV nếu SV tạo. NULL khi nhóm GV tạo chưa có trưởng SV',
  `manhom` varchar(20) DEFAULT NULL,
  `ngaytao` datetime DEFAULT NULL,
  `isActive` tinyint DEFAULT '1',
  PRIMARY KEY (`idnhom`),
  UNIQUE KEY `manhom` (`manhom`),
  KEY `idChuNhom` (`idChuNhom`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `nhom_fk_chunhom` FOREIGN KEY (`idChuNhom`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `nhom_ibfk_2` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=504;

INSERT INTO `nhom` VALUES
(1,1,4,'GRP_AI_01','2026-02-11 22:11:23',1),
(2,1,5,'GRP_IOT_01','2026-02-21 12:51:34',1),
(3,11,4,'GRP_THDH_01','2026-02-21 12:51:34',1),
(4,11,6,'GRP_THDH_02','2026-02-21 12:51:34',1),
(5,11,5,'GRP_THDH_03','2026-02-21 12:51:34',1),
(6,11,6,'GRP_177175480715','2026-02-22 17:06:47',1),
(500,500,4,'GRP_HACK_01','2026-02-23 15:34:08',1),
(501,500,6,'GRP_HACK_02','2026-02-23 15:34:08',1),
(502,24,NULL,'GRP_24_1772009346','2026-02-25 15:49:06',1),
(503,24,NULL,'GRP_24_1772009407','2026-02-25 15:50:07',1);

-- ============================================================
-- BẢNG: thanhviennhom
-- ============================================================
CREATE TABLE `thanhviennhom` (
  `idnhom` int NOT NULL,
  `idtk` int DEFAULT NULL,
  `idvaitronhom` int DEFAULT NULL,
  `trangthai` tinyint NOT NULL DEFAULT '0' COMMENT '0:chờ duyệt, 1:đã tham gia',
  `ngaythamgia` datetime DEFAULT CURRENT_TIMESTAMP,
  KEY `idnhom` (`idnhom`),
  KEY `idtk` (`idtk`),
  KEY `idvaitronhom` (`idvaitronhom`),
  CONSTRAINT `thanhviennhom_ibfk_1` FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `thanhviennhom_ibfk_2` FOREIGN KEY (`idtk`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `thanhviennhom_ibfk_3` FOREIGN KEY (`idvaitronhom`) REFERENCES `vaitronhom` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `thanhviennhom` VALUES
(1,4,1,1,'2026-02-11 22:11:23'),
(1,5,2,1,'2026-02-11 22:11:23'),
(6,6,1,1,'2026-02-22 17:06:47'),
(6,5,2,1,'2026-02-23 09:18:51'),
(500,4,1,1,'2026-02-23 15:34:08'),
(500,5,2,1,'2026-02-23 15:34:08'),
(501,6,1,1,'2026-02-23 15:34:08'),
(501,8,2,1,'2026-02-23 15:34:08'),
(502,2,3,0,'2026-02-25 15:49:06'),
(503,2,3,1,'2026-02-25 15:50:07');

-- ============================================================
-- BẢNG: thongtinnhom
-- ============================================================
CREATE TABLE `thongtinnhom` (
  `idthongtin` int NOT NULL AUTO_INCREMENT,
  `idnhom` int NOT NULL,
  `tennhom` varchar(50) DEFAULT NULL,
  `mota` text,
  `soluongtoida` int DEFAULT '5' COMMENT 'soluongtoida>0',
  `dangtuyen` tinyint DEFAULT '1',
  PRIMARY KEY (`idthongtin`),
  KEY `idnhom` (`idnhom`),
  CONSTRAINT `thongtinnhom_ibfk_1` FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=504;

INSERT INTO `thongtinnhom` VALUES
(1,1,'AI Pioneers','Nhóm nghiên cứu Computer Vision',5,0),
(2,6,'a','a',5,1),
(500,500,'Bug Busters','Đội chuyên fix bug và tạo bug mới',5,0),
(501,501,'Cyber Ninjas','Đội ninja code dạo đêm khuya',5,0),
(502,502,'a','a',5,1),
(503,503,'d','d',5,1);

-- ============================================================
-- BẢNG: loaitailieu
-- ============================================================
CREATE TABLE `loaitailieu` (
  `idtailieu` int NOT NULL AUTO_INCREMENT,
  `loaitailieu` varchar(100) NOT NULL,
  `mota` text NOT NULL,
  `dinhDangChoPhep` varchar(200) DEFAULT NULL COMMENT 'Định dạng cho phép, vd: pdf,docx hoặc url. NULL = không giới hạn',
  `dungLuongToiDa` int DEFAULT NULL COMMENT 'Dung lượng tối đa KB. NULL = không giới hạn',
  PRIMARY KEY (`idtailieu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=4;

INSERT INTO `loaitailieu` VALUES
(1,'Báo cáo tóm tắt','File PDF mô tả ngắn gọn',NULL,NULL),
(2,'Báo cáo toàn văn','File PDF đầy đủ',NULL,NULL),
(3,'Source Code','Link Github hoặc file Zip',NULL,NULL);

-- ============================================================
-- BẢNG: chude
-- ============================================================
CREATE TABLE `chude` (
  `idChuDe` int NOT NULL AUTO_INCREMENT,
  `tenChuDe` varchar(200) DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `moTaChuDe` text,
  `isActive` tinyint DEFAULT '1',
  PRIMARY KEY (`idChuDe`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `chude_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=3;

INSERT INTO `chude` VALUES
(1,'Trí tuệ nhân tạo (AI)',1,'Các ứng dụng AI trong thực tế',1),
(2,'Internet vạn vật (IoT)',1,'Giải pháp nhà thông minh, nông nghiệp thông minh',1);

-- ============================================================
-- BẢNG: chude_sukien
-- ============================================================
CREATE TABLE `chude_sukien` (
  `idChuDeSK` int NOT NULL AUTO_INCREMENT,
  `idSK` int NOT NULL,
  `idchude` int DEFAULT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isActive` int DEFAULT '1',
  PRIMARY KEY (`idChuDeSK`),
  KEY `idSK` (`idSK`),
  KEY `idchude` (`idchude`),
  CONSTRAINT `chude_sukien_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  CONSTRAINT `chude_sukien_ibfk_2` FOREIGN KEY (`idchude`) REFERENCES `chude` (`idChuDe`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3;

INSERT INTO `chude_sukien` VALUES
(1,1,1,'Chuyên đề AI',1),
(2,1,2,'Chuyên đề IoT',1);

-- ============================================================
-- BẢNG: sanpham
-- ============================================================
CREATE TABLE `sanpham` (
  `idSanPham` int NOT NULL AUTO_INCREMENT,
  `idNhom` int DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idChuDeSK` int DEFAULT NULL,
  `idloaitailieu` int DEFAULT NULL,
  `moTataiLieu` text,
  `TrangThai` varchar(50) DEFAULT NULL COMMENT 'Tên trạng thái: Chờ/Đã duyệt/Bị loại...',
  `isActive` int DEFAULT '0',
  `tensanpham` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`idSanPham`),
  KEY `idloaitailieu` (`idloaitailieu`),
  KEY `idChuDeSK` (`idChuDeSK`),
  KEY `idNhom` (`idNhom`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `sanpham_ibfk_1` FOREIGN KEY (`idloaitailieu`) REFERENCES `loaitailieu` (`idtailieu`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sanpham_ibfk_2` FOREIGN KEY (`idChuDeSK`) REFERENCES `chude_sukien` (`idChuDeSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sanpham_ibfk_3` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `sanpham_ibfk_4` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=502;

INSERT INTO `sanpham` VALUES
(1,1,1,1,1,'Link Overleaf báo cáo','Chờ duyệt',1,'Hệ thống điểm danh bằng nhận diện khuôn mặt'),
(2,2,1,2,2,'Link Google Drive chứa báo cáo toàn văn và video demo hệ thống','Chờ duyệt',1,'Hệ thống nhà kính thông minh giám sát qua IoT'),
(3,1,1,1,2,'Báo cáo chi tiết quá trình huấn luyện mô hình YOLOv8','Chờ duyệt',1,'Nhận diện rác thải tái chế bằng Deep Learning'),
(4,3,11,NULL,3,'Link Github mã nguồn ứng dụng Android/iOS','Chờ duyệt',1,'Ứng dụng di động hỗ trợ sinh viên ôn thi trắc nghiệm'),
(5,4,11,NULL,1,'File PDF báo cáo tóm tắt thuật toán','Chờ duyệt',1,'Thuật toán tối ưu hóa lịch biểu giảng đường đại học'),
(6,5,11,NULL,3,'Source code C# Winform','Chờ duyệt',1,'Phần mềm quản lý chi tiêu cá nhân tích hợp AI'),
(7,3,11,NULL,2,'Tài liệu phân tích thiết kế hệ thống','Chờ duyệt',1,'Hệ thống Blockchain lưu trữ văn bằng chứng chỉ'),
(500,500,500,NULL,3,'Link Github: github.com/bugbusters/ai-traffic','Chờ duyệt',1,'Hệ thống cảnh báo giao thông AI'),
(501,501,500,NULL,3,'Link Github: github.com/cyberninjas/pomo3d','Chờ duyệt',1,'App quản lý thời gian Pomodoro 3D');

-- ============================================================
-- BẢNG: sanpham_vongthi
-- ============================================================
CREATE TABLE `sanpham_vongthi` (
  `idSanPham` int NOT NULL,
  `idVongThi` int NOT NULL,
  `diemTrungBinh` decimal(5,0) DEFAULT NULL,
  `xepLoai` varchar(50) DEFAULT NULL COMMENT 'Đạt/Không đạt/Xuất sắc',
  `trangThai` varchar(50) DEFAULT NULL COMMENT 'Chờ chấm/Đã chấm/Bị loại',
  `ngayCapNhat` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idSanPham`,`idVongThi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `sanpham_vongthi` VALUES
(2,1,42,NULL,'Đã duyệt','2026-02-21 14:51:53'),
(7,3,43,NULL,'Đã duyệt','2026-02-21 16:08:46'),
(500,500,NULL,NULL,'Đã nộp','2026-02-23 15:34:08'),
(501,500,NULL,NULL,'Đã nộp','2026-02-23 15:34:08');

-- ============================================================
-- BẢNG: botieuchi_tieuchi
-- ============================================================
CREATE TABLE `botieuchi_tieuchi` (
  `idBoTieuChi` int NOT NULL,
  `idTieuChi` int NOT NULL,
  `tyTrong` decimal(5,2) DEFAULT '1.00',
  `diemToiDa` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`idBoTieuChi`,`idTieuChi`),
  KEY `idTieuChi` (`idTieuChi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `botieuchi_tieuchi` VALUES
(1,1,1.00,10.00),(1,2,1.00,10.00),(1,3,1.00,10.00),(1,4,1.00,10.00),(1,5,1.00,10.00),
(2,1,1.00,9.00),(2,2,1.00,10.00),(2,3,1.00,8.00),(2,4,1.00,10.00),(2,5,1.00,10.00),
(3,1,1.00,9.00),(3,2,1.00,10.00),(3,3,1.00,8.00),(3,4,1.00,10.00),(3,5,1.00,10.00),
(4,1,1.00,10.00),(4,2,1.00,10.00),(4,3,1.00,10.00),(4,4,1.00,10.00),(4,5,1.00,10.00),(4,6,1.00,10.00);

-- ============================================================
-- BẢNG: tieuchi
-- ============================================================
CREATE TABLE `tieuchi` (
  `idTieuChi` int NOT NULL AUTO_INCREMENT,
  `noiDungTieuChi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idTieuChi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=7;

INSERT INTO `tieuchi` VALUES
(1,'Tính cấp thiết của đề tài'),
(2,'Phương pháp nghiên cứu'),
(3,'Kết quả đạt được'),
(4,'Hình thức trình bày'),
(5,'tiêu chí điểm rèn luyện'),
(6,'Hello');

ALTER TABLE `botieuchi_tieuchi`
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_1` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`),
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_2` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`);

-- ============================================================
-- BẢNG: phancongcham
-- ============================================================
CREATE TABLE `phancongcham` (
  `idPhanCongCham` int NOT NULL AUTO_INCREMENT,
  `idGV` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int NOT NULL,
  `trangThaiXacNhan` varchar(50) DEFAULT 'Chờ xác nhận' COMMENT 'Chờ xác nhận/Đã xác nhận/Từ chối',
  `ngayXacNhan` datetime NOT NULL,
  `isActive` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`idPhanCongCham`),
  KEY `idGV` (`idGV`),
  KEY `idBoTieuChi` (`idBoTieuChi`),
  KEY `idSK` (`idSK`),
  KEY `idVongThi` (`idVongThi`),
  CONSTRAINT `phancongcham_ibfk_1` FOREIGN KEY (`idGV`) REFERENCES `giangvien` (`idGV`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `phancongcham_ibfk_2` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `phancongcham_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `phancongcham_ibfk_4` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=502;

INSERT INTO `phancongcham` VALUES
(1,1,1,1,1,'Đã xác nhận','0000-00-00 00:00:00',1),
(2,2,1,1,1,'Đã xác nhận','0000-00-00 00:00:00',1),
(3,3,1,1,1,'Đã xác nhận','0000-00-00 00:00:00',1),
(4,1,11,3,1,'Đã xác nhận','2026-02-21 15:49:16',1),
(500,2,500,500,1,'Đã xác nhận','2026-02-24 08:34:17',1),
(501,3,500,500,1,'Đã xác nhận','2026-02-24 08:35:06',1);

-- ============================================================
-- BẢNG: chamtieuchi
-- ============================================================
CREATE TABLE `chamtieuchi` (
  `idChamDiem` int NOT NULL AUTO_INCREMENT,
  `idPhanCongCham` int DEFAULT NULL,
  `idSanPham` int DEFAULT NULL,
  `idTieuChi` int DEFAULT NULL,
  `diem` decimal(5,2) DEFAULT NULL,
  `nhanXet` text,
  `thoiGianCham` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idChamDiem`),
  KEY `idPhanCongCham` (`idPhanCongCham`),
  KEY `idSanPham` (`idSanPham`),
  KEY `idTieuChi` (`idTieuChi`),
  CONSTRAINT `chamtieuchi_ibfk_1` FOREIGN KEY (`idPhanCongCham`) REFERENCES `phancongcham` (`idPhanCongCham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chamtieuchi_ibfk_2` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chamtieuchi_ibfk_3` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=51;

INSERT INTO `chamtieuchi` VALUES
(1,1,1,1,9.50,'Tốt','2026-02-21 14:41:11'),
(2,1,1,2,9.00,'Được','2026-02-21 14:41:11'),
(3,1,1,3,9.50,'Rất tốt','2026-02-21 14:41:11'),
(4,1,1,4,9.00,'Tốt','2026-02-21 14:41:11'),
(5,1,1,5,10.00,'Xuất sắc','2026-02-21 14:41:11'),
(6,2,1,1,8.00,'Khá','2026-02-21 14:41:11'),
(7,2,1,2,8.50,'Khá','2026-02-21 14:41:11'),
(8,2,1,3,8.00,'Ổn','2026-02-21 14:41:11'),
(9,2,1,4,8.50,'Khá','2026-02-21 14:41:11'),
(10,2,1,5,8.00,'Được','2026-02-21 14:41:11'),
(11,3,1,1,8.50,'Khá','2026-02-21 14:41:11'),
(12,3,1,2,8.00,'Khá','2026-02-21 14:41:11'),
(13,3,1,3,2.00,'Phương pháp sai lệch','2026-02-21 14:41:11'),
(14,3,1,4,3.00,'Cẩu thả','2026-02-21 14:41:11'),
(15,3,1,5,8.50,'Khá','2026-02-21 14:41:11'),
(16,1,2,1,8.50,'Tốt','2026-02-21 14:41:11'),
(17,1,2,2,8.50,'Được','2026-02-21 14:41:11'),
(18,1,2,3,8.00,'Ổn','2026-02-21 14:41:11'),
(19,1,2,4,9.00,'Tốt','2026-02-21 14:41:11'),
(20,1,2,5,8.50,'Tốt','2026-02-21 14:41:11'),
(21,2,2,1,8.00,'Khá','2026-02-21 14:41:11'),
(22,2,2,2,8.00,'Khá','2026-02-21 14:41:11'),
(23,2,2,3,8.50,'Tốt','2026-02-21 14:41:11'),
(24,2,2,4,8.50,'Khá','2026-02-21 14:41:11'),
(25,2,2,5,8.00,'Được','2026-02-21 14:41:11'),
(26,4,7,1,9.00,'','2026-02-21 15:49:16'),
(27,4,7,2,8.00,'','2026-02-21 15:49:16'),
(28,4,7,3,9.00,'','2026-02-21 15:49:16'),
(29,4,7,4,8.00,'','2026-02-21 15:49:16'),
(30,4,7,5,9.00,'','2026-02-21 15:49:16'),
(31,500,500,1,9.00,'Ý tưởng rất thực tế, có tính ứng dụng cao','2026-02-23 15:34:08'),
(32,500,500,2,8.50,'Cần làm rõ thuật toán nhận diện','2026-02-23 15:34:08'),
(33,500,500,3,9.00,'Demo chạy tốt, ít độ trễ','2026-02-23 15:34:08'),
(34,500,500,4,8.00,'Báo cáo cần chỉn chu hơn','2026-02-23 15:34:08'),
(35,500,500,5,9.00,'Sinh viên nhiệt tình','2026-02-23 15:34:08'),
(36,501,500,1,8.50,'Khá tốt','2026-02-23 15:34:08'),
(37,501,500,2,8.00,'Phương pháp ổn định','2026-02-23 15:34:08'),
(38,501,500,3,8.50,'Tốt','2026-02-23 15:34:08'),
(39,501,500,4,8.00,'Đạt yêu cầu','2026-02-23 15:34:08'),
(40,501,500,5,8.50,'Tốt','2026-02-23 15:34:08'),
(41,500,501,1,10.00,'','2026-02-24 08:34:17'),
(42,500,501,2,10.00,'','2026-02-24 08:34:17'),
(43,500,501,3,10.00,'','2026-02-24 08:34:17'),
(44,500,501,4,10.00,'','2026-02-24 08:34:17'),
(45,500,501,5,10.00,'','2026-02-24 08:34:17'),
(46,501,501,1,10.00,'','2026-02-24 08:35:06'),
(47,501,501,2,10.00,'','2026-02-24 08:35:06'),
(48,501,501,3,10.00,'','2026-02-24 08:35:06'),
(49,501,501,4,10.00,'','2026-02-24 08:35:06'),
(50,501,501,5,10.00,'','2026-02-24 08:35:06');

-- ============================================================
-- BẢNG: canhbaodiem
-- ============================================================
CREATE TABLE `canhbaodiem` (
  `idCanhBao` int NOT NULL AUTO_INCREMENT,
  `idSanPham` int NOT NULL,
  `idVongThi` int NOT NULL,
  `doLech` decimal(5,2) NOT NULL,
  `trangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xử lý',
  `thoiGian` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idCanhBao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG: cauhinh_tieuchi_sk
-- ============================================================
CREATE TABLE `cauhinh_tieuchi_sk` (
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int DEFAULT NULL,
  PRIMARY KEY (`idSK`,`idVongThi`),
  KEY `idVongThi` (`idVongThi`),
  KEY `idBoTieuChi` (`idBoTieuChi`),
  CONSTRAINT `cauhinh_tieuchi_sk_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`),
  CONSTRAINT `cauhinh_tieuchi_sk_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`),
  CONSTRAINT `cauhinh_tieuchi_sk_ibfk_3` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cauhinh_tieuchi_sk` VALUES
(1,2,1),(11,3,1),(500,500,1),(11,4,3);

-- ============================================================
-- BẢNG: chungnhan
-- ============================================================
CREATE TABLE `chungnhan` (
  `idChungNhan` int NOT NULL AUTO_INCREMENT,
  `maChungNhan` varchar(50) DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `loaiChungNhan` varchar(50) DEFAULT NULL,
  `ngayCap` datetime DEFAULT NULL,
  `filePDF` varchar(255) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idChungNhan`),
  KEY `idGiaiThuong` (`idGiaiThuong`),
  KEY `idSK` (`idSK`),
  KEY `idTK` (`idTK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- ============================================================
-- BẢNG: lichtrinh  (thuần timeline sự kiện — không còn GPS/điểm danh)
-- ============================================================
CREATE TABLE `lichtrinh` (
  `idLichTrinh` int NOT NULL AUTO_INCREMENT,
  `idSK`        int NOT NULL,
  `idVongThi`   int DEFAULT NULL,
  `tenHoatDong` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thoiGian`    datetime NOT NULL,
  `diaDiem`     varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idLichTrinh`),
  KEY `idSK`      (`idSK`),
  KEY `idVongThi` (`idVongThi`),
  KEY `idx_lt_sk` (`idSK`),
  CONSTRAINT `lichtrinh_ibfk_1` FOREIGN KEY (`idSK`)      REFERENCES `sukien`   (`idSK`)      ON DELETE CASCADE,
  CONSTRAINT `lichtrinh_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi`  (`idVongThi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5;

INSERT INTO `lichtrinh` VALUES
(1, 11,  NULL, 'Vòng 1', '2026-02-22 13:18:00', ''),
(2, 11,  NULL, 'Hi',     '2026-02-22 13:59:00', 'a'),
(3, 503, NULL, 'k',      '2026-02-25 14:49:00', ''),
(4, 503, NULL, 'kkk',    '2026-02-25 14:49:00', 'l');

-- ============================================================
-- BẢNG: phien_diemdanh  (mỗi buổi/phiên điểm danh — độc lập)
-- ============================================================
CREATE TABLE `phien_diemdanh` (
  `idPhienDD`       int           NOT NULL AUTO_INCREMENT,

  -- Thuộc về đâu
  `idSK`            int           NOT NULL                    COMMENT 'Sự kiện',
  `idVongThi`       int           DEFAULT NULL                COMMENT 'Vòng thi (nullable)',
  `idLichTrinh`     int           DEFAULT NULL                COMMENT 'Gắn với hoạt động lịch trình nếu muốn (nullable)',

  -- Mô tả phiên
  `tenPhien`        varchar(200)  NOT NULL                    COMMENT 'VD: Điểm danh buổi sáng vòng 1',
  `thoiGianBatDau`  datetime      DEFAULT NULL                COMMENT 'Kế hoạch dự kiến — BTC đặt trước',

  -- Cấu hình GPS (nullable — không bắt buộc)
  `viTriLat`        decimal(10,7) DEFAULT NULL                COMMENT 'Vĩ độ địa điểm điểm danh',
  `viTriLng`        decimal(10,7) DEFAULT NULL                COMMENT 'Kinh độ địa điểm điểm danh',
  `banKinhDiemDanh` int           DEFAULT 150                 COMMENT 'Bán kính hợp lệ tính bằng mét',

  -- Trạng thái thực tế (BTC bấm mở/đóng)
  `thoiGianMo`      datetime      DEFAULT NULL                COMMENT 'Timestamp thực tế lúc BTC bấm mở',
  `thoiGianDong`    datetime      DEFAULT NULL                COMMENT 'Timestamp thực tế lúc BTC bấm đóng',

  PRIMARY KEY (`idPhienDD`),
  KEY `idx_phien_sk`        (`idSK`),
  KEY `idx_phien_vong`      (`idVongThi`),
  KEY `idx_phien_lichtrinh` (`idLichTrinh`),

  CONSTRAINT `phien_dd_ibfk_sk`
    FOREIGN KEY (`idSK`)        REFERENCES `sukien`    (`idSK`)        ON DELETE CASCADE,
  CONSTRAINT `phien_dd_ibfk_vong`
    FOREIGN KEY (`idVongThi`)   REFERENCES `vongthi`   (`idVongThi`)   ON DELETE SET NULL,
  CONSTRAINT `phien_dd_ibfk_lich`
    FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5
  COMMENT='Mỗi bản ghi là một buổi/phiên điểm danh trong sự kiện';

-- Migrate data: 4 bản ghi lichtrinh cũ vốn đang hoạt động như phiên điểm danh
-- idPhienDD giữ nguyên = idLichTrinh cũ để FK trong diemdanh khớp sau khi đổi tên cột
INSERT INTO `phien_diemdanh`
  (`idPhienDD`, `idSK`, `idVongThi`, `idLichTrinh`,
   `tenPhien`,  `thoiGianBatDau`,
   `viTriLat`,  `viTriLng`,  `banKinhDiemDanh`,
   `thoiGianMo`,  `thoiGianDong`) VALUES
(1, 11,  NULL, NULL, 'Vòng 1',  '2026-02-22 13:18:00', 21.0377777, 105.8518399, 100, '2026-02-22 13:20:43', '2026-02-22 13:50:43'),
(2, 11,  NULL, NULL, 'Hi',      '2026-02-22 13:59:00', 21.0377862, 105.8518308, 150, '2026-02-22 13:59:55', '2026-02-22 14:29:55'),
(3, 503, NULL, NULL, 'k',       '2026-02-25 14:49:00', NULL,       NULL,        150, NULL,                  NULL),
(4, 503, NULL, NULL, 'kkk',     '2026-02-25 14:49:00', 21.0374280, 105.7834034, 150, NULL,                  NULL);

-- ============================================================
-- BẢNG: diemdanh  (idLichTrinh → idPhienDD)
-- ============================================================
CREATE TABLE `diemdanh` (
  `idDiemDanh`       int           NOT NULL AUTO_INCREMENT,
  `idNhom`           int           DEFAULT NULL,
  `idTK`             int           DEFAULT NULL,
  `thoiGianDiemDanh` datetime      DEFAULT CURRENT_TIMESTAMP,
  `hienDien`         tinyint       DEFAULT '1',
  `ghiChu`           varchar(100)  DEFAULT NULL,
  `idPhienDD`        int           DEFAULT NULL                COMMENT 'Liên kết phiên điểm danh',
  `phuongThuc`       enum('QR','GPS','Manual','NFC') DEFAULT 'QR' COMMENT 'Cách điểm danh',
  `viTriLat`         decimal(10,7) DEFAULT NULL                COMMENT 'Vị trí SV lúc điểm danh',
  `viTriLng`         decimal(10,7) DEFAULT NULL,
  `ipDiemDanh`       varchar(45)   DEFAULT NULL                COMMENT 'IP thiết bị',
  PRIMARY KEY (`idDiemDanh`),
  KEY `idNhom`              (`idNhom`),
  KEY `idTK`                (`idTK`),
  KEY `idx_dd_phien`        (`idPhienDD`),
  KEY `idx_dd_tk_phien`     (`idTK`, `idPhienDD`),
  CONSTRAINT `diemdanh_ibfk_1` FOREIGN KEY (`idNhom`)    REFERENCES `nhom`           (`idnhom`)    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `diemdanh_ibfk_2` FOREIGN KEY (`idTK`)      REFERENCES `taikhoan`       (`idTK`)      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `diemdanh_ibfk_3` FOREIGN KEY (`idPhienDD`) REFERENCES `phien_diemdanh` (`idPhienDD`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=3;

INSERT INTO `diemdanh` VALUES
(1, NULL, 6, '2026-02-22 13:22:32', 1, 'Tự điểm danh', 1, 'GPS', 21.0377847, 105.8518394, '::1'),
(2, NULL, 1, '2026-02-22 14:02:16', 1, 'Tự điểm danh', 2, 'GPS', 21.0377867, 105.8518433, '::1');

  `loaiToanTu` enum('logic','compare') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idToanTu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=8;

INSERT INTO `toantu` VALUES
(1,'=','Bằng','compare'),
(2,'>','Lớn hơn','compare'),
(3,'<','Nhỏ hơn','compare'),
(4,'>=','Lớn hơn hoặc bằng','compare'),
(5,'<=','Nhỏ hơn hoặc bằng','compare'),
(6,'AND','Và','logic'),
(7,'OR','Hoặc','logic');

-- ============================================================
-- BẢNG: thuoctinh_kiemtra
-- [MIGRATION] loaiApDung: THAMGIA → THAMGIA_SV cho SV; thêm id 12,13 cho GV
-- ============================================================
CREATE TABLE `thuoctinh_kiemtra` (
  `idThuocTinhKiemTra` int NOT NULL AUTO_INCREMENT,
  `tenThuocTinh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenTruongDL` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bangDuLieu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `loaiApDung` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA_SV',
  PRIMARY KEY (`idThuocTinhKiemTra`),
  UNIQUE KEY `uq_thuoctinh` (`tenTruongDL`,`bangDuLieu`,`loaiApDung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=18;

INSERT INTO `thuoctinh_kiemtra` VALUES
(1,'Điểm trung bình (GPA)','GPA','sinhvien','THAMGIA_SV'),
(2,'Điểm rèn luyện','DRL','sinhvien','THAMGIA_SV'),
(3,'Điểm trung bình vòng thi','diemTrungBinh','sanpham_vongthi','VONGTHI'),
(4,'Xếp loại vòng thi','xepLoai','sanpham_vongthi','VONGTHI'),
(5,'Trạng thái vòng thi','trangThai','sanpham_vongthi','VONGTHI'),
(6,'Trạng thái sản phẩm','TrangThai','sanpham','SANPHAM'),
(7,'Loại tài liệu','idloaitailieu','sanpham','SANPHAM'),
(8,'Kích hoạt sản phẩm','isActive','sanpham','SANPHAM'),
(9,'Điểm tổng kết','diemTongKet','ketqua','GIAITHUONG'),
(10,'Xếp hạng','xepHang','ketqua','GIAITHUONG'),
(11,'Đã có giải','idGiaiThuong','ketqua','GIAITHUONG'),
(12,'Khoa giảng dạy','idKhoa','giangvien','THAMGIA_GV'),
(13,'Học hàm/học vị','hocHam','giangvien','THAMGIA_GV');

-- ============================================================
-- BẢNG: dieukien
-- ============================================================
CREATE TABLE `dieukien` (
  `idDieuKien` int NOT NULL AUTO_INCREMENT,
  `loaiDieuKien` enum('DON','TOHOP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenDieuKien` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`idDieuKien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=14;

INSERT INTO `dieukien` VALUES
(1,'DON','Điều kiện GPA khá','Sinh viên phải có GPA từ 2.5 trở lên'),
(2,'DON','diem tb',''),
(3,'TOHOP','to hop 1',NULL),
(4,'TOHOP','',NULL),
(5,'TOHOP','to hop 2',NULL),
(6,'DON','DK_DON_699898cd17f51',''),
(7,'DON','DK_DON_699898cd18cb0',''),
(8,'TOHOP','TOHOP_699898cd193e9',''),
(9,'DON','DK_DON_69989912a0f0c',''),
(10,'DON','DK_DON_69989912a3d56',''),
(11,'DON','DK_DON_69989912a4b11',''),
(12,'TOHOP','TOHOP_69989912a5eb6',''),
(13,'TOHOP','TOHOP_69989912a7900','');

-- ============================================================
-- BẢNG: dieukien_don
-- ============================================================
CREATE TABLE `dieukien_don` (
  `idDieuKien` int NOT NULL,
  `idThuocTinhKiemTra` int NOT NULL,
  `idToanTu` int NOT NULL,
  `giaTriSoSanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`idDieuKien`),
  KEY `idThuocTinhKiemTra` (`idThuocTinhKiemTra`),
  KEY `idToanTu` (`idToanTu`),
  CONSTRAINT `dieukien_don_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`),
  CONSTRAINT `dieukien_don_ibfk_2` FOREIGN KEY (`idThuocTinhKiemTra`) REFERENCES `thuoctinh_kiemtra` (`idThuocTinhKiemTra`),
  CONSTRAINT `dieukien_don_ibfk_3` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dieukien_don` VALUES
(1,1,4,'2.5'),
(2,1,1,'3.0'),
(6,2,1,'80'),
(7,1,1,'3.0'),
(9,3,1,'50'),
(10,5,1,'hoạt động'),
(11,4,4,'1');

-- ============================================================
-- BẢNG: tohop_dieukien
-- ============================================================
CREATE TABLE `tohop_dieukien` (
  `idDieuKien` int NOT NULL,
  `idDieuKienTrai` int NOT NULL,
  `idDieuKienPhai` int NOT NULL,
  `idToanTu` int NOT NULL,
  PRIMARY KEY (`idDieuKien`),
  KEY `idDieuKienTrai` (`idDieuKienTrai`),
  KEY `idDieuKienPhai` (`idDieuKienPhai`),
  KEY `idToanTu` (`idToanTu`),
  CONSTRAINT `tohop_dieukien_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`),
  CONSTRAINT `tohop_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienTrai`) REFERENCES `dieukien` (`idDieuKien`),
  CONSTRAINT `tohop_dieukien_ibfk_3` FOREIGN KEY (`idDieuKienPhai`) REFERENCES `dieukien` (`idDieuKien`),
  CONSTRAINT `tohop_dieukien_ibfk_4` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tohop_dieukien` VALUES
(3,1,2,6),(4,3,2,6),(5,3,1,6),(8,6,7,6),(12,10,11,7),(13,9,12,6);

-- ============================================================
-- BẢNG: nhom_quyen
-- ============================================================
CREATE TABLE `nhom_quyen` (
  `idNhomQuyen` int NOT NULL AUTO_INCREMENT,
  `tenNhom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên nhóm hiển thị trên UI',
  `thuTu` int DEFAULT '0' COMMENT 'Thứ tự hiển thị',
  PRIMARY KEY (`idNhomQuyen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=7;

INSERT INTO `nhom_quyen` VALUES
(1,'Cấu hình sự kiện',1),
(2,'Chấm điểm',2),
(3,'Sản phẩm',3),
(4,'Kết quả',4),
(5,'Nhóm thi',5),
(6,'Quản trị hệ thống',0);

-- ============================================================
-- BẢNG: quyen
-- ============================================================
CREATE TABLE `quyen` (
  `idQuyen` int NOT NULL AUTO_INCREMENT,
  `maQuyen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenQuyen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `idNhomQuyen` int DEFAULT NULL COMMENT 'FK → nhom_quyen, dùng để gom nhóm trên UI',
  `maQuyen_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phamVi` enum('HE_THONG','SU_KIEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SU_KIEN',
  PRIMARY KEY (`idQuyen`),
  UNIQUE KEY `maQuyen` (`maQuyen`),
  KEY `idNhomQuyen` (`idNhomQuyen`),
  CONSTRAINT `quyen_ibfk_nhomquyen` FOREIGN KEY (`idNhomQuyen`) REFERENCES `nhom_quyen` (`idNhomQuyen`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=39;

INSERT INTO `quyen` VALUES
(24,'cauhinh_sukien','Cấu hình thông tin sự kiện','Sửa tên, mô tả, thời gian, cấp tổ chức',1,'cauhinh_sukien','SU_KIEN'),
(25,'cauhinh_vongthi','Cấu hình vòng thi','Thêm/sửa/xóa vòng thi, thời gian mỗi vòng',1,'cauhinh_vongthi','SU_KIEN'),
(26,'cauhinh_tailieu','Cấu hình loại tài liệu nộp','Thiết lập slot nộp tài liệu, định dạng cho phép',1,'cauhinh_tailieu','SU_KIEN'),
(27,'phan_cong_cham','Phân công giảng viên chấm điểm','Gán GV vào danh sách chấm điểm của vòng thi',1,'phan_cong_cham','SU_KIEN'),
(28,'nhap_diem','Nhập điểm chấm','Nhập điểm theo từng tiêu chí cho bài được phân công',2,'nhap_diem','SU_KIEN'),
(29,'xem_bai_phan_cong','Xem bài được phân công chấm','Xem nội dung sản phẩm của nhóm mình được phân công',2,'xem_bai_phan_cong','SU_KIEN'),
(30,'nop_san_pham','Nộp/cập nhật sản phẩm','Nộp hoặc ghi đè sản phẩm khi chưa đóng nộp',3,'nop_san_pham','SU_KIEN'),
(31,'xem_ketqua_truocCB','Xem kết quả trước công bố','Xem điểm và xếp hạng khi chưa công bố chính thức',4,'xem_ketqua_truocCB','SU_KIEN'),
(32,'xem_ketqua_sauCB','Xem kết quả sau công bố','Xem kết quả sau khi BTC đã công bố chính thức',4,'xem_ketqua_sauCB','SU_KIEN'),
(33,'quan_ly_nhom','Tạo và quản lý nhóm thi','Tạo nhóm, mời thành viên, đổi nhóm trưởng',5,'quan_ly_nhom','SU_KIEN'),
(34,'admin.users','Quản lý tài khoản','Truy cập trang admin/users, tạo và sửa tài khoản',6,'admin_users','HE_THONG'),
(35,'admin.events','Quản lý sự kiện','Tạo sự kiện mới, xem toàn bộ danh sách sự kiện',6,'admin_events','HE_THONG'),
(36,'admin.criteria','Quản lý bộ tiêu chí','Tạo và sửa bộ tiêu chí chấm điểm dùng chung toàn hệ thống',6,'admin_criteria','HE_THONG'),
(37,'admin.reports','Xem báo cáo tổng hợp','Xem thống kê và báo cáo toàn hệ thống',6,'admin_reports','HE_THONG'),
(38,'tao_su_kien','Tạo sự kiện','Cho phép tài khoản tạo sự kiện NCKH mới',6,'tao_su_kien','HE_THONG');

-- ============================================================
-- BẢNG: vaitro
-- ============================================================
CREATE TABLE `vaitro` (
  `idvatro` int NOT NULL AUTO_INCREMENT,
  `tenvaitro` varchar(100) DEFAULT NULL,
  `mota` text,
  `btcCoTheGan` tinyint DEFAULT '1' COMMENT '1: BTC gan thu cong duoc | 0: chi he thong tu gan',
  PRIMARY KEY (`idvatro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=7;

INSERT INTO `vaitro` VALUES
(1,'BTC','Ban tổ chức, toàn quyền cấu hình sự kiện',1),
(2,'GV_PHAN_BIEN','Giảng viên phản biện, chấm bài được phân công',0),
(3,'GV_HUONG_DAN','Giảng viên hướng dẫn, xem kết quả sau công bố',0),
(4,'THAM_GIA','Sinh viên tham gia thi, tạo nhóm và nộp bài',0),
(5,'GV_CHAM_DOCLAP','Chấm độc lập theo sản phẩm được phân công',0),
(6,'GV_CHAM_TIEUHAN','Chấm trong tiểu ban/hội đồng',0);

-- ============================================================
-- BẢNG: vaitro_quyen
-- ============================================================
CREATE TABLE `vaitro_quyen` (
  `idVaiTro` int NOT NULL,
  `idQuyen` int NOT NULL,
  PRIMARY KEY (`idVaiTro`,`idQuyen`),
  KEY `vaitro_quyen_ibfk_2` (`idQuyen`),
  CONSTRAINT `vaitro_quyen_ibfk_1` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idvatro`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `vaitro_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin gán quyền cho role hệ thống. Áp dụng mặc định mọi sự kiện.';

-- vaitro_quyen: BTC(1) toàn quyền SK | GV_PHAN_BIEN(2) chấm+xem | GV_HUONG_DAN(3) xem kq
-- THAM_GIA(4) nộp bài+xem+nhóm | GV_CHAM_DOCLAP(5) nhập điểm | GV_CHAM_TIEUHAN(6) nhập điểm
INSERT INTO `vaitro_quyen` VALUES
(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,31),(1,32),(1,33),
(2,28),(2,29),(2,32),
(3,32),
(4,30),(4,32),(4,33),
(5,28),(5,29),
(6,28),(6,29);

-- ============================================================
-- BẢNG: taikhoan_quyen
-- ============================================================
CREATE TABLE `taikhoan_quyen` (
  `idTK` int NOT NULL,
  `idQuyen` int NOT NULL,
  `isActive` tinyint DEFAULT '1',
  `thoiGianBatDau` datetime DEFAULT CURRENT_TIMESTAMP,
  `thoiGianKetThuc` datetime DEFAULT NULL,
  PRIMARY KEY (`idTK`,`idQuyen`),
  KEY `idQuyen` (`idQuyen`),
  CONSTRAINT `taikhoan_quyen_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  CONSTRAINT `taikhoan_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taikhoan_quyen` VALUES
(1,34,1,'2026-02-22 22:40:25',NULL),
(1,35,1,'2026-02-22 22:40:25',NULL),
(1,36,1,'2026-02-22 22:40:25',NULL),
(1,37,1,'2026-02-22 22:40:25',NULL),
(1,38,1,'2026-02-23 15:18:35',NULL),
(2,38,1,'2026-02-25 14:43:19',NULL),
(3,38,1,'2026-02-23 15:18:35',NULL),
(7,38,1,'2026-02-23 15:18:35',NULL),
(9,38,1,'2026-02-23 15:36:21',NULL),
(12,38,1,'2026-02-23 17:51:13',NULL);

-- ============================================================
-- BẢNG: taikhoan_vaitro_sukien
-- [MIGRATION] enum nguonTao: QUAT_NHOM → QUA_NHOM
-- ============================================================
CREATE TABLE `taikhoan_vaitro_sukien` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idTK` int NOT NULL,
  `idSK` int NOT NULL,
  `idVaiTro` int DEFAULT NULL COMMENT 'FK -> vaitro(idvatro)',
  `nguonTao` enum('BTC_THEM','PHANCONG_CHAM','QUA_NHOM','DANG_KY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'BTC_THEM: BTC thêm thủ công | PHANCONG_CHAM: sinh khi phân công chấm | QUA_NHOM: GV vào nhóm | DANG_KY: SV/GV tự đăng ký',
  `idNguoiCap` int DEFAULT NULL COMMENT 'idTK người thực hiện, NULL nếu tự động',
  `ngayCap` datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idTK` (`idTK`),
  KEY `idSK` (`idSK`),
  KEY `tvs_ibfk_nguoicap` (`idNguoiCap`),
  KEY `idx_tk_sk_active` (`idTK`,`idSK`,`isActive`),
  KEY `fk_tvs_vaitro` (`idVaiTro`),
  CONSTRAINT `fk_tvs_vaitro` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idvatro`),
  CONSTRAINT `tvs_ibfk_nguoicap` FOREIGN KEY (`idNguoiCap`) REFERENCES `taikhoan` (`idTK`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tvs_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tvs_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bảng trung tâm phân quyền: ai có role gì trong sự kiện nào.'
  AUTO_INCREMENT=25;

INSERT INTO `taikhoan_vaitro_sukien` VALUES
-- Rows 1-4: PHANCONG_CHAM → idVaiTro=5 (GV_CHAM_DOCLAP, schema mới)
(1,2,1,5,'PHANCONG_CHAM',NULL,'2026-02-22 22:27:53',1),
(2,3,1,5,'PHANCONG_CHAM',NULL,'2026-02-22 22:27:53',1),
(3,7,1,5,'PHANCONG_CHAM',NULL,'2026-02-22 22:27:53',1),
(4,2,11,5,'PHANCONG_CHAM',NULL,'2026-02-22 22:27:53',1),
(8,4,1,4,'DANG_KY',NULL,'2026-02-22 22:27:53',1),
(9,5,1,4,'DANG_KY',NULL,'2026-02-22 22:27:53',1),
(10,6,11,4,'DANG_KY',NULL,'2026-02-22 22:27:53',1),
(11,2,24,1,'BTC_THEM',2,'2026-02-23 15:30:58',1),
(12,2,500,1,'BTC_THEM',NULL,'2026-02-23 15:34:08',1),
(13,3,500,5,'PHANCONG_CHAM',NULL,'2026-02-23 15:34:08',1),
(14,7,500,5,'PHANCONG_CHAM',NULL,'2026-02-23 15:34:08',1),
(15,4,500,4,'DANG_KY',NULL,'2026-02-23 15:34:08',1),
(16,5,500,4,'DANG_KY',NULL,'2026-02-23 15:34:08',1),
(17,6,500,4,'DANG_KY',NULL,'2026-02-23 15:34:08',1),
(18,8,500,4,'DANG_KY',NULL,'2026-02-23 15:34:08',1),
(19,7,501,1,'BTC_THEM',7,'2026-02-23 16:05:16',1),
(20,1,502,1,'BTC_THEM',1,'2026-02-23 17:29:56',1),
(21,2,503,1,'BTC_THEM',2,'2026-02-23 17:32:23',1),
(22,1,504,1,'BTC_THEM',1,'2026-02-25 14:33:27',1),
(23,2,24,4,'DANG_KY',2,'2026-02-25 15:48:52',0),
(24,2,24,4,'DANG_KY',2,'2026-02-25 15:49:54',1);

-- ============================================================
-- BẢNG: quyche
-- ============================================================
CREATE TABLE `quyche` (
  `idQuyChe` int NOT NULL AUTO_INCREMENT,
  `tenQuyChe` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `loaiQuyChe` enum('THAMGIA_SV','THAMGIA_GV','VONGTHI','SANPHAM','GIAITHUONG') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA_SV',
  `idSK` int NOT NULL,
  PRIMARY KEY (`idQuyChe`),
  KEY `fk_quyche_sukien` (`idSK`),
  CONSTRAINT `fk_quyche_sukien` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=8;

INSERT INTO `quyche` VALUES
(1,'Quy chế tham gia NCKH 2026','Yêu cầu về học lực tối thiểu','THAMGIA_SV',1),
(4,'quy che tham gia','Ap dung cho sinh vien','THAMGIA_SV',11),
(5,'quy chế qua vòng','','VONGTHI',11),
(6,'j','j','THAMGIA_SV',503),
(7,'a','','THAMGIA_SV',503);

-- ============================================================
-- BẢNG: quyche_dieukien
-- ============================================================
CREATE TABLE `quyche_dieukien` (
  `idQuyChe` int NOT NULL,
  `idDieuKienCuoi` int NOT NULL,
  PRIMARY KEY (`idQuyChe`),
  KEY `idDieuKienCuoi` (`idDieuKienCuoi`),
  CONSTRAINT `quyche_dieukien_ibfk_1` FOREIGN KEY (`idQuyChe`) REFERENCES `quyche` (`idQuyChe`),
  CONSTRAINT `quyche_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienCuoi`) REFERENCES `dieukien` (`idDieuKien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quyche_dieukien` VALUES
(1,1),(4,8),(5,13);

-- ============================================================
-- BẢNG: quyentaosk
-- ============================================================
CREATE TABLE `quyentaosk` (
  `idgv` int NOT NULL,
  `idloaicap` int NOT NULL,
  KEY `idgv` (`idgv`),
  KEY `idloaicap` (`idloaicap`),
  CONSTRAINT `quyentaosk_ibfk_1` FOREIGN KEY (`idgv`) REFERENCES `giangvien` (`idGV`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `quyentaosk_ibfk_2` FOREIGN KEY (`idloaicap`) REFERENCES `loaicap` (`idLoaiCap`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- BẢNG: giaithuong
-- ============================================================
CREATE TABLE `giaithuong` (
  `idGiaiThuong` int NOT NULL AUTO_INCREMENT,
  `idSK` int NOT NULL,
  `tengiaithuong` varchar(200) DEFAULT NULL,
  `mota` text,
  `soluong` int DEFAULT '1',
  `giatri` decimal(15,0) DEFAULT NULL,
  `thutu` int DEFAULT '1',
  `isActive` int DEFAULT '1',
  PRIMARY KEY (`idGiaiThuong`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `giaithuong_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- BẢNG: ketqua
-- ============================================================
CREATE TABLE `ketqua` (
  `idKetQua` tinyint NOT NULL AUTO_INCREMENT,
  `idNhom` int DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `diemTongKet` decimal(5,2) DEFAULT NULL,
  `xepHang` int DEFAULT NULL,
  `ghiChu` text,
  `ngayXetGiai` datetime DEFAULT NULL,
  `isPublic` tinyint DEFAULT '0',
  PRIMARY KEY (`idKetQua`),
  KEY `idGiaiThuong` (`idGiaiThuong`),
  KEY `idNhom` (`idNhom`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `ketqua_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ketqua_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ketqua_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- BẢNG: chungnhan (foreign keys)
-- ============================================================
ALTER TABLE `chungnhan`
  ADD CONSTRAINT `chungnhan_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_2` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- ============================================================
-- BẢNG: phancong_doclap
-- ============================================================
CREATE TABLE `phancong_doclap` (
  `idSanPham` int NOT NULL,
  `idGV` int NOT NULL,
  `idVongThi` int NOT NULL,
  PRIMARY KEY (`idSanPham`,`idGV`,`idVongThi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phancong_doclap` VALUES
(1,1,1),(1,2,1),(1,3,1),(2,1,1),(2,2,1),(3,1,1),(3,2,1),
(6,1,3),(6,2,3),(7,1,3),(500,2,500),(500,3,500),(501,2,500),(501,3,500);

-- ============================================================
-- BẢNG: tieuban
-- ============================================================
CREATE TABLE `tieuban` (
  `idTieuBan` int NOT NULL AUTO_INCREMENT,
  `idSK` int NOT NULL,
  `idVongThi` int DEFAULT NULL,
  `tenTieuBan` varchar(100) DEFAULT NULL,
  `moTa` text,
  `isActive` tinyint DEFAULT '1',
  `ngayBaoCao` date DEFAULT NULL,
  `diaDiem` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idTieuBan`),
  KEY `idSK` (`idSK`),
  CONSTRAINT `tieuban_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=5;

INSERT INTO `tieuban` VALUES
(1,11,3,'Tiểu ban công nghệ AI',NULL,1,'2026-02-28','401C'),
(2,1,1,'Tiểu ban công nghệ AI',NULL,1,NULL,NULL),
(3,11,4,'Hội đồng CNTT',NULL,1,'2026-02-28','202K'),
(4,500,500,'l',NULL,1,'2026-02-27','a');

-- ============================================================
-- BẢNG: tieuban_giangvien
-- ============================================================
CREATE TABLE `tieuban_giangvien` (
  `idTieuBan` int NOT NULL,
  `idGV` int NOT NULL,
  `vaiTro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Thành viên',
  PRIMARY KEY (`idTieuBan`,`idGV`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tieuban_giangvien` VALUES
(1,1,'Thành viên'),(1,2,'Thành viên'),
(2,1,'Thành viên'),(2,2,'Thành viên'),
(3,1,'Thành viên'),(3,2,'Thành viên'),(3,3,'Thành viên'),
(4,3,'Thành viên');

-- ============================================================
-- BẢNG: tieuban_sanpham
-- ============================================================
CREATE TABLE `tieuban_sanpham` (
  `idTieuBan` int NOT NULL,
  `idSanPham` int NOT NULL,
  PRIMARY KEY (`idTieuBan`,`idSanPham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tieuban_sanpham` VALUES (1,7),(2,2);

-- ============================================================
-- BẢNG: thongbao
-- ============================================================
CREATE TABLE `thongbao` (
  `idThongBao` int NOT NULL AUTO_INCREMENT,
  `idSK` int NOT NULL,
  `tieuDe` varchar(200) NOT NULL,
  `noiDung` text NOT NULL,
  `loaiThongBao` varchar(50) DEFAULT NULL,
  `nguoiGui` int DEFAULT NULL,
  `ngayGui` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isPublic` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`idThongBao`),
  KEY `idSK` (`idSK`),
  KEY `nguoiGui` (`nguoiGui`),
  CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `thongbao_ibfk_2` FOREIGN KEY (`nguoiGui`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=2;

INSERT INTO `thongbao` VALUES
(1,504,'Sự kiện mới: a','Sự kiện \"a\" vừa được công bố. Hãy xem chi tiết và đăng ký tham gia!','su_kien_moi',1,'2026-02-25 14:33:27',1);

-- ============================================================
-- BẢNG: thongbao_nguoinhan
-- ============================================================
CREATE TABLE `thongbao_nguoinhan` (
  `idThongBao` int NOT NULL,
  `idTK` int NOT NULL,
  `daDoc` tinyint NOT NULL DEFAULT '0',
  `thoiGianDoc` datetime DEFAULT NULL,
  KEY `idThongBao` (`idThongBao`),
  KEY `idTK` (`idTK`),
  CONSTRAINT `thongbao_nguoinhan_ibfk_1` FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `thongbao_nguoinhan_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `thongbao_nguoinhan` VALUES
(1,2,0,NULL),(1,3,0,NULL),(1,4,0,NULL),(1,5,0,NULL),(1,6,0,NULL),
(1,7,0,NULL),(1,8,0,NULL),(1,9,0,NULL),(1,10,0,NULL),(1,11,0,NULL),
(1,12,0,NULL),(1,13,0,NULL),(1,14,0,NULL);

-- ============================================================
-- BẢNG: xacnhan_thamgia
-- ============================================================
CREATE TABLE `xacnhan_thamgia` (
  `idXacNhan` int NOT NULL AUTO_INCREMENT,
  `idLichTrinh` int DEFAULT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `vaiTro` varchar(50) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Chờ xác nhân',
  `ngayXacNhan` datetime DEFAULT NULL,
  PRIMARY KEY (`idXacNhan`),
  KEY `idLichTrinh` (`idLichTrinh`),
  KEY `idNhom` (`idNhom`),
  KEY `idTK` (`idTK`),
  CONSTRAINT `xacnhan_thamgia_ibfk_1` FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `xacnhan_thamgia_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `xacnhan_thamgia_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- BẢNG: yeucau_thamgia
-- ============================================================
CREATE TABLE `yeucau_thamgia` (
  `idYeuCau` int NOT NULL AUTO_INCREMENT,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `ChieuMoi` tinyint DEFAULT '0' COMMENT '0: nhóm gửi lời mời, 1: người dùng yêu cầu tham gia nhóm',
  `loiNhan` text,
  `trangThai` int DEFAULT '0' COMMENT 'Chờ phản hồi/Đã chấp nhận/Đã từ chối',
  `ngayGui` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayPhanHoi` datetime DEFAULT NULL,
  PRIMARY KEY (`idYeuCau`),
  KEY `idNhom` (`idNhom`),
  KEY `idTK` (`idTK`),
  CONSTRAINT `yeucau_thamgia_ibfk_1` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `yeucau_thamgia_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci AUTO_INCREMENT=2;

INSERT INTO `yeucau_thamgia` VALUES
(1,6,5,0,'',1,'2026-02-22 17:07:01','2026-02-23 09:18:51');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
