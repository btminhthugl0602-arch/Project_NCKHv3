<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$idYeuCau = (int) ($input['id_yeu_cau'] ?? 0);

if ($idYeuCau <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_yeu_cau', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

// Kiểm tra yêu cầu tồn tại, thuộc về người dùng này, và đang pending
$yc = truy_van_mot_ban_ghi($conn, 'yeucau_thamgia', 'idYeuCau', $idYeuCau);

if (!$yc) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ người gửi mới được rút
if ((int) $yc['idTK'] !== $idTK) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền rút yêu cầu này', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ rút được yêu cầu tự gửi đi (ChieuMoi=1), không rút lời mời của nhóm
if ((int) $yc['ChieuMoi'] !== 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Không thể rút lời mời do nhóm gửi', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ rút được khi đang pending
if ((int) $yc['trangThai'] !== 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu đã được xử lý, không thể rút', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare('DELETE FROM yeucau_thamgia WHERE idYeuCau = :idYeuCau');
    $stmt->execute([':idYeuCau' => $idYeuCau]);
    echo json_encode(['status' => 'success', 'message' => 'Đã rút yêu cầu thành công', 'data' => null], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
