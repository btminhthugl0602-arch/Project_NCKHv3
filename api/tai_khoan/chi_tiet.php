<?php

/**
 * API: Chi tiết & cập nhật tài khoản
 *
 * GET  ?id=X                → Lấy thông tin cá nhân + quyền
 * GET  ?id=X&action=su_kien → Lấy danh sách sự kiện đang tham gia
 * PUT  (body JSON)          → Cập nhật thông tin cá nhân (chỉ tài khoản của chính mình)
 * POST ?action=doi_mat_khau → Đổi mật khẩu
 */

define('_AUTHEN', true);
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/session_helper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn, $session_user);
        break;
    case 'PUT':
        handlePut($conn, $session_user);
        break;
    case 'POST':
        handlePost($conn, $session_user);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
}

// ─────────────────────────────────────────────
// GET: Thông tin cá nhân hoặc danh sách sự kiện
// ─────────────────────────────────────────────
function handleGet(PDO $conn, array $session_user): void
{
    $idTK   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $action = $_GET['action'] ?? '';

    if ($idTK <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Thiếu tham số id', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Chỉ được xem của chính mình, trừ Admin
    if ($session_user['idTK'] !== $idTK && (int) $session_user['idLoaiTK'] !== 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Không có quyền xem thông tin này', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        if ($action === 'su_kien') {
            getDanhSachSuKien($conn, $idTK);
        } else {
            getThongTinCaNhan($conn, $idTK);
        }
    } catch (Throwable $e) {
        error_log('chi_tiet.php GET error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}

function getThongTinCaNhan(PDO $conn, int $idTK): void
{
    $stmt = $conn->prepare(
        'SELECT
            tk.idTK,
            tk.tenTK,
            tk.idLoaiTK,
            tk.isActive,
            tk.ngayTao,
            COALESCE(sv.tenSV, gv.tenGV, NULL) AS hoTen,
            sv.MSV   AS maSV,
            sv.GPA,
            sv.DRL,
            l.idLop,
            l.tenLop,
            gv.hocHam,
            k.idKhoa,
            k.tenKhoa
        FROM taikhoan tk
        LEFT JOIN sinhvien  sv ON sv.idTK  = tk.idTK
        LEFT JOIN giangvien gv ON gv.idTK  = tk.idTK
        LEFT JOIN lop        l ON l.idLop  = sv.idLop
        LEFT JOIN khoa       k ON k.idKhoa = COALESCE(gv.idKhoa, sv.idKhoa)
        WHERE tk.idTK = :idTK
        LIMIT 1'
    );
    $stmt->execute([':idTK' => $idTK]);
    $tk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tk) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmtQ = $conn->prepare(
        'SELECT q.maQuyen
         FROM taikhoan_quyen tq
         JOIN quyen q ON q.idQuyen = tq.idQuyen
         WHERE tq.idTK = :idTK
           AND tq.isActive = 1
           AND q.phamVi = "HE_THONG"'
    );
    $stmtQ->execute([':idTK' => $idTK]);
    $tk['dsQuyen'] = $stmtQ->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $tk], JSON_UNESCAPED_UNICODE);
}

function getDanhSachSuKien(PDO $conn, int $idTK): void
{
    $stmt = $conn->prepare(
        'SELECT
            sk.idSK,
            sk.tenSK,
            sk.ngayBatDau,
            sk.ngayKetThuc,
            sk.isActive,
            vt.maVaiTro,
            vt.tenvaitro  AS tenVaiTro,
            tvs.nguonTao,
            tvs.ngayCap
        FROM taikhoan_vaitro_sukien tvs
        JOIN sukien sk ON sk.idSK     = tvs.idSK
        JOIN vaitro  vt ON vt.idVaiTro = tvs.idVaiTro
        WHERE tvs.idTK     = :idTK
          AND tvs.isActive = 1
          AND sk.isDeleted = 0
        ORDER BY tvs.ngayCap DESC'
    );
    $stmt->execute([':idTK' => $idTK]);
    $ds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $ds], JSON_UNESCAPED_UNICODE);
}

// ─────────────────────────────────────────────
// PUT: Cập nhật thông tin cá nhân
// ─────────────────────────────────────────────
function handlePut(PDO $conn, array $session_user): void
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $idTK = (int) ($body['idTK'] ?? 0);

    if ($idTK <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Thiếu tham số idTK', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Chỉ được sửa thông tin của chính mình
    if ($session_user['idTK'] !== $idTK) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Không có quyền cập nhật thông tin này', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $stmt = $conn->prepare('SELECT idLoaiTK FROM taikhoan WHERE idTK = :idTK LIMIT 1');
        $stmt->execute([':idTK' => $idTK]);
        $tk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tk) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
            return;
        }

        $idLoaiTK = (int) $tk['idLoaiTK'];
        $hoTen    = trim((string) ($body['hoTen'] ?? ''));

        if ($hoTen === '') {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Họ tên không được để trống', 'data' => null], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($idLoaiTK === 3) {
            $conn->prepare('UPDATE sinhvien SET tenSV = :hoTen WHERE idTK = :idTK')
                ->execute([':hoTen' => $hoTen, ':idTK' => $idTK]);
        } elseif ($idLoaiTK === 2) {
            $hocHam      = trim((string) ($body['hocHam'] ?? ''));
            $hocHamValid = ['Cu_nhan', 'Tha_si', 'Tien_si', 'Pho_giao_su', 'Giao_su', ''];
            if (!in_array($hocHam, $hocHamValid, true)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Học hàm không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            $conn->prepare('UPDATE giangvien SET tenGV = :hoTen, hocHam = :hocHam WHERE idTK = :idTK')
                ->execute([':hoTen' => $hoTen, ':hocHam' => $hocHam ?: null, ':idTK' => $idTK]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Loại tài khoản này không hỗ trợ cập nhật hồ sơ', 'data' => null], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Sync lại session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['hoTen'] = $hoTen;
        }

        echo json_encode(['status' => 'success', 'message' => 'Cập nhật thông tin thành công', 'data' => null], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('chi_tiet.php PUT error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}

// ─────────────────────────────────────────────
// POST: Đổi mật khẩu
// ─────────────────────────────────────────────
function handlePost(PDO $conn, array $session_user): void
{
    $action = $_GET['action'] ?? '';

    if ($action !== 'doi_mat_khau') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    $body           = json_decode(file_get_contents('php://input'), true) ?? [];
    $matKhauCu      = (string) ($body['matKhauCu']      ?? '');
    $matKhauMoi     = (string) ($body['matKhauMoi']     ?? '');
    $xacNhan        = (string) ($body['xacNhanMatKhau'] ?? '');

    if ($matKhauCu === '' || $matKhauMoi === '' || $xacNhan === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ các trường', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($matKhauMoi !== $xacNhan) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới không khớp', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    if (strlen($matKhauMoi) < 6) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự', 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $idTK = $session_user['idTK'];

        $stmt = $conn->prepare('SELECT matKhau FROM taikhoan WHERE idTK = :idTK LIMIT 1');
        $stmt->execute([':idTK' => $idTK]);
        $tk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tk) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại', 'data' => null], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Hỗ trợ cả hash lẫn plain text (seed data)
        $isValid = password_verify($matKhauCu, $tk['matKhau']) || ($matKhauCu === $tk['matKhau']);
        if (!$isValid) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu hiện tại không đúng', 'data' => null], JSON_UNESCAPED_UNICODE);
            return;
        }

        $conn->prepare('UPDATE taikhoan SET matKhau = :matKhau WHERE idTK = :idTK')
            ->execute([':matKhau' => password_hash($matKhauMoi, PASSWORD_DEFAULT), ':idTK' => $idTK]);

        echo json_encode(['status' => 'success', 'message' => 'Đổi mật khẩu thành công', 'data' => null], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('chi_tiet.php POST error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}
