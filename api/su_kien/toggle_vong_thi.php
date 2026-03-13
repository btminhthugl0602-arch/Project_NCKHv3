<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_vong_thi.php';
require_once __DIR__ . '/../thong_bao/notification_service.php';
/**
 * API Endpoint: Toggle đóng/mở nộp bài của vòng thi
 * Method: PUT/POST
 * 
 * Request body:
 * - id_vong_thi: int (required) - ID vòng thi
 */

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────
$_raw_body = file_get_contents('php://input');
$input = json_decode($_raw_body, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id_vong_thi_auth = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;
if ($id_vong_thi_auth <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID vòng thi không hợp lệ']);
    exit;
}
$_vong_thi_auth = lay_chi_tiet_vong_thi($conn, $id_vong_thi_auth);
if (!$_vong_thi_auth) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Vòng thi không tồn tại']);
    exit;
}
$actor = auth_require_quyen_su_kien((int)$_vong_thi_auth['idSK'], 'cauhinh_sukien');

// Chỉ chấp nhận PUT hoặc POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use PUT or POST.',
    ]);
    exit;
}

try {
    $id_nguoi_thuc_hien = $actor['idTK'];
    $id_vong_thi = $id_vong_thi_auth;

    // Gọi service function
    $result = toggle_dong_nop_vong_thi($conn, $id_nguoi_thuc_hien, $id_vong_thi);

    if ($result['status']) {
        if (notification_feature_enabled('event')) {
            try {
                $thongTinVong = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
                $tenVong = (string) ($thongTinVong['tenVongThi'] ?? ('Vong thi #' . $id_vong_thi));
                $idSK = (int) ($thongTinVong['idSK'] ?? 0);
                $dongNop = (int) ($result['dongNopThuCong'] ?? 0) === 1;
                $message = $dongNop
                    ? 'BTC da dong nop bai cho ' . $tenVong . '. Vui long theo doi thong bao tiep theo.'
                    : 'BTC da mo lai nop bai cho ' . $tenVong . '. Ban co the tiep tuc nop/cap nhat bai.';

                dispatch_group($conn, [
                    'tieuDe' => 'Cap nhat nop bai vong thi',
                    'noiDung' => $message,
                    'loaiThongBao' => 'SU_KIEN',
                    'idSK' => $idSK,
                    'loaiDoiTuong' => 'SANPHAM',
                    'nguoiGui' => $id_nguoi_thuc_hien,
                    'recipientGroups' => [
                        ['loaiNhom' => 'SU_KIEN', 'idNhom' => $idSK],
                    ],
                ]);
            } catch (Throwable $notifyError) {
                error_log('toggle_vong_thi notify error: ' . $notifyError->getMessage());
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'dongNopThuCong' => $result['dongNopThuCong'] ?? null,
            ],
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
        ]);
    }

} catch (Throwable $e) {
    error_log('API toggle_vong_thi error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.',
    ]);
}
