<?php
/**
 * API Xóa bộ tiêu chí
 * Method: POST
 * Body: { id_sk: int, id_bo: int }
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_bo_tieu_chi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_quyen_he_thong('tao_su_kien');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($input)) {
    $input = [];
}

$idSk = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
$idBo = isset($input['id_bo']) ? (int) $input['id_bo'] : 0;

if ($idSk <= 0 || $idBo <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu id_sk hoặc id_bo',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = isset($_SESSION['idTK']) ? (int) $_SESSION['idTK'] : 0;
if ($idUser <= 0 && isset($input['id_nguoi_thuc_hien'])) {
    $idUser = (int) $input['id_nguoi_thuc_hien'];
}

if ($idUser <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = xoa_bo_tieu_chi($conn, $idUser, $idSk, $idBo);

    if (empty($result['status'])) {
        $responseCode = isset($result['hasRelatedData']) && $result['hasRelatedData'] ? 409 : 400;
        http_response_code($responseCode);
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'] ?? 'Không thể xóa bộ tiêu chí',
            'hasRelatedData' => $result['hasRelatedData'] ?? false,
            'relatedData' => $result['relatedData'] ?? [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => $result['message'] ?? 'Đã xóa bộ tiêu chí thành công',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi hệ thống khi xóa bộ tiêu chí',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
