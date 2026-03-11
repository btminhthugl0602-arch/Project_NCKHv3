<?php
/**
 * Partial: Tab Cấu hình tài liệu (Dynamic Form)
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (khoiTaoTabConfigTaiLieu, ...)
 */
?>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    <!-- Cột trái: Danh sách vòng thi + chọn vòng để cấu hình -->
    <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
        <p class="mb-1 text-xs font-bold uppercase text-slate-400">Chọn vòng thi để cấu hình</p>
        <p class="mb-3 text-sm text-slate-500">Mỗi vòng thi có form riêng. Vòng không có form → nhóm không cần nộp tài liệu.</p>
        <div id="tlVongThiList" class="space-y-1.5">
            <div class="px-3 py-2 text-sm text-slate-400 border rounded-lg border-slate-200 bg-white">Đang tải...</div>
        </div>

        <!-- Copy form -->
        <div class="mt-4 pt-4 border-t border-slate-200">
            <p class="mb-2 text-xs font-bold uppercase text-slate-400">Copy form từ vòng khác</p>
            <div class="space-y-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-600">Nguồn</label>
                    <select id="tlCopySrc" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none bg-white"></select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-600">Đích</label>
                    <select id="tlCopyDst" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none bg-white"></select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-600">Kiểu copy</label>
                    <select id="tlCopyMode" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none bg-white">
                        <option value="them_vao">Thêm vào (giữ nguyên đích)</option>
                        <option value="ghi_de">Ghi đè (xóa field cũ chưa có data)</option>
                    </select>
                </div>
                <button id="btnTlCopyForm" type="button"
                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg text-slate-700 border-slate-300 hover:bg-slate-50">
                    <i class="fas fa-copy"></i> Copy form
                </button>
            </div>
        </div>
    </div>

    <!-- Cột phải: Editor field cho vòng đang chọn -->
    <div class="lg:col-span-2 space-y-4">

        <!-- Header vòng đang chọn -->
        <div class="p-4 border rounded-xl border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="mb-0 text-xs font-bold uppercase text-slate-400">Đang cấu hình</p>
                    <p id="tlCurrentRoundName" class="mb-0 text-base font-semibold text-slate-700">— Chọn vòng thi bên trái —</p>
                </div>
                <button id="btnTlAddField" type="button" disabled
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-plus"></i> Thêm trường
                </button>
            </div>
        </div>

        <!-- Danh sách fields -->
        <div id="tlFieldList" class="space-y-2">
            <div class="p-4 text-sm text-center text-slate-400 border rounded-xl border-slate-200 bg-white">
                Chọn vòng thi để xem và chỉnh sửa form.
            </div>
        </div>

    </div>
</div>

<!-- Modal thêm/sửa field -->
<div id="tlFieldModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 id="tlModalTitle" class="text-sm font-bold text-slate-700">Thêm trường mới</h3>
            <button id="btnTlModalClose" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="px-6 py-4 space-y-4">
            <input type="hidden" id="tlFieldEditId" value="" />

            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tên trường <span class="text-red-500">*</span></label>
                <input id="tlFieldTenTruong" type="text" placeholder="VD: Link Github, File báo cáo PDF..."
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Kiểu trường <span class="text-red-500">*</span></label>
                    <select id="tlFieldKieuTruong"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none">
                        <option value="TEXT">TEXT — Văn bản ngắn</option>
                        <option value="TEXTAREA">TEXTAREA — Văn bản dài</option>
                        <option value="URL">URL — Đường dẫn</option>
                        <option value="FILE">FILE — Upload file</option>
                        <option value="SELECT">SELECT — Chọn 1 trong danh sách</option>
                        <option value="CHECKBOX">CHECKBOX — Xác nhận</option>
                    </select>
                </div>
                <div class="flex flex-col justify-end">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input id="tlFieldBatBuoc" type="checkbox" checked
                            class="w-4 h-4 rounded border-slate-300 text-fuchsia-500" />
                        <span class="text-sm font-semibold text-slate-700">Bắt buộc</span>
                    </label>
                </div>
            </div>

            <!-- Cấu hình động theo kieuTruong -->
            <div id="tlFieldCauHinhWrap" class="space-y-3 pt-2 border-t border-slate-100">
                <!-- Được render bằng JS -->
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-200 bg-slate-50">
            <button id="btnTlModalCancel" type="button"
                class="px-4 py-2 text-xs font-bold uppercase rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100">
                Hủy
            </button>
            <button id="btnTlModalSave" type="button"
                class="px-4 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md">
                Lưu trường
            </button>
        </div>
    </div>
</div>

