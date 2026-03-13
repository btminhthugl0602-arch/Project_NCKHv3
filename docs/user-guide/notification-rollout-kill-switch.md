# Notification Rollout + Kill-Switch

## Muc tieu
Tai lieu nay mo ta cach rollout module thong bao theo tung doi tuong, cach giam sat sau deploy, va cach tat nhanh trigger bang feature flag khi can.

## Feature Flags
Cau hinh trong `configs/config.php`:
- `NOTIFICATION_FLAG_EVENT_CLUSTER`
- `NOTIFICATION_FLAG_GROUP_CLUSTER`
- `NOTIFICATION_FLAG_SCORING_CLUSTER`

Gia tri khuyen nghi:
- `true`: bat trigger thong bao cho cum nghiep vu tuong ung.
- `false`: tat trigger thong bao, business flow chinh van tiep tuc binh thuong.

## Thu tu rollout de xuat
1. BTC (quan tri su kien)
- Bat `NOTIFICATION_FLAG_EVENT_CLUSTER = true`
- Theo doi thong bao tao su kien, mo/dong vong thi, doi trang thai su kien.

2. Giang vien / Giam khao
- Bat `NOTIFICATION_FLAG_SCORING_CLUSTER = true`
- Theo doi thong bao phan cong cham, moi trong tai, nhac nho cham diem.

3. Sinh vien / Nhom
- Bat `NOTIFICATION_FLAG_GROUP_CLUSTER = true`
- Theo doi thong bao loi moi nhom, duyet/tu choi yeu cau, nop/cap nhat san pham.

## Checklist smoke test sau moi lan bat flag
1. Mo navbar, kiem tra chuong thong bao hien badge dung.
2. Tao 1 nghiep vu mau trong cum vua bat, xac nhan inbox nhan duoc thong bao.
3. Thu `mark_read` va `mark_all_read`, xac nhan unread count cap nhat.
4. Thu bam thong bao de deep-link den trang lien quan.

## Monitoring va nguong canh bao
Theo doi log PHP va he thong sau deploy:
- Ty le loi API thong bao (`status=error`) theo 15 phut.
- Thoi gian phan hoi endpoint:
  - `api/thong_bao/inbox.php`
  - `api/thong_bao/giam_khao.php`
- So lan trigger fail trong cac module nghiep vu (event/group/scoring).

Nguong de xu ly:
- Error rate > 5% trong 15 phut lien tuc.
- Thoi gian phan hoi p95 > 1500ms cho inbox API.
- User report khong nhan duoc thong bao o luong nghiep vu quan trong.

## Kill-Switch Procedure
Khi can tat nhanh module thong bao ma khong anh huong nghiep vu chinh:
1. Chinh `configs/config.php` va set flag cum gap su co ve `false`.
2. Clear opcode cache neu server co su dung opcache.
3. Xac nhan nghiep vu chinh van chay (tao su kien, nop bai, cham diem).
4. Kiem tra API thong bao trong cum do tra thong diep feature-flag dang tat.
5. Mo ticket root-cause va ghi nhan trong changelog.

## Rollback / Re-enable
1. Sau khi fix, bat lai tung cum theo dung thu tu BTC -> GV -> SV.
2. Chay lai smoke test checklist cho moi cum.
3. Theo doi toi thieu 30 phut truoc khi bat cum tiep theo.
