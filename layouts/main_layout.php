<?php

/**
 * Main Layout Template
 * 
 * Sử dụng layout này bằng cách:
 * 1. Định nghĩa các biến cần thiết ($pageTitle, $currentPage, $pageHeading, etc.)
 * 2. Bắt đầu output buffering cho nội dung trang
 * 3. Include file này để hiển thị layout hoàn chỉnh
 * 
 * Ví dụ sử dụng:
 * 
 * <?php
 * // Định nghĩa các biến
 * $pageTitle = "Dashboard";
 * $currentPage = "dashboard";
 * $pageHeading = "Dashboard";
 * $breadcrumbs = [['title' => 'Dashboard']];
 * $pageCss = "custom-dashboard.css"; // Optional
 * $pageJs = "custom-dashboard.js";   // Optional
 * 
 * // Bắt đầu output buffering cho nội dung
 * ob_start();
 * ?>
 *
 *
<!-- Nội dung HTML của trang ở đây -->
 * <div class="w-full px-6 py-6 mx-auto">
 * <h1>Nội dung trang</h1>
 * </div>
 *
 * <?php
 * // Lưu nội dung vào biến
 * $content = ob_get_clean();
 * 
 * // Include main layout
 * include '../layouts/main_layout.php';
 * ?>
 */

// Thiết lập base URL (điều chỉnh theo cấu trúc thư mục của bạn)
if (!isset($baseUrl)) {
    // Xác định baseUrl dựa trên vị trí file
    $baseUrl = (strpos($_SERVER['REQUEST_URI'], '/views/') !== false) ? '../' : '/';
}

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem người dùng đã đăng nhập chưa (có thể bỏ comment nếu cần)
// if (!isset($_SESSION['idTK'])) {
// header('Location: ' . $baseUrl . 'views/sign-in.php');
// exit();
// }

// Include header
include __DIR__ . '/header.php';
?>

<div class="flex h-screen overflow-hidden">

    <!-- Mobile backdrop -->
    <button type="button" id="sidebarBackdrop"
        class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden w-full cursor-default" aria-label="Đóng menu điều hướng"
        tabindex="-1" onclick="toggleSidebar(false)"
        onkeydown="if(event.key==='Enter'||event.key===' '){toggleSidebar(false);}"></button>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-y-auto bg-background-light lg:ml-0" style="min-width:0">
        <!-- Navbar / Top Header Bar -->
        <?php include __DIR__ . '/navbar.php'; ?>

        <!-- Page Content -->
        <div class="flex-1">
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php else: ?>
                <div class="p-8">
                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <p class="text-slate-500">Nội dung trang chưa được định nghĩa. Vui lòng sử dụng output buffering để
                            định nghĩa biến $content trước khi include layout.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <?php include __DIR__ . '/footer.php'; ?>
    </main>
</div><!-- end flex wrapper -->

<!-- Scripts -->
<?php include __DIR__ . '/scripts.php'; ?>

<script>
    (function() {
        var sidebar = document.getElementById('mainSidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var hamburger = document.getElementById('hamburgerBtn');

        window.toggleSidebar = function(forceOpen) {
            if (!sidebar) return;
            var isOpen = !sidebar.classList.contains('-translate-x-full');
            var open = forceOpen !== undefined ? forceOpen : !isOpen;

            if (open) {
                sidebar.classList.remove('-translate-x-full');
                backdrop && backdrop.classList.remove('hidden');
                hamburger && hamburger.setAttribute('aria-expanded', 'true');
                sidebar.setAttribute('aria-modal', 'true');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop && backdrop.classList.add('hidden');
                hamburger && hamburger.setAttribute('aria-expanded', 'false');
                sidebar.removeAttribute('aria-modal');
            }
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') window.toggleSidebar(false);
        });

        /* Đóng sidebar khi click vào link trên mobile */
        if (sidebar) {
            sidebar.addEventListener('click', function(e) {
                if (window.innerWidth < 1024 && e.target.closest('a')) {
                    window.toggleSidebar(false);
                }
            });
        }

        /* Respect prefers-reduced-motion */
        var mq = window.matchMedia('(prefers-reduced-motion: reduce)');

        function applyMotion() {
            if (sidebar) sidebar.style.transitionDuration = mq.matches ? '0ms' : '';
        }
        applyMotion();
        mq.addEventListener && mq.addEventListener('change', applyMotion);
    }());
</script>