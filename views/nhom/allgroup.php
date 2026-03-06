<?php
/**
 * Tất cả nhóm thi trong sự kiện
 * URL: /nhom/allgroup?id_sk=XX
 */

$pageTitle   = 'Tất Cả Nhóm Thi - ezManagement';
$currentPage = 'events';
$pageJs      = 'nhom-thi.js';

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;

$pageHeading = 'Tất Cả Nhóm Thi';
$breadcrumbs = [
    ['title' => 'Dashboard',        'url' => '/dashboard'],
    ['title' => 'Quản Lý Sự Kiện', 'url' => '/events'],
    ['title' => 'Nhóm Thi'],
];

$useEventSidebar     = true;
$eventSidebarEventId = $idSk;
$eventSidebarSection = 'nhom-all';

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-6 pb-0 rounded-t-2xl">
            <div>
                <h6 class="mb-1">Danh sách nhóm thi</h6>
                <p class="mb-0 text-sm leading-normal text-slate-500" id="groupCountText">Đang tải...</p>
            </div>
            <button id="btnTaoNhom" type="button"
                class="inline-flex items-center px-5 py-2.5 text-xs font-bold text-center text-white uppercase transition-all border-0 rounded-xl cursor-pointer bg-gradient-to-tl from-purple-700 to-pink-500 hover:scale-102 active:opacity-85 shadow-soft-md">
                <i class="mr-2 fas fa-plus"></i> Tạo nhóm mới
            </button>
        </div>

        <!-- ── FORM TẠO NHÓM INLINE (ẩn mặc định) ── -->
        <div id="formTaoNhomWrapper" class="hidden mx-6 mt-4 mb-0 p-5 border border-purple-200 bg-purple-50 rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <h6 class="font-bold text-slate-700 mb-0 text-sm">
                    <i class="fas fa-plus-circle text-purple-600 mr-2"></i>Tạo nhóm mới
                </h6>
                <button id="btnDongFormTaoNhom" class="text-slate-400 hover:text-slate-600 text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                        Tên nhóm <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="inputTenNhom" placeholder="Nhập tên nhóm..."
                           class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">
                        Số thành viên tối đa
                        <span class="normal-case font-normal text-slate-400">(Ban tổ chức cấu hình mặc định)</span>
                    </label>
                    <input type="number" id="inputSoLuong" value="5" min="2" max="20"
                           class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-purple-400"
                           id="inputSoLuong">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Chế độ nhóm</label>
                    <select id="inputDangTuyen"
                            class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-purple-400">
                        <option value="1">🌐 Công khai — hiển thị trong danh sách nhóm</option>
                        <option value="0">🔒 Riêng tư — chỉ thành viên biết</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả / Chủ đề nghiên cứu</label>
                    <textarea id="inputMoTa" rows="2" placeholder="Mô tả ngắn về nhóm..."
                              class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-purple-400 resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button id="btnSubmitTaoNhom"
                    class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition shadow-soft-md">
                    <i class="fas fa-check"></i> Tạo nhóm
                </button>
                <button id="btnHuyTaoNhom"
                    class="px-5 py-2 text-xs font-semibold text-slate-600 uppercase bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                    Huỷ
                </button>
            </div>
        </div>

        <div class="flex-auto p-6">
            <!-- Tìm kiếm -->
            <div class="relative mb-5 max-w-sm">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                <input type="text" id="searchInput" placeholder="Tìm theo tên, mã nhóm..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
            </div>

            <!-- States -->
            <div id="groupsLoading" class="py-5 text-sm text-slate-500">
                <i class="fas fa-circle-notch fa-spin mr-2"></i>Đang tải danh sách nhóm...
            </div>
            <div id="groupsError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
            <div id="groupsEmpty" class="hidden py-10 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 mb-3 rounded-full bg-slate-100">
                    <i class="fas fa-layer-group text-xl text-slate-400"></i>
                </div>
                <p class="text-slate-600 font-semibold mb-1">Chưa có nhóm nào</p>
                <p class="text-slate-400 text-sm mb-4">Hãy là người đầu tiên tạo nhóm trong sự kiện này</p>
                <button id="btnTaoNhomEmpty"
                    class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500">
                    <i class="fas fa-plus"></i> Tạo nhóm đầu tiên
                </button>
            </div>
            <div id="groupsGrid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
        </div>
    </div>
</div>

<script>
    window.NHOM_THI_ID_SK = <?= (int) $idSk ?>;
    window.NHOM_THI_TAB   = 'tat-ca';
</script>

<?php
$content = ob_get_clean();
include '../../layouts/main_layout.php';