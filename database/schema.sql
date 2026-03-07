-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th3 05, 2026 lúc 03:02 AM
-- Phiên bản máy phục vụ: 8.4.7-7
-- Phiên bản PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `mon-nckh`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bantochuc`
--

CREATE TABLE `bantochuc` (
  `idBTC` int NOT NULL,
  `idSK` int NOT NULL,
  `idTK` int NOT NULL,
  `chucVu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `botieuchi`
--

CREATE TABLE `botieuchi` (
  `idBoTieuChi` int NOT NULL,
  `tenBoTieuChi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `botieuchi`
--

INSERT INTO `botieuchi` (`idBoTieuChi`, `tenBoTieuChi`, `moTa`) VALUES
(1, 'Bộ tiêu chuẩn NCKH 2026', 'Áp dụng cho vòng chung kết'),
(2, 'Bộ tiêu chuẩn NCKH 2026 (Bản sao)', 'Áp dụng cho vòng chung kết'),
(3, 'Bộ tiêu chuẩn NCKH 2026 (Bản sao) (Bản sao)', 'Áp dụng cho vòng chung kết'),
(801, 'Phiếu chấm Sơ khảo NCKH 2026', 'Barem điểm chuẩn dành cho Hội đồng Sơ khảo (Thang điểm 10)'),
(802, 'Phiếu chấm Chung khảo (Có Thuyết trình)', 'Dành riêng cho vòng Chung khảo, có đánh giá kỹ năng demo');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `botieuchi_tieuchi`
--

CREATE TABLE `botieuchi_tieuchi` (
  `idBoTieuChi` int NOT NULL,
  `idTieuChi` int NOT NULL,
  `tyTrong` decimal(5,2) DEFAULT '1.00',
  `diemToiDa` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `botieuchi_tieuchi`
--

INSERT INTO `botieuchi_tieuchi` (`idBoTieuChi`, `idTieuChi`, `tyTrong`, `diemToiDa`) VALUES
(1, 1, 1.00, 10.00),
(1, 2, 1.00, 10.00),
(1, 3, 1.00, 10.00),
(1, 4, 1.00, 10.00),
(1, 5, 1.00, 10.00),
(2, 1, 1.00, 9.00),
(2, 2, 1.00, 10.00),
(2, 3, 1.00, 8.00),
(2, 4, 1.00, 10.00),
(2, 5, 1.00, 10.00),
(3, 1, 1.00, 9.00),
(3, 2, 1.00, 10.00),
(3, 3, 1.00, 8.00),
(3, 4, 1.00, 10.00),
(3, 5, 1.00, 10.00),
(801, 801, 1.00, 2.50),
(801, 802, 1.00, 2.50),
(801, 803, 1.00, 3.00),
(801, 804, 1.00, 2.00),
(802, 801, 1.00, 2.00),
(802, 803, 1.00, 3.00),
(802, 804, 1.00, 2.00),
(802, 805, 1.00, 3.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `canhbaodiem`
--

CREATE TABLE `canhbaodiem` (
  `idCanhBao` int NOT NULL,
  `idSanPham` int NOT NULL,
  `idVongThi` int NOT NULL,
  `doLech` decimal(5,2) NOT NULL,
  `trangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xử lý',
  `thoiGian` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cap_tochuc`
--

CREATE TABLE `cap_tochuc` (
  `idCap` int NOT NULL,
  `idLoaiCap` int NOT NULL,
  `tenCap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cap_tochuc`
--

INSERT INTO `cap_tochuc` (`idCap`, `idLoaiCap`, `tenCap`) VALUES
(1, 1, 'Khoa Công nghệ thông tin');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cauhinh_tieuchi_sk`
--

CREATE TABLE `cauhinh_tieuchi_sk` (
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cauhinh_tieuchi_sk`
--

INSERT INTO `cauhinh_tieuchi_sk` (`idSK`, `idVongThi`, `idBoTieuChi`) VALUES
(1, 2, 1),
(11, 3, 1),
(500, 500, 1),
(11, 4, 3),
(800, 801, 801),
(800, 802, 802);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chamtieuchi`
--

CREATE TABLE `chamtieuchi` (
  `idChamDiem` int NOT NULL,
  `idPhanCongCham` int DEFAULT NULL,
  `idSanPham` int DEFAULT NULL,
  `idTieuChi` int DEFAULT NULL,
  `diem` decimal(5,2) DEFAULT NULL,
  `nhanXet` text,
  `thoiGianCham` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chamtieuchi`
--

INSERT INTO `chamtieuchi` (`idChamDiem`, `idPhanCongCham`, `idSanPham`, `idTieuChi`, `diem`, `nhanXet`, `thoiGianCham`) VALUES
(1, 1, 1, 1, 9.50, 'Tốt', '2026-02-21 14:41:11'),
(2, 1, 1, 2, 9.00, 'Được', '2026-02-21 14:41:11'),
(3, 1, 1, 3, 9.50, 'Rất tốt', '2026-02-21 14:41:11'),
(4, 1, 1, 4, 9.00, 'Tốt', '2026-02-21 14:41:11'),
(5, 1, 1, 5, 10.00, 'Xuất sắc', '2026-02-21 14:41:11'),
(6, 2, 1, 1, 8.00, 'Khá', '2026-02-21 14:41:11'),
(7, 2, 1, 2, 8.50, 'Khá', '2026-02-21 14:41:11'),
(8, 2, 1, 3, 8.00, 'Ổn', '2026-02-21 14:41:11'),
(9, 2, 1, 4, 8.50, 'Khá', '2026-02-21 14:41:11'),
(10, 2, 1, 5, 8.00, 'Được', '2026-02-21 14:41:11'),
(11, 3, 1, 1, 8.50, 'Khá', '2026-02-21 14:41:11'),
(12, 3, 1, 2, 8.00, 'Khá', '2026-02-21 14:41:11'),
(13, 3, 1, 3, 2.00, 'Phương pháp sai lệch', '2026-02-21 14:41:11'),
(14, 3, 1, 4, 3.00, 'Cẩu thả', '2026-02-21 14:41:11'),
(15, 3, 1, 5, 8.50, 'Khá', '2026-02-21 14:41:11'),
(16, 1, 2, 1, 8.50, 'Tốt', '2026-02-21 14:41:11'),
(17, 1, 2, 2, 8.50, 'Được', '2026-02-21 14:41:11'),
(18, 1, 2, 3, 8.00, 'Ổn', '2026-02-21 14:41:11'),
(19, 1, 2, 4, 9.00, 'Tốt', '2026-02-21 14:41:11'),
(20, 1, 2, 5, 8.50, 'Tốt', '2026-02-21 14:41:11'),
(21, 2, 2, 1, 8.00, 'Khá', '2026-02-21 14:41:11'),
(22, 2, 2, 2, 8.00, 'Khá', '2026-02-21 14:41:11'),
(23, 2, 2, 3, 8.50, 'Tốt', '2026-02-21 14:41:11'),
(24, 2, 2, 4, 8.50, 'Khá', '2026-02-21 14:41:11'),
(25, 2, 2, 5, 8.00, 'Được', '2026-02-21 14:41:11'),
(26, 4, 7, 1, 9.00, '', '2026-02-21 15:49:16'),
(27, 4, 7, 2, 8.00, '', '2026-02-21 15:49:16'),
(28, 4, 7, 3, 9.00, '', '2026-02-21 15:49:16'),
(29, 4, 7, 4, 8.00, '', '2026-02-21 15:49:16'),
(30, 4, 7, 5, 9.00, '', '2026-02-21 15:49:16'),
(31, 500, 500, 1, 9.00, 'Ý tưởng rất thực tế, có tính ứng dụng cao', '2026-02-23 15:34:08'),
(32, 500, 500, 2, 8.50, 'Cần làm rõ thuật toán nhận diện', '2026-02-23 15:34:08'),
(33, 500, 500, 3, 9.00, 'Demo chạy tốt, ít độ trễ', '2026-02-23 15:34:08'),
(34, 500, 500, 4, 8.00, 'Báo cáo cần chỉn chu hơn', '2026-02-23 15:34:08'),
(35, 500, 500, 5, 9.00, 'Sinh viên nhiệt tình', '2026-02-23 15:34:08'),
(36, 501, 500, 1, 8.50, 'Khá tốt', '2026-02-23 15:34:08'),
(37, 501, 500, 2, 8.00, 'Phương pháp ổn định', '2026-02-23 15:34:08'),
(38, 501, 500, 3, 8.50, 'Tốt', '2026-02-23 15:34:08'),
(39, 501, 500, 4, 8.00, 'Đạt yêu cầu', '2026-02-23 15:34:08'),
(40, 501, 500, 5, 8.50, 'Tốt', '2026-02-23 15:34:08'),
(41, 801, 801, 801, 2.00, 'Tính cấp thiết khá tốt, phù hợp nông nghiệp VN', '2026-02-27 13:33:56'),
(42, 801, 801, 802, 2.50, 'Mô hình YOLOv8 áp dụng chuẩn', '2026-02-27 13:33:56'),
(43, 801, 801, 803, 1.50, 'Cần thêm hình ảnh thực tế', '2026-02-27 13:33:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chude`
--

CREATE TABLE `chude` (
  `idChuDe` int NOT NULL,
  `tenChuDe` varchar(200) DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `moTaChuDe` text,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chude`
--

INSERT INTO `chude` (`idChuDe`, `tenChuDe`, `idSK`, `moTaChuDe`, `isActive`) VALUES
(1, 'Trí tuệ nhân tạo (AI)', 1, 'Các ứng dụng AI trong thực tế', 1),
(2, 'Internet vạn vật (IoT)', 1, 'Giải pháp nhà thông minh, nông nghiệp thông minh', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chude_sukien`
--

CREATE TABLE `chude_sukien` (
  `idChuDeSK` int NOT NULL,
  `idSK` int NOT NULL,
  `idchude` int DEFAULT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isActive` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chude_sukien`
--

INSERT INTO `chude_sukien` (`idChuDeSK`, `idSK`, `idchude`, `moTa`, `isActive`) VALUES
(1, 1, 1, 'Chuyên đề AI', 1),
(2, 1, 2, 'Chuyên đề IoT', 1),
(801, 800, 1, 'Ứng dụng Trí tuệ nhân tạo (AI)', 1),
(802, 800, 2, 'Hệ thống Internet of Things (IoT)', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chungnhan`
--

CREATE TABLE `chungnhan` (
  `idChungNhan` int NOT NULL,
  `maChungNhan` varchar(50) DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `loaiChungNhan` varchar(50) DEFAULT NULL,
  `ngayCap` datetime DEFAULT NULL,
  `filePDF` varchar(255) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diemdanh`
--

CREATE TABLE `diemdanh` (
  `idDiemDanh` int NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `thoiGianDiemDanh` datetime DEFAULT CURRENT_TIMESTAMP,
  `hienDien` tinyint DEFAULT '1',
  `ghiChu` varchar(100) DEFAULT NULL,
  `idLichTrinh` int DEFAULT NULL COMMENT 'Liên kết buổi điểm danh',
  `phuongThuc` enum('QR','GPS','Manual','NFC') DEFAULT 'QR' COMMENT 'Cách điểm danh',
  `viTriLat` decimal(10,7) DEFAULT NULL COMMENT 'Vị trí SV lúc điểm danh',
  `viTriLng` decimal(10,7) DEFAULT NULL,
  `ipDiemDanh` varchar(45) DEFAULT NULL COMMENT 'IP thiết bị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `diemdanh`
--

INSERT INTO `diemdanh` (`idDiemDanh`, `idNhom`, `idTK`, `thoiGianDiemDanh`, `hienDien`, `ghiChu`, `idLichTrinh`, `phuongThuc`, `viTriLat`, `viTriLng`, `ipDiemDanh`) VALUES
(1, NULL, 6, '2026-02-22 13:22:32', 1, 'Tự điểm danh', 1, 'GPS', 21.0377847, 105.8518394, '::1'),
(2, NULL, 1, '2026-02-22 14:02:16', 1, 'Tự điểm danh', 2, 'GPS', 21.0377867, 105.8518433, '::1');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dieukien`
--

CREATE TABLE `dieukien` (
  `idDieuKien` int NOT NULL,
  `loaiDieuKien` enum('DON','TOHOP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenDieuKien` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dieukien`
--

INSERT INTO `dieukien` (`idDieuKien`, `loaiDieuKien`, `tenDieuKien`, `moTa`) VALUES
(1, 'DON', 'Điều kiện GPA khá', 'Sinh viên phải có GPA từ 2.5 trở lên'),
(2, 'DON', 'diem tb', ''),
(3, 'TOHOP', 'to hop 1', NULL),
(4, 'TOHOP', '', NULL),
(5, 'TOHOP', 'to hop 2', NULL),
(6, 'DON', 'DK_DON_699898cd17f51', ''),
(7, 'DON', 'DK_DON_699898cd18cb0', ''),
(8, 'TOHOP', 'TOHOP_699898cd193e9', ''),
(9, 'DON', 'DK_DON_69989912a0f0c', ''),
(10, 'DON', 'DK_DON_69989912a3d56', ''),
(11, 'DON', 'DK_DON_69989912a4b11', ''),
(12, 'TOHOP', 'TOHOP_69989912a5eb6', ''),
(13, 'TOHOP', 'TOHOP_69989912a7900', ''),
(14, 'DON', 'DK_DON_69a1471f1bdeb', ''),
(15, 'DON', 'DK_DON_69a1471f1e36e', ''),
(16, 'TOHOP', 'TOHOP_69a1471f1f147', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dieukien_don`
--

CREATE TABLE `dieukien_don` (
  `idDieuKien` int NOT NULL,
  `idThuocTinhKiemTra` int NOT NULL,
  `idToanTu` int NOT NULL,
  `giaTriSoSanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dieukien_don`
--

INSERT INTO `dieukien_don` (`idDieuKien`, `idThuocTinhKiemTra`, `idToanTu`, `giaTriSoSanh`) VALUES
(1, 1, 4, '2.5'),
(2, 1, 1, '3.0'),
(6, 2, 1, '80'),
(7, 1, 1, '3.0'),
(9, 3, 1, '50'),
(10, 5, 1, 'hoạt động'),
(11, 4, 4, '1'),
(14, 1, 4, '3.2'),
(15, 2, 2, '70');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giaithuong`
--

CREATE TABLE `giaithuong` (
  `idGiaiThuong` int NOT NULL,
  `idSK` int NOT NULL,
  `tengiaithuong` varchar(200) DEFAULT NULL,
  `mota` text,
  `soluong` int DEFAULT '1' COMMENT 'kiểm tra soluong>0',
  `giatri` decimal(15,0) DEFAULT NULL,
  `thutu` int DEFAULT '1',
  `isActive` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giangvien`
--

CREATE TABLE `giangvien` (
  `idGV` int NOT NULL,
  `idTK` int NOT NULL,
  `tenGV` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL,
  `gioiTinh` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `giangvien`
--

INSERT INTO `giangvien` (`idGV`, `idTK`, `tenGV`, `idKhoa`, `gioiTinh`) VALUES
(1, 2, 'Nguyễn Văn Minh', 1, 1),
(2, 3, 'Trần Thị Hương', 1, 0),
(3, 7, 'TS. Phạm Thị Lan', 1, 0),
(4, 9, 'GV Khánh', NULL, 0),
(5, 12, '', NULL, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ketqua`
--

CREATE TABLE `ketqua` (
  `idKetQua` tinyint NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `diemTongKet` decimal(5,2) DEFAULT NULL,
  `xepHang` int DEFAULT NULL,
  `ghiChu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `ngayXetGiai` datetime DEFAULT NULL,
  `isPublic` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khoa`
--

CREATE TABLE `khoa` (
  `idKhoa` int NOT NULL,
  `maKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khoa`
--

INSERT INTO `khoa` (`idKhoa`, `maKhoa`, `tenKhoa`) VALUES
(1, 'CNTT', 'Công nghệ thông tin'),
(2, 'KT', 'Kinh tế');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichtrinh`
--

CREATE TABLE `lichtrinh` (
  `idLichTrinh` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int DEFAULT NULL,
  `tenHoatDong` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thoiGian` datetime NOT NULL,
  `diaDiem` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viTriLat` decimal(10,7) DEFAULT NULL COMMENT 'Vĩ độ GPS địa điểm',
  `viTriLng` decimal(10,7) DEFAULT NULL COMMENT 'Kinh độ GPS địa điểm',
  `banKinhDiemDanh` int DEFAULT '150' COMMENT 'Bán kính hợp lệ (mét)',
  `thoiGianMoDiemDanh` datetime DEFAULT NULL COMMENT 'Thời điểm BTC mở điểm danh',
  `thoiGianDongDiemDanh` datetime DEFAULT NULL COMMENT 'Thời điểm BTC đóng điểm danh'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lichtrinh`
--

INSERT INTO `lichtrinh` (`idLichTrinh`, `idSK`, `idVongThi`, `tenHoatDong`, `thoiGian`, `diaDiem`, `viTriLat`, `viTriLng`, `banKinhDiemDanh`, `thoiGianMoDiemDanh`, `thoiGianDongDiemDanh`) VALUES
(1, 11, NULL, 'Vòng 1', '2026-02-22 13:18:00', '', 21.0377777, 105.8518399, 100, '2026-02-23 16:54:23', '2026-02-23 17:39:23'),
(2, 11, NULL, 'Hi', '2026-02-22 13:59:00', 'a', 21.0377862, 105.8518308, 150, '2026-02-22 13:59:55', '2026-02-22 14:29:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaicap`
--

CREATE TABLE `loaicap` (
  `idLoaiCap` int NOT NULL,
  `tenLoaiCap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loaicap`
--

INSERT INTO `loaicap` (`idLoaiCap`, `tenLoaiCap`) VALUES
(1, 'Cấp Khoa'),
(2, 'Cấp Trường');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaitaikhoan`
--

CREATE TABLE `loaitaikhoan` (
  `idLoaiTK` int NOT NULL,
  `tenLoaiTK` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loaitaikhoan`
--

INSERT INTO `loaitaikhoan` (`idLoaiTK`, `tenLoaiTK`) VALUES
(1, 'Quản trị viên'),
(2, 'Giảng viên'),
(3, 'Sinh viên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaitailieu`
--

CREATE TABLE `loaitailieu` (
  `idtailieu` int NOT NULL,
  `loaitailieu` varchar(100) NOT NULL,
  `mota` text NOT NULL,
  `dinhDangChoPhep` varchar(200) DEFAULT NULL COMMENT 'Định dạng cho phép, vd: pdf,docx hoặc url. NULL = không giới hạn',
  `dungLuongToiDa` int DEFAULT NULL COMMENT 'Dung lượng tối đa KB. NULL = không giới hạn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `loaitailieu`
--

INSERT INTO `loaitailieu` (`idtailieu`, `loaitailieu`, `mota`, `dinhDangChoPhep`, `dungLuongToiDa`) VALUES
(1, 'Báo cáo tóm tắt', 'File PDF mô tả ngắn gọn', NULL, NULL),
(2, 'Báo cáo toàn văn', 'File PDF đầy đủ', NULL, NULL),
(3, 'Source Code', 'Link Github hoặc file Zip', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lop`
--

CREATE TABLE `lop` (
  `idLop` int NOT NULL,
  `maLop` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenLop` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lop`
--

INSERT INTO `lop` (`idLop`, `maLop`, `tenLop`, `idKhoa`) VALUES
(1, '64PM1', 'K64 Phần mềm 1', 1),
(2, '64PM2', 'K64 Phần mềm 2', 1),
(3, '64KT1', 'K64 Kinh tế 1', 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhom`
--

CREATE TABLE `nhom` (
  `idnhom` int NOT NULL,
  `idSK` int DEFAULT NULL,
  `idnhomtruong` int DEFAULT NULL,
  `manhom` varchar(20) DEFAULT NULL,
  `ngaytao` datetime DEFAULT NULL,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `nhom`
--

INSERT INTO `nhom` (`idnhom`, `idSK`, `idnhomtruong`, `manhom`, `ngaytao`, `isActive`) VALUES
(1, 1, 4, 'GRP_AI_01', '2026-02-11 22:11:23', 1),
(2, 1, 5, 'GRP_IOT_01', '2026-02-21 12:51:34', 1),
(3, 11, 4, 'GRP_THDH_01', '2026-02-21 12:51:34', 1),
(4, 11, 6, 'GRP_THDH_02', '2026-02-21 12:51:34', 1),
(5, 11, 5, 'GRP_THDH_03', '2026-02-21 12:51:34', 1),
(6, 11, 6, 'GRP_177175480715', '2026-02-22 17:06:47', 1),
(500, 500, 4, 'GRP_HACK_01', '2026-02-23 15:34:08', 1),
(501, 500, 6, 'GRP_HACK_02', '2026-02-23 15:34:08', 1),
(502, 501, 5, 'GRP_501_1771841506', '2026-02-23 17:11:46', 1),
(801, 800, 4, 'TEAM_AI_PRO', '2026-02-01 00:00:00', 1),
(802, 800, 6, 'TEAM_SMART_IOT', '2026-02-05 00:00:00', 1),
(803, 1, 8, 'GRP_1_1772174418', '2026-02-27 13:40:18', 1),
(804, 800, 5, 'GRP_800_1772179702', '2026-02-27 15:08:22', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhom_quyen`
--

CREATE TABLE `nhom_quyen` (
  `idNhomQuyen` int NOT NULL,
  `tenNhom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên nhóm hiển thị trên UI',
  `thuTu` int DEFAULT '0' COMMENT 'Thứ tự hiển thị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhom_quyen`
--

INSERT INTO `nhom_quyen` (`idNhomQuyen`, `tenNhom`, `thuTu`) VALUES
(1, 'Cấu hình sự kiện', 1),
(2, 'Chấm điểm', 2),
(3, 'Sản phẩm', 3),
(4, 'Kết quả', 4),
(5, 'Nhóm thi', 5),
(6, 'Quản trị hệ thống', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nienkhoa`
--

CREATE TABLE `nienkhoa` (
  `idNienKhoa` int NOT NULL,
  `maNienKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenNienKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phanconbtc`
--

CREATE TABLE `phanconbtc` (
  `idPhanCong` int NOT NULL,
  `idSK` int DEFAULT NULL,
  `idBTC` int DEFAULT NULL,
  `idvaitro` int DEFAULT NULL,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancongcham`
--

CREATE TABLE `phancongcham` (
  `idPhanCongCham` int NOT NULL,
  `idGV` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int NOT NULL,
  `trangThaiXacNhan` varchar(50) DEFAULT 'Chờ xác nhận' COMMENT 'Chờ xác nhận/Đã xác nhận/Từ chối',
  `ngayXacNhan` datetime NOT NULL,
  `isActive` tinyint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `phancongcham`
--

INSERT INTO `phancongcham` (`idPhanCongCham`, `idGV`, `idSK`, `idVongThi`, `idBoTieuChi`, `trangThaiXacNhan`, `ngayXacNhan`, `isActive`) VALUES
(1, 1, 1, 1, 1, 'Đã xác nhận', '0000-00-00 00:00:00', 1),
(2, 2, 1, 1, 1, 'Đã xác nhận', '0000-00-00 00:00:00', 1),
(3, 3, 1, 1, 1, 'Đã xác nhận', '0000-00-00 00:00:00', 1),
(4, 1, 11, 3, 1, 'Đã xác nhận', '2026-02-21 15:49:16', 1),
(500, 2, 500, 500, 1, 'Đã xác nhận', '2026-02-23 15:34:08', 1),
(501, 3, 500, 500, 1, 'Đã xác nhận', '2026-02-23 15:34:08', 1),
(801, 1, 800, 801, 801, 'Đang chấm', '2026-02-27 13:33:56', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phancong_doclap`
--

CREATE TABLE `phancong_doclap` (
  `idSanPham` int NOT NULL,
  `idGV` int NOT NULL,
  `idVongThi` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phancong_doclap`
--

INSERT INTO `phancong_doclap` (`idSanPham`, `idGV`, `idVongThi`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(2, 1, 1),
(2, 2, 1),
(3, 1, 1),
(3, 2, 1),
(6, 1, 3),
(6, 2, 3),
(7, 1, 3),
(500, 2, 500),
(500, 3, 500),
(501, 2, 500),
(501, 3, 500),
(501, 4, 500),
(802, 3, 801);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyche`
--

CREATE TABLE `quyche` (
  `idQuyChe` int NOT NULL,
  `tenQuyChe` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `loaiQuyChe` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA',
  `idSK` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quyche`
--

INSERT INTO `quyche` (`idQuyChe`, `tenQuyChe`, `moTa`, `loaiQuyChe`, `idSK`) VALUES
(1, 'Quy chế tham gia NCKH 2026', 'Yêu cầu về học lực tối thiểu', 'THAMGIA', 1),
(4, 'quy che tham gia', 'Ap dung cho sinh vien', 'THAMGIA', 11),
(5, 'quy chế qua vòng', '', 'VONGTHI', 11),
(6, 'điều kiện', '', 'THAMGIA', 500);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyche_dieukien`
--

CREATE TABLE `quyche_dieukien` (
  `idQuyChe` int NOT NULL,
  `idDieuKienCuoi` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quyche_dieukien`
--

INSERT INTO `quyche_dieukien` (`idQuyChe`, `idDieuKienCuoi`) VALUES
(1, 1),
(4, 8),
(5, 13),
(6, 16);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyen`
--

CREATE TABLE `quyen` (
  `idQuyen` int NOT NULL,
  `maQuyen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenQuyen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `idNhomQuyen` int DEFAULT NULL COMMENT 'FK → nhom_quyen, dùng để gom nhóm trên UI',
  `maQuyen_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Mã để code backend kiểm tra, vd: cauhinh_sukien',
  `phamVi` enum('HE_THONG','SU_KIEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SU_KIEN' COMMENT 'HE_THONG: gán cho tài khoản qua taikhoan_quyen | SU_KIEN: gán cho role qua vaitro_quyen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quyen`
--

INSERT INTO `quyen` (`idQuyen`, `maQuyen`, `tenQuyen`, `moTa`, `idNhomQuyen`, `maQuyen_code`, `phamVi`) VALUES
(24, 'cauhinh_sukien', 'Cấu hình thông tin sự kiện', 'Sửa tên, mô tả, thời gian, cấp tổ chức', 1, 'cauhinh_sukien', 'SU_KIEN'),
(25, 'cauhinh_vongthi', 'Cấu hình vòng thi', 'Thêm/sửa/xóa vòng thi, thời gian mỗi vòng', 1, 'cauhinh_vongthi', 'SU_KIEN'),
(26, 'cauhinh_tailieu', 'Cấu hình loại tài liệu nộp', 'Thiết lập slot nộp tài liệu, định dạng cho phép', 1, 'cauhinh_tailieu', 'SU_KIEN'),
(27, 'phan_cong_cham', 'Phân công giảng viên chấm điểm', 'Gán GV vào danh sách chấm điểm của vòng thi', 1, 'phan_cong_cham', 'SU_KIEN'),
(28, 'nhap_diem', 'Nhập điểm chấm', 'Nhập điểm theo từng tiêu chí cho bài được phân công', 2, 'nhap_diem', 'SU_KIEN'),
(29, 'xem_bai_phan_cong', 'Xem bài được phân công chấm', 'Xem nội dung sản phẩm của nhóm mình được phân công', 2, 'xem_bai_phan_cong', 'SU_KIEN'),
(30, 'nop_san_pham', 'Nộp/cập nhật sản phẩm', 'Nộp hoặc ghi đè sản phẩm khi chưa đóng nộp', 3, 'nop_san_pham', 'SU_KIEN'),
(31, 'xem_ketqua_truocCB', 'Xem kết quả trước công bố', 'Xem điểm và xếp hạng khi chưa công bố chính thức', 4, 'xem_ketqua_truocCB', 'SU_KIEN'),
(32, 'xem_ketqua_sauCB', 'Xem kết quả sau công bố', 'Xem kết quả sau khi BTC đã công bố chính thức', 4, 'xem_ketqua_sauCB', 'SU_KIEN'),
(33, 'quan_ly_nhom', 'Tạo và quản lý nhóm thi', 'Tạo nhóm, mời thành viên, đổi nhóm trưởng', 5, 'quan_ly_nhom', 'SU_KIEN'),
(34, 'admin.users', 'Quản lý tài khoản', 'Truy cập trang admin/users, tạo và sửa tài khoản', 6, 'admin_users', 'HE_THONG'),
(35, 'admin.events', 'Quản lý sự kiện', 'Tạo sự kiện mới, xem toàn bộ danh sách sự kiện', 6, 'admin_events', 'HE_THONG'),
(36, 'admin.criteria', 'Quản lý bộ tiêu chí', 'Tạo và sửa bộ tiêu chí chấm điểm dùng chung toàn hệ thống', 6, 'admin_criteria', 'HE_THONG'),
(37, 'admin.reports', 'Xem báo cáo tổng hợp', 'Xem thống kê và báo cáo toàn hệ thống', 6, 'admin_reports', 'HE_THONG'),
(38, 'tao_su_kien', 'Tạo sự kiện', 'Cho phép tài khoản tạo sự kiện NCKH mới', 6, 'tao_su_kien', 'HE_THONG');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyentaosk`
--

CREATE TABLE `quyentaosk` (
  `idgv` int NOT NULL,
  `idloaicap` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `idSanPham` int NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idChuDeSK` int DEFAULT NULL,
  `idloaitailieu` int DEFAULT NULL,
  `moTataiLieu` text,
  `TrangThai` varchar(50) DEFAULT NULL COMMENT 'Tên trạng thái: Chờ/Đã duyệt/Bị loại...',
  `isActive` int DEFAULT '0',
  `tensanpham` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`idSanPham`, `idNhom`, `idSK`, `idChuDeSK`, `idloaitailieu`, `moTataiLieu`, `TrangThai`, `isActive`, `tensanpham`) VALUES
(1, 1, 1, 1, 1, 'Link Overleaf báo cáo', 'Chờ duyệt', 1, 'Hệ thống điểm danh bằng nhận diện khuôn mặt'),
(2, 2, 1, 2, 2, 'Link Google Drive chứa báo cáo toàn văn và video demo hệ thống', 'Chờ duyệt', 1, 'Hệ thống nhà kính thông minh giám sát qua IoT'),
(3, 1, 1, 1, 2, 'Báo cáo chi tiết quá trình huấn luyện mô hình YOLOv8', 'Chờ duyệt', 1, 'Nhận diện rác thải tái chế bằng Deep Learning'),
(4, 3, 11, NULL, 3, 'Link Github mã nguồn ứng dụng Android/iOS', 'Chờ duyệt', 1, 'Ứng dụng di động hỗ trợ sinh viên ôn thi trắc nghiệm'),
(5, 4, 11, NULL, 1, 'File PDF báo cáo tóm tắt thuật toán', 'Chờ duyệt', 1, 'Thuật toán tối ưu hóa lịch biểu giảng đường đại học'),
(6, 5, 11, NULL, 3, 'Source code C# Winform', 'Chờ duyệt', 1, 'Phần mềm quản lý chi tiêu cá nhân tích hợp AI'),
(7, 3, 11, NULL, 2, 'Tài liệu phân tích thiết kế hệ thống', 'Chờ duyệt', 1, 'Hệ thống Blockchain lưu trữ văn bằng chứng chỉ'),
(500, 500, 500, NULL, 3, 'Link Github: github.com/bugbusters/ai-traffic', 'Chờ duyệt', 1, 'Hệ thống cảnh báo giao thông AI'),
(501, 501, 500, NULL, 3, 'Link Github: github.com/cyberninjas/pomo3d', 'Chờ duyệt', 1, 'App quản lý thời gian Pomodoro 3D'),
(801, 801, 800, 801, 2, NULL, 'Đã duyệt', 1, 'Ứng dụng Deep Learning trong chẩn đoán sớm bệnh lý trên lá lúa'),
(802, 802, 800, 802, 2, NULL, 'Đã duyệt', 1, 'Hệ thống nhà kính thông minh giám sát tự động qua Telegram');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham_vongthi`
--

CREATE TABLE `sanpham_vongthi` (
  `idSanPham` int NOT NULL,
  `idVongThi` int NOT NULL,
  `diemTrungBinh` decimal(5,0) DEFAULT NULL,
  `xepLoai` varchar(50) DEFAULT NULL COMMENT 'Đạt/Không đạt/Xuất sắc',
  `trangThai` varchar(50) DEFAULT NULL COMMENT 'Chờ chấm/Đã chấm/Bị loại',
  `ngayCapNhat` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham_vongthi`
--

INSERT INTO `sanpham_vongthi` (`idSanPham`, `idVongThi`, `diemTrungBinh`, `xepLoai`, `trangThai`, `ngayCapNhat`) VALUES
(2, 1, 42, NULL, 'Đã duyệt', '2026-02-21 14:51:53'),
(7, 3, 43, NULL, 'Đã duyệt', '2026-02-21 16:08:46'),
(500, 500, NULL, NULL, 'Đã nộp', '2026-02-23 15:34:08'),
(501, 500, NULL, NULL, 'Đã nộp', '2026-02-23 15:34:08'),
(801, 801, NULL, NULL, 'Đã phân công', '2026-02-27 13:33:56'),
(802, 801, NULL, NULL, 'Đã phân công', '2026-02-27 13:33:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sinhvien`
--

CREATE TABLE `sinhvien` (
  `idSV` int NOT NULL,
  `idTK` int NOT NULL,
  `tenSV` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `MSV` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `GPA` decimal(4,2) DEFAULT '0.00',
  `DRL` int DEFAULT '0',
  `idLop` int DEFAULT NULL,
  `idKhoa` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sinhvien`
--

INSERT INTO `sinhvien` (`idSV`, `idTK`, `tenSV`, `MSV`, `GPA`, `DRL`, `idLop`, `idKhoa`) VALUES
(1, 4, 'Nguyễn Thanh Tùng', 'SV001', 3.60, 90, 1, 1),
(2, 5, 'Lê Thị Mai', 'SV002', 3.20, 85, 1, 1),
(3, 6, 'Hoàng Văn Nam', 'SV003', 2.80, 70, 2, 1),
(4, 8, 'Trần Văn Sơn', 'Hello', 4.00, 100, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sukien`
--

CREATE TABLE `sukien` (
  `idSK` int NOT NULL,
  `tenSK` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `idCap` int DEFAULT NULL,
  `nguoiTao` int NOT NULL,
  `ngayMoDangKy` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayDongDangKy` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ngayBatDau` datetime DEFAULT NULL,
  `ngayKetThuc` datetime DEFAULT NULL,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sukien`
--

INSERT INTO `sukien` (`idSK`, `tenSK`, `moTa`, `idCap`, `nguoiTao`, `ngayMoDangKy`, `ngayDongDangKy`, `ngayBatDau`, `ngayKetThuc`, `isActive`) VALUES
(1, 'Nghiên cứu khoa học sinh viên CNTT 2026', 'Cuộc thi tìm kiếm ý tưởng công nghệ mới', 1, 2, '2026-01-01 00:00:00', '2026-02-28 00:00:00', '2026-03-15 00:00:00', '2026-05-20 00:00:00', 1),
(11, 'tin hoc dai hoc', 'thdh', 1, 1, '2026-02-19 00:00:00', '2026-02-28 00:00:00', '2026-02-19 00:00:00', '2026-02-28 00:00:00', 0),
(500, 'Hackathon Sinh viên Công nghệ 2026', 'Sự kiện demo full dữ liệu: Nhóm, Bài nộp, Chấm điểm', 1, 2, '2026-02-01 00:00:00', '2026-02-20 00:00:00', '2026-02-25 00:00:00', '2026-03-30 00:00:00', 1),
(501, 'gv Minh tạo', '', 1, 7, '2026-02-23 16:05:00', '2026-03-08 16:05:00', '2026-02-23 16:05:00', '2026-03-08 16:05:00', 0),
(800, 'Hội nghị NCKH Sinh viên Khoa CNTT 2026', 'Sự kiện NCKH trọng điểm nhằm tìm kiếm các giải pháp Công nghệ AI, IoT và Phần mềm ứng dụng xuất sắc nhất.', 1, 1, '2026-01-01 00:00:00', '2026-03-01 00:00:00', '2026-03-10 00:00:00', '2026-05-30 00:00:00', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan`
--

CREATE TABLE `taikhoan` (
  `idTK` int NOT NULL,
  `tenTK` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `matKhau` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idLoaiTK` int NOT NULL,
  `isActive` tinyint DEFAULT '1',
  `ngayTao` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `taikhoan`
--

INSERT INTO `taikhoan` (`idTK`, `tenTK`, `matKhau`, `idLoaiTK`, `isActive`, `ngayTao`) VALUES
(1, 'admin', '123456', 1, 1, '2026-02-11 22:11:22'),
(2, 'gv_minh', '123456', 2, 1, '2026-02-11 22:11:22'),
(3, 'gv_huong', '123456', 2, 1, '2026-02-11 22:11:22'),
(4, 'sv_tung', '123456', 3, 1, '2026-02-11 22:11:22'),
(5, 'sv_mai', '123456', 3, 1, '2026-02-11 22:11:22'),
(6, 'sv_nam', '123456', 3, 1, '2026-02-11 22:11:22'),
(7, 'gv_lan', '123456', 2, 1, '2026-02-21 14:41:11'),
(8, 'son', '123456', 3, 1, '2026-02-22 23:11:07'),
(9, 'gv_khanh', '123456', 2, 1, '2026-02-23 15:36:21'),
(10, 'gv_them', '123456', 2, 1, '2026-02-23 15:38:35'),
(11, 'gv_long', '123456', 2, 1, '2026-02-23 16:08:05'),
(12, 'gv_hai', '123456', 2, 1, '2026-02-26 16:57:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan_quyen`
--

CREATE TABLE `taikhoan_quyen` (
  `idTK` int NOT NULL,
  `idQuyen` int NOT NULL,
  `isActive` tinyint DEFAULT '1',
  `thoiGianBatDau` datetime DEFAULT CURRENT_TIMESTAMP,
  `thoiGianKetThuc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `taikhoan_quyen`
--

INSERT INTO `taikhoan_quyen` (`idTK`, `idQuyen`, `isActive`, `thoiGianBatDau`, `thoiGianKetThuc`) VALUES
(1, 34, 1, '2026-02-22 22:40:25', NULL),
(1, 35, 1, '2026-02-22 22:40:25', NULL),
(1, 36, 1, '2026-02-22 22:40:25', NULL),
(1, 37, 1, '2026-02-22 22:40:25', NULL),
(1, 38, 1, '2026-02-23 15:18:35', NULL),
(2, 38, 1, '2026-02-23 15:18:35', NULL),
(3, 38, 1, '2026-02-23 15:18:35', NULL),
(7, 38, 1, '2026-02-23 15:18:35', NULL),
(9, 38, 1, '2026-02-23 15:36:21', NULL),
(12, 38, 1, '2026-02-26 16:57:57', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan_vaitro_sukien`
--

CREATE TABLE `taikhoan_vaitro_sukien` (
  `id` int NOT NULL,
  `idTK` int NOT NULL,
  `idSK` int NOT NULL,
  `idVaiTroSK` int NOT NULL,
  `idVaiTroGoc` int DEFAULT NULL COMMENT 'Denorm từ vaitro_sukien để query nhanh hơn',
  `nguonTao` enum('BTC_THEM','PHANCONG_CHAM','QUAT_NHOM','DANG_KY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BTC_THEM: BTC thêm thủ công | PHANCONG_CHAM: sinh khi phân công chấm | QUAT_NHOM: GV vào nhóm | DANG_KY: SV vào nhóm',
  `idNguoiCap` int DEFAULT NULL COMMENT 'idTK người thực hiện, NULL nếu tự động',
  `ngayCap` datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng trung tâm phân quyền: ai có role gì trong sự kiện nào.';

--
-- Đang đổ dữ liệu cho bảng `taikhoan_vaitro_sukien`
--

INSERT INTO `taikhoan_vaitro_sukien` (`id`, `idTK`, `idSK`, `idVaiTroSK`, `idVaiTroGoc`, `nguonTao`, `idNguoiCap`, `ngayCap`, `isActive`) VALUES
(1, 2, 1, 34, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(2, 3, 1, 34, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(3, 7, 1, 34, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(4, 2, 11, 24, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(8, 4, 1, 68, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(9, 5, 1, 68, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(10, 6, 11, 58, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(12, 2, 500, 501, 1, 'BTC_THEM', NULL, '2026-02-23 15:34:08', 1),
(13, 3, 500, 502, 2, 'PHANCONG_CHAM', NULL, '2026-02-23 15:34:08', 1),
(14, 7, 500, 502, 2, 'PHANCONG_CHAM', NULL, '2026-02-23 15:34:08', 1),
(15, 4, 500, 504, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(16, 5, 500, 504, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(17, 6, 500, 504, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(18, 8, 500, 504, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(19, 7, 501, 505, 1, 'BTC_THEM', 7, '2026-02-23 16:05:16', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thanhviennhom`
--

CREATE TABLE `thanhviennhom` (
  `idnhom` int NOT NULL,
  `idtk` int DEFAULT NULL,
  `idvaitronhom` int DEFAULT NULL,
  `trangthai` tinyint NOT NULL DEFAULT '0' COMMENT '0:chờ duyệt, 1:đã tham gia',
  `ngaythamgia` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `thanhviennhom`
--

INSERT INTO `thanhviennhom` (`idnhom`, `idtk`, `idvaitronhom`, `trangthai`, `ngaythamgia`) VALUES
(1, 4, 1, 1, '2026-02-11 22:11:23'),
(1, 5, 2, 1, '2026-02-11 22:11:23'),
(6, 6, 1, 1, '2026-02-22 17:06:47'),
(6, 5, 2, 1, '2026-02-23 09:18:51'),
(500, 4, 1, 1, '2026-02-23 15:34:08'),
(500, 5, 2, 1, '2026-02-23 15:34:08'),
(501, 6, 1, 1, '2026-02-23 15:34:08'),
(501, 8, 2, 1, '2026-02-23 15:34:08'),
(502, 5, 1, 1, '2026-02-23 17:11:46'),
(801, 4, 1, 1, '2026-02-27 13:33:56'),
(801, 5, 2, 0, '2026-02-27 13:33:56'),
(802, 6, 1, 1, '2026-02-27 13:33:56'),
(802, 8, 2, 1, '2026-02-27 13:33:56'),
(803, 8, 1, 1, '2026-02-27 13:40:18'),
(804, 5, 1, 1, '2026-02-27 15:08:22'),
(804, 4, 2, 0, '2026-02-27 15:11:10'),
(804, 8, 2, 1, '2026-02-27 15:12:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbao`
--

CREATE TABLE `thongbao` (
  `idThongBao` int NOT NULL,
  `idSK` int NOT NULL,
  `tieuDe` varchar(50) NOT NULL,
  `noiDung` text NOT NULL,
  `loaiThongBao` varchar(50) DEFAULT NULL,
  `nguoiGui` int DEFAULT NULL,
  `ngayGui` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isPublic` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbao_nguoinhan`
--

CREATE TABLE `thongbao_nguoinhan` (
  `idThongBao` int NOT NULL,
  `idTK` int NOT NULL,
  `daDoc` tinyint NOT NULL DEFAULT '0',
  `thoiGianDoc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongtinnhom`
--

CREATE TABLE `thongtinnhom` (
  `idthongtin` int NOT NULL,
  `idnhom` int NOT NULL,
  `tennhom` varchar(50) DEFAULT NULL,
  `mota` text,
  `soluongtoida` int DEFAULT '5' COMMENT 'soluongtoida>0',
  `dangtuyen` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `thongtinnhom`
--

INSERT INTO `thongtinnhom` (`idthongtin`, `idnhom`, `tennhom`, `mota`, `soluongtoida`, `dangtuyen`) VALUES
(1, 1, 'AI Pioneers', 'Nhóm nghiên cứu Computer Vision', 5, 0),
(2, 6, 'a', 'a', 5, 1),
(500, 500, 'Bug Busters', 'Đội chuyên fix bug và tạo bug mới', 5, 0),
(501, 501, 'Cyber Ninjas', 'Đội ninja code dạo đêm khuya', 5, 0),
(502, 502, 'nhom hihi', '', 5, 1),
(801, 801, 'VisionARY Group', 'Nhóm chuyên nghiên cứu Computer Vision', 5, 0),
(802, 802, 'IoT Hardware Lab', 'Nhóm kỹ sư nhúng và IoT', 5, 0),
(803, 803, 'nhom 8383', '', 5, 1),
(804, 804, 'nhom sv mai tao', '', 3, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thuoctinh_kiemtra`
--

CREATE TABLE `thuoctinh_kiemtra` (
  `idThuocTinhKiemTra` int NOT NULL,
  `tenThuocTinh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenTruongDL` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bangDuLieu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `loaiApDung` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thuoctinh_kiemtra`
--

INSERT INTO `thuoctinh_kiemtra` (`idThuocTinhKiemTra`, `tenThuocTinh`, `tenTruongDL`, `bangDuLieu`, `loaiApDung`) VALUES
(1, 'Điểm trung bình (GPA)', 'GPA', 'sinhvien', 'THAMGIA'),
(2, 'Điểm rèn luyện', 'DRL', 'sinhvien', 'THAMGIA'),
(3, 'Điểm trung bình vòng thi', 'diemTrungBinh', 'sanpham_vongthi', 'VONGTHI'),
(4, 'Xếp loại vòng thi', 'xepLoai', 'sanpham_vongthi', 'VONGTHI'),
(5, 'Trạng thái vòng thi', 'trangThai', 'sanpham_vongthi', 'VONGTHI'),
(6, 'Trạng thái sản phẩm', 'TrangThai', 'sanpham', 'SANPHAM'),
(7, 'Loại tài liệu', 'idloaitailieu', 'sanpham', 'SANPHAM'),
(8, 'Kích hoạt sản phẩm', 'isActive', 'sanpham', 'SANPHAM'),
(9, 'Điểm tổng kết', 'diemTongKet', 'ketqua', 'GIAITHUONG'),
(10, 'Xếp hạng', 'xepHang', 'ketqua', 'GIAITHUONG'),
(11, 'Đã có giải', 'idGiaiThuong', 'ketqua', 'GIAITHUONG');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieuban`
--

CREATE TABLE `tieuban` (
  `idTieuBan` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int DEFAULT NULL,
  `idBoTieuChi` int DEFAULT NULL,
  `tenTieuBan` varchar(100) DEFAULT NULL,
  `moTa` text,
  `isActive` tinyint DEFAULT '1',
  `ngayBaoCao` date DEFAULT NULL,
  `diaDiem` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `tieuban`
--

INSERT INTO `tieuban` (`idTieuBan`, `idSK`, `idVongThi`, `idBoTieuChi`, `tenTieuBan`, `moTa`, `isActive`, `ngayBaoCao`, `diaDiem`) VALUES
(1, 11, 3, NULL, 'Tiểu ban công nghệ AI', NULL, 1, '2026-02-28', '401C'),
(2, 1, 1, 1, 'Tiểu ban công nghệ AI', NULL, 1, NULL, NULL),
(3, 11, 4, NULL, 'Hội đồng CNTT', NULL, 1, '2026-02-28', '202K'),
(4, 500, 500, NULL, 'Tiểu ban công nghệ AI', NULL, 1, '2026-02-28', '404K'),
(801, 800, 801, NULL, 'Tiểu ban Trí tuệ Nhân tạo (AI)', NULL, 1, '2026-03-15', 'Phòng Hội thảo 401'),
(802, 800, 801, NULL, 'Tiểu ban Internet vạn vật (IoT)', NULL, 1, '2026-03-15', 'Phòng Kỹ thuật 402');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieuban_giangvien`
--

CREATE TABLE `tieuban_giangvien` (
  `idTieuBan` int NOT NULL,
  `idGV` int NOT NULL,
  `vaiTro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Thành viên'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tieuban_giangvien`
--

INSERT INTO `tieuban_giangvien` (`idTieuBan`, `idGV`, `vaiTro`) VALUES
(1, 1, 'Thành viên'),
(1, 2, 'Thành viên'),
(2, 1, 'Thành viên'),
(2, 2, 'Thành viên'),
(3, 1, 'Thành viên'),
(3, 2, 'Thành viên'),
(3, 3, 'Thành viên'),
(801, 1, 'Trưởng tiểu ban'),
(801, 3, 'Thành viên'),
(802, 2, 'Trưởng tiểu ban'),
(802, 4, 'Thành viên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieuban_sanpham`
--

CREATE TABLE `tieuban_sanpham` (
  `idTieuBan` int NOT NULL,
  `idSanPham` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tieuban_sanpham`
--

INSERT INTO `tieuban_sanpham` (`idTieuBan`, `idSanPham`) VALUES
(1, 7),
(2, 2),
(801, 801),
(802, 802);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tieuchi`
--

CREATE TABLE `tieuchi` (
  `idTieuChi` int NOT NULL,
  `noiDungTieuChi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tieuchi`
--

INSERT INTO `tieuchi` (`idTieuChi`, `noiDungTieuChi`) VALUES
(1, 'Tính cấp thiết của đề tài'),
(2, 'Phương pháp nghiên cứu'),
(3, 'Kết quả đạt được'),
(4, 'Hình thức trình bày'),
(5, 'tiêu chí điểm rèn luyện'),
(801, 'Tính cấp thiết và ý nghĩa khoa học của đề tài'),
(802, 'Cơ sở lý thuyết và tính hợp lý của phương pháp nghiên cứu'),
(803, 'Mức độ hoàn thiện của sản phẩm / source code'),
(804, 'Tính ứng dụng thực tiễn và tiềm năng thương mại hóa'),
(805, 'Kỹ năng thuyết trình và phản biện trước Hội đồng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `toantu`
--

CREATE TABLE `toantu` (
  `idToanTu` int NOT NULL,
  `kyHieu` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loaiToanTu` enum('logic','compare') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `toantu`
--

INSERT INTO `toantu` (`idToanTu`, `kyHieu`, `moTa`, `loaiToanTu`) VALUES
(1, '=', 'Bằng', 'compare'),
(2, '>', 'Lớn hơn', 'compare'),
(3, '<', 'Nhỏ hơn', 'compare'),
(4, '>=', 'Lớn hơn hoặc bằng', 'compare'),
(5, '<=', 'Nhỏ hơn hoặc bằng', 'compare'),
(6, 'AND', 'Và', 'logic'),
(7, 'OR', 'Hoặc', 'logic');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tohop_dieukien`
--

CREATE TABLE `tohop_dieukien` (
  `idDieuKien` int NOT NULL,
  `idDieuKienTrai` int NOT NULL,
  `idDieuKienPhai` int NOT NULL,
  `idToanTu` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tohop_dieukien`
--

INSERT INTO `tohop_dieukien` (`idDieuKien`, `idDieuKienTrai`, `idDieuKienPhai`, `idToanTu`) VALUES
(3, 1, 2, 6),
(4, 3, 2, 6),
(5, 3, 1, 6),
(8, 6, 7, 6),
(12, 10, 11, 7),
(13, 9, 12, 6),
(16, 14, 15, 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro`
--

CREATE TABLE `vaitro` (
  `idvatro` int NOT NULL,
  `tenvaitro` varchar(100) DEFAULT NULL,
  `mota` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `vaitro`
--

INSERT INTO `vaitro` (`idvatro`, `tenvaitro`, `mota`) VALUES
(1, 'BTC', 'Ban tổ chức, toàn quyền cấu hình sự kiện'),
(2, 'GV_PHAN_BIEN', 'Giảng viên phản biện, chấm bài được phân công'),
(3, 'GV_HUONG_DAN', 'Giảng viên hướng dẫn, xem kết quả sau công bố'),
(4, 'THAM_GIA', 'Sinh viên tham gia thi, tạo nhóm và nộp bài');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitronhom`
--

CREATE TABLE `vaitronhom` (
  `id` int NOT NULL,
  `tenvaitronhom` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `vaitronhom`
--

INSERT INTO `vaitronhom` (`id`, `tenvaitronhom`) VALUES
(1, 'Trưởng nhóm'),
(2, 'Thành viên');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro_quyen`
--

CREATE TABLE `vaitro_quyen` (
  `idVaiTro` int NOT NULL,
  `idQuyen` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin gán quyền cho role hệ thống. Áp dụng mặc định mọi sự kiện.';

--
-- Đang đổ dữ liệu cho bảng `vaitro_quyen`
--

INSERT INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`) VALUES
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(2, 28),
(2, 29),
(4, 30),
(1, 31),
(1, 32),
(2, 32),
(3, 32),
(4, 32),
(4, 33);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro_quyen_sk`
--

CREATE TABLE `vaitro_quyen_sk` (
  `idVaiTroSK` int NOT NULL COMMENT 'Chỉ dùng cho role isSystem=0',
  `idQuyen` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quyền cho role BTC tự tạo. Role isSystem=1 tra vaitro_quyen thay thế.';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro_sukien`
--

CREATE TABLE `vaitro_sukien` (
  `idVaiTroSK` int NOT NULL,
  `idSK` int NOT NULL,
  `idVaiTroGoc` int DEFAULT NULL COMMENT 'NULL = BTC tự tạo | có giá trị = mirror từ role hệ thống',
  `tenVaiTro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isSystem` tinyint DEFAULT '0' COMMENT '1: tự sinh khi tạo sự kiện | 0: BTC tạo thêm',
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Role theo từng sự kiện. isSystem=1 tự sinh khi tạo SK, =0 do BTC tạo thêm.';

--
-- Đang đổ dữ liệu cho bảng `vaitro_sukien`
--

INSERT INTO `vaitro_sukien` (`idVaiTroSK`, `idSK`, `idVaiTroGoc`, `tenVaiTro`, `moTa`, `isSystem`, `isActive`) VALUES
(7, 11, 1, 'Ban tổ chức', 'Toàn quyền cấu hình sự kiện', 1, 1),
(17, 1, 1, 'Ban tổ chức', 'Toàn quyền cấu hình sự kiện', 1, 1),
(24, 11, 2, 'Giảng viên phản biện', 'Chấm bài được phân công', 1, 1),
(34, 1, 2, 'Giảng viên phản biện', 'Chấm bài được phân công', 1, 1),
(41, 11, 3, 'Giảng viên hướng dẫn', 'Xem kết quả sau công bố', 1, 1),
(51, 1, 3, 'Giảng viên hướng dẫn', 'Xem kết quả sau công bố', 1, 1),
(58, 11, 4, 'Sinh viên tham gia', 'Tạo nhóm, nộp bài, xem kết quả', 1, 1),
(68, 1, 4, 'Sinh viên tham gia', 'Tạo nhóm, nộp bài, xem kết quả', 1, 1),
(501, 500, 1, 'Ban tổ chức', 'Toàn quyền cấu hình', 1, 1),
(502, 500, 2, 'Giảng viên phản biện', 'Chấm bài', 1, 1),
(503, 500, 3, 'Giảng viên hướng dẫn', 'Hướng dẫn', 1, 1),
(504, 500, 4, 'Sinh viên tham gia', 'Tham gia', 1, 1),
(505, 501, 1, 'BTC', 'Ban tổ chức, toàn quyền cấu hình sự kiện', 1, 1),
(506, 501, 2, 'GV_PHAN_BIEN', 'Giảng viên phản biện, chấm bài được phân công', 1, 1),
(507, 501, 3, 'GV_HUONG_DAN', 'Giảng viên hướng dẫn, xem kết quả sau công bố', 1, 1),
(508, 501, 4, 'THAM_GIA', 'Sinh viên tham gia thi, tạo nhóm và nộp bài', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vongthi`
--

CREATE TABLE `vongthi` (
  `idVongThi` int NOT NULL,
  `idSK` int NOT NULL,
  `tenVongThi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `thuTu` int DEFAULT '1',
  `ngayBatDau` datetime DEFAULT NULL,
  `ngayKetThuc` datetime DEFAULT NULL,
  `thoiGianDongNop` datetime DEFAULT NULL COMMENT 'Deadline nộp sản phẩm. NULL = chưa đặt',
  `dongNopThuCong` tinyint DEFAULT '0' COMMENT '1 = BTC bấm đóng thủ công'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vongthi`
--

INSERT INTO `vongthi` (`idVongThi`, `idSK`, `tenVongThi`, `moTa`, `thuTu`, `ngayBatDau`, `ngayKetThuc`, `thoiGianDongNop`, `dongNopThuCong`) VALUES
(1, 1, 'Vòng Sơ Loại', 'Nộp báo cáo tóm tắt', 1, '2026-03-15 00:00:00', '2026-03-20 00:00:00', NULL, 0),
(2, 1, 'Vòng Chung Kết', 'Bảo vệ trước hội đồng', 2, '2026-05-15 00:00:00', '2026-05-20 00:00:00', NULL, 0),
(3, 11, 'vong 1', '', 1, '2026-02-19 14:48:00', '2026-02-22 23:48:00', NULL, 0),
(4, 11, 'vong 2', '', 2, '2026-02-28 22:01:00', '2026-03-08 22:01:00', NULL, 0),
(500, 500, 'Vòng Sơ Loại Hackathon', 'Nộp mã nguồn và tài liệu', 1, '2026-02-25 00:00:00', '2026-03-10 00:00:00', NULL, 0),
(801, 800, 'Vòng Sơ khảo (Đánh giá Hồ sơ)', 'Hội đồng chuyên môn đánh giá đề tài qua báo cáo toàn văn và source code.', 1, '2026-03-10 00:00:00', '2026-03-25 00:00:00', NULL, 0),
(802, 800, 'Vòng Chung khảo (Bảo vệ trực tiếp)', 'Các đội thi xuất sắc nhất sẽ thuyết trình và demo sản phẩm trực tiếp trước Hội đồng.', 2, '2026-05-15 00:00:00', '2026-05-25 00:00:00', NULL, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `xacnhan_thamgia`
--

CREATE TABLE `xacnhan_thamgia` (
  `idXacNhan` int NOT NULL,
  `idLichTrinh` int DEFAULT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `vaiTro` varchar(50) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Chờ xác nhân',
  `ngayXacNhan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeucau_thamgia`
--

CREATE TABLE `yeucau_thamgia` (
  `idYeuCau` int NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `ChieuMoi` tinyint DEFAULT '0' COMMENT '0: nhóm gửi lời mời, 1: người dùng yêu cầu tham gia nhóm',
  `loiNhan` text,
  `trangThai` int DEFAULT '0' COMMENT 'Chờ phản hồi/Đã chấp nhận/Đã từ chối',
  `ngayGui` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayPhanHoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `yeucau_thamgia`
--

INSERT INTO `yeucau_thamgia` (`idYeuCau`, `idNhom`, `idTK`, `ChieuMoi`, `loiNhan`, `trangThai`, `ngayGui`, `ngayPhanHoi`) VALUES
(1, 6, 5, 0, '', 1, '2026-02-22 17:07:01', '2026-02-23 09:18:51'),
(2, 803, 5, 0, '', 0, '2026-02-27 13:40:59', NULL),
(3, 803, 7, 0, '', 0, '2026-02-27 13:41:13', NULL),
(4, 804, 4, 0, '', 1, '2026-02-27 15:09:43', '2026-02-27 15:11:10'),
(5, 804, 3, 0, '', 0, '2026-02-27 15:09:59', NULL),
(6, 804, 8, 1, '', 1, '2026-02-27 15:10:44', '2026-02-27 15:12:23');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bantochuc`
--
ALTER TABLE `bantochuc`
  ADD PRIMARY KEY (`idBTC`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idTK` (`idTK`);

--
-- Chỉ mục cho bảng `botieuchi`
--
ALTER TABLE `botieuchi`
  ADD PRIMARY KEY (`idBoTieuChi`);

--
-- Chỉ mục cho bảng `botieuchi_tieuchi`
--
ALTER TABLE `botieuchi_tieuchi`
  ADD PRIMARY KEY (`idBoTieuChi`,`idTieuChi`),
  ADD KEY `idTieuChi` (`idTieuChi`);

--
-- Chỉ mục cho bảng `canhbaodiem`
--
ALTER TABLE `canhbaodiem`
  ADD PRIMARY KEY (`idCanhBao`);

--
-- Chỉ mục cho bảng `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  ADD PRIMARY KEY (`idCap`),
  ADD KEY `idLoaiCap` (`idLoaiCap`);

--
-- Chỉ mục cho bảng `cauhinh_tieuchi_sk`
--
ALTER TABLE `cauhinh_tieuchi_sk`
  ADD PRIMARY KEY (`idSK`,`idVongThi`),
  ADD KEY `idVongThi` (`idVongThi`),
  ADD KEY `idBoTieuChi` (`idBoTieuChi`);

--
-- Chỉ mục cho bảng `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  ADD PRIMARY KEY (`idChamDiem`),
  ADD KEY `idPhanCongCham` (`idPhanCongCham`),
  ADD KEY `idSanPham` (`idSanPham`),
  ADD KEY `idTieuChi` (`idTieuChi`);

--
-- Chỉ mục cho bảng `chude`
--
ALTER TABLE `chude`
  ADD PRIMARY KEY (`idChuDe`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `chude_sukien`
--
ALTER TABLE `chude_sukien`
  ADD PRIMARY KEY (`idChuDeSK`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idchude` (`idchude`);

--
-- Chỉ mục cho bảng `chungnhan`
--
ALTER TABLE `chungnhan`
  ADD PRIMARY KEY (`idChungNhan`),
  ADD KEY `idGiaiThuong` (`idGiaiThuong`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idTK` (`idTK`);

--
-- Chỉ mục cho bảng `diemdanh`
--
ALTER TABLE `diemdanh`
  ADD PRIMARY KEY (`idDiemDanh`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`),
  ADD KEY `idx_dd_lich` (`idLichTrinh`),
  ADD KEY `idx_dd_tk_lich` (`idTK`,`idLichTrinh`);

--
-- Chỉ mục cho bảng `dieukien`
--
ALTER TABLE `dieukien`
  ADD PRIMARY KEY (`idDieuKien`);

--
-- Chỉ mục cho bảng `dieukien_don`
--
ALTER TABLE `dieukien_don`
  ADD PRIMARY KEY (`idDieuKien`),
  ADD KEY `idThuocTinhKiemTra` (`idThuocTinhKiemTra`),
  ADD KEY `idToanTu` (`idToanTu`);

--
-- Chỉ mục cho bảng `giaithuong`
--
ALTER TABLE `giaithuong`
  ADD PRIMARY KEY (`idGiaiThuong`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  ADD PRIMARY KEY (`idGV`),
  ADD UNIQUE KEY `idTK` (`idTK`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Chỉ mục cho bảng `ketqua`
--
ALTER TABLE `ketqua`
  ADD PRIMARY KEY (`idKetQua`),
  ADD KEY `idGiaiThuong` (`idGiaiThuong`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `khoa`
--
ALTER TABLE `khoa`
  ADD PRIMARY KEY (`idKhoa`),
  ADD UNIQUE KEY `maKhoa` (`maKhoa`);

--
-- Chỉ mục cho bảng `lichtrinh`
--
ALTER TABLE `lichtrinh`
  ADD PRIMARY KEY (`idLichTrinh`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVongThi` (`idVongThi`),
  ADD KEY `idx_lt_sk` (`idSK`);

--
-- Chỉ mục cho bảng `loaicap`
--
ALTER TABLE `loaicap`
  ADD PRIMARY KEY (`idLoaiCap`);

--
-- Chỉ mục cho bảng `loaitaikhoan`
--
ALTER TABLE `loaitaikhoan`
  ADD PRIMARY KEY (`idLoaiTK`);

--
-- Chỉ mục cho bảng `loaitailieu`
--
ALTER TABLE `loaitailieu`
  ADD PRIMARY KEY (`idtailieu`);

--
-- Chỉ mục cho bảng `lop`
--
ALTER TABLE `lop`
  ADD PRIMARY KEY (`idLop`),
  ADD UNIQUE KEY `maLop` (`maLop`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Chỉ mục cho bảng `nhom`
--
ALTER TABLE `nhom`
  ADD PRIMARY KEY (`idnhom`),
  ADD UNIQUE KEY `manhom` (`manhom`),
  ADD KEY `idnhomtruong` (`idnhomtruong`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `nhom_quyen`
--
ALTER TABLE `nhom_quyen`
  ADD PRIMARY KEY (`idNhomQuyen`);

--
-- Chỉ mục cho bảng `nienkhoa`
--
ALTER TABLE `nienkhoa`
  ADD PRIMARY KEY (`idNienKhoa`),
  ADD UNIQUE KEY `maNienKhoa` (`maNienKhoa`);

--
-- Chỉ mục cho bảng `phanconbtc`
--
ALTER TABLE `phanconbtc`
  ADD PRIMARY KEY (`idPhanCong`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idBTC` (`idBTC`),
  ADD KEY `idvaitro` (`idvaitro`);

--
-- Chỉ mục cho bảng `phancongcham`
--
ALTER TABLE `phancongcham`
  ADD PRIMARY KEY (`idPhanCongCham`),
  ADD KEY `idGV` (`idGV`),
  ADD KEY `idBoTieuChi` (`idBoTieuChi`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVongThi` (`idVongThi`);

--
-- Chỉ mục cho bảng `phancong_doclap`
--
ALTER TABLE `phancong_doclap`
  ADD PRIMARY KEY (`idSanPham`,`idGV`,`idVongThi`);

--
-- Chỉ mục cho bảng `quyche`
--
ALTER TABLE `quyche`
  ADD PRIMARY KEY (`idQuyChe`),
  ADD KEY `fk_quyche_sukien` (`idSK`);

--
-- Chỉ mục cho bảng `quyche_dieukien`
--
ALTER TABLE `quyche_dieukien`
  ADD PRIMARY KEY (`idQuyChe`),
  ADD KEY `idDieuKienCuoi` (`idDieuKienCuoi`);

--
-- Chỉ mục cho bảng `quyen`
--
ALTER TABLE `quyen`
  ADD PRIMARY KEY (`idQuyen`),
  ADD UNIQUE KEY `maQuyen` (`maQuyen`),
  ADD KEY `idNhomQuyen` (`idNhomQuyen`);

--
-- Chỉ mục cho bảng `quyentaosk`
--
ALTER TABLE `quyentaosk`
  ADD KEY `idgv` (`idgv`),
  ADD KEY `idloaicap` (`idloaicap`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`idSanPham`),
  ADD KEY `idloaitailieu` (`idloaitailieu`),
  ADD KEY `idChuDeSK` (`idChuDeSK`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `sanpham_vongthi`
--
ALTER TABLE `sanpham_vongthi`
  ADD PRIMARY KEY (`idSanPham`,`idVongThi`);

--
-- Chỉ mục cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`idSV`),
  ADD UNIQUE KEY `idTK` (`idTK`),
  ADD UNIQUE KEY `MSV` (`MSV`),
  ADD KEY `idLop` (`idLop`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Chỉ mục cho bảng `sukien`
--
ALTER TABLE `sukien`
  ADD PRIMARY KEY (`idSK`),
  ADD KEY `idCap` (`idCap`),
  ADD KEY `nguoiTao` (`nguoiTao`);

--
-- Chỉ mục cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`idTK`),
  ADD UNIQUE KEY `tenTK` (`tenTK`),
  ADD KEY `idLoaiTK` (`idLoaiTK`);

--
-- Chỉ mục cho bảng `taikhoan_quyen`
--
ALTER TABLE `taikhoan_quyen`
  ADD PRIMARY KEY (`idTK`,`idQuyen`),
  ADD KEY `idQuyen` (`idQuyen`);

--
-- Chỉ mục cho bảng `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tk_sk_vaitro` (`idTK`,`idSK`,`idVaiTroSK`),
  ADD KEY `idTK` (`idTK`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVaiTroSK` (`idVaiTroSK`),
  ADD KEY `idx_check_quyen` (`idTK`,`idSK`,`isActive`),
  ADD KEY `tvs_ibfk_nguoicap` (`idNguoiCap`);

--
-- Chỉ mục cho bảng `thanhviennhom`
--
ALTER TABLE `thanhviennhom`
  ADD KEY `idnhom` (`idnhom`),
  ADD KEY `idtk` (`idtk`),
  ADD KEY `idvaitronhom` (`idvaitronhom`);

--
-- Chỉ mục cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`idThongBao`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `nguoiGui` (`nguoiGui`);

--
-- Chỉ mục cho bảng `thongbao_nguoinhan`
--
ALTER TABLE `thongbao_nguoinhan`
  ADD KEY `idThongBao` (`idThongBao`),
  ADD KEY `idTK` (`idTK`);

--
-- Chỉ mục cho bảng `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  ADD PRIMARY KEY (`idthongtin`),
  ADD KEY `idnhom` (`idnhom`);

--
-- Chỉ mục cho bảng `thuoctinh_kiemtra`
--
ALTER TABLE `thuoctinh_kiemtra`
  ADD PRIMARY KEY (`idThuocTinhKiemTra`);

--
-- Chỉ mục cho bảng `tieuban`
--
ALTER TABLE `tieuban`
  ADD PRIMARY KEY (`idTieuBan`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idx_tieuban_idBoTieuChi` (`idBoTieuChi`);

--
-- Chỉ mục cho bảng `tieuban_giangvien`
--
ALTER TABLE `tieuban_giangvien`
  ADD PRIMARY KEY (`idTieuBan`,`idGV`);

--
-- Chỉ mục cho bảng `tieuban_sanpham`
--
ALTER TABLE `tieuban_sanpham`
  ADD PRIMARY KEY (`idTieuBan`,`idSanPham`);

--
-- Chỉ mục cho bảng `tieuchi`
--
ALTER TABLE `tieuchi`
  ADD PRIMARY KEY (`idTieuChi`);

--
-- Chỉ mục cho bảng `toantu`
--
ALTER TABLE `toantu`
  ADD PRIMARY KEY (`idToanTu`);

--
-- Chỉ mục cho bảng `tohop_dieukien`
--
ALTER TABLE `tohop_dieukien`
  ADD PRIMARY KEY (`idDieuKien`),
  ADD KEY `idDieuKienTrai` (`idDieuKienTrai`),
  ADD KEY `idDieuKienPhai` (`idDieuKienPhai`),
  ADD KEY `idToanTu` (`idToanTu`);

--
-- Chỉ mục cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  ADD PRIMARY KEY (`idvatro`);

--
-- Chỉ mục cho bảng `vaitronhom`
--
ALTER TABLE `vaitronhom`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `vaitro_quyen`
--
ALTER TABLE `vaitro_quyen`
  ADD PRIMARY KEY (`idVaiTro`,`idQuyen`),
  ADD KEY `vaitro_quyen_ibfk_2` (`idQuyen`);

--
-- Chỉ mục cho bảng `vaitro_quyen_sk`
--
ALTER TABLE `vaitro_quyen_sk`
  ADD PRIMARY KEY (`idVaiTroSK`,`idQuyen`),
  ADD KEY `vaitro_quyen_sk_ibfk_2` (`idQuyen`);

--
-- Chỉ mục cho bảng `vaitro_sukien`
--
ALTER TABLE `vaitro_sukien`
  ADD PRIMARY KEY (`idVaiTroSK`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVaiTroGoc` (`idVaiTroGoc`);

--
-- Chỉ mục cho bảng `vongthi`
--
ALTER TABLE `vongthi`
  ADD PRIMARY KEY (`idVongThi`),
  ADD KEY `idSK` (`idSK`);

--
-- Chỉ mục cho bảng `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  ADD PRIMARY KEY (`idXacNhan`),
  ADD KEY `idLichTrinh` (`idLichTrinh`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`);

--
-- Chỉ mục cho bảng `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  ADD PRIMARY KEY (`idYeuCau`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bantochuc`
--
ALTER TABLE `bantochuc`
  MODIFY `idBTC` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `botieuchi`
--
ALTER TABLE `botieuchi`
  MODIFY `idBoTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT cho bảng `canhbaodiem`
--
ALTER TABLE `canhbaodiem`
  MODIFY `idCanhBao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  MODIFY `idCap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  MODIFY `idChamDiem` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `chude`
--
ALTER TABLE `chude`
  MODIFY `idChuDe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chude_sukien`
--
ALTER TABLE `chude_sukien`
  MODIFY `idChuDeSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT cho bảng `chungnhan`
--
ALTER TABLE `chungnhan`
  MODIFY `idChungNhan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `diemdanh`
--
ALTER TABLE `diemdanh`
  MODIFY `idDiemDanh` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `dieukien`
--
ALTER TABLE `dieukien`
  MODIFY `idDieuKien` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `giaithuong`
--
ALTER TABLE `giaithuong`
  MODIFY `idGiaiThuong` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  MODIFY `idGV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `ketqua`
--
ALTER TABLE `ketqua`
  MODIFY `idKetQua` tinyint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khoa`
--
ALTER TABLE `khoa`
  MODIFY `idKhoa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `lichtrinh`
--
ALTER TABLE `lichtrinh`
  MODIFY `idLichTrinh` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `loaicap`
--
ALTER TABLE `loaicap`
  MODIFY `idLoaiCap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `loaitaikhoan`
--
ALTER TABLE `loaitaikhoan`
  MODIFY `idLoaiTK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `loaitailieu`
--
ALTER TABLE `loaitailieu`
  MODIFY `idtailieu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `lop`
--
ALTER TABLE `lop`
  MODIFY `idLop` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `nhom`
--
ALTER TABLE `nhom`
  MODIFY `idnhom` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=805;

--
-- AUTO_INCREMENT cho bảng `nhom_quyen`
--
ALTER TABLE `nhom_quyen`
  MODIFY `idNhomQuyen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `nienkhoa`
--
ALTER TABLE `nienkhoa`
  MODIFY `idNienKhoa` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phanconbtc`
--
ALTER TABLE `phanconbtc`
  MODIFY `idPhanCong` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phancongcham`
--
ALTER TABLE `phancongcham`
  MODIFY `idPhanCongCham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=802;

--
-- AUTO_INCREMENT cho bảng `quyche`
--
ALTER TABLE `quyche`
  MODIFY `idQuyChe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `quyen`
--
ALTER TABLE `quyen`
  MODIFY `idQuyen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `idSanPham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `idSV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `sukien`
--
ALTER TABLE `sukien`
  MODIFY `idSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=801;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `idTK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `idThongBao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  MODIFY `idthongtin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=805;

--
-- AUTO_INCREMENT cho bảng `thuoctinh_kiemtra`
--
ALTER TABLE `thuoctinh_kiemtra`
  MODIFY `idThuocTinhKiemTra` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `tieuban`
--
ALTER TABLE `tieuban`
  MODIFY `idTieuBan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT cho bảng `tieuchi`
--
ALTER TABLE `tieuchi`
  MODIFY `idTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=806;

--
-- AUTO_INCREMENT cho bảng `toantu`
--
ALTER TABLE `toantu`
  MODIFY `idToanTu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  MODIFY `idvatro` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `vaitronhom`
--
ALTER TABLE `vaitronhom`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `vaitro_sukien`
--
ALTER TABLE `vaitro_sukien`
  MODIFY `idVaiTroSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=509;

--
-- AUTO_INCREMENT cho bảng `vongthi`
--
ALTER TABLE `vongthi`
  MODIFY `idVongThi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT cho bảng `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  MODIFY `idXacNhan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  MODIFY `idYeuCau` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `bantochuc`
--
ALTER TABLE `bantochuc`
  ADD CONSTRAINT `bantochuc_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `bantochuc_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`);

--
-- Ràng buộc cho bảng `botieuchi_tieuchi`
--
ALTER TABLE `botieuchi_tieuchi`
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_1` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`),
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_2` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`);

--
-- Ràng buộc cho bảng `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  ADD CONSTRAINT `cap_tochuc_ibfk_1` FOREIGN KEY (`idLoaiCap`) REFERENCES `loaicap` (`idLoaiCap`);

--
-- Ràng buộc cho bảng `cauhinh_tieuchi_sk`
--
ALTER TABLE `cauhinh_tieuchi_sk`
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`),
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`),
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_3` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`);

--
-- Ràng buộc cho bảng `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  ADD CONSTRAINT `chamtieuchi_ibfk_1` FOREIGN KEY (`idPhanCongCham`) REFERENCES `phancongcham` (`idPhanCongCham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chamtieuchi_ibfk_2` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chamtieuchi_ibfk_3` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `chude`
--
ALTER TABLE `chude`
  ADD CONSTRAINT `chude_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `chude_sukien`
--
ALTER TABLE `chude_sukien`
  ADD CONSTRAINT `chude_sukien_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `chude_sukien_ibfk_2` FOREIGN KEY (`idchude`) REFERENCES `chude` (`idChuDe`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `chungnhan`
--
ALTER TABLE `chungnhan`
  ADD CONSTRAINT `chungnhan_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_2` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `diemdanh`
--
ALTER TABLE `diemdanh`
  ADD CONSTRAINT `diemdanh_ibfk_1` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `diemdanh_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `diemdanh_ibfk_3` FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `dieukien_don`
--
ALTER TABLE `dieukien_don`
  ADD CONSTRAINT `dieukien_don_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `dieukien_don_ibfk_2` FOREIGN KEY (`idThuocTinhKiemTra`) REFERENCES `thuoctinh_kiemtra` (`idThuocTinhKiemTra`),
  ADD CONSTRAINT `dieukien_don_ibfk_3` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`);

--
-- Ràng buộc cho bảng `giaithuong`
--
ALTER TABLE `giaithuong`
  ADD CONSTRAINT `giaithuong_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `giangvien`
--
ALTER TABLE `giangvien`
  ADD CONSTRAINT `giangvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `giangvien_ibfk_2` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Ràng buộc cho bảng `ketqua`
--
ALTER TABLE `ketqua`
  ADD CONSTRAINT `ketqua_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ketqua_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ketqua_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `lichtrinh`
--
ALTER TABLE `lichtrinh`
  ADD CONSTRAINT `lichtrinh_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichtrinh_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`);

--
-- Ràng buộc cho bảng `lop`
--
ALTER TABLE `lop`
  ADD CONSTRAINT `lop_ibfk_1` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Ràng buộc cho bảng `nhom`
--
ALTER TABLE `nhom`
  ADD CONSTRAINT `nhom_ibfk_1` FOREIGN KEY (`idnhomtruong`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `nhom_ibfk_2` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `phanconbtc`
--
ALTER TABLE `phanconbtc`
  ADD CONSTRAINT `phanconbtc_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phanconbtc_ibfk_2` FOREIGN KEY (`idBTC`) REFERENCES `bantochuc` (`idBTC`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phanconbtc_ibfk_3` FOREIGN KEY (`idvaitro`) REFERENCES `vaitro` (`idvatro`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `phancongcham`
--
ALTER TABLE `phancongcham`
  ADD CONSTRAINT `phancongcham_ibfk_1` FOREIGN KEY (`idGV`) REFERENCES `giangvien` (`idGV`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_2` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_4` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `quyche`
--
ALTER TABLE `quyche`
  ADD CONSTRAINT `fk_quyche_sukien` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `quyche_dieukien`
--
ALTER TABLE `quyche_dieukien`
  ADD CONSTRAINT `quyche_dieukien_ibfk_1` FOREIGN KEY (`idQuyChe`) REFERENCES `quyche` (`idQuyChe`),
  ADD CONSTRAINT `quyche_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienCuoi`) REFERENCES `dieukien` (`idDieuKien`);

--
-- Ràng buộc cho bảng `quyen`
--
ALTER TABLE `quyen`
  ADD CONSTRAINT `quyen_ibfk_nhomquyen` FOREIGN KEY (`idNhomQuyen`) REFERENCES `nhom_quyen` (`idNhomQuyen`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `quyentaosk`
--
ALTER TABLE `quyentaosk`
  ADD CONSTRAINT `quyentaosk_ibfk_1` FOREIGN KEY (`idgv`) REFERENCES `giangvien` (`idGV`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `quyentaosk_ibfk_2` FOREIGN KEY (`idloaicap`) REFERENCES `loaicap` (`idLoaiCap`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `sanpham_ibfk_1` FOREIGN KEY (`idloaitailieu`) REFERENCES `loaitailieu` (`idtailieu`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `sanpham_ibfk_2` FOREIGN KEY (`idChuDeSK`) REFERENCES `chude_sukien` (`idChuDeSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `sanpham_ibfk_3` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `sanpham_ibfk_4` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `sinhvien_ibfk_2` FOREIGN KEY (`idLop`) REFERENCES `lop` (`idLop`),
  ADD CONSTRAINT `sinhvien_ibfk_3` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Ràng buộc cho bảng `sukien`
--
ALTER TABLE `sukien`
  ADD CONSTRAINT `sukien_ibfk_1` FOREIGN KEY (`idCap`) REFERENCES `cap_tochuc` (`idCap`),
  ADD CONSTRAINT `sukien_ibfk_2` FOREIGN KEY (`nguoiTao`) REFERENCES `taikhoan` (`idTK`);

--
-- Ràng buộc cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `taikhoan_ibfk_1` FOREIGN KEY (`idLoaiTK`) REFERENCES `loaitaikhoan` (`idLoaiTK`);

--
-- Ràng buộc cho bảng `taikhoan_quyen`
--
ALTER TABLE `taikhoan_quyen`
  ADD CONSTRAINT `taikhoan_quyen_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `taikhoan_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  ADD CONSTRAINT `tvs_ibfk_nguoicap` FOREIGN KEY (`idNguoiCap`) REFERENCES `taikhoan` (`idTK`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_vts` FOREIGN KEY (`idVaiTroSK`) REFERENCES `vaitro_sukien` (`idVaiTroSK`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `thanhviennhom`
--
ALTER TABLE `thanhviennhom`
  ADD CONSTRAINT `thanhviennhom_ibfk_1` FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `thanhviennhom_ibfk_2` FOREIGN KEY (`idtk`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `thanhviennhom_ibfk_3` FOREIGN KEY (`idvaitronhom`) REFERENCES `vaitronhom` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `thongbao_ibfk_2` FOREIGN KEY (`nguoiGui`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `thongbao_nguoinhan`
--
ALTER TABLE `thongbao_nguoinhan`
  ADD CONSTRAINT `thongbao_nguoinhan_ibfk_1` FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `thongbao_nguoinhan_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  ADD CONSTRAINT `thongtinnhom_ibfk_1` FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `tieuban`
--
ALTER TABLE `tieuban`
  ADD CONSTRAINT `fk_tieuban_botieuchi` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tieuban_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `tohop_dieukien`
--
ALTER TABLE `tohop_dieukien`
  ADD CONSTRAINT `tohop_dieukien_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `tohop_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienTrai`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `tohop_dieukien_ibfk_3` FOREIGN KEY (`idDieuKienPhai`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `tohop_dieukien_ibfk_4` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`);

--
-- Ràng buộc cho bảng `vaitro_quyen`
--
ALTER TABLE `vaitro_quyen`
  ADD CONSTRAINT `vaitro_quyen_ibfk_1` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idvatro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaitro_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `vaitro_quyen_sk`
--
ALTER TABLE `vaitro_quyen_sk`
  ADD CONSTRAINT `vaitro_quyen_sk_ibfk_1` FOREIGN KEY (`idVaiTroSK`) REFERENCES `vaitro_sukien` (`idVaiTroSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaitro_quyen_sk_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `vaitro_sukien`
--
ALTER TABLE `vaitro_sukien`
  ADD CONSTRAINT `vaitro_sukien_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaitro_sukien_ibfk_vt` FOREIGN KEY (`idVaiTroGoc`) REFERENCES `vaitro` (`idvatro`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `vongthi`
--
ALTER TABLE `vongthi`
  ADD CONSTRAINT `vongthi_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_1` FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ràng buộc cho bảng `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  ADD CONSTRAINT `yeucau_thamgia_ibfk_1` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idnhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `yeucau_thamgia_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
