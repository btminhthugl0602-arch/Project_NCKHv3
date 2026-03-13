<?php
/**
 * API Thông báo Giám khảo
 *
 * GET  ?action=lay_chua_doc&id_sk=X          — Lấy thông báo chưa đọc của user hiện tại trong SK
 * POST {action:'gui_nhac_nho', id_sk, id_vong_thi} — BTC nhắc nhở GK chưa hoàn thành chấm điểm
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../../api/core/auth_guard.php';
require_once __DIR__ . '/notification_service.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$idSK_auth = ($method === 'GET')
    ? (isset($_GET['id_sk']) ? (int)$_GET['id_sk'] : 0)
    : 0;

if ($method === 'GET') {
    $actor = auth_require_login();
    handleGetRequest($conn, $actor);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $idSK_auth = isset($input['id_sk']) ? (int)$input['id_sk'] : 0;
    $actor = auth_require_bat_ky_quyen_su_kien($idSK_auth, ['cauhinh_sukien', 'duyet_diem']);
    handlePostRequest($conn, $actor, $input);
} else {
    notification_api_response('error', 'Phuong thuc khong ho tro', null, 405, [
        'api' => 'thong_bao.giam_khao',
        'action' => 'unsupported_method',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────

function handleGetRequest($conn, $actor) {
    $action = $_GET['action'] ?? 'lay_chua_doc';
    $idSK   = isset($_GET['id_sk']) ? (int)$_GET['id_sk'] : 0;
    $idTK   = $actor['idTK'];

    switch ($action) {
        case 'lay_chua_doc':
            $data = list_inbox($conn, $idTK, [
                'idSK' => $idSK,
                'chiLayChuaDoc' => true,
                'includeBroadcast' => false,
                'limit' => 100,
            ]);
            notification_api_response('success', 'Lay thong bao thanh cong', $data, 200, [
                'api' => 'thong_bao.giam_khao',
                'action' => $action,
                'count' => count($data),
            ]);
            break;

        case 'danh_dau_da_doc':
            // Đánh dấu một thông báo là đã đọc
            $idThongBao = isset($_GET['id_thong_bao']) ? (int)$_GET['id_thong_bao'] : 0;
            if ($idThongBao <= 0) {
                notification_api_response('error', 'Thieu id_thong_bao', null, 400, [
                    'api' => 'thong_bao.giam_khao',
                    'action' => $action,
                ]);
                return;
            }
            mark_read($conn, $idThongBao, $idTK);
            notification_api_response('success', 'Da danh dau doc', null, 200, [
                'api' => 'thong_bao.giam_khao',
                'action' => $action,
                'idThongBao' => $idThongBao,
            ]);
            break;

        default:
            notification_api_response('error', 'Action khong hop le', null, 400, [
                'api' => 'thong_bao.giam_khao',
                'action' => $action,
            ]);
    }
}

function handlePostRequest($conn, $actor, $input) {
    $action    = $input['action'] ?? '';
    $idSK      = isset($input['id_sk'])      ? (int)$input['id_sk']      : 0;
    $idVongThi = isset($input['id_vong_thi']) ? (int)$input['id_vong_thi'] : 0;

    switch ($action) {
        case 'gui_nhac_nho':
            if (!notification_feature_enabled('scoring')) {
                notification_api_response('success', 'Trigger thong bao scoring dang tat theo feature flag', [
                    'soGKNhanThongBao' => 0,
                ], 200, [
                    'api' => 'thong_bao.giam_khao',
                    'action' => $action,
                    'featureFlag' => 'NOTIFICATION_FLAG_SCORING_CLUSTER',
                ]);
                return;
            }

            // BTC gửi nhắc nhở tới tất cả GK trong vòng thi chưa hoàn thành chấm điểm
            if ($idSK <= 0 || $idVongThi <= 0) {
                notification_api_response('error', 'Thieu id_sk hoac id_vong_thi', null, 400, [
                    'api' => 'thong_bao.giam_khao',
                    'action' => $action,
                ]);
                return;
            }

            // Lấy tên vòng thi
            $sqlVT = "SELECT tenVongThi FROM vongthi WHERE idVongThi = :idVongThi AND idSK = :idSK LIMIT 1";
            $stmtVT = $conn->prepare($sqlVT);
            $stmtVT->execute([':idVongThi' => $idVongThi, ':idSK' => $idSK]);
            $votgThiRow = $stmtVT->fetch(PDO::FETCH_ASSOC);
            $tenVongThi = $votgThiRow ? $votgThiRow['tenVongThi'] : "Vòng thi #{$idVongThi}";

            // Lấy danh sách GK chưa chấm đủ tất cả SP được phân công
            // GK "chưa xong" = có SP trong phancong_doclap nhưng chưa có điểm trong chamtieuchi
            $sqlGKChuaXong = "SELECT DISTINCT gv.idGV, gv.idTK, gv.tenGV,
                                     COUNT(DISTINCT pd.idSanPham) as tong_sp,
                                     COUNT(DISTINCT ct.idSanPham) as da_cham
                              FROM phancong_doclap pd
                              INNER JOIN giangvien gv ON gv.idGV = pd.idGV
                              LEFT JOIN phancongcham pcc ON pcc.idGV = pd.idGV AND pcc.idVongThi = pd.idVongThi AND pcc.isActive = 1
                              LEFT JOIN chamtieuchi ct ON ct.idPhanCongCham = pcc.idPhanCongCham AND ct.idSanPham = pd.idSanPham
                              WHERE pd.idVongThi = :idVongThi AND pd.isTrongTai = 0
                              GROUP BY gv.idGV, gv.idTK, gv.tenGV
                              HAVING da_cham < tong_sp";
            $stmtGK = $conn->prepare($sqlGKChuaXong);
            $stmtGK->execute([':idVongThi' => $idVongThi]);
            $danhSachGK = $stmtGK->fetchAll(PDO::FETCH_ASSOC);

            if (empty($danhSachGK)) {
                notification_api_response(
                    'success',
                    'Tat ca giam khao da hoan thanh cham diem, khong can gui nhac nho.',
                    ['soGKNhanThongBao' => 0],
                    200,
                    [
                        'api' => 'thong_bao.giam_khao',
                        'action' => $action,
                        'idSK' => $idSK,
                        'idVongThi' => $idVongThi,
                    ]
                );
                return;
            }

            try {
                $tieuDe  = "Nhắc nhở: Hoàn thành chấm điểm — {$tenVongThi}";
                $noiDung = "Bạn vẫn còn sản phẩm chưa chấm điểm trong {$tenVongThi}. Vui lòng đăng nhập và hoàn thành chấm điểm sớm.";

                $recipients = array_map(function ($gk) {
                    return (int) ($gk['idTK'] ?? 0);
                }, $danhSachGK);

                $dispatchResult = dispatch_personal($conn, [
                    'tieuDe' => $tieuDe,
                    'noiDung' => $noiDung,
                    'loaiThongBao' => 'CA_NHAN',
                    'idSK' => $idSK,
                    'loaiDoiTuong' => 'SANPHAM',
                    'nguoiGui' => (int) $actor['idTK'],
                    'recipients' => $recipients,
                ]);

                if (empty($dispatchResult['success'])) {
                    throw new RuntimeException($dispatchResult['message'] ?? 'Khong the tao thong bao nhac nho');
                }

                $danhSachRutGon = array_map(function ($gk) {
                    return [
                        'tenGV' => $gk['tenGV'],
                        'tongSP' => (int) $gk['tong_sp'],
                        'daCham' => (int) $gk['da_cham'],
                    ];
                }, $danhSachGK);

                notification_api_response(
                    'success',
                    'Da gui thong bao nhac nho toi ' . count($danhSachGK) . ' giam khao.',
                    [
                        'idThongBao' => (int) ($dispatchResult['idThongBao'] ?? 0),
                        'soGKNhanThongBao' => count($danhSachGK),
                        'danhSachGK' => $danhSachRutGon,
                    ],
                    200,
                    [
                        'api' => 'thong_bao.giam_khao',
                        'action' => $action,
                        'idSK' => $idSK,
                        'idVongThi' => $idVongThi,
                    ]
                );
            } catch (Throwable $e) {
                error_log('gui_nhac_nho error: ' . $e->getMessage());
                notification_api_response('error', 'Loi he thong khi gui thong bao', null, 500, [
                    'api' => 'thong_bao.giam_khao',
                    'action' => $action,
                    'idSK' => $idSK,
                    'idVongThi' => $idVongThi,
                ]);
            }
            break;

        default:
            notification_api_response('error', 'Action khong hop le', null, 400, [
                'api' => 'thong_bao.giam_khao',
                'action' => $action,
            ]);
    }
}
