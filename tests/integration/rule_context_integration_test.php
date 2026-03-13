<?php

declare(strict_types=1);

function ict_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function ict_map_context_to_loai(array $contexts): array
{
    $map = [
        'DANG_KY_THAM_GIA_SV' => ['THAMGIA_SV'],
        'DANG_KY_THAM_GIA_GV' => ['THAMGIA_GV'],
        'DUYET_VONG_THI' => ['VONGTHI'],
        'DUYET_VONG_THI_HANG_LOAT' => ['VONGTHI'],
        'XET_GIAI_THUONG' => ['GIAITHUONG'],
        'NOP_SAN_PHAM' => ['SANPHAM'],
    ];

    $out = [];
    foreach ($contexts as $context) {
        $key = strtoupper(trim((string) $context));
        if (!isset($map[$key])) {
            continue;
        }
        foreach ($map[$key] as $loai) {
            $out[$loai] = true;
        }
    }

    return array_keys($out);
}

$contexts = ['DUYET_VONG_THI', 'DUYET_VONG_THI_HANG_LOAT'];
$mapped = ict_map_context_to_loai($contexts);
ict_assert_true(count($mapped) === 1 && $mapped[0] === 'VONGTHI', 'Two review contexts must map to one governed loaiApDung VONGTHI');

$contextsMixed = ['DANG_KY_THAM_GIA_SV', 'XET_GIAI_THUONG'];
$mappedMixed = ict_map_context_to_loai($contextsMixed);
sort($mappedMixed);
ict_assert_true($mappedMixed === ['GIAITHUONG', 'THAMGIA_SV'], 'Mixed contexts must keep deterministic mapped types');
