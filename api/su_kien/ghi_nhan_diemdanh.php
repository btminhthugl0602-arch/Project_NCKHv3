<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_to_chuc.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ cần đăng nhập
$actor = auth_require_login();
$idTkLogin = $actor['idTK'];

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

// Resolve phiên: có thể truyền id_phien_dd trực tiếp hoặc qua token QR
$idPhienDD  = 0;
$phuongThuc = strtoupper(trim($input['phuong_thuc'] ?? 'THU_CONG'));

if (!empty($input['token'])) {
    // QR flow: verify token → lấy id_phien_dd
    $tokenRaw = (string) $input['token'];
    // Token encode idPhienDD—thoiGianMo vào trong HMAC
    // Client phải truyền cả id_phien_dd kèm token
    $idPhienDD = (int) ($input['id_phien_dd'] ?? 0);
    if ($idPhienDD <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Thiếu id_phien_dd'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $idPhienDD);
    if (!$phien || !xac_thuc_token_qr($tokenRaw, $idPhienDD, $phien['thoiGianMo'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'QR code không hợp lệ hoặc đã hết hạn'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $phuongThuc = 'QR';
} else {
    $idPhienDD = (int) ($input['id_phien_dd'] ?? 0);
}

// Ai được điểm: BTC có thể truyền id_tk_sv, còn lại tự điểm danh chính mình
$idTkSV = $idTkLogin; // default: tự điểm
$idSk   = (int) ($input['id_sk'] ?? 0);

if (!empty($input['id_tk_sv']) && (int) $input['id_tk_sv'] !== $idTkLogin) {
    // BTC điểm hộ — cần cauhinh_sukien
    if ($idSk <= 0 || !co_quyen_to_chuc_su_kien($conn, $idTkLogin, $idSk)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Chỉ BTC mới có thể điểm danh hộ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $idTkSV = (int) $input['id_tk_sv'];
}

$result = ghi_nhan_diem_danh(
    $conn,
    $idTkLogin,
    $idTkSV,
    $idPhienDD,
    $phuongThuc,
    isset($input['hien_dien']) ? (int) $input['hien_dien'] : 1,
    $input['ghi_chu']   ?? '',
    !empty($input['id_nhom']) ? (int) $input['id_nhom'] : null,
    $input['vi_tri_lat'] ?? null,
    $input['vi_tri_lng'] ?? null
);

$ok = $result['status'] ?? false;
http_response_code($ok ? 200 : 400);
echo json_encode([
    'status'  => $ok ? 'success' : 'error',
    'message' => $result['message'] ?? '',
    'data'    => $result,
], JSON_UNESCAPED_UNICODE);
