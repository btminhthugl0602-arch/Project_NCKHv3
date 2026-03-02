# CHANGELOG

## 2026-03-04.2

### Fixed - API Path Issues trong Event Detail Modules

#### Vấn đề
- Modules **config-rules** và **review-assign** không load được dữ liệu
- Tất cả API calls trong `event-detail.js` sử dụng absolute paths `/api/...`
- Paths không hoạt động với base URL `/Project_NCKHv3/`

#### Giải pháp  
- Sửa **tất cả API endpoints** trong `assets/js/event-detail.js` từ `/api/...` thành `${BASE_PATH}/api/...`
- Sử dụng `window.APP_BASE_PATH` được tạo từ server-side PHP
- Áp dụng pattern đã thành công với `scoring.js`

#### Modules đã sửa
- ✅ **Config-rules**: Quy chế metadata, danh sách quy chế, lưu/xóa quy chế
- ✅ **Config-criteria**: Bộ tiêu chí (đã hoạt động từ trước)  
- ✅ **Review-assign**: Thêm giao diện động load giảng viên + vòng thi
- ✅ **Basic config**: Vòng thi, cập nhật sự kiện

#### Tính năng mới
- **Review-assign module**: Function `khoiTaoTabReviewAssign()` với:
  - Load danh sách giảng viên từ API `phan_cong_giam_khao.php`
  - Interface chọn vòng thi, phân công reviewer
  - Statistics panel hiển thị thống kê phân công
  - Event handlers cho reviewer selection workflow

#### Files thay đổi
- `assets/js/event-detail.js`: Fixed 19+ API endpoints  
- `views/event-detail.php`: Thêm container cho review-assign module

## 2026-03-04

### Fixed - Đếm giám khảo từ cả phancong_doclap và chamtieuchi

#### Vấn đề
- Một số sản phẩm có điểm chấm (chamtieuchi) nhưng không có bản ghi trong phancong_doclap
- Dẫn đến soGiamKhao=0 nhưng soGKDaCham>0 (hiển thị sai logic)

#### Giải pháp
- Cập nhật các hàm đếm giám khảo sử dụng UNION để gộp cả 2 nguồn:
  - Từ `phancong_doclap` (phân công chính thức)
  - Từ `chamtieuchi` via `phancongcham` (đã chấm điểm - legacy data)

#### Các hàm đã cập nhật
- `cham_diem_lay_danh_sach_san_pham()` - Query soGiamKhao bằng UNION
- `cham_diem_lay_giam_khao_san_pham()` - Lấy GK từ cả 2 nguồn
- `cham_diem_lay_thong_ke_tien_do()` - Thống kê đúng số sản phẩm đã phân công/chấm xong
- `cham_diem_lay_tat_ca_bai_thi()` - Query soGiamKhao bằng UNION

#### Migration
- Tạo `database/migrations/2026_03_04_sync_phancong_doclap.sql`
- INSERT IGNORE các bản ghi thiếu vào phancong_doclap từ chamtieuchi

---

### Added - Quản lý Chấm điểm (Scoring Module)

#### Backend - API Endpoints
- Tạo thư mục `api/cham_diem/` với 4 file:

##### `quan_ly_cham_diem.php` - Business Logic
- `cham_diem_lay_danh_sach_san_pham($conn, $idSK, $idVongThi)` - Lấy DS sản phẩm với trạng thái phân công
- `cham_diem_lay_danh_sach_giang_vien($conn)` - Lấy DS giảng viên có thể làm giám khảo
- `cham_diem_lay_giam_khao_san_pham($conn, $idSanPham, $idVongThi)` - Lấy GK đã phân công
- `cham_diem_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi)` - Phân công GK chấm bài
- `cham_diem_go_phan_cong_giam_khao($conn, $idSanPham, $idGV, $idVongThi)` - Gỡ phân công
- `cham_diem_moi_trong_tai($conn, $idSanPham, $idGV, $idVongThi)` - Mời GK thứ 3 phúc khảo
- `cham_diem_lay_thong_ke_tien_do($conn, $idSK, $idVongThi)` - Thống kê tiến độ chấm
- `cham_diem_lay_danh_sach_bai_thi($conn, $idSK, $idVongThi)` - DS bài thi với trạng thái chi tiết
- `cham_diem_tinh_irr($conn, $idSanPham, $idVongThi)` - Tính Inter-Rater Reliability
- `cham_diem_paired_ttest($ds)` - Paired T-test cho 2 GK
- `cham_diem_one_way_anova($ds)` - One-way ANOVA cho 3+ GK
- `cham_diem_lay_danh_sach_canh_bao($conn, $idSK, $idVongThi)` - DS bài có độ lệch điểm cao
- `cham_diem_xu_ly_canh_bao($conn, $idSanPham, $action, $ghiChu)` - Xử lý cảnh báo IRR
- `cham_diem_lay_danh_sach_can_duyet($conn, $idSK, $idVongThi)` - DS bài chờ duyệt điểm
- `cham_diem_duyet_diem_voi_quyche($conn, $idSanPham, $idVongThi, $cach, $ghiChu)` - Duyệt điểm theo quy chế
- `cham_diem_loai_bai($conn, $idSanPham, $idVongThi, $lyDo)` - Đánh rớt bài thi
- `cham_diem_lay_bang_xep_hang($conn, $idSK, $idVongThi)` - Bảng xếp hạng theo điểm TB
- `cham_diem_lay_thong_ke_ket_qua($conn, $idSK, $idVongThi)` - Thống kê kết quả xét duyệt

##### `phan_cong_giam_khao.php` - API Endpoint
- GET `?action=list_san_pham` - Lấy DS sản phẩm
- GET `?action=list_giang_vien` - Lấy DS giảng viên
- GET `?action=giam_khao_san_pham` - Lấy GK của sản phẩm
- POST `action=assign_doclap` - Phân công GK
- POST `action=remove_doclap` - Gỡ phân công
- POST `action=add_3rd_judge` - Mời trọng tài
- POST `action=assign_multiple` - Phân công hàng loạt

##### `tien_do_irr.php` - API Endpoint
- GET `?action=thong_ke` - Thống kê tiến độ chung
- GET `?action=danh_sach_bai_thi` - DS bài với tiến độ chi tiết
- GET `?action=phan_tich_irr` - Phân tích IRR cho 1 bài
- GET `?action=danh_sach_canh_bao` - DS bài có cảnh báo IRR

##### `xet_ket_qua.php` - API Endpoint
- GET `?action=thong_ke_ket_qua` - Thống kê xét duyệt
- GET `?action=danh_sach_can_duyet` - DS bài chờ duyệt
- GET `?action=bang_xep_hang` - Bảng vàng xếp hạng
- POST `action=approve_score_manual` - Duyệt điểm thủ công
- POST `action=reject_score` - Đánh rớt bài
- POST `action=approve_multiple` - Duyệt hàng loạt

#### Frontend - Giao diện
- Cập nhật `views/event-detail.php` tab "scoring":
  - Dropdown chọn vòng thi
  - 4 thẻ thống kê: Tổng sản phẩm, Đã phân công, Đã chấm xong, Đã duyệt
  - 3 sub-tab: Phân công giám khảo | Tiến độ & IRR | Xét kết quả

##### Sub-tab 1: Phân công giám khảo
- Danh sách sản phẩm với tìm kiếm, lọc trạng thái
- Panel chi tiết phân công khi chọn sản phẩm
- UI thêm/gỡ giám khảo cho từng bài

##### Sub-tab 2: Tiến độ & Kiểm định IRR
- Danh sách cảnh báo IRR (độ lệch điểm > 30%)
- Panel phân tích IRR chi tiết với kết quả T-test/ANOVA
- Bảng tiến độ chấm với progress bar
- Nút mời trọng tài khi có cảnh báo

##### Sub-tab 3: Xét kết quả & Bảng vàng
- Thống kê: Đã duyệt, Bị loại, Chờ duyệt, Điểm TB
- Danh sách bài chờ duyệt với nút Duyệt/Loại
- Bảng vàng hiển thị xếp hạng với medal

#### Frontend - JavaScript
- Tạo `assets/js/scoring.js`:
  - Module pattern với state management
  - Quản lý 3 sub-tab và chuyển đổi
  - Gọi API và hiển thị dữ liệu động
  - Các hàm: `loadVongThi()`, `loadSanPham()`, `loadGiangVien()`
  - Phân công: `selectSanPham()`, `themPhanCong()`, `goPhanCong()`
  - IRR: `loadCanhBaoIRR()`, `showIRRDetail()`, `moiTrongTai()`
  - Kết quả: `duyetDiem()`, `loaiDiem()`, `loadBangVang()`
  - Utility: debounce, escapeHtml, showToast

---

## 2026-03-02

### Improved - Thiết lập Bộ Tiêu chí (Tab config-criteria)

#### Backend
- Thêm hàm `xoa_bo_tieu_chi()` trong `quan_ly_bo_tieu_chi.php`:
  - Kiểm tra quyền xóa
  - Kiểm tra bộ tiêu chí có đang được sử dụng trong `cauhinh_tieuchi_sk`, `tieuban`, hoặc `chamtieuchi`
  - Nếu đang sử dụng: Trả về lỗi kèm danh sách nơi đang dùng
  - Nếu không: Xóa các bản ghi trong `botieuchi_tieuchi` trước, rồi xóa bộ tiêu chí
- Tạo mới API endpoint `xoa_bo_tieu_chi.php`:
  - Method: POST
  - Body: `{ id_sk: int, id_bo: int }`
  - Trả về lỗi 409 nếu bộ tiêu chí đang được sử dụng

#### Frontend (event-detail.js)
- Cải thiện `renderCriteriaSetList()`:
  - Hiển thị tag usage với icon và màu sắc phân biệt (xanh lá cho vòng thi, cyan cho tiểu ban)
  - Thêm nút Xóa cho bộ tiêu chí chưa sử dụng
  - Hiển thị badge "Đang dùng" (khóa) cho bộ đang sử dụng - không cho xóa
  - Hiển thị ID bộ tiêu chí dạng `#801`
- Thêm hàm `xoaBoTieuChi()` gọi API xóa
- Cập nhật event handler với confirm dialog và xử lý lỗi hiển thị nơi đang sử dụng

- Cải thiện form tạo/sửa tiêu chí:
  - Thêm cột STT với số thứ tự tự động cập nhật
  - Thêm nút di chuyển lên/xuống để sắp xếp thứ tự tiêu chí
  - Thêm footer hiển thị Tổng điểm tối đa và Tổng tỷ trọng (cập nhật realtime)
  - Cải thiện UI input với placeholder và focus state
  - Thêm hàm `updateCriteriaSTT()`, `updateCriteriaTotals()`, `moveCriteriaRow()`
  - Cập nhật `collectCriteriaRows()` để sử dụng class `.criteria-row`

#### Giao diện (event-detail.php)
- Cải thiện bảng tiêu chí với cột STT và cột Thao tác (di chuyển + xóa)
- Thêm row footer hiển thị tổng điểm và tỷ trọng
- Cải thiện button với icon SVG

### Fixed - Quản lý Vòng thi
- Đồng bộ code với schema.sql:
  - Thay `isActive` bằng `dongNopThuCong` (cột có trong schema)
  - Đổi chức năng toggle từ "Kích hoạt/Vô hiệu" sang "Đóng/Mở nộp bài"
  - Cập nhật `lay_trang_thai_vong_thi()` chỉ trả về 3 trạng thái: chua_bat_dau/dang_dien_ra/da_ket_thuc
  - Cập nhật `lay_ds_vong_thi()` trả về `daDongNop` từ `dongNopThuCong`
  - Đổi tên `toggle_trang_thai_vong_thi()` thành `toggle_dong_nop_vong_thi()`
  - Xóa tham số `force` trong `xoa_vong_thi()` (không soft delete)
- Cập nhật giao diện vòng thi:
  - Badge "Đã đóng nộp" (đỏ) thay "Vô hiệu"
  - Icon khóa/mở cho nút toggle
  - Xóa logic force delete khi có dữ liệu liên quan

## 2026-03-03

### Improved - Quản lý Sự kiện
- Refactor `api/su_kien/quan_ly_su_kien.php`:
  - Thêm header documentation và constants cho validation
  - Tách validation riêng ra hàm `validate_du_lieu_su_kien()` với kiểm tra độ dài tên (5-300 ký tự), mô tả (max 5000 ký tự)
  - Thêm hàm `kiem_tra_trung_ten_su_kien()` để cảnh báo khi tên trùng trong cùng cấp tổ chức
  - Thêm hàm `la_btc_su_kien()` để kiểm tra vai trò BTC
  - Thêm hàm `lay_trang_thai_su_kien()` để xác định trạng thái (chua_bat_dau/dang_dien_ra/da_ket_thuc/bi_vo_hieu)
  - Thêm hàm `lay_thong_ke_su_kien()` để đếm số nhóm, bài nộp, vòng thi, giám khảo
  - Cập nhật `btc_tao_su_kien()` và `btc_cap_nhat_su_kien()` trả về warnings khi có cảnh báo
  - Cải thiện error messages và error logging

### Improved - Quản lý Vòng thi
- Refactor `api/su_kien/quan_ly_vong_thi.php`:
  - Thêm header documentation và constants cho validation
  - Tách validation riêng ra hàm `validate_du_lieu_vong_thi()` với kiểm tra độ dài tên (3-200 ký tự), mô tả (max 2000 ký tự)
  - Thêm hàm `kiem_tra_trung_ten_vong_thi()` để cảnh báo tên trùng
  - Thêm hàm `lay_thu_tu_tiep_theo_vong_thi()` để tự động lấy số thứ tự khi tạo mới
  - Thêm hàm `lay_trang_thai_vong_thi()` để xác định trạng thái vòng thi
  - Thêm hàm `vong_thi_co_du_lieu_lien_quan()` kiểm tra trước khi xóa
  - Thêm hàm `toggle_trang_thai_vong_thi()` để kích hoạt/vô hiệu hóa vòng thi
  - Thêm hàm `sap_xep_thu_tu_vong_thi()` để sắp xếp lại thứ tự vòng thi
  - Thêm hàm `lay_thong_ke_vong_thi()` để đếm số bài nộp, phân công, kết quả chấm
  - Cải thiện hàm `xoa_vong_thi()` với soft delete nếu có dữ liệu liên quan
  - Cập nhật `lay_ds_vong_thi()` và `lay_chi_tiet_vong_thi()` trả về trạng thái

### Added - API Endpoints Vòng thi
- `POST /api/su_kien/cap_nhat_vong_thi.php` - Cập nhật thông tin vòng thi
- `POST /api/su_kien/xoa_vong_thi.php` - Xóa vòng thi (hỗ trợ force soft delete)
- `POST /api/su_kien/sap_xep_vong_thi.php` - Sắp xếp lại thứ tự vòng thi
- `POST /api/su_kien/toggle_vong_thi.php` - Toggle kích hoạt/vô hiệu hóa vòng thi

### Improved - Frontend Quản lý Vòng thi
- Cập nhật `assets/js/event-detail.js`:
  - Nâng cấp `renderRoundList()` hiển thị thêm:
    - Badge trạng thái (Đang diễn ra/Chưa bắt đầu/Đã kết thúc/Vô hiệu)
    - Các nút hành động: Sửa, Xóa, Toggle trạng thái
    - Nút di chuyển lên/xuống để sắp xếp vòng thi
  - Thêm hàm `handleEditRound()` - Modal sửa vòng thi với SweetAlert2
  - Thêm hàm `handleDeleteRound()` - Xác nhận xóa với xử lý dữ liệu liên quan
  - Thêm hàm `handleToggleRound()` - Toggle kích hoạt với xác nhận
  - Thêm hàm `handleMoveRound()` - Di chuyển vòng thi lên/xuống

## 2026-03-02

### Added
- Tạo bộ tài liệu `docs/` theo cấu trúc:
  - `docs/api/README.md`
  - `docs/api/core-functions.md`
  - `docs/api/su-kien-functions.md`
  - `docs/api/nhom-functions.md`
  - `docs/api/tai-khoan-functions.md`
  - `docs/database/README.md`
  - `docs/user-guide/api-dev-quickstart.md`

### Notes
- Tài liệu mô tả cách sử dụng hàm API hiện có và hướng triển khai endpoint theo chuẩn PDO + JSON response thống nhất.

### Changed
- Nâng giao diện nút `Tạo sự kiện` tại trang quản lý sự kiện để hiển thị nổi bật hơn (vùng màu bao quanh rõ hơn).
- Cập nhật form tạo sự kiện trong `SweetAlert`: thay nhập tay `ID cấp` bằng dropdown chọn theo `tên cấp tổ chức`.
- Bổ sung API `GET /api/su_kien/danh_sach_cap_to_chuc.php` để frontend lấy danh sách cấp tổ chức động từ bảng `cap_tochuc` + `loaicap`.
- Thu gọn kích thước modal form tạo sự kiện và làm rõ màu sắc nút xác nhận `Tạo sự kiện` trong form.
- Bổ sung danh sách sự kiện ngay trên trang `events` và thêm API `GET /api/su_kien/danh_sach_su_kien.php` để tải dữ liệu ban đầu.
- Sau khi tạo sự kiện thành công, frontend tự append dòng sự kiện mới vào danh sách tại chỗ (không reload trang).
- Tinh chỉnh lại style nút: nút mở `Tạo sự kiện` hiển thị gradient rõ ràng hơn, nút `Huỷ` trong modal giảm bo góc để cân đối giao diện.
- Tối ưu lại bố cục modal tạo sự kiện theo hướng gọn hơn (không kéo dài theo chiều dọc) và tăng kích thước nút `Tạo sự kiện` / `Huỷ` để tương xứng tổng thể form.
- Bổ sung cột `Hành động` trong danh sách sự kiện với nút `Xem` để xem chi tiết từng sự kiện riêng.
- Thêm API `GET /api/su_kien/chi_tiet_su_kien.php?id_sk=...` phục vụ popup chi tiết sự kiện.
- Điều chỉnh UX chi tiết sự kiện: bỏ nút `Xem`, chuyển thành click trực tiếp vào tên sự kiện để điều hướng.
- Tạo trang riêng `/event-detail?id_sk=...` để hiển thị chi tiết sự kiện với không gian cấu hình sâu hơn.
- Bổ sung style riêng cho link tên sự kiện (tone tím mặc định, hover tím đậm + underline) để người dùng nhận biết thao tác rõ ràng hơn.
- Khi vào trang chi tiết sự kiện, sidebar trái chuyển sang menu chức năng theo ngữ cảnh sự kiện (tổng quan, cấu hình, bài nộp, review, tiểu ban/BGK).
- Tên sự kiện ở đầu sidebar được cập nhật động theo dữ liệu sự kiện đang mở.
- Sửa lỗi hiển thị màu/chữ/icon ở sidebar ngữ cảnh sự kiện bằng cách dùng bộ class màu có sẵn của theme và icon tương thích.
- API chi tiết sự kiện `chi_tiet_su_kien.php` được refactor sang dùng hàm service `btc_lay_chi_tiet_su_kien()` thay cho query SQL viết tay; dữ liệu chi tiết trả về bổ sung `tenLoaiCap` từ service layer.
- Triển khai “tab thật” cho trang chi tiết sự kiện: mỗi mục sidebar (`overview`, `config`, `submissions`, `review-assign`, `review-results`, `committees`, `judges`) hiển thị nội dung riêng theo tab, không chỉ highlight menu.
- Tái cấu trúc menu BTC tại trang chi tiết sự kiện: mục `Cấu hình sự kiện` gồm 3 tab con `Cấu hình cơ bản`, `Cấu hình quy chế`, `Thiết lập bộ tiêu chí`.
- Triển khai chức năng thực cho tab `Cấu hình cơ bản`: chỉnh sửa thông tin sự kiện, mở/đóng sự kiện, xem danh sách vòng thi và thêm vòng thi mới.
- Bổ sung các endpoint mới phục vụ cấu hình cơ bản:
  - `POST /api/su_kien/cap_nhat_su_kien.php`
  - `GET /api/su_kien/danh_sach_vong_thi.php?id_sk=...`
  - `POST /api/su_kien/tao_vong_thi.php`
- Nâng cấp UI khối `Cấu hình vòng thi` trong tab `Cấu hình cơ bản`: bố cục card rõ hơn, danh sách vòng thi dễ đọc hơn.
- Sửa hiển thị nút `Thêm vòng thi` (kích thước, màu sắc, icon, chống co nút) và làm lại popup thêm vòng thi theo layout gọn đẹp, dễ thao tác.
- Tách style nút popup thêm vòng thi sang CSS riêng `assets/css/event-detail.css` để hiển thị ổn định và thẩm mỹ hơn (nút xác nhận/hủy rõ ràng, cân kích thước).
- Tăng specificity CSS cho nút popup `Thêm vòng thi` để tránh bị style global đè (sửa triệt để lỗi nút nhỏ/xấu, chữ lệch và nền không đúng).
- Hoàn thiện luồng `Cấu hình quy chế` theo AST end-to-end tại trang chi tiết sự kiện (`config-rules`): tạo điều kiện đơn A/B/C, ghép token logic, parse cây AST, lưu và xem lại cây điều kiện.
- Bổ sung backend API quy chế để hỗ trợ đầy đủ CRUD theo JSON chuẩn:
  - `GET /api/su_kien/quy_che_metadata.php`
  - `GET /api/su_kien/danh_sach_quy_che.php`
  - `POST /api/su_kien/luu_quy_che.php`
  - `GET /api/su_kien/chi_tiet_quy_che.php`
  - `POST /api/su_kien/xoa_quy_che.php`
- Siết chặt ràng buộc dữ liệu quy chế:
  - Lọc metadata và danh sách quy chế theo `loai_quy_che`.
  - Khóa xem/xóa quy chế theo `id_sk` hiện tại để tránh truy cập chéo sự kiện.
  - Validate AST node đầu vào khi lưu (hỗ trợ cả `operator` và `logic`), kiểm tra thuộc tính đúng `loaiApDung`, toán tử đúng nhóm `compare/logic`.
  - Tự dọn dữ liệu quy chế mới tạo nếu lưu cây điều kiện thất bại giữa chừng.
- Sửa lỗi không nạp được thuộc tính/toán tử ở tab quy chế trên một số DB thực tế:
  - Hỗ trợ alias loại cũ `THAMGIA` → `THAMGIA_SV` tại API metadata/list/save.
  - Metadata fallback khi dữ liệu lọc theo loại bị rỗng.
  - Tương thích cột mô tả toán tử giữa các biến thể schema (`tenToanTu`/`moTa`).
  - Bổ sung nhận tham số query `loai` (ngoài `rule_type`) ở frontend để tự đồng bộ loại quy chế.
- Bổ sung “dịch ngôn ngữ tự nhiên” cho biểu thức quy chế:
  - Hiển thị realtime câu diễn giải sau khi ghép token và parse AST tại tab `config-rules`.
  - Hiển thị thêm dòng diễn giải trong popup xem chi tiết quy chế để BTC kiểm tra nhanh logic đã cấu hình.
- Cập nhật trải nghiệm nhập điều kiện quy chế theo yêu cầu mới:
  - Dropdown thuộc tính hiển thị toàn bộ thuộc tính kiểm tra (không phân loại theo loại quy chế).
  - Thêm API `GET /api/su_kien/goi_y_gia_tri_thuoc_tinh.php?id_thuoc_tinh=...` để gợi ý giá trị theo thuộc tính được chọn.
  - Ô nhập giá trị hỗ trợ datalist gợi ý động theo dữ liệu thực tế trong CSDL của thuộc tính tương ứng.
  - Bỏ ràng buộc lưu quy chế theo `loaiApDung` của thuộc tính, chỉ kiểm tra thuộc tính có tồn tại.
- Siết chặt quy tắc ghép token ở tab cấu hình quy chế trước khi tạo AST:
  - Không cho phép biểu thức bắt đầu/kết thúc sai chuẩn.
  - Kiểm tra ngoặc cân bằng, không cho ngoặc rỗng hoặc toán tử sai vị trí.
  - Bắt lỗi thiếu toán tử giữa 2 điều kiện hoặc thiếu toán hạng cho toán tử logic.
  - Cảnh báo khi trong cùng một cặp ngoặc xuất hiện đồng thời `AND` và `OR`.
  - Hiển thị lỗi theo danh sách để người dùng sửa đúng theo chuẩn cây nhị phân.
- Bổ sung ràng buộc mới cho biểu thức quy chế: mỗi thuộc tính kiểm tra chỉ được xuất hiện đúng 1 lần trong toàn bộ biểu thức (frontend + backend).
- Refactor module [api/su_kien/quan_ly_bo_tieu_chi.php](api/su_kien/quan_ly_bo_tieu_chi.php) theo chuẩn dự án:
  - Chuẩn hóa PDO + helper hiện tại, bỏ toàn bộ `mysqli_*`.
  - Sửa kiểm tra quyền theo `maQuyen_code` mới (`admin_criteria`, `admin_events`, `tao_su_kien`, quyền sự kiện `cauhinh_sukien`/`cauhinh_vongthi`).
  - Sửa đúng schema `tieuchi` (không có cột `diemToiDa`), đồng thời hỗ trợ `diemToiDa` ở bảng liên kết `botieuchi_tieuchi`.
  - Hoàn thiện validate dữ liệu và ràng buộc quan hệ khi gán bộ tiêu chí vào vòng thi theo đúng `idSK`.
- Nâng cấp sâu nghiệp vụ bộ tiêu chí theo luồng clone/create/update cho từng sự kiện:
  - Bổ sung hàm `tim_hoac_tao_tieu_chi_theo_noi_dung()` để tái sử dụng ngân hàng `tieuchi` (find-or-create theo text).
  - Bổ sung hàm `lay_chi_tiet_day_du_bo_tieu_chi()` phục vụ AJAX `get_full_set` (master + details + vòng áp dụng trong sự kiện).
  - Bổ sung hàm `lay_ban_do_su_dung_bo_tieu_chi()` để dựng usage map “đang áp dụng tại…” (vòng thi và tiểu ban nếu schema có cột).
  - Bổ sung hàm `luu_bo_tieu_chi_theo_su_kien()` xử lý transaction cho flow `save_criteria`:
    - `edit_id = 0`: tạo bộ mới.
    - `edit_id > 0`: update và flush/replace cấu hình cũ trong sự kiện.
    - Upsert gán vòng bằng `ON DUPLICATE KEY UPDATE` theo khóa `(idSK, idVongThi)`.
    - Lưu thang điểm theo sự kiện tại `botieuchi_tieuchi.diemToiDa`, không lưu ở `tieuchi`.
- Triển khai giao diện thật cho tab `config-criteria` tại trang chi tiết sự kiện:
  - Form tạo/sửa bộ tiêu chí, bảng tiêu chí con (nội dung/điểm tối đa/tỷ trọng), nút thêm/xóa dòng.
  - Khối nhân bản nhanh từ bộ tiêu chí có sẵn và danh sách ngân hàng bộ tiêu chí kèm badge usage.
  - Hỗ trợ clone vào form và sửa trực tiếp từ danh sách bộ tiêu chí.
- Bổ sung API phục vụ tab `config-criteria`:
  - `GET /api/su_kien/du_lieu_bo_tieu_chi.php?id_sk=...` (vòng thi + ngân hàng tiêu chí + bộ tiêu chí + usage map)
  - `GET /api/su_kien/chi_tiet_bo_tieu_chi.php?id_sk=...&id_bo=...` (master/details để clone/edit)
  - `POST /api/su_kien/luu_bo_tieu_chi.php` (lưu flow create/update theo payload JSON)
- Sửa lỗi dropdown tab `config-criteria` không lấy được dữ liệu từ CSDL:
  - Điều chỉnh hàm `lay_ngan_hang_tieu_chi()` và `lay_danh_sach_bo_tieu_chi()` nhận ngữ cảnh `id_su_kien` để kiểm tra quyền đúng phạm vi sự kiện.
  - Cập nhật endpoint `du_lieu_bo_tieu_chi.php` truyền `id_sk` vào các hàm lấy dropdown data.
- Tinh chỉnh giao diện form bộ tiêu chí:
  - Bổ sung style riêng cho khu vực `criteria-workspace` (focus state, hover card, bảng dễ đọc hơn, nút thao tác cân đối hơn).
