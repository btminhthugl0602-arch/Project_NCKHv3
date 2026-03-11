<?php
/**
 * API Nhập điểm dành cho Giảng viên / Giám khảo
 *
 * GET  ?action=lay_phieu_cham&id_sk=&id_vong_thi=
 *      → Trả về phancongcham của GV + danh sách sản phẩm được phân công + trạng thái
 *
 * GET  ?action=chi_tiet_san_pham&id_sk=&id_vong_thi=&id_san_pham=
 *      → Trả về bộ tiêu chí + điểm đã nhập (nếu có) cho một sản phẩm
 *      → Nếu isTrongTai=true: trả thêm bongTranh (điểm GK chính) và maTranCanhBao (tiêu chí lệch cao)
 *
 * POST {action:'luu_diem', id_sk, id_vong_thi, id_san_pham, diem:[{id_tieu_chi, diem, nhan_xet}]}
 *      → Lưu / cập nhật điểm từng tiêu chí (auto-save)
 *
 * POST {action:'nop_phieu', id_sk, id_vong_thi, id_san_pham}
 *      → Đánh dấu GV đã hoàn thành chấm sản phẩm này
 *      → Nếu isTrongTai=true: sau khi nộp, cập nhật sanpham_vongthi bằng điểm phán quyết của TT
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth: chỉ GV có quyền nhap_diem mới được truy cập ───────
$idSK_auth = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSK_auth <= 0) {
    $_body_auth = json_decode(file_get_contents('php://input'), true) ?? [];
    $idSK_auth  = isset($_body_auth['id_sk']) ? (int) $_body_auth['id_sk'] : 0;
    $_SERVER['_PARSED_INPUT'] = $_body_auth;
}

$actor = auth_require_quyen_su_kien($idSK_auth, 'nhap_diem');

// Lấy idGV từ idTK của người đang đăng nhập
$stmtGV = $conn->prepare("SELECT idGV FROM giangvien WHERE idTK = :idTK LIMIT 1");
$stmtGV->execute([':idTK' => $actor['idTK']]);
$gvRow = $stmtGV->fetch(PDO::FETCH_ASSOC);
if (!$gvRow) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tài khoản không thuộc hệ thống giảng viên',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$idGV = (int) $gvRow['idGV'];

// ── Router ────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
try {
    if ($method === 'GET') {
        handleGetRequest($conn, $idGV, $idSK_auth);
    } elseif ($method === 'POST') {
        handlePostRequest($conn, $idGV, $idSK_auth);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('API Error in nhap_diem.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}

// ─────────────────────────────────────────────────────────────
// GET HANDLERS
// ─────────────────────────────────────────────────────────────

function handleGetRequest($conn, $idGV, $idSK) {
    $action    = $_GET['action']       ?? 'lay_phieu_cham';
    $idVongThi = isset($_GET['id_vong_thi']) ? (int) $_GET['id_vong_thi'] : 0;
    $idSanPham = isset($_GET['id_san_pham'])  ? (int) $_GET['id_san_pham']  : 0;

    switch ($action) {
        case 'lay_phieu_cham':
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk hoặc id_vong_thi', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            $data = nhapDiem_layDanhSach($conn, $idGV, $idSK, $idVongThi);
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'chi_tiet_san_pham':
            if ($idSK <= 0 || $idVongThi <= 0 || $idSanPham <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk, id_vong_thi hoặc id_san_pham', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            $data = nhapDiem_layChiTiet($conn, $idGV, $idSK, $idVongThi, $idSanPham);
            if ($data === null) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Bạn không được phân công chấm sản phẩm này', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}

// ─────────────────────────────────────────────────────────────
// POST HANDLERS
// ─────────────────────────────────────────────────────────────

function handlePostRequest($conn, $idGV, $idSK) {
    $input = $_SERVER['_PARSED_INPUT'] ?? json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    $action    = $input['action']       ?? '';
    $idVongThi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;
    $idSanPham = isset($input['id_san_pham'])  ? (int) $input['id_san_pham']  : 0;

    if ($idVongThi <= 0 || $idSanPham <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Thiếu id_vong_thi hoặc id_san_pham', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    switch ($action) {
        case 'luu_diem':
            $dsDiem = $input['diem'] ?? [];
            if (empty($dsDiem) || !is_array($dsDiem)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Không có dữ liệu điểm', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            $result = nhapDiem_luuDiem($conn, $idGV, $idSK, $idVongThi, $idSanPham, $dsDiem);
            echo json_encode([
                'status'  => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'nop_phieu':
            $result = nhapDiem_nopPhieu($conn, $idGV, $idSK, $idVongThi, $idSanPham);
            echo json_encode([
                'status'  => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => $result['success'] ? ['diemPhanQuyet' => $result['diemPhanQuyet'] ?? null] : null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}

// ─────────────────────────────────────────────────────────────
// BUSINESS LOGIC
// ─────────────────────────────────────────────────────────────

/**
 * Lấy phancongcham + danh sách sản phẩm GV được phân công trong vòng thi.
 */
function nhapDiem_layDanhSach($conn, $idGV, $idSK, $idVongThi) {
    // Lấy bản ghi phancongcham của GV trong vòng này
    $stmtPCC = $conn->prepare(
        "SELECT pcc.idPhanCongCham, pcc.idBoTieuChi, pcc.trangThaiXacNhan,
                b.tenBoTieuChi
         FROM   phancongcham pcc
         INNER JOIN botieuchi b ON pcc.idBoTieuChi = b.idBoTieuChi
         WHERE  pcc.idGV = :idGV AND pcc.idSK = :idSK
            AND pcc.idVongThi = :idVongThi AND pcc.isActive = 1
         LIMIT 1"
    );
    $stmtPCC->execute([':idGV' => $idGV, ':idSK' => $idSK, ':idVongThi' => $idVongThi]);
    $pccRow = $stmtPCC->fetch(PDO::FETCH_ASSOC);

    if (!$pccRow) {
        return ['phancongcham' => null, 'dsSanPham' => []];
    }

    $idPhanCongCham = (int) $pccRow['idPhanCongCham'];
    $idBoTieuChi    = (int) $pccRow['idBoTieuChi'];

    // Tổng số tiêu chí trong bộ (để tính % hoàn thành)
    $stmtSoTC = $conn->prepare("SELECT COUNT(*) FROM botieuchi_tieuchi WHERE idBoTieuChi = :idBo");
    $stmtSoTC->execute([':idBo' => $idBoTieuChi]);
    $soTieuChi = (int) $stmtSoTC->fetchColumn();

    // Danh sách sản phẩm GV được phân công
    $sql = "SELECT
                sp.idSanPham,
                sp.tensanpham,
                n.manhom,
                pd.trangThaiCham,
                pd.isTrongTai,
                COUNT(ct.idChamDiem) AS soTieuChiDaCham
            FROM   phancong_doclap pd
            INNER JOIN sanpham sp ON pd.idSanPham = sp.idSanPham
            INNER JOIN nhom   n  ON sp.idNhom    = n.idnhom
            LEFT  JOIN chamtieuchi ct
                    ON ct.idSanPham       = sp.idSanPham
                   AND ct.idPhanCongCham  = :idPhanCongCham
            WHERE  pd.idGV      = :idGV
               AND pd.idVongThi = :idVongThi
            GROUP  BY sp.idSanPham, sp.tensanpham, n.manhom, pd.trangThaiCham, pd.isTrongTai
            ORDER  BY sp.idSanPham ASC";
    $stmtSP = $conn->prepare($sql);
    $stmtSP->execute([
        ':idGV'          => $idGV,
        ':idVongThi'     => $idVongThi,
        ':idPhanCongCham' => $idPhanCongCham,
    ]);
    $dsSanPham = $stmtSP->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dsSanPham as &$sp) {
        $sp['soTieuChiDaCham'] = (int) $sp['soTieuChiDaCham'];
        $sp['soTieuChi']       = $soTieuChi;
        $sp['daChamHet']       = $soTieuChi > 0 && $sp['soTieuChiDaCham'] >= $soTieuChi;
        $sp['isTrongTai']      = (bool) $sp['isTrongTai'];
    }
    unset($sp);

    return [
        'phancongcham' => $pccRow,
        'dsSanPham'    => $dsSanPham,
    ];
}

/**
 * Lấy chi tiết phiếu chấm: tiêu chí + điểm đã nhập của GV cho sản phẩm đó.
 * Trả về null nếu GV không được phân công chấm sản phẩm này.
 */
function nhapDiem_layChiTiet($conn, $idGV, $idSK, $idVongThi, $idSanPham) {
    // Xác nhận GV được phân công SP này + lấy trạng thái per-SP
    $stmtPD = $conn->prepare(
        "SELECT isTrongTai, trangThaiCham, ngayNop FROM phancong_doclap
         WHERE idGV = :idGV AND idVongThi = :idVongThi AND idSanPham = :idSanPham
         LIMIT 1"
    );
    $stmtPD->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi, ':idSanPham' => $idSanPham]);
    $pdRow = $stmtPD->fetch(PDO::FETCH_ASSOC);
    if (!$pdRow) {
        return null;
    }

    // Lấy phancongcham
    $stmtPCC = $conn->prepare(
        "SELECT pcc.idPhanCongCham, pcc.idBoTieuChi, pcc.trangThaiXacNhan,
                b.tenBoTieuChi, b.moTa AS moTaBo
         FROM   phancongcham pcc
         INNER JOIN botieuchi b ON pcc.idBoTieuChi = b.idBoTieuChi
         WHERE  pcc.idGV = :idGV AND pcc.idSK = :idSK
            AND pcc.idVongThi = :idVongThi AND pcc.isActive = 1
         LIMIT 1"
    );
    $stmtPCC->execute([':idGV' => $idGV, ':idSK' => $idSK, ':idVongThi' => $idVongThi]);
    $pccRow = $stmtPCC->fetch(PDO::FETCH_ASSOC);
    if (!$pccRow) {
        return null;
    }

    $idPhanCongCham = (int) $pccRow['idPhanCongCham'];
    $idBoTieuChi    = (int) $pccRow['idBoTieuChi'];

    // Tiêu chí của bộ + điểm đã nhập (LEFT JOIN chamtieuchi cho SP này)
    $sqlTC = "SELECT
                btc.idTieuChi,
                tc.noiDungTieuChi,
                btc.diemToiDa,
                btc.tyTrong,
                ct.idChamDiem,
                ct.diem,
                ct.nhanXet
              FROM   botieuchi_tieuchi btc
              INNER  JOIN tieuchi tc ON btc.idTieuChi = tc.idTieuChi
              LEFT   JOIN chamtieuchi ct
                       ON ct.idTieuChi      = btc.idTieuChi
                      AND ct.idPhanCongCham  = :idPhanCongCham
                      AND ct.idSanPham       = :idSanPham
              WHERE  btc.idBoTieuChi = :idBoTieuChi
              ORDER  BY btc.idTieuChi ASC";
    $stmtTC = $conn->prepare($sqlTC);
    $stmtTC->execute([
        ':idBoTieuChi'    => $idBoTieuChi,
        ':idPhanCongCham' => $idPhanCongCham,
        ':idSanPham'      => $idSanPham,
    ]);
    $dsTieuChi = $stmtTC->fetchAll(PDO::FETCH_ASSOC);

    // Thông tin sản phẩm
    $stmtSP = $conn->prepare(
        "SELECT sp.idSanPham, sp.tensanpham, sp.moTataiLieu, n.manhom
         FROM   sanpham sp
         INNER  JOIN nhom n ON sp.idNhom = n.idnhom
         WHERE  sp.idSanPham = :idSanPham
         LIMIT 1"
    );
    $stmtSP->execute([':idSanPham' => $idSanPham]);
    $spInfo = $stmtSP->fetch(PDO::FETCH_ASSOC);

    foreach ($dsTieuChi as &$tc) {
        $tc['diem']      = $tc['diem'] !== null ? (float) $tc['diem'] : null;
        $tc['diemToiDa'] = (float) $tc['diemToiDa'];
        $tc['tyTrong']   = (float) $tc['tyTrong'];
    }
    unset($tc);

    $result = [
        'sanPham'         => $spInfo,
        'phancongcham'    => $pccRow,
        'isTrongTai'      => (bool) $pdRow['isTrongTai'],
        'trangThaiChamSP' => $pdRow['trangThaiCham'],
        'ngayNop'         => $pdRow['ngayNop'],
        'dsTieuChi'       => $dsTieuChi,
    ];

    // Trọng tài: bổ sung bức tranh tổng quát để TT có context phán quyết
    if ((int) $pdRow['isTrongTai'] === 1) {
        $result['bongTranh']      = nhapDiem_layBongTranhGiamKhao($conn, $idSanPham, $idVongThi, $idBoTieuChi);
        $result['maTranCanhBao']  = nhapDiem_tinhMaTranCanhBao($result['bongTranh']);
    }

    return $result;
}

/**
 * Lấy điểm từng tiêu chí của tất cả giám khảo CHÍNH (isTrongTai=0) cho một sản phẩm.
 * Dùng để hiển thị "bức tranh tổng quát" cho trọng tài phúc khảo.
 *
 * @return array [{ idGV, tenGV, tongDiem, chiTiet: [{ idTieuChi, noiDungTieuChi, diem, diemToiDa, nhanXet }] }]
 */
function nhapDiem_layBongTranhGiamKhao($conn, $idSanPham, $idVongThi, $idBoTieuChi) {
    // Lấy danh sách GK chính đã thực sự chấm bài này.
    // Logic:
    //   - Nguồn sự thật: chamtieuchi (records điểm thực tế) → JOIN ngược lên phancongcham
    //   - idVongThi từ phancongcham để đảm bảo đúng vòng
    //   - Loại trừ TT: pcc.idGV có record trong phancong_doclap với isTrongTai=1 cho bài này
    //   - KHÔNG phụ thuộc phancong_doclap có đủ records hay không (tương thích data cũ)
    $sqlGK = "SELECT pcc.idGV, pcc.idPhanCongCham, gv.tenGV
              FROM phancongcham pcc
              INNER JOIN giangvien gv ON gv.idGV = pcc.idGV
              WHERE pcc.idVongThi = :idVongThi
                AND pcc.isActive  = 1
                AND EXISTS (
                    SELECT 1 FROM chamtieuchi ct
                    WHERE ct.idPhanCongCham = pcc.idPhanCongCham
                      AND ct.idSanPham      = :idSanPham
                )
                AND pcc.idGV NOT IN (
                    SELECT pd_tt.idGV FROM phancong_doclap pd_tt
                    WHERE pd_tt.idVongThi  = :idVongThi2
                      AND pd_tt.idSanPham  = :idSanPham2
                      AND pd_tt.isTrongTai = 1
                )
              ORDER BY pcc.idGV ASC, pcc.idPhanCongCham ASC";

    $stmtGK = $conn->prepare($sqlGK);
    $stmtGK->execute([
        ':idVongThi'  => $idVongThi,
        ':idSanPham'  => $idSanPham,
        ':idVongThi2' => $idVongThi,
        ':idSanPham2' => $idSanPham,
    ]);
    $dsGKRaw = $stmtGK->fetchAll(PDO::FETCH_ASSOC);

    // Dedup theo idGV — giữ idPhanCongCham nhỏ nhất (trường hợp GV có nhiều PCC records)
    $seenGV = [];
    $dsGK   = [];
    foreach ($dsGKRaw as $row) {
        if (!isset($seenGV[$row['idGV']])) {
            $seenGV[$row['idGV']] = true;
            $dsGK[] = $row;
        }
    }

    $bongTranh = [];
    foreach ($dsGK as $gk) {
        // Lấy điểm từng tiêu chí của GK này, JOIN với botieuchi_tieuchi để lấy đủ thông tin
        $sqlDiem = "SELECT
                        btc.idTieuChi,
                        tc.noiDungTieuChi,
                        btc.diemToiDa,
                        ct.diem,
                        ct.nhanXet
                    FROM botieuchi_tieuchi btc
                    INNER JOIN tieuchi tc ON tc.idTieuChi = btc.idTieuChi
                    LEFT JOIN chamtieuchi ct
                        ON ct.idTieuChi = btc.idTieuChi
                        AND ct.idPhanCongCham = :idPCC
                        AND ct.idSanPham = :idSanPham
                    WHERE btc.idBoTieuChi = :idBoTieuChi
                    ORDER BY btc.idTieuChi ASC";
        $stmtDiem = $conn->prepare($sqlDiem);
        $stmtDiem->execute([
            ':idPCC'        => $gk['idPhanCongCham'],
            ':idSanPham'    => $idSanPham,
            ':idBoTieuChi'  => $idBoTieuChi,
        ]);
        $chiTiet = $stmtDiem->fetchAll(PDO::FETCH_ASSOC);

        $tongDiem = 0.0;
        foreach ($chiTiet as &$tc) {
            $tc['diem']      = $tc['diem'] !== null ? (float) $tc['diem'] : null;
            $tc['diemToiDa'] = (float) $tc['diemToiDa'];
            if ($tc['diem'] !== null) $tongDiem += $tc['diem'];
        }
        unset($tc);

        $bongTranh[] = [
            'idGV'     => (int) $gk['idGV'],
            'tenGV'    => $gk['tenGV'],
            'tongDiem' => round($tongDiem, 2),
            'chiTiet'  => $chiTiet,
        ];
    }
    return $bongTranh;
}

/**
 * Tính ma trận cảnh báo: tiêu chí nào có độ lệch điểm cao (> 30%) giữa các GK chính.
 * Trọng tài cần biết để tập trung phán quyết vào đúng điểm mấu chốt.
 *
 * @param array $bongTranh Kết quả từ nhapDiem_layBongTranhGiamKhao()
 * @return array [{ idTieuChi, avgDiem, deviationPct, isHighDeviation }]
 */
function nhapDiem_tinhMaTranCanhBao(array $bongTranh): array {
    if (count($bongTranh) < 2) return [];

    // Gom điểm theo từng tiêu chí
    $byTieuChi = [];
    foreach ($bongTranh as $gk) {
        foreach ($gk['chiTiet'] as $tc) {
            if ($tc['diem'] === null) continue;
            $byTieuChi[$tc['idTieuChi']]['scores'][]      = $tc['diem'];
            $byTieuChi[$tc['idTieuChi']]['diemToiDa']     = $tc['diemToiDa'];
            $byTieuChi[$tc['idTieuChi']]['noiDungTieuChi'] = $tc['noiDungTieuChi'];
        }
    }

    $result = [];
    foreach ($byTieuChi as $idTC => $data) {
        $scores = $data['scores'];
        $avg    = array_sum($scores) / count($scores);
        $devPct = ($avg > 0 && count($scores) >= 2)
            ? ((max($scores) - min($scores)) / $avg) * 100.0
            : 0.0;

        $result[] = [
            'idTieuChi'       => $idTC,
            'noiDungTieuChi'  => $data['noiDungTieuChi'],
            'diemToiDa'       => $data['diemToiDa'],
            'avgDiem'         => round($avg, 2),
            'deviationPct'    => round($devPct, 1),
            'isHighDeviation' => $devPct > 30.0,
        ];
    }
    return $result;
}

/**
 * Lưu điểm từng tiêu chí (auto-save).
 * Dùng SELECT + UPDATE/INSERT vì chamtieuchi chưa có UNIQUE constraint.
 */
function nhapDiem_luuDiem($conn, $idGV, $idSK, $idVongThi, $idSanPham, $dsDiem) {
    try {
        // Xác nhận GV được phân công SP này + lấy trạng thái per-SP
        $stmtPD = $conn->prepare(
            "SELECT trangThaiCham FROM phancong_doclap
             WHERE idGV = :idGV AND idVongThi = :idVongThi AND idSanPham = :idSanPham
             LIMIT 1"
        );
        $stmtPD->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi, ':idSanPham' => $idSanPham]);
        $pdRow = $stmtPD->fetch(PDO::FETCH_ASSOC);
        if (!$pdRow) {
            return ['success' => false, 'message' => 'Bạn không được phân công chấm sản phẩm này'];
        }
        // Không cho lưu nếu bài này đã chốt điểm
        if ($pdRow['trangThaiCham'] === 'Đã xác nhận') {
            return ['success' => false, 'message' => 'Điểm bài này đã được chốt. Không thể chỉnh sửa.'];
        }

        // Lấy phancongcham (cho thông tin bộ tiêu chí)
        $stmtPCC = $conn->prepare(
            "SELECT idPhanCongCham, idBoTieuChi FROM phancongcham
             WHERE idGV = :idGV AND idSK = :idSK AND idVongThi = :idVongThi AND isActive = 1
             LIMIT 1"
        );
        $stmtPCC->execute([':idGV' => $idGV, ':idSK' => $idSK, ':idVongThi' => $idVongThi]);
        $pccRow = $stmtPCC->fetch(PDO::FETCH_ASSOC);
        if (!$pccRow) {
            return ['success' => false, 'message' => 'Không tìm thấy phân công chấm. Vui lòng liên hệ BTC.'];
        }
        $idPhanCongCham = (int) $pccRow['idPhanCongCham'];
        $idBoTieuChi    = (int) $pccRow['idBoTieuChi'];

        // Validate tất cả điểm trước khi mở transaction (Fail Fast)
        $validatedDiem = [];
        $stmtMaxDiem = $conn->prepare(
            "SELECT diemToiDa FROM botieuchi_tieuchi
             WHERE idBoTieuChi = :idBo AND idTieuChi = :idTC
             LIMIT 1"
        );
        foreach ($dsDiem as $item) {
            $idTieuChi = isset($item['id_tieu_chi']) ? (int) $item['id_tieu_chi'] : 0;
            $diem      = isset($item['diem'])        ? $item['diem']              : null;
            $nhanXet   = isset($item['nhan_xet'])    ? trim((string) $item['nhan_xet']) : '';
            if ($idTieuChi <= 0 || $diem === null || $diem === '') continue;
            $diem = (float) $diem;
            $stmtMaxDiem->execute([':idBo' => $idBoTieuChi, ':idTC' => $idTieuChi]);
            $maxRow = $stmtMaxDiem->fetch(PDO::FETCH_ASSOC);
            if (!$maxRow) {
                return ['success' => false, 'message' => "Tiêu chí #{$idTieuChi} không thuộc bộ tiêu chí được phân công"];
            }
            $diemToiDa = (float) $maxRow['diemToiDa'];
            if ($diem < 0 || ($diemToiDa > 0 && $diem > $diemToiDa)) {
                return ['success' => false, 'message' => "Điểm tiêu chí #{$idTieuChi} không hợp lệ (0 – {$diemToiDa})"];
            }
            $validatedDiem[] = ['idTieuChi' => $idTieuChi, 'diem' => $diem, 'nhanXet' => $nhanXet];
        }
        if (empty($validatedDiem)) {
            return ['success' => false, 'message' => 'Không có điểm hợp lệ để lưu'];
        }

        $conn->beginTransaction();

        // Flush: xóa toàn bộ điểm cũ của SP này trong phiếu (cơ chế Flush & Insert)
        $stmtFlush = $conn->prepare(
            "DELETE FROM chamtieuchi WHERE idPhanCongCham = :idPCC AND idSanPham = :idSP"
        );
        $stmtFlush->execute([':idPCC' => $idPhanCongCham, ':idSP' => $idSanPham]);

        // Insert: thêm mới toàn bộ điểm đã được validate
        $stmtInsert = $conn->prepare(
            "INSERT INTO chamtieuchi (idPhanCongCham, idSanPham, idTieuChi, diem, nhanXet, thoiGianCham)
             VALUES (:idPCC, :idSP, :idTC, :diem, :nhanXet, NOW())"
        );
        foreach ($validatedDiem as $item) {
            $stmtInsert->execute([
                ':idPCC'   => $idPhanCongCham,
                ':idSP'    => $idSanPham,
                ':idTC'    => $item['idTieuChi'],
                ':diem'    => $item['diem'],
                ':nhanXet' => $item['nhanXet'],
            ]);
        }

        // Cập nhật trạng thái SP này → 'Đang chấm' (chỉ nếu đang ở 'Chờ chấm')
        $stmtUpdSP = $conn->prepare(
            "UPDATE phancong_doclap SET trangThaiCham = 'Đang chấm'
             WHERE idGV = :idGV AND idVongThi = :idVongThi AND idSanPham = :idSP
               AND trangThaiCham = 'Chờ chấm'"
        );
        $stmtUpdSP->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi, ':idSP' => $idSanPham]);
        // Cập nhật aggregate phancongcham → 'Đang chấm' nếu mới bắt đầu
        $stmtUpdPCC = $conn->prepare(
            "UPDATE phancongcham SET trangThaiXacNhan = 'Đang chấm'
             WHERE idPhanCongCham = :idPCC
               AND trangThaiXacNhan NOT IN ('Đang chấm', 'Đã xác nhận')"
        );
        $stmtUpdPCC->execute([':idPCC' => $idPhanCongCham]);

        $conn->commit();
        return ['success' => true, 'message' => 'Lưu nháp thành công'];

    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Error in nhapDiem_luuDiem: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi lưu điểm'];
    }
}

/**
 * Đánh dấu GV đã hoàn thành chấm điểm cho sản phẩm.
 * Yêu cầu nhập đủ điểm tất cả tiêu chí mới cho nộp.
 * Nếu là trọng tài (isTrongTai=1): sau khi nộp, điểm phán quyết TT sẽ được
 * ghi thẳng vào sanpham_vongthi như điểm chốt cuối cùng.
 */
function nhapDiem_nopPhieu($conn, $idGV, $idSK, $idVongThi, $idSanPham) {
    try {
        // Xác nhận GV được phân công SP này + kiểm tra trạng thái per-SP
        $stmtPD = $conn->prepare(
            "SELECT trangThaiCham, isTrongTai FROM phancong_doclap
             WHERE idGV = :idGV AND idVongThi = :idVongThi AND idSanPham = :idSanPham
             LIMIT 1"
        );
        $stmtPD->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi, ':idSanPham' => $idSanPham]);
        $pdRow = $stmtPD->fetch(PDO::FETCH_ASSOC);
        if (!$pdRow) {
            return ['success' => false, 'message' => 'Bạn không được phân công chấm sản phẩm này'];
        }
        // Không cho nộp lại nếu bài này đã chốt điểm
        if ($pdRow['trangThaiCham'] === 'Đã xác nhận') {
            return ['success' => false, 'message' => 'Điểm bài này đã được chốt trước đó.'];
        }

        $isTrongTai = (int) $pdRow['isTrongTai'] === 1;

        // Lấy phancongcham (cho thông tin bộ tiêu chí)
        $stmtPCC = $conn->prepare(
            "SELECT idPhanCongCham, idBoTieuChi FROM phancongcham
             WHERE idGV = :idGV AND idSK = :idSK AND idVongThi = :idVongThi AND isActive = 1
             LIMIT 1"
        );
        $stmtPCC->execute([':idGV' => $idGV, ':idSK' => $idSK, ':idVongThi' => $idVongThi]);
        $pccRow = $stmtPCC->fetch(PDO::FETCH_ASSOC);
        if (!$pccRow) {
            return ['success' => false, 'message' => 'Không tìm thấy phân công chấm'];
        }
        $idPhanCongCham = (int) $pccRow['idPhanCongCham'];
        $idBoTieuChi    = (int) $pccRow['idBoTieuChi'];

        // Kiểm tra đã nhập đủ tiêu chí chưa
        $stmtSoTC = $conn->prepare("SELECT COUNT(*) FROM botieuchi_tieuchi WHERE idBoTieuChi = :idBo");
        $stmtSoTC->execute([':idBo' => $idBoTieuChi]);
        $soTieuChi = (int) $stmtSoTC->fetchColumn();

        $stmtDone = $conn->prepare(
            "SELECT COUNT(*) FROM chamtieuchi
             WHERE idPhanCongCham = :idPCC AND idSanPham = :idSP"
        );
        $stmtDone->execute([':idPCC' => $idPhanCongCham, ':idSP' => $idSanPham]);
        $soDaCham = (int) $stmtDone->fetchColumn();

        if ($soDaCham < $soTieuChi) {
            return [
                'success' => false,
                'message' => "Chưa chấm đủ tiêu chí ({$soDaCham}/{$soTieuChi}). "
                           . 'Vui lòng nhập điểm cho tất cả tiêu chí trước khi nộp phiếu.',
            ];
        }

        $conn->beginTransaction();

        // Chốt điểm bài này độc lập
        $stmtNop = $conn->prepare(
            "UPDATE phancong_doclap
             SET trangThaiCham = 'Đã xác nhận', ngayNop = NOW()
             WHERE idGV = :idGV AND idVongThi = :idVongThi AND idSanPham = :idSanPham"
        );
        $stmtNop->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi, ':idSanPham' => $idSanPham]);

        // Cập nhật aggregate: nếu tất cả SP trong vòng này đã chốt → phancongcham cũng ghi nhận
        $stmtConLai = $conn->prepare(
            "SELECT COUNT(*) FROM phancong_doclap
             WHERE idGV = :idGV AND idVongThi = :idVongThi AND trangThaiCham != 'Đã xác nhận'"
        );
        $stmtConLai->execute([':idGV' => $idGV, ':idVongThi' => $idVongThi]);
        if ((int) $stmtConLai->fetchColumn() === 0) {
            $stmtUpdAll = $conn->prepare(
                "UPDATE phancongcham SET trangThaiXacNhan = 'Đã xác nhận', ngayXacNhan = NOW()
                 WHERE idPhanCongCham = :idPCC"
            );
            $stmtUpdAll->execute([':idPCC' => $idPhanCongCham]);
        }

        // ── Trọng tài: cập nhật sanpham_vongthi với điểm phán quyết ──────────────
        if ($isTrongTai) {
            // Tính tổng điểm TT vừa nộp
            $stmtDiemTT = $conn->prepare(
                "SELECT SUM(diem) as tongDiem FROM chamtieuchi
                 WHERE idPhanCongCham = :idPCC AND idSanPham = :idSP"
            );
            $stmtDiemTT->execute([':idPCC' => $idPhanCongCham, ':idSP' => $idSanPham]);
            $diemTTRow = $stmtDiemTT->fetch(PDO::FETCH_ASSOC);
            $diemPhanQuyet = $diemTTRow['tongDiem'] !== null ? round((float) $diemTTRow['tongDiem'], 2) : null;

            if ($diemPhanQuyet !== null) {
                // Ghi điểm phán quyết vào sanpham_vongthi, đánh dấu 'Đã phúc khảo'
                $stmtUpd = $conn->prepare(
                    "INSERT INTO sanpham_vongthi (idSanPham, idVongThi, diemTrungBinh, trangThai, ngayCapNhat)
                     VALUES (:idSP, :idVT, :diem, 'Đã phúc khảo', NOW())
                     ON DUPLICATE KEY UPDATE
                         diemTrungBinh = :diem2,
                         trangThai     = 'Đã phúc khảo',
                         ngayCapNhat   = NOW()"
                );
                $stmtUpd->execute([
                    ':idSP'  => $idSanPham,
                    ':idVT'  => $idVongThi,
                    ':diem'  => $diemPhanQuyet,
                    ':diem2' => $diemPhanQuyet,
                ]);
            }

            $conn->commit();
            return [
                'success'         => true,
                'message'         => 'Phán quyết trọng tài đã được ghi nhận. Điểm cuối cùng đã được cập nhật.',
                'diemPhanQuyet'   => $diemPhanQuyet,
            ];
        }

        $conn->commit();
        return ['success' => true, 'message' => 'Nộp phiếu chấm thành công.'];

    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Error in nhapDiem_nopPhieu: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống khi nộp phiếu'];
    }
}
