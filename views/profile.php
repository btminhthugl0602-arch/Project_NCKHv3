<?php

/**
 * Hồ sơ cá nhân
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['idTK'])) {
    header('Location: /sign-in?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle   = "Hồ sơ cá nhân - ezManagement";
$currentPage = "profile";
$pageHeading = "Hồ sơ cá nhân";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/dashboard'],
    ['title' => 'Hồ sơ cá nhân'],
];

$idTK     = (int) $_SESSION['idTK'];
$idLoaiTK = (int) $_SESSION['idLoaiTK'];

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-wrap gap-6">

        <!-- ─── CỘT TRÁI: Thông tin cá nhân ─── -->
        <div class="w-full lg:w-5/12 flex-none">
            <div class="bg-white rounded-2xl shadow-soft-xl p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="inline-flex items-center justify-center rounded-xl bg-primary/10"
                        style="width:56px;height:56px;">
                        <span class="material-symbols-outlined active-icon text-primary"
                            style="font-size:1.6rem">person</span>
                    </div>
                    <div>
                        <h6 class="mb-0 font-bold text-slate-800" id="displayHoTen">...</h6>
                        <p class="mb-0 text-xs text-slate-400" id="displayLoaiTK">...</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Tên đăng
                            nhập</label>
                        <input id="tenTK" type="text" disabled
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Họ và
                            tên</label>
                        <input id="hoTen" type="text"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10">
                    </div>

                    <!-- Chỉ hiện với Sinh viên -->
                    <div id="fieldSinhVien" class="hidden space-y-4">
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Mã
                                sinh viên</label>
                            <input id="maSV" type="text" disabled
                                class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label
                                    class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">GPA</label>
                                <input id="gpa" type="text" disabled
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                            </div>
                            <div class="flex-1">
                                <label
                                    class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">DRL</label>
                                <input id="drl" type="text" disabled
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                            </div>
                        </div>
                        <div>
                            <label
                                class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Lớp</label>
                            <input id="tenLop" type="text" disabled
                                class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Chỉ hiện với Giảng viên -->
                    <div id="fieldGiangVien" class="hidden">
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Học hàm /
                            Học vị</label>
                        <select id="hocHam"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10">
                            <option value="">-- Chưa cập nhật --</option>
                            <option value="Cu_nhan">Cử nhân</option>
                            <option value="Tha_si">Thạc sĩ</option>
                            <option value="Tien_si">Tiến sĩ</option>
                            <option value="Pho_giao_su">Phó giáo sư</option>
                            <option value="Giao_su">Giáo sư</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Khoa</label>
                        <input id="tenKhoa" type="text" disabled
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed">
                    </div>

                    <button onclick="luuThongTin()"
                        class="w-full py-2.5 rounded-lg text-sm font-semibold text-white bg-primary hover:opacity-90 transition-opacity">
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── CỘT PHẢI: Đổi mật khẩu + Sự kiện ─── -->
        <div class="flex-1 min-w-0 space-y-6">

            <!-- Đổi mật khẩu -->
            <div class="bg-white rounded-2xl shadow-soft-xl p-6">
                <h6 class="mb-4 font-bold text-slate-800">Đổi mật khẩu</h6>
                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Mật khẩu
                            hiện tại</label>
                        <input id="matKhauCu" type="password"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10"
                            placeholder="Nhập mật khẩu hiện tại">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Mật khẩu
                            mới</label>
                        <input id="matKhauMoi" type="password"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10"
                            placeholder="Ít nhất 6 ký tự">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide">Xác nhận
                            mật khẩu mới</label>
                        <input id="xacNhanMatKhau" type="password"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10"
                            placeholder="Nhập lại mật khẩu mới">
                    </div>
                    <button onclick="doiMatKhau()"
                        class="w-full py-2.5 rounded-lg text-sm font-semibold text-white bg-primary hover:opacity-90 transition-opacity">
                        Đổi mật khẩu
                    </button>
                </div>
            </div>

            <!-- Danh sách sự kiện -->
            <div class="bg-white rounded-2xl shadow-soft-xl p-6">
                <h6 class="mb-4 font-bold text-slate-800">Sự kiện đang tham gia</h6>
                <div id="dsSuKien">
                    <p class="text-sm text-slate-400">Đang tải...</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const ID_TK = <?= $idTK ?>;
    const ID_LOAI = <?= $idLoaiTK ?>;

    function showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(message);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadThongTin();
        loadSuKien();
    });

    async function loadThongTin() {
        try {
            const res = await fetch(`/api/tai_khoan/chi_tiet.php?id=${ID_TK}`);
            const json = await res.json();
            if (json.status !== 'success') return showToast(json.message, 'error');

            const d = json.data;
            document.getElementById('displayHoTen').textContent = d.hoTen || d.tenTK;
            document.getElementById('displayLoaiTK').textContent = loaiTKLabel(ID_LOAI);
            document.getElementById('tenTK').value = d.tenTK || '';
            document.getElementById('hoTen').value = d.hoTen || '';
            document.getElementById('tenKhoa').value = d.tenKhoa || '—';

            if (ID_LOAI === 3) {
                document.getElementById('fieldSinhVien').classList.remove('hidden');
                document.getElementById('maSV').value = d.maSV || '';
                document.getElementById('gpa').value = d.GPA || '0.00';
                document.getElementById('drl').value = d.DRL || '0';
                document.getElementById('tenLop').value = d.tenLop || '—';
            } else if (ID_LOAI === 2) {
                document.getElementById('fieldGiangVien').classList.remove('hidden');
                document.getElementById('hocHam').value = d.hocHam || '';
            }
        } catch (e) {
            showToast('Không thể tải thông tin', 'error');
        }
    }

    async function luuThongTin() {
        const body = {
            idTK: ID_TK,
            hoTen: document.getElementById('hoTen').value.trim()
        };
        if (ID_LOAI === 2) body.hocHam = document.getElementById('hocHam').value;

        try {
            const res = await fetch('/api/tai_khoan/chi_tiet.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });
            const json = await res.json();
            showToast(json.message, json.status === 'success' ? 'success' : 'error');
            if (json.status === 'success') {
                document.getElementById('displayHoTen').textContent = body.hoTen;
            }
        } catch (e) {
            showToast('Lỗi kết nối', 'error');
        }
    }

    async function doiMatKhau() {
        const body = {
            matKhauCu: document.getElementById('matKhauCu').value,
            matKhauMoi: document.getElementById('matKhauMoi').value,
            xacNhanMatKhau: document.getElementById('xacNhanMatKhau').value,
        };

        try {
            const res = await fetch('/api/tai_khoan/chi_tiet.php?action=doi_mat_khau', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });
            const json = await res.json();
            showToast(json.message, json.status === 'success' ? 'success' : 'error');
            if (json.status === 'success') {
                ['matKhauCu', 'matKhauMoi', 'xacNhanMatKhau'].forEach(id => {
                    document.getElementById(id).value = '';
                });
            }
        } catch (e) {
            showToast('Lỗi kết nối', 'error');
        }
    }

    async function loadSuKien() {
        try {
            const res = await fetch(`/api/tai_khoan/chi_tiet.php?id=${ID_TK}&action=su_kien`);
            const json = await res.json();
            const el = document.getElementById('dsSuKien');

            if (json.status !== 'success' || !json.data.length) {
                el.innerHTML = '<p class="text-sm text-slate-400">Chưa tham gia sự kiện nào.</p>';
                return;
            }

            el.innerHTML = json.data.map(sk => `
            <a href="/views/event-detail.php?id_sk=${sk.idSK}"
               class="flex items-center justify-between p-3 mb-2 rounded-xl border border-slate-100 hover:border-primary/30 hover:bg-primary/5 transition-all group">
                <div>
                    <p class="mb-0 text-sm font-semibold text-slate-700 group-hover:text-primary">${escHtml(sk.tenSK)}</p>
                    <p class="mb-0 text-xs text-slate-400">${escHtml(sk.tenVaiTro)} · ${formatNgay(sk.ngayBatDau)}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-semibold ${sk.isActive ? 'bg-lime-100 text-lime-700' : 'bg-slate-100 text-slate-400'}">
                    ${sk.isActive ? 'Đang diễn ra' : 'Đã kết thúc'}
                </span>
            </a>
        `).join('');
        } catch (e) {
            document.getElementById('dsSuKien').innerHTML =
                '<p class="text-sm text-red-400">Không thể tải danh sách sự kiện.</p>';
        }
    }

    function loaiTKLabel(id) {
        return {
            1: 'Quản trị viên',
            2: 'Giảng viên',
            3: 'Sinh viên'
        } [id] || 'Người dùng';
    }

    function formatNgay(str) {
        if (!str) return '—';
        return new Date(str).toLocaleDateString('vi-VN');
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
</script>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>