<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_quy_che.php';

header('Content-Type: application/json; charset=utf-8');

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

$idSkRequest = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_cauhinh_su_kien($idSkRequest);

$idQuyChe = isset($input['id_quy_che']) ? (int) $input['id_quy_che'] : 0;
if ($idQuyChe <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai id_quy_che',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idSkRequest <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu id_sk để xác thực ngữ cảnh sự kiện',
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
        'message' => 'Bạn chưa đăng nhập hoặc thiếu thông tin người thực hiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $quyChe = truy_van_mot_ban_ghi($conn, 'quyche', 'idQuyChe', $idQuyChe);
    if (!$quyChe) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Quy chế không tồn tại',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idSk = (int) ($quyChe['idSK'] ?? 0);
    if ($idSk !== $idSkRequest) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Quy chế không thuộc sự kiện hiện tại',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không có quyền xóa quy chế',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
        _delete_info($conn, 'quyche_ngucanh_apdung', ['idQuyChe' => ['=', $idQuyChe, '']]);
    }

    _delete_info($conn, 'quyche_dieukien', ['idQuyChe' => ['=', $idQuyChe, '']]);
    $ok = _delete_info($conn, 'quyche', ['idQuyChe' => ['=', $idQuyChe, '']]);

    if (!$ok) {
        throw new RuntimeException('Không thể xóa quy chế');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Đã xóa quy chế',
        'data' => ['idQuyChe' => $idQuyChe],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi khi xóa quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
