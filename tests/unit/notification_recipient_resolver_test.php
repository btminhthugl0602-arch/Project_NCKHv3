<?php

declare(strict_types=1);

function nrr_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

if (!defined('_AUTHEN')) {
    define('_AUTHEN', true);
}

require_once dirname(__DIR__, 2) . '/api/thong_bao/notification_service.php';

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    return;
}

$conn = new PDO('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$conn->exec('CREATE TABLE taikhoan (idTK INTEGER PRIMARY KEY, idLoaiTK INTEGER, isActive INTEGER)');
$conn->exec('CREATE TABLE taikhoan_vaitro_sukien (idTK INTEGER, idSK INTEGER, idVaiTro INTEGER, isActive INTEGER)');

$conn->exec('INSERT INTO taikhoan (idTK, idLoaiTK, isActive) VALUES (11, 2, 1), (12, 2, 1), (13, 2, 0), (21, 3, 1), (22, 3, 0), (31, 1, 1)');
$conn->exec('INSERT INTO taikhoan_vaitro_sukien (idTK, idSK, idVaiTro, isActive) VALUES (11, 50, 7, 1), (21, 50, 3, 1), (31, 50, 9, 1), (12, 51, 7, 1), (99, 50, 7, 0)');

$payload = notification_normalize_event_payload([
    'tieuDe' => 'Resolver test',
    'noiDung' => 'payload',
    'loaiThongBao' => 'he_thong',
    'phamVi' => 'ca_nhan',
    'nguoiGui' => 1,
    'recipients' => [88, 0, 88],
    'recipientGroups' => [
        ['loaiNhom' => 'GV'],
        ['loaiNhom' => 'SU_KIEN', 'idNhom' => 50, 'idVaiTro' => 7],
    ],
]);

$resolved = notification_resolve_recipients($conn, $payload);
sort($resolved);

$expected = [11, 12, 88];
nrr_assert_true($resolved === $expected, 'resolver: recipients must merge direct users and valid groups, then de-duplicate');
