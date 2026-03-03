<?php
/**
 * Event Detail Page
 */

// TODO: Xóa 2 dòng này sau khi test xong
session_start();
$_SESSION['user_id'] = 1; // Admin test account

$pageTitle = "Chi tiết sự kiện - ezManagement";
$currentPage = "events";
$pageCss = "event-detail.css";
$pageJs = "event-detail.js";

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$tab = isset($_GET['tab']) ? trim((string) $_GET['tab']) : 'overview';

$allowedTabs = ['overview', 'config-basic', 'config-rules', 'config-criteria', 'submissions', 'review-assign', 'review-results', 'scoring', 'subcommittees', 'committees', 'judges'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

$tabTitles = [
    'overview' => 'Tổng quan sự kiện',
    'config-basic' => 'Cấu hình cơ bản',
    'config-rules' => 'Cấu hình quy chế',
    'config-criteria' => 'Thiết lập bộ tiêu chí',
    'submissions' => 'Tất cả bài nộp',
    'review-assign' => 'Phân công phản biện',
    'review-results' => 'Kết quả Review',
    'scoring' => 'Phân công & Quản lý Điểm (Sơ loại)',
    'subcommittees' => 'Quản lý Tiểu ban (Bảo vệ Vòng trong)',
    'committees' => 'Quản lý tiểu ban',
    'judges' => 'Phân công BGK',
];

$pageHeading = $tabTitles[$tab] ?? 'Chi tiết sự kiện';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/dashboard'],
    ['title' => 'Quản lý sự kiện', 'url' => '/events'],
    ['title' => $pageHeading],
];

$useEventSidebar = true;
$eventSidebarEventId = $idSk;
$eventSidebarSection = $tab;

ob_start();
?>

<div class="w-full px-6 py-6 mx-auto">
    <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 mb-4">
            <a href="/events" class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg shadow-soft-sm text-slate-700 border-slate-200">
                <i class="mr-2 fas fa-arrow-left"></i> Quay lại danh sách sự kiện
            </a>
        </div>

        <div class="w-full max-w-full px-3">
            <div class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                <div class="p-6 pb-0">
                    <h6 class="mb-1" id="eventTitle">Đang tải sự kiện...</h6>
                    <p class="mb-0 text-sm text-slate-500" id="eventSubtitle">Không gian cấu hình sâu cho từng sự kiện.</p>
                </div>
                <div class="flex-auto p-6">
                    <div id="eventDetailLoading" class="text-sm text-slate-500">Đang tải dữ liệu chi tiết...</div>
                    <div id="eventDetailError" class="hidden px-4 py-3 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>

                    <div id="eventDetailContent" class="hidden space-y-4">
                        <?php if ($tab === 'overview'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Thông tin chung</p>
                                <div class="space-y-2 text-sm text-slate-600">
                                    <div><span class="font-semibold text-slate-700">Mô tả:</span> <span id="detailMoTa"></span></div>
                                    <div><span class="font-semibold text-slate-700">Cấp tổ chức:</span> <span id="detailCap"></span></div>
                                    <div><span class="font-semibold text-slate-700">Trạng thái:</span> <span id="detailTrangThai"></span></div>
                                </div>
                            </div>

                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Mốc thời gian</p>
                                <div class="space-y-2 text-sm text-slate-600">
                                    <div><span class="font-semibold text-slate-700">Mở đăng ký:</span> <span id="detailNgayMoDK"></span></div>
                                    <div><span class="font-semibold text-slate-700">Đóng đăng ký:</span> <span id="detailNgayDongDK"></span></div>
                                    <div><span class="font-semibold text-slate-700">Bắt đầu:</span> <span id="detailNgayBatDau"></span></div>
                                    <div><span class="font-semibold text-slate-700">Kết thúc:</span> <span id="detailNgayKetThuc"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Đăng ký tham gia</p>
                                <div class="space-y-2 text-sm text-slate-600">
                                    <div><span class="font-semibold text-slate-700">Sinh viên:</span> <span id="detailCheDoSV"></span></div>
                                    <div><span class="font-semibold text-slate-700">Giảng viên:</span> <span id="detailCheDoGV"></span></div>
                                </div>
                            </div>

                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Không gian cấu hình mở rộng</p>
                                <p class="mb-0 text-sm text-slate-500">Tại đây bạn có thể tiếp tục gắn các module cấu hình sâu: vòng thi, quy chế, phân công, lịch trình...</p>
                            </div>
                        </div>
                        <?php elseif ($tab === 'config-basic'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-3 text-xs font-bold uppercase text-slate-400">Chỉnh sửa thông tin sự kiện</p>
                                <div class="space-y-3 text-sm text-slate-600">
                                    <div>
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Tên sự kiện</label>
                                        <input id="basicTenSuKien" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>
                                        <textarea id="basicMoTa" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" rows="3"></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Cấp tổ chức</label>
                                            <select id="basicIdCap" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none"></select>
                                        </div>
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Trạng thái</label>
                                            <div id="basicTrangThaiText" class="px-3 py-2 text-sm border rounded-lg border-slate-200 bg-white">--</div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Mở đăng ký</label>
                                            <input id="basicNgayMoDK" type="datetime-local" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                                        </div>
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Đóng đăng ký</label>
                                            <input id="basicNgayDongDK" type="datetime-local" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày bắt đầu</label>
                                            <input id="basicNgayBatDau" type="datetime-local" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                                        </div>
                                        <div>
                                            <label class="block mb-1 text-xs font-semibold text-slate-700">Ngày kết thúc</label>
                                            <input id="basicNgayKetThuc" type="datetime-local" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-fuchsia-500 focus:outline-none" />
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <button id="btnSaveBasicConfig" type="button" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">Lưu thông tin</button>
                                        <button id="btnToggleEventStatus" type="button" class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase transition-all bg-white border rounded-lg text-slate-700 border-slate-300">Mở/Đóng sự kiện</button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 border rounded-xl border-slate-200 bg-white">
                                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                    <div>
                                        <p class="mb-0 text-xs font-bold uppercase text-slate-400">Cấu hình vòng thi</p>
                                        <p class="mb-0 text-sm text-slate-500">Quản lý các vòng thi theo thứ tự triển khai của sự kiện.</p>
                                    </div>
                                    <button id="btnCreateRound" type="button" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white uppercase transition-all bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md shrink-0 hover:scale-102 active:opacity-85">
                                        <i class="fas fa-plus"></i>
                                        Thêm vòng thi
                                    </button>
                                </div>
                                <div id="basicRoundList" class="space-y-2 text-sm text-slate-600">
                                    <div class="px-3 py-2 border rounded-lg border-slate-200 bg-white">Đang tải danh sách vòng thi...</div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($tab === 'config-rules'): ?>
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3 criteria-workspace">
                            <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Trình xây dựng quy chế logic</p>
                                <p class="mb-3 text-sm text-slate-500">Tạo các điều kiện đơn, sau đó ghép token logic để tạo cây quy chế.</p>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                    <div class="md:col-span-2">
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Thuộc tính kiểm tra</label>
                                        <select id="ruleInputThuocTinh" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"></select>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Toán tử</label>
                                        <select id="ruleInputToanTu" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300"></select>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Giá trị</label>
                                        <input id="ruleInputGiaTri" list="ruleGiaTriSuggestions" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300" />
                                        <datalist id="ruleGiaTriSuggestions"></datalist>
                                        <p id="ruleGiaTriHint" class="mt-1 mb-0 text-xs text-slate-500">Chọn thuộc tính để xem giá trị gợi ý.</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <button id="btnAddCondition" type="button" class="inline-flex items-center px-3 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">Thêm điều kiện</button>
                                    <span class="text-xs text-slate-500">Điều kiện tạo ra sẽ được ánh xạ A, B, C...</span>
                                </div>

                                <div id="conditionPool" class="p-3 mt-3 border rounded-lg border-slate-200 bg-slate-50 min-h-[52px]"></div>

                                <div class="mt-4">
                                    <p class="mb-2 text-xs font-bold uppercase text-slate-400">Bộ ghép token logic</p>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <button id="tokenAnd" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">AND</button>
                                        <button id="tokenOr" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">OR</button>
                                        <button id="tokenOpen" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">(</button>
                                        <button id="tokenClose" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white border border-slate-300">)</button>
                                        <button id="tokenBackspace" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 border border-slate-300">Xóa token cuối</button>
                                        <button id="tokenClear" type="button" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-slate-100 border border-slate-300">Làm mới</button>
                                    </div>
                                    <div id="tokenPreview" class="px-3 py-2 text-sm border rounded-lg border-slate-200 bg-slate-50 text-slate-600 min-h-[42px]"></div>
                                    <div id="tokenError" class="hidden px-3 py-2 mt-2 text-sm border rounded-lg border-rose-200 bg-rose-50 text-rose-600"></div>
                                </div>

                                <div class="mt-4">
                                    <p class="mb-2 text-xs font-bold uppercase text-slate-400">Diễn giải ngôn ngữ tự nhiên</p>
                                    <div id="ruleNaturalPreview" class="px-3 py-2 text-sm border rounded-lg border-cyan-200 bg-cyan-50 text-slate-700 min-h-[52px]">Chưa có dữ liệu phiên dịch...</div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 mt-4 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Tên quy chế</label>
                                        <input id="ruleNameInput" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300" placeholder="Ví dụ: Quy chế tham gia SV" />
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Loại quy chế</label>
                                        <select id="ruleTypeInput" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300">
                                            <option value="THAMGIA_SV">THAMGIA_SV</option>
                                            <option value="THAMGIA_GV">THAMGIA_GV</option>
                                            <option value="VONGTHI">VONGTHI</option>
                                            <option value="SANPHAM">SANPHAM</option>
                                            <option value="GIAITHUONG">GIAITHUONG</option>
                                        </select>
                                    </div>
                                </div>
                                <input id="rules_json" type="hidden" value="" />
                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <button id="btnSaveRuleConfig" type="button" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md" disabled>Lưu quy chế</button>
                                    <span id="astStatusText" class="text-xs text-slate-500">Chưa có cây logic hợp lệ.</span>
                                </div>
                            </div>

                            <div class="p-4 border rounded-xl border-slate-200 bg-white">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Danh sách quy chế của sự kiện</p>
                                <div id="ruleListContainer" class="mt-2 space-y-2 text-sm text-slate-600">
                                    <div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50">Đang tải danh sách quy chế...</div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($tab === 'config-criteria'): ?>
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                            <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
                                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                    <div>
                                        <p class="mb-0 text-xs font-bold uppercase text-slate-400">Tạo phiếu chấm điểm theo sự kiện</p>
                                        <p class="mb-0 text-sm text-slate-500">Cho phép tạo mới hoặc nhân bản bộ tiêu chí có sẵn để tinh chỉnh điểm tối đa theo sự kiện.</p>
                                    </div>
                                    <button id="criteriaResetForm" type="button" class="inline-flex items-center px-3 py-2 text-xs font-bold uppercase bg-white border rounded-lg border-slate-300 text-slate-700">Làm mới form</button>
                                </div>

                                <div class="p-3 mb-3 border rounded-lg border-slate-200 bg-slate-50">
                                    <label class="block mb-1 text-xs font-semibold text-slate-700">Nhân bản nhanh bộ tiêu chí có sẵn</label>
                                    <div class="flex flex-wrap gap-2">
                                        <select id="criteriaReuseSetDropdown" class="flex-1 min-w-[240px] px-3 py-2 text-sm border rounded-lg border-slate-300">
                                            <option value="">-- Chọn bộ tiêu chí để nhân bản --</option>
                                        </select>
                                        <button id="criteriaCloneSetBtn" type="button" class="inline-flex items-center px-3 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">Nhân bản vào form</button>
                                    </div>
                                </div>

                                <input id="criteriaEditId" type="hidden" value="0" />
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                    <div class="md:col-span-5">
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Tên bộ tiêu chí</label>
                                        <input id="criteriaTenBo" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300" placeholder="Ví dụ: Phiếu chấm Vòng Bán kết" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Áp dụng cho vòng</label>
                                        <select id="criteriaVongThi" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300">
                                            <option value="0">-- Chưa gán vòng thi --</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block mb-1 text-xs font-semibold text-slate-700">Mô tả</label>
                                        <input id="criteriaMoTa" type="text" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300" placeholder="Mô tả ngắn bộ tiêu chí" />
                                    </div>
                                </div>

                                <div class="mt-4 overflow-x-auto border rounded-lg border-slate-200">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-2 py-2 text-center text-xs font-bold uppercase text-slate-500 w-12">STT</th>
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Nội dung tiêu chí</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500 w-24">Điểm tối đa</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500 w-20">Tỷ trọng</th>
                                                <th class="px-2 py-2 text-center text-xs font-bold uppercase text-slate-500 w-24">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="criteriaTableBody"></tbody>
                                        <tfoot class="bg-slate-50 border-t border-slate-200">
                                            <tr>
                                                <td colspan="2" class="px-3 py-2 text-right text-xs font-bold text-slate-600">Tổng:</td>
                                                <td class="px-3 py-2 text-center text-xs font-bold text-slate-700" id="criteriaTotalDiem">0</td>
                                                <td class="px-3 py-2 text-center text-xs font-bold text-slate-700" id="criteriaTotalTyTrong">0</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                                    <div class="flex items-center gap-2">
                                        <button id="criteriaAddRow" type="button" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold uppercase bg-white border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            Thêm tiêu chí
                                        </button>
                                        <span class="text-xs text-slate-400">Gõ nội dung hoặc chọn từ gợi ý</span>
                                    </div>
                                    <button id="criteriaSaveBtn" type="button" class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md hover:scale-102 active:opacity-85">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Lưu bộ tiêu chí
                                    </button>
                                </div>
                                <datalist id="criteriaBankList"></datalist>
                            </div>

                            <div class="p-4 border rounded-xl border-slate-200 bg-white">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Ngân hàng bộ tiêu chí</p>
                                <div id="criteriaSetList" class="mt-2 space-y-2 text-sm text-slate-600">
                                    <div class="px-3 py-2 border rounded-lg border-slate-200 bg-slate-50">Đang tải bộ tiêu chí...</div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($tab === 'submissions'): ?>
                        <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                            <p class="mb-1 text-xs font-bold uppercase text-slate-400">Danh sách bài nộp theo sự kiện</p>
                            <p class="mb-3 text-sm text-slate-500">Đây là không gian quản lý tất cả bài nộp của sự kiện hiện tại.</p>
                            <div class="px-4 py-3 text-sm border rounded-lg border-slate-200 bg-white text-slate-600">
                                Module bài nộp đã có tab riêng. Bước tiếp theo có thể gắn API danh sách bài nộp theo id sự kiện để hiển thị bảng chi tiết tại đây.
                            </div>
                        </div>
                        <?php elseif ($tab === 'review-assign'): ?>
                        <!-- Tab: Phân công phản biện -->
                        <div id="reviewAssignContainer" class="reviewAssignContainer">
                            <div class="p-4 text-center text-slate-400">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p class="text-sm">Đang khởi tạo giao diện phân công phản biện...</p>
                            </div>
                        </div>
                        <?php elseif ($tab === 'review-results'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="text-xs font-bold uppercase text-slate-400">Bài đã review</p>
                                <p class="mb-0 text-2xl font-bold text-slate-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="text-xs font-bold uppercase text-slate-400">Điểm trung bình</p>
                                <p class="mb-0 text-2xl font-bold text-slate-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="text-xs font-bold uppercase text-slate-400">Đủ điều kiện vòng sau</p>
                                <p class="mb-0 text-2xl font-bold text-slate-700">--</p>
                            </div>
                        </div>
                        <div class="px-4 py-3 text-sm border rounded-lg border-slate-200 bg-white text-slate-600">
                            Kết quả review theo sự kiện sẽ được tổng hợp tại đây khi gắn API chấm điểm.
                        </div>
                        <?php elseif ($tab === 'scoring'): ?>
                        <!-- Tab: Phân công & Quản lý Điểm với 3 Sub-tabs -->
                        
                        <!-- Chọn vòng thi -->
                        <div class="mb-4 p-4 border rounded-xl border-slate-200 bg-slate-50">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block mb-1 text-xs font-semibold text-slate-600">Chọn vòng thi</label>
                                    <select id="scoringVongThiSelect" class="w-full px-3 py-2 text-sm border rounded-lg border-slate-300 focus:border-purple-500 focus:outline-none">
                                        <option value="">-- Chọn vòng thi --</option>
                                    </select>
                                </div>
                                <div class="flex-shrink-0">
                                    <p class="mb-1 text-xs font-semibold text-slate-600">Trạng thái</p>
                                    <span id="scoringVongThiStatus" class="px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Chưa chọn</span>
                                </div>
                            </div>
                        </div>

                        <!-- Thống kê tổng quan -->
                        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
                            <div class="p-4 border rounded-xl border-slate-200 bg-white shadow-soft-sm">
                                <p class="text-xs font-bold uppercase text-slate-400">Tổng bài nộp</p>
                                <p id="statTongSanPham" class="mb-0 text-2xl font-bold text-slate-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50">
                                <p class="text-xs font-bold uppercase text-amber-600">Đã phân công</p>
                                <p id="statDaPhanCong" class="mb-0 text-2xl font-bold text-amber-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-cyan-200 bg-gradient-to-br from-cyan-50 to-blue-50">
                                <p class="text-xs font-bold uppercase text-cyan-600">Đã chấm xong</p>
                                <p id="statDaChamXong" class="mb-0 text-2xl font-bold text-cyan-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
                                <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt</p>
                                <p id="statDaDuyet" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                            </div>
                        </div>

                        <!-- Sub-tabs Navigation -->
                        <div class="border-b border-slate-200 mb-4">
                            <nav class="flex flex-wrap -mb-px" id="scoringSubTabs">
                                <button type="button" data-subtab="phan-cong" class="scoring-subtab-btn active px-4 py-3 text-sm font-semibold border-b-2 border-purple-600 text-purple-600 bg-purple-50 rounded-t-lg">
                                    <i class="fas fa-user-plus mr-2"></i>Phân công Giám khảo
                                </button>
                                <button type="button" data-subtab="tien-do" class="scoring-subtab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 border-transparent">
                                    <i class="fas fa-chart-line mr-2"></i>Tiến độ & Kiểm định IRR
                                </button>
                                <button type="button" data-subtab="xet-duyet" class="scoring-subtab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 border-transparent">
                                    <i class="fas fa-trophy mr-2"></i>Xét kết quả & Bảng vàng
                                </button>
                            </nav>
                        </div>

                        <!-- Sub-tab 1: Phân công Giám khảo -->
                        <div id="subtab-phan-cong" class="scoring-subtab-content">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                                <!-- Danh sách sản phẩm -->
                                <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                        <div>
                                            <p class="mb-0 text-sm font-bold text-slate-700"><i class="fas fa-file-alt mr-2 text-slate-400"></i>Danh sách bài nộp</p>
                                            <p class="mb-0 text-xs text-slate-500">Click vào bài để phân công giám khảo</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="text" id="searchSanPham" placeholder="Tìm kiếm..." class="px-3 py-1.5 text-xs border rounded-lg border-slate-300 focus:border-purple-500 focus:outline-none w-40">
                                            <select id="filterTrangThai" class="px-3 py-1.5 text-xs border rounded-lg border-slate-300">
                                                <option value="">Tất cả trạng thái</option>
                                                <option value="chua_phan_cong">Chưa phân công</option>
                                                <option value="da_phan_cong">Đã phân công</option>
                                                <option value="dang_cham">Đang chấm</option>
                                                <option value="da_cham">Đã chấm xong</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="listSanPhamPhanCong" class="space-y-2 max-h-[500px] overflow-y-auto">
                                        <div class="px-4 py-8 text-center text-slate-400">
                                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                            <p class="text-sm">Vui lòng chọn vòng thi...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Panel phân công -->
                                <div class="p-4 border rounded-xl border-slate-200 bg-white">
                                    <div id="panelPhanCong">
                                        <p class="mb-3 text-sm font-bold text-slate-700"><i class="fas fa-user-tag mr-2 text-slate-400"></i>Phân công giám khảo</p>
                                        <div class="px-4 py-8 text-center text-slate-400 border rounded-lg border-dashed border-slate-300">
                                            <i class="fas fa-hand-pointer text-2xl mb-2"></i>
                                            <p class="text-sm">Chọn một bài nộp để phân công</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-tab 2: Tiến độ & Kiểm định IRR -->
                        <div id="subtab-tien-do" class="scoring-subtab-content hidden">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                                <!-- Danh sách bài có cảnh báo -->
                                <div class="xl:col-span-2 p-4 border rounded-xl border-slate-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                        <div>
                                            <p class="mb-0 text-sm font-bold text-slate-700"><i class="fas fa-exclamation-triangle mr-2 text-amber-500"></i>Bài cần xem xét (Độ lệch điểm)</p>
                                            <p class="mb-0 text-xs text-slate-500">Các bài có cảnh báo độ lệch điểm giữa các giám khảo > 30%</p>
                                        </div>
                                        <button id="btnRefreshCanhBao" class="px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                                            <i class="fas fa-sync-alt mr-1"></i>Làm mới
                                        </button>
                                    </div>
                                    <div id="listCanhBaoIRR" class="space-y-2 max-h-[400px] overflow-y-auto">
                                        <div class="px-4 py-8 text-center text-slate-400">
                                            <i class="fas fa-check-circle text-2xl mb-2 text-emerald-400"></i>
                                            <p class="text-sm">Chưa có dữ liệu cảnh báo</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chi tiết phân tích IRR -->
                                <div class="p-4 border rounded-xl border-slate-200 bg-white">
                                    <p class="mb-3 text-sm font-bold text-slate-700"><i class="fas fa-chart-bar mr-2 text-slate-400"></i>Phân tích IRR</p>
                                    <div id="panelIRRDetail" class="space-y-3">
                                        <div class="px-4 py-8 text-center text-slate-400 border rounded-lg border-dashed border-slate-300">
                                            <i class="fas fa-chart-pie text-2xl mb-2"></i>
                                            <p class="text-sm">Chọn bài để xem phân tích IRR</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bảng tiến độ tổng hợp -->
                            <div class="mt-4 p-4 border rounded-xl border-slate-200 bg-white">
                                <p class="mb-3 text-sm font-bold text-slate-700"><i class="fas fa-tasks mr-2 text-slate-400"></i>Tiến độ chấm điểm chi tiết</p>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm" id="tableTienDoCham">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Bài nộp</th>
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Nhóm</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500">GK phân công</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500">Đã chấm</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500">Tiến độ</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500">Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyTienDoCham">
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">Chọn vòng thi để xem tiến độ</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-tab 3: Xét kết quả & Bảng vàng -->
                        <div id="subtab-xet-duyet" class="scoring-subtab-content hidden">
                            <!-- Thống kê kết quả -->
                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
                                <div class="p-4 border rounded-xl border-emerald-200 bg-emerald-50">
                                    <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt</p>
                                    <p id="statKQDaDuyet" class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                                </div>
                                <div class="p-4 border rounded-xl border-rose-200 bg-rose-50">
                                    <p class="text-xs font-bold uppercase text-rose-600">Bị loại</p>
                                    <p id="statKQBiLoai" class="mb-0 text-2xl font-bold text-rose-700">--</p>
                                </div>
                                <div class="p-4 border rounded-xl border-amber-200 bg-amber-50">
                                    <p class="text-xs font-bold uppercase text-amber-600">Chờ duyệt</p>
                                    <p id="statKQChoDuyet" class="mb-0 text-2xl font-bold text-amber-700">--</p>
                                </div>
                                <div class="p-4 border rounded-xl border-purple-200 bg-purple-50">
                                    <p class="text-xs font-bold uppercase text-purple-600">Điểm TB</p>
                                    <p id="statKQDiemTB" class="mb-0 text-2xl font-bold text-purple-700">--</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <!-- Danh sách cần duyệt -->
                                <div class="p-4 border rounded-xl border-amber-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                        <div>
                                            <p class="mb-0 text-sm font-bold text-amber-700"><i class="fas fa-clock mr-2"></i>Bài chờ duyệt</p>
                                            <p class="mb-0 text-xs text-slate-500">Các bài đã chấm xong, chờ BTC duyệt</p>
                                        </div>
                                        <button id="btnDuyetTatCa" class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 rounded-lg hover:bg-emerald-600 transition-colors">
                                            <i class="fas fa-check-double mr-1"></i>Duyệt tất cả
                                        </button>
                                    </div>
                                    <div id="listCanDuyet" class="space-y-2 max-h-[400px] overflow-y-auto">
                                        <div class="px-4 py-8 text-center text-slate-400">
                                            <i class="fas fa-inbox text-2xl mb-2"></i>
                                            <p class="text-sm">Không có bài chờ duyệt</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bảng vàng -->
                                <div class="p-4 border rounded-xl border-amber-300 bg-gradient-to-br from-amber-50 to-yellow-50">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                        <div>
                                            <p class="mb-0 text-sm font-bold text-amber-700"><i class="fas fa-trophy mr-2 text-amber-500"></i>Bảng vàng - Xếp hạng</p>
                                            <p class="mb-0 text-xs text-amber-600">Các bài đã duyệt, xếp theo điểm giảm dần</p>
                                        </div>
                                        <button id="btnExportRanking" class="px-3 py-1.5 text-xs font-semibold text-amber-700 bg-white border border-amber-300 rounded-lg hover:bg-amber-100 transition-colors">
                                            <i class="fas fa-file-export mr-1"></i>Xuất Excel
                                        </button>
                                    </div>
                                    <div id="listBangVang" class="space-y-2 max-h-[400px] overflow-y-auto">
                                        <div class="px-4 py-8 text-center text-amber-400">
                                            <i class="fas fa-medal text-3xl mb-2"></i>
                                            <p class="text-sm">Chưa có bài nào được duyệt</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($tab === 'subcommittees'): ?>
                        <!-- Tab: Quản lý Tiểu ban (Bảo vệ Vòng trong) -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-4">
                            <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
                                <p class="text-xs font-bold uppercase text-emerald-600">Tiểu ban đã tạo</p>
                                <p class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
                                <p class="text-xs font-bold uppercase text-emerald-600">Bài đã xếp lịch</p>
                                <p class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                            </div>
                            <div class="p-4 border rounded-xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50">
                                <p class="text-xs font-bold uppercase text-emerald-600">BGK đã phân công</p>
                                <p class="mb-0 text-2xl font-bold text-emerald-700">--</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-emerald-200 bg-emerald-50">
                                <p class="mb-1 text-xs font-bold uppercase text-emerald-600"><i class="fas fa-sitemap mr-1"></i> Quản lý Tiểu ban</p>
                                <p class="mb-2 text-sm text-emerald-700">Tổ chức các tiểu ban bảo vệ, sắp xếp lịch trình và phân công Ban giám khảo cho vòng Chung khảo.</p>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <button class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 rounded-lg hover:bg-emerald-600 transition-colors">
                                        <i class="fas fa-plus mr-1"></i> Tạo tiểu ban
                                    </button>
                                    <button class="px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-white border border-emerald-300 rounded-lg hover:bg-emerald-100 transition-colors">
                                        <i class="fas fa-calendar-alt mr-1"></i> Xếp lịch
                                    </button>
                                </div>
                            </div>
                            <div class="p-4 border rounded-xl border-dashed border-emerald-300 bg-emerald-50/50">
                                <p class="mb-1 text-xs font-bold uppercase text-emerald-500">Quy trình bảo vệ</p>
                                <ul class="pl-5 space-y-1 text-sm list-disc text-emerald-600">
                                    <li>Tạo tiểu ban theo chuyên ngành/lĩnh vực</li>
                                    <li>Phân bổ bài nộp vào từng tiểu ban</li>
                                    <li>Sắp xếp slot thời gian bảo vệ</li>
                                    <li>Phân công BGK chấm điểm trực tiếp</li>
                                </ul>
                            </div>
                        </div>
                        <?php elseif ($tab === 'committees'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Quản lý tiểu ban</p>
                                <p class="mb-0 text-sm text-slate-600">Tạo, sửa và quản lý các tiểu ban báo cáo của sự kiện.</p>
                            </div>
                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Gợi ý tác vụ</p>
                                <ul class="pl-5 space-y-1 text-sm list-disc text-slate-500">
                                    <li>Tạo danh sách tiểu ban theo chuyên đề</li>
                                    <li>Phân bổ bài nộp vào tiểu ban</li>
                                    <li>Thiết lập lịch trình báo cáo theo tiểu ban</li>
                                </ul>
                            </div>
                        </div>
                        <?php elseif ($tab === 'judges'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Phân công Ban giám khảo</p>
                                <p class="mb-0 text-sm text-slate-600">Quản lý danh sách BGK và phân công theo từng tiểu ban, phiên báo cáo.</p>
                            </div>
                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Gợi ý tác vụ</p>
                                <ul class="pl-5 space-y-1 text-sm list-disc text-slate-500">
                                    <li>Thêm giảng viên vào danh sách BGK</li>
                                    <li>Phân công BGK theo tiểu ban</li>
                                    <li>Khóa lịch chấm để tránh trùng lặp</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Tính toán base path của project
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = dirname($scriptPath); // Lùi lại 1 cấp từ /views
if ($basePath === '\\' || $basePath === '/') {
    $basePath = '';
}
?>
<script>
    window.APP_BASE_PATH = <?php echo json_encode($basePath); ?>;
    window.EVENT_DETAIL_ID = <?php echo (int) $idSk; ?>;
    window.EVENT_DETAIL_TAB = <?php echo json_encode($tab, JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php if ($tab === 'scoring'): ?>
<script src="<?php echo $basePath; ?>/assets/js/scoring.js"></script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
