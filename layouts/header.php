<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="/assets/img/favicon.png" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'ezManagement - Hệ Thống Quản Lý Hội Thảo'; ?></title>
    
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Nucleo Icons -->
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Popper -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Main Styling -->
    <link href="/assets/css/soft-ui-dashboard-tailwind.css?v=1.0.5" rel="stylesheet" />
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Page Specific CSS -->
    <?php if (isset($pageCss)): ?>
        <link href="/assets/css/<?php echo $pageCss; ?>" rel="stylesheet" />
    <?php endif; ?>
</head>
<body class="m-0 font-sans text-base antialiased font-normal leading-default bg-gray-50 text-slate-500">
