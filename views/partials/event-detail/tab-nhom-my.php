<?php
/**
 * Partial: Tab "Nhóm của tôi"
 * Biến từ event-detail.php: $idSk, $basePath
 */
?>

<!-- Loading / Error states -->
<div id="myGroupLoading" class="p-4 border rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-500">
    <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
    Đang tải thông tin nhóm...
</div>
<div id="myGroupError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

<!-- Trạng thái chưa có nhóm -->
<div id="noGroupState" class="hidden">
    <div class="p-10 border border-dashed border-slate-300 rounded-xl bg-slate-50 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 mb-4 rounded-full bg-white border border-slate-200">
            <span class="material-symbols-outlined text-2xl text-slate-400">group_add</span>
        </div>
        <p class="text-slate-700 font-semibold mb-1">Bạn chưa tham gia nhóm nào</p>
        <p class="text-slate-400 text-sm mb-5">Hãy tạo nhóm mới hoặc xin vào nhóm có sẵn trong sự kiện này</p>
        <div class="flex gap-3 justify-center">
            <button id="btnTaoNhomCuaToi"
                class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[15px]">add</span> Tạo nhóm
            </button>
            <a href="/event-detail?id_sk=<?= $idSk ?>&tab=nhom-all"
               class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-slate-600 uppercase bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                <span class="material-symbols-outlined text-[15px]">search</span> Tìm nhóm
            </a>
        </div>
    </div>
</div>

<!-- Danh sách nhóm (cards) -->
<div id="myGroupContent" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>


<!-- MODAL: Tạo nhóm mới -->
<div id="modalTaoNhom" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <p class="text-sm font-bold text-slate-700">Tạo nhóm mới</p>
            <button id="btnCloseModalTaoNhom" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên nhóm <span class="text-rose-500">*</span></label>
                <input type="text" id="inputTenNhom" placeholder="Nhập tên nhóm..."
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                <textarea id="inputMoTa" rows="3" placeholder="Mô tả chủ đề nghiên cứu..."
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Số thành viên tối đa</label>
                <input type="number" id="inputSoLuong" value="5" min="2" max="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Chế độ nhóm</label>
                <select id="inputDangTuyen"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
                    <option value="1">🌐 Công khai — hiển thị trong danh sách</option>
                    <option value="0">🔒 Riêng tư — chỉ thành viên biết</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button id="btnHuyTaoNhom"
                    class="flex-1 px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                    Huỷ
                </button>
                <button id="btnSubmitTaoNhom"
                    class="flex-1 px-4 py-2 text-sm font-bold text-white bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90 transition-opacity">
                    <span class="material-symbols-outlined text-[15px] align-middle mr-1">add</span> Tạo nhóm
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: Quản lý nhóm -->
<div id="modalQuanLy" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg mx-4 flex flex-col" style="max-height:85vh">
        <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-slate-100">
            <p id="qlModalTitle" class="text-sm font-bold text-slate-700">Quản lý nhóm</p>
            <button id="btnCloseQuanLy" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div id="qlTabBar" class="flex border-b border-slate-100 px-4"></div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="qlModalLoading" class="text-sm text-slate-500 py-4 text-center">
                <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>Đang tải...
            </div>
            <div id="qlModalContent" class="hidden"></div>
        </div>
    </div>
</div>


<!-- MODAL: Mời thành viên -->
<div id="modalMoiTV" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-bold text-slate-700">Mời thành viên</p>
            <button id="btnCloseMoiTV" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="relative mb-3">
            <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
            <input type="text" id="searchSVInput" placeholder="Tìm sinh viên (tên hoặc MSSV)..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
        </div>
        <div id="svSearchResults" class="max-h-60 overflow-y-auto space-y-1"></div>
    </div>
</div>


<!-- MODAL: Mời GVHD -->
<div id="modalMoiGVHD" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-bold text-slate-700">Mời Giảng viên hướng dẫn</p>
            <button id="btnCloseMoiGVHD" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="relative mb-3">
            <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
            <input type="text" id="searchGVInput" placeholder="Tìm giảng viên theo tên..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
        </div>
        <div id="gvSearchResults" class="max-h-60 overflow-y-auto space-y-1"></div>
    </div>
</div>


<!-- MODAL: Nộp bài -->
<div id="modalNopBai" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-xl mx-4 flex flex-col" style="max-height:90vh">
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
            <p class="text-sm font-bold text-slate-700">Nộp bài / Sản phẩm nghiên cứu</p>
            <button id="btnCloseNopBai" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên đề tài <span class="text-rose-500">*</span></label>
                <input type="text" id="inputTenDeTai" placeholder="Nhập tên đề tài nghiên cứu..."
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Chủ đề nghiên cứu</label>
                <select id="selectChuDe"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
                    <option value="">-- Chọn chủ đề (tuỳ chọn) --</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                <textarea id="inputMoTaNopBai" rows="3" placeholder="Mô tả sản phẩm / nghiên cứu..."
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-2">
                    <i class="fas fa-paperclip mr-1"></i> Tệp bài nộp
                </label>
                <div id="nopBaiDropZone"
                     class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition-colors">
                    <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 block mb-2"></i>
                    <p class="text-sm font-semibold text-slate-600 mb-1">
                        Kéo thả file vào đây hoặc
                        <span class="text-purple-600 underline cursor-pointer" id="nopBaiSelectFile">chọn file</span>
                    </p>
                    <p class="text-xs text-slate-400">PDF, DOC, DOCX (báo cáo) · ZIP, RAR (source code)</p>
                    <p class="text-xs text-slate-400 mt-0.5">Tối đa 20MB/file</p>
                    <input type="file" id="inputFiles" multiple accept=".pdf,.doc,.docx,.zip,.rar" class="hidden">
                </div>
                <div id="fileList" class="mt-2 space-y-1.5"></div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Link tài liệu / GitHub (tuỳ chọn)</label>
                <input type="url" id="inputLinkTL" placeholder="https://..."
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-5 pt-3 border-t border-slate-100">
            <button id="btnHuyNopBai"
                class="flex-1 px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                Huỷ
            </button>
            <button id="btnSubmitNopBai"
                class="flex-1 px-4 py-2 text-sm font-bold text-white bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90 transition-opacity">
                <i class="fas fa-paper-plane mr-1"></i> Nộp bài
            </button>
        </div>
    </div>
</div>

<script>
window.NHOM_THI_ID_SK  = <?= (int) $idSk ?>;
window.NHOM_THI_TAB    = 'cua-toi';
window.QUAN_LY_NHOM_ID = 0;
window.QUAN_LY_TAB     = 'thanh-vien';
</script>