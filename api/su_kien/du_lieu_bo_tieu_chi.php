<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai tham số id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idUser <= 0 && isset($_GET['id_nguoi_thuc_hien'])) {
    $idUser = (int) $_GET['id_nguoi_thuc_hien'];
}

if ($idUser <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rounds = lay_danh_sach_vong_thi_theo_su_kien($conn, $idUser, $idSk);
    if (empty($rounds['status'])) {
        throw new RuntimeException($rounds['message'] ?? 'Không thể lấy danh sách vòng thi');
    }

    $criteriaBank = lay_ngan_hang_tieu_chi($conn, $idUser, $idSk);
    if (empty($criteriaBank['status'])) {
        throw new RuntimeException($criteriaBank['message'] ?? 'Không thể lấy ngân hàng tiêu chí');
    }

    // Toàn bộ ngân hàng — dùng cho dropdown nhân bản
    $setsAll = lay_danh_sach_bo_tieu_chi($conn, $idUser, $idSk, false);
    if (empty($setsAll['status'])) {
        throw new RuntimeException($setsAll['message'] ?? 'Không thể lấy danh sách bộ tiêu chí');
    }

    // Chỉ bộ tiêu chí của sự kiện này — dùng cho panel quản lý bên phải
    $setsSuKien = lay_danh_sach_bo_tieu_chi($conn, $idUser, $idSk, true);
    if (empty($setsSuKien['status'])) {
        throw new RuntimeException($setsSuKien['message'] ?? 'Không thể lấy danh sách bộ tiêu chí theo sự kiện');
    }

    $usageMap = lay_ban_do_su_dung_bo_tieu_chi($conn, $idUser, $idSk);
    if (empty($usageMap['status'])) {
        throw new RuntimeException($usageMap['message'] ?? 'Không thể lấy bản đồ sử dụng');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy dữ liệu bộ tiêu chí thành công',
        'data' => [
            'vong_thi'          => $rounds['data'] ?? [],
            'ngan_hang_tieu_chi' => $criteriaBank['data'] ?? [],
            'bo_tieu_chi'       => $setsSuKien['data'] ?? [],   // panel sự kiện
            'bo_tieu_chi_all'   => $setsAll['data'] ?? [],      // dropdown nhân bản
            'usage_map'         => $usageMap['data'] ?? [],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi lấy dữ liệu bộ tiêu chí',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
