document.addEventListener('DOMContentLoaded', function () {
    const idSk = Number(window.NHOM_THI_ID_SK || 0);
    const tab  = String(window.NHOM_THI_TAB   || 'tat-ca');

    // Load tên sự kiện lên sidebar
    const sidebarNameEl = document.getElementById('sidebarEventName');
    if (sidebarNameEl && idSk > 0) {
        fetch(`/api/su_kien/chi_tiet_su_kien.php?id_sk=${idSk}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => { if (d.status === 'success' && d.data) sidebarNameEl.textContent = d.data.tenSK || ''; })
            .catch(() => {});
    }

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    async function apiFetch(url) {
        const r = await fetch(url, { credentials: 'same-origin' });
        return r.json();
    }
    async function apiPost(url, body) {
        try {
            const r = await fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            return r.json();
        } catch (e) { return { status: 'error', message: 'Lỗi kết nối' }; }
    }
    function showModal(id)  { document.getElementById(id)?.classList.remove('hidden'); document.getElementById(id)?.classList.add('flex'); }
    function closeModal(id) { document.getElementById(id)?.classList.add('hidden');    document.getElementById(id)?.classList.remove('flex'); }

    // ================================================================
    // TAB: TẤT CẢ NHÓM
    // ================================================================
    if (tab === 'tat-ca') {
        const elLoading   = document.getElementById('groupsLoading');
        const elError     = document.getElementById('groupsError');
        const elEmpty     = document.getElementById('groupsEmpty');
        const elGrid      = document.getElementById('groupsGrid');
        const elCount     = document.getElementById('groupCountText');
        const searchEl    = document.getElementById('searchInput');
        const formWrapper = document.getElementById('formTaoNhomWrapper');
        let allGroups = [], userHasGroup = false;

        function showForm() { formWrapper?.classList.remove('hidden'); formWrapper?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); document.getElementById('inputTenNhom')?.focus(); }
        function hideForm() { formWrapper?.classList.add('hidden'); ['inputTenNhom','inputMoTa'].forEach(id => { const el = document.getElementById(id); if(el) el.value=''; }); document.getElementById('inputSoLuong').value='5'; document.getElementById('inputDangTuyen').value='1'; }

        document.getElementById('btnTaoNhom')?.addEventListener('click', showForm);
        document.getElementById('btnTaoNhomEmpty')?.addEventListener('click', showForm);
        document.getElementById('btnDongFormTaoNhom')?.addEventListener('click', hideForm);
        document.getElementById('btnHuyTaoNhom')?.addEventListener('click', hideForm);
        document.getElementById('btnSubmitTaoNhom')?.addEventListener('click', submitTaoNhom);

        function renderCard(g) {
            const soTV  = parseInt(g.so_thanh_vien || 0);
            const toiDa = parseInt(g.soluongtoida  || 5);
            if (parseInt(g.dangtuyen) !== 1) return '';
            const badge  = `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-700">${soTV}/${toiDa} thành viên</span>`;
            const truong = g.ten_truong_nhom ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700"><i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(g.ten_truong_nhom)}</span>` : '';
            const btn    = !userHasGroup ? `<button onclick="xinVaoNhom(${g.idnhom})" class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-white rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition"><i class="fas fa-user-plus"></i> Xin tham gia</button>` : '';
            return `<div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm hover:shadow-soft-md transition">
                <div class="flex items-start justify-between gap-2"><span class="font-bold text-slate-800 text-sm leading-snug">${esc(g.tennhom)}</span>${badge}</div>
                <div class="flex flex-wrap gap-1.5">${truong}</div>
                ${g.mota ? `<p class="text-xs text-slate-500 line-clamp-2">${esc(g.mota)}</p>` : ''}
                ${btn ? `<div>${btn}</div>` : ''}
            </div>`;
        }
        function renderGrid(list) {
            const html = list.map(renderCard).filter(Boolean).join('');
            if (!html) { elGrid.classList.add('hidden'); elEmpty.classList.remove('hidden'); return; }
            elEmpty.classList.add('hidden'); elGrid.innerHTML = html; elGrid.classList.remove('hidden');
        }
        async function load() {
            try {
                const d = await apiFetch(`/api/nhom/getallnhom.php?id_sk=${idSk}`);
                elLoading.classList.add('hidden');
                if (d.status !== 'success') { elError.textContent = d.message; elError.classList.remove('hidden'); return; }
                allGroups = d.data || []; userHasGroup = !!d.user_has_group;
                const publicCount = allGroups.filter(g => parseInt(g.dangtuyen) === 1).length;
                if (elCount) elCount.textContent = `${publicCount} nhóm công khai`;
                renderGrid(allGroups);
                if (userHasGroup) document.getElementById('btnTaoNhom')?.classList.add('hidden');
            } catch (e) { elLoading.classList.add('hidden'); elError.textContent = 'Không thể kết nối máy chủ'; elError.classList.remove('hidden'); }
        }
        let searchTimer;
        searchEl?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const q = searchEl.value.trim().toLowerCase();
                renderGrid(q ? allGroups.filter(g => (g.tennhom||'').toLowerCase().includes(q) || (g.manhom||'').toLowerCase().includes(q)) : allGroups);
            }, 300);
        });
        load();
    }

    // ================================================================
    // TAB: NHÓM CỦA TÔI
    // ================================================================
    if (tab === 'cua-toi') {
        const elLoading = document.getElementById('myGroupLoading');
        const elError   = document.getElementById('myGroupError');
        const elNoGroup = document.getElementById('noGroupState');
        const elContent = document.getElementById('myGroupContent');

        let _activeNhomId = 0;
        let _activeMyRole = 2;

        // ── Render card ──
        function renderGroupCard(nhom) {
            const gvhd     = (nhom.thanh_vien || []).find(tv => parseInt(tv.idvaitronhom) === 3);
            const hasGvhd  = !!gvhd;
            const open     = parseInt(nhom.dangtuyen) === 1;
            const myRole   = parseInt(nhom.my_role || 2);
            const isLeader = myRole === 1;

            const statusBadge = open
                ? `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-700">Công khai</span>`
                : `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700">Riêng tư</span>`;

            const chips = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) !== 3).map(tv =>
                parseInt(tv.idvaitronhom) === 1
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700"><i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(tv.ten)}</span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 text-xs text-slate-600 bg-slate-100 rounded-full">${esc(tv.ten)}</span>`
            ).join('');

            const gvhdRow = hasGvhd
                ? `<div class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600"><i class="fas fa-chalkboard-teacher"></i> GVHD: ${esc(gvhd.ten)}</div>`
                : `<div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg"><i class="fas fa-exclamation-triangle text-amber-500 text-sm"></i><span class="text-xs font-semibold text-amber-700">Nhóm cần có GVHD</span></div>`;

            const border = hasGvhd ? 'border-slate-200' : 'border-purple-200 border-l-4 border-l-purple-400';

            return `
            <div class="bg-white border ${border} rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm">${esc(nhom.tennhom)}</span>
                    ${statusBadge}
                </div>
                <div class="flex flex-wrap gap-1.5">${chips}</div>
                ${gvhdRow}
                ${nhom.mota ? `<p class="text-xs text-slate-500"><span class="font-semibold text-slate-600">Lĩnh vực:</span> ${esc(nhom.mota)}</p>` : ''}
                <div class="flex flex-wrap gap-2 pt-1">
                    <button onclick="openQuanLy(${nhom.idnhom}, ${myRole})"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-slate-600 hover:bg-slate-700 transition">
                       <i class="fas fa-cog"></i> Quản lý
                    </button>
                    ${isLeader ? `<button onclick="openMoiTV(${nhom.idnhom})"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
                       <i class="fas fa-user-plus"></i> Mời
                    </button>` : ''}
                    ${(!hasGvhd && isLeader) ? `<button onclick="openMoiGVHD(${nhom.idnhom})"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-primary hover:opacity-90 transition">
                        <i class="fas fa-chalkboard-teacher"></i> Mời GVHD
                    </button>` : ''}
                    <button onclick="openNopBai(${nhom.idnhom})"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-green-600 hover:bg-green-700 transition">
                        <i class="fas fa-paper-plane"></i> Nộp bài
                    </button>
                </div>
            </div>`;
        }

        async function loadMyGroups() {
            try {
                const d = await apiFetch(`/api/nhom/getmygroup.php?id_sk=${idSk}`);
                elLoading.classList.add('hidden');
                if (d.status !== 'success') { elError.textContent = d.message; elError.classList.remove('hidden'); return; }
                const nhom = d.data;
                if (!nhom) { elNoGroup.classList.remove('hidden'); return; }
                elContent.innerHTML = renderGroupCard(nhom);
                elContent.classList.remove('hidden');
            } catch (e) { elLoading.classList.add('hidden'); elError.textContent = 'Không thể kết nối máy chủ'; elError.classList.remove('hidden'); }
        }

        // ── MODAL: Quản lý nhóm ──
        let _qlTab = 'thanh-vien', _qlData = null;

        document.getElementById('btnCloseQuanLy')?.addEventListener('click', () => closeModal('modalQuanLy'));

        window.openQuanLy = async function(idNhom, myRole) {
            _activeNhomId = idNhom;
            _activeMyRole = myRole;
            _qlTab = 'thanh-vien';
            const qlContent = document.getElementById('qlModalContent');
            const qlLoading = document.getElementById('qlModalLoading');
            qlContent.classList.add('hidden');
            qlLoading.classList.remove('hidden');
            showModal('modalQuanLy');
            try {
                const d = await apiFetch(`/api/nhom/getmygroup.php?id_sk=${idSk}`);
                qlLoading.classList.add('hidden');
                if (d.status !== 'success' || !d.data) { qlContent.innerHTML=`<p class="text-sm text-rose-500">${esc(d.message||'Lỗi')}</p>`; qlContent.classList.remove('hidden'); return; }
                _qlData = d.data;
                const isLeader = parseInt(_qlData.my_role || 2) === 1;
                const qlTitle = document.getElementById('qlModalTitle');
                if (qlTitle) qlTitle.textContent = `Quản lý nhóm: ${_qlData.tennhom || ''}`;

                // Build tabs
                const tabs = [{ slug:'thanh-vien', label:'Thành viên' }];
                if (isLeader) {
                    const soYC = (_qlData.yeu_cau_cho || []).length;
                    tabs.push({ slug:'yeu-cau', label:`Yêu cầu tham gia${soYC > 0 ? ` (${soYC})` : ''}` });
                    tabs.push({ slug:'cai-dat', label:'Cài đặt nhóm' });
                }
                const qlTabBar = document.getElementById('qlTabBar');
                qlTabBar.innerHTML = tabs.map(t => `
                    <button data-slug="${t.slug}"
                        class="ql-tab px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap
                               ${_qlTab===t.slug ? 'border-purple-500 text-purple-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'}">
                        ${t.label}
                    </button>`).join('');
                qlTabBar.querySelectorAll('.ql-tab').forEach(btn => {
                    btn.addEventListener('click', () => {
                        _qlTab = btn.dataset.slug;
                        qlTabBar.querySelectorAll('.ql-tab').forEach(b => {
                            b.className = b.className.replace('border-purple-500 text-purple-600 font-semibold','border-transparent text-slate-500 hover:text-slate-700');
                            if (b.dataset.slug === _qlTab) b.className = b.className.replace('border-transparent text-slate-500 hover:text-slate-700','border-purple-500 text-purple-600 font-semibold');
                        });
                        renderQLTab();
                    });
                });
                renderQLTab();
                qlContent.classList.remove('hidden');
            } catch(e) { qlLoading.classList.add('hidden'); document.getElementById('qlModalContent').innerHTML='<p class="text-sm text-rose-500">Lỗi kết nối</p>'; document.getElementById('qlModalContent').classList.remove('hidden'); }
        };

        function renderQLTab() {
            const el = document.getElementById('qlModalContent');
            if (!_qlData) return;
            if (_qlTab === 'thanh-vien')  el.innerHTML = renderTabTV();
            else if (_qlTab === 'yeu-cau') el.innerHTML = renderTabYC();
            else if (_qlTab === 'cai-dat') { el.innerHTML = renderTabCD(); bindCaiDat(); }
        }

        function renderTabTV() {
            if (!_qlData.thanh_vien?.length) return `<p class="text-sm text-slate-400 text-center py-8">Chưa có thành viên nào</p>`;
            const isLeader = parseInt(_qlData.my_role || 2) === 1;
            const roleLabel = r => r==1 ? `<span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full font-semibold">Trưởng nhóm</span>` : r==3 ? `<span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-semibold">GVHD</span>` : `<span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full">Thành viên</span>`;
            return `<div>${_qlData.thanh_vien.map(tv => `
                <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white text-sm font-bold shrink-0">${esc(tv.ten||'?').charAt(0).toUpperCase()}</div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700 leading-none">${esc(tv.ten)}</p>
                            ${tv.msv_ma ? `<p class="text-xs text-slate-400 mt-0.5">${esc(tv.msv_ma)}</p>` : ''}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        ${roleLabel(tv.idvaitronhom)}
                        ${(isLeader && parseInt(tv.idvaitronhom) !== 1) ? `<button onclick="kickMember(${tv.idtk},'${esc(tv.ten)}')" class="text-xs text-rose-500 hover:text-rose-700 transition" title="Xoá khỏi nhóm"><i class="fas fa-times-circle"></i></button>` : ''}
                    </div>
                </div>`).join('')}</div>`;
        }

        function renderTabYC() {
            const list = _qlData.yeu_cau_cho || [];
            if (!list.length) return `<div class="text-center py-10 text-slate-400"><i class="fas fa-inbox text-3xl mb-3 block"></i><p class="text-sm">Không có yêu cầu nào đang chờ</p></div>`;
            return list.map(yc => `
                <div id="yc-${yc.idYeuCau}" class="flex items-center justify-between p-4 border border-slate-200 rounded-xl mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-400 flex items-center justify-center text-white text-sm font-bold">${esc(yc.ten||'?').charAt(0).toUpperCase()}</div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">${esc(yc.ten)}</p>
                            ${yc.loiNhan ? `<p class="text-xs text-slate-400 italic">"${esc(yc.loiNhan)}"</p>` : ''}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="duyetYeuCau(${yc.idYeuCau},1)" class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-green-600 hover:bg-green-700 transition"><i class="fas fa-check mr-1"></i>Duyệt</button>
                        <button onclick="duyetYeuCau(${yc.idYeuCau},2)" class="px-3 py-1.5 text-xs font-semibold text-slate-600 rounded-lg bg-slate-100 hover:bg-slate-200 transition"><i class="fas fa-times mr-1"></i>Từ chối</button>
                    </div>
                </div>`).join('');
        }

        function renderTabCD() {
            return `<div class="max-w-md space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên nhóm</label>
                    <input type="text" id="caiDatTenNhom" value="${esc(_qlData.tennhom)}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Chế độ nhóm</label>
                    <select id="caiDatDangTuyen" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none">
                        <option value="1" ${parseInt(_qlData.dangtuyen)===1?'selected':''}>🌐 Công khai</option>
                        <option value="0" ${parseInt(_qlData.dangtuyen)===0?'selected':''}>🔒 Riêng tư</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                    <textarea id="caiDatMoTa" rows="4" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none resize-none">${esc(_qlData.mota)}</textarea>
                </div>
                <button id="btnLuuCaiDat" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-bold text-white rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>`;
        }

        function bindCaiDat() {
            document.getElementById('btnLuuCaiDat')?.addEventListener('click', async () => {
                const tenNhom = document.getElementById('caiDatTenNhom')?.value.trim();
                if (!tenNhom) { Swal.fire({ icon:'warning', title:'Vui lòng nhập tên nhóm' }); return; }
                // TODO: gọi API cập nhật thông tin nhóm
                Swal.fire({ icon:'success', title:'Đã lưu thay đổi', timer:1500, showConfirmButton:false });
            });
        }

        window.kickMember = async function(idTk, ten) {
            const c = await Swal.fire({ title:`Xoá ${ten} khỏi nhóm?`, icon:'warning', showCancelButton:true, confirmButtonText:'Xoá', cancelButtonText:'Huỷ', confirmButtonColor:'#ef4444' });
            if (!c.isConfirmed) return;
            const res = await apiPost('/api/nhom/roinhom.php', { id_nhom: _activeNhomId, id_tk_bi_xoa: idTk });
            if (res.status === 'success') Swal.fire({ icon:'success', title:res.message, timer:1200, showConfirmButton:false }).then(() => location.reload());
            else Swal.fire({ icon:'error', title:res.message });
        };

        window.duyetYeuCau = async function(idYC, tt) {
            const res = await apiPost('/api/nhom/duyet_yeu_cau.php', { id_yeu_cau: idYC, trang_thai: tt });
            if (res.status === 'success') {
                document.getElementById(`yc-${idYC}`)?.remove();
                if (_qlData) _qlData.yeu_cau_cho = (_qlData.yeu_cau_cho||[]).filter(y => y.idYeuCau != idYC);
                Swal.fire({ icon:'success', title:res.message, timer:1200, showConfirmButton:false });
            } else Swal.fire({ icon:'error', title:res.message });
        };

        // ── MODAL: Mời sinh viên ──
        window.openMoiTV = function(idNhom) {
            _activeNhomId = idNhom;
            const sv = document.getElementById('searchSVInput');
            if (sv) sv.value = '';
            const el = document.getElementById('svSearchResults');
            el.innerHTML = '<p class="text-xs text-slate-400 p-2"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải...</p>';
            showModal('modalMoiTV');
            searchUser('sv', '', 'svSearchResults');
        };
        document.getElementById('btnCloseMoiTV')?.addEventListener('click', () => closeModal('modalMoiTV'));

        // ── MODAL: Mời GVHD ──
        window.openMoiGVHD = function(idNhom) {
            _activeNhomId = idNhom;
            const gv = document.getElementById('searchGVInput');
            if (gv) gv.value = '';
            const el = document.getElementById('gvSearchResults');
            el.innerHTML = '<p class="text-xs text-slate-400 p-2"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải...</p>';
            showModal('modalMoiGVHD');
            searchUser('gv', '', 'gvSearchResults');
        };
        document.getElementById('btnCloseMoiGVHD')?.addEventListener('click', () => closeModal('modalMoiGVHD'));

        // Search
        let svTimer, gvTimer;
        document.getElementById('searchSVInput')?.addEventListener('input', function() {
            clearTimeout(svTimer);
            svTimer = setTimeout(() => searchUser('sv', this.value.trim(), 'svSearchResults'), 350);
        });
        document.getElementById('searchGVInput')?.addEventListener('input', function() {
            clearTimeout(gvTimer);
            gvTimer = setTimeout(() => searchUser('gv', this.value.trim(), 'gvSearchResults'), 350);
        });

        window.sendInvite = async function(idTK) {
            const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_nhom: _activeNhomId, chieu_moi: 0, id_tk_doi_phuong: idTK });
            closeModal('modalMoiTV'); closeModal('modalMoiGVHD');
            Swal.fire({ icon: res.status==='success' ? 'success' : 'error', title: res.message, timer:1500, showConfirmButton:false });
        };

        // ── MODAL: Nộp bài ──
        let _nopBaiNhomId = 0, _selectedFiles = [], _chuDeList = [];

        window.openNopBai = async function(idNhom) {
            _nopBaiNhomId  = idNhom;
            _selectedFiles = [];
            ['inputTenDeTai','inputMoTaNopBai','inputLinkTL'].forEach(id => { const el = document.getElementById(id); if(el) el.value=''; });
            document.getElementById('fileList').innerHTML = '';
            document.getElementById('inputFiles').value  = '';
            // Load chủ đề
            const selCD = document.getElementById('selectChuDe');
            if (selCD) {
                selCD.innerHTML = '<option value="">-- Đang tải... --</option>';
                try {
                    const d = await apiFetch(`/api/su_kien/get_chude_sk.php?id_sk=${idSk}`);
                    _chuDeList = d.data || [];
                    selCD.innerHTML = '<option value="">-- Chọn chủ đề (tuỳ chọn) --</option>' +
                        _chuDeList.map(c => `<option value="${c.idChuDeSK}">${esc(c.tenChuDe)}</option>`).join('');
                } catch(e) { selCD.innerHTML = '<option value="">-- Không tải được chủ đề --</option>'; }
            }
            showModal('modalNopBai');
        };
        function closeNopBai() { closeModal('modalNopBai'); }
        document.getElementById('btnCloseNopBai')?.addEventListener('click', closeNopBai);
        document.getElementById('btnHuyNopBai')?.addEventListener('click',   closeNopBai);

        // File upload
        const dropZone  = document.getElementById('nopBaiDropZone');
        const fileInput = document.getElementById('inputFiles');
        const fileList  = document.getElementById('fileList');

        document.getElementById('nopBaiSelectFile')?.addEventListener('click', e => { e.stopPropagation(); fileInput?.click(); });
        dropZone?.addEventListener('click', () => fileInput?.click());
        dropZone?.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-purple-400','bg-purple-50'); });
        dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('border-purple-400','bg-purple-50'));
        dropZone?.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('border-purple-400','bg-purple-50'); addFiles(Array.from(e.dataTransfer.files)); });
        fileInput?.addEventListener('change', () => { addFiles(Array.from(fileInput.files)); fileInput.value = ''; });

        function addFiles(files) {
            const MAX = 20*1024*1024;
            files.forEach(f => {
                if (f.size > MAX) { Swal.fire({ icon:'warning', title:`"${f.name}" vượt quá 20MB`, timer:2000, showConfirmButton:false }); return; }
                if (!_selectedFiles.find(x => x.name===f.name && x.size===f.size)) _selectedFiles.push(f);
            });
            renderFileList();
        }
        function renderFileList() {
            if (!fileList) return;
            if (!_selectedFiles.length) { fileList.innerHTML = ''; return; }
            const icons = { pdf:'fa-file-pdf text-red-500', doc:'fa-file-word text-blue-500', docx:'fa-file-word text-blue-500', zip:'fa-file-archive text-yellow-500', rar:'fa-file-archive text-yellow-500' };
            fileList.innerHTML = _selectedFiles.map((f,i) => {
                const ext  = f.name.split('.').pop().toLowerCase();
                const icon = icons[ext] || 'fa-file text-slate-400';
                const size = f.size > 1024*1024 ? (f.size/(1024*1024)).toFixed(1)+'MB' : Math.round(f.size/1024)+'KB';
                return `<div class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fas ${icon} text-base shrink-0"></i>
                        <span class="truncate text-slate-700">${esc(f.name)}</span>
                        <span class="text-xs text-slate-400 shrink-0">${size}</span>
                    </div>
                    <button onclick="removeFile(${i})" class="text-slate-400 hover:text-rose-500 transition shrink-0 ml-2"><i class="fas fa-times text-xs"></i></button>
                </div>`;
            }).join('');
        }
        window.removeFile = function(idx) { _selectedFiles.splice(idx,1); renderFileList(); };

        document.getElementById('btnSubmitNopBai')?.addEventListener('click', async () => {
            const tenDeTai = document.getElementById('inputTenDeTai')?.value.trim();
            if (!tenDeTai) { Swal.fire({ icon:'warning', title:'Vui lòng nhập tên đề tài' }); document.getElementById('inputTenDeTai')?.focus(); return; }
            const idChuDeSK = parseInt(document.getElementById('selectChuDe')?.value || 0);
            const res = await apiPost('/api/nhom/nop_bai.php', {
                id_nhom: _nopBaiNhomId, id_sk: idSk,
                ten_de_tai: tenDeTai,
                mo_ta: document.getElementById('inputMoTaNopBai')?.value.trim(),
                link_tai_lieu: document.getElementById('inputLinkTL')?.value.trim(),
                id_chu_de_sk: idChuDeSK || 0,
            });
            if (res.status === 'success') { closeNopBai(); Swal.fire({ icon:'success', title:res.message, timer:1800, showConfirmButton:false }); }
            else Swal.fire({ icon:'error', title:res.message });
        });

        // Modal tạo nhóm
        document.getElementById('btnTaoNhomCuaToi')?.addEventListener('click', () => showModal('modalTaoNhom'));
        document.getElementById('btnCloseModalTaoNhom')?.addEventListener('click', () => closeModal('modalTaoNhom'));
        document.getElementById('btnHuyTaoNhom')?.addEventListener('click', () => closeModal('modalTaoNhom'));
        document.getElementById('btnSubmitTaoNhom')?.addEventListener('click', submitTaoNhom);

        loadMyGroups();
    }

    // ================================================================
    // TAB: LỜI MỜI
    // ================================================================
    if (tab === 'loi-moi') {
        const elLoading  = document.getElementById('invitesLoading');
        const elError    = document.getElementById('invitesError');
        const elEmpty    = document.getElementById('invitesEmpty');
        const elList     = document.getElementById('invitesList');
        const elHistory  = document.getElementById('historySection');
        const elHistList = document.getElementById('historyList');

        function renderInviteCard(inv) {
            const soTV = parseInt(inv.so_thanh_vien||0), toiDa = parseInt(inv.soluongtoida||5);
            return `<div id="invite-${inv.idYeuCau}" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm">${esc(inv.tennhom)}</span>
                    <span class="text-xs text-slate-400">${soTV}/${toiDa} thành viên</span>
                </div>
                ${inv.ten_truong_nhom ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 w-fit"><i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(inv.ten_truong_nhom)}</span>` : ''}
                ${inv.loiNhan ? `<p class="text-xs text-slate-500 italic">"${esc(inv.loiNhan)}"</p>` : ''}
                <div class="flex gap-2">
                    <button onclick="respondInvite(${inv.idYeuCau},1)" class="flex-1 py-1.5 text-xs font-semibold text-white rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 hover:opacity-90 transition"><i class="fas fa-check mr-1"></i>Chấp nhận</button>
                    <button onclick="respondInvite(${inv.idYeuCau},2)" class="flex-1 py-1.5 text-xs font-semibold text-slate-600 rounded-lg bg-slate-100 hover:bg-slate-200 transition"><i class="fas fa-times mr-1"></i>Từ chối</button>
                </div>
            </div>`;
        }

        async function loadInvites() {
            try {
                const [pRes, aRes] = await Promise.all([
                    apiFetch(`/api/nhom/getrequest.php?id_sk=${idSk}`),
                    apiFetch(`/api/nhom/getrequest.php?id_sk=${idSk}&tat_ca=1`),
                ]);
                elLoading.classList.add('hidden');
                const pending = pRes.data || [];
                const history = (aRes.data||[]).filter(i => parseInt(i.trangThai) !== 0);
                if (!pending.length) {
                    elEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-center"><i class="fas fa-envelope-open-text text-4xl mb-4 text-purple-200"></i><p class="font-semibold text-slate-700 mb-1">Không có lời mời</p><p class="text-sm text-slate-400">Bạn chưa có lời mời tham gia nhóm nào.</p></div>`;
                    elEmpty.classList.remove('hidden');
                } else { elList.innerHTML = pending.map(renderInviteCard).join(''); elList.classList.remove('hidden'); }
                if (history.length) {
                    elHistList.innerHTML = history.map(i => {
                        const ok = parseInt(i.trangThai) === 1;
                        return `<div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0"><span class="text-sm text-slate-600">${esc(i.tennhom)}</span><span class="text-xs px-2 py-0.5 rounded-full ${ok?'bg-green-100 text-green-700':'bg-slate-100 text-slate-500'}">${ok?'Đã chấp nhận':'Đã từ chối'}</span></div>`;
                    }).join('');
                    elHistory.classList.remove('hidden');
                }
            } catch (e) { elLoading.classList.add('hidden'); elError.textContent = 'Không thể kết nối máy chủ'; elError.classList.remove('hidden'); }
        }
        loadInvites();
    }

    // ================================================================
    // HÀM DÙNG CHUNG
    // ================================================================

    window.xinVaoNhom = async function(idNhom) {
        const r = await Swal.fire({ title:'Xin tham gia nhóm?', input:'text', inputPlaceholder:'Lời nhắn (tuỳ chọn)...', showCancelButton:true, confirmButtonText:'Gửi yêu cầu', cancelButtonText:'Huỷ', confirmButtonColor:'#7e22ce' });
        if (!r.isConfirmed) return;
        const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_nhom: idNhom, chieu_moi: 1, loi_nhan: r.value||'' });
        Swal.fire({ icon: res.status==='success'?'success':'error', title: res.message, confirmButtonColor:'#7e22ce' });
    };

    window.respondInvite = async function(idYC, tt) {
        const ok = tt === 1;
        const c = await Swal.fire({ title: ok?'Chấp nhận lời mời?':'Từ chối lời mời?', icon: ok?'question':'warning', showCancelButton:true, confirmButtonText: ok?'Chấp nhận':'Từ chối', cancelButtonText:'Huỷ', confirmButtonColor: ok?'#7e22ce':'#adb5bd' });
        if (!c.isConfirmed) return;
        const res = await apiPost('/api/nhom/duyet_yeu_cau.php', { id_yeu_cau: idYC, trang_thai: tt });
        if (res.status === 'success') { document.getElementById(`invite-${idYC}`)?.remove(); Swal.fire({ icon:'success', title:res.message, timer:1500, showConfirmButton:false }); }
        else Swal.fire({ icon:'error', title:res.message });
    };

    async function submitTaoNhom() {
        const tenNhom   = document.getElementById('inputTenNhom')?.value.trim();
        const moTa      = document.getElementById('inputMoTa')?.value.trim();
        const soLuong   = parseInt(document.getElementById('inputSoLuong')?.value || 5);
        const dangTuyen = parseInt(document.getElementById('inputDangTuyen')?.value ?? 1);
        if (!tenNhom) { Swal.fire({ icon:'warning', title:'Vui lòng nhập tên nhóm', confirmButtonColor:'#7e22ce' }); document.getElementById('inputTenNhom')?.focus(); return; }
        const btn = document.getElementById('btnSubmitTaoNhom');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tạo...'; }
        const res = await apiPost('/api/nhom/taonhom.php', { id_sk: idSk, ten_nhom: tenNhom, mo_ta: moTa, so_luong_toi_da: soLuong, dang_tuyen: dangTuyen });
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check mr-1"></i> Tạo nhóm'; }
        if (res.status === 'success') {
            await Swal.fire({ icon:'success', title:'Tạo nhóm thành công!', html:`<p class="text-slate-600">Nhóm <strong>${tenNhom}</strong> đã được tạo.</p>`, confirmButtonColor:'#7e22ce', confirmButtonText:'OK' });
            location.reload();
        } else Swal.fire({ icon:'error', title:'Không thể tạo nhóm', text:res.message, confirmButtonColor:'#7e22ce' });
    }

    async function searchUser(loai, q, resultId) {
        const el = document.getElementById(resultId);
        if (!el) return;
        if (q.length > 0 && q.length < 2) { el.innerHTML = '<p class="text-xs text-slate-400 p-2">Nhập ít nhất 2 ký tự...</p>'; return; }
        el.innerHTML = '<p class="text-xs text-slate-400 p-2"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải...</p>';
        const d = await apiFetch(`/api/nhom/tim_kiem_user.php?loai=${loai}&q=${encodeURIComponent(q)}`);
        const list = d.data || [];
        if (!list.length) { el.innerHTML = '<p class="text-xs text-slate-400 p-2">Không tìm thấy kết quả</p>'; return; }
        el.innerHTML = list.map(u => {
            const name = esc(u.tenSV || u.tenGV || '');
            const sub  = loai === 'sv' ? esc(u.MSV || '') : '';
            return `<button onclick="sendInvite(${u.idTK})"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50 rounded-lg transition">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white text-xs font-bold shrink-0">${name.charAt(0).toUpperCase()}</div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-700 mb-0 truncate">${name}</p>
                    ${sub ? `<p class="text-xs text-slate-400">${sub}</p>` : ''}
                </div>
                <span class="text-xs font-semibold text-white px-2.5 py-0.5 rounded-full bg-gradient-to-tl from-purple-700 to-pink-500 shrink-0">Mời</span>
            </button>`;
        }).join('');
    }
});