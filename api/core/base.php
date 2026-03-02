<?php
if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}
require_once __DIR__ . '/db_connect.php';

function _safe_identifier(string $identifier): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
        throw new InvalidArgumentException('Identifier không hợp lệ: ' . $identifier);
    }
    return $identifier;
}

function _safe_operator(string $operator): string
{
    $operator = strtoupper(trim($operator));
    $allow = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'IN'];
    if (!in_array($operator, $allow, true)) {
        throw new InvalidArgumentException('Operator không hợp lệ');
    }
    return $operator;
}

function _safe_logic(string $logic): string
{
    $logic = strtoupper(trim($logic));
    if ($logic === '') {
        return '';
    }
    if (!in_array($logic, ['AND', 'OR'], true)) {
        throw new InvalidArgumentException('Logic không hợp lệ');
    }
    return $logic;
}

function _insert_info($conn, $table, $fields = [], $values = [])
{
    try {
        if (!$conn instanceof PDO) {
            throw new InvalidArgumentException('Kết nối không hợp lệ');
        }
        if (count($fields) !== count($values) || empty($fields)) {
            return false;
        }

        $table = _safe_identifier((string) $table);
        $safeFields = [];
        foreach ($fields as $field) {
            $safeFields[] = _safe_identifier((string) $field);
        }

        $fieldList = implode(', ', $safeFields);
        $placeholders = implode(', ', array_fill(0, count($safeFields), '?'));
        $sql = "INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})";

        $stmt = $conn->prepare($sql);
        return $stmt->execute(array_values($values));
    } catch (Throwable $exception) {
        error_log('SQL Error in _insert_info: ' . $exception->getMessage());
        return false;
    }
}

function _update_info($conn, $table, $fields = [], $values = [], $conditions = [])
{
    try {
        if (!$conn instanceof PDO) {
            throw new InvalidArgumentException('Kết nối không hợp lệ');
        }
        if (count($fields) !== count($values) || empty($fields) || empty($conditions)) {
            return false;
        }

        $table = _safe_identifier((string) $table);
        $setParts = [];
        $params = [];

        foreach ($fields as $index => $field) {
            $safeField = _safe_identifier((string) $field);
            $setParts[] = "{$safeField} = ?";
            $params[] = $values[$index];
        }

        $whereParts = [];
        foreach ($conditions as $column => $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }
            $safeColumn = _safe_identifier((string) $column);
            $operator = _safe_operator((string) ($condition[0] ?? '='));
            $value = $condition[1] ?? null;
            $logic = _safe_logic((string) ($condition[2] ?? ''));

            if ($operator === 'IN' && is_array($value) && !empty($value)) {
                $inPlaceholders = implode(', ', array_fill(0, count($value), '?'));
                $whereParts[] = "{$safeColumn} IN ({$inPlaceholders})" . ($logic !== '' ? " {$logic}" : '');
                foreach ($value as $item) {
                    $params[] = $item;
                }
            } else {
                $whereParts[] = "{$safeColumn} {$operator} ?" . ($logic !== '' ? " {$logic}" : '');
                $params[] = $value;
            }
        }

        if (empty($whereParts)) {
            return false;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . ' WHERE ' . implode(' ', $whereParts);
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    } catch (Throwable $exception) {
        error_log('SQL Error in _update_info: ' . $exception->getMessage());
        return false;
    }
}

function _select_info($conn, $table, $fields = [], $conditions = [])
{
    try {
        if (!$conn instanceof PDO) {
            throw new InvalidArgumentException('Kết nối không hợp lệ');
        }

        $table = _safe_identifier((string) $table);
        if (empty($fields)) {
            $fieldList = '*';
        } else {
            $safeFields = [];
            foreach ($fields as $field) {
                $safeFields[] = _safe_identifier((string) $field);
            }
            $fieldList = implode(', ', $safeFields);
        }

        $params = [];
        $sql = "SELECT {$fieldList} FROM {$table}";

        if (isset($conditions['WHERE']) && is_array($conditions['WHERE']) && !empty($conditions['WHERE'])) {
            $whereParts = [];
            $chunks = $conditions['WHERE'];

            for ($i = 0; $i < count($chunks); $i += 4) {
                $column = $chunks[$i] ?? null;
                $operator = $chunks[$i + 1] ?? '=';
                $value = $chunks[$i + 2] ?? null;
                $logic = $chunks[$i + 3] ?? '';

                if ($column === null || $column === '') {
                    continue;
                }

                $safeColumn = _safe_identifier((string) $column);
                $safeOperator = _safe_operator((string) $operator);
                $safeLogic = _safe_logic((string) $logic);

                if ($safeOperator === 'IN' && is_array($value) && !empty($value)) {
                    $inPlaceholders = implode(', ', array_fill(0, count($value), '?'));
                    $whereParts[] = "{$safeColumn} IN ({$inPlaceholders})" . ($safeLogic !== '' ? " {$safeLogic}" : '');
                    foreach ($value as $item) {
                        $params[] = $item;
                    }
                } else {
                    $whereParts[] = "{$safeColumn} {$safeOperator} ?" . ($safeLogic !== '' ? " {$safeLogic}" : '');
                    $params[] = $value;
                }
            }

            if (!empty($whereParts)) {
                $sql .= ' WHERE ' . implode(' ', $whereParts);
            }
        }

        if (isset($conditions['ORDER BY']) && is_array($conditions['ORDER BY']) && count($conditions['ORDER BY']) >= 2) {
            $orderColumn = _safe_identifier((string) ($conditions['ORDER BY'][0] ?? ''));
            $orderDir = strtoupper((string) ($conditions['ORDER BY'][1] ?? 'ASC'));
            if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
                $orderDir = 'ASC';
            }
            $sql .= " ORDER BY {$orderColumn} {$orderDir}";
        }

        if (isset($conditions['LIMIT']) && is_array($conditions['LIMIT']) && isset($conditions['LIMIT'][0])) {
            $limit = (int) $conditions['LIMIT'][0];
            if ($limit > 0) {
                $sql .= ' LIMIT ' . $limit;
            }
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        error_log('SQL Error in _select_info: ' . $exception->getMessage());
        return false;
    }
}

function _delete_info($conn, $table, $conditions = [])
{
    try {
        if (!$conn instanceof PDO) {
            throw new InvalidArgumentException('Kết nối không hợp lệ');
        }
        if (empty($conditions)) {
            return false;
        }

        $table = _safe_identifier((string) $table);
        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }
            $safeColumn = _safe_identifier((string) $column);
            $operator = _safe_operator((string) ($condition[0] ?? '='));
            $value = $condition[1] ?? null;
            $logic = _safe_logic((string) ($condition[2] ?? ''));

            if ($operator === 'IN' && is_array($value) && !empty($value)) {
                $inPlaceholders = implode(', ', array_fill(0, count($value), '?'));
                $whereParts[] = "{$safeColumn} IN ({$inPlaceholders})" . ($logic !== '' ? " {$logic}" : '');
                foreach ($value as $item) {
                    $params[] = $item;
                }
            } else {
                $whereParts[] = "{$safeColumn} {$operator} ?" . ($logic !== '' ? " {$logic}" : '');
                $params[] = $value;
            }
        }

        if (empty($whereParts)) {
            return false;
        }

        $sql = "DELETE FROM {$table} WHERE " . implode(' ', $whereParts);
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    } catch (Throwable $exception) {
        error_log('SQL Error in _delete_info: ' . $exception->getMessage());
        return false;
    }
}

function _is_exist($conn, $table, $field, $value)
{
    try {
        if (!$conn instanceof PDO) {
            throw new InvalidArgumentException('Kết nối không hợp lệ');
        }

        $table = _safe_identifier((string) $table);
        $field = _safe_identifier((string) $field);
        $sql = "SELECT 1 FROM {$table} WHERE {$field} = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$value]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        error_log('SQL Error in _is_exist: ' . $exception->getMessage());
        return false;
    }
}

// ==========================================
// CÁC HÀM HELPER (Auth & Logic)
// ==========================================

function chuan_hoa_chuoi_sql($conn, $str)
{
    return trim((string) $str);
}

function kiem_tra_ton_tai_ban_ghi($conn, $bang, $cot, $gia_tri)
{
    return _is_exist($conn, $bang, $cot, $gia_tri);
}

function truy_van_mot_ban_ghi($conn, $bang, $cot_khoa, $gia_tri_khoa)
{
    $conditions = [
        'WHERE' => [
            $cot_khoa,
            '=',
            $gia_tri_khoa,
            ''
        ],
        'LIMIT' => [1, '', '', '']
    ];

    $data = _select_info($conn, $bang, [], $conditions);
    return !empty($data) ? $data[0] : null;
}

/**
 * Ánh xạ mã quyền -> idQuyen
 * CSDL mới dùng maQuyen_code để code backend kiểm tra.
 * (Nếu cần tương thích cũ, vẫn fallback maQuyen)
 */
function anh_xa_ma_quyen($conn, $ma_quyen_code)
{
    try {
        if (!$conn instanceof PDO) {
            return null;
        }

        $safe = trim((string) $ma_quyen_code);
        if ($safe === '') {
            return null;
        }

        $stmt = $conn->prepare('SELECT idQuyen FROM quyen WHERE maQuyen_code = ? LIMIT 1');
        $stmt->execute([$safe]);
        $idQuyen = $stmt->fetchColumn();
        if ($idQuyen !== false) {
            return (int) $idQuyen;
        }

        $stmtFallback = $conn->prepare('SELECT idQuyen FROM quyen WHERE maQuyen = ? LIMIT 1');
        $stmtFallback->execute([$safe]);
        $idQuyenFallback = $stmtFallback->fetchColumn();

        return $idQuyenFallback !== false ? (int) $idQuyenFallback : null;
    } catch (Throwable $exception) {
        error_log('SQL Error in anh_xa_ma_quyen: ' . $exception->getMessage());
        return null;
    }
}

/**
 * Kiểm tra quyền HỆ THỐNG (HE_THONG) theo bảng taikhoan_quyen.
 * Truyền vào maQuyen_code (vd: admin_events, admin_users)
 */
function kiem_tra_quyen_he_thong($conn, $id_tai_khoan, $ma_quyen_code)
{
    // DEV MODE: bypass quyền
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) {
        return true;
    }

    $user = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $id_tai_khoan);
    if (!$user) return false;

    // Admin hệ thống: full quyền
    if ((int)$user['idLoaiTK'] === 1) return true;

    $id_quyen = anh_xa_ma_quyen($conn, $ma_quyen_code);
    if (!$id_quyen) return false;

    $conditions = [
        'WHERE' => [
            'idTK',
            '=',
            (int)$id_tai_khoan,
            'AND',
            'idQuyen',
            '=',
            (int)$id_quyen,
            'AND',
            'isActive',
            '=',
            1,
            ''
        ],
        'LIMIT' => [1, '', '', '']
    ];

    $result = _select_info($conn, 'taikhoan_quyen', ['thoiGianBatDau', 'thoiGianKetThuc'], $conditions);
    if (empty($result)) return false;

    $quyen_tk = $result[0];
    $now = time();
    $start = strtotime($quyen_tk['thoiGianBatDau']);
    $end = !empty($quyen_tk['thoiGianKetThuc']) ? strtotime($quyen_tk['thoiGianKetThuc']) : null;

    return ($start <= $now && ($end === null || $end >= $now));
}

/**
 * Kiểm tra quyền THEO SỰ KIỆN (SU_KIEN) dựa trên CSDL mới.
 * - Role của user trong sự kiện: taikhoan_vaitro_sukien (isActive=1)
 * - Quyền role lấy từ bảng vaitro_quyen theo idVaiTro
 * - Quyền match theo quyen.maQuyen_code (phamVi='SU_KIEN')
 */
function kiem_tra_quyen_su_kien($conn, int $idTK, int $idSK, string $maQuyenCode): bool
{
    // DEV MODE: bypass quyền
    if (defined('_BYPASS_AUTH') && _BYPASS_AUTH === true) {
        return true;
    }

    if ($idTK <= 0 || $idSK <= 0 || trim($maQuyenCode) === '') return false;

    $idTK = (int)$idTK;
    $idSK = (int)$idSK;
    $code = trim($maQuyenCode);

    // Admin hệ thống: full quyền
    $user = truy_van_mot_ban_ghi($conn, 'taikhoan', 'idTK', $idTK);
    if ($user && (int)$user['idLoaiTK'] === 1) return true;

    try {
        if (!$conn instanceof PDO) {
            return false;
        }

        $sql = "
            SELECT 1
            FROM taikhoan_vaitro_sukien tvs
            JOIN vaitro_quyen vq ON vq.idVaiTro = tvs.idVaiTro
            JOIN quyen q ON q.idQuyen = vq.idQuyen
            WHERE tvs.idTK = :idTK
              AND tvs.idSK = :idSK
              AND tvs.isActive = 1
              AND q.phamVi = 'SU_KIEN'
              AND q.maQuyen_code = :code
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':idTK' => $idTK,
            ':idSK' => $idSK,
            ':code' => $code,
        ]);

        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        error_log('SQL Error in kiem_tra_quyen_su_kien: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Check user có ít nhất 1 quyền trong list (SU_KIEN)
 */
function kiem_tra_bat_ky_quyen_su_kien($conn, int $idTK, int $idSK, array $codes): bool
{
    foreach ($codes as $c) {
        $c = trim((string)$c);
        if ($c !== '' && kiem_tra_quyen_su_kien($conn, $idTK, $idSK, $c)) {
            return true;
        }
    }
    return false;
}


//Hàm thêm layouts
function layout($layout_name, $data = [])
{
    if (file_exists(_PATH_URL_TEMPLATES . '/layouts/' . $layout_name . '.php')) {
        require_once(_PATH_URL_TEMPLATES . '/layouts/' . $layout_name . '.php');
    }
}

//Kiểm tra phương thức Get
function isGet()
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return true;
    }
    return false;
}

//Kiểm tra phương thức Post
function isPost()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return true;
    }
    return false;
}

//Hàm filter lọc dữ liệu
function filter()
{
    $filterArr = [];
    if (isGet()) {
        foreach ($_GET as $key => $value) {
            $filterArr[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }
    if (isPost()) {
        foreach ($_POST as $key => $value) {
            $filterArr[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }
    return $filterArr;
}