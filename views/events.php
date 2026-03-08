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

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex flex-wrap items-center justify-between gap-4 p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                    <div class="pr-2">
                        <h6 class="mb-1">Cấu hình sự kiện</h6>
                        <p class="mb-0 text-sm leading-normal text-slate-500">Bắt đầu bằng việc tạo sự kiện mới để cấu hình các bước tiếp theo.</p>
                    </div>
                    <div class="p-1 rounded-2xl bg-gradient-to-tl from-purple-700 via-fuchsia-600 to-pink-500 shadow-soft-xl">
                        <button
                            id="openCreateEventBtn"
                            type="button"
                            class="inline-flex items-center px-6 py-3 text-xs font-bold text-center text-white uppercase align-middle transition-all border-0 rounded-xl cursor-pointer bg-gradient-to-tl from-purple-700 via-fuchsia-600 to-pink-500 leading-pro ease-soft-in tracking-tight-soft hover:scale-102 active:opacity-85 shadow-soft-md"
                        >
                            <i class="mr-2 fas fa-plus"></i>
                            Tạo sự kiện
                        </button>
                    </div>
                </div>

                <div class="flex-auto p-6">
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        <p class="mb-1 text-sm font-semibold text-slate-700">Luồng triển khai đề xuất</p>
                        <ul class="pl-5 text-sm list-disc text-slate-500 space-y-1">
                            <li>Tạo sự kiện cơ bản (tên, mô tả, thời gian).</li>
                            <li>Cấu hình vòng thi cho từng giai đoạn.</li>
                            <li>Cấu hình quy chế và điều kiện tham gia.</li>
                        </ul>
                    </div>

                    <div class="mt-4 text-sm text-slate-500">
                        Sau khi tạo thành công, bạn có thể tiếp tục cấu hình vòng thi và quy chế trong các bước tiếp theo.
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <h6 class="mb-0 text-sm">Danh sách sự kiện</h6>
                            <span class="text-xs text-slate-500">Cập nhật tự động sau khi tạo</span>
                        </div>
                        <div id="eventListEmpty" class="px-4 py-5 text-sm border border-dashed rounded-xl text-slate-500 border-slate-300 bg-slate-50">
                            Chưa có sự kiện nào để hiển thị.
                        </div>
                        <div id="eventList" class="hidden overflow-x-auto">
                            <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                <thead class="align-bottom">
                                    <tr>
                                        <th class="px-3 py-3 text-xxs font-bold tracking-wider text-left uppercase border-b border-slate-200 text-slate-400">Sự kiện</th>
                                        <th class="px-3 py-3 text-xxs font-bold tracking-wider text-left uppercase border-b border-slate-200 text-slate-400">Cấp tổ chức</th>
                                        <th class="px-3 py-3 text-xxs font-bold tracking-wider text-left uppercase border-b border-slate-200 text-slate-400">Thời gian</th>
                                        <th class="px-3 py-3 text-xxs font-bold tracking-wider text-left uppercase border-b border-slate-200 text-slate-400">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="eventListBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
