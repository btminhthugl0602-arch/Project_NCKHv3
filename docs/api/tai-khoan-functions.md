# Tài Khoản Module (`api/tai_khoan/quan_ly_tai_khoan.php`)

## 1) Quyền quản trị
- Các hàm admin đều check quyền `admin_users`.

## 2) Tạo tài khoản
### `admin_tao_tai_khoan(...)`
- Tạo bản ghi `taikhoan` trước.
- Mật khẩu lưu bằng `password_hash`.
- Tự động tạo profile theo loại tài khoản:
  - Sinh viên (`idLoaiTK=3`) -> `sinhvien`
  - Giảng viên (`idLoaiTK=2`) -> `giangvien`
- Chạy transaction toàn bộ.

### `tao_tai_khoan_sinh_vien(...)`
- Check trùng `MSV`.
- Xác thực `idLop` tồn tại rồi lấy `idKhoa` từ `lop`.

### `tao_tai_khoan_giang_vien(...)`
- Tạo profile giảng viên, có kiểm tra khoa nếu truyền vào.

## 3) Khóa tài khoản
### `admin_khoa_tai_khoan(...)`
- Không cho self-lock.
- Update `taikhoan.isActive = 0`.

## 4) Gán/cập nhật quyền
### `admin_gan_quyen_cho_tai_khoan(...)`
- Map `maQuyen_code` -> `idQuyen`.
- Upsert vào `taikhoan_quyen`.

### `admin_cap_nhat_quyen_tai_khoan(...)`
- Disable toàn bộ quyền cũ (`isActive = 0`).
- Gán lại theo danh sách quyền mới trong transaction.

## 5) Danh sách mã quyền hệ thống thường dùng
- `admin_users`
- `admin_events`
- `admin_criteria`
- `admin_reports`
- `tao_su_kien`
