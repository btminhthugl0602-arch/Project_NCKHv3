<?php

/**
 * Partial: tab-lichtrinh-sv.php
 * Quyền: isLoggedIn && !cauhinh_sukien
 * Features: Xem timeline (tất cả), nút điểm danh GPS/QR khi phiên DANG_MO,
 *           trạng thái "Đã điểm danh lúc HH:MM", redirect nếu chưa đăng nhập
 */
$_ltsvIsLoggedIn = !empty($_SESSION['idTK']);
$_ltsvIdTK       = $_ltsvIsLoggedIn ? (int) $_SESSION['idTK'] : 0;
// Lấy idNhom của user trong sự kiện n㠺�y để gải kèm khi điểm danh
$_ltsvIdNhom = 0;
if ($_ltsvIsLoggedIn && !empty($conn) && !empty($idSk)) {
    try {
        $stmtNhom = $conn->prepare(
            'SELECT tv.idNhom FROM thanhviennhom tv '
                . 'JOIN nhom n ON n.idNhom = tv.idNhom '
                . 'WHERE tv.idTK = ? AND n.idSK = ? AND n.isActive = 1 LIMIT 1'
        );
        $stmtNhom->execute([$_ltsvIdTK, (int) $idSk]);
        $rowNhom = $stmtNhom->fetch(PDO::FETCH_ASSOC);
        if ($rowNhom) $_ltsvIdNhom = (int) $rowNhom['idNhom'];
    } catch (Throwable $e) { /* fallback: id_nhom = 0 */
    }
}
?>

<style>
    /* ── Timeline SV ── */
    .sv-timeline {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .sv-item {
        background: #fff;
        border-radius: 1rem;
        border: 1.5px solid #f0eaf8;
        box-shadow: 0 1px 6px rgba(146, 19, 236, .05);
        overflow: hidden;
        transition: border-color .2s;
    }

    .sv-item-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .875rem 1.25rem;
        flex-wrap: wrap;
    }

    .sv-time-badge {
        font-size: .72rem;
        font-weight: 600;
        color: #7c3aed;
        background: #f3e8ff;
        padding: .2rem .6rem;
        border-radius: 99px;
        white-space: nowrap;
        flex-shrink: 0;
        font-family: monospace;
    }

    .sv-type-badge {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .04em;
        padding: .15rem .55rem;
        border-radius: 99px;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    .sv-type-HOAT_DONG {
        background: #e0f2fe;
        color: #0369a1;
    }

    .sv-type-DIEM_DANH {
        background: #fce7f3;
        color: #be185d;
    }

    .sv-type-NGHI {
        background: #f0fdf4;
        color: #15803d;
    }

    .sv-type-KHAC {
        background: #f8fafc;
        color: #64748b;
    }

    .sv-item-name {
        font-weight: 600;
        color: #1e1b4b;
        flex: 1;
        min-width: 120px;
    }

    .sv-item-place {
        font-size: .78rem;
        color: #94a3b8;
        white-space: nowrap;
    }

    .sv-item-right {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-left: auto;
        flex-wrap: wrap;
    }

    /* ── Phiên status ── */
    .sv-phien-section {
        border-top: 1px solid #f8f0ff;
        padding: .875rem 1.25rem;
        background: linear-gradient(to bottom, #fdf8ff, #fff);
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .sv-phien-closed {
        font-size: .78rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .sv-phien-not-open {
        font-size: .78rem;
        color: #f59e0b;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .sv-already-checked {
        font-size: .8rem;
        color: #16a34a;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .35rem;
        background: #f0fdf4;
        padding: .35rem .875rem;
        border-radius: 99px;
        border: 1px solid #bbf7d0;
    }

    /* ── Button điểm danh ── */
    .sv-btn-dd {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem 1.1rem;
        border-radius: .625rem;
        border: none;
        cursor: pointer;
        font-size: .82rem;
        font-weight: 700;
        background: linear-gradient(135deg, #be185d, #9213ec);
        color: #fff;
        box-shadow: 0 3px 12px rgba(146, 19, 236, .28);
        transition: opacity .15s, transform .1s;
    }

    .sv-btn-dd:hover {
        opacity: .9;
    }

    .sv-btn-dd:active {
        transform: scale(.97);
    }

    .sv-btn-dd:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .sv-btn-login {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem 1rem;
        border-radius: .625rem;
        border: 1.5px solid #ddd6fe;
        color: #7c3aed;
        background: transparent;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }

    .sv-btn-login:hover {
        background: #f3e8ff;
    }

    /* ── GPS overlay ── */
    .sv-gps-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 10, 30, .55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(3px);
    }

    .sv-gps-modal {
        background: #fff;
        border-radius: 1.25rem;
        width: min(400px, 94vw);
        padding: 2rem;
        text-align: center;
        box-shadow: 0 20px 60px rgba(146, 19, 236, .2);
        animation: sv-pop-in .2s ease;
    }

    @keyframes sv-pop-in {
        from {
            transform: scale(.92);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* ── Skeleton ── */
    .sv-skeleton {
        background: linear-gradient(90deg, #f0eef5 25%, #e8e4f0 50%, #f0eef5 75%);
        background-size: 200% 100%;
        animation: sv-shimmer 1.4s infinite;
        border-radius: .5rem;
    }

    @keyframes sv-shimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    /* ── Toast ── */
    #sv-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .sv-toast-msg {
        padding: .7rem 1.2rem;
        border-radius: .75rem;
        font-size: .83rem;
        font-weight: 600;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
        animation: sv-slide-in .25s ease;
        display: flex;
        align-items: center;
        gap: .5rem;
        max-width: 320px;
    }

    @keyframes sv-slide-in {
        from {
            transform: translateX(120%)
        }

        to {
            transform: translateX(0)
        }
    }

    .sv-toast-success {
        background: #22c55e;
        color: #fff;
    }

    .sv-toast-error {
        background: #ef4444;
        color: #fff;
    }

    .sv-toast-info {
        background: #7c3aed;
        color: #fff;
    }
</style>

<div id="sv-root">
    <div style="margin-bottom:1.25rem;">
        <h6 class="mb-0 font-bold text-slate-700" style="font-size:1.05rem;">
            <span class="material-symbols-outlined align-middle mr-1" style="font-size:20px;color:#9213ec;">event_note</span>
            Lịch trình sự kiện
        </h6>
        <p class="text-xs text-slate-400 mt-1 mb-0">Xem lịch trình và điểm danh khi phiên đang mở.</p>
    </div>

    <!-- Loading -->
    <div id="sv-loading" class="space-y-3">
        <div class="sv-skeleton h-14 w-full"></div>
        <div class="sv-skeleton h-14 w-full"></div>
        <div class="sv-skeleton h-14 w-full"></div>
    </div>

    <div id="sv-error" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

    <div id="sv-empty" class="hidden text-center py-14">
        <span class="material-symbols-outlined text-5xl text-slate-300">calendar_today</span>
        <p class="text-slate-400 mt-3 text-sm">Lịch trình chưa được công bố.</p>
    </div>

    <div id="sv-timeline" class="sv-timeline"></div>
</div>

<div id="sv-toast"></div>

<script>
    /* ════════════════════════════════════════════════════════════
   LTSV — Lịch Trình module (SV / Khán giả view)
════════════════════════════════════════════════════════════ */
    const LTSV = (() => {
        const BASE = window.APP_BASE_PATH || '';
        const ID_SK = window.EVENT_DETAIL_ID || 0;
        const IS_GUEST = window.IS_GUEST || false;
        const ID_NHOM = <?= (int) $_ltsvIdNhom ?>; // nhóm của user trong SK này (0 = không thuộc nhóm)

        let _items = [];
        let _booted = false; // guard: prevent double load()

        // ── Toast ────────────────────────────────────────────────
        function toast(msg, type = 'success') {
            const el = document.createElement('div');
            el.className = `sv-toast-msg sv-toast-${type}`;
            const icons = {
                success: 'check_circle',
                error: 'error',
                info: 'info'
            };
            el.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">${icons[type]||'info'}</span>${msg}`;
            document.getElementById('sv-toast').appendChild(el);
            setTimeout(() => el.remove(), 3500);
        }

        // ── Load ─────────────────────────────────────────────────
        async function load() {
            if (_booted) return;
            _booted = true;
            document.getElementById('sv-loading').classList.remove('hidden');
            document.getElementById('sv-timeline').innerHTML = '';
            document.getElementById('sv-error').classList.add('hidden');
            document.getElementById('sv-empty').classList.add('hidden');

            try {
                const res = await fetch(`${BASE}/api/su_kien/lay_lich_trinh.php?id_sk=${ID_SK}`);
                const json = await res.json();
                _items = json.data || [];

                document.getElementById('sv-loading').classList.add('hidden');
                if (_items.length === 0) {
                    document.getElementById('sv-empty').classList.remove('hidden');
                } else {
                    renderTimeline();
                }
            } catch (e) {
                document.getElementById('sv-loading').classList.add('hidden');
                const errEl = document.getElementById('sv-error');
                errEl.textContent = 'Không thể tải lịch trình. Vui lòng thử lại.';
                errEl.classList.remove('hidden');
            }
        }

        // ── Render ───────────────────────────────────────────────
        function renderTimeline() {
            const container = document.getElementById('sv-timeline');
            container.innerHTML = '';
            _items.forEach(item => container.appendChild(buildItem(item)));
        }

        function buildItem(item) {
            const div = document.createElement('div');
            div.className = 'sv-item';

            const startTime = item.thoiGianBatDau ?
                new Date(item.thoiGianBatDau).toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                }) :
                '—';
            const endTime = item.thoiGianKetThuc ?
                new Date(item.thoiGianKetThuc).toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                }) :
                null;
            const timeStr = endTime ? `${startTime}–${endTime}` : startTime;

            const loaiMap = {
                HOAT_DONG: 'Hoạt động',
                DIEM_DANH: 'Điểm danh',
                NGHI: 'Nghỉ',
                KHAC: 'Khác'
            };

            div.innerHTML = `
            <div class="sv-item-header">
                <span class="sv-time-badge">${esc(timeStr)}</span>
                <span class="sv-type-badge sv-type-${item.loaiHoatDong}">${loaiMap[item.loaiHoatDong] || item.loaiHoatDong}</span>
                <span class="sv-item-name">${esc(item.tenHoatDong)}</span>
                <span class="sv-item-place">${item.diaDiem ? '📍 ' + esc(item.diaDiem) : ''}</span>
            </div>
            ${item.loaiHoatDong === 'DIEM_DANH' ? buildDiemDanhSection(item) : ''}
        `;
            return div;
        }

        function buildDiemDanhSection(item) {
            const phien = item.phien;

            // Chưa có phiên
            if (!phien) {
                return `<div class="sv-phien-section">
                <span class="sv-phien-not-open">
                    <span class="material-symbols-outlined" style="font-size:16px">schedule</span>
                    Chưa có phiên điểm danh
                </span>
            </div>`;
            }

            const ts = phien.trangThai;

            // Đã đóng
            if (ts === 'DA_DONG') {
                const stats = phien.stats || {};
                return `<div class="sv-phien-section">
                <span class="sv-phien-closed">
                    <span class="material-symbols-outlined" style="font-size:16px">lock</span>
                    Đã kết thúc · ${stats.total || 0} người đã điểm danh
                </span>
                ${phien.da_diem_danh
                    ? `<span class="sv-already-checked">
                        <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                        Bạn đã điểm danh lúc ${new Date(phien.da_diem_danh).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'})}
                       </span>`
                    : ''}
            </div>`;
            }

            // Chuẩn bị
            if (ts === 'CHUAN_BI') {
                return `<div class="sv-phien-section">
                <span class="sv-phien-not-open">
                    <span class="material-symbols-outlined" style="font-size:16px">hourglass_top</span>
                    Phiên chưa mở
                </span>
            </div>`;
            }

            // Đang mở
            const stats = phien.stats || {};
            const statStr = `${stats.total || 0} người đã điểm danh`;

            // Đã điểm danh rồi
            if (phien.da_diem_danh) {
                return `<div class="sv-phien-section">
                <span class="sv-already-checked">
                    <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                    Bạn đã điểm danh lúc ${new Date(phien.da_diem_danh).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'})}
                </span>
                <span style="font-size:.75rem;color:#94a3b8">${statStr}</span>
            </div>`;
            }

            // Chưa điểm danh + phiên đang mở
            if (IS_GUEST) {
                const redirectUrl = encodeURIComponent(window.location.href);
                return `<div class="sv-phien-section">
                <span style="font-size:.78rem;color:#94a3b8">🟢 Đang mở · ${statStr}</span>
                <a href="${BASE}/sign-in?redirect=${redirectUrl}" class="sv-btn-login">
                    <span class="material-symbols-outlined" style="font-size:16px">login</span>
                    Đăng nhập để điểm danh
                </a>
            </div>`;
            }

            return `<div class="sv-phien-section">
            <span style="font-size:.78rem;color:#16a34a;font-weight:600">🟢 Đang mở · ${statStr}</span>
            <button class="sv-btn-dd" id="sv-dd-btn-${phien.idPhienDD}"
                onclick="LTSV.startDiemDanh(${phien.idPhienDD}, ${item.idLichTrinh})">
                <span class="material-symbols-outlined" style="font-size:18px">how_to_reg</span>
                Điểm danh ngay
            </button>
        </div>`;
        }

        // ── Điểm danh GPS ────────────────────────────────────────
        function startDiemDanh(idPhienDD, idLichTrinh) {
            const btn = document.getElementById(`sv-dd-btn-${idPhienDD}`);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Đang xử lý...';
            }

            // Thử GPS trước
            if (navigator.geolocation) {
                const overlay = showGPSOverlay();
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        removeOverlay(overlay);
                        submitDiemDanh(idPhienDD, 'GPS', pos.coords.latitude, pos.coords.longitude, btn);
                    },
                    _err => {
                        removeOverlay(overlay);
                        // Fallback: hỏi có muốn điểm danh không GPS không
                        if (confirm('Không lấy được vị trí GPS.\nBạn có muốn điểm danh không cần xác minh vị trí không?')) {
                            submitDiemDanh(idPhienDD, 'QR', null, null, btn);
                        } else {
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">how_to_reg</span> Điểm danh ngay';
                            }
                        }
                    }, {
                        timeout: 8000,
                        maximumAge: 60000,
                        enableHighAccuracy: true
                    }
                );
            } else {
                submitDiemDanh(idPhienDD, 'THU_CONG', null, null, btn);
            }
        }

        function showGPSOverlay() {
            const el = document.createElement('div');
            el.className = 'sv-gps-modal-backdrop';
            el.innerHTML = `
            <div class="sv-gps-modal">
                <span class="material-symbols-outlined text-5xl" style="color:#9213ec;font-size:3rem">location_searching</span>
                <p style="font-weight:700;color:#1e1b4b;margin:.75rem 0 .35rem">Đang lấy vị trí GPS...</p>
                <p style="font-size:.83rem;color:#94a3b8">Vui lòng cho phép truy cập vị trí khi trình duyệt hỏi.</p>
            </div>`;
            document.body.appendChild(el);
            return el;
        }

        function removeOverlay(el) {
            el?.remove();
        }

        async function submitDiemDanh(idPhienDD, phuongThuc, lat, lng, btn) {
            try {
                const payload = {
                    id_sk: ID_SK,
                    id_phien_dd: idPhienDD,
                    phuong_thuc: phuongThuc,
                };
                if (ID_NHOM > 0) payload.id_nhom = ID_NHOM; // lên kết điểm danh với nhóm
                if (lat !== null) {
                    payload.vi_tri_lat = lat;
                    payload.vi_tri_lng = lng;
                }

                const res = await fetch(`${BASE}/api/su_kien/ghi_nhan_diemdanh.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();

                if (json.status === 'success') {
                    toast('✅ Điểm danh thành công!');
                    // Reload để cập nhật trạng thái
                    setTimeout(() => load(), 800);
                } else {
                    toast(json.message || 'Không thể điểm danh', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">how_to_reg</span> Điểm danh ngay';
                    }
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">how_to_reg</span> Điểm danh ngay';
                }
            }
        }

        function esc(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        return {
            load,
            startDiemDanh
        };
    })();

    // Boot
    document.addEventListener('DOMContentLoaded', () => {
        if (window.EVENT_DETAIL_TAB === 'lichtrinh-sv') LTSV.load();
    });
    if (document.readyState !== 'loading') {
        if (window.EVENT_DETAIL_TAB === 'lichtrinh-sv') LTSV.load();
    }
</script>