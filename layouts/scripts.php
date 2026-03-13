<!-- Page Specific JavaScript -->
<?php if (isset($pageJs)): ?>
    <script src="<?php echo $basePath; ?>/assets/js/<?php echo $pageJs; ?>?v=<?php echo filemtime(__DIR__ . '/../assets/js/' . $pageJs) ?: time(); ?>"></script>
<?php endif; ?>

<!-- Navbar Search (global — load on every page) -->
<?php $navSearchFile = __DIR__ . '/../assets/js/navbar-search.js'; ?>
<script src="<?php echo $basePath; ?>/assets/js/navbar-search.js?v=<?php echo filemtime($navSearchFile) ?: time(); ?>"></script>

<!-- Notifications (global inbox bell) -->
<?php $notificationFile = __DIR__ . '/../assets/js/notifications.js'; ?>
<script src="<?php echo $basePath; ?>/assets/js/notifications.js?v=<?php echo filemtime($notificationFile) ?: time(); ?>"></script>

<!-- Plugin for charts -->
<script src="<?php echo $basePath; ?>/assets/js/plugins/chartjs.min.js" defer></script>
<!-- Plugin for scrollbar -->
<script src="<?php echo $basePath; ?>/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
</body>
</html>
