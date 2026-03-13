<?php
/**
 * Partial: Tab Quản lý Tiểu ban (Báo cáo)
 * Biến cần có: $idSk, $tab, $perm
 * JS xử lý: event-detail.js — khoiTaoTabSubcommittees()
 */
$canEdit = !empty($perm['quan_ly_tieuban']) || !empty($perm['cauhinh_sukien']);
?>
<div id="subcommitteesTab">

    <!-- Thống kê tổng quan -->
    <div id="subcommitteeStats" class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-6">
        <div class="p-4 border rounded-xl border-slate-200 bg-white">
            <p class="mb-0 text-xs font-bold uppercase text-slate-400">Tiểu ban đã tạo</p>
            <p id="statSoTieuBan" class="mb-0 text-2xl font-bold text-emerald-600">--</p>
        </div>
        <div class="p-4 border rounded-xl border-slate-200 bg-white">
            <p class="mb-0 text-xs font-bold uppercase text-slate-400">Bài đã xếp phòng</p>
            <p id="statSoBaiXep" class="mb-0 text-2xl font-bold text-blue-600">--</p>
        </div>
        <div class="p-4 border rounded-xl border-slate-200 bg-white">
            <p class="mb-0 text-xs font-bold uppercase text-slate-400">Bài chờ xếp</p>
            <p id="statSoBaiCho" class="mb-0 text-2xl font-bold text-amber-500">--</p>
        </div>
    </div>

    <!-- Header danh sách -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <p class="mb-0 text-sm font-bold text-slate-700">
                <i class="fas fa-sitemap mr-2 text-emerald-500"></i>Danh sách Tiểu ban báo cáo
            </p>
            <p class="mb-0 text-xs text-slate-400">Mỗi tiểu ban là một phòng báo cáo độc lập trong vòng thi</p>
        </div>
        <?php if ($canEdit): ?>
        <button id="btnTaoTieuBanMoi" type="button"
            class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-emerald-600 to-teal-400 rounded-lg shadow-soft-md hover:shadow-soft-lg transition-all">
            <i class="fas fa-plus mr-1.5"></i> Tạo tiểu ban mới
        </button>
        <?php endif; ?>
    </div>

    <!-- Danh sách tiểu ban (JS render vào đây) -->
    <div id="subcommitteeList">
        <div class="p-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
            <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
            <p class="text-sm">Đang tải danh sách tiểu ban...</p>
        </div>
    </div>

</div>

<script>
    // Truyền quyền sang JS
    window.TB_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
</script>