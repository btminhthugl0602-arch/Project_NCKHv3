<?php
/**
 * Partial: Tab "Lời mời tham gia"
 * Biến từ event-detail.php: $idSk, $tab, $basePath
 */
?>

<div class="mb-4">
    <p class="text-xs font-bold uppercase text-slate-400 mb-0.5">Lời mời đang chờ</p>
    <p class="text-sm text-slate-500">Các nhóm đã gửi lời mời cho bạn tham gia</p>
</div>

<!-- States -->
<div id="invitesLoading" class="py-4 text-sm text-slate-500">
    <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
    Đang tải lời mời...
</div>
<div id="invitesError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

<div id="invitesEmpty" class="hidden p-8 border border-dashed border-slate-300 rounded-xl bg-slate-50 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-white border border-slate-200">
        <span class="material-symbols-outlined text-xl text-slate-400">mail</span>
    </div>
    <p class="text-slate-600 font-semibold text-sm mb-1">Không có lời mời nào đang chờ</p>
    <p class="text-slate-400 text-sm">
        Hãy <a href="/event-detail?id_sk=<?= $idSk ?>&tab=nhom-all" class="text-primary font-semibold hover:underline">xem tất cả nhóm</a>
        để tìm nhóm phù hợp và xin tham gia.
    </p>
</div>

<div id="invitesList" class="hidden space-y-3"></div>

<!-- Lịch sử đã xử lý -->
<div id="historySection" class="hidden mt-6 pt-6 border-t border-slate-200">
    <p class="text-xs font-bold uppercase text-slate-400 mb-3">Đã xử lý</p>
    <div id="historyList" class="space-y-3 opacity-60"></div>
</div>

<script>
window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
window.NHOM_THI_TAB   = 'loi-moi';
</script>
