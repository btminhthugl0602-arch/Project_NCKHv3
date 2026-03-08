<?php
/**
 * Dashboard Page
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$_isGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!isset($_SESSION['idTK']) && !$_isGuest) {
    header('Location: /sign-in');
    exit;
}

// Thiết lập các biến cho layout
$pageTitle = "Dashboard - ezManagement";
$currentPage = "dashboard";
$pageHeading = "Dashboard";
$breadcrumbs = [
    ['title' => 'Dashboard']
];

// Bắt đầu output buffering để capture nội dung trang
ob_start();
?>

<!-- NỘI DUNG DASHBOARD -->
<div class="w-full px-6 py-6 mx-auto">
    <!-- Row 1: Thống kê Cards -->
    <div class="flex flex-wrap -mx-3">
        <!-- Card 1: Tổng số sự kiện -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Tổng Sự kiện</p>
                                <h5 class="mb-0 font-bold">
                                    12
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+5%</span>
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

        <!-- Card 2: Nhóm đăng ký -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Nhóm Tham gia</p>
                                <h5 class="mb-0 font-bold">
                                    48
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+12%</span>
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

        <!-- Card 3: Bài báo nộp -->
        <div class="w-full max-w-full px-3 mb-6 sm:w-1/2 sm:flex-none xl:mb-0 xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Bài Báo</p>
                                <h5 class="mb-0 font-bold">
                                    35
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

        <!-- Card 4: Đánh giá -->
        <div class="w-full max-w-full px-3 sm:w-1/2 sm:flex-none xl:w-1/4">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans text-sm font-semibold leading-normal">Đánh Giá</p>
                                <h5 class="mb-0 font-bold">
                                    28
                                    <span class="text-sm leading-normal font-weight-bolder text-lime-500">+8%</span>
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

    <!-- Row 2: Thông báo và Hoạt động -->
    <div class="flex flex-wrap mt-6 -mx-3">
        <!-- Thông báo -->
        <div class="w-full max-w-full px-3 mb-6 lg:mb-0 lg:w-7/12 lg:flex-none">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="border-black/12.5 mb-0 rounded-t-2xl border-b-0 border-solid bg-white p-6 pb-0">
                    <h6 class="mb-2">Thông báo mới</h6>
                    <p class="text-sm leading-normal">
                        <i class="fa fa-bell text-lime-500"></i>
                        <span class="font-semibold">3 thông báo mới</span> hôm nay
                    </p>
                </div>
                <div class="flex-auto p-6">
                    <div class="before:border-r-solid relative before:absolute before:top-0 before:left-4 before:h-full before:border-r-2 before:border-r-slate-100 before:content-[''] before:lg:-ml-px">
                        <div class="relative mb-4 mt-0 after:clear-both after:table after:content-['']">
                            <span class="w-6.5 h-6.5 text-base absolute left-4 z-10 inline-flex -translate-x-1/2 items-center justify-center rounded-full bg-white text-center font-semibold">
                                <i class="relative z-10 leading-none text-transparent ni ni-bell-55 leading-pro bg-gradient-to-tl from-green-600 to-lime-400 bg-clip-text fill-transparent"></i>
                            </span>
                            <div class="ml-11.252 pt-1.4 lg:max-w-120 relative -top-1.5 w-auto">
                                <h6 class="mb-0 text-sm font-semibold leading-normal text-slate-700">Sự kiện mới được tạo</h6>
                                <p class="mt-1 mb-0 text-xs font-semibold leading-tight text-slate-400">
                                    <i class="mr-1 fa fa-clock"></i>
                                    Vừa xong
                                </p>
                            </div>
                        </div>
                        <div class="relative mb-4 after:clear-both after:table after:content-['']">
                            <span class="w-6.5 h-6.5 text-base absolute left-4 z-10 inline-flex -translate-x-1/2 items-center justify-center rounded-full bg-white text-center font-semibold">
                                <i class="relative z-10 leading-none text-transparent ni ni-html5 leading-pro bg-gradient-to-tl from-red-600 to-rose-400 bg-clip-text fill-transparent"></i>
                            </span>
                            <div class="ml-11.252 pt-1.4 lg:max-w-120 relative -top-1.5 w-auto">
                                <h6 class="mb-0 text-sm font-semibold leading-normal text-slate-700">Nhóm mới đăng ký</h6>
                                <p class="mt-1 mb-0 text-xs font-semibold leading-tight text-slate-400">
                                    <i class="mr-1 fa fa-clock"></i>
                                    2 giờ trước
                                </p>
                            </div>
                        </div>
                        <div class="relative mb-0 after:clear-both after:table after:content-['']">
                            <span class="w-6.5 h-6.5 text-base absolute left-4 z-10 inline-flex -translate-x-1/2 items-center justify-center rounded-full bg-white text-center font-semibold">
                                <i class="relative z-10 leading-none text-transparent ni ni-cart leading-pro bg-gradient-to-tl from-blue-600 to-cyan-400 bg-clip-text fill-transparent"></i>
                            </span>
                            <div class="ml-11.252 pt-1.4 lg:max-w-120 relative -top-1.5 w-auto">
                                <h6 class="mb-0 text-sm font-semibold leading-normal text-slate-700">Bài báo mới được nộp</h6>
                                <p class="mt-1 mb-0 text-xs font-semibold leading-tight text-slate-400">
                                    <i class="mr-1 fa fa-clock"></i>
                                    5 giờ trước
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="w-full max-w-full px-3 lg:w-5/12 lg:flex-none">
            <div class="border-black/12.5 shadow-soft-xl relative flex h-full min-w-0 flex-col break-words rounded-2xl border-0 border-solid bg-white bg-clip-border p-4">
                <div class="relative h-full overflow-hidden bg-cover rounded-xl" style="background-image: url('../assets/img/ivancik.jpg')">
                    <span class="absolute top-0 left-0 w-full h-full bg-center bg-cover bg-gradient-to-tl from-gray-900 to-slate-800 opacity-80"></span>
                    <div class="relative z-10 flex flex-col flex-auto h-full p-4">
                        <h5 class="pt-2 mb-6 font-bold text-white">Chào mừng đến với ezManagement</h5>
                        <p class="text-white">Hệ thống quản lý hội thảo nghiên cứu khoa học. Tạo sự kiện, quản lý nhóm, bình duyệt bài báo một cách dễ dàng.</p>
                        <a class="mt-auto mb-0 text-sm font-semibold leading-normal text-white group" href="javascript:;">
                            Tìm hiểu thêm
                            <i class="fas fa-arrow-right ease-bounce text-sm group-hover:translate-x-1.25 ml-1 leading-normal transition-all duration-200"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- KẾT THÚC NỘI DUNG DASHBOARD -->

<?php
// Lấy nội dung đã buffer và lưu vào biến $content
$content = ob_get_clean();

// Include main layout - layout sẽ sử dụng biến $content đã được định nghĩa
include '../layouts/main_layout.php';
?>
