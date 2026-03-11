# ezManagement — Changelog

## [Session Unification + Quản lý Tài khoản] — 2026-03-07

### Tóm tắt

Hai thay đổi lớn trong lần này:

1. **Thống nhất session toàn hệ thống** — loại bỏ xung đột giữa các key cũ (`user_id`, `user_name`, `user_role`) và key mới (`idTK`)
2. **Module Quản lý Tài khoản** — trang admin mới với đầy đủ UI + API backend

---

## 1. Thống nhất Session (Hướng B — Replace toàn bộ)

### Vấn đề

Hệ thống tồn tại 3 "phong cách" session song song, viết ở các thời điểm khác nhau:

| Key cũ | Set ở đâu | Vấn đề |
|--------|-----------|--------|
| `$_SESSION['user_id']` | `dev_mock_session.php` | 19 file API dùng key này, sau đăng nhập thật → luôn = 0 |
| `$_SESSION['user_name']` | Không ai set | navbar luôn hiển thị "Tài khoản" |
| `$_SESSION['user_role']` | Không ai set | sidebar không hiện menu đúng vai trò |

`dang_nhap.php` chỉ set `$_SESSION['idTK']` — không đủ để layout và API hoạt động.

### Giải pháp

Chuẩn hoá về **3 key duy nhất**, set đầy đủ ngay khi đăng nhập:

```php
$_SESSION['idTK']     = (int) $taiKhoan['idTK'];
$_SESSION['idLoaiTK'] = (int) $taiKhoan['idLoaiTK'];  // 1=Admin, 2=GV, 3=SV
$_SESSION['hoTen']    = $hoTen;  // JOIN từ sinhvien.tenSV / giangvien.tenGV
```

### Files thay đổi

#### `api/tai_khoan/dang_nhap.php`
- Thêm query JOIN lấy họ tên từ `sinhvien`/`giangvien` sau khi xác thực thành công
- Set đủ 3 key: `idTK`, `idLoaiTK`, `hoTen`
- Fallback `hoTen` về `tenTK` nếu không có profile

#### `layouts/navbar.php`
- Bỏ đọc `$_SESSION['user_name']` và `$_SESSION['user_role']`
- Đọc `$_SESSION['hoTen']` để hiển thị tên người dùng
- Map `$_SESSION['idLoaiTK']` → nhãn vai trò (1=Quản trị viên, 2=Giảng viên, 3=Sinh viên)

#### `layouts/sidebar.php`
- `$_SESSION['user_role'] === 'student'` → `(int)$_SESSION['idLoaiTK'] === 3`
- `in_array($role, ['lecturer','admin'])` → `in_array($idLoaiTK, [1, 2])`
- Thêm section "Hệ thống" + link Quản lý Tài khoản (chỉ hiện khi có quyền `quan_ly_tai_khoan`)

#### `api/su_kien/` — 12 files
Tất cả thay `$_SESSION['user_id']` → `$_SESSION['idTK']`:
- `cap_nhat_su_kien.php`
- `chi_tiet_bo_tieu_chi.php`
- `chi_tiet_quy_che.php`
- `danh_sach_quy_che.php`
- `danh_sach_vong_thi.php`
- `du_lieu_bo_tieu_chi.php`
- `luu_bo_tieu_chi.php`
- `luu_quy_che.php`
- `tao_su_kien.php`
- `tao_vong_thi.php`
- `xoa_bo_tieu_chi.php`
- `xoa_quy_che.php`

#### `api/nhom/` — 7 files
Tất cả thay `$_SESSION['user_id']` → `$_SESSION['idTK']`:
- `duyet_yeu_cau.php`
- `getallnhom.php`
- `getmygroup.php`
- `getrequest.php`
- `gui_yeu_cau.php`
- `roinhom.php`
- `taonhom.php`

#### `api/core/base.php`
- Xoá block alias `user_id ↔ idTK` (không cần sau khi đã replace trực tiếp)
- Cập nhật comment guard trong `main_layout.php` và `index.php`

#### `api/core/dev_mock_session.php`
- Cập nhật theo key mới: `idTK`, `idLoaiTK`, `hoTen`
- Xoá `user_id`, `user_role`, `user_name`

#### `views/event-detail.php`
- Xoá dòng hardcode `$_SESSION['user_id'] = 1` (TODO đã giải quyết)

---

## 2. Module Quản lý Tài khoản

### Tổng quan

Trang mới `/admin/users` cho phép admin (có quyền `quan_ly_tai_khoan`) quản lý toàn bộ tài khoản trong hệ thống.

### Tính năng

- **Danh sách tài khoản** — bảng với skeleton loading, filter theo loại, search real-time (client-side)
- **Stats row** — 4 card đếm tổng / admin / giảng viên / sinh viên
- **Tạo tài khoản** — modal với form động theo loại (Admin / Giảng viên / Sinh viên), validation inline
- **Chi tiết tài khoản** — slide-over panel bên phải, 2 tab: Thông tin + Phân quyền
- **Khóa / Mở khóa** — confirm dialog, không thể tự khóa mình
- **Phân quyền** — 3 checkbox quyền HE_THONG, disabled nếu là Admin (auto full quyền)

### Files mới

#### `views/admin_users.php`
- Guard: check `$_SESSION['idTK']` + `kiem_tra_quyen_he_thong(..., 'quan_ly_tai_khoan')`
- Truyền `window.ADMIN_USERS_CONFIG = { idTKHienTai, basePath }` xuống JS
- HTML: stats row, bảng + skeleton 5 dòng, modal tạo TK, slide-over chi tiết

#### `assets/js/admin_users.js`
- IIFE pattern (giống `scoring.js`)
- State management: `dsTaiKhoan`, `filterLoai`, `searchQuery`, `selectedIdTK`
- SweetAlert2 Toast mixin cho notification
- SweetAlert2 confirm dialog cho khóa/mở khóa
- Filter + search hoàn toàn client-side (không gọi API thêm)
- Slide-over load fresh data mỗi lần mở (`chi_tiet.php`)

#### `assets/css/admin_users.css`
- Slide-over transition `cubic-bezier(0.4, 0, 0.2, 1)`
- Skeleton shimmer animation
- Row focus-visible cho keyboard navigation

#### `api/tai_khoan/danh_sach.php`
- `GET` — JOIN 6 bảng, trả mảng tài khoản kèm `dsQuyen[]`

#### `api/tai_khoan/chi_tiet.php`
- `GET ?id=X` — chi tiết 1 tài khoản + profile + quyền active

#### `api/tai_khoan/danh_sach_lop_khoa.php`
- `GET` — trả `{ dsLop, dsKhoa }` cho dropdown modal tạo TK

#### `api/tai_khoan/tao_tai_khoan.php`
- `POST` — wrapper `admin_tao_tai_khoan()`, xử lý thêm `hocHam` cho GV

#### `api/tai_khoan/khoa_mo_tai_khoan.php`
- `POST { id_tai_khoan, action: "khoa"|"mo" }` — gọi hàm tương ứng

#### `api/tai_khoan/cap_nhat_quyen.php`
- `POST { id_tai_khoan, danh_sach_ma_quyen[] }` — wrapper `admin_cap_nhat_quyen_tai_khoan()`

#### `api/tai_khoan/quan_ly_tai_khoan.php`
- Thêm hàm `admin_mo_tai_khoan()` — mirror của `admin_khoa_tai_khoan()`, set `isActive = 1`

---

## Session Keys — Tài liệu tham khảo

| Key | Kiểu | Set ở đâu | Dùng ở đâu |
|-----|------|-----------|------------|
| `$_SESSION['idTK']` | `int` | `dang_nhap.php` | Guard auth, tất cả API |
| `$_SESSION['idLoaiTK']` | `int` | `dang_nhap.php` | `navbar.php`, `sidebar.php` |
| `$_SESSION['hoTen']` | `string` | `dang_nhap.php` | `navbar.php` |

**idLoaiTK mapping:**
- `1` = Quản trị viên
- `2` = Giảng viên
- `3` = Sinh viên
