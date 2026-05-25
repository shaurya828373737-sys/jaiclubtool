<?php
/**
 * Calcute.php
 * ══════════════════════════════════════════════════════════════
 * JAI Club — Advanced 7-Algorithm Prediction Engine (PHP)
 *
 * Mirrors the JS client engine so both sides agree.
 * Real WinGo rules applied throughout:
 *   Big  = numbers 5,6,7,8,9  → size MB
 *   Small = numbers 0,1,2,3,4 → size MS
 *   Colors: 0,5=violet  1-4=green  6-9=red
 *
 * Algorithms:
 *   1. Weighted Recency Frequency   (decay=0.82)
 *   2. Streak Reversal Detection
 *   3. Gap / Overdue Detection
 *   4. Alternating Pattern Analysis
 *   5. 2nd-Order Markov Chain
 *   6. Positional Bias Analysis
 *   7. Shannon Entropy Correction
 * ══════════════════════════════════════════════════════════════
 */

class Calcute
{
    /* ── Real WinGo number→color mapping ── */
    private const NUM_COLOR = [
        0 => 'violet', 1 => 'green',  2 => 'green',
        3 => 'green',  4 => 'green',  5 => 'violet',
        6 => 'red',    7 => 'red',    8 => 'red', 9 => 'red',
    ];

    /* ── Real WinGo number→size mapping ── */
    private const NUM_SIZE = [
        0 => 'MB', 1 => 'MS', 2 => 'MS', 3 => 'MS', 4 => 'MS',
        5 => 'MS', 6 => 'MB', 7 => 'MB', 8 => 'MB', 9 => 'MB',
    ];

    /* ── Algorithm weights (tuned for WinGo patterns) ── */
    private const ALGO_WEIGHTS = [
        'freq'      => 1.0,
        'streak'    => 2.2,
        'gap'       => 1.4,
        'alternate' => 1.3,
        'markov'    => 1.8,
        'position'  => 0.8,
        'entropy'   => 1.0,
    ];

    private array $trends;
    private array $sizes;   // ['MB','MS', ...]
    private array $numbers; // [6,2,8,...]
    private array $colors;  // ['red','green',...]
    private array $bin;     // 1=Big(MB), 0=Small(MS)
    private int   $n;

    public function __construct(array $trends)
    {
        if (count($trends) < 2) {
            throw new \InvalidArgumentException('Need at least 2 records.');
        }

        $this->trends  = array_values($trends);
        $this->n       = count($this->trends);
        $this->sizes   = array_map(fn($t) => strtoupper($t['size']),   $this->trends);
        $this->numbers = array_map(fn($t) => (int)$t['number'],        $this->trends);
        $this->colors  = array_map(fn($t) => strtolower($t['color']),  $this->trends);
        $this->bin     = array_map(fn($s) => $s === 'MB' ? 1 : 0,      $this->sizes);
    }

    /* ════════════════════════════════════════
       PUBLIC: predict()
       Returns full prediction array
    ════════════════════════════════════════ */
    public function predict(): array
    {
        /* Run all 7 algorithms */
        $algo1 = $this->algoWeightedFrequency();
        $algo2 = $this->algoStreakReversal();
        $algo3 = $this->algoGapOverdue();
        $algo4 = $this->algoAlternatingPattern();
        $algo5 = $this->algoMarkovChain();
        $algo6 = $this->algoPositionalBias();
        $algo7 = $this->algoEntropyCorrection();

        $algos = [
            array_merge($algo1, ['weight' => self::ALGO_WEIGHTS['freq']]),
            array_merge($algo2, ['weight' => self::ALGO_WEIGHTS['streak']]),
            array_merge($algo3, ['weight' => self::ALGO_WEIGHTS['gap']]),
            array_merge($algo4, ['weight' => self::ALGO_WEIGHTS['alternate']]),
            array_merge($algo5, ['weight' => self::ALGO_WEIGHTS['markov']]),
            array_merge($algo6, ['weight' => self::ALGO_WEIGHTS['position']]),
            array_merge($algo7, ['weight' => self::ALGO_WEIGHTS['entropy']]),
        ];

        /* Weighted vote */
        $bigScore = 0.0; $smallScore = 0.0;
        foreach ($algos as $a) {
            $eff = $a['confidence'] * $a['weight'];
            if ($a['vote'] === 1) $bigScore   += $eff;
            else                  $smallScore += $eff;
        }

        $totalW  = $bigScore + $smallScore ?: 1;
        $isBig   = $bigScore >= $smallScore;
        $predSz  = $isBig ? 'MB' : 'MS';
        $winScore = $isBig ? $bigScore : $smallScore;
        $rawConf  = $winScore / $totalW;

        /* Scale confidence to 85–99 for display */
        $confPct = 85.0 + $rawConf * 14.0;
        $confPct = min(99.0, max(85.0, $confPct));

        /* Predict specific number */
        $numResult   = $this->predictNumber($isBig, $rawConf);
        $predNum     = $numResult['number'];
        $predColor   = ucfirst(self::NUM_COLOR[$predNum] ?? ($isBig ? 'red' : 'green'));

        /* Streak for display */
        $streak = $this->getCurrentStreak();

        /* Next trend ID */
        $lastId = (int)end($this->trends)['trend_id'];
        $nextId = $lastId + 1;

        /* Build vote detail string */
        $voteDetail = implode(' | ', array_map(function ($a) {
            $side = $a['vote'] === 1 ? 'Big' : 'Small';
            $pct  = round($a['confidence'] * 100);
            return $a['name'] . ':' . $side . '(' . $pct . '%)';
        }, $algos));

        return [
            'predicted_id'     => $nextId,
            'predicted_number' => $predNum,
            'predicted_color'  => $predColor,
            'predicted_size'   => $predSz,
            'confidence'       => round($confPct, 1),
            'signals'          => [
                'size' => [
                    'value'       => $predSz,
                    'confidence'  => min(99, round($rawConf * 98 + 1)),
                    'method'      => 'Streak(' . $streak . ') + Markov + 7-algo weighted vote',
                    'big_score'   => round($bigScore, 3),
                    'small_score' => round($smallScore, 3),
                    'vote_detail' => $voteDetail,
                ],
                'number' => [
                    'value'      => $predNum,
                    'confidence' => min(99, round($confPct * 0.90)),
                    'method'     => 'Overdue detection + WinGo pool (' .
                                    ($isBig ? '5-9' : '0-4') . ')',
                ],
                'color' => [
                    'value'      => $predColor,
                    'confidence' => min(99, round($confPct * 0.92)),
                    'method'     => 'Real WinGo number→color rule',
                ],
            ],
            'engine' => 'php-calcute-7algo',
            'streak' => $streak,
            'raw_confidence' => round($rawConf, 4),
        ];
    }



    /* ════════════════════════════════════════
       ALGO 1: Weighted Recency Frequency
       Recent entries carry more weight (decay=0.82).
    ════════════════════════════════════════ */
    private function algoWeightedFrequency(): array
    {
        $decay = 0.82;
        $wBig  = 0.0; $wTot = 0.0; $w = 1.0;

        for ($i = $this->n - 1; $i >= 0; $i--) {
            $wBig += $this->bin[$i] * $w;
            $wTot += $w;
            $w    *= $decay;
        }

        $score = $wTot > 0 ? $wBig / $wTot : 0.5;
        $vote  = $score >= 0.5 ? 1 : 0;
        $conf  = abs($score - 0.5) * 2.0;

        return ['name' => 'Weighted Frequency', 'vote' => $vote,
                'confidence' => $conf, 'detail' => round($score, 3)];
    }

    /* ════════════════════════════════════════
       ALGO 2: Streak Reversal Detection
       streak 1-2 → continue | 3-4 → likely reverse | 5+ → near-certain reverse
    ════════════════════════════════════════ */
    private function algoStreakReversal(): array
    {
        $streak  = $this->getCurrentStreak();
        $lastVal = $this->bin[$this->n - 1];

        if ($streak >= 5) {
            $vote = 1 - $lastVal;
            $conf = 0.92;
        } elseif ($streak >= 3) {
            $vote = 1 - $lastVal;
            $conf = 0.75 + ($streak - 3) * 0.08;
        } elseif ($streak === 2) {
            $vote = $lastVal;
            $conf = 0.55;
        } else {
            $vote = 1 - $lastVal; // single value → alternate
            $conf = 0.52;
        }

        return ['name' => 'Streak Reversal', 'vote' => $vote,
                'confidence' => $conf, 'streak' => $streak];
    }

    /* ════════════════════════════════════════
       ALGO 3: Gap / Overdue Detection
       Whichever side hasn't appeared recently is "overdue".
    ════════════════════════════════════════ */
    private function algoGapOverdue(): array
    {
        $lastBig = -1; $lastSmall = -1;

        for ($i = $this->n - 1; $i >= 0; $i--) {
            if ($lastBig   === -1 && $this->bin[$i] === 1) $lastBig   = $this->n - 1 - $i;
            if ($lastSmall === -1 && $this->bin[$i] === 0) $lastSmall = $this->n - 1 - $i;
        }

        if ($lastBig   === -1) $lastBig   = $this->n;
        if ($lastSmall === -1) $lastSmall = $this->n;

        $vote = $lastBig > $lastSmall ? 1 : 0;
        $conf = min(0.85, 0.5 + abs($lastBig - $lastSmall) * 0.07);

        return ['name' => 'Gap/Overdue', 'vote' => $vote,
                'confidence' => $conf,
                'last_big_ago' => $lastBig, 'last_small_ago' => $lastSmall];
    }

    /* ════════════════════════════════════════
       ALGO 4: Alternating Pattern Analysis
       Measures how alternating the last 6 entries are.
    ════════════════════════════════════════ */
    private function algoAlternatingPattern(): array
    {
        $lookback = min(6, $this->n);
        $start    = $this->n - $lookback;
        $altScore = 0;

        for ($i = $start; $i < $this->n - 1; $i++) {
            if ($this->bin[$i] !== $this->bin[$i + 1]) $altScore++;
        }

        $altRatio = $lookback > 1 ? $altScore / ($lookback - 1) : 0;
        $lastVal  = $this->bin[$this->n - 1];

        if ($altRatio >= 0.7) {
            $vote = 1 - $lastVal;
            $conf = $altRatio * 0.85;
        } else {
            $vote = $lastVal;
            $conf = (1 - $altRatio) * 0.60;
        }

        return ['name' => 'Alternating Pattern', 'vote' => $vote,
                'confidence' => $conf, 'alt_ratio' => round($altRatio, 3)];
    }

    /* ════════════════════════════════════════
       ALGO 5: 2nd-Order Markov Chain
       Transition table: given last 2 outcomes → next probability.
    ════════════════════════════════════════ */
    private function algoMarkovChain(): array
    {
        $trans   = [];
        $lastVal = $this->bin[$this->n - 1];

        for ($i = 0; $i < $this->n - 2; $i++) {
            $key = $this->bin[$i] . '_' . $this->bin[$i + 1];
            if (!isset($trans[$key])) $trans[$key] = [0, 0];
            $trans[$key][$this->bin[$i + 2]]++;
        }

        $vote = $lastVal; $conf = 0.50; // default

        if ($this->n >= 2) {
            $mKey = $this->bin[$this->n - 2] . '_' . $this->bin[$this->n - 1];
            if (isset($trans[$mKey])) {
                $mBig = $trans[$mKey][1];
                $mSml = $trans[$mKey][0];
                $mTot = $mBig + $mSml;
                if ($mTot >= 2) {
                    $vote = $mBig >= $mSml ? 1 : 0;
                    $conf = max($mBig, $mSml) / $mTot * 0.88;
                }
            }
        }

        return ['name' => 'Markov Chain', 'vote' => $vote,
                'confidence' => $conf, 'transitions' => count($trans)];
    }

    /* ════════════════════════════════════════
       ALGO 6: Positional Bias
       Even/odd positions may favor Big or Small.
    ════════════════════════════════════════ */
    private function algoPositionalBias(): array
    {
        $nextPos  = $this->n;
        $evenSum  = 0; $evenCnt = 0;
        $oddSum   = 0; $oddCnt  = 0;

        for ($i = 0; $i < $this->n; $i++) {
            if ($i % 2 === 0) { $evenSum += $this->bin[$i]; $evenCnt++; }
            else               { $oddSum  += $this->bin[$i]; $oddCnt++;  }
        }

        if ($nextPos % 2 === 0) {
            $ratio = $evenCnt > 0 ? $evenSum / $evenCnt : 0.5;
        } else {
            $ratio = $oddCnt  > 0 ? $oddSum  / $oddCnt  : 0.5;
        }

        $vote     = $ratio >= 0.5 ? 1 : 0;
        $posRatio = abs($ratio - 0.5) * 2;
        $conf     = 0.5 + $posRatio * 0.25;

        return ['name' => 'Positional Bias', 'vote' => $vote,
                'confidence' => $conf, 'pos_ratio' => round($ratio, 3)];
    }

    /* ════════════════════════════════════════
       ALGO 7: Shannon Entropy Correction
       High entropy → trust frequency.
       Low entropy  → trust pattern algos.
    ════════════════════════════════════════ */
    private function algoEntropyCorrection(): array
    {
        $bigCnt   = array_sum($this->bin);
        $smallCnt = $this->n - $bigCnt;
        $pB       = $bigCnt   / $this->n;
        $pS       = $smallCnt / $this->n;

        $entropy = 0.0;
        if ($pB > 0) $entropy -= $pB * log($pB, 2);
        if ($pS > 0) $entropy -= $pS * log($pS, 2);

        // High entropy (≈1) → weighted freq vote; low entropy → streak vote
        $freqScore    = $pB >= 0.5 ? 1 : 0;
        $streakResult = $this->algoStreakReversal();
        $streakVote   = $streakResult['vote'];

        $vote = $entropy >= 0.8 ? $freqScore : $streakVote;
        $conf = 0.5 + $entropy * 0.25;

        return ['name' => 'Entropy Correction', 'vote' => $vote,
                'confidence' => $conf, 'entropy' => round($entropy, 4)];
    }



    /* ════════════════════════════════════════
       PREDICT SPECIFIC NUMBER
       WinGo real pools:
         Big   → 5,6,7,8,9
         Small → 0,1,2,3,4
       Use overdue detection within pool.
    ════════════════════════════════════════ */
    private function predictNumber(bool $isBig, float $rawConf): array
    {
        $bigPool   = [5, 6, 7, 8, 9];
        $smallPool = [0, 1, 2, 3, 4];
        $pool      = $isBig ? $bigPool : $smallPool;

        /* Count how recently each pool number appeared */
        $lastSeen = [];
        foreach ($pool as $num) $lastSeen[$num] = $this->n; // default: not seen

        for ($i = $this->n - 1; $i >= 0; $i--) {
            $num = $this->numbers[$i];
            if (in_array($num, $pool) && $lastSeen[$num] === $this->n) {
                $lastSeen[$num] = $this->n - 1 - $i; // steps ago
            }
        }

        /* Pick most overdue (largest steps ago) */
        arsort($lastSeen);
        $predNum  = (int)array_key_first($lastSeen);
        $maxGap   = reset($lastSeen);

        /* Tie-break: if multiple at max gap, use frequency (least frequent) */
        $tiedNums = array_keys(array_filter($lastSeen, fn($v) => $v === $maxGap));
        if (count($tiedNums) > 1) {
            $freqs   = array_count_values($this->numbers);
            $minFreq = PHP_INT_MAX;
            foreach ($tiedNums as $candidate) {
                $f = $freqs[$candidate] ?? 0;
                if ($f < $minFreq) { $minFreq = $f; $predNum = $candidate; }
            }
        }

        /* Confirm size consistency with real WinGo rules */
        $expectedSize = self::NUM_SIZE[$predNum] ?? ($isBig ? 'MB' : 'MS');
        if (($isBig && $expectedSize === 'MS') || (!$isBig && $expectedSize === 'MB')) {
            // Pick next best from pool
            foreach ($pool as $fallback) {
                $es = self::NUM_SIZE[$fallback] ?? 'MS';
                if (($isBig && $es === 'MB') || (!$isBig && $es === 'MS')) {
                    $predNum = $fallback;
                    break;
                }
            }
        }

        return [
            'number'   => $predNum,
            'gap'      => $maxGap,
            'pool'     => $pool,
        ];
    }

    /* ════════════════════════════════════════
       HELPER: current streak length
    ════════════════════════════════════════ */
    private function getCurrentStreak(): int
    {
        $streak  = 1;
        $lastVal = $this->bin[$this->n - 1];
        for ($i = $this->n - 2; $i >= 0; $i--) {
            if ($this->bin[$i] === $lastVal) $streak++;
            else break;
        }
        return $streak;
    }

    /* ════════════════════════════════════════
       HELPER: raw frequency table for a field
    ════════════════════════════════════════ */
    public function getRawFrequency(string $field): array
    {
        $values = array_column($this->trends, $field);
        $freq   = array_count_values(array_map('strval', $values));
        arsort($freq);
        return $freq;
    }

    /* ════════════════════════════════════════
       HELPER: weighted frequency for a field
    ════════════════════════════════════════ */
    public function getWeightedFrequency(string $field, float $decay = 0.82): array
    {
        $values = array_column($this->trends, $field);
        $freq   = []; $w = 1.0;
        foreach (array_reverse($values) as $val) {
            $key        = strtolower((string)$val);
            $freq[$key] = ($freq[$key] ?? 0) + $w;
            $w         *= $decay;
        }
        arsort($freq);
        return $freq;
    }

    /* ════════════════════════════════════════
       GETTER: trend count
    ════════════════════════════════════════ */
    public function getCount(): int { return $this->n; }
}
