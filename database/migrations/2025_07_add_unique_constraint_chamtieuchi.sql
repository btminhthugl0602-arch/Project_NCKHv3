-- Migration: Thêm UNIQUE constraint cho bảng chamtieuchi
-- Mục đích: đảm bảo mỗi giám khảo chỉ có một bản ghi điểm
--           cho một tiêu chí của một sản phẩm trên cùng một phiếu chấm.
--           Ngăn chặn dữ liệu trùng lặp khi lưu điểm nhiều lần.
--
-- ⚠️  Chạy migration này TRƯỚC khi deploy api/cham_diem/nhap_diem.php
-- ⚠️  Kiểm tra dữ liệu trùng lặp hiện có trước khi ALTER TABLE:
--
--    SELECT idPhanCongCham, idSanPham, idTieuChi, COUNT(*)
--    FROM chamtieuchi
--    GROUP BY idPhanCongCham, idSanPham, idTieuChi
--    HAVING COUNT(*) > 1;
--
-- Nếu có kết quả trả về, cần xóa bản ghi trùng trước bằng lệnh thủ công.

ALTER TABLE `chamtieuchi`
    ADD CONSTRAINT `uq_chamtieuchi_pcc_sp_tc`
    UNIQUE (`idPhanCongCham`, `idSanPham`, `idTieuChi`);
