<?php
/**
 * Partial: Tab Quản lý Tiểu ban (Báo cáo)
 * Biến: $idSk, $tab, $perm
 * JS  : inline IIFE bên dưới, gọi khoiTaoTabSubcommittees() từ event-detail.js
 */
$canEdit = !empty($perm['quan_ly_tieuban']) || !empty($perm['cauhinh_sukien']);
?>
<div id="subcommitteesTab">

    <!-- ── Thống kê tổng quan ────────────────────────────── -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
        <div class="relative flex items-center gap-4 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tl from-purple-700 to-fuchsia-500 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-sitemap text-lg"></i>
            </div>
            <div>
                <p class="mb-0 text-xs font-bold uppercase text-slate-500 tracking-wide">Tiểu ban đã tạo</p>
                <p id="statSoTieuBan" class="mb-0 text-2xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
        <div class="relative flex items-center gap-4 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tl from-blue-600 to-cyan-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-file-alt text-lg"></i>
            </div>
            <div>
                <p class="mb-0 text-xs font-bold uppercase text-slate-500 tracking-wide">Bài đã xếp phòng</p>
                <p id="statSoBaiXep" class="mb-0 text-2xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
        <div class="relative flex items-center gap-4 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tl from-amber-600 to-yellow-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-clock text-lg"></i>
            </div>
            <div>
                <p class="mb-0 text-xs font-bold uppercase text-slate-500 tracking-wide">Bài chờ xếp phòng</p>
                <p id="statSoBaiCho" class="mb-0 text-2xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
    </div>

    <!-- ── Thanh công cụ ─────────────────────────────────── -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <p class="mb-0 text-sm font-bold text-slate-700">
                <i class="fas fa-sitemap mr-2 text-emerald-500"></i>Danh sách Tiểu ban báo cáo
            </p>
            <p class="mb-0 text-xs text-slate-400 mt-0.5">Mỗi tiểu ban là một phòng báo cáo độc lập trong vòng thi</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button id="btnRefreshTieuBan" type="button"
                class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-soft-sm">
                <i class="fas fa-sync-alt mr-1.5"></i>Làm mới
            </button>
            <?php if ($canEdit): ?>
            <button id="btnTaoTieuBanMoi" type="button"
                class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-fuchsia-500 rounded-lg shadow-soft-md hover:shadow-soft-xl transition-all">
                <i class="fas fa-plus mr-1.5"></i>Tạo tiểu ban mới
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Bộ lọc nhanh ──────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-3 mb-5 p-3 bg-slate-50 border border-slate-200 rounded-xl">
        <div class="relative flex-1 min-w-44">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <input id="tbSearchInput" type="text" placeholder="Tìm tiểu ban, phòng..."
                class="w-full pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-200 transition">
        </div>
        <select id="tbFilterVong"
            class="px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:border-emerald-400 transition">
            <option value="">Tất cả vòng thi</option>
        </select>
        <span id="tbFilterCount" class="text-xs text-slate-400 italic ml-1"></span>
    </div>

    <!-- ── Danh sách (JS render) ─────────────────────────── -->
    <div id="subcommitteeList">
        <div class="p-10 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl bg-slate-50">
            <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-slate-300"></i>
            <p class="text-sm font-medium">Đang tải danh sách tiểu ban...</p>
        </div>
    </div>

</div>

<script>
    window.TB_CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;
</script>