<?php
/**
 * Partial: Tab Kết quả Review
 * Biến cần có: $idSk, $tab
 * Hiển thị thống kê tổng quan + bảng vàng xếp hạng theo vòng thi.
 * Reuse API: xet_ket_qua.php (thong_ke_ket_qua, bang_xep_hang)
 */
?>

<!-- Chọn vòng thi -->
<div class="mb-4 p-4 border rounded-xl border-slate-200 bg-slate-50">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block mb-1 text-xs font-semibold text-slate-600">Chọn vòng thi</label>
            <select id="rrVongThiSelect"
                class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-purple-500 focus:outline-none">
                <option value="">-- Chọn vòng thi --</option>
            </select>
        </div>
        <div class="flex-shrink-0 self-end">
            <button id="rrBtnExport"
                class="px-4 py-2 text-xs font-semibold text-amber-700 bg-white border border-amber-300 rounded-lg hover:bg-amber-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                <i class="fas fa-file-export mr-1"></i>Xuất Excel
            </button>
        </div>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
    <div class="p-4 border rounded-xl border-slate-200 bg-white">
        <p class="text-xs font-bold uppercase text-slate-400">Tổng bài nộp</p>
        <p id="rrStatTong" class="mb-0 text-2xl font-bold text-slate-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
        <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt</p>
        <p id="rrStatDaDuyet" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-rose-200 bg-gradient-to-br from-rose-50 to-red-50">
        <p class="text-xs font-bold uppercase text-rose-600">Bị loại</p>
        <p id="rrStatBiLoai" class="mb-0 text-2xl font-bold text-rose-700">--</p>
    </div>
    <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
        <p class="text-xs font-bold uppercase text-amber-600">Sẵn sàng duyệt</p>
        <p id="rrStatChoduyet" class="mb-0 text-2xl font-bold text-amber-700">--</p>
    </div>
</div>

<!-- Bảng vàng -->
<div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50/40 to-yellow-50/40">
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="mb-0 text-sm font-bold text-amber-700">
                <i class="fas fa-trophy mr-2 text-amber-500"></i>Bảng vàng — Xếp hạng
            </p>
            <p class="mb-0 text-xs text-amber-600">Các bài đã được BTC duyệt, sắp xếp theo điểm giảm dần</p>
        </div>
    </div>

    <div id="rrBangVang" class="space-y-2">
        <div class="px-4 py-10 text-center text-slate-400">
            <i class="fas fa-hand-pointer text-2xl mb-2"></i>
            <p class="text-sm">Chọn vòng thi để xem kết quả</p>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE      = window.APP_BASE_PATH || '';
    const ID_SK     = <?php echo (int) $idSk; ?>;
    const API_VT    = `${BASE}/api/su_kien/danh_sach_vong_thi.php`;
    const API_KQ    = `${BASE}/api/cham_diem/xet_ket_qua.php`;

    const sel        = document.getElementById('rrVongThiSelect');
    const btnExport  = document.getElementById('rrBtnExport');
    const elTong     = document.getElementById('rrStatTong');
    const elDaDuyet  = document.getElementById('rrStatDaDuyet');
    const elBiLoai   = document.getElementById('rrStatBiLoai');
    const elChoDuyet = document.getElementById('rrStatChoduyet');
    const elBangVang = document.getElementById('rrBangVang');

    // ── Load danh sách vòng thi ───────────────────────────────────
    async function loadVongThi() {
        try {
            const res = await fetch(`${API_VT}?id_sk=${ID_SK}`, { credentials: 'same-origin' })
                .then(r => r.json());
            const list = res.data || res || [];
            list.forEach(v => {
                const opt = document.createElement('option');
                opt.value       = v.idVongThi;
                opt.textContent = v.tenVongThi;
                sel.appendChild(opt);
            });
        } catch (e) {
            console.error('Lỗi load vòng thi:', e);
        }
    }

    // ── Load thống kê + bảng vàng song song ──────────────────────
    async function loadKetQua(idVongThi) {
        setStats('…', '…', '…', '…');
        elBangVang.innerHTML = `
            <div class="px-4 py-8 text-center text-slate-400">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p class="text-sm">Đang tải...</p>
            </div>`;
        btnExport.disabled = true;

        try {
            const [resThongKe, resBangVang] = await Promise.all([
                fetch(`${API_KQ}?action=thong_ke_ket_qua&id_sk=${ID_SK}&id_vong_thi=${idVongThi}`, { credentials: 'same-origin' }).then(r => r.json()),
                fetch(`${API_KQ}?action=bang_xep_hang&id_sk=${ID_SK}&id_vong_thi=${idVongThi}`,   { credentials: 'same-origin' }).then(r => r.json()),
            ]);

            if (resThongKe.status === 'success' && resThongKe.data) {
                const d = resThongKe.data;
                setStats(d.tongSanPham ?? '--', d.daDuyet ?? '--', d.biLoai ?? '--', d.sanSangDuyet ?? '--');
            }

            if (resBangVang.status === 'success') {
                renderBangVang(resBangVang.data || []);
                btnExport.disabled = false;
            } else {
                throw new Error(resBangVang.message || 'Lỗi tải bảng vàng');
            }
        } catch (e) {
            console.error('Lỗi load kết quả:', e);
            elBangVang.innerHTML = `
                <div class="p-4 border rounded-lg border-rose-200 bg-rose-50 text-rose-600 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>${e.message || 'Không thể tải dữ liệu'}
                </div>`;
        }
    }

    function setStats(tong, daDuyet, biLoai, choDuyet) {
        elTong.textContent     = tong;
        elDaDuyet.textContent  = daDuyet;
        elBiLoai.textContent   = biLoai;
        elChoDuyet.textContent = choDuyet;
    }

    function renderBangVang(data) {
        if (!data.length) {
            elBangVang.innerHTML = `
                <div class="px-4 py-10 text-center text-amber-400">
                    <i class="fas fa-medal text-3xl mb-2"></i>
                    <p class="text-sm">Chưa có bài nào được duyệt trong vòng thi này</p>
                </div>`;
            return;
        }

        const medals = ['🥇', '🥈', '🥉'];
        elBangVang.innerHTML = data.map((item, i) => {
            const isTop3     = i < 3;
            const cardClass  = isTop3 ? 'border-amber-300 bg-gradient-to-r from-amber-50 to-yellow-50' : 'border-slate-200 bg-white';
            const rankClass  = isTop3 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600';
            const scoreClass = isTop3 ? 'text-amber-600' : 'text-slate-700';
            const diem       = item.diemTrungBinh !== null ? parseFloat(item.diemTrungBinh).toFixed(2) : '--';
            const thanhVien  = item.thanhVien
                ? `<p class="text-xs text-slate-400 mt-0.5 truncate">${esc(item.thanhVien)}</p>` : '';

            return `
            <div class="p-3 border rounded-lg ${cardClass} hover:shadow-sm transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full ${rankClass} font-bold text-sm">
                        ${medals[i] || item.xepHang}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-700 truncate">${esc(item.tensanpham || 'N/A')}</p>
                        <p class="text-xs text-slate-500">${esc(item.tennhom || item.manhom || 'N/A')}</p>
                        ${thanhVien}
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xl font-bold ${scoreClass}">${diem}</p>
                        <p class="text-xs text-slate-400">điểm</p>
                        ${item.xepLoai ? `<span class="text-xs font-semibold text-emerald-600">${esc(item.xepLoai)}</span>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Event listeners ───────────────────────────────────────────
    sel.addEventListener('change', function () {
        if (!this.value) {
            setStats('--', '--', '--', '--');
            btnExport.disabled = true;
            elBangVang.innerHTML = `
                <div class="px-4 py-10 text-center text-slate-400">
                    <i class="fas fa-hand-pointer text-2xl mb-2"></i>
                    <p class="text-sm">Chọn vòng thi để xem kết quả</p>
                </div>`;
            return;
        }
        loadKetQua(this.value);
    });

    btnExport.addEventListener('click', function () {
        if (!sel.value || this.disabled) return;
        window.open(`${API_KQ}?action=export_ranking&id_sk=${ID_SK}&id_vong_thi=${sel.value}`, '_blank');
    });

    // Init
    loadVongThi();
})();
</script>
