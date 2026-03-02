-- Migration: Thêm cột isActive cho bảng vongthi
-- Date: 2026-03-02
-- Description: Cho phép vô hiệu hóa vòng thi thay vì xóa khi có dữ liệu liên quan

ALTER TABLE `vongthi` 
ADD COLUMN `isActive` TINYINT(1) NOT NULL DEFAULT 1 
COMMENT '1 = Đang hoạt động, 0 = Bị vô hiệu hóa'
AFTER `dongNopThuCong`;

-- Cập nhật tất cả vòng thi hiện có thành active
UPDATE `vongthi` SET `isActive` = 1 WHERE `isActive` IS NULL;
