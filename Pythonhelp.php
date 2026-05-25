<?php
/**
 * Pythonhelp.php
 * Validation helpers — mirrors the strict type-checking you would get
 * from a real Python (Pydantic / dataclass) backend.
 */

class PythonHelp
{
    // Allowed color values (case-insensitive comparison)
    private const ALLOWED_COLORS = ['red', 'green', 'blue', 'violet', 'orange', 'yellow', 'purple', 'pink', 'black', 'white'];

    // Allowed size tokens
    private const ALLOWED_SIZES  = ['MS', 'MB'];

    // Expected number of trend records
    public const REQUIRED_RECORDS = 10;

    // ────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Validate the full trends array.
     *
     * @param  array $trends
     * @return array { valid: bool, message: string }
     */
    public static function validateTrends(array $trends): array
    {
        if (count($trends) < 2) {
            return self::fail('At least 2 trend records are required for a prediction.');
        }

        if (count($trends) > self::REQUIRED_RECORDS) {
            return self::fail('Maximum ' . self::REQUIRED_RECORDS . ' trend records are accepted.');
        }

        foreach ($trends as $index => $record) {
            $rowNum = $index + 1;

            $check = self::validateSingleRecord($record, $rowNum);
            if (!$check['valid']) {
                return $check;
            }
        }

        // Ensure trend IDs are unique
        $ids = array_column($trends, 'trend_id');
        if (count($ids) !== count(array_unique($ids))) {
            return self::fail('Duplicate Trend IDs detected. Each Trend ID must be unique.');
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Validate a single trend record.
     *
     * @param  mixed $record
     * @param  int   $rowNum  1-based row number for error messages
     * @return array { valid: bool, message: string }
     */
    public static function validateSingleRecord($record, int $rowNum): array
    {
        if (!is_array($record)) {
            return self::fail("Row {$rowNum}: record must be an object/array.");
        }

        // ── trend_id ──────────────────────────────────────────────────────
        if (!isset($record['trend_id']) || trim((string)$record['trend_id']) === '') {
            return self::fail("Row {$rowNum}: 'trend_id' is required.");
        }
        if (!preg_match('/^\d{6,12}$/', (string)$record['trend_id'])) {
            return self::fail("Row {$rowNum}: 'trend_id' must be a 6-12 digit numeric ID (e.g. 504312612).");
        }

        // ── number ────────────────────────────────────────────────────────
        if (!isset($record['number']) || trim((string)$record['number']) === '') {
            return self::fail("Row {$rowNum}: 'number' is required.");
        }
        if (!is_numeric($record['number'])) {
            return self::fail("Row {$rowNum}: 'number' must be numeric.");
        }
        $num = (int)$record['number'];
        if ($num < 0 || $num > 99) {
            return self::fail("Row {$rowNum}: 'number' must be between 0 and 99.");
        }

        // ── color ─────────────────────────────────────────────────────────
        if (!isset($record['color']) || trim((string)$record['color']) === '') {
            return self::fail("Row {$rowNum}: 'color' is required.");
        }
        $colorLower = strtolower(trim((string)$record['color']));
        if (!in_array($colorLower, self::ALLOWED_COLORS, true)) {
            return self::fail(
                "Row {$rowNum}: 'color' must be one of: " . implode(', ', self::ALLOWED_COLORS) . "."
            );
        }

        // ── size ──────────────────────────────────────────────────────────
        if (!isset($record['size']) || trim((string)$record['size']) === '') {
            return self::fail("Row {$rowNum}: 'size' is required (MS or MB).");
        }
        $sizeUpper = strtoupper(trim((string)$record['size']));
        if (!in_array($sizeUpper, self::ALLOWED_SIZES, true)) {
            return self::fail("Row {$rowNum}: 'size' must be 'MS' (Small) or 'MB' (Big).");
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Utility helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Normalise a color string to title-case.
     */
    public static function normaliseColor(string $color): string
    {
        return ucfirst(strtolower(trim($color)));
    }

    /**
     * Normalise size to uppercase token.
     */
    public static function normaliseSize(string $size): string
    {
        return strtoupper(trim($size));
    }

    /**
     * Return allowed colors list.
     */
    public static function allowedColors(): array
    {
        return self::ALLOWED_COLORS;
    }

    /**
     * Return allowed sizes list.
     */
    public static function allowedSizes(): array
    {
        return self::ALLOWED_SIZES;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Private
    // ────────────────────────────────────────────────────────────────────────

    private static function fail(string $message): array
    {
        return ['valid' => false, 'message' => $message];
    }
}
