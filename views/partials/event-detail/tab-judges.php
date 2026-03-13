<?php
/**
 * Partial: Tab Phân công Ban giám khảo
 * Biến cần có: $idSk, $tab, $perm
 * JS xử lý: event-detail.js — khoiTaoTabJudges()
 */
$canEdit = !empty($perm['quan_ly_tieuban']) || !empty($perm['cauhinh_sukien']);
?>
<div id="judgesTab">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <p class="mb-0 text-sm font-bold text-slate-700">
                <i class="fas fa-user-tie mr-2 text-slate-500"></i>Phân công Ban Giám Khảo theo Tiểu ban
            </p>
            <p class="mb-0 text-xs text-slate-400">Gán giảng viên vào từng phòng báo cáo</p>
        </div>
        <button id="btnRefreshJudges" type="button"
            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="fas fa-sync-alt mr-1.5"></i> Làm mới
        </button>
    </div>

    <!-- Bảng phân công -->
    <div id="judgesTableWrapper" class="overflow-x-auto border border-slate-200 rounded-xl">
        <div class="p-8 text-center text-slate-400">
            <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
            <p class="text-sm">Đang tải dữ liệu...</p>
        </div>
    </div>

</div>

<script>
    window.JUDGES_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
</script>