<?php
/**
 * Event Detail Page
 */

$pageTitle = "Chi tiết sự kiện - ezManagement";
$currentPage = "events";
$pageCss = "event-detail.css";
$pageJs = "event-detail.js";

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$tab = isset($_GET['tab']) ? trim((string) $_GET['tab']) : 'overview';

$allowedTabs = ['overview', 'config-basic', 'config-rules', 'config-criteria', 'submissions', 'review-assign', 'review-results', 'committees', 'judges'];
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
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Nội dung tiêu chí</th>
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Điểm tối đa</th>
                                                <th class="px-3 py-2 text-left text-xs font-bold uppercase text-slate-500">Tỷ trọng</th>
                                                <th class="px-3 py-2 text-center text-xs font-bold uppercase text-slate-500">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody id="criteriaTableBody"></tbody>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                                    <button id="criteriaAddRow" type="button" class="inline-flex items-center px-3 py-2 text-xs font-bold uppercase bg-white border rounded-lg border-slate-300 text-slate-700">Thêm tiêu chí</button>
                                    <button id="criteriaSaveBtn" type="button" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white uppercase bg-gradient-to-tl from-purple-700 to-pink-500 rounded-lg shadow-soft-md">Lưu bộ tiêu chí</button>
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
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Phân công phản biện</p>
                                <p class="mb-0 text-sm text-slate-600">Màn hình này dành cho gán giảng viên phản biện cho bài nộp trong sự kiện.</p>
                            </div>
                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Dữ liệu đầu vào cần có</p>
                                <ul class="pl-5 space-y-1 text-sm list-disc text-slate-500">
                                    <li>Danh sách bài nộp hợp lệ</li>
                                    <li>Danh sách giảng viên phản biện</li>
                                    <li>Quy tắc số lượng phản biện / bài</li>
                                </ul>
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

<script>
    window.EVENT_DETAIL_ID = <?php echo (int) $idSk; ?>;
    window.EVENT_DETAIL_TAB = <?php echo json_encode($tab, JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php
$content = ob_get_clean();
include '../layouts/main_layout.php';
