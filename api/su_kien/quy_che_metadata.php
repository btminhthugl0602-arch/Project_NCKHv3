<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_quy_che.php';


header('Content-Type: application/json; charset=utf-8');

function context_defaults_for_ui()
{
    return [
        ['maNguCanh' => 'DANG_KY_THAM_GIA_SV', 'tenNguCanh' => 'Dang ky tham gia (Sinh vien)', 'moTa' => 'Ap dung khi sinh vien dang ky vao su kien', 'isHeThong' => 1],
        ['maNguCanh' => 'DANG_KY_THAM_GIA_GV', 'tenNguCanh' => 'Dang ky tham gia (Giang vien)', 'moTa' => 'Ap dung khi giang vien dang ky vao su kien', 'isHeThong' => 1],
        ['maNguCanh' => 'DUYET_VONG_THI', 'tenNguCanh' => 'Duyet ket qua vong thi', 'moTa' => 'Ap dung khi BTC duyet diem va chot trang thai san pham trong vong thi', 'isHeThong' => 1],
        ['maNguCanh' => 'DUYET_VONG_THI_HANG_LOAT', 'tenNguCanh' => 'Duyet ket qua vong thi hang loat', 'moTa' => 'Ap dung khi BTC duyet nhieu san pham cung luc', 'isHeThong' => 1],
        ['maNguCanh' => 'XET_GIAI_THUONG', 'tenNguCanh' => 'Xet giai thuong', 'moTa' => 'Ap dung khi tong hop va xet giai', 'isHeThong' => 1],
    ];
}

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSk <= 0) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai id_sk',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUser = (int) ($actor['idTK'] ?? 0);
if ($idUser <= 0 || !xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn không có quyền truy cập metadata quy chế của sự kiện này',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


$loaiQuyChe = strtoupper(trim((string) ($_GET['loai_quy_che'] ?? '')));
$maNguCanhRaw = trim((string) ($_GET['ma_ngu_canh'] ?? ''));

if ($loaiQuyChe !== '' && !loai_quy_che_hop_le($loaiQuyChe)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'loai_quy_che không nằm trong danh mục chuẩn',
        'data' => [
            'allowed_loai_quy_che' => lay_danh_muc_loai_quy_che(),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$selectedNguCanh = [];
foreach (explode(',', $maNguCanhRaw) as $item) {
    $clean = chuan_hoa_ma_ngu_canh($item);
    if ($clean !== '') {
        $selectedNguCanh[$clean] = true;
    }
}
$selectedNguCanh = array_keys($selectedNguCanh);

$danhMucNguCanhHeThong = lay_danh_muc_ngu_canh_ap_dung($conn);
if (!empty($danhMucNguCanhHeThong)) {
    $allowedSet = array_fill_keys($danhMucNguCanhHeThong, true);
    foreach ($selectedNguCanh as $maNguCanh) {
        if (!isset($allowedSet[$maNguCanh])) {
            http_response_code(422);
            echo json_encode([
                'status' => 'error',
                'message' => 'ma_ngu_canh không thuộc danh mục chuẩn',
                'data' => [
                    'invalid_context' => $maNguCanh,
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$filterLoaiApDung = [];

try {
    $stmtThuocTinh = $conn->prepare(
        'SELECT idThuocTinhKiemTra, tenThuocTinh, tenTruongDL, bangDuLieu, loaiApDung
         FROM thuoctinh_kiemtra
         ORDER BY loaiApDung ASC, tenThuocTinh ASC'
    );
    $stmtThuocTinh->execute();
    $thuocTinhRows = $stmtThuocTinh->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $thuocTinh = array_values(array_filter($thuocTinhRows, function ($row) use ($conn) {
        return cot_du_lieu_an_toan_cho_goi_y(
            $conn,
            (string) ($row['bangDuLieu'] ?? ''),
            (string) ($row['tenTruongDL'] ?? '')
        );
    }));

    $toanTuColumns = $conn->query('SHOW COLUMNS FROM toantu')->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasTenToanTu = in_array('tenToanTu', $toanTuColumns, true);
    $hasMoTa = in_array('moTa', $toanTuColumns, true);
    $labelExpr = $hasTenToanTu ? 'tenToanTu' : ($hasMoTa ? 'moTa AS tenToanTu' : 'kyHieu AS tenToanTu');

    $stmtToanTu = $conn->prepare(
        "SELECT idToanTu, kyHieu, {$labelExpr}, loaiToanTu
         FROM toantu
         ORDER BY loaiToanTu ASC, idToanTu ASC"
    );
    $stmtToanTu->execute();
    $toanTu = $stmtToanTu->fetchAll(PDO::FETCH_ASSOC);

    $nguCanhApDung = [];
    if (bang_ton_tai($conn, 'quyche_danhmuc_ngucanh')) {
        $stmtNguCanh = $conn->prepare(
            'SELECT maNguCanh, tenNguCanh, moTa, isHeThong
             FROM quyche_danhmuc_ngucanh
             WHERE isHeThong = 1 OR isHeThong IS NULL
             ORDER BY isHeThong DESC, maNguCanh ASC'
        );
        $stmtNguCanh->execute();
        $nguCanhApDung = $stmtNguCanh->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (empty($nguCanhApDung) && bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
        $stmtNguCanhLegacy = $conn->prepare(
            'SELECT DISTINCT maNguCanh
             FROM quyche_ngucanh_apdung
             ORDER BY maNguCanh ASC'
        );
        $stmtNguCanhLegacy->execute();
        $rows = $stmtNguCanhLegacy->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
        foreach ($rows as $maNguCanh) {
            $clean = chuan_hoa_ma_ngu_canh($maNguCanh);
            if ($clean === '') {
                continue;
            }
            $nguCanhApDung[] = [
                'maNguCanh' => $clean,
                'tenNguCanh' => 'Legacy: ' . $clean,
                'moTa' => 'Ngu canh lay tu du lieu quy che hien co',
                'isHeThong' => 0,
            ];
        }
    }

    if (empty($nguCanhApDung)) {
        $nguCanhApDung = context_defaults_for_ui();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy metadata quy chế thành công',
        'data' => [
            'selected_loai_quy_che' => $loaiQuyChe,
            'selected_ngu_canh' => $selectedNguCanh,
            'filter_loai_ap_dung' => $filterLoaiApDung,
            'loai_quy_che_catalog' => lay_danh_muc_loai_quy_che(),
            'thuoc_tinh' => $thuocTinh,
            'toan_tu' => $toanTu,
            'ngu_canh_ap_dung' => $nguCanhApDung,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy metadata quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
