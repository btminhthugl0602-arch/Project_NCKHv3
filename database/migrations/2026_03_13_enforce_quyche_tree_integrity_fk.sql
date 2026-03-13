-- 2026-03-13
-- Phase 1: enforce integrity for quy che save flow
-- Goals:
-- 1) Remove orphan rows before tightening constraints
-- 2) Ensure FK with ON DELETE CASCADE where appropriate

START TRANSACTION;

-- ------------------------------------------------------------------
-- 1. Cleanup existing orphan rows
-- ------------------------------------------------------------------
DELETE dd
FROM dieukien_don dd
LEFT JOIN dieukien d ON d.idDieuKien = dd.idDieuKien
WHERE d.idDieuKien IS NULL;

DELETE th
FROM tohop_dieukien th
LEFT JOIN dieukien d ON d.idDieuKien = th.idDieuKien
WHERE d.idDieuKien IS NULL;

DELETE th
FROM tohop_dieukien th
LEFT JOIN dieukien dl ON dl.idDieuKien = th.idDieuKienTrai
WHERE dl.idDieuKien IS NULL;

DELETE th
FROM tohop_dieukien th
LEFT JOIN dieukien dr ON dr.idDieuKien = th.idDieuKienPhai
WHERE dr.idDieuKien IS NULL;

DELETE qd
FROM quyche_dieukien qd
LEFT JOIN quyche q ON q.idQuyChe = qd.idQuyChe
WHERE q.idQuyChe IS NULL;

DELETE qd
FROM quyche_dieukien qd
LEFT JOIN dieukien d ON d.idDieuKien = qd.idDieuKienCuoi
WHERE d.idDieuKien IS NULL;

-- ------------------------------------------------------------------
-- 2. Drop existing FK constraints
-- (Lưu ý: Nếu khóa ngoại chưa từng tồn tại, lệnh DROP sẽ báo lỗi nhỏ. 
-- Bạn có thể bỏ qua lỗi đó và tiếp tục chạy lệnh ADD ở bước 3)
-- ------------------------------------------------------------------
ALTER TABLE dieukien_don DROP FOREIGN KEY dieukien_don_ibfk_1;
ALTER TABLE tohop_dieukien DROP FOREIGN KEY tohop_dieukien_ibfk_1;
ALTER TABLE quyche_dieukien DROP FOREIGN KEY quyche_dieukien_ibfk_1;
ALTER TABLE quyche_dieukien DROP FOREIGN KEY quyche_dieukien_ibfk_2;

-- ------------------------------------------------------------------
-- 3. Rebuild FK constraints with explicit actions
-- ------------------------------------------------------------------
ALTER TABLE dieukien_don
  ADD CONSTRAINT dieukien_don_ibfk_1
  FOREIGN KEY (idDieuKien) REFERENCES dieukien(idDieuKien)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE tohop_dieukien
  ADD CONSTRAINT tohop_dieukien_ibfk_1
  FOREIGN KEY (idDieuKien) REFERENCES dieukien(idDieuKien)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE quyche_dieukien
  ADD CONSTRAINT quyche_dieukien_ibfk_1
  FOREIGN KEY (idQuyChe) REFERENCES quyche(idQuyChe)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE quyche_dieukien
  ADD CONSTRAINT quyche_dieukien_ibfk_2
  FOREIGN KEY (idDieuKienCuoi) REFERENCES dieukien(idDieuKien)
  ON DELETE CASCADE ON UPDATE RESTRICT;

COMMIT;