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

$input         = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom        = (int)    ($input['id_nhom']            ?? 0);
$chieuMoi      = (int)    ($input['chieu_moi']          ?? 1);
$loiNhan       = trim((string) ($input['loi_nhan']      ?? ''));
$idTKDoiPhuong = (int)    ($input['id_tk_doi_phuong']   ?? 0);
$loaiYeuCau    = trim((string) ($input['loai_yeu_cau']  ?? 'SV'));

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy idSK từ nhóm
$nhomCheck = lay_nhom_theo_id($conn, $idNhom);
if (!$nhomCheck) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$idSk = (int) $nhomCheck['idSK'];

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (không cần quyền sự kiện cụ thể)
$actor       = auth_require_login();
$idTKSession = $actor['idTK'];

// Tự xin vào: đối phương = chính mình
if ($chieuMoi === 1) {
    $idTKDoiPhuong = $idTKSession;
} elseif ($idTKDoiPhuong <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_tk_doi_phuong', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = gui_yeu_cau_nhom($conn, $idTKSession, $idNhom, $idTKDoiPhuong, $chieuMoi, $loaiYeuCau, $loiNhan);
    if ($result['status'] === true) {
        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
