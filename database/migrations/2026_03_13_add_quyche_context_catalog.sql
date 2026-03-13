-- Phase 2: Context governance for rule application
-- 1) Add context catalog
-- 2) Backfill mapping from legacy loaiQuyChe
-- 3) Enforce mapping values by FK to the catalog

CREATE TABLE IF NOT EXISTS `quyche_danhmuc_ngucanh` (
  `maNguCanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenNguCanh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `moTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `isHeThong` tinyint(1) NOT NULL DEFAULT '1',
  `ngayTao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maNguCanh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quyche_danhmuc_ngucanh` (`maNguCanh`, `tenNguCanh`, `moTa`, `isHeThong`) VALUES
('DANG_KY_THAM_GIA_SV', 'Dang ky tham gia (Sinh vien)', 'Ap dung khi sinh vien dang ky vao su kien', 1),
('DANG_KY_THAM_GIA_GV', 'Dang ky tham gia (Giang vien)', 'Ap dung khi giang vien dang ky vao su kien', 1),
('DUYET_VONG_THI', 'Duyet ket qua vong thi', 'Ap dung khi BTC duyet diem va chot trang thai san pham trong vong thi', 1),
('DUYET_VONG_THI_HANG_LOAT', 'Duyet ket qua vong thi hang loat', 'Ap dung khi BTC duyet nhieu san pham cung luc', 1),
('XET_GIAI_THUONG', 'Xet giai thuong', 'Ap dung khi tong hop va xet giai', 1)
ON DUPLICATE KEY UPDATE
  `tenNguCanh` = VALUES(`tenNguCanh`),
  `moTa` = VALUES(`moTa`);

INSERT INTO `quyche_ngucanh_apdung` (`idQuyChe`, `maNguCanh`)
SELECT q.idQuyChe, UPPER(REPLACE(TRIM(q.loaiQuyChe), ' ', '_'))
FROM `quyche` q
LEFT JOIN `quyche_ngucanh_apdung` n
  ON n.idQuyChe = q.idQuyChe
  AND n.maNguCanh = UPPER(REPLACE(TRIM(q.loaiQuyChe), ' ', '_'))
WHERE COALESCE(TRIM(q.loaiQuyChe), '') <> ''
  AND n.idQuyChe IS NULL;

INSERT INTO `quyche_danhmuc_ngucanh` (`maNguCanh`, `tenNguCanh`, `moTa`, `isHeThong`)
SELECT DISTINCT n.maNguCanh,
       CONCAT('Legacy: ', n.maNguCanh) AS tenNguCanh,
       'Ngu canh sinh ra tu loaiQuyChe cu, can review de chuan hoa' AS moTa,
       0 AS isHeThong
FROM `quyche_ngucanh_apdung` n
LEFT JOIN `quyche_danhmuc_ngucanh` dm ON dm.maNguCanh = n.maNguCanh
WHERE dm.maNguCanh IS NULL;

SET @fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_quyche_ngucanh_danhmuc'
);

SET @fk_sql := IF(
  @fk_exists = 0,
  'ALTER TABLE `quyche_ngucanh_apdung` ADD CONSTRAINT `fk_quyche_ngucanh_danhmuc` FOREIGN KEY (`maNguCanh`) REFERENCES `quyche_danhmuc_ngucanh` (`maNguCanh`) ON DELETE RESTRICT ON UPDATE CASCADE',
  'SELECT 1'
);

PREPARE stmt_fk FROM @fk_sql;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;
