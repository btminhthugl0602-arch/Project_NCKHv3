<?php
/**
 * Partial: Tab "Lời mời & Yêu cầu"
 * Biến từ event-detail.php: $idSk, $tab, $basePath
 *
 * Subtab:
 *   loi-moi  — lời mời nhóm gửi đến user (ChieuMoi=0, pending)
 *   yeu-cau  — yêu cầu user tự gửi đi   (ChieuMoi=1, pending + lịch sử)
 */

$allowedRequestTabs = ['loi-moi', 'yeu-cau'];
$requestTab = 'loi-moi';
if (isset($_GET['request_tab']) && in_array($_GET['request_tab'], $allowedRequestTabs, true)) {
    $requestTab = $_GET['request_tab'];
}
$requestTabTitles = [
    'loi-moi' => 'Lời mời',
    'yeu-cau' => 'Yêu cầu của tôi',
];
?>

<!-- Sub-tab bar -->
<div class="flex border-b border-slate-200 bg-white px-4 -mx-6 mb-6">
    <?php foreach ($requestTabTitles as $slug => $label): ?>
        <a href="?id_sk=<?= $idSk ?>&tab=nhom-request&request_tab=<?= $slug ?>"
            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px
            <?= $requestTab === $slug
                ? 'border-primary text-primary font-semibold'
                : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($requestTab === 'loi-moi'): ?>
<!-- ══════════════════════════════════════════
     SUBTAB: LỜI MỜI
     Nhóm gửi lời mời đến user (ChieuMoi=0)
══════════════════════════════════════════ -->
<div class="mb-4">
    <p class="text-xs font-bold uppercase text-slate-400 mb-0.5">Lời mời và yêu cầu đang chờ</p>
    <p class="text-sm text-slate-500">Bao gồm lời mời bạn nhận được và yêu cầu xin vào nhóm do bạn làm chủ nhóm</p>
</div>

<div id="invitesLoading" class="py-4 text-sm text-slate-500">
    <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
    Đang tải lời mời...
</div>
<div id="invitesError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
<div id="invitesEmpty" class="hidden"></div>
<div id="invitesList" class="hidden space-y-3"></div>

<?php else: ?>
<!-- ══════════════════════════════════════════
     SUBTAB: YÊU CẦU CỦA TÔI
     User tự gửi yêu cầu tham gia nhóm (ChieuMoi=1)
══════════════════════════════════════════ -->
<div class="mb-4">
    <p class="text-xs font-bold uppercase text-slate-400 mb-0.5">Yêu cầu đang chờ</p>
    <p class="text-sm text-slate-500">Các yêu cầu tham gia nhóm bạn đã gửi đi</p>
</div>

<div id="sentLoading" class="py-4 text-sm text-slate-500">
    <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
    Đang tải yêu cầu...
</div>
<div id="sentError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
<div id="sentPendingEmpty" class="hidden"></div>
<div id="sentPendingList" class="hidden space-y-3"></div>

<!-- Lịch sử đã xử lý -->
<div id="sentHistorySection" class="hidden mt-6 pt-6 border-t border-slate-200">
    <p class="text-xs font-bold uppercase text-slate-400 mb-3">Đã xử lý</p>
    <div id="sentHistoryList" class="space-y-2 opacity-70"></div>
</div>
<?php endif; ?>

<script>
window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
window.NHOM_THI_TAB   = 'loi-moi';
window.REQUEST_SUBTAB = <?= json_encode($requestTab) ?>;
</script>
