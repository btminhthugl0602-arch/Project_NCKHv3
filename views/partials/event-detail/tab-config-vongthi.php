<?php

/**
 * Partial: Tab Cấu hình nhóm thi
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (doDuLieuVaoBasicForm + btnSaveNhomConfig)
 */
?>

<div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
            <p class="mb-0 text-xs font-bold uppercase text-slate-400">Cấu hình nhóm thi</p>
            <p class="mb-0 text-sm text-slate-500">BTC chỉ cấu hình số lượng thành viên; tùy chọn GVHD chỉ hiện khi sự kiện bật luồng GVHD.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div class="space-y-3">
            <p class="text-xs font-semibold text-slate-600">Số thành viên (sinh viên)</p>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tối thiểu</label>
                <input id="basicSoTVToiThieu" type="number" min="1"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tối đa</label>
                <input id="basicSoTVToiDa" type="number" min="1"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Số đội tối đa mỗi sinh viên</label>
                <input id="basicSoNhomToiDaSV" type="number" min="1"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                <p class="mt-1 mb-0 text-xs text-slate-500">Ví dụ: 1 = mỗi sinh viên chỉ được tham gia 1 đội trong sự kiện.</p>
            </div>
        </div>

        <div id="basicGvhdOptions" class="space-y-3 hidden md:col-span-2 lg:col-span-2">
            <p class="text-xs font-semibold text-slate-600">Tùy chọn khi sự kiện có GVHD</p>
            <div class="flex items-center gap-2">
                <input id="basicYeuCauCoGVHD" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                <label for="basicYeuCauCoGVHD" class="text-sm text-slate-700">Bắt buộc có GVHD mới được nộp bài</label>
            </div>
            <div class="flex items-center gap-2">
                <input id="basicChoPhepGVTaoNhom" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                <label for="basicChoPhepGVTaoNhom" class="text-sm text-slate-700">Cho phép giảng viên tạo nhóm</label>
            </div>
            <div class="px-3 py-2 text-xs border rounded-lg border-amber-200 bg-amber-50 text-amber-700">
                Các thông số số lượng GVHD không còn cấu hình tại đây. Chế độ có/không GVHD được quyết định ngay từ lúc tạo sự kiện.
            </div>
        </div>
    </div>

    <div class="flex pt-3 mt-3 border-t border-slate-200">
        <button id="btnSaveNhomConfig" type="button"
            class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">
            Lưu cấu hình nhóm
        </button>
    </div>
</div>
