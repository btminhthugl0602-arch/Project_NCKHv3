# CHANGELOG

## 2026-03-02

### Added
- Tạo bộ tài liệu `docs/` theo cấu trúc:
  - `docs/api/README.md`
  - `docs/api/core-functions.md`
  - `docs/api/su-kien-functions.md`
  - `docs/api/nhom-functions.md`
  - `docs/api/tai-khoan-functions.md`
  - `docs/database/README.md`
  - `docs/user-guide/api-dev-quickstart.md`

### Notes
- Tài liệu mô tả cách sử dụng hàm API hiện có và hướng triển khai endpoint theo chuẩn PDO + JSON response thống nhất.

### Changed
- Nâng giao diện nút `Tạo sự kiện` tại trang quản lý sự kiện để hiển thị nổi bật hơn (vùng màu bao quanh rõ hơn).
- Cập nhật form tạo sự kiện trong `SweetAlert`: thay nhập tay `ID cấp` bằng dropdown chọn theo `tên cấp tổ chức`.
- Bổ sung API `GET /api/su_kien/danh_sach_cap_to_chuc.php` để frontend lấy danh sách cấp tổ chức động từ bảng `cap_tochuc` + `loaicap`.
- Thu gọn kích thước modal form tạo sự kiện và làm rõ màu sắc nút xác nhận `Tạo sự kiện` trong form.
- Bổ sung danh sách sự kiện ngay trên trang `events` và thêm API `GET /api/su_kien/danh_sach_su_kien.php` để tải dữ liệu ban đầu.
- Sau khi tạo sự kiện thành công, frontend tự append dòng sự kiện mới vào danh sách tại chỗ (không reload trang).
- Tinh chỉnh lại style nút: nút mở `Tạo sự kiện` hiển thị gradient rõ ràng hơn, nút `Huỷ` trong modal giảm bo góc để cân đối giao diện.
- Tối ưu lại bố cục modal tạo sự kiện theo hướng gọn hơn (không kéo dài theo chiều dọc) và tăng kích thước nút `Tạo sự kiện` / `Huỷ` để tương xứng tổng thể form.
- Bổ sung cột `Hành động` trong danh sách sự kiện với nút `Xem` để xem chi tiết từng sự kiện riêng.
- Thêm API `GET /api/su_kien/chi_tiet_su_kien.php?id_sk=...` phục vụ popup chi tiết sự kiện.
- Điều chỉnh UX chi tiết sự kiện: bỏ nút `Xem`, chuyển thành click trực tiếp vào tên sự kiện để điều hướng.
- Tạo trang riêng `/event-detail?id_sk=...` để hiển thị chi tiết sự kiện với không gian cấu hình sâu hơn.
- Bổ sung style riêng cho link tên sự kiện (tone tím mặc định, hover tím đậm + underline) để người dùng nhận biết thao tác rõ ràng hơn.
- Khi vào trang chi tiết sự kiện, sidebar trái chuyển sang menu chức năng theo ngữ cảnh sự kiện (tổng quan, cấu hình, bài nộp, review, tiểu ban/BGK).
- Tên sự kiện ở đầu sidebar được cập nhật động theo dữ liệu sự kiện đang mở.
- Sửa lỗi hiển thị màu/chữ/icon ở sidebar ngữ cảnh sự kiện bằng cách dùng bộ class màu có sẵn của theme và icon tương thích.
- API chi tiết sự kiện `chi_tiet_su_kien.php` được refactor sang dùng hàm service `btc_lay_chi_tiet_su_kien()` thay cho query SQL viết tay; dữ liệu chi tiết trả về bổ sung `tenLoaiCap` từ service layer.
- Triển khai “tab thật” cho trang chi tiết sự kiện: mỗi mục sidebar (`overview`, `config`, `submissions`, `review-assign`, `review-results`, `committees`, `judges`) hiển thị nội dung riêng theo tab, không chỉ highlight menu.
