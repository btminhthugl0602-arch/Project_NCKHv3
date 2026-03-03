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

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto" aria-label="Điều hướng chính">

        <?php if ($useEventSidebar): ?>

        <!-- Current Event Card -->
        <div class="mb-4 bg-gradient-to-br from-[#d946ef] to-[#9333ea] rounded-xl p-4 text-white shadow-lg shadow-purple-500/20">
            <div class="flex items-start justify-between gap-2 mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest opacity-80">Sự kiện hiện tại</span>
                <a href="/events"
                   class="flex items-center justify-center size-6 rounded-md bg-white/20 hover:bg-white/30 transition-colors shrink-0"
                   title="Quay lại danh sách sự kiện"
                   aria-label="Quay lại danh sách sự kiện">
                    <span class="material-symbols-outlined text-sm">swap_horiz</span>
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
