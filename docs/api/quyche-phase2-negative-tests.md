# Phase 2 - Negative Authorization Tests (Quy che metadata)

Muc tieu: xac nhan user khong co quyen su kien khong the truy cap metadata/goi y, va khong the doc field ngoai whitelist an toan.

## Precondition
- Co 2 session/cookie:
  - `COOKIE_BTC`: tai khoan co quyen cauhinh_sukien o su kien test.
  - `COOKIE_NO_PERMISSION`: tai khoan da login nhung KHONG co quyen cauhinh_sukien o su kien test.
- Su kien test: `id_sk=800`.

## Test 1 - Metadata bi chan khi khong co quyen

Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/quy_che_metadata.php?id_sk=800&loai_ap_dung=VONGTHI" \
  -H "Cookie: PHPSESSID=COOKIE_NO_PERMISSION"
```

Expected:
- HTTP `403`
- JSON `status=error`
- Message chua noi dung khong co quyen truy cap metadata.

## Test 2 - Goi y gia tri bi chan khi khong co quyen

Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/goi_y_gia_tri_thuoc_tinh.php?id_sk=800&id_thuoc_tinh=3" \
  -H "Cookie: PHPSESSID=COOKIE_NO_PERMISSION"
```

Expected:
- HTTP `403`
- JSON `status=error`
- Message chua noi dung khong co quyen truy cap goi y.

## Test 3 - Thuoc tinh ngoai whitelist bi chan (anti-pentest)

Request (dung account co quyen):

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/goi_y_gia_tri_thuoc_tinh.php?id_sk=800&id_thuoc_tinh=7" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expected:
- HTTP `403`
- JSON `status=error`
- Message: `Thuoc tinh khong nam trong danh muc goi y an toan`.

Ghi chu: `id_thuoc_tinh=7` (sanpham.idloaitailieu) duoc xem la ngoai whitelist an toan.

## Test 4 - Positive sanity check (khong phai negative)

Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/goi_y_gia_tri_thuoc_tinh.php?id_sk=800&id_thuoc_tinh=3" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expected:
- HTTP `200`
- JSON `status=success`
- Co mang `data.goi_y`.
