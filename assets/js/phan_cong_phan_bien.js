/**
 * phan_cong_phan_bien.js
 * ══════════════════════════════════════════════════════════════
 * Xử lý 2 chức năng chạy song song với scoring.js / scoring_gv.js:
 *
 *  [1] Admin — tab "review-assign":
 *      Quản lý phân công GV phản biện trong tiểu ban.
 *
 *  [2] GV — tab "scoring-gv" (section #pbTieuBanSection):
 *      GV xem bài phản biện tiểu ban + nhập điểm. Section này được
 *      thêm vào cuối tab-scoring-gv.php và chỉ hiện khi GV có bài.
 */

(function () {
    'use strict';

    const BASE = window.APP_BASE_PATH || '';
    const idSk = window.EVENT_DETAIL_ID || 0;
    const TAB  = window.EVENT_DETAIL_TAB || '';
    const API  = BASE + '/api/su_kien/phan_cong_phan_bien.php';

    // ══════════════════════════════════════════════════════════
    // [1] ADMIN MODULE — tab "review-assign"
    // ══════════════════════════════════════════════════════════
    const Admin = {
        data: [],

        init() {
            if (TAB !== 'review-assign') return;
            this.load();
            document.getElementById('btnRefreshReviewAssign')?.addEventListener('click', () => this.load());
            document.getElementById('raBtnCloseModal')?.addEventListener('click', () => this.closeModal());
            document.getElementById('raModalBackdrop')?.addEventListener('click', () => this.closeModal());
        },

        async load() {
            const wrap = document.getElementById('raDanhSachWrap');
            if (!wrap) return;
            wrap.innerHTML = _loading('Đang tải danh sách bài...');
            try {
                const j = await _get(`${API}?action=danh_sach_bai&id_sk=${idSk}`);
                this.data = j.data || [];
                this.renderStats();
                this.renderList();
            } catch (e) {
                wrap.innerHTML = _errBox(e.message);
            }
        },

        renderStats() {
            let chua = 0, dangCham = 0, daNop = 0;
            for (const b of this.data) {
                if (!b.phan_bien?.length) { chua++; continue; }
                if (b.phan_bien.every(g => g.trangThaiCham === 'Đã nộp')) daNop++;
                else if (b.phan_bien.some(g => g.trangThaiCham === 'Đang chấm')) dangCham++;
            }
            _set('raSoTongBai', this.data.length);
            _set('raChuaPhanCong', chua);
            _set('raDangCham', dangCham);
            _set('raDaNop', daNop);
        },

        renderList() {
            const wrap = document.getElementById('raDanhSachWrap');
            if (!this.data.length) {
                wrap.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-3"></i>
                    <p class="text-sm font-medium">Chưa có bài nào trong tiểu ban</p>
                    <p class="text-xs mt-1">Hãy thêm sản phẩm vào tiểu ban trước</p>
                </div>`;
                return;
            }

            // Group theo tiểu ban
            const tbMap = {};
            for (const b of this.data) {
                if (!tbMap[b.idTieuBan]) tbMap[b.idTieuBan] = { info: b, bais: [] };
                tbMap[b.idTieuBan].bais.push(b);
            }

            let html = '';
            for (const { info: tb, bais } of Object.values(tbMap)) {
                const btcBadge = tb.tenBoTieuChi
                    ? `<span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">${_e(tb.tenBoTieuChi)} · ${tb.soTieuChi} TC</span>`
                    : `<span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-600"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa cấu hình bộ tiêu chí</span>`;

                html += `<div class="bg-white border border-slate-200 rounded-2xl shadow-soft-xl overflow-hidden mb-4">
                    <div class="flex flex-wrap items-center gap-3 px-5 py-3 bg-slate-50 border-b border-slate-200">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex-shrink-0">
                            <i class="fas fa-sitemap text-xs"></i>
                        </span>
                        <span class="text-sm font-bold text-slate-700">${_e(tb.tenTieuBan)}</span>
                        ${btcBadge}
                        <div class="flex items-center gap-3 ml-auto text-xs text-slate-500">
                            ${tb.tenVongThi ? `<span><i class="fas fa-flag mr-1"></i>${_e(tb.tenVongThi)}</span>` : ''}
                            ${tb.ngayBaoCao ? `<span><i class="fas fa-calendar mr-1"></i>${tb.ngayBaoCao}</span>` : ''}
                            ${tb.diaDiem    ? `<span><i class="fas fa-map-marker-alt mr-1"></i>${_e(tb.diaDiem)}</span>` : ''}
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        ${bais.map(b => this._rowBai(b)).join('')}
                    </div>
                </div>`;
            }
            wrap.innerHTML = html;

            wrap.addEventListener('click', e => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                if (btn.dataset.action === 'mo-phan-cong')
                    this.openModal(+btn.dataset.sp, btn.dataset.ten);
                if (btn.dataset.action === 'go-phan-cong')
                    this.goPhanCong(+btn.dataset.sp, +btn.dataset.gv, btn.dataset.tengv);
            });
        },

        _rowBai(b) {
            const badges = (b.phan_bien || []).map(g => {
                const cls = { 'Chờ chấm': 'bg-slate-100 text-slate-600', 'Đang chấm': 'bg-amber-100 text-amber-700', 'Đã nộp': 'bg-emerald-100 text-emerald-700' }[g.trangThaiCham] || 'bg-slate-100 text-slate-600';
                return `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg ${cls}">
                    <i class="fas fa-user text-[10px]"></i>${_e(g.tenGV)}
                    <span class="font-bold">·</span>${_e(g.trangThaiCham)}
                    <button data-action="go-phan-cong" data-sp="${b.idSanPham}" data-gv="${g.idGV}" data-tengv="${_e(g.tenGV)}"
                        class="ml-1 opacity-60 hover:opacity-100 transition-opacity" title="Gỡ phân công">
                        <i class="fas fa-times-circle text-xs"></i>
                    </button>
                </span>`;
            }).join('');
            return `<div class="flex flex-wrap items-center gap-3 px-5 py-3.5 hover:bg-slate-50 transition-colors">
                <div class="min-w-0 flex-1">
                    <p class="mb-0.5 text-sm font-semibold text-slate-700 truncate">${_e(b.tenSanPham)}</p>
                    <p class="mb-0 text-xs text-slate-400">
                        ${b.manhom ? `<span class="mr-2"><i class="fas fa-users mr-1"></i>${_e(b.manhom)}</span>` : ''}
                        ${b.tennhom ? `<span>${_e(b.tennhom)}</span>` : ''}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    ${badges || `<span class="text-xs text-slate-400 italic">Chưa phân công</span>`}
                    <button data-action="mo-phan-cong" data-sp="${b.idSanPham}" data-ten="${_e(b.tenSanPham)}"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-plus mr-1"></i>Phân công
                    </button>
                </div>
            </div>`;
        },

        async openModal(idSanPham, tenSanPham) {
            this._currentSP = idSanPham;
            const modal = document.getElementById('raModalPhanCong');
            const body  = document.getElementById('raModalBody');
            document.getElementById('raModalTitle').textContent    = 'Phân công phản biện';
            document.getElementById('raModalSubtitle').textContent = tenSanPham;
            body.innerHTML = _loading('Đang tải danh sách GV...');
            modal.classList.remove('hidden');
            try {
                const j = await _get(`${API}?action=gv_hop_le&id_sk=${idSk}&id_san_pham=${idSanPham}`);
                const gvs = j.data || [];
                if (!gvs.length) {
                    body.innerHTML = `<div class="py-6 text-center text-slate-400 text-sm">
                        <i class="fas fa-user-slash text-2xl mb-2 block"></i>
                        Không có GV hợp lệ (GV hướng dẫn đã bị loại trừ)</div>`;
                    return;
                }
                body.innerHTML = `<div class="space-y-2">` + gvs.map(g => {
                    if (+g.daPhanCong) {
                        const cls = { 'Chờ chấm': 'text-slate-500', 'Đang chấm': 'text-amber-600', 'Đã nộp': 'text-emerald-600' }[g.trangThaiCham] || '';
                        return `<div class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div>
                                <p class="mb-0 text-sm font-semibold text-slate-700">${_e(g.tenGV)}</p>
                                <p class="mb-0 text-xs ${cls}">${_e(g.vaiTro)} · ${_e(g.trangThaiCham || '')}</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg"><i class="fas fa-check mr-1"></i>Đã phân công</span>
                        </div>`;
                    }
                    return `<div class="flex items-center justify-between px-3 py-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors">
                        <div>
                            <p class="mb-0 text-sm font-semibold text-slate-700">${_e(g.tenGV)}</p>
                            <p class="mb-0 text-xs text-slate-400">${_e(g.vaiTro || 'Thành viên')}</p>
                        </div>
                        <button onclick="window._raPhanCong(${idSanPham},${g.idGV})"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white rounded-lg hover:opacity-90 transition-all"
                            style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                            <i class="fas fa-plus mr-1"></i>Phân công
                        </button>
                    </div>`;
                }).join('') + `</div>`;
            } catch (e) {
                body.innerHTML = _errBox(e.message);
            }
        },

        closeModal() {
            document.getElementById('raModalPhanCong')?.classList.add('hidden');
        },

        async goPhanCong(idSanPham, idGV, tenGV) {
            if (!confirm(`Gỡ phân công phản biện của "${tenGV}" khỏi bài này?`)) return;
            try {
                const j = await _post(API, { action: 'go_phan_cong', id_sk: idSk, id_san_pham: idSanPham, id_gv: idGV });
                _toast(j.message, j.status === 'success' ? 'success' : 'error');
                if (j.status === 'success') this.load();
            } catch { _toast('Lỗi kết nối', 'error'); }
        },
    };

    // Expose để onclick gọi được
    window._raPhanCong = async (idSanPham, idGV) => {
        try {
            const j = await _post(API, { action: 'phan_cong', id_sk: idSk, id_san_pham: idSanPham, id_gv: idGV });
            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') { Admin.closeModal(); Admin.load(); }
        } catch { _toast('Lỗi kết nối', 'error'); }
    };

    // ══════════════════════════════════════════════════════════
    // [2] GV MODULE — section trong tab "scoring-gv"
    // ══════════════════════════════════════════════════════════
    const GV = {
        data: [],
        selectedSP: null,

        init() {
            if (TAB !== 'scoring-gv') return;
            this.load();
            document.getElementById('pbBtnLuuNhap')?.addEventListener('click',  () => this.luuNhap());
            document.getElementById('pbBtnNopPhieu')?.addEventListener('click', () => this.nopPhieu());
        },

        async load() {
            try {
                const j = await _get(`${API}?action=phan_cong_cua_toi&id_sk=${idSk}`);
                this.data = j.data || [];
                const section = document.getElementById('pbTieuBanSection');
                if (!section) return;
                // Chỉ hiện section nếu GV có bài phản biện
                if (!this.data.length) { section.classList.add('hidden'); return; }
                section.classList.remove('hidden');
                this.renderStats();
                this.renderList();
            } catch { /* lỗi im lặng — không làm hỏng scoring-gv */ }
        },

        renderStats() {
            const daNop = this.data.filter(b => b.trangThaiCham === 'Đã nộp').length;
            _set('pbStatTong',  this.data.length);
            _set('pbStatDaNop', daNop);
            _set('pbStatChua',  this.data.length - daNop);
        },

        renderList() {
            const wrap = document.getElementById('pbListSanPham');
            if (!wrap) return;
            const tmpl = document.getElementById('pbSanPhamItemTemplate');
            wrap.innerHTML = '';

            for (const b of this.data) {
                const pct = b.soTieuChiTong > 0
                    ? Math.round(b.soTieuChiDaNhap / b.soTieuChiTong * 100) : 0;
                const statusCls = {
                    'Chờ chấm':  'bg-slate-200 text-slate-600',
                    'Đang chấm': 'bg-amber-200 text-amber-700',
                    'Đã nộp':    'bg-emerald-200 text-emerald-700',
                }[b.trangThaiCham] || 'bg-slate-200 text-slate-600';

                const clone = tmpl.content.cloneNode(true);
                const el    = clone.querySelector('.pb-sp-item');
                el.dataset.id = b.idSanPham;
                el.querySelector('.pb-sp-ten').textContent         = b.tenSanPham || '';
                el.querySelector('.pb-sp-tieuban').textContent     = b.tenTieuBan || '';
                el.querySelector('.pb-sp-tiendo-text').textContent = `${b.soTieuChiDaNhap}/${b.soTieuChiTong} tiêu chí`;
                el.querySelector('.pb-sp-tiendo-bar').style.width  = pct + '%';
                el.querySelector('.pb-sp-badge').innerHTML =
                    `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full ${statusCls}">${_e(b.trangThaiCham)}</span>`;

                el.addEventListener('click', () => this.selectBai(b.idSanPham));
                wrap.appendChild(clone);
            }
        },

        async selectBai(idSanPham) {
            // Highlight item
            document.querySelectorAll('.pb-sp-item').forEach(el => {
                el.classList.toggle('border-purple-400', +el.dataset.id === idSanPham);
                el.classList.toggle('bg-purple-50', +el.dataset.id === idSanPham);
            });
            document.getElementById('pbPhieuCham')?.classList.add('hidden');
            document.getElementById('pbPhieuPlaceholder')?.classList.remove('hidden');

            try {
                const j = await _get(`${API}?action=chi_tiet_phieu&id_sk=${idSk}&id_san_pham=${idSanPham}`);
                if (j.status !== 'success') { _toast(j.message, 'error'); return; }
                this.selectedSP = idSanPham;
                this.renderPhieu(j.data);
            } catch (e) { _toast(e.message, 'error'); }
        },

        renderPhieu(phieu) {
            const isDaNop = phieu.trangThaiCham === 'Đã nộp';
            document.getElementById('pbPhieuPlaceholder')?.classList.add('hidden');
            document.getElementById('pbPhieuCham')?.classList.remove('hidden');

            _set('pbTenSanPham', phieu.san_pham?.tenSanPham || '--');
            _set('pbMaNhom',     phieu.san_pham?.manhom || '--');
            _set('pbTenTieuBan', phieu.bo_tieu_chi?.tenVongThi ? `${phieu.bo_tieu_chi.tenVongThi}` : '--');

            const badge = document.getElementById('pbBoTieuChiBadge');
            if (badge) badge.textContent = phieu.bo_tieu_chi?.tenBoTieuChi || '--';

            const statusBadge = document.getElementById('pbChamStatusBadge');
            if (statusBadge) {
                const cls = { 'Chờ chấm': 'bg-slate-200 text-slate-600', 'Đang chấm': 'bg-amber-200 text-amber-700', 'Đã nộp': 'bg-emerald-200 text-emerald-700' }[phieu.trangThaiCham] || '';
                statusBadge.className = `px-2.5 py-1 text-xs font-semibold rounded-full ${cls}`;
                statusBadge.textContent = phieu.trangThaiCham || '';
            }

            // Ẩn/hiện nút
            document.getElementById('pbBtnLuuNhap').style.display  = isDaNop ? 'none' : '';
            document.getElementById('pbBtnNopPhieu').style.display = isDaNop ? 'none' : '';

            if (phieu.loi) {
                document.getElementById('pbTieuChiTbody').innerHTML =
                    `<tr><td colspan="4" class="px-3 py-4 text-sm text-amber-600">${_e(phieu.loi)}</td></tr>`;
                _set('pbTongDiem', '--');
                return;
            }

            const tbody = document.getElementById('pbTieuChiTbody');
            tbody.innerHTML = '';
            let stt = 0;
            for (const tc of phieu.ds_tieu_chi || []) {
                stt++;
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50';
                tr.innerHTML = `
                    <td class="px-3 py-2 text-xs text-slate-500">${stt}</td>
                    <td class="px-3 py-2 text-sm text-slate-700">${_e(tc.noiDungTieuChi)}</td>
                    <td class="px-3 py-2">
                        <div class="flex items-center justify-center gap-1">
                            <input type="number" min="0" max="${tc.diemToiDa}" step="0.5"
                                class="pb-tc-input w-16 px-2 py-1 text-sm text-center border rounded-lg focus:outline-none
                                ${isDaNop ? 'bg-slate-50 border-slate-200 cursor-default' : 'border-slate-300 focus:border-purple-400 focus:ring-1 focus:ring-purple-200'}"
                                data-tc="${tc.idTieuChi}" data-max="${tc.diemToiDa}"
                                value="${tc.diem ?? ''}" ${isDaNop ? 'readonly' : 'oninput="window._pbTinhTong()"'}
                                placeholder="0">
                            <span class="text-xs text-slate-400">/ <span class="font-medium text-slate-600">${tc.diemToiDa}</span></span>
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <input type="text" data-nhanxet="${tc.idTieuChi}"
                            class="w-full px-2 py-1 text-xs border rounded-lg focus:outline-none
                            ${isDaNop ? 'bg-slate-50 border-slate-200 cursor-default' : 'border-slate-200 focus:border-purple-400'}"
                            value="${_e(tc.nhanXet || '')}" ${isDaNop ? 'readonly' : ''}
                            placeholder="${isDaNop ? '' : 'Nhận xét (tuỳ chọn)'}">
                    </td>`;
                tbody.appendChild(tr);
            }
            window._pbTinhTong();
        },

        _collectDiem() {
            const nxMap = {};
            document.querySelectorAll('#pbTieuChiTbody input[data-nhanxet]').forEach(n => nxMap[n.dataset.nhanxet] = n.value.trim());
            return [...document.querySelectorAll('#pbTieuChiTbody .pb-tc-input')].map(inp => ({
                id_tieu_chi: +inp.dataset.tc,
                diem: inp.value !== '' ? parseFloat(inp.value) : null,
                nhan_xet: nxMap[inp.dataset.tc] || '',
            }));
        },

        async luuNhap() {
            if (!this.selectedSP) return;
            try {
                const j = await _post(API, { action: 'luu_diem', id_sk: idSk, id_san_pham: this.selectedSP, diem: this._collectDiem() });
                _toast(j.message, j.status === 'success' ? 'success' : 'error');
                if (j.status === 'success') {
                    const s = document.getElementById('pbLuuStatus');
                    if (s) { s.classList.remove('hidden'); setTimeout(() => s.classList.add('hidden'), 3000); }
                    this.load();
                }
            } catch { _toast('Lỗi kết nối', 'error'); }
        },

        async nopPhieu() {
            if (!this.selectedSP) return;
            const diem = this._collectDiem();
            if (diem.some(d => d.diem === null)) { _toast('Vui lòng nhập điểm cho tất cả tiêu chí trước khi nộp phiếu', 'error'); return; }
            if (!confirm('Sau khi nộp phiếu, bạn không thể chỉnh sửa lại điểm. Bạn chắc chắn muốn nộp?')) return;
            try {
                // Lưu trước
                const jSave = await _post(API, { action: 'luu_diem', id_sk: idSk, id_san_pham: this.selectedSP, diem });
                if (jSave.status !== 'success') { _toast(jSave.message, 'error'); return; }
                // Nộp
                const j = await _post(API, { action: 'nop_phieu', id_sk: idSk, id_san_pham: this.selectedSP });
                _toast(j.message, j.status === 'success' ? 'success' : 'error');
                if (j.status === 'success') { this.selectedSP = null; this.load(); }
            } catch { _toast('Lỗi kết nối', 'error'); }
        },
    };

    // Expose helper tính tổng điểm phản biện
    window._pbTinhTong = () => {
        let tong = 0;
        document.querySelectorAll('#pbTieuChiTbody .pb-tc-input').forEach(inp => { tong += parseFloat(inp.value) || 0; });
        _set('pbTongDiem', tong.toFixed(1));
    };

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════
    function _set(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
    function _e(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function _loading(msg) {
        return `<div class="flex items-center justify-center py-10 text-slate-400">
            <i class="fas fa-spinner fa-spin text-2xl mr-3"></i><span class="text-sm">${msg}</span></div>`;
    }
    function _errBox(msg) {
        return `<div class="px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
            <i class="fas fa-exclamation-triangle mr-2"></i>${_e(msg)}</div>`;
    }
    async function _get(url) {
        const r = await fetch(url, { credentials: 'same-origin' });
        const j = await r.json();
        if (j.status !== 'success') throw new Error(j.message);
        return j;
    }
    async function _post(url, data) {
        const r = await fetch(url, { method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        return r.json();
    }
    function _toast(msg, type = 'info') {
        const colors = { success: 'bg-emerald-500', error: 'bg-rose-500', info: 'bg-slate-700' };
        const t = document.createElement('div');
        t.className = `fixed bottom-6 right-6 z-[9999] px-4 py-3 rounded-xl text-white text-sm font-semibold shadow-lg
            flex items-center gap-2 ${colors[type] || colors.info}`;
        t.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':type==='error'?'fa-exclamation-circle':'fa-info-circle'}"></i> ${_e(msg)}`;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity 0.35s'; setTimeout(()=>t.remove(),400); }, 3000);
    }

    // ── Khởi động ──────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { Admin.init(); GV.init(); });
    } else {
        Admin.init();
        GV.init();
    }

})();