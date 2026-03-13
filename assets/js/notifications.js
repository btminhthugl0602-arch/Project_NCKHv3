(function () {
    'use strict';

    const context = window.NOTIFICATION_CONTEXT || { isGuest: true, idTK: 0 };
    if (context.isGuest || !context.idTK) {
        return;
    }

    const BASE_PATH = window.APP_BASE_PATH || '';
    const API_URL = BASE_PATH + '/api/thong_bao/inbox.php';
    const POLLING_INTERVAL = 30000;
    const REQUEST_TIMEOUT = 10000;

    const root = document.getElementById('notificationRoot');
    const btn = document.getElementById('notificationBtn');
    const dot = document.getElementById('notificationDot');
    const badge = document.getElementById('notificationCountBadge');
    const dropdown = document.getElementById('notificationDropdown');
    const list = document.getElementById('notificationList');
    const subtitle = document.getElementById('notificationSubtitle');
    const markAllBtn = document.getElementById('markAllReadBtn');

    if (!root || !btn || !dropdown || !list || !subtitle || !markAllBtn) {
        return;
    }

    let pollingTimer = null;
    let isOpen = false;

    function escapeHtml(raw) {
        return String(raw || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildLink(item) {
        if (item && typeof item.deepLink === 'string' && item.deepLink.trim() !== '') {
            return item.deepLink;
        }

        const idSK = parseInt(item.idSK || 0, 10);
        if (idSK > 0) {
            return '/event-detail?id_sk=' + idSK + '&tab=overview';
        }
        return '/dashboard';
    }

    function formatTime(value) {
        if (!value) {
            return '--';
        }
        const dt = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(dt.getTime())) {
            return String(value);
        }
        return dt.toLocaleString('vi-VN', {
            hour12: false,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    async function apiGet(params) {
        const query = new URLSearchParams(params || {});
        const controller = new AbortController();
        const timeout = window.setTimeout(function () {
            controller.abort();
        }, REQUEST_TIMEOUT);

        const res = await fetch(API_URL + '?' + query.toString(), {
            credentials: 'same-origin',
            signal: controller.signal
        });
        window.clearTimeout(timeout);
        return res.json();
    }

    async function apiPost(body) {
        const controller = new AbortController();
        const timeout = window.setTimeout(function () {
            controller.abort();
        }, REQUEST_TIMEOUT);

        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body || {}),
            signal: controller.signal
        });
        window.clearTimeout(timeout);
        return res.json();
    }

    function setUnreadBadge(unreadCount) {
        const count = Math.max(0, parseInt(unreadCount || 0, 10));

        if (count > 0) {
            dot?.classList.remove('hidden');
            badge?.classList.remove('hidden');
            badge.textContent = count > 99 ? '99+' : String(count);
            subtitle.textContent = 'Ban co ' + count + ' thong bao chua doc';
            markAllBtn.disabled = false;
        } else {
            dot?.classList.add('hidden');
            badge?.classList.add('hidden');
            badge.textContent = '';
            subtitle.textContent = 'Khong co thong bao moi';
            markAllBtn.disabled = true;
        }
    }

    function renderList(items) {
        const listItems = Array.isArray(items) ? items : [];

        if (listItems.length === 0) {
            list.innerHTML = '<div class="px-4 py-8 text-center text-sm text-slate-400">Khong co thong bao nao.</div>';
            return;
        }

        list.innerHTML = listItems.map(function (item) {
            const id = parseInt(item.idThongBao || 0, 10);
            const title = escapeHtml(item.tieuDe || '--');
            const message = escapeHtml(item.noiDung || '');
            const loai = escapeHtml(item.loaiThongBao || 'HE_THONG');
            const timeText = escapeHtml(formatTime(item.ngayGui));
            const daDoc = parseInt(item.daDoc || 0, 10) === 1;
            const href = escapeHtml(buildLink(item));

            return [
                '<button type="button" data-notification-item="' + id + '" data-notification-link="' + href + '"',
                ' class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors ' + (daDoc ? 'bg-white' : 'bg-blue-50/40') + '">',
                '<div class="flex items-start justify-between gap-3">',
                '<div class="min-w-0">',
                '<p class="text-sm font-semibold text-slate-800 truncate">' + title + '</p>',
                '<p class="text-xs text-slate-500 mt-0.5 line-clamp-2">' + message + '</p>',
                '<p class="text-[11px] text-slate-400 mt-1.5">' + timeText + '</p>',
                '</div>',
                '<div class="shrink-0 flex flex-col items-end gap-1">',
                '<span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-600">' + loai + '</span>',
                (daDoc ? '' : '<span class="size-2 rounded-full bg-blue-500"></span>'),
                '</div>',
                '</div>',
                '</button>'
            ].join('');
        }).join('');
    }

    async function loadInbox() {
        try {
            const payload = await apiGet({ action: 'list', limit: 20, unread_only: 0 });
            if (payload.status !== 'success') {
                throw new Error(payload.message || 'Khong the tai inbox');
            }
            const data = payload.data || {};
            renderList(data.items || []);
            setUnreadBadge(data.unreadCount || 0);
        } catch (err) {
            console.error('loadInbox error:', err);
            list.innerHTML = '<div class="px-4 py-8 text-center text-sm text-rose-500">Khong the tai thong bao.</div>';
        }
    }

    async function refreshUnreadCount() {
        try {
            const payload = await apiGet({ action: 'unread_count' });
            if (payload.status !== 'success') {
                return;
            }
            const unreadCount = payload.data?.unreadCount || 0;
            setUnreadBadge(unreadCount);
        } catch (err) {
            console.error('refreshUnreadCount error:', err);
        }
    }

    async function markAllRead() {
        try {
            markAllBtn.disabled = true;
            const payload = await apiPost({ action: 'mark_all_read' });
            if (payload.status !== 'success') {
                throw new Error(payload.message || 'Khong the danh dau tat ca');
            }
            await loadInbox();
        } catch (err) {
            console.error('markAllRead error:', err);
            markAllBtn.disabled = false;
        }
    }

    async function handleNotificationClick(target) {
        const itemEl = target.closest('[data-notification-item]');
        if (!itemEl) {
            return;
        }

        const idThongBao = parseInt(itemEl.getAttribute('data-notification-item') || '0', 10);
        const link = itemEl.getAttribute('data-notification-link') || '/dashboard';

        try {
            if (idThongBao > 0) {
                await apiPost({ action: 'mark_read', id_thong_bao: idThongBao });
            }
        } catch (err) {
            console.error('mark_read error:', err);
        }

        window.location.href = link;
    }

    function openDropdown() {
        isOpen = true;
        btn.setAttribute('aria-expanded', 'true');
        dropdown.classList.remove('hidden');
        dropdown.setAttribute('aria-hidden', 'false');
        loadInbox();
    }

    function closeDropdown() {
        isOpen = false;
        btn.setAttribute('aria-expanded', 'false');
        dropdown.classList.add('hidden');
        dropdown.setAttribute('aria-hidden', 'true');
    }

    function toggleDropdown() {
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    function softRefresh() {
        if (isOpen) {
            return loadInbox();
        }
        return refreshUnreadCount();
    }

    btn.addEventListener('click', function (event) {
        event.stopPropagation();
        toggleDropdown();
    });

    markAllBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        markAllRead();
    });

    list.addEventListener('click', function (event) {
        handleNotificationClick(event.target);
    });

    document.addEventListener('click', function (event) {
        if (!isOpen) {
            return;
        }
        if (!root.contains(event.target)) {
            closeDropdown();
        }
    });

    window.addEventListener('notification:refresh', function () {
        softRefresh();
    });

    window.NotificationCenter = {
        refresh: softRefresh,
        refreshUnreadCount,
        refreshInbox: loadInbox,
    };

    refreshUnreadCount();

    pollingTimer = window.setInterval(function () {
        if (isOpen) {
            loadInbox();
            return;
        }
        refreshUnreadCount();
    }, POLLING_INTERVAL);

    window.addEventListener('beforeunload', function () {
        if (pollingTimer) {
            window.clearInterval(pollingTimer);
        }
    });
})();
