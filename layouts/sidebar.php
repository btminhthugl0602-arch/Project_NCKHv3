<?php
$useEventSidebar = isset($useEventSidebar) && $useEventSidebar === true;
$eventSidebarEventId = isset($eventSidebarEventId) ? (int) $eventSidebarEventId : 0;
$eventSidebarSection = isset($eventSidebarSection) ? (string) $eventSidebarSection : 'overview';

function _sb_link(string $section, string $current, int $idSk, string $icon, string $label): string {
    $isActive = $section === $current;
    $activeClass = $isActive
        ? 'bg-primary/5 text-primary border-l-2 border-primary font-semibold'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
    $iconClass = $isActive ? 'text-primary active-icon' : 'text-slate-400';
    $ariaCurrent = $isActive ? 'page' : 'false';
    $href = "/event-detail?id_sk={$idSk}&amp;tab={$section}";
    return <<<HTML
<a href="{$href}"
   class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors {$activeClass}"
   aria-current="{$ariaCurrent}">
    <span class="material-symbols-outlined text-[18px] shrink-0 {$iconClass}">{$icon}</span>
    <span class="text-sm">{$label}</span>
</a>
HTML;
}

function _sb_section_label(string $label): string {
    return "<p class='px-4 pt-4 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider'>{$label}</p>";
}
?>
<!-- Sidebar -->
<aside class="w-72 bg-white border-r border-slate-200 flex flex-col shrink-0 h-screen sticky top-0 overflow-y-auto">

    <!-- Logo -->
    <div class="p-5 flex items-center gap-3 border-b border-slate-100 shrink-0">
        <div class="size-9 bg-gradient-to-br from-[#d946ef] to-[#9333ea] rounded-xl flex items-center justify-center text-white shrink-0">
            <span class="material-symbols-outlined text-xl active-icon">school</span>
        </div>
        <div class="min-w-0">
            <h1 class="text-slate-900 font-bold text-base leading-tight truncate">ezManagement</h1>
            <p class="text-slate-500 text-xs font-medium truncate">Management Portal</p>
        </div>
    </div>

    <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent" />

    <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
        <ul class="flex flex-col pl-0 mb-0">
            <?php
                $useEventSidebar = isset($useEventSidebar) && $useEventSidebar === true;
                $eventSidebarEventId = isset($eventSidebarEventId) ? (int) $eventSidebarEventId : 0;
                $eventSidebarSection = isset($eventSidebarSection) ? (string) $eventSidebarSection : 'overview';
            ?>

            <?php if ($useEventSidebar): ?>
            <li class="px-4 py-3 mx-4 mb-3 text-white bg-gradient-to-tl from-purple-700 to-pink-500 rounded-xl shadow-soft-xl">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="mb-0 text-xs text-white opacity-80">Sự kiện hiện tại</p>
                        <p id="sidebarEventName" class="mb-0 text-xl font-bold leading-tight truncate">Đang tải...</p>
                    </div>
                    <a href="/events" class="flex items-center justify-center w-9 h-9 text-white rounded-xl bg-white/20 hover:bg-white/30 transition-colors" title="Quay lại danh sách sự kiện">
                        <i class="fas fa-exchange-alt"></i>
                    </a>
                </div>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'overview' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=overview">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-home text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Tổng quan</span>
                </a>
            </li>

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase opacity-60">Cấu hình</h6>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'config' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=config">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-cog text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Cấu hình sự kiện</span>
                </a>
            </li>

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase opacity-60">Quản lý bài nộp</h6>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'submissions' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=submissions">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-file-alt text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Tất cả bài nộp</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'review-assign' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=review-assign">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-user-check text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Phân công phản biện</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'review-results' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=review-results">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-chart-bar text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Kết quả Review</span>
                </a>
            </li>

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase opacity-60">Nhóm thi</h6>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'nhom-all' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/nhom/allgroup?id_sk=<?php echo $eventSidebarEventId; ?>">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-globe text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Nhóm công khai</span>
                </a>
            </li>
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'nhom-my' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/nhom/mygroup?id_sk=<?php echo $eventSidebarEventId; ?>">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-users text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Nhóm của tôi</span>
                </a>
            </li>
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'nhom-request' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/nhom/request?id_sk=<?php echo $eventSidebarEventId; ?>">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-envelope text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Lời mời nhóm</span>
                </a>
            </li>
            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase opacity-60">Tiểu ban &amp; giải thưởng</h6>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'committees' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=committees">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-users text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Quản lý tiểu ban</span>
                </a>
            </li>

            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo $eventSidebarSection === 'judges' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/event-detail?id_sk=<?php echo $eventSidebarEventId; ?>&tab=judges">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white text-center xl:p-2.5">
                        <i class="fas fa-gavel text-slate-700 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Phân công BGK</span>
                </a>
            </li>

            <?php else: ?>
            <!-- Dashboard -->
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo isset($currentPage) && $currentPage == 'dashboard' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap rounded-lg px-4 text-slate-700 transition-colors" href="/dashboard">
                    <div class="<?php echo isset($currentPage) && $currentPage == 'dashboard' ? 'bg-gradient-to-tl from-purple-700 to-pink-500' : ''; ?> shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <svg width="12px" height="12px" viewBox="0 0 45 40" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <title>shop</title>
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <g transform="translate(-1716.000000, -439.000000)" fill="#FFFFFF" fill-rule="nonzero">
                                    <g transform="translate(1716.000000, 291.000000)">
                                        <g transform="translate(0.000000, 148.000000)">
                                            <path class="opacity-60" d="M46.7199583,10.7414583 L40.8449583,0.949791667 C40.4909749,0.360605034 39.8540131,0 39.1666667,0 L7.83333333,0 C7.1459869,0 6.50902508,0.360605034 6.15504167,0.949791667 L0.280041667,10.7414583 C0.0969176761,11.0460037 -1.23209662e-05,11.3946378 -1.23209662e-05,11.75 C-0.00758042603,16.0663731 3.48367543,19.5725301 7.80004167,19.5833333 L7.81570833,19.5833333 C9.75003686,19.5882688 11.6168794,18.8726691 13.0522917,17.5760417 C16.0171492,20.2556967 20.5292675,20.2556967 23.494125,17.5760417 C26.4604562,20.2616016 30.9794188,20.2616016 33.94575,17.5760417 C36.2421905,19.6477597 39.5441143,20.1708521 42.3684437,18.9103691 C45.1927731,17.649886 47.0084685,14.8428276 47.0000295,11.75 C47.0000295,11.3946378 46.9030823,11.0460037 46.7199583,10.7414583 Z"></path>
                                            <path class="" d="M39.198,22.4912623 C37.3776246,22.4928106 35.5817531,22.0149171 33.951625,21.0951667 L33.92225,21.1107282 C31.1430221,22.6838032 27.9255001,22.9318916 24.9844167,21.7998837 C24.4750389,21.605469 23.9777983,21.3722567 23.4960833,21.1018359 L23.4745417,21.1129513 C20.6961809,22.6871153 17.4786145,22.9344611 14.5386667,21.7998837 C14.029926,21.6054643 13.533337,21.3722507 13.0522917,21.1018359 C11.4250962,22.0190609 9.63246555,22.4947009 7.81570833,22.4912623 C7.16510551,22.4842162 6.51607673,22.4173045 5.875,22.2911849 L5.875,44.7220845 C5.875,45.9498589 6.7517757,46.9451667 7.83333333,46.9451667 L19.5833333,46.9451667 L19.5833333,33.6066734 L27.4166667,33.6066734 L27.4166667,46.9451667 L39.1666667,46.9451667 C40.2482243,46.9451667 41.125,45.9498589 41.125,44.7220845 L41.125,22.2822926 C40.4887822,22.4116582 39.8442868,22.4815492 39.198,22.4912623 Z"></path>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Dashboard</span>
                </a>
            </li>

            <!-- Quản lý sự kiện -->
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo isset($currentPage) && $currentPage == 'events' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors" href="/events">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <svg width="12px" height="12px" viewBox="0 0 42 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <title>office</title>
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <g transform="translate(-1869.000000, -293.000000)" fill="#FFFFFF" fill-rule="nonzero">
                                    <g transform="translate(1716.000000, 291.000000)">
                                        <g transform="translate(153.000000, 2.000000)">
                                            <path class="fill-slate-800 opacity-60" d="M12.25,17.5 L8.75,17.5 L8.75,1.75 C8.75,0.78225 9.53225,0 10.5,0 L31.5,0 C32.46775,0 33.25,0.78225 33.25,1.75 L33.25,12.25 L29.75,12.25 L29.75,3.5 L12.25,3.5 L12.25,17.5 Z"></path>
                                            <path class="fill-slate-800" d="M40.25,14 L24.5,14 C23.53225,14 22.75,14.78225 22.75,15.75 L22.75,38.5 L19.25,38.5 L19.25,22.75 C19.25,21.78225 18.46775,21 17.5,21 L1.75,21 C0.78225,21 0,21.78225 0,22.75 L0,40.25 C0,41.21775 0.78225,42 1.75,42 L40.25,42 C41.21775,42 42,41.21775 42,40.25 L42,15.75 C42,14.78225 41.21775,14 40.25,14 Z M12.25,36.75 L7,36.75 L7,33.25 L12.25,33.25 L12.25,36.75 Z M12.25,29.75 L7,29.75 L7,26.25 L12.25,26.25 L12.25,29.75 Z M35,36.75 L29.75,36.75 L29.75,33.25 L35,33.25 L35,36.75 Z M35,29.75 L29.75,29.75 L29.75,26.25 L35,26.25 L35,29.75 Z M35,22.75 L29.75,22.75 L29.75,19.25 L35,19.25 L35,22.75 Z"></path>
                                        </g>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Quản lý Sự kiện</span>
                </a>
            </li>
            <!-- Quản lý nhóm (Sinh viên) -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo isset($currentPage) && $currentPage == 'groups' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors" href="/groups">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <i class="fas fa-users text-slate-800 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Nhóm của tôi</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Bình duyệt (Giảng viên) -->
            <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'lecturer' || $_SESSION['user_role'] === 'admin')): ?>
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo isset($currentPage) && $currentPage == 'review' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors" href="/review">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <i class="fas fa-clipboard-check text-slate-800 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Bình duyệt</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="w-full mt-4">
                <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase opacity-60">Tài khoản</h6>
            </li>

            <!-- Profile -->
            <li class="mt-0.5 w-full">
                <a class="py-2.7 <?php echo isset($currentPage) && $currentPage == 'profile' ? 'shadow-soft-xl bg-white font-semibold' : ''; ?> text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors" href="/profile">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <i class="fas fa-user text-slate-800 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Hồ sơ</span>
                </a>
            </li>

            <!-- Sign Out -->
            <li class="mt-0.5 w-full">
                <a class="py-2.7 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors" href="/api/auth/logout.php">
                    <div class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                        <i class="fas fa-sign-out-alt text-slate-800 text-sm"></i>
                    </div>
                    <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Đăng xuất</span>
                </a>
            </div>
            <p id="sidebarEventName" class="font-bold text-sm leading-snug mb-3 truncate">Đang tải…</p>
            <div class="flex items-center gap-2">
                <span class="size-1.5 bg-green-400 rounded-full animate-pulse"></span>
                <span class="text-xs font-medium">Đang diễn ra</span>
            </div>
        </div>

        <!-- Tổng quan -->
        <?php echo _sb_link('overview', $eventSidebarSection, $eventSidebarEventId, 'dashboard', 'Tổng quan'); ?>

        <!-- Cấu hình -->
        <?php echo _sb_section_label('Cấu hình sự kiện'); ?>
        <?php echo _sb_link('config-basic',    $eventSidebarSection, $eventSidebarEventId, 'tune',        'Cấu hình cơ bản'); ?>
        <?php echo _sb_link('config-rules',    $eventSidebarSection, $eventSidebarEventId, 'gavel',       'Cấu hình quy chế'); ?>
        <?php echo _sb_link('config-criteria', $eventSidebarSection, $eventSidebarEventId, 'checklist',   'Thiết lập bộ tiêu chí'); ?>

        <!-- Bài nộp -->
        <?php echo _sb_section_label('Quản lý bài nộp'); ?>
        <?php echo _sb_link('submissions',    $eventSidebarSection, $eventSidebarEventId, 'description',  'Tất cả bài nộp'); ?>
        <?php echo _sb_link('review-assign',  $eventSidebarSection, $eventSidebarEventId, 'person_check', 'Phân công phản biện'); ?>
        <?php echo _sb_link('review-results', $eventSidebarSection, $eventSidebarEventId, 'bar_chart',    'Kết quả Review'); ?>

        <!-- Chấm thi -->
        <?php echo _sb_section_label('Nghiệp vụ chấm thi'); ?>
        <?php echo _sb_link('scoring',       $eventSidebarSection, $eventSidebarEventId, 'edit_note',    'Phân công & Quản lý Điểm'); ?>
        <?php echo _sb_link('subcommittees', $eventSidebarSection, $eventSidebarEventId, 'account_tree', 'Quản lý Tiểu ban'); ?>

        <!-- Tiểu ban & Giải thưởng -->
        <?php echo _sb_section_label('Tiểu ban & Giải thưởng'); ?>
        <?php echo _sb_link('committees', $eventSidebarSection, $eventSidebarEventId, 'groups', 'Quản lý tiểu ban'); ?>
        <?php echo _sb_link('judges',     $eventSidebarSection, $eventSidebarEventId, 'gavel',  'Phân công BGK'); ?>

        <?php else: ?>

        <!-- Standard nav (non-event pages) -->
        <a href="/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'dashboard' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
            <span class="material-symbols-outlined text-[18px] shrink-0">dashboard</span>
            <span class="text-sm">Dashboard</span>
        </a>
        <a href="/events" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'events' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
            <span class="material-symbols-outlined text-[18px] shrink-0">event</span>
            <span class="text-sm">Quản lý Sự kiện</span>
        </a>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
        <a href="/groups" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'groups' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
            <span class="material-symbols-outlined text-[18px] shrink-0">group</span>
            <span class="text-sm">Nhóm của tôi</span>
        </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['lecturer', 'admin'])): ?>
        <a href="/review" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'review' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
            <span class="material-symbols-outlined text-[18px] shrink-0">rate_review</span>
            <span class="text-sm">Bình duyệt</span>
        </a>
        <?php endif; ?>

        <?php echo _sb_section_label('Tài khoản'); ?>
        <a href="/profile" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors text-slate-600 hover:bg-slate-50">
            <span class="material-symbols-outlined text-[18px] shrink-0">person</span>
            <span class="text-sm">Hồ sơ</span>
        </a>
        <a href="/api/auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors text-rose-500 hover:bg-rose-50">
            <span class="material-symbols-outlined text-[18px] shrink-0">logout</span>
            <span class="text-sm">Đăng xuất</span>
        </a>

        <?php endif; ?>
    </nav>
</aside>
<!-- End Sidebar -->
