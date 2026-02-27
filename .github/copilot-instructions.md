# Hệ Thống Quản Lý Hội Thảo Nghiên Cứu Khoa Học (ezManagement)
**AI System Prompt & Coding Guidelines**

## 1. Tổng quan dự án (Project Overview)
Dự án là một hệ thống web quản lý hội nghị nghiên cứu khoa học (tương tự EasyChair). Hệ thống hỗ trợ vòng đời trọn vẹn của một sự kiện học thuật, từ khâu tổ chức, nộp bài, bình duyệt (peer review), đến sắp xếp lịch báo cáo và xét giải.

**Phạm vi tính năng chính (Core Workflow):**
1. **Khởi tạo & Cấu hình:** Ban tổ chức (BTC) tạo sự kiện, thiết lập form nộp bài.
2. **Quản lý nhóm (Sinh viên):** Sinh viên tạo nhóm (Public/Private), mời thành viên hoặc duyệt yêu cầu tham gia.
3. **Nộp bài:** Các nhóm nộp sản phẩm/bài báo theo form cấu hình của BTC.
4. **Sàng lọc vòng 1:** BTC đánh giá sơ bộ và quyết định cho qua/loại.
5. **Bình duyệt (Peer Review):** BTC phân công Giảng viên chấm bài theo khung điểm chung. Giảng viên thực hiện chấm.
6. **Sàng lọc vòng 2:** BTC xem tổng điểm từ Giảng viên để quyết định danh sách vào vòng báo cáo.
7. **Tổ chức báo cáo:** BTC tạo các tiểu ban, sắp xếp lịch và phân công Ban giám khảo (BGK).
8. **Chấm giải:** BGK chấm điểm tại tiểu ban, BTC tổng hợp kết quả và xét giải thưởng.

## 2. Mục tiêu dự án (Project Goals)
* Xây dựng chức năng hoàn chỉnh, logic nghiệp vụ chặt chẽ cho 2 đối tượng chính: **Sinh viên** và **Giảng viên** (bao gồm vai trò BTC/Giám khảo).
* Đảm bảo kiến trúc phân tách rõ ràng giữa Frontend và Backend API.
* Mã nguồn dễ đọc, dễ bảo trì, phục vụ tốt cho việc báo cáo và phát triển mở rộng.

## 3. Tech Stack
* **Backend:** PHP thuần làm API (không sử dụng framework nào). Sử dụng PDO để tương tác với MySQL. Mỗi thao tác API được định nghĩa trong một file PHP riêng biệt trong thư mục 'api/'.
* **Database:** MySQL.
* **Frontend:** HTML5, CSS3, JavaScript thuần, Tailwind.
* **Giao tiếp:** Sử dụng `Fetch API` từ Frontend để gọi các endpoint API của Backend.

## 4. Kiến trúc hệ thống (System Architecture)
* **Mô hình:** Client - Server (API-driven).
* **Backend (API Layer):** Xử lý logic nghiệp vụ, tương tác CSDL, và trả về dữ liệu định dạng JSON. Không render HTML từ Backend.
* **Frontend (Presentation Layer):** Gọi API, xử lý DOM (DOM Manipulation) và hiển thị dữ liệu cho người dùng. Sử dụng cấu trúc layout chuẩn với header, main content, và footer. Tối ưu trải nghiệm người dùng (UX) với Tailwind CSS. Có thư mục layout và sử dụng php require/include và ob_start() để tái sử dụng layout cho các trang khác nhau. Các css và js đặc thù cho từng trang sẽ được để riêng với cấu trúc html, các file này sẽ được để ở thư mục assets/css và assets/js, và được include vào layout thông qua biến $pageCss và $pageJs.
* **Xác thực (Authentication):** Sử dụng session-based authentication. Người dùng đăng nhập sẽ được cấp session và lưu thông tin trong session để xác thực các yêu cầu sau này.

## 5. Quy tắc viết code (Coding Guidelines)

### 5.1. Backend (PHP API)
* **Database interaction:** Bắt buộc sử dụng **PDO** (PHP Data Objects) với Prepared Statements để chống SQL Injection. Không dùng `mysqli_*` cũ.
* **Response Format:** Chuẩn hóa JSON response cho mọi API:
  ```json
  {
    "status": "success" | "error",
    "message": "Mô tả kết quả",
    "data": { ... } // Dữ liệu trả về nếu có
  }

### 5.2. Database Schema:**
- Luôn luôn xem cấu trúc cơ sở dữ liệu trong thư mục database/schema.sql trước khi viết API để đảm bảo hiểu rõ các bảng, mối quan hệ, và ràng buộc dữ liệu.
- Tuân thủ các quy tắc về khóa chính, khóa ngoại, và ràng buộc dữ liệu đã được định nghĩa trong schema để đảm bảo tính toàn vẹn của dữ liệu.
- Mọi sự thay đổi về cơ sở dữ liệu phải tạo thêm một file migration mới trong thư mục database/migrations/ với tên rõ ràng, ví dụ: `2024_06_01_add_review_table.sql`.
- Không thực hiện thay đổi cơ sở dữ liệu trực tiếp qua terminal, các thay đổi về cấu trúc cơ sở dữ liệu cần tạo migration và được người dùng kiểm tra, thực thi.

### 5.2. Frontend (HTML/CSS/JS)
* **Cấu trúc file:** Tách biệt rõ ràng giữa HTML, CSS và JavaScript. Không viết inline styles hoặc scripts.
* **CSS:** Sử dụng Tailwind CSS để đảm bảo tính nhất quán và dễ bảo trì. Tránh viết CSS tùy chỉnh nếu có thể, tận dụng tối đa các utility classes của Tailwind.
* **JavaScript:** Sử dụng JavaScript thuần để xử lý DOM và gọi API. Không sử dụng thư viện hoặc framework nào (như jQuery, React, Vue).
* **Tối ưu UX:** Đảm bảo giao diện thân thiện, dễ sử dụng. Sử dụng các component của Tailwind để tạo giao diện hiện đại và responsive.
* **Xử lý lỗi:** Hiển thị thông báo lỗi rõ ràng cho người dùng khi có lỗi xảy ra (ví dụ: lỗi mạng, lỗi xác thực, lỗi server).
* **Tái sử dụng code:** Sử dụng các component và layout chung để tránh lặp lại mã. Tận dụng PHP `require/include` để tái sử dụng layout và các phần tử giao diện.
* **Bảo mật:** Đảm bảo các yêu cầu API được xác thực đúng cách. Không lưu trữ thông tin nhạy cảm trên client-side.
* **Sử dụng SweetAlert:** Sử dụng thư viện SweetAlert để hiển thị các thông báo, cảnh báo, và xác nhận một cách đẹp mắt và dễ hiểu cho người dùng.
* **Pattern thiết kế:** Tham khảo pattern thiết kế tại thư mục soft-ui/pages/* và soft-ui/index.html

### 5.3. Quy tắc chung
* **Đặt tên:** Sử dụng tên biến, hàm, và class có ý nghĩa rõ ràng, phản ánh đúng chức năng của chúng. Ví dụ: `getUserById`, `submitPaper`, `reviewPaper`.
* **Comment:** Viết comment rõ ràng, giải thích mục đích của các đoạn code phức tạp hoặc các logic nghiệp vụ quan trọng. Tránh comment thừa thãi hoặc comment không cần thiết.
* **Định dạng mã nguồn:** Giữ mã nguồn sạch sẽ, có cấu trúc rõ ràng.
* **Docs**: Cung cấp tài liệu chi tiết về cách sử dụng API, cấu trúc dữ liệu, và hướng dẫn triển khai hệ thống. Tài liệu được cập nhật thường xuyên để phản ánh đúng trạng thái hiện tại của dự án. Các tài liệu mô tả về api, database schema, và hướng dẫn sử dụng sẽ được lưu trữ trong thư mục `docs/` của dự án. docs/api, docs/database, docs/user-guide.
* **Changelog:** Mỗi sự thay đổi cần cập nhật changelog để ghi lại các thay đổi quan trọng, cập nhật tính năng, và sửa lỗi trong docs/CHANGELOG.md.