<?php
/**
 * api.php
 * Main API endpoint — receives POST data, delegates to calculation engine,
 * and returns a JSON prediction response.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

// ── Load dependencies ────────────────────────────────────────────────────────
require_once 'Pythonhelp.php';
require_once 'Calcute.php';
require_once 'Support-backend.php';

// ── Parse input ──────────────────────────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);

// Fallback: accept form-encoded data
if (!$data || !isset($data['trends'])) {
    $data = $_POST;
}

// Validate top-level structure
if (empty($data['trends']) || !is_array($data['trends'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing or invalid "trends" array in request body.'
    ]);
    exit();
}

$trends = $data['trends'];

// ── Validate each trend record ───────────────────────────────────────────────
$validationResult = PythonHelp::validateTrends($trends);
if (!$validationResult['valid']) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => $validationResult['message']
    ]);
    exit();
}

// ── Sanitise input ───────────────────────────────────────────────────────────
$sanitised = SupportBackend::sanitiseTrends($trends);

// ── Run prediction engine ────────────────────────────────────────────────────
try {
    $calculator  = new Calcute($sanitised);
    $prediction  = $calculator->predict();

    // Enrich with support metadata
    $meta        = SupportBackend::buildMeta($sanitised, $prediction);

    echo json_encode([
        'success'    => true,
        'prediction' => $prediction,
        'meta'       => $meta,
        'timestamp'  => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Prediction engine error: ' . $e->getMessage()
    ]);
}
