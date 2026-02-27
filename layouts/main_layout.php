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
 * <!-- Nội dung HTML của trang ở đây -->
 * <div class="w-full px-6 py-6 mx-auto">
 *     <h1>Nội dung trang</h1>
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
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ' . $baseUrl . 'views/sign-in.php');
//     exit();
// }

// Include header
include __DIR__ . '/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- Main Content -->
<main class="ease-soft-in-out xl:ml-68.5 relative h-full max-h-screen rounded-xl transition-all duration-200">
    <!-- Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <!-- Page Content -->
    <?php if (isset($content)): ?>
        <?php echo $content; ?>
    <?php else: ?>
        <div class="w-full px-6 py-6 mx-auto">
            <div class="flex flex-wrap -mx-3">
                <div class="w-full px-3">
                    <div class="p-6 bg-white rounded-lg shadow-soft-xl">
                        <p class="text-slate-500">Nội dung trang chưa được định nghĩa. Vui lòng sử dụng output buffering để định nghĩa biến $content trước khi include layout.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <?php include __DIR__ . '/footer.php'; ?>
</main>

<!-- Scripts -->
<?php include __DIR__ . '/scripts.php'; ?>
