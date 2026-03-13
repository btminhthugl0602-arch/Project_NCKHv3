-- 2026-03-13
-- Verify script for Phase 1 (rule tree integrity)
-- Expectation: all orphan counters must be 0

SELECT 'dieukien_don_without_dieukien' AS check_name, COUNT(*) AS orphan_count
FROM dieukien_don dd
LEFT JOIN dieukien d ON d.idDieuKien = dd.idDieuKien
WHERE d.idDieuKien IS NULL

UNION ALL

SELECT 'tohop_without_dieukien_self' AS check_name, COUNT(*) AS orphan_count
FROM tohop_dieukien th
LEFT JOIN dieukien d ON d.idDieuKien = th.idDieuKien
WHERE d.idDieuKien IS NULL

UNION ALL

SELECT 'tohop_left_child_missing' AS check_name, COUNT(*) AS orphan_count
FROM tohop_dieukien th
LEFT JOIN dieukien dl ON dl.idDieuKien = th.idDieuKienTrai
WHERE dl.idDieuKien IS NULL

UNION ALL

SELECT 'tohop_right_child_missing' AS check_name, COUNT(*) AS orphan_count
FROM tohop_dieukien th
LEFT JOIN dieukien dr ON dr.idDieuKien = th.idDieuKienPhai
WHERE dr.idDieuKien IS NULL

UNION ALL

SELECT 'quyche_dieukien_without_quyche' AS check_name, COUNT(*) AS orphan_count
FROM quyche_dieukien qd
LEFT JOIN quyche q ON q.idQuyChe = qd.idQuyChe
WHERE q.idQuyChe IS NULL

UNION ALL

SELECT 'quyche_dieukien_root_missing' AS check_name, COUNT(*) AS orphan_count
FROM quyche_dieukien qd
LEFT JOIN dieukien d ON d.idDieuKien = qd.idDieuKienCuoi
WHERE d.idDieuKien IS NULL;

SELECT
    CASE
        WHEN (
            (SELECT COUNT(*) FROM dieukien_don dd LEFT JOIN dieukien d ON d.idDieuKien = dd.idDieuKien WHERE d.idDieuKien IS NULL)
          + (SELECT COUNT(*) FROM tohop_dieukien th LEFT JOIN dieukien d ON d.idDieuKien = th.idDieuKien WHERE d.idDieuKien IS NULL)
          + (SELECT COUNT(*) FROM tohop_dieukien th LEFT JOIN dieukien dl ON dl.idDieuKien = th.idDieuKienTrai WHERE dl.idDieuKien IS NULL)
          + (SELECT COUNT(*) FROM tohop_dieukien th LEFT JOIN dieukien dr ON dr.idDieuKien = th.idDieuKienPhai WHERE dr.idDieuKien IS NULL)
          + (SELECT COUNT(*) FROM quyche_dieukien qd LEFT JOIN quyche q ON q.idQuyChe = qd.idQuyChe WHERE q.idQuyChe IS NULL)
          + (SELECT COUNT(*) FROM quyche_dieukien qd LEFT JOIN dieukien d ON d.idDieuKien = qd.idDieuKienCuoi WHERE d.idDieuKien IS NULL)
        ) = 0
        THEN 'PASS'
        ELSE 'FAIL'
    END AS verify_result;
