<?php

define('_AUTHEN', true);
/**
 * API Endpoint: Sắp xếp thứ tự vòng thi
 * Method: PUT/POST
 * 
 * Request body:
 * - id_su_kien: int (required) - ID sự kiện
 * - thu_tu_moi: array (required) - Mảng { "idVongThi": thuTuMoi, ... }
 *   Ví dụ: { "1": 1, "2": 3, "3": 2 }
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
    $id_su_kien = isset($input['id_su_kien']) ? (int) $input['id_su_kien'] : 0;
    $thu_tu_moi = isset($input['thu_tu_moi']) ? $input['thu_tu_moi'] : [];

    if ($id_su_kien <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID sự kiện không hợp lệ',
        ]);
        exit;
    }

    if (!is_array($thu_tu_moi) || empty($thu_tu_moi)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Dữ liệu thứ tự vòng thi không hợp lệ',
        ]);
        exit;
    }

    // Gọi service function
    $conn = _connect();
    $result = sap_xep_thu_tu_vong_thi($conn, $id_nguoi_thuc_hien, $id_su_kien, $thu_tu_moi);

    if ($result['status']) {
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'updatedCount' => $result['updatedCount'] ?? 0,
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
    error_log('API sap_xep_vong_thi error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.',
    ]);
}
