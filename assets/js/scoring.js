/**
 * Scoring Module - Quản lý chấm điểm
 * 
 * Xử lý 3 chức năng chính:
 * 1. Phân công giám khảo
 * 2. Tiến độ & Kiểm định IRR
 * 3. Xét kết quả & Bảng vàng
 */

(function () {
    'use strict';

    // Get base path from current script location or use default
    const BASE_PATH = window.APP_BASE_PATH || '';

    // State management
    const state = {
        idSK: window.EVENT_DETAIL_ID || 0,
        idVongThi: 0,
        dsVongThi: [],
        dsGiangVien: [],
        dsSanPham: [],
        selectedSanPham: null,
        currentSubTab: 'phan-cong'
    };

    // DOM elements
    const elements = {};

    // API endpoints (relative to BASE_PATH)
    const API = {
        vongThi: BASE_PATH + '/api/su_kien/danh_sach_vong_thi.php',
        phanCong: BASE_PATH + '/api/cham_diem/phan_cong_giam_khao.php',
        tienDoIRR: BASE_PATH + '/api/cham_diem/tien_do_irr.php',
        xetKetQua: BASE_PATH + '/api/cham_diem/xet_ket_qua.php'
    };

    /**
     * Initialize module
     */
    function init() {
        if (window.EVENT_DETAIL_TAB !== 'scoring') return;

        cacheElements();
        bindEvents();
        loadVongThi();
        loadGiangVien();
    }

    /**
     * Cache DOM elements
     */
    function cacheElements() {
        elements.vongThiSelect = document.getElementById('scoringVongThiSelect');
        elements.vongThiStatus = document.getElementById('scoringVongThiStatus');
        elements.subTabBtns = document.querySelectorAll('.scoring-subtab-btn');
        elements.subTabContents = document.querySelectorAll('.scoring-subtab-content');

        // Stats
        elements.statTongSanPham = document.getElementById('statTongSanPham');
        elements.statDaPhanCong = document.getElementById('statDaPhanCong');
        elements.statDaChamXong = document.getElementById('statDaChamXong');
        elements.statDaDuyet = document.getElementById('statDaDuyet');

        // Tab 1: Phân công
        elements.listSanPhamPhanCong = document.getElementById('listSanPhamPhanCong');
        elements.panelPhanCong = document.getElementById('panelPhanCong');
        elements.searchSanPham = document.getElementById('searchSanPham');
        elements.filterTrangThai = document.getElementById('filterTrangThai');

        // Tab 2: Tiến độ & IRR
        elements.listCanhBaoIRR = document.getElementById('listCanhBaoIRR');
        elements.panelIRRDetail = document.getElementById('panelIRRDetail');
        elements.tbodyTienDoCham = document.getElementById('tbodyTienDoCham');
        elements.btnRefreshCanhBao = document.getElementById('btnRefreshCanhBao');

        // Tab 3: Xét kết quả
        elements.listCanDuyet = document.getElementById('listCanDuyet');
        elements.listBangVang = document.getElementById('listBangVang');
        elements.btnDuyetTatCa = document.getElementById('btnDuyetTatCa');
        elements.btnExportRanking = document.getElementById('btnExportRanking');
        elements.statKQDaDuyet = document.getElementById('statKQDaDuyet');
        elements.statKQBiLoai = document.getElementById('statKQBiLoai');
        elements.statKQChoDuyet = document.getElementById('statKQChoDuyet');
        elements.statKQDiemTB = document.getElementById('statKQDiemTB');
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Vòng thi select
        if (elements.vongThiSelect) {
            elements.vongThiSelect.addEventListener('change', handleVongThiChange);
        }

        // Sub-tab navigation
        elements.subTabBtns.forEach(btn => {
            btn.addEventListener('click', () => switchSubTab(btn.dataset.subtab));
        });

        // Search & Filter
        if (elements.searchSanPham) {
            elements.searchSanPham.addEventListener('input', debounce(filterSanPham, 300));
        }
        if (elements.filterTrangThai) {
            elements.filterTrangThai.addEventListener('change', filterSanPham);
        }

        // Buttons
        if (elements.btnRefreshCanhBao) {
            elements.btnRefreshCanhBao.addEventListener('click', loadCanhBaoIRR);
        }
        if (elements.btnDuyetTatCa) {
            elements.btnDuyetTatCa.addEventListener('click', handleDuyetTatCa);
        }
        if (elements.btnExportRanking) {
            elements.btnExportRanking.addEventListener('click', handleExportRanking);
        }
    }

    /**
     * Load danh sách vòng thi
     */
    async function loadVongThi() {
        try {
            const response = await fetch(`${API.vongThi}?id_sk=${state.idSK}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                state.dsVongThi = result.data;
                renderVongThiSelect();
            }
        } catch (error) {
            console.error('Error loading vong thi:', error);
        }
    }

    /**
     * Load danh sách giảng viên
     */
    async function loadGiangVien() {
        try {
            const response = await fetch(`${API.phanCong}?action=list_giang_vien&id_sk=${state.idSK}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                state.dsGiangVien = result.data;
            }
        } catch (error) {
            console.error('Error loading giang vien:', error);
        }
    }

    /**
     * Render vòng thi select
     */
    function renderVongThiSelect() {
        if (!elements.vongThiSelect || !state.dsVongThi.length) return;

        let html = '<option value="">-- Chọn vòng thi --</option>';
        state.dsVongThi.forEach(vt => {
            html += `<option value="${vt.idVongThi}">${vt.tenVongThi}</option>`;
        });
        elements.vongThiSelect.innerHTML = html;

        // Tự động chọn vòng thi đầu tiên để tải dữ liệu ngay
        if (state.dsVongThi.length > 0 && state.idVongThi <= 0) {
            elements.vongThiSelect.value = state.dsVongThi[0].idVongThi;
            elements.vongThiSelect.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Handle vòng thi change
     */
    function handleVongThiChange(e) {
        state.idVongThi = parseInt(e.target.value) || 0;

        if (state.idVongThi > 0) {
            const vt = state.dsVongThi.find(v => v.idVongThi === state.idVongThi);
            if (elements.vongThiStatus) {
                elements.vongThiStatus.textContent = vt ? vt.tenVongThi : 'Đã chọn';
                elements.vongThiStatus.className = 'px-3 py-1.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700';
            }
            loadAllData();
        } else {
            if (elements.vongThiStatus) {
                elements.vongThiStatus.textContent = 'Chưa chọn';
                elements.vongThiStatus.className = 'px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-200 text-slate-600';
            }
            resetAllData();
        }
    }

    /**
     * Load all data for current vòng thi
     */
    async function loadAllData() {
        await Promise.all([
            loadThongKe(),
            loadSanPham()
        ]);

        // Load data for current sub-tab
        switch (state.currentSubTab) {
            case 'tien-do':
                loadTienDoCham();
                loadCanhBaoIRR();
                break;
            case 'xet-duyet':
                loadThongKeKetQua();
                loadCanDuyet();
                loadBangVang();
                break;
        }
    }

    /**
     * Reset all data
     */
    function resetAllData() {
        state.dsSanPham = [];
        state.selectedSanPham = null;

        updateStats({ tongSanPham: '--', daPhanCong: '--', daChamXong: '--', daDuyet: '--' });

        if (elements.listSanPhamPhanCong) {
            elements.listSanPhamPhanCong.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p class="text-sm">Vui lòng chọn vòng thi...</p>
                </div>`;
        }
    }

    /**
     * Load thống kê tiến độ
     */
    async function loadThongKe() {
        try {
            const response = await fetch(`${API.tienDoIRR}?action=thong_ke&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                updateStats(result.data);
            }
        } catch (error) {
            console.error('Error loading thong ke:', error);
        }
    }

    /**
     * Update stats display
     */
    function updateStats(data) {
        if (elements.statTongSanPham) elements.statTongSanPham.textContent = data.tongSanPham ?? '--';
        if (elements.statDaPhanCong) elements.statDaPhanCong.textContent = data.daPhanCong ?? '--';
        if (elements.statDaChamXong) elements.statDaChamXong.textContent = data.daChamXong ?? '--';
        if (elements.statDaDuyet) elements.statDaDuyet.textContent = data.daDuyet ?? '--';
    }

    /**
     * Load danh sách sản phẩm
     */
    async function loadSanPham() {
        try {
            const response = await fetch(`${API.phanCong}?action=list_san_pham&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                state.dsSanPham = result.data;
                renderSanPhamList();
            }
        } catch (error) {
            console.error('Error loading san pham:', error);
        }
    }

    /**
     * Render danh sách sản phẩm (Tab 1)
     */
    function renderSanPhamList() {
        if (!elements.listSanPhamPhanCong) return;

        const filtered = getFilteredSanPham();

        if (!filtered.length) {
            elements.listSanPhamPhanCong.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-sm">Không có bài nộp nào</p>
                </div>`;
            return;
        }

        let html = '';
        filtered.forEach(sp => {
            const trangThai = getTrangThaiLabel(sp);
            const isSelected = state.selectedSanPham === sp.idSanPham;

            html += `
                <div class="san-pham-item p-3 border rounded-lg cursor-pointer transition-all hover:border-purple-300 hover:bg-purple-50 ${isSelected ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-200' : 'border-slate-200 bg-white'}" 
                     data-id="${sp.idSanPham}" onclick="scoringModule.selectSanPham(${sp.idSanPham})">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(sp.tensanpham || 'Chưa đặt tên')}</p>
                            <p class="text-xs text-slate-500">${escapeHtml(sp.tennhom || sp.manhom || 'N/A')}</p>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${trangThai.class}">${trangThai.text}</span>
                            <span class="text-xs text-slate-400">${sp.soGKDaCham}/${sp.soGiamKhao} GK</span>
                        </div>
                    </div>
                </div>`;
        });

        elements.listSanPhamPhanCong.innerHTML = html;
    }

    /**
     * Get filtered sản phẩm based on search and filter
     */
    function getFilteredSanPham() {
        let filtered = [...state.dsSanPham];
        const search = (elements.searchSanPham?.value || '').toLowerCase();
        const filter = elements.filterTrangThai?.value || '';

        if (search) {
            filtered = filtered.filter(sp =>
                (sp.tensanpham || '').toLowerCase().includes(search) ||
                (sp.tennhom || '').toLowerCase().includes(search) ||
                (sp.manhom || '').toLowerCase().includes(search)
            );
        }

        if (filter) {
            filtered = filtered.filter(sp => {
                switch (filter) {
                    case 'chua_phan_cong': return sp.soGiamKhao === 0;
                    case 'da_phan_cong': return sp.soGiamKhao > 0 && sp.soGKDaCham === 0;
                    case 'dang_cham': return sp.soGiamKhao > 0 && sp.soGKDaCham > 0 && sp.soGKDaCham < sp.soGiamKhao;
                    case 'da_cham': return sp.soGiamKhao > 0 && sp.soGKDaCham >= sp.soGiamKhao;
                    default: return true;
                }
            });
        }

        return filtered;
    }

    /**
     * Filter sản phẩm (debounced)
     */
    function filterSanPham() {
        renderSanPhamList();
    }

    /**
     * Get trạng thái label for sản phẩm
     */
    function getTrangThaiLabel(sp) {
        if (sp.trangThaiVongThi === 'Đã duyệt') {
            return { text: 'Đã duyệt', class: 'bg-emerald-100 text-emerald-700' };
        }
        if (sp.trangThaiVongThi === 'Bị loại') {
            return { text: 'Bị loại', class: 'bg-rose-100 text-rose-700' };
        }
        if (sp.soGiamKhao === 0) {
            return { text: 'Chờ phân công', class: 'bg-slate-100 text-slate-600' };
        }
        if (sp.soGKDaCham >= sp.soGiamKhao) {
            return { text: 'Đã chấm xong', class: 'bg-cyan-100 text-cyan-700' };
        }
        if (sp.soGKDaCham > 0) {
            return { text: 'Đang chấm', class: 'bg-amber-100 text-amber-700' };
        }
        return { text: 'Đã phân công', class: 'bg-purple-100 text-purple-700' };
    }

    /**
     * Select sản phẩm để phân công
     */
    async function selectSanPham(idSanPham) {
        state.selectedSanPham = idSanPham;
        renderSanPhamList();
        await loadPanelPhanCong(idSanPham);
    }

    /**
     * Load panel phân công giám khảo
     */
    async function loadPanelPhanCong(idSanPham) {
        if (!elements.panelPhanCong) return;

        const sp = state.dsSanPham.find(s => s.idSanPham === idSanPham);
        if (!sp) return;

        // Show loading
        elements.panelPhanCong.innerHTML = `
            <p class="mb-3 text-sm font-bold text-slate-700"><i class="fas fa-user-tag mr-2 text-slate-400"></i>Phân công giám khảo</p>
            <div class="px-4 py-4 text-center text-slate-400">
                <i class="fas fa-spinner fa-spin"></i> Đang tải...
            </div>`;

        try {
            // Load giám khảo đã phân công
            const response = await fetch(`${API.phanCong}?action=giam_khao_san_pham&id_san_pham=${idSanPham}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            const dsGKDaPhanCong = result.status === 'success' ? result.data : [];
            const dsGKDaPhanCongIds = dsGKDaPhanCong.map(gk => gk.idGV);

            // Build HTML
            let html = `
                <p class="mb-2 text-sm font-bold text-slate-700"><i class="fas fa-file-alt mr-2 text-slate-400"></i>${escapeHtml(sp.tensanpham || 'Bài nộp')}</p>
                <p class="mb-3 text-xs text-slate-500">Nhóm: ${escapeHtml(sp.tennhom || sp.manhom || 'N/A')}</p>

                <div class="mb-3">
                    <p class="mb-2 text-xs font-semibold text-slate-600">Giám khảo đã phân công (${dsGKDaPhanCong.length})</p>
                    <div class="space-y-2 max-h-[200px] overflow-y-auto">`;

            if (dsGKDaPhanCong.length > 0) {
                dsGKDaPhanCong.forEach(gk => {
                    const daCham = gk.trangThaiCham === 'Đã chấm';
                    html += `
                        <div class="flex items-center justify-between p-2 border rounded-lg ${daCham ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'}">
                            <div>
                                <p class="text-sm font-medium text-slate-700">${escapeHtml(gk.tenGV)}</p>
                                <p class="text-xs ${daCham ? 'text-emerald-600' : 'text-slate-400'}">${gk.trangThaiCham}${gk.diemTB ? ` - Điểm: ${parseFloat(gk.diemTB).toFixed(1)}` : ''}</p>
                            </div>
                            ${!daCham ? `
                                <button class="px-2 py-1 text-xs text-rose-600 hover:bg-rose-100 rounded transition-colors" 
                                        onclick="scoringModule.goPhanCong(${idSanPham}, ${gk.idGV})" title="Gỡ phân công">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </div>`;
                });
            } else {
                html += `<p class="text-xs text-slate-400 py-2">Chưa phân công giám khảo nào</p>`;
            }

            html += `</div></div>

                <div class="border-t border-slate-200 pt-3">
                    <p class="mb-2 text-xs font-semibold text-slate-600">Thêm giám khảo</p>
                    <select id="selectGiamKhao" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 mb-2">
                        <option value="">-- Chọn giảng viên --</option>`;

            state.dsGiangVien.forEach(gv => {
                if (!dsGKDaPhanCongIds.includes(gv.idGV)) {
                    html += `<option value="${gv.idGV}">${escapeHtml(gv.tenGV)} (${gv.soBaiDangCham} bài)</option>`;
                }
            });

            html += `</select>
                    <button class="w-full px-3 py-2 text-xs font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors"
                            onclick="scoringModule.themPhanCong(${idSanPham})">
                        <i class="fas fa-plus mr-1"></i>Phân công
                    </button>
                </div>`;

            elements.panelPhanCong.innerHTML = html;

        } catch (error) {
            console.error('Error loading panel phan cong:', error);
            elements.panelPhanCong.innerHTML = `
                <p class="mb-3 text-sm font-bold text-slate-700"><i class="fas fa-user-tag mr-2 text-slate-400"></i>Phân công giám khảo</p>
                <div class="px-4 py-4 text-center text-rose-400">
                    <i class="fas fa-exclamation-circle"></i> Lỗi tải dữ liệu
                </div>`;
        }
    }

    /**
     * Thêm phân công giám khảo
     */
    async function themPhanCong(idSanPham) {
        const select = document.getElementById('selectGiamKhao');
        const idGV = parseInt(select?.value) || 0;

        if (!idGV) {
            showToast('Vui lòng chọn giảng viên', 'warning');
            return;
        }

        try {
            const response = await fetch(`${API.phanCong}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'assign_doclap',
                    id_san_pham: idSanPham,
                    id_gv: idGV,
                    id_vong_thi: state.idVongThi
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('Phân công thành công', 'success');
                await loadSanPham();
                await loadPanelPhanCong(idSanPham);
                await loadThongKe();
            } else {
                showToast(result.message || 'Lỗi phân công', 'error');
            }
        } catch (error) {
            console.error('Error them phan cong:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Gỡ phân công giám khảo
     */
    async function goPhanCong(idSanPham, idGV) {
        const confirm = await Swal.fire({
            title: 'Gỡ phân công?',
            text: 'Bạn có chắc muốn gỡ giám khảo này khỏi bài thi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Gỡ',
            cancelButtonText: 'Hủy'
        });
        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`${API.phanCong}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'remove_doclap',
                    id_san_pham: idSanPham,
                    id_gv: idGV,
                    id_vong_thi: state.idVongThi
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('Đã gỡ phân công', 'success');
                await loadSanPham();
                await loadPanelPhanCong(idSanPham);
                await loadThongKe();
            } else {
                showToast(result.message || 'Lỗi gỡ phân công', 'error');
            }
        } catch (error) {
            console.error('Error go phan cong:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Switch sub-tab
     */
    function switchSubTab(tabId) {
        state.currentSubTab = tabId;

        // Update button styles
        elements.subTabBtns.forEach(btn => {
            if (btn.dataset.subtab === tabId) {
                btn.className = 'scoring-subtab-btn active px-4 py-3 text-sm font-semibold border-b-2 border-purple-600 text-purple-600 bg-purple-50 rounded-t-lg';
            } else {
                btn.className = 'scoring-subtab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 border-transparent';
            }
        });

        // Show/hide content
        elements.subTabContents.forEach(content => {
            content.classList.add('hidden');
        });

        const activeContent = document.getElementById(`subtab-${tabId}`);
        if (activeContent) {
            activeContent.classList.remove('hidden');
        }

        // Load data for tab if vòng thi is selected
        if (state.idVongThi > 0) {
            switch (tabId) {
                case 'phan-cong':
                    // Data already loaded
                    break;
                case 'tien-do':
                    loadTienDoCham();
                    loadCanhBaoIRR();
                    break;
                case 'xet-duyet':
                    loadThongKeKetQua();
                    loadCanDuyet();
                    loadBangVang();
                    break;
            }
        }
    }

    /**
     * Load tiến độ chấm điểm (Tab 2)
     */
    async function loadTienDoCham() {
        if (!elements.tbodyTienDoCham) return;

        try {
            const response = await fetch(`${API.tienDoIRR}?action=danh_sach_bai_thi&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                renderTienDoCham(result.data);
            }
        } catch (error) {
            console.error('Error loading tien do cham:', error);
        }
    }

    /**
     * Render bảng tiến độ chấm
     */
    function renderTienDoCham(data) {
        if (!elements.tbodyTienDoCham) return;

        if (!data.length) {
            elements.tbodyTienDoCham.innerHTML = `
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Không có dữ liệu</td></tr>`;
            return;
        }

        let html = '';
        data.forEach(item => {
            const percent = item.soGiamKhao > 0 ? Math.round((item.soGKDaCham / item.soGiamKhao) * 100) : 0;
            const trangThai = getTrangThaiLabel(item);

            html += `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-3 py-2 text-sm text-slate-700">${escapeHtml(item.tensanpham || 'N/A')}</td>
                    <td class="px-3 py-2 text-sm text-slate-500">${escapeHtml(item.tennhom || item.manhom || 'N/A')}</td>
                    <td class="px-3 py-2 text-center text-sm text-slate-600">${item.soGiamKhao}</td>
                    <td class="px-3 py-2 text-center text-sm text-slate-600">${item.soGKDaCham}</td>
                    <td class="px-3 py-2">
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="h-2 rounded-full ${percent >= 100 ? 'bg-emerald-500' : percent > 0 ? 'bg-amber-400' : 'bg-slate-300'}" style="width: ${percent}%"></div>
                        </div>
                        <p class="text-xs text-center text-slate-400 mt-1">${percent}%</p>
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${trangThai.class}">${trangThai.text}</span>
                    </td>
                </tr>`;
        });

        elements.tbodyTienDoCham.innerHTML = html;
    }

    /**
     * Load danh sách cảnh báo IRR (Tab 2)
     */
    async function loadCanhBaoIRR() {
        if (!elements.listCanhBaoIRR) return;

        try {
            const response = await fetch(`${API.tienDoIRR}?action=danh_sach_canh_bao&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                renderCanhBaoIRR(result.data);
            }
        } catch (error) {
            console.error('Error loading canh bao IRR:', error);
        }
    }

    /**
     * Render danh sách cảnh báo IRR
     */
    function renderCanhBaoIRR(data) {
        if (!elements.listCanhBaoIRR) return;

        if (!data.length) {
            elements.listCanhBaoIRR.innerHTML = `
                <div class="px-4 py-8 text-center text-emerald-400">
                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                    <p class="text-sm">Không phát hiện bài có độ lệch điểm bất thường</p>
                </div>`;
            return;
        }

        let html = '';
        data.forEach(item => {
            html += `
                <div class="p-3 border rounded-lg border-amber-200 bg-amber-50 cursor-pointer hover:bg-amber-100 transition-colors"
                     onclick="scoringModule.showIRRDetail(${item.idSanPham})">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(item.tensanpham || 'N/A')}</p>
                            <p class="text-xs text-slate-500">${escapeHtml(item.tennhom || 'N/A')} • ${item.soGiamKhao} GK</p>
                        </div>
                        <div class="ml-3 text-right">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Độ lệch: ${item.irr?.doLechMaxMin || 0}
                            </span>
                            <p class="text-xs text-slate-400 mt-1">p-value: ${item.irr?.pValue || 'N/A'}</p>
                        </div>
                    </div>
                </div>`;
        });

        elements.listCanhBaoIRR.innerHTML = html;
    }

    /**
     * Show IRR detail for a sản phẩm
     */
    async function showIRRDetail(idSanPham) {
        if (!elements.panelIRRDetail) return;

        elements.panelIRRDetail.innerHTML = `
            <div class="px-4 py-4 text-center text-slate-400">
                <i class="fas fa-spinner fa-spin"></i> Đang phân tích...
            </div>`;

        try {
            const response = await fetch(`${API.tienDoIRR}?action=phan_tich_irr&id_san_pham=${idSanPham}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                renderIRRDetail(result.data, idSanPham);
            }
        } catch (error) {
            console.error('Error loading IRR detail:', error);
        }
    }

    /**
     * Render IRR detail panel
     */
    function renderIRRDetail(data, idSanPham) {
        if (!elements.panelIRRDetail) return;

        const sp = state.dsSanPham.find(s => s.idSanPham === idSanPham);
        const irr = data.irr;
        const chiTiet = data.chiTietDiem || [];

        let html = `
            <div class="mb-3 p-3 rounded-lg ${irr?.canhBao ? 'bg-rose-50 border border-rose-200' : 'bg-emerald-50 border border-emerald-200'}">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold ${irr?.canhBao ? 'text-rose-700' : 'text-emerald-700'}">
                        ${irr?.canhBao ? '<i class="fas fa-exclamation-triangle mr-1"></i>Có cảnh báo' : '<i class="fas fa-check-circle mr-1"></i>Đồng thuận cao'}
                    </span>
                    <span class="text-xs text-slate-500">${irr?.phuongPhap || 'N/A'}</span>
                </div>
                <p class="text-sm text-slate-600">${irr?.ketLuan || 'Không có dữ liệu'}</p>
            </div>`;

        if (irr) {
            html += `
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="p-2 bg-slate-50 rounded-lg text-center">
                        <p class="text-xs text-slate-500">p-value</p>
                        <p class="text-lg font-bold ${irr.pValue < 0.05 ? 'text-rose-600' : 'text-emerald-600'}">${irr.pValue || 'N/A'}</p>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg text-center">
                        <p class="text-xs text-slate-500">Độ lệch Max-Min</p>
                        <p class="text-lg font-bold text-slate-700">${irr.doLechMaxMin || 0}</p>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg text-center">
                        <p class="text-xs text-slate-500">Điểm TB chung</p>
                        <p class="text-lg font-bold text-slate-700">${irr.diemTBChung || 0}</p>
                    </div>
                    <div class="p-2 bg-slate-50 rounded-lg text-center">
                        <p class="text-xs text-slate-500">${irr.phuongPhap === 'Paired T-test' ? 't-statistic' : 'F-statistic'}</p>
                        <p class="text-lg font-bold text-slate-700">${irr.statistic || 'N/A'}</p>
                    </div>
                </div>`;
        }

        // Chi tiết điểm theo GK
        if (chiTiet.length > 0) {
            html += `<p class="text-xs font-semibold text-slate-600 mb-2">Điểm theo giám khảo</p>`;
            chiTiet.forEach((gk, idx) => {
                html += `
                    <div class="p-2 border rounded-lg border-slate-200 mb-2">
                        <p class="text-sm font-medium text-slate-700">${escapeHtml(gk.tenGV)}</p>
                        <p class="text-xs text-slate-500">Tổng điểm: <span class="font-bold">${gk.tongDiem?.toFixed(1) || 0}</span></p>
                    </div>`;
            });
        }

        // Action buttons
        if (irr?.canhBao && chiTiet.length === 2) {
            html += `
                <button class="w-full mt-3 px-3 py-2 text-xs font-semibold text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors"
                        onclick="scoringModule.moiTrongTai(${idSanPham})">
                    <i class="fas fa-gavel mr-1"></i>Mời Trọng tài (GK thứ 3)
                </button>`;
        }

        elements.panelIRRDetail.innerHTML = html;
    }

    /**
     * Mời trọng tài (GK thứ 3)
     */
    async function moiTrongTai(idSanPham) {
        // Show modal to select GV
        const gvOptions = state.dsGiangVien.map(gv => `<option value="${gv.idGV}">${escapeHtml(gv.tenGV)}</option>`).join('');

        const result = await Swal.fire({
            title: 'Mời Trọng tài phúc khảo',
            html: `
                <p class="text-sm text-slate-600 mb-3">Chọn giám khảo thứ 3 để phúc khảo bài thi này. Điểm chốt hiện tại sẽ bị reset.</p>
                <select id="swalSelectGV" class="w-full px-3 py-2 border rounded-lg border-slate-300">
                    <option value="">-- Chọn giảng viên --</option>
                    ${gvOptions}
                </select>`,
            showCancelButton: true,
            confirmButtonText: 'Mời trọng tài',
            cancelButtonText: 'Hủy',
            preConfirm: () => {
                const idGV = document.getElementById('swalSelectGV')?.value;
                if (!idGV) {
                    Swal.showValidationMessage('Vui lòng chọn giảng viên');
                    return false;
                }
                return idGV;
            }
        });

        if (result.isConfirmed && result.value) {
            try {
                const response = await fetch(`${API.phanCong}?id_sk=${state.idSK}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_3rd_judge',
                        id_san_pham: idSanPham,
                        id_gv: parseInt(result.value),
                        id_vong_thi: state.idVongThi
                    })
                });

                const apiResult = await response.json();

                if (apiResult.status === 'success') {
                    showToast('Đã mời trọng tài thành công', 'success');
                    await loadAllData();
                } else {
                    showToast(apiResult.message || 'Lỗi mời trọng tài', 'error');
                }
            } catch (error) {
                console.error('Error moi trong tai:', error);
                showToast('Lỗi hệ thống', 'error');
            }
        }
    }

    /**
     * Load thống kê kết quả (Tab 3)
     */
    async function loadThongKeKetQua() {
        try {
            const response = await fetch(`${API.xetKetQua}?action=thong_ke_ket_qua&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                const d = result.data;
                if (elements.statKQDaDuyet) elements.statKQDaDuyet.textContent = d.daDuyet ?? '--';
                if (elements.statKQBiLoai) elements.statKQBiLoai.textContent = d.biLoai ?? '--';
                if (elements.statKQChoDuyet) elements.statKQChoDuyet.textContent = (d.dangXet || 0) + (d.chuaDuyet || 0);
                if (elements.statKQDiemTB) elements.statKQDiemTB.textContent = d.diemTBChung ?? '--';
            }
        } catch (error) {
            console.error('Error loading thong ke ket qua:', error);
        }
    }

    /**
     * Load danh sách cần duyệt (Tab 3)
     */
    async function loadCanDuyet() {
        if (!elements.listCanDuyet) return;

        try {
            const response = await fetch(`${API.xetKetQua}?action=danh_sach_can_duyet&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                renderCanDuyet(result.data);
            }
        } catch (error) {
            console.error('Error loading can duyet:', error);
        }
    }

    /**
     * Render danh sách cần duyệt
     */
    function renderCanDuyet(data) {
        if (!elements.listCanDuyet) return;

        if (!data.length) {
            elements.listCanDuyet.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p class="text-sm">Không có bài chờ duyệt</p>
                </div>`;
            return;
        }

        let html = '';
        data.forEach(item => {
            html += `
                <div class="p-3 border rounded-lg border-slate-200 bg-white hover:border-amber-300 transition-colors" data-id="${item.idSanPham}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(item.tensanpham || 'N/A')}</p>
                            <p class="text-xs text-slate-500">${escapeHtml(item.tennhom || item.manhom || 'N/A')}</p>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <span class="text-sm font-bold text-purple-600">${item.diemTrungBinh ? parseFloat(item.diemTrungBinh).toFixed(1) : '--'}</span>
                            <button class="px-2 py-1 text-xs font-semibold text-white bg-emerald-500 rounded hover:bg-emerald-600 transition-colors"
                                    onclick="scoringModule.duyetDiem(${item.idSanPham})">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="px-2 py-1 text-xs font-semibold text-white bg-rose-500 rounded hover:bg-rose-600 transition-colors"
                                    onclick="scoringModule.loaiDiem(${item.idSanPham})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
        });

        elements.listCanDuyet.innerHTML = html;
    }

    /**
     * Load bảng vàng (Tab 3)
     */
    async function loadBangVang() {
        if (!elements.listBangVang) return;

        try {
            const response = await fetch(`${API.xetKetQua}?action=bang_xep_hang&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`);
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                renderBangVang(result.data);
            }
        } catch (error) {
            console.error('Error loading bang vang:', error);
        }
    }

    /**
     * Render bảng vàng
     */
    function renderBangVang(data) {
        if (!elements.listBangVang) return;

        if (!data.length) {
            elements.listBangVang.innerHTML = `
                <div class="px-4 py-8 text-center text-amber-400">
                    <i class="fas fa-medal text-3xl mb-2"></i>
                    <p class="text-sm">Chưa có bài nào được duyệt</p>
                </div>`;
            return;
        }

        const medals = ['🥇', '🥈', '🥉'];

        let html = '';
        data.forEach((item, index) => {
            const medal = medals[index] || '';
            const rankClass = index < 3 ? 'border-amber-300 bg-gradient-to-r from-amber-50 to-yellow-50' : 'border-slate-200 bg-white';

            html += `
                <div class="p-3 border rounded-lg ${rankClass}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full ${index < 3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'} font-bold text-sm">
                            ${medal || item.xepHang}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(item.tensanpham || 'N/A')}</p>
                            <p class="text-xs text-slate-500">${escapeHtml(item.tennhom || item.manhom || 'N/A')}</p>
                            ${item.thanhVien ? `<p class="text-xs text-slate-400 truncate">${escapeHtml(item.thanhVien)}</p>` : ''}
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold ${index < 3 ? 'text-amber-600' : 'text-slate-700'}">${parseFloat(item.diemTrungBinh).toFixed(1)}</p>
                            <p class="text-xs text-slate-400">điểm</p>
                        </div>
                    </div>
                </div>`;
        });

        elements.listBangVang.innerHTML = html;
    }

    /**
     * Duyệt điểm bài thi
     */
    async function duyetDiem(idSanPham) {
        try {
            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'approve_score_manual',
                    id_san_pham: idSanPham,
                    id_vong_thi: state.idVongThi
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('Duyệt điểm thành công', 'success');
                await loadThongKeKetQua();
                await loadCanDuyet();
                await loadBangVang();
                await loadThongKe();
            } else {
                showToast(result.message || 'Lỗi duyệt điểm', 'error');
            }
        } catch (error) {
            console.error('Error duyet diem:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Loại bài thi
     */
    async function loaiDiem(idSanPham) {
        const confirm = await Swal.fire({
            title: 'Đánh rớt bài thi?',
            text: 'Hành động này sẽ chuyển trạng thái bài thi thành "Bị loại".',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Xác nhận loại',
            cancelButtonText: 'Hủy'
        });
        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'reject_score',
                    id_san_pham: idSanPham,
                    id_vong_thi: state.idVongThi
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast('Đã đánh rớt bài thi', 'success');
                await loadThongKeKetQua();
                await loadCanDuyet();
                await loadThongKe();
            } else {
                showToast(result.message || 'Lỗi đánh rớt', 'error');
            }
        } catch (error) {
            console.error('Error loai diem:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Duyệt tất cả bài chờ duyệt
     */
    async function handleDuyetTatCa() {
        const confirm = await Swal.fire({
            title: 'Duyệt tất cả?',
            text: 'Tất cả bài đang chờ sẽ được duyệt điểm và vào Bảng vàng.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Duyệt tất cả',
            cancelButtonText: 'Hủy'
        });
        if (!confirm.isConfirmed) return;

        try {
            // Get list of items to approve
            const items = elements.listCanDuyet?.querySelectorAll('[data-id]') || [];
            const dsSanPham = Array.from(items).map(el => parseInt(el.dataset.id));

            if (dsSanPham.length === 0) {
                showToast('Không có bài cần duyệt', 'info');
                return;
            }

            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'approve_multiple',
                    ds_san_pham: dsSanPham,
                    id_vong_thi: state.idVongThi
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showToast(result.message || 'Duyệt thành công', 'success');
                await loadThongKeKetQua();
                await loadCanDuyet();
                await loadBangVang();
                await loadThongKe();
            } else {
                showToast(result.message || 'Có lỗi xảy ra', 'warning');
            }
        } catch (error) {
            console.error('Error duyet tat ca:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Export ranking to Excel
     */
    function handleExportRanking() {
        showToast('Tính năng xuất Excel đang phát triển', 'info');
        // TODO: Implement Excel export
    }

    // =====================
    // Utility functions
    // =====================

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(message);
        }
    }

    // Expose public methods
    window.scoringModule = {
        init,
        selectSanPham,
        themPhanCong,
        goPhanCong,
        duyetDiem,
        loaiDiem,
        showIRRDetail,
        moiTrongTai
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
