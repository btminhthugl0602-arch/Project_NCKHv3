<?php
/**
 * Partial: Tab Phân công & Quản lý Điểm
 * Biến cần có: $idSk, $tab
 * JS xử lý: scoring.js (module riêng, load theo tab)
 */
?>
<!-- Chọn vòng thi -->
<div class="mb-4 p-4 border rounded-xl border-slate-200 bg-slate-50">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block mb-1 text-xs font-semibold text-slate-600">Chọn vòng thi</label>
            <select id="scoringVongThiSelect" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-purple-500 focus:outline-none">
                <option value="">-- Chọn vòng thi --</option>
            </select>
        </div>
        <div class="flex-shrink-0">
            <p class="mb-1 text-xs font-semibold text-slate-600">Trạng thái</p>
            <span id="scoringVongThiStatus" class="px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Chưa chọn</span>
        </div>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
    <div class="p-4 border rounded-xl border-slate-200 bg-white shadow-soft-sm">
        <p class="text-xs font-bold uppercase text-slate-400">Tổng bài nộp</p>
        <p id="statTongSanPham" class="mb-0 text-2xl font-bold text-slate-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
        <p class="text-xs font-bold uppercase text-amber-600">Đã phân công</p>
        <p id="statDaPhanCong" class="mb-0 text-2xl font-bold text-amber-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-cyan-200 bg-gradient-to-br from-cyan-50 to-blue-50">
        <p class="text-xs font-bold uppercase text-cyan-600">Đã chấm xong</p>
        <p id="statDaChamXong" class="mb-0 text-2xl font-bold text-cyan-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
        <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt</p>
        <p id="statDaDuyet" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
    </div>
</div>

<!-- Sub-tabs Navigation -->
<div class="border-b border-slate-200 mb-4">
    <nav class="flex flex-wrap -mb-px" id="scoringSubTabs">
        <button type="button" data-subtab="phan-cong"
            class="scoring-subtab-btn active px-4 py-3 text-sm font-semibold border-b-2 border-purple-600 text-purple-600 bg-purple-50 rounded-t-lg">
            <i class="fas fa-user-plus mr-2"></i>Phân công Giám khảo
        </button>
        <button type="button" data-subtab="tien-do"
            class="scoring-subtab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 border-transparent">
            <i class="fas fa-chart-line mr-2"></i>Tiến độ & Kiểm định IRR
        </button>
        <button type="button" data-subtab="xet-duyet"
            class="scoring-subtab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 border-transparent">
            <i class="fas fa-trophy mr-2"></i>Xét kết quả & Bảng vàng
        </button>
    </nav>
</div>

<!-- Sub-tab 1: Phân công Giám khảo -->
<div id="subtab-phan-cong" class="scoring-subtab-content">
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <p class="mb-0 text-sm font-bold text-slate-700">
                        <i class="fas fa-file-alt mr-2 text-slate-400"></i>Danh sách bài nộp
                    </p>
                    <p class="mb-0 text-xs text-slate-500">Click vào bài để phân công giám khảo</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" id="searchSanPham" placeholder="Tìm kiếm..."
                        class="px-3 py-1.5 text-xs border rounded-lg border-slate-300 focus:border-purple-500 focus:outline-none w-40">
                    <select id="filterTrangThai" class="px-3 py-1.5 text-xs border rounded-lg border-slate-300">
                        <option value="">Tất cả trạng thái</option>
                        <option value="chua_phan_cong">Chưa phân công</option>
                        <option value="da_phan_cong">Đã phân công</option>
                        <option value="dang_cham">Đang chấm</option>
                        <option value="da_cham">Đã chấm xong</option>
                    </select>
                </div>
            </div>
            <div id="listSanPhamPhanCong" class="space-y-2 max-h-[500px] overflow-y-auto">
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p class="text-sm">Vui lòng chọn vòng thi...</p>
                </div>
            </div>
        </div>
        <div class="p-4 border rounded-xl border-slate-200 bg-white">
            <div id="panelPhanCong">
                <p class="mb-3 text-sm font-bold text-slate-700">
                    <i class="fas fa-user-tag mr-2 text-slate-400"></i>Phân công giám khảo
                </p>
                <div class="px-4 py-8 text-center text-slate-400 border rounded-lg border-dashed border-slate-300">
                    <i class="fas fa-hand-pointer text-2xl mb-2"></i>
                    <p class="text-sm">Chọn một bài nộp để phân công</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sub-tab 2: Tiến độ & Kiểm định IRR -->
<div id="subtab-tien-do" class="scoring-subtab-content hidden">
    <div class="p-4 border rounded-xl border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="mb-0 text-sm font-bold text-slate-700">
                    <i class="fas fa-tasks mr-2 text-slate-400"></i>Quản lý Tiến độ chấm
                </p>
                <p class="mb-0 text-xs text-slate-500">Nhấn <strong>Chi tiết</strong> để xem phân tích độ lệch; nhấn <strong>Quyết định</strong> để duyệt/loại kết quả</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="warningCountBadge" class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200">
                    <i class="fas fa-info-circle mr-1"></i>Chưa tải
                </span>
                <span class="text-xs text-slate-400">
                    <i class="fas fa-exclamation-triangle text-amber-400 mr-1"></i>Lệch &gt; 30% giữa các GK
                </span>
                <button id="btnRefreshCanhBao"
                    class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                    <i class="fas fa-sync-alt mr-1"></i>Làm mới
                </button>
                <button id="btnGuiNhacNho"
                    class="px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors"
                    title="Gửi thông báo nhắc nhở tới các giám khảo chưa hoàn thành chấm điểm">
                    <i class="fas fa-bell mr-1"></i>Nhắc nhở GK
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="tableTienDoCham">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-bold uppercase text-slate-500 w-28">Nhóm</th>
                        <th class="px-3 py-2.5 text-left text-xs font-bold uppercase text-slate-500">Đề tài</th>
                        <th class="px-3 py-2.5 text-center text-xs font-bold uppercase text-slate-500 w-44">Tiến độ</th>
                        <th class="px-3 py-2.5 text-center text-xs font-bold uppercase text-slate-500 w-28">Điểm TB</th>
                        <th class="px-3 py-2.5 text-center text-xs font-bold uppercase text-slate-500 w-28">Phân tích</th>
                        <th class="px-3 py-2.5 text-center text-xs font-bold uppercase text-slate-500 w-44">Xét duyệt</th>
                    </tr>
                </thead>
                <tbody id="tbodyTienDoCham">
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">Chọn vòng thi để xem tiến độ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sub-tab 3: Xét kết quả & Bảng vàng -->
<div id="subtab-xet-duyet" class="scoring-subtab-content hidden">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
        <div class="p-4 border rounded-xl border-emerald-200 bg-emerald-50">
            <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt</p>
            <p id="statKQDaDuyet" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
        </div>
        <div class="p-4 border rounded-xl border-rose-200 bg-rose-50">
            <p class="text-xs font-bold uppercase text-rose-600">Bị loại</p>
            <p id="statKQBiLoai" class="mb-0 text-2xl font-bold text-rose-700">--</p>
        </div>
        <div class="p-4 border rounded-xl border-amber-200 bg-amber-50">
            <p class="text-xs font-bold uppercase text-amber-600">Chờ duyệt</p>
            <p id="statKQChoDuyet" class="mb-0 text-2xl font-bold text-amber-700">--</p>
        </div>
        <div class="p-4 border rounded-xl border-purple-200 bg-purple-50">
            <p class="text-xs font-bold uppercase text-purple-600">Điểm TB</p>
            <p id="statKQDiemTB" class="mb-0 text-2xl font-bold text-purple-700">--</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="p-4 border rounded-xl border-amber-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <p class="mb-0 text-sm font-bold text-amber-700"><i class="fas fa-clock mr-2"></i>Bài chờ duyệt</p>
                    <p class="mb-0 text-xs text-slate-500">Các bài đã chấm xong, chờ BTC duyệt</p>
                </div>
                <button id="btnDuyetTatCa"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 rounded-lg hover:bg-emerald-600 transition-colors">
                    <i class="fas fa-check-double mr-1"></i>Duyệt tất cả
                </button>
            </div>
            <div id="listCanDuyet" class="space-y-2 max-h-[400px] overflow-y-auto">
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-sm">Không có bài chờ duyệt</p>
                </div>
            </div>
        </div>
        <div class="p-4 border rounded-xl border-amber-300 bg-gradient-to-br from-amber-50 to-yellow-50">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <p class="mb-0 text-sm font-bold text-amber-700">
                        <i class="fas fa-trophy mr-2 text-amber-500"></i>Bảng vàng - Xếp hạng
                    </p>
                    <p class="mb-0 text-xs text-amber-600">Các bài đã duyệt, xếp theo điểm giảm dần</p>
                </div>
                <button id="btnExportRanking"
                    class="px-3 py-1.5 text-xs font-semibold text-amber-700 bg-white border border-amber-300 rounded-lg hover:bg-amber-100 transition-colors">
                    <i class="fas fa-file-export mr-1"></i>Xuất Excel
                </button>
            </div>
            <div id="listBangVang" class="space-y-2 max-h-[400px] overflow-y-auto">
                <div class="px-4 py-8 text-center text-amber-400">
                    <i class="fas fa-medal text-3xl mb-2"></i>
                    <p class="text-sm">Chưa có bài nào được duyệt</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Phân tích điểm & Quyết định -->
<div id="modalPhanTichDiem" class="hidden fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" id="modalPhanTichOverlay" onclick="scoringModule.closeIRRModal()"></div>
    <div class="relative min-h-screen flex items-start justify-center p-4 pt-8 pb-8">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-list-check text-lg"></i>
                    <span class="font-bold text-base" id="modalPhanTichTitle">Phân tích điểm</span>
                </div>
                <button onclick="scoringModule.closeIRRModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <!-- Modal Body (scrollable) -->
            <div class="p-6 max-h-[75vh] overflow-y-auto" id="modalPhanTichBody">
                <div class="flex items-center justify-center py-16 text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
                    <span>Đang tải...</span>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end bg-slate-50 rounded-b-2xl">
                <button onclick="scoringModule.closeIRRModal()"
                    class="px-6 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors shadow-sm">
                    <i class="fas fa-times mr-2"></i>Đóng
                </button>
            </div>
        </div>
    </div>
</div>
