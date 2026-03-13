<?php

declare(strict_types=1);

function npv_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

if (!defined('_AUTHEN')) {
    define('_AUTHEN', true);
}

require_once dirname(__DIR__, 2) . '/api/thong_bao/notification_service.php';

$normalized = notification_normalize_event_payload([
    'tieuDe' => '  Test title  ',
    'noiDung' => 'Hello',
    'loaiThongBao' => 'su_kien',
    'phamVi' => 'nhom_nguoi',
    'idSK' => '17',
    'idDoiTuong' => '41',
    'loaiDoiTuong' => 'sanpham',
    'nguoiGui' => '9',
    'recipients' => [2, '3', 3],
]);

npv_assert_true($normalized['tieuDe'] === 'Test title', 'normalize: title must be trimmed');
npv_assert_true($normalized['loaiThongBao'] === 'SU_KIEN', 'normalize: loaiThongBao must be uppercase');
npv_assert_true($normalized['phamVi'] === 'NHOM_NGUOI', 'normalize: phamVi must be uppercase');
npv_assert_true($normalized['idSK'] === 17, 'normalize: idSK must be cast to int');
npv_assert_true($normalized['loaiDoiTuong'] === 'SANPHAM', 'normalize: loaiDoiTuong must be uppercase');
npv_assert_true($normalized['nguoiGui'] === 9, 'normalize: nguoiGui must be cast to int');

$valid = notification_validate_event_payload($normalized);
npv_assert_true($valid['valid'] === true, 'validate: normalized payload should be valid');

$invalid = notification_validate_event_payload(notification_normalize_event_payload([
    'tieuDe' => ' ',
    'loaiThongBao' => 'INVALID',
    'phamVi' => 'INVALID',
    'nguoiGui' => 0,
    'loaiDoiTuong' => 'WRONG',
]));

npv_assert_true($invalid['valid'] === false, 'validate: invalid payload must fail');
npv_assert_true(count($invalid['errors']) >= 4, 'validate: invalid payload must contain detailed error list');
