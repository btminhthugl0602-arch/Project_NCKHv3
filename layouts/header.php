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
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $basePath; ?>/assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="<?php echo $basePath; ?>/assets/img/favicon.png" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'ezManagement - Hệ Thống Quản Lý Hội Thảo'; ?></title>
    
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
    <!-- Nucleo Icons -->
    <link href="<?php echo $basePath; ?>/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?php echo $basePath; ?>/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Popper -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Main Styling -->
    <link href="<?php echo $basePath; ?>/assets/css/soft-ui-dashboard-tailwind.css?v=1.0.5" rel="stylesheet" />
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Page Specific CSS -->
    <?php if (isset($pageCss)): ?>
        <link href="<?php echo $basePath; ?>/assets/css/<?php echo $pageCss; ?>" rel="stylesheet" />
    <?php endif; ?>
</head>
<body class="m-0 font-sans text-base antialiased font-normal leading-default bg-gray-50 text-slate-500">
