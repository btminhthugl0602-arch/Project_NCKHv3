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
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hỗ trợ', 'data' => null], JSON_UNESCAPED_UNICODE);
}

// ─────────────────────────────────────────────────────────────────────────────

function handleGetRequest($conn, $actor) {
    $action = $_GET['action'] ?? 'lay_chua_doc';
    $idSK   = isset($_GET['id_sk']) ? (int)$_GET['id_sk'] : 0;
    $idTK   = $actor['idTK'];

    switch ($action) {
        case 'lay_chua_doc':
            // Lấy thông báo CA_NHAN chưa đọc gửi tới user này (tùy chọn lọc theo idSK)
            $params = [':idTK' => $idTK];
            $whereExtra = '';
            if ($idSK > 0) {
                $whereExtra = ' AND tb.idSK = :idSK';
                $params[':idSK'] = $idSK;
            }

            $sql = "SELECT tb.idThongBao, tb.tieuDe, tb.noiDung, tb.loaiThongBao,
                           tb.idSK, tb.ngayGui
                    FROM thongbao tb
                    INNER JOIN thongbao_ca_nhan tcn ON tcn.idThongBao = tb.idThongBao AND tcn.idTK = :idTK
                    LEFT JOIN thongbao_da_doc tdd ON tdd.idThongBao = tb.idThongBao AND tdd.idTK = :idTK2
                    WHERE tdd.idThongBao IS NULL{$whereExtra}
                    ORDER BY tb.ngayGui DESC";
            $params[':idTK2'] = $idTK;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Lấy thông báo thành công',
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'danh_dau_da_doc':
            // Đánh dấu một thông báo là đã đọc
            $idThongBao = isset($_GET['id_thong_bao']) ? (int)$_GET['id_thong_bao'] : 0;
            if ($idThongBao <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_thong_bao', 'data' => null], JSON_UNESCAPED_UNICODE);
                return;
            }
            $sql = "INSERT IGNORE INTO thongbao_da_doc (idThongBao, idTK) VALUES (:idThongBao, :idTK)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':idThongBao' => $idThongBao, ':idTK' => $idTK]);
            echo json_encode(['status' => 'success', 'message' => 'Đã đánh dấu đọc', 'data' => null], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}

function handlePostRequest($conn, $actor, $input) {
    $action    = $input['action'] ?? '';
    $idSK      = isset($input['id_sk'])      ? (int)$input['id_sk']      : 0;
    $idVongThi = isset($input['id_vong_thi']) ? (int)$input['id_vong_thi'] : 0;

    switch ($action) {
        case 'gui_nhac_nho':
            // BTC gửi nhắc nhở tới tất cả GK trong vòng thi chưa hoàn thành chấm điểm
            if ($idSK <= 0 || $idVongThi <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Thiếu id_sk hoặc id_vong_thi', 'data' => null], JSON_UNESCAPED_UNICODE);
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
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Tất cả giám khảo đã hoàn thành chấm điểm, không cần gửi nhắc nhở.',
                    'data'    => ['soGKNhanThongBao' => 0]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $conn->beginTransaction();
            try {
                $tieuDe  = "Nhắc nhở: Hoàn thành chấm điểm — {$tenVongThi}";
                $noiDung = "Bạn vẫn còn sản phẩm chưa chấm điểm trong {$tenVongThi}. Vui lòng đăng nhập và hoàn thành chấm điểm sớm.";

                $sqlInsertTB = "INSERT INTO thongbao (tieuDe, noiDung, loaiThongBao, phamVi, idSK, nguoiGui)
                                VALUES (:tieuDe, :noiDung, 'CA_NHAN', 'CA_NHAN', :idSK, :nguoiGui)";
                $stmtInsertTB = $conn->prepare($sqlInsertTB);
                $stmtInsertTB->execute([
                    ':tieuDe'    => $tieuDe,
                    ':noiDung'   => $noiDung,
                    ':idSK'      => $idSK,
                    ':nguoiGui'  => $actor['idTK']
                ]);
                $idThongBao = (int)$conn->lastInsertId();

                $sqlInsertCN = "INSERT IGNORE INTO thongbao_ca_nhan (idThongBao, idTK) VALUES (:idThongBao, :idTK)";
                $stmtInsertCN = $conn->prepare($sqlInsertCN);
                foreach ($danhSachGK as $gk) {
                    $stmtInsertCN->execute([':idThongBao' => $idThongBao, ':idTK' => (int)$gk['idTK']]);
                }

                $conn->commit();

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Đã gửi thông báo nhắc nhở tới ' . count($danhSachGK) . ' giám khảo.',
                    'data'    => [
                        'idThongBao'       => $idThongBao,
                        'soGKNhanThongBao' => count($danhSachGK),
                        'danhSachGK'       => array_map(fn($gk) => ['tenGV' => $gk['tenGV'], 'tongSP' => (int)$gk['tong_sp'], 'daCham' => (int)$gk['da_cham']], $danhSachGK)
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                $conn->rollBack();
                error_log('gui_nhac_nho error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống khi gửi thông báo', 'data' => null], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ', 'data' => null], JSON_UNESCAPED_UNICODE);
    }
}
