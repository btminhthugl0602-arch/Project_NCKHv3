document.addEventListener('DOMContentLoaded', function () {
    // Get base path for API calls
    const BASE_PATH = window.APP_BASE_PATH || '';

    const idSk = Number(window.EVENT_DETAIL_ID || 0);
    const currentTab = String(window.EVENT_DETAIL_TAB || 'overview');
    const isGuest = window.IS_GUEST === true;

    // Global 401/403 handler — nếu API trả về 401/403, redirect về login
    const _origFetch = window.fetch;
    window.fetch = async function (...args) {
        const res = await _origFetch(...args);
        if ((res.status === 401 || res.status === 403) && isGuest) {
            window.location.href = '/sign-in?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        }
        return res;
    };

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

    const ruleInputThuocTinh = document.getElementById('ruleInputThuocTinh');
    const ruleInputToanTu = document.getElementById('ruleInputToanTu');
    const ruleInputGiaTri = document.getElementById('ruleInputGiaTri');
    const btnAddCondition = document.getElementById('btnAddCondition');
    const conditionPool = document.getElementById('conditionPool');
    const tokenAnd = document.getElementById('tokenAnd');
    const tokenOr = document.getElementById('tokenOr');
    const tokenOpen = document.getElementById('tokenOpen');
    const tokenClose = document.getElementById('tokenClose');
    const tokenBackspace = document.getElementById('tokenBackspace');
    const tokenClear = document.getElementById('tokenClear');
    const tokenPreview = document.getElementById('tokenPreview');
    const tokenError = document.getElementById('tokenError');
    const ruleNaturalPreview = document.getElementById('ruleNaturalPreview');
    const ruleGiaTriSuggestions = document.getElementById('ruleGiaTriSuggestions');
    const ruleGiaTriHint = document.getElementById('ruleGiaTriHint');
    const ruleNameInput = document.getElementById('ruleNameInput');
    const ruleTypeInput = document.getElementById('ruleTypeInput');
    const rulesJsonInput = document.getElementById('rules_json');
    const btnSaveRuleConfig = document.getElementById('btnSaveRuleConfig');
    const astStatusText = document.getElementById('astStatusText');
    const ruleListContainer = document.getElementById('ruleListContainer');

    const criteriaEditId = document.getElementById('criteriaEditId');
    const criteriaTenBo = document.getElementById('criteriaTenBo');
    const criteriaVongThi = document.getElementById('criteriaVongThi');
    const criteriaMoTa = document.getElementById('criteriaMoTa');
    const criteriaReuseSetDropdown = document.getElementById('criteriaReuseSetDropdown');
    const criteriaCloneSetBtn = document.getElementById('criteriaCloneSetBtn');
    const criteriaAddRow = document.getElementById('criteriaAddRow');
    const criteriaSaveBtn = document.getElementById('criteriaSaveBtn');
    const criteriaResetForm = document.getElementById('criteriaResetForm');
    const criteriaTableBody = document.getElementById('criteriaTableBody');
    const criteriaSetList = document.getElementById('criteriaSetList');
    const criteriaBankList = document.getElementById('criteriaBankList');

    let eventDetailCache = null;
    const conditionsMap = {};
    let conditionCounter = 0;
    const tokens = [];
    let compareOperators = [];
    let criteriaUsageMap = {};
    const allowedRuleTypes = ['THAMGIA_SV', 'THAMGIA_GV', 'VONGTHI', 'SANPHAM', 'GIAITHUONG'];
    const ruleTypeAliasMap = {
        THAMGIA: 'THAMGIA_SV',
        THAMGIA_SV: 'THAMGIA_SV',
        THAMGIA_GV: 'THAMGIA_GV',
        VONGTHI: 'VONGTHI',
        SANPHAM: 'SANPHAM',
        GIAITHUONG: 'GIAITHUONG',
    };
    let activeRuleType = 'THAMGIA_SV';

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
        const response = await fetch(`${BASE_PATH}/api/su_kien/chi_tiet_su_kien.php?id_sk=${encodeURIComponent(id)}`, {
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
        const response = await fetch(`${BASE_PATH}/api/su_kien/danh_sach_cap_to_chuc.php`, {
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
        const response = await fetch(`${BASE_PATH}/api/su_kien/cap_nhat_su_kien.php`, {
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
        const response = await fetch(`${BASE_PATH}/api/su_kien/danh_sach_vong_thi.php?id_sk=${encodeURIComponent(id)}`, {
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
        const response = await fetch(`${BASE_PATH}/api/su_kien/tao_vong_thi.php`, {
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

    async function layMetadataQuyChe(loaiQuyChe) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/quy_che_metadata.php?loai_quy_che=${encodeURIComponent(loaiQuyChe)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không lấy được metadata quy chế');
        }
        return payload.data;
    }

    async function layGoiYGiaTriTheoThuocTinh(idThuocTinh) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/goi_y_gia_tri_thuoc_tinh.php?id_thuoc_tinh=${encodeURIComponent(idThuocTinh)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không lấy được gợi ý giá trị');
        }
        return payload.data;
    }

    async function luuQuyChe(payload) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/luu_quy_che.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (result.status !== 'success') {
            throw new Error(result.message || 'Không thể lưu quy chế');
        }
        return result.data || {};
    }

    async function layDanhSachQuyChe(id, loaiQuyChe) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/danh_sach_quy_che.php?id_sk=${encodeURIComponent(id)}&loai_quy_che=${encodeURIComponent(loaiQuyChe)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !Array.isArray(payload.data)) {
            throw new Error(payload.message || 'Không lấy được danh sách quy chế');
        }
        return payload.data;
    }

    async function layChiTietQuyChe(idQuyChe) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/chi_tiet_quy_che.php?id_quy_che=${encodeURIComponent(idQuyChe)}&id_sk=${encodeURIComponent(idSk)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không lấy được chi tiết quy chế');
        }
        return payload.data;
    }

    async function xoaQuyChe(idQuyChe) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/xoa_quy_che.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id_quy_che: idQuyChe, id_sk: idSk }),
        });
        const payload = await response.json();
        if (payload.status !== 'success') {
            throw new Error(payload.message || 'Không thể xóa quy chế');
        }
        return payload.data || {};
    }

    async function layDuLieuBoTieuChi() {
        const response = await fetch(`${BASE_PATH}/api/su_kien/du_lieu_bo_tieu_chi.php?id_sk=${encodeURIComponent(idSk)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không thể lấy dữ liệu bộ tiêu chí');
        }
        return payload.data;
    }

    async function layChiTietBoTieuChi(idBo) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/chi_tiet_bo_tieu_chi.php?id_sk=${encodeURIComponent(idSk)}&id_bo=${encodeURIComponent(idBo)}`, {
            method: 'GET',
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (payload.status !== 'success' || !payload.data) {
            throw new Error(payload.message || 'Không thể lấy chi tiết bộ tiêu chí');
        }
        return payload.data;
    }

    async function luuBoTieuChi(payload) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/luu_bo_tieu_chi.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Không thể lưu bộ tiêu chí');
        }
        return data.data || {};
    }

    async function xoaBoTieuChi(idBo) {
        const response = await fetch(`${BASE_PATH}/api/su_kien/xoa_bo_tieu_chi.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id_sk: idSk, id_bo: idBo }),
        });
        const data = await response.json();
        if (data.status !== 'success') {
            const error = new Error(data.message || 'Không thể xóa bộ tiêu chí');
            error.hasRelatedData = data.hasRelatedData || false;
            error.relatedData = data.relatedData || [];
            throw error;
        }
        return data.data || {};
    }

    function addCriteriaRow(noiDung = '', diemToiDa = '', tyTrong = '1') {
        if (!criteriaTableBody) {
            return;
        }

        const tr = document.createElement('tr');
        tr.className = 'criteria-row border-b border-slate-100 hover:bg-slate-50/50';
        tr.innerHTML = `
            <td class="px-2 py-2 text-center">
                <span class="criteria-stt text-xs font-semibold text-slate-400"></span>
            </td>
            <td class="px-3 py-2">
                <input type="text" data-field="noi_dung" list="criteriaBankList" class="w-full px-2 py-1.5 text-sm border rounded border-slate-300 focus:border-purple-400 focus:ring-1 focus:ring-purple-200" placeholder="Nhập nội dung tiêu chí..." value="${String(noiDung).replace(/"/g, '&quot;')}" />
            </td>
            <td class="px-3 py-2">
                <input type="number" data-field="diem_toi_da" step="0.5" min="0" class="w-full px-2 py-1.5 text-sm text-center border rounded border-slate-300 focus:border-purple-400 focus:ring-1 focus:ring-purple-200" placeholder="10" value="${diemToiDa === null ? '' : String(diemToiDa)}" />
            </td>
            <td class="px-3 py-2">
                <input type="number" data-field="ty_trong" step="0.1" min="0" class="w-full px-2 py-1.5 text-sm text-center border rounded border-slate-300 focus:border-purple-400 focus:ring-1 focus:ring-purple-200" placeholder="1" value="${String(tyTrong)}" />
            </td>
            <td class="px-2 py-2 text-center">
                <div class="flex items-center justify-center gap-1">
                    <button type="button" class="criteria-row-up p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30" title="Di chuyển lên">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    </button>
                    <button type="button" class="criteria-row-down p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30" title="Di chuyển xuống">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <button type="button" class="criteria-row-remove p-1 text-rose-400 hover:text-rose-600" title="Xóa tiêu chí">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </td>
        `;
        criteriaTableBody.appendChild(tr);
        updateCriteriaSTT();
        updateCriteriaTotals();
    }

    function updateCriteriaSTT() {
        if (!criteriaTableBody) return;
        const rows = criteriaTableBody.querySelectorAll('.criteria-row');
        rows.forEach((row, index) => {
            const stt = row.querySelector('.criteria-stt');
            if (stt) stt.textContent = String(index + 1);

            // Disable nút lên cho dòng đầu, nút xuống cho dòng cuối
            const upBtn = row.querySelector('.criteria-row-up');
            const downBtn = row.querySelector('.criteria-row-down');
            if (upBtn) upBtn.disabled = index === 0;
            if (downBtn) downBtn.disabled = index === rows.length - 1;
        });
    }

    function updateCriteriaTotals() {
        const totalDiemEl = document.getElementById('criteriaTotalDiem');
        const totalTyTrongEl = document.getElementById('criteriaTotalTyTrong');
        if (!criteriaTableBody || !totalDiemEl || !totalTyTrongEl) return;

        let totalDiem = 0;
        let totalTyTrong = 0;

        criteriaTableBody.querySelectorAll('.criteria-row').forEach((row) => {
            const diemInput = row.querySelector('[data-field="diem_toi_da"]');
            const tyTrongInput = row.querySelector('[data-field="ty_trong"]');

            const diem = parseFloat(diemInput?.value || 0);
            const tyTrong = parseFloat(tyTrongInput?.value || 0);

            if (!isNaN(diem)) totalDiem += diem;
            if (!isNaN(tyTrong)) totalTyTrong += tyTrong;
        });

        totalDiemEl.textContent = totalDiem.toFixed(1).replace(/\.0$/, '');
        totalTyTrongEl.textContent = totalTyTrong.toFixed(1).replace(/\.0$/, '');
    }

    function moveCriteriaRow(row, direction) {
        if (!criteriaTableBody || !row) return;

        if (direction === 'up') {
            const prev = row.previousElementSibling;
            if (prev) {
                criteriaTableBody.insertBefore(row, prev);
            }
        } else {
            const next = row.nextElementSibling;
            if (next) {
                criteriaTableBody.insertBefore(next, row);
            }
        }

        updateCriteriaSTT();
    }

    function resetCriteriaForm() {
        if (criteriaEditId) criteriaEditId.value = '0';
        if (criteriaTenBo) criteriaTenBo.value = '';
        if (criteriaMoTa) criteriaMoTa.value = '';
        if (criteriaVongThi) criteriaVongThi.value = '0';
        if (criteriaTableBody) criteriaTableBody.innerHTML = '';
        addCriteriaRow();
    }

    function renderCriteriaSetList(sets = []) {
        if (!criteriaSetList) {
            return;
        }

        if (!Array.isArray(sets) || sets.length === 0) {
            criteriaSetList.innerHTML = '<div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Chưa có bộ tiêu chí nào. Tạo mới ở form bên trái.</div>';
            return;
        }

        criteriaSetList.innerHTML = sets.map((set) => {
            const idBo = Number(set.idBoTieuChi || 0);
            const usage = Array.isArray(criteriaUsageMap[idBo]) ? criteriaUsageMap[idBo] : [];

            // Phân loại usage theo loại
            const vongThiUsage = usage.filter((item) => item.loai === 'vong');
            const tieubanUsage = usage.filter((item) => item.loai === 'tieuban');

            let usageHtml = '';
            if (vongThiUsage.length > 0 || tieubanUsage.length > 0) {
                // Có đang sử dụng
                usageHtml = '<div class="flex flex-wrap gap-1">';
                vongThiUsage.forEach((item) => {
                    usageHtml += `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        ${item.text || ''}
                    </span>`;
                });
                tieubanUsage.forEach((item) => {
                    usageHtml += `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-cyan-100 text-cyan-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        ${item.text || ''}
                    </span>`;
                });
                usageHtml += '</div>';
            } else {
                // Chưa sử dụng
                usageHtml = `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-slate-100 text-slate-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Chưa gán trong sự kiện này
                </span>`;
            }

            const canDelete = usage.length === 0;

            return `<div class="p-3 border rounded-lg border-slate-200 bg-slate-50 hover:border-slate-300 transition-colors">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="mb-0 text-sm font-semibold text-slate-700 truncate">${set.tenBoTieuChi || '--'}</p>
                                <p class="mb-0 text-xs text-slate-500 line-clamp-2">${set.moTa || 'Không có mô tả'}</p>
                            </div>
                            <span class="px-1.5 py-0.5 text-xs font-mono rounded bg-white border border-slate-200 text-slate-400 shrink-0">#${idBo}</span>
                        </div>
                        <div class="mb-2">${usageHtml}</div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" data-criteria-clone="${idBo}" class="criteria-clone-btn inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded border border-slate-300 bg-white text-slate-600 hover:bg-slate-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                Nhân bản
                            </button>
                            <button type="button" data-criteria-edit="${idBo}" class="criteria-edit-btn inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded border border-blue-300 bg-white text-blue-600 hover:bg-blue-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Sửa
                            </button>
                            ${canDelete ? `
                            <button type="button" data-criteria-delete="${idBo}" class="criteria-delete-btn inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded border border-rose-300 bg-white text-rose-600 hover:bg-rose-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Xóa
                            </button>
                            ` : `
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded border border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed" title="Không thể xóa vì đang được sử dụng">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Đang dùng
                            </span>
                            `}
                        </div>
                    </div>`;
        }).join('');
    }

    function collectCriteriaRows() {
        if (!criteriaTableBody) {
            return [];
        }

        const rows = [...criteriaTableBody.querySelectorAll('.criteria-row')];
        return rows
            .map((row) => {
                const noiDung = (row.querySelector('[data-field="noi_dung"]')?.value || '').trim();
                const diemToiDa = (row.querySelector('[data-field="diem_toi_da"]')?.value || '').trim();
                const tyTrong = (row.querySelector('[data-field="ty_trong"]')?.value || '').trim();

                return {
                    noi_dung: noiDung,
                    diem_toi_da: diemToiDa === '' ? null : Number(diemToiDa),
                    ty_trong: tyTrong === '' ? 1 : Number(tyTrong),
                };
            })
            .filter((item) => item.noi_dung !== '');
    }

    async function doDuLieuBoTieuChiVaoForm(idBo, mode) {
        const detail = await layChiTietBoTieuChi(idBo);
        const master = detail.master || {};
        const details = Array.isArray(detail.details) ? detail.details : [];

        if (criteriaEditId) criteriaEditId.value = mode === 'edit' ? String(idBo) : '0';
        if (criteriaTenBo) criteriaTenBo.value = mode === 'clone' ? `${master.tenBoTieuChi || ''} (Bản sao)` : (master.tenBoTieuChi || '');
        if (criteriaMoTa) criteriaMoTa.value = master.moTa || '';
        if (criteriaVongThi) criteriaVongThi.value = mode === 'edit' ? String(master.idVongThi || 0) : '0';

        if (criteriaTableBody) {
            criteriaTableBody.innerHTML = '';
            if (details.length === 0) {
                addCriteriaRow();
            } else {
                details.forEach((item) => {
                    addCriteriaRow(item.noiDungTieuChi || '', item.diemToiDa ?? '', item.tyTrong ?? '1');
                });
            }
        }
    }

    async function khoiTaoTabConfigCriteria() {
        if (currentTab !== 'config-criteria') {
            return;
        }

        // Hiển thị loading state
        if (criteriaSetList) {
            criteriaSetList.innerHTML = '<div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Đang tải dữ liệu bộ tiêu chí...</div>';
        }

        try {
            const data = await layDuLieuBoTieuChi();
            console.log('Dữ liệu bộ tiêu chí:', data);

            // Populate dropdown vòng thi
            const rounds = Array.isArray(data.vong_thi) ? data.vong_thi : [];
            console.log('Danh sách vòng thi:', rounds);
            if (criteriaVongThi) {
                criteriaVongThi.innerHTML = '<option value="0">-- Chưa gán vòng thi --</option>' + rounds
                    .map((round) => `<option value="${round.idVongThi}">${round.tenVongThi} (Vòng ${round.thuTu || '--'})</option>`)
                    .join('');
            }

            // Populate datalist ngân hàng tiêu chí
            const bank = Array.isArray(data.ngan_hang_tieu_chi) ? data.ngan_hang_tieu_chi : [];
            console.log('Ngân hàng tiêu chí:', bank);
            if (criteriaBankList) {
                criteriaBankList.innerHTML = bank
                    .map((item) => `<option value="${String(item.noiDungTieuChi || '').replace(/"/g, '&quot;')}"></option>`)
                    .join('');
            }

            // Populate dropdown bộ tiêu chí có sẵn
            const sets = Array.isArray(data.bo_tieu_chi) ? data.bo_tieu_chi : [];
            console.log('Danh sách bộ tiêu chí:', sets);
            if (criteriaReuseSetDropdown) {
                criteriaReuseSetDropdown.innerHTML = '<option value="">-- Chọn bộ tiêu chí để nhân bản --</option>' + sets
                    .map((item) => `<option value="${item.idBoTieuChi}">${item.tenBoTieuChi}</option>`)
                    .join('');
            }

            criteriaUsageMap = data.usage_map || {};
            renderCriteriaSetList(sets);
            resetCriteriaForm();
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu bộ tiêu chí:', error);
            if (criteriaSetList) {
                criteriaSetList.innerHTML = `<div class="px-3 py-2 border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
                    <p class="font-semibold mb-1">Không tải được dữ liệu bộ tiêu chí</p>
                    <p class="text-xs">${error.message || 'Vui lòng kiểm tra đăng nhập và quyền truy cập.'}</p>
                    <button type="button" onclick="location.reload()" class="mt-2 px-3 py-1 text-xs font-semibold rounded border border-rose-300 bg-white text-rose-600 hover:bg-rose-50">Tải lại trang</button>
                </div>`;
            }
        }
    }

    function generateConditionKey() {
        const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const key = conditionCounter < alphabet.length
            ? alphabet[conditionCounter]
            : `${alphabet[conditionCounter % alphabet.length]}${Math.floor(conditionCounter / alphabet.length)}`;
        conditionCounter += 1;
        return key;
    }

    function buildAstFromTokens(tokenList) {
        let index = 0;

        function parseExpression() {
            let node = parseTerm();
            while (tokenList[index] === 'OR') {
                index += 1;
                const right = parseTerm();
                node = { type: 'group', operator: 'OR', children: [node, right] };
            }
            return node;
        }

        function parseTerm() {
            let node = parseFactor();
            while (tokenList[index] === 'AND') {
                index += 1;
                const right = parseFactor();
                node = { type: 'group', operator: 'AND', children: [node, right] };
            }
            return node;
        }

        function parseFactor() {
            const token = tokenList[index];
            if (!token) {
                throw new Error('Biểu thức thiếu toán hạng');
            }

            if (token === '(') {
                index += 1;
                const node = parseExpression();
                if (tokenList[index] !== ')') {
                    throw new Error('Thiếu dấu đóng ngoặc )');
                }
                index += 1;
                return node;
            }

            if (token === ')' || token === 'AND' || token === 'OR') {
                throw new Error('Vị trí toán tử hoặc ngoặc không hợp lệ');
            }

            const condition = conditionsMap[token];
            if (!condition) {
                throw new Error(`Không tìm thấy điều kiện ${token}`);
            }

            index += 1;
            return {
                type: 'rule',
                key: token,
                idThuocTinhKiemTra: Number(condition.idThuocTinhKiemTra),
                idToanTu: Number(condition.idToanTu),
                giaTriSoSanh: condition.giaTriSoSanh,
                label: condition.label,
            };
        }

        const ast = parseExpression();
        if (index < tokenList.length) {
            throw new Error('Biểu thức còn token dư chưa xử lý');
        }
        return ast;
    }

    function kiemTraQuyTacToken(tokenList) {
        const errors = [];

        if (!Array.isArray(tokenList) || tokenList.length === 0) {
            errors.push('Công thức đang trống.');
            return { ok: false, errors };
        }

        const isLogic = (token) => token === 'AND' || token === 'OR';
        const isParen = (token) => token === '(' || token === ')';
        const isOperand = (token) => !isLogic(token) && !isParen(token);

        if (isLogic(tokenList[0]) || tokenList[0] === ')') {
            errors.push('Công thức không được bắt đầu bằng toán tử logic hoặc dấu đóng ngoặc.');
        }
        if (isLogic(tokenList[tokenList.length - 1]) || tokenList[tokenList.length - 1] === '(') {
            errors.push('Công thức không được kết thúc bằng toán tử logic hoặc dấu mở ngoặc.');
        }

        let balance = 0;
        let previous = null;

        for (let i = 0; i < tokenList.length; i += 1) {
            const current = tokenList[i];

            if (current === '(') {
                balance += 1;
                if (previous && (isOperand(previous) || previous === ')')) {
                    errors.push(`Thiếu toán tử logic trước dấu "(" tại vị trí ${i + 1}.`);
                }
            } else if (current === ')') {
                balance -= 1;
                if (balance < 0) {
                    errors.push(`Dấu ngoặc đóng dư tại vị trí ${i + 1}.`);
                    balance = 0;
                }
                if (!previous || isLogic(previous) || previous === '(') {
                    errors.push(`Nội dung trong ngoặc không hợp lệ trước vị trí ${i + 1}.`);
                }
            } else if (isLogic(current)) {
                if (!previous || isLogic(previous) || previous === '(') {
                    errors.push(`Toán tử ${current} đang đứng sai vị trí ${i + 1}.`);
                }
            } else if (isOperand(current)) {
                if (!conditionsMap[current]) {
                    errors.push(`Điều kiện ${current} chưa được định nghĩa.`);
                }
                if (previous && (isOperand(previous) || previous === ')')) {
                    errors.push(`Thiếu toán tử logic giữa các điều kiện gần vị trí ${i + 1}.`);
                }
            }

            previous = current;
        }

        if (balance !== 0) {
            errors.push('Số lượng ngoặc mở/đóng chưa cân bằng.');
        }

        const attributeUsed = new Map();
        for (let i = 0; i < tokenList.length; i += 1) {
            const token = tokenList[i];
            if (!isOperand(token) || !conditionsMap[token]) {
                continue;
            }

            const condition = conditionsMap[token];
            const attrId = Number(condition.idThuocTinhKiemTra || 0);
            const attrName = String(condition.label?.thuocTinh || `#${attrId}`);

            if (attrId <= 0) {
                continue;
            }

            if (attributeUsed.has(attrId)) {
                errors.push(`Thuộc tính "${attrName}" chỉ được phép xuất hiện 1 lần trong biểu thức.`);
            } else {
                attributeUsed.set(attrId, token);
            }
        }

        const stack = [];
        for (let i = 0; i < tokenList.length; i += 1) {
            const token = tokenList[i];

            if (token === '(') {
                stack.push({ start: i, logic: new Set(), depth: stack.length + 1 });
                continue;
            }

            if (token === ')') {
                if (stack.length > 0) {
                    const scope = stack.pop();
                    if (scope.logic.size > 1) {
                        errors.push(`Trong cặp ngoặc từ vị trí ${scope.start + 1} đến ${i + 1} đang trộn AND/OR.`);
                    }
                }
                continue;
            }

            if (isLogic(token) && stack.length > 0) {
                const top = stack[stack.length - 1];
                top.logic.add(token);
            }
        }

        return {
            ok: errors.length === 0,
            errors,
        };
    }

    function parseAstToHtmlString(node) {
        if (!node) {
            return '<span class="text-slate-500">--</span>';
        }

        if (node.type === 'rule') {
            const label = node.label || {};
            const display = `${label.thuocTinh || ''} ${label.toanTu || ''} ${node.giaTriSoSanh || ''}`.trim();
            return `<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-md bg-slate-100 border border-slate-200 text-slate-700">${display}</span>`;
        }

        if (node.type === 'group' && Array.isArray(node.children) && node.children.length === 2) {
            const left = parseAstToHtmlString(node.children[0]);
            const right = parseAstToHtmlString(node.children[1]);
            return `<span class="inline-flex items-center gap-2">( ${left} <span class="px-2 py-0.5 text-xs font-bold rounded bg-fuchsia-100 text-fuchsia-700">${node.operator}</span> ${right} )</span>`;
        }

        return '<span class="text-rose-600">[AST lỗi]</span>';
    }

    function toNaturalTextFromAst(node) {
        if (!node) {
            return 'Chưa có dữ liệu phiên dịch...';
        }

        if (node.type === 'rule') {
            const label = node.label || {};
            const thuocTinh = String(label.thuocTinh || 'thuộc tính').trim();
            const toanTu = String(label.toanTu || 'toán tử').trim();
            const giaTri = String(node.giaTriSoSanh || '').trim();
            return `${thuocTinh} ${toanTu} ${giaTri}`.trim();
        }

        if (node.type === 'group' && Array.isArray(node.children) && node.children.length === 2) {
            const leftText = toNaturalTextFromAst(node.children[0]);
            const rightText = toNaturalTextFromAst(node.children[1]);
            const operator = String(node.operator || '').toUpperCase() === 'OR' ? 'HOẶC' : 'VÀ';
            return `( ${leftText} ${operator} ${rightText} )`;
        }

        return 'Không thể phiên dịch biểu thức hiện tại.';
    }

    function renderNaturalPreviewText(text) {
        if (!ruleNaturalPreview) {
            return;
        }

        ruleNaturalPreview.textContent = text || 'Chưa có dữ liệu phiên dịch...';
    }

    function renderConditionPool() {
        if (!conditionPool) {
            return;
        }

        const keys = Object.keys(conditionsMap);
        if (keys.length === 0) {
            conditionPool.innerHTML = '<span class="text-sm text-slate-500">Chưa có điều kiện nào được khởi tạo.</span>';
            return;
        }

        conditionPool.innerHTML = keys
            .map((key) => {
                const cond = conditionsMap[key];
                return `<button type="button" data-cond-key="${key}" class="cond-token-btn inline-flex items-center px-2.5 py-1.5 mr-2 mb-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700">[${key}] ${cond.label.thuocTinh} ${cond.label.toanTu} ${cond.giaTriSoSanh}</button>`;
            })
            .join('');
    }

    function renderTokenPreview() {
        if (!tokenPreview || !rulesJsonInput || !btnSaveRuleConfig || !astStatusText || !tokenError) {
            return;
        }

        tokenPreview.textContent = tokens.length ? tokens.join(' ') : '(trống)';

        if (tokens.length === 0) {
            rulesJsonInput.value = '';
            btnSaveRuleConfig.disabled = true;
            astStatusText.textContent = 'Chưa có cây logic hợp lệ.';
            tokenError.classList.add('hidden');
            renderNaturalPreviewText('Chưa có dữ liệu phiên dịch...');
            return;
        }

        try {
            const check = kiemTraQuyTacToken(tokens);
            if (!check.ok) {
                throw new Error(check.errors.join(' '));
            }

            const ast = buildAstFromTokens(tokens);
            rulesJsonInput.value = JSON.stringify(ast);
            btnSaveRuleConfig.disabled = false;
            astStatusText.textContent = 'Cây logic hợp lệ, sẵn sàng lưu.';
            tokenError.classList.add('hidden');
            renderNaturalPreviewText(toNaturalTextFromAst(ast));
        } catch (error) {
            rulesJsonInput.value = '';
            btnSaveRuleConfig.disabled = true;
            astStatusText.textContent = 'Cây logic chưa hợp lệ.';
            const rawMessage = error.message || 'Biểu thức logic không hợp lệ';
            const listMessage = rawMessage
                .split('. ')
                .map((item) => item.trim())
                .filter((item) => item.length > 0)
                .map((item) => `• ${item}`)
                .join('<br>');
            tokenError.innerHTML = listMessage || '• Biểu thức logic không hợp lệ';
            tokenError.classList.remove('hidden');
            renderNaturalPreviewText('Công thức chưa hợp lệ nên chưa thể phiên dịch.');
        }
    }

    function pushToken(token) {
        tokens.push(token);
        renderTokenPreview();
    }

    function resetRuleBuilder() {
        Object.keys(conditionsMap).forEach((key) => delete conditionsMap[key]);
        conditionCounter = 0;
        tokens.length = 0;
        if (ruleInputGiaTri) {
            ruleInputGiaTri.value = '';
        }
        if (rulesJsonInput) {
            rulesJsonInput.value = '';
        }
        renderNaturalPreviewText('Chưa có dữ liệu phiên dịch...');
        renderConditionPool();
        renderTokenPreview();
    }

    function detectRuleTypeFromQuery() {
        const search = new URLSearchParams(window.location.search);
        const queryValue = search.get('rule_type') || search.get('loai');
        const normalized = String(queryValue || '').toUpperCase().trim();
        const mapped = ruleTypeAliasMap[normalized] || '';
        if (allowedRuleTypes.includes(mapped)) {
            return mapped;
        }
        return 'THAMGIA_SV';
    }

    function applyMetadataToInputs(metadata) {
        const thuocTinh = Array.isArray(metadata.thuoc_tinh) ? metadata.thuoc_tinh : [];
        const toanTu = Array.isArray(metadata.toan_tu) ? metadata.toan_tu : [];

        compareOperators = toanTu.filter((item) => String(item.loaiToanTu || '').toLowerCase() === 'compare');

        if (ruleInputThuocTinh) {
            ruleInputThuocTinh.innerHTML = '<option value="">-- Chọn thuộc tính --</option>' + thuocTinh
                .map((item) => `<option value="${item.idThuocTinhKiemTra}">${item.tenThuocTinh} (${item.loaiApDung})</option>`)
                .join('');
        }

        if (ruleInputToanTu) {
            ruleInputToanTu.innerHTML = '<option value="">-- Chọn toán tử --</option>' + compareOperators
                .map((item) => `<option value="${item.idToanTu}" data-ky-hieu="${item.kyHieu}">${item.kyHieu} - ${item.tenToanTu}</option>`)
                .join('');
        }

        return {
            thuocTinhCount: thuocTinh.length,
            compareCount: compareOperators.length,
        };
    }

    function renderGiaTriSuggestions(goiY = []) {
        if (!ruleGiaTriSuggestions) {
            return;
        }

        const suggestions = Array.isArray(goiY) ? goiY : [];
        ruleGiaTriSuggestions.innerHTML = suggestions
            .map((item) => `<option value="${String(item).replace(/"/g, '&quot;')}"></option>`)
            .join('');

        const hintText = suggestions.length > 0
            ? `Có ${suggestions.length} giá trị gợi ý`
            : 'Không có dữ liệu gợi ý, bạn có thể nhập tay.';

        if (ruleInputGiaTri) {
            ruleInputGiaTri.placeholder = hintText;
        }

        if (ruleGiaTriHint) {
            ruleGiaTriHint.textContent = hintText;
        }
    }

    async function napGoiYGiaTriTheoThuocTinhDangChon() {
        if (!ruleInputThuocTinh) {
            return;
        }

        const idThuocTinh = Number(ruleInputThuocTinh.value || 0);
        if (idThuocTinh <= 0) {
            renderGiaTriSuggestions([]);
            if (ruleInputGiaTri) {
                ruleInputGiaTri.placeholder = 'Chọn thuộc tính để xem giá trị gợi ý.';
            }
            if (ruleGiaTriHint) {
                ruleGiaTriHint.textContent = 'Chọn thuộc tính để xem giá trị gợi ý.';
            }
            return;
        }

        if (ruleGiaTriHint) {
            ruleGiaTriHint.textContent = 'Đang tải giá trị gợi ý...';
        }
        if (ruleInputGiaTri) {
            ruleInputGiaTri.placeholder = 'Đang tải giá trị gợi ý...';
        }

        try {
            const data = await layGoiYGiaTriTheoThuocTinh(idThuocTinh);
            renderGiaTriSuggestions(data.goi_y || []);
        } catch (error) {
            renderGiaTriSuggestions([]);
            if (ruleGiaTriHint) {
                ruleGiaTriHint.textContent = error.message || 'Không tải được giá trị gợi ý.';
            }
            if (ruleInputGiaTri) {
                ruleInputGiaTri.placeholder = error.message || 'Không tải được giá trị gợi ý.';
            }
        }
    }

    async function renderRuleList() {
        if (!ruleListContainer) {
            return;
        }

        ruleListContainer.innerHTML = '<div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50">Đang tải danh sách quy chế...</div>';

        try {
            const list = await layDanhSachQuyChe(idSk, activeRuleType);
            if (list.length === 0) {
                ruleListContainer.innerHTML = '<div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Chưa có quy chế nào cho sự kiện này.</div>';
                return;
            }

            ruleListContainer.innerHTML = list
                .map((item) => {
                    return `<div class="p-3 border rounded-lg border-slate-200 bg-slate-50">
                                <p class="mb-0 text-sm font-semibold text-slate-700">${item.tenQuyChe || '--'}</p>
                                <p class="mb-2 text-xs text-slate-500">Loại: ${item.loaiQuyChe || '--'}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-rule-view="${item.idQuyChe}" class="rule-view-btn px-2.5 py-1 text-xs font-bold rounded border border-slate-300 bg-white">Xem</button>
                                    <button type="button" data-rule-delete="${item.idQuyChe}" class="rule-delete-btn px-2.5 py-1 text-xs font-bold rounded border border-rose-300 text-rose-600 bg-white">Xóa</button>
                                </div>
                            </div>`;
                })
                .join('');
        } catch (error) {
            ruleListContainer.innerHTML = `<div class="px-3 py-2 border rounded-lg border-rose-200 bg-rose-50 text-rose-600">${error.message || 'Không tải được danh sách quy chế'}</div>`;
        }
    }

    async function khoiTaoTabConfigRules() {
        if (currentTab !== 'config-rules') {
            return;
        }

        if (!ruleInputThuocTinh || !ruleInputToanTu) {
            return;
        }

        if (ruleTypeInput) {
            const detected = detectRuleTypeFromQuery();
            const selectedType = allowedRuleTypes.includes(ruleTypeInput.value) ? ruleTypeInput.value : detected;
            activeRuleType = selectedType;
            ruleTypeInput.value = selectedType;
        }

        try {
            const metadata = await layMetadataQuyChe('');
            const applied = applyMetadataToInputs(metadata);

            if (applied.thuocTinhCount === 0 || applied.compareCount === 0) {
                if (tokenError) {
                    tokenError.textContent = 'Metadata thuộc tính/toán tử đang rỗng. Vui lòng kiểm tra dữ liệu CSDL.';
                    tokenError.classList.remove('hidden');
                }
            }

            await napGoiYGiaTriTheoThuocTinhDangChon();
        } catch (error) {
            if (tokenError) {
                tokenError.textContent = error.message || 'Không tải được metadata quy chế';
                tokenError.classList.remove('hidden');
            }
        }

        renderConditionPool();
        renderTokenPreview();
        await renderRuleList();
    }

    // Function to load data for review-assign tab
    async function khoiTaoTabReviewAssign() {
        if (currentTab !== 'review-assign') {
            return;
        }

        // Get review-assign container elements
        const reviewAssignContainer = document.querySelector('#reviewAssignContainer') ||
            document.querySelector('[data-tab="review-assign"]') ||
            document.querySelector('.review-assign-content');

        if (!reviewAssignContainer) {
            console.warn('Review assign container not found');
            return;
        }

        try {
            // Show loading state
            reviewAssignContainer.innerHTML = `
                <div class="p-4 text-center">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2 text-slate-400"></i>
                    <p class="text-sm text-slate-500">Đang tải dữ liệu phân công phản biện...</p>
                </div>
            `;

            // Load necessary data
            const promises = [
                fetch(`${BASE_PATH}/api/cham_diem/phan_cong_giam_khao.php?action=list_giang_vien`, {
                    method: 'GET',
                    credentials: 'same-origin'
                }).then(r => r.json()),

                fetch(`${BASE_PATH}/api/su_kien/danh_sach_vong_thi.php?id_sk=${encodeURIComponent(idSk)}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                }).then(r => r.json())
            ];

            const [giangVienResponse, vongThiResponse] = await Promise.all(promises);

            if (giangVienResponse.status !== 'success') {
                throw new Error(giangVienResponse.message || 'Không thể lấy danh sách giảng viên');
            }

            if (vongThiResponse.status !== 'success') {
                throw new Error(vongThiResponse.message || 'Không thể lấy danh sách vòng thi');
            }

            const giangVien = giangVienResponse.data || [];
            const vongThi = vongThiResponse.data || [];

            // Render interface
            renderReviewAssignInterface(giangVien, vongThi);

        } catch (error) {
            if (reviewAssignContainer) {
                reviewAssignContainer.innerHTML = `
                    <div class="p-4 border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Lỗi: ${error.message || 'Không thể tải dữ liệu phân công phản biện'}
                    </div>
                `;
            }
        }
    }

    function renderReviewAssignInterface(giangVien, vongThi) {
        const reviewAssignContainer = document.querySelector('#reviewAssignContainer') ||
            document.querySelector('[data-tab="review-assign"]') ||
            document.querySelector('.review-assign-content');

        if (!reviewAssignContainer) return;

        reviewAssignContainer.innerHTML = `
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Left panel: Assignment interface -->
                <div class="p-4 border rounded-xl border-slate-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div>
                            <p class="mb-0 text-sm font-bold text-slate-700">
                                <i class="fas fa-user-plus mr-2 text-slate-400"></i>Phân công phản biện
                            </p>
                            <p class="mb-0 text-xs text-slate-500">
                                Gán giảng viên phản biện cho bài nộp trong sự kiện
                            </p>
                        </div>
                        <select id="reviewVongThiSelect" class="px-3 py-2 text-sm border rounded-lg border-slate-300">
                            <option value="">-- Chọn vòng thi --</option>
                            ${vongThi.map(v => `<option value="${v.idVongThi}">${v.tenVongThi}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div id="reviewAssignmentList" class="space-y-2 max-h-[400px] overflow-y-auto">
                        <div class="px-4 py-8 text-center text-slate-400">
                            <i class="fas fa-hand-pointer text-2xl mb-2"></i>
                            <p class="text-sm">Chọn vòng thi để xem danh sách bài nộp</p>
                        </div>
                    </div>
                </div>

                <!-- Right panel: Reviewer list -->
                <div class="p-4 border rounded-xl border-slate-200 bg-white">
                    <p class="mb-3 text-sm font-bold text-slate-700">
                        <i class="fas fa-users mr-2 text-slate-400"></i>Danh sách giảng viên phản biện (${giangVien.length})
                    </p>
                    
                    <div class="space-y-2 max-h-[400px] overflow-y-auto">
                        ${giangVien.map(gv => `
                            <div class="p-3 border rounded-lg border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors">
                                <p class="mb-1 text-sm font-semibold text-slate-700">${gv.tenGV || gv.tenTK || 'N/A'}</p>
                                <p class="mb-1 text-xs text-slate-500">
                                    ${gv.tenKhoa ? `Khoa: ${gv.tenKhoa}` : `Tài khoản: ${gv.tenTK || 'N/A'}`}
                                </p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400">
                                        Đã chấm: ${gv.soBaiDangCham || 0} bài
                                    </span>
                                    <button type="button" data-reviewer-id="${gv.idGV}" 
                                            class="reviewer-select-btn px-2 py-1 text-xs font-semibold text-purple-600 bg-purple-100 rounded hover:bg-purple-200 transition-colors">
                                        Chọn
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>

            <!-- Statistics panel -->
            <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="p-4 border rounded-xl border-slate-200 bg-gradient-to-br from-blue-50 to-cyan-50">
                    <p class="text-xs font-bold uppercase text-blue-600">Tổng bài nộp</p>
                    <p id="statTongBaiNop" class="mb-0 text-2xl font-bold text-blue-700">--</p>
                </div>
                <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
                    <p class="text-xs font-bold uppercase text-amber-600">Đã phân công</p>
                    <p id="statDaPhanCongReview" class="mb-0 text-2xl font-bold text-amber-700">--</p>
                </div>
                <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
                    <p class="text-xs font-bold uppercase text-emerald-600">Đã review</p>
                    <p id="statDaReview" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                </div>
                <div class="p-4 border rounded-xl border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50">
                    <p class="text-xs font-bold uppercase text-purple-600">Giảng viên tham gia</p>
                    <p class="mb-0 text-2xl font-bold text-purple-700">${giangVien.length}</p>
                </div>
            </div>
        `;

        // Add event listeners
        const vongThiSelect = document.getElementById('reviewVongThiSelect');
        if (vongThiSelect) {
            vongThiSelect.addEventListener('change', loadSubmissionsForReview);
        }

        // Add reviewer selection event listeners
        reviewAssignContainer.addEventListener('click', function (event) {
            if (event.target.matches('.reviewer-select-btn')) {
                const reviewerId = event.target.getAttribute('data-reviewer-id');
                // Handle reviewer selection logic here
                console.log('Selected reviewer:', reviewerId);
            }
        });
    }

    async function loadSubmissionsForReview() {
        const vongThiSelect = document.getElementById('reviewVongThiSelect');
        const listContainer = document.getElementById('reviewAssignmentList');

        if (!vongThiSelect || !listContainer) return;

        const selectedVongThi = vongThiSelect.value;

        if (!selectedVongThi) {
            listContainer.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-hand-pointer text-2xl mb-2"></i>
                    <p class="text-sm">Chọn vòng thi để xem danh sách bài nộp</p>
                </div>
            `;
            return;
        }

        try {
            listContainer.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p class="text-sm">Đang tải danh sách bài nộp...</p>
                </div>
            `;

            // This is a placeholder - you'll need to create appropriate API endpoints
            // For now, showing a message that the feature needs API implementation
            listContainer.innerHTML = `
                <div class="p-4 border rounded-lg border-yellow-200 bg-yellow-50 text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Đã chọn vòng thi ${selectedVongThi}</strong>
                    <p class="mt-2 text-sm">
                        API endpoint để lấy danh sách bài nộp theo vòng thi cần được tạo. 
                        Vui lòng tham khảo API có sẵn trong thư mục <code>/api/</code> để tạo endpoint phù hợp.
                    </p>
                    <p class="mt-1 text-xs text-yellow-600">
                        Gợi ý: <code>GET /api/su_kien/danh_sach_bai_nop.php?id_sk=${idSk}&id_vong_thi=${selectedVongThi}</code>
                    </p>
                </div>
            `;
        } catch (error) {
            listContainer.innerHTML = `
                <div class="p-4 border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ${error.message || 'Không thể tải danh sách bài nộp'}
                </div>
            `;
        }
    }

    async function reloadRuleTypeContext(nextType) {
        if (!allowedRuleTypes.includes(nextType)) {
            return;
        }

        activeRuleType = nextType;
        resetRuleBuilder();

        try {
            const metadata = await layMetadataQuyChe('');
            const applied = applyMetadataToInputs(metadata);

            if (applied.thuocTinhCount === 0 || applied.compareCount === 0) {
                if (tokenError) {
                    tokenError.textContent = 'Metadata thuộc tính/toán tử đang rỗng. Vui lòng kiểm tra dữ liệu CSDL.';
                    tokenError.classList.remove('hidden');
                }
            }

            await napGoiYGiaTriTheoThuocTinhDangChon();

            await renderRuleList();
        } catch (error) {
            if (tokenError) {
                tokenError.textContent = error.message || 'Không tải được dữ liệu quy chế theo loại';
                tokenError.classList.remove('hidden');
            }
        }
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

        const totalRounds = rounds.length;

        basicRoundList.innerHTML = rounds
            .map((round, index) => {
                const idVongThi = Number(round.idVongThi || 0);
                const tenVong = String(round.tenVongThi || '--');
                const thuTu = Number(round.thuTu || 0);
                const ngayBatDau = formatDateTime(round.ngayBatDau);
                const ngayKetThuc = formatDateTime(round.ngayKetThuc);
                const moTa = String(round.moTa || '').trim();
                const trangThai = round.trangThai || 'dang_dien_ra';
                // Kiểm tra đã đóng nộp thủ công chưa
                const daDongNop = Boolean(round.daDongNop) || Number(round.dongNopThuCong) === 1;

                // Badge trạng thái
                let statusBadge = '';
                if (trangThai === 'chua_bat_dau') {
                    statusBadge = '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Chưa bắt đầu</span>';
                } else if (trangThai === 'da_ket_thuc') {
                    statusBadge = '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-500">Đã kết thúc</span>';
                } else {
                    statusBadge = '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Đang diễn ra</span>';
                }

                // Badge đóng nộp
                const dongNopBadge = daDongNop
                    ? '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-600">Đã đóng nộp</span>'
                    : '';

                // Nút di chuyển lên/xuống
                const canMoveUp = index > 0;
                const canMoveDown = index < totalRounds - 1;

                return `<div class="p-3 border rounded-lg border-slate-200 bg-slate-50" data-round-id="${idVongThi}" data-round-order="${thuTu}">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <p class="mb-0 text-sm font-semibold text-slate-700 truncate">${tenVong}</p>
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-white border border-slate-200 text-slate-600 shrink-0">Vòng ${thuTu}</span>
                                        ${statusBadge}
                                        ${dongNopBadge}
                                    </div>
                                    <p class="mb-1 text-xs text-slate-500">${ngayBatDau} → ${ngayKetThuc}</p>
                                    <p class="mb-0 text-xs text-slate-500 line-clamp-2">${moTa || 'Không có mô tả.'}</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Nút di chuyển -->
                                    <button type="button" class="btn-move-round-up p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed" ${!canMoveUp ? 'disabled' : ''} data-round-id="${idVongThi}" title="Di chuyển lên">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    </button>
                                    <button type="button" class="btn-move-round-down p-1 text-slate-400 hover:text-slate-600 disabled:opacity-30 disabled:cursor-not-allowed" ${!canMoveDown ? 'disabled' : ''} data-round-id="${idVongThi}" title="Di chuyển xuống">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <!-- Nút hành động -->
                                    <button type="button" class="btn-edit-round p-1 text-blue-400 hover:text-blue-600" data-round-id="${idVongThi}" title="Sửa vòng thi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-toggle-round p-1 ${daDongNop ? 'text-emerald-400 hover:text-emerald-600' : 'text-amber-400 hover:text-amber-600'}" data-round-id="${idVongThi}" title="${daDongNop ? 'Mở lại nộp bài' : 'Đóng nộp bài'}">
                                        ${daDongNop
                        ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>'
                        : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>'
                    }
                                    </button>
                                    <button type="button" class="btn-delete-round p-1 text-rose-400 hover:text-rose-600" data-round-id="${idVongThi}" title="Xóa vòng thi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>`;
            })
            .join('');

        // Gắn event listeners cho các nút
        attachRoundActionListeners();
    }

    // Lưu trữ dữ liệu vòng thi hiện tại
    let currentRoundsData = [];

    async function napDanhSachVongThi() {
        if (!basicRoundList || idSk <= 0) {
            return;
        }

        basicRoundList.innerHTML = '<div class="px-3 py-3 text-sm border rounded-lg border-slate-200 bg-slate-50 text-slate-500">Đang tải danh sách vòng thi...</div>';

        try {
            const rounds = await layDanhSachVongThi(idSk);
            currentRoundsData = rounds;
            renderRoundList(rounds);
        } catch (error) {
            basicRoundList.innerHTML = `<div class="px-3 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600">${error.message || 'Không tải được danh sách vòng thi'}</div>`;
        }
    }

    // Hàm gắn event listeners cho các nút action của vòng thi
    function attachRoundActionListeners() {
        // Nút sửa
        document.querySelectorAll('.btn-edit-round').forEach(btn => {
            btn.addEventListener('click', function () {
                const roundId = Number(this.dataset.roundId);
                handleEditRound(roundId);
            });
        });

        // Nút xóa
        document.querySelectorAll('.btn-delete-round').forEach(btn => {
            btn.addEventListener('click', function () {
                const roundId = Number(this.dataset.roundId);
                handleDeleteRound(roundId);
            });
        });

        // Nút toggle
        document.querySelectorAll('.btn-toggle-round').forEach(btn => {
            btn.addEventListener('click', function () {
                const roundId = Number(this.dataset.roundId);
                handleToggleRound(roundId);
            });
        });

        // Nút di chuyển lên
        document.querySelectorAll('.btn-move-round-up').forEach(btn => {
            btn.addEventListener('click', function () {
                const roundId = Number(this.dataset.roundId);
                handleMoveRound(roundId, 'up');
            });
        });

        // Nút di chuyển xuống
        document.querySelectorAll('.btn-move-round-down').forEach(btn => {
            btn.addEventListener('click', function () {
                const roundId = Number(this.dataset.roundId);
                handleMoveRound(roundId, 'down');
            });
        });
    }

    // Xử lý sửa vòng thi
    async function handleEditRound(roundId) {
        const round = currentRoundsData.find(r => Number(r.idVongThi) === roundId);
        if (!round) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Không tìm thấy thông tin vòng thi' });
            return;
        }

        const { value: formValues } = await Swal.fire({
            title: 'Sửa vòng thi',
            html:
                '<div class="text-left swal-round-form">' +
                '<div class="space-y-3">' +
                '<div>' +
                '<label class="block mb-1 text-xs font-semibold text-slate-700">Tên vòng thi <span class="text-rose-500">*</span></label>' +
                `<input id="swal-edit-tenVongThi" type="text" class="swal2-input !w-full !m-0" value="${round.tenVongThi || ''}" />` +
                '</div>' +
                '<div>' +
                '<label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>' +
                `<textarea id="swal-edit-moTaVongThi" rows="2" class="swal2-textarea !w-full !m-0">${round.moTa || ''}</textarea>` +
                '</div>' +
                '<div>' +
                '<label class="block mb-1 text-xs font-semibold text-slate-700">Thứ tự</label>' +
                `<input id="swal-edit-thuTuVongThi" type="number" min="1" class="swal2-input !w-full !m-0" value="${round.thuTu || 1}" />` +
                '</div>' +
                '<div class="grid grid-cols-2 gap-3">' +
                '<div>' +
                '<label class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>' +
                `<input id="swal-edit-ngayBDVongThi" type="datetime-local" class="swal2-input !w-full !m-0" value="${toDatetimeLocal(round.ngayBatDau)}" />` +
                '</div>' +
                '<div>' +
                '<label class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>' +
                `<input id="swal-edit-ngayKTVongThi" type="datetime-local" class="swal2-input !w-full !m-0" value="${toDatetimeLocal(round.ngayKetThuc)}" />` +
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
            confirmButtonText: 'Lưu thay đổi',
            cancelButtonText: 'Huỷ',
            preConfirm: () => {
                const tenVong = document.getElementById('swal-edit-tenVongThi').value.trim();
                if (!tenVong) {
                    Swal.showValidationMessage('Tên vòng thi không được để trống');
                    return false;
                }

                return {
                    id_vong_thi: roundId,
                    ten_vong: tenVong,
                    mo_ta: document.getElementById('swal-edit-moTaVongThi').value.trim(),
                    thu_tu: Number(document.getElementById('swal-edit-thuTuVongThi').value || 1),
                    ngay_bat_dau: toDatabaseDateTime(document.getElementById('swal-edit-ngayBDVongThi').value),
                    ngay_ket_thuc: toDatabaseDateTime(document.getElementById('swal-edit-ngayKTVongThi').value),
                };
            },
        });

        if (!formValues) return;

        try {
            const response = await fetch(`${BASE_PATH}/api/su_kien/cap_nhat_vong_thi.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues),
            });
            const result = await response.json();

            if (result.status === 'success') {
                await napDanhSachVongThi();
                Swal.fire({ icon: 'success', title: 'Thành công', text: result.message });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: error.message || 'Không thể cập nhật vòng thi' });
        }
    }

    // Xử lý xóa vòng thi
    async function handleDeleteRound(roundId) {
        const round = currentRoundsData.find(r => Number(r.idVongThi) === roundId);
        if (!round) return;

        const confirm = await Swal.fire({
            title: 'Xác nhận xóa',
            html: `Bạn có chắc muốn xóa vòng thi "<strong>${round.tenVongThi}</strong>"?<br><small class="text-slate-500">Thao tác này không thể hoàn tác.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: '#ef4444',
        });

        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`${BASE_PATH}/api/su_kien/xoa_vong_thi.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_vong_thi: roundId }),
            });
            const result = await response.json();

            if (result.status === 'success') {
                await napDanhSachVongThi();
                Swal.fire({ icon: 'success', title: 'Thành công', text: result.message });
            } else if (result.hasRelatedData) {
                // Hiển thị thông báo có dữ liệu liên quan - không thể xóa
                Swal.fire({
                    title: 'Không thể xóa',
                    html: `Vòng thi này có <strong>${result.relatedData.join(', ')}</strong> liên quan.<br>Bạn cần xóa dữ liệu liên quan trước khi xóa vòng thi.`,
                    icon: 'error',
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#64748b',
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: error.message || 'Không thể xóa vòng thi' });
        }
    }

    // Xử lý toggle trạng thái đóng/mở nộp bài vòng thi
    async function handleToggleRound(roundId) {
        const round = currentRoundsData.find(r => Number(r.idVongThi) === roundId);
        if (!round) return;

        // Kiểm tra đã đóng nộp thủ công chưa
        const daDongNop = Boolean(round.daDongNop) || Number(round.dongNopThuCong) === 1;
        const action = daDongNop ? 'mở lại nộp bài' : 'đóng nộp bài';

        const confirm = await Swal.fire({
            title: `Xác nhận ${action}`,
            text: daDongNop
                ? `Bạn có muốn mở lại cho sinh viên nộp bài vào vòng "${round.tenVongThi}"?`
                : `Bạn có muốn đóng nộp bài cho vòng "${round.tenVongThi}"? Sinh viên sẽ không thể nộp bài mới.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: daDongNop ? 'Mở lại nộp bài' : 'Đóng nộp bài',
            cancelButtonText: 'Huỷ',
            confirmButtonColor: daDongNop ? '#10b981' : '#f59e0b',
        });

        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`${BASE_PATH}/api/su_kien/toggle_vong_thi.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_vong_thi: roundId }),
            });
            const result = await response.json();

            if (result.status === 'success') {
                await napDanhSachVongThi();
                Swal.fire({ icon: 'success', title: 'Thành công', text: result.message, timer: 1500, showConfirmButton: false });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: error.message || 'Không thể thay đổi trạng thái nộp bài' });
        }
    }

    // Xử lý di chuyển vòng thi lên/xuống
    async function handleMoveRound(roundId, direction) {
        const currentIndex = currentRoundsData.findIndex(r => Number(r.idVongThi) === roundId);
        if (currentIndex === -1) return;

        const swapIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
        if (swapIndex < 0 || swapIndex >= currentRoundsData.length) return;

        // Tạo mảng thứ tự mới
        const thuTuMoi = {};
        currentRoundsData.forEach((round, idx) => {
            let newOrder = idx + 1;
            if (idx === currentIndex) {
                newOrder = swapIndex + 1;
            } else if (idx === swapIndex) {
                newOrder = currentIndex + 1;
            }
            thuTuMoi[round.idVongThi] = newOrder;
        });

        try {
            const response = await fetch(`${BASE_PATH}/api/su_kien/sap_xep_vong_thi.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_su_kien: idSk,
                    thu_tu_moi: thuTuMoi,
                }),
            });
            const result = await response.json();

            if (result.status === 'success') {
                await napDanhSachVongThi();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Lỗi', text: error.message || 'Không thể sắp xếp vòng thi' });
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
    // Các tab sau yêu cầu đăng nhập — guest không init
    if (!isGuest) {
        khoiTaoTabConfigRules();
        khoiTaoTabConfigCriteria();
        khoiTaoTabReviewAssign();
    }

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

    if (btnAddCondition) {
        btnAddCondition.addEventListener('click', function () {
            if (!ruleInputThuocTinh || !ruleInputToanTu || !ruleInputGiaTri) {
                return;
            }

            const idThuocTinhKiemTra = Number(ruleInputThuocTinh.value || 0);
            const idToanTu = Number(ruleInputToanTu.value || 0);
            const giaTriSoSanh = ruleInputGiaTri.value.trim();

            if (idThuocTinhKiemTra <= 0 || idToanTu <= 0 || giaTriSoSanh === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu dữ liệu điều kiện',
                    text: 'Vui lòng chọn thuộc tính, toán tử và nhập giá trị so sánh.',
                });
                return;
            }

            const thuocTinhText = ruleInputThuocTinh.options[ruleInputThuocTinh.selectedIndex]?.textContent || '';
            const toanTuKyHieu = ruleInputToanTu.options[ruleInputToanTu.selectedIndex]?.dataset?.kyHieu || '';

            const key = generateConditionKey();
            conditionsMap[key] = {
                idThuocTinhKiemTra,
                idToanTu,
                giaTriSoSanh,
                label: {
                    thuocTinh: thuocTinhText,
                    toanTu: toanTuKyHieu,
                },
            };

            ruleInputGiaTri.value = '';
            renderConditionPool();
        });
    }

    if (ruleInputThuocTinh) {
        ruleInputThuocTinh.addEventListener('change', function () {
            napGoiYGiaTriTheoThuocTinhDangChon();
        });
    }

    if (conditionPool) {
        conditionPool.addEventListener('click', function (event) {
            const targetBtn = event.target.closest('.cond-token-btn');
            if (!targetBtn) {
                return;
            }

            const condKey = targetBtn.dataset.condKey || '';
            if (!conditionsMap[condKey]) {
                return;
            }

            pushToken(condKey);
        });
    }

    if (tokenAnd) tokenAnd.addEventListener('click', () => pushToken('AND'));
    if (tokenOr) tokenOr.addEventListener('click', () => pushToken('OR'));
    if (tokenOpen) tokenOpen.addEventListener('click', () => pushToken('('));
    if (tokenClose) tokenClose.addEventListener('click', () => pushToken(')'));

    if (tokenBackspace) {
        tokenBackspace.addEventListener('click', function () {
            tokens.pop();
            renderTokenPreview();
        });
    }

    if (tokenClear) {
        tokenClear.addEventListener('click', function () {
            tokens.length = 0;
            renderTokenPreview();
        });
    }

    if (btnSaveRuleConfig) {
        btnSaveRuleConfig.addEventListener('click', async function () {
            const tenQuyChe = ruleNameInput ? ruleNameInput.value.trim() : '';
            const loaiQuyChe = activeRuleType;
            const rulesJson = rulesJsonInput ? rulesJsonInput.value : '';

            if (!tenQuyChe || !loaiQuyChe || !rulesJson) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu dữ liệu',
                    text: 'Vui lòng nhập tên quy chế và xây dựng biểu thức logic hợp lệ.',
                });
                return;
            }

            try {
                await luuQuyChe({
                    id_sk: idSk,
                    ten_quy_che: tenQuyChe,
                    loai_quy_che: loaiQuyChe,
                    rules_json: rulesJson,
                });

                await Swal.fire({
                    icon: 'success',
                    title: 'Đã lưu quy chế',
                    text: 'Cấu trúc cây logic đã được lưu vào hệ thống.',
                });

                tokens.length = 0;
                renderTokenPreview();
                await renderRuleList();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể lưu quy chế',
                    text: error.message || 'Vui lòng thử lại.',
                });
            }
        });
    }

    if (ruleListContainer) {
        ruleListContainer.addEventListener('click', async function (event) {
            const viewBtn = event.target.closest('.rule-view-btn');
            const deleteBtn = event.target.closest('.rule-delete-btn');

            if (viewBtn) {
                const idQuyChe = Number(viewBtn.dataset.ruleView || 0);
                if (idQuyChe <= 0) {
                    return;
                }

                try {
                    const detail = await layChiTietQuyChe(idQuyChe);
                    const astHtml = parseAstToHtmlString(detail.ast || null);
                    const naturalText = toNaturalTextFromAst(detail.ast || null);

                    await Swal.fire({
                        title: detail.tenQuyChe || 'Chi tiết quy chế',
                        width: 780,
                        html:
                            '<div class="text-left space-y-2 text-sm text-slate-600">' +
                            `<div><span class="font-semibold text-slate-700">Loại:</span> ${detail.loaiQuyChe || '--'}</div>` +
                            `<div><span class="font-semibold text-slate-700">Mô tả:</span> ${detail.moTa || '--'}</div>` +
                            `<div><span class="font-semibold text-slate-700">Diễn giải:</span> ${naturalText}</div>` +
                            `<div><span class="font-semibold text-slate-700">Biểu thức:</span> <div class="mt-1">${astHtml}</div></div>` +
                            '</div>',
                        confirmButtonText: 'Đóng',
                    });
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể tải chi tiết',
                        text: error.message || 'Vui lòng thử lại.',
                    });
                }
                return;
            }

            if (deleteBtn) {
                const idQuyChe = Number(deleteBtn.dataset.ruleDelete || 0);
                if (idQuyChe <= 0) {
                    return;
                }

                const confirmed = await Swal.fire({
                    icon: 'warning',
                    title: 'Xóa quy chế?',
                    text: 'Quy chế sẽ bị xóa khỏi danh sách áp dụng của sự kiện.',
                    showCancelButton: true,
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Huỷ',
                });

                if (!confirmed.isConfirmed) {
                    return;
                }

                try {
                    await xoaQuyChe(idQuyChe);
                    await renderRuleList();
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa',
                        text: 'Quy chế đã được xóa khỏi sự kiện.',
                    });
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: error.message || 'Vui lòng thử lại.',
                    });
                }
            }
        });
    }

    if (ruleTypeInput) {
        ruleTypeInput.addEventListener('change', function () {
            const nextType = String(ruleTypeInput.value || '').trim();
            reloadRuleTypeContext(nextType);
        });
    }

    if (criteriaAddRow) {
        criteriaAddRow.addEventListener('click', function () {
            addCriteriaRow();
        });
    }

    if (criteriaResetForm) {
        criteriaResetForm.addEventListener('click', function () {
            resetCriteriaForm();
        });
    }

    if (criteriaTableBody) {
        // Xử lý click các nút hành động
        criteriaTableBody.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.criteria-row-remove');
            const upBtn = event.target.closest('.criteria-row-up');
            const downBtn = event.target.closest('.criteria-row-down');

            if (removeBtn) {
                const row = removeBtn.closest('.criteria-row');
                if (row) {
                    row.remove();
                    updateCriteriaSTT();
                    updateCriteriaTotals();
                }
                if (criteriaTableBody.querySelectorAll('.criteria-row').length === 0) {
                    addCriteriaRow();
                }
                return;
            }

            if (upBtn) {
                const row = upBtn.closest('.criteria-row');
                moveCriteriaRow(row, 'up');
                return;
            }

            if (downBtn) {
                const row = downBtn.closest('.criteria-row');
                moveCriteriaRow(row, 'down');
            }
        });

        // Cập nhật tổng khi input thay đổi
        criteriaTableBody.addEventListener('input', function (event) {
            const target = event.target;
            if (target.matches('[data-field="diem_toi_da"], [data-field="ty_trong"]')) {
                updateCriteriaTotals();
            }
        });
    }

    if (criteriaCloneSetBtn) {
        criteriaCloneSetBtn.addEventListener('click', async function () {
            const idBo = Number(criteriaReuseSetDropdown?.value || 0);
            if (idBo <= 0) {
                Swal.fire({ icon: 'warning', title: 'Thiếu bộ tiêu chí', text: 'Vui lòng chọn bộ tiêu chí để nhân bản.' });
                return;
            }

            try {
                await doDuLieuBoTieuChiVaoForm(idBo, 'clone');
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Không thể nhân bản', text: error.message || 'Vui lòng thử lại.' });
            }
        });
    }

    if (criteriaSetList) {
        criteriaSetList.addEventListener('click', async function (event) {
            const cloneBtn = event.target.closest('.criteria-clone-btn');
            const editBtn = event.target.closest('.criteria-edit-btn');
            const deleteBtn = event.target.closest('.criteria-delete-btn');

            if (cloneBtn) {
                const idBo = Number(cloneBtn.dataset.criteriaClone || 0);
                if (idBo > 0) {
                    try {
                        await doDuLieuBoTieuChiVaoForm(idBo, 'clone');
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Không thể nhân bản', text: error.message || 'Vui lòng thử lại.' });
                    }
                }
                return;
            }

            if (editBtn) {
                const idBo = Number(editBtn.dataset.criteriaEdit || 0);
                if (idBo > 0) {
                    try {
                        await doDuLieuBoTieuChiVaoForm(idBo, 'edit');
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Không thể nạp dữ liệu sửa', text: error.message || 'Vui lòng thử lại.' });
                    }
                }
                return;
            }

            if (deleteBtn) {
                const idBo = Number(deleteBtn.dataset.criteriaDelete || 0);
                if (idBo <= 0) return;

                const confirm = await Swal.fire({
                    title: 'Xác nhận xóa',
                    html: `Bạn có chắc muốn xóa bộ tiêu chí <strong>#${idBo}</strong>?<br><small class="text-slate-500">Thao tác này không thể hoàn tác.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Huỷ',
                    confirmButtonColor: '#ef4444',
                });

                if (!confirm.isConfirmed) return;

                try {
                    await xoaBoTieuChi(idBo);
                    await khoiTaoTabConfigCriteria();
                    Swal.fire({ icon: 'success', title: 'Đã xóa', text: 'Bộ tiêu chí đã được xóa thành công.', timer: 1500, showConfirmButton: false });
                } catch (error) {
                    if (error.hasRelatedData && Array.isArray(error.relatedData)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Không thể xóa',
                            html: `Bộ tiêu chí đang được sử dụng tại:<br><ul class="text-left mt-2">${error.relatedData.map((r) => `<li>• ${r}</li>`).join('')}</ul>`,
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Không thể xóa', text: error.message || 'Vui lòng thử lại.' });
                    }
                }
            }
        });
    }

    if (criteriaSaveBtn) {
        criteriaSaveBtn.addEventListener('click', async function () {
            const tenBo = criteriaTenBo ? criteriaTenBo.value.trim() : '';
            const moTa = criteriaMoTa ? criteriaMoTa.value.trim() : '';
            const idVongThi = criteriaVongThi ? Number(criteriaVongThi.value || 0) : 0;
            const editId = criteriaEditId ? Number(criteriaEditId.value || 0) : 0;
            const danhSachTieuChi = collectCriteriaRows();

            if (!tenBo || danhSachTieuChi.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu dữ liệu',
                    text: 'Vui lòng nhập tên bộ tiêu chí và ít nhất một tiêu chí con.',
                });
                return;
            }

            try {
                await luuBoTieuChi({
                    id_sk: idSk,
                    edit_id: editId,
                    tenBoTieuChi: tenBo,
                    moTa: moTa,
                    idVongThi: idVongThi,
                    danh_sach_tieu_chi: danhSachTieuChi,
                });

                await Swal.fire({ icon: 'success', title: 'Đã lưu bộ tiêu chí', text: 'Cấu hình bộ tiêu chí đã được cập nhật.' });
                await khoiTaoTabConfigCriteria();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Không thể lưu', text: error.message || 'Vui lòng thử lại.' });
            }
        });
    }
});