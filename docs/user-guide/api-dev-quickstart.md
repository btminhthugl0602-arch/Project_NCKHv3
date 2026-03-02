# API Dev Quickstart

## 1) Tạo endpoint mới
1. Tạo file trong đúng module (`api/su_kien`, `api/nhom`, `api/tai_khoan`, ...).
2. Include `api/core/base.php`.
3. Validate method và input.
4. Gọi hàm service trong file nghiệp vụ.
5. Trả JSON chuẩn (`status`, `message`, `data`).

## 2) Mẫu xử lý request

```php
requireMethod('POST');
$input = getJsonInput();

$result = some_service_function($conn, ...);

if (($result['status'] ?? false) === true) {
    apiSuccess($result['message'] ?? 'Thành công', $result);
}

apiError($result['message'] ?? 'Thất bại', $result, 400);
```

## 3) Checklist trước khi merge
- [ ] Không còn `mysqli_*`.
- [ ] Không còn SQL nối chuỗi trực tiếp.
- [ ] Có check quyền phù hợp (`admin_users`, `admin_events`, `cauhinh_*`, ...).
- [ ] Có transaction với nghiệp vụ nhiều bước.
- [ ] Có test thủ công request thành công và lỗi.
