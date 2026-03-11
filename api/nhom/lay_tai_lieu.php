<?php

/**
 * lay_tai_lieu.php — GET: Lấy dữ liệu tài liệu nộp của nhóm
 *
 * Query params:
 *   id_nhom     (required) — id của nhóm
 *   id_vong_thi (optional) — lấy thêm form fields + giá trị đã nộp cho vòng này
 *
 * Response:
 *   sanpham      — thông tin đề tài (null nếu chưa tạo)
 *   chuDeSK      — danh sách chủ đề của sự kiện (để chọn khi tạo/sửa)
 *   vongThi      — danh sách vòng thi kèm soField, daQiaHan, daNop
 *   formFields   — (nếu có id_vong_thi) danh sách field của vòng đó
 *   daNopValues  — (nếu có id_vong_thi + sanpham) giá trị đã nộp
 */
define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_nhom.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$idNhom    = (int) ($_GET['id_nhom'] ?? 0);
$idVongThi = isset($_GET['id_vong_thi']) ? (int) $_GET['id_vong_thi'] : null;

if ($idNhom <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id_nhom', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$actor = auth_require_login();
$idTK  = $actor['idTK'];

// Lấy nhóm
$nhom = lay_nhom_theo_id($conn, $idNhom);
if (!$nhom) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Nhóm không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}
$idSK = (int) $nhom['idSK'];

// Kiểm tra quyền: phải là thành viên SV, chủ nhóm, hoặc GVHD
$isMember = la_thanh_vien_sv($conn, $idTK, $idNhom) || la_chu_nhom($conn, $idTK, $idNhom);
$isGVHD   = la_gvhd_nhom($conn, $idTK, $idNhom);
if (!$isMember && !$isGVHD) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Bạn không phải thành viên của nhóm này', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$isTruongNhom = ((int) $nhom['idTruongNhom'] === $idTK);

try {

    // ── Lấy thông tin đề tài ──────────────────────────────────────
    $sanpham = lay_san_pham_nhom($conn, $idNhom, $idSK);

    // ── Lấy chủ đề sự kiện (để chọn khi tạo/sửa SP) ─────────────
    $stmtCD = $conn->prepare(
        'SELECT cs.idChuDeSK, c.tenChuDe
     FROM chude_sukien cs
     JOIN chude c ON c.idChuDe = cs.idchude
     WHERE cs.idSK = :idSK AND cs.isActive = 1
     ORDER BY c.tenChuDe ASC'
    );
    $stmtCD->execute([':idSK' => $idSK]);
    $chuDeSK = $stmtCD->fetchAll(PDO::FETCH_ASSOC);

    // ── Lấy danh sách vòng thi với trạng thái ─────────────────────
    $stmtVT = $conn->prepare(
        'SELECT vt.idVongThi, vt.tenVongThi, vt.thuTu, vt.thoiGianDongNop, vt.dongNopThuCong,
            COUNT(ff.idField) AS soField
     FROM vongthi vt
     LEFT JOIN form_field ff ON ff.idVongThi = vt.idVongThi AND ff.isActive = 1
     WHERE vt.idSK = :idSK
     GROUP BY vt.idVongThi
     ORDER BY vt.thuTu ASC'
    );
    $stmtVT->execute([':idSK' => $idSK]);
    $vongThiRaw = $stmtVT->fetchAll(PDO::FETCH_ASSOC);

    $vongThi = [];
    foreach ($vongThiRaw as $vt) {
        $daQiaHan = ((int)($vt['dongNopThuCong'] ?? 0) === 1) ||
            (!empty($vt['thoiGianDongNop']) && strtotime($vt['thoiGianDongNop']) <= time());
        $soField  = (int) $vt['soField'];

        // Kiểm tra đã nộp chưa (chỉ check nếu có form và có sản phẩm)
        $daNop = false;
        if ($soField > 0 && $sanpham) {
            $stmtDN = $conn->prepare(
                'SELECT COUNT(*) FROM sanpham_field_value sfv
             JOIN form_field ff ON ff.idField = sfv.idField
             WHERE sfv.idSanPham = :idSP AND ff.idVongThi = :idVT AND ff.isActive = 1'
            );
            $stmtDN->execute([':idSP' => (int) $sanpham['idSanPham'], ':idVT' => (int) $vt['idVongThi']]);
            $daNop = (int) $stmtDN->fetchColumn() > 0;
        }

        $vongThi[] = [
            'idVongThi'       => (int) $vt['idVongThi'],
            'tenVongThi'      => $vt['tenVongThi'],
            'thuTu'           => (int) $vt['thuTu'],
            'thoiGianDongNop' => $vt['thoiGianDongNop'],
            'soField'         => $soField,
            'khongCanNop'     => $soField === 0,
            'daQiaHan'        => $daQiaHan,
            'daNop'           => $daNop,
        ];
    }

    // ── Nếu có id_vong_thi: lấy form fields + giá trị đã nộp ──────
    $formFields   = null;
    $daNopValues  = null;

    if ($idVongThi !== null && $idVongThi > 0) {
        $formFields  = lay_form_vong_thi($conn, $idVongThi);

        if ($sanpham && !empty($formFields)) {
            $rawValues = lay_tai_lieu_da_nop($conn, (int) $sanpham['idSanPham'], $idVongThi);
            // index by idField
            $daNopValues = [];
            foreach ($rawValues as $val) {
                $daNopValues[(int) $val['idField']] = $val;
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'OK',
        'data' => [
            'sanpham'      => $sanpham,
            'chuDeSK'      => $chuDeSK,
            'vongThi'      => $vongThi,
            'formFields'   => $formFields,
            'daNopValues'  => $daNopValues,
            'isTruongNhom' => $isTruongNhom,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}
