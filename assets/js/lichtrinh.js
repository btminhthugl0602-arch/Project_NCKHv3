/**
 * lichtrinh.js
 * Được load khi tab === 'lichtrinh' hoặc 'lichtrinh-sv'.
 * Logic chính đã được nhúng inline trong tab-lichtrinh.php và tab-lichtrinh-sv.php
 * vì mỗi tab có IIFE riêng (LT, LTSV) với full state management.
 *
 * File này đóng vai trò:
 * 1. Entry point cho router trong event-detail.js (để load JS đúng tab)
 * 2. Khởi động đúng module tương ứng với tab hiện tại
 * 3. Cung cấp shared utilities nếu cần dùng ở nhiều nơi
 */

(function () {
    'use strict';

    const TAB = window.EVENT_DETAIL_TAB || '';

    function boot() {
        if (TAB === 'lichtrinh' && typeof LT !== 'undefined') {
            LT.load();
        } else if (TAB === 'lichtrinh-sv' && typeof LTSV !== 'undefined') {
            LTSV.load();
        }
    }

    // Nếu DOM đã sẵn sàng thì boot ngay, không thì đợi
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        // Đợi một tick để inline scripts trong partial kịp define LT / LTSV
        setTimeout(boot, 0);
    }
})();
