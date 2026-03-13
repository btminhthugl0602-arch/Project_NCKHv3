<?php

require_once __DIR__ . '/../core/base.php';

// ─── CONSTANTS ────────────────────────────────────────────────────────────────

define('LOAI_HOAT_DONG_ENUM', ['HOAT_DONG', 'DIEM_DANH', 'NGHI', 'KHAC']);
define('PHUONG_THUC_DIEM_DANH', ['QR', 'GPS', 'THU_CONG', 'NFC']);
define('TRANG_THAI_PHIEN', ['CHUAN_BI', 'DANG_MO', 'DA_DONG']);

// SECRET_KEY dùng để ký HMAC token QR
// Ưu tiên: configs/config.php → env → fallback dev
$_cfg = __DIR__ . '/../../configs/config.php';
if (file_exists($_cfg)) require_once $_cfg;
unset($_cfg);
if (!defined('NCKH_SECRET_KEY')) {
    define('NCKH_SECRET_KEY', getenv('NCKH_SECRET_KEY') ?: 'nckh_dev_secret_2024');
}

// ─── QUYỀN ────────────────────────────────────────────────────────────────────

function co_quyen_to_chuc_su_kien($conn, int $id_tk, int $id_sk): bool
{
    if (kiem_tra_quyen_he_thong($conn, $id_tk, 'tao_su_kien')) {
        return true;
    }
    return kiem_tra_quyen_su_kien($conn, $id_tk, $id_sk, 'cauhinh_sukien');
}

function co_trong_su_kien($conn, int $id_tk, int $id_sk): bool
{
    if ($id_tk <= 0 || $id_sk <= 0) return false;
    $rows = _select_info($conn, 'taikhoan_vaitro_sukien', ['id'], [
        'WHERE' => ['idTK', '=', $id_tk, 'AND', 'idSK', '=', $id_sk, 'AND', 'isActive', '=', 1, ''],
        'LIMIT' => [1],
    ]);
    return !empty($rows);
}

// ─── TOKEN QR (HMAC — không lưu DB) ──────────────────────────────────────────

function tao_token_qr(int $id_phien_dd, string $thoi_gian_mo): string
{
    return hash_hmac('sha256', $id_phien_dd . '|' . $thoi_gian_mo, NCKH_SECRET_KEY);
}

function xac_thuc_token_qr(string $token, int $id_phien_dd, string $thoi_gian_mo): bool
{
    $expected = tao_token_qr($id_phien_dd, $thoi_gian_mo);
    return hash_equals($expected, $token);
}

// ─── LAZY CHECK — tự động mở/đóng phiên theo thời gian ───────────────────────

/**
 * Gọi mỗi khi cần lấy trạng thái phiên.
 * Nếu đến giờ bắt đầu → tự mở (CHUAN_BI → DANG_MO).
 * Nếu đến giờ kết thúc → tự đóng (DANG_MO → DA_DONG).
 * Trả về phiên đã được cập nhật.
 */
function kiem_tra_tu_dong_phien($conn, array $phien): array
{
    if (empty($phien)) return $phien;

    $now = date('Y-m-d H:i:s');
    $id_phien = (int) $phien['idPhienDD'];
    $trang_thai = $phien['trangThai'];

    // CHUAN_BI + đến giờ mở → tự mở
    if ($trang_thai === 'CHUAN_BI' && $phien['thoiGianMo'] <= $now) {
        $ok = _update_info($conn, 'phien_diemdanh',
            ['trangThai'],
            ['DANG_MO'],
            ['idPhienDD' => ['=', $id_phien]]
        );
        if ($ok) $phien['trangThai'] = 'DANG_MO';
    }

    // DANG_MO + có giờ đóng + đến giờ đóng → tự đóng
    if ($phien['trangThai'] === 'DANG_MO'
        && !empty($phien['thoiGianDong'])
        && $phien['thoiGianDong'] <= $now
    ) {
        $ok = _update_info($conn, 'phien_diemdanh',
            ['trangThai'],
            ['DA_DONG'],
            ['idPhienDD' => ['=', $id_phien]]
        );
        if ($ok) $phien['trangThai'] = 'DA_DONG';
    }

    return $phien;
}

// ─── LAY PHIEN HIEN TAI ───────────────────────────────────────────────────────

/**
 * Lấy phiên đang hoạt động (DANG_MO) hoặc gần nhất (DA_DONG) của 1 lịch trình.
 * Luôn chạy lazy check trước khi trả về.
 * Kèm thống kê chinh_thuc / khan_gia.
 */
function lay_phien_hien_tai($conn, int $id_lich_trinh, int $id_sk): ?array
{
    if ($id_lich_trinh <= 0) return null;

    // Ưu tiên DANG_MO, nếu không có thì lấy CHUAN_BI, rồi DA_DONG gần nhất
    try {
        $stmt = $conn->prepare("
            SELECT * FROM phien_diemdanh
            WHERE idLichTrinh = ?
            ORDER BY
                CASE trangThai
                    WHEN 'DANG_MO'  THEN 1
                    WHEN 'CHUAN_BI' THEN 2
                    WHEN 'DA_DONG'  THEN 3
                END,
                idPhienDD DESC
            LIMIT 1
        ");
        $stmt->execute([$id_lich_trinh]);
        $phien = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('lay_phien_hien_tai error: ' . $e->getMessage());
        return null;
    }

    if (!$phien) return null;

    // Lazy check thời gian
    $phien = kiem_tra_tu_dong_phien($conn, $phien);

    // Thêm thống kê điểm danh
    try {
        $stmt2 = $conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN tvs.id IS NOT NULL THEN 1 ELSE 0 END) AS chinh_thuc,
                SUM(CASE WHEN tvs.id IS NULL     THEN 1 ELSE 0 END) AS khan_gia
            FROM diemdanh dd
            LEFT JOIN taikhoan_vaitro_sukien tvs
                   ON tvs.idTK = dd.idTK
                  AND tvs.idSK = ?
                  AND tvs.isActive = 1
            WHERE dd.idPhienDD = ?
        ");
        $stmt2->execute([$id_sk, (int) $phien['idPhienDD']]);
        $stats = $stmt2->fetch();
        $phien['stats'] = [
            'total'       => (int) ($stats['total']      ?? 0),
            'chinh_thuc'  => (int) ($stats['chinh_thuc'] ?? 0),
            'khan_gia'    => (int) ($stats['khan_gia']   ?? 0),
        ];
    } catch (Throwable $e) {
        $phien['stats'] = ['total' => 0, 'chinh_thuc' => 0, 'khan_gia' => 0];
    }

    // Thêm tokenQR nếu DANG_MO
    if ($phien['trangThai'] === 'DANG_MO') {
        $phien['tokenQR'] = tao_token_qr((int) $phien['idPhienDD'], $phien['thoiGianMo']);
    }

    return $phien;
}

// ─── LICH TRINH CRUD ─────────────────────────────────────────────────────────

function tao_lich_trinh(
    $conn,
    $id_nguoi_tao,
    $id_sk,
    $ten_hoat_dong,
    $loai_hoat_dong,
    $thoi_gian_bat_dau,
    $thoi_gian_ket_thuc = null,
    $dia_diem           = null,
    $vi_tri_lat         = null,
    $vi_tri_lng         = null,
    $id_vong_thi        = null,
    $id_tieu_ban        = null
) {
    $id_nguoi_tao     = (int) $id_nguoi_tao;
    $id_sk            = (int) $id_sk;
    $id_vong_thi      = ($id_vong_thi  !== null && (int) $id_vong_thi  > 0) ? (int) $id_vong_thi  : null;
    $id_tieu_ban      = ($id_tieu_ban  !== null && (int) $id_tieu_ban  > 0) ? (int) $id_tieu_ban  : null;
    $ten_hoat_dong    = trim((string) $ten_hoat_dong);
    $loai_hoat_dong   = strtoupper(trim((string) $loai_hoat_dong));
    $vi_tri_lat       = ($vi_tri_lat !== null && $vi_tri_lat !== '') ? (float) $vi_tri_lat : null;
    $vi_tri_lng       = ($vi_tri_lng !== null && $vi_tri_lng !== '') ? (float) $vi_tri_lng : null;
    $thoi_gian_ket_thuc = ($thoi_gian_ket_thuc !== null && $thoi_gian_ket_thuc !== '') ? $thoi_gian_ket_thuc : null;

    // ── Validation cơ bản
    if ($id_nguoi_tao <= 0 || $id_sk <= 0 || $ten_hoat_dong === '' || empty($thoi_gian_bat_dau)) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!in_array($loai_hoat_dong, LOAI_HOAT_DONG_ENUM, true)) {
        return ['status' => false, 'message' => 'Loại hoạt động không hợp lệ'];
    }

    // DIEM_DANH bắt buộc có thời gian kết thúc
    if ($loai_hoat_dong === 'DIEM_DANH' && empty($thoi_gian_ket_thuc)) {
        return ['status' => false, 'message' => 'Hoạt động điểm danh phải có thời gian kết thúc'];
    }

    // ── Quyền
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_tao, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // ── Validate FK
    if ($id_vong_thi !== null) {
        $vt = truy_van_mot_ban_ghi($conn, 'vongthi', 'idVongThi', $id_vong_thi);
        if (!$vt || (int) $vt['idSK'] !== $id_sk) {
            return ['status' => false, 'message' => 'Vòng thi không thuộc sự kiện'];
        }
    }

    if ($id_tieu_ban !== null) {
        $tb = truy_van_mot_ban_ghi($conn, 'tieuban', 'idTieuBan', $id_tieu_ban);
        if (!$tb || (int) $tb['idSK'] !== $id_sk) {
            return ['status' => false, 'message' => 'Tiểu ban không thuộc sự kiện'];
        }
    }

    // ── Tính thuTu tự động MAX + 1
    try {
        $stmt = $conn->prepare("SELECT COALESCE(MAX(thuTu), 0) + 1 AS next_thu_tu FROM lichtrinh WHERE idSK = ?");
        $stmt->execute([$id_sk]);
        $thu_tu = (int) ($stmt->fetchColumn() ?: 1);
    } catch (Throwable $e) {
        $thu_tu = 1;
    }

    $result = _insert_info(
        $conn,
        'lichtrinh',
        ['idSK', 'idVongThi', 'idTieuBan', 'tenHoatDong', 'loaiHoatDong', 'thuTu',
         'thoiGianBatDau', 'thoiGianKetThuc', 'diaDiem', 'viTriLat', 'viTriLng'],
        [$id_sk, $id_vong_thi, $id_tieu_ban, $ten_hoat_dong, $loai_hoat_dong, $thu_tu,
         $thoi_gian_bat_dau, $thoi_gian_ket_thuc, $dia_diem, $vi_tri_lat, $vi_tri_lng]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã thêm lịch trình', 'idLichTrinh' => (int) $conn->lastInsertId()]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ─────────────────────────────────────────────────────────────────────────────

function cap_nhat_lich_trinh(
    $conn,
    $id_nguoi_sua,
    $id_lich_trinh,
    $ten_hoat_dong,
    $loai_hoat_dong,
    $thoi_gian_bat_dau,
    $thoi_gian_ket_thuc = null,
    $dia_diem           = null,
    $vi_tri_lat         = null,
    $vi_tri_lng         = null,
    $id_vong_thi        = null,
    $id_tieu_ban        = null
) {
    $id_nguoi_sua   = (int) $id_nguoi_sua;
    $id_lich_trinh  = (int) $id_lich_trinh;
    $ten_hoat_dong  = trim((string) $ten_hoat_dong);
    $loai_hoat_dong = strtoupper(trim((string) $loai_hoat_dong));
    $vi_tri_lat     = ($vi_tri_lat !== null && $vi_tri_lat !== '') ? (float) $vi_tri_lat : null;
    $vi_tri_lng     = ($vi_tri_lng !== null && $vi_tri_lng !== '') ? (float) $vi_tri_lng : null;
    $thoi_gian_ket_thuc = ($thoi_gian_ket_thuc !== null && $thoi_gian_ket_thuc !== '') ? $thoi_gian_ket_thuc : null;
    $id_vong_thi    = ($id_vong_thi !== null && (int) $id_vong_thi > 0) ? (int) $id_vong_thi : null;
    $id_tieu_ban    = ($id_tieu_ban !== null && (int) $id_tieu_ban > 0) ? (int) $id_tieu_ban : null;

    if ($id_lich_trinh <= 0 || $ten_hoat_dong === '' || empty($thoi_gian_bat_dau)) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }
    if (!in_array($loai_hoat_dong, LOAI_HOAT_DONG_ENUM, true)) {
        return ['status' => false, 'message' => 'Loại hoạt động không hợp lệ'];
    }
    if ($loai_hoat_dong === 'DIEM_DANH' && empty($thoi_gian_ket_thuc)) {
        return ['status' => false, 'message' => 'Hoạt động điểm danh phải có thời gian kết thúc'];
    }

    $lt = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', $id_lich_trinh);
    if (!$lt) return ['status' => false, 'message' => 'Lịch trình không tồn tại'];

    $id_sk = (int) $lt['idSK'];
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_sua, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    // ── Chặn đổi loaiHoatDong nếu đã có phiên active/có điểm danh
    if ($lt['loaiHoatDong'] !== $loai_hoat_dong) {
        $check = _kiem_tra_co_phien_active($conn, $id_lich_trinh);
        if ($check['blocked']) {
            return ['status' => false, 'message' => $check['message']];
        }
    }

    $result = _update_info(
        $conn,
        'lichtrinh',
        ['tenHoatDong', 'loaiHoatDong', 'thoiGianBatDau', 'thoiGianKetThuc',
         'diaDiem', 'viTriLat', 'viTriLng', 'idVongThi', 'idTieuBan'],
        [$ten_hoat_dong, $loai_hoat_dong, $thoi_gian_bat_dau, $thoi_gian_ket_thuc,
         $dia_diem, $vi_tri_lat, $vi_tri_lng, $id_vong_thi, $id_tieu_ban],
        ['idLichTrinh' => ['=', $id_lich_trinh]]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã cập nhật lịch trình']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ─────────────────────────────────────────────────────────────────────────────

function xoa_lich_trinh($conn, $id_nguoi_xoa, $id_lich_trinh)
{
    $id_nguoi_xoa  = (int) $id_nguoi_xoa;
    $id_lich_trinh = (int) $id_lich_trinh;

    $lt = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', $id_lich_trinh);
    if (!$lt) return ['status' => false, 'message' => 'Lịch trình không tồn tại'];

    $id_sk = (int) $lt['idSK'];
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_xoa, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    // ── Kiểm tra phiên
    try {
        $stmt = $conn->prepare("
            SELECT pd.idPhienDD, pd.trangThai,
                   COUNT(dd.idDiemDanh) AS so_diem_danh
            FROM phien_diemdanh pd
            LEFT JOIN diemdanh dd ON dd.idPhienDD = pd.idPhienDD
            WHERE pd.idLichTrinh = ?
            GROUP BY pd.idPhienDD, pd.trangThai
        ");
        $stmt->execute([$id_lich_trinh]);
        $phiens = $stmt->fetchAll();
    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    foreach ($phiens as $p) {
        if ($p['trangThai'] === 'DANG_MO') {
            return ['status' => false, 'message' => 'Đang có phiên điểm danh mở. Đóng phiên trước rồi mới xóa.'];
        }
        if ((int) $p['so_diem_danh'] > 0) {
            return ['status' => false, 'message' => 'Đã có ' . $p['so_diem_danh'] . ' lượt điểm danh. Không thể xóa để giữ audit trail.'];
        }
    }

    $result = _delete_info($conn, 'lichtrinh', ['idLichTrinh' => ['=', $id_lich_trinh]]);
    return $result
        ? ['status' => true, 'message' => 'Đã xóa lịch trình']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ─────────────────────────────────────────────────────────────────────────────

function lay_danh_sach_lich_trinh($conn, int $id_sk, int $id_tk_nguoi_dung = 0): array
{
    if ($id_sk <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT lt.*,
                   vt.tenVong AS tenVongThi,
                   tb.tenTieuBan
            FROM lichtrinh lt
            LEFT JOIN vongthi vt ON vt.idVongThi = lt.idVongThi
            LEFT JOIN tieuban tb ON tb.idTieuBan = lt.idTieuBan
            WHERE lt.idSK = ?
            ORDER BY lt.thuTu ASC, lt.thoiGianBatDau ASC
        ");
        $stmt->execute([$id_sk]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('lay_danh_sach_lich_trinh error: ' . $e->getMessage());
        return [];
    }

    $result = [];
    foreach ($rows as $row) {
        $item = $row;

        // Với mục DIEM_DANH, nhúng thông tin phiên
        if ($row['loaiHoatDong'] === 'DIEM_DANH') {
            $item['phien'] = lay_phien_hien_tai($conn, (int) $row['idLichTrinh'], $id_sk);
        } else {
            $item['phien'] = null;
        }

        // Nếu người dùng đã đăng nhập, check xem họ đã điểm danh chưa
        if ($id_tk_nguoi_dung > 0 && $item['phien'] !== null) {
            $id_phien = (int) $item['phien']['idPhienDD'];
            try {
                $s = $conn->prepare("SELECT thoiGianDiemDanh FROM diemdanh WHERE idPhienDD = ? AND idTK = ? LIMIT 1");
                $s->execute([$id_phien, $id_tk_nguoi_dung]);
                $dd = $s->fetch();
                $item['phien']['da_diem_danh'] = $dd ? $dd['thoiGianDiemDanh'] : null;
            } catch (Throwable $e) {
                $item['phien']['da_diem_danh'] = null;
            }
        }

        $result[] = $item;
    }

    return $result;
}

// ─────────────────────────────────────────────────────────────────────────────

function sap_xep_lich_trinh($conn, int $id_nguoi_thuc_hien, int $id_sk, array $items): array
{
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_thuc_hien, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("UPDATE lichtrinh SET thuTu = ? WHERE idLichTrinh = ? AND idSK = ?");
        $updated = 0;
        foreach ($items as $item) {
            $id = (int) ($item['id_lich_trinh'] ?? 0);
            $thu_tu = (int) ($item['thu_tu'] ?? 0);
            if ($id > 0) {
                $stmt->execute([$thu_tu, $id, $id_sk]);
                $updated += $stmt->rowCount();
            }
        }
        $conn->commit();
        return ['status' => true, 'message' => 'Đã cập nhật thứ tự', 'updated' => $updated];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('sap_xep_lich_trinh error: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }
}

// ─── PHIEN DIEM DANH ─────────────────────────────────────────────────────────

function tao_phien_diem_danh(
    $conn,
    int $id_nguoi_tao,
    int $id_lich_trinh,
    $vi_tri_lat = null,
    $vi_tri_lng = null,
    int $ban_kinh = 150
): array {
    $lt = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', $id_lich_trinh);
    if (!$lt) return ['status' => false, 'message' => 'Lịch trình không tồn tại'];
    if ($lt['loaiHoatDong'] !== 'DIEM_DANH') {
        return ['status' => false, 'message' => 'Lịch trình này không phải loại Điểm danh'];
    }

    $id_sk = (int) $lt['idSK'];
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_tao, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    // Không cho tạo phiên mới nếu đang có phiên CHUAN_BI hoặc DANG_MO
    try {
        $stmt = $conn->prepare("
            SELECT idPhienDD FROM phien_diemdanh
            WHERE idLichTrinh = ? AND trangThai IN ('CHUAN_BI','DANG_MO')
            LIMIT 1
        ");
        $stmt->execute([$id_lich_trinh]);
        if ($stmt->fetch()) {
            return ['status' => false, 'message' => 'Đã có phiên đang chuẩn bị hoặc đang mở'];
        }
    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    // Dùng tọa độ từ lịch trình nếu không truyền vào
    $lat = ($vi_tri_lat !== null && $vi_tri_lat !== '') ? (float) $vi_tri_lat : ($lt['viTriLat'] ?? null);
    $lng = ($vi_tri_lng !== null && $vi_tri_lng !== '') ? (float) $vi_tri_lng : ($lt['viTriLng'] ?? null);
    $ban_kinh = max(10, (int) $ban_kinh);

    // thoiGianMo NOT NULL — set từ thoiGianBatDau của lịch trình
    $thoi_gian_mo = $lt['thoiGianBatDau'];
    // thoiGianDong lấy từ thoiGianKetThuc để lazy auto-close
    $thoi_gian_dong = $lt['thoiGianKetThuc'] ?? null;

    $result = _insert_info(
        $conn,
        'phien_diemdanh',
        ['idLichTrinh', 'viTriLat', 'viTriLng', 'banKinh', 'thoiGianMo', 'thoiGianDong', 'trangThai'],
        [$id_lich_trinh, $lat, $lng, $ban_kinh, $thoi_gian_mo, $thoi_gian_dong, 'CHUAN_BI']
    );

    if (!$result) return ['status' => false, 'message' => 'Lỗi hệ thống'];

    $id_phien = (int) $conn->lastInsertId();
    return [
        'status'     => true,
        'message'    => 'Đã tạo phiên điểm danh',
        'idPhienDD'  => $id_phien,
        'trangThai'  => 'CHUAN_BI',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────

function mo_phien_diem_danh($conn, int $id_nguoi_thuc_hien, int $id_phien_dd): array
{
    $phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $id_phien_dd);
    if (!$phien) return ['status' => false, 'message' => 'Phiên không tồn tại'];

    $lt   = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', (int) $phien['idLichTrinh']);
    $id_sk = $lt ? (int) $lt['idSK'] : 0;

    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_thuc_hien, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    // Cho phép CHUAN_BI → DANG_MO  và  DA_DONG → DANG_MO (mở lại)
    if (!in_array($phien['trangThai'], ['CHUAN_BI', 'DA_DONG'], true)) {
        return ['status' => false, 'message' => 'Phiên đang mở, không cần mở lại'];
    }

    $now = date('Y-m-d H:i:s');
    $result = _update_info($conn, 'phien_diemdanh',
        ['trangThai', 'thoiGianMo'],
        ['DANG_MO', $now],
        ['idPhienDD' => ['=', $id_phien_dd]]
    );

    if (!$result) return ['status' => false, 'message' => 'Lỗi hệ thống'];

    $token = tao_token_qr($id_phien_dd, $now);
    return [
        'status'    => true,
        'message'   => 'Đã mở phiên điểm danh',
        'idPhienDD' => $id_phien_dd,
        'trangThai' => 'DANG_MO',
        'tokenQR'   => $token,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────

function dong_phien_diem_danh($conn, int $id_nguoi_thuc_hien, int $id_phien_dd): array
{
    $phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $id_phien_dd);
    if (!$phien) return ['status' => false, 'message' => 'Phiên không tồn tại'];
    if ($phien['trangThai'] !== 'DANG_MO') {
        return ['status' => false, 'message' => 'Phiên không đang mở'];
    }

    $lt   = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', (int) $phien['idLichTrinh']);
    $id_sk = $lt ? (int) $lt['idSK'] : 0;
    if (!co_quyen_to_chuc_su_kien($conn, $id_nguoi_thuc_hien, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    $now    = date('Y-m-d H:i:s');
    $result = _update_info($conn, 'phien_diemdanh',
        ['trangThai', 'thoiGianDong'],
        ['DA_DONG', $now],
        ['idPhienDD' => ['=', $id_phien_dd]]
    );

    return $result
        ? ['status' => true, 'message' => 'Đã đóng phiên điểm danh']
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ─── GHI NHAN DIEM DANH ──────────────────────────────────────────────────────

/**
 * Ghi nhận điểm danh — dùng cho cả SV tự điểm (QR/GPS), BTC điểm hộ (THU_CONG), khán giả.
 *
 * $id_phien_dd: required — phiên phải DANG_MO
 * $id_tk_sv:    required — TK người được điểm danh
 * $id_nhom:     optional — NULL nếu GV/BTC/khán giả không thuộc nhóm
 * $hien_dien:   1 = có mặt (default), 0 = vắng (BTC điểm hộ)
 * $phuong_thuc: QR|GPS|THU_CONG|NFC
 * $vi_tri_lat/lng: bắt buộc nếu GPS
 */
function ghi_nhan_diem_danh(
    $conn,
    int $id_nguoi_check,
    int $id_tk_sv,
    int $id_phien_dd,
    string $phuong_thuc = 'THU_CONG',
    int $hien_dien      = 1,
    string $ghi_chu     = '',
    ?int $id_nhom       = null,
    $vi_tri_lat         = null,
    $vi_tri_lng         = null
): array {
    if ($id_tk_sv <= 0 || $id_phien_dd <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!in_array($phuong_thuc, PHUONG_THUC_DIEM_DANH, true)) {
        $phuong_thuc = 'THU_CONG';
    }

    // ── Load phiên
    $phien = truy_van_mot_ban_ghi($conn, 'phien_diemdanh', 'idPhienDD', $id_phien_dd);
    if (!$phien) return ['status' => false, 'message' => 'Phiên điểm danh không tồn tại'];

    // Lazy check auto open/close
    $phien = kiem_tra_tu_dong_phien($conn, $phien);

    if ($phien['trangThai'] !== 'DANG_MO') {
        return ['status' => false, 'message' => 'Phiên điểm danh chưa mở hoặc đã đóng'];
    }

    // ── Check người điểm danh tồn tại
    if (!_is_exist($conn, 'taikhoan', 'idTK', $id_tk_sv)) {
        return ['status' => false, 'message' => 'Tài khoản không tồn tại'];
    }

    // ── Check quyền: BTC điểm hộ cần cauhinh_sukien, SV tự điểm chỉ cần login
    $lt    = truy_van_mot_ban_ghi($conn, 'lichtrinh', 'idLichTrinh', (int) $phien['idLichTrinh']);
    $id_sk = $lt ? (int) $lt['idSK'] : 0;

    $la_btc = $id_sk > 0 && co_quyen_to_chuc_su_kien($conn, $id_nguoi_check, $id_sk);
    $la_chinh_minh = ($id_nguoi_check === $id_tk_sv);

    if (!$la_btc && !$la_chinh_minh) {
        return ['status' => false, 'message' => 'Không có quyền điểm danh'];
    }

    // SV tự điểm luôn hienDien = 1
    if ($la_chinh_minh && !$la_btc) {
        $hien_dien = 1;
    }

    // ── Validate GPS
    $lat_float = ($vi_tri_lat !== null && $vi_tri_lat !== '') ? (float) $vi_tri_lat : null;
    $lng_float = ($vi_tri_lng !== null && $vi_tri_lng !== '') ? (float) $vi_tri_lng : null;

    if ($phuong_thuc === 'GPS') {
        if ($lat_float === null || $lng_float === null) {
            return ['status' => false, 'message' => 'Cần cung cấp tọa độ GPS để điểm danh'];
        }
        if (!empty($phien['viTriLat']) && !empty($phien['viTriLng'])) {
            $khoang_cach = _tinh_khoang_cach_haversine(
                (float) $phien['viTriLat'], (float) $phien['viTriLng'],
                $lat_float, $lng_float
            );
            $ban_kinh = (int) ($phien['banKinh'] ?? 150);
            if ($khoang_cach > $ban_kinh) {
                return [
                    'status'  => false,
                    'message' => "Ngoài vùng điểm danh (khoảng cách: {$khoang_cach}m, bán kính: {$ban_kinh}m)",
                ];
            }
        }
    }

    // ── Check duplicate
    try {
        $s = $conn->prepare("SELECT idDiemDanh FROM diemdanh WHERE idPhienDD = ? AND idTK = ? LIMIT 1");
        $s->execute([$id_phien_dd, $id_tk_sv]);
        if ($s->fetch()) {
            return ['status' => false, 'message' => 'Bạn đã điểm danh phiên này rồi'];
        }
    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }

    // ── Lấy IP
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip && strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    $result = _insert_info(
        $conn,
        'diemdanh',
        ['idNhom', 'idTK', 'thoiGianDiemDanh', 'hienDien', 'ghiChu',
         'idPhienDD', 'phuongThuc', 'viTriLat', 'viTriLng', 'ipDiemDanh'],
        [$id_nhom, $id_tk_sv, date('Y-m-d H:i:s'), $hien_dien ? 1 : 0, $ghi_chu,
         $id_phien_dd, $phuong_thuc, $lat_float, $lng_float, $ip]
    );

    return $result
        ? ['status' => true, 'message' => 'Điểm danh thành công', 'idDiemDanh' => (int) $conn->lastInsertId()]
        : ['status' => false, 'message' => 'Lỗi hệ thống'];
}

// ─── LAY DANH SACH DIEM DANH ─────────────────────────────────────────────────

function lay_danh_sach_diem_danh($conn, int $id_phien_dd, int $id_sk): array
{
    if ($id_phien_dd <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT
                dd.*,
                tk.tenTK,
                sv.tenSV,
                gv.tenGV,
                n.tenNhom,
                CASE WHEN tvs.id IS NOT NULL THEN 1 ELSE 0 END AS la_chinh_thuc
            FROM diemdanh dd
            LEFT JOIN taikhoan tk         ON tk.idTK = dd.idTK
            LEFT JOIN sinhvien sv         ON sv.idTK = dd.idTK
            LEFT JOIN giangvien gv        ON gv.idTK = dd.idTK
            LEFT JOIN nhom n              ON n.idNhom = dd.idNhom
            LEFT JOIN taikhoan_vaitro_sukien tvs
                   ON tvs.idTK = dd.idTK
                  AND tvs.idSK = ?
                  AND tvs.isActive = 1
            WHERE dd.idPhienDD = ?
            ORDER BY dd.thoiGianDiemDanh ASC
        ");
        $stmt->execute([$id_sk, $id_phien_dd]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('lay_danh_sach_diem_danh error: ' . $e->getMessage());
        return [];
    }
}

// ─── HELPER: Haversine distance (meters) ─────────────────────────────────────

function _tinh_khoang_cach_haversine(
    float $lat1, float $lng1,
    float $lat2, float $lng2
): int {
    $R = 6371000; // bán kính Trái Đất (m)
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $d_phi = deg2rad($lat2 - $lat1);
    $d_lam = deg2rad($lng2 - $lng1);

    $a = sin($d_phi / 2) ** 2
       + cos($phi1) * cos($phi2) * sin($d_lam / 2) ** 2;

    return (int) round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)));
}

// ─── HELPER: check có phiên active không ─────────────────────────────────────

function _kiem_tra_co_phien_active($conn, int $id_lich_trinh): array
{
    try {
        $stmt = $conn->prepare("
            SELECT pd.trangThai, COUNT(dd.idDiemDanh) AS so_dd
            FROM phien_diemdanh pd
            LEFT JOIN diemdanh dd ON dd.idPhienDD = pd.idPhienDD
            WHERE pd.idLichTrinh = ?
            GROUP BY pd.idPhienDD, pd.trangThai
        ");
        $stmt->execute([$id_lich_trinh]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return ['blocked' => false];
    }

    foreach ($rows as $r) {
        if ($r['trangThai'] === 'DANG_MO') {
            return ['blocked' => true, 'message' => 'Đang có phiên điểm danh mở, không thể đổi loại hoạt động'];
        }
        if ((int) $r['so_dd'] > 0) {
            return ['blocked' => true, 'message' => 'Đã có ' . $r['so_dd'] . ' lượt điểm danh, không thể đổi loại hoạt động'];
        }
    }

    return ['blocked' => false];
}

// ─── GIỮ LẠI HÀM CŨ (BTC) ──────────────────────────────────────────────────

function them_thanh_vien_btc($conn, $id_admin, $id_sk, $id_tk_can_bo, $chuc_vu = 'BTC')
{
    $id_admin     = (int) $id_admin;
    $id_sk        = (int) $id_sk;
    $id_tk_can_bo = (int) $id_tk_can_bo;

    if ($id_admin <= 0 || $id_sk <= 0 || $id_tk_can_bo <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ'];
    }

    if (!co_quyen_to_chuc_su_kien($conn, $id_admin, $id_sk)) {
        return ['status' => false, 'message' => 'Không có quyền'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $id_sk) || !_is_exist($conn, 'taikhoan', 'idTK', $id_tk_can_bo)) {
        return ['status' => false, 'message' => 'Sự kiện hoặc tài khoản không tồn tại'];
    }

    $exists = _select_info($conn, 'taikhoan_vaitro_sukien', ['id'], [
        'WHERE' => ['idTK', '=', $id_tk_can_bo, 'AND', 'idSK', '=', $id_sk, 'AND', 'idVaiTro', '=', 1, 'AND', 'isActive', '=', 1, ''],
        'LIMIT' => [1],
    ]);

    if (!empty($exists)) {
        return ['status' => true, 'message' => 'Tài khoản đã là thành viên BTC'];
    }

    $result = _insert_info(
        $conn,
        'taikhoan_vaitro_sukien',
        ['idTK', 'idSK', 'idVaiTro', 'nguonTao', 'idNguoiCap', 'isActive'],
        [$id_tk_can_bo, $id_sk, 1, 'BTC_THEM', $id_admin, 1]
    );

    if (!$result) {
        return ['status' => false, 'message' => 'Không thêm được thành viên BTC'];
    }

    return ['status' => true, 'message' => 'Đã thêm cán bộ vào BTC', 'vaiTro' => $chuc_vu];
}
