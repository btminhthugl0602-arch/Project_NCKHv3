<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/unit/semantic_parser_test.php',
    __DIR__ . '/unit/notification_payload_validation_test.php',
    __DIR__ . '/unit/notification_recipient_resolver_test.php',
    __DIR__ . '/integration/rule_context_integration_test.php',
    __DIR__ . '/integration/notification_inbox_contract_test.php',
    __DIR__ . '/regression/approve_actions_regression_test.php',
    __DIR__ . '/regression/notification_legacy_giam_khao_actions_test.php',
];

$failures = [];

foreach ($tests as $testFile) {
    try {
        require $testFile;
        echo "[PASS] {$testFile}\n";
    } catch (Throwable $exception) {
        $failures[] = [
            'file' => $testFile,
            'error' => $exception->getMessage(),
        ];
        echo "[FAIL] {$testFile}: {$exception->getMessage()}\n";
    }
}

if (!empty($failures)) {
    echo "\nTest summary: " . count($failures) . " failure(s).\n";
    exit(1);
}

echo "\nTest summary: all tests passed.\n";
