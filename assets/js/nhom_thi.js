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

        let allGroups = [], userHasGroup = false, userLoaiTK = 0, userSoNhomHuongDan = 0;

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
            let btn = '';
            if (!userHasGroup) {
                if (userLoaiTK === 2) {
                    // GV: nút "Xin làm GVHD" — disable nếu đã đủ giới hạn
                    const soNhomToiDa = parseInt(g.so_nhom_toi_da_gvhd ?? -1);
                    const daDu = soNhomToiDa >= 0 && userSoNhomHuongDan >= soNhomToiDa;
                    btn = daDu
                        ? `<button disabled title="Bạn đã đủ số nhóm hướng dẫn"
                               class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-slate-400 rounded-lg bg-slate-100 cursor-not-allowed opacity-60">
                               <i class="fas fa-chalkboard-teacher"></i> Xin làm GVHD
                           </button>`
                        : `<button onclick="xinLamGVHD(${g.idnhom})"
                               class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 transition">
                               <i class="fas fa-chalkboard-teacher"></i> Xin làm GVHD
                           </button>`;
                } else {
                    // SV: nút "Xin tham gia"
                    btn = `<button onclick="xinVaoNhom(${g.idnhom})"
                               class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 transition">
                               <i class="fas fa-user-plus"></i> Xin tham gia
                           </button>`;
                }
            }
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
                userLoaiTK = parseInt(d.user_loai_tk || 0);
                userSoNhomHuongDan = parseInt(d.user_so_nhom_huong_dan || 0);
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
                    else if (qlTab === 'nop-tai-lieu') elContent.innerHTML = renderNopTaiLieuSkeleton();

                    elContent.classList.remove('hidden');
                    bindCaiDat(nhom);
                    if (qlTab === 'nop-tai-lieu') loadNopTaiLieu(nhom);
                } catch (e) {
                    elLoading.classList.add('hidden');
                    elError.textContent = 'Không thể kết nối máy chủ';
                    elError.classList.remove('hidden');
                }
            }

            function renderThanhVien(nhom) {
                const svList = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) !== 3);
                const gvhdList = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) === 3);
                const soGVHDToiDa = nhom.so_gvhd_toi_da ?? null;
                const coTheMotGVHD = soGVHDToiDa === null || gvhdList.length < soGVHDToiDa;

                const roleLabel = r => {
                    if (r == 1) return '<span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full font-semibold">Trưởng nhóm</span>';
                    if (r == 3) return '<span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-semibold">GVHD</span>';
                    return '<span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full">Thành viên</span>';
                };

                const memberRows = (nhom.thanh_vien || []).map(tv => {
                    const isSV = parseInt(tv.idvaitronhom) !== 3;
                    const isChuNhom = parseInt(tv.idtk) === parseInt(nhom.idChuNhom);
                    const isTruongNhom = parseInt(tv.idvaitronhom) === 1;
                    const actionBtns = nhom.is_chu_nhom && !isChuNhom ? `
                        <div class="flex items-center gap-1">
                            ${isSV && !isTruongNhom ? `
                                <button onclick="setTruongNhom(${tv.idtk}, '${esc(tv.ten)}')" title="Chỉ định trưởng nhóm"
                                    class="text-xs text-purple-500 hover:text-purple-700 transition"><i class="fas fa-crown"></i></button>
                                <button onclick="chuyenChuNhom(${tv.idtk}, '${esc(tv.ten)}')" title="Chuyển quyền chủ nhóm"
                                    class="text-xs text-amber-500 hover:text-amber-700 transition"><i class="fas fa-key"></i></button>
                            ` : ''}
                            <button onclick="kickMember(${tv.idtk}, '${esc(tv.ten)}')"
                                class="text-xs text-rose-500 hover:text-rose-700 transition"><i class="fas fa-times-circle"></i></button>
                        </div>` : '';
                    return `
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
                            ${actionBtns}
                        </div>
                    </div>`;
                }).join('');

                return `<div class="divide-y-0">${memberRows}</div>
                    ${nhom.is_chu_nhom ? `<div class="mt-4 flex gap-2 flex-wrap">
                        <button onclick="openMoiTV()"
                            class="inline-flex items-center gap-1 px-4 py-2 text-xs font-semibold text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition">
                            <i class="fas fa-user-plus"></i> Mời thành viên
                        </button>
                        ${coTheMotGVHD ? `<button onclick="openMoiGVHD()"
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

                return list.map(yc => {
                    const roleBadge = yc.loaiYeuCau === 'GVHD'
                        ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700"><i class="fas fa-chalkboard-teacher"></i> Xin làm GVHD</span>`
                        : `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><i class="fas fa-user-plus"></i> Xin tham gia</span>`;
                    return `
                    <div id="yc-${yc.idYeuCau}" class="flex items-center justify-between p-4 border border-slate-200 rounded-xl mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-400 flex items-center justify-center text-white text-sm font-bold">
                                ${esc(yc.ten || '?').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700 mb-0">${esc(yc.ten)}</p>
                                <div class="mt-0.5">${roleBadge}</div>
                                ${yc.loiNhan ? `<p class="text-xs text-slate-400 italic mt-0.5">"${esc(yc.loiNhan)}"</p>` : ''}
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
                    </div>`;
                }).join('');
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

            // ================================================================
            // TAB: NỘP TÀI LIỆU
            // ================================================================
            function renderNopTaiLieuSkeleton() {
                return `<div id="ntlLoading" class="text-sm text-slate-400 text-center py-8">
                    <i class="fas fa-circle-notch fa-spin text-xl mb-2 block"></i>
                    Đang tải...
                </div>
                <div id="ntlError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
                <div id="ntlContent" class="hidden"></div>`;
            }

            async function loadNopTaiLieu(nhomInfo) {
                const elLoading2 = document.getElementById('ntlLoading');
                const elError2 = document.getElementById('ntlError');
                const elContent2 = document.getElementById('ntlContent');
                if (!elContent2) return;

                try {
                    const d = await apiFetch(`/api/nhom/lay_tai_lieu.php?id_nhom=${qlNhomId}`);
                    elLoading2?.classList.add('hidden');
                    if (d.status !== 'success') {
                        if (elError2) { elError2.textContent = d.message; elError2.classList.remove('hidden'); }
                        return;
                    }
                    const { sanpham, chuDeSK, vongThi, isTruongNhom } = d.data;

                    elContent2.innerHTML = buildNopTaiLieuUI(sanpham, chuDeSK, vongThi, isTruongNhom);
                    elContent2.classList.remove('hidden');
                    bindNopTaiLieu(sanpham, chuDeSK, vongThi, isTruongNhom);
                } catch (e) {
                    elLoading2?.classList.add('hidden');
                    if (elError2) { elError2.textContent = 'Không thể kết nối máy chủ'; elError2.classList.remove('hidden'); }
                }
            }

            function buildNopTaiLieuUI(sanpham, chuDeSK, vongThi, isTruongNhom) {
                // ── Card đề tài ──────────────────────────────────────
                const thuocChuDe = sanpham?.tenChuDe ? `<span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">${esc(sanpham.tenChuDe)}</span>` : '';
                const trangThaiMap = { CHO_DUYET: ['Chờ duyệt', 'bg-amber-100 text-amber-700'], DA_DUYET: ['Đã duyệt', 'bg-green-100 text-green-700'], BI_LOAI: ['Bị loại', 'bg-red-100 text-red-700'] };
                const [ttLabel, ttClass] = trangThaiMap[sanpham?.trangThai] || ['—', 'bg-slate-100 text-slate-500'];

                let deTaiCard;
                if (!sanpham) {
                    deTaiCard = `<div class="p-4 border border-dashed border-slate-300 rounded-xl bg-slate-50">
                        <p class="text-sm text-slate-500 mb-3">
                            <i class="fas fa-info-circle text-slate-400 mr-1"></i>
                            Nhóm chưa có đề tài nghiên cứu.
                            ${isTruongNhom ? 'Tạo đề tài để bắt đầu nộp tài liệu.' : 'Đợi Trưởng nhóm tạo đề tài.'}
                        </p>
                        ${isTruongNhom ? `<button id="btnTaoDeTai"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md">
                            <i class="fas fa-plus"></i> Tạo đề tài
                        </button>` : ''}
                    </div>`;
                } else {
                    deTaiCard = `<div class="p-4 border border-slate-200 rounded-xl bg-white flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white flex-shrink-0">
                                <i class="fas fa-flask text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800 mb-1">${esc(sanpham.tenSanPham)}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    ${thuocChuDe}
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold ${ttClass}">${ttLabel}</span>
                                </div>
                            </div>
                        </div>
                        ${isTruongNhom ? `<button id="btnSuaDeTai" data-id="${sanpham.idSanPham}" data-ten="${esc(sanpham.tenSanPham)}" data-chude="${sanpham.idChuDeSK || ''}"
                            class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                            <i class="fas fa-pen mr-1"></i> Sửa
                        </button>` : ''}
                    </div>`;
                }

                // ── Modal tạo/sửa đề tài ─────────────────────────
                const chuDeOptions = chuDeSK.map(c =>
                    `<option value="${c.idChuDeSK}">${esc(c.tenChuDe)}</option>`
                ).join('');
                const modalDeTai = `
                <div id="ntlModalDeTai" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                            <h3 id="ntlModalDeTaiTitle" class="text-sm font-bold text-slate-700">Tạo đề tài</h3>
                            <button id="btnNtlModalDeTaiClose" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-slate-700">Tên đề tài <span class="text-red-500">*</span></label>
                                <input id="ntlInputTenDeTai" type="text" maxlength="200" placeholder="Nhập tên đề tài nghiên cứu..."
                                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                            </div>
                            ${chuDeOptions ? `<div>
                                <label class="block mb-1 text-xs font-semibold text-slate-700">Chủ đề</label>
                                <select id="ntlInputChuDe" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none">
                                    <option value="">— Chọn chủ đề —</option>
                                    ${chuDeOptions}
                                </select>
                            </div>` : ''}
                            <div id="ntlFormFieldsWrap"></div>
                        </div>
                        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-200 bg-slate-50">
                            <button id="btnNtlModalDeTaiCancel" class="px-4 py-2 text-xs font-bold uppercase rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100">Hủy</button>
                            <button id="btnNtlModalDeTaiSave" class="px-4 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md">Lưu</button>
                        </div>
                    </div>
                </div>`;

                // ── Danh sách vòng thi ────────────────────────────
                const vongThiList = vongThi.length
                    ? vongThi.map(vt => {
                        let statusBadge;
                        if (vt.khongCanNop) {
                            statusBadge = `<span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Không cần nộp</span>`;
                        } else if (vt.daQuaHan) {
                            statusBadge = vt.daNop
                                ? `<span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700"><i class="fas fa-check mr-1"></i>Đã nộp</span>`
                                : `<span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500"><i class="fas fa-lock mr-1"></i>Đã đóng</span>`;
                        } else {
                            statusBadge = vt.daNop
                                ? `<span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700"><i class="fas fa-check mr-1"></i>Đã nộp</span>`
                                : `<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700"><i class="fas fa-clock mr-1"></i>Chưa nộp</span>`;
                        }
                        const deadline = vt.thoiGianDongNop
                            ? `<span class="text-xs text-slate-400 mt-0.5 block">Hạn: ${new Date(vt.thoiGianDongNop).toLocaleString('vi-VN')}</span>`
                            : '';
                        const canSelect = !vt.khongCanNop && isTruongNhom && (!vt.daQuaHan || vt.daNop);
                        return `<button type="button" data-id="${vt.idVongThi}" ${!canSelect ? 'disabled' : ''}
                            class="ntl-vt-btn w-full text-left px-3 py-2.5 rounded-lg border transition-all text-sm
                                   border-slate-200 bg-white text-slate-700
                                   ${canSelect ? 'hover:border-fuchsia-300 hover:bg-fuchsia-50/50 cursor-pointer' : 'opacity-60 cursor-not-allowed'}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-slate-700 truncate">${esc(vt.tenVongThi)}</span>
                                ${statusBadge}
                            </div>
                            ${vt.soField > 0 ? `<span class="text-xs text-slate-400">${vt.soField} trường</span>` : ''}
                            ${deadline}
                        </button>`;
                    }).join('')
                    : '<p class="text-xs text-slate-400 text-center py-4">Sự kiện chưa có vòng thi</p>';

                return `
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Cột trái: đề tài + vòng thi -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400 mb-2">Đề tài nghiên cứu</p>
                            ${deTaiCard}
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400 mb-2">Chọn vòng thi để nộp</p>
                            <div id="ntlVongThiList" class="space-y-1.5">${vongThiList}</div>
                        </div>
                    </div>
                    <!-- Cột phải: form nộp tài liệu -->
                    <div class="lg:col-span-2">
                        <div id="ntlFormArea">
                            <div class="p-8 text-sm text-center text-slate-400 border-2 border-dashed border-slate-200 rounded-xl">
                                ${sanpham
                        ? (isTruongNhom ? 'Chọn vòng thi bên trái để nộp tài liệu.' : 'Chỉ Trưởng nhóm mới có thể nộp tài liệu.')
                        : 'Tạo đề tài trước, sau đó chọn vòng thi để nộp tài liệu.'}
                            </div>
                        </div>
                    </div>
                </div>
                ${modalDeTai}`;
            }

            // ── Build form nhập liệu cho một vòng thi ────────────
            function buildFormHTML(formFields, daNopValues) {
                if (!formFields || !formFields.length) {
                    return `<div class="p-6 text-center text-slate-400 text-sm border rounded-xl border-slate-200 bg-white">
                        <i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>
                        Vòng thi này không yêu cầu nộp tài liệu.
                    </div>`;
                }
                const buildField = (f) => {
                    const cfg = f.cauHinhJson ? JSON.parse(f.cauHinhJson) : {};
                    const existing = daNopValues?.[f.idField];
                    const val = existing?.giaTriText || '';
                    const fileVal = existing?.duongDanFile || '';
                    const required = parseInt(f.batBuoc) === 1;
                    const reqMark = required ? '<span class="text-red-500 ml-0.5">*</span>' : '';
                    let inputHTML;
                    switch (f.kieuTruong) {
                        case 'TEXT':
                            inputHTML = `<input type="text" name="field_${f.idField}" value="${esc(val)}"
                                maxlength="${cfg.maxLength || 200}" placeholder="${esc(cfg.placeholder || '')}"
                                ${required ? 'required' : ''}
                                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />`;
                            break;
                        case 'TEXTAREA':
                            inputHTML = `<textarea name="field_${f.idField}" rows="${cfg.rows || 4}"
                                placeholder="${esc(cfg.placeholder || '')}"
                                ${required ? 'required' : ''}
                                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none resize-y">${esc(val)}</textarea>`;
                            break;
                        case 'URL':
                            inputHTML = `<input type="url" name="field_${f.idField}" value="${esc(val)}"
                                placeholder="${esc(cfg.placeholder || 'https://')}"
                                ${required ? 'required' : ''}
                                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />`;
                            break;
                        case 'SELECT': {
                            const opts = (cfg.options || []).map(o =>
                                `<option value="${esc(o)}" ${val === o ? 'selected' : ''}>${esc(o)}</option>`
                            ).join('');
                            inputHTML = `<select name="field_${f.idField}" ${required ? 'required' : ''}
                                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none">
                                <option value="">— Chọn —</option>${opts}
                            </select>`;
                            break;
                        }
                        case 'CHECKBOX':
                            inputHTML = `<label class="flex items-start gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="field_${f.idField}" value="1" ${val === '1' ? 'checked' : ''} ${required ? 'required' : ''}
                                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-fuchsia-500 flex-shrink-0" />
                                <span class="text-sm text-slate-700">${esc(cfg.label || f.tenTruong)}</span>
                            </label>`;
                            break;
                        case 'FILE': {
                            const acceptAttr = cfg.accept ? `accept=".${(cfg.accept).split(',').map(s => s.trim()).join(',.')}"` : '';
                            const sizeInfo = cfg.maxSizeKB ? `Tối đa ${Math.round(cfg.maxSizeKB / 1024 * 10) / 10} MB` : '';
                            const existingFile = fileVal
                                ? `<div class="flex items-center gap-2 mt-1.5 text-xs text-slate-500">
                                    <i class="fas fa-file text-slate-400"></i>
                                    <span>File hiện tại: <a href="${esc(fileVal)}" target="_blank" class="text-fuchsia-600 hover:underline">${esc(fileVal.split('/').pop())}</a></span>
                                  </div>`
                                : '';
                            inputHTML = `<input type="file" name="file_${f.idField}" ${acceptAttr} ${required && !fileVal ? 'required' : ''}
                                class="w-full px-3 py-1.5 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-fuchsia-50 file:text-fuchsia-700 hover:file:bg-fuchsia-100" />
                                ${sizeInfo ? `<p class="text-xs text-slate-400 mt-1">${esc(sizeInfo)}</p>` : ''}
                                ${existingFile}`;
                            break;
                        }
                        default:
                            inputHTML = `<input type="text" name="field_${f.idField}" value="${esc(val)}"
                                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />`;
                    }
                    return `<div>
                        <label class="block mb-1 text-xs font-semibold text-slate-700">${esc(f.tenTruong)}${reqMark}</label>
                        ${inputHTML}
                    </div>`;
                };
                return formFields.map(buildField).join('');
            }

            async function loadVongThiForm(idVongThi, tenVongThi, sanphamId) {
                const formArea = document.getElementById('ntlFormArea');
                if (!formArea) return;
                formArea.innerHTML = `<div class="p-4 text-sm text-slate-400 text-center border rounded-xl border-slate-200 bg-white">
                    <i class="fas fa-circle-notch fa-spin text-xl mb-2 block"></i>Đang tải form...</div>`;
                try {
                    const d = await apiFetch(`/api/nhom/lay_tai_lieu.php?id_nhom=${qlNhomId}&id_vong_thi=${idVongThi}`);
                    if (d.status !== 'success') {
                        formArea.innerHTML = `<p class="text-xs text-red-500 p-4">${esc(d.message)}</p>`;
                        return;
                    }
                    const { formFields, daNopValues, sanpham } = d.data;
                    if (!formFields || !formFields.length) {
                        formArea.innerHTML = `<div class="p-6 text-center text-slate-400 text-sm border rounded-xl border-slate-200 bg-white">
                            <i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>
                            Vòng thi này không yêu cầu nộp tài liệu.
                        </div>`;
                        return;
                    }
                    const hasFile = formFields.some(f => f.kieuTruong === 'FILE');
                    const deadlineInfo = d.data.vongThi?.find?.(vt => vt.idVongThi === idVongThi)?.thoiGianDongNop;
                    formArea.innerHTML = `
                        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50">
                                <div>
                                    <p class="text-xs font-bold uppercase text-slate-400">Nộp tài liệu cho</p>
                                    <p class="text-sm font-semibold text-slate-700">${esc(tenVongThi)}</p>
                                </div>
                                ${daNopValues && Object.keys(daNopValues).length
                            ? `<span class="text-xs px-2.5 py-1 rounded-full bg-green-100 text-green-700 font-semibold"><i class="fas fa-check mr-1"></i>Đã nộp — có thể cập nhật</span>`
                            : `<span class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 font-semibold">Chưa nộp</span>`}
                            </div>
                            <form id="ntlForm" ${hasFile ? 'enctype="multipart/form-data"' : ''} class="px-4 py-5 space-y-4">
                                <input type="hidden" name="id_nhom" value="${qlNhomId}" />
                                <input type="hidden" name="id_vong_thi" value="${idVongThi}" />
                                ${buildFormHTML(formFields, daNopValues)}
                            </form>
                            <div class="flex justify-end px-4 py-3 border-t border-slate-200 bg-slate-50">
                                <button id="btnNtlSubmit" type="button"
                                    class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md">
                                    <i class="fas fa-paper-plane"></i>
                                    ${daNopValues && Object.keys(daNopValues).length ? 'Cập nhật tài liệu' : 'Nộp tài liệu'}
                                </button>
                            </div>
                        </div>`;
                    bindNtlSubmit();
                } catch {
                    formArea.innerHTML = `<p class="text-xs text-red-500 p-4">Lỗi kết nối máy chủ.</p>`;
                }
            }

            function bindNtlSubmit() {
                document.getElementById('btnNtlSubmit')?.addEventListener('click', async function () {
                    const form = document.getElementById('ntlForm');
                    if (!form) return;

                    // Client-side required validation
                    const invalids = form.querySelectorAll('[required]');
                    let valid = true;
                    invalids.forEach(el => {
                        if (!el.value && !(el.type === 'checkbox' && el.checked)) {
                            el.classList.add('border-red-400');
                            valid = false;
                        } else {
                            el.classList.remove('border-red-400');
                        }
                    });
                    if (!valid) {
                        Swal.fire({ icon: 'warning', title: 'Vui lòng điền đủ các trường bắt buộc (*)', timer: 2000, showConfirmButton: false });
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang nộp...';

                    try {
                        // Luôn dùng FormData để nop_tai_lieu.php có thể đọc qua $_POST/$_FILES
                        const fd = new FormData(form);
                        // Checkbox không check sẽ không có trong FormData; gán '0' để BE biết
                        form.querySelectorAll('input[type=checkbox]').forEach(cb => {
                            if (!cb.checked) fd.set(cb.name, '0');
                        });
                        const r = await fetch('/api/nhom/nop_tai_lieu.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: fd,
                        });
                        const res = await r.json();

                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Nộp tài liệu thành công!', timer: 1800, showConfirmButton: false })
                                .then(() => loadNopTaiLieu(null));
                        } else {
                            Swal.fire({ icon: 'error', title: 'Không thể nộp', text: res.message });
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Nộp tài liệu';
                        }
                    } catch {
                        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Vui lòng thử lại.' });
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Nộp tài liệu';
                    }
                });
            }

            function bindNopTaiLieu(sanpham, chuDeSK, vongThi, isTruongNhom) {
                // Vòng thi click
                document.querySelectorAll('.ntl-vt-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (this.disabled) return;
                        // Active state
                        document.querySelectorAll('.ntl-vt-btn').forEach(b => {
                            b.classList.remove('border-fuchsia-400', 'bg-fuchsia-50', 'text-fuchsia-700', 'font-semibold');
                            b.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
                        });
                        this.classList.add('border-fuchsia-400', 'bg-fuchsia-50', 'text-fuchsia-700', 'font-semibold');
                        this.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                        const idVT = parseInt(this.dataset.id);
                        const tenVT = this.querySelector('.font-medium')?.textContent.trim() || '';
                        loadVongThiForm(idVT, tenVT, sanpham?.idSanPham);
                    });
                });

                // Modal đề tài
                const modal = document.getElementById('ntlModalDeTai');
                const btnClose = document.getElementById('btnNtlModalDeTaiClose');
                const btnCancel = document.getElementById('btnNtlModalDeTaiCancel');
                const btnSave = document.getElementById('btnNtlModalDeTaiSave');
                const inputTen = document.getElementById('ntlInputTenDeTai');
                const inputCD = document.getElementById('ntlInputChuDe');
                const formFieldsWrap = document.getElementById('ntlFormFieldsWrap');

                // Load form mặc định SK vào modal
                let _formFieldsMacDinh = [];
                async function loadFormFieldsMacDinh(tenHienTai, chuDeId) {
                    if (formFieldsWrap) formFieldsWrap.innerHTML = '';
                    try {
                        const d = await apiFetch(`/api/nhom/san_pham.php?id_nhom=${qlNhomId}`);
                        if (d.status !== 'success') return;
                        _formFieldsMacDinh = d.data.formFields || [];
                        const daNopValues = d.data.daNopValues || {};
                        if (!_formFieldsMacDinh.length || !formFieldsWrap) return;

                        formFieldsWrap.innerHTML = `
                            <div class="pt-3 border-t border-slate-100">
                                <p class="text-xs font-bold uppercase text-slate-400 mb-3">Thông tin bổ sung</p>
                                <div class="space-y-3" id="ntlDynamicFields">
                                    ${_formFieldsMacDinh.map(f => {
                            const cfg = f.cauHinhJson ? JSON.parse(f.cauHinhJson) : {};
                            const existing = daNopValues[f.idField];
                            const val = existing?.giaTriText || '';
                            const required = parseInt(f.batBuoc) === 1;
                            const reqMark = required ? '<span class="text-red-500 ml-0.5">*</span>' : '';
                            let inputHTML;
                            switch (f.kieuTruong) {
                                case 'TEXTAREA':
                                    inputHTML = `<textarea name="ff_${f.idField}" rows="${cfg.rows || 3}"
                                                    placeholder="${esc(cfg.placeholder || '')}" ${required ? 'required' : ''}
                                                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none resize-y">${esc(val)}</textarea>`;
                                    break;
                                case 'URL':
                                    inputHTML = `<input type="url" name="ff_${f.idField}" value="${esc(val)}"
                                                    placeholder="${esc(cfg.placeholder || 'https://')}" ${required ? 'required' : ''}
                                                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />`;
                                    break;
                                case 'SELECT': {
                                    const opts = (cfg.options || []).map(o =>
                                        `<option value="${esc(o)}" ${val === o ? 'selected' : ''}>${esc(o)}</option>`
                                    ).join('');
                                    inputHTML = `<select name="ff_${f.idField}" ${required ? 'required' : ''}
                                                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none">
                                                    <option value="">— Chọn —</option>${opts}
                                                </select>`;
                                    break;
                                }
                                case 'CHECKBOX':
                                    inputHTML = `<label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" name="ff_${f.idField}" value="1" ${val === '1' ? 'checked' : ''} ${required ? 'required' : ''}
                                                        class="w-4 h-4 rounded border-slate-300 text-fuchsia-500" />
                                                    <span class="text-sm text-slate-700">${esc(cfg.label || f.tenTruong)}</span>
                                                </label>`;
                                    break;
                                default: // TEXT
                                    inputHTML = `<input type="text" name="ff_${f.idField}" value="${esc(val)}"
                                                    maxlength="${cfg.maxLength || 200}" placeholder="${esc(cfg.placeholder || '')}" ${required ? 'required' : ''}
                                                    class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />`;
                            }
                            return `<div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">${esc(f.tenTruong)}${reqMark}</label>
                                            ${inputHTML}
                                        </div>`;
                        }).join('')}
                                </div>
                            </div>`;
                    } catch { /* form mặc định không bắt buộc phải load được */ }
                }

                const showModal = async (tenHienTai, chuDeId) => {
                    if (inputTen) inputTen.value = tenHienTai || '';
                    if (inputCD) inputCD.value = chuDeId || '';
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                    inputTen?.focus();
                    await loadFormFieldsMacDinh(tenHienTai, chuDeId);
                };
                const hideModal = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

                document.getElementById('btnTaoDeTai')?.addEventListener('click', () => showModal('', ''));
                document.getElementById('btnSuaDeTai')?.addEventListener('click', function () {
                    showModal(this.dataset.ten, this.dataset.chude);
                });
                [btnClose, btnCancel].forEach(b => b?.addEventListener('click', hideModal));
                modal?.addEventListener('click', e => { if (e.target === modal) hideModal(); });

                btnSave?.addEventListener('click', async function () {
                    const tenDeTai = inputTen?.value.trim() || '';
                    if (!tenDeTai) { Swal.fire({ icon: 'warning', title: 'Vui lòng nhập tên đề tài' }); return; }

                    // Collect dynamic field values
                    const fieldValues = {};
                    _formFieldsMacDinh.forEach(f => {
                        const el = document.querySelector(`[name="ff_${f.idField}"]`);
                        if (!el) return;
                        if (f.kieuTruong === 'CHECKBOX') {
                            fieldValues[f.idField] = el.checked ? '1' : '0';
                        } else {
                            fieldValues[f.idField] = el.value;
                        }
                    });

                    this.disabled = true; this.textContent = 'Đang lưu...';
                    const res = await apiPost('/api/nhom/san_pham.php', {
                        id_nhom: qlNhomId,
                        ten_san_pham: tenDeTai,
                        id_chu_de_sk: inputCD?.value ? parseInt(inputCD.value) : null,
                        field_values: fieldValues,
                    });
                    this.disabled = false; this.textContent = 'Lưu';
                    if (res.status === 'success') {
                        hideModal();
                        Swal.fire({ icon: 'success', title: 'Đã lưu!', timer: 1200, showConfirmButton: false })
                            .then(() => loadNopTaiLieu(null));
                    } else {
                        Swal.fire({ icon: 'error', title: res.message });
                    }
                });
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
            document.getElementById('btnCloseMoiTV')?.addEventListener('click', () => {
                document.getElementById('modalMoiTV')?.classList.add('hidden');
                document.getElementById('searchSVInput').value = '';
                document.getElementById('svSearchResults').innerHTML = '';
            });
            document.getElementById('btnCloseMoiGVHD')?.addEventListener('click', () => {
                document.getElementById('modalMoiGVHD')?.classList.add('hidden');
                document.getElementById('searchGVInput').value = '';
                document.getElementById('gvSearchResults').innerHTML = '';
            });

            // Hàm mở modal + load danh sách ngay
            window.openMoiTV = function () {
                document.getElementById('searchSVInput').value = '';
                document.getElementById('modalMoiTV').classList.remove('hidden');
                searchUser('sv', '', 'svSearchResults');
            };
            window.openMoiGVHD = function () {
                document.getElementById('searchGVInput').value = '';
                document.getElementById('modalMoiGVHD').classList.remove('hidden');
                searchUser('gv', '', 'gvSearchResults');
            };

            let svTimer, gvTimer;
            document.getElementById('searchSVInput')?.addEventListener('input', function () {
                clearTimeout(svTimer);
                svTimer = setTimeout(() => searchUser('sv', this.value.trim(), 'svSearchResults'), 350);
            });
            document.getElementById('searchGVInput')?.addEventListener('input', function () {
                clearTimeout(gvTimer);
                gvTimer = setTimeout(() => searchUser('gv', this.value.trim(), 'gvSearchResults'), 350);
            });

            window.sendInvite = async function (idTK, loaiYeuCau = 'SV') {
                const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_sk: idSk, id_nhom: qlNhomId, chieu_moi: 0, id_tk_doi_phuong: idTK, loai_yeu_cau: loaiYeuCau });
                document.getElementById('modalMoiTV')?.classList.add('hidden');
                document.getElementById('modalMoiGVHD')?.classList.add('hidden');
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, timer: 1500, showConfirmButton: false });
            };

            // Chuyển quyền chủ nhóm
            window.chuyenChuNhom = async function (idTk, ten) {
                const c = await Swal.fire({
                    title: `Chuyển quyền chủ nhóm cho ${ten}?`,
                    text: 'Bạn sẽ trở thành thành viên thường sau khi chuyển.',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Xác nhận', cancelButtonText: 'Huỷ',
                    confirmButtonColor: '#f59e0b',
                });
                if (!c.isConfirmed) return;
                const res = await apiPost('/api/nhom/nhuong_quyen.php', { id_sk: idSk, id_nhom: qlNhomId, action: 'chu_nhom', id_nguoi_nhan: idTk });
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: res.message });
                }
            };

            // Chỉ định trưởng nhóm
            window.setTruongNhom = async function (idTk, ten) {
                const c = await Swal.fire({
                    title: `Chỉ định ${ten} làm trưởng nhóm?`,
                    icon: 'question', showCancelButton: true,
                    confirmButtonText: 'Xác nhận', cancelButtonText: 'Huỷ',
                    confirmButtonColor: '#8b5cf6',
                });
                if (!c.isConfirmed) return;
                const res = await apiPost('/api/nhom/nhuong_quyen.php', { id_sk: idSk, id_nhom: qlNhomId, action: 'truong_nhom', id_nguoi_nhan: idTk });
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }).then(() => loadQL());
                } else {
                    Swal.fire({ icon: 'error', title: res.message });
                }
            };

            loadQL();
            return; // không chạy tiếp load danh sách
        }

        // ── Danh sách nhóm của tôi ──
        const elLoading = document.getElementById('myGroupLoading');
        const elError = document.getElementById('myGroupError');
        const elNoGroup = document.getElementById('noGroupState');
        const elContent = document.getElementById('myGroupContent');

        function renderGroupCard(nhom) {
            const gvhdList = (nhom.thanh_vien || []).filter(tv => parseInt(tv.idvaitronhom) === 3);
            const hasGvhd = gvhdList.length > 0;
            const yeuCauCoGVHD = parseInt(nhom.yeu_cau_co_gvhd) === 1;
            const soGVHDToiDa = nhom.so_gvhd_toi_da ?? null;
            const coTheMotGVHD = soGVHDToiDa === null || gvhdList.length < soGVHDToiDa;
            const showCanhBao = yeuCauCoGVHD && !hasGvhd;
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

            // GVHD row: hiện danh sách nếu có, cảnh báo chỉ khi bắt buộc mà chưa có
            const gvhdRow = hasGvhd
                ? gvhdList.map(gv => `
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-white rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600">
                        <i class="fas fa-chalkboard-teacher"></i> GVHD: ${esc(gv.ten)}
                    </div>`).join('')
                : showCanhBao
                    ? `<div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                           <i class="fas fa-exclamation-triangle text-amber-500 text-sm"></i>
                           <span class="text-xs font-semibold text-amber-700">Nhóm cần có GVHD</span>
                       </div>`
                    : '';

            const qlUrl = `/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idNhom}&quan_ly_tab=thanh-vien`;
            const moiUrl = `/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idNhom}&quan_ly_tab=thanh-vien`;
            const nopUrl = `/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idNhom}&quan_ly_tab=nop-tai-lieu`;

            return `
            <div class="bg-white border ${showCanhBao ? 'border-orange-300 border-l-4 border-l-orange-400' : 'border-slate-200'} rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-slate-800 text-sm">${esc(nhom.tennhom)}</span>
                    ${statusBadge}
                </div>
                <div class="flex flex-wrap gap-1.5">${chips}</div>
                ${gvhdRow ? `<div class="flex flex-wrap gap-2">${gvhdRow}</div>` : ''}
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
                    ${coTheMotGVHD ? `<a href="/event-detail?id_sk=${idSk}&tab=nhom-my&id_nhom=${nhom.idNhom}&quan_ly_tab=thanh-vien"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-orange-500 hover:bg-orange-600 transition">
                        <i class="fas fa-chalkboard-teacher"></i> Mời GVHD
                    </a>` : ''}
                    <a href="${nopUrl}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded-lg bg-green-600 hover:bg-green-700 transition">
                        <i class="fas fa-paper-plane"></i> Nộp tài liệu
                    </a>
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
        const requestSubtab = String(window.REQUEST_SUBTAB || 'loi-moi');

        // ── SUBTAB: LỜI MỜI ────────────────────────────────────────────
        if (requestSubtab === 'loi-moi') {
            const elLoading = document.getElementById('invitesLoading');
            const elError   = document.getElementById('invitesError');
            const elEmpty   = document.getElementById('invitesEmpty');
            const elList    = document.getElementById('invitesList');

            function renderInviteCard(inv) {
                const soTV = parseInt(inv.so_thanh_vien_sv || 0);
                const roleBadge = inv.loaiYeuCau === 'GVHD'
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700"><i class="fas fa-chalkboard-teacher"></i> Mời làm GVHD</span>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><i class="fas fa-users"></i> Mời tham gia nhóm</span>`;
                return `<div id="invite-${inv.idYeuCau}" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-800 text-sm">${esc(inv.tennhom)}</span>
                        <span class="text-xs text-slate-400">${soTV} thành viên</span>
                    </div>
                    <div>${roleBadge}</div>
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

            function showInvitesEmpty() {
                elEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-16 h-16 mb-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="font-semibold text-slate-700 mb-1">Không có lời mời</p>
                    <p class="text-sm text-slate-400">Bạn chưa có lời mời tham gia nhóm nào trong sự kiện này.</p>
                </div>`;
                elList.classList.add('hidden');
                elEmpty.classList.remove('hidden');
            }

            async function loadInvites() {
                try {
                    const d = await apiFetch(`/api/nhom/getrequest.php?id_sk=${idSk}`);
                    elLoading.classList.add('hidden');
                    if (d.status !== 'success') {
                        elError.textContent = d.message || 'Lỗi tải dữ liệu';
                        elError.classList.remove('hidden');
                        return;
                    }
                    const pending = d.data?.loi_moi_den || [];
                    if (!pending.length) {
                        showInvitesEmpty();
                    } else {
                        elList.innerHTML = pending.map(renderInviteCard).join('');
                        elList.classList.remove('hidden');
                    }
                } catch (e) {
                    elLoading.classList.add('hidden');
                    elError.textContent = 'Không thể kết nối máy chủ';
                    elError.classList.remove('hidden');
                }
            }
            loadInvites();
        }

        // ── SUBTAB: YÊU CẦU CỦA TÔI ───────────────────────────────────
        if (requestSubtab === 'yeu-cau') {
            const elLoading      = document.getElementById('sentLoading');
            const elError        = document.getElementById('sentError');
            const elPendingEmpty = document.getElementById('sentPendingEmpty');
            const elPendingList  = document.getElementById('sentPendingList');
            const elHistSection  = document.getElementById('sentHistorySection');
            const elHistList     = document.getElementById('sentHistoryList');

            function renderSentCard(yc) {
                const roleLabel = yc.loaiYeuCau === 'GVHD' ? 'Xin làm GVHD' : 'Xin tham gia';
                const roleBadge = yc.loaiYeuCau === 'GVHD'
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700"><i class="fas fa-chalkboard-teacher"></i> ${roleLabel}</span>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><i class="fas fa-users"></i> ${roleLabel}</span>`;
                return `<div id="sent-${yc.idYeuCau}" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-3 shadow-soft-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-800 text-sm">${esc(yc.tennhom || yc.maNhom)}</span>
                        <span class="text-xs text-slate-400">${yc.maNhom || ''}</span>
                    </div>
                    <div>${roleBadge}</div>
                    ${yc.loiNhan ? `<p class="text-xs text-slate-500 italic">"${esc(yc.loiNhan)}"</p>` : ''}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-amber-600 font-medium"><i class="fas fa-clock mr-1"></i>Đang chờ duyệt</span>
                        <button onclick="rutYeuCau(${yc.idYeuCau})"
                            class="px-3 py-1.5 text-xs font-semibold text-rose-600 rounded-lg border border-rose-200 hover:bg-rose-50 transition">
                            <i class="fas fa-times mr-1"></i> Rút yêu cầu
                        </button>
                    </div>
                </div>`;
            }

            function renderHistoryItem(yc) {
                const ok = parseInt(yc.trangThai) === 1;
                const roleLabel = yc.loaiYeuCau === 'GVHD' ? 'GVHD' : 'Thành viên';
                return `<div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                    <div>
                        <span class="text-sm text-slate-600">${esc(yc.tennhom || yc.maNhom)}</span>
                        <span class="text-xs text-slate-400 ml-2">(${roleLabel})</span>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full ${ok ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-600'}">
                        ${ok ? 'Đã chấp nhận' : 'Bị từ chối'}
                    </span>
                </div>`;
            }

            async function loadSentRequests() {
                try {
                    const d = await apiFetch(`/api/nhom/getrequest.php?id_sk=${idSk}`);
                    elLoading.classList.add('hidden');
                    if (d.status !== 'success') {
                        elError.textContent = d.message || 'Lỗi tải dữ liệu';
                        elError.classList.remove('hidden');
                        return;
                    }
                    const all     = d.data?.yeu_cau_gui_di || [];
                    const pending = all.filter(i => parseInt(i.trangThai) === 0);
                    const history = all.filter(i => parseInt(i.trangThai) !== 0);

                    if (!pending.length) {
                        elPendingEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-center border border-dashed border-slate-300 rounded-xl bg-slate-50">
                            <span class="material-symbols-outlined text-3xl text-slate-300 mb-2">send</span>
                            <p class="font-semibold text-slate-600 text-sm mb-1">Chưa có yêu cầu đang chờ</p>
                            <p class="text-slate-400 text-xs">Hãy <a href="/event-detail?id_sk=${idSk}&tab=nhom-all" class="text-primary font-semibold hover:underline">xem tất cả nhóm</a> để xin tham gia.</p>
                        </div>`;
                        elPendingEmpty.classList.remove('hidden');
                    } else {
                        elPendingList.innerHTML = pending.map(renderSentCard).join('');
                        elPendingList.classList.remove('hidden');
                    }

                    if (history.length) {
                        elHistList.innerHTML = history.map(renderHistoryItem).join('');
                        elHistSection.classList.remove('hidden');
                    }
                } catch (e) {
                    elLoading.classList.add('hidden');
                    elError.textContent = 'Không thể kết nối máy chủ';
                    elError.classList.remove('hidden');
                }
            }
            loadSentRequests();
        }
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
        const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_sk: idSk, id_nhom: idNhom, chieu_moi: 1, loai_yeu_cau: 'SV', loi_nhan: r.value || '' });
        Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, confirmButtonColor: '#5e72e4' });
    };

    window.xinLamGVHD = async function (idNhom) {
        const r = await Swal.fire({
            title: 'Xin làm Giảng viên hướng dẫn?', input: 'text',
            inputPlaceholder: 'Lời nhắn (tuỳ chọn)...',
            showCancelButton: true, confirmButtonText: 'Gửi yêu cầu', cancelButtonText: 'Huỷ',
            confirmButtonColor: '#5e72e4',
        });
        if (!r.isConfirmed) return;
        const res = await apiPost('/api/nhom/gui_yeu_cau.php', { id_sk: idSk, id_nhom: idNhom, chieu_moi: 1, loai_yeu_cau: 'GVHD', loi_nhan: r.value || '' });
        Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message, confirmButtonColor: '#5e72e4' });
    };

    window.rutYeuCau = async function (idYC) {
        const c = await Swal.fire({
            title: 'Rút yêu cầu?',
            text: 'Bạn có chắc muốn rút yêu cầu này không?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Rút yêu cầu', cancelButtonText: 'Huỷ',
            confirmButtonColor: '#ef4444',
        });
        if (!c.isConfirmed) return;
        const res = await apiPost('/api/nhom/huy_yeu_cau.php', { id_yeu_cau: idYC });
        if (res.status === 'success') {
            document.getElementById(`sent-${idYC}`)?.remove();
            // Nếu list pending rỗng → hiện empty state
            if (!document.querySelector('#sentPendingList [id^="sent-"]')) {
                const elList = document.getElementById('sentPendingList');
                const elEmpty = document.getElementById('sentPendingEmpty');
                elList?.classList.add('hidden');
                if (elEmpty) {
                    elEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-center border border-dashed border-slate-300 rounded-xl bg-slate-50">
                        <span class="material-symbols-outlined text-3xl text-slate-300 mb-2">send</span>
                        <p class="font-semibold text-slate-600 text-sm mb-1">Chưa có yêu cầu đang chờ</p>
                    </div>`;
                    elEmpty.classList.remove('hidden');
                }
            }
            Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: res.message });
        }
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
            // Nếu list lời mời rỗng → hiện empty state
            if (!document.querySelector('#invitesList [id^="invite-"]')) {
                const elList  = document.getElementById('invitesList');
                const elEmpty = document.getElementById('invitesEmpty');
                elList?.classList.add('hidden');
                if (elEmpty) {
                    elEmpty.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg class="w-16 h-16 mb-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="font-semibold text-slate-700 mb-1">Không còn lời mời nào</p>
                        <p class="text-sm text-slate-400">Bạn đã xử lý tất cả lời mời.</p>
                    </div>`;
                    elEmpty.classList.remove('hidden');
                }
            }
            Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        } else Swal.fire({ icon: 'error', title: res.message });
    };

    async function submitTaoNhom() {
        const tenNhom = document.getElementById('inputTenNhom')?.value.trim();
        const moTa = document.getElementById('inputMoTa')?.value.trim();
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
            dang_tuyen: dangTuyen
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
        // Cho phép q rỗng để load danh sách ban đầu; chặn khi gõ 1 ký tự
        if (q.length === 1) { el.innerHTML = '<p class="text-xs text-slate-400 p-2">Tiếp tục gõ để tìm kiếm...</p>'; return; }
        el.innerHTML = '<p class="text-xs text-slate-400 p-2"><i class="fas fa-circle-notch fa-spin mr-1"></i>Đang tải...</p>';
        const d = await apiFetch(`/api/nhom/tim_kiem_user.php?loai=${loai}&q=${encodeURIComponent(q)}&id_sk=${idSk}`);
        const list = d.data || [];
        const meta = d.meta || {};
        if (!list.length) { el.innerHTML = '<p class="text-xs text-slate-400 p-2">Không tìm thấy kết quả</p>'; return; }
        el.innerHTML = list.map(u => {
            const name = esc(u.tenSV || u.tenGV || '');
            const sub = loai === 'sv' ? esc(u.MSV || '') : '';
            const loaiYeuCau = loai === 'gv' ? 'GVHD' : 'SV';

            // Xác định trạng thái disable
            let disableReason = '';
            if (loai === 'sv') {
                if (!u.da_dang_ky_sk) disableReason = 'Chưa đăng ký sự kiện';
                else if (u.da_co_nhom == 1) disableReason = 'Đã có nhóm';
            } else {
                if (!u.da_dang_ky_sk) disableReason = 'Chưa đăng ký sự kiện';
                else if (meta.so_nhom_toi_da_gvhd !== null && meta.so_nhom_toi_da_gvhd !== undefined
                    && parseInt(u.so_nhom_dang_huong_dan) >= parseInt(meta.so_nhom_toi_da_gvhd)) {
                    disableReason = 'Đã đủ số nhóm hướng dẫn';
                }
            }
            const isDisabled = disableReason !== '';

            return `<button ${isDisabled ? 'disabled' : `onclick="sendInvite(${u.idTK}, '${loaiYeuCau}')"`}
                title="${isDisabled ? disableReason : ''}"
                class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm rounded-lg transition
                    ${isDisabled ? 'opacity-50 cursor-not-allowed bg-slate-50' : 'hover:bg-slate-50'}">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-pink-400 flex items-center justify-center text-white text-xs font-bold shrink-0">
                    ${name.charAt(0).toUpperCase()}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-700 mb-0">${name}</p>
                    ${sub ? `<p class="text-xs text-slate-400">${sub}</p>` : ''}
                    ${isDisabled ? `<p class="text-xs text-rose-400 mt-0.5"><i class="fas fa-ban mr-1"></i>${disableReason}</p>` : ''}
                </div>
            </button>`;
        }).join('');
    }
});