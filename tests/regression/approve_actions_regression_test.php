<?php

declare(strict_types=1);

function reg_assert_contains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

$filePath = dirname(__DIR__, 2) . '/api/cham_diem/xet_ket_qua.php';
$content = file_get_contents($filePath);
if ($content === false) {
    throw new RuntimeException('Cannot read regression target file: ' . $filePath);
}

reg_assert_contains('approve_score_auto', $content, 'Regression guard: approve_score_auto action must exist');
reg_assert_contains('approve_multiple', $content, 'Regression guard: approve_multiple action must exist');
reg_assert_contains('cham_diem_duyet_diem_voi_quyche', $content, 'Regression guard: approval actions must use governed rule engine path');
reg_assert_contains('DUYET_VONG_THI_HANG_LOAT', $content, 'Regression guard: bulk approval context must stay explicit');
reg_assert_contains('viPhamQuyChe', $content, 'Regression guard: bulk approval response must include rule violations');
