<?php
/**
 * API Xét kết quả & Xếp hạng (Bảng vàng)
 * 
 * GET: Lấy bảng xếp hạng, danh sách bài thi cần duyệt
 * POST: Duyệt điểm, đánh rớt thủ công
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../../api/core/auth_guard.php';

require_once __DIR__ . '/quan_ly_cham_diem.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth: chỉ BTC (cauhinh_sukien) mới được duyệt/loại kết quả ──
$idSK_auth = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$actor = auth_require_bat_ky_quyen_su_kien($idSK_auth, ['cauhinh_sukien', 'duyet_diem']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetRequest($conn);
    } elseif ($method === 'POST') {
        handlePostRequest($conn);
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Phương thức không được hỗ trợ',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('API Error in xet_ket_qua.php: ' . $e->getMessage());
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
    $action = $_GET['action'] ?? 'bang_xep_hang';
    $idSK = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
    $idVongThi = isset($_GET['id_vong_thi']) ? (int) $_GET['id_vong_thi'] : 0;
    $idSanPham = isset($_GET['id_san_pham']) ? (int) $_GET['id_san_pham'] : 0;
    
    switch ($action) {
        case 'bang_xep_hang':
            // Lấy bảng xếp hạng (Bảng vàng)
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $data = cham_diem_lay_bang_xep_hang($conn, $idSK, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy bảng xếp hạng thành công',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'danh_sach_can_duyet':
            // Lấy danh sách bài thi cần duyệt (đã chấm xong, chưa duyệt)
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $allItems = cham_diem_lay_tat_ca_bai_thi($conn, $idSK, $idVongThi);
            
            // Lọc những bài đã chấm xong (soGKDaCham >= soGiamKhao > 0) và chưa duyệt
            $canDuyet = array_filter($allItems, function($item) {
                return $item['soGiamKhao'] > 0 
                    && $item['soGKDaCham'] >= $item['soGiamKhao']
                    && !in_array($item['trangThaiVongThi'], ['Đã duyệt', 'Bị loại']);
            });
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Lấy danh sách cần duyệt thành công',
                'data' => array_values($canDuyet)
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'tinh_diem_tb':
            // Tính điểm trung bình của một sản phẩm
            if ($idSanPham <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $diemTB = cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi);
            echo json_encode([
                'status' => 'success',
                'message' => $diemTB !== null ? 'Tính điểm trung bình thành công' : 'Chưa có điểm chấm',
                'data' => ['diemTrungBinh' => $diemTB]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'thong_ke_ket_qua':
            // Thống kê kết quả vòng thi
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $allItems = cham_diem_lay_tat_ca_bai_thi($conn, $idSK, $idVongThi);
            
            $daDuyet = array_filter($allItems, fn($i) => $i['trangThaiVongThi'] === 'Đã duyệt');
            $biLoai = array_filter($allItems, fn($i) => $i['trangThaiVongThi'] === 'Bị loại');
            $dangXet = array_filter($allItems, fn($i) => $i['trangThaiVongThi'] === 'Đang xét');
            $chuaDuyet = array_filter($allItems, fn($i) => !in_array($i['trangThaiVongThi'], ['Đã duyệt', 'Bị loại', 'Đang xét']));
            
            // Tính điểm trung bình của các bài đã duyệt
            $diemDaDuyet = array_column($daDuyet, 'diemTrungBinh');
            $diemTBChung = count($diemDaDuyet) > 0 ? array_sum($diemDaDuyet) / count($diemDaDuyet) : 0;
            $diemCaoNhat = count($diemDaDuyet) > 0 ? max($diemDaDuyet) : 0;
            $diemThapNhat = count($diemDaDuyet) > 0 ? min($diemDaDuyet) : 0;
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Thống kê kết quả thành công',
                'data' => [
                    'tongSanPham' => count($allItems),
                    'daDuyet' => count($daDuyet),
                    'biLoai' => count($biLoai),
                    'dangXet' => count($dangXet),
                    'chuaDuyet' => count($chuaDuyet),
                    'diemTBChung' => round($diemTBChung, 2),
                    'diemCaoNhat' => $diemCaoNhat,
                    'diemThapNhat' => $diemThapNhat
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
        
        case 'export_ranking':
            // Xuất bảng xếp hạng ra file CSV
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $data = cham_diem_lay_bang_xep_hang($conn, $idSK, $idVongThi);
            $tenFile = 'bang-xep-hang-sk' . $idSK . '-vt' . $idVongThi . '.csv';

            // Override content type — phải gọi trước khi có output
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $tenFile . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            // UTF-8 BOM để Excel nhận diện đúng tiếng Việt
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Hạng', 'Tên sản phẩm', 'Mã nhóm', 'Tên nhóm', 'Điểm TB', 'Xếp loại', 'Thành viên']);
            foreach ($data as $row) {
                fputcsv($out, [
                    $row['xepHang'],
                    $row['tensanpham'],
                    $row['manhom'],
                    $row['tennhom'] ?? '',
                    $row['diemTrungBinh'] !== null ? number_format((float)$row['diemTrungBinh'], 2) : '',
                    $row['xepLoai'] ?? '',
                    $row['thanhVien'] ?? ''
                ]);
            }
            fclose($out);
            return;
            
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
function handlePostRequest($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
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
    $idVongThi = isset($input['id_vong_thi']) ? (int) $input['id_vong_thi'] : 0;
    $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;
    $diemChot = isset($input['diem_chot']) ? (float) $input['diem_chot'] : null;
    
    // Validate common params
    if (in_array($action, ['approve_score_manual', 'approve_score_auto', 'reject_score', 'cancel_approval']) && ($idSanPham <= 0 || $idVongThi <= 0)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Thiếu tham số id_san_pham hoặc id_vong_thi',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    switch ($action) {
        case 'approve_score_manual':
            // Duyệt điểm thủ công (không qua quy chế)
            if ($diemChot === null) {
                // Nếu không truyền điểm, tự tính điểm TB
                $diemChot = cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi);
            }
            
            if ($diemChot === null) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Không có điểm để duyệt. Vui lòng đảm bảo bài thi đã được chấm.',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $result = cham_diem_duyet_diem($conn, $idSanPham, $idVongThi, $diemChot, 'Đã duyệt');
            echo json_encode([
                'status' => $result ? 'success' : 'error',
                'message' => $result ? 'Duyệt điểm thành công' : 'Lỗi khi duyệt điểm',
                'data' => ['diemChot' => $diemChot, 'trangThai' => 'Đã duyệt']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'approve_score_auto':
            // Duyệt điểm tự động (qua kiểm tra quy chế)
            if ($idSK <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số id_sk để kiểm tra quy chế',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            if ($diemChot === null) {
                $diemChot = cham_diem_tinh_diem_trung_binh($conn, $idSanPham, $idVongThi);
            }
            
            if ($diemChot === null) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Không có điểm để duyệt. Vui lòng đảm bảo bài thi đã được chấm.',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            $result = cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $diemChot, $idSK);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => [
                    'diemChot' => $diemChot, 
                    'trangThai' => $result['trangThai'] ?? null
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'reject_score':
            // Đánh rớt bài thi thủ công
            $result = cham_diem_danh_rot_thu_cong($conn, $idSanPham, $idVongThi, $diemChot);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'cancel_approval':
            // Hủy duyệt / hủy loại — reset trangThai về NULL
            $result = cham_diem_huy_duyet($conn, $idSanPham, $idVongThi);
            echo json_encode([
                'status' => $result ? 'success' : 'error',
                'message' => $result ? 'Đã hủy duyệt thành công' : 'Lỗi hủy duyệt',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'approve_multiple':
            // Duyệt nhiều bài thi cùng lúc
            // skip_warned=true: bỏ qua bài có cảnh báo IRR thay vì duyệt hết
            $dsSanPham  = $input['ds_san_pham'] ?? [];
            $skipWarned = !empty($input['skip_warned']);
            if (empty($dsSanPham) || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số ds_san_pham hoặc id_vong_thi',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $successCount = 0;
            $skippedCount = 0;
            $errors = [];
            foreach ($dsSanPham as $spId) {
                $spId = (int) $spId;

                // Khi skip_warned=true: kiểm tra IRR trước khi duyệt
                if ($skipWarned) {
                    $chiTietDiem = cham_diem_lay_chi_tiet_diem($conn, $spId, $idVongThi);
                    $chiTietChinh = array_values(array_filter($chiTietDiem, fn($gk) => empty($gk['isTrongTai'])));
                    if (count($chiTietChinh) >= 2) {
                        $diemTheoGK = array_map(fn($gk) => array_column($gk['chiTiet'], 'diem'), $chiTietChinh);
                        $irr = cham_diem_tinh_irr($diemTheoGK);
                        if (!empty($irr['canhBao'])) {
                            $skippedCount++;
                            continue; // Bỏ qua bài có cảnh báo
                        }
                    }
                }

                $diemTB = cham_diem_tinh_diem_trung_binh($conn, $spId, $idVongThi);
                if ($diemTB !== null) {
                    $result = cham_diem_duyet_diem($conn, $spId, $idVongThi, $diemTB, 'Đã duyệt');
                    if ($result) {
                        $successCount++;
                    } else {
                        $errors[] = "SP $spId: Lỗi khi duyệt";
                    }
                } else {
                    $errors[] = "SP $spId: Chưa có điểm";
                }
            }

            $msg = "Đã duyệt $successCount/" . count($dsSanPham) . " bài thi";
            if ($skippedCount > 0) {
                $msg .= ". Bỏ qua $skippedCount bài có cảnh báo IRR";
            }
            if (count($errors) > 0) {
                $msg .= ". Lỗi: " . implode(', ', $errors);
            }
            echo json_encode([
                'status' => $successCount > 0 ? 'success' : 'error',
                'message' => $msg,
                'data' => ['successCount' => $successCount, 'skippedCount' => $skippedCount, 'totalCount' => count($dsSanPham)]
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
