-- ============================================================
-- Migration: Tạo VIEW v_giam_khao_san_pham
-- Mục đích  : Gộp 2 nguồn giám khảo (phancong_doclap và
--             chamtieuchi) vào 1 VIEW để tránh lặp UNION inline.
-- Áp dụng   : 2026-03-10 (Phase 4 – LOGIC-5)
-- ============================================================

CREATE OR REPLACE VIEW v_giam_khao_san_pham AS
SELECT
    allGK.idSanPham,
    allGK.idGV,
    allGK.idVongThi,
    allGK.nguon,
    gv.tenGV,
    tk.tenTK,
    COALESCE(pd.isTrongTai, 0) AS isTrongTai
FROM (
    -- Nguồn 1: phân công chính thức (phancong_doclap)
    SELECT pd_inner.idSanPham,
           pd_inner.idGV,
           pd_inner.idVongThi,
           'phancong' AS nguon
    FROM phancong_doclap pd_inner

    UNION

    -- Nguồn 2: giám khảo đã chấm điểm thực tế (chamtieuchi / legacy)
    SELECT DISTINCT ct.idSanPham,
                    pcc.idGV,
                    pcc.idVongThi,
                    'chamtieuchi' AS nguon
    FROM chamtieuchi ct
    INNER JOIN phancongcham pcc ON ct.idPhanCongCham = pcc.idPhanCongCham
) AS allGK
INNER JOIN giangvien gv ON gv.idGV    = allGK.idGV
INNER JOIN taikhoan  tk ON tk.idTK     = gv.idTK
LEFT  JOIN phancong_doclap pd
       ON  pd.idGV       = allGK.idGV
       AND pd.idSanPham  = allGK.idSanPham
       AND pd.idVongThi  = allGK.idVongThi;
