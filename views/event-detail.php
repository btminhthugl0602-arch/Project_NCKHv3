<?php

/**
 * Event Detail Page — Router
 * Luồng quyền:
 *   1. Tính $perm từ DB một lần
 *   2. Gate từng tab — redirect về overview nếu không có quyền
 *   3. Truyền $perm xuống partial (PHP) và JS qua window.PERMISSIONS
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$_isGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!isset($_SESSION['idTK']) && !$_isGuest) {
    header('Location: /sign-in?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!defined('_AUTHEN')) define('_AUTHEN', true);
require_once __DIR__ . '/../api/core/base.php';
require_once __DIR__ . '/../api/core/auth_guard.php';

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$tab  = isset($_GET['tab'])   ? trim((string) $_GET['tab']) : 'overview';

// ── Tính permissions một lần ──────────────────────────────────
$idTKLogin  = isset($_SESSION['idTK'])     ? (int) $_SESSION['idTK']     : 0;
$idLoaiTK   = isset($_SESSION['idLoaiTK']) ? (int) $_SESSION['idLoaiTK'] : 0;
$isLoggedIn = !$_isGuest && $idTKLogin > 0;

$perm = [
    'cauhinh_sukien'     => false,
    'phan_cong_cham'     => false,
    'nhap_diem'          => false,
    'xem_bai_phan_cong'  => false,
    'nop_san_pham'       => false,
    'xem_ketqua_truocCB' => false,
    'xem_ketqua_sauCB'   => false,
    'xem_nhom'           => false,
    'tao_nhom'           => false,
    'quan_ly_diemdanh'   => false,
    'duyet_diem'         => false,
    'quan_ly_tieuban'    => false,
];

if ($isLoggedIn && $idSk > 0) {
    foreach ($perm as $maQuyen => $_) {
        $perm[$maQuyen] = kiem_tra_quyen_su_kien($conn, $idTKLogin, $idSk, $maQuyen);
    }
}

// ── Quyền vào từng tab ────────────────────────────────────────
// Không có Admin bypass — phải được gán vai trò trong sự kiện
$tabAccess = [
    'overview'        => true,
    'config-basic'    => $perm['cauhinh_sukien'],
    'config-vongthi'  => $perm['cauhinh_sukien'],
    'config-tailieu'  => $perm['cauhinh_sukien'],
    'config-rules'    => $perm['cauhinh_sukien'],
    'config-criteria' => $perm['cauhinh_sukien'],
    'subcommittees'   => $perm['cauhinh_sukien'] || $perm['quan_ly_tieuban'],
    'judges'          => $perm['cauhinh_sukien'] || $perm['quan_ly_tieuban'],
    'review-assign'   => $perm['phan_cong_cham'],
    'review-results'  => $perm['duyet_diem'],
    'scoring'         => $perm['phan_cong_cham'] || $perm['duyet_diem'],
    'scoring-gv'      => $perm['nhap_diem'],
    'nhom-my'         => $perm['xem_nhom'],
    'nhom-all'        => $perm['xem_nhom'],
    'nhom-request'    => $perm['xem_nhom'],
];

// ── Validate & gate tab ───────────────────────────────────────
if (!array_key_exists($tab, $tabAccess)) {
    $tab = 'overview';
}

if ($_isGuest) {
    $tab = 'overview';
} elseif (!($tabAccess[$tab] ?? false)) {
    $_SESSION['_flash_warning'] = 'Bạn không có quyền truy cập tab này.';
    header("Location: /event-detail?id_sk={$idSk}&tab=overview");
    exit;
}

// ── Metadata ─────────────────────────────────────────────────
$tabTitles = [
    'overview'        => 'Tổng quan sự kiện',
    'config-basic'    => 'Cấu hình cơ bản',
    'config-vongthi'  => 'Cấu hình vòng thi',
    'config-tailieu'  => 'Cấu hình loại tài liệu',
    'config-rules'    => 'Cấu hình quy chế',
    'config-criteria' => 'Thiết lập bộ tiêu chí',
    'subcommittees'   => 'Quản lý Tiểu ban',
    'judges'          => 'Phân công Ban Giám Khảo',
    'review-assign'   => 'Phân công phản biện',
    'review-results'  => 'Kết quả Review',
    'scoring'         => 'Phân công & Quản lý Điểm',
    'scoring-gv'      => 'Chấm điểm',
    'nhom-my'         => 'Nhóm của tôi',
    'nhom-all'        => 'Tất cả nhóm',
    'nhom-request'    => 'Lời mời tham gia',
];

$pageTitle             = "Chi tiết sự kiện - ezManagement";
$currentPage           = "events";
$pageCss               = "event-detail.css";
$pageJs                = 'event-detail.js';
$pageHeading           = $tabTitles[$tab] ?? 'Chi tiết sự kiện';
$breadcrumbs           = [
    ['title' => 'Dashboard',        'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện', 'url' => '/events'],
    ['title' => $pageHeading],
];
$useEventSidebar       = true;
$eventSidebarEventId   = $idSk;
$eventSidebarSection   = $tab;
$eventSidebarPerm      = $perm;
$eventSidebarTabAccess = $tabAccess;

$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath   = dirname($scriptPath);
if ($basePath === '\\' || $basePath === '/') $basePath = '';

ob_start();
?>

<?php if (!empty($_SESSION['_flash_warning'])): ?>
    <?php $flashMsg = htmlspecialchars($_SESSION['_flash_warning']);
    unset($_SESSION['_flash_warning']); ?>
    <div id="tabAccessWarning"
        class="mx-6 mt-4 px-4 py-3 text-sm border rounded-lg border-amber-200 bg-amber-50 text-amber-700 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px] shrink-0">warning</span>
        <?php echo $flashMsg; ?>
    </div>
    <script>
        setTimeout(() => document.getElementById('tabAccessWarning')?.remove(), 4000);
    </script>
<?php endif; ?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 mb-4">
            <a href="/events"
                class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg shadow-soft-sm text-slate-700 border-slate-200">
                <i class="mr-2 fas fa-arrow-left"></i> Quay lại danh sách sự kiện
            </a>
        </div>
        <div class="w-full max-w-full px-3">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="p-6 pb-0">
                    <h6 class="mb-1" id="eventTitle">Đang tải sự kiện...</h6>
                    <p class="mb-0 text-sm text-slate-500" id="eventSubtitle">Không gian cấu hình sâu cho từng sự kiện.
                    </p>
                </div>
                <div class="flex-auto p-6">
                    <div id="eventDetailLoading" class="text-sm text-slate-500">Đang tải dữ liệu chi tiết...</div>
                    <div id="eventDetailError"
                        class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
                    </div>
                    <?php
                    /**
                     * QUAN TRỌNG: inject window globals TRƯỚC khi include partial.
                     * Các script inline trong partial (tab-overview.php, nhom-my.php, ...)
                     * chạy ngay lập tức — nếu window.EVENT_DETAIL_ID chưa được set thì idSk = 0.
                     */
                    ?>
                    <script>
                        window.APP_BASE_PATH = <?php echo json_encode($basePath); ?>;
                        window.EVENT_DETAIL_ID = <?php echo (int) $idSk; ?>;
                        window.EVENT_DETAIL_TAB = <?php echo json_encode($tab, JSON_UNESCAPED_UNICODE); ?>;
                        window.IS_GUEST = <?php echo $_isGuest ? 'true' : 'false'; ?>;
                        window.PERMISSIONS = <?php echo json_encode($perm,      JSON_UNESCAPED_UNICODE); ?>;
                        window.TAB_ACCESS = <?php echo json_encode($tabAccess, JSON_UNESCAPED_UNICODE); ?>;
                    </script>
                    <div id="eventDetailContent" class="hidden space-y-4">
                        <?php
                        $partialFile = __DIR__ . "/partials/event-detail/tab-{$tab}.php";
                        if (file_exists($partialFile)) {
                            include $partialFile;
                        } else {
                            echo '<div class="px-4 py-3 text-sm border rounded-lg border-amber-200 bg-amber-50 text-amber-700">Tab chưa được xây dựng.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($tab === 'scoring' || $tab === 'scoring-gv'): ?>
    <script src="<?php echo $basePath; ?>/assets/js/scoring.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/scoring.js'); ?>"></script>
<?php endif; ?>
<?php if (in_array($tab, ['nhom-my', 'nhom-all', 'nhom-request'])): ?>
    <script src="<?php echo $basePath; ?>/assets/js/nhom_thi.js"></script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';