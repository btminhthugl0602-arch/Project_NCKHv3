<?php
/**
 * Event Detail Page
 */

$pageTitle = "Chi tiết sự kiện - ezManagement";
$currentPage = "events";
$pageJs = "event-detail.js";

$idSk = isset($_GET['id_sk']) ? (int) $_GET['id_sk'] : 0;
$tab = isset($_GET['tab']) ? trim((string) $_GET['tab']) : 'overview';

$allowedTabs = ['overview', 'config', 'submissions', 'review-assign', 'review-results', 'committees', 'judges'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}

$tabTitles = [
    'overview' => 'Tổng quan sự kiện',
    'config' => 'Cấu hình sự kiện',
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
                        <?php elseif ($tab === 'config'): ?>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="p-4 border rounded-xl border-slate-200 bg-slate-50">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Cấu hình nền tảng</p>
                                <div class="space-y-2 text-sm text-slate-600">
                                    <div><span class="font-semibold text-slate-700">Tên sự kiện:</span> <span id="configTenSuKien">--</span></div>
                                    <div><span class="font-semibold text-slate-700">Cấp tổ chức:</span> <span id="configCapToChuc">--</span></div>
                                    <div><span class="font-semibold text-slate-700">Đăng ký SV:</span> <span id="configCheDoSV">--</span></div>
                                    <div><span class="font-semibold text-slate-700">Đăng ký GV:</span> <span id="configCheDoGV">--</span></div>
                                </div>
                            </div>
                            <div class="p-4 border rounded-xl border-dashed border-slate-300">
                                <p class="mb-1 text-xs font-bold uppercase text-slate-400">Các khối cấu hình sâu</p>
                                <ul class="pl-5 space-y-1 text-sm list-disc text-slate-500">
                                    <li>Thiết lập vòng thi theo giai đoạn</li>
                                    <li>Thiết lập quy chế tham gia</li>
                                    <li>Thiết lập mốc thời gian nộp/đánh giá</li>
                                </ul>
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
