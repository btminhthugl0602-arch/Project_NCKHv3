-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2026 at 07:23 PM
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
(2000, 'Tiêu chí chấm Poster 26/3', 'Thang điểm 10'),
(3001, 'Tiêu chí Thuyết trình Sư phạm', 'Trọng tâm vào kỹ năng truyền đạt và giáo án'),
(3002, 'Tiêu chí Thuyết trình Công nghệ', 'Trọng tâm vào tính đổi mới và ứng dụng'),
(5000, 'Phiếu chấm Sơ khảo NCKH', NULL);

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
(2000, 2001, '1.00', '4.00'),
(2000, 2002, '1.00', '3.00'),
(2000, 2003, '1.00', '3.00'),
(3001, 3001, '1.00', '4.00'),
(3001, 3002, '1.00', '4.00'),
(3001, 3003, '1.00', '2.00'),
(3002, 3004, '1.00', '4.00'),
(3002, 3005, '1.00', '3.00'),
(3002, 3006, '1.00', '3.00'),
(5000, 5001, '1.00', '3.00'),
(5000, 5002, '1.00', '3.00'),
(5000, 5003, '1.00', '4.00');

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

--
-- Dumping data for table `canhbaodiem`
--

INSERT INTO `canhbaodiem` (`idCanhBao`, `idSanPham`, `idVongThi`, `doLech`, `trangThai`, `thoiGian`) VALUES
(2001, 2001, 2001, '5.50', 'Chờ xử lý', '2026-03-14 01:20:50'),
(5001, 5001, 5001, '5.50', 'Chờ xử lý', '2026-03-14 02:18:03');

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
(2000, 2001, 2000),
(5000, 5001, 5000);

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
(1, 2001, 2001, 2001, '4.00', 'Thiết kế xuất sắc', '2026-03-14 01:20:50'),
(2, 2001, 2001, 2002, '3.00', 'Đúng chủ đề', '2026-03-14 01:20:50'),
(3, 2001, 2001, 2003, '2.50', 'Bố cục hài hòa', '2026-03-14 01:20:50'),
(4, 2002, 2001, 2001, '1.50', 'Phối màu xấu', '2026-03-14 01:20:50'),
(5, 2002, 2001, 2002, '1.50', 'Lạc đề', '2026-03-14 01:20:50'),
(6, 2002, 2001, 2003, '1.00', 'Bố cục rời rạc', '2026-03-14 01:20:50'),
(7, 2001, 2002, 2001, '3.50', 'Khá tốt', '2026-03-14 01:20:50'),
(8, 2001, 2002, 2002, '2.50', 'Ổn', '2026-03-14 01:20:50'),
(9, 2001, 2002, 2003, '2.50', 'Ổn', '2026-03-14 01:20:50'),
(10, 2002, 2002, 2001, '3.00', 'Tốt', '2026-03-14 01:20:50'),
(11, 2002, 2002, 2002, '2.50', 'Khá', '2026-03-14 01:20:50'),
(12, 2002, 2002, 2003, '2.50', 'Khá', '2026-03-14 01:20:50'),
(13, 2001, 2003, 2001, '2.00', 'Copy mạng', '2026-03-14 01:20:50'),
(14, 2001, 2003, 2002, '1.50', 'Kém', '2026-03-14 01:20:50'),
(15, 2001, 2003, 2003, '1.00', 'Kém', '2026-03-14 01:20:50'),
(16, 2002, 2003, 2001, '2.00', 'Chưa đầu tư', '2026-03-14 01:20:50'),
(17, 2002, 2003, 2002, '2.00', 'Kém', '2026-03-14 01:20:50'),
(18, 2002, 2003, 2003, '1.00', 'Xấu', '2026-03-14 01:20:50'),
(19, 3001, 3001, 3001, '3.50', 'Truyền đạt tốt', '2026-03-14 01:32:02'),
(20, 3001, 3001, 3002, '3.50', 'Giáo án chuẩn', '2026-03-14 01:32:02'),
(21, 3001, 3001, 3003, '2.00', 'Tương tác mạnh', '2026-03-14 01:32:02'),
(22, 3002, 3001, 3001, '3.00', 'Khá', '2026-03-14 01:32:02'),
(23, 3002, 3001, 3002, '3.50', 'Tốt', '2026-03-14 01:32:02'),
(24, 3002, 3001, 3003, '1.50', 'Hơi run', '2026-03-14 01:32:02'),
(25, 3003, 3002, 3004, '4.00', 'Tính mới rất cao', '2026-03-14 01:32:02'),
(26, 3003, 3002, 3005, '2.50', 'Thuyết trình ổn', '2026-03-14 01:32:02'),
(27, 3003, 3002, 3006, '3.00', 'Demo mượt mà', '2026-03-14 01:32:02'),
(28, 3004, 3002, 3004, '3.50', 'Khá sáng tạo', '2026-03-14 01:32:02'),
(29, 3004, 3002, 3005, '3.00', 'Lưu loát', '2026-03-14 01:32:02'),
(30, 3004, 3002, 3006, '2.50', 'Demo có 1 lỗi nhỏ', '2026-03-14 01:32:02'),
(31, 5001, 5001, 5001, '3.00', 'Đề tài rất đột phá', '2026-03-14 02:18:03'),
(32, 5001, 5001, 5002, '3.00', 'Model chuẩn', '2026-03-14 02:18:03'),
(33, 5001, 5001, 5003, '3.50', 'Kết quả Accuracy 98%', '2026-03-14 02:18:03'),
(34, 5002, 5001, 5001, '1.00', 'Đã có nhiều người làm', '2026-03-14 02:18:03'),
(35, 5002, 5001, 5002, '1.00', 'Chưa rõ phương pháp', '2026-03-14 02:18:03'),
(36, 5002, 5001, 5003, '2.00', 'Bộ data test quá ít', '2026-03-14 02:18:03'),
(37, 5001, 5002, 5001, '2.50', 'Tốt', '2026-03-14 02:18:03'),
(38, 5001, 5002, 5002, '2.50', 'Tốt', '2026-03-14 02:18:03'),
(39, 5001, 5002, 5003, '3.50', 'Tốt', '2026-03-14 02:18:03'),
(40, 5002, 5002, 5001, '2.50', 'Ổn', '2026-03-14 02:18:03'),
(41, 5002, 5002, 5002, '2.50', 'Ổn', '2026-03-14 02:18:03'),
(42, 5002, 5002, 5003, '3.00', 'Khá', '2026-03-14 02:18:03');

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
(2000, 'Tự hào Tuổi trẻ CNTT', 2000, 'Thể hiện tinh thần nhiệt huyết của sinh viên IT', 1, NULL),
(3001, 'Nghiệp vụ Sư phạm Tin học', 3000, 'Dành cho sinh viên ngành Sư phạm', 1, NULL),
(3002, 'Xu hướng Công nghệ mới', 3000, 'Dành cho sinh viên ngành Công nghệ', 1, NULL),
(5001, 'Ứng dụng Trí tuệ nhân tạo (AI)', 5000, 'Các giải pháp AI, Deep Learning', 1, NULL),
(5002, 'Công nghệ phần mềm & Web/App', 5000, 'Hệ thống quản lý, ứng dụng di động', 1, NULL);

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
(2000, 2000, 2000, 'Chủ đề chính', 1),
(3001, 3000, 3001, 'Track Sư phạm', 1),
(3002, 3000, 3002, 'Track Công nghệ', 1),
(5001, 5000, 5001, 'Track AI', 1),
(5002, 5000, 5002, 'Track Software', 1);

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
(2001, 'DON', 'GPA > 3.0', 'Điểm trung bình lớn hơn 3.0'),
(2002, 'DON', 'DRL > 70', 'Điểm rèn luyện lớn hơn 70'),
(2003, 'DON', 'GPA >= 2.5', 'Điểm trung bình từ 2.5 trở lên'),
(2004, 'DON', 'DRL >= 85', 'Điểm rèn luyện từ 85 trở lên'),
(2005, 'TOHOP', 'Nhóm 1: Học lực Giỏi (GPA>3, DRL>70)', 'Điều kiện vế trái'),
(2006, 'TOHOP', 'Nhóm 2: Rèn luyện Tốt (GPA>=2.5, DRL>=85)', 'Điều kiện vế phải'),
(2007, 'TOHOP', 'Điều kiện Tổng: Tham gia sự kiện Poster', 'Hoặc thỏa mãn Nhóm 1, hoặc thỏa mãn Nhóm 2'),
(5001, 'DON', 'GPA >= 3.2', NULL),
(5002, 'DON', 'GPA >= 3.0', NULL),
(5003, 'DON', 'DRL >= 80', NULL),
(5004, 'TOHOP', 'Nhóm 2: GPA >= 3.0 VÀ DRL >= 80', NULL),
(5005, 'TOHOP', 'Điều kiện TỔNG NCKH', NULL);

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
(2001, 1, 2, '3.0'),
(2002, 2, 2, '70'),
(2003, 1, 4, '2.5'),
(2004, 2, 4, '85'),
(5001, 1, 4, '3.2'),
(5002, 1, 4, '3.0'),
(5003, 2, 4, '80');

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

--
-- Dumping data for table `form_field`
--

INSERT INTO `form_field` (`idField`, `idSK`, `idVongThi`, `tenTruong`, `kieuTruong`, `batBuoc`, `thuTu`, `cauHinhJson`, `isActive`) VALUES
(2001, 2000, 2001, 'File Poster (Định dạng PNG/JPG/PDF)', 'FILE', 1, 1, '{\"accept\": \"pdf,jpg,png\", \"maxSizeKB\": 10240}', 1),
(2002, 2000, 2001, 'Tài liệu giới thiệu ý tưởng (Docx)', 'FILE', 1, 2, '{\"accept\": \"docx\", \"maxSizeKB\": 5120}', 1),
(5001, 5000, 5001, 'Báo cáo toàn văn (PDF/Docx)', 'FILE', 1, 1, '{\"accept\": \"pdf,docx\", \"maxSizeKB\": 15360}', 1),
(5002, 5000, 5001, 'Link Source Code (Github/Gitlab)', 'URL', 0, 2, '{\"placeholder\": \"https://github.com/...\"}', 1);

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
(2001, 2, 'ThS. Nguyễn Trọng Tài', 1, NULL, 0),
(2002, 3, 'TS. Trần Mỹ Thuật', 1, NULL, 0),
(2003, 4, 'ThS. Lê Công Tâm', 1, NULL, 0),
(3001, 3001, 'TS. Lê Giáo Dục (Sư phạm)', 1, NULL, 0),
(3002, 3002, 'ThS. Trần Phương Pháp (Sư phạm)', 1, NULL, 0),
(3003, 3003, 'TS. Nguyễn Trí Tuệ (Công nghệ)', 1, NULL, 0),
(3004, 3004, 'ThS. Phạm Phần Mềm (Công nghệ)', 1, NULL, 0);

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

--
-- Dumping data for table `lichtrinh`
--

INSERT INTO `lichtrinh` (`idLichTrinh`, `idSK`, `idVongThi`, `idTieuBan`, `tenHoatDong`, `loaiHoatDong`, `thuTu`, `thoiGianBatDau`, `thoiGianKetThuc`, `diaDiem`, `viTriLat`, `viTriLng`) VALUES
(2001, 2000, 2002, 2001, 'Bảo vệ Poster và Trao giải', 'HOAT_DONG', 0, '2026-03-24 08:00:00', '2026-03-24 11:30:00', 'Hội trường 401K', NULL, NULL);

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
(2001, 2000, 5, 5, 'TEAM_POSTER_01', '2026-03-14 01:19:12', 1),
(2002, 2000, 7, 7, 'TEAM_POSTER_02', '2026-03-14 01:19:12', 1),
(2003, 2000, 9, 9, 'TEAM_POSTER_03', '2026-03-14 01:19:12', 1),
(3001, 3000, 3005, 3005, 'TEAM_SP', '2026-03-14 01:32:01', 1),
(3002, 3000, 3007, 3007, 'TEAM_CN', '2026-03-14 01:32:01', 1),
(5001, 5000, 5, 5, 'NCKH_AI_01', '2026-03-14 02:18:03', 1),
(5002, 5000, 7, 7, 'NCKH_WEB_01', '2026-03-14 02:18:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `nhom_gvhd`
--

CREATE TABLE `nhom_gvhd` (
  `idNhom` int NOT NULL,
  `idTK` int NOT NULL COMMENT 'Tài khoản GV_HUONG_DAN',
  `ngayThamGia` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày GV chính thức vào nhóm (sau khi chấp nhận lời mời)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='GVHD của nhóm. Tách riêng vì:\n           - GVHD không tính vào soThanhVienToiDa\n           - Một nhóm có thể có 0..N GVHD (tùy cấu hình SK)\n           - GVHD có thể chủ động rút khỏi nhóm\n           - GV là Chủ nhóm cũng có bản ghi ở đây\n           - Khi GVHD accept lời mời → INSERT ở đây\n             + INSERT taikhoan_vaitro_sukien (nguonTao=QUA_NHOM)';

--
-- Dumping data for table `nhom_gvhd`
--

INSERT INTO `nhom_gvhd` (`idNhom`, `idTK`, `ngayThamGia`) VALUES
(5001, 2, '2026-03-14 02:18:03'),
(5002, 4, '2026-03-14 02:18:03');

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
(2001, 2001, 2000, 2001, 2000, 'Đang chấm', '2026-03-12 08:00:00', 1),
(2002, 2002, 2000, 2001, 2000, 'Đang chấm', '2026-03-12 08:05:00', 1),
(3001, 3001, 3000, 3001, 3001, 'Đang chấm', '2026-04-15 08:00:00', 1),
(3002, 3002, 3000, 3001, 3001, 'Đang chấm', '2026-04-15 08:00:00', 1),
(3003, 3003, 3000, 3001, 3002, 'Đang chấm', '2026-04-15 08:00:00', 1),
(3004, 3004, 3000, 3001, 3002, 'Đang chấm', '2026-04-15 08:00:00', 1),
(5001, 3001, 5000, 5001, 5000, 'Đang chấm', '2026-05-26 08:00:00', 1),
(5002, 3002, 5000, 5001, 5000, 'Đang chấm', '2026-05-26 08:00:00', 1);

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
(2001, 2001, 2001, 0, 'Đã nộp', NULL),
(2001, 2002, 2001, 0, 'Đã nộp', NULL),
(2002, 2001, 2001, 0, 'Đã nộp', NULL),
(2002, 2002, 2001, 0, 'Đã nộp', NULL),
(2003, 2001, 2001, 0, 'Đã nộp', NULL),
(2003, 2002, 2001, 0, 'Đã nộp', NULL),
(3001, 3001, 3001, 0, 'Đã nộp', NULL),
(3001, 3002, 3001, 0, 'Đã nộp', NULL),
(3002, 3003, 3001, 0, 'Đã nộp', NULL),
(3002, 3004, 3001, 0, 'Đã nộp', NULL),
(5001, 3001, 5001, 0, 'Đã nộp', NULL),
(5001, 3002, 5001, 0, 'Đã nộp', NULL),
(5002, 3001, 5001, 0, 'Đã nộp', NULL),
(5002, 3002, 5001, 0, 'Đã nộp', NULL);

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
(2000, 'Quy chế tham gia Vẽ Poster 26/3', 'Kiểm tra GPA và ĐRL của sinh viên đăng ký', 'THAMGIA', 2000),
(5000, 'Quy chế tham gia NCKH', NULL, 'THAMGIA', 5000);

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
(2000, 2007),
(5000, 5005);

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
(2000, 'DANG_KY_THAM_GIA_SV', '2026-03-14 01:50:27'),
(5000, 'DANG_KY_THAM_GIA_SV', '2026-03-14 02:18:03');

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
(2001, 2001, 2000, 2000, 'Poster: IT Khát vọng vươn xa', 'DA_DUYET', '2026-03-14 01:19:12', '2026-03-14 01:19:12'),
(2002, 2002, 2000, 2000, 'Poster: Dòng code xanh', 'DA_DUYET', '2026-03-14 01:19:12', '2026-03-14 01:19:12'),
(2003, 2003, 2000, 2000, 'Poster: Kỷ nguyên AI', 'DA_DUYET', '2026-03-14 01:19:12', '2026-03-14 01:19:12'),
(3001, 3001, 3000, 3001, 'Bài giảng: Cấu trúc lặp trong Python', 'DA_DUYET', '2026-03-14 01:32:01', '2026-03-14 01:32:01'),
(3002, 3002, 3000, 3002, 'Ứng dụng AI nhận diện giọng nói', 'DA_DUYET', '2026-03-14 01:32:01', '2026-03-14 01:32:01'),
(5001, 5001, 5000, 5001, 'Nhận diện ung thư qua ảnh X-Quang', 'DA_DUYET', '2026-03-14 02:18:03', '2026-03-14 02:18:03'),
(5002, 5002, 5000, 5002, 'Hệ thống điểm danh bằng Blockchain', 'DA_DUYET', '2026-03-14 02:18:03', '2026-03-14 02:18:03');

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

--
-- Dumping data for table `sanpham_field_value`
--

INSERT INTO `sanpham_field_value` (`idSanPham`, `idVongThi`, `idField`, `giaTriText`, `duongDanFile`, `ngayNop`) VALUES
(2001, 2001, 2001, NULL, '/uploads/sk2000/nhom2001/poster1.png', '2026-03-14 01:19:12'),
(2001, 2001, 2002, NULL, '/uploads/sk2000/nhom2001/gioithieu1.docx', '2026-03-14 01:19:12'),
(2002, 2001, 2001, NULL, '/uploads/sk2000/nhom2002/poster2.jpg', '2026-03-14 01:19:12'),
(2002, 2001, 2002, NULL, '/uploads/sk2000/nhom2002/gioithieu2.docx', '2026-03-14 01:19:12'),
(2003, 2001, 2001, NULL, '/uploads/sk2000/nhom2003/poster3.pdf', '2026-03-14 01:19:12'),
(2003, 2001, 2002, NULL, '/uploads/sk2000/nhom2003/gioithieu3.docx', '2026-03-14 01:19:12'),
(5001, 5001, 5001, NULL, '/nckh/baocao_y_te.pdf', '2026-03-14 02:18:03'),
(5001, 5001, 5002, 'https://github.com/ai-med', NULL, '2026-03-14 02:18:03'),
(5002, 5001, 5001, NULL, '/nckh/bc_blockchain.pdf', '2026-03-14 02:18:03'),
(5002, 5001, 5002, 'https://github.com/block-dd', NULL, '2026-03-14 02:18:03');

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
(2001, 2001, '6.75', NULL, 'Đã phân công', '2026-03-14 01:19:12'),
(2001, 2002, NULL, NULL, 'Chờ báo cáo', '2026-03-14 01:20:50'),
(2002, 2001, '8.25', NULL, 'Đã phân công', '2026-03-14 01:19:12'),
(2002, 2002, NULL, NULL, 'Chờ báo cáo', '2026-03-14 01:20:50'),
(2003, 2001, '4.75', NULL, 'Bị loại', '2026-03-14 01:19:12'),
(5001, 5001, NULL, NULL, 'Đã phân công', '2026-03-14 02:18:03'),
(5002, 5001, '8.25', NULL, 'Đã phân công', '2026-03-14 02:18:03'),
(5002, 5002, NULL, NULL, 'Chờ báo cáo', '2026-03-14 02:18:03');

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
(2001, 5, 'Trưởng Đội 1 (Design)', 'SV2001', '3.50', 75, 1, 1),
(2002, 6, 'Thành viên Đội 1', 'SV2002', '3.20', 0, 1, 1),
(2003, 7, 'Trưởng Đội 2 (Sáng tạo)', 'SV2003', '3.80', 0, 1, 1),
(2004, 8, 'Thành viên Đội 2', 'SV2004', '3.00', 85, 1, 1),
(2005, 9, 'Trưởng Đội 3 (Test trượt)', 'SV2005', '2.50', 90, 1, 1),
(2006, 10, 'Thành viên Đội 3', 'SV2006', '2.80', 65, 1, 1),
(2011, 2011, 'Sinh viên 1 (Pass)', 'SV2011', '3.20', 80, 1, 1),
(2012, 2012, 'Sinh viên 2 (Fail)', 'SV2012', '2.40', 60, 1, 1),
(3001, 3005, 'SV Sư Phạm 1', 'SV3001', '3.60', 0, NULL, 1),
(3002, 3006, 'SV Sư Phạm 2', 'SV3002', '3.50', 0, NULL, 1),
(3003, 3007, 'SV Công Nghệ 1', 'SV3003', '3.70', 0, NULL, 1),
(3004, 3008, 'SV Công Nghệ 2', 'SV3004', '3.40', 0, NULL, 1);

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
  `soNhomToiDaSV` int NOT NULL DEFAULT '1' COMMENT 'So doi toi da moi sinh vien duoc tham gia trong su kien',
  `soNhomToiDaGVHD` int DEFAULT NULL COMMENT 'Số nhóm tối đa 1 GVHD hướng dẫn. NULL = không giới hạn',
  `soThanhVienToiThieu` int NOT NULL DEFAULT '1' COMMENT 'Số SV tối thiểu trong nhóm',
  `soThanhVienToiDa` int NOT NULL DEFAULT '5' COMMENT 'Số SV tối đa trong nhóm (không tính GVHD)',
  `soGVHDToiDa` int DEFAULT NULL COMMENT 'Số GVHD tối đa/nhóm. NULL = không giới hạn',
  `yeuCauCoGVHD` tinyint NOT NULL DEFAULT '0' COMMENT 'Bắt buộc có GVHD mới được nộp bài: 0=không, 1=có',
  `choPhepGVTaoNhom` tinyint NOT NULL DEFAULT '1' COMMENT 'Cho phép GV tạo nhóm: 0=không, 1=có',
  `coGVHDTheoSuKien` tinyint NOT NULL DEFAULT '1' COMMENT 'Bat/tat luong GVHD theo su kien: 0=khong, 1=co',
  `isDeleted` tinyint NOT NULL DEFAULT '0' COMMENT 'Xóa mềm. Tách biệt hoàn toàn với isActive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sukien`
--

INSERT INTO `sukien` (`idSK`, `tenSK`, `moTa`, `idCap`, `nguoiTao`, `ngayMoDangKy`, `ngayDongDangKy`, `ngayBatDau`, `ngayKetThuc`, `isActive`, `soNhomToiDaSV`, `soNhomToiDaGVHD`, `soThanhVienToiThieu`, `soThanhVienToiDa`, `soGVHDToiDa`, `yeuCauCoGVHD`, `choPhepGVTaoNhom`, `coGVHDTheoSuKien`, `isDeleted`) VALUES
(2000, 'Cuộc thi Thiết kế Poster chào mừng 26/3 Khoa CNTT', 'Sự kiện thiết kế đồ họa dành cho sinh viên CNTT.', 1, 2000, '2026-03-13 00:00:00', '2026-03-17 00:00:00', '2026-03-11 00:00:00', '2026-03-26 00:00:00', 1, 1, NULL, 1, 3, NULL, 0, 0, 0, 0),
(3000, 'Cuộc thi Thuyết trình Sinh viên Khoa CNTT 2026', 'Chia làm 2 mảng: Nghiệp vụ Sư phạm và Công nghệ ứng dụng', 1, 2000, '2026-04-01 00:00:00', '2026-04-10 00:00:00', '2026-04-15 00:00:00', '2026-04-20 00:00:00', 1, 1, NULL, 1, 5, NULL, 0, 1, 1, 0),
(5000, 'Hội nghị NCKH Sinh viên Khoa CNTT 2026', 'Sự kiện học thuật trọng điểm. Yêu cầu bắt buộc có GVHD.', 1, 2000, '2026-05-01 00:00:00', '2026-05-15 00:00:00', '2026-05-16 00:00:00', '2026-06-30 00:00:00', 1, 1, NULL, 1, 5, NULL, 1, 1, 1, 0);

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
(2, 'gv_cham1', '123456', 2, 1, '2026-03-14 01:19:12'),
(3, 'gv_cham2', '123456', 2, 1, '2026-03-14 01:19:12'),
(4, 'gv_cham3', '123456', 2, 1, '2026-03-14 01:19:12'),
(5, 'sv_doi1_truong', '123456', 3, 1, '2026-03-14 01:19:12'),
(6, 'sv_doi1_tv', '123456', 3, 1, '2026-03-14 01:19:12'),
(7, 'sv_doi2_truong', '123456', 3, 1, '2026-03-14 01:19:12'),
(8, 'sv_doi2_tv', '123456', 3, 1, '2026-03-14 01:19:12'),
(9, 'sv_doi3_truong', '123456', 3, 1, '2026-03-14 01:19:12'),
(10, 'sv_doi3_tv', '123456', 3, 1, '2026-03-14 01:19:12'),
(2000, 'btc_khoa', '123456', 1, 1, '2026-03-14 01:22:27'),
(2011, 'sv1', '123456', 3, 1, '2026-03-14 01:57:04'),
(2012, 'sv2', '123456', 3, 1, '2026-03-14 01:57:04'),
(3000, 'btc_thuyettrinh', '123456', 1, 1, '2026-03-14 01:32:01'),
(3001, 'gv_supham1', '123456', 2, 1, '2026-03-14 01:32:01'),
(3002, 'gv_supham2', '123456', 2, 1, '2026-03-14 01:32:01'),
(3003, 'gv_congnghe1', '123456', 2, 1, '2026-03-14 01:32:01'),
(3004, 'gv_congnghe2', '123456', 2, 1, '2026-03-14 01:32:01'),
(3005, 'sv_sp_truong', '123456', 3, 1, '2026-03-14 01:32:01'),
(3006, 'sv_sp_tv', '123456', 3, 1, '2026-03-14 01:32:01'),
(3007, 'sv_cn_truong', '123456', 3, 1, '2026-03-14 01:32:01'),
(3008, 'sv_cn_tv', '123456', 3, 1, '2026-03-14 01:32:01');

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
(1, 38, 1, '2026-02-23 15:18:35', NULL);

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
(2, 5, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(3, 6, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(4, 7, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(5, 8, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(6, 9, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(7, 10, 2000, 4, 'DANG_KY', NULL, '2026-03-14 01:19:12', 1),
(8, 2, 2000, 5, 'BTC_THEM', NULL, '2026-03-14 01:19:12', 1),
(9, 3, 2000, 5, 'BTC_THEM', NULL, '2026-03-14 01:19:12', 1),
(10, 2000, 2000, 1, 'BTC_THEM', NULL, '2026-03-14 01:22:27', 1),
(11, 2000, 3000, 1, 'BTC_THEM', NULL, '2026-03-14 01:32:01', 1),
(12, 3001, 3000, 6, 'BTC_THEM', NULL, '2026-03-14 01:32:01', 1),
(13, 3002, 3000, 6, 'BTC_THEM', NULL, '2026-03-14 01:32:01', 1),
(14, 3003, 3000, 6, 'BTC_THEM', NULL, '2026-03-14 01:32:01', 1),
(15, 3004, 3000, 6, 'BTC_THEM', NULL, '2026-03-14 01:32:01', 1),
(21, 2000, 5000, 1, 'BTC_THEM', NULL, '2026-03-14 02:18:02', 1),
(22, 5, 5000, 4, 'DANG_KY', NULL, '2026-03-14 02:18:03', 1),
(23, 6, 5000, 4, 'DANG_KY', NULL, '2026-03-14 02:18:03', 1),
(24, 7, 5000, 4, 'DANG_KY', NULL, '2026-03-14 02:18:03', 1),
(25, 8, 5000, 4, 'DANG_KY', NULL, '2026-03-14 02:18:03', 1),
(26, 2, 5000, 3, 'QUA_NHOM', NULL, '2026-03-14 02:18:03', 1),
(27, 4, 5000, 3, 'QUA_NHOM', NULL, '2026-03-14 02:18:03', 1),
(28, 3001, 5000, 5, 'BTC_THEM', NULL, '2026-03-14 02:18:03', 1),
(29, 3002, 5000, 5, 'BTC_THEM', NULL, '2026-03-14 02:18:03', 1);

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
(2001, 5, '2026-03-14 01:19:12'),
(2001, 6, '2026-03-14 01:19:12'),
(2002, 7, '2026-03-14 01:19:12'),
(2002, 8, '2026-03-14 01:19:12'),
(2003, 9, '2026-03-14 01:19:12'),
(2003, 10, '2026-03-14 01:19:12'),
(5001, 5, '2026-03-14 02:18:03'),
(5001, 6, '2026-03-14 02:18:03'),
(5002, 7, '2026-03-14 02:18:03'),
(5002, 8, '2026-03-14 02:18:03');

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
(2001, 2001, 'Đội Mắt Biếc', NULL, 0),
(2002, 2002, 'IT Creative', NULL, 0),
(2003, 2003, 'Đội Try Hard', NULL, 0),
(3001, 3001, 'Sư phạm Tương lai', NULL, 1),
(3002, 3002, 'AI Tech Makers', NULL, 1),
(5001, 5001, 'Lab AI Vision', NULL, 1),
(5002, 5002, 'Dev Team', NULL, 1);

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
(2001, 2000, 2002, NULL, 'Tiểu ban Đánh giá Poster Offline', NULL, 1, '2026-03-24', 'Hội trường 401K'),
(3001, 3000, 3001, 3001, 'Hội đồng Sư phạm', NULL, 1, NULL, 'Phòng 301'),
(3002, 3000, 3001, 3002, 'Hội đồng Công nghệ', NULL, 1, NULL, 'Phòng 302'),
(5001, 5000, 5002, NULL, 'Hội đồng Phản biện NCKH - Phần mềm', NULL, 1, NULL, 'Phòng Hội Thảo 401C');

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
(2001, 2001, 'Trưởng tiểu ban'),
(2001, 2002, 'Thành viên'),
(2001, 2003, 'Thành viên'),
(3001, 3001, 'Trưởng tiểu ban'),
(3001, 3002, 'Thành viên'),
(3002, 3003, 'Trưởng tiểu ban'),
(3002, 3004, 'Thành viên'),
(5001, 3003, 'Trưởng tiểu ban'),
(5001, 3004, 'Thành viên');

-- --------------------------------------------------------

--
-- Table structure for table `tieuban_phan_bien`
--

CREATE TABLE `tieuban_phan_bien` (
  `idPhanBien` int NOT NULL,
  `idSanPham` int NOT NULL COMMENT 'Bài báo cáo được phân công phản biện',
  `idGV` int NOT NULL COMMENT 'Giảng viên phản biện (phải trong tiểu ban chứa bài)',
  `idSK` int NOT NULL COMMENT 'Sự kiện',
  `trangThaiCham` enum('Chờ chấm','Đang chấm','Đã nộp') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chờ chấm',
  `ngayPhanCong` datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayNop` datetime DEFAULT NULL COMMENT 'Thời điểm GV nộp phiếu chấm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phân công phản biện offline trong tiểu ban. Tách biệt với phancong_doclap (online).';

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
(2001, 2001),
(2001, 2002),
(3001, 3001),
(3002, 3002),
(5001, 5002);

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
(2001, 'Tính sáng tạo và thẩm mỹ (4đ)'),
(2002, 'Nội dung bám sát chủ đề (3đ)'),
(2003, 'Chất lượng kỹ thuật (Bố cục, màu sắc) (3đ)'),
(3001, 'Kỹ năng sư phạm & truyền đạt (4đ)'),
(3002, 'Nội dung bài giảng/Giáo án (4đ)'),
(3003, 'Tương tác với người học (2đ)'),
(3004, 'Tính đổi mới & Sáng tạo công nghệ (4đ)'),
(3005, 'Kỹ năng thuyết trình (3đ)'),
(3006, 'Mức độ hoàn thiện Demo (3đ)'),
(5001, 'Tính mới và cấp thiết (3đ)'),
(5002, 'Phương pháp nghiên cứu (3đ)'),
(5003, 'Kết quả đạt được & Thực nghiệm (4đ)');

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
(2005, 2001, 2002, 6),
(2006, 2003, 2004, 6),
(2007, 2005, 2006, 7),
(5004, 5002, 5003, 6),
(5005, 5001, 5004, 7);

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
(2000, 1),
(2000, 4),
(2000, 5),
(2000, 6);

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
(2001, 2000, 'Vòng Sản Phẩm (Sơ khảo)', 'Nộp Poster và file giới thiệu', 1, '2026-03-11 00:00:00', '2026-03-18 00:00:00', '2026-03-18 23:59:59', 0),
(2002, 2000, 'Vòng Chung Kết (Trực tiếp)', 'Trình bày ý tưởng tại Hội trường 401K', 2, '2026-03-22 00:00:00', '2026-03-26 00:00:00', NULL, 0),
(3001, 3000, 'Vòng Chung khảo Thuyết trình', NULL, 1, NULL, NULL, '2026-04-14 23:59:59', 0),
(5001, 5000, 'Vòng Sơ Khảo (Chấm Báo cáo)', NULL, 1, NULL, NULL, '2026-05-25 23:59:59', 0),
(5002, 5000, 'Vòng Chung Khảo (Hội đồng)', NULL, 2, NULL, NULL, NULL, 0);

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
-- Indexes for table `tieuban_phan_bien`
--
ALTER TABLE `tieuban_phan_bien`
  ADD PRIMARY KEY (`idPhanBien`),
  ADD UNIQUE KEY `uq_sp_gv_sk` (`idSanPham`,`idGV`,`idSK`),
  ADD KEY `idx_tpb_idSK` (`idSK`),
  ADD KEY `idx_tpb_idGV` (`idGV`),
  ADD KEY `idx_tpb_idSanPham` (`idSanPham`);

--
-- Indexes for table `tieuban_sanpham`
--
ALTER TABLE `tieuban_sanpham`
  ADD PRIMARY KEY (`idTieuBan`,`idSanPham`),
  ADD KEY `tieuban_sanpham_ibfk_2` (`idSanPham`);

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
  MODIFY `idBoTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5001;

--
-- AUTO_INCREMENT for table `canhbaodiem`
--
ALTER TABLE `canhbaodiem`
  MODIFY `idCanhBao` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5002;

--
-- AUTO_INCREMENT for table `cap_tochuc`
--
ALTER TABLE `cap_tochuc`
  MODIFY `idCap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chamtieuchi`
--
ALTER TABLE `chamtieuchi`
  MODIFY `idChamDiem` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `chude`
--
ALTER TABLE `chude`
  MODIFY `idChuDe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `chude_sukien`
--
ALTER TABLE `chude_sukien`
  MODIFY `idChuDeSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

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
  MODIFY `idDieuKien` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5006;

--
-- AUTO_INCREMENT for table `form_field`
--
ALTER TABLE `form_field`
  MODIFY `idField` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `giaithuong`
--
ALTER TABLE `giaithuong`
  MODIFY `idGiaiThuong` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giangvien`
--
ALTER TABLE `giangvien`
  MODIFY `idGV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3005;

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
  MODIFY `idLichTrinh` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2002;

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
  MODIFY `idNhom` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `nienkhoa`
--
ALTER TABLE `nienkhoa`
  MODIFY `idNienKhoa` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phancongcham`
--
ALTER TABLE `phancongcham`
  MODIFY `idPhanCongCham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `phien_diemdanh`
--
ALTER TABLE `phien_diemdanh`
  MODIFY `idPhienDD` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quyche`
--
ALTER TABLE `quyche`
  MODIFY `idQuyChe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5001;

--
-- AUTO_INCREMENT for table `quyen`
--
ALTER TABLE `quyen`
  MODIFY `idQuyen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `idSanPham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `idSV` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3005;

--
-- AUTO_INCREMENT for table `sukien`
--
ALTER TABLE `sukien`
  MODIFY `idSK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5001;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `idTK` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3009;

--
-- AUTO_INCREMENT for table `taikhoan_vaitro_sukien`
--
ALTER TABLE `taikhoan_vaitro_sukien`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `idThongBao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thongtinnhom`
--
ALTER TABLE `thongtinnhom`
  MODIFY `idthongtin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `thuoctinh_kiemtra`
--
ALTER TABLE `thuoctinh_kiemtra`
  MODIFY `idThuocTinhKiemTra` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tieuban`
--
ALTER TABLE `tieuban`
  MODIFY `idTieuBan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5002;

--
-- AUTO_INCREMENT for table `tieuban_phan_bien`
--
ALTER TABLE `tieuban_phan_bien`
  MODIFY `idPhanBien` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tieuchi`
--
ALTER TABLE `tieuchi`
  MODIFY `idTieuChi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5004;

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
  MODIFY `idVongThi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5003;

--
-- AUTO_INCREMENT for table `xacnhan_thamgia`
--
ALTER TABLE `xacnhan_thamgia`
  MODIFY `idXacNhan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yeucau_thamgia`
--
ALTER TABLE `yeucau_thamgia`
  MODIFY `idYeuCau` int NOT NULL AUTO_INCREMENT;

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
