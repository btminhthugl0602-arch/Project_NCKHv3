<?php
/**
 * Partial: Tab Thiết lập bộ tiêu chí
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (khoiTaoTabConfigCriteria, renderCriteriaSetList, xoaBoTieuChi, ...)
 */
?>
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <!-- Form tạo/sửa bộ tiêu chí -->
    <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <p class="mb-0 text-xs font-bold uppercase text-slate-400">Tạo phiếu chấm điểm theo sự kiện</p>
                <p class="mb-0 text-sm text-slate-500">Cho phép tạo mới hoặc nhân bản bộ tiêu chí có sẵn để tinh chỉnh điểm tối đa theo sự kiện.</p>
            </div>
            <button id="criteriaResetForm" type="button"
                class="inline-flex items-center px-3 py-2 text-xs font-bold uppercase bg-white border rounded-lg border-slate-300 text-slate-700">
                Làm mới form
            </button>
        </div>

        <!-- Nhân bản nhanh -->
        <div class="p-3 mb-3 border rounded-lg border-slate-200 bg-slate-50">
            <label class="block mb-1 text-xs font-semibold text-slate-700">Nhân bản nhanh bộ tiêu chí có sẵn</label>
            <div class="flex flex-wrap gap-2">
                <select id="criteriaReuseSetDropdown" class="flex-1 min-w-[240px] px-3 py-2 text-sm border rounded-lg border-slate-300">
                    <option value="">-- Chọn bộ tiêu chí để nhân bản --</option>
                </select>
                <button id="criteriaCloneSetBtn" type="button"
                    class="inline-flex items-center px-3 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">
                    Nhân bản vào form
                </button>
            </div>
        </div>

        <input id="criteriaEditId" type="hidden" value="0" />
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-5">
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tên bộ tiêu chí</label>
                <input id="criteriaTenBo" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"
                    placeholder="Ví dụ: Phiếu chấm Vòng Bán kết" />
            </div>
            <div class="md:col-span-3">
                <label class="block mb-1 text-xs font-semibold text-slate-700">Áp dụng cho vòng</label>
                <select id="criteriaVongThi" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300">
                    <option value="0">-- Chưa gán vòng thi --</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>
                <input id="criteriaMoTa" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"
                    placeholder="Mô tả ngắn bộ tiêu chí" />
            </div>
        </div>

        <!-- Bảng tiêu chí -->
        <div class="mt-4 overflow-x-auto border rounded-lg border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-2 py-2 text-center text-xs font-bold uppercase text-slate-500 w-12">STT</th>
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Nội dung tiêu chí</th>
                        <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500 w-24">Điểm tối đa</th>
                        <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500 w-20">Tỷ trọng</th>
                        <th class="px-2 py-2 text-center text-xs font-bold uppercase text-slate-500 w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="criteriaTableBody"></tbody>
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-right text-xs font-bold text-slate-600">Tổng:</td>
                        <td class="px-3 py-2 text-center text-xs font-bold text-slate-700" id="criteriaTotalDiem">0</td>
                        <td class="px-3 py-2 text-center text-xs font-bold text-slate-700" id="criteriaTotalTyTrong">0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
            <div class="flex items-center gap-2">
                <button id="criteriaAddRow" type="button"
                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold uppercase bg-white border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm tiêu chí
                </button>
                <span class="text-xs text-slate-400">Gõ nội dung hoặc chọn từ gợi ý</span>
            </div>
            <button id="criteriaSaveBtn" type="button"
                class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md hover:scale-102 active:opacity-85">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Lưu bộ tiêu chí
            </button>
        </div>
        <datalist id="criteriaBankList"></datalist>
    </div>

    <!-- Bộ tiêu chí của sự kiện này -->
    <div class="p-4 border rounded-xl border-slate-200 bg-white">
        <p class="mb-0 text-xs font-bold uppercase text-slate-400">Bộ tiêu chí của sự kiện này</p>
        <p class="mb-1 text-xs text-slate-400">Chỉ hiển thị các bộ tiêu chí đã được gán vào vòng thi của sự kiện.</p>
        <div id="criteriaSetList" class="mt-2 space-y-2 text-sm text-slate-600">
            <div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50">Đang tải bộ tiêu chí...</div>
        </div>
    </div>
</div>
