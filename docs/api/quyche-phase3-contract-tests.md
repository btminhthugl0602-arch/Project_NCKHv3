# Phase 3 - Contract tests cho loai quy che va ngu canh

Muc tieu:
- Loai quy che khong con nhap tu do (governance theo danh muc chuan).
- Metadata bat buoc filter theo loai + ngu canh da chon.
- FE/BE dung chung contract query/body.

## Contract chinh

### 1) Metadata API
Endpoint:
- GET /api/su_kien/quy_che_metadata.php

Query bat buoc:
- id_sk: int > 0
- loai_quy_che: mot trong danh muc chuan
- ma_ngu_canh: CSV context (cho phep nhieu)

Response data quan trong:
- selected_loai_quy_che
- selected_ngu_canh
- filter_loai_ap_dung
- loai_quy_che_catalog
- ngu_canh_ap_dung

### 2) Save Rule API
Endpoint:
- POST /api/su_kien/luu_quy_che.php

Body bat buoc:
- id_sk
- ten_quy_che
- loai_quy_che (governed)
- ngu_canh_ap_dung (array, >= 1)
- rules_json

## Test cases

### TC1 - Metadata fail khi thieu loai_quy_che
Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/quy_che_metadata.php?id_sk=800&ma_ngu_canh=DANG_KY_THAM_GIA_SV" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expect:
- HTTP 400
- status=error
- message bao loi thieu/sai loai_quy_che

### TC2 - Metadata fail khi thieu ma_ngu_canh
Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/quy_che_metadata.php?id_sk=800&loai_quy_che=THAMGIA_SV" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expect:
- HTTP 400
- status=error
- message bao loi thieu ma_ngu_canh

### TC3 - Metadata fail khi context ngoai danh muc
Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/quy_che_metadata.php?id_sk=800&loai_quy_che=THAMGIA_SV&ma_ngu_canh=FAKE_CONTEXT" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expect:
- HTTP 400
- status=error
- message bao loi context khong thuoc danh muc chuan

### TC4 - Metadata success voi filter hop le
Request:

```bash
curl -i "http://localhost/Project_NCKHv3/api/su_kien/quy_che_metadata.php?id_sk=800&loai_quy_che=THAMGIA_SV&ma_ngu_canh=DANG_KY_THAM_GIA_SV" \
  -H "Cookie: PHPSESSID=COOKIE_BTC"
```

Expect:
- HTTP 200
- status=success
- data.selected_loai_quy_che = THAMGIA_SV
- data.selected_ngu_canh co DANG_KY_THAM_GIA_SV
- data.filter_loai_ap_dung khong rong
- data.loai_quy_che_catalog khong rong

### TC5 - Save fail khi loai_quy_che ngoai governance
Request:

```bash
curl -i -X POST "http://localhost/Project_NCKHv3/api/su_kien/luu_quy_che.php" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=COOKIE_BTC" \
  -d '{
    "id_sk": 800,
    "ten_quy_che": "QC test invalid type",
    "loai_quy_che": "FREE_TEXT_TYPE",
    "ngu_canh_ap_dung": ["DANG_KY_THAM_GIA_SV"],
    "rules_json": {"type":"RULE","idThuocTinhKiemTra":1,"idToanTu":3,"giaTriSoSanh":"2.5"}
  }'
```

Expect:
- HTTP 400
- status=error
- message: loai_quy_che khong nam trong danh muc chuan

### TC6 - Save success voi multi-context
Request:

```bash
curl -i -X POST "http://localhost/Project_NCKHv3/api/su_kien/luu_quy_che.php" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=COOKIE_BTC" \
  -d '{
    "id_sk": 800,
    "ten_quy_che": "QC test multi context",
    "loai_quy_che": "VONGTHI",
    "ngu_canh_ap_dung": ["DUYET_VONG_THI", "DUYET_VONG_THI_HANG_LOAT"],
    "rules_json": {"type":"RULE","idThuocTinhKiemTra":3,"idToanTu":5,"giaTriSoSanh":"5"}
  }'
```

Expect:
- HTTP 200
- status=success
- data.nguCanhApDung co du 2 context
