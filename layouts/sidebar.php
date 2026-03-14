<?php

/**
 * layouts/sidebar.php
 *
 * REBUILT: Burgundy + Sand palette — theo mockup Stitch
 * - Logo: hình vuông bo góc burgundy + chữ "ez"
 * - Active state: nền primary-light + viền trái primary
 * - Event sidebar: card sự kiện hiện tại
 * - Logic PHP: giữ nguyên 100%
 */

$useEventSidebar     = isset($useEventSidebar)     && $useEventSidebar === true;
$eventSidebarEventId = isset($eventSidebarEventId) ? (int)    $eventSidebarEventId : 0;
$eventSidebarSection = isset($eventSidebarSection) ? (string) $eventSidebarSection : 'overview';
$_sbTabAccess        = isset($eventSidebarTabAccess) && is_array($eventSidebarTabAccess) ? $eventSidebarTabAccess : [];

function _sb_link(string $section, string $current, int $idSk, string $icon, string $label): string
{
    $isActive    = $section === $current;
    $activeClass = $isActive
        ? 'bg-primary-light text-primary border-l-2 border-primary font-semibold'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
    $iconClass   = $isActive ? 'text-primary active-icon' : 'text-slate-400';
    $ariaCurrent = $isActive ? 'page' : 'false';
    $href        = "/event-detail?id_sk={$idSk}&amp;tab={$section}";
    return <<<HTML
<a href="{$href}"
   class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg transition-colors {$activeClass} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset"
   aria-current="{$ariaCurrent}">
    <span class="material-symbols-outlined text-[18px] shrink-0 {$iconClass}" aria-hidden="true">{$icon}</span>
    <span class="text-sm truncate min-w-0">{$label}</span>
</a>
HTML;
}

function _sb_section_label(string $label): string
{
    return "<p class='px-4 pt-4 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest'>{$label}</p>";
}

function _sb_link_if(array $tabAccess, string $section, string $current, int $idSk, string $icon, string $label): string
{
    if (empty($tabAccess[$section])) return '';
    return _sb_link($section, $current, $idSk, $icon, $label);
}
?>
<aside id="mainSidebar" class="w-64 bg-white border-r border-slate-100 flex flex-col shrink-0 h-screen
           fixed lg:sticky top-0 left-0 overflow-y-auto z-30
           -translate-x-full lg:translate-x-0
           transition-transform duration-300" aria-label="Điều hướng chính">
    <!-- Logo -->
    <div class="px-5 py-4 flex items-center gap-3 border-b border-slate-100 shrink-0">
        <a href="/dashboard"
            class="flex items-center gap-3 min-w-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded-lg"
            aria-label="Trang chủ ezManagement">
            <div class="size-9 rounded-xl flex items-center justify-center text-white shrink-0 select-none bg-primary">
                <span class="font-bold text-sm tracking-tight" aria-hidden="true">ez</span>
            </div>
            <div class="min-w-0">
                <p class="text-slate-900 font-bold text-sm leading-tight truncate">ezManagement</p>
                <p class="text-slate-400 text-[10px] font-semibold uppercase tracking-widest truncate">Academic Portal
                </p>
            </div>
        </a>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto" aria-label="Menu điều hướng">

        <?php if ($useEventSidebar): ?>

            <!-- Event card -->
            <div class="mb-3 rounded-xl p-3.5 text-white bg-gradient-to-br from-primary to-[#a8293d]">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest opacity-80">Sự kiện hiện tại</span>
                    <a href="/events"
                        class="flex items-center justify-center size-6 rounded-md bg-white/20 hover:bg-white/30 transition-opacity shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        title="Quay lại danh sách sự kiện" aria-label="Quay lại danh sách sự kiện">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">swap_horiz</span>
                    </a>
                </div>
                <p id="sidebarEventName" class="font-bold text-sm leading-snug mb-3 truncate" aria-live="polite">Đang tải…
                </p>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 bg-green-400 rounded-full" aria-hidden="true"></span>
                    <span class="text-xs font-medium">Đang diễn ra</span>
                </div>
            </div>

            <!-- Tổng quan -->
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
                    <?php echo _sb_link_if($_sbTabAccess, 'config-vongthi',  $eventSidebarSection, $eventSidebarEventId, 'flag',        'Cấu hình nhóm thi'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'config-tailieu',  $eventSidebarSection, $eventSidebarEventId, 'folder_open', 'Cấu hình tài liệu'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'config-rules',    $eventSidebarSection, $eventSidebarEventId, 'gavel',       'Cấu hình quy chế'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'config-criteria', $eventSidebarSection, $eventSidebarEventId, 'checklist',   'Thiết lập bộ tiêu chí'); ?>
                <?php endif; ?>

                <?php
                $hasBaiNop = !empty($_sbTabAccess['review-assign'])
                    || !empty($_sbTabAccess['review-results']);
                if ($hasBaiNop):
                ?>
                    <?php echo _sb_section_label('Phản biện & Kết quả'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'review-assign',  $eventSidebarSection, $eventSidebarEventId, 'person_check', 'Phân công phản biện'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'review-results', $eventSidebarSection, $eventSidebarEventId, 'bar_chart',    'Kết quả review'); ?>
                <?php endif; ?>

                <?php
                $hasTieuBan = !empty($_sbTabAccess['subcommittees'])
                    || !empty($_sbTabAccess['judges']);
                if ($hasTieuBan):
                ?>
                    <?php echo _sb_section_label('Tiểu ban & Hội đồng'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'subcommittees', $eventSidebarSection, $eventSidebarEventId, 'meeting_room',       'Quản lý tiểu ban'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'judges',        $eventSidebarSection, $eventSidebarEventId, 'supervisor_account', 'Phân công ban GK'); ?>
                <?php endif; ?>

                <?php
                $hasChamThi = !empty($_sbTabAccess['scoring'])
                    || !empty($_sbTabAccess['scoring-gv']);
                if ($hasChamThi):
                ?>
                    <?php echo _sb_section_label('Nghiệp vụ chấm thi'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'scoring',    $eventSidebarSection, $eventSidebarEventId, 'edit_note',   'Quản lý & duyệt điểm'); ?>
                    <?php echo _sb_link_if($_sbTabAccess, 'scoring-gv', $eventSidebarSection, $eventSidebarEventId, 'rate_review', 'Chấm điểm'); ?>
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
                    <?php echo _sb_link_if($_sbTabAccess, 'lichtrinh-sv', $eventSidebarSection, $eventSidebarEventId, 'event_note',     'Lịch trình & điểm danh'); ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="mx-1 mt-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-2">Đăng nhập để xem đầy đủ tính năng.</p>
                    <a href="/sign-in"
                        class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs font-semibold text-white rounded-lg hover:opacity-90 transition-opacity focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 bg-primary">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">login</span>
                        Đăng nhập ngay
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <!-- Standard nav -->
            <?php $sbIsGuest = isset($_SESSION['role']) && $_SESSION['role'] === 'guest'; ?>

            <?php
            $navItems = [
                ['href' => '/dashboard', 'page' => 'dashboard', 'icon' => 'dashboard',   'label' => 'Dashboard'],
                ['href' => '/events',    'page' => 'events',     'icon' => 'event',        'label' => 'Sự kiện'],
            ];
            foreach ($navItems as $item):
                $isActive = isset($currentPage) && $currentPage === $item['page'];
                $cls = $isActive
                    ? 'bg-primary-light text-primary border-l-2 border-primary font-semibold'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
                $iconCls = $isActive ? 'text-primary active-icon' : 'text-slate-400';
            ?>
                <a href="<?php echo $item['href']; ?>"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg transition-colors <?php echo $cls; ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset"
                    <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined text-[18px] shrink-0 <?php echo $iconCls; ?>"
                        aria-hidden="true"><?php echo $item['icon']; ?></span>
                    <span class="text-sm truncate min-w-0"><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>

            <?php if (!$sbIsGuest): ?>

                <?php if (isset($_SESSION['idLoaiTK']) && (int)$_SESSION['idLoaiTK'] === 3):
                    $isActive = isset($currentPage) && $currentPage === 'groups';
                    $cls = $isActive ? 'bg-primary-light text-primary border-l-2 border-primary font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
                    $iconCls = $isActive ? 'text-primary active-icon' : 'text-slate-400';
                ?>
                    <a href="/groups"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg transition-colors <?php echo $cls; ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset"
                        <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <span class="material-symbols-outlined text-[18px] shrink-0 <?php echo $iconCls; ?>"
                            aria-hidden="true">group</span>
                        <span class="text-sm truncate min-w-0">Nhóm của tôi</span>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['idLoaiTK']) && in_array((int)$_SESSION['idLoaiTK'], [1, 2], true)):
                    $isActive = isset($currentPage) && $currentPage === 'review';
                    $cls = $isActive ? 'bg-primary-light text-primary border-l-2 border-primary font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
                    $iconCls = $isActive ? 'text-primary active-icon' : 'text-slate-400';
                ?>
                    <a href="/review"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg transition-colors <?php echo $cls; ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset"
                        <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <span class="material-symbols-outlined text-[18px] shrink-0 <?php echo $iconCls; ?>"
                            aria-hidden="true">rate_review</span>
                        <span class="text-sm truncate min-w-0">Bình duyệt</span>
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
                if ($canManageUsers):
                    $isActive = isset($currentPage) && $currentPage === 'admin-users';
                    $cls = $isActive ? 'bg-primary-light text-primary border-l-2 border-primary font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-2 border-transparent';
                    $iconCls = $isActive ? 'text-primary active-icon' : 'text-slate-400';
                ?>
                    <?php echo _sb_section_label('Hệ thống'); ?>
                    <a href="/admin_users"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg transition-colors <?php echo $cls; ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset"
                        <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <span class="material-symbols-outlined text-[18px] shrink-0 <?php echo $iconCls; ?>"
                            aria-hidden="true">manage_accounts</span>
                        <span class="text-sm truncate min-w-0">Quản lý tài khoản</span>
                    </a>
                <?php endif; ?>

            <?php endif; ?>

        <?php endif; ?>
    </nav>

    <!-- Tài khoản — pinned bottom -->
    <div class="shrink-0 border-t border-slate-100 px-3 py-3 space-y-0.5">
        <?php echo _sb_section_label('Tài khoản'); ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'guest'): ?>
            <a href="/sign-in"
                class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg border-l-2 border-transparent font-semibold transition-colors hover:bg-primary-light text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset">
                <span class="material-symbols-outlined text-[18px] shrink-0" aria-hidden="true">login</span>
                <span class="text-sm">Đăng nhập</span>
            </a>
        <?php else: ?>
            <a href="/profile"
                class="flex items-center gap-3 px-4 py-2.5 rounded-r-lg border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset">
                <span class="material-symbols-outlined text-[18px] shrink-0 text-slate-400" aria-hidden="true">person</span>
                <span class="text-sm truncate min-w-0">Hồ sơ</span>
            </a>
            <button type="button" id="sidebarLogoutBtn"
                class="w-full flex items-center gap-3 px-4 py-2.5 rounded-r-lg border-l-2 border-transparent text-rose-500 hover:bg-rose-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50 focus-visible:ring-inset"
                aria-label="Đăng xuất khỏi hệ thống"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();doLogout();}"
                onclick="doLogout()">
                <span class="material-symbols-outlined text-[18px] shrink-0" aria-hidden="true">logout</span>
                <span class="text-sm">Đăng xuất</span>
            </button>
            <script>
                function doLogout() {
                    fetch('/api/tai_khoan/sign-in.php', {
                            method: 'DELETE',
                            credentials: 'same-origin'
                        })
                        .finally(() => {
                            window.location.href = '/sign-in';
                        });
                }
            </script>
        <?php endif; ?>
    </div>
</aside>