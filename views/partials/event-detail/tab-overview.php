<?php
/**
 * Partial: tab-overview.php
 * Dữ liệu load qua JS fetch từ /api/su_kien/overview_su_kien.php?id_sk=X
 * window.EVENT_DETAIL_ID và window.IS_GUEST được inject từ event-detail.php
 */
?>

<style>
.ov-stat-card {
    transition: transform .2s ease, box-shadow .2s ease;
}

.ov-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(146, 19, 236, .10);
}

.ov-round-card {
    transition: transform .15s ease;
}

.ov-round-card:hover {
    transform: translateX(4px);
}

.ov-btn-register {
    background: linear-gradient(135deg, #9213ec, #c026d3);
    box-shadow: 0 4px 18px rgba(146, 19, 236, .35);
    transition: box-shadow .2s, transform .2s;
}

.ov-btn-register:hover {
    box-shadow: 0 6px 26px rgba(146, 19, 236, .50);
    transform: translateY(-1px);
}

.ov-skeleton {
    background: linear-gradient(90deg, #f0eef5 25%, #e8e4f0 50%, #f0eef5 75%);
    background-size: 200% 100%;
    animation: ov-shimmer 1.5s infinite;
    border-radius: 8px;
}

@keyframes ov-shimmer {
    0% {
        background-position: 200% 0
    }

    100% {
        background-position: -200% 0
    }
}
</style>

<!-- ── Loading State ── -->
<div id="ov-loading" class="space-y-5">
    <div class="bg-white rounded-2xl p-7" style="box-shadow:0 1px 8px rgba(146,19,236,.06)">
        <div class="ov-skeleton h-5 w-32 mb-4"></div>
        <div class="ov-skeleton h-9 w-2/3 mb-3"></div>
        <div class="flex gap-2">
            <div class="ov-skeleton h-6 w-16"></div>
            <div class="ov-skeleton h-6 w-24"></div>
        </div>
    </div>
    <div class="grid grid-cols-4 gap-3">
        <div class="ov-skeleton h-20 rounded-2xl"></div>
        <div class="ov-skeleton h-20 rounded-2xl"></div>
        <div class="ov-skeleton h-20 rounded-2xl"></div>
        <div class="ov-skeleton h-20 rounded-2xl"></div>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div class="ov-skeleton h-32 rounded-2xl"></div>
        <div class="ov-skeleton h-32 rounded-2xl"></div>
        <div class="ov-skeleton h-32 rounded-2xl"></div>
    </div>
    <div class="ov-skeleton h-48 rounded-2xl"></div>
</div>

<!-- ── Error State ── -->
<div id="ov-error"
    class="hidden px-5 py-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 flex items-center gap-3 text-sm">
    <span class="material-symbols-outlined shrink-0">error</span>
    <span id="ov-error-msg">Không thể tải dữ liệu sự kiện.</span>
</div>

<!-- ── Content ── -->
<div id="ov-content" class="hidden space-y-5">

    <!-- Hero -->
    <div class="bg-white rounded-2xl px-8 py-7" style="box-shadow:0 1px 8px rgba(146,19,236,.06)">
        <div class="flex items-start justify-between gap-6 flex-wrap">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2 items-center mb-3">
                    <span id="ov-badge-cap"
                        class="px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full uppercase tracking-wider hidden"></span>
                    <span id="ov-badge-status"
                        class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full"></span>
                </div>
                <h1 id="ov-ten-sk" class="text-2xl font-extrabold text-slate-900 leading-snug mb-3"></h1>
                <div id="ov-chu-de" class="flex flex-wrap gap-2"></div>
            </div>
            <div id="ov-action" class="shrink-0"></div>
        </div>
    </div>

    <!-- 4 Time Chips -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php
        $timeChips = [
            ['id' => 'ov-time-mo',   'icon' => 'how_to_reg',  'label' => 'Mở đăng ký'],
            ['id' => 'ov-time-dong', 'icon' => 'event_busy',  'label' => 'Đóng đăng ký'],
            ['id' => 'ov-time-bd',   'icon' => 'play_circle', 'label' => 'Bắt đầu'],
            ['id' => 'ov-time-kt',   'icon' => 'verified',    'label' => 'Kết thúc'],
        ];
        foreach ($timeChips as $chip): ?>
        <div class="bg-white rounded-2xl px-5 py-4 flex items-center gap-4 border border-primary/5 hover:border-primary/25 transition-colors"
            style="box-shadow:0 1px 6px rgba(146,19,236,.05)">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                style="background:rgba(146,19,236,.08)">
                <span class="material-symbols-outlined text-primary text-[20px]"><?php echo $chip['icon']; ?></span>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                    <?php echo $chip['label']; ?></div>
                <div id="<?php echo $chip['id']; ?>" class="text-[15px] font-bold text-slate-800">—</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php
        $statCards = [
            ['id' => 'ov-stat-nhom', 'icon' => 'group',     'label' => 'Tổng số nhóm'],
            ['id' => 'ov-stat-gv',   'icon' => 'school',    'label' => 'Tổng số giảng viên'],
            ['id' => 'ov-stat-cd',   'icon' => 'menu_book', 'label' => 'Số chủ đề nghiên cứu'],
        ];
        foreach ($statCards as $card): ?>
        <div class="ov-stat-card bg-white rounded-2xl p-6 relative overflow-hidden"
            style="border:1px solid rgba(146,19,236,.08);box-shadow:0 1px 8px rgba(146,19,236,.05)">
            <div class="absolute -right-3 -bottom-3 opacity-[0.07]">
                <span class="material-symbols-outlined text-primary"
                    style="font-size:80px;"><?php echo $card['icon']; ?></span>
            </div>
            <div class="text-sm font-semibold text-slate-500 mb-2"><?php echo $card['label']; ?></div>
            <div id="<?php echo $card['id']; ?>" class="text-4xl font-extrabold text-primary mb-3 leading-none">—</div>
            <div id="<?php echo $card['id']; ?>-sub"
                class="text-xs font-semibold text-primary/60 flex items-center gap-1"></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Vòng thi -->
    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 1px 8px rgba(146,19,236,.06)">
        <div class="flex items-center gap-2 mb-5">
            <span class="material-symbols-outlined text-primary text-[20px]">alt_route</span>
            <h2 class="text-base font-bold text-slate-800">Lộ trình cuộc thi</h2>
            <span id="ov-vong-count"
                class="ml-1 px-2 py-0.5 bg-primary/10 text-primary text-xs font-bold rounded-full"></span>
        </div>
        <div id="ov-vong-list" class="space-y-3"></div>
        <div id="ov-vong-empty" class="hidden text-sm text-slate-400 text-center py-6">Chưa có vòng thi nào.</div>
    </div>

</div><!-- end #ov-content -->

<script>
(function() {
    const BASE = window.APP_BASE_PATH || '';
    const idSk = window.EVENT_DETAIL_ID || 0;
    const isGuest = window.IS_GUEST === true;

    const $loading = document.getElementById('ov-loading');
    const $error = document.getElementById('ov-error');
    const $errMsg = document.getElementById('ov-error-msg');
    const $content = document.getElementById('ov-content');

    function fmt(dt) {
        if (!dt) return '—';
        const d = new Date(dt);
        if (isNaN(d)) return '—';
        return d.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    function showError(msg) {
        $loading.classList.add('hidden');
        $error.classList.remove('hidden');
        $errMsg.textContent = msg || 'Không thể tải dữ liệu sự kiện.';
    }

    function renderVongThi(list) {
        const $list = document.getElementById('ov-vong-list');
        const $empty = document.getElementById('ov-vong-empty');
        const $count = document.getElementById('ov-vong-count');
        if (!list || list.length === 0) {
            $empty.classList.remove('hidden');
            return;
        }
        $count.textContent = list.length + ' vòng';
        $list.innerHTML = list.map(v => {
            const isActive = v.trangThai === 'dang_dien_ra';
            const isDone = v.trangThai === 'da_ket_thuc';
            const borderCls = isActive ? 'border-primary' : 'border-slate-200';
            const opacityCls = isDone ? 'opacity-60' : '';
            const titleCls = isActive ? 'text-primary' : 'text-slate-800';
            const deadlineCls = isActive ? 'text-primary' : 'text-slate-600';
            const activeDot = isActive ?
                `<div style="position:absolute;left:-10px;top:50%;transform:translateY(-50%);width:20px;height:20px;background:#9213ec;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 4px rgba(146,19,236,.15);"><span class="material-symbols-outlined" style="font-size:11px;color:white;">adjust</span></div>` :
                '';
            const badgeHienTai = isActive ?
                `<span style="padding:2px 8px;background:rgba(146,19,236,.1);color:#9213ec;font-size:10px;font-weight:700;border-radius:99px;text-transform:uppercase;">Hiện tại</span>` :
                '';
            const deadline = v.thoiGianDongNop ?
                `<div class="text-xs font-semibold ${deadlineCls}">Hạn nộp: ${fmt(v.thoiGianDongNop)}</div>` :
                (v.ngayKetThuc ?
                    `<div class="text-xs font-semibold ${deadlineCls}">Kết thúc: ${fmt(v.ngayKetThuc)}</div>` :
                    '');
            const ringCls = isActive ? 'ring-2 ring-primary/20 shadow-md' : 'shadow-sm';
            return `<div class="ov-round-card relative bg-white rounded-xl px-6 py-5 border-l-4 ${borderCls} ${ringCls} ${opacityCls}">${activeDot}<div class="flex items-center justify-between flex-wrap gap-3"><div><div class="flex items-center gap-2 mb-1"><span class="text-sm font-bold ${titleCls}">${v.tenVongThi || ''}</span>${badgeHienTai}</div><div class="text-xs text-slate-400">${v.moTa || ''}</div></div><div class="text-right">${deadline}<div class="text-xs text-slate-400">${fmt(v.ngayBatDau)} — ${fmt(v.ngayKetThuc)}</div></div></div></div>`;
        }).join('');
    }

    function renderChuDe(list) {
        const $el = document.getElementById('ov-chu-de');
        if (!list || list.length === 0) return;
        $el.innerHTML = list.map(c =>
            `<span style="padding:4px 10px;background:rgba(146,19,236,.08);color:#7c3aed;font-size:11px;font-weight:600;border-radius:6px;">#${c.tenChuDe}</span>`
            ).join('');
    }

    function renderAction(dangKy, trangThai) {
        const $el = document.getElementById('ov-action');
        if (isGuest) {
            $el.innerHTML =
                `<a href="/sign-in" class="ov-btn-register flex items-center gap-2 px-6 py-3 text-white font-bold text-sm rounded-2xl border-none cursor-pointer no-underline"><span class="material-symbols-outlined text-[18px]">login</span>Đăng nhập để tham gia</a>`;
            return;
        }
        if (dangKy.da_dang_ky) {
            $el.innerHTML =
                `<div class="flex items-center gap-2 px-5 py-3 bg-green-50 text-green-600 font-bold text-sm rounded-2xl border border-green-200"><span class="material-symbols-outlined text-[18px]">check_circle</span>Đã tham gia</div>`;
            return;
        }
        if (!trangThai.dang_mo_dk) {
            $el.innerHTML =
                `<div class="flex items-center gap-2 px-5 py-3 bg-slate-100 text-slate-400 font-bold text-sm rounded-2xl border border-slate-200 cursor-not-allowed"><span class="material-symbols-outlined text-[18px]">event_busy</span>${trangThai.dk_label}</div>`;
            return;
        }
        $el.innerHTML =
            `<button id="ov-btn-register" class="ov-btn-register flex items-center gap-2 px-7 py-3.5 text-white font-bold text-sm rounded-2xl border-none cursor-pointer"><span class="material-symbols-outlined text-[18px]">person_add</span>Đăng ký tham gia</button>`;
        document.getElementById('ov-btn-register').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML =
                `<span class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span> Đang xử lý...`;
            try {
                const res = await fetch(`${BASE}/api/su_kien/dang_ky_tham_gia.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_sk: idSk
                    })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: json.message || 'Đăng ký thành công!',
                        showConfirmButton: false,
                        timer: 2500
                    });
                    document.getElementById('ov-action').innerHTML =
                        `<div class="flex items-center gap-2 px-5 py-3 bg-green-50 text-green-600 font-bold text-sm rounded-2xl border border-green-200"><span class="material-symbols-outlined text-[18px]">check_circle</span>Đã tham gia</div>`;
                } else {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: json.message || 'Đăng ký thất bại',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    btn.disabled = false;
                    btn.innerHTML =
                        `<span class="material-symbols-outlined text-[18px]">person_add</span> Đăng ký tham gia`;
                }
            } catch (e) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    showConfirmButton: false,
                    timer: 3000
                });
                btn.disabled = false;
                btn.innerHTML =
                    `<span class="material-symbols-outlined text-[18px]">person_add</span> Đăng ký tham gia`;
            }
        });
    }

    async function loadOverview() {
        if (!idSk) {
            showError('Không xác định được sự kiện.');
            return;
        }
        try {
            const res = await fetch(`${BASE}/api/su_kien/overview_su_kien.php?id_sk=${idSk}`);
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                showError(json.message || 'Không tìm thấy sự kiện.');
                return;
            }

            const {
                su_kien,
                trang_thai,
                dang_ky,
                thong_ke,
                vong_thi,
                chu_de
            } = json.data;

            const $sbName = document.getElementById('sidebarEventName');
            if ($sbName) $sbName.textContent = su_kien.tenSK;
            document.title = su_kien.tenSK + ' — ezManagement';

            if (su_kien.tenCap) {
                const $cap = document.getElementById('ov-badge-cap');
                $cap.textContent = su_kien.tenCap;
                $cap.classList.remove('hidden');
            }

            const $status = document.getElementById('ov-badge-status');
            const isOpen = trang_thai.dang_mo_dk;
            $status.className =
                `flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full ${isOpen ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500'}`;
            $status.innerHTML = isOpen ?
                `<span style="width:6px;height:6px;background:#22c55e;border-radius:50%;display:inline-block;"></span>${trang_thai.dk_label}` :
                trang_thai.dk_label;

            document.getElementById('ov-ten-sk').textContent = su_kien.tenSK;
            renderChuDe(chu_de);
            renderAction(dang_ky, trang_thai);

            document.getElementById('ov-time-mo').textContent = fmt(su_kien.ngayMoDangKy);
            document.getElementById('ov-time-dong').textContent = fmt(su_kien.ngayDongDangKy);
            document.getElementById('ov-time-bd').textContent = fmt(su_kien.ngayBatDau);
            document.getElementById('ov-time-kt').textContent = fmt(su_kien.ngayKetThuc);

            document.getElementById('ov-stat-nhom').textContent = thong_ke.so_nhom ?? 0;
            document.getElementById('ov-stat-gv').textContent = thong_ke.so_giang_vien ?? 0;
            document.getElementById('ov-stat-cd').textContent = chu_de.length ?? 0;
            document.getElementById('ov-stat-nhom-sub').innerHTML =
                `<span class="material-symbols-outlined" style="font-size:13px;">groups</span>${thong_ke.so_vong_thi ?? 0} vòng thi`;
            document.getElementById('ov-stat-gv-sub').innerHTML =
                `<span class="material-symbols-outlined" style="font-size:13px;">check_circle</span>${thong_ke.so_gv_huong_dan ?? 0} GVHD`;
            document.getElementById('ov-stat-cd-sub').innerHTML =
                `<span class="material-symbols-outlined" style="font-size:13px;">category</span>${chu_de.length} chủ đề`;

            renderVongThi(vong_thi);

            $loading.classList.add('hidden');
            $content.classList.remove('hidden');
        } catch (e) {
            showError('Lỗi kết nối. Vui lòng thử lại.');
        }
    }

    loadOverview();
})();
</script>