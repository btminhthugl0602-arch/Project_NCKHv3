# Sự kiện Module (`api/su_kien`)

## 1) `quan_ly_su_kien.php`
### `btc_tao_su_kien(...)`
- Tạo sự kiện mới trong `sukien`.
- Validate dữ liệu cơ bản: tên sự kiện, mốc thời gian, `idCap`.
- Tự động gán vai trò BTC cho người tạo vào `taikhoan_vaitro_sukien` (`idVaiTro=1`, `nguonTao='BTC_THEM'`).
- Nếu sự kiện active thì gửi thông báo tới giảng viên/sinh viên.

### `_gui_thong_bao_su_kien_moi(...)`
- Tạo bản ghi `thongbao`.
- Tạo danh sách nhận trong `thongbao_nguoinhan`.

### `btc_cap_nhat_su_kien(...)`
- Cập nhật thông tin sự kiện và thời gian.

### `btc_lay_chi_tiet_su_kien(...)`
- Trả chi tiết sự kiện + thông tin `tenCap`, `nguoiTaoTen`.

## 2) `quan_ly_vong_thi.php`
### `tao_vong_thi(...)`
- Tạo vòng thi cho một sự kiện (`vongthi`).
- Check quyền và validate thứ tự/thời gian.

### `cap_nhat_vong_thi(...)`
- Cập nhật tên, mô tả, thời gian, thứ tự.

### `lay_ds_vong_thi($conn, $id_sk)`
- Lấy danh sách vòng thi của sự kiện theo `thuTu ASC`.

### `lay_chi_tiet_vong_thi($conn, $id_vong_thi)`
- Lấy 1 vòng thi theo ID.

### `xoa_vong_thi(...)`
- Xóa vòng thi (nếu ràng buộc FK cho phép).

## 3) `quan_ly_quy_che.php`
### `tao_quy_che(...)`
- Tạo quy chế theo loại: `THAMGIA_SV`, `THAMGIA_GV`, `VONGTHI`, `SANPHAM`, `GIAITHUONG`.

### `tao_dieu_kien_don(...)`
- Tạo điều kiện đơn vào `dieukien` và `dieukien_don` (transaction).

### `tao_to_hop_dieu_kien(...)`
- Tạo tổ hợp điều kiện vào `dieukien` và `tohop_dieukien` (transaction).

### `gan_dieu_kien_cho_quy_che(...)`
- Upsert liên kết `quyche_dieukien`.

### `xet_duyet_quy_che_su_kien(...)`
- Duyệt cây điều kiện để quyết định pass/fail đối tượng.

## 4) `quan_ly_to_chuc.php`
### `tao_lich_trinh(...)`
- Tạo timeline hoạt động trong `lichtrinh`.

### `ghi_nhan_diem_danh(...)`
- Ghi điểm danh vào `diemdanh` (hỗ trợ `idPhienDD`, `phuongThuc`).

### `them_thanh_vien_btc(...)`
- Gán thêm thành viên BTC cho sự kiện qua `taikhoan_vaitro_sukien`.

## 5) Quyền khuyến nghị endpoint
- Quản lý sự kiện: `admin_events` hoặc `tao_su_kien` hoặc `cauhinh_sukien` theo event.
- Quản lý vòng thi: `cauhinh_vongthi` hoặc `cauhinh_sukien` theo event.
- Quy chế: `admin_events` hoặc `cauhinh_sukien` theo event.
