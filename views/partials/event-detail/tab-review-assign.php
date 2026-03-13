<?php
/**
 * Partial: Tab Phân công Phản biện — Admin sự kiện
 * Biến: $idSk, $tab, $perm, $basePath
 */
?>
<div id="reviewAssignContainer">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <p class="mb-0 text-sm font-bold text-slate-700">
                <i class="fas fa-user-check mr-2 text-indigo-500"></i>Phân công Phản biện
            </p>
            <p class="mb-0 text-xs text-slate-400 mt-0.5">
                Gán giảng viên trong tiểu ban phản biện từng đề tài. GV hướng dẫn không được phân công phản biện nhóm của mình.
            </p>
        </div>
        <button id="btnRefreshReviewAssign" type="button"
            class="inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-soft-sm flex-shrink-0">
            <i class="fas fa-sync-alt mr-1.5"></i>Làm mới
        </button>
    </div>

    <!-- Thống kê -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="relative flex items-center gap-3 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tl from-indigo-700 to-violet-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-file-alt text-sm"></i>
            </div>
            <div>
                <p class="mb-0 text-[10px] font-bold uppercase text-slate-400 tracking-wide">Tổng bài</p>
                <p id="raSoTongBai" class="mb-0 text-xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
        <div class="relative flex items-center gap-3 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tl from-amber-600 to-yellow-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-clock text-sm"></i>
            </div>
            <div>
                <p class="mb-0 text-[10px] font-bold uppercase text-slate-400 tracking-wide">Chưa phân công</p>
                <p id="raChuaPhanCong" class="mb-0 text-xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
        <div class="relative flex items-center gap-3 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tl from-cyan-600 to-sky-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-tasks text-sm"></i>
            </div>
            <div>
                <p class="mb-0 text-[10px] font-bold uppercase text-slate-400 tracking-wide">Đang chấm</p>
                <p id="raDangCham" class="mb-0 text-xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
        <div class="relative flex items-center gap-3 p-4 bg-white border-0 shadow-soft-xl rounded-2xl">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tl from-emerald-600 to-green-400 shadow-soft-md text-white flex-shrink-0">
                <i class="fas fa-check-double text-sm"></i>
            </div>
            <div>
                <p class="mb-0 text-[10px] font-bold uppercase text-slate-400 tracking-wide">Đã nộp phiếu</p>
                <p id="raDaNop" class="mb-0 text-xl font-extrabold text-slate-700">--</p>
            </div>
        </div>
    </div>

    <!-- Danh sách bài -->
    <div id="raDanhSachWrap" class="space-y-4">
        <div class="flex items-center justify-center py-12 text-slate-400">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                <p class="text-sm">Đang tải danh sách bài...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal phân công GV -->
<div id="raModalPhanCong" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="raModalBackdrop"></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl z-10 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div>
                <h5 class="mb-0 text-sm font-bold text-slate-700" id="raModalTitle">Phân công phản biện</h5>
                <p class="mb-0 text-xs text-slate-400" id="raModalSubtitle"></p>
            </div>
            <button id="raBtnCloseModal" type="button"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <div class="p-6 max-h-[65vh] overflow-y-auto" id="raModalBody">
            <div class="flex items-center justify-center py-8 text-slate-400">
                <i class="fas fa-spinner fa-spin text-xl mr-2"></i> Đang tải...
            </div>
        </div>
    </div>
</div>