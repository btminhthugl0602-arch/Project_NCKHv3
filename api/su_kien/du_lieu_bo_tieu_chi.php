<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

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

$idUser = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
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
    $warnings = [];

    $rounds = lay_danh_sach_vong_thi_theo_su_kien($conn, $idUser, $idSk);
    if (empty($rounds['status'])) {
        $warnings['vong_thi'] = $rounds['message'] ?? 'Không thể lấy danh sách vòng thi';
    }

    $criteriaBank = lay_ngan_hang_tieu_chi($conn, $idUser, $idSk);
    if (empty($criteriaBank['status'])) {
        $warnings['ngan_hang_tieu_chi'] = $criteriaBank['message'] ?? 'Không thể lấy ngân hàng tiêu chí';
    }

    $sets = lay_danh_sach_bo_tieu_chi($conn, $idUser, $idSk);
    if (empty($sets['status'])) {
        $warnings['bo_tieu_chi'] = $sets['message'] ?? 'Không thể lấy danh sách bộ tiêu chí';
    }

    $usageMap = lay_ban_do_su_dung_bo_tieu_chi($conn, $idUser, $idSk);
    if (empty($usageMap['status'])) {
        $warnings['usage_map'] = $usageMap['message'] ?? 'Không thể lấy bản đồ sử dụng';
    }

    $hasAnyData =
        !empty($rounds['data']) ||
        !empty($criteriaBank['data']) ||
        !empty($sets['data']) ||
        !empty($usageMap['data']);

    $message = empty($warnings)
        ? 'Lấy dữ liệu bộ tiêu chí thành công'
        : ($hasAnyData
            ? 'Lấy dữ liệu bộ tiêu chí thành công (một phần)'
            : 'Không thể lấy dữ liệu bộ tiêu chí');

    echo json_encode([
        'status' => ($hasAnyData || empty($warnings)) ? 'success' : 'error',
        'message' => $message,
        'data' => [
            'vong_thi' => !empty($rounds['status']) ? ($rounds['data'] ?? []) : [],
            'ngan_hang_tieu_chi' => !empty($criteriaBank['status']) ? ($criteriaBank['data'] ?? []) : [],
            'bo_tieu_chi' => !empty($sets['status']) ? ($sets['data'] ?? []) : [],
            'usage_map' => !empty($usageMap['status']) ? ($usageMap['data'] ?? []) : [],
            'warnings' => $warnings,
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
