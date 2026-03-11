-- Migration: 2026_03_add_per_sp_status_to_phancong_doclap
-- Mục đích: Thêm trạng thái chấm điểm độc lập cho từng sản phẩm trong phancong_doclap
--            Mỗi bài (SP) có phiếu chấm, nộp phiếu riêng, không phụ thuộc vào các bài khác.

ALTER TABLE `phancong_doclap`
    ADD COLUMN `trangThaiCham` VARCHAR(20) NOT NULL DEFAULT 'Chờ chấm'
        COMMENT 'Chờ chấm / Đang chấm / Đã xác nhận' AFTER `isTrongTai`,
    ADD COLUMN `ngayNop` DATETIME NULL DEFAULT NULL
        COMMENT 'Thời điểm nộp phiếu chấm cho SP này' AFTER `trangThaiCham`;
