<?php
/**
 * Service: Quản lý Tiểu ban
 *
 * Tập hợp các hàm nghiệp vụ cho tiểu ban báo cáo.
 * Sử dụng PDO + _insert_info / _update_info / _delete_info từ base.php.
 *
 * Require: api/core/base.php
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

// ============================================================
// READ
// ============================================================

/**
 * Lấy danh sách tiểu ban của một sự kiện, kèm tên vòng thi và bộ tiêu chí.
 */
function tieuban_lay_danh_sach($conn, int $idSK): array
{
    if ($idSK <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT tb.*,
                   v.tenVongThi,
                   btc.tenBoTieuChi
            FROM tieuban tb
            LEFT JOIN vongthi v ON tb.idVongThi = v.idVongThi
            LEFT JOIN botieuchi btc ON tb.idBoTieuChi = btc.idBoTieuChi
            WHERE tb.idSK = :idSK
            ORDER BY tb.idTieuBan ASC
        ");
        $stmt->execute([':idSK' => $idSK]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('tieuban_lay_danh_sach: ' . $e->getMessage());
        return [];
    }
}

/**
 * Lấy map giảng viên theo tiểu ban: [idTieuBan => [{idGV, tenGV, vaiTro}]]
 */
function tieuban_lay_map_giang_vien($conn, int $idSK): array
{
    if ($idSK <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT tbg.idTieuBan, tbg.idGV, tbg.vaiTro, gv.tenGV
            FROM tieuban_giangvien tbg
            JOIN giangvien gv ON tbg.idGV = gv.idGV
            WHERE tbg.idTieuBan IN (
                SELECT idTieuBan FROM tieuban WHERE idSK = :idSK
            )
            ORDER BY gv.tenGV ASC
        ");
        $stmt->execute([':idSK' => $idSK]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['idTieuBan']][] = $r;
        }
        return $map;
    } catch (Throwable $e) {
        error_log('tieuban_lay_map_giang_vien: ' . $e->getMessage());
        return [];
    }
}

/**
 * Lấy map sản phẩm theo tiểu ban: [idTieuBan => [{idSanPham, tenSanPham, manhom, tennhom}]]
 * Đồng thời trả về $assignedIds — mảng idSanPham đã được xếp phòng.
 */
function tieuban_lay_map_san_pham($conn, int $idSK, array &$assignedIds = []): array
{
    if ($idSK <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT tbs.idTieuBan, tbs.idSanPham,
                   sp.tenSanPham,
                   n.manhom,
                   ttn.tennhom
            FROM tieuban_sanpham tbs
            JOIN sanpham sp ON tbs.idSanPham = sp.idSanPham
            LEFT JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            WHERE tbs.idTieuBan IN (
                SELECT idTieuBan FROM tieuban WHERE idSK = :idSK
            )
        ");
        $stmt->execute([':idSK' => $idSK]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        $assignedIds = [];
        foreach ($rows as $r) {
            $map[(int)$r['idTieuBan']][] = $r;
            $assignedIds[] = (int)$r['idSanPham'];
        }
        return $map;
    } catch (Throwable $e) {
        error_log('tieuban_lay_map_san_pham: ' . $e->getMessage());
        return [];
    }
}

/**
 * Lấy danh sách sản phẩm đã duyệt (DA_DUYET) của sự kiện, chưa được xếp tiểu ban.
 */
function tieuban_lay_sp_chua_xep($conn, int $idSK): array
{
    if ($idSK <= 0) return [];

    try {
        $stmt = $conn->prepare("
            SELECT sp.idSanPham, sp.tenSanPham, n.manhom, ttn.tennhom
            FROM sanpham sp
            LEFT JOIN nhom n ON sp.idNhom = n.idnhom
            LEFT JOIN thongtinnhom ttn ON n.idnhom = ttn.idnhom
            WHERE sp.idSK = :idSK
              AND sp.trangThai = 'DA_DUYET'
              AND sp.idSanPham NOT IN (
                  SELECT tbs.idSanPham
                  FROM tieuban_sanpham tbs
                  JOIN tieuban tb ON tbs.idTieuBan = tb.idTieuBan
                  WHERE tb.idSK = :idSK2
              )
            ORDER BY sp.tenSanPham ASC
        ");
        $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('tieuban_lay_sp_chua_xep: ' . $e->getMessage());
        return [];
    }
}

/**
 * Lấy toàn bộ giảng viên trong hệ thống (để chọn thêm vào tiểu ban).
 */
function tieuban_lay_ds_giang_vien($conn): array
{
    try {
        $stmt = $conn->prepare("SELECT idGV, tenGV FROM giangvien ORDER BY tenGV ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('tieuban_lay_ds_giang_vien: ' . $e->getMessage());
        return [];
    }
}

/**
 * Lấy danh sách bộ tiêu chí (để hiện dropdown khi tạo/sửa tiểu ban).
 */
function tieuban_lay_ds_bo_tieu_chi($conn): array
{
    try {
        $stmt = $conn->prepare("SELECT idBoTieuChi, tenBoTieuChi FROM botieuchi ORDER BY idBoTieuChi DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('tieuban_lay_ds_bo_tieu_chi: ' . $e->getMessage());
        return [];
    }
}

/**
 * Thống kê tổng quan tiểu ban của sự kiện.
 */
function tieuban_thong_ke($conn, int $idSK): array
{
    $tieuban = tieuban_lay_danh_sach($conn, $idSK);
    $assignedIds = [];
    tieuban_lay_map_san_pham($conn, $idSK, $assignedIds);

    try {
        $spChuaXep = tieuban_lay_sp_chua_xep($conn, $idSK);
        return [
            'so_tieu_ban'   => count($tieuban),
            'so_bai_xep'    => count($assignedIds),
            'so_bai_cho_xep' => count($spChuaXep),
        ];
    } catch (Throwable $e) {
        return ['so_tieu_ban' => 0, 'so_bai_xep' => 0, 'so_bai_cho_xep' => 0];
    }
}

// ============================================================
// WRITE: Tiểu ban
// ============================================================

/**
 * Tạo tiểu ban mới.
 */
function tieuban_tao($conn, int $idNguoiTao, int $idSK, string $tenTieuBan, int $idVongThi, ?int $idBoTieuChi = null, ?string $ngayBaoCao = null, ?string $diaDiem = null): array
{
    $tenTieuBan = trim($tenTieuBan);
    if ($idSK <= 0 || $idVongThi <= 0 || $tenTieuBan === '') {
        return ['status' => false, 'message' => 'Thiếu thông tin bắt buộc (tên tiểu ban, vòng thi)'];
    }

    if (!_is_exist($conn, 'sukien', 'idSK', $idSK)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    if (!_is_exist($conn, 'vongthi', 'idVongThi', $idVongThi)) {
        return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
    }

    $fields = ['idSK', 'idVongThi', 'tenTieuBan'];
    $values = [$idSK, $idVongThi, $tenTieuBan];

    if ($idBoTieuChi !== null && $idBoTieuChi > 0) {
        $fields[] = 'idBoTieuChi';
        $values[] = $idBoTieuChi;
    }
    if (!empty($ngayBaoCao)) {
        $fields[] = 'ngayBaoCao';
        $values[] = $ngayBaoCao;
    }
    if (!empty($diaDiem)) {
        $fields[] = 'diaDiem';
        $values[] = trim($diaDiem);
    }

    $ok = _insert_info($conn, 'tieuban', $fields, $values);
    if (!$ok) {
        return ['status' => false, 'message' => 'Lỗi khi tạo tiểu ban'];
    }

    return [
        'status'      => true,
        'message'     => 'Tạo tiểu ban thành công',
        'idTieuBan'   => (int) $conn->lastInsertId(),
    ];
}

/**
 * Cập nhật thông tin tiểu ban.
 */
function tieuban_cap_nhat($conn, int $idNguoiTao, int $idTieuBan, string $tenTieuBan, ?int $idBoTieuChi = null, ?string $ngayBaoCao = null, ?string $diaDiem = null): array
{
    $tenTieuBan = trim($tenTieuBan);
    if ($idTieuBan <= 0 || $tenTieuBan === '') {
        return ['status' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }

    if (!_is_exist($conn, 'tieuban', 'idTieuBan', $idTieuBan)) {
        return ['status' => false, 'message' => 'Tiểu ban không tồn tại'];
    }

    $fields = ['tenTieuBan', 'idBoTieuChi', 'ngayBaoCao', 'diaDiem'];
    $values = [
        $tenTieuBan,
        ($idBoTieuChi !== null && $idBoTieuChi > 0) ? $idBoTieuChi : null,
        !empty($ngayBaoCao) ? $ngayBaoCao : null,
        !empty($diaDiem) ? trim($diaDiem) : null,
    ];

    $ok = _update_info($conn, 'tieuban', $fields, $values, [
        'idTieuBan' => ['=', $idTieuBan],
    ]);

    return $ok
        ? ['status' => true, 'message' => 'Cập nhật thành công']
        : ['status' => false, 'message' => 'Lỗi khi cập nhật tiểu ban'];
}

/**
 * Xóa tiểu ban (cascade xóa GV và SP liên quan trước).
 */
function tieuban_xoa($conn, int $idNguoiTao, int $idTieuBan): array
{
    if ($idTieuBan <= 0) {
        return ['status' => false, 'message' => 'ID tiểu ban không hợp lệ'];
    }

    if (!_is_exist($conn, 'tieuban', 'idTieuBan', $idTieuBan)) {
        return ['status' => false, 'message' => 'Tiểu ban không tồn tại'];
    }

    try {
        $conn->beginTransaction();

        _delete_info($conn, 'tieuban_giangvien', ['idTieuBan' => ['=', $idTieuBan]]);
        _delete_info($conn, 'tieuban_sanpham',   ['idTieuBan' => ['=', $idTieuBan]]);
        _delete_info($conn, 'tieuban',            ['idTieuBan' => ['=', $idTieuBan]]);

        $conn->commit();
        return ['status' => true, 'message' => 'Đã xóa tiểu ban'];
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('tieuban_xoa: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi xóa tiểu ban'];
    }
}

// ============================================================
// WRITE: Giảng viên
// ============================================================

/**
 * Thêm giảng viên vào tiểu ban.
 */
function tieuban_them_giang_vien($conn, int $idTieuBan, int $idGV, string $vaiTro = 'Thành viên'): array
{
    if ($idTieuBan <= 0 || $idGV <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }

    if (!_is_exist($conn, 'tieuban', 'idTieuBan', $idTieuBan)) {
        return ['status' => false, 'message' => 'Tiểu ban không tồn tại'];
    }

    try {
        // INSERT IGNORE để tránh duplicate
        $stmt = $conn->prepare("INSERT IGNORE INTO tieuban_giangvien (idTieuBan, idGV, vaiTro) VALUES (?, ?, ?)");
        $stmt->execute([$idTieuBan, $idGV, $vaiTro]);
        return ['status' => true, 'message' => 'Đã thêm giảng viên vào tiểu ban'];
    } catch (Throwable $e) {
        error_log('tieuban_them_giang_vien: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi khi thêm giảng viên'];
    }
}

/**
 * Xóa giảng viên khỏi tiểu ban.
 */
function tieuban_xoa_giang_vien($conn, int $idTieuBan, int $idGV): array
{
    if ($idTieuBan <= 0 || $idGV <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }

    try {
        $stmt = $conn->prepare("DELETE FROM tieuban_giangvien WHERE idTieuBan = ? AND idGV = ?");
        $stmt->execute([$idTieuBan, $idGV]);
        return ['status' => true, 'message' => 'Đã xóa giảng viên khỏi tiểu ban'];
    } catch (Throwable $e) {
        error_log('tieuban_xoa_giang_vien: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi khi xóa giảng viên'];
    }
}

// ============================================================
// WRITE: Sản phẩm
// ============================================================

/**
 * Xếp sản phẩm vào tiểu ban.
 */
function tieuban_them_san_pham($conn, int $idTieuBan, int $idSanPham): array
{
    if ($idTieuBan <= 0 || $idSanPham <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }

    if (!_is_exist($conn, 'tieuban', 'idTieuBan', $idTieuBan)) {
        return ['status' => false, 'message' => 'Tiểu ban không tồn tại'];
    }

    if (!_is_exist($conn, 'sanpham', 'idSanPham', $idSanPham)) {
        return ['status' => false, 'message' => 'Sản phẩm không tồn tại'];
    }

    // Kiểm tra sản phẩm đã được xếp vào tiểu ban khác chưa
    try {
        $stmt = $conn->prepare("SELECT idTieuBan FROM tieuban_sanpham WHERE idSanPham = ? LIMIT 1");
        $stmt->execute([$idSanPham]);
        $existing = $stmt->fetchColumn();
        if ($existing !== false) {
            return ['status' => false, 'message' => 'Sản phẩm đã được xếp vào tiểu ban khác'];
        }

        $stmtIns = $conn->prepare("INSERT IGNORE INTO tieuban_sanpham (idTieuBan, idSanPham) VALUES (?, ?)");
        $stmtIns->execute([$idTieuBan, $idSanPham]);
        return ['status' => true, 'message' => 'Đã xếp sản phẩm vào tiểu ban'];
    } catch (Throwable $e) {
        error_log('tieuban_them_san_pham: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi khi xếp sản phẩm'];
    }
}

/**
 * Rút sản phẩm khỏi tiểu ban.
 */
function tieuban_xoa_san_pham($conn, int $idTieuBan, int $idSanPham): array
{
    if ($idTieuBan <= 0 || $idSanPham <= 0) {
        return ['status' => false, 'message' => 'Dữ liệu không hợp lệ'];
    }

    try {
        $stmt = $conn->prepare("DELETE FROM tieuban_sanpham WHERE idTieuBan = ? AND idSanPham = ?");
        $stmt->execute([$idTieuBan, $idSanPham]);
        return ['status' => true, 'message' => 'Đã rút sản phẩm khỏi tiểu ban'];
    } catch (Throwable $e) {
        error_log('tieuban_xoa_san_pham: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi khi rút sản phẩm'];
    }
}