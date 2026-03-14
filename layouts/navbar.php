<?php

/**
 * layouts/navbar.php
 *
 * REBUILT: Burgundy + Sand palette — theo mockup Stitch
 * - Breadcrumb bên trái
 * - Search + bell + avatar bên phải
 * - Avatar tròn burgundy + chữ cái đầu tên
 * - Logic PHP + notification JS: giữ nguyên 100%
 */

$navIsGuest  = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
$navHoTen    = isset($_SESSION['hoTen'])    ? $_SESSION['hoTen']    : 'Tài khoản';
$navIdLoaiTK = isset($_SESSION['idLoaiTK']) ? (int)$_SESSION['idLoaiTK'] : 0;
$navIdTK     = isset($_SESSION['idTK'])     ? (int)$_SESSION['idTK']     : 0;
$navLoaiMap  = [1 => 'Quản trị viên', 2 => 'Giảng viên', 3 => 'Sinh viên'];
$navLoaiLabel = $navLoaiMap[$navIdLoaiTK] ?? 'Người dùng';
$navInitial  = mb_strtoupper(mb_substr($navHoTen, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!-- Navbar -->
<header class="h-16 bg-white border-b border-slate-100 sticky top-0 z-20 shrink-0 flex items-center px-4 gap-3">

    <!-- Hamburger — mobile only -->
    <button type="button" id="hamburgerBtn"
        class="lg:hidden flex items-center justify-center size-9 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 shrink-0"
        aria-label="Mở menu điều hướng" aria-expanded="false" aria-controls="mainSidebar" onclick="toggleSidebar()">
        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">menu</span>
    </button>

    <!-- Breadcrumbs -->
    <nav aria-label="Điều hướng trang" class="flex-1 min-w-0">
        <ol class="flex items-center gap-1.5 text-sm">
            <li>
                <a href="/"
                    class="text-slate-400 hover:text-slate-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded">
                    Trang chủ
                </a>
            </li>
            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                    <li class="flex items-center gap-1.5 text-slate-300 min-w-0">
                        <span class="select-none" aria-hidden="true">/</span>
                        <?php if (isset($breadcrumb['url'])): ?>
                            <a href="<?php echo htmlspecialchars($breadcrumb['url']); ?>"
                                class="text-slate-500 hover:text-slate-700 transition-colors truncate focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded">
                                <?php echo htmlspecialchars($breadcrumb['title']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-slate-800 font-semibold truncate" aria-current="page">
                                <?php echo htmlspecialchars($breadcrumb['title']); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="flex items-center gap-1.5 text-slate-300">
                    <span class="select-none" aria-hidden="true">/</span>
                    <span class="text-slate-800 font-semibold truncate" aria-current="page">
                        <?php echo htmlspecialchars(isset($pageHeading) ? $pageHeading : 'Dashboard'); ?>
                    </span>
                </li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Right actions -->
    <div class="flex items-center gap-2 shrink-0">

        <!-- Search -->
        <div class="relative hidden sm:block">
            <span
                class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                aria-hidden="true">search</span>
            <input type="search" id="navbarSearch" name="q" autocomplete="off" aria-label="Tìm kiếm sự kiện"
                aria-expanded="false" aria-controls="navbarSearchDropdown" aria-haspopup="listbox"
                placeholder="Tìm kiếm sự kiện…" class="w-56 pl-8 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:border-primary
                       transition-colors duration-150" />
            <div id="navbarSearchDropdown" role="listbox" aria-label="Kết quả tìm kiếm" aria-hidden="true"
                class="hidden absolute left-0 top-[calc(100%+6px)] w-80 bg-white rounded-xl shadow-[0_8px_32px_-4px_rgba(0,0,0,0.10)] border border-slate-100 overflow-hidden z-50">
            </div>
        </div>

        <!-- Notification bell -->
        <div class="relative" id="notificationRoot" data-guest="<?php echo $navIsGuest ? '1' : '0'; ?>">
            <button type="button" id="notificationBtn" aria-label="Thông báo" aria-haspopup="true" aria-expanded="false"
                class="relative flex items-center justify-center size-9 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 transition-colors duration-150">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">notifications</span>
            </button>
            <!-- Unread dot -->
            <span id="notificationDot"
                class="hidden absolute top-1.5 right-1.5 size-2 rounded-full ring-2 ring-white bg-red-600"
                aria-hidden="true"></span>
            <!-- Count badge -->
            <span id="notificationCountBadge"
                class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full text-white text-[10px] font-bold leading-[18px] text-center bg-red-600"
                aria-hidden="true"></span>

            <!-- Dropdown -->
            <div id="notificationDropdown" role="dialog" aria-label="Danh sách thông báo" aria-hidden="true"
                aria-live="polite"
                class="hidden absolute right-0 top-[calc(100%+8px)] w-96 max-w-[90vw] bg-white rounded-xl shadow-[0_8px_32px_-4px_rgba(0,0,0,0.10)] border border-slate-100 z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">Thông báo</p>
                        <p id="notificationSubtitle" class="text-xs text-slate-400 truncate">Đang tải dữ liệu…</p>
                    </div>
                    <button type="button" id="markAllReadBtn"
                        class="text-xs font-semibold text-primary hover:opacity-75 disabled:opacity-40 disabled:pointer-events-none transition-opacity shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded"
                        aria-label="Đánh dấu tất cả thông báo là đã đọc">
                        Đánh dấu tất cả
                    </button>
                </div>
                <div id="notificationList" class="max-h-[400px] overflow-y-auto divide-y divide-slate-50"
                    aria-live="polite" aria-atomic="false">
                    <div class="px-4 py-6 text-sm text-slate-400 text-center">Đang tải thông báo…</div>
                </div>
                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 text-right">
                    <a href="/dashboard"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded">
                        Xem tổng quan
                    </a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-6 w-px bg-slate-200 mx-1" aria-hidden="true"></div>

        <!-- User / Guest -->
        <?php if ($navIsGuest): ?>
            <a href="/sign-in"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-lg hover:opacity-90 transition-opacity focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 bg-primary">
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">login</span>
                Đăng nhập
            </a>
        <?php else: ?>
            <a href="/profile"
                class="flex items-center gap-2.5 px-2 py-1 rounded-lg hover:bg-slate-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                aria-label="Trang cá nhân của <?php echo htmlspecialchars($navHoTen); ?>">
                <!-- Name + role -->
                <div class="text-right hidden sm:block min-w-0">
                    <p class="text-sm font-semibold text-slate-800 leading-tight truncate max-w-[130px]">
                        <?php echo htmlspecialchars($navHoTen); ?>
                    </p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-tight truncate">
                        <?php echo htmlspecialchars($navLoaiLabel); ?>
                    </p>
                </div>
                <!-- Avatar -->
                <div class="size-9 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0 select-none bg-primary"
                    aria-hidden="true">
                    <?php echo $navInitial; ?>
                </div>
            </a>
        <?php endif; ?>

    </div>
</header>

<script>
    window.NOTIFICATION_CONTEXT = {
        isGuest: <?php echo $navIsGuest ? 'true' : 'false'; ?>,
        idTK: <?php echo $navIdTK; ?>
    };
</script>
<!-- End Navbar -->