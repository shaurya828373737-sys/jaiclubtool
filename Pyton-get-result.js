/**
 * Pyton-get-result.js
 * Front-end logic:
 *  - Collects form data
 *  - Shows loading dialog
 *  - POSTs to api.php
 *  - Renders prediction result
 */

(function () {
    'use strict';

    // ── DOM references ────────────────────────────────────────────────────────
    const form           = document.getElementById('trendForm');
    const btnPredict     = document.getElementById('btnPredict');
    const overlay        = document.getElementById('loadingOverlay');
    const errorMsg       = document.getElementById('errorMsg');
    const resultBox      = document.getElementById('resultBox');

    // Result fields
    const resTrendId     = document.getElementById('resTrendId');
    const resNumber      = document.getElementById('resNumber');
    const resColor       = document.getElementById('resColor');
    const resSize        = document.getElementById('resSize');
    const confPct        = document.getElementById('confPct');
    const confBar        = document.getElementById('confBar');
    const analysisTbody  = document.getElementById('analysisTbody');

    // ── API endpoint ──────────────────────────────────────────────────────────
    const API_URL = 'api.php';

    // Minimum simulated loading time (ms) — gives the dialog time to be seen
    const MIN_LOADING_MS = 2800;

    // ── Form submit ───────────────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearError();
        hideResult();

        const payload = collectFormData();
        if (!payload) return;   // validation error already shown

        showLoading();

        const startTime = Date.now();

        fetchPrediction(payload)
            .then(function (data) {
                const elapsed   = Date.now() - startTime;
                const remaining = Math.max(0, MIN_LOADING_MS - elapsed);

                // Ensure loading dialog shows for at least MIN_LOADING_MS
                setTimeout(function () {
                    hideLoading();
                    if (data.success) {
                        renderResult(data);
                    } else {
                        showError(data.error || 'An unexpected error occurred.');
                    }
                }, remaining);
            })
            .catch(function (err) {
                const elapsed   = Date.now() - startTime;
                const remaining = Math.max(0, MIN_LOADING_MS - elapsed);
                setTimeout(function () {
                    hideLoading();
                    showError('Network error: ' + err.message);
                }, remaining);
            });
    });

    // ── Collect & validate form data ──────────────────────────────────────────
    function collectFormData() {
        const trendIds = document.querySelectorAll('.trend-id');
        const numbers  = document.querySelectorAll('.trend-number');
        const colors   = document.querySelectorAll('.trend-color');
        const sizes    = document.querySelectorAll('.trend-size');

        const trends = [];
        const seenIds = new Set();

        for (let i = 0; i < trendIds.length; i++) {
            const rowNum  = i + 1;
            const id      = trendIds[i].value.trim();
            const num     = numbers[i].value.trim();
            const color   = colors[i].value.trim();
            const size    = sizes[i].value.trim();

            // All fields required
            if (!id || !num || !color || !size) {
                showError('Row ' + rowNum + ': All fields are required.');
                trendIds[i].focus();
                return null;
            }

            // Trend ID format: 6-12 digits
            if (!/^\d{6,12}$/.test(id)) {
                showError('Row ' + rowNum + ': Trend ID must be a 6-12 digit number (e.g. 504312612).');
                trendIds[i].focus();
                return null;
            }

            // Unique IDs
            if (seenIds.has(id)) {
                showError('Row ' + rowNum + ': Duplicate Trend ID "' + id + '" found. Each ID must be unique.');
                trendIds[i].focus();
                return null;
            }
            seenIds.add(id);

            // Number range
            const numInt = parseInt(num, 10);
            if (isNaN(numInt) || numInt < 0 || numInt > 99) {
                showError('Row ' + rowNum + ': Number must be between 0 and 99.');
                numbers[i].focus();
                return null;
            }

            trends.push({ trend_id: id, number: numInt, color: color, size: size });
        }

        return { trends: trends };
    }

    // ── API call ──────────────────────────────────────────────────────────────
    function fetchPrediction(payload) {
        return fetch(API_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        }).then(function (res) {
            if (!res.ok) {
                return res.json().then(function (d) {
                    throw new Error(d.error || 'HTTP ' + res.status);
                });
            }
            return res.json();
        });
    }

    // ── Render result ─────────────────────────────────────────────────────────
    function renderResult(data) {
        const pred = data.prediction;
        const meta = data.meta || {};

        // Main result fields
        resTrendId.textContent = pred.predicted_id     || '—';
        resNumber.textContent  = pred.predicted_number !== undefined ? pred.predicted_number : '—';
        resColor.textContent   = pred.predicted_color  || '—';
        resSize.textContent    = pred.predicted_size   || '—';

        // Color the size badge
        resSize.className = 'value';

        // Confidence bar
        const conf = parseFloat(pred.confidence) || 0;
        confPct.textContent      = conf.toFixed(1) + '%';
        confBar.style.width      = '0%';
        setTimeout(function () {
            confBar.style.width  = conf + '%';
        }, 60);

        // Analysis breakdown rows
        analysisTbody.innerHTML = '';
        const signals = pred.signals || {};

        const rows = [
            {
                field:  'Number',
                value:  pred.predicted_number,
                conf:   (signals.number || {}).confidence,
                method: (signals.number || {}).method
            },
            {
                field:  'Color',
                value:  pred.predicted_color,
                conf:   (signals.color || {}).confidence,
                method: (signals.color || {}).method
            },
            {
                field:  'Size',
                value:  pred.predicted_size,
                conf:   (signals.size || {}).confidence,
                method: (signals.size || {}).method
            },
        ];

        rows.forEach(function (r) {
            const tr   = document.createElement('tr');
            const conf = parseFloat(r.conf) || 0;
            tr.innerHTML =
                '<td><strong>' + escHtml(r.field) + '</strong></td>' +
                '<td>' + renderBadge(r.field, r.value) + '</td>' +
                '<td>' +
                    '<div class="conf-bar-bg" style="min-width:100px">' +
                        '<div class="conf-bar-fill" style="width:' + conf + '%"></div>' +
                    '</div>' +
                    '<small style="color:#555">' + conf.toFixed(1) + '%</small>' +
                '</td>' +
                '<td style="font-size:12px;color:#666">' + escHtml(r.method || '') + '</td>';
            analysisTbody.appendChild(tr);
        });

        // Meta info row
        if (meta.number_stats) {
            const stats = meta.number_stats;
            const tr = document.createElement('tr');
            tr.style.background = '#f9f9f9';
            tr.innerHTML =
                '<td colspan="4" style="font-size:12px;color:#666;padding:8px">' +
                '📈 <strong>Dataset stats</strong> — ' +
                'Avg: ' + stats.average + ' | Min: ' + stats.min + ' | Max: ' + stats.max +
                ' | Dominant color: <strong>' + escHtml(meta.dominant_color || '') + '</strong>' +
                ' | Dominant size: <strong>' + escHtml(meta.dominant_size || '') + '</strong>' +
                ' | Analyzed: ' + (meta.records_analysed || 0) + ' records' +
                '</td>';
            analysisTbody.appendChild(tr);
        }

        resultBox.classList.add('visible');
        resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Badge renderer ────────────────────────────────────────────────────────
    function renderBadge(field, value) {
        if (!value && value !== 0) return '—';
        const v = String(value);

        if (field === 'Color') {
            const colorMap = {
                red: 'badge-red', green: 'badge-green', blue: 'badge-blue',
                violet: 'badge-blue', orange: 'badge-big', yellow: 'badge-small',
                purple: 'badge-blue', pink: 'badge-red', black: 'badge-big', white: 'badge-small'
            };
            const cls = colorMap[v.toLowerCase()] || 'badge-blue';
            return '<span class="badge ' + cls + '">' + escHtml(v) + '</span>';
        }

        if (field === 'Size') {
            const cls = v.toUpperCase() === 'MS' ? 'badge-small' : 'badge-big';
            return '<span class="badge ' + cls + '">' + escHtml(v) + '</span>';
        }

        return '<strong>' + escHtml(v) + '</strong>';
    }

    // ── UI helpers ────────────────────────────────────────────────────────────
    function showLoading() {
        overlay.classList.add('active');
        btnPredict.disabled = true;
    }

    function hideLoading() {
        overlay.classList.remove('active');
        btnPredict.disabled = false;
    }

    function showError(msg) {
        errorMsg.textContent = '⚠ ' + msg;
        errorMsg.classList.add('visible');
        errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearError() {
        errorMsg.textContent = '';
        errorMsg.classList.remove('visible');
    }

    function hideResult() {
        resultBox.classList.remove('visible');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
