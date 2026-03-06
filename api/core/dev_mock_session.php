<?php
/**
 * ⚠️  CHỈ DÙNG KHI PHÁT TRIỂN - XOÁ TRƯỚC KHI LÊN PRODUCTION
 * 
 * Giả lập session đăng nhập để test API mà không cần login thật.
 * Cách dùng: include file này vào đầu bất kỳ view nào đang test.
 * 
 * Ví dụ: thêm vào đầu allgroup.php:
 *   require_once '../../api/core/dev_mock_session.php';
 * 
 * Hoặc bật AUTO_MOCK bên dưới để apply toàn bộ (qua base.php).
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Đổi các giá trị này cho phù hợp với DB của bạn ──
$_SESSION['user_id']   = 3;          // idTK của tài khoản test (sinh viên)
$_SESSION['user_role'] = 'student';  // 'student' | 'lecturer' | 'admin'
$_SESSION['user_name'] = 'Dev Test User';

/*
 * Các idTK có thể dùng để test (xem bảng taikhoan):
 *   Sinh viên:  idLoaiTK = 3  → test tạo nhóm, xin vào nhóm, nộp bài
 *   Giảng viên: idLoaiTK = 2  → test mời GVHD, duyệt yêu cầu
 *
 * Cách tìm idTK: SELECT idTK, idLoaiTK FROM taikhoan WHERE isActive = 1 LIMIT 10;
 */