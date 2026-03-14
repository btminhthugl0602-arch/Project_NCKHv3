<?php

/**
 * layouts/header.php
 * Global <head> + opening <body> cho toàn bộ app.
 *
 * REBUILT: Burgundy + Sand palette — đồng bộ layout.css
 * - primary: #7d1f2e (Burgundy)
 * - Bỏ nucleo-icons.css / nucleo-svg.css
 * - Font Awesome giữ tạm (một số view cũ vẫn dùng fa-*)
 * - Duplicate .material-symbols-outlined style đã chuyển hết vào layout.css
 */

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (!isset($basePath)) {
    $basePath = dirname(dirname($scriptName));
    if ($basePath === '\\' || $basePath === '/' || $basePath === '.') {
        $basePath = '';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $basePath; ?>/assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo $basePath; ?>/assets/img/favicon.png" />
    <title>
        <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'ezManagement — Hệ Thống Quản Lý Sự Kiện Học Thuật'; ?>
    </title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        /* ── Primary palette ── */
                        'primary': '#7d1f2e',
                        'primary-dark': '#621828',
                        'primary-light': '#f9edef',

                        /* ── Accent palette ── */
                        'accent': '#9a6c2e',
                        'accent-light': '#fdf6ec',

                        /* ── Surface / background ── */
                        'background-light': '#f7f6f5',
                        'sand': '#f5f0e8',
                        'sand-border': '#ede7da',

                        /* ── Legacy (giữ để không vỡ view cũ) ── */
                        'background-dark': '#1a1022',
                    },
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif'],
                        display: ['Manrope', 'sans-serif'],
                    },
                    borderRadius: {
                        DEFAULT: '0.25rem',
                        lg: '0.5rem',
                        xl: '0.75rem',
                        '2xl': '1rem',
                        full: '9999px',
                    },
                },
            },
        }
    </script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    <!-- Font Awesome — giữ tạm, xóa sau khi migrate hết view cũ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        crossorigin="anonymous" />

    <!-- Global Layout CSS + Design Tokens -->
    <link href="<?php echo $basePath; ?>/assets/css/layout.css" rel="stylesheet" />

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

    <!-- Page-specific CSS -->
    <?php if (isset($pageCss)): ?>
        <link href="<?php echo $basePath; ?>/assets/css/<?php echo htmlspecialchars($pageCss); ?>" rel="stylesheet" />
    <?php endif; ?>
</head>

<body class="bg-background-light text-slate-900 antialiased" style="font-family:'Manrope',sans-serif;">