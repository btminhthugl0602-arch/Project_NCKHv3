<?php

/**
 * Auth Guard — Middleware xác thực & phân quyền tập trung
 *
 * Cách dùng trong mỗi API endpoint:
 *
 *   require_once __DIR__ . '/../core/auth_guard.php';
 *
 *   // Chỉ cần đăng nhập
 *   $actor = auth_require_login();
 *
 *   // Cần quyền hệ thống (vd: quan_ly_tai_khoan, tao_su_kien)
 *   $actor = auth_require_quyen_he_thong('quan_ly_tai_khoan');
 *
 *   // Cần quyền trong sự kiện cụ thể
 *   $actor = auth_require_quyen_su_kien($idSK, 'cauhinh_sukien');
 *
 *   // Cần một trong nhiều quyền sự kiện
 *   $actor = auth_require_bat_ky_quyen_su_kien($idSK, ['cauhinh_sukien', 'xem_sukien']);
 *
 * $actor trả về array: ['idTK' => int, 'idLoaiTK' => int, 'hoTen' => string]
 *
 * Nếu không đủ quyền: tự động trả JSON lỗi + exit.
 * File này phải được require SAU base.php (cần $conn và session đã start).
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// ── Helper nội bộ: trả lỗi JSON và exit ─────────────────────
function _auth_fail(int $code, string $message): void
{
    http_response_code($code);
    // Đảm bảo header JSON đã được set (endpoint có thể set trước rồi)
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status'  => 'error',
        'message' => $message,
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Lấy actor từ session ─────────────────────────────────────
function _auth_get_actor(): ?array
{
    $idTK = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
    if ($idTK <= 0) {
        return null;
    }
    return [
        'idTK'     => $idTK,
        'idLoaiTK' => (int) ($_SESSION['idLoaiTK'] ?? 0),
        'hoTen'    => (string) ($_SESSION['hoTen']    ?? ''),
    ];
}

// ── 1. Chỉ cần đăng nhập ────────────────────────────────────
/**
 * Yêu cầu người dùng đã đăng nhập.
 * @return array actor ['idTK', 'idLoaiTK', 'hoTen']
 */
function auth_require_login(): array
{
    $actor = _auth_get_actor();
    if ($actor === null) {
        _auth_fail(401, 'Bạn chưa đăng nhập');
    }
    return $actor;
}

// ── 2. Quyền hệ thống ───────────────────────────────────────
/**
 * Yêu cầu quyền hệ thống cụ thể (phamVi = HE_THONG).
 * Admin (idLoaiTK=1) luôn pass qua kiem_tra_quyen_he_thong.
 *
 * @param string $maQuyen  vd: 'quan_ly_tai_khoan', 'tao_su_kien', 'xem_thong_ke'
 * @return array actor
 */
function auth_require_quyen_he_thong(string $maQuyen): array
{
    global $conn;
    $actor = auth_require_login();

    if (!kiem_tra_quyen_he_thong($conn, $actor['idTK'], $maQuyen)) {
        _auth_fail(403, 'Bạn không có quyền thực hiện thao tác này');
    }
    return $actor;
}

// ── 3. Quyền sự kiện ────────────────────────────────────────
/**
 * Yêu cầu quyền cụ thể trong một sự kiện (phamVi = SU_KIEN).
 *
 * @param int    $idSK     ID sự kiện
 * @param string $maQuyen  vd: 'cauhinh_sukien', 'cham_diem', 'xem_ket_qua'
 * @return array actor
 */
function auth_require_quyen_su_kien(int $idSK, string $maQuyen): array
{
    global $conn;
    $actor = auth_require_login();

    if ($idSK <= 0) {
        _auth_fail(400, 'Thiếu hoặc sai id sự kiện');
    }

    if (!kiem_tra_quyen_su_kien($conn, $actor['idTK'], $idSK, $maQuyen)) {
        _auth_fail(403, 'Bạn không có quyền thực hiện thao tác này trong sự kiện');
    }
    return $actor;
}

// ── 4. Bất kỳ quyền sự kiện nào trong danh sách ─────────────
/**
 * Yêu cầu ít nhất 1 trong các quyền sự kiện được liệt kê.
 * Dùng cho các trang xem chung (BTC + GV_PHAN_BIEN đều được xem).
 *
 * @param int      $idSK    ID sự kiện
 * @param string[] $maCodes Danh sách maQuyen
 * @return array actor
 */
function auth_require_bat_ky_quyen_su_kien(int $idSK, array $maCodes): array
{
    global $conn;
    $actor = auth_require_login();

    if ($idSK <= 0) {
        _auth_fail(400, 'Thiếu hoặc sai id sự kiện');
    }

    if (!kiem_tra_bat_ky_quyen_su_kien($conn, $actor['idTK'], $idSK, $maCodes)) {
        _auth_fail(403, 'Bạn không có quyền truy cập sự kiện này');
    }
    return $actor;
}

// ── 5. Admin hoặc BTC sự kiện ────────────────────────────────
/**
 * Cho phép: Admin hệ thống HOẶC người có quyền HE_THONG 'tao_su_kien'
 * HOẶC BTC của sự kiện cụ thể.
 * Dùng cho: xem danh sách, chi tiết sự kiện (đọc).
 *
 * @param int $idSK  0 = chỉ check quyền tao_su_kien, >0 = check cả BTC
 * @return array actor
 */
function auth_require_quan_ly_hoac_btc(int $idSK = 0): array
{
    global $conn;
    $actor = auth_require_login();

    // Có quyền hệ thống tao_su_kien (bao gồm Admin)
    if (kiem_tra_quyen_he_thong($conn, $actor['idTK'], 'tao_su_kien')) {
        return $actor;
    }

    // Là BTC của sự kiện cụ thể
    if ($idSK > 0 && la_btc_su_kien($conn, $actor['idTK'], $idSK)) {
        return $actor;
    }

    // Tham gia sự kiện với bất kỳ vai trò nào (để xem)
    if ($idSK > 0 && kiem_tra_bat_ky_quyen_su_kien($conn, $actor['idTK'], $idSK, [
        'cauhinh_sukien',
        'cham_diem',
        'xem_ket_qua',
        'tham_gia',
    ])) {
        return $actor;
    }

    _auth_fail(403, 'Bạn không có quyền truy cập');
    return []; // unreachable — để IDE không báo lỗi
}
