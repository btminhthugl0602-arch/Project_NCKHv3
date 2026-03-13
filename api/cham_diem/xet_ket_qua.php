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
require_once __DIR__ . '/../thong_bao/notification_service.php';

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
            $biLoai  = array_filter($allItems, fn($i) => $i['trangThaiVongThi'] === 'Bị loại');
            $dangXet = array_filter($allItems, fn($i) => $i['trangThaiVongThi'] === 'Đang xét');

            // Bài sẵn sàng duyệt = đã chấm xong (soGKDaCham >= soGiamKhao > 0)
            // và chưa ở trạng thái cuối ('Đã duyệt' / 'Bị loại')
            // Không dùng bucket 'chuaDuyet' cũ vì nó bao gồm cả bài chưa chấm xong
            $sanSangDuyet = array_filter($allItems, fn($i) =>
                $i['soGiamKhao'] > 0
                && $i['soGKDaCham'] >= $i['soGiamKhao']
                && !in_array($i['trangThaiVongThi'], ['Đã duyệt', 'Bị loại'])
            );
            
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
                    'daDuyet'      => count($daDuyet),
                    'biLoai'       => count($biLoai),
                    'dangXet'      => count($dangXet),
                    'sanSangDuyet' => count($sanSangDuyet),
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
function handlePostRequest($conn, array $actor) {
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
    $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : (isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0);
    $diemChot = isset($input['diem_chot']) ? (float) $input['diem_chot'] : null;
    $hanMucDuyet = isset($input['han_muc_duyet']) ? max(0, (int) $input['han_muc_duyet']) : 10;
    
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
            // Duyệt điểm thủ công nhưng vẫn áp dụng engine quy chế + hạn mức top N
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
            
            $result = cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $diemChot, $idSK, 'DUYET_VONG_THI');
            $ketQuaHanMuc = cham_diem_ap_dung_han_muc_duyet($conn, $idVongThi, $hanMucDuyet);

            if (!empty($result['success']) && notification_feature_enabled('scoring')) {
                notify_ket_qua_san_pham($conn, [
                    'idSanPham' => $idSanPham,
                    'idSK' => $idSK,
                    'trangThai' => (string) ($result['trangThai'] ?? ''),
                    'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                    'diemChot' => $diemChot,
                ]);
            }

            echo json_encode([
                'status' => !empty($result['success']) ? 'success' : 'error',
                'message' => !empty($result['success']) ? 'Duyệt điểm thành công' : ($result['message'] ?? 'Lỗi khi duyệt điểm'),
                'data' => [
                    'diemChot' => $diemChot,
                    'trangThai' => $result['trangThai'] ?? null,
                    'quyChe' => $result['quyChe'] ?? null,
                    'hanMuc' => $ketQuaHanMuc,
                ]
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
            
            $result = cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $diemChot, $idSK, 'DUYET_VONG_THI');
            $ketQuaHanMuc = cham_diem_ap_dung_han_muc_duyet($conn, $idVongThi, $hanMucDuyet);

            if (!empty($result['success']) && notification_feature_enabled('scoring')) {
                notify_ket_qua_san_pham($conn, [
                    'idSanPham' => $idSanPham,
                    'idSK' => $idSK,
                    'trangThai' => (string) ($result['trangThai'] ?? ''),
                    'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                    'diemChot' => $diemChot,
                ]);
            }

            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => [
                    'diemChot' => $diemChot, 
                    'trangThai' => $result['trangThai'] ?? null,
                    'quyChe' => $result['quyChe'] ?? null,
                    'hanMuc' => $ketQuaHanMuc,
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'reject_score':
            // Đánh rớt bài thi thủ công
            $result = cham_diem_danh_rot_thu_cong($conn, $idSanPham, $idVongThi, $diemChot);

            if (!empty($result['success']) && notification_feature_enabled('scoring')) {
                notify_ket_qua_san_pham($conn, [
                    'idSanPham' => $idSanPham,
                    'idSK' => $idSK,
                    'trangThai' => 'Bị loại',
                    'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                    'diemChot' => $diemChot,
                ]);
            }

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
            if (empty($dsSanPham) || $idVongThi <= 0 || $idSK <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Thiếu tham số ds_san_pham, id_vong_thi hoặc id_sk',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $successCount = 0;
            $skippedCount = 0;
            $errors = [];
            $viPhamQuyChe = [];
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
                    $result = cham_diem_duyet_diem_voi_quyche($conn, $spId, $idVongThi, $diemTB, $idSK, 'DUYET_VONG_THI_HANG_LOAT');
                    if (!empty($result['success']) && (($result['trangThai'] ?? '') === 'Đã duyệt')) {
                        $successCount++;

                        if (notification_feature_enabled('scoring')) {
                            notify_ket_qua_san_pham($conn, [
                                'idSanPham' => $spId,
                                'idSK' => $idSK,
                                'trangThai' => 'Đã duyệt',
                                'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                                'diemChot' => $diemTB,
                            ]);
                        }
                    } elseif (!empty($result['success']) && (($result['trangThai'] ?? '') === 'Bị loại')) {
                        $viPhamQuyChe[] = [
                            'idSanPham' => $spId,
                            'quyChe' => $result['quyChe']['viPham'] ?? [],
                        ];

                        if (notification_feature_enabled('scoring')) {
                            notify_ket_qua_san_pham($conn, [
                                'idSanPham' => $spId,
                                'idSK' => $idSK,
                                'trangThai' => 'Bị loại',
                                'nguoiGui' => (int) ($actor['idTK'] ?? 0),
                                'diemChot' => $diemTB,
                            ]);
                        }
                    } else {
                        $errors[] = "SP $spId: " . ($result['message'] ?? 'Lỗi khi duyệt theo quy chế');
                    }
                } else {
                    $errors[] = "SP $spId: Chưa có điểm";
                }
            }

            $ketQuaHanMuc = cham_diem_ap_dung_han_muc_duyet($conn, $idVongThi, $hanMucDuyet);

            $msg = "Đã duyệt $successCount/" . count($dsSanPham) . " bài thi";
            if ($skippedCount > 0) {
                $msg .= ". Bỏ qua $skippedCount bài có cảnh báo IRR";
            }
            if (count($errors) > 0) {
                $msg .= ". Lỗi: " . implode(', ', $errors);
            }
            if (!empty($viPhamQuyChe)) {
                $msg .= ". Có " . count($viPhamQuyChe) . " bài không đạt quy chế và bị loại";
            }
            if (!empty($ketQuaHanMuc['biLoaiBoiHanMuc'])) {
                $msg .= ". Hệ thống tự lọc theo hạn mức: chỉ giữ " . (int) ($ketQuaHanMuc['giuLai'] ?? 0) . "/" . (int) ($ketQuaHanMuc['tongDaDuyet'] ?? 0) . " bài Đã duyệt";
            }
            echo json_encode([
                'status' => $successCount > 0 ? 'success' : 'error',
                'message' => $msg,
                'data' => [
                    'successCount' => $successCount,
                    'skippedCount' => $skippedCount,
                    'totalCount' => count($dsSanPham),
                    'viPhamQuyChe' => $viPhamQuyChe,
                    'hanMuc' => $ketQuaHanMuc,
                ]
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

function lay_id_tk_nhom_truong_theo_san_pham(PDO $conn, int $idSanPham): int
{
    if ($idSanPham <= 0) {
        return 0;
    }

    $stmt = $conn->prepare(
        'SELECT COALESCE(n.idTruongNhom, n.idChuNhom) AS idTK
         FROM sanpham sp
         INNER JOIN nhom n ON n.idNhom = sp.idNhom
         WHERE sp.idSanPham = :idSanPham
         LIMIT 1'
    );
    $stmt->execute([':idSanPham' => $idSanPham]);
    return (int) $stmt->fetchColumn();
}

function notify_ket_qua_san_pham(PDO $conn, array $ctx): void
{
    try {
        $idSanPham = (int) ($ctx['idSanPham'] ?? 0);
        $idSK = (int) ($ctx['idSK'] ?? 0);
        $trangThai = trim((string) ($ctx['trangThai'] ?? ''));
        $nguoiGui = (int) ($ctx['nguoiGui'] ?? 0);
        $diemChot = isset($ctx['diemChot']) ? (float) $ctx['diemChot'] : null;

        if ($idSanPham <= 0 || $idSK <= 0 || $nguoiGui <= 0 || $trangThai === '') {
            return;
        }

        $idTKNhan = lay_id_tk_nhom_truong_theo_san_pham($conn, $idSanPham);
        if ($idTKNhan <= 0) {
            return;
        }

        $noiDung = 'Ket qua bai thi da duoc cap nhat: ' . $trangThai . '.';
        if ($diemChot !== null) {
            $noiDung .= ' Diem ghi nhan: ' . number_format($diemChot, 2) . '.';
        }

        dispatch_personal($conn, [
            'tieuDe' => 'Cap nhat ket qua bai thi',
            'noiDung' => $noiDung,
            'loaiThongBao' => 'CA_NHAN',
            'idSK' => $idSK,
            'loaiDoiTuong' => 'SANPHAM',
            'idDoiTuong' => $idSanPham,
            'nguoiGui' => $nguoiGui,
            'recipients' => [$idTKNhan],
        ]);
    } catch (Throwable $notifyError) {
        error_log('notify_ket_qua_san_pham error: ' . $notifyError->getMessage());
    }
}
