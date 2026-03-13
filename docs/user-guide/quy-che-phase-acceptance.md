# Quy trinh chuan hoa module Quy che theo phase

Tai lieu nay dung de nghiem thu tung phase voi tieu chi done ro rang.

## Phase 1 - Chuan hoa du lieu loaiApDung

### Muc tieu
- Dong bo gia tri `thuoctinh_kiemtra.loaiApDung` voi evaluator moi.
- Loai bo lech du lieu legacy `THAMGIA`.

### Thay doi
- Chay migration: `database/migrations/2026_03_13_normalize_thuoctinh_loaiapdung.sql`.
- Them fallback tuong thich trong backend khi gap du lieu cu.

### Done criteria
- Khong con ban ghi `thuoctinh_kiemtra.loaiApDung = 'THAMGIA'`.
- API metadata loc theo `loai_ap_dung=THAMGIA` van tra du ca `THAMGIA_SV` va `THAMGIA_GV`.
- Dang ky tham gia SV/GV khong bi loi do mismatch loai ap dung.

## Phase 2 - Quan tri danh muc ngu canh

### Muc tieu
- Khong de context free-text troi noi.
- Rang buoc ngu canh ap dung vao danh muc chuan.

### Thay doi
- Chay migration: `database/migrations/2026_03_13_add_quyche_context_catalog.sql`.
- Them bang danh muc `quyche_danhmuc_ngucanh`.
- Backfill context cho quy che cu tu `loaiQuyChe`.
- Them FK `quyche_ngucanh_apdung.maNguCanh` -> `quyche_danhmuc_ngucanh.maNguCanh`.
- API metadata tra them `ngu_canh_ap_dung`.

### Done criteria
- Tao quy che voi context khong thuoc danh muc bi chan dung thong bao loi ro rang.
- Quy che cu van co context (sau backfill), khong mat kha nang xet duyet.
- FK ton tai va khong con context mo coi.

## Phase 3 - Ap dung quy che vao diem nghiep vu

### Muc tieu
- Thuc thi quy che theo context tai dung vi tri nghiep vu.

### Thay doi
- `approve_score_auto`: context `DUYET_VONG_THI`.
- `approve_multiple`: context `DUYET_VONG_THI_HANG_LOAT`.
- Bo xu ly duyet tra ve danh sach vi pham quy che chi tiet.

### Done criteria
- Bai dat quy che -> trang thai `Da duyet`.
- Bai khong dat quy che -> trang thai `Bi loai`, API tra `viPham` co thong tin dieu kien fail.
- Duyet hang loat co thong ke bai vi pham quy che trong response.

## Phase 4 - Explain-fail va tuong thich nguoc

### Muc tieu
- Tang kha nang debug quy che khi fail.
- Giu he thong khong vo khi du lieu cu chua chay migration ngay.

### Thay doi
- Evaluator tra them `chiTiet` cho moi quy che vi pham (thuoc tinh, toan tu, gia tri ky vong, gia tri thuc te, ly do).
- Co fallback legacy: neu chua gan context mapping thi van map qua `loaiQuyChe`.

### Done criteria
- API xet quy che co du lieu giai thich fail o muc dieu kien don.
- He thong van chay an toan ca truoc va sau migration.

## Phase 5 - Nghiem thu tong va van hanh

### Checklist nghiem thu nhanh
1. Chay het migration theo thu tu:
   1. `2026_03_13_add_quyche_context_mapping.sql`
   2. `2026_03_13_normalize_thuoctinh_loaiapdung.sql`
   3. `2026_03_13_add_quyche_context_catalog.sql`
2. Tao 1 quy che context `DANG_KY_THAM_GIA_SV` va test SV dang ky.
3. Tao 1 quy che context `DUYET_VONG_THI` va test duyet auto.
4. Tao 1 quy che context `DUYET_VONG_THI_HANG_LOAT` va test duyet multiple.
5. Xac nhan response co `viPham[].chiTiet[]` khi fail.

### Done criteria
- 100% test checklist tren pass.
- Khong phat sinh loi schema mismatch trong log API.
- Co tai lieu changelog + tai lieu nghiem thu phase de ban giao.
