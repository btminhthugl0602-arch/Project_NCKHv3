<?php

/**
 * Event Detail Page — Router
 * Mỗi tab được render từ file partial riêng trong views/partials/event-detail/
 */


$pageTitle   = "Chi tiết sự kiện - ezManagement";
$currentPage = "events";
$pageCss     = "event-detail.css";

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$tab  = isset($_GET['tab'])   ? trim((string) $_GET['tab']) : 'overview';

$allowedTabs = [
    'overview',
    'config-basic',
    'config-rules',
    'config-criteria',
    'submissions',
    'review-assign',
    'review-results',
    'scoring',
    'subcommittees',
    'committees',
    'judges',
    'nhom-my',
    'nhom-all',
    'nhom-request',
];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

$tabTitles = [
    'overview'         => 'Tổng quan sự kiện',
    'config-basic'     => 'Cấu hình cơ bản',
    'config-rules'     => 'Cấu hình quy chế',
    'config-criteria'  => 'Thiết lập bộ tiêu chí',
    'submissions'      => 'Tất cả bài nộp',
    'review-assign'    => 'Phân công phản biện',
    'review-results'   => 'Kết quả Review',
    'scoring'          => 'Phân công & Quản lý Điểm (Sơ loại)',
    'subcommittees'    => 'Quản lý Tiểu ban (Bảo vệ Vòng trong)',
    'committees'       => 'Quản lý tiểu ban',
    'judges'           => 'Phân công BGK',
    'nhom-my'          => 'Nhóm của tôi',
    'nhom-all'         => 'Tất cả nhóm',
    'nhom-request'     => 'Lời mời tham gia',
];

// Mọi tab đều load event-detail.js
$pageJs = 'event-detail.js';

$pageHeading         = $tabTitles[$tab] ?? 'Chi tiết sự kiện';
$breadcrumbs         = [
    ['title' => 'Dashboard',        'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện', 'url' => '/events'],
    ['title' => $pageHeading],
];
$useEventSidebar     = true;
$eventSidebarEventId = $idSk;
$eventSidebarSection = $tab;

// Tính base path cho JS/API calls
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath   = dirname($scriptPath);
if ($basePath === '\\' || $basePath === '/') {
    $basePath = '';
}

ob_start();
?>

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

                    <div id="eventDetailContent" class="hidden space-y-4">
                        <?php
                        $partialFile = __DIR__ . "/partials/event-detail/tab-{$tab}.php";
                        if (file_exists($partialFile)) {
                            include $partialFile;
                        } else {
                            echo '<div class="px-4 py-3 text-sm border rounded-lg border-amber-200 bg-amber-50 text-amber-700">Tab không tồn tại.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.APP_BASE_PATH = <?php echo json_encode($basePath); ?>;
window.EVENT_DETAIL_ID = <?php echo (int) $idSk; ?>;
window.EVENT_DETAIL_TAB = <?php echo json_encode($tab, JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php if ($tab === 'scoring'): ?>
<script src="<?php echo $basePath; ?>/assets/js/scoring.js"></script>
<?php endif; ?>

<?php if (in_array($tab, ['nhom-my', 'nhom-all', 'nhom-request'])): ?>
<script src="<?php echo $basePath; ?>/assets/js/nhom_thi.js"></script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';