<?php
/**
 * Module 2: Statistical Test
 *
 * Kiểm định thống kê độ tương đồng giữa các giám khảo (Inter-Rater Reliability):
 * - Paired T-test (2 giám khảo)
 * - One-way ANOVA (≥ 3 giám khảo)
 * - Chuyển đổi thống kê → p-value (xấp xỉ)
 * - Diễn giải kết quả bằng ngôn ngữ tự nhiên
 *
 * Sử dụng: require_once __DIR__ . '/modules/statistical_test.php';
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

/**
 * Kiểm định IRR tổng hợp (tự chọn phương pháp theo số lượng giám khảo).
 *
 * @param  array[] $diemTheoGK  Mảng 2 chiều: [[diem_tc1, diem_tc2, ...], [...], ...]
 *                               Mỗi phần tử là mảng điểm từng tiêu chí của một giám khảo.
 * @return array {
 *   phuongPhap, pValue, statistic, ketLuan, canhBao,
 *   doLechMaxMin, diemTBChung, diemTBTheoGK
 * }
 */
function stat_test_irr(array $diemTheoGK): array
{
    $n = count($diemTheoGK);

    if ($n < 2) {
        return [
            'phuongPhap' => 'N/A',
            'pValue'     => null,
            'statistic'  => null,
            'ketLuan'    => 'Cần ít nhất 2 giám khảo để tính IRR',
            'canhBao'    => false,
        ];
    }

    // Điểm TB theo từng giám khảo
    $diemTBTheoGK = array_map(function ($scores) {
        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
    }, $diemTheoGK);

    $diemTBChung  = array_sum($diemTBTheoGK) / $n;
    $doLechMaxMin = max($diemTBTheoGK) - min($diemTBTheoGK);
    $canhBao      = $diemTBChung > 0 && ($doLechMaxMin / $diemTBChung) >= 0.30;

    // Cũng kiểm tra từng tiêu chí riêng lẻ: nếu bất kỳ tiêu chí nào có độ lệch > 30%
    // thì cũng bật cảnh báo (nhất quán với modal phân tích phía Frontend)
    if (!$canhBao) {
        $nCriteria = count($diemTheoGK[0] ?? []);
        for ($i = 0; $i < $nCriteria; $i++) {
            $scoresForCriterion = array_values(array_filter(
                array_map(fn($gkScores) => $gkScores[$i] ?? null, $diemTheoGK),
                fn($s) => $s !== null
            ));
            if (count($scoresForCriterion) >= 2) {
                $avg = array_sum($scoresForCriterion) / count($scoresForCriterion);
                if ($avg > 0 && (max($scoresForCriterion) - min($scoresForCriterion)) / $avg >= 0.30) {
                    $canhBao = true;
                    break;
                }
            }
        }
    }

    if ($n === 2) {
        $r = stat_test_paired_ttest(
            array_map('floatval', $diemTheoGK[0]),
            array_map('floatval', $diemTheoGK[1])
        );
        return [
            'phuongPhap'   => 'Paired T-test',
            'pValue'       => $r['pValue'],
            'statistic'    => $r['tStatistic'],
            'ketLuan'      => $r['pValue'] < 0.05
                ? 'Có sự khác biệt có ý nghĩa thống kê giữa 2 giám khảo (p < 0.05)'
                : 'Không có sự khác biệt có ý nghĩa thống kê (p ≥ 0.05)',
            'canhBao'      => $canhBao || ($r['pValue'] < 0.05),
            'doLechMaxMin' => round($doLechMaxMin, 2),
            'diemTBChung'  => round($diemTBChung, 2),
            'diemTBTheoGK' => array_map(fn($d) => round($d, 2), $diemTBTheoGK),
        ];
    }

    // 3+ judges → ANOVA
    $r = stat_test_one_way_anova($diemTheoGK);
    return [
        'phuongPhap'   => 'One-way ANOVA',
        'pValue'       => $r['pValue'],
        'statistic'    => $r['fStatistic'],
        'ketLuan'      => $r['pValue'] < 0.05
            ? 'Có sự khác biệt có ý nghĩa thống kê giữa các giám khảo (p < 0.05)'
            : 'Không có sự khác biệt có ý nghĩa thống kê (p ≥ 0.05)',
        'canhBao'      => $canhBao || ($r['pValue'] < 0.05),
        'doLechMaxMin' => round($doLechMaxMin, 2),
        'diemTBChung'  => round($diemTBChung, 2),
        'diemTBTheoGK' => array_map(fn($d) => round($d, 2), $diemTBTheoGK),
    ];
}

/**
 * Paired T-test (Kiểm định T bắt cặp) cho 2 giám khảo.
 *
 * @param  float[] $group1 Mảng điểm theo tiêu chí của GK 1
 * @param  float[] $group2 Mảng điểm theo tiêu chí của GK 2
 * @return array { tStatistic: float, pValue: float, df: int }
 */
function stat_test_paired_ttest(array $group1, array $group2): array
{
    $n = min(count($group1), count($group2));
    if ($n < 2) {
        return ['tStatistic' => 0.0, 'pValue' => 1.0, 'df' => 0];
    }

    $diffs    = [];
    for ($i = 0; $i < $n; $i++) {
        $diffs[] = (float) $group1[$i] - (float) $group2[$i];
    }

    $meanDiff       = array_sum($diffs) / $n;
    $sumSqDiff      = array_sum(array_map(fn($d) => pow($d - $meanDiff, 2), $diffs));
    $sdDiff         = sqrt($sumSqDiff / ($n - 1));

    if ($sdDiff == 0) {
        return ['tStatistic' => 0.0, 'pValue' => 1.0, 'df' => $n - 1];
    }

    $tStatistic = $meanDiff / ($sdDiff / sqrt($n));
    $df         = $n - 1;
    $pValue     = stat_test_t_to_p($tStatistic, $df);

    return [
        'tStatistic' => round($tStatistic, 4),
        'pValue'     => round($pValue, 4),
        'df'         => $df,
    ];
}

/**
 * One-way ANOVA (Phân tích phương sai một yếu tố) cho ≥ 3 nhóm.
 *
 * @param  array[] $groups Mảng mảng điểm, mỗi phần tử là điểm của một giám khảo
 * @return array { fStatistic: float, pValue: float, dfBetween: int, dfWithin: int }
 */
function stat_test_one_way_anova(array $groups): array
{
    $k = count($groups);
    if ($k < 2) {
        return ['fStatistic' => 0.0, 'pValue' => 1.0, 'dfBetween' => 0, 'dfWithin' => 0];
    }

    $allValues  = [];
    $groupMeans = [];
    $groupSizes = [];

    foreach ($groups as $i => $group) {
        $groupSizes[$i] = count($group);
        $groupMeans[$i] = $groupSizes[$i] > 0 ? array_sum($group) / $groupSizes[$i] : 0.0;
        $allValues      = array_merge($allValues, $group);
    }

    $n = count($allValues);
    if ($n < $k + 1) {
        return ['fStatistic' => 0.0, 'pValue' => 1.0, 'dfBetween' => $k - 1, 'dfWithin' => max(0, $n - $k)];
    }

    $grandMean = array_sum($allValues) / $n;

    // Sum of Squares Between (SSB)
    $ssb = 0.0;
    foreach ($groups as $i => $group) {
        $ssb += $groupSizes[$i] * pow($groupMeans[$i] - $grandMean, 2);
    }

    // Sum of Squares Within (SSW)
    $ssw = 0.0;
    foreach ($groups as $i => $group) {
        foreach ($group as $v) {
            $ssw += pow($v - $groupMeans[$i], 2);
        }
    }

    $dfBetween = $k - 1;
    $dfWithin  = $n - $k;

    if ($dfWithin <= 0 || $ssw == 0) {
        return ['fStatistic' => 0.0, 'pValue' => 1.0, 'dfBetween' => $dfBetween, 'dfWithin' => $dfWithin];
    }

    $fStatistic = ($ssb / $dfBetween) / ($ssw / $dfWithin);
    $pValue     = stat_test_f_to_p($fStatistic, $dfBetween, $dfWithin);

    return [
        'fStatistic' => round($fStatistic, 4),
        'pValue'     => round($pValue, 4),
        'dfBetween'  => $dfBetween,
        'dfWithin'   => $dfWithin,
    ];
}

/**
 * Chuyển đổi t-statistic → p-value (xấp xỉ, two-tailed).
 *
 * @param  float $t  T-statistic (có thể âm)
 * @param  int   $df Degrees of freedom
 * @return float     p-value ∈ (0, 1]
 */
function stat_test_t_to_p(float $t, int $df): float
{
    if ($df > 30) {
        // Normal approximation (Bowling et al.)
        $z = abs($t);
        $p = 1.0 / (1.0 + exp(-0.07056 * pow($z, 3) - 1.5976 * $z));
        return 2.0 * (1.0 - $p);
    }

    // Table-based approximation for small df
    $cv = [
        1  => [6.314, 12.706, 63.657],
        2  => [2.920,  4.303,  9.925],
        3  => [2.353,  3.182,  5.841],
        4  => [2.132,  2.776,  4.604],
        5  => [2.015,  2.571,  4.032],
        10 => [1.812,  2.228,  3.169],
        20 => [1.725,  2.086,  2.845],
        30 => [1.697,  2.042,  2.750],
    ];

    $keys = array_keys($cv);
    usort($keys, fn($a, $b) => abs($a - $df) <=> abs($b - $df));
    $dfKey = $keys[0];
    $absT  = abs($t);

    if ($absT < $cv[$dfKey][0]) return 0.20;
    if ($absT < $cv[$dfKey][1]) return 0.10;
    if ($absT < $cv[$dfKey][2]) return 0.05;
    return 0.01;
}

/**
 * Chuyển đổi F-statistic → p-value (xấp xỉ).
 *
 * @param  float $f   F-statistic
 * @param  int   $df1 dfBetween (k−1)
 * @param  int   $df2 dfWithin  (n−k)
 * @return float      p-value ∈ (0, 1]
 */
function stat_test_f_to_p(float $f, int $df1, int $df2): float
{
    if ($f <= 0) {
        return 1.0;
    }

    if ($df1 >= 1 && $df2 >= 10) {
        // Linear approximation for standard cases
        $fCrit005 = max(2.0, 4.0 - ($df2 - 10) * 0.02);
        $fCrit001 = max(4.0, 7.5 - ($df2 - 10) * 0.05);

        if ($f < $fCrit005) return 0.10;
        if ($f < $fCrit001) return 0.05;
        return 0.01;
    }

    return 0.05; // Conservative default
}

/**
 * Diễn giải kết quả kiểm định thành văn bản kết luận và khuyến nghị.
 *
 * @param  float  $pValue     p-value từ kiểm định
 * @param  string $method     'Paired T-test' | 'One-way ANOVA'
 * @param  bool   $hasCanhBao Có cảnh báo độ lệch > 30% hay không
 * @return array { significant: bool, level: string, conclusion: string, recommendation: string }
 */
function stat_test_interpret(float $pValue, string $method, bool $hasCanhBao): array
{
    $significant = $pValue < 0.05;
    $level       = $pValue < 0.01 ? 'rất cao' : ($pValue < 0.05 ? 'có ý nghĩa' : 'không đáng kể');

    if ($significant || $hasCanhBao) {
        $conclusion     = "Kết quả kiểm định {$method} cho thấy sự khác biệt ở mức {$level} (p = " . number_format($pValue, 4) . ").";
        $recommendation = 'Cần xem xét lại điểm số giữa các giám khảo. Cân nhắc mời Trọng tài phúc khảo để đảm bảo tính khách quan.';
    } else {
        $conclusion     = "Kết quả kiểm định {$method} không tìm thấy sự khác biệt có ý nghĩa (p = " . number_format($pValue, 4) . ").";
        $recommendation = 'Kết quả đồng thuận tốt giữa các giám khảo. Có thể tiến hành duyệt kết quả.';
    }

    return [
        'significant'    => $significant,
        'level'          => $level,
        'conclusion'     => $conclusion,
        'recommendation' => $recommendation,
    ];
}
