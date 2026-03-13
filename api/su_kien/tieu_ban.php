<?php
/**
 * API Endpoint: Quản lý Tiểu ban
 * ============================================================
 *
 * GET  ?action=danh_sach&id_sk=X          → DS tiểu ban + GV + SP nhúng sẵn
 * GET  ?action=ds_giang_vien&id_sk=X      → toàn bộ GV của sự kiện
 * GET  ?action=ds_bo_tieu_chi             → danh sách bộ tiêu chí
 * GET  ?action=sp_chua_xep&id_sk=X        → SP đã duyệt chưa xếp phòng
 * GET  ?action=thong_ke&id_sk=X           → thống kê nhanh
 *
 * POST body JSON:
 *   tao          { id_sk, ten_tieu_ban, id_vong_thi, id_bo_tieu_chi?, ngay_bao_cao?, dia_diem?, mo_ta? }
 *   cap_nhat     { id_tieu_ban, ten_tieu_ban, id_bo_tieu_chi?, ngay_bao_cao?, dia_diem?, mo_ta? }
 *   xoa          { id_tieu_ban }
 *   them_gv      { id_tieu_ban, id_gv, vai_tro? }
 *   cap_nhat_vai_tro { id_tieu_ban, id_gv, vai_tro }
 *   xoa_gv       { id_tieu_ban, id_gv }
 *   them_sp      { id_tieu_ban, id_san_pham }
 *   them_nhieu_sp { id_tieu_ban, ids: [...] }
 *   xoa_sp       { id_tieu_ban, id_san_pham }
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/quan_ly_tieu_ban.php';

header('Content-Type: application/json; charset=utf-8');

// ── Xác định idSK để auth ────────────────────────────────────
$idSK_auth   = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$_parsedBody = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput    = file_get_contents('php://input');
    $_parsedBody = json_decode($rawInput, true) ?? [];

    if ($idSK_auth <= 0) {
        $idSK_auth = isset($_parsedBody['id_sk']) ? (int) $_parsedBody['id_sk'] : 0;
    }

    // Với actions không có id_sk: tra từ id_tieu_ban
    if ($idSK_auth <= 0 && isset($_parsedBody['id_tieu_ban'])) {
        $idTB_auth = (int) $_parsedBody['id_tieu_ban'];
        if ($idTB_auth > 0) {
            try {
                $st = $conn->prepare('SELECT idSK FROM tieuban WHERE idTieuBan = ? LIMIT 1');
                $st->execute([$idTB_auth]);
                $idSK_auth = (int) ($st->fetchColumn() ?: 0);
            } catch (Throwable $e) { /* fall through */ }
        }
    }
}

$actor = auth_require_bat_ky_quyen_su_kien($idSK_auth, ['quan_ly_tieuban', 'cauhinh_sukien']);

// ── Router ───────────────────────────────────────────────────
try {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        handleGet($conn, $actor);
    } elseif ($method === 'POST') {
        handlePost($conn, $actor, $_parsedBody);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('tieu_ban.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// GET handlers
// ============================================================
function handleGet($conn, $actor): void
{
    $action = trim($_GET['action'] ?? 'danh_sach');
    $idSK   = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

    switch ($action) {

        // ── Danh sách tiểu ban kèm GV + SP nhúng ────────────
        case 'danh_sach':
            if ($idSK <= 0) { _fail(400, 'Thiếu id_sk'); return; }

            $list        = tieuban_lay_danh_sach($conn, $idSK);
            $gvMap       = tieuban_lay_map_giang_vien($conn, $idSK);
            $assignedIds = [];
            $spMap       = tieuban_lay_map_san_pham($conn, $idSK, $assignedIds);

            foreach ($list as &$tb) {
                $id               = (int) $tb['idTieuBan'];
                $tb['giang_vien'] = $gvMap[$id] ?? [];
                $tb['san_pham']   = $spMap[$id] ?? [];
            }
            unset($tb);

            echo json_encode([
                'status'  => 'success',
                'message' => 'OK',
                'data'    => [
                    'tieuban_list' => $list,
                    'assigned_ids' => $assignedIds,
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Danh sách giảng viên ─────────────────────────────
        case 'ds_giang_vien':
            $list = tieuban_lay_ds_giang_vien($conn);
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $list], JSON_UNESCAPED_UNICODE);
            break;

        // ── Danh sách bộ tiêu chí ────────────────────────────
        case 'ds_bo_tieu_chi':
            $list = tieuban_lay_ds_bo_tieu_chi($conn);
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $list], JSON_UNESCAPED_UNICODE);
            break;

        // ── SP đủ điều kiện chưa xếp phòng ──────────────────
        case 'sp_chua_xep':
            if ($idSK <= 0) { _fail(400, 'Thiếu id_sk'); return; }
            $list = _sp_du_dieu_kien($conn, $idSK);
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $list], JSON_UNESCAPED_UNICODE);
            break;

        // ── Thống kê nhanh ───────────────────────────────────
        case 'thong_ke':
            if ($idSK <= 0) { _fail(400, 'Thiếu id_sk'); return; }
            $stats = tieuban_thong_ke($conn, $idSK);
            echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $stats], JSON_UNESCAPED_UNICODE);
            break;

        default:
            _fail(400, 'Action không hợp lệ: ' . $action);
    }
}

// ============================================================
// POST handlers
// ============================================================
function handlePost($conn, $actor, $body): void
{
    $action = trim($body['action'] ?? '');
    $idTK   = (int) $actor['idTK'];

    switch ($action) {

        // ── Tạo tiểu ban ─────────────────────────────────────
        case 'tao':
            $idSK        = (int) ($body['id_sk']        ?? 0);
            $ten         = trim($body['ten_tieu_ban']   ?? '');
            $idVongThi   = (int) ($body['id_vong_thi']  ?? 0);
            $idBoTieuChi = _nullableInt($body['id_bo_tieu_chi'] ?? null);
            $ngayBaoCao  = _nullableStr($body['ngay_bao_cao']   ?? null);
            $diaDiem     = _nullableStr($body['dia_diem']       ?? null);
            $moTa        = _nullableStr($body['mo_ta']          ?? null);

            $result = tieuban_tao($conn, $idTK, $idSK, $ten, $idVongThi, $idBoTieuChi, $ngayBaoCao, $diaDiem);

            if ($result['status'] && $moTa !== null && isset($result['idTieuBan'])) {
                $conn->prepare('UPDATE tieuban SET moTa = ? WHERE idTieuBan = ?')
                     ->execute([$moTa, $result['idTieuBan']]);
            }

            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => $result['status'] ? ['idTieuBan' => $result['idTieuBan']] : null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Cập nhật tiểu ban ────────────────────────────────
        case 'cap_nhat':
            $idTieuBan   = (int) ($body['id_tieu_ban'] ?? 0);
            $ten         = trim($body['ten_tieu_ban']  ?? '');
            $idBoTieuChi = _nullableInt($body['id_bo_tieu_chi'] ?? null);
            $ngayBaoCao  = _nullableStr($body['ngay_bao_cao']   ?? null);
            $diaDiem     = _nullableStr($body['dia_diem']       ?? null);
            $moTa        = isset($body['mo_ta']) ? trim($body['mo_ta']) : false;

            $result = tieuban_cap_nhat($conn, $idTK, $idTieuBan, $ten, $idBoTieuChi, $ngayBaoCao, $diaDiem);

            if ($result['status'] && $moTa !== false) {
                $conn->prepare('UPDATE tieuban SET moTa = ? WHERE idTieuBan = ?')
                     ->execute([$moTa ?: null, $idTieuBan]);
            }

            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Xóa tiểu ban ─────────────────────────────────────
        case 'xoa':
            $idTieuBan = (int) ($body['id_tieu_ban'] ?? 0);
            $result    = tieuban_xoa($conn, $idTK, $idTieuBan);
            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Thêm giảng viên ──────────────────────────────────
        case 'them_gv':
            $idTieuBan = (int) ($body['id_tieu_ban'] ?? 0);
            $idGV      = (int) ($body['id_gv']       ?? 0);
            $vaiTro    = _sanitizeVaiTro($body['vai_tro'] ?? 'Thành viên');
            $result    = tieuban_them_giang_vien($conn, $idTieuBan, $idGV, $vaiTro);
            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Cập nhật vai trò GV trong tiểu ban ──────────────
        case 'cap_nhat_vai_tro':
            $idTieuBan = (int) ($body['id_tieu_ban'] ?? 0);
            $idGV      = (int) ($body['id_gv']       ?? 0);
            $vaiTro    = _sanitizeVaiTro($body['vai_tro'] ?? '');
            if ($idTieuBan <= 0 || $idGV <= 0 || $vaiTro === '') {
                _fail(400, 'Dữ liệu không hợp lệ'); return;
            }
            $ok = _update_info($conn, 'tieuban_giangvien',
                ['vaiTro'], [$vaiTro],
                ['idTieuBan' => ['=', $idTieuBan, 'AND'], 'idGV' => ['=', $idGV]]
            );
            echo json_encode([
                'status'  => $ok ? 'success' : 'error',
                'message' => $ok ? 'Đã cập nhật vai trò' : 'Lỗi cập nhật vai trò',
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Xóa giảng viên ───────────────────────────────────
        case 'xoa_gv':
            $idTieuBan = (int) ($body['id_tieu_ban'] ?? 0);
            $idGV      = (int) ($body['id_gv']       ?? 0);
            $result    = tieuban_xoa_giang_vien($conn, $idTieuBan, $idGV);
            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Thêm 1 sản phẩm ──────────────────────────────────
        case 'them_sp':
            $idTieuBan = (int) ($body['id_tieu_ban']  ?? 0);
            $idSanPham = (int) ($body['id_san_pham']  ?? 0);
            $result    = tieuban_them_san_pham($conn, $idTieuBan, $idSanPham);
            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Thêm nhiều sản phẩm cùng lúc ────────────────────
        case 'them_nhieu_sp':
            $idTieuBan = (int) ($body['id_tieu_ban'] ?? 0);
            $ids       = is_array($body['ids'] ?? null) ? $body['ids'] : [];
            if ($idTieuBan <= 0 || empty($ids)) {
                _fail(400, 'Dữ liệu không hợp lệ'); return;
            }
            $ok = $fail = 0;
            $failMsgs = [];
            foreach ($ids as $idSP) {
                $r = tieuban_them_san_pham($conn, $idTieuBan, (int) $idSP);
                if ($r['status']) { $ok++; } else { $fail++; $failMsgs[] = $r['message']; }
            }
            echo json_encode([
                'status'  => 'success',
                'message' => "Đã xếp $ok bài" . ($fail ? ", $fail bài bị lỗi" : ''),
                'data'    => ['ok' => $ok, 'fail' => $fail, 'fail_messages' => array_unique($failMsgs)],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── Xóa sản phẩm ─────────────────────────────────────
        case 'xoa_sp':
            $idTieuBan = (int) ($body['id_tieu_ban']  ?? 0);
            $idSanPham = (int) ($body['id_san_pham']  ?? 0);
            $result    = tieuban_xoa_san_pham($conn, $idTieuBan, $idSanPham);
            echo json_encode([
                'status'  => $result['status'] ? 'success' : 'error',
                'message' => $result['message'],
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            _fail(400, 'Action không hợp lệ: ' . $action);
    }
}

// ============================================================
// Helpers nội bộ
// ============================================================

/**
 * Lấy sản phẩm đủ điều kiện báo cáo (DA_DUYET) và chưa xếp tiểu ban.
 * Mở rộng: cũng lấy bài đã đạt vòng thi trước (sanpham_vongthi).
 */
function _sp_du_dieu_kien($conn, int $idSK): array
{
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT sp.idSanPham, sp.tenSanPham, sp.trangThai,
                   n.manhom, ttn.tennhom
            FROM sanpham sp
            LEFT JOIN nhom n   ON sp.idNhom  = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            WHERE sp.idSK = :idSK
              AND (
                  sp.trangThai = 'DA_DUYET'
                  OR sp.idSanPham IN (
                      SELECT svt.idSanPham
                      FROM   sanpham_vongthi svt
                      JOIN   vongthi v ON svt.idVongThi = v.idVongThi
                      WHERE  v.idSK = :idSK2
                        AND  svt.trangThai IN ('Đã duyệt','DA_DUYET','Đạt')
                  )
              )
              AND sp.idSanPham NOT IN (
                  SELECT tbs.idSanPham
                  FROM   tieuban_sanpham tbs
                  JOIN   tieuban tb ON tbs.idTieuBan = tb.idTieuBan
                  WHERE  tb.idSK = :idSK3
              )
            ORDER BY sp.tenSanPham ASC
        ");
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK, ':idSK3' => $idSK]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('_sp_du_dieu_kien: ' . $e->getMessage());
        // fallback — dùng hàm đơn giản hơn
        return tieuban_lay_sp_chua_xep($conn, $idSK);
    }
}

function _nullableInt($val): ?int
{
    if ($val === null || $val === '' || $val === 'null') return null;
    $i = (int) $val;
    return $i > 0 ? $i : null;
}

function _nullableStr($val): ?string
{
    if ($val === null || trim((string)$val) === '') return null;
    return trim((string)$val);
}

function _sanitizeVaiTro(string $v): string
{
    $allowed = ['Thành viên', 'Trưởng tiểu ban', 'Thư ký'];
    return in_array($v, $allowed, true) ? $v : 'Thành viên';
}

function _fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $msg, 'data' => null], JSON_UNESCAPED_UNICODE);
}