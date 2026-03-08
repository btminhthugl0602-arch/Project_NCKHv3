document.addEventListener('DOMContentLoaded', function () {
    const openCreateEventBtn = document.getElementById('openCreateEventBtn');
    const eventList = document.getElementById('eventList');
    const eventListBody = document.getElementById('eventListBody');
    const eventListEmpty = document.getElementById('eventListEmpty');

    // openCreateEventBtn chỉ hiện với người có quyền tao_su_kien
    // nhưng list sự kiện vẫn cần load cho tất cả user

    const normalizeDateTime = (value) => (value ? value.replace('T', ' ') + ':00' : null);

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

    function formatDateTime(value) {
        if (!value) {
            return '--';
        }

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString('vi-VN', {
            hour12: false,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function capText(item) {
        const tenCap = String(item.tenCap || '').trim();
        const tenLoaiCap = String(item.tenLoaiCap || '').trim();

        if (!tenCap && !tenLoaiCap) {
            return '--';
        }

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

    function taoDongSuKien(item, prepend = false) {
        if (!eventListBody || !eventList || !eventListEmpty) {
            return;
        }

        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-100';

        const trangThai = Number(item.isActive || 0) === 1
            ? '<span class="px-2 py-1 text-xs font-semibold rounded-lg bg-emerald-100 text-emerald-600">Đang mở</span>'
            : '<span class="px-2 py-1 text-xs font-semibold rounded-lg bg-slate-200 text-slate-600">Tạm ẩn</span>';

        const idSk = Number(item.idSK || 0);
        const tenSuKienHtml = idSk > 0
            ? `<a href="/event-detail?id_sk=${idSk}" class="event-title-link text-sm font-semibold">${escapeHtml(item.tenSK)}</a>`
            : `<p class="mb-0 text-sm font-semibold text-slate-700">${escapeHtml(item.tenSK)}</p>`;

        tr.innerHTML =
            `<td class="p-3 align-middle">${tenSuKienHtml}</td>` +
            `<td class="p-3 align-middle"><p class="mb-0 text-sm">${escapeHtml(capText(item))}</p></td>` +
            `<td class="p-3 align-middle"><p class="mb-0 text-sm">${formatDateTime(item.ngayBatDau)} → ${formatDateTime(item.ngayKetThuc)}</p></td>` +
            `<td class="p-3 align-middle">${trangThai}</td>`;

        if (prepend && eventListBody.firstChild) {
            eventListBody.insertBefore(tr, eventListBody.firstChild);
        } else {
            eventListBody.appendChild(tr);
        }

        eventList.classList.remove('hidden');
        eventListEmpty.classList.add('hidden');
    }

    async function napDanhSachSuKien() {
        if (!eventListBody || !eventList || !eventListEmpty) {
            return;
        }

        const loadingEl = document.getElementById('eventListLoading');

        // Hiện spinner, ẩn list và empty state
        if (loadingEl) loadingEl.classList.remove('hidden');
        eventList.classList.add('hidden');
        eventListEmpty.classList.add('hidden');

        try {
            const events = await layDanhSachSuKien();
            eventListBody.innerHTML = '';

            if (loadingEl) loadingEl.classList.add('hidden');

            if (events.length === 0) {
                eventListEmpty.classList.remove('hidden');
                return;
            }

            events.forEach((item) => taoDongSuKien(item));
        } catch (error) {
            if (loadingEl) loadingEl.classList.add('hidden');
            eventListEmpty.classList.remove('hidden');
            eventListEmpty.textContent = 'Không thể tải danh sách sự kiện. Vui lòng thử lại.';
        }
    }

    napDanhSachSuKien();

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
                '</div>',
            focusConfirm: false,
            showCancelButton: true,
            buttonsStyling: false,
            customClass: {
                popup: '!rounded-2xl',
                actions: '!w-full !mt-5 !gap-3 !justify-end',
                confirmButton: 'inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white uppercase align-middle transition-all bg-gradient-to-tl from-purple-700 to-pink-500 border-0 !rounded-lg cursor-pointer shadow-soft-md leading-pro ease-soft-in tracking-tight-soft min-w-[140px]',
                cancelButton: 'inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-slate-700 uppercase align-middle transition-all bg-slate-100 border border-slate-300 !rounded-lg cursor-pointer leading-pro ease-soft-in tracking-tight-soft min-w-[120px]',
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