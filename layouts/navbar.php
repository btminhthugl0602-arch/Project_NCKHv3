<!-- Navbar -->
<header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 shrink-0 flex items-center px-6 gap-4">
    <!-- Breadcrumbs -->
    <nav aria-label="Điều hướng trang" class="flex-1 min-w-0">
        <ol class="flex items-center gap-1.5 text-sm">
            <li>
                <a href="/" class="text-slate-400 hover:text-slate-600 transition-colors">Trang chủ</a>
            </li>
            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                    <li class="flex items-center gap-1.5 text-slate-400">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">chevron_right</span>
                        <?php if (isset($breadcrumb['url'])): ?>
                            <a href="<?php echo $breadcrumb['url']; ?>" class="text-slate-500 hover:text-slate-700 transition-colors"><?php echo $breadcrumb['title']; ?></a>
                        <?php else: ?>
                            <span class="text-slate-900 font-semibold" aria-current="page"><?php echo $breadcrumb['title']; ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="flex items-center gap-1.5 text-slate-400">
                    <span class="material-symbols-outlined text-[14px]" aria-hidden="true">chevron_right</span>
                    <span class="text-slate-900 font-semibold"><?php echo isset($pageHeading) ? $pageHeading : 'Dashboard'; ?></span>
                </li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Right actions -->
    <div class="flex items-center gap-3 shrink-0">
        <!-- Search -->
        <div class="relative hidden sm:block">
            <span class="material-symbols-outlined text-[18px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true">search</span>
            <input type="search"
                id="navbarSearch"
                autocomplete="off"
                aria-label="Tìm kiếm sự kiện"
                aria-expanded="false"
                aria-controls="navbarSearchDropdown"
                placeholder="Tìm kiếm sự kiện…"
                class="w-64 pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400
                          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:border-primary
                          transition-colors" />
            <!-- Search dropdown -->
            <div id="navbarSearchDropdown"
                role="listbox"
                aria-label="Kết quả tìm kiếm"
                aria-hidden="true"
                class="hidden absolute left-0 top-[calc(100%+8px)] w-80 bg-white rounded-2xl shadow-[0_8px_32px_-4px_rgba(0,0,0,0.12),0_2px_8px_-2px_rgba(0,0,0,0.06)] border border-slate-100 overflow-hidden z-50">
            </div>
        </div>

        <?php
        $navIsGuest  = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
        $navHoTen    = isset($_SESSION['hoTen'])    ? $_SESSION['hoTen']    : 'Tai khoan';
        $navIdLoaiTK = isset($_SESSION['idLoaiTK']) ? (int)$_SESSION['idLoaiTK'] : 0;
        $navIdTK     = isset($_SESSION['idTK']) ? (int)$_SESSION['idTK'] : 0;
        $navLoaiMap  = [1 => 'Quản trị viên', 2 => 'Giảng viên', 3 => 'Sinh viên'];
        $navLoaiLabel = $navLoaiMap[$navIdLoaiTK] ?? 'Người dùng';
        $navInitial  = mb_strtoupper(mb_substr($navHoTen, 0, 1, 'UTF-8'), 'UTF-8');
        ?>

        <!-- Notification bell -->
        <div class="relative" id="notificationRoot" data-guest="<?php echo $navIsGuest ? '1' : '0'; ?>">
            <button type="button"
                id="notificationBtn"
                aria-label="Thông báo"
                aria-haspopup="true"
                aria-expanded="false"
                class="flex items-center justify-center size-9 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100
                           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-colors">
                <span class="material-symbols-outlined text-xl">notifications</span>
            </button>
            <!-- Red dot -->
            <span id="notificationDot" class="hidden absolute top-1.5 right-1.5 size-2 bg-red-500 rounded-full ring-2 ring-white" aria-hidden="true"></span>
            <span id="notificationCountBadge" class="hidden absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold leading-[18px] text-center" aria-hidden="true"></span>

            <div id="notificationDropdown"
                class="hidden absolute right-0 top-[calc(100%+8px)] w-96 max-w-[90vw] bg-white rounded-2xl shadow-[0_8px_32px_-4px_rgba(0,0,0,0.12),0_2px_8px_-2px_rgba(0,0,0,0.06)] border border-slate-100 z-50 overflow-hidden"
                role="dialog"
                aria-label="Danh sách thông báo"
                aria-hidden="true">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Thong bao</p>
                        <p id="notificationSubtitle" class="text-xs text-slate-500">Dang tai du lieu...</p>
                    </div>
                    <button type="button" id="markAllReadBtn" class="text-xs font-semibold text-primary hover:text-primary/80 disabled:opacity-50 disabled:pointer-events-none">
                        Danh dau tat ca
                    </button>
                </div>
                <div id="notificationList" class="max-h-[420px] overflow-y-auto divide-y divide-slate-100">
                    <div class="px-4 py-6 text-sm text-slate-400 text-center">Dang tai thong bao...</div>
                </div>
                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 text-right">
                    <a href="/dashboard" class="text-xs font-semibold text-slate-600 hover:text-slate-800">Xem tong quan</a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-8 w-px bg-slate-200" aria-hidden="true"></div>

        <!-- User info / Guest -->
        <?php if ($navIsGuest): ?>
            <a href="/sign-in"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition-all hover:scale-102 active:opacity-85"
                style="background: linear-gradient(135deg, #d946ef, #9333ea);">
                <span class="material-symbols-outlined text-[16px]">login</span>
                Đăng nhập
            </a>
        <?php else: ?>
            <a href="/profile"
                class="flex items-center gap-2.5 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 rounded-xl p-1 transition-opacity"
                aria-label="Trang cá nhân của <?php echo htmlspecialchars($navHoTen); ?>">
                <div class="text-right hidden sm:block min-w-0">
                    <p class="text-sm font-semibold text-slate-800 leading-tight truncate max-w-[140px]">
                        <?php echo htmlspecialchars($navHoTen); ?>
                    </p>
                    <p class="text-xs text-slate-500 leading-tight truncate">
                        <?php echo $navLoaiLabel; ?>
                    </p>
                </div>
                <!-- Avatar -->
                <div class="size-9 rounded-full bg-gradient-to-br from-[#d946ef] to-[#9333ea] flex items-center justify-center text-white font-bold text-sm shrink-0 select-none">
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