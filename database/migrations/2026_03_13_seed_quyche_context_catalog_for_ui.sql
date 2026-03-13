-- Seed danh muc ngu canh dung cho dropdown UI
-- Muc tieu: cung cap ma ngu canh chuan de nguoi dung chon, tranh nhap tay sai mapping

INSERT INTO `quyche_danhmuc_ngucanh` (`maNguCanh`, `tenNguCanh`, `moTa`, `isHeThong`) VALUES
('DANG_KY_THAM_GIA_SV', 'Dang ky tham gia (Sinh vien)', 'Ap dung khi sinh vien dang ky vao su kien', 1),
('DANG_KY_THAM_GIA_GV', 'Dang ky tham gia (Giang vien)', 'Ap dung khi giang vien dang ky vao su kien', 1),
('DUYET_VONG_THI', 'Duyet ket qua vong thi', 'Ap dung khi BTC duyet diem va chot trang thai san pham trong vong thi', 1),
('DUYET_VONG_THI_HANG_LOAT', 'Duyet ket qua vong thi hang loat', 'Ap dung khi BTC duyet nhieu san pham cung luc', 1),
('XET_GIAI_THUONG', 'Xet giai thuong', 'Ap dung khi tong hop va xet giai', 1),
('TAO_NHOM', 'Tao nhom', 'Ap dung khi tao nhom moi trong su kien', 1),
('GUI_YEU_CAU_NHOM', 'Gui yeu cau nhom', 'Ap dung khi gui loi moi hoac yeu cau vao nhom', 1),
('DUYET_YEU_CAU_NHOM', 'Duyet yeu cau nhom', 'Ap dung khi chu nhom duyet yeu cau tham gia', 1),
('NOP_SAN_PHAM', 'Nop san pham', 'Ap dung khi nhom tao/cap nhat san pham', 1),
('NOP_TAI_LIEU_VONG_THI', 'Nop tai lieu vong thi', 'Ap dung khi nhom nop tai lieu theo form vong thi', 1)
ON DUPLICATE KEY UPDATE
  `tenNguCanh` = VALUES(`tenNguCanh`),
  `moTa` = VALUES(`moTa`),
  `isHeThong` = VALUES(`isHeThong`);

-- Danh dau context cu la legacy de UI co the an di
UPDATE `quyche_danhmuc_ngucanh`
SET `isHeThong` = 0
WHERE `maNguCanh` IN ('THAMGIA', 'VONGTHI', 'SANPHAM', 'GIAITHUONG');
