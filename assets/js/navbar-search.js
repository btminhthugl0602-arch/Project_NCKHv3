(function () {
    'use strict';

    // ── Constants ────────────────────────────────────────────
    const DEBOUNCE_MS   = 300;
    const MIN_QUERY_LEN = 2;
    const API_URL       = '/api/su_kien/danh_sach_su_kien.php';

    // ── State ────────────────────────────────────────────────
    let _debounceTimer = null;
    let _lastQuery     = '';
    let _isOpen        = false;

    // ── Elements ─────────────────────────────────────────────
    const input    = document.querySelector('#navbarSearch');
    const dropdown = document.querySelector('#navbarSearchDropdown');

    if (!input || !dropdown) return; // navbar search không có trên trang này

    // ── Helpers ──────────────────────────────────────────────
    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatDate(val) {
        if (!val) return null;
        const d = new Date(String(val).replace(' ', 'T'));
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // Highlight từ khớp trong text
    function highlight(text, query) {
        if (!query) return escHtml(text);
        const safe  = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safe})`, 'gi');
        return escHtml(text).replace(regex, '<mark class="nss-mark">$1</mark>');
    }

    // ── Dropdown open / close ─────────────────────────────────
    function openDropdown() {
        dropdown.classList.remove('hidden');
        dropdown.setAttribute('aria-hidden', 'false');
        input.setAttribute('aria-expanded', 'true');
        _isOpen = true;
    }

    function closeDropdown() {
        dropdown.classList.add('hidden');
        dropdown.setAttribute('aria-hidden', 'true');
        input.setAttribute('aria-expanded', 'false');
        _isOpen = false;
    }

    // ── Render states ─────────────────────────────────────────
    function renderSkeleton() {
        const row = () =>
            '<div class="flex items-center gap-3 px-4 py-2.5">' +
                '<div class="w-8 h-8 rounded-lg flex-shrink-0 nss-shimmer"></div>' +
                '<div class="flex-1 flex flex-col gap-1.5">' +
                    '<div class="h-3 w-3/5 rounded nss-shimmer"></div>' +
                    '<div class="h-2.5 w-2/5 rounded nss-shimmer"></div>' +
                '</div>' +
            '</div>';
        dropdown.innerHTML = '<div class="py-1">' + [1,2,3].map(row).join('') + '</div>';
        openDropdown();
    }

    function renderEmpty(query) {
        dropdown.innerHTML =
            '<div class="px-4 py-6 text-center">' +
                '<span class="material-symbols-outlined text-3xl text-slate-300 block mb-2">search_off</span>' +
                '<p class="text-sm text-slate-400">Không tìm thấy kết quả cho <strong class="text-slate-500 font-semibold">"' + escHtml(query) + '"</strong></p>' +
            '</div>';
        openDropdown();
    }

    function renderResults(items, query) {
        const rows = items.map(function (item) {
            const idSk     = Number(item.idSK || 0);
            const isActive = Number(item.isActive) === 1;
            const tenCap   = [item.tenCap, item.tenLoaiCap].filter(Boolean).join(' · ');
            const date     = formatDate(item.ngayBatDau);

            const badgeCls = isActive
                ? 'bg-emerald-100 text-emerald-600'
                : 'bg-slate-100 text-slate-400';

            return '<a href="/event-detail?id_sk=' + idSk + '"' +
                ' class="nss-result-item flex items-center gap-3 px-4 py-2.5 hover:bg-violet-50 focus:bg-violet-50 focus:outline-none transition-colors"' +
                ' data-id="' + idSk + '">' +
                    '<div class="flex-shrink-0 w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">' +
                        '<span class="material-symbols-outlined text-violet-600" style="font-size:16px">event</span>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-semibold text-slate-800 truncate leading-snug">' + highlight(item.tenSK, query) + '</p>' +
                        '<p class="text-xs text-slate-400 truncate leading-snug">' +
                            (tenCap ? escHtml(tenCap) : '') +
                            (tenCap && date ? ' · ' : '') +
                            (date ? date : '') +
                        '</p>' +
                    '</div>' +
                    '<span class="flex-shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full ' + badgeCls + '">' +
                        (isActive ? 'Đang mở' : 'Tạm ẩn') +
                    '</span>' +
                '</a>';
        }).join('');

        dropdown.innerHTML =
            '<p class="px-4 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Sự kiện</p>' +
            rows +
            '<p class="px-4 py-2 mt-1 text-[10px] text-center text-slate-300 border-t border-slate-100">Nhấn Enter để xem tất cả kết quả</p>';
        openDropdown();
    }

    // ── Fetch ─────────────────────────────────────────────────
    async function doSearch(query) {
        if (query === _lastQuery && _isOpen) return;
        _lastQuery = query;

        renderSkeleton();

        try {
            const res  = await fetch(API_URL + '?search=' + encodeURIComponent(query), {
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (!_isOpen) return; // user đã đóng trong lúc fetch

            if (data.status !== 'success' || !Array.isArray(data.data) || data.data.length === 0) {
                renderEmpty(query);
                return;
            }

            renderResults(data.data, query);
        } catch (_err) {
            if (_isOpen) renderEmpty(query);
        }
    }

    // ── Event: input ──────────────────────────────────────────
    input.addEventListener('input', function () {
        const q = input.value.trim();
        clearTimeout(_debounceTimer);

        if (q.length < MIN_QUERY_LEN) {
            closeDropdown();
            _lastQuery = '';
            return;
        }

        _debounceTimer = setTimeout(function () { doSearch(q); }, DEBOUNCE_MS);
    });

    // ── Event: keyboard ───────────────────────────────────────
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDropdown();
            input.blur();
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const q = input.value.trim();
            if (q.length >= MIN_QUERY_LEN) {
                // Navigate đến trang events với query
                window.location.href = '/events?search=' + encodeURIComponent(q);
            }
            return;
        }

        // Arrow keys — focus vào item đầu tiên
        if (e.key === 'ArrowDown' && _isOpen) {
            e.preventDefault();
            const first = dropdown.querySelector('.nss-result-item');
            if (first) first.focus();
        }
    });

    // Arrow navigation trong dropdown
    dropdown.addEventListener('keydown', function (e) {
        const items = Array.from(dropdown.querySelectorAll('.nss-result-item'));
        const idx   = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const next = items[idx + 1];
            if (next) next.focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx === 0) { input.focus(); }
            else if (items[idx - 1]) items[idx - 1].focus();
        } else if (e.key === 'Escape') {
            closeDropdown();
            input.focus();
        }
    });

    // ── Event: click outside ──────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    // ── Event: focus input (reopen nếu có query) ──────────────
    input.addEventListener('focus', function () {
        const q = input.value.trim();
        if (q.length >= MIN_QUERY_LEN && !_isOpen) {
            doSearch(q);
        }
    });

})();
