<?php

/** 
 * API Phân công giám khảo chấm điểm
 * 
 * GET: Lấy danh sách sản phẩm, giám khảo, phân công
 * POST: Phân công/gỡ phân công/mời trọng tài
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/../thong_bao/notification_service.php';

require_once __DIR__ . '/quan_ly_cham_diem.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth: quyền BTC / cauhinh_sukien (parse idSK từ GET hoặc body) ──────────
$idSK_auth = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
if ($idSK_auth <= 0) {
    $_body_auth = json_decode(file_get_contents('php://input'), true) ?? [];
    $idSK_auth  = isset($_body_auth['id_sk']) ? (int) $_body_auth['id_sk'] : 0;
    // Lưu lại để handler dùng không cần parse lại
    $_SERVER['_PARSED_INPUT'] = $_body_auth;
}
$actor = auth_require_bat_ky_quyen_su_kien($idSK_auth, ['phan_cong_cham', 'cauhinh_sukien', 'duyet_diem']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetRequest($conn);
    } elseif ($method === 'POST') {
        handlePostRequest($conn, $actor);
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Phương thức không được hỗ trợ',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('API Error in phan_cong_giam_khao.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi hệ thống',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Xử lý GET request
 */
function handleGetRequest($conn)
{
    $action = $_GET['action'] ?? 'list_san_pham';
    $idSK = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
    $idVongThi = isset($_GET['id_vong_thi']) ? (int) $_GET['id_vong_thi'] : 0;
    $idSanPham = isset($_GET['id_san_pham']) ? (int) $_GET['id_san_pham'] : 0;

    switch ($action) {
        case 'list_san_pham':
            // Lấy danh sách sản phẩm cần chấm
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $data = cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'list_giang_vien':
            // Lấy danh sách giảng viên có thể phân công
            $data = cham_diem_lay_danh_sach_giang_vien($conn, $idSK);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách giảng viên thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'giam_khao_san_pham':
            // Lấy danh sách giám khảo đã phân công cho sản phẩm
            if ($idSanPham <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $data = cham_diem_lay_giam_khao_san_pham($conn, $idSanPham, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách giám khảo thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Action không hợp lệ',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Xử lý POST request
 */
function handlePostRequest($conn, array $actor)
{
    // Dùng lại body đã parse từ bước xác thực — php://input chỉ đọc được 1 lần.
    // Nếu auth không cần đọc body (id_sk đến từ GET), parse lần đầu ở đây.
    $input = isset($_SERVER['_PARSED_INPUT']) ? $_SERVER['_PARSED_INPUT'] : null;
    if ($input === null) {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Dữ liệu không hợp lệ',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $action = $input['action'] ?? '';
    $idSanPham = isset($input['id_san_pham']) ? (int) $input['id_san_pham'] : 0;
    $idGV = isset($input['id_gv']) ? (int) $input['id_gv'] : 0;
    $idVongThi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;

    // Validate common params
    if (in_array($action, ['assign_doclap', 'remove_doclap', 'add_3rd_judge']) && ($idSanPham <= 0 || $idGV <= 0 || $idVongThi <= 0)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Thiếu hoặc sai tham số id_san_pham, id_gv, id_vong_thi',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    switch ($action) {
        case 'assign_doclap':
            // Phân công giám khảo chấm độc lập
            $result = cham_diem_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi);

            if (!empty($result['success']) && notification_feature_enabled('scoring')) {
                try {
                    $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
                    $idTKReviewer = lay_id_tk_theo_id_gv($conn, $idGV);
                    if ($idTKReviewer > 0) {
                        dispatch_personal($conn, [
                            'tieuDe' => 'Ban vua duoc phan cong cham diem',
                            'noiDung' => 'Ban vua duoc phan cong cham mot bai thi. Vui long vao muc Cham diem de xu ly.',
                            'loaiThongBao' => 'CA_NHAN',
                            'idSK' => $idSK,
                            'loaiDoiTuong' => 'SANPHAM',
                            'idDoiTuong' => $idSanPham,
                            'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                            'recipients' => [$idTKReviewer],
                        ]);
                    }
                } catch (Throwable $notifyError) {
                    error_log('assign_doclap notify error: ' . $notifyError->getMessage());
                }
            }

            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'remove_doclap':
            // Gỡ phân công giám khảo
            $result = cham_diem_go_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'add_3rd_judge':
            // Mời giám khảo thứ 3 (Trọng tài phúc khảo)
            $result = cham_diem_moi_trong_tai($conn, $idSanPham, $idGV, $idVongThi);

            if (!empty($result['success']) && notification_feature_enabled('scoring')) {
                try {
                    $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
                    $idTKReviewer = lay_id_tk_theo_id_gv($conn, $idGV);
                    if ($idTKReviewer > 0) {
                        dispatch_personal($conn, [
                            'tieuDe' => 'Ban vua duoc moi lam trong tai phuc khao',
                            'noiDung' => 'Ban vua duoc moi cham phuc khao mot bai thi.',
                            'loaiThongBao' => 'CA_NHAN',
                            'idSK' => $idSK,
                            'loaiDoiTuong' => 'SANPHAM',
                            'idDoiTuong' => $idSanPham,
                            'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                            'recipients' => [$idTKReviewer],
                        ]);
                    }
                } catch (Throwable $notifyError) {
                    error_log('add_3rd_judge notify error: ' . $notifyError->getMessage());
                }
            }

            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'assign_multiple':
            // Phân công nhiều giám khảo cho một sản phẩm
            $dsGV = $input['ds_gv'] ?? [];
            if (empty($dsGV) || $idSanPham <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số ds_gv, id_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $successCount = 0;
            $errors = [];
            foreach ($dsGV as $gvId) {
                $result = cham_diem_phan_cong_giam_khao($conn, $idSanPham, (int) $gvId, $idVongThi);
                if ($result['success']) {
                    $successCount++;

                    if (notification_feature_enabled('scoring')) {
                        try {
                            $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
                            $idTKReviewer = lay_id_tk_theo_id_gv($conn, (int) $gvId);
                            if ($idTKReviewer > 0) {
                                dispatch_personal($conn, [
                                    'tieuDe' => 'Ban vua duoc phan cong cham diem',
                                    'noiDung' => 'Ban vua duoc phan cong cham mot bai thi.',
                                    'loaiThongBao' => 'CA_NHAN',
                                    'idSK' => $idSK,
                                    'loaiDoiTuong' => 'SANPHAM',
                                    'idDoiTuong' => $idSanPham,
                                    'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                                    'recipients' => [$idTKReviewer],
                                ]);
                            }
                        } catch (Throwable $notifyError) {
                            error_log('assign_multiple notify error: ' . $notifyError->getMessage());
                        }
                    }
                } else {
                    $errors[] = "GV $gvId: " . $result['message'];
                }
            }

            echo json_encode([
                'status' => $successCount > 0 ? 'success' : 'error',
                'message' => "Đã phân công $successCount/" . count($dsGV) . " giám khảo" . (count($errors) > 0 ? ". Lỗi: " . implode(', ', $errors) : ''),
                'data' => ['successCount' => $successCount, 'totalCount' => count($dsGV)]
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Action không hợp lệ',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
    }
}

function lay_id_tk_theo_id_gv(PDO $conn, int $idGV): int
{
    if ($idGV <= 0) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT idTK FROM giangvien WHERE idGV = :idGV LIMIT 1');
    $stmt->execute([':idGV' => $idGV]);
    return (int) $stmt->fetchColumn();
}