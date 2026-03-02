-- Migration: Fix setup cho chức năng Thiết lập bộ tiêu chí
-- Ngày: 2026-03-02
-- Mục tiêu:
-- 1) Bổ sung cột tieuban.idBoTieuChi nếu thiếu (để usage map đầy đủ)
-- 2) Tự cấp vai trò BTC (idVaiTroGoc=1 + idVaiTroSK tương ứng sự kiện) cho người tạo sự kiện nếu thiếu
-- 3) Tạo vòng thi mặc định cho sự kiện chưa có vòng
-- 4) Seed tối thiểu ngân hàng tiêu chí + bộ tiêu chí mẫu (nếu trống)
-- 5) Gán bộ tiêu chí mặc định cho vòng đầu tiên mỗi sự kiện (nếu chưa cấu hình)

START TRANSACTION;

-- ------------------------------------------------------------------
-- [1] Bổ sung tieuban.idBoTieuChi (tương thích nckh.sql)
-- ------------------------------------------------------------------
SET @has_col_idBoTieuChi := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tieuban'
      AND COLUMN_NAME = 'idBoTieuChi'
);

SET @sql_add_col := IF(
    @has_col_idBoTieuChi = 0,
    'ALTER TABLE tieuban ADD COLUMN idBoTieuChi INT NULL AFTER idVongThi',
    'SELECT 1'
);
PREPARE stmt_add_col FROM @sql_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @has_idx_idBoTieuChi := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tieuban'
      AND INDEX_NAME = 'idx_tieuban_idBoTieuChi'
);

SET @sql_add_idx := IF(
    @has_idx_idBoTieuChi = 0,
    'ALTER TABLE tieuban ADD INDEX idx_tieuban_idBoTieuChi (idBoTieuChi)',
    'SELECT 1'
);
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

SET @has_fk_idBoTieuChi := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tieuban'
      AND CONSTRAINT_NAME = 'fk_tieuban_botieuchi'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql_add_fk := IF(
    @has_fk_idBoTieuChi = 0,
    'ALTER TABLE tieuban ADD CONSTRAINT fk_tieuban_botieuchi FOREIGN KEY (idBoTieuChi) REFERENCES botieuchi(idBoTieuChi) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt_add_fk FROM @sql_add_fk;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;

-- ------------------------------------------------------------------
-- [2] Đảm bảo người tạo sự kiện có role BTC trong từng sự kiện
-- ------------------------------------------------------------------
INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTroSK, idVaiTroGoc, nguonTao, idNguoiCap, isActive)
SELECT sk.nguoiTao, sk.idSK, vsk.idVaiTroSK, 1, 'BTC_THEM', sk.nguoiTao, 1
FROM sukien sk
JOIN vaitro_sukien vsk
  ON vsk.idSK = sk.idSK
 AND vsk.idVaiTroGoc = 1
 AND vsk.isSystem = 1
 AND vsk.isActive = 1
WHERE sk.nguoiTao IS NOT NULL
  AND sk.nguoiTao > 0
  AND NOT EXISTS (
      SELECT 1
      FROM taikhoan_vaitro_sukien tvs
      WHERE tvs.idTK = sk.nguoiTao
        AND tvs.idSK = sk.idSK
        AND tvs.idVaiTroGoc = 1
        AND tvs.isActive = 1
  );

-- ------------------------------------------------------------------
-- [3] Tạo vòng thi mặc định cho sự kiện chưa có vòng thi
-- ------------------------------------------------------------------
INSERT INTO vongthi (idSK, tenVongThi, moTa, thuTu, ngayBatDau, ngayKetThuc, thoiGianDongNop, dongNopThuCong)
SELECT sk.idSK,
       'Vòng 1',
       'Tự động tạo để khởi tạo cấu hình bộ tiêu chí',
       1,
       NULL,
       NULL,
       NULL,
       0
FROM sukien sk
WHERE NOT EXISTS (
    SELECT 1
    FROM vongthi vt
    WHERE vt.idSK = sk.idSK
);

-- ------------------------------------------------------------------
-- [4] Seed tối thiểu tieuchi/botieuchi nếu hệ thống đang trống
-- ------------------------------------------------------------------
INSERT INTO tieuchi (noiDungTieuChi)
SELECT 'Tính cấp thiết của đề tài'
WHERE NOT EXISTS (SELECT 1 FROM tieuchi LIMIT 1);

INSERT INTO tieuchi (noiDungTieuChi)
SELECT 'Phương pháp nghiên cứu'
WHERE NOT EXISTS (SELECT 1 FROM tieuchi WHERE noiDungTieuChi = 'Phương pháp nghiên cứu');

INSERT INTO tieuchi (noiDungTieuChi)
SELECT 'Kết quả đạt được'
WHERE NOT EXISTS (SELECT 1 FROM tieuchi WHERE noiDungTieuChi = 'Kết quả đạt được');

INSERT INTO tieuchi (noiDungTieuChi)
SELECT 'Hình thức trình bày'
WHERE NOT EXISTS (SELECT 1 FROM tieuchi WHERE noiDungTieuChi = 'Hình thức trình bày');

INSERT INTO tieuchi (noiDungTieuChi)
SELECT 'Khả năng ứng dụng thực tiễn'
WHERE NOT EXISTS (SELECT 1 FROM tieuchi WHERE noiDungTieuChi = 'Khả năng ứng dụng thực tiễn');

INSERT INTO botieuchi (tenBoTieuChi, moTa)
SELECT 'Bộ tiêu chí mặc định', 'Bộ tiêu chí khởi tạo tự động cho cấu hình sự kiện'
WHERE NOT EXISTS (SELECT 1 FROM botieuchi LIMIT 1);

SET @default_bo_tieu_chi := (
    SELECT idBoTieuChi
    FROM botieuchi
    ORDER BY idBoTieuChi ASC
    LIMIT 1
);

INSERT INTO botieuchi_tieuchi (idBoTieuChi, idTieuChi, tyTrong, diemToiDa)
SELECT @default_bo_tieu_chi, t.idTieuChi, 1.00, 10.00
FROM tieuchi t
WHERE @default_bo_tieu_chi IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM botieuchi_tieuchi bt
      WHERE bt.idBoTieuChi = @default_bo_tieu_chi
        AND bt.idTieuChi = t.idTieuChi
  );

-- ------------------------------------------------------------------
-- [5] Nếu sự kiện chưa map bộ tiêu chí, map vào vòng đầu tiên
-- ------------------------------------------------------------------
INSERT INTO cauhinh_tieuchi_sk (idSK, idVongThi, idBoTieuChi)
SELECT vt.idSK, vt.idVongThi, @default_bo_tieu_chi
FROM vongthi vt
WHERE @default_bo_tieu_chi IS NOT NULL
  AND vt.idVongThi = (
      SELECT vt2.idVongThi
      FROM vongthi vt2
      WHERE vt2.idSK = vt.idSK
      ORDER BY vt2.thuTu ASC, vt2.idVongThi ASC
      LIMIT 1
  )
  AND NOT EXISTS (
      SELECT 1
      FROM cauhinh_tieuchi_sk c
      WHERE c.idSK = vt.idSK
  );

COMMIT;
