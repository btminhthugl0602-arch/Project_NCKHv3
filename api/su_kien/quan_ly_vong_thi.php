<?php
/**
 * Quản lý vòng thi - Service Layer
 * 
 * File này chứa các hàm nghiệp vụ để quản lý vòng thi của sự kiện.
 * Bao gồm: tạo, cập nhật, xóa, sắp xếp, toggle trạng thái vòng thi.
 */

require_once __DIR__ . '/../core/base.php';

// ==========================================
// CONSTANTS
// ==========================================
define('VONG_THI_TEN_MIN_LENGTH', 3);
define('VONG_THI_TEN_MAX_LENGTH', 200);
define('VONG_THI_MO_TA_MAX_LENGTH', 2000);

// ==========================================
// PERMISSION FUNCTIONS
// ==========================================

/**
 * Kiểm tra quyền quản lý vòng thi của sự kiện
 */
function co_quyen_quan_ly_vong_thi($conn, int $id_nguoi_thuc_hien, int $id_sk): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'admin_events')) {
        return true;
    }

    if (kiem_tra_quyen_he_thong($conn, $id_nguoi_thuc_hien, 'tao_su_kien')) {
        return true;
    }

    return kiem_tra_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_sk, 'cauhinh_vongthi')
        || kiem_tra_quyen_su_kien($conn, $id_nguoi_thuc_hien, $id_sk, 'cauhinh_sukien');
}

// ==========================================
// VALIDATION FUNCTIONS
// ==========================================

/**
 * Validate dữ liệu vòng thi
 * 
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_du_lieu_vong_thi($ten_vong, $mo_ta, $ngay_bd, $ngay_kt): array
{
    $errors = [];

    // Validate tên vòng thi
    $ten_vong = trim((string) $ten_vong);
    if ($ten_vong === '') {
        $errors[] = 'Tên vòng thi không được để trống';
    } elseif (mb_strlen($ten_vong) < VONG_THI_TEN_MIN_LENGTH) {
        $errors[] = 'Tên vòng thi phải có ít nhất ' . VONG_THI_TEN_MIN_LENGTH . ' ký tự';
    } elseif (mb_strlen($ten_vong) > VONG_THI_TEN_MAX_LENGTH) {
        $errors[] = 'Tên vòng thi không được vượt quá ' . VONG_THI_TEN_MAX_LENGTH . ' ký tự';
    }

    // Validate mô tả
    $mo_ta = trim((string) $mo_ta);
    if (mb_strlen($mo_ta) > VONG_THI_MO_TA_MAX_LENGTH) {
        $errors[] = 'Mô tả vòng thi không được vượt quá ' . VONG_THI_MO_TA_MAX_LENGTH . ' ký tự';
    }

    // Validate ngày tháng
    $ngay_bd = !empty($ngay_bd) ? $ngay_bd : null;
    $ngay_kt = !empty($ngay_kt) ? $ngay_kt : null;

    if ($ngay_bd !== null && $ngay_kt !== null) {
        $ts_bd = strtotime((string) $ngay_bd);
        $ts_kt = strtotime((string) $ngay_kt);
        if ($ts_bd === false || $ts_kt === false) {
            $errors[] = 'Định dạng ngày không hợp lệ';
        } elseif ($ts_bd > $ts_kt) {
            $errors[] = 'Ngày bắt đầu không được sau ngày kết thúc';
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

/**
 * Kiểm tra tên vòng thi có trùng trong cùng sự kiện không
 */
function kiem_tra_trung_ten_vong_thi($conn, string $ten_vong, int $id_sk, int $exclude_id = 0): bool
{
    if (!$conn instanceof PDO || $id_sk <= 0) {
        return false;
    }

    $sql = 'SELECT COUNT(*) FROM vongthi WHERE tenVongThi = :tenVong AND idSK = :idSK';
    $params = [':tenVong' => trim($ten_vong), ':idSK' => $id_sk];

    if ($exclude_id > 0) {
        $sql .= ' AND idVongThi != :excludeId';
        $params[':excludeId'] = $exclude_id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

// ==========================================
// HELPER FUNCTIONS  
// ==========================================

/**
 * Lấy số thứ tự tiếp theo cho vòng thi mới của sự kiện
 */
function lay_thu_tu_tiep_theo_vong_thi($conn, int $id_sk): int
{
    if (!$conn instanceof PDO || $id_sk <= 0) {
        return 1;
    }

    $stmt = $conn->prepare('SELECT MAX(thuTu) FROM vongthi WHERE idSK = ?');
    $stmt->execute([$id_sk]);
    $max = $stmt->fetchColumn();

    return $max ? ((int) $max + 1) : 1;
}

/**
 * Lấy trạng thái hiện tại của vòng thi dựa trên ngày bắt đầu/kết thúc
 * 
 * @return string 'chua_bat_dau' | 'dang_dien_ra' | 'da_ket_thuc'
 */
function lay_trang_thai_vong_thi($vong_thi): string
{
    if (!is_array($vong_thi)) {
        return 'da_ket_thuc';
    }

    $now = time();
    $ngay_bd = !empty($vong_thi['ngayBatDau']) ? strtotime((string) $vong_thi['ngayBatDau']) : null;
    $ngay_kt = !empty($vong_thi['ngayKetThuc']) ? strtotime((string) $vong_thi['ngayKetThuc']) : null;

    if ($ngay_bd !== null && $now < $ngay_bd) {
        return 'chua_bat_dau';
    }

    if ($ngay_kt !== null && $now > $ngay_kt) {
        return 'da_ket_thuc';
    }

    return 'dang_dien_ra';
}

/**
 * Kiểm tra vòng thi có dữ liệu liên quan không (để quyết định có thể xóa không)
 */
function vong_thi_co_du_lieu_lien_quan($conn, int $id_vong_thi): array
{
    $has_data = [];

    if (!$conn instanceof PDO || $id_vong_thi <= 0) {
        return $has_data;
    }

    // Kiểm tra sản phẩm đã tham gia vòng thi
    $stmt = $conn->prepare('SELECT COUNT(*) FROM sanpham_vongthi WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    if ((int) $stmt->fetchColumn() > 0) {
        $has_data[] = 'sản phẩm đã nộp';
    }

    // Kiểm tra phân công chấm độc lập
    $stmt = $conn->prepare('SELECT COUNT(*) FROM phancong_doclap WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    if ((int) $stmt->fetchColumn() > 0) {
        $has_data[] = 'phân công chấm';
    }

    // Kiểm tra phân công chấm theo bộ tiêu chí
    $stmt = $conn->prepare('SELECT COUNT(*) FROM phancongcham WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    if ((int) $stmt->fetchColumn() > 0) {
        $has_data[] = 'kết quả chấm';
    }

    return $has_data;
}

// ==========================================
// CRUD FUNCTIONS
// ==========================================

/**
 * Tạo vòng thi mới
 * 
 * @param int|null $thu_tu Nếu null sẽ tự động lấy số tiếp theo
 */
function tao_vong_thi($conn, $id_nguoi_tao, $id_sk, $ten_vong, $mo_ta, $thu_tu = null, $ngay_bd = null, $ngay_kt = null)
{
    $id_nguoi_tao = (int) $id_nguoi_tao;
    $id_sk = (int) $id_sk;
    $ten_vong = trim((string) $ten_vong);
    $mo_ta = trim((string) $mo_ta);
    $warnings = [];

    // Validate cơ bản
    if ($id_nguoi_tao <= 0 || $id_sk <= 0) {
        return ['status' => false, 'message' => 'Thông tin người tạo hoặc sự kiện không hợp lệ'];
    }

    // Kiểm tra sự kiện tồn tại
    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // Kiểm tra quyền
    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_tao, $id_sk)) {
        return ['status' => false, 'message' => 'Bạn không có quyền cấu hình vòng thi cho sự kiện này'];
    }

    // Validate dữ liệu
    $validation = validate_du_lieu_vong_thi($ten_vong, $mo_ta, $ngay_bd, $ngay_kt);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    // Cảnh báo nếu tên trùng
    if (kiem_tra_trung_ten_vong_thi($conn, $ten_vong, $id_sk)) {
        $warnings[] = 'Đã tồn tại vòng thi cùng tên trong sự kiện này';
    }

    // Tự động lấy số thứ tự nếu không truyền
    if ($thu_tu === null) {
        $thu_tu = lay_thu_tu_tiep_theo_vong_thi($conn, $id_sk);
    } else {
        $thu_tu = max(1, (int) $thu_tu);
    }

    // Chuẩn hóa ngày tháng
    $ngay_bd = !empty($ngay_bd) ? $ngay_bd : null;
    $ngay_kt = !empty($ngay_kt) ? $ngay_kt : null;

    try {
        $result = _insert_info(
            $conn,
            'vongthi',
            ['idSK', 'tenVongThi', 'moTa', 'thuTu', 'ngayBatDau', 'ngayKetThuc'],
            [$id_sk, $ten_vong, $mo_ta, $thu_tu, $ngay_bd, $ngay_kt]
        );

        if (!$result) {
            return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo vòng thi'];
        }

        $id_vong_thi = (int) $conn->lastInsertId();

        $response = [
            'status' => true,
            'message' => 'Đã tạo vòng thi thành công',
            'idVongThi' => $id_vong_thi,
            'thuTu' => $thu_tu,
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return $response;

    } catch (Throwable $e) {
        error_log('Lỗi tạo vòng thi: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo vòng thi'];
    }
}

/**
 * Cập nhật thông tin vòng thi
 */
function cap_nhat_vong_thi($conn, $id_nguoi_sua, $id_vong_thi, $ten_vong, $mo_ta, $ngay_bd, $ngay_kt, $thu_tu = null)
{
    $id_nguoi_sua = (int) $id_nguoi_sua;
    $id_vong_thi = (int) $id_vong_thi;
    $ten_vong = trim((string) $ten_vong);
    $mo_ta = trim((string) $mo_ta);
    $warnings = [];

    // Validate ID
    if ($id_nguoi_sua <= 0 || $id_vong_thi <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    // Kiểm tra vòng thi tồn tại
    $vong_thi = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
    if (!$vong_thi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại hoặc đã bị xóa'];
    }

    $id_sk = (int) $vong_thi['idSK'];

    // Kiểm tra quyền
    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_sua, $id_sk)) {
        return ['status' => false, 'message' => 'Bạn không có quyền cập nhật vòng thi này'];
    }

    // Validate dữ liệu
    $validation = validate_du_lieu_vong_thi($ten_vong, $mo_ta, $ngay_bd, $ngay_kt);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    // Cảnh báo nếu tên trùng
    if (kiem_tra_trung_ten_vong_thi($conn, $ten_vong, $id_sk, $id_vong_thi)) {
        $warnings[] = 'Đã tồn tại vòng thi cùng tên trong sự kiện này';
    }

    // Chuẩn hóa ngày tháng
    $ngay_bd = !empty($ngay_bd) ? $ngay_bd : null;
    $ngay_kt = !empty($ngay_kt) ? $ngay_kt : null;

    try {
        $fields = ['tenVongThi', 'moTa', 'ngayBatDau', 'ngayKetThuc'];
        $values = [$ten_vong, $mo_ta, $ngay_bd, $ngay_kt];

        if ($thu_tu !== null) {
            $fields[] = 'thuTu';
            $values[] = max(1, (int) $thu_tu);
        }

        $conditions = ['idVongThi' => ['=', $id_vong_thi, '']];
        $result = _update_info($conn, 'vongthi', $fields, $values, $conditions);

        if (!$result) {
            return ['status' => false, 'message' => 'Lỗi cập nhật vòng thi'];
        }

        $response = [
            'status' => true,
            'message' => 'Cập nhật vòng thi thành công',
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return $response;

    } catch (Throwable $e) {
        error_log('Lỗi cập nhật vòng thi: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi cập nhật vòng thi'];
    }
}

/**
 * Lấy danh sách vòng thi của sự kiện (có thêm trạng thái)
 */
function lay_ds_vong_thi($conn, $id_sk)
{
    $id_sk = (int) $id_sk;
    if ($id_sk <= 0) {
        return [];
    }

    $conditions = [
        'WHERE' => ['idSK', '=', $id_sk, ''],
        'ORDER BY' => ['thuTu', 'ASC'],
    ];

    $data = _select_info($conn, 'vongthi', [], $conditions);

    if (!is_array($data)) {
        return [];
    }

    // Bổ sung trạng thái cho mỗi vòng thi
    foreach ($data as &$vong) {
        $vong['trangThai'] = lay_trang_thai_vong_thi($vong);
        // Kiểm tra đóng nộp thủ công
        $vong['daDongNop'] = ((int) ($vong['dongNopThuCong'] ?? 0) === 1);
    }

    return $data;
}

/**
 * Lấy chi tiết vòng thi theo ID
 */
function lay_chi_tiet_vong_thi($conn, $id_vong_thi)
{
    $id_vong_thi = (int) $id_vong_thi;
    if ($id_vong_thi <= 0) {
        return null;
    }

    $conditions = [
        'WHERE' => ['idVongThi', '=', $id_vong_thi, ''],
        'LIMIT' => [1],
    ];

    $data = _select_info($conn, 'vongthi', [], $conditions);
    
    if (empty($data) || !is_array($data)) {
        return null;
    }

    $vong = $data[0];
    $vong['trangThai'] = lay_trang_thai_vong_thi($vong);

    return $vong;
}

/**
 * Xóa vòng thi (chỉ khi không có dữ liệu liên quan)
 */
function xoa_vong_thi($conn, $id_nguoi_xoa, $id_vong_thi)
{
    $id_nguoi_xoa = (int) $id_nguoi_xoa;
    $id_vong_thi = (int) $id_vong_thi;

    if ($id_nguoi_xoa <= 0 || $id_vong_thi <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $vong_thi = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
    if (!$vong_thi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    $id_sk = (int) $vong_thi['idSK'];

    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_xoa, $id_sk)) {
        return ['status' => false, 'message' => 'Bạn không có quyền xóa vòng thi này'];
    }

    // Kiểm tra dữ liệu liên quan
    $du_lieu_lien_quan = vong_thi_co_du_lieu_lien_quan($conn, $id_vong_thi);

    if (!empty($du_lieu_lien_quan)) {
        return [
            'status' => false,
            'message' => 'Không thể xóa vòng thi vì đã có ' . implode(', ', $du_lieu_lien_quan) . ' liên quan',
            'hasRelatedData' => true,
            'relatedData' => $du_lieu_lien_quan,
        ];
    }

    try {
        $conditions = ['idVongThi' => ['=', $id_vong_thi, '']];
        $result = _delete_info($conn, 'vongthi', $conditions);

        return $result
            ? ['status' => true, 'message' => 'Đã xóa vòng thi thành công']
            : ['status' => false, 'message' => 'Lỗi xóa vòng thi'];

    } catch (Throwable $e) {
        error_log('Lỗi xóa vòng thi: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi xóa vòng thi'];
    }
}

// ==========================================
// EXTENDED FUNCTIONS
// ==========================================

/**
 * Toggle đóng/mở nộp bài của vòng thi (dùng cột dongNopThuCong)
 */
function toggle_dong_nop_vong_thi($conn, int $id_nguoi_thuc_hien, int $id_vong_thi): array
{
    if ($id_nguoi_thuc_hien <= 0 || $id_vong_thi <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    $vong_thi = lay_chi_tiet_vong_thi($conn, $id_vong_thi);
    if (!$vong_thi) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    $id_sk = (int) $vong_thi['idSK'];

    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_thuc_hien, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền thay đổi trạng thái vòng thi'];
    }

    $dong_nop_hien_tai = (int) ($vong_thi['dongNopThuCong'] ?? 0);
    $new_status = ($dong_nop_hien_tai === 1) ? 0 : 1;

    try {
        $result = _update_info(
            $conn,
            'vongthi',
            ['dongNopThuCong'],
            [$new_status],
            ['idVongThi' => ['=', $id_vong_thi, '']]
        );

        if (!$result) {
            return ['status' => false, 'message' => 'Lỗi cập nhật trạng thái'];
        }

        return [
            'status' => true,
            'message' => $new_status === 1 ? 'Đã đóng nộp bài cho vòng thi' : 'Đã mở lại nộp bài cho vòng thi',
            'dongNopThuCong' => $new_status,
        ];

    } catch (Throwable $e) {
        error_log('Lỗi toggle trạng thái vòng thi: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }
}

/**
 * Sắp xếp lại thứ tự các vòng thi
 * 
 * @param array $thu_tu_moi Mảng ['idVongThi' => thuTuMoi, ...]
 */
function sap_xep_thu_tu_vong_thi($conn, int $id_nguoi_thuc_hien, int $id_sk, array $thu_tu_moi): array
{
    if ($id_nguoi_thuc_hien <= 0 || $id_sk <= 0 || empty($thu_tu_moi)) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    // Kiểm tra sự kiện
    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // Kiểm tra quyền
    if (!co_quyen_quan_ly_vong_thi($conn, $id_nguoi_thuc_hien, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền sắp xếp vòng thi'];
    }

    if (!$conn instanceof PDO) {
        return ['status' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'];
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare('UPDATE vongthi SET thuTu = ? WHERE idVongThi = ? AND idSK = ?');

        $updated = 0;
        foreach ($thu_tu_moi as $id_vong => $thu_tu) {
            $id_vong = (int) $id_vong;
            $thu_tu = max(1, (int) $thu_tu);

            if ($id_vong <= 0) {
                continue;
            }

            $stmt->execute([$thu_tu, $id_vong, $id_sk]);
            $updated += $stmt->rowCount();
        }

        $conn->commit();

        return [
            'status' => true,
            'message' => "Đã sắp xếp lại thứ tự {$updated} vòng thi",
            'updatedCount' => $updated,
        ];

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Lỗi sắp xếp vòng thi: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi sắp xếp vòng thi'];
    }
}

/**
 * Lấy thống kê vòng thi (số bài nộp, số đã chấm, etc.)
 */
function lay_thong_ke_vong_thi($conn, int $id_vong_thi): array
{
    if (!$conn instanceof PDO || $id_vong_thi <= 0) {
        return [];
    }

    $stats = [];

    // Đếm số sản phẩm tham gia vòng thi
    $stmt = $conn->prepare('SELECT COUNT(*) FROM sanpham_vongthi WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    $stats['soBaiNop'] = (int) $stmt->fetchColumn();

    // Đếm phân công chấm độc lập
    $stmt = $conn->prepare('SELECT COUNT(DISTINCT idSanPham) FROM phancong_doclap WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    $stats['soPhanCong'] = (int) $stmt->fetchColumn();

    // Đếm phân công chấm theo bộ tiêu chí
    $stmt = $conn->prepare('SELECT COUNT(*) FROM phancongcham WHERE idVongThi = ?');
    $stmt->execute([$id_vong_thi]);
    $stats['soKetQuaCham'] = (int) $stmt->fetchColumn();

    return $stats;
}
