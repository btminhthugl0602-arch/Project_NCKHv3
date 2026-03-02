# Core Functions (`api/core`)

## 1) `db_connect.php`
### `get_db_connection(): PDO`
- Trả kết nối `PDO` singleton.
- Dùng biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- Mặc định DB: `nckh`.

### `$conn`
- Biến kết nối dùng chung toàn module.
- Có thể truyền trực tiếp vào các hàm trong service file.

## 2) `base.php` - nhóm helper dữ liệu

### `_insert_info($conn, $table, $fields, $values): bool`
- Chèn 1 bản ghi.
- `$fields` và `$values` phải cùng số phần tử.
- Tự động dùng placeholder `?` và execute prepared statement.

### `_update_info($conn, $table, $fields, $values, $conditions): bool`
- Cập nhật dữ liệu theo điều kiện.
- `conditions` dạng map:
```php
[
  'idTK' => ['=', 10, 'AND'],
  'isActive' => ['=', 1, '']
]
```

### `_select_info($conn, $table, $fields = [], $conditions = []): array|false`
- Truy vấn danh sách dữ liệu.
- Hỗ trợ `WHERE`, `ORDER BY`, `LIMIT`.
- `WHERE` dạng chunk 4 phần tử (cột, toán tử, giá trị, logic):
```php
[
  'WHERE' => ['idSK', '=', 11, 'AND', 'isActive', '=', 1, ''],
  'ORDER BY' => ['ngaytao', 'DESC'],
  'LIMIT' => [20]
]
```

### `_delete_info($conn, $table, $conditions): bool`
- Xóa dữ liệu theo điều kiện.
- Không cho phép xóa toàn bảng (conditions rỗng).

### `_is_exist($conn, $table, $field, $value): bool`
- Kiểm tra tồn tại bản ghi đơn giản.

## 3) `base.php` - nhóm helper auth/quyền

### `kiem_tra_quyen_he_thong($conn, $id_tai_khoan, $ma_quyen_code): bool`
- Check quyền hệ thống theo `taikhoan_quyen` + `quyen.maQuyen_code`.
- Admin (`idLoaiTK = 1`) có full quyền.

### `kiem_tra_quyen_su_kien($conn, int $idTK, int $idSK, string $maQuyenCode): bool`
- Check quyền theo sự kiện qua:
  - `taikhoan_vaitro_sukien`
  - `vaitro_quyen`
  - `quyen`

### `anh_xa_ma_quyen($conn, $ma_quyen_code): ?int`
- Trả `idQuyen` theo `maQuyen_code` (fallback `maQuyen` nếu cần tương thích).

## 4) Lưu ý triển khai
- Luôn cast kiểu rõ ràng (`(int)`, `trim((string)...)`) trước khi gọi helper.
- Ưu tiên kiểm tra tồn tại dữ liệu liên quan trước insert/update.
- Bao transaction cho nghiệp vụ nhiều bước có quan hệ phụ thuộc.
