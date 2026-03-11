<?php
/**
 * cap_nhat_form_field.php
 * POST { action: 'cap_nhat' | 'xoa' | 'toggle' | 'sap_xep' | 'copy', ...payload }
 */
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_form_field.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim((string) ($input['action'] ?? ''));

try {
    switch ($action) {

        // ── Cập nhật nội dung field ─────────────────────────
        case 'cap_nhat': {
            $idField = (int) ($input['id_field'] ?? 0);
            if ($idField <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_field', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = cap_nhat_form_field($conn, $idTK, $idField, $input);
            break;
        }

        // ── Xóa field ───────────────────────────────────────
        case 'xoa': {
            $idField = (int) ($input['id_field'] ?? 0);
            if ($idField <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_field', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = xoa_form_field($conn, $idTK, $idField);
            break;
        }

        // ── Ẩn/hiện field ──────────────────────────────────
        case 'toggle': {
            $idField = (int) ($input['id_field'] ?? 0);
            if ($idField <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_field', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = toggle_form_field($conn, $idTK, $idField);
            break;
        }

        // ── Sắp xếp lại thuTu ──────────────────────────────
        case 'sap_xep': {
            $idSK  = (int) ($input['id_sk'] ?? 0);
            $order = $input['order'] ?? [];
            if ($idSK <= 0 || !is_array($order)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk hoặc order', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = sap_xep_form_field($conn, $idTK, $idSK, $order);
            break;
        }

        // ── Copy form sang vòng thi khác ────────────────────
        case 'copy': {
            $idSK      = (int) ($input['id_sk'] ?? 0);
            $srcVT     = isset($input['src_vong_thi']) && $input['src_vong_thi'] !== null
                ? (int) $input['src_vong_thi'] : null;
            $dstVT     = isset($input['dst_vong_thi']) && $input['dst_vong_thi'] !== null
                ? (int) $input['dst_vong_thi'] : null;
            $mode      = (string) ($input['mode'] ?? 'them_vao');

            if ($idSK <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = copy_form_field($conn, $idTK, $idSK, $srcVT, $dstVT, $mode);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
            exit;
    }

    if ($result['status'] === true) {
        echo json_encode(['status' => 'success', 'message' => $result['message'], 'data' => $result], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => $result], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
