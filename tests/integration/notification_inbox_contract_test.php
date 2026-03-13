<?php

declare(strict_types=1);

function nic_assert_contains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

$filePath = dirname(__DIR__, 2) . '/api/thong_bao/inbox.php';
$content = file_get_contents($filePath);
if ($content === false) {
    throw new RuntimeException('Cannot read integration target file: ' . $filePath);
}

nic_assert_contains("case 'list'", $content, 'inbox contract: GET list action must exist');
nic_assert_contains("case 'unread_count'", $content, 'inbox contract: GET unread_count action must exist');
nic_assert_contains("case 'mark_all_read'", $content, 'inbox contract: POST mark_all_read action must exist');
nic_assert_contains("case 'mark_read'", $content, 'inbox contract: POST mark_read action must exist');
nic_assert_contains('notification_api_response', $content, 'inbox contract: responses must use centralized notification_api_response');
nic_assert_contains('items = array_map(\'inbox_attach_deep_link\'', $content, 'inbox contract: list action must enrich deepLink');
nic_assert_contains("'deepLink'", $content, 'inbox contract: deepLink field must be attached to each item');
nic_assert_contains("'api' => 'thong_bao.inbox'", $content, 'inbox contract: response meta.api must stay stable');
