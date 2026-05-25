<?php
/**
 * Python-File.php
 * Main entry point — renders the full UI.
 * Include all required assets and build the 10-row input form.
 */

require_once 'Pythonhelp.php';

$allowedColors = PythonHelp::allowedColors();
$allowedSizes  = PythonHelp::allowedSizes();
$requiredRows  = PythonHelp::REQUIRED_RECORDS;

// Example seed IDs (user can overwrite)
$seedId = 504312610;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trend Prediction Tool</title>
    <link rel="stylesheet" href="help.css">
</head>
<body>

<!-- ── Header ── -->
<div class="header">
    🔮 Educational Trend Prediction Tool
</div>

<div class="container">

    <!-- ── Input card ── -->
    <div class="card">
        <h2>📊 Enter Last <?= $requiredRows ?> Trend Records</h2>

        <form id="trendForm" novalidate>
            <div style="overflow-x:auto;">
                <table class="input-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trend ID</th>
                            <th>Number (0–99)</th>
                            <th>Color</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php for ($i = 1; $i <= $requiredRows; $i++): ?>
                        <tr>
                            <td class="row-label"><?= $i ?></td>
                            <td>
                                <input
                                    type="text"
                                    name="trend_id[]"
                                    class="trend-id"
                                    placeholder="e.g. <?= $seedId + $i ?>"
                                    maxlength="12"
                                    required
                                    aria-label="Trend ID row <?= $i ?>"
                                >
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="number[]"
                                    class="trend-number"
                                    placeholder="0–99"
                                    min="0"
                                    max="99"
                                    required
                                    aria-label="Number row <?= $i ?>"
                                >
                            </td>
                            <td>
                                <select name="color[]" class="trend-color" required aria-label="Color row <?= $i ?>">
                                    <option value="">-- Color --</option>
                                    <?php foreach ($allowedColors as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= ucfirst($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="size[]" class="trend-size" required aria-label="Size row <?= $i ?>">
                                    <option value="">-- Size --</option>
                                    <?php foreach ($allowedSizes as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>"><?= $s === 'MS' ? 'MS (Small)' : 'MB (Big)' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Error message placeholder -->
            <div class="error-msg" id="errorMsg"></div>

            <button type="submit" class="btn-predict" id="btnPredict">
                🔍 Fetch result next
            </button>
        </form>
    </div>

    <!-- ── Result box (hidden until prediction returned) ── -->
    <div class="result-box" id="resultBox">
        <h2>✅ Predicted Next Trend</h2>

        <div class="result-grid">
            <div class="result-item highlight">
                <div class="label">Trend ID</div>
                <div class="value" id="resTrendId">—</div>
            </div>
            <div class="result-item">
                <div class="label">Number</div>
                <div class="value" id="resNumber">—</div>
            </div>
            <div class="result-item">
                <div class="label">Color</div>
                <div class="value" id="resColor">—</div>
            </div>
            <div class="result-item">
                <div class="label">Size</div>
                <div class="value" id="resSize">—</div>
            </div>
        </div>

        <!-- Confidence bar -->
        <div class="confidence-wrap">
            <div class="conf-label">Prediction Confidence: <strong id="confPct">0%</strong></div>
            <div class="conf-bar-bg">
                <div class="conf-bar-fill" id="confBar" style="width:0%"></div>
            </div>
        </div>

        <!-- Analysis breakdown table -->
        <table class="analysis-table" id="analysisTable">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Predicted</th>
                    <th>Signal Confidence</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody id="analysisTbody">
                <!-- Filled by JS -->
            </tbody>
        </table>
    </div>

</div><!-- /container -->

<!-- ── Loading overlay ── -->
<div class="overlay" id="loadingOverlay">
    <div class="dialog">
        <div class="spinner"></div>
        <p>Analyzing Python... Kindly wait, all our files are doing their work. To get a best and accurate result.</p>
    </div>
</div>

<div class="footer">Educational Trend Prediction Tool &mdash; For skill practice only.</div>

<!-- JS logic -->
<script src="Pyton-get-result.js"></script>

</body>
</html>
