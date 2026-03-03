<?php
// Calculate base path for assets
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
    <title><?php echo isset($pageTitle) ? $pageTitle : 'ezManagement - Hệ Thống Quản Lý Hội Thảo'; ?></title>

    <!-- Tailwind CSS + Custom Design Tokens -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#9213ec",
                        "background-light": "#f7f6f8",
                        "background-dark": "#1a1022",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>

    <!-- Manrope Font -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <!-- Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <!-- Font Awesome (backward compat) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
    <!-- Nucleo Icons -->
    <link href="<?php echo $basePath; ?>/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo $basePath; ?>/assets/css/nucleo-svg.css" rel="stylesheet" />

    <!-- Global Layout CSS -->
    <link href="<?php echo $basePath; ?>/assets/css/layout.css" rel="stylesheet" />

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global styles -->
    <style>
        * { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-family: 'Material Symbols Outlined'; }
        .active-icon { font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
    </style>

    <!-- Page Specific CSS -->
    <?php if (isset($pageCss)): ?>
        <link href="<?php echo $basePath; ?>/assets/css/<?php echo $pageCss; ?>" rel="stylesheet" />
    <?php endif; ?>
</head>
<body class="bg-background-light text-slate-900 antialiased">
