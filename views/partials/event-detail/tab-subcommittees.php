<?php
/**
 * Partial: Tab Quản lý Tiểu ban (Bảo vệ Vòng trong)
 * Biến cần có: $idSk, $tab
 */
?>
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
        <p class="mb-1 text-xs font-bold uppercase text-emerald-600">
            <i class="fas fa-sitemap mr-1"></i> Quản lý Tiểu ban
        </p>
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
