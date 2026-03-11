-- Migration: Fix độ chính xác cột diemTrungBinh trong sanpham_vongthi
-- Vấn đề: decimal(5,0) chỉ lưu số nguyên → điểm chốt bị làm tròn (vd: 42.75 → 43)
-- Giải pháp: Đổi sang decimal(7,2) để giữ 2 chữ số thập phân
-- Ngày:  2026-03-10

ALTER TABLE `sanpham_vongthi`
    MODIFY COLUMN `diemTrungBinh` decimal(7,2) DEFAULT NULL
        COMMENT 'Điểm trung bình chốt của vòng thi (2 chữ số thập phân)';
