<?php

declare(strict_types=1);

function nlga_assert_contains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

$filePath = dirname(__DIR__, 2) . '/api/thong_bao/giam_khao.php';
$content = file_get_contents($filePath);
if ($content === false) {
    throw new RuntimeException('Cannot read regression target file: ' . $filePath);
}

nlga_assert_contains("'lay_chua_doc'", $content, 'legacy API: lay_chua_doc action must remain available');
nlga_assert_contains("'danh_dau_da_doc'", $content, 'legacy API: danh_dau_da_doc action must remain available');
nlga_assert_contains("'gui_nhac_nho'", $content, 'legacy API: gui_nhac_nho action must remain available');
nlga_assert_contains("notification_feature_enabled('scoring')", $content, 'legacy API: scoring feature-flag guard must remain in place');
nlga_assert_contains('list_inbox($conn, $idTK', $content, 'legacy API: unread list must still use centralized inbox service');
nlga_assert_contains('mark_read($conn, $idThongBao, $idTK)', $content, 'legacy API: mark read path must still call centralized service');
nlga_assert_contains('dispatch_personal($conn, [', $content, 'legacy API: reminder dispatch must use centralized notification service');
