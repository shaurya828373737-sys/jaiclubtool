<?php
/**
 * Support-backend.php
 * Backend support utilities:
 *   - Input sanitisation
 *   - Response metadata builder
 *   - Rate-limiting (file-based, lightweight)
 *   - Logging
 */

class SupportBackend
{
    // ── Config ───────────────────────────────────────────────────────────────
    private const LOG_FILE        = __DIR__ . '/logs/prediction.log';
    private const RATE_LIMIT_FILE = __DIR__ . '/logs/rate_limit.json';
    private const RATE_LIMIT_MAX  = 60;   // max requests per window
    private const RATE_LIMIT_WIN  = 60;   // window in seconds

    // ────────────────────────────────────────────────────────────────────────
    // Sanitisation
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Sanitise and normalise an array of trend records.
     *
     * @param  array $trends  Raw validated trends
     * @return array          Cleaned trends
     */
    public static function sanitiseTrends(array $trends): array
    {
        $clean = [];
        foreach ($trends as $t) {
            $clean[] = [
                'trend_id' => self::sanitiseId($t['trend_id']),
                'number'   => (int) filter_var($t['number'], FILTER_SANITIZE_NUMBER_INT),
                'color'    => self::sanitiseString($t['color']),
                'size'     => strtoupper(self::sanitiseString($t['size'])),
            ];
        }
        return $clean;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Metadata builder
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Build supplementary metadata to accompany the prediction response.
     *
     * @param  array $trends      Sanitised input trends
     * @param  array $prediction  Output from Calcute::predict()
     * @return array
     */
    public static function buildMeta(array $trends, array $prediction): array
    {
        $numbers = array_column($trends, 'number');
        $colors  = array_column($trends, 'color');
        $sizes   = array_column($trends, 'size');

        $colorFreq  = array_count_values(array_map('strtolower', $colors));
        $sizeFreq   = array_count_values(array_map('strtoupper', $sizes));

        arsort($colorFreq);
        arsort($sizeFreq);

        $dominantColor = ucfirst((string) array_key_first($colorFreq));
        $dominantSize  = (string) array_key_first($sizeFreq);

        // Streaks
        $colorStreak  = self::currentStreak($colors);
        $sizeStreak   = self::currentStreak($sizes);

        // Number stats
        $avg    = array_sum($numbers) / count($numbers);
        $min    = min($numbers);
        $max    = max($numbers);
        $range  = $max - $min;

        // Log the prediction
        self::logPrediction($trends, $prediction);

        return [
            'records_analysed' => count($trends),
            'dominant_color'   => $dominantColor,
            'dominant_size'    => $dominantSize,
            'color_frequency'  => $colorFreq,
            'size_frequency'   => $sizeFreq,
            'color_streak'     => $colorStreak,
            'size_streak'      => $sizeStreak,
            'number_stats'     => [
                'average' => round($avg, 2),
                'min'     => $min,
                'max'     => $max,
                'range'   => $range,
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Rate limiting
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Check whether the current client IP is within the rate limit.
     * Returns true if allowed, false if throttled.
     */
    public static function checkRateLimit(): bool
    {
        $ip  = self::getClientIp();
        $now = time();

        self::ensureLogDir();

        $data = [];
        if (file_exists(self::RATE_LIMIT_FILE)) {
            $raw  = file_get_contents(self::RATE_LIMIT_FILE);
            $data = json_decode($raw, true) ?? [];
        }

        // Clean old entries
        if (isset($data[$ip])) {
            $data[$ip] = array_filter($data[$ip], fn($ts) => ($now - $ts) < self::RATE_LIMIT_WIN);
            $data[$ip] = array_values($data[$ip]);
        }

        $count = count($data[$ip] ?? []);
        if ($count >= self::RATE_LIMIT_MAX) {
            return false;
        }

        $data[$ip][] = $now;
        file_put_contents(self::RATE_LIMIT_FILE, json_encode($data), LOCK_EX);
        return true;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Logging
    // ────────────────────────────────────────────────────────────────────────

    public static function logPrediction(array $trends, array $prediction): void
    {
        self::ensureLogDir();

        $entry = [
            'time'       => date('Y-m-d H:i:s'),
            'ip'         => self::getClientIp(),
            'input_rows' => count($trends),
            'predicted'  => [
                'id'     => $prediction['predicted_id']     ?? null,
                'number' => $prediction['predicted_number'] ?? null,
                'color'  => $prediction['predicted_color']  ?? null,
                'size'   => $prediction['predicted_size']   ?? null,
            ],
            'confidence' => $prediction['confidence'] ?? null,
        ];

        $line = json_encode($entry) . PHP_EOL;

        // Rotate log if > 2 MB
        if (file_exists(self::LOG_FILE) && filesize(self::LOG_FILE) > 2 * 1024 * 1024) {
            rename(self::LOG_FILE, self::LOG_FILE . '.' . date('Ymd_His') . '.bak');
        }

        file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }

    public static function logError(string $message): void
    {
        self::ensureLogDir();
        $line = json_encode(['time' => date('Y-m-d H:i:s'), 'error' => $message]) . PHP_EOL;
        file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────────────────

    private static function sanitiseId(string $id): string
    {
        return preg_replace('/[^0-9]/', '', $id);
    }

    private static function sanitiseString(string $val): string
    {
        return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
    }

    private static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return explode(',', $_SERVER[$key])[0];
            }
        }
        return '0.0.0.0';
    }

    /**
     * Count the current streak length of the last value in an array.
     */
    private static function currentStreak(array $values): array
    {
        if (empty($values)) return ['value' => null, 'length' => 0];
        $last   = end($values);
        $streak = 0;
        foreach (array_reverse($values) as $v) {
            if (strtolower((string)$v) === strtolower((string)$last)) { $streak++; }
            else { break; }
        }
        return ['value' => $last, 'length' => $streak];
    }

    private static function ensureLogDir(): void
    {
        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
