<?php

/**
 * API: Lấy toàn bộ dữ liệu cho tab Overview sự kiện
 * GET /api/su_kien/overview_su_kien.php?id_sk=X
 * Public — guest được xem, không cần quyền sự kiện
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_su_kien.php';
require_once __DIR__ . '/quan_ly_vong_thi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth: guest được phép ─────────────────────────────────
$_isGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!$_isGuest) {
    $actor = auth_require_login();
    $idTK  = $actor['idTK'];
} else {
    $idTK = 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ── 1. Chi tiết sự kiện ───────────────────────────────
    $suKien = btc_lay_chi_tiet_su_kien($conn, $idSk);
    if (!$suKien) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sự kiện', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 2. Trạng thái đăng ký ─────────────────────────────
    $now            = time();
    $ngayMo         = !empty($suKien['ngayMoDangKy'])   ? strtotime($suKien['ngayMoDangKy'])   : null;
    $ngayDong       = !empty($suKien['ngayDongDangKy']) ? strtotime($suKien['ngayDongDangKy']) : null;
    $dangMoDangKy   = ($ngayMo !== null && $ngayDong !== null && $now >= $ngayMo && $now <= $ngayDong);

    // ── 3. Trạng thái sự kiện ─────────────────────────────
    $trangThaiSK = lay_trang_thai_su_kien($conn, $idSk);

    $trangThaiLabel = match ($trangThaiSK) {
        'chua_bat_dau' => 'Chưa bắt đầu',
        'dang_dien_ra' => 'Đang diễn ra',
        'da_ket_thuc'  => 'Đã kết thúc',
        'bi_vo_hieu'   => 'Đã vô hiệu',
        default        => 'Không xác định',
    };

    // Trạng thái đăng ký label riêng (ưu tiên hiển thị)
    if (!$suKien['isActive']) {
        $dangKyLabel = 'Đã vô hiệu';
    } elseif ($dangMoDangKy) {
        $dangKyLabel = 'Đang mở đăng ký';
    } elseif ($ngayMo !== null && $now < $ngayMo) {
        $dangKyLabel = 'Chưa mở đăng ký';
    } else {
        $dangKyLabel = 'Đã đóng đăng ký';
    }

    // ── 4. User đã đăng ký chưa ──────────────────────────
    $daDangKy = false;
    $vaiTroCuaUser = null;
    if ($idTK > 0) {
        $stmt = $conn->prepare("
            SELECT v.maVaiTro
            FROM taikhoan_vaitro_sukien tvs
            JOIN vaitro v ON v.idVaiTro = tvs.idVaiTro
            WHERE tvs.idTK = ? AND tvs.idSK = ? AND tvs.isActive = 1
            LIMIT 1
        ");
        $stmt->execute([$idTK, $idSk]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $daDangKy      = true;
            $vaiTroCuaUser = $row['maVaiTro'];
        }
    }

    // ── 5. Thống kê ───────────────────────────────────────
    $thongKe = lay_thong_ke_su_kien($conn, $idSk);

    // Thêm số GV hướng dẫn
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT tvs.idTK)
        FROM taikhoan_vaitro_sukien tvs
        JOIN vaitro v ON v.idVaiTro = tvs.idVaiTro
        WHERE tvs.idSK = ? AND tvs.isActive = 1
          AND v.maVaiTro = 'GV_HUONG_DAN'
    ");
    $stmt->execute([$idSk]);
    $thongKe['so_gv_huong_dan'] = (int) $stmt->fetchColumn();

    // Tổng GV (idLoaiTK=2: giảng viên)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT tvs.idTK)
        FROM taikhoan_vaitro_sukien tvs
        JOIN taikhoan tk ON tk.idTK = tvs.idTK
        WHERE tvs.idSK = ? AND tvs.isActive = 1
          AND tk.idLoaiTK = 2
    ");
    $stmt->execute([$idSk]);
    $thongKe['so_giang_vien'] = (int) $stmt->fetchColumn();

    // ── 6. Vòng thi ───────────────────────────────────────
    $vongThis = lay_ds_vong_thi($conn, $idSk);

    // ── 7. Chủ đề ─────────────────────────────────────────
    // Đọc qua chude_sukien — bảng trung gian chính tắc gán chủ đề vào sự kiện.
    // chude là ngân hàng chung, không query trực tiếp theo idSK.
    $stmt = $conn->prepare("
        SELECT c.idChuDe, c.tenChuDe
        FROM chude_sukien cs
        JOIN chude c ON c.idChuDe = cs.idchude
        WHERE cs.idSK = ? AND cs.isActive = 1
        ORDER BY cs.idChuDeSK ASC
    ");
    $stmt->execute([$idSk]);
    $chuDes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Response ──────────────────────────────────────────
    echo json_encode([
        'status'  => 'success',
        'message' => 'OK',
        'data'    => [
            'su_kien'        => [
                'idSK'              => (int) $suKien['idSK'],
                'tenSK'             => $suKien['tenSK'],
                'moTa'              => $suKien['moTa'] ?? '',
                'tenCap'            => $suKien['tenCap'] ?? null,
                'ngayMoDangKy'      => $suKien['ngayMoDangKy'],
                'ngayDongDangKy'    => $suKien['ngayDongDangKy'],
                'ngayBatDau'        => $suKien['ngayBatDau'],
                'ngayKetThuc'       => $suKien['ngayKetThuc'],
                'isActive'          => (int) $suKien['isActive'],
            ],
            'trang_thai'     => [
                'ma'            => $trangThaiSK,
                'nhan'          => $trangThaiLabel,
                'dang_mo_dk'    => $dangMoDangKy,
                'dk_label'      => $dangKyLabel,
            ],
            'dang_ky'        => [
                'da_dang_ky'    => $daDangKy,
                'vai_tro'       => $vaiTroCuaUser,
            ],
            'thong_ke'       => $thongKe,
            'vong_thi'       => $vongThis,
            'chu_de'         => $chuDes,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}