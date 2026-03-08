<?php

define('_AUTHEN', true);
/**
 * API Endpoint: Cập nhật vòng thi
 * Method: PUT/POST
 * 
 * Request body:
 * - id_vong_thi: int (required) - ID vòng thi cần cập nhật
 * - ten_vong: string (required) - Tên vòng thi
 * - mo_ta: string - Mô tả vòng thi
 * - ngay_bat_dau: string - Ngày bắt đầu (YYYY-MM-DD)
 * - ngay_ket_thuc: string - Ngày kết thúc (YYYY-MM-DD)
 * - thu_tu: int - Thứ tự vòng thi
 */

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('tao_su_kien');

require_once __DIR__ . '/quan_ly_vong_thi.php';
require_once __DIR__ . '/../core/session_helper.php';

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
    // Lấy thông tin người dùng từ session
    $session_user = get_current_user_from_session();
    $id_nguoi_thuc_hien = $session_user['idTK'] ?? 0;

    if ($id_nguoi_thuc_hien <= 0) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Vui lòng đăng nhập để thực hiện thao tác này',
        ]);
        exit;
    }

    // Parse request body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // Validate required fields
    $id_vong_thi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;
    $ten_vong = isset($input['ten_vong']) ? trim($input['ten_vong']) : '';

    if ($id_vong_thi <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID vòng thi không hợp lệ',
        ]);
        exit;
    }

    if ($ten_vong === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Tên vòng thi không được để trống',
        ]);
        exit;
    }

    // Lấy các tham số khác
    $mo_ta = $input['mo_ta'] ?? '';
    $ngay_bd = $input['ngay_bat_dau'] ?? null;
    $ngay_kt = $input['ngay_ket_thuc'] ?? null;
    $thu_tu = isset($input['thu_tu']) ? (int) $input['thu_tu'] : null;

    // Gọi service function
    $conn = _connect();
    $result = cap_nhat_vong_thi(
        $conn,
        $id_nguoi_thuc_hien,
        $id_vong_thi,
        $ten_vong,
        $mo_ta,
        $ngay_bd,
        $ngay_kt,
        $thu_tu
    );

    if ($result['status']) {
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'warnings' => $result['warnings'] ?? [],
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
    error_log('API cap_nhat_vong_thi error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.',
    ]);
}
