<?php

/**
 * Quản lý sự kiện - Service Layer
 * 
 * File này chứa các hàm nghiệp vụ để quản lý sự kiện NCKH.
 * Bao gồm: tạo, cập nhật, lấy chi tiết, thống kê sự kiện.
 */

require_once __DIR__ . '/../core/base.php';

// ==========================================
// CONSTANTS
// ==========================================
define('SU_KIEN_TEN_MIN_LENGTH', 5);
define('SU_KIEN_TEN_MAX_LENGTH', 300);
define('SU_KIEN_MO_TA_MAX_LENGTH', 5000);

// ==========================================
// PERMISSION FUNCTIONS
// ==========================================

/**
 * Kiểm tra quyền quản lý sự kiện
 * 
 * @param PDO $conn Kết nối database
 * @param int $id_tk ID tài khoản
 * @param int $id_sk ID sự kiện (0 = kiểm tra quyền tạo mới)
 * @return bool
 */
function co_quyen_quan_ly_su_kien($conn, int $id_tk, int $id_sk = 0): bool
{
    // Người có quyền tạo sự kiện (Admin tự bypass qua kiem_tra_quyen_he_thong)
    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'tao_su_kien')) {
        return true;
    }

    // Kiểm tra quyền cấu hình sự kiện cụ thể
    if ($id_sk > 0) {
        return kiem_tra_quyen_su_kien($conn, $id_tk, $id_sk, 'cauhinh_sukien');
    }

    return false;
}

/**
 * Kiểm tra người dùng có phải BTC của sự kiện không
 */
function la_btc_su_kien($conn, int $id_tk, int $id_sk): bool
{
    if ($id_tk <= 0 || $id_sk <= 0 || !$conn instanceof PDO) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT 1
        FROM taikhoan_vaitro_sukien tvs
        JOIN vaitro v ON v.idVaiTro = tvs.idVaiTro
        WHERE tvs.idTK = :idTK
          AND tvs.idSK = :idSK
          AND tvs.isActive = 1
          AND v.maVaiTro = 'BTC'
        LIMIT 1
    ");
    $stmt->execute([':idTK' => $id_tk, ':idSK' => $id_sk]);
    return (bool) $stmt->fetchColumn();
}

// ==========================================
// VALIDATION FUNCTIONS
// ==========================================

/**
 * Validate dữ liệu đầu vào của sự kiện
 * 
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_du_lieu_su_kien(
    $ten_su_kien,
    $mo_ta,
    $ngay_mo_dk,
    $ngay_dong_dk,
    $ngay_bat_dau,
    $ngay_ket_thuc
): array {
    $errors = [];

    // Validate tên sự kiện
    $ten_su_kien = trim((string) $ten_su_kien);
    if ($ten_su_kien === '') {
        $errors[] = 'Tên sự kiện không được để trống';
    } elseif (mb_strlen($ten_su_kien) < SU_KIEN_TEN_MIN_LENGTH) {
        $errors[] = 'Tên sự kiện phải có ít nhất ' . SU_KIEN_TEN_MIN_LENGTH . ' ký tự';
    } elseif (mb_strlen($ten_su_kien) > SU_KIEN_TEN_MAX_LENGTH) {
        $errors[] = 'Tên sự kiện không được vượt quá ' . SU_KIEN_TEN_MAX_LENGTH . ' ký tự';
    }

    // Validate mô tả
    $mo_ta = trim((string) $mo_ta);
    if (mb_strlen($mo_ta) > SU_KIEN_MO_TA_MAX_LENGTH) {
        $errors[] = 'Mô tả không được vượt quá ' . SU_KIEN_MO_TA_MAX_LENGTH . ' ký tự';
    }

    // Validate ngày tháng
    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    if ($ngay_mo_dk !== null && $ngay_dong_dk !== null) {
        $ts_mo = strtotime((string) $ngay_mo_dk);
        $ts_dong = strtotime((string) $ngay_dong_dk);
        if ($ts_mo === false || $ts_dong === false) {
            $errors[] = 'Định dạng ngày mở/đóng đăng ký không hợp lệ';
        } elseif ($ts_mo > $ts_dong) {
            $errors[] = 'Ngày mở đăng ký phải trước ngày đóng đăng ký';
        }
    }

    if ($ngay_bat_dau !== null && $ngay_ket_thuc !== null) {
        $ts_bd = strtotime((string) $ngay_bat_dau);
        $ts_kt = strtotime((string) $ngay_ket_thuc);
        if ($ts_bd === false || $ts_kt === false) {
            $errors[] = 'Định dạng ngày bắt đầu/kết thúc không hợp lệ';
        } elseif ($ts_bd > $ts_kt) {
            $errors[] = 'Ngày bắt đầu phải trước ngày kết thúc';
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Kiểm tra tên sự kiện có trùng trong cùng cấp tổ chức không
 */
function kiem_tra_trung_ten_su_kien($conn, string $ten_su_kien, ?int $id_cap, int $exclude_id = 0): bool
{
    if (!$conn instanceof PDO) {
        return false;
    }

    $sql = 'SELECT COUNT(*) FROM sukien WHERE tenSK = :tenSK AND isActive = 1';
    $params = [':tenSK' => trim($ten_su_kien)];

    if ($id_cap !== null) {
        $sql .= ' AND idCap = :idCap';
        $params[':idCap'] = $id_cap;
    }

    if ($exclude_id > 0) {
        $sql .= ' AND idSK != :excludeId';
        $params[':excludeId'] = $exclude_id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

// ==========================================
// CRUD FUNCTIONS
// ==========================================

/**
 * Tạo sự kiện mới
 * 
 * @param PDO $conn Kết nối database
 * @param int $id_nguoi_tao ID người tạo
 * @param string $ten_su_kien Tên sự kiện
 * @param string $mo_ta Mô tả
 * @param int|null $id_cap ID cấp tổ chức
 * @param string|null $ngay_mo_dk Ngày mở đăng ký
 * @param string|null $ngay_dong_dk Ngày đóng đăng ký
 * @param string|null $ngay_bat_dau Ngày bắt đầu
 * @param string|null $ngay_ket_thuc Ngày kết thúc
 * @param int $is_active Trạng thái active
 * @return array ['status' => bool, 'message' => string, 'idSK' => int|null, 'warnings' => array]
 */
function btc_tao_su_kien(
    $conn,
    $id_nguoi_tao,
    $ten_su_kien,
    $mo_ta,
    $id_cap,
    $ngay_mo_dk = null,
    $ngay_dong_dk = null,
    $ngay_bat_dau = null,
    $ngay_ket_thuc = null,
    $is_active = 1
) {
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_cap = ($id_cap !== null && (int) $id_cap > 0) ? (int) $id_cap : null;
    $is_active = ((int) $is_active === 1) ? 1 : 0;
    $ten_su_kien = trim((string) $ten_su_kien);
    $mo_ta = trim((string) $mo_ta);
    $warnings = [];

    // Kiểm tra quyền
    if (!co_quyen_quan_ly_su_kien($conn, $id_nguoi_tao)) {
        return ['status' => false, 'message' => 'Không có quyền tạo sự kiện'];
    }

    // Validate dữ liệu
    $validation = validate_du_lieu_su_kien($ten_su_kien, $mo_ta, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    // Chuẩn hóa ngày tháng
    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    // Kiểm tra cấp tổ chức tồn tại
    if ($id_cap !== null && !_is_exist($conn, 'cap_tochuc', 'idCap', $id_cap)) {
        return ['status' => false, 'message' => 'Cấp tổ chức không tồn tại'];
    }

    // Cảnh báo nếu tên trùng (không chặn tạo)
    if (kiem_tra_trung_ten_su_kien($conn, $ten_su_kien, $id_cap)) {
        $warnings[] = 'Đã tồn tại sự kiện cùng tên trong cấp tổ chức này';
    }

    try {
        if (!$conn instanceof PDO) {
            return ['status' => false, 'message' => 'Kết nối cơ sở dữ liệu không hợp lệ'];
        }

        $conn->beginTransaction();

        $fields = ['tenSK', 'moTa', 'nguoiTao', 'isActive'];
        $values = [$ten_su_kien, $mo_ta, $id_nguoi_tao, $is_active];

        if ($id_cap !== null) {
            $fields[] = 'idCap';
            $values[] = $id_cap;
        }

        $dateFields = [
            'ngayMoDangKy' => $ngay_mo_dk,
            'ngayDongDangKy' => $ngay_dong_dk,
            'ngayBatDau' => $ngay_bat_dau,
            'ngayKetThuc' => $ngay_ket_thuc,
        ];

        foreach ($dateFields as $column => $value) {
            if ($value !== null) {
                $fields[] = $column;
                $values[] = $value;
            }
        }

        $created = _insert_info($conn, 'sukien', $fields, $values);
        if (!$created) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện'];
        }

        $id_sk = (int) $conn->lastInsertId();

        $stmtBTC = $conn->prepare("SELECT idVaiTro FROM vaitro WHERE maVaiTro = 'BTC' LIMIT 1");
        $stmtBTC->execute();
        $idVaiTroBTC = (int) $stmtBTC->fetchColumn();
        if ($idVaiTroBTC <= 0) {
            $conn->rollBack();
            return ['status' => false, 'message' => 'Không tìm thấy vai trò BTC trong hệ thống'];
        }

        $existsBtcRole = _select_info($conn, 'taikhoan_vaitro_sukien', ['id'], [
            'WHERE' => [
                'idTK',
                '=',
                $id_nguoi_tao,
                'AND',
                'idSK',
                '=',
                $id_sk,
                'AND',
                'idVaiTro',
                '=',
                $idVaiTroBTC,
                'AND',
                'isActive',
                '=',
                1,
                '',
            ],
            'LIMIT' => [1],
        ]);

        if (empty($existsBtcRole)) {
            $assigned = _insert_info(
                $conn,
                'taikhoan_vaitro_sukien',
                ['idTK', 'idSK', 'idVaiTro', 'nguonTao', 'idNguoiCap', 'isActive'],
                [$id_nguoi_tao, $id_sk, $idVaiTroBTC, 'BTC_THEM', $id_nguoi_tao, 1]
            );

            if (!$assigned) {
                $conn->rollBack();
                return ['status' => false, 'message' => 'Tạo sự kiện thành công nhưng gán vai trò BTC thất bại'];
            }
        }

        if ($is_active === 1) {
            _gui_thong_bao_su_kien_moi($conn, $id_sk, $ten_su_kien, $id_nguoi_tao);
        }

        $conn->commit();

        $response = [
            'status' => true,
            'message' => 'Đã khởi tạo sự kiện thành công',
            'idSK' => $id_sk,
        ];

        // Thêm cảnh báo nếu có
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return $response;
    } catch (Throwable $exception) {
        if ($conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log('Lỗi tạo sự kiện: ' . $exception->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo sự kiện. Vui lòng thử lại sau.'];
    }
}

function _gui_thong_bao_su_kien_moi($conn, int $id_sk, string $ten_su_kien, int $id_nguoi_tao): void
{
    if (!$conn instanceof PDO) {
        return;
    }

    $created = _insert_info(
        $conn,
        'thongbao',
        ['idSK', 'tieuDe', 'noiDung', 'loaiThongBao', 'nguoiGui', 'isPublic'],
        [
            $id_sk,
            'Sự kiện mới: ' . $ten_su_kien,
            'Sự kiện "' . $ten_su_kien . '" vừa được công bố. Hãy xem chi tiết và đăng ký tham gia!',
            'su_kien_moi',
            $id_nguoi_tao,
            1,
        ]
    );

    if (!$created) {
        return;
    }

    $id_thong_bao = (int) $conn->lastInsertId();

    $stmt = $conn->prepare(
        'SELECT idTK FROM taikhoan WHERE isActive = 1 AND idLoaiTK IN (2, 3) AND idTK != :idNguoiTao'
    );
    $stmt->execute([':idNguoiTao' => $id_nguoi_tao]);
    $nguoi_nhan = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($nguoi_nhan)) {
        return;
    }

    $insertStmt = $conn->prepare(
        'INSERT INTO thongbao_nguoinhan (idThongBao, idTK, daDoc) VALUES (:idThongBao, :idTK, 0)'
    );

    foreach ($nguoi_nhan as $id_tk) {
        $insertStmt->execute([
            ':idThongBao' => $id_thong_bao,
            ':idTK' => (int) $id_tk,
        ]);
    }
}

/**
 * Cập nhật thông tin sự kiện
 * 
 * @return array ['status' => bool, 'message' => string, 'warnings' => array]
 */
function btc_cap_nhat_su_kien(
    $conn,
    $id_nguoi_thuc_hien,
    $id_su_kien,
    $ten_su_kien,
    $mo_ta,
    $id_cap,
    $ngay_mo_dk,
    $ngay_dong_dk,
    $ngay_bat_dau,
    $ngay_ket_thuc,
    $is_active
) {
    $id_nguoi_thuc_hien = (int) $id_nguoi_thuc_hien;
    $id_su_kien = (int) $id_su_kien;
    $id_cap = ($id_cap !== null && (int) $id_cap > 0) ? (int) $id_cap : null;
    $is_active = ((int) $is_active === 1) ? 1 : 0;
    $ten_su_kien = trim((string) $ten_su_kien);
    $mo_ta = trim((string) $mo_ta);
    $warnings = [];

    // Validate ID
    if ($id_su_kien <= 0) {
        return ['status' => false, 'message' => 'ID sự kiện không hợp lệ'];
    }

    // Kiểm tra quyền
    if (!co_quyen_quan_ly_su_kien($conn, $id_nguoi_thuc_hien, $id_su_kien)) {
        return ['status' => false, 'message' => 'Không có quyền cập nhật sự kiện'];
    }

    // Kiểm tra sự kiện tồn tại
    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại hoặc đã bị xoá'];
    }

    // Validate dữ liệu
    $validation = validate_du_lieu_su_kien($ten_su_kien, $mo_ta, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    // Kiểm tra cấp tổ chức tồn tại
    if ($id_cap !== null && !_is_exist($conn, 'cap_tochuc', 'idCap', $id_cap)) {
        return ['status' => false, 'message' => 'Cấp tổ chức không tồn tại'];
    }

    // Cảnh báo nếu tên trùng (không chặn cập nhật)
    if (kiem_tra_trung_ten_su_kien($conn, $ten_su_kien, $id_cap, $id_su_kien)) {
        $warnings[] = 'Đã tồn tại sự kiện cùng tên trong cấp tổ chức này';
    }

    // Chuẩn hóa ngày tháng
    $ngay_mo_dk = !empty($ngay_mo_dk) ? $ngay_mo_dk : null;
    $ngay_dong_dk = !empty($ngay_dong_dk) ? $ngay_dong_dk : null;
    $ngay_bat_dau = !empty($ngay_bat_dau) ? $ngay_bat_dau : null;
    $ngay_ket_thuc = !empty($ngay_ket_thuc) ? $ngay_ket_thuc : null;

    try {
        $fields = ['tenSK', 'moTa', 'idCap', 'ngayMoDangKy', 'ngayDongDangKy', 'ngayBatDau', 'ngayKetThuc', 'isActive'];
        $values = [$ten_su_kien, $mo_ta, $id_cap, $ngay_mo_dk, $ngay_dong_dk, $ngay_bat_dau, $ngay_ket_thuc, $is_active];

        $updated = _update_info($conn, 'sukien', $fields, $values, ['idSK' => ['=', $id_su_kien, '']]);

        if (!$updated) {
            return ['status' => false, 'message' => 'Lỗi cập nhật sự kiện. Vui lòng thử lại.'];
        }

        $response = [
            'status' => true,
            'message' => 'Cập nhật sự kiện thành công',
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return $response;
    } catch (Throwable $exception) {
        error_log('Lỗi cập nhật sự kiện: ' . $exception->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi cập nhật sự kiện'];
    }
}

/**
 * Lấy chi tiết sự kiện bao gồm thông tin cấp và người tạo
 */
function btc_lay_chi_tiet_su_kien($conn, $id_su_kien)
{
    $id_su_kien = (int) $id_su_kien;
    if ($id_su_kien <= 0) {
        return null;
    }

    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return null;
    }

    $cap = !empty($su_kien['idCap']) ? truy_van_mot_ban_ghi($conn, 'cap_tochuc', 'idCap', (int) $su_kien['idCap']) : null;
    $loai_cap = (!empty($cap) && !empty($cap['idLoaiCap'])) ? truy_van_mot_ban_ghi($conn, 'loaicap', 'idLoaiCap', (int) $cap['idLoaiCap']) : null;
    $nguoi_tao = !empty($su_kien['nguoiTao']) ? truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', (int) $su_kien['nguoiTao']) : null;

    $su_kien['tenCap'] = $cap['tenCap'] ?? null;
    $su_kien['tenLoaiCap'] = $loai_cap['tenLoaiCap'] ?? null;
    $su_kien['nguoiTaoTen'] = $nguoi_tao['tenTK'] ?? null;

    return $su_kien;
}

/**
 * Lấy trạng thái hiện tại của sự kiện
 * 
 * @return string|null 'chua_bat_dau' | 'dang_dien_ra' | 'da_ket_thuc' | 'bi_vo_hieu'
 */
function lay_trang_thai_su_kien($conn, int $id_su_kien): ?string
{
    $su_kien = truy_van_mot_ban_ghi($conn, 'sukien', 'idSK', $id_su_kien);
    if (!$su_kien) {
        return null;
    }

    if ((int) $su_kien['isActive'] !== 1) {
        return 'bi_vo_hieu';
    }

    $now = time();
    $ngay_bat_dau = !empty($su_kien['ngayBatDau']) ? strtotime((string) $su_kien['ngayBatDau']) : null;
    $ngay_ket_thuc = !empty($su_kien['ngayKetThuc']) ? strtotime((string) $su_kien['ngayKetThuc']) : null;

    if ($ngay_bat_dau !== null && $now < $ngay_bat_dau) {
        return 'chua_bat_dau';
    }

    if ($ngay_ket_thuc !== null && $now > $ngay_ket_thuc) {
        return 'da_ket_thuc';
    }

    return 'dang_dien_ra';
}

/**
 * Lấy thống kê sự kiện (số nhóm, số bài nộp, etc.)
 */
function lay_thong_ke_su_kien($conn, int $id_su_kien): array
{
    if (!$conn instanceof PDO || $id_su_kien <= 0) {
        return [];
    }

    $stats = [];

    // Đếm số nhóm tham gia — bảng `nhom`, cột `idSK`
    $stmt = $conn->prepare('SELECT COUNT(*) FROM nhom WHERE idSK = ? AND isActive = 1');
    $stmt->execute([$id_su_kien]);
    $stats['so_nhom'] = (int) $stmt->fetchColumn();

    // Đếm số bài nộp — bảng `sanpham`, cột `idSK`
    $stmt = $conn->prepare('SELECT COUNT(*) FROM sanpham WHERE idSK = ? AND isActive = 1');
    $stmt->execute([$id_su_kien]);
    $stats['so_bai_nop'] = (int) $stmt->fetchColumn();

    // Đếm số vòng thi
    $stmt = $conn->prepare('SELECT COUNT(*) FROM vongthi WHERE idSK = ?');
    $stmt->execute([$id_su_kien]);
    $stats['so_vong_thi'] = (int) $stmt->fetchColumn();

    // Đếm số giám khảo (các vai trò chấm điểm: GV_PHAN_BIEN, GV_CHAM_DOCLAP, GV_CHAM_TIEUBAN)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT tvs.idTK)
        FROM taikhoan_vaitro_sukien tvs
        JOIN vaitro v ON v.idVaiTro = tvs.idVaiTro
        WHERE tvs.idSK = ?
          AND tvs.isActive = 1
          AND v.maVaiTro IN ('GV_PHAN_BIEN', 'GV_CHAM_DOCLAP', 'GV_CHAM_TIEUBAN')
    ");
    $stmt->execute([$id_su_kien]);
    $stats['so_giam_khao'] = (int) $stmt->fetchColumn();

    return $stats;
}
