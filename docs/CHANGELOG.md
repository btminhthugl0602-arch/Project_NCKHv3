# CHANGELOG

## 2026-03-10 — Refactor Nhóm 1: Khởi tạo nhóm (Schema v2)

### Thay đổi nghiệp vụ
- **Chủ nhóm** (`nhom.idChuNhom`) và **Trưởng nhóm** (`nhom.idTruongNhom`) tách biệt hoàn toàn
- SV tạo nhóm → tự động là Chủ nhóm + Trưởng nhóm
- GV tạo nhóm → là Chủ nhóm, Trưởng nhóm = NULL (chỉ định sau)
- GVHD lưu riêng ở bảng `nhom_gvhd`, không tính vào `soThanhVienToiDa`
- `soThanhVienToiDa` lấy từ cấu hình sự kiện, bỏ cột `thongtinnhom.soluongtoida`

### API thay đổi
- **`api/nhom/taonhom.php`**: Bỏ param `so_luong_toi_da`, `dang_tuyen`. Response trả `idNhom`, `maNhom`
- **`api/nhom/cap_nhat_nhom.php`**: Bỏ param `id_sk` (lấy từ DB). Thêm param `is_active` (optional)
- **`api/nhom/nhuong_quyen.php`** *(MỚI)*: Nhượng quyền Chủ nhóm hoặc Trưởng nhóm. Input: `id_nhom`, `action` (`chu_nhom`|`truong_nhom`), `id_nguoi_nhan`

### Business logic (`api/nhom/quan_ly_nhom.php`)
- Viết lại: `la_chu_nhom()`, `la_truong_nhom()`, `kiem_tra_sv_co_nhom()`, `tao_nhom_moi()`, `cap_nhat_thong_tin_nhom()`, `kiem_tra_thanh_vien_nhom()`
- Thêm mới: `la_thanh_vien_sv()`, `la_gvhd_nhom()`, `so_thanh_vien_sv()`, `so_gvhd_nhom()`, `so_nhom_gv_huong_dan()`, `nhuong_quyen_nhom()`

### Migration
- `database/migrations/2026_03_10_drop_soluongtoida_thongtinnhom.sql`: Drop cột `soluongtoida` khỏi `thongtinnhom`

---

## 2026-03-10 (Phase 5 — Feature Completion)

### Tính năng & Sửa lỗi cuối cùng

#### 5.1 — Implement động cơ quy chế vòng thi `cham_diem_kiem_tra_quy_che`
**`api/cham_diem/quan_ly_cham_diem.php`**
- Thay thế stub `return true` bằng bộ đánh giá điều kiện đệ quy thực sự
- Thêm `dk_lay_du_lieu_spv($conn, $idSanPham, $idVongThi)`:
  - Lấy `{diemTrungBinh, xepLoai, trangThai}` từ `sanpham_vongthi` sau khi điểm đã chốt
- Thêm `dk_evaluate($conn, $idDieuKien, $spvData, $depth)`:
  - Đệ quy trên cây `dieukien` (DON/TOHOP)
  - DON: truy vấn `dieukien_don → thuoctinh_kiemtra → toantu`, so sánh số học & chuỗi với short-circuit
  - TOHOP: truy vấn `tohop_dieukien`, áp dụng AND/OR với short-circuit evaluation
  - Bảo vệ khỏi vòng lặp vô hạn: giới hạn độ sâu đệ quy = 20
- `cham_diem_kiem_tra_quy_che()`:
  - Lấy **tất cả** quy chế `VONGTHI` của sự kiện (không chỉ `LIMIT 1`)
  - Đánh giá `quyche_dieukien.idDieuKienCuoi` của từng quy chế
  - Trả về `false` ngay khi vi phạm bất kỳ quy chế nào (AND giữa các quy chế)
  - Không có quy chế → trả về `true` (mặc định đạt)

#### 5.2 — Fix `idBoTieuChi LIMIT 1` sai ngữ cảnh sản phẩm
**`api/cham_diem/quan_ly_cham_diem.php`** — hàm `cham_diem_moi_trong_tai()`
- **Lỗi**: Query `SELECT ... FROM phancongcham WHERE idVongThi=X LIMIT 1` lấy bộ tiêu chí của bất kỳ GK nào trong vòng thi, có thể không phải GK chấm SP đang xét
- **Fix**: Thêm `INNER JOIN phancong_doclap pd ON pd.idGV = pcc.idGV AND pd.idSanPham = :idSanPham AND pd.isTrongTai = 0` để đảm bảo lấy đúng `idBoTieuChi` của GK chính đang phụ trách SP cụ thể này
- Cập nhật `execute()` thêm binding `:idSanPham`

#### 5.3 — Export bảng xếp hạng ra CSV
**`api/cham_diem/xet_ket_qua.php`**
- Thêm `case 'export_ranking':` trong `handleGetRequest()`
- Stream CSV với UTF-8 BOM (để Excel nhận đúng tiếng Việt)
- Cột: Hạng, Tên sản phẩm, Mã nhóm, Tên nhóm, Điểm TB, Xếp loại, Thành viên
- Header `Content-Disposition: attachment` → browser tự download

**`assets/js/scoring.js`** — `handleExportRanking()`
- Thay `showToast('đang phát triển')` bằng `window.open(url)` với URL `?action=export_ranking&id_sk=...&id_vong_thi=...`
- Kiểm tra `idSK` và `idVongThi` trước khi mở

#### 5.4 — Hệ thống thông báo giám khảo
**`api/thong_bao/giam_khao.php`** *(file mới)*
- GET `?action=lay_chua_doc&id_sk=X` — lấy thông báo `CA_NHAN` chưa đọc của user hiện tại
- GET `?action=danh_dau_da_doc&id_thong_bao=Y` — đánh dấu đã đọc (INSERT IGNORE vào `thongbao_da_doc`)
- POST `{action:'gui_nhac_nho', id_sk, id_vong_thi}` (chỉ BTC):
  - Truy vấn GK chưa hoàn thành: `phancong_doclap LEFT JOIN chamtieuchi HAVING da_cham < tong_sp`
  - Tạo 1 bản ghi `thongbao` (phamVi=`CA_NHAN`) + N bản ghi `thongbao_ca_nhan`
  - Trả về danh sách GK nhận + số lượng SP còn thiếu

**`assets/js/scoring.js`**
- Thêm `API.thongBaoGK` endpoint
- Thêm `elements.btnGuiNhacNho` (cache DOM)
- Bind `click → handleGuiNhacNho()` trong `bindEvents()`
- `handleGuiNhacNho()`: confirm Swal → POST → showToast với kết quả

**`views/partials/event-detail/tab-scoring.php`**
- Thêm nút `#btnGuiNhacNho` (Nhắc nhở GK) bên cạnh nút "Làm mới" ở Tab 2 (Tiến độ IRR)

---

## 2026-03-10 (Phase 4 — Performance & Code Quality)

### Refactor — Hiệu năng & Chất lượng mã nguồn

#### 4.1 — Batch query cảnh báo IRR (N+1 → 2 queries)
**`api/cham_diem/quan_ly_cham_diem.php`**
- Thêm `cham_diem_lay_chi_tiet_diem_batch($conn, $idSK, $idVongThi)`:
  - 1 query SQL duy nhất lấy toàn bộ điểm chấm của mọi SP trong vòng thi
  - Group by `idSanPham → idGV → chiTiet[]` hoàn toàn trong PHP
- Refactor `cham_diem_lay_danh_sach_canh_bao()`:
  - **Trước**: gọi `cham_diem_lay_chi_tiet_diem()` trong vòng lặp → N queries
  - **Sau**: 1 query batch + PHP filter/map → tổng **2 queries** thay vì N+1
  - Lọc chỉ GK chính (`isTrongTai=0`) trước khi tính IRR (nhất quán với Phase 1)

#### 4.2 — Fix php://input double-read
**`api/cham_diem/phan_cong_giam_khao.php`**
- Vấn đề: Auth block đọc `php://input` lần 1 (khi `id_sk` vắng mặt trong GET) và lưu vào `$_SERVER['_PARSED_INPUT']`; `handlePostRequest()` gọi `file_get_contents('php://input')` lần 2 → body rỗng
- Fix: `handlePostRequest()` kiểm tra `$_SERVER['_PARSED_INPUT']` trước; chỉ đọc `php://input` nếu chưa có (trường hợp `id_sk` đã có trong GET query string)

#### 4.3 — VIEW phân công giám khảo
**`database/migrations/2026_03_10_create_view_giam_khao_san_pham.sql`** *(migration mới)*
- `CREATE OR REPLACE VIEW v_giam_khao_san_pham` — gộp `phancong_doclap UNION chamtieuchi` thành 1 VIEW kèm `isTrongTai`
- **Cần thực thi migration này để VIEW hoạt động**

**`api/cham_diem/quan_ly_cham_diem.php`** — `cham_diem_lay_giam_khao_san_pham()`
- **Trước**: inline UNION SELECT với 10 tham số PDO (`:idSanPhamN`, `:idVongThiN`)
- **Sau**: `SELECT ... FROM v_giam_khao_san_pham WHERE idSanPham=? AND idVongThi=?` — 6 tham số, dễ đọc hơn

---

## 2026-03-10 (Phase 3 — UX Refinements)

### Fix + Feature — Cải tiến trải nghiệm quản lý chấm điểm

#### 3.1 — Badge "Trọng tài" trong Tab 1 (Phân công)
**`api/cham_diem/quan_ly_cham_diem.php`** — `cham_diem_lay_giam_khao_san_pham`
- LEFT JOIN `phancong_doclap pd_info` để lấy `isTrongTai` cho mỗi GK
- Thứ tự hiển thị: GK chính trước, Trọng tài sau (`ORDER BY pd_info.isTrongTai ASC`)

**`assets/js/scoring.js`** — `loadPanelPhanCong`
- Hiển thị badge vàng `Trọng tài` (<i>fas fa-shield-alt</i>) bên cạnh tên GV có `isTrongTai=1`
- Viền/nền thẻ khác màu (amber) để phân biệt trực quan

#### 3.2 — Fix typo "Đánh rật" → "Đánh rớt"
**`assets/js/scoring.js`** — `handleRejectFromModal`
- Sửa 4 vị trí: title, confirmButtonText, toast success message, toast error message

#### Bugfix — `scoredJudges` chưa đổi tên sau Phase 2
**`assets/js/scoring.js`** — `renderIRRDetailModal`
- `const noteCount = scoredJudges.length` → `const noteCount = scoredMainJudges.length`

#### 3.3 — Auto-refresh polling Tab 2 (Tiến độ & IRR)
**`assets/js/scoring.js`**
- Thêm `state.pollingTimer` + hằng `POLLING_INTERVAL = 30000` (30 giây)
- `startPolling()` — bắt đầu `setInterval` refresh `loadTienDoCham()`; dừng interval cũ trước khi tạo mới
- `stopPolling()` — `clearInterval` và reset
- `switchSubTab()` — tự động `startPolling()` khi chuyển sang Tab 2, `stopPolling()` khi rời
- Nút refresh thủ công `btnRefreshCanhBao` vẫn hoạt động bình thường

#### 3.4 — Nút "Hủy duyệt" cho bài đã duyệt/loại
**`api/cham_diem/quan_ly_cham_diem.php`**
- Thêm `cham_diem_huy_duyet($conn, $idSanPham, $idVongThi)` — reset `trangThai = NULL` về trạng thái chờ xét

**`api/cham_diem/xet_ket_qua.php`**
- Thêm `cancel_approval` vào danh sách validation params
- Thêm `case 'cancel_approval'` gọi `cham_diem_huy_duyet()`

**`assets/js/scoring.js`**
- Thêm `async function huyDuyet(idSanPham)` với Swal xác nhận (phân biệt "hủy duyệt" / "hủy loại")
- `renderTienDoCham`: Badge "Đã duyệt" và "Bị loại" giờ đi kèm nút "Hủy duyệt" / "Hủy loại"
- `renderBangVang`: Mỗi bài trong Bảng vàng có nút "Hủy" nhỏ để rút lại duyệt
- Expose `huyDuyet` trong `window.scoringModule`

---

## 2026-03-10 (Phase 2 — Decision Safety)

### Fix + Feature — An toàn quyết định duyệt điểm & hiển thị IRR

#### 2.1 — Backend `approve_multiple` kiểm tra IRR
**`api/cham_diem/xet_ket_qua.php`**
- Tham số `skip_warned` (bool): khi `true`, mỗi bài được kiểm tra IRR trước khi duyệt — bài có `canhBao=true` sẽ bị bỏ qua
- Response thêm `skippedCount` để frontend biết số bài đã bỏ qua

#### 2.2 — Frontend: `duyetDiem` thêm Swal xác nhận
**`assets/js/scoring.js`**
- Hiển thị dialog xác nhận trước khi gọi API duyệt đơn lẻ
- Nếu bài có cảnh báo IRR (`state.canhBaoMap`): icon `warning`, nút màu vàng, nội dung nhắc nhở

#### 2.3 — Frontend: `handleDuyetTatCa` phân tách bài có cảnh báo
**`assets/js/scoring.js`**
- Trước khi gọi API, kiểm tra từng bài trong `listCanDuyet` với `state.canhBaoMap`
- Hiển thị Swal 3 lựa chọn khi có bài cảnh báo:
  - **"Duyệt tất cả"**: gồm cả bài cảnh báo
  - **"Chỉ duyệt bài không cảnh báo"** (nút vàng): chỉ gửi danh sách bài sạch
  - **"Hủy"**: không làm gì
- Khi không có cảnh báo: confirm đơn giản như cũ

#### 2.4 — Backend: Ranking đồng hạng chuẩn (competition ranking)
**`api/cham_diem/quan_ly_cham_diem.php`** — `cham_diem_lay_bang_xep_hang`
- Các bài điểm bằng nhau chia sẻ cùng hạng; hạng tiếp theo nhảy (1, 1, 3 thay vì 1, 1, 2)

#### 2.5 — Frontend: `overallAvg` chỉ tính GK chính
**`assets/js/scoring.js`** — `renderIRRDetailModal`
- `scoredMainJudges = mainJudges.filter(...)` thay vì dùng toàn bộ `judges`
- Điểm TB hiển thị trong modal IRR không bị pha trộn điểm trọng tài phúc khảo

#### 2.6 — Frontend: `ttScored` yêu cầu trọng tài chấm đủ tiêu chí
**`assets/js/scoring.js`** — `renderIRRDetailModal`
- `.some(tc => ...)` → `.every(tc => ...)` để trọng tài chỉ được xem là "đã chấm xong" khi chấm đủ **tất cả** tiêu chí, không chỉ 1

---

## 2026-03-10 (Phase 1 — Data Integrity)

### Fix — Toàn vẹn dữ liệu module chấm điểm (BUG-1, BUG-2, BUG-3, BUG-8, LOGIC-2)

#### 1.1 — Migration: Sửa độ chính xác cột `diemTrungBinh`
**`database/migrations/2026_03_10_fix_diem_trung_binh_decimal_precision.sql`**
- `ALTER TABLE sanpham_vongthi MODIFY COLUMN diemTrungBinh decimal(7,2)` — fix lỗi `decimal(5,0)` làm mất phần thập phân (ví dụ 42.75 → 43)

#### 1.2 — BUG-1: Tách `soGiamKhao` thành GK chính / Trọng tài
**`api/cham_diem/quan_ly_cham_diem.php`** (3 hàm)
- `cham_diem_lay_danh_sach_san_pham`: `soGiamKhao` giờ chỉ đếm GK chính (`isTrongTai=0`), thêm cột `soTrongTai`, `soGKDaCham` JOIN `phancong_doclap WHERE isTrongTai=0`
- `cham_diem_lay_thong_ke_tien_do`: `sqlAssigned` chỉ đếm SP có GK chính; `sqlDone` dùng `soGKChinh/soGKChinhDaCham` thay UNION cũ
- `cham_diem_lay_tat_ca_bai_thi`: Tương tự `lay_danh_sach_san_pham` — thêm `soTrongTai`, fix `soGKDaCham`
**`assets/js/scoring.js`**
- Progress label hiển thị `+NTT` khi có trọng tài đã mời (ví dụ: `2/2 GK +1TT`)

#### 1.3 — BUG-2: `cham_diem_tinh_diem_trung_binh` loại trọng tài khỏi TB
**`api/cham_diem/quan_ly_cham_diem.php`**
- INNER JOIN `phancong_doclap WHERE isTrongTai=0` — điểm TB chỉ tính GK chính, không trộn điểm trọng tài phúc khảo

#### 1.4 — BUG-3: IRR chỉ kiểm định trên GK chính
**`api/cham_diem/tien_do_irr.php`**
- Filter `$chiTietChinhThuc = array_filter(...fn($gk) => empty($gk['isTrongTai']))` trước khi build `$diemTheoGK`
- Early-return nếu GK chính < 2 (thay vì check tổng)

#### 1.5 — BUG-8: Kiểm tra trùng vai trò khi mời Trọng tài
**`api/cham_diem/quan_ly_cham_diem.php`** — `cham_diem_moi_trong_tai`
- Kiểm tra GV không phải GK chính của bài thi (isTrongTai=0) trước khi mời làm Trọng tài
- Kiểm tra GV chưa là Trọng tài của bài thi đó — tránh mời trùng
- Cả 2 kiểm tra đều trước `beginTransaction()` để không tốn overhead

---

## 2026-03-10

### Refactor + Feature — Tách module chấm điểm & Luồng Trọng tài 5 bước

#### Tổng quan
Tái cấu trúc backend chấm điểm thành 3 module rõ ràng, sửa lỗi trọng tài không thể chấm điểm thực sự, và nâng cấp modal phân tích IRR thành giao diện 5 bước có hướng dẫn đầy đủ cho Trọng tài phúc khảo.

---

#### Module 1 — Score Analyzer *(file mới)*
**`api/cham_diem/modules/score_analyzer.php`**
- `score_calc_total($scores)` — tính tổng điểm từ mảng tiêu chí
- `score_calc_deviation_percent($scores)` — tính `(max−min)/avg × 100`
- `score_build_scoring_matrix($chiTietByJudge)` — xây ma trận criterion × judge với `avg`, `deviationPct`, `isHighDeviation`, `commentsByGV`
- `score_calc_judge_quality($matrix)` — chỉ số chất lượng từng GK: `biasScore`, `consistencyScore`, `qualityLevel` (ok/warning/outlier)

#### Module 2 — Statistical Test *(file mới)*
**`api/cham_diem/modules/statistical_test.php`**
- `stat_test_irr($diemTheoGK)` — tự chọn phương pháp (T-test / ANOVA) theo số GK
- `stat_test_paired_ttest($g1, $g2)` — Paired T-test
- `stat_test_one_way_anova($groups)` — One-way ANOVA
- `stat_test_t_to_p($t, $df)` / `stat_test_f_to_p($f, $df1, $df2)` — chuyển đổi thống kê → p-value
- `stat_test_interpret($pValue, $method, $hasCanhBao)` — diễn giải kết quả thành văn bản

#### Module 3 — Warning System *(file mới)*
**`api/cham_diem/modules/warning_system.php`**
- `warn_check_criterion($deviationPct)` — 'critical' (>50%) / 'high' (>30%) / 'ok'
- `warn_check_judge_outlier($judgeTotal, $allTotals)` — z-score outlier detection
- `warn_generate_report($matrix)` — báo cáo tổng hợp: criterion warnings + judge warnings + overallLevel
- `warn_get_level($pValue, $maxDeviation)` — mức cảnh báo tổng thể
- `warn_judge_quality_summary($judgeQuality)` — bản tóm tắt hiển thị cho UI

---

#### Refactor — `api/cham_diem/quan_ly_cham_diem.php`
- Thêm `require_once` cho 3 module mới ở đầu file
- Thay `cham_diem_tinh_irr`, `cham_diem_paired_ttest`, `cham_diem_one_way_anova`, `cham_diem_t_to_p`, `cham_diem_f_to_p` bằng thin-wrapper gọi module tương ứng (backward-compatible)
- **Fix lỗi quan trọng** `cham_diem_moi_trong_tai()`:
  - Trước: chỉ INSERT `phancong_doclap` → trọng tài không thể chấm điểm
  - Sau: cũng INSERT `phancongcham` (tra cứu `idBoTieuChi`/`idSK` từ phân công hiện có) → trọng tài có đầy đủ quyền chấm điểm

---

#### Feature — Modal phân tích IRR: giao diện 5 bước Trọng tài
**`assets/js/scoring.js` — `renderIRRDetailModal()`**

| Bước | Nội dung |
|------|----------|
| **1** | Bảng điểm chi tiết (criterion × judge) · IRR stats · Kết luận |
| **2** | Nhận xét của từng Giám khảo (accordion per-judge, expandable) |
| **3** | Trạng thái Trọng tài: Đã mời/Đã chấm/Chưa mời + form mời |
| **4** | Điều chỉnh điểm cuối: input chốt + Duyệt &amp; Chốt + Đánh rớt |
| **5** | Giám sát chất lượng GK: bias direction, z-score, consistency score |

---

#### Database Migration
**`database/migrations/2026_03_10_trong_tai_phancongcham.sql`**
- Thêm `INDEX idx_pcc_vongthi_active` để tăng tốc tra cứu
- Backfill: cấp quyền chấm (`phancongcham`) cho các trọng tài đã mời trước khi migration

---

## 2026-03-09


### Feature - Gỡ bộ tiêu chí khỏi vòng thi (Ungap Criteria from Round)

#### Tính năng
- Cho phép BTC gỡ liên kết giữa bộ tiêu chí và một vòng thi cụ thể trực tiếp từ panel bên phải tab Config Criteria
- Mỗi badge "Đang dùng" trên vòng thi nay có nút ✕ inline để gỡ ngay
- Cảnh báo nếu đã có dữ liệu chấm điểm liên quan (không xóa dữ liệu chấm)

#### Files thay đổi

##### `api/su_kien/quan_ly_bo_tieu_chi.php`
- `lay_ban_do_su_dung_bo_tieu_chi`: Thêm `c.idVongThi` vào SELECT query vòng thi; thêm `'idVongThi'` vào mỗi item trong usage map
- Thêm hàm mới `go_bo_tieu_chi_khoi_vong($conn, $id_nguoi, $id_sk, $id_bo, $id_vong_thi)`: kiểm tra quyền, cảnh báo nếu có chấm điểm, xóa bản ghi `cauhinh_tieuchi_sk`

##### `api/su_kien/go_bo_tieu_chi.php` *(file mới)*
- Endpoint POST nhận `id_sk`, `id_bo`, `id_vong_thi`
- Gọi `go_bo_tieu_chi_khoi_vong()` và trả về JSON chuẩn

##### `assets/js/event-detail.js`
- Thêm hàm `goBoTieuChiKhoiVong(idBo, idVongThi)` fetch tới `go_bo_tieu_chi.php`
- `renderCriteriaSetList`: Mỗi badge vòng thi (`bg-emerald-100`) nay có nút X (`criteria-ungap-btn`) với `data-id-bo` và `data-id-vong`
- `criteriaSetList` click handler: Thêm nhánh xử lý `.criteria-ungap-btn` — xác nhận SweetAlert → gọi API → reload panel

---

## 2026-03-05

### Fixed - Lỗi Tạo Vòng thi (Không khởi tạo được, báo lỗi hệ thống)

#### Nguyên nhân gốc rễ (Root causes)
1. **`kiem_tra_trung_ten_vong_thi`** — Query dùng `AND isActive = 1` nhưng bảng `vongthi` không có cột này (đã đổi thành `dongNopThuCong`). PDO ở chế độ `ERRMODE_EXCEPTION` → throw exception → hàm `tao_vong_thi()` bắt lỗi và trả về "Lỗi hệ thống".
2. **Session key không khớp** — `tao_vong_thi.php` đọc `$_SESSION['user_id']` nhưng `dang_nhap.php` lưu vào `$_SESSION['idTK']` → luôn nhận được `idNguoiTao = 0` → trả về 401 "Chưa đăng nhập".
3. **Bảng không tồn tại trong schema** — `vong_thi_co_du_lieu_lien_quan()` và `lay_thong_ke_vong_thi()` tham chiếu `bainop`, `phancong_chamthi`, `ketqua_chamthi` → không có trong `schema.sql`.
4. **Thiếu `define('_AUTHEN', true)`** — `cap_nhat_vong_thi.php`, `toggle_vong_thi.php`, `xoa_vong_thi.php`, `sap_xep_vong_thi.php` không khai báo hằng guard trước khi require `base.php` → PHP die ngay khi gọi API.
5. **Require file không tồn tại** — `toggle_vong_thi.php`, `xoa_vong_thi.php`, `sap_xep_vong_thi.php`, `cap_nhat_vong_thi.php` đều require `api/core/session_helper.php` không tồn tại.

#### Files đã sửa

##### `api/su_kien/quan_ly_vong_thi.php`
- `kiem_tra_trung_ten_vong_thi`: Xóa `AND isActive = 1` khỏi query
- `vong_thi_co_du_lieu_lien_quan`: Đổi bảng sai → `sanpham_vongthi`, `phancong_doclap`, `phancongcham`
- `lay_thong_ke_vong_thi`: Đổi bảng sai → `sanpham_vongthi`, `phancong_doclap`, `phancongcham`

##### `api/su_kien/tao_vong_thi.php`
- Đổi `$_SESSION['user_id']` → `$_SESSION['idTK']`

##### `api/su_kien/cap_nhat_vong_thi.php`
- Thêm `define('_AUTHEN', true)`
- Xóa require `session_helper.php`, đổi sang đọc trực tiếp `$_SESSION['idTK']`

##### `api/su_kien/toggle_vong_thi.php`, `xoa_vong_thi.php`, `sap_xep_vong_thi.php`
- Thêm `define('_AUTHEN', true)` vào đầu mỗi file
- Xóa require `session_helper.php`, đổi sang đọc trực tiếp `$_SESSION['idTK']`

##### `api/su_kien/danh_sach_vong_thi.php`, `cap_nhat_su_kien.php`, `luu_quy_che.php`, `luu_bo_tieu_chi.php`, `du_lieu_bo_tieu_chi.php`, `chi_tiet_quy_che.php`, `chi_tiet_bo_tieu_chi.php`
- Đổi toàn bộ `$_SESSION['user_id']` → `$_SESSION['idTK']` để đồng nhất với session key của `dang_nhap.php`

---

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
