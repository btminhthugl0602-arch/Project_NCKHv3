document.addEventListener('DOMContentLoaded', function () {
    const idSk = Number(window.NHOM_THI_ID_SK || 0);
    const tab = String(window.NHOM_THI_TAB || 'tat-ca');
    const qlNhomId = Number(window.QUAN_LY_NHOM_ID || 0);
    const qlTab = String(window.QUAN_LY_TAB || 'thanh-vien');

    // ── Load tên sự kiện lên sidebar ──
    const sidebarNameEl = document.getElementById('sidebarEventName');
    if (sidebarNameEl && idSk > 0) {
        fetch(`/api/su_kien/chi_tiet_su_kien.php?id_sk=${idSk}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success' && d.data) {
                    sidebarNameEl.textContent = d.data.tenSK || '';
                }
            }).catch(() => { });
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    async function apiFetch(url) {
        const r = await fetch(url, { credentials: 'same-origin' });
        return r.json();
    }

    async function apiPost(url, body) {
        try {
            const r = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            return r.json();
        } catch (e) {
            return { status: 'error', message: 'Lỗi kết nối' };
        }
    }

    // ================================================================
    // TAB: TẤT CẢ NHÓM
    // ================================================================
    if (tab === 'tat-ca') {
        const elLoading = document.getElementById('groupsLoading');
        const elError = document.getElementById('groupsError');
        const elEmpty = document.getElementById('groupsEmpty');
        const elGrid = document.getElementById('groupsGrid');
        const elCount = document.getElementById('groupCountText');
        const searchEl = document.getElementById('searchInput');
        const formWrapper = document.getElementById('formTaoNhomWrapper');

        let allGroups = [], userHasGroup = false;

        // ── Toggle form inline ──
        function showForm() {
            formWrapper?.classList.remove('hidden');
            formWrapper?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            document.getElementById('inputTenNhom')?.focus();
        }
        function hideForm() {
            formWrapper?.classList.add('hidden');
            document.getElementById('inputTenNhom').value = '';
            document.getElementById('inputMoTa').value = '';
            document.getElementById('inputSoLuong').value = '5';
            document.getElementById('inputDangTuyen').value = '1';
        }

        document.getElementById('btnTaoNhom')?.addEventListener('click', showForm);
        document.getElementById('btnTaoNhomEmpty')?.addEventListener('click', showForm);
        document.getElementById('btnDongFormTaoNhom')?.addEventListener('click', hideForm);
        document.getElementById('btnHuyTaoNhom')?.addEventListener('click', hideForm);
        document.getElementById('btnSubmitTaoNhom')?.addEventListener('click', submitTaoNhom);

        function renderCard(g) {
            const soTV = parseInt(g.so_thanh_vien || 0);
            const toiDa = parseInt(g.soluongtoida || 5);
            const open = parseInt(g.dangtuyen) === 1;
            // Chỉ hiển thị nhóm công khai
            if (!open) return '';
            const badge = `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-700">${soTV}/${toiDa} thành viên</span>`;
            const truong = g.ten_truong_nhom
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                       <i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(g.ten_truong_nhom)}
                   </span>` : '';
            const btn = (!userHasGroup)
                ? `<button onclick="xinVaoNhom(${g.idnhom})"
                       class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 transition">
                       <i class="fas fa-user-plus"></i> Xin tham gia
                   </button>` : '';
            return `<div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm hover:shadow-soft-md transition">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm leading-snug">${esc(g.tennhom)}</span>
                    ${badge}
                </div>
                <div class="flex flex-wrap gap-1.5">${truong}</div>
                ${g.mota ? `<p class="text-xs text-slate-500 line-clamp-2">${esc(g.mota)}</p>` : ''}
                ${btn ? `<div>${btn}</div>` : ''}
            </div>`;
        }

        function renderGrid(list) {
            const html = list.map(renderCard).filter(Boolean).join('');
            if (!html) { elGrid.classList.add('hidden'); elEmpty.classList.remove('hidden'); return; }
            elEmpty.classList.add('hidden');
            elGrid.innerHTML = html;
            elGrid.classList.remove('hidden');
        }

        async function load() {
            try {
                const d = await apiFetch(`/api/nhom/getallnhom.php?id_sk=${idSk}`);
                elLoading.classList.add('hidden');
                if (d.status !== 'success') { elError.textContent = d.message; elError.classList.remove('hidden'); return; }
                allGroups = d.data || [];
                userHasGroup = !!d.user_has_group;
                const publicCount = allGroups.filter(g => parseInt(g.dangtuyen) === 1).length;
                if (elCount) elCount.textContent = `${publicCount} nhóm công khai`;
                renderGrid(allGroups);
                if (userHasGroup) document.getElementById('btnTaoNhom')?.classList.add('hidden');
            } catch (e) {
                elLoading.classList.add('hidden');
                elError.textContent = 'Không thể kết nối máy chủ';
                elError.classList.remove('hidden');
            }
        }

        let searchTimer;
        searchEl?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const q = searchEl.value.trim().toLowerCase();
                renderGrid(q ? allGroups.filter(g =>
                    (g.tennhom || '').toLowerCase().includes(q) || (g.manhom || '').toLowerCase().includes(q)
                ) : allGroups);
            }, 300);
        });

        load();
    }

    // ================================================================
    // TAB: NHÓM CỦA TÔI
    // ================================================================
    if (tab === 'cua-toi') {

        // ── Chế độ Quản lý nhóm (có id_nhom trên URL) ──
        if (qlNhomId > 0) {
            const elLoading = document.getElementById('qlLoading');
            const elError = document.getElementById('qlError');
            const elContent = document.getElementById('qlContent');
            const elTitle = document.getElementById('qlNhomTitle');
            const elBadge = document.getElementById('yeuCauBadge');

            async function loadQL() {
                try {
                    const d = await apiFetch(`/api/nhom/getmygroup.php?id_sk=${idSk}`);
                    elLoading.classList.add('hidden');
                    if (d.status !== 'success' || !d.data) {
                        elError.textContent = d.message || 'Không tìm thấy nhóm';
                        elError.classList.remove('hidden');
                        return;
                    }
                    const nhom = d.data;
                    if (elTitle) elTitle.textContent = `Quản lý nhóm: ${nhom.tennhom || ''}`;

                    // Badge yêu cầu
                    const soYC = (nhom.yeu_cau_cho || []).length;
                    if (soYC > 0 && elBadge) { elBadge.textContent = soYC; elBadge.classList.remove('hidden'); }

                    // Render theo tab
                    if (qlTab === 'thanh-vien') elContent.innerHTML = renderThanhVien(nhom);
                    else if (qlTab === 'yeu-cau') elContent.innerHTML = renderYeuCau(nhom);
                    else if (qlTab === 'cai-dat') elContent.innerHTML = renderCaiDat(nhom);

                    elContent.classList.remove('hidden');
                    bindCaiDat(nhom);
                } catch (e) {
                    elLoading.classList.add('hidden');
                    elError.textContent = 'Không thể kết nối máy chủ';
                    elError.classList.remove('hidden');
                }
            }

            function renderThanhVien(nhom) {
                const svList = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) !== 3);
                const gvhd = (nhom.thanh_vien || []).find(tv => parseInt(tv.idvaitronhom) === 3);

                const roleLabel = r => {
                    if (r == 1) return '<span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full font-semibold">Trưởng nhóm</span>';
                    if (r == 3) return '<span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-semibold">GVHD</span>';
                    return '<span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full">Thành viên</span>';
                };

                const memberRows = (nhom.thanh_vien || []).map(tv => `
                    <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white text-sm font-bold shrink-0">
                                ${esc(tv.ten || '?').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700 mb-0 leading-none">${esc(tv.ten)}</p>
                                ${tv.msv_ma ? `<p class="text-xs text-slate-400 mt-0.5">${esc(tv.msv_ma)}</p>` : ''}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            ${roleLabel(tv.idvaitronhom)}
                            ${nhom.is_chu_nhom && parseInt(tv.idvaitronhom) !== 1
                        ? `<button onclick="kickMember(${tv.idtk}, '${esc(tv.ten)}')"
                                       class="text-xs text-rose-500 hover:text-rose-700 transition"><i class="fas fa-times-circle"></i></button>`
                        : ''}
                        </div>
                    </div>`).join('');

                return `<div class="divide-y-0">${memberRows}</div>
                    ${nhom.is_chu_nhom ? `<div class="mt-4 flex gap-2">
                        <button onclick="document.getElementById('modalMoiTV').classList.remove('hidden')"
                            class="inline-flex items-center gap-1 px-4 py-2 text-xs font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
                            <i class="fas fa-user-plus"></i> Mời thành viên
                        </button>
                        ${!gvhd ? `<button onclick="document.getElementById('modalMoiGVHD').classList.remove('hidden')"
                            class="inline-flex items-center gap-1 px-4 py-2 text-xs font-semibold text-white rounded-lg bg-orange-500 hover:bg-orange-600 transition">
                            <i class="fas fa-chalkboard-teacher"></i> Mời GVHD
                        </button>` : ''}
                    </div>` : ''}`;
            }

            function renderYeuCau(nhom) {
                const list = nhom.yeu_cau_cho || [];
                if (!list.length) return `<div class="text-center py-10 text-slate-400">
                    <i class="fas fa-inbox text-3xl mb-3 block"></i>
                    <p class="text-sm">Không có yêu cầu nào đang chờ</p>
                </div>`;

                return list.map(yc => `
                    <div id="yc-${yc.idYeuCau}" class="flex items-center justify-between p-4 border border-slate-200 rounded-xl mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-400 flex items-center justify-center text-white text-sm font-bold">
                                ${esc(yc.ten || '?').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700 mb-0">${esc(yc.ten)}</p>
                                ${yc.loiNhan ? `<p class="text-xs text-slate-400 italic">"${esc(yc.loiNhan)}"</p>` : ''}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="duyetYeuCau(${yc.idYeuCau}, 1)"
                                class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-green-600 hover:bg-green-700 transition">
                                <i class="fas fa-check mr-1"></i> Duyệt
                            </button>
                            <button onclick="duyetYeuCau(${yc.idYeuCau}, 2)"
                                class="px-3 py-1.5 text-xs font-semibold text-slate-600 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                                <i class="fas fa-times mr-1"></i> Từ chối
                            </button>
                        </div>
                    </div>`).join('');
            }

            function renderCaiDat(nhom) {
                const dangTuyen = parseInt(nhom.dangtuyen) === 1 ? 'Công khai' : 'Riêng tư';
                return `<div class="max-w-md space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tên nhóm</label>
                        <input type="text" id="caiDatTenNhom" value="${esc(nhom.tennhom)}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Loại nhóm</label>
                        <select id="caiDatDangTuyen" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400">
                            <option value="1" ${parseInt(nhom.dangtuyen) === 1 ? 'selected' : ''}>Công khai</option>
                            <option value="0" ${parseInt(nhom.dangtuyen) === 0 ? 'selected' : ''}>Riêng tư</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Mô tả</label>
                        <textarea id="caiDatMoTa" rows="4"
                                  class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-slate-400 resize-none">${esc(nhom.mota)}</textarea>
                    </div>
                    <button id="btnLuuCaiDat"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-bold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 transition">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>`;
            }

            function bindCaiDat(nhom) {
                document.getElementById('btnLuuCaiDat')?.addEventListener('click', async () => {
                    const tenNhom = document.getElementById('caiDatTenNhom')?.value.trim();
                    const dangTuyen = document.getElementById('caiDatDangTuyen')?.value;
                    const moTa = document.getElementById('caiDatMoTa')?.value.trim();
                    if (!tenNhom) { Swal.fire({ icon: 'warning', title: 'Vui lòng nhập tên nhóm' }); return; }
                    const btnLuu = document.getElementById('btnLuuCaiDat');
                    if (btnLuu) { btnLuu.disabled = true; btnLuu.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang lưu...'; }
                    const res = await apiPost('/api/nhom/cap_nhat_nhom.php', {
                        id_sk: idSk, id_nhom: qlNhomId,
                        ten_nhom: tenNhom, mo_ta: moTa, dang_tuyen: parseInt(dangTuyen)
                    });
                    if (btnLuu) { btnLuu.disabled = false; btnLuu.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi'; }
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Đã lưu thay đổi', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: res.message });
                    }
                });
            }

            // Kick member
            window.kickMember = async function (idTk, ten) {
                const c = await Swal.fire({
                    title: `Xoá ${ten} khỏi nhóm?`,
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Xoá', cancelButtonText: 'Huỷ',
                    confirmButtonColor: '#ef4444',
                });
                if (!c.isConfirmed) return;
                const res = await apiPost('/api/nhom/roinhom.php', { id_sk: idSk, id_nhom: qlNhomId, id_tk_bi_xoa: idTk });
                if (res.status === 'success') { Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }).then(() => location.reload()); }
                else Swal.fire({ icon: 'error', title: res.message });
            };

            // Duyệt yêu cầu
            window.duyetYeuCau = async function (idYC, tt) {
                const res = await apiPost('/api/nhom/duyet_yeu_cau.php', { id_sk: idSk, id_yeu_cau: idYC, trang_thai: tt });
                if (res.status === 'success') {
                    document.getElementById(`yc-${idYC}`)?.remove();
                    Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                } else Swal.fire({ icon: 'error', title: res.message });
            };

            // Modal mời TV/GVHD
            document.getElementById('btnCloseMoiTV')?.addEventListener('click', () => document.getElementById('modalMoiTV')?.classList.add('hidden'));
            document.getElementById('btnCloseMoiGVHD')?.addEventListener('click', () => document.getElementById('modalMoiGVHD')?.classList.add('hidden'));

            let svTimer, gvTimer;
            document.getElementById('searchSVInput')?.addEventListener('input', function () {
                clearTimeout(svTimer);
                svTimer = setTimeout(() => searchUser('sv', this.value.trim(), 'svSearchResults'), 350);
            });
            document.getElementById('searchGVInput')?.addEventListener('input', function () {
                clearTimeout(gvTimer);
                gvTimer = setTimeout(() => searchUser('gv', this.value.trim(), 'gvSearchResults'), 350);
            });

            window.sendInvite = async function (idTK) {
                const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_nhom: qlNhomId, chieu_moi: 0, id_tk_doi_phuong: idTK });
                document.getElementById('modalMoiTV')?.classList.add('hidden');
                document.getElementById('modalMoiGVHD')?.classList.add('hidden');
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, timer: 1500, showConfirmButton: false });
            };

            // Nộp bài
            document.getElementById('btnCloseNopBai')?.addEventListener('click', () => document.getElementById('modalNopBai')?.classList.add('hidden'));
            document.getElementById('btnHuyNopBai')?.addEventListener('click', () => document.getElementById('modalNopBai')?.classList.add('hidden'));
            document.getElementById('btnSubmitNopBai')?.addEventListener('click', async () => {
                const tenDeTai = document.getElementById('inputTenDeTai')?.value.trim();
                if (!tenDeTai) { Swal.fire({ icon: 'warning', title: 'Vui lòng nhập tên đề tài' }); return; }
                const res = await apiPost('/api/nhom/nop_bai.php', {
                    id_nhom: qlNhomId, id_sk: idSk,
                    ten_de_tai: tenDeTai,
                    mo_ta: document.getElementById('inputMoTaNopBai')?.value.trim(),
                    link_tai_lieu: document.getElementById('inputLinkTL')?.value.trim(),
                });
                if (res.status === 'success') {
                    document.getElementById('modalNopBai')?.classList.add('hidden');
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                } else Swal.fire({ icon: 'error', title: res.message });
            });

            loadQL();
            return; // không chạy tiếp load danh sách
        }

        // ── Danh sách nhóm của tôi ──
        const elLoading = document.getElementById('myGroupLoading');
        const elError = document.getElementById('myGroupError');
        const elNoGroup = document.getElementById('noGroupState');
        const elContent = document.getElementById('myGroupContent');

        function renderGroupCard(nhom) {
            const gvhd = (nhom.thanh_vien || []).find(tv => parseInt(tv.idvaitronhom) === 3);
            const hasGvhd = !!gvhd;
            const open = parseInt(nhom.dangtuyen) === 1;
            const svList = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) !== 3);

            const statusBadge = open
                ? `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-green-100 text-green-700">Công khai</span>`
                : `<span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-700">Riêng tư</span>`;

            const chips = svList.map(tv => {
                const isLeader = parseInt(tv.idvaitronhom) === 1;
                return isLeader
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                           <i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(tv.ten)}
                       </span>`
                    : `<span class="inline-flex items-center px-2 py-0.5 text-xs text-slate-600 bg-slate-100 rounded-full">${esc(tv.ten)}</span>`;
            }).join('');

            const gvhdRow = hasGvhd
                ? `<div class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600">
                       <i class="fas fa-chalkboard-teacher"></i> GVHD: ${esc(gvhd.ten)}
                   </div>`
                : `<div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                       <i class="fas fa-exclamation-triangle text-amber-500 text-sm"></i>
                       <span class="text-xs font-semibold text-amber-700">Nhóm cần có GVHD</span>
                   </div>`;

            const qlUrl = `/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idnhom}&quan_ly_tab=thanh-vien`;
            const moiUrl = `/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idnhom}&quan_ly_tab=thanh-vien`;

            return `
            <div class="bg-white border ${hasGvhd ? 'border-slate-200' : 'border-orange-300 border-l-4 border-l-orange-400'} rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm">${esc(nhom.tennhom)}</span>
                    ${statusBadge}
                </div>
                <div class="flex flex-wrap gap-1.5">${chips}</div>
                ${gvhdRow}
                ${nhom.mota ? `<p class="text-xs text-slate-500"><span class="font-semibold text-slate-600">Lĩnh vực:</span> ${esc(nhom.mota)}</p>` : ''}
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="${qlUrl}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-slate-600 hover:bg-slate-700 transition">
                       <i class="fas fa-cog"></i> Quản lý
                    </a>
                    <a href="${moiUrl}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
                       <i class="fas fa-user-plus"></i> Mời
                    </a>
                    ${!hasGvhd ? `<a href="/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idnhom}&quan_ly_tab=thanh-vien"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-orange-500 hover:bg-orange-600 transition">
                        <i class="fas fa-chalkboard-teacher"></i> Mời GVHD
                    </a>` : ''}
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

                // API trả về 1 nhóm hoặc null
                const nhom = d.data;
                if (!nhom) { elNoGroup.classList.remove('hidden'); return; }

                elContent.innerHTML = renderGroupCard(nhom);
                elContent.classList.remove('hidden');
            } catch (e) {
                elLoading.classList.add('hidden');
                elError.textContent = 'Không thể kết nối máy chủ';
                elError.classList.remove('hidden');
            }
        }

        // Nộp bài từ trang danh sách
        let _nopBaiNhomId = 0;
        window.openNopBai = (idNhom) => {
            _nopBaiNhomId = idNhom;
            document.getElementById('modalNopBai')?.classList.remove('hidden');
        };
        document.getElementById('btnCloseNopBai')?.addEventListener('click', () => document.getElementById('modalNopBai')?.classList.add('hidden'));
        document.getElementById('btnHuyNopBai')?.addEventListener('click', () => document.getElementById('modalNopBai')?.classList.add('hidden'));
        document.getElementById('btnSubmitNopBai')?.addEventListener('click', async () => {
            const tenDeTai = document.getElementById('inputTenDeTai')?.value.trim();
            if (!tenDeTai) { Swal.fire({ icon: 'warning', title: 'Vui lòng nhập tên đề tài' }); return; }
            const res = await apiPost('/api/nhom/nop_bai.php', {
                id_nhom: _nopBaiNhomId, id_sk: idSk,
                ten_de_tai: tenDeTai,
                mo_ta: document.getElementById('inputMoTaNopBai')?.value.trim(),
                link_tai_lieu: document.getElementById('inputLinkTL')?.value.trim(),
            });
            if (res.status === 'success') {
                document.getElementById('modalNopBai')?.classList.add('hidden');
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            } else Swal.fire({ icon: 'error', title: res.message });
        });

        // Modal tạo nhóm
        const modalTao = document.getElementById('modalTaoNhom');
        document.getElementById('btnTaoNhomCuaToi')?.addEventListener('click', () => modalTao?.classList.remove('hidden'));
        document.getElementById('btnCloseModalTaoNhom')?.addEventListener('click', () => modalTao?.classList.add('hidden'));
        document.getElementById('btnHuyTaoNhom')?.addEventListener('click', () => modalTao?.classList.add('hidden'));
        document.getElementById('btnSubmitTaoNhom')?.addEventListener('click', submitTaoNhom);

        loadMyGroups();
    }

    // ================================================================
    // TAB: LỜI MỜI
    // ================================================================
    if (tab === 'loi-moi') {
        const elLoading = document.getElementById('invitesLoading');
        const elError = document.getElementById('invitesError');
        const elEmpty = document.getElementById('invitesEmpty');
        const elList = document.getElementById('invitesList');
        const elHistory = document.getElementById('historySection');
        const elHistList = document.getElementById('historyList');

        function renderInviteCard(inv) {
            const soTV = parseInt(inv.so_thanh_vien || 0);
            const toiDa = parseInt(inv.soluongtoida || 5);
            return `<div id="invite-${inv.idYeuCau}" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm">${esc(inv.tennhom)}</span>
                    <span class="text-xs text-slate-400">${soTV}/${toiDa} thành viên</span>
                </div>
                ${inv.ten_truong_nhom ? `<div><span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                    <i class="fas fa-crown text-yellow-500" style="font-size:9px"></i> ${esc(inv.ten_truong_nhom)}
                </span></div>` : ''}
                ${inv.loiNhan ? `<p class="text-xs text-slate-500 italic">"${esc(inv.loiNhan)}"</p>` : ''}
                <div class="flex gap-2">
                    <button onclick="respondInvite(${inv.idYeuCau}, 1)"
                        class="flex-1 py-1.5 text-xs font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
                        <i class="fas fa-check mr-1"></i> Chấp nhận
                    </button>
                    <button onclick="respondInvite(${inv.idYeuCau}, 2)"
                        class="flex-1 py-1.5 text-xs font-semibold text-slate-600 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                        <i class="fas fa-times mr-1"></i> Từ chối
                    </button>
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
                const history = (aRes.data || []).filter(i => parseInt(i.trangThai) !== 0);

                if (!pending.length) {
                    elEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg class="w-16 h-16 mb-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="font-semibold text-slate-700 mb-1">Không có lời mời</p>
                        <p class="text-sm text-slate-400">Bạn chưa có lời mời tham gia nhóm nào trong sự kiện này.</p>
                    </div>`;
                    elEmpty.classList.remove('hidden');
                } else {
                    elList.innerHTML = pending.map(renderInviteCard).join('');
                    elList.classList.remove('hidden');
                }

                if (history.length) {
                    elHistList.innerHTML = history.map(i => {
                        const ok = parseInt(i.trangThai) === 1;
                        return `<div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                            <span class="text-sm text-slate-600">${esc(i.tennhom)}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full ${ok ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}">${ok ? 'Đã chấp nhận' : 'Đã từ chối'}</span>
                        </div>`;
                    }).join('');
                    elHistory.classList.remove('hidden');
                }
            } catch (e) {
                elLoading.classList.add('hidden');
                elError.textContent = 'Không thể kết nối máy chủ';
                elError.classList.remove('hidden');
            }
        }
        loadInvites();
    }

    // ================================================================
    // HÀM DÙNG CHUNG
    // ================================================================

    window.xinVaoNhom = async function (idNhom) {
        const r = await Swal.fire({
            title: 'Xin tham gia nhóm?', input: 'text',
            inputPlaceholder: 'Lời nhắn (tuỳ chọn)...',
            showCancelButton: true, confirmButtonText: 'Gửi yêu cầu', cancelButtonText: 'Huỷ',
            confirmButtonColor: '#5e72e4',
        });
        if (!r.isConfirmed) return;
        const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_nhom: idNhom, chieu_moi: 1, loi_nhan: r.value || '' });
        Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, confirmButtonColor: '#5e72e4' });
    };

    window.respondInvite = async function (idYC, tt) {
        const ok = tt === 1;
        const c = await Swal.fire({
            title: ok ? 'Chấp nhận lời mời?' : 'Từ chối lời mời?',
            icon: ok ? 'question' : 'warning', showCancelButton: true,
            confirmButtonText: ok ? 'Chấp nhận' : 'Từ chối', cancelButtonText: 'Huỷ',
            confirmButtonColor: ok ? '#2dce89' : '#adb5bd',
        });
        if (!c.isConfirmed) return;
        const res = await apiPost('/api/nhom/duyet_yeu_cau.php', { id_sk: idSk, id_yeu_cau: idYC, trang_thai: tt });
        if (res.status === 'success') {
            document.getElementById(`invite-${idYC}`)?.remove();
            Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        } else Swal.fire({ icon: 'error', title: res.message });
    };

    async function submitTaoNhom() {
        const tenNhom = document.getElementById('inputTenNhom')?.value.trim();
        const moTa = document.getElementById('inputMoTa')?.value.trim();
        const soLuong = parseInt(document.getElementById('inputSoLuong')?.value || 5);
        const dangTuyen = parseInt(document.getElementById('inputDangTuyen')?.value ?? 1);

        if (!tenNhom) {
            Swal.fire({ icon: 'warning', title: 'Vui lòng nhập tên nhóm', confirmButtonColor: '#5e72e4' });
            document.getElementById('inputTenNhom')?.focus();
            return;
        }

        const btn = document.getElementById('btnSubmitTaoNhom');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang tạo...'; }

        const res = await apiPost('/api/nhom/taonhom.php', {
            id_sk: idSk, ten_nhom: tenNhom, mo_ta: moTa,
            so_luong_toi_da: soLuong, dang_tuyen: dangTuyen
        });

        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Tạo nhóm'; }

        if (res.status === 'success') {
            // Dùng hàm Swal sẵn có để thông báo thành công
            await Swal.fire({
                icon: 'success',
                title: 'Tạo nhóm thành công!',
                html: `<p class="text-slate-600">Nhóm <strong>${tenNhom}</strong> đã được tạo.</p>`,
                confirmButtonColor: '#5e72e4',
                confirmButtonText: 'OK'
            });
            window.location.href = `/event-detail?id_sk=${idSk}&tab=nhom-my`;
        } else {
            Swal.fire({ icon: 'error', title: 'Không thể tạo nhóm', text: res.message, confirmButtonColor: '#5e72e4' });
        }
    }

    async function searchUser(loai, q, resultId) {
        const el = document.getElementById(resultId);
        if (!el) return;
        if (q.length < 2) { el.innerHTML = ''; return; }
        el.innerHTML = '<p class="text-xs text-slate-400 p-2"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tìm...</p>';
        const d = await apiFetch(`/api/nhom/tim_kiem_user.php?loai=${loai}&q=${encodeURIComponent(q)}`);
        const list = d.data || [];
        if (!list.length) { el.innerHTML = '<p class="text-xs text-slate-400 p-2">Không tìm thấy kết quả</p>'; return; }
        el.innerHTML = list.map(u => {
            const name = esc(u.tenSV || u.tenGV || '');
            const sub = loai === 'sv' ? esc(u.MSV || '') : '';
            return `<button onclick="sendInvite(${u.idTK})"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50 rounded-lg transition">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white text-xs font-bold shrink-0">
                    ${name.charAt(0).toUpperCase()}
                </div>
                <div>
                    <p class="font-medium text-slate-700 mb-0">${name}</p>
                    ${sub ? `<p class="text-xs text-slate-400">${sub}</p>` : ''}
                </div>
            </button>`;
        }).join('');
    }
});