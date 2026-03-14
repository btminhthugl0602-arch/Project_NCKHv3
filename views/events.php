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
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[16px] text-primary" aria-hidden="true">info</span>
            <p class="text-sm font-semibold text-slate-700">Luồng triển khai đề xuất</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?php foreach ([['1', 'Tạo sự kiện cơ bản'], ['2', 'Cấu hình vòng thi'], ['3', 'Cấu hình quy chế']] as [$n, $l]): ?>
                <div class="flex items-center gap-3 bg-white rounded-lg border border-slate-200 px-4 py-3">
                    <span
                        class="inline-flex items-center justify-center size-7 rounded-full bg-primary-light text-primary text-xs font-bold shrink-0"><?php echo $n; ?></span>
                    <span class="text-sm font-medium text-slate-700"><?php echo $l; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Loading skeleton -->
    <div id="eventListLoading" aria-live="polite" aria-label="Đang tải danh sách sự kiện">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="rounded-xl overflow-hidden border border-slate-100">
                    <div class="h-28 nss-shimmer"></div>
                    <div class="bg-white p-4 space-y-2.5">
                        <div class="h-3 bg-slate-100 rounded w-3/4 nss-shimmer"></div>
                        <div class="h-3 bg-slate-100 rounded w-2/3 nss-shimmer"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/2 nss-shimmer"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/3 ml-auto nss-shimmer mt-2"></div>
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

    <!-- Card grid + pagination -->
    <div id="eventList" class="hidden">
        <div id="eventListGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" aria-live="polite"
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

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
