-- ============================================================
-- SCHEMA MỚI: MODULE NHÓM + NỘP SẢN PHẨM (Hướng C - Làm lại)
-- Phiên bản: 2.1
-- ============================================================
-- Ghi chú:
--   [SỬA]  = thay đổi so với schema cũ
--   [MỚI]  = bảng/cột hoàn toàn mới
--   [XÓA]  = đã loại bỏ, ghi rõ lý do
--   [GIỮ]  = không thay đổi
-- ============================================================

-- Bước 1: DROP tất cả FK trỏ vào các bảng sẽ bị thay đổi
-- (cần thiết vì phpMyAdmin không giữ FOREIGN_KEY_CHECKS=0 giữa các statement)

-- FK trỏ vào nhom
ALTER TABLE `diemdanh`        DROP FOREIGN KEY `diemdanh_ibfk_1`;
ALTER TABLE `ketqua`          DROP FOREIGN KEY `ketqua_ibfk_2`;
ALTER TABLE `thongtinnhom`    DROP FOREIGN KEY `thongtinnhom_ibfk_1`;
ALTER TABLE `xacnhan_thamgia` DROP FOREIGN KEY `xacnhan_thamgia_ibfk_2`;
ALTER TABLE `yeucau_thamgia`  DROP FOREIGN KEY `yeucau_thamgia_ibfk_1`;
ALTER TABLE `thanhviennhom`   DROP FOREIGN KEY `thanhviennhom_ibfk_1`;
ALTER TABLE `thanhviennhom`   DROP FOREIGN KEY `thanhviennhom_ibfk_2`;
ALTER TABLE `thanhviennhom`   DROP FOREIGN KEY `thanhviennhom_ibfk_3`;
ALTER TABLE `sanpham`         DROP FOREIGN KEY `sanpham_ibfk_1`;
ALTER TABLE `sanpham`         DROP FOREIGN KEY `sanpham_ibfk_2`;
ALTER TABLE `sanpham`         DROP FOREIGN KEY `sanpham_ibfk_3`;
ALTER TABLE `sanpham`         DROP FOREIGN KEY `sanpham_ibfk_4`;

-- FK trỏ vào sanpham
ALTER TABLE `chamtieuchi`     DROP FOREIGN KEY `chamtieuchi_ibfk_2`;

-- FK trỏ vào loaitailieu (sanpham đã drop ở trên)
-- FK trỏ vào vaitronhom (thanhviennhom đã drop ở trên)


-- ============================================================
-- PHẦN 1: BẢNG sukien — Thêm cấu hình nhóm
-- ============================================================

-- [SỬA] Xóa cheDoDangKy* (thay bằng quy chế THAMGIA_SV / THAMGIA_GV)
-- Thêm các trường cấu hình nhóm
ALTER TABLE `sukien`
  DROP COLUMN `cheDoDangKySV`,
  DROP COLUMN `cheDoDangKyGV`,

  ADD COLUMN `soThanhVienToiThieu` int NOT NULL DEFAULT 1
    COMMENT 'Số SV tối thiểu trong nhóm'
    AFTER `soNhomToiDaGVHD`,

  ADD COLUMN `soThanhVienToiDa` int NOT NULL DEFAULT 5
    COMMENT 'Số SV tối đa trong nhóm (không tính GVHD)'
    AFTER `soThanhVienToiThieu`,

  ADD COLUMN `soGVHDToiDa` int DEFAULT NULL
    COMMENT 'Số GVHD tối đa/nhóm. NULL = không giới hạn'
    AFTER `soThanhVienToiDa`,

  ADD COLUMN `yeuCauCoGVHD` tinyint NOT NULL DEFAULT 0
    COMMENT 'Bắt buộc có GVHD mới được nộp bài: 0=không, 1=có'
    AFTER `soGVHDToiDa`,

  ADD COLUMN `choPhepGVTaoNhom` tinyint NOT NULL DEFAULT 1
    COMMENT 'Cho phép GV tạo nhóm: 0=không, 1=có'
    AFTER `yeuCauCoGVHD`;


-- ============================================================
-- PHẦN 2: BẢNG nhom — Tách biệt Chủ nhóm và Trưởng nhóm
-- ============================================================

-- [XÓA] nhom cũ:
--   - idnhomtruong: không phân biệt chủ nhóm vs trưởng nhóm
--   - Không hỗ trợ GV là chủ nhóm nhưng SV là trưởng nhóm



DROP TABLE IF EXISTS `nhom`;

-- [MỚI] Bảng nhom với phân tách rõ ràng
CREATE TABLE `nhom` (
  `idNhom`        int NOT NULL AUTO_INCREMENT,
  `idSK`          int NOT NULL,
  `idChuNhom`     int NOT NULL
    COMMENT 'Chủ nhóm: GV hoặc SV. Có quyền quản lý hành chính nhóm
             (mời, kick, duyệt yêu cầu, chọn/đổi trưởng nhóm)',
  `idTruongNhom`  int DEFAULT NULL
    COMMENT 'Trưởng nhóm: bắt buộc là SV. NULL khi GV là chủ nhóm
             và chưa chỉ định. Người duy nhất được nộp sản phẩm.',
  `maNhom`        varchar(30) NOT NULL
    COMMENT 'Mã nhóm unique trong sự kiện',
  `ngayTao`       datetime DEFAULT CURRENT_TIMESTAMP,
  `isActive`      tinyint NOT NULL DEFAULT 1,

  PRIMARY KEY (`idNhom`),
  UNIQUE KEY `uq_manhom_sk` (`maNhom`, `idSK`),
  KEY `idx_nhom_sk` (`idSK`),
  KEY `idx_nhom_chunhom` (`idChuNhom`),
  KEY `idx_nhom_truongnhom` (`idTruongNhom`),

  CONSTRAINT `nhom_fk_sk`
    FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT,
  CONSTRAINT `nhom_fk_chunhom`
    FOREIGN KEY (`idChuNhom`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT,
  CONSTRAINT `nhom_fk_truongnhom`
    FOREIGN KEY (`idTruongNhom`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nhóm tham gia sự kiện.
           Quy tắc:
           - SV tạo nhóm → idChuNhom = idTruongNhom = SV đó
           - GV tạo nhóm → idChuNhom = GV, idTruongNhom = NULL
           - Chỉ Chủ nhóm mới được thay đổi Trưởng nhóm
           - Chủ nhóm không được rời nếu chưa nhượng quyền
           - Trưởng nhóm không thể tự bỏ role';

-- [MIGRATE] INSERT data cũ vào nhom mới trước khi add FK
-- Mapping: idChuNhom = idTruongNhom = idnhomtruong (toàn bộ do SV tạo)
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `nhom` (`idNhom`, `idSK`, `idChuNhom`, `idTruongNhom`, `maNhom`, `ngayTao`, `isActive`) VALUES
(1,   1,   4,   4,   'GRP_AI_01',             '2026-02-11 22:11:23', 1),
(2,   1,   5,   5,   'GRP_IOT_01',            '2026-02-21 12:51:34', 1),
(3,   11,  4,   4,   'GRP_THDH_01',           '2026-02-21 12:51:34', 1),
(4,   11,  6,   6,   'GRP_THDH_02',           '2026-02-21 12:51:34', 1),
(5,   11,  5,   5,   'GRP_THDH_03',           '2026-02-21 12:51:34', 1),
(6,   11,  6,   6,   'GRP_177175480715',      '2026-02-22 17:06:47', 1),
(500, 500, 4,   4,   'GRP_HACK_01',           '2026-02-23 15:34:08', 1),
(501, 500, 6,   6,   'GRP_HACK_02',           '2026-02-23 15:34:08', 1),
(502, 501, 5,   5,   'GRP_501_1771841506',    '2026-02-23 17:11:46', 1),
(801, 800, 4,   4,   'TEAM_AI_PRO',           '2026-02-01 00:00:00', 1),
(802, 800, 6,   6,   'TEAM_SMART_IOT',        '2026-02-05 00:00:00', 1),
(803, 1,   8,   8,   'GRP_1_1772174418',      '2026-02-27 13:40:18', 1),
(804, 800, 5,   5,   'GRP_800_1772179702',    '2026-02-27 15:08:22', 1),
(805, 800, 1,   1,   'GRP_800_1772981521',    '2026-03-08 14:52:01', 1),
(991, 999, 904, 904, 'TEAM_TEST_LECH_1',      NULL,                  1),
(992, 999, 905, 905, 'TEAM_TEST_LECH_2',      NULL,                  1);

SET FOREIGN_KEY_CHECKS = 1;

-- [FIX] Add FK các bảng trỏ vào nhom mới
ALTER TABLE `thongtinnhom`
  ADD CONSTRAINT `thongtinnhom_ibfk_1`
  FOREIGN KEY (`idnhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `diemdanh`
  ADD CONSTRAINT `diemdanh_ibfk_1`
  FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `ketqua`
  ADD CONSTRAINT `ketqua_ibfk_2`
  FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `xacnhan_thamgia`
  ADD CONSTRAINT `xacnhan_thamgia_ibfk_2`
  FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `yeucau_thamgia`
  ADD CONSTRAINT `yeucau_thamgia_ibfk_1`
  FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT ON UPDATE RESTRICT;


-- ============================================================
-- PHẦN 3: BẢNG thanhviennhom — Chỉ chứa SV đã confirmed
-- ============================================================

-- [XÓA] thanhviennhom cũ:
--   - laChuNhom: redundant, đã dùng nhom.idChuNhom
--   - idvaitronhom: redundant, vai trò xác định qua idChuNhom/idTruongNhom
--   - Không có PRIMARY KEY → có thể duplicate rows
--   - GVHD tách sang bảng nhom_gvhd riêng
DROP TABLE IF EXISTS `thanhviennhom`;

-- [MỚI] Chỉ chứa SV thành viên đã confirmed
CREATE TABLE `thanhviennhom` (
  `idNhom`        int NOT NULL,
  `idTK`          int NOT NULL
    COMMENT 'Chỉ là SV (THAM_GIA). GVHD lưu ở bảng nhom_gvhd',
  `ngayThamGia`   datetime DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`idNhom`, `idTK`),

  CONSTRAINT `thanhvien_fk_nhom`
    FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE CASCADE,
  CONSTRAINT `thanhvien_fk_tk`
    FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Thành viên SV đã confirmed trong nhóm.
           Lưu ý: Chủ nhóm và Trưởng nhóm CŨNG phải có bản ghi ở đây
           nếu họ là SV. GV chủ nhóm không có bản ghi ở đây.';


-- ============================================================
-- PHẦN 4: BẢNG nhom_gvhd — Tách GVHD ra khỏi thanhviennhom
-- ============================================================

-- [MỚI] GVHD không phải "thành viên" theo nghĩa thông thường
CREATE TABLE `nhom_gvhd` (
  `idNhom`        int NOT NULL,
  `idTK`          int NOT NULL
    COMMENT 'Tài khoản GV_HUONG_DAN',
  `ngayThamGia`   datetime DEFAULT CURRENT_TIMESTAMP
    COMMENT 'Ngày GV chính thức vào nhóm (sau khi chấp nhận lời mời)',

  PRIMARY KEY (`idNhom`, `idTK`),

  CONSTRAINT `gvhd_fk_nhom`
    FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE CASCADE,
  CONSTRAINT `gvhd_fk_tk`
    FOREIGN KEY (`idTK`) REFERENCES `taikhoan` (`idTK`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='GVHD của nhóm. Tách riêng vì:
           - GVHD không tính vào soThanhVienToiDa
           - Một nhóm có thể có 0..N GVHD (tùy cấu hình SK)
           - GVHD có thể chủ động rút khỏi nhóm
           - GV là Chủ nhóm cũng có bản ghi ở đây
           - Khi GVHD accept lời mời → INSERT ở đây
             + INSERT taikhoan_vaitro_sukien (nguonTao=QUA_NHOM)';


-- ============================================================
-- PHẦN 5: BẢNG yeucau_thamgia — Thêm loaiYeuCau
-- ============================================================

-- [SỬA] Thêm loaiYeuCau để phân biệt luồng duyệt:
--   SV:   Chủ nhóm duyệt → INSERT thanhviennhom
--   GVHD: GV tự duyệt   → INSERT nhom_gvhd + INSERT taikhoan_vaitro_sukien
ALTER TABLE `yeucau_thamgia`
  ADD COLUMN `loaiYeuCau` enum('SV','GVHD') NOT NULL DEFAULT 'SV'
    COMMENT 'SV: yêu cầu thành viên sinh viên
             GVHD: mời giảng viên hướng dẫn'
    AFTER `ChieuMoi`;

-- [GIỮ NGUYÊN] Các trường khác:
--   ChieuMoi:    0=nhóm gửi mời, 1=người dùng tự xin vào
--   trangThai:   0=chờ, 1=chấp nhận, 2=từ chối
--   loiNhan, ngayGui, ngayPhanHoi
-- [QUY TẮC] Khi kick thành viên:
--   UPDATE yeucau_thamgia SET trangThai=2
--   WHERE idTK=:idTK AND idNhom=:idNhom AND trangThai=0


-- ============================================================
-- PHẦN 6: BẢNG vaitronhom — Xóa (không còn dùng)
-- ============================================================

-- [XÓA] Vai trò trong nhóm giờ xác định qua:
--   nhom.idChuNhom    → Chủ nhóm
--   nhom.idTruongNhom → Trưởng nhóm
--   nhom_gvhd         → GVHD
--   thanhviennhom     → Thành viên thường
DROP TABLE IF EXISTS `vaitronhom`;


-- ============================================================
-- PHẦN 7: BẢNG sanpham — Hardcode các trường quan trọng
-- ============================================================

-- [XÓA] sanpham cũ:
--   - idloaitailieu: thay bằng dynamic form
--   - moTaTaiLieu: thay bằng dynamic form
--   - isActive: không cần thiết
DROP TABLE IF EXISTS `sanpham`;

-- [MỚI] sanpham với các trường hardcode thiết yếu
CREATE TABLE `sanpham` (
  `idSanPham`     int NOT NULL AUTO_INCREMENT,
  `idNhom`        int NOT NULL,
  `idSK`          int NOT NULL,
  `idChuDeSK`     int DEFAULT NULL
    COMMENT 'Chủ đề đề tài — hardcode vì ảnh hưởng phân công tiểu ban chấm',
  `tenSanPham`    varchar(200) NOT NULL
    COMMENT 'Tên đề tài — hardcode vì dùng ở mọi nơi: chấm điểm, kết quả, chứng nhận',
  `trangThai`     enum('CHO_DUYET','DA_DUYET','BI_LOAI') NOT NULL DEFAULT 'CHO_DUYET',
  `ngayTao`       datetime DEFAULT CURRENT_TIMESTAMP,
  `ngayCapNhat`   datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`idSanPham`),
  UNIQUE KEY `uq_nhom_sk` (`idNhom`, `idSK`)
    COMMENT 'Mỗi nhóm chỉ có 1 sản phẩm per sự kiện',
  KEY `idx_sp_sk` (`idSK`),
  KEY `idx_sp_chude` (`idChuDeSK`),

  CONSTRAINT `sp_fk_nhom`
    FOREIGN KEY (`idNhom`) REFERENCES `nhom` (`idNhom`) ON DELETE RESTRICT,
  CONSTRAINT `sp_fk_sk`
    FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE RESTRICT,
  CONSTRAINT `sp_fk_chude`
    FOREIGN KEY (`idChuDeSK`) REFERENCES `chude_sukien` (`idChuDeSK`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sản phẩm của nhóm. 1 bản ghi per nhóm per sự kiện.
           Nội dung chi tiết (file, link...) lưu ở sanpham_field_value.
           Chỉ Trưởng nhóm được tạo/cập nhật.
           Chỉ được sửa khi vongthi.thoiGianDongNop chưa qua.';

-- [MIGRATE] Dọn dẹp data tham chiếu idSanPham=3,7 (bị loại do vi phạm UNIQUE nhóm+SK)
-- chamtieuchi: dòng tham chiếu idSanPham=7
DELETE FROM `chamtieuchi` WHERE `idSanPham` IN (3, 7);
-- tieuban_sanpham: dòng tham chiếu idSanPham=7
DELETE FROM `tieuban_sanpham` WHERE `idSanPham` IN (3, 7);
-- sanpham_vongthi nếu có
DELETE FROM `sanpham_vongthi` WHERE `idSanPham` IN (3, 7);
-- phancong_doclap nếu có
DELETE FROM `phancong_doclap` WHERE `idSanPham` IN (3, 7);

-- [MIGRATE] INSERT data thanhviennhom mới (chỉ trangthai=1 confirmed)
-- Bỏ: (801,5) và (804,4) vì trangthai=0 trong schema cũ
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `thanhviennhom` (`idNhom`, `idTK`, `ngayThamGia`) VALUES
(1,   4,   '2026-02-11 22:11:23'),
(1,   5,   '2026-02-11 22:11:23'),
(6,   6,   '2026-02-22 17:06:47'),
(6,   5,   '2026-02-23 09:18:51'),
(500, 4,   '2026-02-23 15:34:08'),
(500, 5,   '2026-02-23 15:34:08'),
(501, 6,   '2026-02-23 15:34:08'),
(501, 8,   '2026-02-23 15:34:08'),
(502, 5,   '2026-02-23 17:11:46'),
(801, 4,   '2026-02-27 13:33:56'),
(802, 6,   '2026-02-27 13:33:56'),
(802, 8,   '2026-02-27 13:33:56'),
(803, 8,   '2026-02-27 13:40:18'),
(804, 5,   '2026-02-27 15:08:22'),
(804, 8,   '2026-02-27 15:12:23'),
(805, 1,   '2026-03-08 14:52:01'),
(991, 904, '2026-03-09 14:58:02'),
(992, 905, '2026-03-09 14:58:02');

-- [MIGRATE] INSERT data sanpham mới
-- Bỏ id=3 (nhóm 1+SK 1 trùng id=1) và id=7 (nhóm 3+SK 11 trùng id=4)
INSERT INTO `sanpham` (`idSanPham`, `idNhom`, `idSK`, `idChuDeSK`, `tenSanPham`, `trangThai`, `ngayTao`) VALUES
(1,   1,   1,   1,    'Hệ thống điểm danh bằng nhận diện khuôn mặt',                    'CHO_DUYET', NOW()),
(2,   2,   1,   2,    'Hệ thống nhà kính thông minh giám sát qua IoT',                   'CHO_DUYET', NOW()),
(4,   3,   11,  NULL, 'Ứng dụng di động hỗ trợ sinh viên ôn thi trắc nghiệm',            'CHO_DUYET', NOW()),
(5,   4,   11,  NULL, 'Thuật toán tối ưu hóa lịch biểu giảng đường đại học',             'CHO_DUYET', NOW()),
(6,   5,   11,  NULL, 'Phần mềm quản lý chi tiêu cá nhân tích hợp AI',                  'CHO_DUYET', NOW()),
(500, 500, 500, NULL, 'Hệ thống cảnh báo giao thông AI',                                 'CHO_DUYET', NOW()),
(501, 501, 500, NULL, 'App quản lý thời gian Pomodoro 3D',                               'CHO_DUYET', NOW()),
(801, 801, 800, 801,  'Ứng dụng Deep Learning trong chẩn đoán sớm bệnh lý trên lá lúa', 'DA_DUYET',  NOW()),
(802, 802, 800, 802,  'Hệ thống nhà kính thông minh giám sát tự động qua Telegram',      'DA_DUYET',  NOW()),
(991, 991, 999, NULL, 'Hệ thống AI Test Cảnh Báo 1',                                     'DA_DUYET',  NOW()),
(992, 992, 999, NULL, 'Giải pháp IoT Test Cảnh Báo 2',                                   'DA_DUYET',  NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- [FIX] Add FK các bảng trỏ vào sanpham mới
ALTER TABLE `chamtieuchi`
  ADD CONSTRAINT `chamtieuchi_ibfk_2`
  FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `tieuban_sanpham`
  ADD CONSTRAINT `tieuban_sanpham_ibfk_2`
  FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT;


-- ============================================================
-- PHẦN 8: BẢNG loaitailieu — Xóa (thay bằng Dynamic Form)
-- ============================================================

-- [XÓA] Thay thế hoàn toàn bằng form_field động
--   Các loại tài liệu cố định (PDF, Link...) → kieuTruong trong form_field
DROP TABLE IF EXISTS `loaitailieu`;


-- ============================================================
-- PHẦN 9: BẢNG form_field — Dynamic Form
-- ============================================================

-- [MỚI] BTC thiết kế form nộp tài liệu per vòng thi
CREATE TABLE `form_field` (
  `idField`       int NOT NULL AUTO_INCREMENT,
  `idSK`          int NOT NULL
    COMMENT 'Luôn có — field thuộc về sự kiện nào. Dùng để copy form và cascade delete',
  `idVongThi`     int DEFAULT NULL
    COMMENT 'NULL = field mặc định của sự kiện (dùng khi tạo sản phẩm lần đầu)
             Có giá trị = field riêng của vòng thi đó
             Logic resolve khi nhóm nộp ở Vòng X:
               Vòng X có field? → dùng form Vòng X
               Không → không cần nộp gì, thông báo vòng này không yêu cầu tài liệu',
  `tenTruong`     varchar(200) NOT NULL
    COMMENT 'Tên hiển thị, vd: "Link Github", "File báo cáo PDF"',
  `kieuTruong`    enum('TEXT','TEXTAREA','URL','FILE','SELECT','CHECKBOX') NOT NULL,
  `batBuoc`       tinyint NOT NULL DEFAULT 1,
  `thuTu`         int NOT NULL DEFAULT 0
    COMMENT 'Thứ tự hiển thị trong form',
  `cauHinhJson`   json DEFAULT NULL
    COMMENT 'Cấu hình riêng theo kieuTruong:
             FILE:     {"accept":"pdf,docx","maxSizeKB":5120}
             SELECT:   {"options":["Lựa chọn A","Lựa chọn B"]}
             TEXT:     {"maxLength":200,"placeholder":"Nhập tên đề tài..."}
             TEXTAREA: {"maxLength":1000,"rows":5}
             URL:      {"placeholder":"https://github.com/..."}
             CHECKBOX: {"label":"Tôi xác nhận đã đọc quy định"}',
  `isActive`      tinyint NOT NULL DEFAULT 1,

  PRIMARY KEY (`idField`),
  KEY `idx_ff_sk` (`idSK`),
  KEY `idx_ff_vongthi` (`idVongThi`),
  KEY `idx_ff_sk_vongthi` (`idSK`, `idVongThi`),

  CONSTRAINT `ff_fk_sk`
    FOREIGN KEY (`idSK`) REFERENCES `sukien` (`idSK`) ON DELETE CASCADE,
  CONSTRAINT `ff_fk_vongthi`
    FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Dynamic form BTC thiết kế cho từng vòng thi.
           Copy form: INSERT lại với idSK/idVongThi đích.
           Nếu đích đã có field → hỏi BTC: Ghi đè hay Thêm vào.';


-- ============================================================
-- PHẦN 10: BẢNG sanpham_field_value — Nhóm điền form
-- ============================================================

-- [MỚI] Lưu giá trị từng field nhóm đã điền, per vòng thi
CREATE TABLE `sanpham_field_value` (
  `idSanPham`     int NOT NULL,
  `idVongThi`     int DEFAULT NULL
    COMMENT 'NULL = nộp theo form SK mặc định (khi tạo sản phẩm lần đầu)
             Có giá trị = nộp theo form vòng thi cụ thể',
  `idField`       int NOT NULL,
  `giaTriText`    text DEFAULT NULL
    COMMENT 'Dùng cho kieuTruong: TEXT, TEXTAREA, URL, SELECT, CHECKBOX',
  `duongDanFile`  varchar(500) DEFAULT NULL
    COMMENT 'Dùng cho kieuTruong: FILE. Lưu path tương đối.
             Pattern: /uploads/sanpham/{idSK}/{idNhom}/{idVongThi}/{tenFile}',
  `ngayNop`       datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    COMMENT 'Thời điểm nộp/cập nhật lần cuối',

  PRIMARY KEY (`idSanPham`, `idField`),
  KEY `idx_sfv_vongthi` (`idVongThi`),
  KEY `idx_sfv_field` (`idField`),

  CONSTRAINT `sfv_fk_sanpham`
    FOREIGN KEY (`idSanPham`) REFERENCES `sanpham` (`idSanPham`) ON DELETE CASCADE,
  CONSTRAINT `sfv_fk_vongthi`
    FOREIGN KEY (`idVongThi`) REFERENCES `vongthi` (`idVongThi`) ON DELETE RESTRICT,
  CONSTRAINT `sfv_fk_field`
    FOREIGN KEY (`idField`) REFERENCES `form_field` (`idField`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Giá trị các field nhóm đã điền khi nộp tài liệu.
           PRIMARY KEY (idSanPham, idField): mỗi field chỉ có 1 giá trị
           → nộp lại = UPDATE, không INSERT thêm.
           Check deadline: vongthi.thoiGianDongNop > NOW() mới cho phép UPDATE.
           Mỗi vòng có idField riêng trong form_field nên không bị trùng PK.';


-- Hoàn tất migration


-- ============================================================
-- PHẦN 11: TÓM TẮT CÁC THAY ĐỔI
-- ============================================================

/*
  BẢNG BỊ XÓA:
  ├── vaitronhom       → vai trò xác định qua nhom.idChuNhom / idTruongNhom / nhom_gvhd
  └── loaitailieu      → thay bằng form_field động

  BẢNG SỬA:
  ├── sukien           → xóa cheDoDangKy*, thêm 5 trường cấu hình nhóm
  ├── nhom             → tạo lại: idnhomtruong → idChuNhom + idTruongNhom
  ├── thanhviennhom    → tạo lại: xóa laChuNhom + idvaitronhom, thêm PRIMARY KEY
  ├── yeucau_thamgia   → thêm loaiYeuCau (SV|GVHD)
  └── sanpham          → tạo lại: xóa idloaitailieu + moTaTaiLieu + isActive

  BẢNG MỚI:
  ├── nhom_gvhd           → GVHD của nhóm (tách khỏi thanhviennhom)
  ├── form_field          → BTC thiết kế dynamic form per vòng thi
  └── sanpham_field_value → nhóm điền form, UPDATE khi nộp lại

  BẢNG GIỮ NGUYÊN:
  ├── thongtinnhom        → thông tin chi tiết nhóm (tennhom, mota, dangtuyen...)
  ├── sanpham_vongthi     → kết quả chấm điểm per vòng (module chấm điểm)
  ├── vongthi             → giữ thoiGianDongNop làm deadline nộp bài
  └── taikhoan_vaitro_sukien → khi GVHD vào nhóm INSERT với nguonTao=QUA_NHOM

  FK ĐƯỢC RESTORE SAU KHI TẠO LẠI BẢNG:
  ├── thongtinnhom, diemdanh, ketqua, xacnhan_thamgia, yeucau_thamgia → nhom
  └── chamtieuchi, tieuban_sanpham → sanpham

  QUY TẮC NGHIỆP VỤ QUAN TRỌNG:
  ├── SV tạo nhóm → idChuNhom = idTruongNhom = SV (có bản ghi trong thanhviennhom)
  ├── GV tạo nhóm → idChuNhom = GV (không có bản ghi trong thanhviennhom)
  │                 idTruongNhom = NULL, GV tự động có bản ghi trong nhom_gvhd
  ├── Chủ nhóm chỉ định Trưởng nhóm trực tiếp (không cần SV đồng ý)
  ├── Trưởng nhóm phải là SV có bản ghi trong thanhviennhom
  ├── Chủ nhóm không được rời nếu chưa nhượng quyền
  ├── Trưởng nhóm không thể tự bỏ role
  ├── GV nhượng quyền Chủ nhóm cho SV → GV vẫn giữ bản ghi trong nhom_gvhd
  ├── Kick thành viên → UPDATE yeucau_thamgia SET trangThai=2 (cleanup pending)
  └── GVHD accept lời mời → INSERT nhom_gvhd + INSERT taikhoan_vaitro_sukien(nguonTao=QUA_NHOM)
*/
