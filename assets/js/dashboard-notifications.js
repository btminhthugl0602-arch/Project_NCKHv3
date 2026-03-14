/**
 * assets/js/dashboard-notifications.js
 *
 * REBUILT: Wire stat cards, thông báo mới, sự kiện sắp tới
 * - Stat cards: đếm từ danh_sach_su_kien API
 * - Thông báo: inbox API (giữ nguyên logic cũ, đổi HTML render)
 * - Sự kiện sắp tới: lấy từ danh_sach_su_kien, lọc chưa kết thúc
 */
(function () {
    'use strict';

    const ctx = window.NOTIFICATION_CONTEXT || { isGuest: true, idTK: 0 };

    /* ── Helpers ── */
    function esc(raw) {
        return String(raw || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function relTime(value) {
        if (!value) return '—';
        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(value);
        const diff = Math.floor((Date.now() - d.getTime()) / 60000);
        if (diff < 1) return 'Vừa xong';
        if (diff < 60) return diff + ' phút trước';
        const h = Math.floor(diff / 60);
        if (h < 24) return h + ' giờ trước';
        const day = Math.floor(h / 24);
        if (day < 7) return day + ' ngày trước';
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function iconByType(type) {
        const map = {
            SU_KIEN: { icon: 'event', bg: 'bg-emerald-50', color: 'text-emerald-600' },
            NHOM: { icon: 'group', bg: 'bg-blue-50', color: 'text-blue-600' },
            CA_NHAN: { icon: 'person', bg: 'bg-violet-50', color: 'text-violet-600' },
        };
        return map[String(type || '').toUpperCase()]
            || { icon: 'notifications', bg: 'bg-slate-100', color: 'text-slate-500' };
    }

    /* ── Stat Cards ── */
    function setStatCard(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (value === null || value === undefined || value === '—' || value === '-') {
            el.textContent = '—';
            return;
        }
        const n = Number(value);
        el.textContent = isNaN(n) ? '—' : n.toLocaleString('vi-VN');
    }

    async function loadStats(events) {
        if (!Array.isArray(events)) return;

        const total = events.length;
        const active = events.filter(e => e.isActive == 1).length;

        setStatCard('statSuKien', total);
        setStatCard('statNhom', active);   /* Tạm dùng sự kiện active — đổi khi có API riêng */

        /* Bài báo + Đánh giá: tạm set placeholder cho đến khi có API thống kê */
        setStatCard('statBaiBao', '—');
        setStatCard('statDanhGia', '—');
    }

    /* ── Upcoming Events ── */
    function renderUpcoming(events) {
        const el = document.getElementById('dashboardUpcomingEvents');
        if (!el) return;

        const now = Date.now();
        const upcoming = events
            .filter(e => {
                const end = e.ngayKetThuc ? new Date(e.ngayKetThuc.replace(' ', 'T')).getTime() : null;
                return end === null || end >= now;
            })
            .sort((a, b) => {
                const ta = a.ngayBatDau ? new Date(a.ngayBatDau.replace(' ', 'T')).getTime() : 0;
                const tb = b.ngayBatDau ? new Date(b.ngayBatDau.replace(' ', 'T')).getTime() : 0;
                return ta - tb;
            })
            .slice(0, 4);

        if (upcoming.length === 0) {
            el.innerHTML = '<p class="px-4 py-5 text-xs text-slate-400 text-center">Không có sự kiện sắp tới.</p>';
            return;
        }

        el.innerHTML = upcoming.map(e => {
            const d = e.ngayBatDau ? new Date(e.ngayBatDau.replace(' ', 'T')) : null;
            const day = d ? String(d.getDate()).padStart(2, '0') : '—';
            const month = d ? 'TH' + String(d.getMonth() + 1).padStart(2, '0') : '';
            const href = '/event-detail?id_sk=' + (e.idSK || '');

            return [
                '<a href="' + esc(href) + '"',
                '   class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors',
                '          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-inset">',
                '  <div class="flex flex-col items-center w-10 shrink-0 text-center">',
                '    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--color-primary)">' + esc(month) + '</span>',
                '    <span class="text-lg font-bold text-slate-800 leading-tight">' + esc(day) + '</span>',
                '  </div>',
                '  <div class="min-w-0 flex-1">',
                '    <p class="text-sm font-semibold text-slate-800 truncate">' + esc(e.tenSK || 'Sự kiện') + '</p>',
                '    <p class="text-xs text-slate-400 truncate">' + esc(e.tenCap || 'ezManagement') + '</p>',
                '  </div>',
                '</a>',
            ].join('');
        }).join('');
    }

    /* ── Notifications ── */
    function renderNotifications(items) {
        const el = document.getElementById('dashboardNotificationList');
        if (!el) return;

        if (!Array.isArray(items) || items.length === 0) {
            el.innerHTML = [
                '<div class="flex flex-col items-center justify-center py-10 text-center">',
                '  <span class="material-symbols-outlined text-[32px] text-slate-300 mb-2" aria-hidden="true">notifications_off</span>',
                '  <p class="text-sm text-slate-400">Bạn chưa có thông báo nào.</p>',
                '</div>',
            ].join('');
            return;
        }

        el.innerHTML = items.map(item => {
            const ic = iconByType(item.loaiThongBao);
            const deepLink = typeof item.deepLink === 'string' && item.deepLink.trim()
                ? item.deepLink : '/dashboard';
            const isUnread = item.daDoc == 0 || item.daDoc === false;

            return [
                '<a href="' + esc(deepLink) + '"',
                '   class="group flex items-start gap-3 px-5 py-4 hover:bg-slate-50',
                '          transition-colors focus-visible:outline-none focus-visible:ring-2',
                '          focus-visible:ring-primary/40 focus-visible:ring-inset"',
                '   aria-label="' + esc(item.tieuDe || 'Thông báo') + '">',
                /* Icon */
                '  <div class="size-9 rounded-full flex items-center justify-center shrink-0 ' + ic.bg + '">',
                '    <span class="material-symbols-outlined text-[16px] ' + ic.color + '" aria-hidden="true">' + ic.icon + '</span>',
                '  </div>',
                /* Content */
                '  <div class="flex-1 min-w-0">',
                '    <p class="text-sm font-semibold text-slate-800 truncate' + (isUnread ? '' : ' font-normal') + '">',
                '      ' + esc(item.tieuDe || 'Thông báo hệ thống'),
                '    </p>',
                '    <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">',
                '      ' + esc(item.noiDung || ''),
                '    </p>',
                '  </div>',
                /* Time */
                '  <span class="text-xs text-slate-400 shrink-0 mt-0.5">' + esc(relTime(item.ngayGui)) + '</span>',
                '</a>',
            ].join('');
        }).join('');
    }

    function renderNotifError(msg) {
        const el = document.getElementById('dashboardNotificationList');
        if (!el) return;
        el.innerHTML = [
            '<div class="flex flex-col items-center justify-center py-10 text-center">',
            '  <span class="material-symbols-outlined text-[32px] text-slate-300 mb-2" aria-hidden="true">wifi_off</span>',
            '  <p class="text-sm text-slate-400">' + esc(msg) + '</p>',
            '</div>',
        ].join('');
    }

    /* ── Load all data ── */
    async function loadAll() {
        const isGuest = ctx.isGuest || !ctx.idTK;

        /* 1. Events list — ai cũng load được */
        try {
            const res = await fetch('/api/su_kien/danh_sach_su_kien.php', { credentials: 'same-origin' });
            const result = await res.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                loadStats(result.data);
                renderUpcoming(result.data);
            }
        } catch (e) {
            console.warn('Dashboard: không tải được danh sách sự kiện', e);
        }

        /* 2. Notifications — chỉ load khi đã đăng nhập */
        if (isGuest) {
            renderNotifError('Đăng nhập để xem thông báo cá nhân.');
            return;
        }

        try {
            const res = await fetch('/api/thong_bao/inbox.php?action=list&limit=5&unread_only=0', { credentials: 'same-origin' });
            const result = await res.json();
            if (!result || result.status !== 'success') throw new Error(result?.message || 'Lỗi tải thông báo');
            const items = Array.isArray(result.data?.items) ? result.data.items : [];
            renderNotifications(items);
        } catch (e) {
            console.error('Dashboard notifications error:', e);
            renderNotifError('Không thể tải thông báo. Vui lòng thử lại sau.');
        }
    }

    window.addEventListener('notification:refresh', loadAll);
    loadAll();
})();