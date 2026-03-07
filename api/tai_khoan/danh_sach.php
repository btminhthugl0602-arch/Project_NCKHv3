<?php

/**
 * API: Danh sách tài khoản (server-side pagination + filter + search)
 *
 * GET params:
 *   page   (int, default 1)
 *   limit  (int, default 10, max 100)
 *   search (string) — tìm theo tenTK hoặc hoTen
 *   loai   (int) — 1=Admin, 2=GV, 3=SV, bỏ qua nếu không truyền
 */

define("_AUTHEN", true);
require_once __DIR__ . "/../core/base.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Phương thức không hợp lệ", "data" => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Params ──────────────────────────────────────────────────
$page   = max(1, (int) ($_GET["page"]  ?? 1));
$limit  = min(100, max(1, (int) ($_GET["limit"] ?? 10)));
$search = trim((string) ($_GET["search"] ?? ""));
$loai   = isset($_GET["loai"]) && $_GET["loai"] !== "" ? (int) $_GET["loai"] : null;
$offset = ($page - 1) * $limit;

// ── Build WHERE ──────────────────────────────────────────────
$wheres = [];
$params = [];

if ($loai !== null && in_array($loai, [1, 2, 3], true)) {
    $wheres[] = "tk.idLoaiTK = :loai";
    $params[":loai"] = $loai;
}

if ($search !== "") {
    $wheres[] = "(tk.tenTK LIKE :search1 OR COALESCE(sv.tenSV, gv.tenGV, '') LIKE :search2)";
    $params[":search1"] = "%{$search}%";
    $params[":search2"] = "%{$search}%";
}

$whereSQL = count($wheres) ? "WHERE " . implode(" AND ", $wheres) : "";

try {
    // ── COUNT ────────────────────────────────────────────────
    $sqlCount = "SELECT COUNT(DISTINCT tk.idTK)
        FROM taikhoan tk
        LEFT JOIN sinhvien  sv ON sv.idTK = tk.idTK
        LEFT JOIN giangvien gv ON gv.idTK = tk.idTK
        {$whereSQL}";

    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = (int) $stmtCount->fetchColumn();
    $totalPages = (int) ceil($total / $limit);

    // ── STATS (breakdown theo loại — không bị ảnh hưởng bởi filter) ──
    $sqlStats = "SELECT idLoaiTK, COUNT(*) AS soLuong FROM taikhoan GROUP BY idLoaiTK";
    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->execute();
    $statsRaw = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

    $stats = ['tong' => 0, 'admin' => 0, 'gv' => 0, 'sv' => 0];
    foreach ($statsRaw as $s) {
        $n = (int) $s['soLuong'];
        $stats['tong'] += $n;
        if ((int)$s['idLoaiTK'] === 1) $stats['admin'] = $n;
        if ((int)$s['idLoaiTK'] === 2) $stats['gv']    = $n;
        if ((int)$s['idLoaiTK'] === 3) $stats['sv']    = $n;
    }

    // ── DATA ─────────────────────────────────────────────────
    $sqlData = "SELECT
            tk.idTK,
            tk.tenTK,
            tk.idLoaiTK,
            tk.isActive,
            tk.ngayTao,
            COALESCE(sv.tenSV, gv.tenGV, NULL) AS hoTen,
            CASE
                WHEN tk.idLoaiTK = 3 THEN l.tenLop
                WHEN tk.idLoaiTK = 2 THEN k.tenKhoa
                ELSE NULL
            END AS donVi,
            GROUP_CONCAT(
                CASE WHEN tq.isActive = 1 THEN q.maQuyen ELSE NULL END
                ORDER BY q.maQuyen
                SEPARATOR ','
            ) AS dsQuyenRaw
        FROM taikhoan tk
        LEFT JOIN sinhvien  sv ON sv.idTK  = tk.idTK
        LEFT JOIN giangvien gv ON gv.idTK  = tk.idTK
        LEFT JOIN lop        l ON l.idLop  = sv.idLop
        LEFT JOIN khoa       k ON k.idKhoa = COALESCE(gv.idKhoa, sv.idKhoa)
        LEFT JOIN taikhoan_quyen tq ON tq.idTK    = tk.idTK
        LEFT JOIN quyen          q  ON q.idQuyen  = tq.idQuyen AND q.phamVi = 'HE_THONG'
        {$whereSQL}
        GROUP BY tk.idTK, tk.tenTK, tk.idLoaiTK, tk.isActive, tk.ngayTao,
                 sv.tenSV, gv.tenGV, l.tenLop, k.tenKhoa
        ORDER BY tk.idTK DESC
        LIMIT :limit OFFSET :offset";

    $stmtData = $conn->prepare($sqlData);
    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val);
    }
    $stmtData->bindValue(":limit",  $limit,  PDO::PARAM_INT);
    $stmtData->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmtData->execute();
    $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // dsQuyenRaw → array
    foreach ($rows as &$row) {
        $row["dsQuyen"] = $row["dsQuyenRaw"]
            ? explode(",", $row["dsQuyenRaw"])
            : [];
        unset($row["dsQuyenRaw"]);
    }
    unset($row);

    echo json_encode([
        "status"  => "success",
        "message" => "OK",
        "data"    => [
            "rows"       => $rows,
            "total"      => $total,
            "page"       => $page,
            "limit"      => $limit,
            "totalPages" => $totalPages,
            "stats"      => $stats,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("danh_sach.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Lỗi hệ thống", "data" => null], JSON_UNESCAPED_UNICODE);
}
