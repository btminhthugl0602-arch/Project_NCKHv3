<?php

/**
 * Event Management Page
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$_isGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!isset($_SESSION['idTK']) && !$_isGuest) {
    header('Location: /sign-in');
    exit;
}

// Check quyền tạo sự kiện — dùng raw query thay vì require business file
// Tránh define() conflict với constants trong quan_ly_su_kien.php
// gây E_WARNING → output trước HTML → DOMContentLoaded không fire đúng
$_coQuyenTao = false;
if (!$_isGuest && isset($_SESSION['idTK']) && (int)$_SESSION['idTK'] > 0) {
    $_idTKCheck = (int)$_SESSION['idTK'];
    try {
        if (!defined('_AUTHEN')) define('_AUTHEN', true);
        require_once __DIR__ . '/../api/core/base.php';
        // Admin (idLoaiTK=1) luôn có quyền
        if ((int)($_SESSION['idLoaiTK'] ?? 0) === 1) {
            $_coQuyenTao = true;
        } else {
            // Kiểm tra bảng taikhoan_quyen
            $_stmtQ = $conn->prepare("
                SELECT 1
                FROM taikhoan_quyen tq
                JOIN quyen q ON q.idQuyen = tq.idQuyen
                WHERE tq.idTK = :idTK
                  AND q.maQuyen = 'tao_su_kien'
                  AND q.phamVi = 'HE_THONG'
                  AND tq.isActive = 1
                  AND (tq.thoiGianKetThuc IS NULL OR tq.thoiGianKetThuc > NOW())
                LIMIT 1
            ");
            $_stmtQ->execute([':idTK' => $_idTKCheck]);
            $_coQuyenTao = (bool) $_stmtQ->fetchColumn();
        }
    } catch (Throwable $_e) {
        // Không critical — nút tạo ẩn đi, list sự kiện vẫn load bình thường
        $_coQuyenTao = false;
    }
}

$pageTitle = "Quản lý sự kiện - ezManagement";
$currentPage = "events";
$pageHeading = "Quản lý sự kiện";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện'],
];
$pageCss = "events.css";
$pageJs = "events.js";

ob_start();
?>

<div class="w-full px-4 sm:px-6 py-4 sm:py-6 mx-auto max-w-screen-xl">

    <!-- Page header -->
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-bold text-slate-800 leading-tight">Quản lý sự kiện</h1>
            <p class="text-sm text-slate-500 mt-0.5">Danh sách và quản lý các sự kiện học thuật trong hệ thống.</p>
        </div>
        <?php if ($_coQuyenTao): ?>
            <button id="openCreateEventBtn" type="button" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white
                       bg-primary hover:bg-primary-dark rounded-lg transition-colors shrink-0
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">add</span>
                Tạo sự kiện
            </button>
        <?php endif; ?>
    </div>

    <?php if ($_coQuyenTao): ?>
    <!-- Step banner -->
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 mb-6">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Luồng triển khai đề xuất</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?php foreach ([
                ['1', 'Tạo sự kiện cơ bản',  'Thông tin cơ bản'],
                ['2', 'Cấu hình vòng thi',    'Thiết lập lộ trình'],
                ['3', 'Cấu hình quy chế',     'Điều khoản & điều lệ'],
            ] as [$n, $l, $sub]): ?>
                <div class="flex items-center gap-3 bg-white rounded-lg border border-slate-200 px-4 py-3">
                    <span class="inline-flex items-center justify-center size-7 rounded-full bg-primary-light text-primary text-xs font-bold shrink-0"><?php echo $n; ?></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-700 leading-tight"><?php echo $l; ?></p>
                        <p class="text-[11px] text-slate-400 leading-tight"><?php echo $sub; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div class="bg-white rounded-xl border border-slate-100 p-3 mb-4 flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative flex-1 min-w-[200px]">
            <span class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true">search</span>
            <input id="evSearch" type="search" placeholder="Tìm kiếm sự kiện..."
                aria-label="Tìm kiếm sự kiện"
                class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
        </div>
        <!-- Filter cấp tổ chức -->
        <select id="evFilterCap" aria-label="Lọc theo cấp tổ chức"
            class="py-2 pl-3 pr-8 text-sm border border-slate-200 rounded-lg text-slate-700 bg-white
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors">
            <option value="">Cấp tổ chức</option>
        </select>
        <!-- Filter thời gian -->
        <select id="evFilterThoiGian" aria-label="Lọc theo thời gian"
            class="py-2 pl-3 pr-8 text-sm border border-slate-200 rounded-lg text-slate-700 bg-white
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors">
            <option value="">Tất cả thời gian</option>
            <option value="dang_dien_ra">Đang diễn ra</option>
            <option value="sap_dien_ra">Sắp diễn ra</option>
            <option value="da_ket_thuc">Đã kết thúc</option>
        </select>
    </div>

    <!-- Loading skeleton -->
    <div id="eventListLoading" aria-live="polite" aria-label="Đang tải danh sách sự kiện">
        <div class="space-y-3">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="rounded-xl border border-slate-100 bg-white flex overflow-hidden">
                    <div class="w-44 shrink-0 min-h-[140px] nss-shimmer"></div>
                    <div class="flex-1 p-4 space-y-2.5">
                        <div class="h-3 w-20 bg-slate-100 rounded-full nss-shimmer"></div>
                        <div class="h-3 w-2/3 bg-slate-100 rounded nss-shimmer"></div>
                        <div class="h-3 w-1/2 bg-slate-100 rounded nss-shimmer"></div>
                        <div class="flex gap-4 mt-4 pt-3 border-t border-slate-100">
                            <div class="h-3 w-16 bg-slate-100 rounded nss-shimmer"></div>
                            <div class="h-3 w-20 bg-slate-100 rounded nss-shimmer"></div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Empty state -->
    <div id="eventListEmpty" class="hidden">
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <span class="material-symbols-outlined text-[48px] text-slate-300 mb-3" aria-hidden="true">event_busy</span>
            <p class="text-sm font-semibold text-slate-500">Chưa có sự kiện nào</p>
            <p class="text-xs text-slate-400 mt-1">Hãy tạo sự kiện đầu tiên để bắt đầu.</p>
        </div>
    </div>

    <!-- Card list + pagination -->
    <div id="eventList" class="hidden">
        <div id="eventListGrid" class="space-y-3" aria-live="polite"
            aria-label="Danh sách sự kiện"></div>

        <!-- Pagination -->
        <div id="eventPagination" class="hidden mt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p id="eventPaginationInfo" class="text-xs text-slate-500"></p>
            <nav class="flex items-center gap-1" aria-label="Phân trang danh sách sự kiện">
                <button id="eventPrevBtn" type="button"
                    class="inline-flex items-center justify-center size-8 rounded-lg border border-slate-200
                           text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed
                           transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-label="Trang trước">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_left</span>
                </button>
                <div id="eventPageBtns" class="flex items-center gap-1"></div>
                <button id="eventNextBtn" type="button"
                    class="inline-flex items-center justify-center size-8 rounded-lg border border-slate-200
                           text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed
                           transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-label="Trang sau">
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_right</span>
                </button>
            </nav>
        </div>
    </div>

</div>

<?php if ($_coQuyenTao): ?>
<!-- ═══════════════════════════════════════════════════════
     MODAL: TẠO SỰ KIỆN
════════════════════════════════════════════════════════ -->
<div id="evCreateModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity duration-200"
    role="dialog" aria-modal="true" aria-labelledby="evCreateModalTitle">

    <!-- Backdrop -->
    <div id="evCreateBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    <!-- Box -->
    <div id="evCreateBox"
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden scale-95 opacity-0 transition-[transform,opacity] duration-200">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="size-9 bg-primary rounded-xl flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-lg" aria-hidden="true">add</span>
                </div>
                <div>
                    <h3 id="evCreateModalTitle" class="text-sm font-bold text-slate-800">Tạo sự kiện mới</h3>
                    <p class="text-xs text-slate-500">Vui lòng nhập đầy đủ thông tin chi tiết bên dưới.</p>
                </div>
            </div>
            <button id="evCreateClose" type="button" aria-label="Đóng"
                class="size-8 rounded-lg flex items-center justify-center text-slate-400
                       hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

            <!-- Row 1: Tên SK + Cấp tổ chức -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="evTenSK" class="block mb-1.5 text-xs font-semibold text-slate-700">
                        Tên sự kiện <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <input id="evTenSK" type="text" placeholder="Nhập tên sự kiện"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                    <p id="evTenSKError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                </div>
                <div>
                    <label for="evIdCap" class="block mb-1.5 text-xs font-semibold text-slate-700">
                        Cấp tổ chức <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <select id="evIdCap"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors">
                        <option value="">-- Chọn cấp tổ chức --</option>
                    </select>
                    <p id="evIdCapError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                </div>
            </div>

            <!-- Row 2: Mô tả -->
            <div>
                <label for="evMoTa" class="block mb-1.5 text-xs font-semibold text-slate-700">Mô tả sự kiện</label>
                <textarea id="evMoTa" rows="3" placeholder="Mô tả tóm tắt về nội dung sự kiện..."
                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400 resize-none
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors"></textarea>
            </div>

            <!-- Row 3: Ngày bắt đầu + Ngày kết thúc -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="evNgayBatDau" class="block mb-1.5 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>
                    <input id="evNgayBatDau" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
                <div>
                    <label for="evNgayKetThuc" class="block mb-1.5 text-xs font-semibold text-slate-700">Ngày kết thúc</label>
                    <input id="evNgayKetThuc" type="datetime-local"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg text-slate-700
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary transition-colors" />
                </div>
            </div>

            <!-- Row 4: Checkbox GVHD -->
            <label class="flex items-start gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer
                          hover:border-primary/30 hover:bg-primary-light transition-colors
                          has-[:checked]:border-primary/40 has-[:checked]:bg-primary-light">
                <input id="evCoGVHD" type="checkbox" checked
                    class="mt-0.5 accent-primary cursor-pointer shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-slate-700">Sự kiện có GVHD</p>
                    <p class="text-xs text-slate-500 mt-0.5">Đánh dấu nếu sự kiện cần sự phê duyệt hoặc hỗ trợ chuyên môn từ Giảng viên hướng dẫn.</p>
                </div>
            </label>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <button id="evCreateCancel" type="button"
                class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-200
                       rounded-lg hover:bg-slate-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                Huỷ
            </button>
            <button id="evCreateSubmit" type="button"
                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-lg
                       bg-primary hover:bg-primary-dark transition-colors
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40
                       disabled:opacity-60 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined text-[15px]" aria-hidden="true">add</span>
                <span id="evCreateSubmitLabel">Tạo sự kiện</span>
            </button>
        </div>

    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
