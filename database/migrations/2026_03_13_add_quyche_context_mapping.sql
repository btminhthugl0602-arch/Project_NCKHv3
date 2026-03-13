-- Mapping quy che -> ngu canh ap dung
-- Cho phep gan 1 quy che vao nhieu diem nghiep vu thay vi fix cung theo loaiQuyChe

CREATE TABLE IF NOT EXISTS `quyche_ngucanh_apdung` (
  `idQuyChe` int NOT NULL,
  `maNguCanh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ngayGan` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idQuyChe`, `maNguCanh`),
  KEY `idx_quyche_ngucanh_ma` (`maNguCanh`),
  CONSTRAINT `fk_quyche_ngucanh_quyche`
    FOREIGN KEY (`idQuyChe`) REFERENCES `quyche` (`idQuyChe`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
