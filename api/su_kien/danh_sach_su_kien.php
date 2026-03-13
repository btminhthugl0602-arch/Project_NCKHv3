<?php

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';


header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────
// Danh sách sự kiện: ai cũng xem được (kể cả guest)
$_isGuestRequest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!$_isGuestRequest) {
    $actor = auth_require_login();
} else {
    $actor = ['idTK' => 0, 'idLoaiTK' => 0, 'hoTen' => 'Khách'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Phương thức không hợp lệ',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$search = trim((string)($_GET['search'] ?? ''));
$limit  = isset($_GET['search']) ? 8 : 50; // search mode trả ít hơn cho dropdown

try {
    if ($search !== '') {
        $stmt = $conn->prepare(
            'SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
                    ct.tenCap, lc.tenLoaiCap
             FROM sukien sk
             LEFT JOIN cap_tochuc ct ON ct.idCap = sk.idCap
             LEFT JOIN loaicap lc ON lc.idLoaiCap = ct.idLoaiCap
             WHERE sk.tenSK LIKE :search
               AND sk.isDeleted = 0
             ORDER BY sk.isActive DESC, sk.idSK DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare(
            'SELECT sk.idSK, sk.tenSK, sk.ngayBatDau, sk.ngayKetThuc, sk.isActive,
                    ct.tenCap, lc.tenLoaiCap
             FROM sukien sk
             LEFT JOIN cap_tochuc ct ON ct.idCap = sk.idCap
             LEFT JOIN loaicap lc ON lc.idLoaiCap = ct.idLoaiCap
             WHERE sk.isDeleted = 0
             ORDER BY sk.idSK DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Lấy danh sách sự kiện thành công',
        'data' => $rows,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống khi lấy danh sách sự kiện',
        'data' => null,
    ], JSON_UNESCAPED_UNICODE);
}
