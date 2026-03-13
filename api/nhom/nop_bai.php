<?php
/**
 * @deprecated DEPRECATED — File này không còn được sử dụng.
 * Dùng api/nhom/san_pham.php thay thế.
 * File giữ lại để tham khảo, không xóa để tránh ảnh hưởng đến các reference cũ.
 */
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

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom    = (int)    ($input['id_nhom']         ?? 0);
$tenDeTai  = trim((string) ($input['ten_de_tai'] ?? ''));

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tenDeTai === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tên đề tài', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth ──────────────────────────────────────────────────
// Chỉ yêu cầu đăng nhập (quyền đã được gán qua role)
$actor = auth_require_login();
$idTK  = $actor['idTK'];
$idSk  = (int) ($nhomCheck['idSK'] ?? 0);

try {
    $idChuDeSK = isset($input['id_chu_de_sk']) ? (int) $input['id_chu_de_sk'] : null;
    $result = tao_hoac_cap_nhat_san_pham($conn, $idTK, $idNhom, $tenDeTai, $idChuDeSK ?: null);

    if ($result['status'] === true) {
        if (notification_feature_enabled('group')) {
            try {
                dispatch_group($conn, [
                    'tieuDe' => 'Nhom da nop/cap nhat bai',
                    'noiDung' => 'Mot nhom vua nop hoac cap nhat san pham. Vui long kiem tra trong su kien.',
                    'loaiThongBao' => 'NHOM',
                    'idSK' => $idSk,
                    'loaiDoiTuong' => 'SANPHAM',
                    'nguoiGui' => $idTK,
                    'recipientGroups' => [
                        ['loaiNhom' => 'SU_KIEN', 'idNhom' => $idSk, 'idVaiTro' => 1],
                    ],
                ]);
            } catch (Throwable $notifyError) {
                error_log('nop_bai notify error: ' . $notifyError->getMessage());
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
