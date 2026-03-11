-- ============================================================
-- Migration: Cập nhật hệ thống quyền nhóm thi
-- Ngày: 2026-03-08
-- Mô tả:
--   - Xóa quyền cũ: quan_ly_nhom, cauhinh_vongthi, cauhinh_tailieu
--   - Xóa gán quyền thừa: THAM_GIA→nop_san_pham
--   - Thêm quyền mới: xem_nhom, tao_nhom
--   - Gán quyền mới cho THAM_GIA và GV_HUONG_DAN
-- ============================================================

START TRANSACTION;

-- ── 1. Xóa khỏi vaitro_quyen trước (tránh lỗi FK) ───────────

-- BTC: xóa cauhinh_vongthi (25), cauhinh_tailieu (26)
DELETE FROM `vaitro_quyen` WHERE `idVaiTro` = 1 AND `idQuyen` IN (25, 26);

-- THAM_GIA: xóa quan_ly_nhom (33), nop_san_pham (30)
DELETE FROM `vaitro_quyen` WHERE `idVaiTro` = 4 AND `idQuyen` IN (33, 30);

-- ── 2. Xóa quyền cũ khỏi quyen ───────────────────────────────

-- quan_ly_nhom, cauhinh_vongthi, cauhinh_tailieu
DELETE FROM `quyen` WHERE `idQuyen` IN (25, 26, 33);

-- ── 3. Thêm quyền mới ─────────────────────────────────────────

INSERT INTO `quyen` (`idQuyen`, `maQuyen`, `tenQuyen`, `moTa`, `phamVi`) VALUES
(42, 'xem_nhom', 'Xem nhóm thi', 'Xem danh sách nhóm, lời mời, và nhóm đang tham gia', 'SU_KIEN'),
(43, 'tao_nhom', 'Tạo nhóm thi', 'Tạo nhóm mới trong sự kiện', 'SU_KIEN');

-- ── 4. Gán quyền mới vào vaitro_quyen ────────────────────────

-- THAM_GIA (4): xem_nhom, tao_nhom
INSERT INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`) VALUES
(4, 42),
(4, 43);

-- GV_HUONG_DAN (3): xem_nhom, tao_nhom
INSERT INTO `vaitro_quyen` (`idVaiTro`, `idQuyen`) VALUES
(3, 42),
(3, 43);

COMMIT;

-- ── Verify ───────────────────────────────────────────────────
-- Chạy sau migration để kiểm tra:
--
-- SELECT v.maVaiTro, q.maQuyen
-- FROM vaitro_quyen vq
-- JOIN vaitro v ON v.idVaiTro = vq.idVaiTro
-- JOIN quyen q ON q.idQuyen = vq.idQuyen
-- ORDER BY v.idVaiTro, q.maQuyen;
