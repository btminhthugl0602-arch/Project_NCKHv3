<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
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

$idUser = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($idUser <= 0 && isset($input['id_nguoi_thuc_hien'])) {
    $idUser = (int) $input['id_nguoi_thuc_hien'];
}

$idSk = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
$tenQuyChe = trim((string) ($input['ten_quy_che'] ?? ''));
$loaiQuyCheRaw = strtoupper(trim((string) ($input['loai_quy_che'] ?? '')));
$moTa = trim((string) ($input['mo_ta'] ?? ''));
$rulesJson = $input['rules_json'] ?? null;

$normalizeLoai = [
    'THAMGIA' => 'THAMGIA_SV',
    'THAMGIA_SV' => 'THAMGIA_SV',
    'THAMGIA_GV' => 'THAMGIA_GV',
    'VONGTHI' => 'VONGTHI',
    'SANPHAM' => 'SANPHAM',
    'GIAITHUONG' => 'GIAITHUONG',
];
$loaiQuyChe = $loaiQuyCheRaw !== '' ? ($normalizeLoai[$loaiQuyCheRaw] ?? '') : '';

if ($idUser <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập hoặc thiếu thông tin người thực hiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idSk <= 0 || $tenQuyChe === '' || $loaiQuyChe === '' || $rulesJson === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu dữ liệu đầu vào để lưu quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedLoai = ['THAMGIA_SV', 'THAMGIA_GV', 'VONGTHI', 'SANPHAM', 'GIAITHUONG'];
if (!in_array($loaiQuyChe, $allowedLoai, true)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Loại quy chế không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!xac_thuc_quyen_quy_che($conn, $idUser, $idSk)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Không có quyền cấu hình quy chế cho sự kiện này',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_string($rulesJson)) {
    $decoded = json_decode($rulesJson, true);
} else {
    $decoded = $rulesJson;
}

if (!is_array($decoded)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'rules_json không đúng định dạng JSON',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function validate_rule_tree_unique_attribute(array $node, array &$usedAttributeIds): void
{
    $type = strtoupper(trim((string) ($node['type'] ?? '')));

    if ($type === 'RULE') {
        $idThuocTinh = (int) (($node['idThuocTinhKiemTra'] ?? 0) ?: ($node['idThuocTinh'] ?? 0));
        if ($idThuocTinh <= 0) {
            throw new RuntimeException('Nút điều kiện đơn thiếu thuộc tính kiểm tra');
        }

        if (isset($usedAttributeIds[$idThuocTinh])) {
            throw new RuntimeException('Trong một biểu thức quy chế, mỗi thuộc tính chỉ được xuất hiện 1 lần');
        }

        $usedAttributeIds[$idThuocTinh] = true;
        return;
    }

    if ($type === 'GROUP') {
        $children = $node['children'] ?? [];
        if (!is_array($children) || count($children) !== 2) {
            throw new RuntimeException('Nhóm điều kiện phải có đúng 2 nhánh');
        }

        validate_rule_tree_unique_attribute($children[0], $usedAttributeIds);
        validate_rule_tree_unique_attribute($children[1], $usedAttributeIds);
        return;
    }

    throw new RuntimeException('Loại node không hợp lệ trong rules_json');
}

function parse_rule_node_to_db($conn, $idUser, $idSk, $loaiQuyChe, array $node): int
{
    $type = strtoupper(trim((string) ($node['type'] ?? '')));

    if ($type === 'RULE') {
        $idThuocTinh = (int) (($node['idThuocTinhKiemTra'] ?? 0) ?: ($node['idThuocTinh'] ?? 0));
        $idToanTu = (int) ($node['idToanTu'] ?? 0);
        $giaTriSoSanh = (string) (($node['giaTriSoSanh'] ?? '') !== '' ? $node['giaTriSoSanh'] : ($node['giaTri'] ?? ''));

        if ($idThuocTinh <= 0 || $idToanTu <= 0 || trim($giaTriSoSanh) === '') {
            throw new RuntimeException('Nút điều kiện đơn không hợp lệ');
        }

        $stmtThuocTinh = $conn->prepare(
            'SELECT idThuocTinhKiemTra
             FROM thuoctinh_kiemtra
             WHERE idThuocTinhKiemTra = :idThuocTinh
             LIMIT 1'
        );
        $stmtThuocTinh->execute([
            ':idThuocTinh' => $idThuocTinh,
        ]);
        if (!(int) $stmtThuocTinh->fetchColumn()) {
            throw new RuntimeException('Thuộc tính kiểm tra không tồn tại');
        }

        $stmtCompare = $conn->prepare(
            'SELECT idToanTu
             FROM toantu
             WHERE idToanTu = :idToanTu AND loaiToanTu = :loaiToanTu
             LIMIT 1'
        );
        $stmtCompare->execute([
            ':idToanTu' => $idToanTu,
            ':loaiToanTu' => 'compare',
        ]);
        if (!(int) $stmtCompare->fetchColumn()) {
            throw new RuntimeException('Toán tử của điều kiện đơn không hợp lệ');
        }

        $result = tao_dieu_kien_don(
            $conn,
            $idUser,
            $idSk,
            'DK_DON_' . uniqid(),
            $idThuocTinh,
            $idToanTu,
            $giaTriSoSanh,
            ''
        );

        if (empty($result['status']) || empty($result['idDieuKien'])) {
            throw new RuntimeException($result['message'] ?? 'Không thể tạo điều kiện đơn');
        }

        return (int) $result['idDieuKien'];
    }

    if ($type === 'GROUP') {
        $operator = strtoupper(trim((string) ($node['operator'] ?? '')));
        $logicId = isset($node['logic']) ? (int) $node['logic'] : 0;
        $children = $node['children'] ?? [];

        if (!is_array($children) || count($children) !== 2) {
            throw new RuntimeException('Nhóm điều kiện phải có đúng 2 nhánh');
        }

        $leftId = parse_rule_node_to_db($conn, $idUser, $idSk, $loaiQuyChe, $children[0]);
        $rightId = parse_rule_node_to_db($conn, $idUser, $idSk, $loaiQuyChe, $children[1]);

        $idToanTuLogic = 0;
        if ($logicId > 0) {
            $stmt = $conn->prepare('SELECT idToanTu, kyHieu FROM toantu WHERE idToanTu = :idToanTu AND loaiToanTu = :loai LIMIT 1');
            $stmt->execute([
                ':idToanTu' => $logicId,
                ':loai' => 'logic',
            ]);
            $logicRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($logicRow) {
                $idToanTuLogic = (int) $logicRow['idToanTu'];
                $operator = strtoupper((string) ($logicRow['kyHieu'] ?? $operator));
            }
        }

        if ($idToanTuLogic <= 0) {
            if (!in_array($operator, ['AND', 'OR'], true)) {
                throw new RuntimeException('Toán tử logic của nhóm không hợp lệ');
            }

            $stmt = $conn->prepare('SELECT idToanTu FROM toantu WHERE loaiToanTu = :loai AND kyHieu = :kyHieu LIMIT 1');
            $stmt->execute([
                ':loai' => 'logic',
                ':kyHieu' => $operator,
            ]);
            $idToanTuLogic = (int) ($stmt->fetchColumn() ?: 0);
        }

        if ($idToanTuLogic <= 0) {
            throw new RuntimeException('Không tìm thấy toán tử logic trong hệ thống');
        }

        $result = tao_to_hop_dieu_kien(
            $conn,
            $idUser,
            $idSk,
            $leftId,
            $idToanTuLogic,
            $rightId,
            'TOHOP_' . uniqid(),
            ''
        );

        if (empty($result['status']) || empty($result['idDieuKien'])) {
            throw new RuntimeException($result['message'] ?? 'Không thể tạo tổ hợp điều kiện');
        }

        return (int) $result['idDieuKien'];
    }

    throw new RuntimeException('Loại node không hợp lệ trong rules_json');
}

try {
    $usedAttributeIds = [];
    validate_rule_tree_unique_attribute($decoded, $usedAttributeIds);

    $idQuyChe = 0;
    $createRuleResult = tao_quy_che($conn, $idUser, $idSk, $tenQuyChe, $loaiQuyChe, $moTa);
    if (empty($createRuleResult['status']) || empty($createRuleResult['idQuyChe'])) {
        throw new RuntimeException($createRuleResult['message'] ?? 'Không tạo được quy chế');
    }

    $idQuyChe = (int) $createRuleResult['idQuyChe'];
    $idDieuKienRoot = parse_rule_node_to_db($conn, $idUser, $idSk, $loaiQuyChe, $decoded);

    $assignResult = gan_dieu_kien_cho_quy_che($conn, $idUser, $idSk, $idQuyChe, $idDieuKienRoot);
    if (empty($assignResult['status'])) {
        throw new RuntimeException($assignResult['message'] ?? 'Không gán được cây điều kiện cho quy chế');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Đã lưu quy chế thành công',
        'data' => [
            'idQuyChe' => $idQuyChe,
            'idDieuKienCuoi' => $idDieuKienRoot,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if (!empty($idQuyChe)) {
        try {
            _delete_info($conn, 'quyche_dieukien', ['idQuyChe' => ['=', (int) $idQuyChe, '']]);
            _delete_info($conn, 'quyche', ['idQuyChe' => ['=', (int) $idQuyChe, '']]);
        } catch (Throwable $innerException) {
            // noop
        }
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi khi lưu quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
