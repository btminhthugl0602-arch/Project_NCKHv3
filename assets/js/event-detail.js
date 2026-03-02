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
});
