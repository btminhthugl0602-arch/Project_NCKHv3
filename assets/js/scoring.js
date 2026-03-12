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
        canhBaoMap: {},
        selectedSanPham: null,
        currentSubTab: 'phan-cong',
        pollingTimer: null
    };

    const POLLING_INTERVAL = 30000; // 30 giây

    // DOM elements
    const elements = {};

    // API endpoints (relative to BASE_PATH)
    const API = {
        vongThi: BASE_PATH + '/api/su_kien/danh_sach_vong_thi.php',
        phanCong: BASE_PATH + '/api/cham_diem/phan_cong_giam_khao.php',
        tienDoIRR: BASE_PATH + '/api/cham_diem/tien_do_irr.php',
        xetKetQua: BASE_PATH + '/api/cham_diem/xet_ket_qua.php',
        thongBaoGK: BASE_PATH + '/api/thong_bao/giam_khao.php'
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
        elements.tbodyTienDoCham = document.getElementById('tbodyTienDoCham');
        elements.btnRefreshCanhBao = document.getElementById('btnRefreshCanhBao');
        elements.warningCountBadge = document.getElementById('warningCountBadge');

        // Tab 3: Xét kết quả
        elements.listCanDuyet = document.getElementById('listCanDuyet');
        elements.listBangVang = document.getElementById('listBangVang');
        elements.btnDuyetTatCa = document.getElementById('btnDuyetTatCa');
        elements.btnExportRanking = document.getElementById('btnExportRanking');
        elements.btnGuiNhacNho = document.getElementById('btnGuiNhacNho');
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
            elements.btnRefreshCanhBao.addEventListener('click', loadTienDoCham);
        }
        if (elements.btnDuyetTatCa) {
            elements.btnDuyetTatCa.addEventListener('click', handleDuyetTatCa);
        }
        if (elements.btnExportRanking) {
            elements.btnExportRanking.addEventListener('click', handleExportRanking);
        }
        if (elements.btnGuiNhacNho) {
            elements.btnGuiNhacNho.addEventListener('click', handleGuiNhacNho);
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
            const response = await fetch(`${API.phanCong}?action=giam_khao_san_pham&id_sk=${state.idSK}&id_san_pham=${idSanPham}&id_vong_thi=${state.idVongThi}`);
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
                    const isTrongTai = parseInt(gk.isTrongTai) === 1;
                    const trongTaiBadge = isTrongTai
                        ? `<span class="ml-1 px-1.5 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-700 border border-amber-200">
                               <i class="fas fa-shield-alt mr-0.5"></i>Trọng tài
                           </span>`
                        : '';
                    html += `
                        <div class="flex items-center justify-between p-2 border rounded-lg ${daCham ? 'border-emerald-200 bg-emerald-50' : isTrongTai ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'}">
                            <div>
                                <p class="text-sm font-medium text-slate-700">${escapeHtml(gk.tenGV)}${trongTaiBadge}</p>
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

        // Bắt đầu / dừng polling tự động khi chuyển tab
        if (tabId === 'tien-do') {
            startPolling();
        } else {
            stopPolling();
        }

        // Load data for tab if vòng thi is selected
        if (state.idVongThi > 0) {
            switch (tabId) {
                case 'phan-cong':
                    // Data already loaded
                    break;
                case 'tien-do':
                    loadTienDoCham();
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
     * Tự cập nhật: bắt đầu polling 30s khi Tab 2 đang mở
     */
    function startPolling() {
        stopPolling();
        state.pollingTimer = setInterval(async () => {
            if (state.idVongThi > 0 && state.currentSubTab === 'tien-do') {
                await loadTienDoCham();
            }
        }, POLLING_INTERVAL);
    }

    /**
     * Dừng polling
     */
    function stopPolling() {
        if (state.pollingTimer) {
            clearInterval(state.pollingTimer);
            state.pollingTimer = null;
        }
    }

    /**
     * Load tiến độ chấm điểm + cảnh báo IRR (Tab 2)
     */
    async function loadTienDoCham() {
        if (!elements.tbodyTienDoCham) return;

        elements.tbodyTienDoCham.innerHTML = `
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">
                <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...
            </td></tr>`;

        try {
            const [tienDoRes, canhBaoRes] = await Promise.all([
                fetch(`${API.tienDoIRR}?action=danh_sach_bai_thi&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`),
                fetch(`${API.tienDoIRR}?action=danh_sach_canh_bao&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`)
            ]);

            const [tienDoResult, canhBaoResult] = await Promise.all([
                tienDoRes.json(),
                canhBaoRes.json()
            ]);

            // Build warning map: idSanPham -> irr data
            state.canhBaoMap = {};
            if (canhBaoResult.status === 'success' && Array.isArray(canhBaoResult.data)) {
                canhBaoResult.data.forEach(item => {
                    state.canhBaoMap[item.idSanPham] = item.irr;
                });
            }

            // Update warning count badge
            const warnCount = Object.keys(state.canhBaoMap).length;
            if (elements.warningCountBadge) {
                if (warnCount > 0) {
                    elements.warningCountBadge.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${warnCount} bài có độ lệch cao`;
                    elements.warningCountBadge.className = 'text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200';
                } else {
                    elements.warningCountBadge.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Không có bài lệch cao`;
                    elements.warningCountBadge.className = 'text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg border border-emerald-200';
                }
            }

            if (tienDoResult.status === 'success' && tienDoResult.data) {
                renderTienDoCham(tienDoResult.data);
            }
        } catch (error) {
            console.error('Error loading tien do cham:', error);
        }
    }

    /**
     * Render bảng tiến độ chấm (6 cột mới: Nhóm, Đề tài, Tiến độ, Điểm TB, Phân tích, Xét duyệt)
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
            const hasWarning = !!state.canhBaoMap[item.idSanPham];
            const diemTB = (item.diemTrungBinh !== null && item.diemTrungBinh !== undefined)
                ? parseFloat(item.diemTrungBinh) : null;

            // Điểm TB cell
            let diemTBHtml = '<span class="text-slate-400">-</span>';
            if (diemTB !== null) {
                const diemClass = hasWarning ? 'text-amber-600 font-bold' : 'text-slate-700 font-bold';
                const warnIcon = hasWarning ? '<i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i>' : '';
                diemTBHtml = `<span class="${diemClass}">${warnIcon}${diemTB.toFixed(1)}</span>`;
            }

            // Xét duyệt cell
            let xetDuyetHtml = '';
            if (item.trangThaiVongThi === 'Đã duyệt') {
                const diemStr = diemTB !== null ? ` (${diemTB.toFixed(1)})` : '';
                xetDuyetHtml = `<div class="flex flex-col items-center gap-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-check-circle"></i>Đã duyệt${diemStr}</span>
                    <button onclick="scoringModule.huyDuyet(${item.idSanPham})"
                        class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fas fa-undo mr-0.5"></i>Hủy duyệt
                    </button>
                </div>`;
            } else if (item.trangThaiVongThi === 'Bị loại') {
                xetDuyetHtml = `<div class="flex flex-col items-center gap-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-700 border border-rose-200">
                        <i class="fas fa-times-circle"></i>Bị loại</span>
                    <button onclick="scoringModule.huyDuyet(${item.idSanPham})"
                        class="text-xs text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fas fa-undo mr-0.5"></i>Hủy loại
                    </button>
                </div>`;
            } else if (item.soGiamKhao > 0 && item.soGKDaCham >= item.soGiamKhao) {
                const btnClass = hasWarning
                    ? 'bg-amber-400 hover:bg-amber-500 text-white border-amber-400'
                    : 'bg-indigo-500 hover:bg-indigo-600 text-white border-indigo-500';
                const icon = hasWarning ? 'fa-exclamation-triangle' : 'fa-gavel';
                xetDuyetHtml = `<button onclick="scoringModule.showIRRDetailModal(${item.idSanPham})"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border ${btnClass} transition-colors">
                    <i class="fas ${icon}"></i>Quyết định</button>`;
            } else {
                xetDuyetHtml = `<span class="text-xs text-slate-400 italic">Đang chấm...</span>`;
            }

            // Tiến độ cell
            const progressColor = percent >= 100 ? 'bg-emerald-500' : percent > 0 ? 'bg-amber-400' : 'bg-slate-300';
            const progressTextColor = percent >= 100 ? 'text-emerald-600' : percent > 0 ? 'text-amber-600' : 'text-slate-500';
            const trongTaiLabel = item.soTrongTai > 0 ? ` +${item.soTrongTai}TT` : '';
            const progressLabel = `${item.soGKDaCham}/${item.soGiamKhao} GK${trongTaiLabel}`;

            // Can show detail - always enabled, modal handles "not enough data" state
            const canDetail = item.soGKDaCham >= 2;

            html += `
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-3 text-xs font-medium text-slate-500">${escapeHtml(item.manhom || item.tennhom || 'N/A')}</td>
                    <td class="px-3 py-3 text-sm max-w-[220px]">
                        <p class="font-medium text-slate-700 leading-snug">${escapeHtml(item.tensanpham || 'N/A')}</p>
                        ${hasWarning ? `<span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-200">
                            <i class="fas fa-exclamation-triangle"></i>Phát hiện độ lệch cao
                        </span>` : ''}
                    </td>
                    <td class="px-3 py-3 w-44">
                        <p class="text-xs font-bold ${progressTextColor} mb-1 text-center">${progressLabel}</p>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="h-2 rounded-full ${progressColor} transition-all" style="width: ${percent}%"></div>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center">${diemTBHtml}</td>
                    <td class="px-3 py-3 text-center">
                        <button onclick="scoringModule.showIRRDetailModal(${item.idSanPham})"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors ${canDetail ? 'text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100' : 'text-slate-400 bg-slate-50 border-slate-200 hover:bg-slate-100'}">
                            <i class="fas fa-chart-bar mr-1"></i>Chi tiết
                        </button>
                    </td>
                    <td class="px-3 py-3 text-center">${xetDuyetHtml}</td>
                </tr>`;
        });

        elements.tbodyTienDoCham.innerHTML = html;
    }

    /**
     * Load danh sách cảnh báo IRR (Tab 2)
     */
    // loadCanhBaoIRR is now handled inside loadTienDoCham (canhBaoMap state)
    function loadCanhBaoIRR() { loadTienDoCham(); }

    // renderCanhBaoIRR is no longer used (replaced by inline table + modal)
    function renderCanhBaoIRR() {}

    /**
     * Show IRR detail modal for a sản phẩm
     */
    async function showIRRDetailModal(idSanPham) {
        const modal = document.getElementById('modalPhanTichDiem');
        const body = document.getElementById('modalPhanTichBody');
        const titleEl = document.getElementById('modalPhanTichTitle');
        if (!modal || !body) return;

        // Find item meta from dsSanPham
        const item = state.dsSanPham.find(s => s.idSanPham === idSanPham) || {};

        if (titleEl) {
            titleEl.textContent = `Phân tích điểm - ${item.tensanpham || 'Bài thi'}`;
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        body.innerHTML = `
            <div class="flex items-center justify-center py-16 text-slate-400">
                <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
                <span>Đang phân tích...</span>
            </div>`;

        try {
            const response = await fetch(
                `${API.tienDoIRR}?action=phan_tich_irr&id_san_pham=${idSanPham}&id_vong_thi=${state.idVongThi}&id_sk=${state.idSK}`
            );
            const result = await response.json();

            if (result.status === 'success' && result.data) {
                const chiTiet = result.data.chiTietDiem || [];
                if (chiTiet.length < 2) {
                    // Not enough judges scored yet — show informative state
                    const scored = chiTiet.length;
                    const sp = state.dsSanPham.find(s => s.idSanPham === idSanPham) || {};
                    body.innerHTML = `
                        <div class="py-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                <i class="fas fa-hourglass-half text-2xl text-slate-400"></i>
                            </div>
                            <p class="text-base font-semibold text-slate-600 mb-2">Chưa đủ dữ liệu để phân tích</p>
                            <p class="text-sm text-slate-400 mb-4">
                                Bài thi hiện có <strong>${scored}</strong> giám khảo đã chấm điểm.<br>
                                Cần <strong>ít nhất 2 giám khảo</strong> hoàn thành chấm để xem phân tích độ lệch.
                            </p>
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-amber-200 bg-amber-50 text-amber-700 text-sm font-medium">
                                <i class="fas fa-info-circle"></i>
                                Vui lòng chờ giám khảo hoàn thành chấm điểm
                            </div>
                        </div>`;
                } else {
                    renderIRRDetailModal(result.data, idSanPham, item);
                }
            } else {
                body.innerHTML = `<div class="py-8 text-center text-rose-500"><i class="fas fa-exclamation-circle mr-2"></i>${result.message || 'Không thể tải dữ liệu'}</div>`;
            }
        } catch (error) {
            console.error('Error in showIRRDetailModal:', error);
            body.innerHTML = `<div class="py-8 text-center text-rose-500"><i class="fas fa-exclamation-circle mr-2"></i>Lỗi hệ thống</div>`;
        }
    }

    /**
     * Render full IRR analysis modal body — 5-step arbitrator workflow
     *
     * Step 1: Bảng điểm & phân tích độ lệch
     * Step 2: Nhận xét chi tiết của từng giám khảo
     * Step 3: Trạng thái Trọng tài phúc khảo
     * Step 4: Điều chỉnh & Quyết định điểm cuối
     * Step 5: Giám sát chất lượng giám khảo
     */
    function renderIRRDetailModal(data, idSanPham, itemMeta) {
        const body = document.getElementById('modalPhanTichBody');
        if (!body) return;

        const irr               = data.irr;
        const chiTietDiem       = data.chiTietDiem || [];
        // phanCongTrongTai: danh sách trọng tài đã phân công từ DB (kể cả chưa chấm)
        const phanCongTrongTai  = data.phanCongTrongTai || [];
        const tenSanPham        = itemMeta?.tensanpham || 'N/A';
        const tenNhom           = itemMeta?.tennhom || itemMeta?.manhom || 'N/A';

        const baiThiMeta       = state.dsSanPham.find(s => s.idSanPham === idSanPham) || {};
        const trangThaiHienTai = baiThiMeta.trangThaiVongThi || null;
        const isDone           = trangThaiHienTai === 'Đã duyệt' || trangThaiHienTai === 'Bị loại';

        // Nhãn: "Giám khảo N" cho GK chính, "Trọng tài [N]" cho trọng tài
        let gkCount = 0, ttCount = 0;
        const judges = chiTietDiem.map((gk) => {
            const isTrongTai = !!gk.isTrongTai;
            const label = isTrongTai
                ? (ttCount++ === 0 ? 'Trọng tài' : `Trọng tài ${ttCount}`)
                : `Giám khảo ${++gkCount}`;
            return {
                idGV:       gk.idGV,
                tenGV:      gk.tenGV,
                tongDiem:   gk.tongDiem || 0,
                label,
                isTrongTai,
            };
        });

        // Per-criterion data
        const criteriaMap = {};
        chiTietDiem.forEach(gk => {
            (gk.chiTiet || []).forEach(tc => {
                if (!criteriaMap[tc.idTieuChi]) {
                    criteriaMap[tc.idTieuChi] = {
                        id:        tc.idTieuChi,
                        name:      tc.noiDungTieuChi,
                        diemToiDa: parseFloat(tc.diemToiDa) || 10,
                        scoresByGV: {},
                    };
                }
                criteriaMap[tc.idTieuChi].scoresByGV[gk.idGV] = parseFloat(tc.diem);
            });
        });

        // Độ lệch tính trên GK chính (không tính trọng tài)
        const mainJudges = judges.filter(j => !j.isTrongTai);
        // trongTaiJudges: ưu tiên danh sách từ phancong_doclap (đầy đủ, kể cả chưa chấm)
        // nếu không có thì fallback về judges đã chấm
        const trongTaiJudges = phanCongTrongTai.length > 0
            ? phanCongTrongTai.map(tt => {
                const scored = judges.find(j => j.idGV == tt.idGV);
                return {
                    idGV:       tt.idGV,
                    tenGV:      tt.tenGV,
                    tongDiem:   scored?.tongDiem || 0,
                    isTrongTai: true,
                    daChấm:     !!scored,
                };
            })
            : judges.filter(j => j.isTrongTai);

        let problemCriteria = 0, maxDeviation = 0, maxDeviationName = '';

        const criteriaList = Object.values(criteriaMap).map(tc => {
            const mainScores = mainJudges.map(j => tc.scoresByGV[j.idGV]).filter(s => s !== undefined);
            const allScores  = judges.map(j => tc.scoresByGV[j.idGV]).filter(s => s !== undefined);
            let avg = 0, deviation = 0;
            if (mainScores.length >= 2) {
                avg       = mainScores.reduce((a, b) => a + b, 0) / mainScores.length;
                const mx  = Math.max(...mainScores), mn = Math.min(...mainScores);
                deviation = avg > 0 ? ((mx - mn) / avg) * 100 : 0;
            } else if (allScores.length >= 1) {
                avg = allScores.reduce((a, b) => a + b, 0) / allScores.length;
            }
            const isHigh = deviation > 30;
            if (isHigh) {
                problemCriteria++;
                if (deviation > maxDeviation) {
                    maxDeviation     = deviation;
                    maxDeviationName = tc.name;
                }
            }
            return { ...tc, avg, deviation, isHigh, allScores };
        });

        const totalCriteria  = criteriaList.length;
        const hasCanhBao     = irr?.canhBao || problemCriteria > 0;
        const hasArbitrator  = trongTaiJudges.length > 0;
        // ttScored: true nếu TẤT CẢ trọng tài đã chấm ĐỦ TẤT CẢ tiêu chí (không chỉ 1 tiêu chí)
        const ttScored = hasArbitrator && trongTaiJudges.every(tt =>
            criteriaList.every(tc => tc.scoresByGV[tt.idGV] !== undefined)
        );

        // TB tổng điểm chỉ trên GK chính (mainJudges) — không trộn điểm trọng tài
        const scoredMainJudges = mainJudges.filter(j =>
            j.tongDiem > 0 || criteriaList.some(tc => tc.scoresByGV[j.idGV] !== undefined)
        );
        const overallAvg  = scoredMainJudges.length > 0
            ? (scoredMainJudges.reduce((a, j) => a + j.tongDiem, 0) / scoredMainJudges.length).toFixed(2)
            : '0.00';
        const diemHienTai = baiThiMeta.diemTrungBinh != null
            ? parseFloat(baiThiMeta.diemTrungBinh).toFixed(2)
            : overallAvg;

        // Tỉ lệ đồng thuận hiển thị = % tiêu chí KHÔNG lệch cao (dễ hiểu với BTC/GK)
        const okCriteria  = totalCriteria - problemCriteria;
        const irrPercent  = totalCriteria > 0
            ? ((okCriteria / totalCriteria) * 100).toFixed(1)
            : '100.0';

        // ── Bảng điểm chi tiết ────────────────────────────────────────────
        const judgeHeaders = judges.map(j =>
            `<th class="px-3 py-2.5 text-center text-xs font-semibold uppercase ${j.isTrongTai ? 'text-amber-600' : 'text-slate-500'}">${escapeHtml(j.label)}</th>`
        ).join('');

        const criteriaRows = criteriaList.map(tc => {
            const scoreCells = judges.map(j => {
                const s   = tc.scoresByGV[j.idGV];
                const cls = j.isTrongTai ? 'text-amber-700 font-semibold' : '';
                return `<td class="px-3 py-2.5 text-center text-sm ${cls}">${s !== undefined ? s : '<span class="text-slate-300">—</span>'}</td>`;
            }).join('');
            const avgTxt = tc.allScores.length > 0 ? tc.avg.toFixed(2) : '—';
            const devTxt = mainJudges.length >= 2 ? `${tc.deviation.toFixed(1)}%` : '—';
            const badge  = mainJudges.length < 2 ? '' : tc.isHigh
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">&#9651; Lệch cao</span>`
                : `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">&#10003; OK</span>`;
            return `<tr class="border-b border-slate-100 ${tc.isHigh ? 'bg-red-50' : 'hover:bg-slate-50'}">
                <td class="px-3 py-2.5 text-sm text-slate-700">${escapeHtml(tc.name)}<span class="text-slate-400 text-xs ml-1">(/${tc.diemToiDa})</span></td>
                ${scoreCells}
                <td class="px-3 py-2.5 text-center font-bold text-sm ${tc.isHigh ? 'text-red-600' : 'text-slate-700'}">${avgTxt}</td>
                <td class="px-3 py-2.5 text-center text-sm text-slate-500">${devTxt}</td>
                <td class="px-3 py-2.5 text-center">${badge}</td>
            </tr>`;
        }).join('');

        const totalCells = judges.map(j => {
            const v = j.tongDiem;
            const display = v > 0 ? (v % 1 === 0 ? v : v.toFixed(1)) : '—';
            return `<td class="px-3 py-2.5 text-center font-bold text-sm ${j.isTrongTai ? 'text-amber-700' : 'text-slate-800'}">${display}</td>`;
        }).join('');

        // ── Section 2: IRR stats + kết luận ──────────────────────────────
        const irrKetLuan = hasCanhBao
            ? `<b>Kết luận:</b> <span class="text-red-600 font-semibold">&#9651; CÓ SỰ KHÁC BIỆT: ${problemCriteria}/${totalCriteria} tiêu chí có độ lệch cao. Cần xem xét lại!</span>`
            : `<b>Kết luận:</b> <span class="text-green-600 font-semibold">&#10003; ĐÁNH GIÁ ĐỒNG THUẬN TỐT: Các giám khảo có sự thống nhất cao trong đánh giá.</span>`;

        const irrKhuyenNghi = hasCanhBao ? `
            <li>Xem xét lại điểm số của các tiêu chí có độ lệch cao (được đánh dấu đỏ).</li>
            <li>Yêu cầu Hội đồng / Người chấm giải thích lý do cho điểm.</li>
            ${!hasArbitrator ? '<li>Cân nhắc mời Giám khảo thứ 3 (nếu hiện tại chỉ có 2) để phúc khảo nhằm đưa ra quyết định công bằng.</li>' : ''}
        ` : `<li>Kết quả đánh giá đáng tin cậy, có thể tiến hành duyệt điểm.</li>`;

        // ── Section 3: Quyết định ─────────────────────────────────────────
        let decisionContent = '';

        if (isDone) {
            decisionContent = trangThaiHienTai === 'Đã duyệt'
                ? `<div class="flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200">
                       <i class="fas fa-check-circle text-green-500 text-xl"></i>
                       <div>
                           <p class="text-sm font-bold text-green-700">Đã duyệt thành công</p>
                           <p class="text-xs text-green-600">Điểm chốt: <strong>${diemHienTai}</strong></p>
                       </div>
                   </div>`
                : `<div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                       <i class="fas fa-times-circle text-red-500 text-xl"></i>
                       <p class="text-sm font-bold text-red-700">Bài đã bị loại khỏi vòng thi</p>
                   </div>`;

        } else if (hasArbitrator && !ttScored) {
            // Trọng tài được mời nhưng chưa chấm — hiện danh sách đầy đủ
            const trongTaiRows = trongTaiJudges.map(tt => {
                const scored = tt.daChấm || criteriaList.some(tc => tc.scoresByGV[tt.idGV] !== undefined);
                return `<div class="flex items-center justify-between py-2.5 border-b border-amber-100 last:border-0">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-shield text-amber-400 text-xs"></i>
                        <span class="text-sm font-medium text-amber-900">${escapeHtml(tt.tenGV)}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full ${scored ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">
                        ${scored ? '<i class="fas fa-check"></i> Đã chấm xong' : '<i class="fas fa-hourglass-half"></i> Chưa chấm'}
                    </span>
                </div>`;
            }).join('');

            decisionContent = `
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-3">
                    <p class="text-sm font-bold text-amber-700 mb-3">
                        <i class="fas fa-shield-alt mr-2"></i>Danh sách Trọng tài đã mời
                        <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full">${trongTaiJudges.length}</span>
                    </p>
                    <div class="divide-y divide-amber-100">
                        ${trongTaiRows}
                    </div>
                </div>
                <p class="text-xs text-slate-400 italic">
                    <i class="fas fa-info-circle mr-1"></i>Vui lòng chờ Trọng tài hoàn thành chấm điểm trước khi ra quyết định.
                </p>`;

        } else {
            // Bình thường (không có trọng tài / trọng tài đã chấm xong)
            const noteCount = scoredMainJudges.length;
            const noteText  = noteCount > 1
                ? `* Điểm gợi ý hiện tại là trung bình cộng của ${noteCount} giám khảo. BTC có thể chốt trực tiếp hoặc sửa lại theo quyết định cuối cùng.`
                : `* Điểm gợi ý từ giám khảo. BTC có thể điều chỉnh trước khi chốt.`;

            // Hiển thị danh sách trọng tài đã chấm xong (khi có)
            const trongTaiDoneHtml = hasArbitrator ? `
                <div class="rounded-xl border border-green-200 bg-green-50 p-3 mb-3">
                    <p class="text-xs font-bold text-green-700 mb-2">
                        <i class="fas fa-shield-alt mr-1"></i>Trọng tài phúc khảo đã tham gia chấm
                    </p>
                    <div class="space-y-1">
                        ${trongTaiJudges.map(tt => `
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user-shield text-green-500 text-xs"></i>
                                <span class="text-xs font-medium text-green-800">${escapeHtml(tt.tenGV)}</span>
                            </div>
                            <span class="text-xs text-green-600 font-semibold">
                                <i class="fas fa-check mr-1"></i>${tt.tongDiem > 0 ? tt.tongDiem.toFixed(1) + ' điểm' : 'Đã chấm'}
                            </span>
                        </div>`).join('')}
                    </div>
                </div>` : '';

            // Phần mời trọng tài chỉ hiện khi có lệch điểm VÀ chưa có trọng tài
            const trongTaiHtml = (hasCanhBao && !hasArbitrator) ? `
                <hr class="border-dashed border-slate-300 my-4">
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
                    <p class="text-sm font-bold text-amber-700 mb-1">
                        <i class="fas fa-shield-alt mr-2"></i>Mời thêm Trọng tài (Phúc khảo)
                    </p>
                    <p class="text-xs text-slate-600 mb-3">
                        Bài thi đang có sự chênh lệch điểm lớn giữa các giám khảo. Để đảm bảo tính khách quan và công bằng,
                        Ban tổ chức nên phân công thêm một Giám khảo thứ 3 (Trọng tài) để tham gia chấm phúc khảo
                        trước khi đưa ra quyết định cuối cùng.
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <select id="selectTrongTai_${idSanPham}"
                            class="flex-1 min-w-[180px] px-3 py-2 text-sm border border-amber-300 rounded-lg bg-white focus:border-amber-500 focus:outline-none">
                            <option value="">-- Chọn Giám khảo bổ sung --</option>
                            ${state.dsGiangVien.map(gv => `<option value="${gv.idGV}">${escapeHtml(gv.tenGV)}</option>`).join('')}
                        </select>
                        <button onclick="scoringModule.moiTrongTaiFromModal(${idSanPham})"
                            class="px-4 py-2 text-sm font-semibold text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors whitespace-nowrap">
                            <i class="fas fa-user-plus mr-2"></i>Gửi lời mời Trọng tài
                        </button>
                    </div>
                </div>` : '';

            decisionContent = `
                ${trongTaiDoneHtml}
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <div class="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        <span class="text-sm text-slate-600 font-medium">Điểm chốt:</span>
                        <input type="number" id="inputDiemChot_${idSanPham}" step="0.01" min="0"
                            value="${overallAvg}"
                            class="w-24 text-base font-bold text-red-500 text-center bg-transparent focus:outline-none border-b border-red-300 focus:border-red-500">
                    </div>
                    <button onclick="scoringModule.handleApproveFromModal(${idSanPham})"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>Duyệt &amp; Chốt
                    </button>
                    <button onclick="scoringModule.handleRejectFromModal(${idSanPham})"
                        class="px-5 py-2 text-sm font-semibold text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-times-circle mr-2"></i>Đánh rớt
                    </button>
                </div>
                <p class="text-xs text-slate-400 italic">${noteText}</p>
                ${trongTaiHtml}`;
        }

        // ── Render ───────────────────────────────────────────────────────
        body.innerHTML = `
            <div class="space-y-4">

                <!-- Metadata -->
                <div class="text-sm space-y-1">
                    <p><span class="text-slate-500">Nhóm:</span> <span class="font-semibold">${escapeHtml(tenNhom)}</span></p>
                    <p><span class="text-slate-500">Đề tài:</span> <a href="#" class="text-blue-600 hover:underline">${escapeHtml(tenSanPham)}</a></p>
                    <p><span class="text-slate-500">Số người chấm:</span>
                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-600 rounded-full ml-1">${judges.length}</span>
                    </p>
                </div>

                <!-- Section 1: Bảng điểm chi tiết -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-table text-slate-500 text-sm"></i>
                        <span class="text-sm font-semibold text-slate-700">Bảng điểm chi tiết</span>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase text-slate-500 min-w-[160px]">Tiêu chí</th>
                                    ${judgeHeaders}
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase text-slate-500">TB</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase text-slate-500">Độ lệch</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase text-slate-500 w-24">Cảnh báo</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${criteriaRows}
                                <tr class="bg-slate-100 border-t-2 border-slate-300">
                                    <td class="px-3 py-2.5 text-sm font-bold text-slate-800">TỔNG ĐIỂM</td>
                                    ${totalCells}
                                    <td class="px-3 py-2.5 text-center font-bold text-sm ${hasCanhBao ? 'text-red-600' : 'text-slate-800'}">
                                        ${parseFloat(overallAvg) % 1 === 0 ? parseFloat(overallAvg) : parseFloat(overallAvg).toFixed(1)}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 2: Kiểm định thống kê (accordion, mở mặc định) -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <button onclick="(function(btn){btn.nextElementSibling.classList.toggle('hidden');btn.querySelector('.irr-chevron').classList.toggle('rotate-180');})(this)"
                        class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition-colors text-left">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chart-bar text-slate-500 text-sm"></i>
                            <span class="text-sm font-semibold text-slate-700">Kiểm định thống kê (Inter-Rater Reliability)</span>
                        </div>
                        <i class="fas fa-chevron-down text-slate-400 text-xs irr-chevron transition-transform rotate-180"></i>
                    </button>
                    <div class="p-4 border-t border-slate-200">
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="rounded-lg border border-slate-200 p-3 text-center bg-white">
                                <p class="text-xs text-slate-500 mb-1">Mức độ đồng thuận (Tổng thể):</p>
                                <p class="text-2xl font-bold ${hasCanhBao ? 'text-amber-500' : 'text-green-600'}">${irrPercent}%</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 p-3 text-center bg-white">
                                <p class="text-xs text-slate-500 mb-1">Tiêu chí có vấn đề (Lệch cao):</p>
                                <p class="text-2xl font-bold ${problemCriteria > 0 ? 'text-red-600' : 'text-green-600'}">${problemCriteria}/${totalCriteria}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 p-3 text-center bg-white">
                                <p class="text-xs text-slate-500 mb-1">Độ lệch cao nhất:</p>
                                <p class="text-2xl font-bold ${maxDeviation > 30 ? 'text-red-600' : 'text-green-600'}">${maxDeviation.toFixed(1)}%</p>
                                ${maxDeviationName ? `<p class="text-xs text-slate-400 mt-0.5">(${escapeHtml(maxDeviationName)})</p>` : ''}
                            </div>
                        </div>
                        <div class="border-l-4 ${hasCanhBao ? 'border-amber-400 bg-amber-50' : 'border-green-400 bg-green-50'} rounded-r-lg p-4">
                            <p class="text-sm text-slate-700 mb-2">${irrKetLuan}</p>
                            <p class="text-sm font-semibold text-slate-700 mb-1">Khuyến nghị:</p>
                            <ul class="list-disc list-inside space-y-1 text-sm text-slate-600">${irrKhuyenNghi}</ul>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Quyết định (accordion, mở mặc định) -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <button onclick="(function(btn){btn.nextElementSibling.classList.toggle('hidden');btn.querySelector('.dec-chevron').classList.toggle('rotate-180');})(this)"
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition-colors text-left">
                        <span class="text-sm font-semibold text-blue-700">Quyết định điểm chốt của Hội đồng</span>
                        <i class="fas fa-chevron-down text-blue-400 text-xs dec-chevron transition-transform rotate-180"></i>
                    </button>
                    <div class="p-4 border-t border-slate-200">
                        ${decisionContent}
                    </div>
                </div>

            </div>`;
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
                if (elements.statKQChoDuyet) elements.statKQChoDuyet.textContent = d.sanSangDuyet ?? '--';
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
                            <button onclick="scoringModule.huyDuyet(${item.idSanPham})"
                                class="mt-1 px-2 py-0.5 text-xs text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded transition-colors"
                                title="Hủy duyệt">
                                <i class="fas fa-undo mr-0.5"></i>Hủy
                            </button>
                        </div>
                    </div>
                </div>`;
        });

        elements.listBangVang.innerHTML = html;
    }

    /**
     * Duyệt điểm bài thi (có xác nhận)
     */
    async function duyetDiem(idSanPham) {
        const sp = state.dsSanPham.find(s => s.idSanPham === idSanPham);
        const hasWarning = !!state.canhBaoMap[idSanPham];
        const diemTB = sp?.diemTrungBinh ? parseFloat(sp.diemTrungBinh).toFixed(1) : '?';

        const confirmResult = await Swal.fire({
            title: 'Duyệt điểm bài thi?',
            html: hasWarning
                ? `<p class="text-sm text-slate-600">Bài này đang có <span class="text-amber-600 font-semibold">cảnh báo độ lệch điểm cao</span>. Bạn có chắc muốn duyệt với điểm TB <strong>${diemTB}</strong>?</p>`
                : `<p class="text-sm text-slate-600">Xác nhận duyệt bài thi với điểm TB <strong>${diemTB}</strong>?</p>`,
            icon: hasWarning ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: hasWarning ? '#f59e0b' : '#10b981',
            confirmButtonText: 'Duyệt',
            cancelButtonText: 'Hủy'
        });
        if (!confirmResult.isConfirmed) return;
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
                await Promise.all([
                    loadThongKe(),
                    loadSanPham(),
                    loadThongKeKetQua(),
                    loadCanDuyet(),
                    loadBangVang()
                ]);
                // Cập nhật bảng tiến độ nếu đang ở Tab 2
                if (state.currentSubTab === 'tien-do') loadTienDoCham();
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
                await Promise.all([
                    loadThongKe(),
                    loadSanPham(),
                    loadThongKeKetQua(),
                    loadCanDuyet()
                ]);
                if (state.currentSubTab === 'tien-do') loadTienDoCham();
            } else {
                showToast(result.message || 'Lỗi đánh rớt', 'error');
            }
        } catch (error) {
            console.error('Error loai diem:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Duyệt tất cả bài chờ duyệt (có cảnh báo IRR)
     */
    async function handleDuyetTatCa() {
        // Thu thập danh sách từ DOM
        const items = elements.listCanDuyet?.querySelectorAll('[data-id]') || [];
        const dsSanPham = Array.from(items).map(el => parseInt(el.dataset.id));

        if (dsSanPham.length === 0) {
            showToast('Không có bài cần duyệt', 'info');
            return;
        }

        // Tách danh sách bài có cảnh báo và bài sạch
        const warnedIds = dsSanPham.filter(id => !!state.canhBaoMap[id]);
        const cleanIds  = dsSanPham.filter(id => !state.canhBaoMap[id]);
        const hasWarned = warnedIds.length > 0;

        // Xây dựng nội dung cảnh báo
        let warnHtml = '';
        if (hasWarned) {
            const spNames = warnedIds.map(id => {
                const sp = state.dsSanPham.find(s => s.idSanPham === id);
                return `<li class="text-xs">${escapeHtml(sp?.tensanpham || `ID ${id}`)}</li>`;
            }).join('');
            warnHtml = `
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-left">
                    <p class="text-sm font-semibold text-amber-700 mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>${warnedIds.length} bài có cảnh báo độ lệch cao:
                    </p>
                    <ul class="list-disc list-inside text-amber-800 space-y-0.5">${spNames}</ul>
                </div>`;
        }

        const confirmResult = await Swal.fire({
            title: 'Duyệt tất cả?',
            html: hasWarned
                ? `<p class="text-sm text-slate-600">Có <strong>${dsSanPham.length}</strong> bài chờ duyệt, trong đó <span class="text-amber-600 font-bold">${warnedIds.length} bài có cảnh báo độ lệch điểm.</span>${warnHtml}</p>`
                : `<p class="text-sm text-slate-600">Tất cả <strong>${dsSanPham.length}</strong> bài đang chờ sẽ được duyệt điểm và vào Bảng vàng.</p>`,
            icon: hasWarned ? 'warning' : 'question',
            showCancelButton: true,
            showDenyButton: hasWarned,
            confirmButtonColor: '#10b981',
            denyButtonColor: '#f59e0b',
            confirmButtonText: hasWarned ? `Duyệt tất cả (kể cả ${warnedIds.length} bài cảnh báo)` : 'Duyệt tất cả',
            denyButtonText: hasWarned ? `Chỉ duyệt ${cleanIds.length} bài không cảnh báo` : '',
            cancelButtonText: 'Hủy'
        });

        if (!confirmResult.isConfirmed && !confirmResult.isDenied) return;

        // Xác định danh sách cần duyệt và cờ skip_warned
        let dsToApprove = dsSanPham;
        let skipWarned  = false;
        if (confirmResult.isDenied) {
            // Chỉ duyệt bài sạch, bỏ qua bài cảnh báo
            dsToApprove = cleanIds;
            skipWarned  = false; // Chỉ gửi bài sạch, không cần skip_warned
        }

        if (dsToApprove.length === 0) {
            showToast('Không có bài nào đủ điều kiện để duyệt', 'info');
            return;
        }

        try {
            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'approve_multiple',
                    ds_san_pham: dsToApprove,
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
     * Hủy duyệt / hủy loại bài thi
     */
    async function huyDuyet(idSanPham) {
        const sp = state.dsSanPham.find(s => s.idSanPham === idSanPham);
        const trangThai = sp?.trangThaiVongThi || 'Đã duyệt';
        const action = trangThai === 'Bị loại' ? 'hủy loại' : 'hủy duyệt';

        const confirmed = await Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)}?`,
            html: `<p class="text-sm text-slate-600">Xác nhận <strong>${action}</strong> bài <em>${escapeHtml(sp?.tensanpham || 'N/A')}</em>?<br><span class="text-amber-600">Trạng thái sẽ về "Chờ duyệt" để xem xét lại.</span></p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: `Xác nhận ${action}`,
            cancelButtonText: 'Hủy'
        });
        if (!confirmed.isConfirmed) return;

        try {
            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cancel_approval',
                    id_san_pham: idSanPham,
                    id_vong_thi: state.idVongThi
                })
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(`Đã ${action} thành công`, 'success');
                await loadThongKeKetQua();
                await loadCanDuyet();
                await loadBangVang();
                await loadThongKe();
                await loadSanPham();
            } else {
                showToast(result.message || `Lỗi ${action}`, 'error');
            }
        } catch (error) {
            console.error('Error huy duyet:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Export ranking to Excel
     */
    function handleExportRanking() {
        if (!state.idSK || !state.idVongThi) {
            showToast('Vui lòng chọn vòng thi trước khi xuất bảng xếp hạng', 'warning');
            return;
        }
        const url = `${API.xetKetQua}?action=export_ranking&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`;
        window.open(url, '_blank');
    }

    /**
     * Gửi thông báo nhắc nhở tới các GK chưa hoàn thành chấm điểm
     */
    async function handleGuiNhacNho() {
        if (!state.idSK || !state.idVongThi) {
            showToast('Vui lòng chọn vòng thi trước', 'warning');
            return;
        }

        const confirmed = await Swal.fire({
            title: 'Gửi nhắc nhở giám khảo?',
            text: 'Hệ thống sẽ gửi thông báo nhắc nhở tới tất cả giám khảo chưa hoàn thành chấm điểm trong vòng thi này.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Gửi ngay',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#3b82f6'
        });
        if (!confirmed.isConfirmed) return;

        try {
            const response = await fetch(API.thongBaoGK + '?id_sk=' + state.idSK, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'gui_nhac_nho',
                    id_sk: state.idSK,
                    id_vong_thi: state.idVongThi
                })
            });
            const result = await response.json();

            if (result.status === 'success') {
                const so = result.data?.soGKNhanThongBao ?? 0;
                if (so === 0) {
                    showToast(result.message, 'info');
                } else {
                    showToast(`Đã gửi nhắc nhở tới ${so} giám khảo`, 'success');
                }
            } else {
                showToast(result.message || 'Gửi thông báo thất bại', 'error');
            }
        } catch (err) {
            console.error('handleGuiNhacNho error:', err);
            showToast('Lỗi kết nối khi gửi thông báo', 'error');
        }
    }

    // =====================
    // Utility functions
    // =====================

    /**
     * Close IRR analysis modal
     */
    function closeIRRModal() {
        const modal = document.getElementById('modalPhanTichDiem');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    /**
     * Approve & lock score from modal
     */
    async function handleApproveFromModal(idSanPham) {
        const input = document.getElementById(`inputDiemChot_${idSanPham}`);
        const diemChot = input ? parseFloat(input.value) : null;

        if (diemChot === null || isNaN(diemChot)) {
            showToast('Vui lòng nhập điểm chốt hợp lệ', 'warning');
            return;
        }

        const confirm = await Swal.fire({
            title: 'Duyệt & Chốt điểm?',
            text: `Xác nhận duyệt bài thi với điểm chốt: ${diemChot.toFixed(2)}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Duyệt & Chốt',
            cancelButtonText: 'Hủy'
        });
        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`${API.xetKetQua}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'approve_score_manual',
                    id_san_pham: idSanPham,
                    id_vong_thi: state.idVongThi,
                    diem_chot: diemChot
                })
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast('Duyệt điểm thành công!', 'success');
                closeIRRModal();
                await loadAllData();
            } else {
                showToast(result.message || 'Lỗi duyệt điểm', 'error');
            }
        } catch (error) {
            console.error('Error approving:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Reject score from modal
     */
    async function handleRejectFromModal(idSanPham) {
        const confirm = await Swal.fire({
            title: 'Đánh rớt bài thi?',
            text: 'Bài thi sẽ bị loại khỏi vòng này.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Đánh rớt',
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
                closeIRRModal();
                await loadAllData();
            } else {
                showToast(result.message || 'Lỗi đánh rớt', 'error');
            }
        } catch (error) {
            console.error('Error rejecting:', error);
            showToast('Lỗi hệ thống', 'error');
        }
    }

    /**
     * Invite arbitrator judge from modal
     */
    async function moiTrongTaiFromModal(idSanPham) {
        const select = document.getElementById(`selectTrongTai_${idSanPham}`);
        const idGV = select?.value;
        if (!idGV) {
            showToast('Vui lòng chọn giảng viên trọng tài', 'warning');
            return;
        }

        const gv = state.dsGiangVien.find(g => g.idGV == idGV);

        // Đóng modal phân tích trước để tránh xung đột z-index với SweetAlert
        closeIRRModal();

        const confirmed = await Swal.fire({
            title: 'Mời Trọng tài phúc khảo?',
            html: `<p class="text-sm text-slate-600">Mời <strong>${escapeHtml(gv?.tenGV || 'giảng viên')}</strong> làm trọng tài phúc khảo.<br><span class="text-amber-600">Điểm chốt hiện tại sẽ bị reset.</span></p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: '<i class="fas fa-user-plus mr-1"></i> Xác nhận mời',
            cancelButtonText: 'Hủy'
        });

        if (!confirmed.isConfirmed) {
            // Huỷ — mở lại modal với dữ liệu cũ
            await showIRRDetailModal(idSanPham);
            return;
        }

        try {
            const response = await fetch(`${API.phanCong}?id_sk=${state.idSK}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_3rd_judge',
                    id_san_pham: idSanPham,
                    id_gv: parseInt(idGV),
                    id_vong_thi: state.idVongThi
                })
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(`Đã mời ${escapeHtml(gv?.tenGV || 'trọng tài')} thành công!`, 'success');
                // Tải lại dữ liệu rồi mở lại modal — sẽ hiển thị trạng thái "Đang chờ Trọng tài"
                await loadAllData();
                await showIRRDetailModal(idSanPham);
            } else {
                showToast(result.message || 'Lỗi mời trọng tài', 'error');
                // Mở lại modal khi có lỗi
                await showIRRDetailModal(idSanPham);
            }
        } catch (error) {
            console.error('Error moiTrongTai:', error);
            showToast('Lỗi hệ thống', 'error');
            await showIRRDetailModal(idSanPham);
        }
    }

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
        huyDuyet,
        showIRRDetailModal,
        closeIRRModal,
        handleApproveFromModal,
        handleRejectFromModal,
        moiTrongTaiFromModal
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
