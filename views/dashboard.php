<?php

/**
 * Dashboard Page
 * views/dashboard.php
 *
 * REBUILT: Burgundy + Sand palette — theo mockup Stitch
 * - 4 stat cards hàng đầu
 * - Cột trái: thông báo mới (load từ JS)
 * - Cột phải: welcome card + sự kiện sắp tới
 * - Bỏ: ni-* icons, shadow-soft-xl, hard-code gradient tím
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$_isGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
if (!isset($_SESSION['idTK']) && !$_isGuest) {
    header('Location: /sign-in');
    exit;
}

$pageTitle   = 'Dashboard — ezManagement';
$currentPage = 'dashboard';
$pageHeading = 'Dashboard';
$pageJs      = 'dashboard-notifications.js';
$breadcrumbs = [['title' => 'Dashboard']];

ob_start();
?>

<div class="w-full px-4 sm:px-6 py-4 sm:py-6 mx-auto max-w-screen-xl">

    <!-- ── Row 1: Stat cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">

        <?php
        $stats = [
            ['label' => 'Tổng sự kiện',   'id' => 'statSuKien',  'icon' => 'event',          'delta' => '+12%', 'up' => true],
            ['label' => 'Nhóm tham gia',  'id' => 'statNhom',    'icon' => 'groups',          'delta' => '+5%',  'up' => true],
            ['label' => 'Bài báo nộp',    'id' => 'statBaiBao',  'icon' => 'description',     'delta' => '-2%',  'up' => false],
            ['label' => 'Đánh giá',       'id' => 'statDanhGia', 'icon' => 'rate_review',     'delta' => '+8%',  'up' => true],
        ];
        foreach ($stats as $s):
            $deltaColor = $s['up'] ? 'text-emerald-600 bg-emerald-50' : 'text-red-500 bg-red-50';
            $deltaIcon  = $s['up'] ? 'trending_up' : 'trending_down';
        ?>
            <div class="bg-white rounded-xl border border-slate-100 p-4 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-500 leading-tight"><?php echo $s['label']; ?></p>
                    <div class="size-9 rounded-lg flex items-center justify-center shrink-0 bg-primary-light">
                        <span class="material-symbols-outlined text-[18px] text-primary"
                            aria-hidden="true"><?php echo $s['icon']; ?></span>
                    </div>
                </div>
                <p id="<?php echo $s['id']; ?>" class="text-2xl font-bold text-slate-800 leading-none" aria-live="polite">—
                </p>
                <span
                    class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full w-fit <?php echo $deltaColor; ?>">
                    <span class="material-symbols-outlined text-[13px]" aria-hidden="true"><?php echo $deltaIcon; ?></span>
                    <?php echo $s['delta']; ?>
                </span>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- ── Row 2: Thông báo + Cột phải ── -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] xl:grid-cols-[1fr_340px] gap-4">

        <!-- Thông báo mới -->
        <div class="bg-white rounded-xl border border-slate-100 flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800">Thông báo mới</h2>
                <a href="/dashboard"
                    class="text-xs font-semibold text-primary hover:opacity-75 transition-opacity focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded">
                    Xem tất cả
                </a>
            </div>
            <div id="dashboardNotificationList" class="flex-1 divide-y divide-slate-50" aria-live="polite"
                aria-atomic="false" aria-label="Danh sách thông báo mới">
                <!-- Skeleton loading -->
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="flex items-start gap-3 px-5 py-4">
                        <div class="size-9 rounded-full bg-slate-100 shrink-0 nss-shimmer"></div>
                        <div class="flex-1 min-w-0 space-y-2">
                            <div class="h-3 bg-slate-100 rounded w-3/4 nss-shimmer"></div>
                            <div class="h-2.5 bg-slate-100 rounded w-1/2 nss-shimmer"></div>
                        </div>
                        <div class="h-2.5 bg-slate-100 rounded w-14 shrink-0 nss-shimmer"></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Cột phải -->
        <div class="flex flex-col gap-4">

            <!-- Welcome card -->
            <div class="rounded-xl overflow-hidden relative min-h-[200px] flex flex-col justify-end p-5 bg-primary"
                role="complementary" aria-label="Giới thiệu ezManagement">
                <!-- Decorative shapes -->
                <div class="absolute top-4 right-4 size-16 rounded-full opacity-10 bg-white" aria-hidden="true"></div>
                <div class="absolute top-10 right-10 size-8 rounded-full opacity-10 bg-white" aria-hidden="true"></div>
                <div class="absolute -top-2 right-16 size-10 rotate-12 opacity-10 bg-white"
                    style="clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%)" aria-hidden="true"></div>
                <!-- Content -->
                <div class="relative z-10">
                    <h2 class="text-lg font-bold text-white leading-snug mb-2">
                        Chào mừng đến với<br>ezManagement
                    </h2>
                    <p class="text-sm text-white/80 leading-relaxed mb-4">
                        Giải pháp toàn diện giúp quản lý các sự kiện học thuật, hội nghị và công trình nghiên cứu một
                        cách chuyên nghiệp và hiệu quả nhất.
                    </p>
                    <a href="/events"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-lg text-sm font-semibold text-primary hover:bg-slate-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                        Tìm hiểu thêm
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Sự kiện sắp tới -->
            <div class="bg-white rounded-xl border border-slate-100 flex flex-col">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-100">
                    <span class="material-symbols-outlined text-[16px] text-primary"
                        aria-hidden="true">calendar_month</span>
                    <h2 class="text-sm font-bold text-slate-800">Sự kiện sắp tới</h2>
                </div>
                <div id="dashboardUpcomingEvents" class="divide-y divide-slate-50" aria-live="polite"
                    aria-label="Danh sách sự kiện sắp tới">
                    <!-- Skeleton -->
                    <?php for ($i = 0; $i < 2; $i++): ?>
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex flex-col items-center justify-center w-10 shrink-0">
                                <div class="h-2.5 bg-slate-100 rounded w-8 mb-1 nss-shimmer"></div>
                                <div class="h-5 bg-slate-100 rounded w-7 nss-shimmer"></div>
                            </div>
                            <div class="flex-1 min-w-0 space-y-1.5">
                                <div class="h-3 bg-slate-100 rounded w-3/4 nss-shimmer"></div>
                                <div class="h-2.5 bg-slate-100 rounded w-1/2 nss-shimmer"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>