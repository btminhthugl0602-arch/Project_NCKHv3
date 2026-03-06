<?php
/**
 * Lời mời nhóm
 * URL: /nhom/loi-moi-nhom?id_sk=XX
 */

$pageTitle   = 'Lời mời nhóm - ezManagement';
$currentPage = 'events';
$pageJs      = 'nhom-thi.js';

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

$pageHeading = 'Lời mời nhóm';
$breadcrumbs = [
    ['title' => 'Dashboard',        'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện', 'url' => '/events'],
    ['title' => 'Nhóm thi'],
];

$useEventSidebar     = true;
$eventSidebarEventId = $idSk;
$eventSidebarSection = 'nhom-request';

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
        <div class="p-6 pb-0">
            <h6 class="mb-1">Lời mời đang chờ</h6>
            <p class="mb-0 text-sm text-slate-500">Các nhóm đã gửi lời mời cho bạn tham gia</p>
        </div>
        <div class="flex-auto p-6">
            <!-- States -->
            <div id="invitesLoading" class="text-sm text-slate-500">
                <i class="fas fa-circle-notch fa-spin mr-2"></i>Đang tải lời mời...
            </div>
            <div id="invitesError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
            <div id="invitesEmpty" class="hidden px-4 py-5 text-sm border border-dashed rounded-xl text-slate-500 border-slate-300 bg-slate-50 text-center">
                Không có lời mời nào đang chờ.
                <a href="/nhom/tat-ca-nhom?id_sk=<?= $idSk ?>" class="text-purple-600 font-semibold underline ml-1">Xem tất cả nhóm</a>
            </div>
            <div id="invitesList" class="hidden space-y-4"></div>

            <!-- Lịch sử -->
            <div id="historySection" class="hidden mt-6 pt-6 border-t border-slate-100">
                <h6 class="text-xs font-bold uppercase text-slate-400 mb-3">Đã xử lý</h6>
                <div id="historyList" class="space-y-3 opacity-60"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
    window.NHOM_THI_TAB   = 'loi-moi';
</script>

<?php
$content = ob_get_clean();
include '../../layouts/main_layout.php';