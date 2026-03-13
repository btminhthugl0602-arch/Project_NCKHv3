<?php

/**
 * Partial: tab-lichtrinh.php
 * Quyền: cauhinh_sukien (BTC)
 * Features: CRUD lịch trình, drag-drop reorder, Leaflet map picker,
 *           quản lý phiên điểm danh (tạo/mở/đóng), xem danh sách điểm danh
 */
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

<style>
    /* ── Layout ── */
    .lt-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .lt-timeline {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    /* ── Timeline item ── */
    .lt-item {
        background: #fff;
        border-radius: 1rem;
        border: 1.5px solid #f0eaf8;
        box-shadow: 0 1px 6px rgba(146, 19, 236, .05);
        transition: border-color .2s, box-shadow .2s;
        overflow: hidden;
        cursor: grab;
    }

    .lt-item:active {
        cursor: grabbing;
    }

    .lt-item.dragging {
        opacity: .5;
        border-color: #9213ec;
    }

    .lt-item.drag-over {
        border-color: #9213ec;
        box-shadow: 0 0 0 3px rgba(146, 19, 236, .15);
    }

    .lt-item-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .875rem 1.25rem;
    }

    .lt-drag-handle {
        color: #d1c4e9;
        cursor: grab;
        flex-shrink: 0;
    }

    .lt-time-badge {
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

    .lt-type-badge {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .04em;
        padding: .15rem .55rem;
        border-radius: 99px;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    .lt-type-HOAT_DONG {
        background: #e0f2fe;
        color: #0369a1;
    }

    .lt-type-DIEM_DANH {
        background: #fce7f3;
        color: #be185d;
    }

    .lt-type-NGHI {
        background: #f0fdf4;
        color: #15803d;
    }

    .lt-type-KHAC {
        background: #f8fafc;
        color: #64748b;
    }

    .lt-item-name {
        font-weight: 600;
        color: #1e1b4b;
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lt-item-place {
        font-size: .78rem;
        color: #94a3b8;
        margin-left: auto;
        white-space: nowrap;
    }

    .lt-item-actions {
        display: flex;
        gap: .35rem;
        flex-shrink: 0;
    }

    .lt-btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        padding: .35rem;
        border-radius: .5rem;
        color: #94a3b8;
        transition: background .15s, color .15s;
        display: flex;
        align-items: center;
        font-size: 18px;
    }

    .lt-btn-icon:hover {
        background: #f3f4f6;
        color: #7c3aed;
    }

    .lt-btn-icon.danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    /* ── Expand panel ── */
    .lt-expand-panel {
        border-top: 1px solid #f0eaf8;
        background: linear-gradient(to bottom, #fdf8ff, #fff);
        padding: 1rem 1.25rem 1.25rem 1.25rem;
        display: none;
    }

    .lt-expand-panel.open {
        display: block;
    }

    .lt-phien-empty {
        text-align: center;
        padding: 1.5rem;
        background: #fdf8ff;
        border-radius: .75rem;
        border: 1.5px dashed #ddd6fe;
    }

    .lt-phien-card {
        border-radius: .75rem;
        padding: 1rem;
        background: #fff;
        border: 1.5px solid #e8e0f7;
    }

    /* ── Trạng thái phiên ── */
    .phien-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        font-weight: 600;
        padding: .25rem .75rem;
        border-radius: 99px;
    }

    .phien-CHUAN_BI {
        background: #fef9c3;
        color: #92400e;
    }

    .phien-DANG_MO {
        background: #dcfce7;
        color: #166534;
    }

    .phien-DA_DONG {
        background: #f1f5f9;
        color: #64748b;
    }

    /* ── Attendance table ── */
    .lt-att-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .82rem;
        margin-top: .75rem;
    }

    .lt-att-table th {
        background: #f8f5ff;
        color: #7c3aed;
        font-weight: 600;
        padding: .5rem .75rem;
        text-align: left;
        border-bottom: 1px solid #ede9fe;
    }

    .lt-att-table td {
        padding: .5rem .75rem;
        border-bottom: 1px solid #f8f5ff;
        color: #374151;
    }

    .lt-att-table tr:last-child td {
        border-bottom: none;
    }

    /* ── Buttons ── */
    .lt-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .5rem 1rem;
        border-radius: .625rem;
        border: none;
        cursor: pointer;
        font-size: .8rem;
        font-weight: 600;
        transition: opacity .15s, transform .1s;
    }

    .lt-btn:active {
        transform: scale(.97);
    }

    .lt-btn-primary {
        background: linear-gradient(135deg, #9213ec, #c026d3);
        color: #fff;
        box-shadow: 0 3px 12px rgba(146, 19, 236, .3);
    }

    .lt-btn-primary:hover {
        opacity: .9;
    }

    .lt-btn-success {
        background: #22c55e;
        color: #fff;
    }

    .lt-btn-success:hover {
        opacity: .9;
    }

    .lt-btn-warning {
        background: #f59e0b;
        color: #fff;
    }

    .lt-btn-warning:hover {
        opacity: .9;
    }

    .lt-btn-danger {
        background: #ef4444;
        color: #fff;
    }

    .lt-btn-danger:hover {
        opacity: .9;
    }

    .lt-btn-outline {
        background: transparent;
        border: 1.5px solid #ddd6fe;
        color: #7c3aed;
    }

    .lt-btn-outline:hover {
        background: #f3e8ff;
    }

    /* ── Modal ── */
    .lt-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 10, 30, .5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity .2s;
        pointer-events: none;
    }

    .lt-modal-backdrop.open {
        opacity: 1;
        pointer-events: all;
    }

    .lt-modal {
        background: #fff;
        border-radius: 1.25rem;
        width: min(720px, 95vw);
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(146, 19, 236, .18);
        transform: translateY(16px);
        transition: transform .2s;
    }

    .lt-modal-backdrop.open .lt-modal {
        transform: translateY(0);
    }

    .lt-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f0eaf8;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 1;
    }

    .lt-modal-title {
        font-weight: 700;
        color: #1e1b4b;
        font-size: 1rem;
    }

    .lt-modal-body {
        padding: 1.5rem;
    }

    .lt-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f0eaf8;
        display: flex;
        justify-content: flex-end;
        gap: .75rem;
        position: sticky;
        bottom: 0;
        background: #fff;
    }

    /* ── Form ── */
    .lt-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .lt-form-row.full {
        grid-template-columns: 1fr;
    }

    .lt-form-group label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        color: #4b4580;
        margin-bottom: .35rem;
    }

    .lt-form-group input,
    .lt-form-group select,
    .lt-form-group textarea {
        width: 100%;
        padding: .6rem .875rem;
        border: 1.5px solid #e5dff5;
        border-radius: .625rem;
        font-size: .85rem;
        color: #1e1b4b;
        background: #fdfcff;
        transition: border-color .15s;
        box-sizing: border-box;
    }

    .lt-form-group input:focus,
    .lt-form-group select:focus,
    .lt-form-group textarea:focus {
        outline: none;
        border-color: #9213ec;
        box-shadow: 0 0 0 3px rgba(146, 19, 236, .1);
    }

    .lt-form-group textarea {
        resize: vertical;
        min-height: 70px;
    }

    .lt-form-required {
        color: #dc2626;
        margin-left: .2rem;
    }

    /* ── Leaflet map ── */
    #lt-map-picker {
        height: 250px;
        border-radius: .75rem;
        overflow: hidden;
        border: 1.5px solid #e5dff5;
        margin-top: .5rem;
    }

    .lt-map-hint {
        font-size: .75rem;
        color: #94a3b8;
        margin-top: .3rem;
    }

    /* ── Search ── */
    .lt-search-box {
        position: relative;
    }

    .lt-search-box input {
        padding-left: 2.25rem;
    }

    .lt-search-icon {
        position: absolute;
        left: .7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
    }

    .lt-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1.5px solid #e5dff5;
        border-radius: .75rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
        z-index: 100;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }

    .lt-search-results.open {
        display: block;
    }

    .lt-search-item {
        padding: .6rem 1rem;
        cursor: pointer;
        font-size: .83rem;
        color: #374151;
        display: flex;
        align-items: center;
        gap: .5rem;
        transition: background .1s;
    }

    .lt-search-item:hover {
        background: #fdf8ff;
    }

    /* ── QR Display ── */
    #lt-qr-container {
        text-align: center;
        padding: 1rem;
    }

    #lt-qr-container canvas {
        border-radius: .75rem;
        box-shadow: 0 4px 20px rgba(146, 19, 236, .1);
    }

    /* ── Skeleton ── */
    .lt-skeleton {
        background: linear-gradient(90deg, #f0eef5 25%, #e8e4f0 50%, #f0eef5 75%);
        background-size: 200% 100%;
        animation: lt-shimmer 1.4s infinite;
        border-radius: .5rem;
    }

    @keyframes lt-shimmer {
        0% {
            background-position: 200% 0
        }

        100% {
            background-position: -200% 0
        }
    }

    /* ── Toast ── */
    #lt-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .lt-toast-msg {
        padding: .75rem 1.25rem;
        border-radius: .75rem;
        font-size: .83rem;
        font-weight: 600;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
        animation: lt-slide-in .25s ease;
        display: flex;
        align-items: center;
        gap: .5rem;
        max-width: 340px;
    }

    @keyframes lt-slide-in {
        from {
            transform: translateX(120%)
        }

        to {
            transform: translateX(0)
        }
    }

    .lt-toast-success {
        background: #22c55e;
        color: #fff;
    }

    .lt-toast-error {
        background: #ef4444;
        color: #fff;
    }

    .lt-toast-info {
        background: #7c3aed;
        color: #fff;
    }
</style>

<!-- ── HTML ── -->
<div id="lt-root">
    <!-- Header -->
    <div class="lt-header">
        <div>
            <h6 class="mb-0 font-bold text-slate-700" style="font-size:1.05rem;">
                <span class="material-symbols-outlined align-middle mr-1" style="font-size:20px;color:#9213ec;">calendar_month</span>
                Lịch trình sự kiện
            </h6>
            <p class="text-xs text-slate-400 mt-1 mb-0">Kéo thả để sắp xếp thứ tự. Click vào hoạt động Điểm danh để quản lý phiên.</p>
        </div>
        <button class="lt-btn lt-btn-primary" onclick="LT.openModal(null)">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span>
            Thêm hoạt động
        </button>
    </div>

    <!-- Loading skeleton -->
    <div id="lt-loading" class="space-y-3">
        <div class="lt-skeleton h-14 w-full"></div>
        <div class="lt-skeleton h-14 w-full"></div>
        <div class="lt-skeleton h-14 w-full"></div>
    </div>

    <!-- Error -->
    <div id="lt-error" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

    <!-- Empty state -->
    <div id="lt-empty" class="hidden text-center py-14">
        <span class="material-symbols-outlined text-5xl text-slate-300">calendar_today</span>
        <p class="text-slate-500 mt-3 text-sm">Chưa có hoạt động nào trong lịch trình.</p>
        <button class="lt-btn lt-btn-primary mt-4" onclick="LT.openModal(null)">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span>
            Thêm hoạt động đầu tiên
        </button>
    </div>

    <!-- Timeline -->
    <div id="lt-timeline" class="lt-timeline"></div>
</div>

<!-- ── Modal tạo/sửa lịch trình ── -->
<div id="lt-modal" class="lt-modal-backdrop" onclick="if(event.target===this)LT.closeModal()">
    <div class="lt-modal">
        <div class="lt-modal-header">
            <span class="lt-modal-title" id="lt-modal-title">Thêm hoạt động</span>
            <button class="lt-btn-icon" onclick="LT.closeModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="lt-modal-body">
            <div class="lt-form-row">
                <div class="lt-form-group" style="grid-column:1/-1">
                    <label>Tên hoạt động <span class="lt-form-required">*</span></label>
                    <input type="text" id="lt-f-ten" placeholder="VD: Điểm danh khai mạc, Thuyết trình vòng 1..." />
                </div>
            </div>
            <div class="lt-form-row">
                <div class="lt-form-group">
                    <label>Loại hoạt động <span class="lt-form-required">*</span></label>
                    <select id="lt-f-loai">
                        <option value="HOAT_DONG">🎯 Hoạt động</option>
                        <option value="DIEM_DANH">📋 Điểm danh</option>
                        <option value="NGHI">☕ Nghỉ giải lao</option>
                        <option value="KHAC">📌 Khác</option>
                    </select>
                </div>
                <div class="lt-form-group">
                    <label>Địa điểm</label>
                    <input type="text" id="lt-f-diadiem" placeholder="VD: Hội trường A, Phòng 401..." />
                </div>
            </div>
            <div class="lt-form-row">
                <div class="lt-form-group">
                    <label>Thời gian bắt đầu <span class="lt-form-required">*</span></label>
                    <input type="datetime-local" id="lt-f-batdau" />
                </div>
                <div class="lt-form-group">
                    <label id="lt-ketthuc-label">Thời gian kết thúc</label>
                    <input type="datetime-local" id="lt-f-ketthuc" />
                </div>
            </div>

            <!-- GPS Section -->
            <div class="lt-form-group mb-4">
                <label style="display:flex;align-items:center;justify-content:space-between;">
                    <span>📍 Tọa độ GPS địa điểm</span>
                    <button type="button" class="lt-btn lt-btn-outline" style="padding:.3rem .75rem;font-size:.75rem;" onclick="LT.getMyLocation()">
                        <span class="material-symbols-outlined" style="font-size:15px;">my_location</span>
                        Vị trí hiện tại
                    </button>
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.5rem;">
                    <input type="number" id="lt-f-lat" placeholder="Vĩ độ (VD: 10.7769)" step="0.0000001" />
                    <input type="number" id="lt-f-lng" placeholder="Kinh độ (VD: 106.7009)" step="0.0000001" />
                </div>
                <div id="lt-map-picker"></div>
                <p class="lt-map-hint">Nhập tọa độ hoặc nhấn "Vị trí hiện tại", hoặc click thẳng trên bản đồ để đặt marker.</p>
            </div>

            <!-- Optional fields -->
            <details class="mb-2">
                <summary style="cursor:pointer;font-size:.8rem;font-weight:600;color:#7c3aed;user-select:none;">
                    Cài đặt nâng cao (Vòng thi / Tiểu ban)
                </summary>
                <div class="lt-form-row" style="margin-top:.75rem;">
                    <div class="lt-form-group">
                        <label>Vòng thi (không bắt buộc)</label>
                        <select id="lt-f-vongthi">
                            <option value="">— Áp dụng cho toàn sự kiện —</option>
                        </select>
                    </div>
                    <div class="lt-form-group">
                        <label>Tiểu ban (không bắt buộc)</label>
                        <select id="lt-f-tieuban">
                            <option value="">— Áp dụng cho tất cả tiểu ban —</option>
                        </select>
                    </div>
                </div>
            </details>
        </div>
        <div class="lt-modal-footer">
            <button class="lt-btn lt-btn-outline" onclick="LT.closeModal()">Hủy</button>
            <button class="lt-btn lt-btn-primary" onclick="LT.submitForm()">
                <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                <span id="lt-modal-save-text">Lưu hoạt động</span>
            </button>
        </div>
    </div>
</div>

<!-- ── Modal xem danh sách điểm danh ── -->
<div id="lt-att-modal" class="lt-modal-backdrop" onclick="if(event.target===this)LT.closeAttModal()">
    <div class="lt-modal">
        <div class="lt-modal-header">
            <span class="lt-modal-title">Danh sách điểm danh</span>
            <button class="lt-btn-icon" onclick="LT.closeAttModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="lt-modal-body" id="lt-att-body">
            <div class="lt-skeleton h-8 w-full mb-2"></div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="lt-toast"></div>

<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<!-- qrcode.js for QR generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    /* ════════════════════════════════════════════════════════════
   LT — Lịch Trình module (BTC view)
   Phụ thuộc: window.EVENT_DETAIL_ID, window.APP_BASE_PATH
════════════════════════════════════════════════════════════ */
    const LT = (() => {
        const BASE = window.APP_BASE_PATH || '';
        const ID_SK = window.EVENT_DETAIL_ID || 0;

        let _items = []; // current schedule data
        let _booted = false; // guard: prevent double load()
        let _editId = null; // null = create mode
        let _map = null; // Leaflet map instance
        let _marker = null; // Leaflet marker
        let _dragSrc = null; // drag source element

        // ── API helpers ─────────────────────────────────────────
        async function api(endpoint, method = 'GET', body = null) {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch(BASE + endpoint, opts);
            return res.json();
        }

        // ── Toast ────────────────────────────────────────────────
        function toast(msg, type = 'success') {
            const el = document.createElement('div');
            el.className = `lt-toast-msg lt-toast-${type}`;
            const icon = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';
            el.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">${icon}</span>${msg}`;
            document.getElementById('lt-toast').appendChild(el);
            setTimeout(() => el.remove(), 3500);
        }

        // ── Load data ─────────────────────────────────────────────
        async function load() {
            if (_booted) return;
            _booted = true;
            document.getElementById('lt-loading').classList.remove('hidden');
            document.getElementById('lt-timeline').innerHTML = '';
            document.getElementById('lt-error').classList.add('hidden');
            document.getElementById('lt-empty').classList.add('hidden');

            try {
                const res = await api(`/api/su_kien/lay_lich_trinh.php?id_sk=${ID_SK}`);
                _items = res.data || [];
                document.getElementById('lt-loading').classList.add('hidden');

                if (_items.length === 0) {
                    document.getElementById('lt-empty').classList.remove('hidden');
                } else {
                    renderTimeline();
                }
            } catch (e) {
                document.getElementById('lt-loading').classList.add('hidden');
                const errEl = document.getElementById('lt-error');
                errEl.textContent = 'Không thể tải dữ liệu lịch trình. Vui lòng thử lại.';
                errEl.classList.remove('hidden');
            }
        }

        // ── Render timeline ──────────────────────────────────────
        function renderTimeline() {
            const container = document.getElementById('lt-timeline');
            container.innerHTML = '';

            _items.forEach(item => {
                const el = buildItem(item);
                container.appendChild(el);
            });

            initDragDrop();
        }

        function buildItem(item) {
            const div = document.createElement('div');
            div.className = 'lt-item';
            div.dataset.id = item.idLichTrinh;

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
            const isDiemDanh = item.loaiHoatDong === 'DIEM_DANH';

            div.innerHTML = `
            <div class="lt-item-header">
                <span class="material-symbols-outlined lt-drag-handle" title="Kéo để sắp xếp">drag_indicator</span>
                <span class="lt-time-badge">${escHtml(timeStr)}</span>
                <span class="lt-type-badge lt-type-${item.loaiHoatDong}">${loaiMap[item.loaiHoatDong] || item.loaiHoatDong}</span>
                <span class="lt-item-name">${escHtml(item.tenHoatDong)}</span>
                ${item.diaDiem ? `<span class="lt-item-place">📍 ${escHtml(item.diaDiem)}</span>` : ''}
                <div class="lt-item-actions">
                    ${isDiemDanh ? `<button class="lt-btn-icon" title="Quản lý điểm danh" onclick="LT.toggleExpand(${item.idLichTrinh})">
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>` : ''}
                    <button class="lt-btn-icon" title="Chỉnh sửa" onclick="LT.openModal(${item.idLichTrinh})">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="lt-btn-icon danger" title="Xóa" onclick="LT.deleteItem(${item.idLichTrinh})">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            ${isDiemDanh ? buildExpandPanel(item) : ''}
        `;

            return div;
        }

        function buildExpandPanel(item) {
            const phien = item.phien;
            let phienHtml = '';

            if (!phien) {
                phienHtml = `
                <div class="lt-phien-empty">
                    <span class="material-symbols-outlined text-3xl" style="color:#ddd6fe">event_busy</span>
                    <p class="text-sm text-slate-500 mt-2 mb-3">Chưa có phiên điểm danh nào.</p>
                    <button class="lt-btn lt-btn-primary" onclick="LT.createPhien(${item.idLichTrinh})">
                        <span class="material-symbols-outlined" style="font-size:16px">add_circle</span>
                        Tạo phiên điểm danh
                    </button>
                </div>`;
            } else {
                const ts = phien.trangThai;
                const stats = phien.stats || {};
                const badgeClass = `phien-badge phien-${ts}`;
                const tsLabel = ts === 'CHUAN_BI' ? '⏳ Chuẩn bị' : ts === 'DANG_MO' ? '🟢 Đang mở' : '🔒 Đã đóng';

                const statStr = ts !== 'CHUAN_BI' ?
                    `<span style="font-size:.78rem;color:#64748b;">${stats.total || 0} người đã điểm danh
                    (${stats.chinh_thuc || 0} chính thức · ${stats.khan_gia || 0} khán giả)</span>` :
                    '';

                let buttons = '';
                if (ts === 'CHUAN_BI') {
                    buttons = `<button class="lt-btn lt-btn-success" onclick="LT.moPhien(${phien.idPhienDD}, ${item.idSK || ID_SK})">
                    <span class="material-symbols-outlined" style="font-size:16px">play_circle</span> Mở phiên
                </button>`;
                } else if (ts === 'DANG_MO') {
                    buttons = `
                    <button class="lt-btn lt-btn-outline" onclick="LT.showQR('${phien.tokenQR || ''}', ${phien.idPhienDD})">
                        <span class="material-symbols-outlined" style="font-size:16px">qr_code</span> Xem QR
                    </button>
                    <button class="lt-btn lt-btn-danger" onclick="LT.dongPhien(${phien.idPhienDD}, ${ID_SK})">
                        <span class="material-symbols-outlined" style="font-size:16px">stop_circle</span> Đóng phiên
                    </button>`;
                } else {
                    buttons = `
                    <button class="lt-btn lt-btn-warning" onclick="LT.moLaiPhien(${phien.idPhienDD}, ${ID_SK})">
                        <span class="material-symbols-outlined" style="font-size:16px">restart_alt</span> Mở lại
                    </button>`;
                }

                // BTC điểm danh thủ công
                const thuCongSection = ts === 'DANG_MO' ? `
                <div style="margin-top:.75rem;border-top:1px solid #f0eaf8;padding-top:.75rem;">
                    <p style="font-size:.78rem;font-weight:600;color:#7c3aed;margin-bottom:.4rem;">Điểm danh thủ công</p>
                    <div class="lt-search-box" style="position:relative">
                        <span class="material-symbols-outlined lt-search-icon">search</span>
                        <input type="text" placeholder="Tìm theo tên hoặc tên tài khoản..."
                            id="lt-search-${phien.idPhienDD}"
                            oninput="LT.searchUser(this, ${phien.idPhienDD})" autocomplete="off" />
                        <div class="lt-search-results" id="lt-sr-${phien.idPhienDD}"></div>
                    </div>
                </div>` : '';

                const viewBtn = ts !== 'CHUAN_BI' ?
                    `<button class="lt-btn lt-btn-outline" onclick="LT.viewAttendance(${phien.idPhienDD})">
                    <span class="material-symbols-outlined" style="font-size:16px">list</span> Xem danh sách
                  </button>` : '';

                phienHtml = `
                <div class="lt-phien-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;">
                        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                            <span class="${badgeClass}">${tsLabel}</span>
                            ${statStr}
                        </div>
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">${buttons}${viewBtn}</div>
                    </div>
                    ${phien.banKinh ? `<p style="font-size:.75rem;color:#94a3b8;margin-bottom:0">GPS bán kính: ${phien.banKinh}m</p>` : ''}
                    ${thuCongSection}
                </div>`;
            }

            return `<div class="lt-expand-panel" id="lt-panel-${item.idLichTrinh}">${phienHtml}</div>`;
        }

        // ── Toggle expand ────────────────────────────────────────
        function toggleExpand(id) {
            const panel = document.getElementById(`lt-panel-${id}`);
            if (!panel) return;
            panel.classList.toggle('open');
            // Update expand button icon
            const btn = document.querySelector(`[data-id="${id}"] .lt-btn-icon[title="Quản lý điểm danh"] .material-symbols-outlined`);
            if (btn) btn.textContent = panel.classList.contains('open') ? 'expand_less' : 'expand_more';
        }

        // ── Modal ────────────────────────────────────────────────
        function openModal(id) {
            _editId = id;
            document.getElementById('lt-modal-title').textContent = id ? 'Chỉnh sửa hoạt động' : 'Thêm hoạt động';
            document.getElementById('lt-modal-save-text').textContent = id ? 'Lưu thay đổi' : 'Lưu hoạt động';

            // Reset form
            ['lt-f-ten', 'lt-f-diadiem'].forEach(f => document.getElementById(f).value = '');
            document.getElementById('lt-f-loai').value = 'HOAT_DONG';
            document.getElementById('lt-f-batdau').value = '';
            document.getElementById('lt-f-ketthuc').value = '';
            document.getElementById('lt-f-lat').value = '';
            document.getElementById('lt-f-lng').value = '';

            if (id) {
                const item = _items.find(i => i.idLichTrinh == id);
                if (item) populateForm(item);
            }

            document.getElementById('lt-modal').classList.add('open');
            setTimeout(() => {
                if (!_map) initMap();
                else _map.invalidateSize();
            }, 250);

            // Loại điểm danh → bắt buộc kết thúc
            document.getElementById('lt-f-loai').onchange = function() {
                const lbl = document.getElementById('lt-ketthuc-label');
                lbl.innerHTML = this.value === 'DIEM_DANH' ?
                    'Thời gian kết thúc <span class="lt-form-required">*</span>' :
                    'Thời gian kết thúc';
            };
        }

        function populateForm(item) {
            document.getElementById('lt-f-ten').value = item.tenHoatDong || '';
            document.getElementById('lt-f-loai').value = item.loaiHoatDong || 'HOAT_DONG';
            document.getElementById('lt-f-diadiem').value = item.diaDiem || '';
            if (item.thoiGianBatDau)
                document.getElementById('lt-f-batdau').value = toDatetimeLocal(item.thoiGianBatDau);
            if (item.thoiGianKetThuc)
                document.getElementById('lt-f-ketthuc').value = toDatetimeLocal(item.thoiGianKetThuc);
            if (item.viTriLat) {
                document.getElementById('lt-f-lat').value = item.viTriLat;
                document.getElementById('lt-f-lng').value = item.viTriLng;
            }
        }

        function closeModal() {
            document.getElementById('lt-modal').classList.remove('open');
        }

        async function submitForm() {
            const ten = document.getElementById('lt-f-ten').value.trim();
            const loai = document.getElementById('lt-f-loai').value;
            const batDau = document.getElementById('lt-f-batdau').value;
            const ketThuc = document.getElementById('lt-f-ketthuc').value;

            if (!ten || !batDau) {
                toast('Vui lòng điền tên và thời gian bắt đầu', 'error');
                return;
            }
            if (loai === 'DIEM_DANH' && !ketThuc) {
                toast('Hoạt động điểm danh cần có thời gian kết thúc', 'error');
                return;
            }

            const payload = {
                id_sk: ID_SK,
                ten_hoat_dong: ten,
                loai_hoat_dong: loai,
                thoi_gian_bat_dau: batDau,
                thoi_gian_ket_thuc: ketThuc || null,
                dia_diem: document.getElementById('lt-f-diadiem').value.trim() || null,
                vi_tri_lat: document.getElementById('lt-f-lat').value || null,
                vi_tri_lng: document.getElementById('lt-f-lng').value || null,
            };

            if (_editId) {
                payload.id_lich_trinh = _editId;
            }

            const endpoint = _editId ?
                '/api/su_kien/cap_nhat_lich_trinh.php' :
                '/api/su_kien/tao_lich_trinh.php';

            try {
                const res = await api(endpoint, 'POST', payload);
                if (res.status === 'success') {
                    toast(_editId ? 'Đã cập nhật hoạt động' : 'Đã thêm hoạt động');
                    closeModal();
                    load();
                } else {
                    toast(res.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        async function deleteItem(id) {
            if (!confirm('Xóa hoạt động này? Hành động không thể hoàn tác.')) return;
            try {
                const res = await api('/api/su_kien/xoa_lich_trinh.php', 'POST', {
                    id_sk: ID_SK,
                    id_lich_trinh: id
                });
                if (res.status === 'success') {
                    toast('Đã xóa hoạt động');
                    load();
                } else {
                    toast(res.message || 'Không thể xóa', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        // ── Phiên điểm danh ─────────────────────────────────────
        async function createPhien(idLichTrinh) {
            try {
                const res = await api('/api/su_kien/tao_phien_diemdanh.php', 'POST', {
                    id_sk: ID_SK,
                    id_lich_trinh: idLichTrinh,
                });
                if (res.status === 'success') {
                    toast('Đã tạo phiên điểm danh');
                    load();
                } else {
                    toast(res.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        async function moPhien(idPhienDD, idSk) {
            try {
                const res = await api('/api/su_kien/mo_phien_diemdanh.php', 'POST', {
                    id_sk: idSk,
                    id_phien_dd: idPhienDD
                });
                if (res.status === 'success') {
                    toast('✅ Phiên điểm danh đã mở!');
                    load();
                } else {
                    toast(res.message || 'Lỗi', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        async function moLaiPhien(idPhienDD, idSk) {
            try {
                const res = await api('/api/su_kien/mo_phien_diemdanh.php', 'POST', {
                    id_sk: idSk,
                    id_phien_dd: idPhienDD
                });
                if (res.status === 'success') {
                    toast('✅ Đã mở lại phiên điểm danh!');
                    // QR cũ hết hiệu lực sau khi mở lại - cảnh báo BTC
                    setTimeout(() => toast('⚠️ QR code đã được làm mới. Hãy chia sẻ QR mới với người tham dự.', 'info'), 600);
                    load();
                } else {
                    toast(res.message || 'Lỗi', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        async function dongPhien(idPhienDD, idSk) {
            if (!confirm('Đóng phiên điểm danh? Mọi người sẽ không điểm danh được nữa.')) return;
            try {
                const res = await api('/api/su_kien/dong_phien_diemdanh.php', 'POST', {
                    id_sk: idSk,
                    id_phien_dd: idPhienDD
                });
                if (res.status === 'success') {
                    toast('Đã đóng phiên điểm danh');
                    load();
                } else {
                    toast(res.message || 'Lỗi', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }


        // ── QR Code ──────────────────────────────────────────────
        function showQR(token, idPhienDD) {
            if (!token) {
                toast('Không có token QR', 'error');
                return;
            }
            const url = window.location.origin + '/diem-danh?token=' + encodeURIComponent(token) + '&id_phien_dd=' + idPhienDD;

            const modal = document.createElement('div');
            modal.className = 'lt-modal-backdrop open';
            modal.innerHTML = `
            <div class="lt-modal" style="max-width:380px">
                <div class="lt-modal-header">
                    <span class="lt-modal-title">QR Code điểm danh</span>
                    <button class="lt-btn-icon" onclick="this.closest('.lt-modal-backdrop').remove()">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="lt-modal-body text-center">
                    <p class="text-xs text-slate-400 mb-3">Chia sẻ QR này để mọi người điểm danh</p>
                    <div id="qr-gen-${idPhienDD}" style="display:inline-block"></div>
                    <p class="text-xs text-slate-500 mt-3 break-all">${escHtml(url)}</p>
                    <button class="lt-btn lt-btn-outline mt-3" onclick="navigator.clipboard.writeText('${url.replace(/'/g,"\\'")}').then(()=>LT._toast('Đã sao chép link','info'))">
                        <span class="material-symbols-outlined" style="font-size:16px">content_copy</span> Sao chép link
                    </button>
                </div>
            </div>`;
            document.body.appendChild(modal);
            modal.onclick = e => {
                if (e.target === modal) modal.remove();
            };

            setTimeout(() => {
                if (typeof QRCode !== 'undefined') {
                    new QRCode(document.getElementById(`qr-gen-${idPhienDD}`), {
                        text: url,
                        width: 200,
                        height: 200,
                        colorDark: '#7c3aed',
                        colorLight: '#ffffff',
                    });
                } else {
                    document.getElementById(`qr-gen-${idPhienDD}`).innerHTML =
                        `<p class="text-sm text-slate-500">Không thể tạo QR. Link: <a href="${url}" target="_blank" class="text-purple-600 underline">Mở</a></p>`;
                }
            }, 100);
        }

        // ── Xem danh sách điểm danh ──────────────────────────────
        async function viewAttendance(idPhienDD) {
            document.getElementById('lt-att-modal').classList.add('open');
            const body = document.getElementById('lt-att-body');
            body.innerHTML = '<div class="lt-skeleton h-8 w-full mb-2"></div><div class="lt-skeleton h-8 w-full mb-2"></div>';

            try {
                const res = await api(`/api/su_kien/lay_diemdanh.php?id_sk=${ID_SK}&id_phien_dd=${idPhienDD}`);
                const list = res.data || [];
                if (list.length === 0) {
                    body.innerHTML = '<p class="text-center text-slate-400 py-8 text-sm">Chưa có ai điểm danh.</p>';
                    return;
                }
                body.innerHTML = `
                <p class="text-sm text-slate-500 mb-3">${list.length} lượt điểm danh</p>
                <table class="lt-att-table">
                    <thead><tr>
                        <th>#</th><th>Tên</th><th>Tài khoản</th><th>Phương thức</th>
                        <th>Thời gian</th><th>Có mặt</th><th>Loại</th>
                    </tr></thead>
                    <tbody>
                        ${list.map((r,i) => `
                        <tr>
                            <td>${i+1}</td>
                            <td>${escHtml(r.tenSV || r.tenGV || '—')}</td>
                            <td>${escHtml(r.tenTK || '—')}</td>
                            <td>${r.phuongThuc}</td>
                            <td>${r.thoiGianDiemDanh ? new Date(r.thoiGianDiemDanh).toLocaleTimeString('vi-VN') : '—'}</td>
                            <td>${r.hienDien == 1 ? '✅' : '❌'}</td>
                            <td>${r.la_chinh_thuc == 1 ? '<span style="color:#7c3aed;font-weight:600">Chính thức</span>' : 'Khán giả'}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>`;
            } catch (e) {
                body.innerHTML = '<p class="text-rose-500 text-sm">Không thể tải dữ liệu.</p>';
            }
        }

        function closeAttModal() {
            document.getElementById('lt-att-modal').classList.remove('open');
        }

        // ── Search user (BTC điểm hộ) ────────────────────────────
        let _searchTimer = null;

        function searchUser(input, idPhienDD) {
            clearTimeout(_searchTimer);
            const q = input.value.trim();
            const resultsEl = document.getElementById(`lt-sr-${idPhienDD}`);
            if (!q || q.length < 2) {
                resultsEl.classList.remove('open');
                return;
            }

            _searchTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${BASE}/api/nhom/tim_kiem_user.php?q=${encodeURIComponent(q)}&id_sk=${ID_SK}&loai=sv`);
                    const data = await res.json();
                    const users = data.data || [];

                    if (!users.length) {
                        resultsEl.innerHTML = '<div class="lt-search-item" style="color:#94a3b8">Không tìm thấy</div>';
                    } else {
                        resultsEl.innerHTML = users.slice(0, 8).map(u => `
                        <div class="lt-search-item" onclick="LT.diemDanhThuCong(${u.idTK || u.id},'${escAttr(u.tenSV || u.tenGV || u.tenTK)}',${idPhienDD},this.closest('.lt-search-box').querySelector('input'))">
                            <span class="material-symbols-outlined" style="font-size:16px;color:#a78bfa">person</span>
                            <div>
                                <div style="font-weight:600">${escHtml(u.tenSV || u.tenGV || u.tenTK)}</div>
                                <div style="font-size:.73rem;color:#94a3b8">${escHtml(u.tenTK || '')} · ${u.maSV || u.maGV || ''}</div>
                            </div>
                        </div>`).join('');
                    }
                    resultsEl.classList.add('open');
                } catch (e) {}
            }, 300);
        }

        async function diemDanhThuCong(idTk, tenNguoi, idPhienDD, inputEl) {
            inputEl.closest('.lt-search-box').querySelector('.lt-search-results').classList.remove('open');
            inputEl.value = '';
            try {
                const res = await api('/api/su_kien/ghi_nhan_diemdanh.php', 'POST', {
                    id_sk: ID_SK,
                    id_phien_dd: idPhienDD,
                    id_tk_sv: idTk,
                    phuong_thuc: 'THU_CONG',
                    hien_dien: 1,
                });
                if (res.status === 'success') {
                    toast(`✅ Đã điểm danh: ${tenNguoi}`);
                    load();
                } else {
                    toast(res.message || 'Lỗi', 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối', 'error');
            }
        }

        // ── Drag & Drop reorder ──────────────────────────────────
        function initDragDrop() {
            const items = document.querySelectorAll('.lt-item');
            items.forEach(item => {
                item.draggable = true;
                item.addEventListener('dragstart', e => {
                    _dragSrc = item;
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    document.querySelectorAll('.lt-item').forEach(i => i.classList.remove('drag-over'));
                    saveOrder();
                });
                item.addEventListener('dragover', e => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    if (_dragSrc !== item) item.classList.add('drag-over');
                });
                item.addEventListener('dragleave', () => item.classList.remove('drag-over'));
                item.addEventListener('drop', e => {
                    e.preventDefault();
                    item.classList.remove('drag-over');
                    if (_dragSrc && _dragSrc !== item) {
                        const container = item.parentNode;
                        const allItems = [...container.querySelectorAll('.lt-item')];
                        const srcIdx = allItems.indexOf(_dragSrc);
                        const tgtIdx = allItems.indexOf(item);
                        if (srcIdx < tgtIdx) {
                            container.insertBefore(_dragSrc, item.nextSibling);
                        } else {
                            container.insertBefore(_dragSrc, item);
                        }
                    }
                });
            });
        }

        async function saveOrder() {
            const items = [...document.querySelectorAll('.lt-item')];
            const payload = {
                id_sk: ID_SK,
                items: items.map((el, i) => ({
                    id_lich_trinh: parseInt(el.dataset.id),
                    thu_tu: i + 1
                }))
            };
            try {
                await api('/api/su_kien/sap_xep_lich_trinh.php', 'POST', payload);
            } catch (e) {
                /* silent */ }
        }

        // ── Leaflet map ──────────────────────────────────────────
        function initMap() {
            const mapEl = document.getElementById('lt-map-picker');
            if (!mapEl || _map) return;

            const defaultLat = 10.7769,
                defaultLng = 106.7009; // HCMC
            _map = L.map('lt-map-picker').setView([defaultLat, defaultLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(_map);

            _map.on('click', e => {
                setMarker(e.latlng.lat, e.latlng.lng);
            });

            // Nếu đã có tọa độ → đặt marker
            const lat = parseFloat(document.getElementById('lt-f-lat').value);
            const lng = parseFloat(document.getElementById('lt-f-lng').value);
            if (lat && lng) {
                setMarker(lat, lng);
                _map.setView([lat, lng], 17);
            }

            // Sync input → map
            ['lt-f-lat', 'lt-f-lng'].forEach(id => {
                document.getElementById(id).addEventListener('change', () => {
                    const la = parseFloat(document.getElementById('lt-f-lat').value);
                    const ln = parseFloat(document.getElementById('lt-f-lng').value);
                    if (la && ln) {
                        setMarker(la, ln);
                        _map.setView([la, ln], 17);
                    }
                });
            });
        }

        function setMarker(lat, lng) {
            if (_marker) _map.removeLayer(_marker);
            _marker = L.marker([lat, lng], {
                    draggable: true
                })
                .addTo(_map)
                .bindPopup(`📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}`)
                .openPopup();
            document.getElementById('lt-f-lat').value = lat.toFixed(7);
            document.getElementById('lt-f-lng').value = lng.toFixed(7);
            _marker.on('dragend', e => {
                const p = e.target.getLatLng();
                setMarker(p.lat, p.lng);
            });
        }

        function getMyLocation() {
            if (!navigator.geolocation) {
                toast('Trình duyệt không hỗ trợ geolocation', 'error');
                return;
            }
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude,
                    lng = pos.coords.longitude;
                document.getElementById('lt-f-lat').value = lat.toFixed(7);
                document.getElementById('lt-f-lng').value = lng.toFixed(7);
                if (!_map) initMap();
                else {
                    setMarker(lat, lng);
                    _map.setView([lat, lng], 17);
                }
                toast('Đã lấy vị trí hiện tại', 'info');
            }, () => toast('Không lấy được vị trí. Hãy cho phép truy cập GPS.', 'error'));
        }

        // ── Utils ────────────────────────────────────────────────
        function toDatetimeLocal(dbStr) {
            if (!dbStr) return '';
            return dbStr.replace(' ', 'T').substring(0, 16);
        }

        function escHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function escAttr(str) {
            return escHtml(str);
        }

        // expose for inline onclick
        return {
            load,
            openModal,
            closeModal,
            submitForm,
            deleteItem,
            toggleExpand,
            createPhien,
            moPhien,
            dongPhien,
            moLaiPhien,
            showQR,
            viewAttendance,
            closeAttModal,
            searchUser,
            diemDanhThuCong,
            getMyLocation,
            _toast: toast,
        };
    })();

    // ── Boot ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (window.EVENT_DETAIL_TAB === 'lichtrinh') LT.load();
    });
    // Also fire if DOM already ready (inline partial load)
    if (document.readyState !== 'loading') {
        if (window.EVENT_DETAIL_TAB === 'lichtrinh') LT.load();
    }
</script>