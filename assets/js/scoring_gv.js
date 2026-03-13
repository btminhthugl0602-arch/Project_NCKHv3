/**
 * Scoring GV Module - Nhập điểm dành cho Giảng viên / Giám khảo
 *
 * Chức năng:
 * 1. Hiển thị danh sách bài được phân công trong vòng thi
 * 2. Hiển thị phiếu chấm (tiêu chí + điểm tối đa)
 * 3. Lưu nháp điểm (auto-save / manual save)
 * 4. Nộp phiếu chấm khi hoàn thành
 */

(function () {
    'use strict';

    const BASE_PATH = window.APP_BASE_PATH || '';

    const state = {
        idSK: window.EVENT_DETAIL_ID || 0,
        idVongThi: 0,
        idSanPhamSelected: null,
        dsSanPham: [],
        phieuData: null,    // dữ liệu chi tiết phiếu chấm của SP đang chọn
        autoSaveTimer: null,
        isTrongTai: false,  // true khi GV được phân công vai trò Trọng tài phúc khảo
    };

    const API = {
        vongThi: BASE_PATH + '/api/su_kien/danh_sach_vong_thi.php',
        nhapDiem: BASE_PATH + '/api/cham_diem/nhap_diem.php',
    };

    const el = {};  // DOM cache

    // ─────────────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────────────

    function init() {
        if (window.EVENT_DETAIL_TAB !== 'scoring-gv') return;

        cacheElements();
        bindEvents();
        loadVongThi();
    }

    function cacheElements() {
        el.vongThiSelect = document.getElementById('gvVongThiSelect');
        el.phieuStatus = document.getElementById('gvPhieuStatus');
        el.statTong = document.getElementById('gvStatTongBai');
        el.statDaCham = document.getElementById('gvStatDaCham');
        el.statChuaCham = document.getElementById('gvStatChuaCham');
        el.listSanPham = document.getElementById('gvListSanPham');
        el.listPlaceholder = document.getElementById('gvListSanPhamPlaceholder');
        el.phieuCham = document.getElementById('gvPhieuCham');
        el.phieuPlaceholder = document.getElementById('gvPhieuChamPlaceholder');
        el.tenSanPham = document.getElementById('gvTenSanPham');
        el.maNhom = document.getElementById('gvMaNhom');
        el.boTieuChiBadge = document.getElementById('gvBoTieuChiBadge');
        el.chamStatusBadge = document.getElementById('gvChamStatusBadge');
        el.moTaDiv = document.getElementById('gvTaiLieuSection');
        el.moTaList = document.getElementById('gvTaiLieuList');
        el.btnToggleTaiLieu = document.getElementById('gvBtnToggleTaiLieu');
        el.taiLieuToggleLabel = document.getElementById('gvTaiLieuToggleLabel');
        el.taiLieuChevron = document.getElementById('gvTaiLieuChevron');
        el.tieuChiTbody = document.getElementById('gvTieuChiTableBody');
        el.tongDiem = document.getElementById('gvTongDiem');
        el.luuStatus = document.getElementById('gvLuuStatus');
        el.btnLuuNhap = document.getElementById('gvBtnLuuNhap');
        el.btnNopPhieu = document.getElementById('gvBtnNopPhieu');
        el.tmplSpItem = document.getElementById('gvSanPhamItemTemplate');
        el.tmplTieuChiRow = document.getElementById('gvTieuChiRowTemplate');

        // Phiếu Trọng tài
        el.phieuTrongTai = document.getElementById('gvPhieuTrongTai');
        el.ttTenSanPham = document.getElementById('gvTTTenSanPham');
        el.ttMaNhom = document.getElementById('gvTTMaNhom');
        el.ttChamStatusBadge = document.getElementById('gvTTChamStatusBadge');
        el.ttGKSummary = document.getElementById('gvTTGKSummary');
        el.ttTableHead = document.getElementById('gvTTTableHead');
        el.ttTableBody = document.getElementById('gvTTTableBody');
        el.ttTongDiem = document.getElementById('gvTTTongDiem');
        el.ttTongDiemGKCols = document.getElementById('gvTTTongDiemGKCols');
        el.ttLuuStatus = document.getElementById('gvTTLuuStatus');
        el.ttBtnLuuNhap = document.getElementById('gvTTBtnLuuNhap');
        el.ttBtnNopPhieu = document.getElementById('gvTTBtnNopPhieu');
        el.tmplTieuChiTrongTaiRow = document.getElementById('gvTieuChiTrongTaiRowTemplate');
    }

    function bindEvents() {
        el.vongThiSelect.addEventListener('change', handleVongThiChange);
        el.btnLuuNhap.addEventListener('click', () => luuDiem(false));
        el.btnNopPhieu.addEventListener('click', handleNopPhieu);

        // Trọng tài buttons
        el.ttBtnLuuNhap.addEventListener('click', () => luuDiem(false));
        el.ttBtnNopPhieu.addEventListener('click', handleNopPhieu);
    }

    // ─────────────────────────────────────────────────────────────
    // VÒNG THI
    // ─────────────────────────────────────────────────────────────

    async function loadVongThi() {
        try {
            const res = await fetch(`${API.vongThi}?id_sk=${state.idSK}&quyen=nhap_diem`);
            const json = await res.json();
            if (json.status !== 'success' || !Array.isArray(json.data)) return;

            const vongThis = json.data;
            el.vongThiSelect.innerHTML = '<option value="">-- Chọn vòng thi --</option>';
            vongThis.forEach(vt => {
                const opt = document.createElement('option');
                opt.value = vt.idVongThi;
                opt.textContent = vt.tenVongThi || `Vòng ${vt.idVongThi}`;
                el.vongThiSelect.appendChild(opt);
            });

            // Tự chọn vòng đầu tiên nếu chỉ có 1
            if (vongThis.length === 1) {
                el.vongThiSelect.value = vongThis[0].idVongThi;
                el.vongThiSelect.dispatchEvent(new Event('change'));
            }
        } catch (err) {
            console.error('Lỗi khi tải danh sách vòng thi:', err);
        }
    }

    function handleVongThiChange() {
        const val = parseInt(el.vongThiSelect.value, 10);
        if (!val) {
            resetDanhSach();
            return;
        }
        state.idVongThi = val;
        state.idSanPhamSelected = null;
        loadDanhSachBai();
    }

    // ─────────────────────────────────────────────────────────────
    // DANH SÁCH BÀI
    // ─────────────────────────────────────────────────────────────

    async function loadDanhSachBai() {
        renderListLoading();
        try {
            const url = `${API.nhapDiem}?action=lay_phieu_cham&id_sk=${state.idSK}&id_vong_thi=${state.idVongThi}`;
            const res = await fetch(url);
            const json = await res.json();

            if (json.status !== 'success') {
                renderListError(json.message || 'Không thể tải danh sách bài');
                return;
            }

            const { phancongcham, dsSanPham } = json.data;

            if (!phancongcham) {
                renderListEmpty('Bạn chưa được phân công chấm vòng thi này.');
                updateStats(0, 0);
                updatePhieuStatusBadge(null);
                return;
            }

            state.dsSanPham = dsSanPham || [];
            updatePhieuStatusBadge(phancongcham.trangThaiXacNhan);
            renderDanhSach(dsSanPham, phancongcham);

        } catch (err) {
            console.error('Lỗi khi tải danh sách bài:', err);
            renderListError('Lỗi kết nối. Vui lòng thử lại.');
        }
    }

    function renderDanhSach(dsSanPham, phancongcham) {
        el.listSanPham.innerHTML = '';

        if (!dsSanPham || dsSanPham.length === 0) {
            renderListEmpty('Chưa có bài nào được phân công cho vòng này.');
            updateStats(0, 0);
            return;
        }

        el.listPlaceholder.classList.add('hidden');
        el.listSanPham.classList.remove('hidden');

        let daChot = 0;
        dsSanPham.forEach(sp => {
            if (sp.trangThaiCham === 'Đã xác nhận') daChot++;
            el.listSanPham.appendChild(buildSpItem(sp));
        });

        updateStats(dsSanPham.length, daChot);
    }

    function buildSpItem(sp) {
        const tmpl = el.tmplSpItem.content.cloneNode(true);
        const wrapper = tmpl.querySelector('.gv-sp-item');

        wrapper.dataset.id = sp.idSanPham;
        wrapper.querySelector('.gv-sp-ten-san-pham').textContent = sp.tensanpham || `SP #${sp.idSanPham}`;
        wrapper.querySelector('.gv-sp-ma-nhom').textContent = sp.manhom || '—';

        // Badge trạng thái (dựa trên trangThaiCham per-SP)
        const badge = wrapper.querySelector('.gv-sp-badge');
        renderSpBadge(badge, sp.trangThaiCham, sp.isTrongTai);

        // Thanh tiến độ
        const soTC = sp.soTieuChi || 0;
        const soDa = sp.soTieuChiDaCham || 0;
        const pct = soTC > 0 ? Math.round((soDa / soTC) * 100) : 0;
        wrapper.querySelector('.gv-sp-tien-do-text').textContent = `${soDa} / ${soTC} tiêu chí`;
        wrapper.querySelector('.gv-sp-tien-do-bar').style.width = `${pct}%`;

        wrapper.addEventListener('click', () => selectSanPham(sp.idSanPham, wrapper));
        return tmpl;
    }

    function renderSpBadge(badgeEl, trangThaiCham, isTrongTai) {
        if (isTrongTai) {
            // Badge role TT — hiện bên trái, kế bên là trạng thái chấm
            const roleHtml = '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-700 mr-1"><i class="fas fa-balance-scale mr-1"></i>TT</span>';
            if (trangThaiCham === 'Đã xác nhận') {
                badgeEl.innerHTML = roleHtml + '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700"><i class="fas fa-gavel mr-1"></i>Đã phán quyết</span>';
            } else if (trangThaiCham === 'Đang chấm') {
                badgeEl.innerHTML = roleHtml + '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Đang phúc khảo</span>';
            } else {
                badgeEl.innerHTML = roleHtml + '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-500">Chờ phúc khảo</span>';
            }
            return;
        }
        if (trangThaiCham === 'Đã xác nhận') {
            badgeEl.innerHTML = '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700"><i class="fas fa-lock mr-1"></i>Đã nộp</span>';
        } else if (trangThaiCham === 'Đang chấm') {
            badgeEl.innerHTML = '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Đang chấm</span>';
        } else {
            badgeEl.innerHTML = '<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-500">Chờ chấm</span>';
        }
    }

    function selectSanPham(idSanPham, itemEl) {
        // Remove active state from previous
        document.querySelectorAll('.gv-sp-item').forEach(i => {
            i.classList.remove('border-indigo-400', 'bg-indigo-50', 'shadow-soft-sm');
        });
        if (itemEl) {
            itemEl.classList.add('border-indigo-400', 'bg-indigo-50', 'shadow-soft-sm');
        }

        state.idSanPhamSelected = idSanPham;
        loadPhieuCham(idSanPham);
    }

    // ─────────────────────────────────────────────────────────────
    // PHIẾU CHẤM
    // ─────────────────────────────────────────────────────────────

    async function loadPhieuCham(idSanPham) {
        showPhieuLoading();
        try {
            const url = `${API.nhapDiem}?action=chi_tiet_san_pham`
                + `&id_sk=${state.idSK}`
                + `&id_vong_thi=${state.idVongThi}`
                + `&id_san_pham=${idSanPham}`;
            const res = await fetch(url);
            const json = await res.json();

            if (json.status !== 'success') {
                Swal.fire({ icon: 'error', title: 'Lỗi', text: json.message, timer: 3000, showConfirmButton: false });
                hidePhieu();
                return;
            }

            state.phieuData = json.data;
            renderPhieuCham(json.data);

        } catch (err) {
            console.error('Lỗi khi tải phiếu chấm:', err);
            hidePhieu();
        }
    }

    function renderPhieuCham(data) {
        const { sanPham, phancongcham, dsTieuChi, isTrongTai, trangThaiChamSP } = data;

        // Lưu flag để các hàm khác (lưu, nộp) biết chế độ hiện tại
        state.isTrongTai = !!isTrongTai;

        if (state.isTrongTai) {
            renderPhieuTrongTai(data);
            return;
        }

        // ── GK thường (logic gốc) ──────────────────────────────────

        // Header
        el.tenSanPham.textContent = sanPham?.tensanpham || '--';
        el.maNhom.textContent = sanPham?.manhom || '--';
        el.boTieuChiBadge.innerHTML = `<i class="fas fa-clipboard-list mr-1"></i>${phancongcham?.tenBoTieuChi || 'Bộ tiêu chí'}`;

        renderTaiLieu(data.dsTaiLieu || []);

        // Trạng thái chấm dựa trên per-SP trangThaiChamSP
        const tatCaDaCham = dsTieuChi.length > 0 && dsTieuChi.every(tc => tc.diem !== null);
        updateChamStatusBadge(tatCaDaCham, trangThaiChamSP);

        // Lock dựa trên trạng thái per-SP (độc lập với bài khác)
        const isSubmitted = trangThaiChamSP === 'Đã xác nhận';

        // Bảng tiêu chí
        renderTieuChiTable(dsTieuChi, isSubmitted);
        calcTongDiem();

        // Hiện phiếu
        el.phieuPlaceholder.classList.add('hidden');
        el.phieuCham.classList.remove('hidden');
        el.luuStatus.classList.add('hidden');

        // Cập nhật trạng thái buttons
        el.btnLuuNhap.disabled = isSubmitted;
        el.btnNopPhieu.disabled = isSubmitted;
        if (isSubmitted) {
            el.btnLuuNhap.classList.add('opacity-50', 'cursor-not-allowed');
            el.btnNopPhieu.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            el.btnLuuNhap.classList.remove('opacity-50', 'cursor-not-allowed');
            el.btnNopPhieu.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PHIẾU TRỌNG TÀI (PHÚC KHẢO)
    // ─────────────────────────────────────────────────────────────

    /**
     * Render toàn bộ phiếu phúc khảo cho Trọng tài:
     *  - Tóm tắt điểm tổng từng GK chính
     *  - Bảng ma trận: hàng = tiêu chí, cột = mỗi GK + avg+deviation + input TT
     *  - Hàng có độ lệch cao (>30%) được highlight cam
     */
    function renderPhieuTrongTai(data) {
        const { sanPham, dsTieuChi, bongTranh, maTranCanhBao, trangThaiChamSP } = data;
        const isSubmitted = trangThaiChamSP === 'Đã xác nhận';

        // Header
        el.ttTenSanPham.textContent = sanPham?.tensanpham || '--';
        el.ttMaNhom.textContent = sanPham?.manhom || '--';
        updateTTChamStatusBadge(trangThaiChamSP);

        // Tóm tắt điểm GK chính
        renderTTGKSummary(bongTranh || []);

        // Thead động (cột cho từng GK)
        buildTTTableHeader(bongTranh || []);

        // Tbody: mỗi tiêu chí 1 hàng
        renderTTTableBody(dsTieuChi, bongTranh || [], maTranCanhBao || [], isSubmitted);

        // Tổng điểm TT footer
        calcTTTongDiem();

        // Ẩn phiếu GK thường, hiện phiếu TT
        el.phieuPlaceholder.classList.add('hidden');
        el.phieuCham.classList.add('hidden');
        el.phieuTrongTai.classList.remove('hidden');
        el.ttLuuStatus.classList.add('hidden');

        // Buttons
        el.ttBtnLuuNhap.disabled = isSubmitted;
        el.ttBtnNopPhieu.disabled = isSubmitted;
        if (isSubmitted) {
            el.ttBtnLuuNhap.classList.add('opacity-50', 'cursor-not-allowed');
            el.ttBtnNopPhieu.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            el.ttBtnLuuNhap.classList.remove('opacity-50', 'cursor-not-allowed');
            el.ttBtnNopPhieu.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    /** Hiển thị tóm tắt tổng điểm của từng GK chính */
    function renderTTGKSummary(bongTranh) {
        if (!bongTranh.length) {
            el.ttGKSummary.innerHTML = '<p class="text-sm text-slate-400 italic">Chưa có giám khảo nào chấm bài này.</p>';
            return;
        }
        el.ttGKSummary.innerHTML = bongTranh.map(gk => {
            const tongDiem = (gk.chiTiet || []).reduce((s, c) => s + parseFloat(c.diem || 0), 0);
            return `<div class="flex flex-col items-center px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 min-w-[90px]">
                        <p class="text-lg font-bold text-slate-800">${tongDiem.toFixed(1)}</p>
                        <p class="text-xs text-slate-500 mt-0.5 text-center leading-tight">${escHtml(gk.tenGV || 'GK')}</p>
                    </div>`;
        }).join('');
    }

    /** Xây dựng thead có cột GK động */
    function buildTTTableHeader(bongTranh) {
        const gkCols = bongTranh.map(gk =>
            `<th class="px-3 py-2 text-center text-xs font-bold text-slate-500 uppercase whitespace-nowrap">${escHtml(gk.tenGV || 'GK')}</th>`
        ).join('');

        el.ttTableHead.innerHTML = `
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase">Tiêu chí</th>
                ${gkCols}
                <th class="px-3 py-2 text-center text-xs font-bold text-slate-500 uppercase w-28">TB / Lệch%</th>
                <th class="px-3 py-2 text-center text-xs font-bold text-orange-600 uppercase w-32">Điểm phán quyết</th>
                <th class="px-3 py-2 text-left text-xs font-bold text-slate-500 uppercase w-40">Lý do / Nhận xét</th>
            </tr>`;

        // Footer: tổng cột GK
        const gkFooterCols = bongTranh.map(gk => {
            const tongGK = (gk.chiTiet || []).reduce((s, c) => s + parseFloat(c.diem || 0), 0);
            return `<td class="px-3 py-2 text-center text-sm font-bold text-slate-600">${tongGK.toFixed(1)}</td>`;
        }).join('');
        el.ttTongDiemGKCols.outerHTML = `<td id="gvTTTongDiemGKCols" colspan="${bongTranh.length}">${gkFooterCols ? `<table><tr>${gkFooterCols}</tr></table>` : ''}</td>`;
        // Re-cache sau outerHTML swap
        el.ttTongDiemGKCols = document.getElementById('gvTTTongDiemGKCols');
    }

    /** Render từng hàng tiêu chí của TT */
    function renderTTTableBody(dsTieuChi, bongTranh, maTranCanhBao, isSubmitted) {
        el.ttTableBody.innerHTML = '';

        dsTieuChi.forEach((tc, idx) => {
            const canhBao = maTranCanhBao.find(m => m.idTieuChi == tc.idTieuChi) || {};
            const isHigh = !!canhBao.isHighDeviation;

            const row = document.createElement('tr');
            row.dataset.id = tc.idTieuChi;
            row.dataset.isHighDeviation = isHigh ? '1' : '0';
            row.className = isHigh
                ? 'gv-tt-row border-b border-orange-100 bg-orange-50'
                : 'gv-tt-row border-b border-slate-100';

            // Cột 1: Tiêu chí
            const tdNoiDung = document.createElement('td');
            tdNoiDung.className = 'px-3 py-2 align-top';
            tdNoiDung.innerHTML = `
                <p class="text-sm font-medium text-slate-700">${escHtml(tc.noiDungTieuChi || '--')}</p>
                <p class="text-xs text-slate-400 mt-0.5">Tối đa: <b>${tc.diemToiDa}</b></p>`;
            row.appendChild(tdNoiDung);

            // Cột GK (mỗi GK 1 cột)
            bongTranh.forEach(gk => {
                const chiTiet = (gk.chiTiet || []).find(c => c.idTieuChi == tc.idTieuChi);
                const diem = chiTiet ? parseFloat(chiTiet.diem) : null;
                const td = document.createElement('td');
                td.className = 'px-3 py-2 text-center align-top';
                td.innerHTML = diem !== null
                    ? `<span class="font-semibold text-slate-700">${diem.toFixed(1)}</span>`
                    + (chiTiet?.nhanXet ? `<p class="text-xs text-slate-400 mt-0.5 italic">${escHtml(chiTiet.nhanXet)}</p>` : '')
                    : '<span class="text-slate-300 text-xs">—</span>';
                row.appendChild(td);
            });

            // Cột TB + Lệch%
            const avg = canhBao.avgDiem !== undefined ? parseFloat(canhBao.avgDiem) : null;
            const dev = canhBao.deviationPct !== undefined ? parseFloat(canhBao.deviationPct) : null;
            const tdAvg = document.createElement('td');
            tdAvg.className = 'px-3 py-2 text-center align-top';
            if (avg !== null) {
                const devBadge = dev !== null
                    ? `<span class="px-1.5 py-0.5 text-xs font-semibold rounded-full ${isHigh ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500'}">${dev.toFixed(0)}%</span>`
                    : '';
                tdAvg.innerHTML = `<p class="font-semibold text-slate-700">${avg.toFixed(1)}</p>${devBadge}`;
            } else {
                tdAvg.innerHTML = '<span class="text-slate-300 text-xs">—</span>';
            }
            row.appendChild(tdAvg);

            // Cột input điểm TT
            const tdInput = document.createElement('td');
            tdInput.className = 'px-3 py-2 text-center align-top';
            if (isSubmitted) {
                const diemHienTai = tc.diem !== null ? parseFloat(tc.diem).toFixed(1) : '--';
                tdInput.innerHTML = `<span class="font-bold text-orange-700">${diemHienTai}</span>`;
            } else {
                const inputDiem = document.createElement('input');
                inputDiem.type = 'number';
                inputDiem.min = 0;
                inputDiem.max = tc.diemToiDa;
                inputDiem.step = '0.5';
                inputDiem.className = `gv-tt-input w-20 text-center px-2 py-1 text-sm border rounded-lg focus:outline-none focus:ring-2 ${isHigh ? 'border-orange-300 focus:ring-orange-300' : 'border-slate-300 focus:ring-indigo-300'}`;
                // Pre-fill: dùng điểm TT đã lưu nháp nếu có, fallback về avg
                if (tc.diem !== null) {
                    inputDiem.value = parseFloat(tc.diem).toFixed(1);
                } else if (avg !== null) {
                    inputDiem.value = avg.toFixed(1);
                }
                inputDiem.addEventListener('input', () => calcTTTongDiem());
                tdInput.appendChild(inputDiem);

                const maxLabel = document.createElement('p');
                maxLabel.className = 'text-xs text-slate-400 mt-0.5';
                maxLabel.textContent = `/ ${tc.diemToiDa}`;
                tdInput.appendChild(maxLabel);

                if (isHigh) {
                    const warn = document.createElement('p');
                    warn.className = 'text-xs text-orange-600 mt-0.5 font-semibold';
                    warn.innerHTML = '<i class="fas fa-exclamation-triangle mr-0.5"></i>Lệch cao';
                    tdInput.appendChild(warn);
                }
            }
            row.appendChild(tdInput);

            // Cột lý do / nhận xét TT
            const tdNhanXet = document.createElement('td');
            tdNhanXet.className = 'px-3 py-2 align-top';
            if (isSubmitted) {
                tdNhanXet.innerHTML = tc.nhanXet
                    ? `<p class="text-xs text-slate-600 italic">${escHtml(tc.nhanXet)}</p>`
                    : '<span class="text-slate-300 text-xs">—</span>';
            } else {
                const textarea = document.createElement('textarea');
                textarea.rows = 2;
                textarea.className = `gv-tt-nhan-xet w-full px-2 py-1 text-xs border rounded-lg resize-none focus:outline-none focus:ring-1 ${isHigh ? 'border-orange-300 focus:ring-orange-300 bg-orange-50' : 'border-slate-200 focus:ring-indigo-300'}`;
                textarea.placeholder = isHigh ? 'Lý do bắt buộc (lệch cao)...' : 'Nhận xét (không bắt buộc)...';
                if (tc.nhanXet) textarea.value = tc.nhanXet;
                tdNhanXet.appendChild(textarea);
            }
            row.appendChild(tdNhanXet);

            el.ttTableBody.appendChild(row);
        });
    }

    function calcTTTongDiem() {
        let total = 0;
        el.ttTableBody.querySelectorAll('.gv-tt-input').forEach(inp => {
            const v = parseFloat(inp.value);
            if (!isNaN(v)) total += v;
        });
        el.ttTongDiem.textContent = total.toFixed(2);
    }

    function updateTTChamStatusBadge(trangThaiChamSP) {
        if (trangThaiChamSP === 'Đã xác nhận') {
            el.ttChamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700';
            el.ttChamStatusBadge.innerHTML = '<i class="fas fa-gavel mr-1"></i>Đã phán quyết';
        } else if (trangThaiChamSP === 'Đang chấm') {
            el.ttChamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700';
            el.ttChamStatusBadge.textContent = 'Đang phúc khảo';
        } else {
            el.ttChamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600';
            el.ttChamStatusBadge.textContent = 'Chưa phán quyết';
        }
    }

    /** Khóa toàn bộ inputs TT sau khi phán quyết */
    function lockCurrentTTPhieu() {
        el.ttTableBody.querySelectorAll('.gv-tt-input, .gv-tt-nhan-xet').forEach(inp => {
            inp.disabled = true;
            inp.classList.add('bg-slate-50', 'text-slate-500');
        });
        el.ttBtnLuuNhap.disabled = true;
        el.ttBtnNopPhieu.disabled = true;
        el.ttBtnLuuNhap.classList.add('opacity-50', 'cursor-not-allowed');
        el.ttBtnNopPhieu.classList.add('opacity-50', 'cursor-not-allowed');
        updateTTChamStatusBadge('Đã xác nhận');
    }

    /** Escape HTML để tránh XSS khi chèn data từ server vào innerHTML */
    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderTieuChiTable(dsTieuChi, readOnly) {
        el.tieuChiTbody.innerHTML = '';
        dsTieuChi.forEach((tc, idx) => {
            const tmpl = el.tmplTieuChiRow.content.cloneNode(true);
            const row = tmpl.querySelector('tr');

            row.dataset.id = tc.idTieuChi;
            row.querySelector('.gv-tc-stt').textContent = idx + 1;
            row.querySelector('.gv-tc-noi-dung').textContent = tc.noiDungTieuChi || '--';
            row.querySelector('.gv-tc-diem-toi-da').textContent = tc.diemToiDa;

            const inputDiem = row.querySelector('.gv-tc-input');
            const inputNhanXet = row.querySelector('.gv-tc-nhan-xet');

            inputDiem.max = tc.diemToiDa;
            if (tc.diem !== null) inputDiem.value = tc.diem;
            if (tc.nhanXet) inputNhanXet.value = tc.nhanXet;

            if (readOnly) {
                inputDiem.disabled = true;
                inputNhanXet.disabled = true;
                inputDiem.classList.add('bg-slate-50', 'text-slate-500');
                inputNhanXet.classList.add('bg-slate-50', 'text-slate-500');
            } else {
                inputDiem.addEventListener('input', handleDiemChange);
                inputDiem.addEventListener('change', handleDiemChange);
            }

            el.tieuChiTbody.appendChild(tmpl);
        });
    }

    function handleDiemChange() {
        calcTongDiem();
        scheduleAutoSave();
    }

    function calcTongDiem() {
        let total = 0;
        let valid = true;
        el.tieuChiTbody.querySelectorAll('.gv-tc-input').forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val)) total += val;
            else valid = false;
        });
        el.tongDiem.textContent = valid ? total.toFixed(2) : '--';
    }

    // ─────────────────────────────────────────────────────────────
    // AUTO-SAVE
    // ─────────────────────────────────────────────────────────────

    function scheduleAutoSave() {
        clearTimeout(state.autoSaveTimer);
        state.autoSaveTimer = setTimeout(() => luuDiem(true), 2000);  // 2s debounce
    }

    // ─────────────────────────────────────────────────────────────
    // LƯU ĐIỂM
    // ─────────────────────────────────────────────────────────────

    async function luuDiem(isAutoSave) {
        if (!state.idSanPhamSelected || !state.idVongThi) return;

        const diem = collectDiemFromForm();
        if (diem.length === 0) return;

        const payload = {
            action: 'luu_diem',
            id_sk: state.idSK,
            id_vong_thi: state.idVongThi,
            id_san_pham: state.idSanPhamSelected,
            diem,
        };

        try {
            const res = await fetch(API.nhapDiem, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();

            if (json.status === 'success') {
                if (!isAutoSave) {
                    showLuuStatus('Đã lưu nháp');
                } else {
                    showLuuStatus('Tự động lưu');
                }
                // Cập nhật trạng thái per-SP trong danh sách → 'Đang chấm'
                updateSpItemStatus(state.idSanPhamSelected, 'Đang chấm');
                refreshSpItemProgress(state.idSanPhamSelected, diem.length);
            } else if (!isAutoSave) {
                Swal.fire({ icon: 'error', title: 'Lỗi lưu điểm', text: json.message, timer: 4000, showConfirmButton: false });
            }
        } catch (err) {
            console.error('Lỗi khi lưu điểm:', err);
            if (!isAutoSave) {
                Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Mất kết nối. Vui lòng thử lại.', timer: 3000, showConfirmButton: false });
            }
        }
    }

    function collectDiemFromForm() {
        if (state.isTrongTai) {
            // Thu thập điểm từ bảng phán quyết TT
            const result = [];
            el.ttTableBody.querySelectorAll('tr.gv-tt-row').forEach(row => {
                const idTieuChi = parseInt(row.dataset.id, 10);
                const inputDiem = row.querySelector('.gv-tt-input');
                const inputNX = row.querySelector('.gv-tt-nhan-xet');
                if (!inputDiem) return;  // đã khóa (readonly) — bỏ qua
                const diem = inputDiem.value;
                const nhanXet = inputNX ? inputNX.value : '';
                if (idTieuChi && diem !== '') {
                    result.push({ id_tieu_chi: idTieuChi, diem: parseFloat(diem), nhan_xet: nhanXet });
                }
            });
            return result;
        }

        // GK thường
        const rows = el.tieuChiTbody.querySelectorAll('tr.gv-tieuchi-row');
        const result = [];
        rows.forEach(row => {
            const idTieuChi = parseInt(row.dataset.id, 10);
            const diem = row.querySelector('.gv-tc-input')?.value;
            const nhanXet = row.querySelector('.gv-tc-nhan-xet')?.value || '';
            if (idTieuChi && diem !== '') {
                result.push({ id_tieu_chi: idTieuChi, diem: parseFloat(diem), nhan_xet: nhanXet });
            }
        });
        return result;
    }

    // ─────────────────────────────────────────────────────────────
    // NỘP PHIẾU
    // ─────────────────────────────────────────────────────────────

    async function handleNopPhieu() {
        if (!state.idSanPhamSelected || !state.idVongThi) return;

        // ── Trọng tài: validation lý do bắt buộc cho hàng lệch cao ──
        if (state.isTrongTai) {
            // Kiểm tra đã nhập điểm cho tất cả tiêu chí chưa
            const ttRows = Array.from(el.ttTableBody.querySelectorAll('tr.gv-tt-row'));
            const emptyDiem = ttRows.some(r => {
                const inp = r.querySelector('.gv-tt-input');
                return inp && inp.value === '';
            });
            if (emptyDiem) {
                Swal.fire({ icon: 'warning', title: 'Chưa nhập đủ điểm', text: 'Vui lòng nhập điểm phán quyết cho tất cả tiêu chí.', confirmButtonColor: '#ea580c' });
                return;
            }

            // Hàng lệch cao PHẢI có lý do
            const missingReason = ttRows.filter(r => r.dataset.isHighDeviation === '1').some(r => {
                const nx = r.querySelector('.gv-tt-nhan-xet');
                return nx && nx.value.trim() === '';
            });
            if (missingReason) {
                Swal.fire({ icon: 'warning', title: 'Thiếu lý do', text: 'Các tiêu chí có độ lệch cao (>30%) bắt buộc phải có lý do phán quyết.', confirmButtonColor: '#ea580c' });
                return;
            }

            const confirm = await Swal.fire({
                icon: 'question',
                title: 'Xác nhận phán quyết?',
                html: '<p class="text-sm text-slate-600">Phán quyết này sẽ <strong>thay thế hoàn toàn</strong> điểm trung bình của các giám khảo chính và trở thành <strong>điểm cuối cùng</strong> của bài thi. Hành động này <strong>không thể hoàn tác</strong>.</p>',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-gavel mr-1"></i> Xác nhận phán quyết',
                cancelButtonText: 'Xem lại',
                confirmButtonColor: '#ea580c',
                customClass: { confirmButton: 'font-semibold' },
            });
            if (!confirm.isConfirmed) return;
        } else {
            // ── GK thường: kiểm tra đã nhập đủ điểm ──
            const rows = el.tieuChiTbody.querySelectorAll('tr.gv-tieuchi-row');
            const empty = Array.from(rows).some(r => r.querySelector('.gv-tc-input')?.value === '');
            if (empty) {
                Swal.fire({ icon: 'warning', title: 'Chưa nhập đủ điểm', text: 'Vui lòng nhập điểm cho tất cả tiêu chí trước khi nộp phiếu.' });
                return;
            }

            const confirm = await Swal.fire({
                icon: 'question',
                title: 'Xác nhận nộp phiếu?',
                text: 'Sau khi nộp, bạn sẽ không thể chỉnh sửa điểm sản phẩm này nữa.',
                showCancelButton: true,
                confirmButtonText: 'Nộp phiếu',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#4f46e5',
            });
            if (!confirm.isConfirmed) return;
        }

        // Lưu lần cuối trước khi nộp
        await luuDiem(false);

        const payload = {
            action: 'nop_phieu',
            id_sk: state.idSK,
            id_vong_thi: state.idVongThi,
            id_san_pham: state.idSanPhamSelected,
        };

        try {
            const res = await fetch(API.nhapDiem, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();

            if (json.status === 'success') {
                if (state.isTrongTai) {
                    const diemPQ = json.data?.diemPhanQuyet !== undefined
                        ? ` Điểm phán quyết cuối cùng: <b>${parseFloat(json.data.diemPhanQuyet).toFixed(2)}</b>`
                        : '';
                    Swal.fire({ icon: 'success', title: 'Phán quyết đã xác nhận!', html: `${json.message}${diemPQ}`, timer: 3500, showConfirmButton: false });
                    updateSpItemStatus(state.idSanPhamSelected, 'Đã xác nhận');
                    lockCurrentTTPhieu();
                } else {
                    Swal.fire({ icon: 'success', title: 'Nộp phiếu thành công!', text: json.message, timer: 2500, showConfirmButton: false });
                    updateSpItemStatus(state.idSanPhamSelected, 'Đã xác nhận');
                    lockCurrentPhieu();
                }
                updateStats(
                    state.dsSanPham.length,
                    state.dsSanPham.filter(s => s.trangThaiCham === 'Đã xác nhận').length
                );
            } else {
                Swal.fire({ icon: 'error', title: state.isTrongTai ? 'Không thể xác nhận phán quyết' : 'Không thể nộp phiếu', text: json.message });
            }
        } catch (err) {
            console.error('Lỗi khi nộp phiếu:', err);
            Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Mất kết nối. Vui lòng thử lại.' });
        }
    }

    // ─────────────────────────────────────────────────────────────
    // UI HELPERS
    // ─────────────────────────────────────────────────────────────

    function renderTaiLieu(dsTaiLieu) {
        if (!dsTaiLieu || dsTaiLieu.length === 0) {
            el.moTaDiv.classList.add('hidden');
            return;
        }

        el.moTaDiv.classList.remove('hidden');
        el.moTaList.classList.add('hidden');  // collapsed by default
        el.taiLieuChevron.style.transform = '';

        // Build field list
        el.moTaList.innerHTML = dsTaiLieu.map(field => {
            const label = `<p class="text-[11px] font-semibold text-slate-500 mb-0.5">${escHtml(field.tenTruong)}</p>`;
            let content = '';
            const kieu = (field.kieuTruong || '').toUpperCase();

            if (kieu === 'FILE' && field.duongDanFile) {
                const fileName = field.duongDanFile.split('/').pop();
                content = `<a href="${escHtml(field.duongDanFile)}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-medium break-all">
                    <i class="fas fa-file-download"></i>${escHtml(fileName)}
                </a>`;
            } else if (kieu === 'URL' && field.giaTriText) {
                content = `<a href="${escHtml(field.giaTriText)}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 break-all">
                    <i class="fas fa-external-link-alt"></i>${escHtml(field.giaTriText)}
                </a>`;
            } else if (field.giaTriText) {
                content = `<p class="text-xs text-slate-700 whitespace-pre-wrap break-words">${escHtml(field.giaTriText)}</p>`;
            } else {
                content = `<p class="text-xs text-slate-400 italic">Chưa nộp</p>`;
            }

            return `<div class="p-2 bg-slate-50 rounded-lg border border-slate-100">${label}${content}</div>`;
        }).join('');

        // Toggle
        el.btnToggleTaiLieu.onclick = () => {
            const isHidden = el.moTaList.classList.contains('hidden');
            el.moTaList.classList.toggle('hidden', !isHidden);
            el.taiLieuChevron.style.transform = isHidden ? 'rotate(180deg)' : '';
        };
    }

    function updateStats(tong, daCham) {
        el.statTong.textContent = tong;
        el.statDaCham.textContent = daCham;
        el.statChuaCham.textContent = tong - daCham;
    }

    function updatePhieuStatusBadge(trangThai) {
        const badges = {
            'Chờ chấm': { bg: 'bg-slate-100', text: 'text-slate-500', label: 'Chờ chấm' },
            'Đang chấm': { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Đang chấm' },
            'Đã xác nhận': { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Đã chốt điểm' },
        };
        const cfg = trangThai ? (badges[trangThai] || { bg: 'bg-slate-200', text: 'text-slate-600', label: trangThai })
            : { bg: 'bg-slate-200', text: 'text-slate-600', label: 'Chưa phân công' };

        el.phieuStatus.className = `px-3 py-1.5 text-xs font-semibold rounded-full ${cfg.bg} ${cfg.text}`;
        el.phieuStatus.textContent = cfg.label;
    }

    function updateChamStatusBadge(tatCaDaCham, trangThaiChamSP) {
        if (trangThaiChamSP === 'Đã xác nhận') {
            el.chamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700';
            el.chamStatusBadge.textContent = 'Đã chốt điểm';
        } else if (tatCaDaCham) {
            el.chamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700';
            el.chamStatusBadge.textContent = 'Nhập đủ — chưa nộp';
        } else {
            el.chamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600';
            el.chamStatusBadge.textContent = 'Chưa chấm';
        }
    }

    function showLuuStatus(msg) {
        // Hiện trạng thái lưu trên phiếu đang active (GK thường hoặc TT)
        if (state.isTrongTai) {
            el.ttLuuStatus.innerHTML = `<i class="fas fa-check-circle mr-1 text-emerald-500"></i>${msg}`;
            el.ttLuuStatus.classList.remove('hidden');
            setTimeout(() => el.ttLuuStatus.classList.add('hidden'), 3000);
        } else {
            el.luuStatus.innerHTML = `<i class="fas fa-check-circle mr-1 text-emerald-500"></i>${msg}`;
            el.luuStatus.classList.remove('hidden');
            setTimeout(() => el.luuStatus.classList.add('hidden'), 3000);
        }
    }

    function refreshSpItemProgress(idSanPham, soDaDiem) {
        const item = el.listSanPham.querySelector(`.gv-sp-item[data-id="${idSanPham}"]`);
        if (!item) return;
        const sp = state.dsSanPham.find(s => s.idSanPham == idSanPham);
        if (!sp) return;

        sp.soTieuChiDaCham = soDaDiem;
        const soTC = sp.soTieuChi || 1;
        const pct = Math.min(Math.round((soDaDiem / soTC) * 100), 100);
        item.querySelector('.gv-sp-tien-do-text').textContent = `${soDaDiem} / ${soTC} tiêu chí`;
        item.querySelector('.gv-sp-tien-do-bar').style.width = `${pct}%`;
    }

    // Cập nhật badge và state per-SP trong danh sách (không reload)
    function updateSpItemStatus(idSanPham, trangThaiCham) {
        const sp = state.dsSanPham.find(s => s.idSanPham == idSanPham);
        if (sp) sp.trangThaiCham = trangThaiCham;

        const item = el.listSanPham.querySelector(`.gv-sp-item[data-id="${idSanPham}"]`);
        if (!item) return;
        renderSpBadge(item.querySelector('.gv-sp-badge'), trangThaiCham);
    }

    // Khóa form phiếu chấm hiện tại (sau khi nộp)
    function lockCurrentPhieu() {
        el.tieuChiTbody.querySelectorAll('.gv-tc-input, .gv-tc-nhan-xet').forEach(inp => {
            inp.disabled = true;
            inp.classList.add('bg-slate-50', 'text-slate-500');
        });
        el.btnLuuNhap.disabled = true;
        el.btnNopPhieu.disabled = true;
        el.btnLuuNhap.classList.add('opacity-50', 'cursor-not-allowed');
        el.btnNopPhieu.classList.add('opacity-50', 'cursor-not-allowed');
        el.chamStatusBadge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700';
        el.chamStatusBadge.textContent = 'Đã chốt điểm';
    }

    function renderListLoading() {
        el.listSanPham.classList.add('hidden');
        el.listPlaceholder.classList.remove('hidden');
        el.listPlaceholder.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl mb-2 block text-indigo-400"></i><p class="text-sm text-slate-500">Đang tải...</p>';
    }

    function renderListError(msg) {
        el.listSanPham.classList.add('hidden');
        el.listPlaceholder.classList.remove('hidden');
        el.listPlaceholder.innerHTML = `<i class="fas fa-exclamation-circle text-2xl mb-2 block text-red-400"></i><p class="text-sm text-red-500">${msg}</p>`;
    }

    function renderListEmpty(msg) {
        el.listSanPham.classList.add('hidden');
        el.listPlaceholder.classList.remove('hidden');
        el.listPlaceholder.innerHTML = `<i class="fas fa-inbox text-2xl mb-2 block opacity-40"></i><p class="text-sm text-slate-500">${msg}</p>`;
    }

    function showPhieuLoading() {
        el.phieuCham.classList.add('hidden');
        el.phieuTrongTai.classList.add('hidden');
        el.phieuPlaceholder.classList.remove('hidden');
        el.phieuPlaceholder.innerHTML = '<i class="fas fa-spinner fa-spin text-4xl mb-3 opacity-30"></i><p class="text-sm">Đang tải phiếu chấm...</p>';
    }

    function hidePhieu() {
        el.phieuCham.classList.add('hidden');
        el.phieuTrongTai.classList.add('hidden');
        el.phieuPlaceholder.classList.remove('hidden');
        el.phieuPlaceholder.innerHTML = '<i class="fas fa-clipboard-check text-5xl mb-3 opacity-20"></i><p class="text-sm font-medium">Chọn một bài từ danh sách bên trái để bắt đầu chấm điểm</p>';
    }

    function resetDanhSach() {
        state.idVongThi = 0;
        state.dsSanPham = [];
        state.idSanPhamSelected = null;
        el.listSanPham.innerHTML = '';
        el.listSanPham.classList.add('hidden');
        el.listPlaceholder.classList.remove('hidden');
        el.listPlaceholder.innerHTML = '<i class="fas fa-arrow-up text-2xl mb-2 block opacity-40"></i><p class="text-sm">Chọn vòng thi để xem danh sách</p>';
        hidePhieu();
        updateStats(0, 0);
        updatePhieuStatusBadge(null);
    }

    // ─────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────

    window.scoringGVModule = { init, loadDanhSachBai, loadPhieuCham };

    // Auto-init khi DOM sẵn sàng
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();