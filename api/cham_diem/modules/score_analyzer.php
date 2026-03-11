<?php
/**
 * Module 1: Score Analyzer
 *
 * Phân tích điểm chấm: tính tổng điểm, % độ lệch,
 * xây dựng ma trận điểm (criterion × judge),
 * và đánh giá chất lượng giám khảo.
 *
 * Sử dụng: require_once __DIR__ . '/modules/score_analyzer.php';
 */

if (!defined('_AUTHEN')) {
    die('Truy cập không hợp lệ');
}

/**
 * Tính tổng điểm từ mảng điểm các tiêu chí.
 *
 * @param float[] $scores Mảng điểm tùy ý (cho phép float)
 * @return float
 */
function score_calc_total(array $scores): float
{
    return (float) array_sum(array_filter($scores, fn($s) => $s !== null));
}

/**
 * Tính % độ lệch giữa các điểm: (max − min) / avg × 100.
 * Trả về 0.0 nếu chưa đủ 2 điểm hoặc avg = 0.
 *
 * @param float[] $scores
 * @return float
 */
function score_calc_deviation_percent(array $scores): float
{
    $nonNull = array_values(array_filter($scores, fn($s) => $s !== null && $s !== false));
    if (count($nonNull) < 2) {
        return 0.0;
    }
    $avg = array_sum($nonNull) / count($nonNull);
    if ($avg <= 0) {
        return 0.0;
    }
    return ((max($nonNull) - min($nonNull)) / $avg) * 100.0;
}

/**
 * Xây dựng ma trận điểm (criterion × judge).
 *
 * @param array $chiTietByJudge Mảng dữ liệu theo định dạng của cham_diem_lay_chi_tiet_diem():
 *   [{ idGV, tenGV, tongDiem, chiTiet: [{ idTieuChi, noiDungTieuChi, diem, diemToiDa, nhanXet }] }]
 *
 * @return array {
 *   judges: [{ idGV, tenGV, tongDiem }],
 *   criteria: [{
 *     idTieuChi, name, diemToiDa,
 *     scoresByGV: { idGV => diem },
 *     commentsByGV: { idGV => nhanXet },
 *     avg, deviationPct, isHighDeviation
 *   }]
 * }
 */
function score_build_scoring_matrix(array $chiTietByJudge): array
{
    $judges = array_map(fn($gk) => [
        'idGV'      => $gk['idGV'],
        'tenGV'     => $gk['tenGV'],
        'tongDiem'  => isset($gk['tongDiem'])
            ? (float) $gk['tongDiem']
            : score_calc_total(array_column($gk['chiTiet'] ?? [], 'diem')),
    ], $chiTietByJudge);

    // Collect per-criterion data from all judges
    $criteriaMap = [];
    foreach ($chiTietByJudge as $gk) {
        foreach ($gk['chiTiet'] ?? [] as $tc) {
            $id = $tc['idTieuChi'];
            if (!isset($criteriaMap[$id])) {
                $criteriaMap[$id] = [
                    'idTieuChi'    => $id,
                    'name'         => $tc['noiDungTieuChi'],
                    'diemToiDa'    => (float) ($tc['diemToiDa'] ?? 10),
                    'scoresByGV'   => [],
                    'commentsByGV' => [],
                ];
            }
            $criteriaMap[$id]['scoresByGV'][$gk['idGV']]   = (float) $tc['diem'];
            $criteriaMap[$id]['commentsByGV'][$gk['idGV']] = $tc['nhanXet'] ?? null;
        }
    }

    // Calculate deviation stats per criterion
    $criteria = array_values(array_map(function ($tc) {
        $scores    = array_values($tc['scoresByGV']);
        $avg       = count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
        $devPct    = score_calc_deviation_percent($scores);
        return array_merge($tc, [
            'avg'              => round($avg, 2),
            'deviationPct'     => round($devPct, 1),
            'isHighDeviation'  => $devPct > 30.0,
        ]);
    }, $criteriaMap));

    return [
        'judges'   => $judges,
        'criteria' => $criteria,
    ];
}

/**
 * Tính chỉ số chất lượng giám khảo (Judge Quality Index).
 *
 * Với mỗi GK, tính:
 * - biasScore: Trung bình chênh lệch so với điểm TB nhóm (+ = lenient, − = strict)
 * - consistencyScore: 0-100 (100 = hoàn toàn đồng đều với nhóm)
 * - qualityLevel: 'ok' | 'warning' | 'outlier'
 *
 * @param array $matrix Kết quả từ score_build_scoring_matrix()
 * @return array [{ idGV, tenGV, tongDiem, biasScore, consistencyScore,
 *                  aboveAvgCount, belowAvgCount, qualityLevel }]
 */
function score_calc_judge_quality(array $matrix): array
{
    $judges   = $matrix['judges'];
    $criteria = $matrix['criteria'];
    $result   = [];

    foreach ($judges as $judge) {
        $idGV          = $judge['idGV'];
        $diffs         = [];
        $aboveAvgCount = 0;
        $belowAvgCount = 0;

        foreach ($criteria as $tc) {
            $judgeScore = $tc['scoresByGV'][$idGV] ?? null;
            if ($judgeScore === null) {
                continue;
            }
            $diff = $judgeScore - $tc['avg'];
            $diffs[] = $diff;
            if ($diff > 0.001) {
                $aboveAvgCount++;
            } elseif ($diff < -0.001) {
                $belowAvgCount++;
            }
        }

        if (empty($diffs)) {
            $biasScore        = 0.0;
            $consistencyScore = 100.0;
            $qualityLevel     = 'ok';
        } else {
            $biasScore = array_sum($diffs) / count($diffs);
            $variance  = array_sum(array_map(fn($d) => $d * $d, $diffs)) / count($diffs);
            // consistencyScore: starts at 100, penalised by variance
            $consistencyScore = round(max(0.0, 100.0 - $variance * 10.0), 1);

            $absAvgBias = abs($biasScore);
            if ($absAvgBias > 2.0 || $consistencyScore < 50) {
                $qualityLevel = 'outlier';
            } elseif ($absAvgBias > 1.0 || $consistencyScore < 70) {
                $qualityLevel = 'warning';
            } else {
                $qualityLevel = 'ok';
            }
        }

        $result[] = [
            'idGV'             => $idGV,
            'tenGV'            => $judge['tenGV'],
            'tongDiem'         => $judge['tongDiem'],
            'biasScore'        => round($biasScore, 2),
            'consistencyScore' => $consistencyScore,
            'aboveAvgCount'    => $aboveAvgCount,
            'belowAvgCount'    => $belowAvgCount,
            'qualityLevel'     => $qualityLevel,
        ];
    }

    return $result;
}
