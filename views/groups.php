<?php
/**
 * Nhóm của tôi Page — Placeholder
 * TODO: Thêm nội dung khi hoàn thiện tính năng
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['idTK'])) {
    header('Location: /views/dang_nhap.php');
    exit;
}

$pageTitle   = "Nhóm của tôi - ezManagement";
$currentPage = "groups";
$pageHeading = "Nhóm của tôi";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/dashboard'],
    ['title' => 'Nhóm của tôi'],
];

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-col items-center justify-center" style="min-height: 60vh;">
        <div class="bg-white rounded-2xl shadow-soft-xl p-12 text-center max-w-md w-full">
            <div class="inline-flex items-center justify-center rounded-2xl mb-6 shadow-soft-md"
                 style="width:72px;height:72px;background:linear-gradient(135deg,#7928ca,#ff0080);">
                <span class="material-symbols-outlined text-white active-icon" style="font-size:2rem">group</span>
            </div>
            <h4 class="font-bold text-slate-800 mb-2" style="font-size:1.25rem">Nhóm của tôi</h4>
            <p class="text-sm text-slate-500 mb-6">Quản lý nhóm thi, xem thành viên và trạng thái nộp bài của nhóm.</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                  style="background:#fef3c7;color:#92400e;">
                <span class="material-symbols-outlined" style="font-size:14px">construction</span>
                Đang phát triển
            </span>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>
