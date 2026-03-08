<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';


header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$actor = auth_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idThuocTinh = isset($_GET['id_thuoc_tinh']) ? (int) $_GET['id_thuoc_tinh'] : 0;
if ($idThuocTinh <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu hoặc sai id_thuoc_tinh',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare(
        'SELECT idThuocTinhKiemTra, tenThuocTinh, tenTruongDL, bangDuLieu, loaiApDung
         FROM thuoctinh_kiemtra
         WHERE idThuocTinhKiemTra = :idThuocTinh
         LIMIT 1'
    );
    $stmt->execute([':idThuocTinh' => $idThuocTinh]);
    $thuocTinh = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thuocTinh) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Không tìm thấy thuộc tính kiểm tra',
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $bangDuLieu = (string) ($thuocTinh['bangDuLieu'] ?? '');
    $tenTruongDL = (string) ($thuocTinh['tenTruongDL'] ?? '');

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $bangDuLieu) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tenTruongDL)) {
        throw new RuntimeException('Cấu hình bảng/trường dữ liệu không hợp lệ');
    }

    $sql = "SELECT DISTINCT {$tenTruongDL} AS giaTri\n            FROM {$bangDuLieu}\n            WHERE {$tenTruongDL} IS NOT NULL AND TRIM(CAST({$tenTruongDL} AS CHAR)) <> ''\n            ORDER BY {$tenTruongDL} ASC\n            LIMIT 30";

    $stmtValues = $conn->prepare($sql);
    $stmtValues->execute();
    $rows = $stmtValues->fetchAll(PDO::FETCH_ASSOC);

    $goiY = [];
    foreach ($rows as $row) {
        $value = isset($row['giaTri']) ? trim((string) $row['giaTri']) : '';
        if ($value !== '') {
            $goiY[] = $value;
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy gợi ý giá trị thành công',
        'data' => [
            'thuoc_tinh' => $thuocTinh,
            'goi_y' => array_values(array_unique($goiY)),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy gợi ý giá trị',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
