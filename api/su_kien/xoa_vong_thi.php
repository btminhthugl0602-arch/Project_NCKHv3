<?php

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_vong_thi.php';
/**
 * API Endpoint: Xóa vòng thi
 * Method: DELETE/POST
 * 
 * Request:
 * - id_vong_thi: int (required) - ID vòng thi cần xóa
 */

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('tao_su_kien');

// Chấp nhận DELETE hoặc POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use DELETE or POST.',
    ]);
    exit;
}

try {
    $id_nguoi_thuc_hien = $actor['idTK'];

    // Parse request
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = array_merge($_GET, $_POST);
    }

    $id_vong_thi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;

    if ($id_vong_thi <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID vòng thi không hợp lệ',
        ]);
        exit;
    }

    // Gọi service function
    $result = xoa_vong_thi($conn, $id_nguoi_thuc_hien, $id_vong_thi);

    if ($result['status']) {
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
        ]);
    } else {
        $response = [
            'status' => 'error',
            'message' => $result['message'],
        ];

        // Thêm thông tin dữ liệu liên quan nếu có
        if (!empty($result['hasRelatedData'])) {
            $response['hasRelatedData'] = true;
            $response['relatedData'] = $result['relatedData'] ?? [];
        }

        http_response_code(400);
        echo json_encode($response);
    }

} catch (Throwable $e) {
    error_log('API xoa_vong_thi error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.',
    ]);
}
