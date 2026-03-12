<?php

/**
 * Partial: Tab "Tất cả nhóm thi"
 * Biến từ event-detail.php: $idSk, $tab, $basePath
 */
?>

<!-- Header + nút tạo -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div>
        <p class="text-xs font-bold uppercase text-slate-400 mb-0.5">Danh sách nhóm thi</p>
        <p class="text-sm text-slate-500" id="groupCountText">Đang tải...</p>
    </div>
    <button id="btnTaoNhom" type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90 transition-opacity">
        <span class="material-symbols-outlined text-[15px]">add</span> Tạo nhóm mới
    </button>
</div>

<!-- Form tạo nhóm inline (ẩn mặc định) -->
<div id="formTaoNhomWrapper" class="hidden mb-5 p-5 border border-primary/30 bg-primary/5 rounded-xl">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm font-bold text-slate-700">
            <span class="material-symbols-outlined text-[16px] align-middle text-primary mr-1">add_circle</span>
            Tạo nhóm mới
        </p>
        <button id="btnDongFormTaoNhom" class="text-slate-400 hover:text-slate-600">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                Tên nhóm <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="inputTenNhom" placeholder="Nhập tên nhóm..."
                class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Chế độ nhóm</label>
            <select id="inputDangTuyen"
                class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
                <option value="1">🌐 Công khai — hiển thị trong danh sách</option>
                <option value="0">🔒 Riêng tư — chỉ thành viên biết</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả / Chủ đề nghiên cứu</label>
            <textarea id="inputMoTa" rows="2" placeholder="Mô tả ngắn về nhóm..."
                class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-primary resize-none"></textarea>
        </div>
    </div>
    <div class="flex gap-3 mt-4">
        <button id="btnSubmitTaoNhom"
            class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[15px]">check</span> Tạo nhóm
        </button>
        <button id="btnHuyTaoNhom"
            class="px-5 py-2 text-xs font-semibold text-slate-600 uppercase bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
            Huỷ
        </button>
    </div>
</div>

<!-- Tìm kiếm -->
<div class="relative mb-5 max-w-sm">
    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
    <input type="text" id="searchInput" placeholder="Tìm theo tên, mã nhóm..."
        class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
</div>

<!-- States -->
<div id="groupsLoading" class="py-5 text-sm text-slate-500">
    <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
    Đang tải danh sách nhóm...
</div>
<div id="groupsError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

<div id="groupsEmpty" class="hidden p-10 border border-dashed border-slate-300 rounded-xl bg-slate-50 text-center">
    <div class="inline-flex items-center justify-center w-14 h-14 mb-3 rounded-full bg-white border border-slate-200">
        <span class="material-symbols-outlined text-2xl text-slate-400">group</span>
    </div>
    <p class="text-slate-700 font-semibold mb-1">Chưa có nhóm nào</p>
    <p class="text-slate-400 text-sm mb-4">Hãy là người đầu tiên tạo nhóm trong sự kiện này</p>
    <button id="btnTaoNhomEmpty"
        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition-opacity">
        <span class="material-symbols-outlined text-[15px]">add</span> Tạo nhóm đầu tiên
    </button>
</div>

<div id="groupsGrid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>

<script>
    window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
    window.NHOM_THI_TAB = 'tat-ca';
</script>