# Database Notes

## Nguồn schema chính
- Dùng duy nhất file `database/schema.sql` làm chuẩn thiết kế dữ liệu.

## Quy tắc thay đổi CSDL
- Không sửa cấu trúc DB trực tiếp trong runtime API.
- Mọi thay đổi schema cần tạo migration SQL riêng trong `database/migrations/`.
- Đặt tên migration theo timestamp + nội dung thay đổi.

Ví dụ:
- `2026_03_02_add_index_for_thongbao.sql`
- `2026_03_02_update_vaitro_constraints.sql`

## Kiểm tra trước khi viết API
- Xác định rõ:
  - khóa chính
  - khóa ngoại
  - ràng buộc enum
  - ràng buộc null/not null
