# Database Notes

## Nguồn schema chính
- Dùng duy nhất file `database/schema.sql` làm chuẩn thiết kế dữ liệu.

## Contract dữ liệu mới (Pha 1 - GVHD theo sự kiện)
- Bảng `sukien` bổ sung cột `coGVHDTheoSuKien` (`tinyint`, `0|1`, mặc định `1`).
- Ý nghĩa:
  - `1`: sự kiện có luồng GVHD.
  - `0`: sự kiện không có luồng GVHD.
- Cột này là nguồn sự thật để bật/tắt luồng GVHD toàn sự kiện, không suy diễn từ các cột giới hạn.
- Các cột cũ `soGVHDToiDa`, `soNhomToiDaGVHD`, `yeuCauCoGVHD` vẫn giữ để tương thích trong giai đoạn chuyển tiếp.

## Quy tắc thay đổi CSDL
- Không sửa cấu trúc DB trực tiếp trong runtime API.
- Mọi thay đổi schema cần tạo migration SQL riêng trong `database/migrations/`.
- Đặt tên migration theo timestamp + nội dung thay đổi.

Quy tắc chuyển đổi cho đợt này:
- Migration thêm cột phải đặt mặc định `1` để không làm thay đổi hành vi event hiện có.
- Không dùng `soGVHDToiDa = 0` làm thiết kế chính thức; chỉ cho phép làm fallback legacy ngắn hạn khi chưa migrate.

Ví dụ:
- `2026_03_02_add_index_for_thongbao.sql`
- `2026_03_02_update_vaitro_constraints.sql`

## Kiểm tra trước khi viết API
- Xác định rõ:
  - khóa chính
  - khóa ngoại
  - ràng buộc enum
  - ràng buộc null/not null
