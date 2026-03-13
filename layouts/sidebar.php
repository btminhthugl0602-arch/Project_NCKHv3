<?php
$useEventSidebar     = isset($useEventSidebar)     && $useEventSidebar === true;
$eventSidebarEventId = isset($eventSidebarEventId) ? (int)    $eventSidebarEventId : 0;
$eventSidebarSection = isset($eventSidebarSection) ? (string) $eventSidebarSection : 'overview';
$_sbTabAccess        = isset($eventSidebarTabAccess) && is_array($eventSidebarTabAccess) ? $eventSidebarTabAccess : [];

function _sb_link(string $section, string $current, int $idSk, string $icon, string $label): string
{
    $isActive    = $section === $current;
    $activeClass = $isActive
        ? 'bg-primary/5 text-primary border-l-2 border-primary font-semibold'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
    $iconClass   = $isActive ? 'text-primary active-icon' : 'text-slate-400';
    $ariaCurrent = $isActive ? 'page' : 'false';
    $href        = "/event-detail?id_sk={$idSk}&amp;tab={$section}";
    return <<<HTML
<a href="{$href}"
   class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors {$activeClass}"
   aria-current="{$ariaCurrent}">
    <span class="material-symbols-outlined text-[18px] shrink-0 {$iconClass}">{$icon}</span>
    <span class="text-sm">{$label}</span>
</a>
HTML;
}

function _sb_section_label(string $label): string
{
    return "<p class='px-4 pt-4 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider'>{$label}</p>";
}

function _sb_link_if(array $tabAccess, string $section, string $current, int $idSk, string $icon, string $label): string
{
    if (empty($tabAccess[$section])) return '';
    return _sb_link($section, $current, $idSk, $icon, $label);
}
?>
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

    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto" aria-label="Điều hướng chính">

        <?php if ($useEventSidebar): ?>

            <!-- Event Card -->
            <div class="mb-4 bg-gradient-to-br from-[#d946ef] to-[#9333ea] rounded-xl p-4 text-white shadow-lg shadow-purple-500/20">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest opacity-80">Sự kiện hiện tại</span>
                    <a href="/events"
                        class="flex items-center justify-center size-6 rounded-md bg-white/20 hover:bg-white/30 transition-colors shrink-0"
                        title="Quay lại danh sách sự kiện">
                        <span class="material-symbols-outlined text-sm">swap_horiz</span>
                    </a>
                </div>
                <p id="sidebarEventName" class="font-bold text-sm leading-snug mb-3 truncate">Đang tải…</p>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-xs font-medium">Đang diễn ra</span>
                </div>
            </div>

            <!-- Tổng quan — tất cả đều thấy -->
            <?php echo _sb_link('overview', $eventSidebarSection, $eventSidebarEventId, 'dashboard', 'Tổng quan'); ?>

            <?php $evSbIsGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest'; ?>
            <?php if (!$evSbIsGuest): ?>

                <?php
                $hasCauhinh = !empty($_sbTabAccess['config-basic'])
                           || !empty($_sbTabAccess['config-vongthi'])
                           || !empty($_sbTabAccess['config-tailieu'])
                           || !empty($_sbTabAccess['config-rules'])
                           || !empty($_sbTabAccess['config-criteria']);
                if ($hasCauhinh):
                ?>
                <?php echo _sb_section_label('Cấu hình sự kiện'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'config-basic',    $eventSidebarSection, $eventSidebarEventId, 'tune',        'Cấu hình cơ bản'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'config-vongthi',  $eventSidebarSection, $eventSidebarEventId, 'flag',        'Cấu hình vòng thi'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'config-tailieu',  $eventSidebarSection, $eventSidebarEventId, 'folder_open', 'Cấu hình tài liệu'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'config-rules',    $eventSidebarSection, $eventSidebarEventId, 'gavel',       'Cấu hình quy chế'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'config-criteria', $eventSidebarSection, $eventSidebarEventId, 'checklist',   'Thiết lập bộ tiêu chí'); ?>
                <?php endif; ?>

                <?php
                $hasBaiNop = !empty($_sbTabAccess['review-assign'])
                          || !empty($_sbTabAccess['review-results']);
                if ($hasBaiNop):
                ?>
                <?php echo _sb_section_label('Quản lý bài nộp'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'review-assign',  $eventSidebarSection, $eventSidebarEventId, 'person_check', 'Phân công phản biện'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'review-results', $eventSidebarSection, $eventSidebarEventId, 'bar_chart',    'Kết quả Review'); ?>
                <?php endif; ?>

                <?php
                $hasChamThi = !empty($_sbTabAccess['scoring'])
                           || !empty($_sbTabAccess['scoring-gv']);
                if ($hasChamThi):
                ?>
                <?php echo _sb_section_label('Nghiệp vụ chấm thi'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'scoring',    $eventSidebarSection, $eventSidebarEventId, 'edit_note',    'Quản lý & Duyệt điểm'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'scoring-gv', $eventSidebarSection, $eventSidebarEventId, 'rate_review',  'Chấm điểm'); ?>
                <?php endif; ?>

                <?php
                $hasNhom = !empty($_sbTabAccess['nhom-my'])
                        || !empty($_sbTabAccess['nhom-all'])
                        || !empty($_sbTabAccess['nhom-request']);
                if ($hasNhom):
                ?>
                <?php echo _sb_section_label('Nhóm thi'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'nhom-my',      $eventSidebarSection, $eventSidebarEventId, 'group',  'Nhóm của tôi'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'nhom-all',     $eventSidebarSection, $eventSidebarEventId, 'groups', 'Tất cả nhóm'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'nhom-request', $eventSidebarSection, $eventSidebarEventId, 'mail',   'Lời mời'); ?>
                <?php endif; ?>

                <?php
                $hasLichTrinh = !empty($_sbTabAccess['lichtrinh'])
                             || !empty($_sbTabAccess['lichtrinh-sv']);
                if ($hasLichTrinh):
                ?>
                <?php echo _sb_section_label('Lịch trình & Điểm danh'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'lichtrinh',    $eventSidebarSection, $eventSidebarEventId, 'calendar_month', 'Quản lý lịch trình'); ?>
                <?php echo _sb_link_if($_sbTabAccess, 'lichtrinh-sv', $eventSidebarSection, $eventSidebarEventId, 'event_note',     'Lịch trình & Điểm danh'); ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="mx-3 mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <p class="text-xs text-slate-500 mb-2">Đăng nhập để xem đầy đủ tính năng của sự kiện.</p>
                    <a href="/sign-in"
                        class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:scale-102"
                        style="background: linear-gradient(135deg, #d946ef, #9333ea);">
                        <span class="material-symbols-outlined text-[14px]">login</span>
                        Đăng nhập ngay
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <!-- Standard nav -->
            <?php $sbIsGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest'; ?>

            <a href="/dashboard"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'dashboard' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <span class="material-symbols-outlined text-[18px] shrink-0">dashboard</span>
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="/events"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'events' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <span class="material-symbols-outlined text-[18px] shrink-0">event</span>
                <span class="text-sm">Sự kiện</span>
            </a>

            <?php if (!$sbIsGuest): ?>

                <?php if (isset($_SESSION['idLoaiTK']) && (int)$_SESSION['idLoaiTK'] === 3): ?>
                    <a href="/groups"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'groups' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                        <span class="material-symbols-outlined text-[18px] shrink-0">group</span>
                        <span class="text-sm">Nhóm của tôi</span>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['idLoaiTK']) && in_array((int)$_SESSION['idLoaiTK'], [1, 2], true)): ?>
                    <a href="/review"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'review' ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                        <span class="material-symbols-outlined text-[18px] shrink-0">rate_review</span>
                        <span class="text-sm">Bình duyệt</span>
                    </a>
                <?php endif; ?>

                <?php
                $canManageUsers = false;
                if (isset($_SESSION['idTK']) && (int)$_SESSION['idTK'] > 0) {
                    if (!isset($conn) || !($conn instanceof PDO)) {
                        if (!defined('_AUTHEN')) define('_AUTHEN', true);
                        require_once __DIR__ . '/../api/core/base.php';
                    }
                    if (function_exists('kiem_tra_quyen_he_thong')) {
                        $canManageUsers = kiem_tra_quyen_he_thong($conn, (int)$_SESSION['idTK'], 'quan_ly_tai_khoan');
                    }
                }
                if ($canManageUsers): ?>
                    <?php echo _sb_section_label('Hệ thống'); ?>
                    <a href="/admin_users"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors <?php echo isset($currentPage) && $currentPage === 'admin-users' ? 'bg-primary/5 text-primary border-l-2 border-primary font-semibold' : 'text-slate-600 hover:bg-slate-50 border-l-2 border-transparent'; ?>">
                        <span class="material-symbols-outlined text-[18px] shrink-0 <?php echo isset($currentPage) && $currentPage === 'admin-users' ? 'text-primary active-icon' : 'text-slate-400'; ?>">manage_accounts</span>
                        <span class="text-sm">Quản lý Tài khoản</span>
                    </a>
                <?php endif; ?>

                <?php echo _sb_section_label('Tài khoản'); ?>
                <a href="/profile"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors text-slate-600 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px] shrink-0">person</span>
                    <span class="text-sm">Hồ sơ</span>
                </a>
                <a href="/api/tai_khoan/sign-in.php" id="logoutBtn"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors text-rose-500 hover:bg-rose-50 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px] shrink-0">logout</span>
                    <span class="text-sm">Đăng xuất</span>
                </a>
                <script>
                    document.getElementById('logoutBtn').addEventListener('click', function(e) {
                        e.preventDefault();
                        fetch('/api/tai_khoan/sign-in.php', { method: 'DELETE', credentials: 'same-origin' })
                            .finally(function() { window.location.href = '/sign-in'; });
                    });
                </script>

            <?php else: ?>
                <?php echo _sb_section_label('Tài khoản'); ?>
                <a href="/sign-in"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors text-primary hover:bg-primary/5 font-semibold">
                    <span class="material-symbols-outlined text-[18px] shrink-0">login</span>
                    <span class="text-sm">Đăng nhập</span>
                </a>
            <?php endif; ?>

        <?php endif; ?>
    </nav>
</aside>
