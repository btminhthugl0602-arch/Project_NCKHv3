<?php

/**
 * API Đăng nhập / Đăng xuất
 * api/tai_khoan/dang_nhap.php
 *
 * POST   → Đăng nhập (thường + khách)
 * DELETE → Đăng xuất
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// DELETE: Đăng xuất
// ============================================================
if ($method === 'DELETE') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Đăng xuất thành công',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// Chỉ cho phép POST
// ============================================================
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Phương thức không được hỗ trợ',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// POST: Đăng nhập
// ============================================================

$redirect = trim($_POST['redirect'] ?? '/dashboard');

// --- Nhánh khách ---
if (!empty($_POST['guest'])) {
    session_regenerate_id(true);
    $_SESSION['role'] = 'guest';

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Tiếp tục với tư cách khách',
        'data'    => [
            'role'     => 'guest',
            'redirect' => '/su-kien',
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 1: Validate input ---
$tenTK   = trim($_POST['tenTK'] ?? '');
$matKhau = $_POST['matKhau'] ?? '';

if ($tenTK === '' || $matKhau === '') {
    http_response_code(422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 2: Tìm tài khoản ---
try {
    $stmt = $conn->prepare('
        SELECT tk.idTK, tk.tenTK, tk.matKhau, tk.isActive, tk.idLoaiTK,
               ltk.tenLoaiTK
        FROM taikhoan tk
        LEFT JOIN loaitaikhoan ltk ON ltk.idLoaiTK = tk.idLoaiTK
        WHERE tk.tenTK = ?
        LIMIT 1
    ');
    $stmt->execute([$tenTK]);
    $taiKhoan = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Lỗi đăng nhập - truy vấn DB: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Lỗi hệ thống, vui lòng thử lại',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 3: Kiểm tra tồn tại ---
if (!$taiKhoan) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tên đăng nhập hoặc mật khẩu không đúng',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 4: Kiểm tra bị khóa ---
if ((int) $taiKhoan['isActive'] !== 1) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 5: Xác minh mật khẩu ---
// Hỗ trợ cả password_hash (production) lẫn plain text (seed data / dev)
$isValidHash  = password_verify($matKhau, $taiKhoan['matKhau']);
$isValidPlain = !$isValidHash && ($matKhau === $taiKhoan['matKhau']);

if (!$isValidHash && !$isValidPlain) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tên đăng nhập hoặc mật khẩu không đúng',
        'data'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Bước 6: Lấy họ tên từ profile ---
$hoTen = $taiKhoan['tenTK']; // fallback
try {
    $idLoaiTK = (int) $taiKhoan['idLoaiTK'];
    if ($idLoaiTK === 3) {
        $stmtHoTen = $conn->prepare('SELECT tenSV FROM sinhvien WHERE idTK = ? LIMIT 1');
    } elseif ($idLoaiTK === 2) {
        $stmtHoTen = $conn->prepare('SELECT tenGV AS tenSV FROM giangvien WHERE idTK = ? LIMIT 1');
    } else {
        $stmtHoTen = null;
    }
    if ($stmtHoTen) {
        $stmtHoTen->execute([$taiKhoan['idTK']]);
        $profile = $stmtHoTen->fetch(PDO::FETCH_ASSOC);
        if ($profile && !empty($profile['tenSV'])) {
            $hoTen = $profile['tenSV'];
        }
    }
} catch (Throwable $e) {
    // Không critical — fallback về tenTK
}

// --- Bước 7: Tạo session ---
session_regenerate_id(true);
$_SESSION['idTK']      = (int) $taiKhoan['idTK'];
$_SESSION['idLoaiTK']  = (int) $taiKhoan['idLoaiTK'];
$_SESSION['hoTen']     = $hoTen;

// --- Bước 7: Trả kết quả ---
http_response_code(200);
echo json_encode([
    'status'  => 'success',
    'message' => 'Đăng nhập thành công',
    'data'    => [
        'idTK'      => (int) $taiKhoan['idTK'],
        'tenTK'     => $taiKhoan['tenTK'],
        'idLoaiTK'  => (int) $taiKhoan['idLoaiTK'],
        'tenLoaiTK' => $taiKhoan['tenLoaiTK'],
        'redirect'  => $redirect,
    ],
], JSON_UNESCAPED_UNICODE);