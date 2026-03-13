<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/monitor_rule_eval.php <log_file> [window_lines] [error_rate_threshold]\n");
    exit(2);
}

$logFile = $argv[1];
$windowLines = isset($argv[2]) ? max(100, (int) $argv[2]) : 1000;
$errorRateThreshold = isset($argv[3]) ? (float) $argv[3] : 0.20;

if (!is_file($logFile)) {
    fwrite(STDERR, "Log file not found: {$logFile}\n");
    exit(2);
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    fwrite(STDERR, "Unable to read log file\n");
    exit(2);
}

$tail = array_slice($lines, -$windowLines);
$totalEvaluate = 0;
$errorEvaluate = 0;
$failedRules = 0;

foreach ($tail as $line) {
    $payload = json_decode($line, true);
    if (!is_array($payload)) {
        continue;
    }

    if (($payload['module'] ?? '') !== 'quy_che' || ($payload['event'] ?? '') !== 'evaluate_rules') {
        continue;
    }

    $totalEvaluate++;
    $status = (string) ($payload['status'] ?? '');
    if ($status === 'error') {
        $errorEvaluate++;
    }
    if ($status === 'failed_rules') {
        $failedRules++;
    }
}

if ($totalEvaluate === 0) {
    echo "No evaluate_rules events in the selected window.\n";
    exit(0);
}

$errorRate = $errorEvaluate / $totalEvaluate;
$failedRate = $failedRules / $totalEvaluate;

echo json_encode([
    'module' => 'quy_che',
    'event' => 'evaluate_rules_monitor',
    'window' => $windowLines,
    'totalEvaluate' => $totalEvaluate,
    'errorEvaluate' => $errorEvaluate,
    'failedRules' => $failedRules,
    'errorRate' => round($errorRate, 4),
    'failedRate' => round($failedRate, 4),
    'threshold' => $errorRateThreshold,
], JSON_UNESCAPED_UNICODE) . PHP_EOL;

if ($errorRate > $errorRateThreshold) {
    fwrite(STDERR, "ALERT: evaluate_rules errorRate exceeds threshold\n");
    exit(1);
}

exit(0);
