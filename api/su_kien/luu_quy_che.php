<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

require_once __DIR__ . '/quan_ly_quy_che.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(422);
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

// ── Auth ──────────────────────────────────────────────────────────
$actor = auth_require_cauhinh_su_kien($idSk);
$idUser = $actor['idTK'];
$tenQuyChe = trim((string) ($input['ten_quy_che'] ?? ''));
$loaiQuyChe = strtoupper(trim((string) ($input['loai_quy_che'] ?? 'TUY_CHINH')));
$moTa = trim((string) ($input['mo_ta'] ?? ''));
$rulesJson = $input['rules_json'] ?? null;
$nguCanhApDungRaw = $input['ngu_canh_ap_dung'] ?? [];

$nguCanhApDung = [];
if (is_array($nguCanhApDungRaw)) {
    foreach ($nguCanhApDungRaw as $item) {
        $clean = chuan_hoa_ma_ngu_canh($item);
        if ($clean !== '') {
            $nguCanhApDung[$clean] = true;
        }
    }
} elseif (is_string($nguCanhApDungRaw)) {
    $parts = explode(',', $nguCanhApDungRaw);
    foreach ($parts as $part) {
        $clean = chuan_hoa_ma_ngu_canh($part);
        if ($clean !== '') {
            $nguCanhApDung[$clean] = true;
        }
    }
}

$nguCanhApDung = array_keys($nguCanhApDung);

if ($idUser <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Bạn chưa đăng nhập hoặc thiếu thông tin người thực hiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($idSk <= 0 || $tenQuyChe === '' || $rulesJson === null) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Thiếu dữ liệu đầu vào để lưu quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($nguCanhApDung)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Quy chế phải có ít nhất 1 ngữ cảnh áp dụng',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($loaiQuyChe === '') {
    $loaiQuyChe = 'TUY_CHINH';
}

if (!loai_quy_che_hop_le($loaiQuyChe)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'loai_quy_che không nằm trong danh mục chuẩn',
        'data' => [
            'allowed' => lay_danh_muc_loai_quy_che(),
        ],
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
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'rules_json không đúng định dạng JSON',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function validate_rule_tree_structure(array $node): void
{
    $type = strtoupper(trim((string) ($node['type'] ?? '')));

    if ($type === 'RULE') {
        $idThuocTinh = (int) (($node['idThuocTinhKiemTra'] ?? 0) ?: ($node['idThuocTinh'] ?? 0));
        if ($idThuocTinh <= 0) {
            throw new RuntimeException('Nút điều kiện đơn thiếu thuộc tính kiểm tra');
        }

        return;
    }

    if ($type === 'GROUP') {
        $children = $node['children'] ?? [];
        if (!is_array($children) || count($children) !== 2) {
            throw new RuntimeException('Nhóm điều kiện phải có đúng 2 nhánh');
        }

        validate_rule_tree_structure($children[0]);
        validate_rule_tree_structure($children[1]);
        return;
    }

    throw new RuntimeException('Loại node không hợp lệ trong rules_json');
}

function semantic_operator_symbol(PDO $conn, int $idToanTu, array &$cache): string
{
    if ($idToanTu <= 0) {
        return '';
    }

    if (isset($cache[$idToanTu])) {
        return $cache[$idToanTu];
    }

    $stmt = $conn->prepare('SELECT kyHieu FROM toantu WHERE idToanTu = :idToanTu LIMIT 1');
    $stmt->execute([':idToanTu' => $idToanTu]);
    $symbol = strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    $cache[$idToanTu] = $symbol;

    return $symbol;
}

function semantic_attribute_name(PDO $conn, int $idThuocTinh, array &$cache): string
{
    if ($idThuocTinh <= 0) {
        return '#0';
    }

    if (isset($cache[$idThuocTinh])) {
        return $cache[$idThuocTinh];
    }

    $stmt = $conn->prepare('SELECT tenThuocTinh FROM thuoctinh_kiemtra WHERE idThuocTinhKiemTra = :idThuocTinh LIMIT 1');
    $stmt->execute([':idThuocTinh' => $idThuocTinh]);
    $name = trim((string) ($stmt->fetchColumn() ?: ''));
    if ($name === '') {
        $name = '#' . $idThuocTinh;
    }
    $cache[$idThuocTinh] = $name;

    return $name;
}

function semantic_extract_conjunctions(PDO $conn, array $node, array &$toanTuCache, array &$thuocTinhCache): array
{
    $type = strtoupper(trim((string) ($node['type'] ?? '')));

    if ($type === 'RULE') {
        $idThuocTinh = (int) (($node['idThuocTinhKiemTra'] ?? 0) ?: ($node['idThuocTinh'] ?? 0));
        $idToanTu = (int) ($node['idToanTu'] ?? 0);
        $giaTri = trim((string) (($node['giaTriSoSanh'] ?? '') !== '' ? $node['giaTriSoSanh'] : ($node['giaTri'] ?? '')));

        return [[[
            'idThuocTinh' => $idThuocTinh,
            'tenThuocTinh' => semantic_attribute_name($conn, $idThuocTinh, $thuocTinhCache),
            'toanTu' => semantic_operator_symbol($conn, $idToanTu, $toanTuCache),
            'giaTri' => $giaTri,
        ]]];
    }

    if ($type !== 'GROUP') {
        throw new RuntimeException('Loại node không hợp lệ trong semantic validation');
    }

    $children = $node['children'] ?? [];
    if (!is_array($children) || count($children) !== 2) {
        throw new RuntimeException('Nhóm điều kiện phải có đúng 2 nhánh');
    }

    $left = semantic_extract_conjunctions($conn, $children[0], $toanTuCache, $thuocTinhCache);
    $right = semantic_extract_conjunctions($conn, $children[1], $toanTuCache, $thuocTinhCache);

    $operator = strtoupper(trim((string) ($node['operator'] ?? '')));
    $logicId = isset($node['logic']) ? (int) $node['logic'] : 0;
    if ($logicId > 0) {
        $logicSymbol = semantic_operator_symbol($conn, $logicId, $toanTuCache);
        if ($logicSymbol !== '') {
            $operator = $logicSymbol;
        }
    }

    if ($operator === 'OR') {
        return array_merge($left, $right);
    }

    if ($operator === 'AND') {
        $merged = [];
        foreach ($left as $leftBranch) {
            foreach ($right as $rightBranch) {
                $merged[] = array_merge($leftBranch, $rightBranch);
            }
        }
        return $merged;
    }

    throw new RuntimeException('Toán tử logic không hợp lệ trong semantic validation');
}

function semantic_analyze_attribute_constraints(string $attrName, array $constraints): ?string
{
    if (empty($constraints)) {
        return null;
    }

    $numericMode = true;
    foreach ($constraints as $constraint) {
        $raw = trim((string) ($constraint['giaTri'] ?? ''));
        if ($raw === '' || !is_numeric($raw)) {
            $numericMode = false;
            break;
        }
    }

    if (!$numericMode) {
        $equals = [];
        $notEquals = [];

        foreach ($constraints as $constraint) {
            $op = trim((string) ($constraint['toanTu'] ?? ''));
            $val = trim((string) ($constraint['giaTri'] ?? ''));
            if ($op === '=') {
                $equals[$val] = true;
            }
            if ($op === '!=' || $op === '<>') {
                $notEquals[$val] = true;
            }
        }

        if (count($equals) > 1) {
            return 'Thuộc tính "' . $attrName . '" có nhiều điều kiện bằng khác nhau trong cùng một nhánh AND';
        }

        if (count($equals) === 1) {
            $eqVal = array_key_first($equals);
            if ($eqVal !== null && isset($notEquals[$eqVal])) {
                return 'Thuộc tính "' . $attrName . '" vừa yêu cầu bằng vừa khác "' . $eqVal . '" trong cùng một nhánh AND';
            }
        }

        return null;
    }

    $lower = null; // ['value' => float, 'inclusive' => bool]
    $upper = null; // ['value' => float, 'inclusive' => bool]
    $equals = [];
    $notEquals = [];

    foreach ($constraints as $constraint) {
        $op = trim((string) ($constraint['toanTu'] ?? ''));
        $val = (float) ($constraint['giaTri'] ?? 0);

        if ($op === '>') {
            if ($lower === null || $val > $lower['value'] || ($val === $lower['value'] && $lower['inclusive'])) {
                $lower = ['value' => $val, 'inclusive' => false];
            }
        } elseif ($op === '>=') {
            if ($lower === null || $val > $lower['value']) {
                $lower = ['value' => $val, 'inclusive' => true];
            }
        } elseif ($op === '<') {
            if ($upper === null || $val < $upper['value'] || ($val === $upper['value'] && $upper['inclusive'])) {
                $upper = ['value' => $val, 'inclusive' => false];
            }
        } elseif ($op === '<=') {
            if ($upper === null || $val < $upper['value']) {
                $upper = ['value' => $val, 'inclusive' => true];
            }
        } elseif ($op === '=') {
            $equals[(string) $val] = $val;
        } elseif ($op === '!=' || $op === '<>') {
            $notEquals[(string) $val] = true;
        }
    }

    if (count($equals) > 1) {
        return 'Thuộc tính "' . $attrName . '" có nhiều điều kiện bằng khác nhau trong cùng một nhánh AND';
    }

    if ($lower !== null && $upper !== null) {
        if ($lower['value'] > $upper['value']) {
            return 'Thuộc tính "' . $attrName . '" có khoảng giá trị rỗng (cận dưới lớn hơn cận trên)';
        }
        if ($lower['value'] === $upper['value'] && (!$lower['inclusive'] || !$upper['inclusive'])) {
            return 'Thuộc tính "' . $attrName . '" có khoảng giá trị rỗng tại điểm biên ' . $lower['value'];
        }
    }

    if (count($equals) === 1) {
        $eq = (float) array_values($equals)[0];
        if ($lower !== null && ($eq < $lower['value'] || ($eq === $lower['value'] && !$lower['inclusive']))) {
            return 'Thuộc tính "' . $attrName . '" có điều kiện bằng không thỏa cận dưới';
        }
        if ($upper !== null && ($eq > $upper['value'] || ($eq === $upper['value'] && !$upper['inclusive']))) {
            return 'Thuộc tính "' . $attrName . '" có điều kiện bằng không thỏa cận trên';
        }
        if (isset($notEquals[(string) $eq])) {
            return 'Thuộc tính "' . $attrName . '" vừa yêu cầu bằng vừa khác ' . $eq . ' trong cùng một nhánh AND';
        }
    } elseif ($lower !== null && $upper !== null && $lower['value'] === $upper['value'] && $lower['inclusive'] && $upper['inclusive']) {
        $single = (string) $lower['value'];
        if (isset($notEquals[$single])) {
            return 'Thuộc tính "' . $attrName . '" chỉ còn 1 giá trị hợp lệ nhưng lại bị loại trừ';
        }
    }

    return null;
}

function semantic_validate_rule_tree(PDO $conn, array $ast): void
{
    $toanTuCache = [];
    $thuocTinhCache = [];
    $conjunctions = semantic_extract_conjunctions($conn, $ast, $toanTuCache, $thuocTinhCache);

    $errors = [];
    foreach ($conjunctions as $index => $branch) {
        $byAttr = [];
        foreach ($branch as $constraint) {
            $attrName = (string) ($constraint['tenThuocTinh'] ?? '#0');
            if (!isset($byAttr[$attrName])) {
                $byAttr[$attrName] = [];
            }
            $byAttr[$attrName][] = $constraint;
        }

        foreach ($byAttr as $attrName => $constraints) {
            $msg = semantic_analyze_attribute_constraints($attrName, $constraints);
            if ($msg !== null) {
                $errors[] = 'Nhánh AND #' . ($index + 1) . ': ' . $msg;
            }
        }
    }

    if (!empty($errors)) {
        throw new RuntimeException('Biểu thức quy chế mâu thuẫn ngữ nghĩa. ' . implode('. ', $errors));
    }
}

function parse_rule_node_to_db($conn, $idUser, $idSk, array $node, array &$createdNodeIds): int
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

        $idDieuKien = (int) $result['idDieuKien'];
        $createdNodeIds[$idDieuKien] = true;
        return $idDieuKien;
    }

    if ($type === 'GROUP') {
        $operator = strtoupper(trim((string) ($node['operator'] ?? '')));
        $logicId = isset($node['logic']) ? (int) $node['logic'] : 0;
        $children = $node['children'] ?? [];

        if (!is_array($children) || count($children) !== 2) {
            throw new RuntimeException('Nhóm điều kiện phải có đúng 2 nhánh');
        }

        $leftId = parse_rule_node_to_db($conn, $idUser, $idSk, $children[0], $createdNodeIds);
        $rightId = parse_rule_node_to_db($conn, $idUser, $idSk, $children[1], $createdNodeIds);

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

        $idDieuKien = (int) $result['idDieuKien'];
        $createdNodeIds[$idDieuKien] = true;
        return $idDieuKien;
    }

    throw new RuntimeException('Loại node không hợp lệ trong rules_json');
}

function cleanup_created_condition_nodes(PDO $conn, array $createdNodeIds): void
{
    if (empty($createdNodeIds)) {
        return;
    }

    $ids = array_values(array_unique(array_map('intval', array_keys($createdNodeIds))));
    if (empty($ids)) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmtToHop = $conn->prepare(
        "DELETE FROM tohop_dieukien
         WHERE idDieuKien IN ({$placeholders})
            OR idDieuKienTrai IN ({$placeholders})
            OR idDieuKienPhai IN ({$placeholders})"
    );
    $stmtToHop->execute(array_merge($ids, $ids, $ids));

    $stmtDon = $conn->prepare("DELETE FROM dieukien_don WHERE idDieuKien IN ({$placeholders})");
    $stmtDon->execute($ids);

    $stmtRootMap = $conn->prepare("DELETE FROM quyche_dieukien WHERE idDieuKienCuoi IN ({$placeholders})");
    $stmtRootMap->execute($ids);

    $stmtDieuKien = $conn->prepare("DELETE FROM dieukien WHERE idDieuKien IN ({$placeholders})");
    $stmtDieuKien->execute($ids);
}

$idQuyChe = 0;
$idDieuKienRoot = 0;
$createdNodeIds = [];
$startedAt = microtime(true);

ghi_log_quy_che('save_rule_start', [
    'idSK' => (int) $idSk,
    'idUser' => (int) $idUser,
    'loaiQuyChe' => $loaiQuyChe,
    'nguCanhApDung' => $nguCanhApDung,
]);

try {
    validate_rule_tree_structure($decoded);
    semantic_validate_rule_tree($conn, $decoded);

    $conn->beginTransaction();

    $createRuleResult = tao_quy_che($conn, $idUser, $idSk, $tenQuyChe, $loaiQuyChe, $moTa);
    if (empty($createRuleResult['status']) || empty($createRuleResult['idQuyChe'])) {
        throw new RuntimeException($createRuleResult['message'] ?? 'Không tạo được quy chế');
    }

    $idQuyChe = (int) $createRuleResult['idQuyChe'];
    $idDieuKienRoot = parse_rule_node_to_db($conn, $idUser, $idSk, $decoded, $createdNodeIds);

    $assignResult = gan_dieu_kien_cho_quy_che($conn, $idUser, $idSk, $idQuyChe, $idDieuKienRoot);
    if (empty($assignResult['status'])) {
        throw new RuntimeException($assignResult['message'] ?? 'Không gán được cây điều kiện cho quy chế');
    }

    $assignContextResult = gan_ngucanh_ap_dung_cho_quy_che($conn, $idUser, $idSk, $idQuyChe, $nguCanhApDung);
    if (empty($assignContextResult['status'])) {
        throw new RuntimeException($assignContextResult['message'] ?? 'Không gán được ngữ cảnh áp dụng cho quy chế');
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Đã lưu quy chế thành công',
        'data' => [
            'idQuyChe' => $idQuyChe,
            'idDieuKienCuoi' => $idDieuKienRoot,
            'nguCanhApDung' => $nguCanhApDung,
        ],
    ], JSON_UNESCAPED_UNICODE);

    ghi_log_quy_che('save_rule_result', [
        'status' => 'success',
        'idSK' => (int) $idSk,
        'idUser' => (int) $idUser,
        'idQuyChe' => (int) $idQuyChe,
        'nodeCount' => count($createdNodeIds),
        'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
    ]);
} catch (Throwable $exception) {
    if ($conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    if (!empty($idQuyChe)) {
        try {
            if (bang_ton_tai($conn, 'quyche_ngucanh_apdung')) {
                _delete_info($conn, 'quyche_ngucanh_apdung', ['idQuyChe' => ['=', (int) $idQuyChe, '']]);
            }
            _delete_info($conn, 'quyche_dieukien', ['idQuyChe' => ['=', (int) $idQuyChe, '']]);
            _delete_info($conn, 'quyche', ['idQuyChe' => ['=', (int) $idQuyChe, '']]);
        } catch (Throwable $innerException) {
            // noop
        }
    }

    if (!empty($createdNodeIds)) {
        try {
            cleanup_created_condition_nodes($conn, $createdNodeIds);
        } catch (Throwable $innerException) {
            // noop
        }
    }

    $statusCode = $exception instanceof RuntimeException ? 422 : 500;
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage() ?: 'Lỗi khi lưu quy chế',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);

    ghi_log_quy_che('save_rule_result', [
        'status' => $statusCode === 422 ? 'validation_error' : 'system_error',
        'idSK' => (int) $idSk,
        'idUser' => (int) $idUser,
        'idQuyChe' => (int) $idQuyChe,
        'nodeCount' => count($createdNodeIds),
        'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
        'error' => $exception->getMessage(),
    ]);
}
