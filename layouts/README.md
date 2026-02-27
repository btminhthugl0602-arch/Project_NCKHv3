# Layout System - ezManagement

## Cấu trúc Layout

Hệ thống layout đã được tách thành các file riêng biệt trong thư mục `layouts/`:

```
layouts/
├── header.php      - Phần <head> với meta tags, CSS
├── sidebar.php     - Sidebar navigation
├── navbar.php      - Top navigation bar
├── footer.php      - Footer
├── scripts.php     - JavaScript files
└── main_layout.php - Layout chính tổng hợp tất cả
```

## Cách sử dụng Layout

### 1. Cấu trúc cơ bản của một trang

```php
<?php
// Định nghĩa các biến cần thiết
$pageTitle = "Tiêu đề trang";
$currentPage = "dashboard"; // Để highlight menu trong sidebar
$pageHeading = "Tiêu đề hiển thị";
$breadcrumbs = [
    ['title' => 'Trang chủ', 'url' => '../index.php'],
    ['title' => 'Trang hiện tại']
];

// Optional: CSS và JS riêng cho trang
$pageCss = "custom.css";  // File trong assets/css/
$pageJs = "custom.js";    // File trong assets/js/

// Bắt đầu output buffering
ob_start();
?>

<!-- Nội dung HTML của trang -->
<div class="w-full px-6 py-6 mx-auto">
    <h1>Nội dung trang</h1>
</div>

<?php
// Lưu nội dung
$content = ob_get_clean();

// Include layout
include '../layouts/main_layout.php';
?>
```

### 2. Các biến có thể sử dụng

#### Biến bắt buộc:

- `$content` - Nội dung chính của trang (được tạo bằng output buffering)

#### Biến tùy chọn:

- `$pageTitle` - Tiêu đề trang (hiển thị trên tab trình duyệt)
- `$currentPage` - Tên trang hiện tại (để highlight menu trong sidebar)
- `$pageHeading` - Tiêu đề hiển thị trên trang
- `$breadcrumbs` - Mảng breadcrumb navigation
- `$pageCss` - Tên file CSS riêng cho trang (trong assets/css/)
- `$pageJs` - Tên file JavaScript riêng cho trang (trong assets/js/)
- `$baseUrl` - URL gốc của ứng dụng (mặc định: '../')

### 3. Breadcrumbs

```php
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Sự kiện', 'url' => '../views/events.php'],
    ['title' => 'Chi tiết sự kiện'] // Trang hiện tại không cần URL
];
```

### 4. Menu hiện tại (Current Page)

Các giá trị `$currentPage` để highlight menu:

- `dashboard` - Dashboard
- `events` - Quản lý sự kiện
- `groups` - Quản lý nhóm (Sinh viên)
- `review` - Bình duyệt (Giảng viên)
- `profile` - Hồ sơ

### 5. Sidebar theo vai trò

Sidebar tự động hiển thị menu phù hợp dựa trên `$_SESSION['user_role']`:

- **Sinh viên (`student`)**: Dashboard, Sự kiện, Nhóm của tôi, Hồ sơ
- **Giảng viên (`lecturer`)**: Dashboard, Sự kiện, Bình duyệt, Hồ sơ
- **Admin (`admin`)**: Tất cả các menu

## Ví dụ thực tế

Xem file `views/example_page.php` để biết ví dụ chi tiết.

### Trang Dashboard đơn giản

```php
<?php
$pageTitle = "Dashboard - ezManagement";
$currentPage = "dashboard";
$pageHeading = "Dashboard";
$breadcrumbs = [['title' => 'Dashboard']];

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-wrap -mx-3">
        <div class="w-full px-3">
            <div class="p-6 bg-white rounded-2xl shadow-soft-xl">
                <h2 class="mb-4 text-xl font-bold">Chào mừng đến với ezManagement</h2>
                <p>Hệ thống quản lý hội thảo nghiên cứu khoa học</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>
```

### Trang với CSS/JS riêng

```php
<?php
$pageTitle = "Danh sách sự kiện";
$currentPage = "events";
$pageHeading = "Quản lý Sự kiện";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Sự kiện']
];
$pageCss = "events.css";
$pageJs = "events.js";

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <!-- Nội dung trang -->
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>
```

## Lưu ý quan trọng

1. **Output Buffering**: Luôn sử dụng `ob_start()` và `ob_get_clean()` để capture nội dung trang
2. **Base URL**: Điều chỉnh `$baseUrl` nếu cấu trúc thư mục khác
3. **Session**: Layout tự động kiểm tra và bắt đầu session
4. **Path**: Đảm bảo đường dẫn include đúng với vị trí file của bạn

## Tùy chỉnh Layout

### Thay đổi Sidebar

Chỉnh sửa file `layouts/sidebar.php` để thêm/bớt menu items

### Thay đổi Navbar

Chỉnh sửa file `layouts/navbar.php` để tùy chỉnh top navigation

### Thay đổi Footer

Chỉnh sửa file `layouts/footer.php` để thay đổi nội dung footer

### Thêm CSS/JS chung

Chỉnh sửa `layouts/header.php` (CSS) hoặc `layouts/scripts.php` (JS)

## Troubleshooting

### Lỗi "Headers already sent"

- Đảm bảo không có output trước khi gọi `ob_start()`
- Kiểm tra không có khoảng trắng/BOM trước `<?php`

### CSS/JS không load

- Kiểm tra đường dẫn `$baseUrl`
- Xác nhận file tồn tại trong thư mục assets

### Menu không highlight

- Kiểm tra giá trị `$currentPage` có khớp với điều kiện trong sidebar không
