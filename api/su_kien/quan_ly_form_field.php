<?php

/**
 * quan_ly_form_field.php — Service layer cấu hình form tài liệu
 *
 * BTC thiết kế form nộp tài liệu cho từng vòng thi.
 * Mỗi form_field thuộc về 1 sự kiện (idSK) và 1 vòng thi (idVongThi).
 *
 * Logic resolve khi nhóm nộp ở Vòng X:
 *   - Vòng X có field (idVongThi = X)? → dùng form đó
 *   - Không có? → "Vòng này không yêu cầu nộp tài liệu"
 */

require_once __DIR__ . '/../core/base.php';

// ==========================================
// CONSTANTS
// ==========================================

define('FF_TEN_TRUONG_MAX', 200);
define('FF_KIEU_TRUONG_ALLOWED', ['TEXT', 'TEXTAREA', 'URL', 'FILE', 'SELECT', 'CHECKBOX']);

// ==========================================
// PERMISSION
// ==========================================

function co_quyen_cauhinh_tailieu(PDO $conn, int $idTK, int $idSK): bool
{
    if (kiem_tra_quyen_he_thong($conn, $idTK, 'tao_su_kien')) {
        return true;
    }
    return kiem_tra_quyen_su_kien($conn, $idTK, $idSK, 'cauhinh_sukien');
}

// ==========================================
// VALIDATION
// ==========================================

function validate_form_field(array $data): array
{
    $errors = [];

    $tenTruong = trim((string) ($data['ten_truong'] ?? ''));
    if ($tenTruong === '') {
        $errors[] = 'Tên trường không được để trống';
    } elseif (mb_strlen($tenTruong) > FF_TEN_TRUONG_MAX) {
        $errors[] = 'Tên trường tối đa ' . FF_TEN_TRUONG_MAX . ' ký tự';
    }

    $kieuTruong = strtoupper(trim((string) ($data['kieu_truong'] ?? '')));
    if (!in_array($kieuTruong, FF_KIEU_TRUONG_ALLOWED, true)) {
        $errors[] = 'Kiểu trường không hợp lệ. Chấp nhận: ' . implode(', ', FF_KIEU_TRUONG_ALLOWED);
    }

    // Validate cauHinhJson nếu có
    $cauHinhJson = $data['cau_hinh_json'] ?? null;
    if ($cauHinhJson !== null && $cauHinhJson !== '') {
        if (is_string($cauHinhJson)) {
            $decoded = json_decode($cauHinhJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Cấu hình JSON không hợp lệ';
            }
        } elseif (!is_array($cauHinhJson)) {
            $errors[] = 'Cấu hình JSON phải là object hoặc chuỗi JSON';
        }
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}

// ==========================================
// HELPER
// ==========================================

function _normalize_cau_hinh_json($raw): ?string
{
    if ($raw === null || $raw === '') return null;
    if (is_array($raw)) return json_encode($raw, JSON_UNESCAPED_UNICODE);
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) return $raw;
    }
    return null;
}

function _next_thu_tu(PDO $conn, int $idSK, ?int $idVongThi): int
{
    $stmt = $conn->prepare(
        'SELECT COALESCE(MAX(thuTu), 0) FROM form_field
         WHERE idSK = :idSK AND ' . ($idVongThi ? 'idVongThi = :idVT' : 'idVongThi IS NULL')
    );
    $params = [':idSK' => $idSK];
    if ($idVongThi) $params[':idVT'] = $idVongThi;
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() + 1;
}

// ==========================================
// READ
// ==========================================

/**
 * Lấy tất cả field của 1 vòng thi (hoặc field SK mặc định nếu idVongThi = null).
 */
function lay_form_fields(PDO $conn, int $idSK, ?int $idVongThi): array
{
    $sql = 'SELECT * FROM form_field
            WHERE idSK = :idSK AND ' . ($idVongThi ? 'idVongThi = :idVT' : 'idVongThi IS NULL') . '
            ORDER BY thuTu ASC, idField ASC';
    $stmt = $conn->prepare($sql);
    $params = [':idSK' => $idSK];
    if ($idVongThi) $params[':idVT'] = $idVongThi;
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy tổng quan form theo tất cả vòng thi của sự kiện.
 * Trả về mảng: [ ['idVongThi' => X, 'tenVongThi' => '...', 'soField' => N], ... ]
 * Thêm 1 dòng đặc biệt idVongThi = null (form mặc định SK).
 */
function lay_tong_quan_form_sk(PDO $conn, int $idSK): array
{
    // Form mặc định SK (idVongThi IS NULL)
    $stmtDefault = $conn->prepare(
        'SELECT COUNT(*) FROM form_field WHERE idSK = :idSK AND idVongThi IS NULL'
    );
    $stmtDefault->execute([':idSK' => $idSK]);
    $soFieldDefault = (int) $stmtDefault->fetchColumn();

    // Form theo từng vòng thi
    $stmt = $conn->prepare(
        'SELECT vt.idVongThi, vt.tenVongThi, vt.thuTu,
                COUNT(ff.idField) AS soField
         FROM vongthi vt
         LEFT JOIN form_field ff ON ff.idVongThi = vt.idVongThi AND ff.idSK = :idSK
         WHERE vt.idSK = :idSK2
         GROUP BY vt.idVongThi
         ORDER BY vt.thuTu ASC'
    );
    $stmt->execute([':idSK' => $idSK, ':idSK2' => $idSK]);
    $vongThiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'formMacDinh' => $soFieldDefault,
        'vongThi'     => $vongThiList,
    ];
}

/**
 * Lấy chi tiết 1 field.
 */
function lay_chi_tiet_field(PDO $conn, int $idField): ?array
{
    $stmt = $conn->prepare('SELECT * FROM form_field WHERE idField = :id LIMIT 1');
    $stmt->execute([':id' => $idField]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ==========================================
// CREATE
// ==========================================

/**
 * Tạo field mới.
 *
 * @param int      $idTK         Người thực hiện (BTC)
 * @param int      $idSK         Sự kiện
 * @param int|null $idVongThi    Vòng thi (null = field mặc định SK)
 * @param array    $data         Dữ liệu field
 */
function tao_form_field(PDO $conn, int $idTK, int $idSK, ?int $idVongThi, array $data): array
{
    // Quyền
    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền cấu hình tài liệu cho sự kiện này'];
    }

    // Check SK tồn tại
    if (!_is_exist($conn, 'sukien', 'idSK', $idSK)) {
        return ['status' => false, 'message' => 'Sự kiện không tồn tại'];
    }

    // Check vòng thi tồn tại và thuộc SK
    if ($idVongThi !== null) {
        $stmt = $conn->prepare('SELECT idSK FROM vongthi WHERE idVongThi = :id LIMIT 1');
        $stmt->execute([':id' => $idVongThi]);
        $vt = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$vt) {
            return ['status' => false, 'message' => 'Vòng thi không tồn tại'];
        }
        if ((int) $vt['idSK'] !== $idSK) {
            return ['status' => false, 'message' => 'Vòng thi không thuộc sự kiện này'];
        }
    }

    // Validate
    $validation = validate_form_field($data);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    $tenTruong   = trim((string) $data['ten_truong']);
    $kieuTruong  = strtoupper(trim((string) $data['kieu_truong']));
    $batBuoc     = isset($data['bat_buoc']) ? (int)(bool) $data['bat_buoc'] : 1;
    $thuTu       = isset($data['thu_tu']) ? (int) $data['thu_tu'] : _next_thu_tu($conn, $idSK, $idVongThi);
    $cauHinhJson = _normalize_cau_hinh_json($data['cau_hinh_json'] ?? null);

    try {
        $stmt = $conn->prepare(
            'INSERT INTO form_field (idSK, idVongThi, tenTruong, kieuTruong, batBuoc, thuTu, cauHinhJson, isActive)
             VALUES (:idSK, :idVT, :ten, :kieu, :batBuoc, :thuTu, :json, 1)'
        );
        $stmt->execute([
            ':idSK'    => $idSK,
            ':idVT'    => $idVongThi,
            ':ten'     => $tenTruong,
            ':kieu'    => $kieuTruong,
            ':batBuoc' => $batBuoc,
            ':thuTu'   => $thuTu,
            ':json'    => $cauHinhJson,
        ]);

        return [
            'status'  => true,
            'message' => 'Đã thêm trường mới',
            'idField' => (int) $conn->lastInsertId(),
        ];
    } catch (Throwable $e) {
        error_log('tao_form_field: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi tạo trường'];
    }
}

// ==========================================
// UPDATE
// ==========================================

function cap_nhat_form_field(PDO $conn, int $idTK, int $idField, array $data): array
{
    $field = lay_chi_tiet_field($conn, $idField);
    if (!$field) {
        return ['status' => false, 'message' => 'Trường không tồn tại'];
    }

    $idSK = (int) $field['idSK'];

    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền chỉnh sửa trường này'];
    }

    $validation = validate_form_field($data);
    if (!$validation['valid']) {
        return ['status' => false, 'message' => implode('. ', $validation['errors'])];
    }

    $tenTruong   = trim((string) $data['ten_truong']);
    $kieuTruong  = strtoupper(trim((string) $data['kieu_truong']));
    $batBuoc     = isset($data['bat_buoc']) ? (int)(bool) $data['bat_buoc'] : (int) $field['batBuoc'];
    $cauHinhJson = array_key_exists('cau_hinh_json', $data)
        ? _normalize_cau_hinh_json($data['cau_hinh_json'])
        : $field['cauHinhJson'];

    try {
        $stmt = $conn->prepare(
            'UPDATE form_field
             SET tenTruong = :ten, kieuTruong = :kieu, batBuoc = :batBuoc, cauHinhJson = :json
             WHERE idField = :id'
        );
        $stmt->execute([
            ':ten'     => $tenTruong,
            ':kieu'    => $kieuTruong,
            ':batBuoc' => $batBuoc,
            ':json'    => $cauHinhJson,
            ':id'      => $idField,
        ]);

        return ['status' => true, 'message' => 'Đã cập nhật trường'];
    } catch (Throwable $e) {
        error_log('cap_nhat_form_field: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi cập nhật trường'];
    }
}

// ==========================================
// DELETE
// ==========================================

function xoa_form_field(PDO $conn, int $idTK, int $idField): array
{
    $field = lay_chi_tiet_field($conn, $idField);
    if (!$field) {
        return ['status' => false, 'message' => 'Trường không tồn tại'];
    }

    $idSK = (int) $field['idSK'];

    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền xóa trường này'];
    }

    // Kiểm tra đã có nhóm nộp dữ liệu vào field này chưa
    $stmt = $conn->prepare('SELECT COUNT(*) FROM sanpham_field_value WHERE idField = :id');
    $stmt->execute([':id' => $idField]);
    if ((int) $stmt->fetchColumn() > 0) {
        return [
            'status'  => false,
            'message' => 'Không thể xóa trường đã có dữ liệu nộp. Hãy ẩn trường thay vì xóa.',
            'hasData' => true,
        ];
    }

    try {
        $stmt = $conn->prepare('DELETE FROM form_field WHERE idField = :id');
        $stmt->execute([':id' => $idField]);
        return ['status' => true, 'message' => 'Đã xóa trường'];
    } catch (Throwable $e) {
        error_log('xoa_form_field: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi xóa trường'];
    }
}

/**
 * Ẩn/hiện field (toggle isActive).
 */
function toggle_form_field(PDO $conn, int $idTK, int $idField): array
{
    $field = lay_chi_tiet_field($conn, $idField);
    if (!$field) {
        return ['status' => false, 'message' => 'Trường không tồn tại'];
    }

    $idSK = (int) $field['idSK'];

    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền'];
    }

    $newActive = ((int) $field['isActive'] === 1) ? 0 : 1;

    try {
        $stmt = $conn->prepare('UPDATE form_field SET isActive = :v WHERE idField = :id');
        $stmt->execute([':v' => $newActive, ':id' => $idField]);
        return [
            'status'   => true,
            'message'  => $newActive ? 'Đã hiện trường' : 'Đã ẩn trường',
            'isActive' => $newActive,
        ];
    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Lỗi hệ thống'];
    }
}

// ==========================================
// REORDER
// ==========================================

/**
 * Sắp xếp lại thuTu của các field.
 *
 * @param array $order  [['idField' => X, 'thuTu' => N], ...]
 */
function sap_xep_form_field(PDO $conn, int $idTK, int $idSK, array $order): array
{
    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền sắp xếp form này'];
    }

    if (empty($order)) {
        return ['status' => false, 'message' => 'Danh sách sắp xếp trống'];
    }

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare(
            'UPDATE form_field SET thuTu = :thuTu WHERE idField = :id AND idSK = :idSK'
        );
        foreach ($order as $item) {
            $idField = (int) ($item['idField'] ?? 0);
            $thuTu   = (int) ($item['thuTu']   ?? 0);
            if ($idField <= 0) continue;
            $stmt->execute([':thuTu' => $thuTu, ':id' => $idField, ':idSK' => $idSK]);
        }
        $conn->commit();
        return ['status' => true, 'message' => 'Đã sắp xếp lại thứ tự'];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('sap_xep_form_field: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi sắp xếp'];
    }
}

// ==========================================
// COPY FORM
// ==========================================

/**
 * Copy form từ nguồn sang đích.
 *
 * @param int|null $srcVongThi   null = form SK mặc định nguồn
 * @param int|null $dstVongThi   null = form SK mặc định đích
 * @param string   $mode         'ghi_de' | 'them_vao'
 */
function copy_form_field(
    PDO $conn,
    int $idTK,
    int $idSK,
    ?int $srcVongThi,
    ?int $dstVongThi,
    string $mode = 'them_vao'
): array {
    if (!co_quyen_cauhinh_tailieu($conn, $idTK, $idSK)) {
        return ['status' => false, 'message' => 'Bạn không có quyền copy form'];
    }

    if ($srcVongThi === $dstVongThi) {
        return ['status' => false, 'message' => 'Nguồn và đích không được giống nhau'];
    }

    if (!in_array($mode, ['ghi_de', 'them_vao'], true)) {
        return ['status' => false, 'message' => 'Mode không hợp lệ'];
    }

    // Lấy fields nguồn
    $srcFields = lay_form_fields($conn, $idSK, $srcVongThi);
    if (empty($srcFields)) {
        return ['status' => false, 'message' => 'Form nguồn không có trường nào'];
    }

    // Kiểm tra đích đã có field không
    $dstFields = lay_form_fields($conn, $idSK, $dstVongThi);
    $dichDaCoField = !empty($dstFields);

    try {
        $conn->beginTransaction();

        if ($mode === 'ghi_de' && $dichDaCoField) {
            // Xóa toàn bộ field đích (chỉ field chưa có dữ liệu nộp)
            $stmt = $conn->prepare(
                'DELETE FROM form_field
                 WHERE idSK = :idSK
                 AND ' . ($dstVongThi ? 'idVongThi = :idVT' : 'idVongThi IS NULL') . '
                 AND idField NOT IN (SELECT DISTINCT idField FROM sanpham_field_value)'
            );
            $params = [':idSK' => $idSK];
            if ($dstVongThi) $params[':idVT'] = $dstVongThi;
            $stmt->execute($params);
        }

        // Lấy thuTu max hiện tại của đích để append
        $startThuTu = ($mode === 'them_vao')
            ? _next_thu_tu($conn, $idSK, $dstVongThi)
            : 1;

        $stmtInsert = $conn->prepare(
            'INSERT INTO form_field (idSK, idVongThi, tenTruong, kieuTruong, batBuoc, thuTu, cauHinhJson, isActive)
             VALUES (:idSK, :idVT, :ten, :kieu, :batBuoc, :thuTu, :json, :active)'
        );

        foreach ($srcFields as $i => $f) {
            $stmtInsert->execute([
                ':idSK'    => $idSK,
                ':idVT'    => $dstVongThi,
                ':ten'     => $f['tenTruong'],
                ':kieu'    => $f['kieuTruong'],
                ':batBuoc' => $f['batBuoc'],
                ':thuTu'   => $startThuTu + $i,
                ':json'    => $f['cauHinhJson'],
                ':active'  => $f['isActive'],
            ]);
        }

        $conn->commit();

        return [
            'status'  => true,
            'message' => 'Đã copy ' . count($srcFields) . ' trường',
            'soField' => count($srcFields),
        ];
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('copy_form_field: ' . $e->getMessage());
        return ['status' => false, 'message' => 'Lỗi hệ thống khi copy form'];
    }
}
