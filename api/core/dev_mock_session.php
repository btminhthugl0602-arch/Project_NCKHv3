<?php
/**
 * ⚠️  CHỈ DÙNG KHI PHÁT TRIỂN - XOÁ TRƯỚC KHI LÊN PRODUCTION
 *
 * Giả lập session đăng nhập để test API mà không cần login thật.
 * Cách dùng: include file này vào đầu bất kỳ view nào đang test.
 *
 * Ví dụ: thêm vào đầu event-detail.php:
 *   require_once __DIR__ . '/../../api/core/dev_mock_session.php';
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Đổi các giá trị này cho phù hợp với DB của bạn ──
$_SESSION['idTK']     = 3;                // idTK trong bảng taikhoan
$_SESSION['idLoaiTK'] = 3;                // 1=Admin, 2=Giảng viên, 3=Sinh viên
$_SESSION['hoTen']    = 'Dev Test User';  // Hiển thị trên navbar

/*
 * Các idTK có thể dùng để test (xem bảng taikhoan):
 *   idLoaiTK = 1  → Quản trị viên: test quản lý tài khoản, tạo sự kiện
 *   idLoaiTK = 2  → Giảng viên:    test mời GVHD, duyệt yêu cầu, chấm điểm
 *   idLoaiTK = 3  → Sinh viên:     test tạo nhóm, xin vào nhóm, nộp bài
 *
 * Cách tìm idTK:
 *   SELECT idTK, tenTK, idLoaiTK FROM taikhoan WHERE isActive = 1 LIMIT 10;
 */
