<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';
require_once __DIR__ . '/../thong_bao/notification_service.php';

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

if ($loaiYeuCau === 'GVHD') {
    $suKien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $idSk);
    $coGVHDTheoSuKien = $suKien ? (int) ($suKien['coGVHDTheoSuKien'] ?? 1) : 1;
    if ($coGVHDTheoSuKien !== 1) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Sự kiện này không áp dụng luồng giảng viên hướng dẫn (GVHD)',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

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
        if (notification_feature_enabled('group')) {
            try {
                // chieu_moi = 1: user tu xin vao nhom -> thong bao cho Chu nhom
                // chieu_moi = 0: nhom moi user             -> thong bao cho user duoc moi
                $recipients = [];
                if ($chieuMoi === 1) {
                    $idChuNhom = (int) ($nhomCheck['idChuNhom'] ?? 0);
                    $idTruongNhom = (int) ($nhomCheck['idTruongNhom'] ?? 0);
                    if ($idChuNhom > 0) {
                        $recipients[] = $idChuNhom;
                    }
                    if ($idTruongNhom > 0) {
                        $recipients[] = $idTruongNhom;
                    }
                    $recipients = array_values(array_unique($recipients));
                } else {
                    $recipients = [$idTKDoiPhuong];
                }

                $tieuDe = $chieuMoi === 1 ? 'Co yeu cau xin tham gia nhom' : 'Loi moi tham gia nhom';
                $noiDung = $chieuMoi === 1
                    ? 'Nhom cua ban vua nhan mot yeu cau tham gia. Vui long vao muc Yeu cau de xu ly.'
                    : 'Ban nhan duoc loi moi tham gia nhom. Vui long xem va phan hoi.';

                if (!empty($recipients)) {
                    dispatch_personal($conn, [
                        'tieuDe' => $tieuDe,
                        'noiDung' => $noiDung,
                        'loaiThongBao' => 'NHOM',
                        'idSK' => $idSk,
                        'loaiDoiTuong' => 'YEUCAU',
                        'nguoiGui' => $idTKSession,
                        'recipients' => $recipients,
                    ]);
                }
            } catch (Throwable $notifyError) {
                error_log('gui_yeu_cau notify error: ' . $notifyError->getMessage());
            }
        }

        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
