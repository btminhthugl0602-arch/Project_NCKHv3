<?php
/**
 * Partial: Tab Tổng quan sự kiện
 * Biến cần có: $idSk, $tab
 * JS xử lý: event-detail.js (loadEventDetail, section overview)
 */
?>
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
