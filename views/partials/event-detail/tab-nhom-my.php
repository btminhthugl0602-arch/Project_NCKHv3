<?php

/**
 * Partial: Tab "Nhóm của tôi"
 * Biến từ event-detail.php: $idSk, $tab, $basePath
 */

$idNhom        = isset($_GET['id_nhom']) ? (int) $_GET['id_nhom'] : 0;
$quanLyTab     = 'thanh-vien';
$allowedQLTabs = ['thanh-vien', 'yeu-cau', 'nop-tai-lieu', 'cai-dat'];
if ($idNhom > 0 && isset($_GET['quan_ly_tab']) && in_array($_GET['quan_ly_tab'], $allowedQLTabs, true)) {
    $quanLyTab = $_GET['quan_ly_tab'];
}
$qlTabTitles = [
    'thanh-vien'   => 'Thành viên',
    'yeu-cau'      => 'Yêu cầu tham gia',
    'nop-tai-lieu' => 'Nộp tài liệu',
    'cai-dat'      => 'Cài đặt nhóm',
];
?>

<?php if ($idNhom > 0): ?>
    <!-- ── QUẢN LÝ NHÓM CỤ THỂ ── -->
    <div class="mb-4">
        <a href="/event-detail?id_sk=<?= $idSk ?>&tab=nhom-my"
            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold uppercase text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
            <span class="material-symbols-outlined text-[15px]">arrow_back</span>
            Quay lại nhóm của tôi
        </a>
    </div>

    <div class="border border-slate-200 rounded-xl overflow-hidden">
        <!-- Sub-tab bar -->
        <div class="flex border-b border-slate-200 bg-white px-4">
            <?php foreach ($qlTabTitles as $slug => $label): ?>
                <a href="?id_sk=<?= $idSk ?>&tab=nhom-my&id_nhom=<?= $idNhom ?>&quan_ly_tab=<?= $slug ?>"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px
                  <?= $quanLyTab === $slug
                        ? 'border-primary text-primary font-semibold'
                        : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                    <?= htmlspecialchars($label) ?>
                    <?php if ($slug === 'yeu-cau'): ?>
                        <span id="yeuCauBadge" class="hidden ml-1 text-xs bg-rose-500 text-white px-1.5 py-0.5 rounded-full font-bold">0</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="p-6 bg-white">
            <div id="qlLoading" class="text-sm text-slate-500">
                <span class="material-symbols-outlined text-[16px] align-middle animate-spin mr-1">refresh</span>
                Đang tải...
            </div>
            <div id="qlError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
            <div id="qlContent" class="hidden"></div>
        </div>
    </div>

<?php else: ?>
    <!-- ── DANH SÁCH NHÓM CỦA TÔI ── -->

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

<?php endif; ?>

<!-- Modal: Tạo nhóm -->
<div id="modalTaoNhom" class="fixed inset-0 z-50 hidden bg-black/50">
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md p-6">
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
</div>

<!-- Modal: Mời sinh viên -->
<div id="modalMoiTV" class="fixed inset-0 z-50 hidden bg-black/50">
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-bold text-slate-700">Mời thành viên</p>
                <button id="btnCloseMoiTV" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <div class="relative mb-2">
                <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
                <input type="text" id="searchSVInput" placeholder="Tìm sinh viên (tên hoặc MSSV)..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
            <p class="text-xs text-slate-400 mb-3">Hiển thị 20 sinh viên đầu tiên — nhập tên hoặc MSSV để tìm kiếm</p>
            <div id="svSearchResults" class="max-h-64 overflow-y-auto space-y-1 border border-slate-100 rounded-lg"></div>
        </div>
    </div>
</div>

<!-- Modal: Mời GVHD -->
<div id="modalMoiGVHD" class="fixed inset-0 z-50 hidden bg-black/50">
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-bold text-slate-700">Mời Giảng viên hướng dẫn</p>
                <button id="btnCloseMoiGVHD" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <div class="relative mb-2">
                <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">search</span>
                <input type="text" id="searchGVInput" placeholder="Tìm giảng viên theo tên..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-primary">
            </div>
            <p class="text-xs text-slate-400 mb-3">Hiển thị 20 giảng viên đầu tiên — nhập tên để tìm kiếm</p>
            <div id="gvSearchResults" class="max-h-64 overflow-y-auto space-y-1 border border-slate-100 rounded-lg"></div>
        </div>
    </div>
</div>

<script>
    window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
    window.NHOM_THI_TAB = 'cua-toi';
    window.QUAN_LY_NHOM_ID = <?= (int) $idNhom ?>;
    window.QUAN_LY_TAB = <?= json_encode($quanLyTab) ?>;
</script>