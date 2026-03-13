<?php
/**
 * Partial: Tab Phân công Ban Giám Khảo
 * Biến: $idSk, $tab, $perm
 */
$canEdit = !empty($perm['quan_ly_tieuban']) || !empty($perm['cauhinh_sukien']);
?>
<div id="judgesTab">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <p class="mb-0 text-sm font-bold text-slate-700">
                <i class="fas fa-user-tie mr-2 text-blue-500"></i>Phân công Ban Giám Khảo theo Tiểu ban
            </p>
            <p class="mb-0 text-xs text-slate-400 mt-0.5">Gán giảng viên vào từng phòng báo cáo và thiết lập vai trò trong Hội đồng</p>
        </div>
        <button id="btnRefreshJudges" type="button"
            class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-soft-sm flex-shrink-0">
            <i class="fas fa-sync-alt mr-1.5"></i>Làm mới
        </button>
    </div>

    <!-- Bảng tổng hợp -->
    <div id="judgesTableWrapper" class="overflow-x-auto border border-slate-200 rounded-2xl shadow-soft-sm">
        <div class="p-10 text-center text-slate-400">
            <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-slate-300"></i>
            <p class="text-sm">Đang tải dữ liệu phân công...</p>
        </div>
    </div>

    <!-- Thống kê tóm tắt -->
    <div id="judgesStatSummary" class="hidden mt-4">
        <div class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-700">
            <i class="fas fa-info-circle flex-shrink-0"></i>
            <span id="judgesStatText"></span>
        </div>
    </div>

</div>

<script>
    window.JUDGES_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
</script>