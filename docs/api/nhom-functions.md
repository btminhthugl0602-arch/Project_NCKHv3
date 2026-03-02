# Nhóm Module (`api/nhom/quan_ly_nhom.php`)

## 1) `tao_nhom_moi(...)`
- Chỉ cho sinh viên (`idLoaiTK = 3`) tạo nhóm.
- Check sinh viên đã có nhóm trong cùng sự kiện chưa.
- Tạo dữ liệu đồng bộ qua 3 bảng:
  - `nhom`
  - `thongtinnhom`
  - `thanhviennhom` (trưởng nhóm, `idvaitronhom=1`)
- Chạy trong transaction.

## 2) `gui_yeu_cau_nhom(...)`
- Tạo yêu cầu vào `yeucau_thamgia`.
- Chặn gửi trùng khi đã là thành viên hoặc đã có yêu cầu chờ.
- Với mời GV (idLoaiTK=2), đảm bảo nhóm chưa có GVHD (`idvaitronhom=3`).

## 3) `duyet_yeu_cau_nhom(...)`
- Kiểm tra quyền duyệt:
  - `ChieuMoi=1` (xin vào): trưởng nhóm duyệt.
  - `ChieuMoi=0` (được mời): người được mời tự duyệt.
- Khi chấp nhận:
  - thêm/cập nhật `thanhviennhom`.
  - phân vai trò: GV -> 3, SV -> 2.
  - kiểm tra giới hạn số lượng nhóm.
- Chạy transaction.

## 4) `roi_nhom(...)`
- Thành viên tự rời hoặc trưởng nhóm loại thành viên.
- Không cho trưởng nhóm rời trực tiếp (phải chuyển quyền trước).

## 5) Tìm kiếm
### `tim_kiem_giang_vien(...)`
- Search giảng viên active, giới hạn 10 kết quả.

### `tim_kiem_sinh_vien(...)`
- Search sinh viên active theo tên/MSV, giới hạn 10 kết quả.

## 6) Lưu ý triển khai endpoint nhóm
- Nên truyền thêm `id_nguoi_gui` vào API gửi yêu cầu để kiểm soát quyền chặt hơn ở controller.
- Khi response thành công, trả kèm dữ liệu `idnhom`, `manhom` để frontend điều hướng nhanh.
