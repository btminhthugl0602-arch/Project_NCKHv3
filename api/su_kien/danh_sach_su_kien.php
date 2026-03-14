<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
$_isGuestRequest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!$_isGuestRequest) {
    $actor = auth_require_login();
} else {
    $actor = ['idTK' => 0, 'idLoaiTK' => 0, 'hoTen' => 'Khách'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Params ────────────────────────────────────────────────
$search   = trim((string) ($_GET['search']    ?? ''));
$idCap    = isset($_GET['id_cap'])   && $_GET['id_cap']   !== '' ? (int) $_GET['id_cap']   : null;
$thoiGian = trim((string) ($_GET['thoi_gian'] ?? ''));
$page     = max(1, (int) ($_GET['page']  ?? 1));
$limit    = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
$offset   = ($page - 1) * $limit;

// Search-only mode (navbar dropdown) — không cần pagination
$searchOnly = isset($_GET['search']) && !isset($_GET['page']);
if ($searchOnly) { $limit = 8; $offset = 0; }

// ── WHERE ─────────────────────────────────────────────────
$wheres = ['sk.isDeleted = 0'];
$params = [];

if ($search !== '') {
    $wheres[] = 'sk.tenSK LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
if ($idCap !== null) {
    $wheres[] = 'sk.idCap = :idCap';
    $params[':idCap'] = $idCap;
}
if ($thoiGian === 'dang_dien_ra') {
    $wheres[] = 'sk.ngayBatDau <= NOW() AND (sk.ngayKetThuc IS NULL OR sk.ngayKetThuc >= NOW())';
} elseif ($thoiGian === 'sap_dien_ra') {
    $wheres[] = 'sk.ngayBatDau > NOW()';
} elseif ($thoiGian === 'da_ket_thuc') {
    $wheres[] = 'sk.ngayKetThuc IS NOT NULL AND sk.ngayKetThuc < NOW()';
}

$whereSQL = 'WHERE ' . implode(' AND ', $wheres);
$selectFields = 'sk.idSK, sk.tenSK, sk.moTa, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
                 ct.tenCap, lc.tenLoaiCap,
                 (SELECT COUNT(*) FROM nhom n WHERE n.idSK = sk.idSK AND n.isActive = 1) AS soNhom,
                 (SELECT COUNT(*) FROM thanhviennhom tv JOIN nhom n ON tv.idNhom = n.idNhom WHERE n.idSK = sk.idSK AND n.isActive = 1) AS soThanhVien';
$fromJoin = 'FROM sukien sk
             LEFT JOIN cap_tochuc ct ON ct.idCap = sk.idCap
             LEFT JOIN loaicap lc ON lc.idLoaiCap = ct.idLoaiCap';
$orderBy  = $search !== '' ? 'ORDER BY sk.isActive DESC, sk.idSK DESC' : 'ORDER BY sk.idSK DESC';

try {
    $total = 0;
    if (!$searchOnly) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) $fromJoin $whereSQL");
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
    }

    $stmtData = $conn->prepare("SELECT $selectFields $fromJoin $whereSQL $orderBy LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) $stmtData->bindValue($k, $v);
    $stmtData->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtData->execute();
    $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Lấy danh sách sự kiện thành công',
        'data'       => $rows,
        'pagination' => $searchOnly ? null : [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
