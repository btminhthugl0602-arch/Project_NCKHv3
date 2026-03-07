/**
 * admin_users.js — Quản lý tài khoản
 *
 * Chức năng:
 *  1. Load + hiển thị danh sách tài khoản (server-side pagination)
 *  2. Filter + search server-side theo loại và tên
 *  3. Modal tạo tài khoản (form động theo loại)
 *  4. Slide-over chi tiết: thông tin + khóa/mở + phân quyền
 */

(function () {
    'use strict';

    // ── CONFIG ──────────────────────────────────────────────────
    const CFG = window.ADMIN_USERS_CONFIG || {};
    const BASE_PATH = CFG.basePath || '';
    const ID_TK_HIEN_TAI = CFG.idTKHienTai || 0;

    // ── SWEETALERT2 TOAST MIXIN ─────────────────────────────────
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });

    // ── API ENDPOINTS ────────────────────────────────────────────
    const API = {
        danhSach: BASE_PATH + '/api/tai_khoan/danh_sach.php',
        resetMatKhau: BASE_PATH + '/api/tai_khoan/reset_mat_khau.php',
        chiTiet: BASE_PATH + '/api/tai_khoan/chi_tiet.php',
        taoTaiKhoan: BASE_PATH + '/api/tai_khoan/tao_tai_khoan.php',
        khoaMo: BASE_PATH + '/api/tai_khoan/khoa_mo_tai_khoan.php',
        capNhatQuyen: BASE_PATH + '/api/tai_khoan/cap_nhat_quyen.php',
        danhSachLopKhoa: BASE_PATH + '/api/tai_khoan/danh_sach_lop_khoa.php',
    };

    // ── STATE ────────────────────────────────────────────────────
    const state = {
        filterLoai: 'all',  // 'all' | '1' | '2' | '3'
        searchQuery: '',
        currentPage: 1,
        limit: 10,
        total: 0,
        totalPages: 0,
        selectedIdTK: null,
    };

    let _searchDebounce = null;

    // ── DOM CACHE ────────────────────────────────────────────────
    const el = {};

    // ════════════════════════════════════════════════════════════
    // INIT
    // ════════════════════════════════════════════════════════════
    function init() {
        cacheElements();
        bindEvents();
        loadDanhSach();
        loadDropdownData();
    }

    function cacheElements() {
        // Stats
        el.statTotal = document.getElementById('statTotal');
        el.statAdmin = document.getElementById('statAdmin');
        el.statGV = document.getElementById('statGV');
        el.statSV = document.getElementById('statSV');

        // Table
        el.tableBody = document.getElementById('auTableBody');
        el.emptyState = document.getElementById('auEmptyState');
        el.resultCount = document.getElementById('auResultCount');
        el.searchInput = document.getElementById('auSearch');
        el.filterTabs = document.querySelectorAll('.au-filter-tab');
        el.pagination = document.getElementById('auPagination');

        // Modal tạo tài khoản
        el.createModal = document.getElementById('auCreateModal');
        el.createBox = document.getElementById('auCreateBox');
        el.createBackdrop = document.getElementById('auCreateBackdrop');
        el.btnOpenCreate = document.getElementById('btnOpenCreate');
        el.createClose = document.getElementById('auCreateClose');
        el.createCancel = document.getElementById('auCreateCancel');
        el.createSubmit = document.getElementById('auCreateSubmit');
        el.createSubmitLabel = document.getElementById('auCreateSubmitLabel');
        el.typeBtns = document.querySelectorAll('.au-type-btn');
        el.profileSection = document.getElementById('auProfileSection');
        el.fieldHoTen = document.getElementById('auFieldHoTen');
        el.fieldSV = document.getElementById('auFieldSV');
        el.fieldGV = document.getElementById('auFieldGV');
        // Inputs
        el.inTenTK = document.getElementById('auTenTK');
        el.inMatKhau = document.getElementById('auMatKhau');
        el.inXacNhan = document.getElementById('auXacNhan');
        el.inHoTen = document.getElementById('auHoTen');
        el.inMSV = document.getElementById('auMSV');
        el.inLop = document.getElementById('auLop');
        el.inKhoa = document.getElementById('auKhoa');
        el.inHocHam = document.getElementById('auHocHam');

        // Slide-over
        el.slideOver = document.getElementById('auSlideOver');
        el.soBackdrop = document.getElementById('auSOBackdrop');
        el.soClose = document.getElementById('auSOClose');
        el.soSkeleton = document.getElementById('auSOSkeleton');
        el.soTabInfo = document.getElementById('auSOTabInfo');
        el.soTabPerm = document.getElementById('auSOTabPerm');
        el.soTabInfoBtn = document.getElementById('auSOTabInfoBtn');
        el.soTabPermBtn = document.getElementById('auSOTabPermBtn');
        // Info fields
        el.soAvatar = document.getElementById('auSOAvatar');
        el.soName = document.getElementById('auSOName');
        el.soBadge = document.getElementById('auSOBadge');
        el.soStatusBanner = document.getElementById('auSOStatusBanner');
        el.soStatusIcon = document.getElementById('auSOStatusIcon');
        el.soStatusTitle = document.getElementById('auSOStatusTitle');
        el.soStatusDesc = document.getElementById('auSOStatusDesc');
        el.soTenTK = document.getElementById('auSOTenTK');
        el.soNgayTao = document.getElementById('auSONgayTao');
        el.soHoTen = document.getElementById('auSOHoTen');
        el.soProfileSection = document.getElementById('auSOProfileSection');
        el.soProfileContent = document.getElementById('auSOProfileContent');
        el.soLockIcon = document.getElementById('auSOLockIcon');
        el.soLockLabel = document.getElementById('auSOLockLabel');
        el.soLockDesc = document.getElementById('auSOLockDesc');
        el.soLockBtn = document.getElementById('auSOLockBtn');
        // Perm
        el.soAdminNote = document.getElementById('auSOAdminNote');
        el.soPermList = document.getElementById('auSOPermList');
        el.soSavePerms = document.getElementById('auSOSavePerms');
        el.soSavePermsLabel = document.getElementById('auSavePermsLabel');
        el.permCheckboxes = document.querySelectorAll('#auSOPermList input[type="checkbox"]');
        // Reset mật khẩu
        el.soResetToggle = document.getElementById('auSOResetToggle');
        el.soResetForm = document.getElementById('auSOResetForm');
        el.soResetPw = document.getElementById('auSOResetPw');
        el.soResetPwConfirm = document.getElementById('auSOResetPwConfirm');
        el.soResetSubmit = document.getElementById('auSOResetSubmit');
        el.soResetLabel = document.getElementById('auSOResetLabel');
        el.soResetCancel = document.getElementById('auSOResetCancel');
    }

    // ════════════════════════════════════════════════════════════
    // EVENTS
    // ════════════════════════════════════════════════════════════
    function bindEvents() {
        // Filter tabs
        el.filterTabs.forEach(btn => {
            btn.addEventListener('click', () => {
                state.filterLoai = btn.dataset.filter;
                state.currentPage = 1;
                el.filterTabs.forEach(b => {
                    b.classList.toggle('bg-primary/10', b === btn);
                    b.classList.toggle('text-primary', b === btn);
                    b.classList.toggle('text-slate-500', b !== btn);
                    b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                });
                loadDanhSach();
            });
        });

        // Search — debounce 300ms, reset về trang 1
        el.searchInput.addEventListener('input', () => {
            state.searchQuery = el.searchInput.value.trim();
            clearTimeout(_searchDebounce);
            _searchDebounce = setTimeout(() => {
                state.currentPage = 1;
                loadDanhSach();
            }, 300);
        });

        // Mở modal tạo
        el.btnOpenCreate.addEventListener('click', openCreateModal);
        el.createClose.addEventListener('click', closeCreateModal);
        el.createCancel.addEventListener('click', closeCreateModal);
        el.createBackdrop.addEventListener('click', closeCreateModal);

        // Chọn loại tài khoản
        el.typeBtns.forEach(btn => {
            btn.addEventListener('click', () => switchLoaiTK(btn.dataset.type));
        });

        // Submit tạo tài khoản
        el.createSubmit.addEventListener('click', submitCreateForm);

        // Đóng slide-over
        el.soClose.addEventListener('click', closeSlideOver);
        el.soBackdrop.addEventListener('click', closeSlideOver);

        // Tabs slide-over
        el.soTabInfoBtn.addEventListener('click', () => switchSOTab('info'));
        el.soTabPermBtn.addEventListener('click', () => switchSOTab('perm'));

        // Lưu quyền
        el.soSavePerms.addEventListener('click', savePermissions);

        // Reset mật khẩu — toggle form
        el.soResetToggle.addEventListener('click', () => {
            el.soResetForm.classList.toggle('hidden');
            el.soResetPw.value = '';
            el.soResetPwConfirm.value = '';
            if (!el.soResetForm.classList.contains('hidden')) el.soResetPw.focus();
        });
        el.soResetCancel.addEventListener('click', () => {
            el.soResetForm.classList.add('hidden');
            el.soResetPw.value = '';
            el.soResetPwConfirm.value = '';
        });
        el.soResetSubmit.addEventListener('click', resetMatKhau);

        // ESC key
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (el.slideOver.classList.contains('translate-x-0')) closeSlideOver();
                else if (!el.createModal.classList.contains('pointer-events-none')) closeCreateModal();
            }
        });
    }

    // ════════════════════════════════════════════════════════════
    // DANH SÁCH TÀI KHOẢN
    // ════════════════════════════════════════════════════════════
    async function loadDanhSach() {
        try {
            const params = new URLSearchParams({
                page: state.currentPage,
                limit: state.limit,
            });
            if (state.searchQuery) params.set('search', state.searchQuery);
            if (state.filterLoai !== 'all') params.set('loai', state.filterLoai);

            const res = await fetch(`${API.danhSach}?${params}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (data.status !== 'success' || !data.data) {
                throw new Error(data.message || 'Không tải được danh sách');
            }

            const { rows, total, page, totalPages, stats } = data.data;
            state.total = total;
            state.totalPages = totalPages;
            state.currentPage = page;

            updateStats(stats);
            renderTable(rows);
            renderPagination();
        } catch (err) {
            el.tableBody.innerHTML = '';
            el.emptyState.classList.remove('hidden');
            el.resultCount.textContent = 'Không thể tải dữ liệu.';
            if (el.pagination) el.pagination.innerHTML = '';
            Toast.fire({ icon: 'error', title: err.message || 'Lỗi tải danh sách tài khoản' });
        }
    }

    function updateStats(stats) {
        if (!stats) return;
        el.statTotal.textContent = stats.tong ?? '—';
        el.statAdmin.textContent = stats.admin ?? '—';
        el.statGV.textContent = stats.gv ?? '—';
        el.statSV.textContent = stats.sv ?? '—';
    }

    function renderTable(ds) {
        // Xoá skeleton + data cũ
        el.tableBody.innerHTML = '';
        el.emptyState.classList.add('hidden');

        if (ds.length === 0) {
            el.emptyState.classList.remove('hidden');
            el.resultCount.textContent = 'Không tìm thấy tài khoản nào';
            return;
        }

        ds.forEach(tk => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-violet-50/30 transition-colors cursor-pointer';
            tr.setAttribute('tabindex', '0');
            tr.setAttribute('role', 'button');
            tr.setAttribute('aria-label', `Xem chi tiết tài khoản ${escapeHtml(tk.tenTK)}`);

            tr.innerHTML =
                `<td class="px-6 py-3 align-middle">
                    <div class="flex items-center gap-3">
                        <div class="size-9 rounded-xl flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                             style="background:${getAvatarBg(tk.hoTen || tk.tenTK)}"
                             aria-hidden="true">
                            ${escapeHtml(getInitials(tk.hoTen || tk.tenTK))}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 leading-tight">${escapeHtml(tk.tenTK)}</p>
                            <p class="text-xs text-slate-400 leading-tight">${escapeHtml(tk.hoTen || '—')}</p>
                        </div>
                    </div>
                </td>` +
                `<td class="px-4 py-3 align-middle">${renderLoaiBadge(tk.idLoaiTK)}</td>` +
                `<td class="px-4 py-3 align-middle">
                    <p class="text-xs text-slate-600">${escapeHtml(tk.donVi || '—')}</p>
                </td>` +
                `<td class="px-4 py-3 align-middle">${renderQuyenChips(tk.dsQuyen || [])}</td>` +
                `<td class="px-4 py-3 align-middle">${renderTrangThaiBadge(tk.isActive)}</td>` +
                `<td class="px-4 py-3 align-middle">
                    <p class="text-xs text-slate-500">${formatDate(tk.ngayTao)}</p>
                </td>` +
                `<td class="px-4 py-3 align-middle text-right">
                    <button type="button"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-600
                                   border border-slate-200 rounded-lg hover:bg-slate-100 hover:border-slate-300
                                   transition-colors cursor-pointer"
                            data-idtk="${tk.idTK}"
                            aria-label="Xem chi tiết ${escapeHtml(tk.tenTK)}">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">open_in_new</span>
                        Chi tiết
                    </button>
                </td>`;

            // Click toàn row hoặc nút Chi tiết đều mở slide-over
            const openDetail = () => openSlideOver(tk.idTK);
            tr.addEventListener('click', e => {
                if (!e.target.closest('button[data-idtk]')) return;
                openDetail();
            });
            tr.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(); }
            });
            // Click nút bên trong
            const detailBtn = tr.querySelector('button[data-idtk]');
            if (detailBtn) detailBtn.addEventListener('click', e => { e.stopPropagation(); openDetail(); });

            el.tableBody.appendChild(tr);
        });

        const from = (state.currentPage - 1) * state.limit + 1;
        const to = Math.min(state.currentPage * state.limit, state.total);
        el.resultCount.textContent = state.total === 0
            ? 'Không có kết quả'
            : `Hiển thị ${from}–${to} / ${state.total} tài khoản`;
    }

    // ════════════════════════════════════════════════════════════
    // PAGINATION
    // ════════════════════════════════════════════════════════════
    function renderPagination() {
        if (!el.pagination) return;

        const { currentPage, totalPages } = state;

        if (totalPages <= 1) {
            el.pagination.innerHTML = '';
            return;
        }

        // Tạo range các trang hiển thị (tối đa 5 nút, có "...")
        const pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > 3) pages.push('...');
            const start = Math.max(2, currentPage - 1);
            const end = Math.min(totalPages - 1, currentPage + 1);
            for (let i = start; i <= end; i++) pages.push(i);
            if (currentPage < totalPages - 2) pages.push('...');
            pages.push(totalPages);
        }

        const btnClass = (active) => active
            ? 'inline-flex items-center justify-center min-w-[32px] h-8 px-2 text-xs font-semibold rounded-lg bg-primary text-white cursor-default'
            : 'inline-flex items-center justify-center min-w-[32px] h-8 px-2 text-xs font-semibold rounded-lg text-slate-600 hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer';

        const arrowClass = (disabled) => disabled
            ? 'inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-300 cursor-not-allowed'
            : 'inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors cursor-pointer';

        let html = `<div class="flex items-center gap-1">`;

        // Prev
        html += `<button class="${arrowClass(currentPage === 1)}" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" aria-label="Trang trước">
            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
        </button>`;

        // Page numbers
        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="inline-flex items-center justify-center min-w-[32px] h-8 text-xs text-slate-400">…</span>`;
            } else {
                html += `<button class="${btnClass(p === currentPage)}" data-page="${p}" ${p === currentPage ? 'aria-current="page"' : ''}>${p}</button>`;
            }
        });

        // Next
        html += `<button class="${arrowClass(currentPage === totalPages)}" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" aria-label="Trang sau">
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        </button>`;

        html += `</div>`;
        el.pagination.innerHTML = html;

        // Events
        el.pagination.querySelectorAll('button[data-page]').forEach(btn => {
            if (btn.disabled) return;
            btn.addEventListener('click', () => {
                state.currentPage = parseInt(btn.dataset.page, 10);
                loadDanhSach();
                // Scroll lên đầu bảng
                el.tableBody.closest('table')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
    }

    // ════════════════════════════════════════════════════════════
    // MODAL TẠO TÀI KHOẢN
    // ════════════════════════════════════════════════════════════
    let selectedLoai = null;

    function openCreateModal() {
        selectedLoai = null;
        clearCreateForm();
        el.createModal.classList.remove('opacity-0', 'pointer-events-none');
        el.createBox.classList.remove('scale-95', 'opacity-0');
        el.inTenTK.focus();
    }

    function closeCreateModal() {
        el.createModal.classList.add('opacity-0', 'pointer-events-none');
        el.createBox.classList.add('scale-95', 'opacity-0');
    }

    function clearCreateForm() {
        // Reset type selector
        el.typeBtns.forEach(b => {
            b.classList.remove('border-violet-400', 'bg-violet-100',
                'border-sky-400', 'bg-sky-100',
                'border-emerald-400', 'bg-emerald-100');
            b.classList.add('border-slate-200', 'bg-slate-50');
            b.setAttribute('aria-checked', 'false');
            const icon = b.querySelector('.material-symbols-outlined');
            if (icon) icon.classList.replace('text-violet-600', 'text-slate-400'),
                icon.classList.replace('text-sky-600', 'text-slate-400'),
                icon.classList.replace('text-emerald-600', 'text-slate-400');
        });

        // Ẩn profile section
        el.profileSection.classList.add('hidden');
        el.fieldSV.classList.add('hidden');
        el.fieldGV.classList.add('hidden');

        // Reset inputs
        [el.inTenTK, el.inMatKhau, el.inXacNhan, el.inHoTen, el.inMSV].forEach(i => { if (i) i.value = ''; });
        el.inLop.value = ''; el.inKhoa.value = ''; el.inHocHam.value = '';

        // Clear errors
        clearAllErrors();
    }

    function switchLoaiTK(loai) {
        selectedLoai = loai;

        // Highlight selected button
        const colorMap = {
            '1': { border: 'border-violet-400', bg: 'bg-violet-100', icon: 'text-violet-600' },
            '2': { border: 'border-sky-400', bg: 'bg-sky-100', icon: 'text-sky-600' },
            '3': { border: 'border-emerald-400', bg: 'bg-emerald-100', icon: 'text-emerald-600' },
        };

        el.typeBtns.forEach(btn => {
            const isSelected = btn.dataset.type === loai;
            const c = colorMap[btn.dataset.type];
            btn.classList.toggle(c.border, isSelected);
            btn.classList.toggle(c.bg, isSelected);
            btn.classList.toggle('border-slate-200', !isSelected);
            btn.classList.toggle('bg-slate-50', !isSelected);
            btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.classList.toggle(c.icon, isSelected);
                icon.classList.toggle('text-slate-400', !isSelected);
            }
        });

        clearFieldError('auTypeError');

        // Hiện/ẩn profile section
        if (loai === '1') {
            el.profileSection.classList.add('hidden');
            el.fieldSV.classList.add('hidden');
            el.fieldGV.classList.add('hidden');
        } else {
            el.profileSection.classList.remove('hidden');
            el.fieldHoTen.classList.remove('hidden');
            el.fieldSV.classList.toggle('hidden', loai !== '3');
            el.fieldGV.classList.toggle('hidden', loai !== '2');
        }
    }

    async function submitCreateForm() {
        clearAllErrors();

        // Validate loại
        if (!selectedLoai) {
            showFieldError('auTypeError', 'Vui lòng chọn loại tài khoản');
            return;
        }

        // Validate tên đăng nhập
        const tenTK = el.inTenTK.value.trim();
        if (!tenTK) {
            showFieldError('auTenTKError', 'Tên đăng nhập không được để trống');
            el.inTenTK.focus(); return;
        }
        if (!/^[a-z0-9_]+$/.test(tenTK)) {
            showFieldError('auTenTKError', 'Chỉ dùng chữ thường, số và dấu gạch dưới');
            el.inTenTK.focus(); return;
        }

        // Validate mật khẩu
        const matKhau = el.inMatKhau.value;
        if (matKhau.length < 6) {
            showFieldError('auMatKhauError', 'Mật khẩu tối thiểu 6 ký tự');
            el.inMatKhau.focus(); return;
        }
        if (el.inXacNhan.value !== matKhau) {
            showFieldError('auXacNhanError', 'Mật khẩu xác nhận không khớp');
            el.inXacNhan.focus(); return;
        }

        // Validate profile
        let hoTen = '', msv = '', idLop = 0, idKhoa = 0, hocHam = '';

        if (selectedLoai !== '1') {
            hoTen = el.inHoTen.value.trim();
            if (!hoTen) {
                showFieldError('auHoTenError', 'Họ tên không được để trống');
                el.inHoTen.focus(); return;
            }
        }

        if (selectedLoai === '3') {
            msv = el.inMSV.value.trim();
            if (!msv) {
                showFieldError('auMSVError', 'Mã sinh viên không được để trống');
                el.inMSV.focus(); return;
            }
            idLop = parseInt(el.inLop.value) || 0;
            if (!idLop) {
                showFieldError('auLopError', 'Vui lòng chọn lớp');
                el.inLop.focus(); return;
            }
        }

        if (selectedLoai === '2') {
            idKhoa = parseInt(el.inKhoa.value) || 0;
            hocHam = el.inHocHam.value;
        }

        // Set loading state
        setCreateLoading(true);

        try {
            const body = {
                ten_dang_nhap: tenTK,
                mat_khau: matKhau,
                id_loai_tai_khoan: parseInt(selectedLoai),
                ho_ten: hoTen,
                ma_so_sinh_vien: msv,
                id_don_vi: selectedLoai === '3' ? idLop : idKhoa,
                hoc_ham: hocHam,
            };

            const res = await fetch(API.taoTaiKhoan, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const data = await res.json();

            if (data.status === 'success') {
                closeCreateModal();
                Toast.fire({ icon: 'success', title: 'Tạo tài khoản thành công' });
                await loadDanhSach(); // Reload bảng
            } else {
                // Lỗi cụ thể — hiện inline nếu biết trường nào
                const msg = data.message || 'Có lỗi xảy ra';
                if (msg.toLowerCase().includes('tên đăng nhập')) {
                    showFieldError('auTenTKError', msg);
                    el.inTenTK.focus();
                } else if (msg.toLowerCase().includes('mã sinh viên')) {
                    showFieldError('auMSVError', msg);
                    el.inMSV.focus();
                } else {
                    Toast.fire({ icon: 'error', title: msg });
                }
            }
        } catch (err) {
            Toast.fire({ icon: 'error', title: 'Lỗi kết nối. Vui lòng thử lại.' });
        } finally {
            setCreateLoading(false);
        }
    }

    function setCreateLoading(loading) {
        el.createSubmit.disabled = loading;
        el.createSubmitLabel.textContent = loading ? 'Đang tạo…' : 'Tạo tài khoản';
    }

    // ════════════════════════════════════════════════════════════
    // SLIDE-OVER CHI TIẾT
    // ════════════════════════════════════════════════════════════
    function openSlideOver(idTK) {
        state.selectedIdTK = idTK;

        // Hiện skeleton, ẩn content
        el.soSkeleton.classList.remove('hidden');
        el.soTabInfo.classList.add('hidden');
        el.soTabPerm.classList.add('hidden');

        // Mở panel
        el.slideOver.classList.remove('translate-x-full');
        el.slideOver.classList.add('translate-x-0');
        el.soBackdrop.classList.remove('opacity-0', 'pointer-events-none');
        el.soBackdrop.classList.add('opacity-100');

        // Default tab
        switchSOTab('info');

        // Load data
        loadChiTiet(idTK);
    }

    function closeSlideOver() {
        el.slideOver.classList.remove('translate-x-0');
        el.slideOver.classList.add('translate-x-full');
        el.soBackdrop.classList.remove('opacity-100');
        el.soBackdrop.classList.add('opacity-0', 'pointer-events-none');
        state.selectedIdTK = null;
        // Reset form đặt lại mật khẩu
        el.soResetForm.classList.add('hidden');
        el.soResetPw.value = '';
        el.soResetPwConfirm.value = '';
    }

    function switchSOTab(tab) {
        const isInfo = tab === 'info';

        el.soTabInfoBtn.classList.toggle('text-primary', isInfo);
        el.soTabInfoBtn.classList.toggle('border-primary', isInfo);
        el.soTabInfoBtn.classList.toggle('text-slate-500', !isInfo);
        el.soTabInfoBtn.classList.toggle('border-transparent', !isInfo);
        el.soTabInfoBtn.setAttribute('aria-selected', isInfo ? 'true' : 'false');

        el.soTabPermBtn.classList.toggle('text-primary', !isInfo);
        el.soTabPermBtn.classList.toggle('border-primary', !isInfo);
        el.soTabPermBtn.classList.toggle('text-slate-500', isInfo);
        el.soTabPermBtn.classList.toggle('border-transparent', isInfo);
        el.soTabPermBtn.setAttribute('aria-selected', !isInfo ? 'true' : 'false');

        // Chỉ toggle content nếu không đang skeleton
        if (!el.soSkeleton.classList.contains('hidden')) return;
        el.soTabInfo.classList.toggle('hidden', !isInfo);
        el.soTabPerm.classList.toggle('hidden', isInfo);
    }

    async function loadChiTiet(idTK) {
        try {
            const res = await fetch(`${API.chiTiet}?id=${idTK}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (data.status !== 'success' || !data.data) {
                throw new Error(data.message || 'Không tải được chi tiết');
            }

            renderSlideOver(data.data);
        } catch (err) {
            Toast.fire({ icon: 'error', title: err.message || 'Lỗi tải chi tiết tài khoản' });
            closeSlideOver();
        }
    }

    function renderSlideOver(tk) {
        // Avatar + tên + badge
        const initials = getInitials(tk.hoTen || tk.tenTK);
        el.soAvatar.textContent = initials;
        el.soAvatar.style.background = getAvatarBg(tk.hoTen || tk.tenTK);
        el.soName.textContent = tk.hoTen || tk.tenTK;
        el.soBadge.innerHTML = renderLoaiBadge(tk.idLoaiTK);

        // Status banner
        const isActive = tk.isActive == 1;
        el.soStatusBanner.className = `flex items-center gap-3 p-3 rounded-xl border ${isActive ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'
            }`;
        el.soStatusIcon.textContent = isActive ? 'check_circle' : 'lock';
        el.soStatusIcon.className = `material-symbols-outlined text-[20px] active-icon shrink-0 ${isActive ? 'text-emerald-600' : 'text-red-500'
            }`;
        el.soStatusTitle.className = `text-xs font-semibold ${isActive ? 'text-emerald-700' : 'text-red-700'}`;
        el.soStatusTitle.textContent = isActive ? 'Tài khoản đang hoạt động' : 'Tài khoản đã bị khóa';
        el.soStatusDesc.className = `text-[11px] ${isActive ? 'text-emerald-600' : 'text-red-500'}`;
        el.soStatusDesc.textContent = isActive
            ? 'Người dùng có thể đăng nhập bình thường'
            : 'Người dùng không thể đăng nhập';

        // Thông tin cơ bản
        el.soTenTK.textContent = tk.tenTK || '—';
        el.soNgayTao.textContent = formatDate(tk.ngayTao);
        el.soHoTen.textContent = tk.hoTen || '—';

        // Profile
        renderSOProfile(tk);

        // Lock button
        const isSelf = tk.idTK == ID_TK_HIEN_TAI;
        if (isSelf) {
            el.soLockBtn.disabled = true;
            el.soLockBtn.classList.add('opacity-40', 'cursor-not-allowed');
            el.soLockDesc.textContent = 'Không thể khóa tài khoản của chính mình';
        } else {
            el.soLockBtn.disabled = false;
            el.soLockBtn.classList.remove('opacity-40', 'cursor-not-allowed');

            if (isActive) {
                el.soLockIcon.textContent = 'lock';
                el.soLockIcon.className = 'material-symbols-outlined text-[20px] text-amber-500';
                el.soLockLabel.textContent = 'Khóa tài khoản';
                el.soLockDesc.textContent = 'Người dùng sẽ không thể đăng nhập';
                el.soLockBtn.textContent = 'Khóa';
                el.soLockBtn.className = `px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors cursor-pointer
                                               border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100`;
                el.soLockBtn.onclick = () => confirmKhoaMo(tk.idTK, true);
            } else {
                el.soLockIcon.textContent = 'lock_open';
                el.soLockIcon.className = 'material-symbols-outlined text-[20px] text-emerald-500';
                el.soLockLabel.textContent = 'Mở khóa tài khoản';
                el.soLockDesc.textContent = 'Người dùng sẽ đăng nhập được trở lại';
                el.soLockBtn.textContent = 'Mở khóa';
                el.soLockBtn.className = `px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors cursor-pointer
                                               border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100`;
                el.soLockBtn.onclick = () => confirmKhoaMo(tk.idTK, false);
            }
        }

        // Phân quyền — ẩn tab với SV
        const isSV = tk.idLoaiTK == 3;
        const isAdmin = tk.idLoaiTK == 1;
        el.soTabPermBtn.classList.toggle('hidden', isSV);

        el.soAdminNote.classList.toggle('hidden', !isAdmin);
        el.soPermList.classList.toggle('opacity-50', isAdmin);
        el.soPermList.classList.toggle('pointer-events-none', isAdmin);

        // Set checkbox states
        const dsQuyen = Array.isArray(tk.dsQuyen) ? tk.dsQuyen : [];
        el.permCheckboxes.forEach(cb => {
            cb.checked = dsQuyen.includes(cb.dataset.perm);
            cb.disabled = isAdmin; // Admin auto full quyền
        });

        // Ẩn skeleton, hiện content
        el.soSkeleton.classList.add('hidden');
        el.soTabInfo.classList.remove('hidden');
    }

    function renderSOProfile(tk) {
        const loai = tk.idLoaiTK;
        if (loai == 1) {
            el.soProfileSection.classList.add('hidden');
            return;
        }

        el.soProfileSection.classList.remove('hidden');
        let html = '';

        if (loai == 2) {
            // Giảng viên
            html += infoCell('Khoa', tk.tenKhoa || '—');
            html += infoCell('Học hàm', formatHocHam(tk.hocHam));
        } else if (loai == 3) {
            // Sinh viên
            html += infoCell('Mã SV', tk.maSV || '—');
            html += infoCell('Lớp', tk.tenLop || '—');
            html += infoCell('Khoa', tk.tenKhoa || '—');
            html += infoCell('GPA', tk.GPA != null ? tk.GPA : '—');
            html += infoCell('ĐRL', tk.DRL != null ? tk.DRL : '—');
        }

        el.soProfileContent.innerHTML = html;
    }

    function infoCell(label, value) {
        return `<div class="bg-slate-50 rounded-xl p-3">
            <p class="text-[11px] text-slate-400 font-medium mb-0.5">${escapeHtml(label)}</p>
            <p class="text-sm font-semibold text-slate-700">${escapeHtml(String(value))}</p>
        </div>`;
    }

    // ════════════════════════════════════════════════════════════
    // KHÓA / MỞ KHÓA
    // ════════════════════════════════════════════════════════════
    async function confirmKhoaMo(idTK, doKhoa) {
        const { isConfirmed } = await Swal.fire({
            icon: doKhoa ? 'warning' : 'question',
            title: doKhoa ? 'Khóa tài khoản?' : 'Mở khóa tài khoản?',
            text: doKhoa
                ? 'Người dùng sẽ không thể đăng nhập sau khi bị khóa.'
                : 'Người dùng sẽ có thể đăng nhập trở lại.',
            showCancelButton: true,
            confirmButtonText: doKhoa ? 'Khóa' : 'Mở khóa',
            cancelButtonText: 'Huỷ',
            buttonsStyling: false,
            customClass: {
                confirmButton: doKhoa
                    ? 'px-5 py-2 text-xs font-bold text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors'
                    : 'px-5 py-2 text-xs font-bold text-white bg-emerald-500 rounded-lg hover:bg-emerald-600 transition-colors',
                cancelButton: 'px-5 py-2 text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-lg ml-2',
                actions: 'gap-2',
            },
        });

        if (!isConfirmed) return;

        try {
            const res = await fetch(API.khoaMo, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ id_tai_khoan: idTK, action: doKhoa ? 'khoa' : 'mo' }),
            });
            const data = await res.json();

            if (data.status === 'success') {
                Toast.fire({ icon: 'success', title: doKhoa ? 'Đã khóa tài khoản' : 'Đã mở khóa tài khoản' });
                closeSlideOver();
                await loadDanhSach();
            } else {
                Toast.fire({ icon: 'error', title: data.message || 'Thao tác thất bại' });
            }
        } catch (err) {
            Toast.fire({ icon: 'error', title: 'Lỗi kết nối. Vui lòng thử lại.' });
        }
    }

    // ════════════════════════════════════════════════════════════
    // PHÂN QUYỀN
    // ════════════════════════════════════════════════════════════
    async function savePermissions() {
        if (!state.selectedIdTK) return;

        const dsQuyen = [];
        el.permCheckboxes.forEach(cb => {
            if (cb.checked) dsQuyen.push(cb.dataset.perm);
        });

        el.soSavePerms.disabled = true;
        el.soSavePermsLabel.textContent = 'Đang lưu…';

        try {
            const res = await fetch(API.capNhatQuyen, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    id_tai_khoan: state.selectedIdTK,
                    danh_sach_ma_quyen: dsQuyen,
                }),
            });
            const data = await res.json();

            if (data.status === 'success') {
                Toast.fire({ icon: 'success', title: 'Lưu phân quyền thành công' });
                await loadDanhSach(); // cập nhật cột Quyền HT trong bảng
            } else {
                Toast.fire({ icon: 'error', title: data.message || 'Lưu quyền thất bại' });
            }
        } catch (err) {
            Toast.fire({ icon: 'error', title: 'Lỗi kết nối. Vui lòng thử lại.' });
        } finally {
            el.soSavePerms.disabled = false;
            el.soSavePermsLabel.textContent = 'Lưu phân quyền';
        }
    }

    // ════════════════════════════════════════════════════════════
    // DROPDOWN DATA (Lớp + Khoa)
    // ════════════════════════════════════════════════════════════
    async function loadDropdownData() {
        try {
            const res = await fetch(API.danhSachLopKhoa, { credentials: 'same-origin' });
            const data = await res.json();
            if (data.status !== 'success') return;

            // Populate lớp
            if (Array.isArray(data.data?.dsLop)) {
                data.data.dsLop.forEach(lop => {
                    const opt = document.createElement('option');
                    opt.value = lop.idLop;
                    opt.textContent = `${lop.maLop} — ${lop.tenLop}`;
                    el.inLop.appendChild(opt);
                });
            }

            // Populate khoa
            if (Array.isArray(data.data?.dsKhoa)) {
                data.data.dsKhoa.forEach(khoa => {
                    const opt = document.createElement('option');
                    opt.value = khoa.idKhoa;
                    opt.textContent = khoa.tenKhoa;
                    el.inKhoa.appendChild(opt);
                });
            }
        } catch (_) {
            // Không critical — dropdown sẽ trống, user không tạo được SV/GV
        }
    }

    // ════════════════════════════════════════════════════════════
    // RESET MẬT KHẨU
    // ════════════════════════════════════════════════════════════
    async function resetMatKhau() {
        const pw = el.soResetPw.value.trim();
        const confirm = el.soResetPwConfirm.value.trim();

        if (!pw) {
            el.soResetPw.focus();
            Toast.fire({ icon: 'warning', title: 'Vui lòng nhập mật khẩu mới' });
            return;
        }
        if (pw.length < 6) {
            Toast.fire({ icon: 'warning', title: 'Mật khẩu phải có ít nhất 6 ký tự' });
            return;
        }
        if (pw !== confirm) {
            el.soResetPwConfirm.focus();
            Toast.fire({ icon: 'warning', title: 'Mật khẩu xác nhận không khớp' });
            return;
        }

        const { isConfirmed } = await Swal.fire({
            title: 'Xác nhận đặt lại mật khẩu?',
            text: 'Mật khẩu hiện tại sẽ bị thay thế ngay lập tức.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Đặt lại',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#3b82f6',
        });
        if (!isConfirmed) return;

        el.soResetSubmit.disabled = true;
        el.soResetLabel.textContent = 'Đang xử lý…';

        try {
            const res = await fetch(API.resetMatKhau, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_tai_khoan: state.selectedIdTK,
                    mat_khau_moi: pw,
                    xac_nhan_mat_khau: confirm,
                }),
            });
            const data = await res.json();

            if (data.status === 'success') {
                el.soResetForm.classList.add('hidden');
                el.soResetPw.value = '';
                el.soResetPwConfirm.value = '';
                Toast.fire({ icon: 'success', title: 'Đặt lại mật khẩu thành công' });
            } else {
                Toast.fire({ icon: 'error', title: data.message || 'Có lỗi xảy ra' });
            }
        } catch (_) {
            Toast.fire({ icon: 'error', title: 'Lỗi kết nối. Vui lòng thử lại.' });
        } finally {
            el.soResetSubmit.disabled = false;
            el.soResetLabel.textContent = 'Xác nhận đặt lại';
        }
    }

    // ════════════════════════════════════════════════════════════
    // RENDER HELPERS
    // ════════════════════════════════════════════════════════════
    function renderLoaiBadge(idLoaiTK) {
        const map = {
            1: { cls: 'bg-violet-100 text-violet-700', icon: 'admin_panel_settings', label: 'Quản trị viên' },
            2: { cls: 'bg-sky-100 text-sky-700', icon: 'school', label: 'Giảng viên' },
            3: { cls: 'bg-emerald-100 text-emerald-700', icon: 'person', label: 'Sinh viên' },
        };
        const b = map[idLoaiTK] || { cls: 'bg-slate-100 text-slate-600', icon: 'person', label: 'Không rõ' };
        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold ${b.cls}">
                    <span class="material-symbols-outlined text-[12px] active-icon" aria-hidden="true">${b.icon}</span>
                    ${b.label}
                </span>`;
    }

    function renderTrangThaiBadge(isActive) {
        return isActive == 1
            ? `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                   <span class="size-1.5 bg-emerald-500 rounded-full inline-block" aria-hidden="true"></span>
                   Hoạt động
               </span>`
            : `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-100 text-red-600">
                   <span class="size-1.5 bg-red-400 rounded-full inline-block" aria-hidden="true"></span>
                   Đã khóa
               </span>`;
    }

    function renderQuyenChips(dsQuyen) {
        if (!dsQuyen.length) return '<span class="text-xs text-slate-400">—</span>';
        const labelMap = {
            'quan_ly_tai_khoan': 'QL tài khoản',
            'tao_su_kien': 'Tạo sự kiện',
            'xem_thong_ke': 'Thống kê',
        };
        return dsQuyen.map(q =>
            `<span class="inline-block px-2 py-0.5 text-[10px] font-semibold bg-violet-100 text-violet-700 rounded-full mr-1 mb-1">
                ${labelMap[q] || q}
             </span>`
        ).join('');
    }

    // ════════════════════════════════════════════════════════════
    // FIELD ERROR HELPERS
    // ════════════════════════════════════════════════════════════
    function showFieldError(id, msg) {
        const el_err = document.getElementById(id);
        if (!el_err) return;
        el_err.textContent = msg;
        el_err.classList.remove('hidden');
        const inputId = id.replace('Error', '');
        const input = document.getElementById(inputId);
        if (input) input.classList.add('border-rose-400', 'ring-1', 'ring-rose-300');
    }

    function clearFieldError(id) {
        const el_err = document.getElementById(id);
        if (!el_err) return;
        el_err.textContent = '';
        el_err.classList.add('hidden');
        const inputId = id.replace('Error', '');
        const input = document.getElementById(inputId);
        if (input) input.classList.remove('border-rose-400', 'ring-1', 'ring-rose-300');
    }

    function clearAllErrors() {
        ['auTypeError', 'auTenTKError', 'auMatKhauError', 'auXacNhanError',
            'auHoTenError', 'auMSVError', 'auLopError'].forEach(clearFieldError);
    }

    // ════════════════════════════════════════════════════════════
    // UTILITIES
    // ════════════════════════════════════════════════════════════
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(val) {
        if (!val) return '—';
        const d = new Date(String(val).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(val);
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function getInitials(name) {
        if (!name) return '?';
        const parts = String(name).trim().split(/\s+/);
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    function getAvatarBg(name) {
        const colors = [
            '#7c3aed', '#2563eb', '#0891b2', '#059669',
            '#d97706', '#dc2626', '#c026d3', '#4f46e5',
        ];
        let hash = 0;
        const str = String(name || '');
        for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
        return colors[Math.abs(hash) % colors.length];
    }

    function formatHocHam(val) {
        const map = {
            'Cu_nhan': 'Cử nhân', 'Tha_si': 'Thạc sĩ', 'Tien_si': 'Tiến sĩ',
            'Pho_giao_su': 'Phó giáo sư', 'Giao_su': 'Giáo sư',
        };
        return map[val] || val || '—';
    }

    // ════════════════════════════════════════════════════════════
    // BOOT
    // ════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', init);

})();