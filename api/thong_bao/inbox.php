<?php
/**
 * API Inbox thong bao tong
 *
 * GET  ?action=list&limit=20&id_sk=...&unread_only=0|1
 * GET  ?action=unread_count&id_sk=...
 * POST {action:'mark_all_read', id_sk?}
 * POST {action:'mark_read', id_thong_bao}
 */

define('_AUTHEN', true);

require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/../core/auth_guard.php';
require_once __DIR__ . '/notification_service.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$actor = auth_require_login();
$idTK = (int) ($actor['idTK'] ?? 0);

if ($method === 'GET') {
    handleGetRequest($conn, $idTK);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    handlePostRequest($conn, $idTK, $input);
    exit;
}

notification_api_response('error', 'Phuong thuc khong ho tro', null, 405, [
    'api' => 'thong_bao.inbox',
    'action' => 'unsupported_method',
]);

function handleGetRequest(PDO $conn, int $idTK): void
{
    $action = trim((string) ($_GET['action'] ?? 'list'));
    $idSK = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

    switch ($action) {
        case 'list':
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $limit = max(1, min($limit, 100));
            $unreadOnly = isset($_GET['unread_only']) ? (int) $_GET['unread_only'] === 1 : false;

            $items = list_inbox($conn, $idTK, [
                'idSK' => $idSK,
                'chiLayChuaDoc' => $unreadOnly,
                'includeBroadcast' => true,
                'limit' => $limit,
            ]);
            $items = array_map('inbox_attach_deep_link', $items);

            $unreadCount = count_unread_notifications($conn, $idTK, [
                'idSK' => $idSK,
                'includeBroadcast' => true,
            ]);

            notification_api_response('success', 'Lay inbox thong bao thanh cong', [
                'items' => $items,
                'unreadCount' => $unreadCount,
            ], 200, [
                'api' => 'thong_bao.inbox',
                'action' => 'list',
                'count' => count($items),
            ]);
            return;

        case 'unread_count':
            $unreadCount = count_unread_notifications($conn, $idTK, [
                'idSK' => $idSK,
                'includeBroadcast' => true,
            ]);

            notification_api_response('success', 'Lay so thong bao chua doc thanh cong', [
                'unreadCount' => $unreadCount,
            ], 200, [
                'api' => 'thong_bao.inbox',
                'action' => 'unread_count',
            ]);
            return;

        default:
            notification_api_response('error', 'Action khong hop le', null, 400, [
                'api' => 'thong_bao.inbox',
                'action' => $action,
            ]);
            return;
    }
}

function inbox_attach_deep_link(array $item): array
{
    $idSK = isset($item['idSK']) ? (int) $item['idSK'] : 0;
    $loaiThongBao = strtoupper(trim((string) ($item['loaiThongBao'] ?? '')));
    $loaiDoiTuong = strtoupper(trim((string) ($item['loaiDoiTuong'] ?? '')));

    if ($idSK <= 0) {
        $item['deepLink'] = '/dashboard';
        return $item;
    }

    $tab = 'overview';
    if ($loaiThongBao === 'NHOM') {
        $tab = $loaiDoiTuong === 'YEUCAU' ? 'nhom-request' : 'nhom-my';
    } elseif ($loaiThongBao === 'SU_KIEN') {
        $tab = 'overview';
    } elseif ($loaiThongBao === 'CA_NHAN' && $loaiDoiTuong === 'SANPHAM') {
        $tab = 'scoring';
    }

    $item['deepLink'] = '/event-detail?id_sk=' . $idSK . '&tab=' . $tab;
    return $item;
}

function handlePostRequest(PDO $conn, int $idTK, array $input): void
{
    $action = trim((string) ($input['action'] ?? ''));
    $idSK = isset($input['id_sk']) ? (int) $input['id_sk'] : 0;

    switch ($action) {
        case 'mark_all_read':
            $affected = mark_all_read($conn, $idTK, [
                'idSK' => $idSK,
                'includeBroadcast' => true,
            ]);

            $unreadCount = count_unread_notifications($conn, $idTK, [
                'idSK' => $idSK,
                'includeBroadcast' => true,
            ]);

            notification_api_response('success', 'Da danh dau tat ca thong bao la da doc', [
                'affected' => $affected,
                'unreadCount' => $unreadCount,
            ], 200, [
                'api' => 'thong_bao.inbox',
                'action' => 'mark_all_read',
            ]);
            return;

        case 'mark_read':
            $idThongBao = isset($input['id_thong_bao']) ? (int) $input['id_thong_bao'] : 0;
            if ($idThongBao <= 0) {
                notification_api_response('error', 'Thieu id_thong_bao', null, 400, [
                    'api' => 'thong_bao.inbox',
                    'action' => 'mark_read',
                ]);
                return;
            }

            $ok = mark_read($conn, $idThongBao, $idTK);
            $unreadCount = count_unread_notifications($conn, $idTK, [
                'idSK' => $idSK,
                'includeBroadcast' => true,
            ]);

            notification_api_response($ok ? 'success' : 'error', $ok ? 'Da danh dau doc' : 'Khong the danh dau doc', [
                'idThongBao' => $idThongBao,
                'unreadCount' => $unreadCount,
            ], $ok ? 200 : 500, [
                'api' => 'thong_bao.inbox',
                'action' => 'mark_read',
            ]);
            return;

        default:
            notification_api_response('error', 'Action khong hop le', null, 400, [
                'api' => 'thong_bao.inbox',
                'action' => $action,
            ]);
            return;
    }
}
