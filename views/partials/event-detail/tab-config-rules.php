<?php
/**
 * Partial: Tab Cấu hình quy chế
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (khoiTaoTabConfigRules, token builder, AST parser)
 */
?>
<div class="grid grid-cols-1 gap-4 xl:grid-cols-3 criteria-workspace">
    <!-- Builder quy chế -->
    <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
        <p class="mb-1 text-xs font-bold uppercase text-slate-400">Trình xây dựng quy chế logic</p>
        <p class="mb-3 text-sm text-slate-500">Tạo các điều kiện đơn và ghép token để hoàn thiện quy chế trước. Ngữ cảnh áp dụng sẽ chọn ở bước cuối trước khi lưu.</p>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="block mb-1 text-xs font-semibold text-slate-700">Thuộc tính kiểm tra</label>
                <select id="ruleInputThuocTinh" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"></select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Toán tử</label>
                <select id="ruleInputToanTu" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"></select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Giá trị</label>
                <input id="ruleInputGiaTri" list="ruleGiaTriSuggestions" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300" />
                <datalist id="ruleGiaTriSuggestions"></datalist>
                <p id="ruleGiaTriHint" class="mt-1 mb-0 text-xs text-slate-500">Chọn thuộc tính để xem giá trị gợi ý.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-3">
            <button id="btnAddCondition" type="button"
                class="inline-flex items-center px-3 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">
                Thêm điều kiện
            </button>
            <span class="text-xs text-slate-500">Điều kiện tạo ra sẽ được ánh xạ A, B, C...</span>
        </div>

        <div id="conditionPool" class="p-3 mt-3 border rounded-lg border-slate-200 bg-slate-50 min-h-[52px]"></div>

        <div class="mt-4">
            <p class="mb-2 text-xs font-bold uppercase text-slate-400">Bộ ghép token logic</p>
            <div class="flex flex-wrap gap-2 mb-2">
                <button id="tokenAnd"       type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">AND</button>
                <button id="tokenOr"        type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">OR</button>
                <button id="tokenOpen"      type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">(</button>
                <button id="tokenClose"     type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">)</button>
                <button id="tokenBackspace" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 border border-slate-300">Xóa token cuối</button>
                <button id="tokenClear"     type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 border border-slate-300">Làm mới</button>
            </div>
            <div id="tokenPreview" class="px-3 py-2 text-sm border rounded-lg border-slate-200 bg-slate-50 text-slate-600 min-h-[42px]"></div>
            <div id="tokenError" class="hidden px-3 py-2 mt-2 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
        </div>

        <div class="mt-4">
            <p class="mb-2 text-xs font-bold uppercase text-slate-400">Diễn giải ngôn ngữ tự nhiên</p>
            <div id="ruleNaturalPreview" class="px-3 py-2 text-sm border rounded-lg border-cyan-200 bg-cyan-50 text-slate-700 min-h-[52px]">
                Chưa có dữ liệu phiên dịch...
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 mt-4 md:grid-cols-2">
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tên quy chế</label>
                <input id="ruleNameInput" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"
                    placeholder="Ví dụ: Quy chế tham gia SV" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Loại quy chế (danh mục chuẩn)</label>
                <select id="ruleTypeInput" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300">
                    <option value="THAMGIA_SV">Tham gia sinh vien (THAMGIA_SV)</option>
                    <option value="THAMGIA_GV">Tham gia giang vien (THAMGIA_GV)</option>
                    <option value="VONGTHI">Duyet vong thi (VONGTHI)</option>
                    <option value="SANPHAM">Xu ly san pham (SANPHAM)</option>
                    <option value="GIAITHUONG">Xet giai thuong (GIAITHUONG)</option>
                    <option value="TUY_CHINH">Tuy chinh (TUY_CHINH)</option>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <label class="block mb-1 text-xs font-semibold text-slate-700">Ngữ cảnh áp dụng (bắt buộc, cho phép nhiều)</label>
            <select id="ruleContextInput" multiple size="6" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300">
            </select>
            <div id="ruleContextChips" class="flex flex-wrap gap-2 mt-2"></div>
            <p class="mt-1 mb-0 text-xs text-slate-500">Chỉ cho phép chọn từ danh mục ngữ cảnh chuẩn của hệ thống để đảm bảo mapping chính xác.</p>
        </div>
        <input id="rules_json" type="hidden" value="" />
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <button id="btnSaveRuleConfig" type="button" disabled
                class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">
                Lưu quy chế
            </button>
            <span id="astStatusText" class="text-xs text-slate-500">Chưa có cây logic hợp lệ.</span>
        </div>
    </div>

    <!-- Danh sách quy chế -->
    <div class="p-4 border rounded-xl border-slate-200 bg-white">
        <p class="mb-1 text-xs font-bold uppercase text-slate-400">Danh sách quy chế của sự kiện</p>
        <div id="ruleListContainer" class="mt-2 space-y-2 text-sm text-slate-600">
            <div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50">Đang tải danh sách quy chế...</div>
        </div>
    </div>
</div>
