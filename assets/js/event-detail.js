document.addEventListener('DOMContentLoaded', function () {
    const idSk = Number(window.EVENT_DETAIL_ID || 0);
    const currentTab = String(window.EVENT_DETAIL_TAB || 'overview');

    const loadingEl = document.getElementById('eventDetailLoading');
    const errorEl = document.getElementById('eventDetailError');
    const contentEl = document.getElementById('eventDetailContent');

    const titleEl = document.getElementById('eventTitle');
    const subtitleEl = document.getElementById('eventSubtitle');
    const sidebarEventNameEl = document.getElementById('sidebarEventName');

    const detailMoTa = document.getElementById('detailMoTa');
    const detailCap = document.getElementById('detailCap');
    const detailTrangThai = document.getElementById('detailTrangThai');
    const detailNgayMoDK = document.getElementById('detailNgayMoDK');
    const detailNgayDongDK = document.getElementById('detailNgayDongDK');
    const detailNgayBatDau = document.getElementById('detailNgayBatDau');
    const detailNgayKetThuc = document.getElementById('detailNgayKetThuc');
    const detailCheDoSV = document.getElementById('detailCheDoSV');
    const detailCheDoGV = document.getElementById('detailCheDoGV');

    const configTenSuKien = document.getElementById('configTenSuKien');
    const configCapToChuc = document.getElementById('configCapToChuc');
    const configCheDoSV = document.getElementById('configCheDoSV');
    const configCheDoGV = document.getElementById('configCheDoGV');

    const basicTenSuKien = document.getElementById('basicTenSuKien');
    const basicMoTa = document.getElementById('basicMoTa');
    const basicIdCap = document.getElementById('basicIdCap');
    const basicTrangThaiText = document.getElementById('basicTrangThaiText');
    const basicNgayMoDK = document.getElementById('basicNgayMoDK');
    const basicNgayDongDK = document.getElementById('basicNgayDongDK');
    const basicNgayBatDau = document.getElementById('basicNgayBatDau');
    const basicNgayKetThuc = document.getElementById('basicNgayKetThuc');
    const btnSaveBasicConfig = document.getElementById('btnSaveBasicConfig');
    const btnToggleEventStatus = document.getElementById('btnToggleEventStatus');
    const btnCreateRound = document.getElementById('btnCreateRound');
    const basicRoundList = document.getElementById('basicRoundList');

    let eventDetailCache = null;

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

    function capText(detail) {
        const tenCap = String(detail.tenCap || '').trim();
        const tenLoaiCap = String(detail.tenLoaiCap || '').trim();

        if (!tenCap && !tenLoaiCap) {
            return '--';
        }

        return tenLoaiCap ? `${tenCap} (${tenLoaiCap})` : tenCap;
    }

    async function layChiTietSuKien(id) {
        const response = await fetch(`/api/su_kien/chi_tiet_su_kien.php?id_sk=${encodeURIComponent(id)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });

        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không lấy được chi tiết sự kiện');
        }

        return payload.data;
    }

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

    async function capNhatSuKien(payload) {
        const response = await fetch('/api/su_kien/cap_nhat_su_kien.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        const result = await response.json();
        if (result.status !== 'success') {
            throw new Error(result.message || 'Không thể cập nhật sự kiện');
        }

        return result.data || {};
    }

    async function layDanhSachVongThi(id) {
        const response = await fetch(`/api/su_kien/danh_sach_vong_thi.php?id_sk=${encodeURIComponent(id)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !Array.isArray(payload.data)) {
            throw new Error(payload.message || 'Không lấy được danh sách vòng thi');
        }
        return payload.data;
    }

    async function taoVongThi(payload) {
        const response = await fetch('/api/su_kien/tao_vong_thi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (result.status !== 'success') {
            throw new Error(result.message || 'Không thể tạo vòng thi');
        }
        return result.data || {};
    }

    function toDatetimeLocal(value) {
        if (!value) {
            return '';
        }

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - offset * 60000);
        return localDate.toISOString().slice(0, 16);
    }

    function toDatabaseDateTime(value) {
        return value ? `${value.replace('T', ' ')}:00` : null;
    }

    function renderRoundList(rounds) {
        if (!basicRoundList) {
            return;
        }

        if (!Array.isArray(rounds) || rounds.length === 0) {
            basicRoundList.innerHTML = '<div class="px-3 py-3 text-sm border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Chưa có vòng thi nào. Hãy bấm "Thêm vòng thi" để tạo mới.</div>';
            return;
        }

        basicRoundList.innerHTML = rounds
            .map((round) => {
                const tenVong = String(round.tenVongThi || '--');
                const thuTu = Number(round.thuTu || 0);
                const ngayBatDau = formatDateTime(round.ngayBatDau);
                const ngayKetThuc = formatDateTime(round.ngayKetThuc);
                const moTa = String(round.moTa || '').trim();

                return `<div class="p-3 border rounded-lg border-slate-200 bg-slate-50">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <p class="mb-0 text-sm font-semibold text-slate-700">${tenVong}</p>
                                <span class="px-2 py-1 text-xs font-bold rounded-md bg-white border border-slate-200 text-slate-600">Vòng ${thuTu}</span>
                            </div>
                            <p class="mb-1 text-xs text-slate-500">${ngayBatDau} → ${ngayKetThuc}</p>
                            <p class="mb-0 text-xs text-slate-500">${moTa || 'Không có mô tả.'}</p>
                        </div>`;
            })
            .join('');
    }

    async function napDanhSachVongThi() {
        if (!basicRoundList || idSk <= 0) {
            return;
        }

        basicRoundList.innerHTML = '<div class="px-3 py-3 text-sm border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Đang tải danh sách vòng thi...</div>';

        try {
            const rounds = await layDanhSachVongThi(idSk);
            renderRoundList(rounds);
        } catch (error) {
            basicRoundList.innerHTML = `<div class="px-3 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600">${error.message || 'Không tải được danh sách vòng thi'}</div>`;
        }
    }

    async function napDanhSachCapVaoSelect(idCapHienTai = null) {
        if (!basicIdCap) {
            return;
        }

        basicIdCap.innerHTML = '<option value="">-- Chọn cấp tổ chức --</option>';

        try {
            const caps = await layDanhSachCapToChuc();
            caps.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.idCap || '');
                option.textContent = `${item.tenCap || ''}${item.tenLoaiCap ? ` (${item.tenLoaiCap})` : ''}`;
                if (idCapHienTai !== null && Number(item.idCap) === Number(idCapHienTai)) {
                    option.selected = true;
                }
                basicIdCap.appendChild(option);
            });
        } catch (error) {
            basicIdCap.innerHTML = '<option value="">Không tải được cấp tổ chức</option>';
        }
    }

    function doDuLieuVaoBasicForm(detail) {
        if (!detail) {
            return;
        }

        if (basicTenSuKien) basicTenSuKien.value = detail.tenSK || '';
        if (basicMoTa) basicMoTa.value = detail.moTa || '';
        if (basicNgayMoDK) basicNgayMoDK.value = toDatetimeLocal(detail.ngayMoDangKy);
        if (basicNgayDongDK) basicNgayDongDK.value = toDatetimeLocal(detail.ngayDongDangKy);
        if (basicNgayBatDau) basicNgayBatDau.value = toDatetimeLocal(detail.ngayBatDau);
        if (basicNgayKetThuc) basicNgayKetThuc.value = toDatetimeLocal(detail.ngayKetThuc);
        if (basicTrangThaiText) basicTrangThaiText.textContent = Number(detail.isActive || 0) === 1 ? 'Đang mở' : 'Đang đóng';
    }

    function hienThiLoi(message) {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (contentEl) contentEl.classList.add('hidden');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    }

    async function khoiTaoTrangChiTiet() {
        if (idSk <= 0) {
            hienThiLoi('ID sự kiện không hợp lệ. Vui lòng quay lại danh sách và thử lại.');
            return;
        }

        try {
            const detail = await layChiTietSuKien(idSk);
            eventDetailCache = detail;

            const trangThai = Number(detail.isActive || 0) === 1 ? 'Đang mở' : 'Tạm ẩn';

            if (titleEl) titleEl.textContent = detail.tenSK || 'Chi tiết sự kiện';
            if (subtitleEl) subtitleEl.textContent = `Mã sự kiện: #${detail.idSK || idSk}`;
            if (sidebarEventNameEl) sidebarEventNameEl.textContent = detail.tenSK || `Sự kiện #${idSk}`;

            if (detailMoTa) detailMoTa.textContent = detail.moTa || '--';
            if (detailCap) detailCap.textContent = capText(detail);
            if (detailTrangThai) detailTrangThai.textContent = trangThai;
            if (detailNgayMoDK) detailNgayMoDK.textContent = formatDateTime(detail.ngayMoDangKy);
            if (detailNgayDongDK) detailNgayDongDK.textContent = formatDateTime(detail.ngayDongDangKy);
            if (detailNgayBatDau) detailNgayBatDau.textContent = formatDateTime(detail.ngayBatDau);
            if (detailNgayKetThuc) detailNgayKetThuc.textContent = formatDateTime(detail.ngayKetThuc);
            if (detailCheDoSV) detailCheDoSV.textContent = detail.cheDoDangKySV || '--';
            if (detailCheDoGV) detailCheDoGV.textContent = detail.cheDoDangKyGV || '--';

            if (configTenSuKien) configTenSuKien.textContent = detail.tenSK || '--';
            if (configCapToChuc) configCapToChuc.textContent = capText(detail);
            if (configCheDoSV) configCheDoSV.textContent = detail.cheDoDangKySV || '--';
            if (configCheDoGV) configCheDoGV.textContent = detail.cheDoDangKyGV || '--';

            if (currentTab === 'config-basic') {
                doDuLieuVaoBasicForm(detail);
                await napDanhSachCapVaoSelect(detail.idCap || null);
                await napDanhSachVongThi();
            }

            if (currentTab !== 'overview' && subtitleEl) {
                subtitleEl.textContent = `${subtitleEl.textContent} • Tab: ${currentTab}`;
            }

            if (loadingEl) loadingEl.classList.add('hidden');
            if (errorEl) errorEl.classList.add('hidden');
            if (contentEl) contentEl.classList.remove('hidden');
        } catch (error) {
            hienThiLoi(error.message || 'Không tải được chi tiết sự kiện. Vui lòng thử lại.');
        }
    }

    khoiTaoTrangChiTiet();

    if (btnSaveBasicConfig) {
        btnSaveBasicConfig.addEventListener('click', async function () {
            if (!eventDetailCache) {
                return;
            }

            const payload = {
                id_su_kien: idSk,
                ten_su_kien: basicTenSuKien ? basicTenSuKien.value.trim() : '',
                mo_ta: basicMoTa ? basicMoTa.value.trim() : '',
                id_cap: basicIdCap && basicIdCap.value ? Number(basicIdCap.value) : null,
                ngay_mo_dk: basicNgayMoDK ? toDatabaseDateTime(basicNgayMoDK.value) : null,
                ngay_dong_dk: basicNgayDongDK ? toDatabaseDateTime(basicNgayDongDK.value) : null,
                ngay_bat_dau: basicNgayBatDau ? toDatabaseDateTime(basicNgayBatDau.value) : null,
                ngay_ket_thuc: basicNgayKetThuc ? toDatabaseDateTime(basicNgayKetThuc.value) : null,
                is_active: Number(eventDetailCache.isActive || 0) === 1 ? 1 : 0,
            };

            try {
                await capNhatSuKien(payload);

                eventDetailCache.tenSK = payload.ten_su_kien;
                eventDetailCache.moTa = payload.mo_ta;
                eventDetailCache.idCap = payload.id_cap;
                eventDetailCache.ngayMoDangKy = payload.ngay_mo_dk;
                eventDetailCache.ngayDongDangKy = payload.ngay_dong_dk;
                eventDetailCache.ngayBatDau = payload.ngay_bat_dau;
                eventDetailCache.ngayKetThuc = payload.ngay_ket_thuc;

                if (titleEl) titleEl.textContent = payload.ten_su_kien || titleEl.textContent;
                if (sidebarEventNameEl) sidebarEventNameEl.textContent = payload.ten_su_kien || sidebarEventNameEl.textContent;

                await Swal.fire({
                    icon: 'success',
                    title: 'Đã lưu',
                    text: 'Cập nhật thông tin sự kiện thành công.',
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể lưu',
                    text: error.message || 'Vui lòng thử lại.',
                });
            }
        });
    }

    if (btnToggleEventStatus) {
        btnToggleEventStatus.addEventListener('click', async function () {
            if (!eventDetailCache) {
                return;
            }

            const isActive = Number(eventDetailCache.isActive || 0) === 1;
            const nextStatus = isActive ? 0 : 1;

            const confirm = await Swal.fire({
                icon: 'question',
                title: isActive ? 'Đóng sự kiện?' : 'Mở sự kiện?',
                text: isActive ? 'Sự kiện sẽ chuyển sang trạng thái đóng.' : 'Sự kiện sẽ chuyển sang trạng thái mở.',
                showCancelButton: true,
                confirmButtonText: isActive ? 'Đóng sự kiện' : 'Mở sự kiện',
                cancelButtonText: 'Huỷ',
            });

            if (!confirm.isConfirmed) {
                return;
            }

            const payload = {
                id_su_kien: idSk,
                ten_su_kien: basicTenSuKien ? basicTenSuKien.value.trim() : (eventDetailCache.tenSK || ''),
                mo_ta: basicMoTa ? basicMoTa.value.trim() : (eventDetailCache.moTa || ''),
                id_cap: basicIdCap && basicIdCap.value ? Number(basicIdCap.value) : (eventDetailCache.idCap || null),
                ngay_mo_dk: basicNgayMoDK ? toDatabaseDateTime(basicNgayMoDK.value) : eventDetailCache.ngayMoDangKy,
                ngay_dong_dk: basicNgayDongDK ? toDatabaseDateTime(basicNgayDongDK.value) : eventDetailCache.ngayDongDangKy,
                ngay_bat_dau: basicNgayBatDau ? toDatabaseDateTime(basicNgayBatDau.value) : eventDetailCache.ngayBatDau,
                ngay_ket_thuc: basicNgayKetThuc ? toDatabaseDateTime(basicNgayKetThuc.value) : eventDetailCache.ngayKetThuc,
                is_active: nextStatus,
            };

            try {
                await capNhatSuKien(payload);
                eventDetailCache.isActive = nextStatus;
                if (basicTrangThaiText) basicTrangThaiText.textContent = nextStatus === 1 ? 'Đang mở' : 'Đang đóng';

                await Swal.fire({
                    icon: 'success',
                    title: 'Thành công',
                    text: nextStatus === 1 ? 'Đã mở sự kiện.' : 'Đã đóng sự kiện.',
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể cập nhật trạng thái',
                    text: error.message || 'Vui lòng thử lại.',
                });
            }
        });
    }

    if (btnCreateRound) {
        btnCreateRound.addEventListener('click', async function () {
            const { value: formValues } = await Swal.fire({
                title: 'Thêm vòng thi',
                width: 620,
                html:
                    '<div class="text-left space-y-3">' +
                    '<div>' +
                    '<label class="block mb-1 text-xs font-semibold text-slate-700">Tên vòng thi</label>' +
                    '<input id="swal-tenVongThi" class="swal2-input !w-full !m-0" placeholder="Ví dụ: Vòng sơ loại" />' +
                    '</div>' +
                    '<div>' +
                    '<label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả vòng thi</label>' +
                    '<textarea id="swal-moTaVongThi" class="swal2-textarea !w-full !m-0 min-h-[84px]" placeholder="Mô tả mục tiêu của vòng thi"></textarea>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 gap-3 md:grid-cols-3">' +
                    '<div>' +
                    '<label class="block mb-1 text-xs font-semibold text-slate-700">Thứ tự</label>' +
                    '<input id="swal-thuTuVongThi" type="number" class="swal2-input !w-full !m-0" min="1" value="1" />' +
                    '</div>' +
                    '<div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">' +
                    '<div>' +
                    '<label class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>' +
                    '<input id="swal-ngayBDVongThi" type="datetime-local" class="swal2-input !w-full !m-0" />' +
                    '</div>' +
                    '<div>' +
                    '<label class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>' +
                    '<input id="swal-ngayKTVongThi" type="datetime-local" class="swal2-input !w-full !m-0" />' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                showCancelButton: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'swal-round-popup',
                    confirmButton: 'swal-round-confirm',
                    cancelButton: 'swal-round-cancel',
                    actions: 'swal-round-actions',
                },
                confirmButtonText: 'Tạo vòng thi',
                cancelButtonText: 'Huỷ',
                preConfirm: () => {
                    const tenVong = document.getElementById('swal-tenVongThi').value.trim();
                    const moTa = document.getElementById('swal-moTaVongThi').value.trim();
                    const thuTuRaw = document.getElementById('swal-thuTuVongThi').value;
                    const ngayBDRaw = document.getElementById('swal-ngayBDVongThi').value;
                    const ngayKTRaw = document.getElementById('swal-ngayKTVongThi').value;

                    if (!tenVong) {
                        Swal.showValidationMessage('Tên vòng thi không được để trống');
                        return false;
                    }

                    return {
                        id_sk: idSk,
                        ten_vong: tenVong,
                        mo_ta: moTa,
                        thu_tu: Number(thuTuRaw || 1),
                        ngay_bat_dau: toDatabaseDateTime(ngayBDRaw),
                        ngay_ket_thuc: toDatabaseDateTime(ngayKTRaw),
                    };
                },
            });

            if (!formValues) {
                return;
            }

            try {
                await taoVongThi(formValues);
                await napDanhSachVongThi();
                Swal.fire({
                    icon: 'success',
                    title: 'Đã tạo vòng thi',
                    text: 'Vòng thi mới đã được thêm vào sự kiện.',
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể tạo vòng thi',
                    text: error.message || 'Vui lòng thử lại.',
                });
            }
        });
    }
});
