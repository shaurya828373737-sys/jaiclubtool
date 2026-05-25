<?php
/**
 * Calcute.php
 * Core prediction / calculation engine.
 *
 * Algorithm overview (simulates a "Python-style" statistical backend):
 *  1. Frequency analysis  – which number / color / size appears most often
 *  2. Weighted recency    – recent records carry more weight
 *  3. Gap detection       – numbers that are "overdue" get a bonus
 *  4. Pattern matching    – alternating / sequential patterns
 *  5. Confidence score    – derived from how strongly signals agree
 */

class Calcute
{
    /** @var array Raw sanitised trend records */
    private array $trends;

    /** @var int Total records supplied */
    private int $count;

    // Weight constants
    private const RECENCY_DECAY   = 0.85;  // weight multiplier per step back in history
    private const COLOR_WEIGHT    = 0.35;
    private const NUMBER_WEIGHT   = 0.40;
    private const SIZE_WEIGHT     = 0.25;

    public function __construct(array $trends)
    {
        $this->trends = array_values($trends);
        $this->count  = count($this->trends);

        if ($this->count < 2) {
            throw new \InvalidArgumentException('At least 2 trend records are required.');
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Public
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Run the full prediction pipeline.
     *
     * @return array {
     *   predicted_id, predicted_number, predicted_color,
     *   predicted_size, confidence, signals
     * }
     */
    public function predict(): array
    {
        $numberPred  = $this->predictNumber();
        $colorPred   = $this->predictColor();
        $sizePred    = $this->predictSize();

        // Derive next Trend ID (increment last by 1)
        $lastId      = (int) end($this->trends)['trend_id'];
        $nextId      = $lastId + 1;

        // Composite confidence
        $confidence  = $this->computeConfidence(
            $numberPred['confidence'],
            $colorPred['confidence'],
            $sizePred['confidence']
        );

        return [
            'predicted_id'     => $nextId,
            'predicted_number' => $numberPred['value'],
            'predicted_color'  => $colorPred['value'],
            'predicted_size'   => $sizePred['value'],
            'confidence'       => round($confidence, 2),
            'signals'          => [
                'number' => $numberPred,
                'color'  => $colorPred,
                'size'   => $sizePred,
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Number prediction
    // ────────────────────────────────────────────────────────────────────────

    private function predictNumber(): array
    {
        $numbers = array_column($this->trends, 'number');
        $numbers = array_map('intval', $numbers);

        // 1. Weighted frequency
        $weightedFreq = $this->weightedFrequency($numbers);

        // 2. Gap / overdue detection
        $allNums     = range(min($numbers), max($numbers));
        $gapScores   = [];
        foreach ($allNums as $n) {
            $lastSeen = -1;
            foreach (array_reverse($numbers, true) as $idx => $val) {
                if ($val === $n) { $lastSeen = $idx; break; }
            }
            $gapScores[$n] = ($lastSeen === -1) ? $this->count * 2 : ($this->count - 1 - $lastSeen);
        }

        // 3. Sequential pattern bonus
        $last = end($numbers);
        $seqBonus = [];
        $seqBonus[$last + 1] = 1.5;
        $seqBonus[$last - 1] = 1.0;

        // Combine scores
        $combined = [];
        foreach ($weightedFreq as $num => $wf) {
            $gap   = $gapScores[$num]   ?? 0;
            $seq   = $seqBonus[$num]    ?? 0;
            $combined[$num] = ($wf * self::NUMBER_WEIGHT)
                            + ($gap  * 0.4)
                            + ($seq  * 0.2);
        }
        // Include seq candidates not in weightedFreq
        foreach ($seqBonus as $num => $bonus) {
            if (!isset($combined[$num])) {
                $combined[$num] = $bonus * 0.2
                                + ($gapScores[$num] ?? $this->count) * 0.4;
            }
        }

        arsort($combined);
        $predicted   = (int) array_key_first($combined);
        $topScore    = reset($combined);
        $totalScore  = array_sum($combined) ?: 1;
        $confidence  = min(99, round(($topScore / $totalScore) * 100 * 2.5, 1));

        return [
            'value'      => $predicted,
            'confidence' => $confidence,
            'method'     => 'weighted-frequency + gap-detection + sequential',
            'frequency'  => $this->rawFrequency($numbers),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Color prediction
    // ────────────────────────────────────────────────────────────────────────

    private function predictColor(): array
    {
        $colors      = array_column($this->trends, 'color');
        $colors      = array_map('strtolower', $colors);

        $weightedFreq = $this->weightedFrequency($colors);

        // Alternating pattern: if last two are same color, other color gets bonus
        $altBonus = [];
        if ($this->count >= 2) {
            $last    = $colors[$this->count - 1];
            $secondL = $colors[$this->count - 2];
            if ($last === $secondL) {
                foreach (array_unique($colors) as $c) {
                    if ($c !== $last) {
                        $altBonus[$c] = ($altBonus[$c] ?? 0) + 2.0;
                    }
                }
            } else {
                // Continuation bonus
                $altBonus[$last] = ($altBonus[$last] ?? 0) + 1.0;
            }
        }

        $combined = [];
        foreach ($weightedFreq as $color => $wf) {
            $combined[$color] = $wf + ($altBonus[$color] ?? 0);
        }
        foreach ($altBonus as $color => $bonus) {
            if (!isset($combined[$color])) {
                $combined[$color] = $bonus;
            }
        }

        arsort($combined);
        $predicted  = (string) array_key_first($combined);
        $topScore   = reset($combined);
        $totalScore = array_sum($combined) ?: 1;
        $confidence = min(99, round(($topScore / $totalScore) * 100 * 2.0, 1));

        return [
            'value'      => ucfirst($predicted),
            'confidence' => $confidence,
            'method'     => 'weighted-frequency + alternating-pattern',
            'frequency'  => $this->rawFrequency($colors),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Size prediction
    // ────────────────────────────────────────────────────────────────────────

    private function predictSize(): array
    {
        $sizes       = array_column($this->trends, 'size');
        $sizes       = array_map('strtoupper', $sizes);

        $weightedFreq = $this->weightedFrequency($sizes);

        // Streak detection: if same size 3+ times in a row → flip
        $streak      = 1;
        $lastSize    = end($sizes);
        for ($i = $this->count - 2; $i >= 0; $i--) {
            if ($sizes[$i] === $lastSize) { $streak++; } else { break; }
        }

        if ($streak >= 3) {
            // Penalise current, boost opposite
            $opposite = ($lastSize === 'MS') ? 'MB' : 'MS';
            $weightedFreq[$lastSize]   = ($weightedFreq[$lastSize]   ?? 0) * 0.3;
            $weightedFreq[$opposite]   = ($weightedFreq[$opposite]   ?? 0) + 3.0;
        }

        arsort($weightedFreq);
        $predicted  = (string) array_key_first($weightedFreq);
        $topScore   = reset($weightedFreq);
        $totalScore = array_sum($weightedFreq) ?: 1;
        $confidence = min(99, round(($topScore / $totalScore) * 100 * 1.8, 1));

        return [
            'value'      => $predicted,
            'confidence' => $confidence,
            'method'     => 'weighted-frequency + streak-reversal',
            'streak'     => $streak,
            'frequency'  => $this->rawFrequency($sizes),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Compute weighted frequency — recent records count more.
     */
    private function weightedFrequency(array $values): array
    {
        $freq   = [];
        $weight = 1.0;
        foreach (array_reverse($values) as $val) {
            $freq[$val] = ($freq[$val] ?? 0) + $weight;
            $weight    *= self::RECENCY_DECAY;
        }
        return $freq;
    }

    /**
     * Simple raw count frequency.
     */
    private function rawFrequency(array $values): array
    {
        $freq = array_count_values(array_map('strval', $values));
        arsort($freq);
        return $freq;
    }

    /**
     * Composite confidence from individual signal confidences.
     */
    private function computeConfidence(float $nc, float $cc, float $sc): float
    {
        return ($nc * self::NUMBER_WEIGHT)
             + ($cc * self::COLOR_WEIGHT)
             + ($sc * self::SIZE_WEIGHT);
    }
}
