/**
 * tieu_ban_tab.js
 * ══════════════════════════════════════════════════════════════
 * JS cho 2 tab Tiểu ban & Hội đồng:
 *
 *  [1] Admin — tab "subcommittees": Quản lý tiểu ban báo cáo
 *      CRUD tiểu ban, thêm/xóa GV, xếp sản phẩm vào phòng.
 *
 *  [2] Admin — tab "judges": Phân công Ban Giám Khảo
 *      Bảng tổng hợp GV theo tiểu ban, hỗ trợ thêm/xóa GV nhanh.
 */

(function () {
    'use strict';

    const BASE  = window.APP_BASE_PATH || '';
    const idSk  = window.EVENT_DETAIL_ID || 0;
    const TAB   = window.EVENT_DETAIL_TAB || '';
    const CAN_EDIT = window.TB_CAN_EDIT === true || window.JUDGES_CAN_EDIT === true;

    const API_TB  = BASE + '/api/su_kien/tieu_ban.php';

    // ══════════════════════════════════════════════════════════
    // HELPERS dùng chung
    // ══════════════════════════════════════════════════════════
    function _set(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }
    function _e(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function _loading(msg) {
        return `<div class="flex items-center justify-center py-12 text-slate-400">
            <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
            <span class="text-sm">${_e(msg)}</span>
        </div>`;
    }
    function _empty(msg, sub) {
        return `<div class="flex flex-col items-center justify-center py-16 text-slate-400
                border border-dashed border-slate-200 rounded-2xl bg-slate-50">
            <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm font-medium">${_e(msg)}</p>
            ${sub ? `<p class="text-xs mt-1">${_e(sub)}</p>` : ''}
        </div>`;
    }
    function _errBox(msg) {
        return `<div class="px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600">
            <i class="fas fa-exclamation-triangle mr-2"></i>${_e(msg)}</div>`;
    }
    function _toast(msg, type = 'info') {
        const colors = { success: 'bg-emerald-500', error: 'bg-rose-500', info: 'bg-slate-700' };
        const icons  = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
        const t = document.createElement('div');
        t.className = `fixed bottom-6 right-6 z-[9999] px-4 py-3 rounded-xl text-white text-sm
            font-semibold shadow-lg flex items-center gap-2 ${colors[type] || colors.info}`;
        t.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${_e(msg)}`;
        document.body.appendChild(t);
        setTimeout(() => {
            t.style.opacity = '0'; t.style.transition = 'opacity 0.35s';
            setTimeout(() => t.remove(), 400);
        }, 3000);
    }
    async function _get(url) {
        const r = await fetch(url, { credentials: 'same-origin' });
        const j = await r.json();
        if (j.status !== 'success') throw new Error(j.message || 'Lỗi không xác định');
        return j;
    }
    async function _post(url, data) {
        const r = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        return r.json();
    }
    function _badge(text, color) {
        const map = {
            purple:  'bg-purple-100 text-purple-700',
            blue:    'bg-blue-100 text-blue-700',
            emerald: 'bg-emerald-100 text-emerald-700',
            amber:   'bg-amber-100 text-amber-700',
            rose:    'bg-rose-100 text-rose-600',
            slate:   'bg-slate-100 text-slate-600',
        };
        return `<span class="px-2 py-0.5 text-[10px] font-bold rounded-full ${map[color] || map.slate}">${_e(text)}</span>`;
    }

    // ══════════════════════════════════════════════════════════
    // [1] MODULE SUBCOMMITTEES — tab "subcommittees"
    // ══════════════════════════════════════════════════════════
    const Subcommittees = {
        data: [],        // tieuban_list đã gắn giang_vien + san_pham
        assignedIds: [], // idSanPham đã xếp
        allGV: [],       // ds giảng viên toàn hệ thống
        allSP: [],       // ds SP chưa xếp
        allBTC: [],      // ds bộ tiêu chí
        allVong: [],     // ds vòng thi (lấy từ sukien overview)
        _filterTimer: null,

        init() {
            if (TAB !== 'subcommittees') return;
            this.load();

            document.getElementById('btnRefreshTieuBan')
                ?.addEventListener('click', () => this.load());

            document.getElementById('btnTaoTieuBanMoi')
                ?.addEventListener('click', () => this.openModalTao());

            document.getElementById('tbSearchInput')
                ?.addEventListener('input', () => {
                    clearTimeout(this._filterTimer);
                    this._filterTimer = setTimeout(() => this.renderList(), 200);
                });

            document.getElementById('tbFilterVong')
                ?.addEventListener('change', () => this.renderList());
        },

        async load() {
            const wrap = document.getElementById('subcommitteeList');
            if (!wrap) return;
            wrap.innerHTML = _loading('Đang tải danh sách tiểu ban...');

            try {
                const [jList, jGV, jBTC, jSP] = await Promise.all([
                    _get(`${API_TB}?action=danh_sach&id_sk=${idSk}`),
                    _get(`${API_TB}?action=ds_giang_vien&id_sk=${idSk}`),
                    _get(`${API_TB}?action=ds_bo_tieu_chi&id_sk=${idSk}`),
                    _get(`${API_TB}?action=sp_chua_xep&id_sk=${idSk}`),
                ]);

                const d = jList.data || {};
                this.data        = d.tieuban_list || [];
                this.assignedIds = d.assigned_ids || [];
                this.allGV       = jGV.data  || [];
                this.allBTC      = jBTC.data || [];
                this.allSP       = jSP.data  || [];

                this.renderThongKe();
                this.renderFilterVong();
                this.renderList();
            } catch (e) {
                wrap.innerHTML = _errBox(e.message);
            }
        },

        renderThongKe() {
            const spXep = this.data.reduce((n, tb) => n + (tb.san_pham?.length || 0), 0);
            _set('statSoTieuBan', this.data.length);
            _set('statSoBaiXep',  spXep);
            _set('statSoBaiCho',  this.allSP.length);
        },

        renderFilterVong() {
            const sel = document.getElementById('tbFilterVong');
            if (!sel) return;
            const vongs = [...new Map(
                this.data.filter(tb => tb.idVongThi)
                    .map(tb => [tb.idVongThi, tb.tenVongThi])
            ).entries()];
            sel.innerHTML = '<option value="">Tất cả vòng thi</option>'
                + vongs.map(([id, ten]) =>
                    `<option value="${id}">${_e(ten || `Vòng #${id}`)}</option>`
                ).join('');
        },

        renderList() {
            const wrap   = document.getElementById('subcommitteeList');
            const search = (document.getElementById('tbSearchInput')?.value || '').toLowerCase();
            const vongId = document.getElementById('tbFilterVong')?.value || '';
            const count  = document.getElementById('tbFilterCount');

            if (!wrap) return;

            let filtered = this.data;
            if (vongId) filtered = filtered.filter(tb => String(tb.idVongThi) === vongId);
            if (search)  filtered = filtered.filter(tb =>
                (tb.tenTieuBan || '').toLowerCase().includes(search) ||
                (tb.diaDiem    || '').toLowerCase().includes(search)
            );

            if (count) count.textContent = filtered.length < this.data.length
                ? `Hiển thị ${filtered.length} / ${this.data.length}` : '';

            if (!filtered.length) {
                wrap.innerHTML = this.data.length
                    ? _empty('Không tìm thấy tiểu ban phù hợp', 'Thử thay đổi bộ lọc')
                    : _empty('Chưa có tiểu ban nào', 'Nhấn "Tạo tiểu ban mới" để bắt đầu');
                return;
            }

            wrap.innerHTML = filtered.map(tb => this._cardTieuBan(tb)).join('');

            // Delegate events
            wrap.onclick = e => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const act = btn.dataset.action;
                const id  = +btn.dataset.id;
                if (act === 'edit-tb')    this.openModalEdit(id);
                if (act === 'delete-tb')  this.xoaTieuBan(id, btn.dataset.ten);
                if (act === 'add-gv')     this.openModalGV(id, btn.dataset.ten);
                if (act === 'remove-gv')  this.xoaGV(id, +btn.dataset.gv, btn.dataset.tengv);
                if (act === 'add-sp')     this.openModalSP(id, btn.dataset.ten);
                if (act === 'remove-sp')  this.xoaSP(id, +btn.dataset.sp, btn.dataset.tensp);
            };
        },

        _cardTieuBan(tb) {
            const gvList = (tb.giang_vien || []).map(g =>
                `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg bg-slate-100 text-slate-700">
                    <i class="fas fa-user text-[10px] text-slate-400"></i>${_e(g.tenGV)}
                    <span class="text-slate-400 text-[10px]">(${_e(g.vaiTro || 'TV')})</span>
                    ${CAN_EDIT ? `<button data-action="remove-gv" data-id="${tb.idTieuBan}"
                        data-gv="${g.idGV}" data-tengv="${_e(g.tenGV)}"
                        class="ml-0.5 opacity-50 hover:opacity-100 transition-opacity" title="Xóa khỏi tiểu ban">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>` : ''}
                </span>`
            ).join('');

            const spList = (tb.san_pham || []).map(sp =>
                `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg bg-blue-50 text-blue-700">
                    <i class="fas fa-file-alt text-[10px]"></i>${_e(sp.tenSanPham)}
                    ${sp.manhom ? `<span class="text-blue-400">(${_e(sp.manhom)})</span>` : ''}
                    ${CAN_EDIT ? `<button data-action="remove-sp" data-id="${tb.idTieuBan}"
                        data-sp="${sp.idSanPham}" data-tensp="${_e(sp.tenSanPham)}"
                        class="ml-0.5 opacity-50 hover:opacity-100 transition-opacity" title="Rút khỏi tiểu ban">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>` : ''}
                </span>`
            ).join('');

            return `<div class="bg-white border border-slate-200 rounded-2xl shadow-soft-xl overflow-hidden mb-4">
                <!-- Header card -->
                <div class="flex flex-wrap items-center gap-3 px-5 py-3 bg-slate-50 border-b border-slate-200">
                    <span class="flex items-center justify-center w-8 h-8 rounded-xl
                        bg-gradient-to-tl from-purple-700 to-fuchsia-500 text-white flex-shrink-0">
                        <i class="fas fa-sitemap text-xs"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="mb-0 text-sm font-bold text-slate-700">${_e(tb.tenTieuBan)}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-0.5 text-xs text-slate-400">
                            ${tb.tenVongThi ? `<span><i class="fas fa-flag mr-1"></i>${_e(tb.tenVongThi)}</span>` : ''}
                            ${tb.ngayBaoCao ? `<span><i class="fas fa-calendar mr-1"></i>${tb.ngayBaoCao}</span>` : ''}
                            ${tb.diaDiem    ? `<span><i class="fas fa-map-marker-alt mr-1"></i>${_e(tb.diaDiem)}</span>` : ''}
                            ${tb.tenBoTieuChi
                                ? _badge(tb.tenBoTieuChi, 'purple')
                                : _badge('Chưa cấu hình bộ tiêu chí', 'rose')}
                        </div>
                    </div>
                    ${CAN_EDIT ? `<div class="flex items-center gap-1.5 flex-shrink-0">
                        <button data-action="edit-tb" data-id="${tb.idTieuBan}"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold
                            text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            <i class="fas fa-pen mr-1 text-[10px]"></i>Sửa
                        </button>
                        <button data-action="delete-tb" data-id="${tb.idTieuBan}" data-ten="${_e(tb.tenTieuBan)}"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold
                            text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors">
                            <i class="fas fa-trash mr-1 text-[10px]"></i>Xóa
                        </button>
                    </div>` : ''}
                </div>

                <!-- Body: GV + SP -->
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                    <!-- Giảng viên -->
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                                <i class="fas fa-chalkboard-teacher mr-1.5 text-purple-400"></i>
                                Giảng viên (${(tb.giang_vien || []).length})
                            </p>
                            ${CAN_EDIT ? `<button data-action="add-gv" data-id="${tb.idTieuBan}" data-ten="${_e(tb.tenTieuBan)}"
                                class="inline-flex items-center px-2 py-1 text-[10px] font-semibold text-purple-600
                                bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                                <i class="fas fa-plus mr-1"></i>Thêm GV
                            </button>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            ${gvList || `<span class="text-xs text-slate-400 italic">Chưa có giảng viên</span>`}
                        </div>
                    </div>

                    <!-- Sản phẩm -->
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                                <i class="fas fa-file-alt mr-1.5 text-blue-400"></i>
                                Bài báo cáo (${(tb.san_pham || []).length})
                            </p>
                            ${CAN_EDIT ? `<button data-action="add-sp" data-id="${tb.idTieuBan}" data-ten="${_e(tb.tenTieuBan)}"
                                class="inline-flex items-center px-2 py-1 text-[10px] font-semibold text-blue-600
                                bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fas fa-plus mr-1"></i>Xếp bài
                            </button>` : ''}
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            ${spList || `<span class="text-xs text-slate-400 italic">Chưa có bài báo cáo</span>`}
                        </div>
                    </div>
                </div>
            </div>`;
        },

        // ── Modal Tạo / Sửa tiểu ban ─────────────────────────
        _modalHtml(title, tb = null) {
            const btcOptions = this.allBTC.map(b =>
                `<option value="${b.idBoTieuChi}" ${tb?.idBoTieuChi == b.idBoTieuChi ? 'selected' : ''}>
                    ${_e(b.tenBoTieuChi)}
                </option>`
            ).join('');

            return `<div id="tbModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="tbModalBackdrop"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl z-10">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <h5 class="mb-0 text-sm font-bold text-slate-700">${_e(title)}</h5>
                        <button id="tbModalClose" class="flex items-center justify-center w-8 h-8 rounded-lg
                            text-slate-400 hover:bg-slate-100 transition-colors">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Tên tiểu ban <span class="text-rose-500">*</span>
                            </label>
                            <input id="tbInputTen" type="text" value="${_e(tb?.tenTieuBan || '')}"
                                placeholder="VD: Tiểu ban Công nghệ AI"
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                focus:outline-none focus:border-purple-400 focus:ring-1 focus:ring-purple-200">
                        </div>
                        ${!tb ? `<div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">
                                Vòng thi <span class="text-rose-500">*</span>
                            </label>
                            <select id="tbInputVong" class="w-full px-3 py-2 text-sm border border-slate-200
                                rounded-lg focus:outline-none focus:border-purple-400">
                                <option value="">-- Chọn vòng thi --</option>
                                ${this.allBTC.length > 0
                                    ? `<option value="0">Vòng thi mặc định (sẽ tải sau)</option>`
                                    : ''}
                            </select>
                        </div>` : ''}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Ngày báo cáo</label>
                                <input id="tbInputNgay" type="date" value="${_e(tb?.ngayBaoCao || '')}"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                    focus:outline-none focus:border-purple-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Địa điểm</label>
                                <input id="tbInputDiaDiem" type="text" value="${_e(tb?.diaDiem || '')}"
                                    placeholder="VD: Phòng 401C"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                    focus:outline-none focus:border-purple-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Bộ tiêu chí</label>
                            <select id="tbInputBTC" class="w-full px-3 py-2 text-sm border border-slate-200
                                rounded-lg focus:outline-none focus:border-purple-400">
                                <option value="">-- Không gán (dùng của vòng thi) --</option>
                                ${btcOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Mô tả</label>
                            <textarea id="tbInputMoTa" rows="2" placeholder="Mô tả ngắn (tuỳ chọn)"
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                focus:outline-none focus:border-purple-400 resize-none">${_e(tb?.moTa || '')}</textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-100">
                        <button id="tbModalCancel" class="px-4 py-2 text-sm font-semibold text-slate-600
                            bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            Huỷ
                        </button>
                        <button id="tbModalSave" class="px-4 py-2 text-sm font-bold text-white rounded-lg
                            bg-gradient-to-tl from-purple-700 to-fuchsia-500 hover:opacity-90 transition-all shadow-soft-md">
                            ${tb ? 'Lưu thay đổi' : 'Tạo tiểu ban'}
                        </button>
                    </div>
                </div>
            </div>`;
        },

        _removeModal() {
            document.getElementById('tbModal')?.remove();
            document.getElementById('tbGVModal')?.remove();
            document.getElementById('tbSPModal')?.remove();
        },

        async openModalTao() {
            // Cần ds vòng thi — lấy từ API nếu chưa có
            if (!this._vongThiLoaded) await this._loadVongThi();

            document.body.insertAdjacentHTML('beforeend', this._modalHtml('Tạo tiểu ban mới'));

            // Fill vòng thi vào select
            const selVong = document.getElementById('tbInputVong');
            if (selVong && this.allVong.length) {
                selVong.innerHTML = '<option value="">-- Chọn vòng thi --</option>'
                    + this.allVong.map(v =>
                        `<option value="${v.idVongThi}">${_e(v.tenVongThi)}</option>`
                    ).join('');
            }

            document.getElementById('tbModalClose')?.addEventListener('click',  () => this._removeModal());
            document.getElementById('tbModalCancel')?.addEventListener('click', () => this._removeModal());
            document.getElementById('tbModalBackdrop')?.addEventListener('click', () => this._removeModal());
            document.getElementById('tbModalSave')?.addEventListener('click',   () => this._saveTao());
        },

        async openModalEdit(idTieuBan) {
            const tb = this.data.find(t => t.idTieuBan == idTieuBan);
            if (!tb) return;

            document.body.insertAdjacentHTML('beforeend', this._modalHtml('Sửa tiểu ban', tb));
            document.getElementById('tbModalClose')?.addEventListener('click',  () => this._removeModal());
            document.getElementById('tbModalCancel')?.addEventListener('click', () => this._removeModal());
            document.getElementById('tbModalBackdrop')?.addEventListener('click', () => this._removeModal());
            document.getElementById('tbModalSave')?.addEventListener('click',
                () => this._saveEdit(idTieuBan));
        },

        async _loadVongThi() {
            try {
                const j = await _get(`${BASE}/api/su_kien/danh_sach_vong_thi.php?id_sk=${idSk}`);
                this.allVong = j.data || [];
                this._vongThiLoaded = true;
            } catch { this.allVong = []; }
        },

        async _saveTao() {
            const ten      = document.getElementById('tbInputTen')?.value.trim();
            const idVong   = +(document.getElementById('tbInputVong')?.value || 0);
            const ngay     = document.getElementById('tbInputNgay')?.value || null;
            const diaDiem  = document.getElementById('tbInputDiaDiem')?.value.trim() || null;
            const idBTC    = +(document.getElementById('tbInputBTC')?.value || 0) || null;
            const moTa     = document.getElementById('tbInputMoTa')?.value.trim() || null;

            if (!ten)    { _toast('Vui lòng nhập tên tiểu ban', 'error'); return; }
            if (!idVong) { _toast('Vui lòng chọn vòng thi', 'error'); return; }

            const btn = document.getElementById('tbModalSave');
            if (btn) btn.disabled = true;

            const j = await _post(API_TB, {
                action: 'tao', id_sk: idSk,
                ten_tieu_ban: ten, id_vong_thi: idVong,
                ngay_bao_cao: ngay, dia_diem: diaDiem,
                id_bo_tieu_chi: idBTC, mo_ta: moTa,
            });

            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') { this._removeModal(); this.load(); }
            else if (btn) btn.disabled = false;
        },

        async _saveEdit(idTieuBan) {
            const ten     = document.getElementById('tbInputTen')?.value.trim();
            const ngay    = document.getElementById('tbInputNgay')?.value || null;
            const diaDiem = document.getElementById('tbInputDiaDiem')?.value.trim() || null;
            const idBTC   = +(document.getElementById('tbInputBTC')?.value || 0) || null;
            const moTa    = document.getElementById('tbInputMoTa')?.value.trim();

            if (!ten) { _toast('Vui lòng nhập tên tiểu ban', 'error'); return; }

            const btn = document.getElementById('tbModalSave');
            if (btn) btn.disabled = true;

            const j = await _post(API_TB, {
                action: 'cap_nhat', id_tieu_ban: idTieuBan,
                ten_tieu_ban: ten, ngay_bao_cao: ngay,
                dia_diem: diaDiem, id_bo_tieu_chi: idBTC, mo_ta: moTa,
            });

            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') { this._removeModal(); this.load(); }
            else if (btn) btn.disabled = false;
        },

        async xoaTieuBan(idTieuBan, ten) {
            if (!confirm(`Xóa tiểu ban "${ten}"? Thao tác này sẽ xóa luôn toàn bộ GV và bài đã xếp.`)) return;
            const j = await _post(API_TB, { action: 'xoa', id_tieu_ban: idTieuBan });
            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') this.load();
        },

        // ── Modal Thêm GV ─────────────────────────────────────
        openModalGV(idTieuBan, tenTieuBan) {
            const tb     = this.data.find(t => t.idTieuBan == idTieuBan);
            const daDung = new Set((tb?.giang_vien || []).map(g => g.idGV));
            const gvCon  = this.allGV.filter(g => !daDung.has(g.idGV));

            const html = `<div id="tbGVModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="tbGVBackdrop"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl z-10">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h5 class="mb-0 text-sm font-bold text-slate-700">Thêm giảng viên</h5>
                            <p class="mb-0 text-xs text-slate-400">${_e(tenTieuBan)}</p>
                        </div>
                        <button id="tbGVClose" class="flex items-center justify-center w-8 h-8
                            rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        ${gvCon.length === 0
                            ? `<p class="text-sm text-slate-400 text-center py-4">Tất cả giảng viên đã có trong tiểu ban.</p>`
                            : `<div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Chọn giảng viên</label>
                                <select id="tbGVSelect" class="w-full px-3 py-2 text-sm border border-slate-200
                                    rounded-lg focus:outline-none focus:border-purple-400">
                                    ${gvCon.map(g =>
                                        `<option value="${g.idGV}">${_e(g.tenGV)}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Vai trò</label>
                                <select id="tbGVVaiTro" class="w-full px-3 py-2 text-sm border border-slate-200
                                    rounded-lg focus:outline-none focus:border-purple-400">
                                    <option value="Thành viên">Thành viên</option>
                                    <option value="Trưởng tiểu ban">Trưởng tiểu ban</option>
                                    <option value="Thư ký">Thư ký</option>
                                </select>
                            </div>`
                        }
                    </div>
                    ${gvCon.length > 0 ? `<div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100">
                        <button id="tbGVCancel" class="px-4 py-2 text-sm font-semibold text-slate-600
                            bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Huỷ</button>
                        <button id="tbGVSave" class="px-4 py-2 text-sm font-bold text-white rounded-lg
                            bg-gradient-to-tl from-purple-700 to-fuchsia-500 hover:opacity-90 shadow-soft-md">Thêm</button>
                    </div>` : ''}
                </div>
            </div>`;

            document.body.insertAdjacentHTML('beforeend', html);
            document.getElementById('tbGVClose')?.addEventListener('click',   () => this._removeModal());
            document.getElementById('tbGVCancel')?.addEventListener('click',  () => this._removeModal());
            document.getElementById('tbGVBackdrop')?.addEventListener('click',() => this._removeModal());
            document.getElementById('tbGVSave')?.addEventListener('click', async () => {
                const idGV  = +(document.getElementById('tbGVSelect')?.value || 0);
                const vaiTro = document.getElementById('tbGVVaiTro')?.value || 'Thành viên';
                if (!idGV) return;
                const j = await _post(API_TB, { action: 'them_gv', id_tieu_ban: idTieuBan, id_gv: idGV, vai_tro: vaiTro });
                _toast(j.message, j.status === 'success' ? 'success' : 'error');
                if (j.status === 'success') { this._removeModal(); this.load(); }
            });
        },

        async xoaGV(idTieuBan, idGV, tenGV) {
            if (!confirm(`Xóa "${tenGV}" khỏi tiểu ban?`)) return;
            const j = await _post(API_TB, { action: 'xoa_gv', id_tieu_ban: idTieuBan, id_gv: idGV });
            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') this.load();
        },

        // ── Modal Xếp Sản phẩm ───────────────────────────────
        openModalSP(idTieuBan, tenTieuBan) {
            const html = `<div id="tbSPModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="tbSPBackdrop"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl z-10">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h5 class="mb-0 text-sm font-bold text-slate-700">Xếp bài báo cáo</h5>
                            <p class="mb-0 text-xs text-slate-400">${_e(tenTieuBan)}</p>
                        </div>
                        <button id="tbSPClose" class="flex items-center justify-center w-8 h-8
                            rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        ${this.allSP.length === 0
                            ? `<p class="text-sm text-slate-400 text-center py-4">Không còn bài nào chờ xếp phòng.</p>`
                            : `<p class="text-xs text-slate-500 mb-3">Chọn các bài muốn xếp vào tiểu ban này:</p>
                               <div class="space-y-1.5 max-h-64 overflow-y-auto">
                                   ${this.allSP.map(sp =>
                                       `<label class="flex items-center gap-3 px-3 py-2.5 rounded-xl border
                                           border-slate-200 hover:border-blue-300 hover:bg-blue-50/40 cursor-pointer transition-colors">
                                           <input type="checkbox" value="${sp.idSanPham}"
                                               class="tb-sp-check w-4 h-4 rounded accent-blue-600">
                                           <div class="min-w-0 flex-1">
                                               <p class="mb-0 text-sm font-semibold text-slate-700 truncate">${_e(sp.tenSanPham)}</p>
                                               <p class="mb-0 text-xs text-slate-400">
                                                   ${sp.manhom ? `<span class="mr-2">${_e(sp.manhom)}</span>` : ''}
                                                   ${sp.tennhom ? `<span>${_e(sp.tennhom)}</span>` : ''}
                                               </p>
                                           </div>
                                       </label>`
                                   ).join('')}
                               </div>`
                        }
                    </div>
                    ${this.allSP.length > 0 ? `<div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100">
                        <button id="tbSPCancel" class="px-4 py-2 text-sm font-semibold text-slate-600
                            bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Huỷ</button>
                        <button id="tbSPSave" class="px-4 py-2 text-sm font-bold text-white rounded-lg
                            bg-gradient-to-tl from-blue-600 to-cyan-400 hover:opacity-90 shadow-soft-md">Xếp bài</button>
                    </div>` : ''}
                </div>
            </div>`;

            document.body.insertAdjacentHTML('beforeend', html);
            document.getElementById('tbSPClose')?.addEventListener('click',   () => this._removeModal());
            document.getElementById('tbSPCancel')?.addEventListener('click',  () => this._removeModal());
            document.getElementById('tbSPBackdrop')?.addEventListener('click',() => this._removeModal());
            document.getElementById('tbSPSave')?.addEventListener('click', async () => {
                const ids = [...document.querySelectorAll('.tb-sp-check:checked')].map(c => +c.value);
                if (!ids.length) { _toast('Vui lòng chọn ít nhất 1 bài', 'error'); return; }
                const j = await _post(API_TB, { action: 'them_nhieu_sp', id_tieu_ban: idTieuBan, ids });
                _toast(j.message, j.status === 'success' ? 'success' : 'error');
                if (j.status === 'success') { this._removeModal(); this.load(); }
            });
        },

        async xoaSP(idTieuBan, idSanPham, tenSP) {
            if (!confirm(`Rút "${tenSP}" khỏi tiểu ban?`)) return;
            const j = await _post(API_TB, { action: 'xoa_sp', id_tieu_ban: idTieuBan, id_san_pham: idSanPham });
            _toast(j.message, j.status === 'success' ? 'success' : 'error');
            if (j.status === 'success') this.load();
        },
    };

    // ══════════════════════════════════════════════════════════
    // [2] MODULE JUDGES — tab "judges"
    // ══════════════════════════════════════════════════════════
    const Judges = {
        data: [],

        init() {
            if (TAB !== 'judges') return;
            this.load();
            document.getElementById('btnRefreshJudges')
                ?.addEventListener('click', () => this.load());
        },

        async load() {
            const wrap = document.getElementById('judgesTableWrapper');
            if (!wrap) return;
            wrap.innerHTML = _loading('Đang tải dữ liệu phân công...');

            try {
                const j = await _get(`${API_TB}?action=danh_sach&id_sk=${idSk}`);
                const d = j.data || {};
                this.data = d.tieuban_list || [];
                this.renderTable();
                this.renderStat();
            } catch (e) {
                wrap.innerHTML = _errBox(e.message);
            }
        },

        renderTable() {
            const wrap = document.getElementById('judgesTableWrapper');
            if (!wrap) return;

            if (!this.data.length) {
                wrap.innerHTML = _empty('Chưa có tiểu ban nào', 'Hãy tạo tiểu ban ở tab "Quản lý Tiểu ban" trước');
                return;
            }

            // Gom tất cả GV unique
            const allGVMap = {};
            this.data.forEach(tb => {
                (tb.giang_vien || []).forEach(g => { allGVMap[g.idGV] = g.tenGV; });
            });
            const allGVs = Object.entries(allGVMap).sort((a, b) => a[1].localeCompare(b[1]));

            if (!allGVs.length) {
                wrap.innerHTML = _empty('Chưa có giảng viên nào trong các tiểu ban',
                    'Thêm GV vào tiểu ban ở tab "Quản lý Tiểu ban"');
                return;
            }

            const thGV = allGVs.map(([, ten]) =>
                `<th class="px-3 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wide
                    bg-slate-50 whitespace-nowrap">${_e(ten)}</th>`
            ).join('');

            const rows = this.data.map(tb => {
                const gvSet = new Set((tb.giang_vien || []).map(g => g.idGV));
                const vaiTroMap = {};
                (tb.giang_vien || []).forEach(g => { vaiTroMap[g.idGV] = g.vaiTro; });

                const cells = allGVs.map(([idGV]) => {
                    if (!gvSet.has(+idGV)) return `<td class="px-3 py-3 text-center text-slate-200">—</td>`;
                    const vaiTro = vaiTroMap[+idGV] || 'Thành viên';
                    const isTruong = vaiTro === 'Trưởng tiểu ban';
                    return `<td class="px-3 py-3 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                            ${isTruong ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'}"
                            title="${_e(vaiTro)}">
                            <i class="fas ${isTruong ? 'fa-star' : 'fa-check'} text-[10px]"></i>
                        </span>
                    </td>`;
                }).join('');

                return `<tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="mb-0 text-sm font-semibold text-slate-700">${_e(tb.tenTieuBan)}</p>
                        <p class="mb-0 text-xs text-slate-400">
                            ${tb.tenVongThi ? `<span class="mr-2"><i class="fas fa-flag mr-1"></i>${_e(tb.tenVongThi)}</span>` : ''}
                            ${tb.diaDiem    ? `<span><i class="fas fa-map-marker-alt mr-1"></i>${_e(tb.diaDiem)}</span>` : ''}
                        </p>
                    </td>
                    <td class="px-3 py-3 text-center text-sm font-bold text-slate-600">
                        ${(tb.giang_vien || []).length}
                    </td>
                    ${cells}
                </tr>`;
            }).join('');

            wrap.innerHTML = `<table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wide bg-slate-50">
                            Tiểu ban
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wide bg-slate-50 whitespace-nowrap">
                            Số GV
                        </th>
                        ${thGV}
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <div class="px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center gap-4 text-[10px] text-slate-400">
                <span><i class="fas fa-star text-amber-500 mr-1"></i>Trưởng tiểu ban</span>
                <span><i class="fas fa-check text-emerald-500 mr-1"></i>Thành viên / Thư ký</span>
                <span class="text-slate-300">— Không tham gia</span>
            </div>`;
        },

        renderStat() {
            const summary = document.getElementById('judgesStatSummary');
            const text    = document.getElementById('judgesStatText');
            if (!summary || !text) return;

            const soTB = this.data.length;
            const soGV = new Set(
                this.data.flatMap(tb => (tb.giang_vien || []).map(g => g.idGV))
            ).size;
            const tbThieu = this.data.filter(tb => !(tb.giang_vien || []).length).length;

            text.textContent = `${soTB} tiểu ban · ${soGV} giảng viên tham gia`
                + (tbThieu ? ` · ${tbThieu} tiểu ban chưa có GV` : '');
            summary.classList.remove('hidden');
        },
    };

    // ── Khởi động ─────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            Subcommittees.init();
            Judges.init();
        });
    } else {
        Subcommittees.init();
        Judges.init();
    }

})();
