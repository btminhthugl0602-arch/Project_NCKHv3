<?php

/**
 * Partial: Tab Nhập điểm dành cho Giảng viên / Giám khảo
 * Biến cần có: $idSk, $tab
 * JS xử lý: scoring_gv.js (module riêng, load theo tab)
 */
?>
<!-- Chọn vòng thi -->
<div class="mb-4 p-4 border rounded-xl border-slate-200 bg-slate-50">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block mb-1 text-xs font-semibold text-slate-600">Chọn vòng thi</label>
            <select id="gvVongThiSelect"
                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-indigo-500 focus:outline-none">
                <option value="">-- Chọn vòng thi --</option>
            </select>
        </div>
        <div class="flex-shrink-0">
            <p class="mb-1 text-xs font-semibold text-slate-600">Trạng thái phiếu chấm</p>
            <span id="gvPhieuStatus"
                class="px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Chưa chọn</span>
        </div>
    </div>
</div>

<!-- Thống kê nhanh -->
<div class="grid grid-cols-3 gap-4 mb-4">
    <div class="p-4 border rounded-xl border-slate-200 bg-white shadow-soft-sm">
        <p class="text-xs font-bold uppercase text-slate-400">Bài được phân công</p>
        <p id="gvStatTongBai" class="mb-0 text-2xl font-bold text-slate-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
        <p class="text-xs font-bold uppercase text-emerald-600">Đã chấm đủ</p>
        <p id="gvStatDaCham" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
        <p class="text-xs font-bold uppercase text-amber-600">Chưa chấm</p>
        <p id="gvStatChuaCham" class="mb-0 text-2xl font-bold text-amber-700">--</p>
    </div>
</div>

<!-- Layout 2 cột: DS bài | Phiếu chấm -->
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3" id="gvMainContent">

    <!-- Cột trái: Danh sách bài được phân công -->
    <div class="xl:col-span-1">
        <div class="p-4 border rounded-xl border-slate-200 bg-white h-full">
            <p class="mb-3 text-sm font-bold text-slate-700">
                <i class="fas fa-list mr-2 text-slate-400"></i>Bài được phân công
            </p>
            <!-- Placeholder trước khi chọn vòng thi -->
            <div id="gvListSanPhamPlaceholder" class="py-8 text-center text-slate-400">
                <i class="fas fa-arrow-up text-2xl mb-2 block opacity-40"></i>
                <p class="text-sm">Chọn vòng thi để xem danh sách</p>
            </div>
            <!-- Danh sách sản phẩm -->
            <div id="gvListSanPham" class="space-y-2 hidden"></div>
        </div>
    </div>

    <!-- Cột phải: Phiếu chấm điểm -->
    <div class="xl:col-span-2">
        <!-- Trạng thái chờ chọn bài -->
        <div id="gvPhieuChamPlaceholder"
            class="p-8 border rounded-xl border-slate-200 bg-white flex flex-col items-center justify-center text-center text-slate-400 min-h-[300px]">
            <i class="fas fa-clipboard-check text-5xl mb-3 opacity-20"></i>
            <p class="text-sm font-medium">Chọn một bài từ danh sách bên trái để bắt đầu chấm điểm</p>
        </div>

        <!-- Phiếu chấm (ẩn lúc đầu) -->
        <div id="gvPhieuCham" class="hidden">
            <!-- Header phiếu -->
            <div class="p-4 border rounded-xl border-slate-200 bg-white mb-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="mb-0 text-base font-bold text-slate-800" id="gvTenSanPham">--</p>
                        <p class="mb-0 text-xs text-slate-500 mt-0.5">
                            <i class="fas fa-users mr-1"></i>
                            <span id="gvMaNhom">--</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="gvBoTieuChiBadge"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700">
                            <i class="fas fa-clipboard-list mr-1"></i>--
                        </span>
                        <span id="gvChamStatusBadge"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Chưa
                            chấm</span>
                    </div>
                </div>
                <div id="gvTaiLieuSection" class="mt-3 hidden">
                    <div class="border-t border-slate-100 pt-3">
                        <button type="button" id="gvBtnToggleTaiLieu"
                            class="flex items-center gap-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                            <i class="fas fa-folder-open"></i>
                            <span id="gvTaiLieuToggleLabel">Xem tài liệu đã nộp</span>
                            <i id="gvTaiLieuChevron" class="fas fa-chevron-down text-[10px] transition-transform"></i>
                        </button>
                        <div id="gvTaiLieuList" class="mt-2 hidden space-y-2"></div>
                    </div>
                </div>
            </div>

            <!-- Bảng tiêu chí -->
            <div class="p-4 border rounded-xl border-slate-200 bg-white">
                <p class="mb-3 text-sm font-bold text-slate-700">
                    <i class="fas fa-tasks mr-2 text-indigo-400"></i>Tiêu chí chấm điểm
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase w-8">#</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase">Nội dung tiêu
                                    chí</th>
                                <th class="px-3 py-2 text-center text-xs font-bold text-slate-500 uppercase w-28">Điểm /
                                    Tối đa</th>
                                <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase w-40">Nhận xét
                                </th>
                            </tr>
                        </thead>
                        <tbody id="gvTieuChiTableBody" class="divide-y divide-slate-100"></tbody>
                        <tfoot>
                            <tr class="bg-indigo-50 border-t-2 border-indigo-200">
                                <td colspan="2" class="px-3 py-2 text-sm font-bold text-slate-700 text-right">Tổng điểm
                                    (tạm tính)</td>
                                <td class="px-3 py-2 text-center text-base font-bold text-indigo-700" id="gvTongDiem">--
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Buttons -->
                <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
                    <div id="gvLuuStatus" class="text-xs text-slate-400 italic hidden">
                        <i class="fas fa-check-circle mr-1 text-emerald-500"></i>Đã lưu nháp
                    </div>
                    <div class="flex gap-2 ml-auto">
                        <button id="gvBtnLuuNhap" type="button"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <i class="fas fa-save"></i> Lưu nháp
                        </button>
                        <button id="gvBtnNopPhieu" type="button"
                            class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-paper-plane"></i> Nộp phiếu chấm
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phiếu phúc khảo TRỌNG TÀI (ẩn lúc đầu, chỉ hiện khi isTrongTai=true) -->
        <div id="gvPhieuTrongTai" class="hidden">
            <!-- Banner cảnh báo vai trò -->
            <div class="mb-3 p-3 rounded-xl border border-orange-200 bg-orange-50 flex items-center gap-3">
                <i class="fas fa-balance-scale text-orange-500 text-xl flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-bold text-orange-800">Chế độ Trọng tài Phúc khảo</p>
                    <p class="text-xs text-orange-600 mt-0.5">Bạn đang xem bức tranh tổng quát từ tất cả giám khảo
                        chính. Phán quyết của bạn sẽ là điểm cuối cùng, thay thế điểm trung bình.</p>
                </div>
            </div>

            <!-- Header phiếu -->
            <div class="p-4 border rounded-xl border-orange-200 bg-white mb-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="mb-0 text-base font-bold text-slate-800" id="gvTTTenSanPham">--</p>
                        <p class="mb-0 text-xs text-slate-500 mt-0.5">
                            <i class="fas fa-users mr-1"></i>
                            <span id="gvTTMaNhom">--</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">
                            <i class="fas fa-balance-scale mr-1"></i>Trọng tài
                        </span>
                        <span id="gvTTChamStatusBadge"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Chưa phán
                            quyết</span>
                    </div>
                </div>
            </div>

            <!-- Tóm tắt điểm các GK chính -->
            <div class="mb-3 p-4 border rounded-xl border-slate-200 bg-white">
                <p class="mb-3 text-sm font-bold text-slate-700">
                    <i class="fas fa-chart-bar mr-2 text-slate-400"></i>Tổng điểm các Giám khảo chính
                </p>
                <div id="gvTTGKSummary" class="flex flex-wrap gap-3"></div>
            </div>

            <!-- Bảng phúc khảo: ma trận tiêu chí × giám khảo + điểm TT -->
            <div class="p-4 border rounded-xl border-slate-200 bg-white">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-bold text-slate-700">
                        <i class="fas fa-table mr-2 text-orange-400"></i>Bảng phán quyết từng tiêu chí
                    </p>
                    <span class="text-xs text-slate-400">
                        <i class="fas fa-circle text-red-400 mr-1"></i>Lệch cao (&gt;30%) cần can thiệp
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="gvTTBangPhanQuyet">
                        <!-- thead được sinh bởi JS (số cột GK động) -->
                        <thead id="gvTTTableHead"></thead>
                        <tbody id="gvTTTableBody" class="divide-y divide-slate-100"></tbody>
                        <tfoot>
                            <tr class="bg-orange-50 border-t-2 border-orange-200">
                                <td class="px-3 py-2 text-xs font-bold text-slate-600">Tổng điểm phán quyết</td>
                                <td id="gvTTTongDiemGKCols"></td>
                                <td class="px-3 py-2 text-center">
                                    <span class="text-xs text-slate-500">Avg GK</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <p class="text-base font-bold text-orange-700" id="gvTTTongDiem">--</p>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Buttons phúc khảo -->
                <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
                    <div id="gvTTLuuStatus" class="text-xs text-slate-400 italic hidden">
                        <i class="fas fa-check-circle mr-1 text-emerald-500"></i>Đã lưu nháp phán quyết
                    </div>
                    <div class="flex gap-2 ml-auto">
                        <button id="gvTTBtnLuuNhap" type="button"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <i class="fas fa-save"></i> Lưu nháp
                        </button>
                        <button id="gvTTBtnNopPhieu" type="button"
                            class="px-4 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-gavel"></i> Xác nhận phán quyết
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template: Item sản phẩm trong danh sách (dùng JS để clone & fill) -->
<template id="gvSanPhamItemTemplate">
    <div class="gv-sp-item group p-3 border rounded-lg cursor-pointer transition-all
                border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50" data-id="">
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-700 truncate gv-sp-ten-san-pham"></p>
                <p class="text-xs text-slate-500 mt-0.5">
                    <i class="fas fa-users mr-1 text-slate-400"></i>
                    <span class="gv-sp-ma-nhom"></span>
                </p>
            </div>
            <div class="flex-shrink-0 gv-sp-badge"></div>
        </div>
        <div class="mt-2">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                <span class="gv-sp-tien-do-text">0 / 0 tiêu chí</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div class="gv-sp-tien-do-bar h-full bg-emerald-500 rounded-full transition-all" style="width: 0%">
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Template: Hàng tiêu chí trong bảng chấm điểm (Giám khảo thường) -->
<template id="gvTieuChiRowTemplate">
    <tr class="gv-tieuchi-row hover:bg-slate-50/50" data-id="">
        <td class="px-3 py-2 text-xs text-slate-500 gv-tc-stt"></td>
        <td class="px-3 py-2 text-sm text-slate-700 gv-tc-noi-dung"></td>
        <td class="px-3 py-2">
            <div class="flex items-center justify-center gap-1">
                <input type="number" class="gv-tc-input w-16 px-2 py-1 text-sm text-center border rounded-lg border-slate-300
                           focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-200" min="0"
                    step="0.5" placeholder="0">
                <span class="text-xs text-slate-400">/ <span
                        class="gv-tc-diem-toi-da font-medium text-slate-600"></span></span>
            </div>
        </td>
        <td class="px-3 py-2">
            <input type="text" class="gv-tc-nhan-xet w-full px-2 py-1 text-xs border rounded-lg border-slate-200
                       focus:border-indigo-400 focus:outline-none placeholder-slate-400"
                placeholder="Nhận xét (tuỳ chọn)">
        </td>
    </tr>
</template>

<!-- Template: Hàng tiêu chí trong bảng phúc khảo (Trọng tài) -->
<!-- Mỗi hàng hiển thị: điểm từng GK chính (read-only), điểm avg, điểm TT tự nhập, nhận xét -->
<template id="gvTieuChiTrongTaiRowTemplate">
    <tr class="gv-tt-row" data-id="">
        <!-- Tiêu chí -->
        <td class="px-3 py-2.5 align-top">
            <p class="text-xs font-semibold text-slate-700 gv-tt-noi-dung"></p>
            <p class="text-xs text-slate-400 gv-tt-diem-toi-da-label"></p>
        </td>
        <!-- Điểm GK chính — sinh ra động bởi JS vào .gv-tt-gk-scores -->
        <td class="gv-tt-gk-scores px-3 py-2.5 align-top text-center text-xs text-slate-600"></td>
        <!-- Trung bình GK + % lệch -->
        <td class="px-3 py-2.5 align-top text-center">
            <p class="text-sm font-bold text-slate-700 gv-tt-avg"></p>
            <p class="text-xs font-semibold gv-tt-dev-badge"></p>
        </td>
        <!-- Điểm phán quyết TT -->
        <td class="px-3 py-2.5 align-top text-center">
            <div class="flex items-center justify-center gap-1">
                <input type="number" class="gv-tt-input w-16 px-2 py-1 text-sm text-center border rounded-lg
                           focus:outline-none focus:ring-1 focus:ring-orange-300" min="0" step="0.5" placeholder="—">
                <span class="text-xs text-slate-400">/ <span
                        class="gv-tt-diem-toi-da font-medium text-slate-600"></span></span>
            </div>
        </td>
        <!-- Lý do phán quyết (bắt buộc nếu tiêu chí lệch cao) -->
        <td class="px-3 py-2.5 align-top">
            <input type="text" class="gv-tt-nhan-xet w-full px-2 py-1 text-xs border rounded-lg border-slate-200
                       focus:border-orange-400 focus:outline-none placeholder-slate-400"
                placeholder="Lý do / nhận xét">
        </td>
    </tr>
</template>
<!-- ══════════════════════════════════════════════════════════════
     SECTION: Bài phản biện tiểu ban (chấm offline)
     Hiển thị khi GV được phân công phản biện qua tiểu ban
     JS xử lý: phan_cong_phan_bien.js (load cùng scoring.js)
     ═══════════════════════════════════════════════════════════ -->
<div id="pbTieuBanSection" class="mt-6 hidden">
    <div class="flex items-center gap-3 mb-4">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span
            class="flex items-center gap-2 px-3 py-1 text-xs font-bold text-purple-700 bg-purple-50 border border-purple-200 rounded-full">
            <i class="fas fa-sitemap text-[10px]"></i>Phản biện tiểu ban
        </span>
        <div class="flex-1 h-px bg-slate-200"></div>
    </div>

    <!-- Thống kê phản biện -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="p-4 border rounded-xl border-slate-200 bg-white shadow-soft-sm">
            <p class="text-xs font-bold uppercase text-slate-400">Bài được phân công PB</p>
            <p id="pbStatTong" class="mb-0 text-2xl font-bold text-slate-700">--</p>
        </div>
        <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
            <p class="text-xs font-bold uppercase text-emerald-600">Đã nộp phiếu</p>
            <p id="pbStatDaNop" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
        </div>
        <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
            <p class="text-xs font-bold uppercase text-amber-600">Chưa hoàn thành</p>
            <p id="pbStatChua" class="mb-0 text-2xl font-bold text-amber-700">--</p>
        </div>
    </div>

    <!-- Layout 2 cột giống scoring-gv -->
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

        <!-- Cột trái: DS bài phản biện -->
        <div class="xl:col-span-1">
            <div class="p-4 border rounded-xl border-slate-200 bg-white h-full">
                <p class="mb-3 text-sm font-bold text-slate-700">
                    <i class="fas fa-list mr-2 text-purple-400"></i>Bài phản biện của tôi
                </p>
                <div id="pbListSanPham" class="space-y-2">
                    <div class="py-8 text-center text-slate-400">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
                        <p class="text-sm">Đang tải...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: Phiếu chấm phản biện -->
        <div class="xl:col-span-2">
            <div id="pbPhieuPlaceholder"
                class="p-8 border rounded-xl border-slate-200 bg-white flex flex-col items-center justify-center text-center text-slate-400 min-h-[300px]">
                <i class="fas fa-clipboard-check text-5xl mb-3 opacity-20"></i>
                <p class="text-sm font-medium">Chọn một bài từ danh sách bên trái để nhập điểm phản biện</p>
            </div>

            <div id="pbPhieuCham" class="hidden">
                <!-- Header -->
                <div class="p-4 border rounded-xl border-slate-200 bg-white mb-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="mb-0 text-base font-bold text-slate-800" id="pbTenSanPham">--</p>
                            <p class="mb-0 text-xs text-slate-500 mt-0.5">
                                <i class="fas fa-sitemap mr-1"></i>
                                <span id="pbTenTieuBan">--</span>
                                <span class="mx-1">·</span>
                                <i class="fas fa-users mr-1"></i>
                                <span id="pbMaNhom">--</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="pbBoTieuChiBadge"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                <i class="fas fa-clipboard-list mr-1"></i>--
                            </span>
                            <span id="pbChamStatusBadge"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">--</span>
                        </div>
                    </div>
                </div>

                <!-- Bảng tiêu chí -->
                <div class="p-4 border rounded-xl border-slate-200 bg-white">
                    <p class="mb-3 text-sm font-bold text-slate-700">
                        <i class="fas fa-tasks mr-2 text-purple-400"></i>Tiêu chí phản biện
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase w-8">#
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase">Nội dung
                                        tiêu chí</th>
                                    <th class="px-3 py-2 text-center text-xs font-bold text-slate-500 uppercase w-28">
                                        Điểm / Tối đa</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase w-40">Nhận
                                        xét</th>
                                </tr>
                            </thead>
                            <tbody id="pbTieuChiTbody" class="divide-y divide-slate-100"></tbody>
                            <tfoot>
                                <tr class="bg-purple-50 border-t-2 border-purple-200">
                                    <td colspan="2" class="px-3 py-2 text-sm font-bold text-slate-700 text-right">Tổng
                                        điểm (tạm tính)</td>
                                    <td class="px-3 py-2 text-center text-base font-bold text-purple-700"
                                        id="pbTongDiem">--</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
                        <div id="pbLuuStatus" class="text-xs text-slate-400 italic hidden">
                            <i class="fas fa-check-circle mr-1 text-emerald-500"></i>Đã lưu nháp
                        </div>
                        <div class="flex gap-2 ml-auto">
                            <button id="pbBtnLuuNhap" type="button"
                                class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2">
                                <i class="fas fa-save"></i> Lưu nháp
                            </button>
                            <button id="pbBtnNopPhieu" type="button"
                                class="px-4 py-2 text-sm font-semibold text-white rounded-lg hover:opacity-90 transition-all flex items-center gap-2"
                                style="background:linear-gradient(135deg,#9333ea,#7c3aed);">
                                <i class="fas fa-paper-plane"></i> Nộp phiếu phản biện
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template: Item bài phản biện trong danh sách -->
<template id="pbSanPhamItemTemplate">
    <div class="pb-sp-item p-3 border rounded-lg cursor-pointer transition-all border-slate-200 hover:border-purple-300 hover:bg-purple-50/50"
        data-id="">
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-700 truncate pb-sp-ten"></p>
                <p class="text-xs text-slate-500 mt-0.5">
                    <i class="fas fa-sitemap mr-1 text-slate-400"></i>
                    <span class="pb-sp-tieubán"></span>
                </p>
            </div>
            <div class="flex-shrink-0 pb-sp-badge"></div>
        </div>
        <div class="mt-2">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                <span class="pb-sp-tiendo-text">0 / 0 tiêu chí</span>
            </div>
            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div class="pb-sp-tiendo-bar h-full bg-purple-500 rounded-full transition-all" style="width:0%"></div>
            </div>
        </div>
    </div>
</template>