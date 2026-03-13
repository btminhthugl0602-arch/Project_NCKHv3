-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2026 at 07:15 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nckh`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `idLog` int NOT NULL,
  `idTK` int DEFAULT NULL COMMENT 'Tài khoản thực hiện. NULL nếu hệ thống tự chạy',
  `hanhDong` enum('CREATE','UPDATE','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bangDuLieu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên bảng bị tác động',
  `idDoiTuong` int NOT NULL COMMENT 'ID bản ghi bị tác động',
  `duLieuCu` json DEFAULT NULL COMMENT 'Snapshot trước thay đổi. NULL nếu CREATE',
  `duLieuMoi` json DEFAULT NULL COMMENT 'Snapshot sau thay đổi. NULL nếu DELETE',
  `trangThaiThaoTac` enum('THANH_CONG','THAT_BAI') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kết quả thao tác. Ghi cả khi thất bại để có dấu vết đầy đủ',
  `thoiGian` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ghi lại mọi thao tác quan trọng. Luôn ghi trong transaction riêng.';

-- --------------------------------------------------------

--
-- Table structure for table `botieuchi`
--

CREATE TABLE `botieuchi` (
  `idBoTieuChi` int NOT NULL,
  `tenBoTieuChi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `botieuchi`
--

INSERT INTO `botieuchi` (`idBoTieuChi`, `tenBoTieuChi`, `moTa`) VALUES
(1, 'Bộ tiêu chuẩn NCKH 2026', 'Áp dụng cho vòng chung kết'),
(2, 'Bộ tiêu chuẩn NCKH 2026 (Bản sao)', 'Áp dụng cho vòng chung kết'),
(3, 'Bộ tiêu chuẩn NCKH 2026 (Bản sao) (Bản sao)', 'Áp dụng cho vòng chung kết'),
(801, 'Phiếu chấm Sơ khảo NCKH 2026', 'Barem điểm chuẩn dành cho Hội đồng Sơ khảo (Thang điểm 10)'),
(802, 'Phiếu chấm Chung khảo (Có Thuyết trình)', 'Dành riêng cho vòng Chung khảo, có đánh giá kỹ năng demo'),
(999, 'Bộ tiêu chí Test Phát hiện lệch', 'Thang 10 điểm');

-- --------------------------------------------------------

--
-- Table structure for table `botieuchi_tieuchi`
--

CREATE TABLE `botieuchi_tieuchi` (
  `idBoTieuChi` int NOT NULL,
  `idTieuChi` int NOT NULL,
  `tyTrong` decimal(5,2) DEFAULT '1.00',
  `diemToiDa` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `botieuchi_tieuchi`
--

INSERT INTO `botieuchi_tieuchi` (`idBoTieuChi`, `idTieuChi`, `tyTrong`, `diemToiDa`) VALUES
(1, 1, '1.00', '10.00'),
(1, 2, '1.00', '10.00'),
(1, 3, '1.00', '10.00'),
(1, 4, '1.00', '10.00'),
(1, 5, '1.00', '10.00'),
(2, 1, '1.00', '9.00'),
(2, 2, '1.00', '10.00'),
(2, 3, '1.00', '8.00'),
(2, 4, '1.00', '10.00'),
(2, 5, '1.00', '10.00'),
(3, 1, '1.00', '9.00'),
(3, 2, '1.00', '10.00'),
(3, 3, '1.00', '8.00'),
(3, 4, '1.00', '10.00'),
(3, 5, '1.00', '10.00'),
(801, 801, '1.00', '2.50'),
(801, 802, '1.00', '2.50'),
(801, 803, '1.00', '3.00'),
(801, 804, '1.00', '2.00'),
(802, 801, '1.00', '2.00'),
(802, 803, '1.00', '3.00'),
(802, 804, '1.00', '2.00'),
(802, 805, '1.00', '3.00'),
(999, 991, '1.00', '10.00'),
(999, 992, '1.00', '10.00'),
(999, 993, '1.00', '10.00');

-- --------------------------------------------------------

--
-- Table structure for table `canhbaodiem`
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
-- Table structure for table `cap_tochuc`
--

CREATE TABLE `cap_tochuc` (
  `idCap` int NOT NULL,
  `idLoaiCap` int NOT NULL,
  `tenCap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cap_tochuc`
--

INSERT INTO `cap_tochuc` (`idCap`, `idLoaiCap`, `tenCap`) VALUES
(1, 1, 'Khoa Công nghệ thông tin');

-- --------------------------------------------------------

--
-- Table structure for table `cauhinh_tieuchi_sk`
--

CREATE TABLE `cauhinh_tieuchi_sk` (
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cauhinh_tieuchi_sk`
--

INSERT INTO `cauhinh_tieuchi_sk` (`idSK`, `idVongThi`, `idBoTieuChi`) VALUES
(1, 2, 1),
(11, 3, 1),
(500, 500, 1),
(11, 4, 3),
(800, 801, 801),
(800, 802, 802),
(999, 999, 999);

-- --------------------------------------------------------

--
-- Table structure for table `chamtieuchi`
--

CREATE TABLE `chamtieuchi` (
  `idChamDiem` int NOT NULL,
  `idPhanCongCham` int DEFAULT NULL,
  `idSanPham` int DEFAULT NULL,
  `idTieuChi` int DEFAULT NULL,
  `diem` decimal(5,2) DEFAULT NULL,
  `nhanXet` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `thoiGianCham` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chamtieuchi`
--

INSERT INTO `chamtieuchi` (`idChamDiem`, `idPhanCongCham`, `idSanPham`, `idTieuChi`, `diem`, `nhanXet`, `thoiGianCham`) VALUES
(1, 1, 1, 1, '9.50', 'Tốt', '2026-02-21 14:41:11'),
(2, 1, 1, 2, '9.00', 'Được', '2026-02-21 14:41:11'),
(3, 1, 1, 3, '9.50', 'Rất tốt', '2026-02-21 14:41:11'),
(4, 1, 1, 4, '9.00', 'Tốt', '2026-02-21 14:41:11'),
(5, 1, 1, 5, '10.00', 'Xuất sắc', '2026-02-21 14:41:11'),
(6, 2, 1, 1, '8.00', 'Khá', '2026-02-21 14:41:11'),
(7, 2, 1, 2, '8.50', 'Khá', '2026-02-21 14:41:11'),
(8, 2, 1, 3, '8.00', 'Ổn', '2026-02-21 14:41:11'),
(9, 2, 1, 4, '8.50', 'Khá', '2026-02-21 14:41:11'),
(10, 2, 1, 5, '8.00', 'Được', '2026-02-21 14:41:11'),
(11, 3, 1, 1, '8.50', 'Khá', '2026-02-21 14:41:11'),
(12, 3, 1, 2, '8.00', 'Khá', '2026-02-21 14:41:11'),
(13, 3, 1, 3, '2.00', 'Phương pháp sai lệch', '2026-02-21 14:41:11'),
(14, 3, 1, 4, '3.00', 'Cẩu thả', '2026-02-21 14:41:11'),
(15, 3, 1, 5, '8.50', 'Khá', '2026-02-21 14:41:11'),
(16, 1, 2, 1, '8.50', 'Tốt', '2026-02-21 14:41:11'),
(17, 1, 2, 2, '8.50', 'Được', '2026-02-21 14:41:11'),
(18, 1, 2, 3, '8.00', 'Ổn', '2026-02-21 14:41:11'),
(19, 1, 2, 4, '9.00', 'Tốt', '2026-02-21 14:41:11'),
(20, 1, 2, 5, '8.50', 'Tốt', '2026-02-21 14:41:11'),
(21, 2, 2, 1, '8.00', 'Khá', '2026-02-21 14:41:11'),
(22, 2, 2, 2, '8.00', 'Khá', '2026-02-21 14:41:11'),
(23, 2, 2, 3, '8.50', 'Tốt', '2026-02-21 14:41:11'),
(24, 2, 2, 4, '8.50', 'Khá', '2026-02-21 14:41:11'),
(25, 2, 2, 5, '8.00', 'Được', '2026-02-21 14:41:11'),
(31, 500, 500, 1, '9.00', 'Ý tưởng rất thực tế, có tính ứng dụng cao', '2026-02-23 15:34:08'),
(32, 500, 500, 2, '8.50', 'Cần làm rõ thuật toán nhận diện', '2026-02-23 15:34:08'),
(33, 500, 500, 3, '9.00', 'Demo chạy tốt, ít độ trễ', '2026-02-23 15:34:08'),
(34, 500, 500, 4, '8.00', 'Báo cáo cần chỉn chu hơn', '2026-02-23 15:34:08'),
(35, 500, 500, 5, '9.00', 'Sinh viên nhiệt tình', '2026-02-23 15:34:08'),
(36, 501, 500, 1, '8.50', 'Khá tốt', '2026-02-23 15:34:08'),
(37, 501, 500, 2, '8.00', 'Phương pháp ổn định', '2026-02-23 15:34:08'),
(38, 501, 500, 3, '8.50', 'Tốt', '2026-02-23 15:34:08'),
(39, 501, 500, 4, '8.00', 'Đạt yêu cầu', '2026-02-23 15:34:08'),
(40, 501, 500, 5, '8.50', 'Tốt', '2026-02-23 15:34:08'),
(41, 801, 801, 801, '2.00', 'Tính cấp thiết khá tốt, phù hợp nông nghiệp VN', '2026-02-27 13:33:56'),
(42, 801, 801, 802, '2.50', 'Mô hình YOLOv8 áp dụng chuẩn', '2026-02-27 13:33:56'),
(43, 801, 801, 803, '1.50', 'Cần thêm hình ảnh thực tế', '2026-02-27 13:33:56'),
(44, 991, 991, 991, '9.50', 'Thuật toán tối ưu rất xuất sắc', '2026-03-09 14:59:06'),
(45, 992, 991, 991, '9.00', 'Phương pháp chuẩn chỉ', '2026-03-09 14:59:06'),
(46, 993, 991, 991, '3.00', 'Thuật toán sai hoàn toàn, copy code trên mạng', '2026-03-09 14:59:06'),
(47, 991, 991, 992, '8.00', 'UI ổn', '2026-03-09 14:59:06'),
(48, 992, 991, 992, '8.50', 'Khá đẹp', '2026-03-09 14:59:06'),
(49, 993, 991, 992, '8.00', 'Tạm được', '2026-03-09 14:59:06'),
(50, 991, 991, 993, '9.00', 'Thuyết trình tốt', '2026-03-09 14:59:06'),
(51, 992, 991, 993, '9.00', 'Lưu loát', '2026-03-09 14:59:06'),
(52, 993, 991, 993, '8.50', 'Trả lời phản biện khá', '2026-03-09 14:59:06'),
(53, 991, 992, 991, '8.50', 'Tốt', '2026-03-09 14:59:06'),
(54, 992, 992, 991, '8.00', 'Ổn', '2026-03-09 14:59:06'),
(55, 993, 992, 991, '8.50', 'Đạt yêu cầu', '2026-03-09 14:59:06'),
(56, 991, 992, 992, '7.50', '', '2026-03-09 14:59:06'),
(57, 992, 992, 992, '8.00', '', '2026-03-09 14:59:06'),
(58, 993, 992, 992, '7.00', '', '2026-03-09 14:59:06'),
(59, 991, 992, 993, '9.50', 'Demo mượt mà, thuyết trình xuất sắc', '2026-03-09 14:59:06'),
(60, 992, 992, 993, '2.00', 'Hệ thống lỗi runtime liên tục, không thể demo', '2026-03-09 14:59:06'),
(61, 993, 992, 993, '9.00', 'Demo ấn tượng', '2026-03-09 14:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `chude`
--

CREATE TABLE `chude` (
  `idChuDe` int NOT NULL,
  `tenChuDe` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `moTaChuDe` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isActive` tinyint DEFAULT '1',
  `idNguoiTao` int DEFAULT NULL COMMENT 'NULL = Admin tạo ngân hàng chủ đề; có giá trị = BTC tạo từ sự kiện'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chude`
--

INSERT INTO `chude` (`idChuDe`, `tenChuDe`, `idSK`, `moTaChuDe`, `isActive`, `idNguoiTao`) VALUES
(1, 'Trí tuệ nhân tạo (AI)', 1, 'Các ứng dụng AI trong thực tế', 1, NULL),
(2, 'Internet vạn vật (IoT)', 1, 'Giải pháp nhà thông minh, nông nghiệp thông minh', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chude_sukien`
--

CREATE TABLE `chude_sukien` (
  `idChuDeSK` int NOT NULL,
  `idSK` int NOT NULL,
  `idchude` int DEFAULT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isActive` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chude_sukien`
--

INSERT INTO `chude_sukien` (`idChuDeSK`, `idSK`, `idchude`, `moTa`, `isActive`) VALUES
(1, 1, 1, 'Chuyên đề AI', 1),
(2, 1, 2, 'Chuyên đề IoT', 1),
(801, 800, 1, 'Ứng dụng Trí tuệ nhân tạo (AI)', 1),
(802, 800, 2, 'Hệ thống Internet of Things (IoT)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `chungnhan`
--

CREATE TABLE `chungnhan` (
  `idChungNhan` int NOT NULL,
  `maChungNhan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `loaiChungNhan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ngayCap` datetime DEFAULT NULL,
  `filePDF` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diemdanh`
--

CREATE TABLE `diemdanh` (
  `idDiemDanh` int NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `thoiGianDiemDanh` datetime DEFAULT CURRENT_TIMESTAMP,
  `hienDien` tinyint DEFAULT '1',
  `ghiChu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idPhienDD` int DEFAULT NULL COMMENT 'Liên kết phiên điểm danh cụ thể',
  `phuongThuc` enum('QR','GPS','THU_CONG','NFC') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'QR' COMMENT 'Cách điểm danh được thực hiện',
  `viTriLat` decimal(10,7) DEFAULT NULL COMMENT 'Vị trí SV lúc điểm danh',
  `viTriLng` decimal(10,7) DEFAULT NULL,
  `ipDiemDanh` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP thiết bị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dieukien`
--

CREATE TABLE `dieukien` (
  `idDieuKien` int NOT NULL,
  `loaiDieuKien` enum('DON','TOHOP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenDieuKien` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dieukien`
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
-- Table structure for table `dieukien_don`
--

CREATE TABLE `dieukien_don` (
  `idDieuKien` int NOT NULL,
  `idThuocTinhKiemTra` int NOT NULL,
  `idToanTu` int NOT NULL,
  `giaTriSoSanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dieukien_don`
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
-- Table structure for table `form_field`
--

CREATE TABLE `form_field` (
  `idField` int NOT NULL,
  `idSK` int NOT NULL COMMENT 'Luôn có — field thuộc về sự kiện nào. Dùng để copy form và cascade delete',
  `idVongThi` int DEFAULT NULL COMMENT 'NULL = field mặc định của sự kiện (dùng khi tạo sản phẩm lần đầu)\n             Có giá trị = field riêng của vòng thi đó\n             Logic resolve khi nhóm nộp ở Vòng X:\n               Vòng X có field? → dùng form Vòng X\n               Không → không cần nộp gì, thông báo vòng này không yêu cầu tài liệu',
  `tenTruong` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên hiển thị, vd: "Link Github", "File báo cáo PDF"',
  `kieuTruong` enum('TEXT','TEXTAREA','URL','FILE','SELECT','CHECKBOX') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batBuoc` tinyint NOT NULL DEFAULT '1',
  `thuTu` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự hiển thị trong form',
  `cauHinhJson` json DEFAULT NULL COMMENT 'Cấu hình riêng theo kieuTruong:\n             FILE:     {"accept":"pdf,docx","maxSizeKB":5120}\n             SELECT:   {"options":["Lựa chọn A","Lựa chọn B"]}\n             TEXT:     {"maxLength":200,"placeholder":"Nhập tên đề tài..."}\n             TEXTAREA: {"maxLength":1000,"rows":5}\n             URL:      {"placeholder":"https://github.com/..."}\n             CHECKBOX: {"label":"Tôi xác nhận đã đọc quy định"}',
  `isActive` tinyint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dynamic form BTC thiết kế cho từng vòng thi.\n           Copy form: INSERT lại với idSK/idVongThi đích.\n           Nếu đích đã có field → hỏi BTC: Ghi đè hay Thêm vào.';

-- --------------------------------------------------------

--
-- Table structure for table `giaithuong`
--

CREATE TABLE `giaithuong` (
  `idGiaiThuong` int NOT NULL,
  `idSK` int NOT NULL,
  `tengiaithuong` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `soluong` int DEFAULT '1' COMMENT 'kiểm tra soluong>0',
  `giatri` decimal(15,0) DEFAULT NULL,
  `thutu` int DEFAULT '1',
  `isActive` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giangvien`
--

CREATE TABLE `giangvien` (
  `idGV` int NOT NULL,
  `idTK` int NOT NULL,
  `tenGV` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL,
  `hocHam` enum('Cu_nhan','Tha_si','Tien_si','Pho_giao_su','Giao_su') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Học hàm/học vị giảng viên',
  `gioiTinh` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giangvien`
--

INSERT INTO `giangvien` (`idGV`, `idTK`, `tenGV`, `idKhoa`, `hocHam`, `gioiTinh`) VALUES
(1, 2, 'Nguyễn Văn Minh', 1, NULL, 1),
(2, 3, 'Trần Thị Hương', 1, NULL, 0),
(3, 7, 'TS. Phạm Thị Lan', 1, NULL, 0),
(4, 9, 'GV Khánh', NULL, NULL, 0),
(5, 12, '', NULL, NULL, 0),
(901, 901, 'Giảng viên Chấm Lệch 1', 1, NULL, 0),
(902, 902, 'Giảng viên Chấm Lệch 2', 1, NULL, 0),
(903, 903, 'Giảng viên Chấm Lệch 3', 1, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ketqua`
--

CREATE TABLE `ketqua` (
  `idKetQua` tinyint NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idSK` int DEFAULT NULL,
  `idGiaiThuong` int DEFAULT NULL,
  `diemTongKet` decimal(5,2) DEFAULT NULL,
  `xepHang` int DEFAULT NULL,
  `ghiChu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ngayXetGiai` datetime DEFAULT NULL,
  `isPublic` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khoa`
--

CREATE TABLE `khoa` (
  `idKhoa` int NOT NULL,
  `maKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khoa`
--

INSERT INTO `khoa` (`idKhoa`, `maKhoa`, `tenKhoa`) VALUES
(1, 'CNTT', 'Công nghệ thông tin'),
(2, 'KT', 'Kinh tế');

-- --------------------------------------------------------

--
-- Table structure for table `lichtrinh`
--

CREATE TABLE `lichtrinh` (
  `idLichTrinh` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int DEFAULT NULL,
  `idTieuBan` int DEFAULT NULL COMMENT 'NULL = hoạt động chung cả SK; có giá trị = riêng tiểu ban',
  `tenHoatDong` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `loaiHoatDong` enum('HOAT_DONG','DIEM_DANH','NGHI','KHAC') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HOAT_DONG' COMMENT 'Phân loại để render UI và validate nghiệp vụ',
  `thuTu` int NOT NULL DEFAULT '0' COMMENT 'Thứ tự hiển thị trong lịch trình',
  `thoiGianBatDau` datetime NOT NULL,
  `thoiGianKetThuc` datetime DEFAULT NULL COMMENT 'Thời điểm kết thúc hoạt động',
  `diaDiem` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viTriLat` decimal(10,7) DEFAULT NULL COMMENT 'Vĩ độ GPS địa điểm',
  `viTriLng` decimal(10,7) DEFAULT NULL COMMENT 'Kinh độ GPS địa điểm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loaicap`
--

CREATE TABLE `loaicap` (
  `idLoaiCap` int NOT NULL,
  `tenLoaiCap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loaicap`
--

INSERT INTO `loaicap` (`idLoaiCap`, `tenLoaiCap`) VALUES
(1, 'Cấp Khoa'),
(2, 'Cấp Trường');

-- --------------------------------------------------------

--
-- Table structure for table `loaitaikhoan`
--

CREATE TABLE `loaitaikhoan` (
  `idLoaiTK` int NOT NULL,
  `tenLoaiTK` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loaitaikhoan`
--

INSERT INTO `loaitaikhoan` (`idLoaiTK`, `tenLoaiTK`) VALUES
(1, 'Quản trị viên'),
(2, 'Giảng viên'),
(3, 'Sinh viên');

-- --------------------------------------------------------

--
-- Table structure for table `lop`
--

CREATE TABLE `lop` (
  `idLop` int NOT NULL,
  `maLop` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenLop` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `idKhoa` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lop`
--

INSERT INTO `lop` (`idLop`, `maLop`, `tenLop`, `idKhoa`) VALUES
(1, '64PM1', 'K64 Phần mềm 1', 1),
(2, '64PM2', 'K64 Phần mềm 2', 1),
(3, '64KT1', 'K64 Kinh tế 1', 2);

-- --------------------------------------------------------

--
-- Table structure for table `nhom`
--

CREATE TABLE `nhom` (
  `idNhom` int NOT NULL,
  `idSK` int NOT NULL,
  `idChuNhom` int NOT NULL COMMENT 'Chủ nhóm: GV hoặc SV. Có quyền quản lý hành chính nhóm\n             (mời, kick, duyệt yêu cầu, chọn/đổi trưởng nhóm)',
  `idTruongNhom` int DEFAULT NULL COMMENT 'Trưởng nhóm: bắt buộc là SV. NULL khi GV là chủ nhóm\n             và chưa chỉ định. Người duy nhất được nộp sản phẩm.',
  `maNhom` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã nhóm unique trong sự kiện',
  `ngayTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhóm tham gia sự kiện.\n           Quy tắc:\n           - SV tạo nhóm → idChuNhom = idTruongNhom = SV đó\n           - GV tạo nhóm → idChuNhom = GV, idTruongNhom = NULL\n           - Chỉ Chủ nhóm mới được thay đổi Trưởng nhóm\n           - Chủ nhóm không được rời nếu chưa nhượng quyền\n           - Trưởng nhóm không thể tự bỏ role';

--
-- Dumping data for table `nhom`
--

INSERT INTO `nhom` (`idNhom`, `idSK`, `idChuNhom`, `idTruongNhom`, `maNhom`, `ngayTao`, `isActive`) VALUES
(1, 1, 4, 4, 'GRP_AI_01', '2026-02-11 22:11:23', 1),
(2, 1, 5, 5, 'GRP_IOT_01', '2026-02-21 12:51:34', 1),
(3, 11, 4, 4, 'GRP_THDH_01', '2026-02-21 12:51:34', 1),
(4, 11, 6, 6, 'GRP_THDH_02', '2026-02-21 12:51:34', 1),
(5, 11, 5, 5, 'GRP_THDH_03', '2026-02-21 12:51:34', 1),
(6, 11, 6, 6, 'GRP_177175480715', '2026-02-22 17:06:47', 1),
(500, 500, 4, 4, 'GRP_HACK_01', '2026-02-23 15:34:08', 1),
(501, 500, 6, 6, 'GRP_HACK_02', '2026-02-23 15:34:08', 1),
(502, 501, 5, 5, 'GRP_501_1771841506', '2026-02-23 17:11:46', 1),
(801, 800, 4, 4, 'TEAM_AI_PRO', '2026-02-01 00:00:00', 1),
(802, 800, 6, 6, 'TEAM_SMART_IOT', '2026-02-05 00:00:00', 1),
(803, 1, 8, 8, 'GRP_1_1772174418', '2026-02-27 13:40:18', 1),
(804, 800, 5, 5, 'GRP_800_1772179702', '2026-02-27 15:08:22', 1),
(805, 800, 1, 1, 'GRP_800_1772981521', '2026-03-08 14:52:01', 1),
(991, 999, 904, 904, 'TEAM_TEST_LECH_1', NULL, 1),
(992, 999, 905, 905, 'TEAM_TEST_LECH_2', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nhom_gvhd`
--

CREATE TABLE `nhom_gvhd` (
  `idNhom` int NOT NULL,
  `idTK` int NOT NULL COMMENT 'Tài khoản GV_HUONG_DAN',
  `ngayThamGia` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày GV chính thức vào nhóm (sau khi chấp nhận lời mời)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='GVHD của nhóm. Tách riêng vì:\n           - GVHD không tính vào soThanhVienToiDa\n           - Một nhóm có thể có 0..N GVHD (tùy cấu hình SK)\n           - GVHD có thể chủ động rút khỏi nhóm\n           - GV là Chủ nhóm cũng có bản ghi ở đây\n           - Khi GVHD accept lời mời → INSERT ở đây\n             + INSERT taikhoan_vaitro_sukien (nguonTao=QUA_NHOM)';

-- --------------------------------------------------------

--
-- Table structure for table `nienkhoa`
--

CREATE TABLE `nienkhoa` (
  `idNienKhoa` int NOT NULL,
  `maNienKhoa` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenNienKhoa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phancongcham`
--

CREATE TABLE `phancongcham` (
  `idPhanCongCham` int NOT NULL,
  `idGV` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int NOT NULL,
  `idBoTieuChi` int NOT NULL,
  `trangThaiXacNhan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xác nhận' COMMENT 'Chờ xác nhận/Đã xác nhận/Từ chối',
  `ngayXacNhan` datetime NOT NULL,
  `isActive` tinyint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phancongcham`
--

INSERT INTO `phancongcham` (`idPhanCongCham`, `idGV`, `idSK`, `idVongThi`, `idBoTieuChi`, `trangThaiXacNhan`, `ngayXacNhan`, `isActive`) VALUES
(1, 1, 1, 1, 1, 'Đang chấm', '0000-00-00 00:00:00', 1),
(2, 2, 1, 1, 1, 'Đang chấm', '0000-00-00 00:00:00', 1),
(3, 3, 1, 1, 1, 'Đang chấm', '0000-00-00 00:00:00', 1),
(4, 1, 11, 3, 1, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(500, 2, 500, 500, 1, 'Đang chấm', '2026-02-23 15:34:08', 1),
(501, 3, 500, 500, 1, 'Đang chấm', '2026-02-23 15:34:08', 1),
(801, 1, 800, 801, 801, 'Đang chấm', '2026-02-27 13:33:56', 1),
(991, 901, 999, 999, 999, 'Đang chấm', '2026-03-09 10:00:00', 1),
(992, 902, 999, 999, 999, 'Đang chấm', '2026-03-09 10:05:00', 1),
(993, 903, 999, 999, 999, 'Đang chấm', '2026-03-09 10:10:00', 1),
(994, 2, 11, 3, 1, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(995, 4, 500, 500, 1, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(996, 4, 500, 500, 1, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(997, 3, 800, 801, 801, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(998, 1, 999, 999, 999, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(999, 4, 999, 999, 999, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(1000, 4, 999, 999, 999, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(1001, 1, 999, 999, 999, 'Chờ chấm', '1000-01-01 00:00:00', 1),
(1002, 2, 999, 999, 999, 'Chờ chấm', '1000-01-01 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `phancong_doclap`
--

CREATE TABLE `phancong_doclap` (
  `idSanPham` int NOT NULL,
  `idGV` int NOT NULL,
  `idVongThi` int NOT NULL,
  `isTrongTai` tinyint NOT NULL DEFAULT '0' COMMENT '0 = Giám khảo chính thức (phan_cong_giam_khao), 1 = Trọng tài phúc khảo (moi_trong_tai)',
  `trangThaiCham` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chờ chấm' COMMENT 'Chờ chấm / Đang chấm / Đã xác nhận',
  `ngayNop` datetime DEFAULT NULL COMMENT 'Thời điểm nộp phiếu chấm cho SP này'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phancong_doclap`
--

INSERT INTO `phancong_doclap` (`idSanPham`, `idGV`, `idVongThi`, `isTrongTai`, `trangThaiCham`, `ngayNop`) VALUES
(1, 1, 1, 0, 'Chờ chấm', NULL),
(1, 2, 1, 0, 'Chờ chấm', NULL),
(1, 3, 1, 0, 'Chờ chấm', NULL),
(2, 1, 1, 0, 'Chờ chấm', NULL),
(2, 2, 1, 0, 'Chờ chấm', NULL),
(6, 1, 3, 0, 'Chờ chấm', NULL),
(6, 2, 3, 0, 'Chờ chấm', NULL),
(500, 2, 500, 0, 'Chờ chấm', NULL),
(500, 3, 500, 0, 'Chờ chấm', NULL),
(500, 4, 500, 0, 'Chờ chấm', NULL),
(501, 2, 500, 0, 'Chờ chấm', NULL),
(501, 3, 500, 0, 'Chờ chấm', NULL),
(501, 4, 500, 0, 'Chờ chấm', NULL),
(802, 3, 801, 0, 'Chờ chấm', NULL),
(991, 2, 999, 1, 'Chờ chấm', NULL),
(991, 902, 999, 0, 'Chờ chấm', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `phien_diemdanh`
--

CREATE TABLE `phien_diemdanh` (
  `idPhienDD` int NOT NULL,
  `idLichTrinh` int NOT NULL COMMENT 'FK → lichtrinh. Chỉ hợp lệ khi loaiHoatDong = DIEM_DANH',
  `viTriLat` decimal(10,7) DEFAULT NULL COMMENT 'Tọa độ GPS tâm điểm danh do BTC cài đặt',
  `viTriLng` decimal(10,7) DEFAULT NULL,
  `banKinh` int NOT NULL DEFAULT '150' COMMENT 'Bán kính hợp lệ (mét) để SV điểm danh GPS',
  `thoiGianMo` datetime NOT NULL COMMENT 'Thời điểm BTC mở phiên',
  `thoiGianDong` datetime DEFAULT NULL COMMENT 'Thời điểm đóng. NULL = chưa đóng',
  `trangThai` enum('CHUAN_BI','DANG_MO','DA_DONG') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CHUAN_BI'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phiên điểm danh GPS/QR. 1 lịch trình DIEM_DANH có thể có nhiều phiên.';

-- --------------------------------------------------------

--
-- Table structure for table `quyche`
--

CREATE TABLE `quyche` (
  `idQuyChe` int NOT NULL,
  `tenQuyChe` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `loaiQuyChe` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TUY_CHINH',
  `idSK` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quyche`
--

INSERT INTO `quyche` (`idQuyChe`, `tenQuyChe`, `moTa`, `loaiQuyChe`, `idSK`) VALUES
(1, 'Quy chế tham gia NCKH 2026', 'Yêu cầu về học lực tối thiểu', 'THAMGIA', 1),
(4, 'quy che tham gia', 'Ap dung cho sinh vien', 'THAMGIA', 11),
(5, 'quy chế qua vòng', '', 'VONGTHI', 11),
(6, 'điều kiện', '', 'THAMGIA', 500);

-- --------------------------------------------------------

--
-- Table structure for table `quyche_danhmuc_ngucanh`
--

CREATE TABLE `quyche_danhmuc_ngucanh` (
  `maNguCanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenNguCanh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isHeThong` tinyint(1) NOT NULL DEFAULT '1',
  `ngayTao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quyche_danhmuc_ngucanh`
--

INSERT INTO `quyche_danhmuc_ngucanh` (`maNguCanh`, `tenNguCanh`, `moTa`, `isHeThong`, `ngayTao`) VALUES
('DANG_KY_THAM_GIA_GV', 'Dang ky tham gia (Giang vien)', 'Ap dung khi giang vien dang ky vao su kien', 1, '2026-03-13 12:09:07'),
('DANG_KY_THAM_GIA_SV', 'Dang ky tham gia (Sinh vien)', 'Ap dung khi sinh vien dang ky vao su kien', 1, '2026-03-13 12:09:07'),
('DUYET_VONG_THI', 'Duyet ket qua vong thi', 'Ap dung khi BTC duyet diem va chot trang thai san pham trong vong thi', 1, '2026-03-13 12:09:07'),
('DUYET_VONG_THI_HANG_LOAT', 'Duyet ket qua vong thi hang loat', 'Ap dung khi BTC duyet nhieu san pham cung luc', 1, '2026-03-13 12:09:07'),
('DUYET_YEU_CAU_NHOM', 'Duyet yeu cau nhom', 'Ap dung khi chu nhom duyet yeu cau tham gia', 1, '2026-03-13 12:33:30'),
('GUI_YEU_CAU_NHOM', 'Gui yeu cau nhom', 'Ap dung khi gui loi moi hoac yeu cau vao nhom', 1, '2026-03-13 12:33:30'),
('NOP_SAN_PHAM', 'Nop san pham', 'Ap dung khi nhom tao/cap nhat san pham', 1, '2026-03-13 12:33:30'),
('NOP_TAI_LIEU_VONG_THI', 'Nop tai lieu vong thi', 'Ap dung khi nhom nop tai lieu theo form vong thi', 1, '2026-03-13 12:33:30'),
('TAO_NHOM', 'Tao nhom', 'Ap dung khi tao nhom moi trong su kien', 1, '2026-03-13 12:33:30'),
('THAMGIA', 'Legacy: THAMGIA', 'Ngu canh sinh ra tu loaiQuyChe cu, can review de chuan hoa', 0, '2026-03-13 12:09:08'),
('VONGTHI', 'Legacy: VONGTHI', 'Ngu canh sinh ra tu loaiQuyChe cu, can review de chuan hoa', 0, '2026-03-13 12:09:08'),
('XET_GIAI_THUONG', 'Xet giai thuong', 'Ap dung khi tong hop va xet giai', 1, '2026-03-13 12:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `quyche_dieukien`
--

CREATE TABLE `quyche_dieukien` (
  `idQuyChe` int NOT NULL,
  `idDieuKienCuoi` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quyche_dieukien`
--

INSERT INTO `quyche_dieukien` (`idQuyChe`, `idDieuKienCuoi`) VALUES
(1, 1),
(4, 8),
(5, 13),
(6, 16);

-- --------------------------------------------------------

--
-- Table structure for table `quyche_ngucanh_apdung`
--

CREATE TABLE `quyche_ngucanh_apdung` (
  `idQuyChe` int NOT NULL,
  `maNguCanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ngayGan` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quyche_ngucanh_apdung`
--

INSERT INTO `quyche_ngucanh_apdung` (`idQuyChe`, `maNguCanh`, `ngayGan`) VALUES
(1, 'THAMGIA', '2026-03-13 12:09:08'),
(4, 'THAMGIA', '2026-03-13 12:09:08'),
(5, 'VONGTHI', '2026-03-13 12:09:08'),
(6, 'THAMGIA', '2026-03-13 12:09:08');

-- --------------------------------------------------------

--
-- Table structure for table `quyen`
--

CREATE TABLE `quyen` (
  `idQuyen` int NOT NULL,
  `maQuyen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenQuyen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phamVi` enum('HE_THONG','SU_KIEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SU_KIEN' COMMENT 'HE_THONG: gán cho tài khoản qua taikhoan_quyen | SU_KIEN: gán cho role qua vaitro_quyen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quyen`
--

INSERT INTO `quyen` (`idQuyen`, `maQuyen`, `tenQuyen`, `moTa`, `phamVi`) VALUES
(24, 'cauhinh_sukien', 'Cấu hình thông tin sự kiện', 'Sửa tên, mô tả, thời gian, cấp tổ chức', 'SU_KIEN'),
(27, 'phan_cong_cham', 'Phân công giảng viên chấm điểm', 'Gán GV vào danh sách chấm điểm của vòng thi', 'SU_KIEN'),
(28, 'nhap_diem', 'Nhập điểm chấm', 'Nhập điểm theo từng tiêu chí cho bài được phân công', 'SU_KIEN'),
(29, 'xem_bai_phan_cong', 'Xem bài được phân công chấm', 'Xem nội dung sản phẩm của nhóm mình được phân công', 'SU_KIEN'),
(30, 'nop_san_pham', 'Nộp/cập nhật sản phẩm', 'Nộp hoặc ghi đè sản phẩm khi chưa đóng nộp', 'SU_KIEN'),
(31, 'xem_ketqua_truocCB', 'Xem kết quả trước công bố', 'Xem điểm và xếp hạng khi chưa công bố chính thức', 'SU_KIEN'),
(32, 'xem_ketqua_sauCB', 'Xem kết quả sau công bố', 'Xem kết quả sau khi BTC đã công bố chính thức', 'SU_KIEN'),
(34, 'quan_ly_tai_khoan', 'Quản lý tài khoản', 'Truy cập trang admin/users, tạo và sửa tài khoản', 'HE_THONG'),
(37, 'xem_thong_ke', 'Xem thống kê hệ thống', 'Xem thống kê và báo cáo toàn hệ thống', 'HE_THONG'),
(38, 'tao_su_kien', 'Tạo sự kiện', 'Cho phép tài khoản tạo sự kiện NCKH mới', 'HE_THONG'),
(39, 'quan_ly_diemdanh', 'Quản lý điểm danh', 'Mở/đóng phiên điểm danh trong sự kiện', 'SU_KIEN'),
(40, 'duyet_diem', 'Duyệt và chốt điểm', 'Xem xét và phê duyệt điểm chấm của vòng thi', 'SU_KIEN'),
(41, 'quan_ly_tieuban', 'Quản lý tiểu ban', 'Thêm/bớt giảng viên vào tiểu ban chấm điểm', 'SU_KIEN'),
(42, 'xem_nhom', 'Xem nhóm thi', 'Xem danh sách nhóm, lời mời, và nhóm đang tham gia', 'SU_KIEN'),
(43, 'tao_nhom', 'Tạo nhóm thi', 'Tạo nhóm mới trong sự kiện', 'SU_KIEN');

-- --------------------------------------------------------

--
-- Table structure for table `sanpham`
--

CREATE TABLE `sanpham` (
  `idSanPham` int NOT NULL,
  `idNhom` int NOT NULL,
  `idSK` int NOT NULL,
  `idChuDeSK` int DEFAULT NULL COMMENT 'Chủ đề đề tài — hardcode vì ảnh hưởng phân công tiểu ban chấm',
  `tenSanPham` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên đề tài — hardcode vì dùng ở mọi nơi: chấm điểm, kết quả, chứng nhận',
  `trangThai` enum('CHO_DUYET','DA_DUYET','BI_LOAI') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CHO_DUYET',
  `ngayTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayCapNhat` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sản phẩm của nhóm. 1 bản ghi per nhóm per sự kiện.\n           Nội dung chi tiết (file, link...) lưu ở sanpham_field_value.\n           Chỉ Trưởng nhóm được tạo/cập nhật.\n           Chỉ được sửa khi vongthi.thoiGianDongNop chưa qua.';

--
-- Dumping data for table `sanpham`
--

INSERT INTO `sanpham` (`idSanPham`, `idNhom`, `idSK`, `idChuDeSK`, `tenSanPham`, `trangThai`, `ngayTao`, `ngayCapNhat`) VALUES
(1, 1, 1, 1, 'Hệ thống điểm danh bằng nhận diện khuôn mặt', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(2, 2, 1, 2, 'Hệ thống nhà kính thông minh giám sát qua IoT', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(4, 3, 11, NULL, 'Ứng dụng di động hỗ trợ sinh viên ôn thi trắc nghiệm', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(5, 4, 11, NULL, 'Thuật toán tối ưu hóa lịch biểu giảng đường đại học', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(6, 5, 11, NULL, 'Phần mềm quản lý chi tiêu cá nhân tích hợp AI', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(500, 500, 500, NULL, 'Hệ thống cảnh báo giao thông AI', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(501, 501, 500, NULL, 'App quản lý thời gian Pomodoro 3D', 'CHO_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(801, 801, 800, 801, 'Ứng dụng Deep Learning trong chẩn đoán sớm bệnh lý trên lá lúa', 'DA_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(802, 802, 800, 802, 'Hệ thống nhà kính thông minh giám sát tự động qua Telegram', 'DA_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(991, 991, 999, NULL, 'Hệ thống AI Test Cảnh Báo 1', 'DA_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32'),
(992, 992, 999, NULL, 'Giải pháp IoT Test Cảnh Báo 2', 'DA_DUYET', '2026-03-10 22:48:32', '2026-03-10 22:48:32');

-- --------------------------------------------------------

--
-- Table structure for table `sanpham_field_value`
--

CREATE TABLE `sanpham_field_value` (
  `idSanPham` int NOT NULL,
  `idVongThi` int DEFAULT NULL COMMENT 'NULL = nộp theo form SK mặc định (khi tạo sản phẩm lần đầu)\n             Có giá trị = nộp theo form vòng thi cụ thể',
  `idField` int NOT NULL,
  `giaTriText` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Dùng cho kieuTruong: TEXT, TEXTAREA, URL, SELECT, CHECKBOX',
  `duongDanFile` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dùng cho kieuTruong: FILE. Lưu path tương đối.\n             Pattern: /uploads/sanpham/{idSK}/{idNhom}/{idVongThi}/{tenFile}',
  `ngayNop` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm nộp/cập nhật lần cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Giá trị các field nhóm đã điền khi nộp tài liệu.\n           PRIMARY KEY (idSanPham, idField): mỗi field chỉ có 1 giá trị\n           → nộp lại = UPDATE, không INSERT thêm.\n           Check deadline: vongthi.thoiGianDongNop > NOW() mới cho phép UPDATE.\n           Mỗi vòng có idField riêng trong form_field nên không bị trùng PK.';

-- --------------------------------------------------------

--
-- Table structure for table `sanpham_vongthi`
--

CREATE TABLE `sanpham_vongthi` (
  `idSanPham` int NOT NULL,
  `idVongThi` int NOT NULL,
  `diemTrungBinh` decimal(7,2) DEFAULT NULL COMMENT 'Điểm trung bình chốt của vòng thi (2 chữ số thập phân)',
  `xepLoai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đạt/Không đạt/Xuất sắc',
  `trangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'State machine: NULL(chờ xét lại) / Đã nộp / Đã phân công / Đang xét / Đã duyệt / Bị loại / Đã phúc khảo',
  `ngayCapNhat` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanpham_vongthi`
--

INSERT INTO `sanpham_vongthi` (`idSanPham`, `idVongThi`, `diemTrungBinh`, `xepLoai`, `trangThai`, `ngayCapNhat`) VALUES
(2, 1, '42.00', NULL, 'Đã duyệt', '2026-02-21 14:51:53'),
(500, 500, '42.50', NULL, NULL, '2026-03-10 16:55:35'),
(501, 500, NULL, NULL, 'Đã nộp', '2026-02-23 15:34:08'),
(801, 801, NULL, NULL, 'Đã phân công', '2026-02-27 13:33:56'),
(802, 801, NULL, NULL, 'Đã phân công', '2026-02-27 13:33:56'),
(991, 999, '26.50', NULL, NULL, '2026-03-10 16:27:13'),
(992, 999, '22.67', NULL, NULL, '2026-03-10 16:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `sinhvien`
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
-- Dumping data for table `sinhvien`
--

INSERT INTO `sinhvien` (`idSV`, `idTK`, `tenSV`, `MSV`, `GPA`, `DRL`, `idLop`, `idKhoa`) VALUES
(1, 4, 'Nguyễn Thanh Tùng', 'SV001', '3.60', 90, 1, 1),
(2, 5, 'Lê Thị Mai', 'SV002', '3.20', 85, 1, 1),
(3, 6, 'Hoàng Văn Nam', 'SV003', '2.80', 70, 2, 1),
(4, 8, 'Trần Văn Sơn', 'Hello', '4.00', 100, 1, 1),
(5, 13, 'Họ Tên', 'SV007', '0.00', 0, 3, 2),
(901, 904, 'Sinh viên Lệch 1', 'SV_LECH01', '0.00', 0, 1, 1),
(902, 905, 'Sinh viên Lệch 2', 'SV_LECH02', '0.00', 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sukien`
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
  `isActive` tinyint DEFAULT '1',
  `soNhomToiDaGVHD` int DEFAULT NULL COMMENT 'Số nhóm tối đa 1 GVHD hướng dẫn. NULL = không giới hạn',
  `soThanhVienToiThieu` int NOT NULL DEFAULT '1' COMMENT 'Số SV tối thiểu trong nhóm',
  `soThanhVienToiDa` int NOT NULL DEFAULT '5' COMMENT 'Số SV tối đa trong nhóm (không tính GVHD)',
  `soGVHDToiDa` int DEFAULT NULL COMMENT 'Số GVHD tối đa/nhóm. NULL = không giới hạn',
  `yeuCauCoGVHD` tinyint NOT NULL DEFAULT '0' COMMENT 'Bắt buộc có GVHD mới được nộp bài: 0=không, 1=có',
  `choPhepGVTaoNhom` tinyint NOT NULL DEFAULT '1' COMMENT 'Cho phép GV tạo nhóm: 0=không, 1=có',
  `coGVHDTheoSuKien` tinyint NOT NULL DEFAULT '1' COMMENT 'Bật/tắt luồng GVHD theo sự kiện: 0=không, 1=có',
  `isDeleted` tinyint NOT NULL DEFAULT '0' COMMENT 'Xóa mềm. Tách biệt hoàn toàn với isActive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sukien`
--

INSERT INTO `sukien` (`idSK`, `tenSK`, `moTa`, `idCap`, `nguoiTao`, `ngayMoDangKy`, `ngayDongDangKy`, `ngayBatDau`, `ngayKetThuc`, `isActive`, `soNhomToiDaGVHD`, `soThanhVienToiThieu`, `soThanhVienToiDa`, `soGVHDToiDa`, `yeuCauCoGVHD`, `choPhepGVTaoNhom`, `coGVHDTheoSuKien`, `isDeleted`) VALUES
(1, 'Nghiên cứu khoa học sinh viên CNTT 2026', 'Cuộc thi tìm kiếm ý tưởng công nghệ mới', 1, 2, '2026-01-01 00:00:00', '2026-02-28 00:00:00', '2026-03-15 00:00:00', '2026-05-20 00:00:00', 1, NULL, 1, 5, NULL, 0, 1, 1, 0),
(11, 'tin hoc dai hoc', 'thdh', 1, 1, '2026-02-19 00:00:00', '2026-02-28 00:00:00', '2026-02-19 00:00:00', '2026-02-28 00:00:00', 0, NULL, 1, 5, NULL, 0, 1, 1, 0),
(500, 'Hackathon Sinh viên Công nghệ 2026', 'Sự kiện demo full dữ liệu: Nhóm, Bài nộp, Chấm điểm', 1, 2, '2026-02-01 00:00:00', '2026-02-20 00:00:00', '2026-02-25 00:00:00', '2026-03-30 00:00:00', 1, NULL, 1, 5, NULL, 0, 1, 1, 0),
(501, 'gv Minh tạo', '', 1, 7, '2026-02-23 16:05:00', '2026-03-08 16:05:00', '2026-02-23 16:05:00', '2026-03-08 16:05:00', 0, NULL, 1, 5, NULL, 0, 1, 1, 0),
(800, 'Hội nghị NCKH Sinh viên Khoa CNTT 2026', 'Sự kiện NCKH trọng điểm nhằm tìm kiếm các giải pháp Công nghệ AI, IoT và Phần mềm ứng dụng xuất sắc nhất.', 1, 1, '2026-01-01 00:00:00', '2026-03-01 00:00:00', '2026-03-10 00:00:00', '2026-05-30 00:00:00', 1, NULL, 1, 5, NULL, 0, 1, 1, 0),
(999, 'Sự kiện Test Độ Lệch Điểm 2026', 'Môi trường test cảnh báo độ lệch', 1, 1, '2026-03-09 14:58:02', '2026-03-09 14:58:02', NULL, NULL, 1, NULL, 1, 5, NULL, 0, 1, 1, 0),
(1000, 'Sự kiện test', '', 1, 2, '2026-03-13 10:31:33', '2026-03-13 10:31:33', '2026-03-13 10:31:00', '2026-03-20 10:31:00', 1, NULL, 1, 5, NULL, 0, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
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
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`idTK`, `tenTK`, `matKhau`, `idLoaiTK`, `isActive`, `ngayTao`) VALUES
(1, 'admin', '$2y$10$i/p0RY22.bBzEF1uXDk8Ou9.0Bqgfq4j92F.V.QY/2y4CsdTEOtoi', 1, 1, '2026-02-11 22:11:22'),
(2, 'gv_minh', '$2y$10$TBELRXfgWatDkNwclMjpjuwzIhxYomgpQwGQ3Ujc6WJ8YjRnUXrLi', 2, 1, '2026-02-11 22:11:22'),
(3, 'gv_huong', '123456', 2, 1, '2026-02-11 22:11:22'),
(4, 'sv_tung', '123456', 3, 1, '2026-02-11 22:11:22'),
(5, 'sv_mai', '123456', 3, 1, '2026-02-11 22:11:22'),
(6, 'sv_nam', '$2y$10$FdMez./nIeCICDAKEsM/2OWg03ikBf/XFDPgpS7AmCboKtOAVP2li', 3, 1, '2026-02-11 22:11:22'),
(7, 'gv_lan', '123456', 2, 1, '2026-02-21 14:41:11'),
(8, 'son', '123456', 3, 1, '2026-02-22 23:11:07'),
(9, 'gv_khanh', '123456', 2, 1, '2026-02-23 15:36:21'),
(10, 'gv_them', '123456', 2, 1, '2026-02-23 15:38:35'),
(11, 'gv_long', '123456', 2, 1, '2026-02-23 16:08:05'),
(12, 'gv_hai', '123456', 2, 1, '2026-02-26 16:57:57'),
(13, 'sv1', '$2y$10$VNIjGiU/6p.LckYCKYETveaEW.x.XdaiwYQnhC56.JUtTHUWikBUm', 3, 1, '2026-03-07 23:27:05'),
(901, 'gv_test_lech_1', '123456', 2, 1, '2026-03-09 14:58:02'),
(902, 'gv_test_lech_2', '123456', 2, 1, '2026-03-09 14:58:02'),
(903, 'gv_test_lech_3', '123456', 2, 1, '2026-03-09 14:58:02'),
(904, 'sv_test_lech_1', '123456', 3, 1, '2026-03-09 14:58:02'),
(905, 'sv_test_lech_2', '123456', 3, 1, '2026-03-09 14:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan_quyen`
--

CREATE TABLE `taikhoan_quyen` (
  `idTK` int NOT NULL,
  `idQuyen` int NOT NULL,
  `isActive` tinyint DEFAULT '1',
  `thoiGianBatDau` datetime DEFAULT CURRENT_TIMESTAMP,
  `thoiGianKetThuc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taikhoan_quyen`
--

INSERT INTO `taikhoan_quyen` (`idTK`, `idQuyen`, `isActive`, `thoiGianBatDau`, `thoiGianKetThuc`) VALUES
(1, 34, 1, '2026-02-22 22:40:25', NULL),
(1, 37, 1, '2026-02-22 22:40:25', NULL),
(1, 38, 1, '2026-02-23 15:18:35', NULL),
(2, 38, 1, '2026-02-23 15:18:35', NULL),
(3, 38, 1, '2026-02-23 15:18:35', NULL),
(7, 38, 1, '2026-02-23 15:18:35', NULL),
(9, 38, 1, '2026-02-23 15:36:21', NULL),
(12, 34, 1, '2026-03-07 16:28:47', NULL),
(12, 37, 1, '2026-03-07 16:28:47', NULL),
(12, 38, 1, '2026-03-07 16:28:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan_vaitro_sukien`
--

CREATE TABLE `taikhoan_vaitro_sukien` (
  `id` int NOT NULL,
  `idTK` int NOT NULL,
  `idSK` int NOT NULL,
  `idVaiTro` int NOT NULL,
  `nguonTao` enum('BTC_THEM','PHANCONG_CHAM','QUA_NHOM','DANG_KY','PHAN_CONG_PHAN_BIEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BTC_THEM: BTC thêm | PHANCONG_CHAM: qua phân công chấm | QUA_NHOM: GV vào nhóm | DANG_KY: SV tự đăng ký',
  `idNguoiCap` int DEFAULT NULL COMMENT 'idTK người thực hiện, NULL nếu tự động',
  `ngayCap` datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng trung tâm phân quyền: ai có role gì trong sự kiện nào.';

--
-- Dumping data for table `taikhoan_vaitro_sukien`
--

INSERT INTO `taikhoan_vaitro_sukien` (`id`, `idTK`, `idSK`, `idVaiTro`, `nguonTao`, `idNguoiCap`, `ngayCap`, `isActive`) VALUES
(1, 2, 1, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(2, 3, 1, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(3, 7, 1, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(4, 2, 11, 2, 'PHANCONG_CHAM', NULL, '2026-02-22 22:27:53', 1),
(8, 4, 1, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(9, 5, 1, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(10, 6, 11, 4, 'DANG_KY', NULL, '2026-02-22 22:27:53', 1),
(12, 2, 500, 1, 'BTC_THEM', NULL, '2026-02-23 15:34:08', 1),
(13, 3, 500, 2, 'PHANCONG_CHAM', NULL, '2026-02-23 15:34:08', 1),
(14, 7, 500, 2, 'PHANCONG_CHAM', NULL, '2026-02-23 15:34:08', 1),
(15, 4, 500, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(16, 5, 500, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(17, 6, 500, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(18, 8, 500, 4, 'DANG_KY', NULL, '2026-02-23 15:34:08', 1),
(19, 7, 501, 1, 'BTC_THEM', 7, '2026-02-23 16:05:16', 1),
(20, 901, 999, 2, 'PHANCONG_CHAM', NULL, '2026-03-09 14:58:02', 1),
(21, 902, 999, 2, 'PHANCONG_CHAM', NULL, '2026-03-09 14:58:02', 1),
(22, 903, 999, 2, 'PHANCONG_CHAM', NULL, '2026-03-09 14:58:02', 1),
(23, 904, 999, 4, 'DANG_KY', NULL, '2026-03-09 14:58:02', 1),
(24, 905, 999, 4, 'DANG_KY', NULL, '2026-03-09 14:58:02', 1),
(25, 2, 999, 1, 'BTC_THEM', NULL, '2026-03-09 15:03:46', 1),
(26, 3, 11, 2, 'PHANCONG_CHAM', NULL, '2026-03-11 00:18:50', 1),
(27, 9, 500, 2, 'PHANCONG_CHAM', NULL, '2026-03-11 00:18:50', 1),
(28, 7, 800, 2, 'PHANCONG_CHAM', NULL, '2026-03-11 00:18:50', 1),
(29, 3, 999, 2, 'PHANCONG_CHAM', NULL, '2026-03-11 00:18:50', 1),
(33, 2, 1000, 1, 'BTC_THEM', 2, '2026-03-13 10:31:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `thanhviennhom`
--

CREATE TABLE `thanhviennhom` (
  `idNhom` int NOT NULL,
  `idTK` int NOT NULL COMMENT 'Chỉ là SV (THAM_GIA). GVHD lưu ở bảng nhom_gvhd',
  `ngayThamGia` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thành viên SV đã confirmed trong nhóm.\n           Lưu ý: Chủ nhóm và Trưởng nhóm CŨNG phải có bản ghi ở đây\n           nếu họ là SV. GV chủ nhóm không có bản ghi ở đây.';

--
-- Dumping data for table `thanhviennhom`
--

INSERT INTO `thanhviennhom` (`idNhom`, `idTK`, `ngayThamGia`) VALUES
(1, 4, '2026-02-11 22:11:23'),
(1, 5, '2026-02-11 22:11:23'),
(6, 5, '2026-02-23 09:18:51'),
(6, 6, '2026-02-22 17:06:47'),
(500, 4, '2026-02-23 15:34:08'),
(500, 5, '2026-02-23 15:34:08'),
(501, 6, '2026-02-23 15:34:08'),
(501, 8, '2026-02-23 15:34:08'),
(502, 5, '2026-02-23 17:11:46'),
(801, 4, '2026-02-27 13:33:56'),
(802, 6, '2026-02-27 13:33:56'),
(802, 8, '2026-02-27 13:33:56'),
(803, 8, '2026-02-27 13:40:18'),
(804, 5, '2026-02-27 15:08:22'),
(804, 8, '2026-02-27 15:12:23'),
(805, 1, '2026-03-08 14:52:01'),
(991, 904, '2026-03-09 14:58:02'),
(992, 905, '2026-03-09 14:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `idThongBao` int NOT NULL,
  `tieuDe` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tăng từ 50 → 200 ký tự',
  `noiDung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'NULL cho phép — một số TB chỉ cần tiêu đề',
  `loaiThongBao` enum('SU_KIEN','HE_THONG','NHOM','CA_NHAN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Đổi từ VARCHAR tự do → ENUM',
  `phamVi` enum('CA_NHAN','NHOM_NGUOI','TAT_CA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CA_NHAN: dùng thongbao_ca_nhan | NHOM_NGUOI: dùng thongbao_nhom_nhan | TAT_CA: không insert bảng phụ',
  `idSK` int DEFAULT NULL COMMENT 'NULL nếu không gắn sự kiện cụ thể',
  `idDoiTuong` int DEFAULT NULL COMMENT 'idYeuCau / idNhom / idSanPham tùy loaiDoiTuong',
  `loaiDoiTuong` enum('NHOM','YEUCAU','SANPHAM') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cho biết idDoiTuong là gì. NULL khi idDoiTuong NULL',
  `nguoiGui` int NOT NULL COMMENT 'Luôn có giá trị — mọi TB đều do người kích hoạt',
  `ngayGui` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng trung tâm thông báo. phamVi quyết định bảng phụ nào được dùng.';

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`idThongBao`, `tieuDe`, `noiDung`, `loaiThongBao`, `phamVi`, `idSK`, `idDoiTuong`, `loaiDoiTuong`, `nguoiGui`, `ngayGui`) VALUES
(1, 'Sự kiện mới: Sự kiện test', 'Sự kiện \"Sự kiện test\" vừa được công bố. Hãy xem chi tiết và đăng ký tham gia!', 'SU_KIEN', 'TAT_CA', 1000, NULL, NULL, 2, '2026-03-13 10:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `thongbao_ca_nhan`
--

CREATE TABLE `thongbao_ca_nhan` (
  `idThongBao` int NOT NULL COMMENT 'FK → thongbao',
  `idTK` int NOT NULL COMMENT 'FK → taikhoan — người nhận cụ thể'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Người nhận cụ thể khi phamVi = CA_NHAN.';

-- --------------------------------------------------------

--
-- Table structure for table `thongbao_da_doc`
--

CREATE TABLE `thongbao_da_doc` (
  `idThongBao` int NOT NULL,
  `idTK` int NOT NULL COMMENT 'Người đã đọc',
  `thoiGianDoc` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track trạng thái đọc. Insert khi user click — dùng INSERT IGNORE tránh duplicate.';

-- --------------------------------------------------------

--
-- Table structure for table `thongbao_nhom_nhan`
--

CREATE TABLE `thongbao_nhom_nhan` (
  `idThongBao` int NOT NULL,
  `loaiNhom` enum('SU_KIEN','TIEU_BAN','GV','SV') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại nhóm nhận thông báo',
  `idNhom` int DEFAULT NULL COMMENT 'NULL khi loaiNhom=GV/SV (toàn hệ thống); có giá trị khi SU_KIEN/TIEU_BAN',
  `idVaiTro` int DEFAULT NULL COMMENT 'NULL = tất cả vai trò; có giá trị = lọc theo vai trò trong sự kiện'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thongtinnhom`
--

CREATE TABLE `thongtinnhom` (
  `idthongtin` int NOT NULL,
  `idnhom` int NOT NULL,
  `tennhom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dangtuyen` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongtinnhom`
--

INSERT INTO `thongtinnhom` (`idthongtin`, `idnhom`, `tennhom`, `mota`, `dangtuyen`) VALUES
(1, 1, 'AI Pioneers', 'Nhóm nghiên cứu Computer Vision', 0),
(2, 6, 'a', 'a', 1),
(500, 500, 'Bug Busters', 'Đội chuyên fix bug và tạo bug mới', 0),
(501, 501, 'Cyber Ninjas', 'Đội ninja code dạo đêm khuya', 0),
(502, 502, 'nhom hihi', '', 1),
(801, 801, 'VisionARY Group', 'Nhóm chuyên nghiên cứu Computer Vision', 0),
(802, 802, 'IoT Hardware Lab', 'Nhóm kỹ sư nhúng và IoT', 0),
(803, 803, 'nhom 8383', '', 1),
(804, 804, 'nhom sv mai tao', '', 1),
(805, 805, 'a', 'd', 1),
(991, 991, 'Đội Test Độ Lệch Số 1', NULL, 1),
(992, 992, 'Đội Test Độ Lệch Số 2', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `thuoctinh_kiemtra`
--

CREATE TABLE `thuoctinh_kiemtra` (
  `idThuocTinhKiemTra` int NOT NULL,
  `tenThuocTinh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenTruongDL` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bangDuLieu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `loaiApDung` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THAMGIA_SV'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thuoctinh_kiemtra`
--

INSERT INTO `thuoctinh_kiemtra` (`idThuocTinhKiemTra`, `tenThuocTinh`, `tenTruongDL`, `bangDuLieu`, `loaiApDung`) VALUES
(1, 'Điểm trung bình (GPA)', 'GPA', 'sinhvien', 'THAMGIA_SV'),
(2, 'Điểm rèn luyện', 'DRL', 'sinhvien', 'THAMGIA_SV'),
(3, 'Điểm trung bình vòng thi', 'diemTrungBinh', 'sanpham_vongthi', 'VONGTHI'),
(4, 'Xếp loại vòng thi', 'xepLoai', 'sanpham_vongthi', 'VONGTHI'),
(5, 'Trạng thái vòng thi', 'trangThai', 'sanpham_vongthi', 'VONGTHI'),
(6, 'Trạng thái sản phẩm', 'trangThaiSanPham', 'sanpham', 'SANPHAM'),
(7, 'Loại tài liệu', 'idloaitailieu', 'sanpham', 'SANPHAM'),
(9, 'Điểm tổng kết', 'diemTongKet', 'ketqua', 'GIAITHUONG');

-- --------------------------------------------------------

--
-- Table structure for table `tieuban`
--

CREATE TABLE `tieuban` (
  `idTieuBan` int NOT NULL,
  `idSK` int NOT NULL,
  `idVongThi` int DEFAULT NULL,
  `idBoTieuChi` int DEFAULT NULL,
  `tenTieuBan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isActive` tinyint DEFAULT '1',
  `ngayBaoCao` date DEFAULT NULL,
  `diaDiem` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tieuban`
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
-- Table structure for table `tieuban_giangvien`
--

CREATE TABLE `tieuban_giangvien` (
  `idTieuBan` int NOT NULL,
  `idGV` int NOT NULL,
  `vaiTro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Thành viên'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tieuban_giangvien`
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
-- Table structure for table `tieuban_sanpham`
--

CREATE TABLE `tieuban_sanpham` (
  `idTieuBan` int NOT NULL,
  `idSanPham` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tieuban_sanpham`
--

INSERT INTO `tieuban_sanpham` (`idTieuBan`, `idSanPham`) VALUES
(2, 2),
(801, 801),
(802, 802);

-- --------------------------------------------------------

--
-- Table structure for table `tieuban_phan_bien`
--

CREATE TABLE `tieuban_phan_bien` (
  `idPhanBien` int NOT NULL,
  `idSanPham` int NOT NULL COMMENT 'Bài báo cáo được phân công phản biện',
  `idGV` int NOT NULL COMMENT 'Giảng viên phản biện (phải trong tiểu ban chứa bài)',
  `idSK` int NOT NULL COMMENT 'Sự kiện',
  `trangThaiCham` enum('Chờ chấm','Đang chấm','Đã nộp') NOT NULL DEFAULT 'Chờ chấm',
  `ngayPhanCong` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayNop` datetime DEFAULT NULL COMMENT 'Thời điểm GV nộp phiếu chấm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Phân công phản biện offline trong tiểu ban. Tách biệt với phancong_doclap (online).';

-- --------------------------------------------------------

--
-- Table structure for table `tieuchi`
--

CREATE TABLE `tieuchi` (
  `idTieuChi` int NOT NULL,
  `noiDungTieuChi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tieuchi`
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
(805, 'Kỹ năng thuyết trình và phản biện trước Hội đồng'),
(991, 'Tiêu chí 1: Giải pháp & Thuật toán'),
(992, 'Tiêu chí 2: Trải nghiệm người dùng (UX/UI)'),
(993, 'Tiêu chí 3: Kỹ năng thuyết trình & Demo');

-- --------------------------------------------------------

--
-- Table structure for table `toantu`
--

CREATE TABLE `toantu` (
  `idToanTu` int NOT NULL,
  `kyHieu` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loaiToanTu` enum('logic','compare') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `toantu`
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
-- Table structure for table `tohop_dieukien`
--

CREATE TABLE `tohop_dieukien` (
  `idDieuKien` int NOT NULL,
  `idDieuKienTrai` int NOT NULL,
  `idDieuKienPhai` int NOT NULL,
  `idToanTu` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tohop_dieukien`
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
-- Table structure for table `vaitro`
--

CREATE TABLE `vaitro` (
  `idVaiTro` int NOT NULL,
  `maVaiTro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenvaitro` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vaitro`
--

INSERT INTO `vaitro` (`idVaiTro`, `maVaiTro`, `tenvaitro`, `mota`) VALUES
(1, 'BTC', 'Ban tổ chức', 'Ban tổ chức, toàn quyền cấu hình sự kiện'),
(2, 'GV_PHAN_BIEN', 'Giảng viên phản biện', 'Giảng viên phản biện, chấm bài được phân công'),
(3, 'GV_HUONG_DAN', 'Giảng viên hướng dẫn', 'Giảng viên hướng dẫn, xem kết quả sau công bố'),
(4, 'THAM_GIA', 'Sinh viên tham gia', 'Sinh viên tham gia thi, tạo nhóm và nộp bài'),
(5, 'GV_CHAM_DOCLAP', 'GV Chấm độc lập', 'Giảng viên chấm điểm độc lập theo phân công'),
(6, 'GV_CHAM_TIEUBAN', 'GV Chấm tiểu ban', 'Giảng viên chấm điểm trong tiểu ban');

-- --------------------------------------------------------

--
-- Table structure for table `vaitro_quyen`
--

CREATE TABLE `vaitro_quyen` (
  `idVaiTro` int NOT NULL,
  `idQuyen` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin gán quyền cho role hệ thống. Áp dụng mặc định mọi sự kiện.';

--
-- Dumping data for table `vaitro_quyen`
--

INSERT INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`) VALUES
(1, 24),
(1, 27),
(2, 28),
(5, 28),
(6, 28),
(2, 29),
(5, 29),
(6, 29),
(1, 31),
(1, 32),
(2, 32),
(3, 32),
(4, 32),
(5, 32),
(6, 32),
(1, 39),
(1, 40),
(1, 41),
(3, 42),
(4, 42),
(3, 43),
(4, 43);

-- --------------------------------------------------------

--
-- Table structure for table `vaitro_sukien`
--

CREATE TABLE `vaitro_sukien` (
  `idSK` int NOT NULL,
  `idVaiTro` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Role theo từng sự kiện. isSystem=1 tự sinh khi tạo SK, =0 do BTC tạo thêm.';

--
-- Dumping data for table `vaitro_sukien`
--

INSERT INTO `vaitro_sukien` (`idSK`, `idVaiTro`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(11, 1),
(11, 2),
(11, 3),
(11, 4),
(500, 1),
(500, 2),
(500, 3),
(500, 4),
(501, 1),
(501, 2),
(501, 3),
(501, 4);

-- --------------------------------------------------------

--
-- Table structure for table `vongthi`
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
-- Dumping data for table `vongthi`
--

INSERT INTO `vongthi` (`idVongThi`, `idSK`, `tenVongThi`, `moTa`, `thuTu`, `ngayBatDau`, `ngayKetThuc`, `thoiGianDongNop`, `dongNopThuCong`) VALUES
(1, 1, 'Vòng Sơ Loại', 'Nộp báo cáo tóm tắt', 1, '2026-03-15 00:00:00', '2026-03-20 00:00:00', NULL, 0),
(2, 1, 'Vòng Chung Kết', 'Bảo vệ trước hội đồng', 2, '2026-05-15 00:00:00', '2026-05-20 00:00:00', NULL, 0),
(3, 11, 'vong 1', '', 1, '2026-02-19 14:48:00', '2026-02-22 23:48:00', NULL, 0),
(4, 11, 'vong 2', '', 2, '2026-02-28 22:01:00', '2026-03-08 22:01:00', NULL, 0),
(500, 500, 'Vòng Sơ Loại Hackathon', 'Nộp mã nguồn và tài liệu', 1, '2026-02-25 00:00:00', '2026-03-10 00:00:00', NULL, 0),
(801, 800, 'Vòng Sơ khảo (Đánh giá Hồ sơ)', 'Hội đồng chuyên môn đánh giá đề tài qua báo cáo toàn văn và source code.', 1, '2026-03-10 00:00:00', '2026-03-25 00:00:00', NULL, 0),
(802, 800, 'Vòng Chung khảo (Bảo vệ trực tiếp)', 'Các đội thi xuất sắc nhất sẽ thuyết trình và demo sản phẩm trực tiếp trước Hội đồng.', 2, '2026-05-15 00:00:00', '2026-05-25 00:00:00', NULL, 0),
(999, 999, 'Vòng Test Độ Lệch', 'Vòng thi áp dụng thuật toán phát hiện lệch điểm', 1, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_giam_khao_san_pham`
-- (See below for the actual view)
--
CREATE TABLE `v_giam_khao_san_pham` (
`idGV` int
,`idSanPham` int
,`idVongThi` int
,`isTrongTai` int
,`nguon` varchar(11)
,`tenGV` varchar(100)
,`tenTK` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `xacnhan_thamgia`
--

CREATE TABLE `xacnhan_thamgia` (
  `idXacNhan` int NOT NULL,
  `idLichTrinh` int DEFAULT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `vaiTro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xác nhân',
  `ngayXacNhan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yeucau_thamgia`
--

CREATE TABLE `yeucau_thamgia` (
  `idYeuCau` int NOT NULL,
  `idNhom` int DEFAULT NULL,
  `idTK` int DEFAULT NULL,
  `ChieuMoi` tinyint DEFAULT '0' COMMENT '0: nhóm gửi lời mời, 1: người dùng yêu cầu tham gia nhóm',
  `loaiYeuCau` enum('SV','GVHD') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SV' COMMENT 'SV: yêu cầu thành viên sinh viên\n             GVHD: mời giảng viên hướng dẫn',
  `loiNhan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `trangThai` int DEFAULT '0' COMMENT 'Chờ phản hồi/Đã chấp nhận/Đã từ chối',
  `ngayGui` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayPhanHoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `yeucau_thamgia`
--

INSERT INTO `yeucau_thamgia` (`idYeuCau`, `idNhom`, `idTK`, `ChieuMoi`, `loaiYeuCau`, `loiNhan`, `trangThai`, `ngayGui`, `ngayPhanHoi`) VALUES
(1, 6, 5, 0, 'SV', '', 1, '2026-02-22 17:07:01', '2026-02-23 09:18:51'),
(2, 803, 5, 0, 'SV', '', 0, '2026-02-27 13:40:59', NULL),
(3, 803, 7, 0, 'SV', '', 0, '2026-02-27 13:41:13', NULL),
(4, 804, 4, 0, 'SV', '', 1, '2026-02-27 15:09:43', '2026-02-27 15:11:10'),
(5, 804, 3, 0, 'SV', '', 0, '2026-02-27 15:09:59', NULL),
(6, 804, 8, 1, 'SV', '', 1, '2026-02-27 15:10:44', '2026-02-27 15:12:23'),
(7, 804, 1, 1, 'SV', 'hello', 0, '2026-03-07 16:45:56', NULL);

-- --------------------------------------------------------

--
-- Structure for view `v_giam_khao_san_pham`
--
DROP TABLE IF EXISTS `v_giam_khao_san_pham`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_giam_khao_san_pham`  AS SELECT `allgk`.`idSanPham` AS `idSanPham`, `allgk`.`idGV` AS `idGV`, `allgk`.`idVongThi` AS `idVongThi`, `allgk`.`nguon` AS `nguon`, `gv`.`tenGV` AS `tenGV`, `tk`.`tenTK` AS `tenTK`, coalesce(`pd`.`isTrongTai`,0) AS `isTrongTai` FROM ((((select `pd_inner`.`idSanPham` AS `idSanPham`,`pd_inner`.`idGV` AS `idGV`,`pd_inner`.`idVongThi` AS `idVongThi`,'phancong' AS `nguon` from `phancong_doclap` `pd_inner` union select distinct `ct`.`idSanPham` AS `idSanPham`,`pcc`.`idGV` AS `idGV`,`pcc`.`idVongThi` AS `idVongThi`,'chamtieuchi' AS `nguon` from (`chamtieuchi` `ct` join `phancongcham` `pcc` on((`ct`.`idPhanCongCham` = `pcc`.`idPhanCongCham`)))) `allgk` join `giangvien` `gv` on((`gv`.`idGV` = `allgk`.`idGV`))) join `taikhoan` `tk` on((`tk`.`idTK` = `gv`.`idTK`))) left join `phancong_doclap` `pd` on(((`pd`.`idGV` = `allgk`.`idGV`) and (`pd`.`idSanPham` = `allgk`.`idSanPham`) and (`pd`.`idVongThi` = `allgk`.`idVongThi`))))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`idLog`),
  ADD KEY `idx_audit_bang` (`bangDuLieu`),
  ADD KEY `idx_audit_doi_tuong` (`bangDuLieu`,`idDoiTuong`),
  ADD KEY `idx_audit_tk` (`idTK`);

--
-- Indexes for table `botieuchi`
--
ALTER TABLE `botieuchi`
  ADD PRIMARY KEY (`idBoTieuChi`);

--
-- Indexes for table `botieuchi_tieuchi`
--
ALTER TABLE `botieuchi_tieuchi`
  ADD PRIMARY KEY (`idBoTieuChi`,`idTieuChi`),
  ADD KEY `idTieuChi` (`idTieuChi`);

--
-- Indexes for table `canhbaodiem`
--
ALTER TABLE `canhbaodiem`
  ADD PRIMARY KEY (`idCanhBao`);

--
-- Indexes for table `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  ADD PRIMARY KEY (`idCap`),
  ADD KEY `idLoaiCap` (`idLoaiCap`);

--
-- Indexes for table `cauhinh_tieuchi_sk`
--
ALTER TABLE `cauhinh_tieuchi_sk`
  ADD PRIMARY KEY (`idSK`,`idVongThi`),
  ADD KEY `idVongThi` (`idVongThi`),
  ADD KEY `idBoTieuChi` (`idBoTieuChi`);

--
-- Indexes for table `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  ADD PRIMARY KEY (`idChamDiem`),
  ADD UNIQUE KEY `uq_chamtieuchi_pcc_sp_tc` (`idPhanCongCham`,`idSanPham`,`idTieuChi`),
  ADD KEY `idPhanCongCham` (`idPhanCongCham`),
  ADD KEY `idSanPham` (`idSanPham`),
  ADD KEY `idTieuChi` (`idTieuChi`);

--
-- Indexes for table `chude`
--
ALTER TABLE `chude`
  ADD PRIMARY KEY (`idChuDe`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `chude_ibfk_nguoitao` (`idNguoiTao`);

--
-- Indexes for table `chude_sukien`
--
ALTER TABLE `chude_sukien`
  ADD PRIMARY KEY (`idChuDeSK`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idchude` (`idchude`);

--
-- Indexes for table `chungnhan`
--
ALTER TABLE `chungnhan`
  ADD PRIMARY KEY (`idChungNhan`),
  ADD KEY `idGiaiThuong` (`idGiaiThuong`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idTK` (`idTK`);

--
-- Indexes for table `diemdanh`
--
ALTER TABLE `diemdanh`
  ADD PRIMARY KEY (`idDiemDanh`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`),
  ADD KEY `idx_dd_phien` (`idPhienDD`),
  ADD KEY `idx_dd_tk_phien` (`idTK`,`idPhienDD`);

--
-- Indexes for table `dieukien`
--
ALTER TABLE `dieukien`
  ADD PRIMARY KEY (`idDieuKien`);

--
-- Indexes for table `dieukien_don`
--
ALTER TABLE `dieukien_don`
  ADD PRIMARY KEY (`idDieuKien`),
  ADD KEY `idThuocTinhKiemTra` (`idThuocTinhKiemTra`),
  ADD KEY `idToanTu` (`idToanTu`);

--
-- Indexes for table `form_field`
--
ALTER TABLE `form_field`
  ADD PRIMARY KEY (`idField`),
  ADD KEY `idx_ff_sk` (`idSK`),
  ADD KEY `idx_ff_vongthi` (`idVongThi`),
  ADD KEY `idx_ff_sk_vongthi` (`idSK`,`idVongThi`);

--
-- Indexes for table `giaithuong`
--
ALTER TABLE `giaithuong`
  ADD PRIMARY KEY (`idGiaiThuong`),
  ADD KEY `idSK` (`idSK`);

--
-- Indexes for table `giangvien`
--
ALTER TABLE `giangvien`
  ADD PRIMARY KEY (`idGV`),
  ADD UNIQUE KEY `idTK` (`idTK`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Indexes for table `ketqua`
--
ALTER TABLE `ketqua`
  ADD PRIMARY KEY (`idKetQua`),
  ADD KEY `idGiaiThuong` (`idGiaiThuong`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idSK` (`idSK`);

--
-- Indexes for table `khoa`
--
ALTER TABLE `khoa`
  ADD PRIMARY KEY (`idKhoa`),
  ADD UNIQUE KEY `maKhoa` (`maKhoa`);

--
-- Indexes for table `lichtrinh`
--
ALTER TABLE `lichtrinh`
  ADD PRIMARY KEY (`idLichTrinh`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVongThi` (`idVongThi`),
  ADD KEY `idx_lt_sk` (`idSK`),
  ADD KEY `lichtrinh_ibfk_tieuban` (`idTieuBan`);

--
-- Indexes for table `loaicap`
--
ALTER TABLE `loaicap`
  ADD PRIMARY KEY (`idLoaiCap`);

--
-- Indexes for table `loaitaikhoan`
--
ALTER TABLE `loaitaikhoan`
  ADD PRIMARY KEY (`idLoaiTK`);

--
-- Indexes for table `lop`
--
ALTER TABLE `lop`
  ADD PRIMARY KEY (`idLop`),
  ADD UNIQUE KEY `maLop` (`maLop`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Indexes for table `nhom`
--
ALTER TABLE `nhom`
  ADD PRIMARY KEY (`idNhom`),
  ADD UNIQUE KEY `uq_manhom_sk` (`maNhom`,`idSK`),
  ADD KEY `idx_nhom_sk` (`idSK`),
  ADD KEY `idx_nhom_chunhom` (`idChuNhom`),
  ADD KEY `idx_nhom_truongnhom` (`idTruongNhom`);

--
-- Indexes for table `nhom_gvhd`
--
ALTER TABLE `nhom_gvhd`
  ADD PRIMARY KEY (`idNhom`,`idTK`),
  ADD KEY `gvhd_fk_tk` (`idTK`);

--
-- Indexes for table `nienkhoa`
--
ALTER TABLE `nienkhoa`
  ADD PRIMARY KEY (`idNienKhoa`),
  ADD UNIQUE KEY `maNienKhoa` (`maNienKhoa`);

--
-- Indexes for table `phancongcham`
--
ALTER TABLE `phancongcham`
  ADD PRIMARY KEY (`idPhanCongCham`),
  ADD KEY `idGV` (`idGV`),
  ADD KEY `idBoTieuChi` (`idBoTieuChi`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idVongThi` (`idVongThi`),
  ADD KEY `idx_pcc_vongthi_active` (`idVongThi`,`isActive`);

--
-- Indexes for table `phancong_doclap`
--
ALTER TABLE `phancong_doclap`
  ADD PRIMARY KEY (`idSanPham`,`idGV`,`idVongThi`);

--
-- Indexes for table `phien_diemdanh`
--
ALTER TABLE `phien_diemdanh`
  ADD PRIMARY KEY (`idPhienDD`),
  ADD KEY `idx_phiendd_lichtrinh` (`idLichTrinh`);

--
-- Indexes for table `quyche`
--
ALTER TABLE `quyche`
  ADD PRIMARY KEY (`idQuyChe`),
  ADD KEY `fk_quyche_sukien` (`idSK`);

--
-- Indexes for table `quyche_danhmuc_ngucanh`
--
ALTER TABLE `quyche_danhmuc_ngucanh`
  ADD PRIMARY KEY (`maNguCanh`);

--
-- Indexes for table `quyche_dieukien`
--
ALTER TABLE `quyche_dieukien`
  ADD PRIMARY KEY (`idQuyChe`),
  ADD KEY `idDieuKienCuoi` (`idDieuKienCuoi`);

--
-- Indexes for table `quyche_ngucanh_apdung`
--
ALTER TABLE `quyche_ngucanh_apdung`
  ADD PRIMARY KEY (`idQuyChe`,`maNguCanh`),
  ADD KEY `idx_quyche_ngucanh_ma` (`maNguCanh`);

--
-- Indexes for table `quyen`
--
ALTER TABLE `quyen`
  ADD PRIMARY KEY (`idQuyen`),
  ADD UNIQUE KEY `maQuyen` (`maQuyen`);

--
-- Indexes for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`idSanPham`),
  ADD UNIQUE KEY `uq_nhom_sk` (`idNhom`,`idSK`) COMMENT 'Mỗi nhóm chỉ có 1 sản phẩm per sự kiện',
  ADD KEY `idx_sp_sk` (`idSK`),
  ADD KEY `idx_sp_chude` (`idChuDeSK`);

--
-- Indexes for table `sanpham_field_value`
--
ALTER TABLE `sanpham_field_value`
  ADD PRIMARY KEY (`idSanPham`,`idField`),
  ADD KEY `idx_sfv_vongthi` (`idVongThi`),
  ADD KEY `idx_sfv_field` (`idField`);

--
-- Indexes for table `sanpham_vongthi`
--
ALTER TABLE `sanpham_vongthi`
  ADD PRIMARY KEY (`idSanPham`,`idVongThi`);

--
-- Indexes for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`idSV`),
  ADD UNIQUE KEY `idTK` (`idTK`),
  ADD UNIQUE KEY `MSV` (`MSV`),
  ADD KEY `idLop` (`idLop`),
  ADD KEY `idKhoa` (`idKhoa`);

--
-- Indexes for table `sukien`
--
ALTER TABLE `sukien`
  ADD PRIMARY KEY (`idSK`),
  ADD KEY `idCap` (`idCap`),
  ADD KEY `nguoiTao` (`nguoiTao`);

--
-- Indexes for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`idTK`),
  ADD UNIQUE KEY `tenTK` (`tenTK`),
  ADD KEY `idLoaiTK` (`idLoaiTK`);

--
-- Indexes for table `taikhoan_quyen`
--
ALTER TABLE `taikhoan_quyen`
  ADD PRIMARY KEY (`idTK`,`idQuyen`),
  ADD KEY `idQuyen` (`idQuyen`);

--
-- Indexes for table `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tk_sk_vaitro` (`idTK`,`idSK`,`idVaiTro`),
  ADD KEY `idTK` (`idTK`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idx_check_quyen` (`idTK`,`idSK`,`isActive`),
  ADD KEY `tvs_ibfk_nguoicap` (`idNguoiCap`),
  ADD KEY `idx_tvs_vaitro` (`idVaiTro`);

--
-- Indexes for table `thanhviennhom`
--
ALTER TABLE `thanhviennhom`
  ADD PRIMARY KEY (`idNhom`,`idTK`),
  ADD KEY `thanhvien_fk_tk` (`idTK`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`idThongBao`),
  ADD KEY `idx_tb_sk` (`idSK`),
  ADD KEY `idx_tb_nguoigui` (`nguoiGui`);

--
-- Indexes for table `thongbao_ca_nhan`
--
ALTER TABLE `thongbao_ca_nhan`
  ADD PRIMARY KEY (`idThongBao`,`idTK`),
  ADD KEY `idx_tbcn_tk` (`idTK`);

--
-- Indexes for table `thongbao_da_doc`
--
ALTER TABLE `thongbao_da_doc`
  ADD PRIMARY KEY (`idThongBao`,`idTK`),
  ADD KEY `idx_tbdd_tk` (`idTK`);

--
-- Indexes for table `thongbao_nhom_nhan`
--
ALTER TABLE `thongbao_nhom_nhan`
  ADD PRIMARY KEY (`idThongBao`,`loaiNhom`),
  ADD KEY `idx_tbnn_vaitro` (`idVaiTro`);

--
-- Indexes for table `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  ADD PRIMARY KEY (`idthongtin`),
  ADD KEY `idnhom` (`idnhom`);

--
-- Indexes for table `thuoctinh_kiemtra`
--
ALTER TABLE `thuoctinh_kiemtra`
  ADD PRIMARY KEY (`idThuocTinhKiemTra`);

--
-- Indexes for table `tieuban`
--
ALTER TABLE `tieuban`
  ADD PRIMARY KEY (`idTieuBan`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `idx_tieuban_idBoTieuChi` (`idBoTieuChi`);

--
-- Indexes for table `tieuban_giangvien`
--
ALTER TABLE `tieuban_giangvien`
  ADD PRIMARY KEY (`idTieuBan`,`idGV`);

--
-- Indexes for table `tieuban_sanpham`
--
ALTER TABLE `tieuban_sanpham`
  ADD PRIMARY KEY (`idTieuBan`,`idSanPham`),
  ADD KEY `tieuban_sanpham_ibfk_2` (`idSanPham`);

--
-- Indexes for table `tieuban_phan_bien`
--
ALTER TABLE `tieuban_phan_bien`
  ADD PRIMARY KEY (`idPhanBien`),
  ADD UNIQUE KEY `uq_sp_gv_sk` (`idSanPham`,`idGV`,`idSK`),
  ADD KEY `idx_tpb_idSK` (`idSK`),
  ADD KEY `idx_tpb_idGV` (`idGV`),
  ADD KEY `idx_tpb_idSanPham` (`idSanPham`);

--
-- Indexes for table `tieuchi`
--
ALTER TABLE `tieuchi`
  ADD PRIMARY KEY (`idTieuChi`);

--
-- Indexes for table `toantu`
--
ALTER TABLE `toantu`
  ADD PRIMARY KEY (`idToanTu`);

--
-- Indexes for table `tohop_dieukien`
--
ALTER TABLE `tohop_dieukien`
  ADD PRIMARY KEY (`idDieuKien`),
  ADD KEY `idDieuKienTrai` (`idDieuKienTrai`),
  ADD KEY `idDieuKienPhai` (`idDieuKienPhai`),
  ADD KEY `idToanTu` (`idToanTu`);

--
-- Indexes for table `vaitro`
--
ALTER TABLE `vaitro`
  ADD PRIMARY KEY (`idVaiTro`),
  ADD UNIQUE KEY `uq_vaitro_maVaiTro` (`maVaiTro`);

--
-- Indexes for table `vaitro_quyen`
--
ALTER TABLE `vaitro_quyen`
  ADD PRIMARY KEY (`idVaiTro`,`idQuyen`),
  ADD KEY `vaitro_quyen_ibfk_2` (`idQuyen`);

--
-- Indexes for table `vaitro_sukien`
--
ALTER TABLE `vaitro_sukien`
  ADD PRIMARY KEY (`idSK`,`idVaiTro`),
  ADD KEY `idSK` (`idSK`),
  ADD KEY `vaitro_sukien_ibfk_vaitro` (`idVaiTro`);

--
-- Indexes for table `vongthi`
--
ALTER TABLE `vongthi`
  ADD PRIMARY KEY (`idVongThi`),
  ADD KEY `idSK` (`idSK`);

--
-- Indexes for table `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  ADD PRIMARY KEY (`idXacNhan`),
  ADD KEY `idLichTrinh` (`idLichTrinh`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`);

--
-- Indexes for table `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  ADD PRIMARY KEY (`idYeuCau`),
  ADD KEY `idNhom` (`idNhom`),
  ADD KEY `idTK` (`idTK`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `idLog` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `botieuchi`
--
ALTER TABLE `botieuchi`
  MODIFY `idBoTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT for table `canhbaodiem`
--
ALTER TABLE `canhbaodiem`
  MODIFY `idCanhBao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  MODIFY `idCap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  MODIFY `idChamDiem` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `chude`
--
ALTER TABLE `chude`
  MODIFY `idChuDe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chude_sukien`
--
ALTER TABLE `chude_sukien`
  MODIFY `idChuDeSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT for table `chungnhan`
--
ALTER TABLE `chungnhan`
  MODIFY `idChungNhan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diemdanh`
--
ALTER TABLE `diemdanh`
  MODIFY `idDiemDanh` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dieukien`
--
ALTER TABLE `dieukien`
  MODIFY `idDieuKien` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `form_field`
--
ALTER TABLE `form_field`
  MODIFY `idField` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giaithuong`
--
ALTER TABLE `giaithuong`
  MODIFY `idGiaiThuong` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giangvien`
--
ALTER TABLE `giangvien`
  MODIFY `idGV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=904;

--
-- AUTO_INCREMENT for table `ketqua`
--
ALTER TABLE `ketqua`
  MODIFY `idKetQua` tinyint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `khoa`
--
ALTER TABLE `khoa`
  MODIFY `idKhoa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lichtrinh`
--
ALTER TABLE `lichtrinh`
  MODIFY `idLichTrinh` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loaicap`
--
ALTER TABLE `loaicap`
  MODIFY `idLoaiCap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loaitaikhoan`
--
ALTER TABLE `loaitaikhoan`
  MODIFY `idLoaiTK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lop`
--
ALTER TABLE `lop`
  MODIFY `idLop` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nhom`
--
ALTER TABLE `nhom`
  MODIFY `idNhom` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=993;

--
-- AUTO_INCREMENT for table `nienkhoa`
--
ALTER TABLE `nienkhoa`
  MODIFY `idNienKhoa` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phancongcham`
--
ALTER TABLE `phancongcham`
  MODIFY `idPhanCongCham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1003;

--
-- AUTO_INCREMENT for table `phien_diemdanh`
--
ALTER TABLE `phien_diemdanh`
  MODIFY `idPhienDD` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quyche`
--
ALTER TABLE `quyche`
  MODIFY `idQuyChe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quyen`
--
ALTER TABLE `quyen`
  MODIFY `idQuyen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `idSanPham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=993;

--
-- AUTO_INCREMENT for table `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `idSV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=903;

--
-- AUTO_INCREMENT for table `sukien`
--
ALTER TABLE `sukien`
  MODIFY `idSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1001;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `idTK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=906;

--
-- AUTO_INCREMENT for table `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `idThongBao` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  MODIFY `idthongtin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=993;

--
-- AUTO_INCREMENT for table `thuoctinh_kiemtra`
--
ALTER TABLE `thuoctinh_kiemtra`
  MODIFY `idThuocTinhKiemTra` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tieuban`
--
ALTER TABLE `tieuban`
  MODIFY `idTieuBan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=803;

--
-- AUTO_INCREMENT for table `tieuban_phan_bien`
--
ALTER TABLE `tieuban_phan_bien`
  MODIFY `idPhanBien` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tieuchi`
--
ALTER TABLE `tieuchi`
  MODIFY `idTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=994;

--
-- AUTO_INCREMENT for table `toantu`
--
ALTER TABLE `toantu`
  MODIFY `idToanTu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vaitro`
--
ALTER TABLE `vaitro`
  MODIFY `idVaiTro` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vongthi`
--
ALTER TABLE `vongthi`
  MODIFY `idVongThi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000;

--
-- AUTO_INCREMENT for table `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  MODIFY `idXacNhan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  MODIFY `idYeuCau` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `botieuchi_tieuchi`
--
ALTER TABLE `botieuchi_tieuchi`
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_1` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`),
  ADD CONSTRAINT `botieuchi_tieuchi_ibfk_2` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`);

--
-- Constraints for table `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  ADD CONSTRAINT `cap_tochuc_ibfk_1` FOREIGN KEY (`idLoaiCap`) REFERENCES `loaicap` (`idLoaiCap`);

--
-- Constraints for table `cauhinh_tieuchi_sk`
--
ALTER TABLE `cauhinh_tieuchi_sk`
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`),
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`),
  ADD CONSTRAINT `cauhinh_tieuchi_sk_ibfk_3` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`);

--
-- Constraints for table `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  ADD CONSTRAINT `chamtieuchi_ibfk_1` FOREIGN KEY (`idPhanCongCham`) REFERENCES `phancongcham` (`idPhanCongCham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chamtieuchi_ibfk_2` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chamtieuchi_ibfk_3` FOREIGN KEY (`idTieuChi`) REFERENCES `tieuchi` (`idTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `chude`
--
ALTER TABLE `chude`
  ADD CONSTRAINT `chude_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chude_ibfk_nguoitao` FOREIGN KEY (`idNguoiTao`) REFERENCES `taikhoan` (`idTK`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `chude_sukien`
--
ALTER TABLE `chude_sukien`
  ADD CONSTRAINT `chude_sukien_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `chude_sukien_ibfk_2` FOREIGN KEY (`idchude`) REFERENCES `chude` (`idChuDe`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `chungnhan`
--
ALTER TABLE `chungnhan`
  ADD CONSTRAINT `chungnhan_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_2` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `chungnhan_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `diemdanh`
--
ALTER TABLE `diemdanh`
  ADD CONSTRAINT `diemdanh_ibfk_1` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `diemdanh_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `diemdanh_ibfk_3` FOREIGN KEY (`idPhienDD`) REFERENCES `phien_diemdanh` (`idPhienDD`) ON DELETE SET NULL ON UPDATE RESTRICT;

--
-- Constraints for table `dieukien_don`
--
ALTER TABLE `dieukien_don`
  ADD CONSTRAINT `dieukien_don_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `dieukien_don_ibfk_2` FOREIGN KEY (`idThuocTinhKiemTra`) REFERENCES `thuoctinh_kiemtra` (`idThuocTinhKiemTra`),
  ADD CONSTRAINT `dieukien_don_ibfk_3` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`);

--
-- Constraints for table `form_field`
--
ALTER TABLE `form_field`
  ADD CONSTRAINT `ff_fk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `ff_fk_vongthi` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE CASCADE;

--
-- Constraints for table `giaithuong`
--
ALTER TABLE `giaithuong`
  ADD CONSTRAINT `giaithuong_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `giangvien`
--
ALTER TABLE `giangvien`
  ADD CONSTRAINT `giangvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `giangvien_ibfk_2` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Constraints for table `ketqua`
--
ALTER TABLE `ketqua`
  ADD CONSTRAINT `ketqua_ibfk_1` FOREIGN KEY (`idGiaiThuong`) REFERENCES `giaithuong` (`idGiaiThuong`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ketqua_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ketqua_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `lichtrinh`
--
ALTER TABLE `lichtrinh`
  ADD CONSTRAINT `lichtrinh_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichtrinh_ibfk_2` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`),
  ADD CONSTRAINT `lichtrinh_ibfk_tieuban` FOREIGN KEY (`idTieuBan`) REFERENCES `tieuban` (`idTieuBan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `lop`
--
ALTER TABLE `lop`
  ADD CONSTRAINT `lop_ibfk_1` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Constraints for table `nhom`
--
ALTER TABLE `nhom`
  ADD CONSTRAINT `nhom_fk_chunhom` FOREIGN KEY (`idChuNhom`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT,
  ADD CONSTRAINT `nhom_fk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT,
  ADD CONSTRAINT `nhom_fk_truongnhom` FOREIGN KEY (`idTruongNhom`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT;

--
-- Constraints for table `nhom_gvhd`
--
ALTER TABLE `nhom_gvhd`
  ADD CONSTRAINT `gvhd_fk_nhom` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE CASCADE,
  ADD CONSTRAINT `gvhd_fk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT;

--
-- Constraints for table `phancongcham`
--
ALTER TABLE `phancongcham`
  ADD CONSTRAINT `phancongcham_ibfk_1` FOREIGN KEY (`idGV`) REFERENCES `giangvien` (`idGV`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_2` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_3` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `phancongcham_ibfk_4` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `phien_diemdanh`
--
ALTER TABLE `phien_diemdanh`
  ADD CONSTRAINT `phiendd_ibfk_lichtrinh` FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quyche`
--
ALTER TABLE `quyche`
  ADD CONSTRAINT `fk_quyche_sukien` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE;

--
-- Constraints for table `quyche_dieukien`
--
ALTER TABLE `quyche_dieukien`
  ADD CONSTRAINT `quyche_dieukien_ibfk_1` FOREIGN KEY (`idQuyChe`) REFERENCES `quyche` (`idQuyChe`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `quyche_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienCuoi`) REFERENCES `dieukien` (`idDieuKien`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Constraints for table `quyche_ngucanh_apdung`
--
ALTER TABLE `quyche_ngucanh_apdung`
  ADD CONSTRAINT `fk_quyche_ngucanh_danhmuc` FOREIGN KEY (`maNguCanh`) REFERENCES `quyche_danhmuc_ngucanh` (`maNguCanh`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_quyche_ngucanh_quyche` FOREIGN KEY (`idQuyChe`) REFERENCES `quyche` (`idQuyChe`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `sp_fk_chude` FOREIGN KEY (`idChuDeSK`) REFERENCES `chude_sukien` (`idChuDeSK`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_fk_nhom` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT,
  ADD CONSTRAINT `sp_fk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT;

--
-- Constraints for table `sanpham_field_value`
--
ALTER TABLE `sanpham_field_value`
  ADD CONSTRAINT `sfv_fk_field` FOREIGN KEY (`idField`) REFERENCES `form_field` (`idField`) ON DELETE RESTRICT,
  ADD CONSTRAINT `sfv_fk_sanpham` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE CASCADE,
  ADD CONSTRAINT `sfv_fk_vongthi` FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE RESTRICT;

--
-- Constraints for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `sinhvien_ibfk_2` FOREIGN KEY (`idLop`) REFERENCES `lop` (`idLop`),
  ADD CONSTRAINT `sinhvien_ibfk_3` FOREIGN KEY (`idKhoa`) REFERENCES `khoa` (`idKhoa`);

--
-- Constraints for table `sukien`
--
ALTER TABLE `sukien`
  ADD CONSTRAINT `sukien_ibfk_1` FOREIGN KEY (`idCap`) REFERENCES `cap_tochuc` (`idCap`),
  ADD CONSTRAINT `sukien_ibfk_2` FOREIGN KEY (`nguoiTao`) REFERENCES `taikhoan` (`idTK`);

--
-- Constraints for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `taikhoan_ibfk_1` FOREIGN KEY (`idLoaiTK`) REFERENCES `loaitaikhoan` (`idLoaiTK`);

--
-- Constraints for table `taikhoan_quyen`
--
ALTER TABLE `taikhoan_quyen`
  ADD CONSTRAINT `taikhoan_quyen_ibfk_1` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE,
  ADD CONSTRAINT `taikhoan_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE;

--
-- Constraints for table `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  ADD CONSTRAINT `tvs_ibfk_nguoicap` FOREIGN KEY (`idNguoiCap`) REFERENCES `taikhoan` (`idTK`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tvs_ibfk_vaitro` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `thanhviennhom`
--
ALTER TABLE `thanhviennhom`
  ADD CONSTRAINT `thanhvien_fk_nhom` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE CASCADE,
  ADD CONSTRAINT `thanhvien_fk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT;

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_nguoigui` FOREIGN KEY (`nguoiGui`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `thongbao_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `thongbao_ca_nhan`
--
ALTER TABLE `thongbao_ca_nhan`
  ADD CONSTRAINT `tbcn_ibfk_thongbao` FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbcn_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thongbao_da_doc`
--
ALTER TABLE `thongbao_da_doc`
  ADD CONSTRAINT `tbdd_ibfk_thongbao` FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbdd_ibfk_tk` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thongbao_nhom_nhan`
--
ALTER TABLE `thongbao_nhom_nhan`
  ADD CONSTRAINT `tbnn_ibfk_thongbao` FOREIGN KEY (`idThongBao`) REFERENCES `thongbao` (`idThongBao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbnn_ibfk_vaitro` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  ADD CONSTRAINT `thongtinnhom_ibfk_1` FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tieuban`
--
ALTER TABLE `tieuban`
  ADD CONSTRAINT `fk_tieuban_botieuchi` FOREIGN KEY (`idBoTieuChi`) REFERENCES `botieuchi` (`idBoTieuChi`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tieuban_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tieuban_sanpham`
--
ALTER TABLE `tieuban_sanpham`
  ADD CONSTRAINT `tieuban_sanpham_ibfk_2` FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tohop_dieukien`
--
ALTER TABLE `tohop_dieukien`
  ADD CONSTRAINT `tohop_dieukien_ibfk_1` FOREIGN KEY (`idDieuKien`) REFERENCES `dieukien` (`idDieuKien`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `tohop_dieukien_ibfk_2` FOREIGN KEY (`idDieuKienTrai`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `tohop_dieukien_ibfk_3` FOREIGN KEY (`idDieuKienPhai`) REFERENCES `dieukien` (`idDieuKien`),
  ADD CONSTRAINT `tohop_dieukien_ibfk_4` FOREIGN KEY (`idToanTu`) REFERENCES `toantu` (`idToanTu`);

--
-- Constraints for table `vaitro_quyen`
--
ALTER TABLE `vaitro_quyen`
  ADD CONSTRAINT `vaitro_quyen_ibfk_1` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaitro_quyen_ibfk_2` FOREIGN KEY (`idQuyen`) REFERENCES `quyen` (`idQuyen`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vaitro_sukien`
--
ALTER TABLE `vaitro_sukien`
  ADD CONSTRAINT `vaitro_sukien_ibfk_sk` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vaitro_sukien_ibfk_vaitro` FOREIGN KEY (`idVaiTro`) REFERENCES `vaitro` (`idVaiTro`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `vongthi`
--
ALTER TABLE `vongthi`
  ADD CONSTRAINT `vongthi_ibfk_1` FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE;

--
-- Constraints for table `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_1` FOREIGN KEY (`idLichTrinh`) REFERENCES `lichtrinh` (`idLichTrinh`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_2` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_3` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  ADD CONSTRAINT `yeucau_thamgia_ibfk_1` FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `yeucau_thamgia_ibfk_2` FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
