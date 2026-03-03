<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$loaiQuyCheRaw = strtoupper(trim((string) ($_GET['loai_quy_che'] ?? '')));
$normalizeLoai = [
    'THAMGIA' => 'THAMGIA_SV',
    'THAMGIA_SV' => 'THAMGIA_SV',
    'THAMGIA_GV' => 'THAMGIA_GV',
    'VONGTHI' => 'VONGTHI',
    'SANPHAM' => 'SANPHAM',
    'GIAITHUONG' => 'GIAITHUONG',
];

$loaiQuyChe = $loaiQuyCheRaw !== '' ? ($normalizeLoai[$loaiQuyCheRaw] ?? '') : '';
if ($loaiQuyCheRaw !== '' && $loaiQuyChe === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'loai_quy_che không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$filterLoaiApDung = [];
if ($loaiQuyChe === 'THAMGIA_SV') {
    $filterLoaiApDung = ['THAMGIA_SV', 'THAMGIA'];
} elseif ($loaiQuyChe !== '') {
    $filterLoaiApDung = [$loaiQuyChe];
}

try {
    if (!empty($filterLoaiApDung)) {
        $placeholders = implode(', ', array_fill(0, count($filterLoaiApDung), '?'));
        $stmtThuocTinh = $conn->prepare(
            "SELECT idThuocTinhKiemTra, tenThuocTinh, tenTruongDL, bangDuLieu, loaiApDung
             FROM thuoctinh_kiemtra
             WHERE loaiApDung IN ({$placeholders})
             ORDER BY tenThuocTinh ASC"
        );
        $stmtThuocTinh->execute($filterLoaiApDung);
    } else {
        $stmtThuocTinh = $conn->prepare(
            'SELECT idThuocTinhKiemTra, tenThuocTinh, tenTruongDL, bangDuLieu, loaiApDung
             FROM thuoctinh_kiemtra
             ORDER BY loaiApDung ASC, tenThuocTinh ASC'
        );
        $stmtThuocTinh->execute();
    }
    $thuocTinh = $stmtThuocTinh->fetchAll(PDO::FETCH_ASSOC);

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

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy metadata quy chế thành công',
        'data' => [
            'loai_quy_che_raw' => $loaiQuyCheRaw,
            'loai_quy_che' => $loaiQuyChe,
            'thuoc_tinh' => $thuocTinh,
            'toan_tu' => $toanTu,
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
