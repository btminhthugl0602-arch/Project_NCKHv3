<?php

/**
 * Partial: Tab Cấu hình cơ bản
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (khoiTaoTabConfigBasic, loadRoundList, handleEditRound, ...)
 */
?>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <!-- Chỉnh sửa thông tin sự kiện -->
    <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
        <p class="mb-3 text-xs font-bold uppercase text-slate-400">Chỉnh sửa thông tin sự kiện</p>
        <div class="space-y-3 text-sm text-slate-600">
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Tên sự kiện</label>
                <input id="basicTenSuKien" type="text"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>
                <textarea id="basicMoTa" rows="3"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700 resize-none
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors"></textarea>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Cấp tổ chức</label>
                    <select id="basicIdCap"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 bg-white text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors"></select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Trạng thái</label>
                    <div id="basicTrangThaiText" class="px-3 py-2 text-sm border rounded-lg border-slate-200 bg-white text-slate-700">
                        --</div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Mở đăng ký</label>
                    <input id="basicNgayMoDK" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Đóng đăng ký</label>
                    <input id="basicNgayDongDK" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>
                    <input id="basicNgayBatDau" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>
                    <input id="basicNgayKetThuc" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-200 text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                <button id="btnSaveBasicConfig" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase rounded-lg
                           bg-primary hover:bg-primary-dark transition-colors
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                    Lưu thông tin
                </button>
                <button id="btnToggleEventStatus" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold uppercase rounded-lg
                           bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                    Mở/Đóng sự kiện
                </button>
            </div>
        </div>
    </div>

    <!-- Cấu hình vòng thi -->
    <div class="p-4 border rounded-xl border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <p class="mb-0 text-xs font-bold uppercase text-slate-400">Cấu hình vòng thi</p>
                <p class="mb-0 text-sm text-slate-500">Quản lý các vòng thi theo thứ tự triển khai của sự kiện.</p>
            </div>
            <button id="btnCreateRound" type="button"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase rounded-lg shrink-0
                       bg-primary hover:bg-primary-dark transition-colors
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">add</span>
                Thêm vòng thi
            </button>
        </div>
        <div id="basicRoundList" class="space-y-2 text-sm text-slate-600">
            <div class="px-3 py-2 border rounded-lg border-slate-200 bg-white">Đang tải danh sách vòng thi...</div>
        </div>
    </div>
</div>