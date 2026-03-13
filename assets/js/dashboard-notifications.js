(function () {
    'use strict';

    const container = document.getElementById('dashboardNotificationList');
    const countEl = document.getElementById('dashboardNotificationCount');

    if (!container || !countEl) {
        return;
    }

    const context = window.NOTIFICATION_CONTEXT || { isGuest: true, idTK: 0 };
    if (context.isGuest || !context.idTK) {
        countEl.textContent = 'Vui long dang nhap de xem thong bao ca nhan';
        container.innerHTML = buildEmptyState('Khong co thong bao cho tai khoan khach.');
        return;
    }

    const BASE_PATH = window.APP_BASE_PATH || '';
    const API_URL = BASE_PATH + '/api/thong_bao/inbox.php';

    function escapeHtml(raw) {
        return String(raw || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatRelativeTime(value) {
        if (!value) {
            return '--';
        }

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMin = Math.floor(diffMs / 60000);

        if (diffMin < 1) return 'Vua xong';
        if (diffMin < 60) return diffMin + ' phut truoc';

        const diffHour = Math.floor(diffMin / 60);
        if (diffHour < 24) return diffHour + ' gio truoc';

        const diffDay = Math.floor(diffHour / 24);
        if (diffDay < 7) return diffDay + ' ngay truoc';

        return date.toLocaleString('vi-VN', {
            hour12: false,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function iconByType(type) {
        const key = String(type || '').toUpperCase();
        if (key === 'SU_KIEN') {
            return 'ni ni-notification-70 bg-gradient-to-tl from-green-600 to-lime-400';
        }
        if (key === 'NHOM') {
            return 'ni ni-single-02 bg-gradient-to-tl from-red-600 to-rose-400';
        }
        if (key === 'CA_NHAN') {
            return 'ni ni-badge bg-gradient-to-tl from-blue-600 to-cyan-400';
        }
        return 'ni ni-bell-55 bg-gradient-to-tl from-slate-600 to-slate-400';
    }

    function buildEmptyState(message) {
        return [
            '<div class="flex items-start gap-3">',
            '<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500">',
            '<i class="ni ni-check-bold leading-none"></i>',
            '</span>',
            '<div>',
            '<h6 class="mb-0 text-sm font-semibold leading-normal text-slate-700">' + escapeHtml(message) + '</h6>',
            '</div>',
            '</div>'
        ].join('');
    }

    function renderItems(items) {
        if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = buildEmptyState('Ban chua co thong bao nao.');
            return;
        }

        container.innerHTML = items.map(function (item) {
            const title = escapeHtml(item.tieuDe || 'Thong bao he thong');
            const content = escapeHtml(item.noiDung || 'Khong co noi dung chi tiet.');
            const relativeTime = formatRelativeTime(item.ngayGui);
            const iconClass = iconByType(item.loaiThongBao);
            const deepLink = typeof item.deepLink === 'string' && item.deepLink.trim() !== ''
                ? item.deepLink
                : '/dashboard';

            return [
                '<a href="' + escapeHtml(deepLink) + '" class="group block rounded-xl border border-slate-100 p-3 hover:border-blue-200 hover:bg-blue-50/30 transition-colors">',
                '<div class="flex items-start gap-3">',
                '<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 shrink-0">',
                '<i class="leading-none text-transparent ' + iconClass + ' bg-clip-text fill-transparent"></i>',
                '</span>',
                '<div class="min-w-0">',
                '<h6 class="mb-0 text-sm font-semibold leading-normal text-slate-700 group-hover:text-blue-600 transition-colors">' + title + '</h6>',
                '<p class="mt-1 mb-0 text-xs leading-tight text-slate-500">' + content + '</p>',
                '<p class="mt-1 mb-0 text-xs font-semibold leading-tight text-slate-400">',
                '<i class="mr-1 fa fa-clock"></i>' + escapeHtml(relativeTime),
                '</p>',
                '</div>',
                '</div>',
                '</a>'
            ].join('');
        }).join('');
    }

    async function loadDashboardNotifications() {
        try {
            const response = await fetch(API_URL + '?action=list&limit=5&unread_only=0', {
                credentials: 'same-origin'
            });
            const result = await response.json();

            if (!result || result.status !== 'success') {
                throw new Error((result && result.message) || 'Khong the tai thong bao');
            }

            const data = result.data || {};
            const items = Array.isArray(data.items) ? data.items : [];
            const unreadCount = parseInt(data.unreadCount || 0, 10);

            if (unreadCount > 0) {
                countEl.textContent = unreadCount + ' thong bao chua doc';
            } else {
                countEl.textContent = items.length + ' thong bao gan day';
            }

            renderItems(items);
        } catch (error) {
            console.error('Dashboard notifications load error:', error);
            countEl.textContent = 'Khong the tai thong bao';
            container.innerHTML = buildEmptyState('Tai thong bao that bai. Vui long thu lai sau.');
        }
    }

    window.addEventListener('notification:refresh', loadDashboardNotifications);
    loadDashboardNotifications();
})();
