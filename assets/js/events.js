document.addEventListener('DOMContentLoaded', function () {
    const openCreateEventBtn = document.getElementById('openCreateEventBtn');
    const eventList = document.getElementById('eventList');
    const eventListGrid = document.getElementById('eventListGrid');
    const eventListEmpty = document.getElementById('eventListEmpty');
    const eventListLoading = document.getElementById('eventListLoading');
    const eventPagination = document.getElementById('eventPagination');
    const eventPaginationInfo = document.getElementById('eventPaginationInfo');
    const eventPrevBtn = document.getElementById('eventPrevBtn');
    const eventNextBtn = document.getElementById('eventNextBtn');
    const eventPageBtns = document.getElementById('eventPageBtns');

    // openCreateEventBtn chỉ hiện với người có quyền tao_su_kien
    // nhưng list sự kiện vẫn cần load cho tất cả user

    const PAGE_SIZE = 9;
    let allEvents = [];
    let currentPage = 1;

    const normalizeDateTime = (value) => (value ? value.replace('T', ' ') + ':00' : null);

    // ── API helpers ──────────────────────────────────────────────

    async function layDanhSachCapToChuc() {
        const response = await fetch('/api/su_kien/danh_sach_cap_to_chuc.php', {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !Array.isArray(payload.data)) {
            throw new Error(payload.message || 'Không lấy được danh sách cấp tổ chức');
        }
        return payload.data;
    }

    async function layDanhSachSuKien() {
        const response = await fetch('/api/su_kien/danh_sach_su_kien.php', {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !Array.isArray(payload.data)) {
            throw new Error(payload.message || 'Không lấy được danh sách sự kiện');
        }
        return payload.data;
    }

    // ── Formatters ───────────────────────────────────────────────

    function formatDateTime(value) {
        if (!value) return '--';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleString('vi-VN', {
            hour12: false,
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        });
    }

    function capText(item) {
        const tenCap = String(item.tenCap || '').trim();
        const tenLoaiCap = String(item.tenLoaiCap || '').trim();
        if (!tenCap && !tenLoaiCap) return '--';
        return tenLoaiCap ? `${tenCap} (${tenLoaiCap})` : tenCap;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ── Card renderer ────────────────────────────────────────────

    function taoCard(item) {
        const isActive = Number(item.isActive || 0) === 1;
        const idSk = Number(item.idSK || 0);

        const badge = isActive
            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Đang mở</span>'
            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Tạm ẩn</span>';

        const detailUrl = idSk > 0 ? `/event-detail?id_sk=${idSk}` : '#';

        const card = document.createElement('article');
        card.className = 'rounded-xl overflow-hidden border border-slate-100 flex flex-col bg-white';
        card.innerHTML =
            `<div class="relative p-4 min-h-[100px] flex flex-col justify-end"
                  style="background:linear-gradient(135deg,#7d1f2e 0%,#a8293d 100%)">
                <div class="absolute top-3 right-3">${badge}</div>
                <h2 class="text-sm font-bold text-white leading-snug line-clamp-2 min-w-0 pr-16">
                    ${escapeHtml(item.tenSK)}
                </h2>
            </div>` +
            `<div class="flex-1 flex flex-col p-4 gap-2">
                <div class="flex items-center gap-2 text-xs text-slate-500 min-w-0">
                    <span class="material-symbols-outlined text-[14px] text-slate-400 shrink-0" aria-hidden="true">calendar_month</span>
                    <span class="truncate">Bắt đầu: <span class="font-medium text-slate-700">${escapeHtml(formatDateTime(item.ngayBatDau))}</span></span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 min-w-0">
                    <span class="material-symbols-outlined text-[14px] text-slate-400 shrink-0" aria-hidden="true">event_busy</span>
                    <span class="truncate">Kết thúc: <span class="font-medium text-slate-700">${escapeHtml(formatDateTime(item.ngayKetThuc))}</span></span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 min-w-0">
                    <span class="material-symbols-outlined text-[14px] text-slate-400 shrink-0" aria-hidden="true">corporate_fare</span>
                    <span class="truncate">Cấp tổ chức: <span class="font-medium text-slate-700">${escapeHtml(capText(item))}</span></span>
                </div>
                <div class="mt-auto pt-3 border-t border-slate-100 flex justify-end">
                    <a href="${detailUrl}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:opacity-75 transition-opacity
                              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded">
                        Xem chi tiết
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>
            </div>`;

        return card;
    }

    // ── Pagination ───────────────────────────────────────────────

    function totalPages() {
        return Math.ceil(allEvents.length / PAGE_SIZE);
    }

    function renderPage(page) {
        currentPage = Math.max(1, Math.min(page, totalPages()));
        const start = (currentPage - 1) * PAGE_SIZE;
        const slice = allEvents.slice(start, start + PAGE_SIZE);

        eventListGrid.innerHTML = '';
        slice.forEach((item) => eventListGrid.appendChild(taoCard(item)));

        const total = allEvents.length;
        const from = start + 1;
        const to = Math.min(start + PAGE_SIZE, total);
        if (eventPaginationInfo) {
            eventPaginationInfo.textContent = `Hiển thị ${from}–${to} trong số ${total} sự kiện`;
        }

        if (eventPrevBtn) eventPrevBtn.disabled = currentPage === 1;
        if (eventNextBtn) eventNextBtn.disabled = currentPage === totalPages();

        renderPageBtns();
    }

    function renderPageBtns() {
        if (!eventPageBtns) return;
        const total = totalPages();
        eventPageBtns.innerHTML = '';

        const pages = [];
        if (total <= 5) {
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > 3) pages.push('…');
            for (let i = Math.max(2, currentPage - 1); i <= Math.min(total - 1, currentPage + 1); i++) {
                pages.push(i);
            }
            if (currentPage < total - 2) pages.push('…');
            pages.push(total);
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
            btn.addEventListener('click', () => renderPage(p));
            eventPageBtns.appendChild(btn);
        });
    }

    // ── Load list ────────────────────────────────────────────────

    async function napDanhSachSuKien() {
        if (eventListLoading) eventListLoading.classList.remove('hidden');
        if (eventList) eventList.classList.add('hidden');
        if (eventListEmpty) eventListEmpty.classList.add('hidden');
        if (eventPagination) eventPagination.classList.add('hidden');

        try {
            allEvents = await layDanhSachSuKien();

            if (eventListLoading) eventListLoading.classList.add('hidden');

            if (allEvents.length === 0) {
                if (eventListEmpty) eventListEmpty.classList.remove('hidden');
                return;
            }

            eventList.classList.remove('hidden');
            renderPage(1);

            if (eventPagination && totalPages() > 1) {
                eventPagination.classList.remove('hidden');
            }
        } catch {
            if (eventListLoading) eventListLoading.classList.add('hidden');
            if (eventListEmpty) eventListEmpty.classList.remove('hidden');
        }
    }

    napDanhSachSuKien();

    if (eventPrevBtn) eventPrevBtn.addEventListener('click', () => renderPage(currentPage - 1));
    if (eventNextBtn) eventNextBtn.addEventListener('click', () => renderPage(currentPage + 1));

    function taoOptionsCap(capList) {
        const firstOption = '<option value="">-- Chọn cấp tổ chức --</option>';
        const dynamicOptions = capList
            .map((item) => {
                const idCap = Number(item.idCap || 0);
                const tenCap = String(item.tenCap || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const tenLoaiCap = String(item.tenLoaiCap || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return `<option value="${idCap}" data-ten-cap="${tenCap}" data-ten-loai-cap="${tenLoaiCap}">${tenCap}${tenLoaiCap ? ` (${tenLoaiCap})` : ''}</option>`;
            })
            .join('');

        return firstOption + dynamicOptions;
    }

    if (openCreateEventBtn) openCreateEventBtn.addEventListener('click', async function () {
        let capList = [];
        try {
            capList = await layDanhSachCapToChuc();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Không tải được cấp tổ chức',
                text: error.message || 'Vui lòng thử lại sau.',
            });
            return;
        }

        const { value: formValues } = await Swal.fire({
            title: 'Tạo sự kiện mới',
            width: 640,
            padding: '1.25rem',
            heightAuto: false,
            html:
                '<div class="text-left mt-1 space-y-3">' +
                '<div class="grid grid-cols-2 gap-3">' +
                '<div>' +
                '<label for="swal-tenSK" class="block mb-1 text-xs font-semibold text-slate-700">Tên sự kiện</label>' +
                '<input id="swal-tenSK" class="swal2-input !w-full !mx-0 !mt-0 !mb-0 !h-11" placeholder="Nhập tên sự kiện" />' +
                '</div>' +
                '<div>' +
                '<label for="swal-idCap" class="block mb-1 text-xs font-semibold text-slate-700">Cấp tổ chức</label>' +
                `<select id="swal-idCap" class="swal2-select !w-full !mx-0 !mt-0 !mb-0 !h-11">${taoOptionsCap(capList)}</select>` +
                '</div>' +
                '</div>' +

                '<div>' +
                '<label for="swal-moTa" class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>' +
                '<textarea id="swal-moTa" class="swal2-textarea !w-full !mx-0 !mt-0 !mb-0 min-h-[84px]" placeholder="Mô tả ngắn về sự kiện"></textarea>' +
                '</div>' +

                '<div class="grid grid-cols-2 gap-3">' +
                '<div>' +
                '<label for="swal-ngayBatDau" class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>' +
                '<input id="swal-ngayBatDau" type="datetime-local" class="swal2-input !w-full !m-0 !h-11" />' +
                '</div>' +
                '<div>' +
                '<label for="swal-ngayKetThuc" class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>' +
                '<input id="swal-ngayKetThuc" type="datetime-local" class="swal2-input !w-full !m-0 !h-11" />' +
                '</div>' +
                '</div>' +

                '<div class="rounded-xl border border-slate-200 bg-slate-50 p-3">' +
                '<label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">' +
                '<input id="swal-coGVHDTheoSuKien" type="checkbox" class="h-4 w-4 accent-[#7d1f2e]" checked />' +
                'Sự kiện có giảng viên hướng dẫn (GVHD)' +
                '</label>' +
                '<p class="mt-1 text-xs text-slate-500">Nếu tắt, toàn bộ luồng mời/xin/duyệt GVHD sẽ bị loại trừ cho sự kiện này.</p>' +
                '</div>' +
                '</div>',
            focusConfirm: false,
            showCancelButton: true,
            buttonsStyling: false,
            customClass: {
                popup: '!rounded-2xl',
                actions: '!w-full !mt-5 !gap-3 !justify-end',
                confirmButton: 'inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-dark rounded-lg transition-colors min-w-[140px]',
                cancelButton: 'inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors min-w-[120px]',
            },
            confirmButtonText: 'Tạo sự kiện',
            cancelButtonText: 'Huỷ',
            preConfirm: () => {
                const tenSK = document.getElementById('swal-tenSK').value.trim();
                const moTa = document.getElementById('swal-moTa').value.trim();
                const capSelect = document.getElementById('swal-idCap');
                const idCapRaw = capSelect.value.trim();
                const ngayBatDauRaw = document.getElementById('swal-ngayBatDau').value;
                const ngayKetThucRaw = document.getElementById('swal-ngayKetThuc').value;
                const coGVHDTheoSuKien = document.getElementById('swal-coGVHDTheoSuKien').checked;

                if (!tenSK) {
                    Swal.showValidationMessage('Tên sự kiện không được để trống');
                    return false;
                }

                if (!idCapRaw) {
                    Swal.showValidationMessage('Vui lòng chọn cấp tổ chức');
                    return false;
                }

                const selectedOption = capSelect.options[capSelect.selectedIndex];
                const tenCap = selectedOption?.dataset?.tenCap || '';
                const tenLoaiCap = selectedOption?.dataset?.tenLoaiCap || '';

                return {
                    ten_su_kien: tenSK,
                    mo_ta: moTa,
                    id_cap: Number(idCapRaw),
                    ngay_bat_dau: normalizeDateTime(ngayBatDauRaw),
                    ngay_ket_thuc: normalizeDateTime(ngayKetThucRaw),
                    is_active: 1,
                    co_gvhd_theo_su_kien: coGVHDTheoSuKien ? 1 : 0,
                    ten_cap_label: tenCap,
                    ten_loai_cap_label: tenLoaiCap,
                };
            },
        });

        if (!formValues) {
            return;
        }

        try {
            const response = await fetch('/api/su_kien/tao_su_kien.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(formValues),
            });

            const payload = await response.json();

            if (payload.status === 'success') {
                await Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: payload.message || 'Đã tạo sự kiện mới',
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                });
                await napDanhSachSuKien();
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Không thể tạo sự kiện',
                text: payload.message || 'Có lỗi xảy ra',
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi kết nối',
                text: 'Không thể gọi API tạo sự kiện. Vui lòng thử lại.',
            });
        }
    });
}); // end openCreateEventBtn listener