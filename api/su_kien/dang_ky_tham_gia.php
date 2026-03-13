<?php

/**
 * API: Đăng ký tham gia sự kiện
 * POST /api/su_kien/dang_ky_tham_gia.php
 * Body: { id_sk: int }
 *
 * Logic:
 *   - SV (idLoaiTK=3) → gán vai trò THAM_GIA
 *   - GV (idLoaiTK=2) → gán vai trò GV_HUONG_DAN
 *   - Điều kiện: sự kiện đang mở đăng ký + chưa đăng ký + chế độ đăng ký phù hợp
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_quy_che.php';

header('Content-Type: application/json; charset=utf-8');

$actor     = auth_require_login();
$idTK      = $actor['idTK'];
$idLoaiTK  = $actor['idLoaiTK'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$idSk  = (int) ($input['id_sk'] ?? 0);

if ($idSk <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ── 1. Lấy thông tin sự kiện ──────────────────────────
    $stmt = $conn->prepare("
        SELECT idSK, isActive, ngayMoDangKy, ngayDongDangKy
        FROM sukien
        WHERE idSK = ? AND isDeleted = 0
        LIMIT 1
    ");
    $stmt->execute([$idSk]);
    $sk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sk) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Sự kiện không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int) $sk['isActive'] !== 1) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Sự kiện đã bị vô hiệu hóa', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 2. Kiểm tra thời gian đăng ký ────────────────────
    $now      = time();
    $ngayMo   = strtotime($sk['ngayMoDangKy']);
    $ngayDong = strtotime($sk['ngayDongDangKy']);

    if ($now < $ngayMo || $now > $ngayDong) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ngoài thời gian đăng ký tham gia', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 3. Kiểm tra loại tài khoản hợp lệ ──────────────
    if (!in_array($idLoaiTK, [2, 3], true)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Loại tài khoản không được phép đăng ký', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 4. Kiểm tra đã đăng ký chưa ──────────────────────
    $stmt = $conn->prepare("
        SELECT 1 FROM taikhoan_vaitro_sukien
        WHERE idTK = ? AND idSK = ? AND isActive = 1
        LIMIT 1
    ");
    $stmt->execute([$idTK, $idSk]);
    if ($stmt->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Bạn đã đăng ký tham gia sự kiện này rồi', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 5. Xác định idVaiTro ──────────────────────────────
    // SV (idLoaiTK=3) → THAM_GIA, GV (idLoaiTK=2) → GV_HUONG_DAN
    $maVaiTro = $idLoaiTK === 3 ? 'THAM_GIA' : 'GV_HUONG_DAN';

    // ── 5.1 Kiểm tra quy chế theo ngữ cảnh áp dụng ───────
    $maNguCanh = $idLoaiTK === 3 ? 'DANG_KY_THAM_GIA_SV' : 'DANG_KY_THAM_GIA_GV';
    $ketQuaQuyChe = xet_duyet_quy_che_theo_ngucanh($conn, $idSk, $maNguCanh, $idTK);

    // Fail-safe cho dữ liệu legacy: thêm 1 lượt check theo mã loại cũ
    // để tránh bỏ lọt khi mapping ngữ cảnh bị lệch/thiếu.
    $legacyNguCanh = $idLoaiTK === 3 ? 'THAMGIA' : 'THAMGIA_GV';
    $ketQuaQuyCheLegacy = xet_duyet_quy_che_theo_ngucanh($conn, $idSk, $legacyNguCanh, $idTK);

    $tongQuyCheChinh = (int) ($ketQuaQuyChe['tongQuyChe'] ?? 0);
    $tongQuyCheLegacy = (int) ($ketQuaQuyCheLegacy['tongQuyChe'] ?? 0);

    $coQuyCheChinh = $tongQuyCheChinh > 0;
    $coQuyCheLegacy = $tongQuyCheLegacy > 0;

    $thatBaiChinh = $coQuyCheChinh && empty($ketQuaQuyChe['hopLe']);
    $thatBaiLegacy = $coQuyCheLegacy && empty($ketQuaQuyCheLegacy['hopLe']);

    if ($thatBaiChinh || $thatBaiLegacy) {
        $viPham = [];
        if ($thatBaiChinh) {
            $viPham = array_merge($viPham, is_array($ketQuaQuyChe['viPham'] ?? null) ? $ketQuaQuyChe['viPham'] : []);
        }
        if ($thatBaiLegacy) {
            $viPham = array_merge($viPham, is_array($ketQuaQuyCheLegacy['viPham'] ?? null) ? $ketQuaQuyCheLegacy['viPham'] : []);
        }

        $tenViPham = [];
        foreach ($viPham as $item) {
            $ten = trim((string) ($item['tenQuyChe'] ?? ''));
            if ($ten !== '') {
                $tenViPham[$ten] = true;
            }
        }

        $message = 'Bạn chưa đạt quy chế áp dụng cho đăng ký tham gia.';
        if (!empty($tenViPham)) {
            $message .= ' Không đạt: ' . implode(', ', array_keys($tenViPham));
        }

        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => [
                'ma_ngu_canh' => $maNguCanh,
                'ma_ngu_canh_legacy' => $legacyNguCanh,
                'tong_quy_che' => $tongQuyCheChinh,
                'tong_quy_che_legacy' => $tongQuyCheLegacy,
                'vi_pham' => array_values($viPham),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("SELECT idVaiTro FROM vaitro WHERE maVaiTro = ? LIMIT 1");
    $stmt->execute([$maVaiTro]);
    $idVaiTro = (int) $stmt->fetchColumn();

    if (!$idVaiTro) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cấu hình vai trò hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 6. Gán vai trò ────────────────────────────────────
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, isActive, nguonTao, idNguoiCap, ngayCap)
        VALUES (?, ?, ?, 1, 'DANG_KY', NULL, NOW())
    ");
    $stmt->execute([$idTK, $idSk, $idVaiTro]);

    $conn->commit();

    $tenVaiTro = $idLoaiTK === 3 ? 'thí sinh' : 'giảng viên hướng dẫn';
    echo json_encode([
        'status'  => 'success',
        'message' => "Đăng ký thành công! Bạn đã tham gia với vai trò {$tenVaiTro}.",
        'data'    => ['vai_tro' => $maVaiTro],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
