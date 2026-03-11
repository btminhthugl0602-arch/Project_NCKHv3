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
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>
                <textarea id="basicMoTa"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none"
                    rows="3"></textarea>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Cấp tổ chức</label>
                    <select id="basicIdCap"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none"></select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Trạng thái</label>
                    <div id="basicTrangThaiText" class="px-3 py-2 text-sm border rounded-lg border-slate-200 bg-white">
                        --</div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Mở đăng ký</label>
                    <input id="basicNgayMoDK" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Đóng đăng ký</label>
                    <input id="basicNgayDongDK" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>
                    <input id="basicNgayBatDau" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>
                    <input id="basicNgayKetThuc" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                <button id="btnSaveBasicConfig" type="button"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">
                    Lưu thông tin
                </button>
                <button id="btnToggleEventStatus" type="button"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg text-slate-700 border-slate-300">
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
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md shrink-0 hover:scale-102 active:opacity-85">
                <i class="fas fa-plus"></i>
                Thêm vòng thi
            </button>
        </div>
        <div id="basicRoundList" class="space-y-2 text-sm text-slate-600">
            <div class="px-3 py-2 border rounded-lg border-slate-200 bg-white">Đang tải danh sách vòng thi...</div>
        </div>
    </div>
</div>

<!-- Cấu hình nhóm thi — full width -->
<div class="p-4 mt-4 border rounded-xl border-slate-200 bg-slate-50">
    <p class="mb-3 text-xs font-bold uppercase text-slate-400">Cấu hình nhóm thi</p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

        <!-- Số thành viên -->
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
        </div>

        <!-- Giảng viên hướng dẫn -->
        <div class="space-y-3">
            <p class="text-xs font-semibold text-slate-600">Giảng viên hướng dẫn (GVHD)</p>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Số GVHD tối đa / nhóm</label>
                <div class="flex items-center gap-2 mb-1">
                    <input id="basicSoGVHDKhongGioiHan" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                    <label for="basicSoGVHDKhongGioiHan" class="text-xs text-slate-600">Không giới hạn</label>
                </div>
                <input id="basicSoGVHDToiDa" type="number" min="1"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>
            <div>
                <label class="block mb-1 text-xs font-semibold text-slate-700">Số nhóm tối đa 1 GVHD hướng dẫn</label>
                <div class="flex items-center gap-2 mb-1">
                    <input id="basicSoNhomGVHDKhongGioiHan" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                    <label for="basicSoNhomGVHDKhongGioiHan" class="text-xs text-slate-600">Không giới hạn</label>
                </div>
                <input id="basicSoNhomToiDaGVHD" type="number" min="1"
                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
            </div>
        </div>

        <!-- Tùy chọn -->
        <div class="space-y-3">
            <p class="text-xs font-semibold text-slate-600">Tùy chọn khác</p>
            <div class="flex items-center gap-2">
                <input id="basicYeuCauCoGVHD" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                <label for="basicYeuCauCoGVHD" class="text-sm text-slate-700">Bắt buộc có GVHD mới được nộp bài</label>
            </div>
            <div class="flex items-center gap-2">
                <input id="basicChoPhepGVTaoNhom" type="checkbox" class="w-4 h-4 accent-fuchsia-600" />
                <label for="basicChoPhepGVTaoNhom" class="text-sm text-slate-700">Cho phép giảng viên tạo nhóm</label>
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