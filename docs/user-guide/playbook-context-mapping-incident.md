# Playbook su co mapping context quy che

## Muc tieu
Xu ly nhanh khi ty le evaluate_rules loi tang dot bien do mapping context.

## Dau hieu nhan biet
- Alert evaluate_rules_error_rate vuot nguong.
- Log evaluate_rules co status=error tang manh.
- User phan anh approve_score_auto/approve_multiple bi loi bat thuong.

## Buoc 1: Xac dinh pham vi
1. Loc log theo maNguCanh trong 30 phut gan nhat.
2. Kiem tra context nao tang loi manh nhat.
3. Kiem tra idSK bi anh huong nhieu nhat.

## Buoc 2: Kiem tra du lieu mapping
1. Kiem tra danh muc context:
```sql
SELECT maNguCanh, tenNguCanh, isHeThong
FROM quyche_danhmuc_ngucanh
ORDER BY maNguCanh;
```
2. Kiem tra mapping quy che -> context:
```sql
SELECT q.idQuyChe, q.tenQuyChe, q.loaiQuyChe, n.maNguCanh
FROM quyche q
LEFT JOIN quyche_ngucanh_apdung n ON n.idQuyChe = q.idQuyChe
WHERE q.idSK = :idSK
ORDER BY q.idQuyChe;
```
3. Kiem tra context co nam trong danh muc chuan hay khong.

## Buoc 3: Kiem tra contract FE/BE
1. Xac nhan FE gui day du `loai_quy_che` va `ma_ngu_canh` khi lay metadata.
2. Xac nhan API tra 422 khi thieu filter, khong fallback metadata rong.
3. Kiem tra changelog/version deploy gan nhat.

## Buoc 4: Giam tac dong
1. Neu context bi sai do du lieu: sua mapping va re-run testcase contract.
2. Neu context moi chua map: bo sung mapping governance trong backend + danh muc context.
3. Neu su co he thong: fallback tam bang cach vo hieu hoa context loi tren event bi anh huong.

## Buoc 5: Xac nhan phuc hoi
1. Theo doi evaluate_rules_error_rate trong 30-60 phut.
2. Run test regression approve_score_auto, approve_multiple.
3. Cap nhat postmortem: nguyen nhan goc, hanh dong phong ngua.

## Checklist chong tai phat
- [ ] Context moi duoc bo sung vao danh muc chuan.
- [ ] Mapping context -> loaiApDung cap nhat dong bo FE/BE.
- [ ] Test contract metadata/save da pass trong CI.
- [ ] Alert threshold duoc review lai neu can.
