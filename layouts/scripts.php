<!-- Page Specific JavaScript -->
<?php if (isset($pageJs)): ?>
    <script src="/assets/js/<?php echo $pageJs; ?>"></script>
<?php endif; ?>

<!-- Plugin for charts -->
<script src="/assets/js/plugins/chartjs.min.js" async></script>
<!-- Plugin for scrollbar -->
<script src="/assets/js/plugins/perfect-scrollbar.min.js" async></script>
<!-- Main script file -->
<script src="/assets/js/soft-ui-dashboard-tailwind.js?v=1.0.5" async></script>
</body>
</html>

<script>
// ── Giữ vị trí scroll của sidebar khi chuyển trang ──
(function () {
    var STORAGE_KEY = 'sidebar_scroll';
    var sidebar = document.querySelector('.h-sidenav');
    if (!sidebar) return;

    // Khôi phục vị trí
    var saved = sessionStorage.getItem(STORAGE_KEY);
    if (saved) sidebar.scrollTop = parseInt(saved, 10);

    // Lưu trước khi rời trang
    document.addEventListener('click', function (e) {
        var link = e.target.closest('aside a');
        if (link) sessionStorage.setItem(STORAGE_KEY, sidebar.scrollTop);
    });
})();
</script>