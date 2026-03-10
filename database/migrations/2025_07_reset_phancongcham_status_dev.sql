-- Migration: Reset trạng thái phancongcham seed data về 'Chờ chấm'
-- Mục đích: Dữ liệu seed cũ dùng 'Đã xác nhận' cho tất cả records,
--           nhưng theo luồng mới 'Đã xác nhận' = đã chốt/lock form.
--           Cần reset về 'Chờ chấm' để GV có thể test nhập điểm.
--
-- ⚠️  CHỈ CHẠY TRÊN MÔI TRƯỜNG PHÁT TRIỂN (dev/localhost)
-- ⚠️  KHÔNG chạy trên production nếu đã có dữ liệu điểm thật.

-- Reset tất cả phancongcham về 'Chờ chấm' (trừ những record có chamtieuchi thật)
-- Dùng giá trị '1000-01-01 00:00:00' làm mốc mặc định thay cho 0000-00-00
UPDATE phancongcham
SET trangThaiXacNhan = 'Chờ chấm', ngayXacNhan = '1000-01-01 00:00:00'
WHERE NOT EXISTS (
    SELECT 1 FROM chamtieuchi ct WHERE ct.idPhanCongCham = phancongcham.idPhanCongCham LIMIT 1
);

-- Cập nhật 'Đang chấm'
UPDATE phancongcham
SET trangThaiXacNhan = 'Đang chấm'
WHERE trangThaiXacNhan = 'Đã xác nhận'
  AND EXISTS (
    SELECT 1 FROM chamtieuchi ct WHERE ct.idPhanCongCham = phancongcham.idPhanCongCham LIMIT 1
  );

INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, nguonTao, isActive)
SELECT DISTINCT gv.idTK, sp.idSK, 2, 'PHANCONG_CHAM', 1
FROM phancong_doclap pd
INNER JOIN sanpham sp ON pd.idSanPham = sp.idSanPham
INNER JOIN giangvien gv ON pd.idGV = gv.idGV
WHERE NOT EXISTS (
    SELECT 1 FROM taikhoan_vaitro_sukien tvs
    WHERE tvs.idTK = gv.idTK AND tvs.idSK = sp.idSK AND tvs.idVaiTro = 2 AND tvs.isActive = 1
);