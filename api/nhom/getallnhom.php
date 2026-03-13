<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

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

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (không cần quyền sự kiện cụ thể)
$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $nhoms        = lay_tat_ca_nhom($conn, $idSk);
    $userHasGroup = kiem_tra_user_co_nhom($conn, $idTK, $idSk);

    // Lấy loại tài khoản để frontend phân biệt SV / GV
    $tk       = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    $loaiTK   = $tk ? (int) $tk['idLoaiTK'] : 0;

    // Với GV: đếm số nhóm đang hướng dẫn để frontend check giới hạn
    $soNhomHuongDan = ($loaiTK === 2) ? so_nhom_gv_huong_dan($conn, $idTK, $idSk) : null;

    echo json_encode([
        'status'                  => 'success',
        'message'                 => 'Lấy danh sách nhóm thành công',
        'data'                    => $nhoms,
        'user_has_group'          => $userHasGroup,
        'user_loai_tk'            => $loaiTK,
        'user_so_nhom_huong_dan'  => $soNhomHuongDan,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
