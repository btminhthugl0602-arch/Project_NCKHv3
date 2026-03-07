<?php

/**
 * Trang Quản lý Tài khoản
 * Chỉ admin có quyền quan_ly_tai_khoan mới truy cập được
 */

// ── GUARD: Kiểm tra đăng nhập ────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['idTK'])) {
    header('Location: /views/dang_nhap.php');
    exit;
}

// ── GUARD: Kiểm tra quyền ────────────────────────────────────
if (!defined('_AUTHEN')) define('_AUTHEN', true);
require_once __DIR__ . '/../api/core/base.php';

if (!kiem_tra_quyen_he_thong($conn, (int)$_SESSION['idTK'], 'quan_ly_tai_khoan')) {
    header('Location: /views/dashboard.php?error=forbidden');
    exit;
}

// ── PAGE META ────────────────────────────────────────────────
$pageTitle   = 'Quản lý tài khoản - ezManagement';
$currentPage = 'admin-users';
$pageHeading = 'Quản lý tài khoản';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/dashboard'],
    ['title' => 'Quản lý tài khoản'],
];
$pageJs  = 'admin_users.js';
$pageCss = 'admin_users.css';

$idTKHienTai = (int) $_SESSION['idTK'];

ob_start();
?>

<!-- Truyền config PHP → JS -->
<script>
    window.ADMIN_USERS_CONFIG = {
        idTKHienTai: <?= $idTKHienTai ?>,
        basePath: '<?= rtrim($basePath ?? '', '/') ?>'
    };
</script>

<div class="w-full px-6 py-6 mx-auto">

    <!-- ── STATS ROW ── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-2xl shadow-soft-xl p-4 flex items-center gap-4">
            <div
                class="size-12 rounded-xl bg-gradient-to-br from-purple-600 to-fuchsia-500 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white active-icon text-xl">group</span>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Tổng tài khoản</p>
                <p class="text-2xl font-bold text-slate-800 leading-tight" id="statTotal">—</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-soft-xl p-4 flex items-center gap-4">
            <div
                class="size-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-700 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white active-icon text-xl">admin_panel_settings</span>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Quản trị viên</p>
                <p class="text-2xl font-bold text-slate-800 leading-tight" id="statAdmin">—</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-soft-xl p-4 flex items-center gap-4">
            <div
                class="size-12 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white active-icon text-xl">school</span>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Giảng viên</p>
                <p class="text-2xl font-bold text-slate-800 leading-tight" id="statGV">—</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-soft-xl p-4 flex items-center gap-4">
            <div
                class="size-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white active-icon text-xl">person</span>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Sinh viên</p>
                <p class="text-2xl font-bold text-slate-800 leading-tight" id="statSV">—</p>
            </div>
        </div>

    </div>

    <!-- ── MAIN CARD ── -->
    <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">

        <!-- Card header -->
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 pt-5 pb-4 border-b border-slate-100">
            <div>
                <h6 class="mb-0.5 font-bold text-slate-800">Danh sách tài khoản</h6>
                <p class="mb-0 text-xs leading-normal text-slate-500">Quản lý tài khoản sinh viên, giảng viên và quản
                    trị viên</p>
            </div>
            <button id="btnOpenCreate" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-white uppercase rounded-xl
                       bg-gradient-to-tl from-purple-700 via-fuchsia-600 to-pink-500
                       shadow-soft-md hover:scale-102 active:opacity-85 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-[16px] active-icon">person_add</span>
                Tạo tài khoản
            </button>
        </div>

        <!-- Filter + Search -->
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 border-b border-slate-100">

            <!-- Tab filter -->
            <div class="flex items-center gap-0.5" role="tablist" aria-label="Lọc theo loại tài khoản">
                <button class="au-filter-tab px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer
                               bg-primary/10 text-primary" data-filter="all" role="tab" aria-selected="true">
                    Tất cả
                </button>
                <button class="au-filter-tab px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer
                               text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-filter="1" role="tab"
                    aria-selected="false">
                    Quản trị viên
                </button>
                <button class="au-filter-tab px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer
                               text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-filter="2" role="tab"
                    aria-selected="false">
                    Giảng viên
                </button>
                <button class="au-filter-tab px-3 py-2 text-xs font-semibold rounded-lg transition-colors cursor-pointer
                               text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-filter="3" role="tab"
                    aria-selected="false">
                    Sinh viên
                </button>
            </div>

            <!-- Search -->
            <div class="relative">
                <span
                    class="material-symbols-outlined text-[16px] text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    aria-hidden="true">search</span>
                <input id="auSearch" type="search" placeholder="Tìm tên đăng nhập, họ tên…"
                    aria-label="Tìm kiếm tài khoản" class="pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-xl w-64
                              focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10
                              transition-all" />
            </div>

        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="items-center w-full mb-0 align-top border-collapse" aria-label="Danh sách tài khoản">
                <thead class="align-bottom bg-slate-50/80">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Tài khoản
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Loại
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Đơn vị
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Quyền hệ thống
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Trạng thái
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-left uppercase text-slate-400 border-b border-slate-200">
                            Ngày tạo
                        </th>
                        <th scope="col"
                            class="px-4 py-3 text-xxs font-bold tracking-wider text-right uppercase text-slate-400 border-b border-slate-200">
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody id="auTableBody" class="divide-y divide-slate-100">
                    <!-- Skeleton rows — JS thay thế sau khi load -->
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr class="au-skeleton-row">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-xl bg-slate-200 animate-pulse shrink-0"></div>
                                    <div class="space-y-1.5">
                                        <div class="h-3 w-24 bg-slate-200 rounded animate-pulse"></div>
                                        <div class="h-2.5 w-32 bg-slate-100 rounded animate-pulse"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-5 w-20 bg-slate-200 rounded-full animate-pulse"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-3 w-28 bg-slate-200 rounded animate-pulse"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-3 w-20 bg-slate-200 rounded animate-pulse"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-5 w-16 bg-slate-200 rounded-full animate-pulse"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="h-3 w-20 bg-slate-200 rounded animate-pulse"></div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="h-7 w-14 bg-slate-200 rounded-lg animate-pulse ml-auto"></div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state (ẩn mặc định) -->
        <div id="auEmptyState" class="hidden py-16 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-300" aria-hidden="true">manage_accounts</span>
            <p class="mt-3 text-sm font-semibold text-slate-500">Không tìm thấy tài khoản nào</p>
            <p class="text-xs text-slate-400 mt-1">Thử thay đổi bộ lọc hoặc từ khoá tìm kiếm</p>
        </div>

        <!-- Footer: đếm kết quả + pagination -->
        <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between gap-4 flex-wrap">
            <p id="auResultCount" class="text-xs text-slate-400">Đang tải…</p>
            <div id="auPagination"></div>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════
     MODAL: TẠO TÀI KHOẢN
════════════════════════════════════════════════════════ -->
<div id="auCreateModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity duration-200"
    role="dialog" aria-modal="true" aria-labelledby="auCreateModalTitle">

    <!-- Backdrop -->
    <div id="auCreateBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    <!-- Box -->
    <div id="auCreateBox" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden
                scale-95 opacity-0 transition-all duration-200">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div
                    class="size-9 bg-gradient-to-br from-purple-600 to-fuchsia-500 rounded-xl flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white active-icon text-lg"
                        aria-hidden="true">person_add</span>
                </div>
                <div>
                    <h3 id="auCreateModalTitle" class="text-sm font-bold text-slate-800">Tạo tài khoản mới</h3>
                    <p class="text-xs text-slate-500">Điền đầy đủ thông tin bên dưới</p>
                </div>
            </div>
            <button id="auCreateClose" type="button" aria-label="Đóng" class="size-8 rounded-lg flex items-center justify-center text-slate-400
                           hover:bg-slate-100 hover:text-slate-600 transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-5 space-y-4 max-h-[68vh] overflow-y-auto">

            <!-- Chọn loại tài khoản -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">
                    Loại tài khoản <span class="text-rose-500" aria-hidden="true">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2" id="auTypeSelector" role="radiogroup"
                    aria-label="Chọn loại tài khoản">
                    <button type="button" data-type="1" role="radio" aria-checked="false"
                        class="au-type-btn flex flex-col items-center gap-2 px-3 py-3 rounded-xl border-2 border-slate-200
                                   bg-slate-50 hover:border-violet-300 hover:bg-violet-50 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[26px] text-slate-400"
                            aria-hidden="true">admin_panel_settings</span>
                        <span class="text-xs font-semibold text-slate-600">Quản trị viên</span>
                    </button>
                    <button type="button" data-type="2" role="radio" aria-checked="false" class="au-type-btn flex flex-col items-center gap-2 px-3 py-3 rounded-xl border-2 border-slate-200
                                   bg-slate-50 hover:border-sky-300 hover:bg-sky-50 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[26px] text-slate-400"
                            aria-hidden="true">school</span>
                        <span class="text-xs font-semibold text-slate-600">Giảng viên</span>
                    </button>
                    <button type="button" data-type="3" role="radio" aria-checked="false"
                        class="au-type-btn flex flex-col items-center gap-2 px-3 py-3 rounded-xl border-2 border-slate-200
                                   bg-slate-50 hover:border-emerald-300 hover:bg-emerald-50 transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[26px] text-slate-400"
                            aria-hidden="true">person</span>
                        <span class="text-xs font-semibold text-slate-600">Sinh viên</span>
                    </button>
                </div>
                <p id="auTypeError" class="hidden mt-1.5 text-xs text-rose-600" role="alert"></p>
            </div>

            <div class="border-t border-slate-100"></div>

            <!-- Thông tin đăng nhập -->
            <div class="space-y-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Thông tin đăng nhập</p>

                <div>
                    <label for="auTenTK" class="block text-xs font-semibold text-slate-700 mb-1">
                        Tên đăng nhập <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <input id="auTenTK" type="text" autocomplete="off" placeholder="vd: gv_nguyen, sv_tung" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                  focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                  transition-all" />
                    <p class="text-[11px] text-slate-400 mt-1">Chỉ dùng chữ thường, số và dấu gạch dưới (_)</p>
                    <p id="auTenTKError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="auMatKhau" class="block text-xs font-semibold text-slate-700 mb-1">
                            Mật khẩu <span class="text-rose-500" aria-hidden="true">*</span>
                        </label>
                        <input id="auMatKhau" type="password" autocomplete="new-password"
                            placeholder="Tối thiểu 6 ký tự" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                      transition-all" />
                        <p id="auMatKhauError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                    </div>
                    <div>
                        <label for="auXacNhan" class="block text-xs font-semibold text-slate-700 mb-1">
                            Xác nhận MK <span class="text-rose-500" aria-hidden="true">*</span>
                        </label>
                        <input id="auXacNhan" type="password" autocomplete="new-password"
                            placeholder="Nhập lại mật khẩu" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                      transition-all" />
                        <p id="auXacNhanError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                    </div>
                </div>
            </div>

            <!-- Profile section — hiện động theo loại -->
            <div id="auProfileSection" class="hidden space-y-3">
                <div class="border-t border-slate-100"></div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Thông tin hồ sơ</p>

                <!-- Họ tên — GV + SV -->
                <div id="auFieldHoTen">
                    <label for="auHoTen" class="block text-xs font-semibold text-slate-700 mb-1">
                        Họ và tên <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <input id="auHoTen" type="text" placeholder="Nguyễn Văn A" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                  focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                  transition-all" />
                    <p id="auHoTenError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                </div>

                <!-- Sinh viên: MSV + Lớp -->
                <div id="auFieldSV" class="hidden space-y-3">
                    <div>
                        <label for="auMSV" class="block text-xs font-semibold text-slate-700 mb-1">
                            Mã sinh viên <span class="text-rose-500" aria-hidden="true">*</span>
                        </label>
                        <input id="auMSV" type="text" placeholder="vd: SV2024001" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                      transition-all" />
                        <p id="auMSVError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                    </div>
                    <div>
                        <label for="auLop" class="block text-xs font-semibold text-slate-700 mb-1">
                            Lớp <span class="text-rose-500" aria-hidden="true">*</span>
                        </label>
                        <select id="auLop" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white
                                       focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                       transition-all">
                            <option value="">-- Chọn lớp --</option>
                        </select>
                        <p id="auLopError" class="hidden mt-1 text-xs text-rose-600" role="alert"></p>
                    </div>
                </div>

                <!-- Giảng viên: Khoa + Học hàm -->
                <div id="auFieldGV" class="hidden space-y-3">
                    <div>
                        <label for="auKhoa" class="block text-xs font-semibold text-slate-700 mb-1">
                            Khoa
                            <span class="text-slate-400 font-normal">(tuỳ chọn)</span>
                        </label>
                        <select id="auKhoa" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white
                                       focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                       transition-all">
                            <option value="">-- Không chọn --</option>
                        </select>
                    </div>
                    <div>
                        <label for="auHocHam" class="block text-xs font-semibold text-slate-700 mb-1">
                            Học hàm / Học vị
                            <span class="text-slate-400 font-normal">(tuỳ chọn)</span>
                        </label>
                        <select id="auHocHam" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white
                                       focus:outline-none focus:border-primary/60 focus:ring-2 focus:ring-primary/10
                                       transition-all">
                            <option value="">-- Không chọn --</option>
                            <option value="Cu_nhan">Cử nhân</option>
                            <option value="Tha_si">Thạc sĩ</option>
                            <option value="Tien_si">Tiến sĩ</option>
                            <option value="Pho_giao_su">Phó giáo sư</option>
                            <option value="Giao_su">Giáo sư</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <button id="auCreateCancel" type="button" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200
                           rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                Huỷ
            </button>
            <button id="auCreateSubmit" type="button" class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white uppercase rounded-lg
                           bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md
                           hover:scale-105 active:opacity-85 transition-all cursor-pointer
                           disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100">
                <span class="material-symbols-outlined text-[15px] active-icon" aria-hidden="true">person_add</span>
                <span id="auCreateSubmitLabel">Tạo tài khoản</span>
            </button>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════
     SLIDE-OVER: CHI TIẾT TÀI KHOẢN
════════════════════════════════════════════════════════ -->

<!-- Backdrop -->
<div id="auSOBackdrop"
    class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
</div>

<!-- Panel -->
<div id="auSlideOver" role="dialog" aria-modal="true" aria-labelledby="auSOName" class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl flex flex-col
            translate-x-full transition-transform duration-300 ease-in-out">

    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <div id="auSOAvatar"
                class="size-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 select-none"
                aria-hidden="true">
                ?
            </div>
            <div class="min-w-0">
                <h3 id="auSOName" class="text-sm font-bold text-slate-800 truncate">—</h3>
                <div id="auSOBadge" class="mt-0.5"></div>
            </div>
        </div>
        <button id="auSOClose" type="button" aria-label="Đóng panel chi tiết" class="size-8 rounded-lg flex items-center justify-center text-slate-400
                       hover:bg-slate-100 transition-colors cursor-pointer shrink-0">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
        </button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-slate-100 px-5 shrink-0" role="tablist">
        <button id="auSOTabInfoBtn"
            class="au-so-tab-btn py-3 pr-5 text-xs font-semibold text-primary border-b-2 border-primary transition-colors cursor-pointer"
            role="tab" aria-selected="true" aria-controls="auSOTabInfo">
            Thông tin
        </button>
        <button id="auSOTabPermBtn" class="au-so-tab-btn py-3 pr-5 text-xs font-semibold text-slate-500 border-b-2 border-transparent
                       hover:text-slate-700 transition-colors cursor-pointer" role="tab" aria-selected="false"
            aria-controls="auSOTabPerm">
            Phân quyền
        </button>
    </div>

    <!-- Tab content -->
    <div class="flex-1 overflow-y-auto">

        <!-- Skeleton (hiện khi đang load) -->
        <div id="auSOSkeleton" class="p-5 space-y-4">
            <div class="h-14 bg-slate-100 rounded-xl animate-pulse"></div>
            <div class="grid grid-cols-2 gap-3">
                <div class="h-16 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-16 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="col-span-2 h-16 bg-slate-100 rounded-xl animate-pulse"></div>
            </div>
            <div class="h-20 bg-slate-100 rounded-xl animate-pulse"></div>
        </div>

        <!-- Tab: Thông tin -->
        <div id="auSOTabInfo" class="hidden p-5 space-y-4" role="tabpanel">

            <!-- Status banner -->
            <div id="auSOStatusBanner" class="flex items-center gap-3 p-3 rounded-xl border">
                <span id="auSOStatusIcon" class="material-symbols-outlined text-[20px] active-icon shrink-0"
                    aria-hidden="true">check_circle</span>
                <div>
                    <p id="auSOStatusTitle" class="text-xs font-semibold"></p>
                    <p id="auSOStatusDesc" class="text-[11px]"></p>
                </div>
            </div>

            <!-- Thông tin cơ bản -->
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Thông tin cơ bản</p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Tên đăng nhập</p>
                        <p id="auSOTenTK" class="text-sm font-semibold text-slate-700">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Ngày tạo</p>
                        <p id="auSONgayTao" class="text-sm font-semibold text-slate-700">—</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 col-span-2">
                        <p class="text-[11px] text-slate-400 font-medium mb-0.5">Họ và tên</p>
                        <p id="auSOHoTen" class="text-sm font-semibold text-slate-700">—</p>
                    </div>
                </div>
            </div>

            <!-- Profile section (render động) -->
            <div id="auSOProfileSection" class="hidden">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Thông tin hồ sơ</p>
                <div id="auSOProfileContent" class="grid grid-cols-2 gap-3">
                    <!-- JS render -->
                </div>
            </div>

            <!-- Thao tác -->
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Thao tác</p>
                <div class="space-y-2">

                    <!-- Khóa / Mở khóa -->
                    <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl">
                        <div class="flex items-center gap-2.5">
                            <span id="auSOLockIcon" class="material-symbols-outlined text-[20px] text-amber-500"
                                aria-hidden="true">lock</span>
                            <div>
                                <p id="auSOLockLabel" class="text-xs font-semibold text-slate-700">Khóa tài khoản</p>
                                <p id="auSOLockDesc" class="text-[11px] text-slate-400">Người dùng sẽ không thể đăng
                                    nhập</p>
                            </div>
                        </div>
                        <button id="auSOLockBtn" type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors cursor-pointer
                                       border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100">
                            Khóa
                        </button>
                    </div>

                    <!-- Đặt lại mật khẩu -->
                    <div class="p-3 border border-slate-200 rounded-xl">
                        <div class="flex items-center justify-between mb-0" id="auSOResetHeader">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[20px] text-blue-500"
                                    aria-hidden="true">key</span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">Đặt lại mật khẩu</p>
                                    <p class="text-[11px] text-slate-400">Đặt mật khẩu mới cho tài khoản này</p>
                                </div>
                            </div>
                            <button id="auSOResetToggle" type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors cursor-pointer
                                           border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100">
                                Đặt lại
                            </button>
                        </div>

                        <!-- Form reset — ẩn mặc định -->
                        <div id="auSOResetForm" class="hidden mt-3 space-y-2">
                            <div>
                                <input id="auSOResetPw" type="password" placeholder="Mật khẩu mới (ít nhất 6 ký tự)"
                                    class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg
                                              focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition-all" />
                            </div>
                            <div>
                                <input id="auSOResetPwConfirm" type="password" placeholder="Xác nhận mật khẩu mới"
                                    class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg
                                              focus:outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition-all" />
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <button id="auSOResetSubmit" type="button"
                                    class="flex-1 py-2 text-xs font-semibold text-white rounded-lg
                                               bg-gradient-to-r from-blue-600 to-blue-500 hover:opacity-90 transition-opacity cursor-pointer">
                                    <span id="auSOResetLabel">Xác nhận đặt lại</span>
                                </button>
                                <button id="auSOResetCancel" type="button" class="px-3 py-2 text-xs font-semibold text-slate-500 border border-slate-200 rounded-lg
                                               hover:bg-slate-50 transition-colors cursor-pointer">
                                    Hủy
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Tab: Phân quyền -->
        <div id="auSOTabPerm" class="hidden p-5 space-y-3" role="tabpanel">

            <!-- Note cho Admin -->
            <div id="auSOAdminNote"
                class="hidden flex items-start gap-2 p-3 bg-violet-50 border border-violet-200 rounded-xl">
                <span class="material-symbols-outlined text-[16px] text-violet-500 shrink-0 mt-0.5"
                    aria-hidden="true">shield</span>
                <p class="text-[11px] text-violet-700">
                    Quản trị viên <strong>tự động có toàn bộ quyền hệ thống</strong>. Không cần cấu hình thêm.
                </p>
            </div>

            <!-- Danh sách quyền HE_THONG -->
            <div id="auSOPermList" class="space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Quyền hệ thống</p>

                <label class="flex items-start gap-3 p-3 border rounded-xl border-slate-200 cursor-pointer
                              hover:border-violet-300 hover:bg-violet-50/50 transition-all
                              has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50">
                    <input type="checkbox" id="auPermQlTK" data-perm="quan_ly_tai_khoan"
                        class="mt-0.5 accent-violet-600 cursor-pointer" />
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Quản lý tài khoản</p>
                        <p class="text-[11px] text-slate-400">Truy cập trang quản lý, tạo và sửa tài khoản</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 border rounded-xl border-slate-200 cursor-pointer
                              hover:border-violet-300 hover:bg-violet-50/50 transition-all
                              has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50">
                    <input type="checkbox" id="auPermTaoSK" data-perm="tao_su_kien"
                        class="mt-0.5 accent-violet-600 cursor-pointer" />
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Tạo sự kiện</p>
                        <p class="text-[11px] text-slate-400">Cho phép tạo sự kiện NCKH mới</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 border rounded-xl border-slate-200 cursor-pointer
                              hover:border-violet-300 hover:bg-violet-50/50 transition-all
                              has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50">
                    <input type="checkbox" id="auPermThongKe" data-perm="xem_thong_ke"
                        class="mt-0.5 accent-violet-600 cursor-pointer" />
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Xem thống kê hệ thống</p>
                        <p class="text-[11px] text-slate-400">Xem thống kê và báo cáo toàn hệ thống</p>
                    </div>
                </label>

                <button id="auSOSavePerms" type="button" class="w-full inline-flex items-center justify-center gap-2 py-2.5 mt-1
                               text-xs font-bold text-white uppercase rounded-xl
                               bg-gradient-to-tl from-purple-700 to-pink-500 shadow-soft-md
                               hover:scale-102 active:opacity-85 transition-all cursor-pointer
                               disabled:opacity-60 disabled:cursor-not-allowed disabled:scale-100">
                    <span class="material-symbols-outlined text-[15px] active-icon" aria-hidden="true">save</span>
                    <span id="auSavePermsLabel">Lưu phân quyền</span>
                </button>
            </div>

        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
?>