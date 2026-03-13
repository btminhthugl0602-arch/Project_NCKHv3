# API Documentation - ezManagement

## 1) Mục tiêu tài liệu
Bộ tài liệu này mô tả **cách sử dụng các hàm API hiện có** trong thư mục `api/` và **cách triển khai mở rộng** theo chuẩn của dự án.

## 2) Chuẩn bắt buộc khi viết API
- Dùng `PDO` và prepared statements (không dùng `mysqli_*`).
- Trả response JSON thống nhất:
  - `status`: `success` hoặc `error`
  - `message`: mô tả kết quả
  - `data`: dữ liệu trả về
- Tuân thủ schema trong `database/schema.sql`.
- Không render HTML trong backend API.

## 3) Cấu trúc API hiện tại
- `api/core`: hàm lõi dùng chung (DB, CRUD helper, auth/permission helper).
- `api/su_kien`: logic sự kiện, vòng thi, quy chế, tổ chức.
- `api/nhom`: logic quản lý nhóm và yêu cầu tham gia.
- `api/tai_khoan`: logic quản trị tài khoản và quyền.

## 4) Cách dùng các file hàm nghiệp vụ
Các file hiện tại là **service function** (chưa phải endpoint REST đầy đủ). Khi tạo endpoint mới:
1. Include `api/core/base.php`.
2. Parse input JSON từ request.
3. Gọi hàm nghiệp vụ tương ứng.
4. Trả JSON theo format chuẩn.

Ví dụ skeleton endpoint:

```php
<?php
require_once __DIR__ . '/../core/base.php';
require_once __DIR__ . '/quan_ly_su_kien.php';

requireMethod('POST');
$input = getJsonInput();

$result = btc_tao_su_kien(
    $conn,
    (int)($_SESSION['user_id'] ?? 0),
    $input['tenSK'] ?? '',
    $input['moTa'] ?? '',
    $input['idCap'] ?? null,
    $input['ngayMoDangKy'] ?? null,
    $input['ngayDongDangKy'] ?? null,
    $input['ngayBatDau'] ?? null,
    $input['ngayKetThuc'] ?? null,
    $input['isActive'] ?? 1,
    isset($input['co_gvhd_theo_su_kien']) ? (int)$input['co_gvhd_theo_su_kien'] : 1
);

if (($result['status'] ?? false) === true) {
    apiSuccess($result['message'] ?? 'Thành công', $result);
}

apiError($result['message'] ?? 'Có lỗi xảy ra', $result, 400);
```

## 5) Tài liệu chi tiết
- `docs/api/core-functions.md`
- `docs/api/su-kien-functions.md`
- `docs/api/nhom-functions.md`
- `docs/api/tai-khoan-functions.md`
- `docs/api/quyche-phase2-negative-tests.md`
- `docs/api/quyche-phase3-contract-tests.md`
- `docs/api/quyche-runtime-observability.md`

## 6) Ghi chú contract mới (Pha 1)
- Payload tạo/cập nhật sự kiện hỗ trợ `co_gvhd_theo_su_kien`:
  - `1`: sự kiện có luồng GVHD
  - `0`: sự kiện không có luồng GVHD
- Nếu không truyền field này, backend mặc định `1` để giữ tương thích với client cũ.
