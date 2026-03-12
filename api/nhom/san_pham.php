<?php
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

// ── GET — trả về form mặc định SK để modal nhóm render ────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idNhom = (int) ($_GET['id_nhom'] ?? 0);
    if ($idNhom <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $nhom = lay_nhom_theo_id($conn, $idNhom);
    if (!$nhom) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $actor = auth_require_login();
    $idTK  = $actor['idTK'];
    $idSK  = (int) $nhom['idSK'];

    // Chỉ thành viên nhóm mới được xem
    if (!la_thanh_vien_sv($conn, $idTK, $idNhom) && !la_truong_nhom($conn, $idTK, $idNhom) && !la_chu_nhom($conn, $idTK, $idNhom)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bạn không phải thành viên nhóm này', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $formFields  = lay_form_mac_dinh_sk($conn, $idSK);
    $sanpham     = lay_san_pham_nhom($conn, $idNhom, $idSK);
    $daNopValues = [];
    if ($sanpham && !empty($formFields)) {
        $rawValues = lay_field_value_mac_dinh($conn, (int) $sanpham['idSanPham']);
        foreach ($rawValues as $val) {
            $daNopValues[(int) $val['idField']] = $val;
        }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => '',
        'data'    => [
            'formFields'  => $formFields,
            'daNopValues' => $daNopValues,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST — tạo hoặc cập nhật sản phẩm ─────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true) ?? [];
$idNhom      = (int) ($input['id_nhom'] ?? 0);
$tenSanPham  = trim((string) ($input['ten_san_pham'] ?? ''));
$idChuDeSK   = isset($input['id_chu_de_sk']) && $input['id_chu_de_sk'] !== null
    ? (int) $input['id_chu_de_sk']
    : null;
$fieldValues = (array) ($input['field_values'] ?? []);

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$nhomCheck = lay_nhom_theo_id($conn, $idNhom);
if (!$nhomCheck) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

try {
    $result = tao_hoac_cap_nhat_san_pham($conn, $idTK, $idNhom, $tenSanPham, $idChuDeSK, $fieldValues);

    if ($result['status'] === true) {
        echo json_encode([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => ['idSanPham' => $result['idSanPham']],
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message'], 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
