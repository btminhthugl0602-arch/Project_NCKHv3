document.addEventListener('DOMContentLoaded', function () {

    // ── DOM refs ─────────────────────────────────────────────────
    const eventList        = document.getElementById('eventList');
    const eventListGrid    = document.getElementById('eventListGrid');
    const eventListEmpty   = document.getElementById('eventListEmpty');
    const eventListLoading = document.getElementById('eventListLoading');
    const eventPagination  = document.getElementById('eventPagination');
    const eventPaginationInfo = document.getElementById('eventPaginationInfo');
    const eventPrevBtn     = document.getElementById('eventPrevBtn');
    const eventNextBtn     = document.getElementById('eventNextBtn');
    const eventPageBtns    = document.getElementById('eventPageBtns');
    const evSearch         = document.getElementById('evSearch');
    const evFilterCap      = document.getElementById('evFilterCap');
    const evFilterThoiGian = document.getElementById('evFilterThoiGian');

    // Modal refs
    const evCreateModal    = document.getElementById('evCreateModal');
    const evCreateBox      = document.getElementById('evCreateBox');
    const evCreateBackdrop = document.getElementById('evCreateBackdrop');
    const evCreateClose    = document.getElementById('evCreateClose');
    const evCreateCancel   = document.getElementById('evCreateCancel');
    const evCreateSubmit   = document.getElementById('evCreateSubmit');
    const evCreateSubmitLabel = document.getElementById('evCreateSubmitLabel');
    const openCreateEventBtn  = document.getElementById('openCreateEventBtn');

    // ── State ────────────────────────────────────────────────────
    const PAGE_LIMIT = 10;
    let currentPage  = 1;
    let totalPages   = 1;
    let _searchDebounce = null;

    const state = {
        search:    '',
        idCap:     '',
        thoiGian:  '',
    };

    const normalizeDateTime = (value) => (value ? value.replace('T', ' ') + ':00' : null);

    // ── API helpers ──────────────────────────────────────────────

    async function layDanhSachCapToChuc() {
        const r = await fetch('/api/su_kien/danh_sach_cap_to_chuc.php', { credentials: 'same-origin' });
        const p = await r.json();
        if (p.status !== 'success' || !Array.isArray(p.data)) throw new Error(p.message);
        return p.data;
    }

    async function layDanhSachSuKien(page = 1) {
        const params = new URLSearchParams({ page, limit: PAGE_LIMIT });
        if (state.search)   params.set('search',    state.search);
        if (state.idCap)    params.set('id_cap',    state.idCap);
        if (state.thoiGian) params.set('thoi_gian', state.thoiGian);

        const r = await fetch(`/api/su_kien/danh_sach_su_kien.php?${params}`, { credentials: 'same-origin' });
        const p = await r.json();
        if (p.status !== 'success') throw new Error(p.message);
        return p;
    }

    // ── Formatters ───────────────────────────────────────────────

    function formatDateTime(value) {
        if (!value) return '--';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return String(value);
        const hasTime = date.getHours() !== 0 || date.getMinutes() !== 0;
        if (hasTime) {
            return date.toLocaleString('vi-VN', { hour12: false, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        }
        return date.toLocaleDateString('vi-VN', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }

    function capText(item) {
        const tenCap = String(item.tenCap || '').trim();
        const tenLoaiCap = String(item.tenLoaiCap || '').trim();
        if (!tenCap && !tenLoaiCap) return '--';
        return tenLoaiCap ? `${tenCap} (${tenLoaiCap})` : tenCap;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ── Card renderer ────────────────────────────────────────────

    function taoCard(item) {
        const isActive = Number(item.isActive || 0) === 1;
        const idSk = Number(item.idSK || 0);
        const detailUrl = idSk > 0 ? `/event-detail?id_sk=${idSk}` : '#';

        const badge = isActive
            ? '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500 text-white">Đang diễn ra</span>'
            : '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-400 text-white">Đã kết thúc</span>';

        const thumbIcon = isActive ? 'event_available' : 'visibility_off';
        const capLabel = capText(item) !== '--' ? escapeHtml(capText(item)) : '';

        const card = document.createElement('article');
        card.className = 'rounded-xl overflow-hidden border border-slate-100 bg-white flex hover:shadow-md transition-shadow';
        card.innerHTML =
            `<div class="relative w-56 shrink-0 flex flex-col justify-between p-4"
                  style="background:linear-gradient(135deg,#7d1f2e 0%,#a8293d 100%);min-height:180px">
                <div>${badge}</div>
                <span class="material-symbols-outlined text-white/20 text-[48px]" aria-hidden="true">${thumbIcon}</span>
            </div>` +
            `<div class="flex-1 flex flex-col justify-between p-5 min-w-0">
                <div class="space-y-2">
                    <h2 class="text-base font-bold text-slate-800 leading-snug">${escapeHtml(item.tenSK)}</h2>
                    ${capLabel ? `<p class="text-xs font-bold text-slate-400 uppercase tracking-widest">${capLabel}</p>` : ''}
                    <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                        <span class="material-symbols-outlined text-[15px] text-slate-400 shrink-0" aria-hidden="true">calendar_month</span>
                        <span>Ngày diễn ra: <span class="font-medium text-slate-700">${escapeHtml(formatDateTime(item.ngayBatDau))}</span></span>
                    </div>
                    ${item.ngayKetThuc ? `
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="material-symbols-outlined text-[15px] text-slate-400 shrink-0" aria-hidden="true">event_busy</span>
                        <span>Ngày kết thúc: <span class="font-medium text-slate-700">${escapeHtml(formatDateTime(item.ngayKetThuc))}</span></span>
                    </div>` : ''}
                </div>
                <div class="flex justify-end pt-4 border-t border-slate-100 mt-4">
                    <a href="${detailUrl}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white
                              bg-primary hover:bg-primary-dark rounded-lg transition-colors
                              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                        Xem chi tiết
                        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>
            </div>`;

        return card;
    }

    // ── Pagination ───────────────────────────────────────────────

    function renderPageBtns() {
        if (!eventPageBtns) return;
        eventPageBtns.innerHTML = '';

        const pages = [];
        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > 3) pages.push('…');
            for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) pages.push(i);
            if (currentPage < totalPages - 2) pages.push('…');
            pages.push(totalPages);
        }

        pages.forEach((p) => {
            if (p === '…') {
                const span = document.createElement('span');
                span.className = 'text-xs text-slate-400 px-1';
                span.textContent = '…';
                eventPageBtns.appendChild(span);
                return;
            }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(p);
            btn.setAttribute('aria-label', `Trang ${p}`);
            btn.setAttribute('aria-current', p === currentPage ? 'page' : 'false');
            btn.className = p === currentPage
                ? 'inline-flex items-center justify-center size-8 rounded-lg text-xs font-semibold bg-primary text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40'
                : 'inline-flex items-center justify-center size-8 rounded-lg text-xs font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40';
            btn.addEventListener('click', () => napDanhSachSuKien(p));
            eventPageBtns.appendChild(btn);
        });
    }

    // ── Load list (server-side) ───────────────────────────────────

    async function napDanhSachSuKien(page = 1) {
        eventListLoading?.classList.remove('hidden');
        eventList?.classList.add('hidden');
        eventListEmpty?.classList.add('hidden');
        eventPagination?.classList.add('hidden');

        try {
            const payload = await layDanhSachSuKien(page);
            const rows = payload.data || [];
            const pg   = payload.pagination || {};

            currentPage  = pg.page       || 1;
            totalPages   = pg.totalPages || 1;
            const total  = pg.total      || rows.length;

            eventListLoading?.classList.add('hidden');

            if (rows.length === 0) {
                eventListEmpty?.classList.remove('hidden');
                return;
            }

            eventListGrid.innerHTML = '';
            rows.forEach((item) => eventListGrid.appendChild(taoCard(item)));
            eventList?.classList.remove('hidden');

            // Pagination info
            const from = (currentPage - 1) * PAGE_LIMIT + 1;
            const to   = Math.min(currentPage * PAGE_LIMIT, total);
            if (eventPaginationInfo) eventPaginationInfo.textContent = `Hiển thị ${from}–${to} trong ${total} sự kiện`;
            if (eventPrevBtn) eventPrevBtn.disabled = currentPage === 1;
            if (eventNextBtn) eventNextBtn.disabled = currentPage === totalPages;
            renderPageBtns();

            if (eventPagination && totalPages > 1) eventPagination.classList.remove('hidden');

        } catch {
            eventListLoading?.classList.add('hidden');
            eventListEmpty?.classList.remove('hidden');
        }
    }

    // ── Filter + Search ──────────────────────────────────────────

    function onFilterChange() {
        state.search   = evSearch?.value.trim()        || '';
        state.idCap    = evFilterCap?.value            || '';
        state.thoiGian = evFilterThoiGian?.value       || '';
        napDanhSachSuKien(1);
    }

    if (evSearch) {
        evSearch.addEventListener('input', () => {
            clearTimeout(_searchDebounce);
            _searchDebounce = setTimeout(onFilterChange, 350);
        });
    }
    if (evFilterCap)      evFilterCap.addEventListener('change', onFilterChange);
    if (evFilterThoiGian) evFilterThoiGian.addEventListener('change', onFilterChange);

    if (eventPrevBtn) eventPrevBtn.addEventListener('click', () => napDanhSachSuKien(currentPage - 1));
    if (eventNextBtn) eventNextBtn.addEventListener('click', () => napDanhSachSuKien(currentPage + 1));

    // ── Load dropdown cấp tổ chức vào filter bar ─────────────────

    async function napCapToChucFilter() {
        try {
            const capList = await layDanhSachCapToChuc();
            if (!evFilterCap) return;
            capList.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.idCap;
                opt.textContent = item.tenCap + (item.tenLoaiCap ? ` (${item.tenLoaiCap})` : '');
                evFilterCap.appendChild(opt);
            });
        } catch { /* silent */ }
    }

    // ── Modal tạo sự kiện ────────────────────────────────────────

    function openModal() {
        if (!evCreateModal) return;
        evCreateModal.classList.remove('opacity-0', 'pointer-events-none');
        evCreateBox?.classList.remove('scale-95', 'opacity-0');
        document.getElementById('evTenSK')?.focus();
    }

    function closeModal() {
        if (!evCreateModal) return;
        evCreateModal.classList.add('opacity-0', 'pointer-events-none');
        evCreateBox?.classList.add('scale-95', 'opacity-0');
        // Reset form
        ['evTenSK','evMoTa'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        ['evNgayBatDau','evNgayKetThuc'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        const evIdCap = document.getElementById('evIdCap'); if (evIdCap) evIdCap.value = '';
        const evCoGVHD = document.getElementById('evCoGVHD'); if (evCoGVHD) evCoGVHD.checked = true;
        ['evTenSKError','evIdCapError'].forEach(id => { const el = document.getElementById(id); if (el) el.classList.add('hidden'); });
    }

    async function napCapToChucModal() {
        const select = document.getElementById('evIdCap');
        if (!select || select.options.length > 1) return; // đã load rồi
        try {
            const capList = await layDanhSachCapToChuc();
            capList.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.idCap;
                opt.dataset.tenCap      = item.tenCap || '';
                opt.dataset.tenLoaiCap  = item.tenLoaiCap || '';
                opt.textContent = item.tenCap + (item.tenLoaiCap ? ` (${item.tenLoaiCap})` : '');
                select.appendChild(opt);
            });
        } catch {
            Toast?.fire({ icon: 'error', title: 'Không tải được cấp tổ chức' });
        }
    }

    const Toast = typeof Swal !== 'undefined' ? Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false,
        timer: 3000, timerProgressBar: true,
    }) : null;

    if (openCreateEventBtn) {
        openCreateEventBtn.addEventListener('click', async () => {
            await napCapToChucModal();
            openModal();
        });
    }
    evCreateClose?.addEventListener('click', closeModal);
    evCreateCancel?.addEventListener('click', closeModal);
    evCreateBackdrop?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    if (evCreateSubmit) {
        evCreateSubmit.addEventListener('click', async () => {
            const tenSK = document.getElementById('evTenSK')?.value.trim() || '';
            const moTa  = document.getElementById('evMoTa')?.value.trim()  || '';
            const capSelect = document.getElementById('evIdCap');
            const idCapRaw  = capSelect?.value.trim() || '';
            const ngayBatDau   = document.getElementById('evNgayBatDau')?.value  || '';
            const ngayKetThuc  = document.getElementById('evNgayKetThuc')?.value || '';
            const coGVHD = document.getElementById('evCoGVHD')?.checked ?? true;

            // Validate
            let valid = true;
            const tenSKErr = document.getElementById('evTenSKError');
            const idCapErr = document.getElementById('evIdCapError');

            if (!tenSK) {
                if (tenSKErr) { tenSKErr.textContent = 'Tên sự kiện không được để trống'; tenSKErr.classList.remove('hidden'); }
                valid = false;
            } else {
                tenSKErr?.classList.add('hidden');
            }
            if (!idCapRaw) {
                if (idCapErr) { idCapErr.textContent = 'Vui lòng chọn cấp tổ chức'; idCapErr.classList.remove('hidden'); }
                valid = false;
            } else {
                idCapErr?.classList.add('hidden');
            }
            if (!valid) return;

            const selectedOpt = capSelect?.options[capSelect.selectedIndex];
            const body = {
                ten_su_kien: tenSK,
                mo_ta:       moTa,
                id_cap:      Number(idCapRaw),
                ngay_bat_dau:   normalizeDateTime(ngayBatDau),
                ngay_ket_thuc:  normalizeDateTime(ngayKetThuc),
                is_active:      1,
                co_gvhd_theo_su_kien: coGVHD ? 1 : 0,
                ten_cap_label:      selectedOpt?.dataset?.tenCap     || '',
                ten_loai_cap_label: selectedOpt?.dataset?.tenLoaiCap || '',
            };

            evCreateSubmit.disabled = true;
            if (evCreateSubmitLabel) evCreateSubmitLabel.textContent = 'Đang tạo…';

            try {
                const r = await fetch('/api/su_kien/tao_su_kien.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
                const p = await r.json();

                if (p.status === 'success') {
                    closeModal();
                    Toast?.fire({ icon: 'success', title: p.message || 'Đã tạo sự kiện mới' });
                    await napDanhSachSuKien(1);
                } else {
                    Toast?.fire({ icon: 'error', title: p.message || 'Có lỗi xảy ra' });
                }
            } catch {
                Toast?.fire({ icon: 'error', title: 'Lỗi kết nối' });
            } finally {
                evCreateSubmit.disabled = false;
                if (evCreateSubmitLabel) evCreateSubmitLabel.textContent = 'Tạo sự kiện';
            }
        });
    }

    // ── Init ─────────────────────────────────────────────────────
    napCapToChucFilter();
    napDanhSachSuKien(1);

}); // end DOMContentLoaded