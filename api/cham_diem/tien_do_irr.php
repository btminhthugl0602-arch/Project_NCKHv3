<?php
/**
 * API Tiến độ chấm điểm & Kiểm định IRR (Inter-Rater Reliability)
 * 
 * GET: Lấy thống kê tiến độ, chi tiết điểm, phân tích IRR
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../../api/core/auth_guard.php';

require_once __DIR__ . '/quan_ly_cham_diem.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth: yêu cầu quyền BTC hoặc phân công chấm trong sự kiện ──
$idSK_auth = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$actor = auth_require_bat_ky_quyen_su_kien($idSK_auth, ['phan_cong_cham', 'cauhinh_sukien', 'duyet_diem']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetRequest($conn);
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Phương thức không được hỗ trợ',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('API Error in tien_do_irr.php: ' . $e->getMessage());
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
function handleGetRequest($conn) {
    $action = $_GET['action'] ?? 'thong_ke';
    $idSK = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
    $idVongThi = isset($_GET['id_vong_thi']) ? (int) $_GET['id_vong_thi'] : 0;
    $idSanPham = isset($_GET['id_san_pham']) ? (int) $_GET['id_san_pham'] : 0;
    
    switch ($action) {
        case 'thong_ke':
            // Lấy thống kê tổng quan tiến độ
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $data = cham_diem_lay_thong_ke_tien_do($conn, $idSK, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy thống kê tiến độ thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'chi_tiet_diem':
            // Lấy chi tiết điểm chấm theo giám khảo cho một sản phẩm
            if ($idSanPham <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $data = cham_diem_lay_chi_tiet_diem($conn, $idSanPham, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy chi tiết điểm thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'phan_tich_irr':
            // Phân tích IRR cho một sản phẩm cụ thể
            if ($idSanPham <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Lấy chi tiết điểm và tính IRR
            $chiTietDiem = cham_diem_lay_chi_tiet_diem($conn, $idSanPham, $idVongThi);
            
            if (count($chiTietDiem) < 2) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cần ít nhất 2 giám khảo để phân tích IRR',
                    'data' => [
                        'irr' => null,
                        'chiTietDiem' => $chiTietDiem
                    ]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            // Chuẩn bị mảng điểm theo giám khảo
            $diemTheoGK = [];
            foreach ($chiTietDiem as $gk) {
                $diemTheoGK[] = array_column($gk['chiTiet'], 'diem');
            }
            
            $irr = cham_diem_tinh_irr($diemTheoGK);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Phân tích IRR thành công',
                'data' => [
                    'irr' => $irr,
                    'chiTietDiem' => $chiTietDiem
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'danh_sach_canh_bao':
            // Lấy danh sách bài thi có cảnh báo độ lệch điểm
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $data = cham_diem_lay_danh_sach_canh_bao($conn, $idSK, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách cảnh báo thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'danh_sach_bai_thi':
            // Lấy danh sách tất cả bài thi với trạng thái chi tiết
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $data = cham_diem_lay_tat_ca_bai_thi($conn, $idSK, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách bài thi thành công',
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
