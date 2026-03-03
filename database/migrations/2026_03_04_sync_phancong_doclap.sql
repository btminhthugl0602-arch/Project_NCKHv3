-- Migration: Đồng bộ phancong_doclap từ chamtieuchi
-- Mô tả: Thêm các bản ghi phancong_doclap cho những giám khảo đã chấm điểm
--        nhưng chưa có trong bảng phancong_doclap (legacy data)
-- Ngày: 2026-03-04

-- Thêm phân công từ chamtieuchi (giám khảo đã chấm nhưng chưa có trong phancong_doclap)
INSERT IGNORE INTO phancong_doclap (idSanPham, idGV, idVongThi)
SELECT DISTINCT 
    ct.idSanPham,
    pcc.idGV,
    pcc.idVongThi
FROM chamtieuchi ct
INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
LEFT JOIN phancong_doclap pd 
    ON pd.idSanPham = ct.idSanPham 
    AND pd.idGV = pcc.idGV 
    AND pd.idVongThi = pcc.idVongThi
WHERE pd.idSanPham IS NULL;

-- Kiểm tra kết quả
-- SELECT * FROM phancong_doclap ORDER BY idSanPham, idGV;
