<?php

if (!defined('_AUTHEN')) {
    die('Truy cap khong hop le');
}

/**
 * Nguon su that xac dinh su kien co luong GVHD hay khong.
 * - Uu tien cot moi coGVHDTheoSuKien.
 * - Fallback legacy: soGVHDToiDa = 0 -> khong co GVHD.
 */
function su_kien_co_gvhd(array $suKien): bool
{
    if (array_key_exists('coGVHDTheoSuKien', $suKien)) {
        return (int) $suKien['coGVHDTheoSuKien'] === 1;
    }

    if (array_key_exists('co_gvhd_theo_su_kien', $suKien)) {
        return (int) $suKien['co_gvhd_theo_su_kien'] === 1;
    }

    if (array_key_exists('soGVHDToiDa', $suKien) && $suKien['soGVHDToiDa'] !== null) {
        return (int) $suKien['soGVHDToiDa'] !== 0;
    }

    return true;
}
