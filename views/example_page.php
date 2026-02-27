<?php
/**
 * Ví dụ sử dụng Main Layout
 * File: views/example_page.php
 * 
 * Đây là file mẫu để minh họa cách sử dụng layout system
 */

// Thiết lập các biến cho layout
$pageTitle = "Trang Ví Dụ - ezManagement";
$currentPage = "dashboard"; // Dùng để highlight menu item trong sidebar
$pageHeading = "Trang Ví Dụ";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Trang Ví Dụ']
];

// Optional: CSS và JS riêng cho trang này
// $pageCss = "custom-page.css";
// $pageJs = "custom-page.js";

// Bắt đầu output buffering để capture nội dung trang
ob_start();
?>

<!-- NỘI DUNG TRANG BẮT ĐẦU TỪ ĐÂY -->
<div class="w-full px-6 py-6 mx-auto">
    <!-- Row 1: Cards -->
    <div class="flex flex-wrap -mx-3">
        <!-- Card 1 -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Tổng số sự kiện</p>
                                <h5 class="mb-0 font-bold">
                                    24
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+12%</span>
                                </h5>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500">
                                <i class="ni leading-none ni-money-coins text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Nhóm đăng ký</p>
                                <h5 class="mb-0 font-bold">
                                    156
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+8%</span>
                                </h5>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500">
                                <i class="ni leading-none ni-world text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Bài báo nộp</p>
                                <h5 class="mb-0 font-bold">
                                    89
                                    <span class="text-sm leading-normal text-red-600 font-weight-bolder">-2%</span>
                                </h5>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500">
                                <i class="ni leading-none ni-paper-diploma text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="w-full max-w-full px-3 sm:w-1/2 sm:flex-none xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Đánh giá hoàn tất</p>
                                <h5 class="mb-0 font-bold">
                                    67
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+15%</span>
                                </h5>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500">
                                <i class="ni leading-none ni-cart text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Content -->
    <div class="flex flex-wrap mt-6 -mx-3">
        <div class="w-full px-3">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="border-black/12.5 mb-0 rounded-t-2xl border-b-0 border-solid bg-white p-6 pb-0">
                    <h6>Danh sách sự kiện gần đây</h6>
                    <p class="text-sm leading-normal">Tổng quan về các sự kiện trong hệ thống</p>
                </div>
                <div class="flex-auto p-6">
                    <div class="overflow-x-auto">
                        <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                            <thead class="align-bottom">
                                <tr>
                                    <th class="px-6 py-3 font-bold tracking-normal text-left uppercase align-middle bg-transparent border-b letter border-b-solid text-xxs whitespace-nowrap border-b-gray-200 text-slate-400 opacity-70">Tên sự kiện</th>
                                    <th class="px-6 py-3 font-bold tracking-normal text-left uppercase align-middle bg-transparent border-b letter border-b-solid text-xxs whitespace-nowrap border-b-gray-200 text-slate-400 opacity-70">Ngày bắt đầu</th>
                                    <th class="px-6 py-3 font-bold tracking-normal text-center uppercase align-middle bg-transparent border-b letter border-b-solid text-xxs whitespace-nowrap border-b-gray-200 text-slate-400 opacity-70">Trạng thái</th>
                                    <th class="px-6 py-3 font-bold tracking-normal text-center uppercase align-middle bg-transparent border-b letter border-b-solid text-xxs whitespace-nowrap border-b-gray-200 text-slate-400 opacity-70">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap">
                                        <div class="flex px-2 py-1">
                                            <div class="flex flex-col justify-center">
                                                <h6 class="mb-0 text-sm leading-normal">Hội nghị khoa học 2024</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap">
                                        <p class="mb-0 text-xs font-semibold leading-tight">15/03/2024</p>
                                    </td>
                                    <td class="p-2 text-sm leading-normal text-center align-middle bg-transparent border-b whitespace-nowrap">
                                        <span class="bg-gradient-to-tl from-green-600 to-lime-400 px-2.5 text-xs rounded-1.8 py-1.4 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white">Đang diễn ra</span>
                                    </td>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap text-center">
                                        <a href="#" class="text-xs font-semibold leading-tight text-slate-400">Xem chi tiết</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap">
                                        <div class="flex px-2 py-1">
                                            <div class="flex flex-col justify-center">
                                                <h6 class="mb-0 text-sm leading-normal">Hội thảo sinh viên NCKH</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap">
                                        <p class="mb-0 text-xs font-semibold leading-tight">20/04/2024</p>
                                    </td>
                                    <td class="p-2 text-sm leading-normal text-center align-middle bg-transparent border-b whitespace-nowrap">
                                        <span class="bg-gradient-to-tl from-slate-600 to-slate-300 px-2.5 text-xs rounded-1.8 py-1.4 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white">Sắp diễn ra</span>
                                    </td>
                                    <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap text-center">
                                        <a href="#" class="text-xs font-semibold leading-tight text-slate-400">Xem chi tiết</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- NỘI DUNG TRANG KẾT THÚC -->

<?php
// Lấy nội dung đã buffer và lưu vào biến $content
$content = ob_get_clean();

// Include main layout - layout sẽ sử dụng biến $content đã được định nghĩa
include '../layouts/main_layout.php';
?>
