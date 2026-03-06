<?php
/**
 * Nhóm của tôi
 * URL: /nhom/mygroup?id_sk=XX
 * URL quản lý: /nhom/mygroup?id_sk=XX&id_nhom=YY&quan_ly_tab=thanh-vien|yeu-cau|cai-dat
 */

$pageTitle   = 'Nhóm của tôi - ezManagement';
$currentPage = 'events';
$pageJs      = 'nhom-thi.js';

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

// Quản lý nhóm cụ thể (URL-based tabs như event-detail)
$idNhom        = isset($_GET['id_nhom']) ? (int) $_GET['id_nhom'] : 0;
$quanLyTab     = 'thanh-vien';
$allowedQLTabs = ['thanh-vien', 'yeu-cau', 'cai-dat'];
if ($idNhom > 0 && isset($_GET['quan_ly_tab']) && in_array($_GET['quan_ly_tab'], $allowedQLTabs, true)) {
    $quanLyTab = $_GET['quan_ly_tab'];
}

$qlTabTitles = [
    'thanh-vien' => 'Thành viên',
    'yeu-cau'    => 'Yêu cầu tham gia',
    'cai-dat'    => 'Cài đặt nhóm',
];

$pageHeading = $idNhom > 0 ? ($qlTabTitles[$quanLyTab] ?? 'Quản lý nhóm') : 'Nhóm của tôi';
$breadcrumbs = [
    ['title' => 'Dashboard',        'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện', 'url' => '/events'],
    ['title' => 'Nhóm Thi'],
];

$useEventSidebar     = true;
$eventSidebarEventId = $idSk;
$eventSidebarSection = 'nhom-my';

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <?php if ($idNhom > 0): ?>
    <!-- QUẢN LÝ NHÓM (URL-based tabs) -->
    <div class="w-full max-w-full mb-4">
        <a href="/nhom/mygroup?id_sk=<?= $idSk ?>"
           class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg shadow-soft-sm text-slate-700 border-slate-200">
            <i class="mr-2 fas fa-arrow-left"></i> Quay lại nhóm của tôi
        </a>
    </div>

    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
        <div class="p-6 pb-0">
            <h6 class="mb-0" id="qlNhomTitle">Đang tải...</h6>
        </div>

        <!-- Sub-tab bar -->
        <div class="flex gap-0 px-6 pt-3 border-b border-slate-100">
            <?php foreach ($qlTabTitles as $slug => $label): ?>
            <a href="?id_sk=<?= $idSk ?>&id_nhom=<?= $idNhom ?>&quan_ly_tab=<?= $slug ?>"
               class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                      <?= $quanLyTab === $slug
                            ? 'border-purple-600 text-purple-700 font-semibold'
                            : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                <?= $label ?>
                <?php if ($slug === 'yeu-cau'): ?>
                <span id="yeuCauBadge" class="hidden ml-1 text-xs bg-rose-500 text-white px-1.5 py-0.5 rounded-full font-bold">0</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="flex-auto p-6">
            <div id="qlLoading" class="text-sm text-slate-500">
                <i class="fas fa-circle-notch fa-spin mr-2"></i>Đang tải...
            </div>
            <div id="qlError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
            <div id="qlContent" class="hidden"></div>
        </div>
    </div>

    <?php else: ?>
    <!-- DANH SÁCH NHÓM CỦA TÔI -->
    <div id="myGroupLoading" class="px-4 py-5 text-sm text-slate-500">
        <i class="fas fa-circle-notch fa-spin mr-2"></i>Đang tải thông tin nhóm...
    </div>
    <div id="myGroupError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

    <div id="noGroupState" class="hidden">
        <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
            <div class="flex-auto p-6 text-center py-14">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-100">
                    <i class="fas fa-user-plus text-2xl text-slate-400"></i>
                </div>
                <p class="text-slate-600 font-semibold mb-1">Bạn chưa tham gia nhóm nào</p>
                <p class="text-slate-400 text-sm mb-5">Hãy tạo nhóm mới hoặc xin vào nhóm có sẵn</p>
                <div class="flex gap-3 justify-center">
                    <button id="btnTaoNhomCuaToi"
                        class="inline-flex items-center px-5 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg">
                        <i class="fas fa-plus mr-2"></i> Tạo nhóm
                    </button>
                    <a href="/nhom/allgroup?id_sk=<?= $idSk ?>"
                       class="inline-flex items-center px-5 py-2 text-xs font-bold text-slate-600 uppercase bg-slate-100 rounded-lg hover:bg-slate-200">
                        <i class="fas fa-search mr-2"></i> Tìm nhóm
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="myGroupContent" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
    <?php endif; ?>
</div>

<!-- Modal: Tạo nhóm -->
<div id="modalTaoNhom" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-soft-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h6 class="font-bold text-slate-700 mb-0">Tạo nhóm mới</h6>
            <button id="btnCloseModalTaoNhom" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên nhóm <span class="text-rose-500">*</span></label>
                <input type="text" id="inputTenNhom" placeholder="Nhập tên nhóm..."
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                <textarea id="inputMoTa" rows="3" placeholder="Mô tả chủ đề..."
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Số thành viên tối đa</label>
                <input type="number" id="inputSoLuong" value="5" min="2" max="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
            </div>
            <div class="flex gap-3 pt-1">
                <button id="btnHuyTaoNhom" class="flex-1 px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">Huỷ</button>
                <button id="btnSubmitTaoNhom" class="flex-1 px-4 py-2 text-sm font-bold text-white bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90">
                    <i class="fas fa-plus mr-1"></i> Tạo nhóm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Mời sinh viên -->
<div id="modalMoiTV" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-soft-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h6 class="font-bold text-slate-700 mb-0">Mời thành viên</h6>
            <button id="btnCloseMoiTV" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="relative mb-3">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
            <input type="text" id="searchSVInput" placeholder="Tìm sinh viên (tên hoặc MSSV)..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
        </div>
        <div id="svSearchResults" class="max-h-56 overflow-y-auto space-y-1"></div>
    </div>
</div>

<!-- Modal: Mời GVHD -->
<div id="modalMoiGVHD" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-soft-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h6 class="font-bold text-slate-700 mb-0">Mời Giảng viên hướng dẫn</h6>
            <button id="btnCloseMoiGVHD" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="relative mb-3">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
            <input type="text" id="searchGVInput" placeholder="Tìm giảng viên theo tên..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
        </div>
        <div id="gvSearchResults" class="max-h-56 overflow-y-auto space-y-1"></div>
    </div>
</div>

<!-- Modal: Nộp bài -->
<div id="modalNopBai" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-soft-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h6 class="font-bold text-slate-700 mb-0">Nộp bài / Sản phẩm nghiên cứu</h6>
            <button id="btnCloseNopBai" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên đề tài <span class="text-rose-500">*</span></label>
                <input type="text" id="inputTenDeTai" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                <textarea id="inputMoTaNopBai" rows="3" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Link tài liệu / GitHub</label>
                <input type="url" id="inputLinkTL" placeholder="https://..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
            </div>
            <div class="flex gap-3 pt-1">
                <button id="btnHuyNopBai" class="flex-1 px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200">Huỷ</button>
                <button id="btnSubmitNopBai" class="flex-1 px-4 py-2 text-sm font-bold text-white bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg hover:opacity-90">
                    <i class="fas fa-paper-plane mr-1"></i> Nộp bài
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.NHOM_THI_ID_SK  = <?= (int) $idSk ?>;
    window.NHOM_THI_TAB    = 'cua-toi';
    window.QUAN_LY_NHOM_ID = <?= (int) $idNhom ?>;
    window.QUAN_LY_TAB     = <?= json_encode($quanLyTab) ?>;
</script>

<?php
$content = ob_get_clean();
include '../../layouts/main_layout.php';