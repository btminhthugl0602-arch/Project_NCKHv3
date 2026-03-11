<?php
/**
 * API: Tạo tài khoản mới
 * POST /api/tai_khoan/tao_tai_khoan.php
 * Body: { "ten_dang_nhap", "mat_khau", "id_loai_tai_khoan", "ho_ten", "id_don_vi", "ma_so_sinh_vien", "hoc_ham" }
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_tai_khoan.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('quan_ly_tai_khoan');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$idNguoiTH = (int) ($_SESSION['idTK'] ?? 0);

$result = admin_tao_tai_khoan(
    $conn,
    $idNguoiTH,
    $body['ten_dang_nhap']     ?? '',
    $body['mat_khau']          ?? '',
    $body['id_loai_tai_khoan'] ?? 0,
    $body['ho_ten']            ?? '',
    $body['id_don_vi']         ?? 0,
    $body['ma_so_sinh_vien']   ?? ''
);

// Xử lý hoc_ham nếu là giảng viên (GV) — cập nhật sau khi tạo xong
if ($result['status'] && !empty($body['hoc_ham']) && (int)($body['id_loai_tai_khoan']) === 2) {
    $idTKMoi = (int) ($result['idTK'] ?? 0);
    if ($idTKMoi > 0) {
        $conn->prepare('UPDATE giangvien SET hocHam = :hocHam WHERE idTK = :idTK')
             ->execute([':hocHam' => $body['hoc_ham'], ':idTK' => $idTKMoi]);
    }
}

http_response_code($result['status'] ? 200 : 400);
echo json_encode([
    'status'  => $result['status'] ? 'success' : 'error',
    'message' => $result['message'],
    'data'    => $result['status'] ? ['idTK' => $result['idTK']] : null,
], JSON_UNESCAPED_UNICODE);
