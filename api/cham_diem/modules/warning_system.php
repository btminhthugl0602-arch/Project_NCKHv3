<?php
/**
 * Module 3: Warning System
 *
 * Hệ thống cảnh báo chất lượng chấm điểm:
 * - Cảnh báo tiêu chí có độ lệch cao (> 30%)
 * - Cảnh báo giám khảo ngoại lai (outlier, z-score > 2.0)
 * - Báo cáo tổng hợp mức độ vấn đề toàn bộ bài thi
 *
 * Sử dụng: require_once __DIR__ . '/modules/warning_system.php';
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

/** Ngưỡng % độ lệch để cảnh báo tiêu chí */
define('WARN_CRITERION_THRESHOLD', 30.0);

/** Ngưỡng z-score để cảnh báo giám khảo ngoại lai */
define('WARN_JUDGE_ZSCORE_THRESHOLD', 2.0);

/**
 * Kiểm tra mức cảnh báo của một tiêu chí.
 *
 * @param  float $deviationPct  % độ lệch (kết quả từ score_calc_deviation_percent)
 * @param  float $threshold     Ngưỡng cảnh báo (mặc định 30%)
 * @return string 'critical' (>50%) | 'high' (>30%) | 'ok'
 */
function warn_check_criterion(float $deviationPct, float $threshold = WARN_CRITERION_THRESHOLD): string
{
    if ($deviationPct > 50.0) return 'critical';
    if ($deviationPct > $threshold) return 'high';
    return 'ok';
}

/**
 * Kiểm tra giám khảo ngoại lai dựa trên z-score tổng điểm.
 *
 * @param  float   $judgeTotal  Tổng điểm của GK cần kiểm tra
 * @param  float[] $allTotals   Tổng điểm của tất cả GK trong nhóm
 * @param  float   $threshold   Ngưỡng z-score (mặc định 2.0)
 * @return array { isOutlier: bool, zScore: float, direction: 'lenient'|'strict'|'normal' }
 */
function warn_check_judge_outlier(float $judgeTotal, array $allTotals, float $threshold = WARN_JUDGE_ZSCORE_THRESHOLD): array
{
    $n = count($allTotals);
    if ($n < 2) {
        return ['isOutlier' => false, 'zScore' => 0.0, 'direction' => 'normal'];
    }

    $mean     = array_sum($allTotals) / $n;
    $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $allTotals)) / $n;
    $std      = sqrt($variance);

    if ($std < 0.0001) {
        return ['isOutlier' => false, 'zScore' => 0.0, 'direction' => 'normal'];
    }

    $zScore    = ($judgeTotal - $mean) / $std;
    $isOutlier = abs($zScore) > $threshold;

    if ($zScore > 0.001) {
        $direction = 'lenient';   // Cho điểm cao hơn nhóm
    } elseif ($zScore < -0.001) {
        $direction = 'strict';    // Cho điểm thấp hơn nhóm
    } else {
        $direction = 'normal';
    }

    return [
        'isOutlier' => $isOutlier,
        'zScore'    => round($zScore, 3),
        'direction' => $direction,
    ];
}

/**
 * Tạo báo cáo cảnh báo đầy đủ từ ma trận điểm.
 *
 * @param  array $matrix Kết quả của score_build_scoring_matrix()
 * @return array {
 *   criterionWarnings: [{ idTieuChi, name, deviationPct, level }],
 *   judgeWarnings:     [{ idGV, tenGV, tongDiem, zScore, direction, message }],
 *   overallLevel:      'critical' | 'warning' | 'ok',
 *   totalWarnings:     int
 * }
 */
function warn_generate_report(array $matrix): array
{
    $judges   = $matrix['judges'];
    $criteria = $matrix['criteria'];

    // ── Criterion-level warnings ───────────────────────────────────────────────
    $criterionWarnings = [];
    foreach ($criteria as $tc) {
        $level = warn_check_criterion($tc['deviationPct']);
        if ($level !== 'ok') {
            $criterionWarnings[] = [
                'idTieuChi'    => $tc['idTieuChi'],
                'name'         => $tc['name'],
                'deviationPct' => $tc['deviationPct'],
                'level'        => $level,
            ];
        }
    }

    // ── Judge-level warnings (outlier detection) ──────────────────────────────
    $allTotals    = array_column($judges, 'tongDiem');
    $judgeWarnings = [];

    foreach ($judges as $judge) {
        $check = warn_check_judge_outlier((float) $judge['tongDiem'], $allTotals);
        if ($check['isOutlier']) {
            $judgeWarnings[] = [
                'idGV'      => $judge['idGV'],
                'tenGV'     => $judge['tenGV'],
                'tongDiem'  => $judge['tongDiem'],
                'zScore'    => $check['zScore'],
                'direction' => $check['direction'],
                'message'   => $check['direction'] === 'lenient'
                    ? "Giám khảo cho điểm cao hơn đáng kể so với nhóm (z = {$check['zScore']})"
                    : "Giám khảo cho điểm thấp hơn đáng kể so với nhóm (z = {$check['zScore']})",
            ];
        }
    }

    // ── Overall level ─────────────────────────────────────────────────────────
    $hasCritical = !empty(array_filter($criterionWarnings, fn($w) => $w['level'] === 'critical'))
                || !empty($judgeWarnings);
    $hasWarning  = !empty($criterionWarnings);

    $overallLevel = $hasCritical ? 'critical' : ($hasWarning ? 'warning' : 'ok');

    return [
        'criterionWarnings' => $criterionWarnings,
        'judgeWarnings'     => $judgeWarnings,
        'overallLevel'      => $overallLevel,
        'totalWarnings'     => count($criterionWarnings) + count($judgeWarnings),
    ];
}

/**
 * Xác định mức độ cảnh báo tổng thể theo p-value và % độ lệch tối đa.
 *
 * @param  float $pValue        p-value từ kiểm định thống kê
 * @param  float $maxDeviation  % độ lệch lớn nhất trong các tiêu chí
 * @return string 'critical' | 'warning' | 'ok'
 */
function warn_get_level(float $pValue, float $maxDeviation): string
{
    if ($pValue < 0.01 || $maxDeviation > 50.0) return 'critical';
    if ($pValue < 0.05 || $maxDeviation > 30.0) return 'warning';
    return 'ok';
}

/**
 * Tóm tắt chất lượng từng giám khảo để hiển thị trong panel giám sát.
 *
 * @param  array $judgeQuality Kết quả của score_calc_judge_quality()
 * @return array [{ idGV, tenGV, tongDiem, biasLabel, biasDirection,
 *                  consistencyScore, qualityLabel, qualityColor }]
 */
function warn_judge_quality_summary(array $judgeQuality): array
{
    $directionLabels = [
        'lenient' => 'Cho điểm cao',
        'strict'  => 'Cho điểm thấp',
        'normal'  => 'Cân bằng',
    ];

    $levelConfig = [
        'ok'      => ['label' => 'Tốt',       'color' => 'emerald'],
        'warning' => ['label' => 'Cần xem xét', 'color' => 'amber'],
        'outlier' => ['label' => 'Ngoại lai',  'color' => 'rose'],
    ];

    return array_map(function ($jq) use ($directionLabels, $levelConfig) {
        $direction  = $jq['biasScore'] > 0.5 ? 'lenient' : ($jq['biasScore'] < -0.5 ? 'strict' : 'normal');
        $levelCfg   = $levelConfig[$jq['qualityLevel']] ?? $levelConfig['ok'];

        return [
            'idGV'             => $jq['idGV'],
            'tenGV'            => $jq['tenGV'],
            'tongDiem'         => $jq['tongDiem'],
            'biasLabel'        => ($jq['biasScore'] >= 0 ? '+' : '') . number_format($jq['biasScore'], 2),
            'biasDirection'    => $directionLabels[$direction],
            'consistencyScore' => $jq['consistencyScore'],
            'qualityLabel'     => $levelCfg['label'],
            'qualityColor'     => $levelCfg['color'],
        ];
    }, $judgeQuality);
}
