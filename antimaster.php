<?php
/**
 * antimaster.php
 * ──────────────────────────────────────────────────────────────
 * ANTI-MASTER LAYER — Forces the floating tool card to ALWAYS
 * render on top of everything, including cross-origin iframes.
 *
 * HOW IT WORKS:
 *  1. Outputs a <script> + <style> block that is injected into
 *     index.html via PHP include.
 *  2. Uses a MutationObserver to re-pin the tool card if any
 *     script ever removes or hides it.
 *  3. Forces pointer-events, z-index, visibility and display
 *     on #toolCard every 300 ms (safety heartbeat).
 *  4. Prevents the iframe from ever stealing focus away from
 *     the card's interactive elements.
 * ──────────────────────────────────────────────────────────────
 */

/* ── Send no-cache headers so the fix is always fresh ── */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=UTF-8');
?>
<!-- ╔═══════════════════════════════════════════════════════╗
     ║  ANTIMASTER.PHP  —  Floating Card Force-Show Layer  ║
     ╚═══════════════════════════════════════════════════════╝ -->

<style id="antimaster-css">
  /* ── Nuclear z-index override ── */
  #toolCard {
    position: fixed !important;
    bottom:   0     !important;
    left:     0     !important;
    right:    0     !important;
    z-index:  2147483647 !important;   /* max possible z-index */
    display:  flex  !important;
    visibility: visible !important;
    opacity:  1     !important;
    pointer-events: all !important;
    transform: none !important;
    clip: auto  !important;
    clip-path: none !important;
  }

  /* ── Iframe must NOT cover the card ── */
  #mainFrame {
    position: fixed  !important;
    top:    0        !important;
    left:   0        !important;
    width:  100%     !important;
    height: calc(100% - 62px) !important;
    z-index: 1       !important;
    pointer-events: all !important;
  }

  /* ── Body / html must be overflow:hidden so no scroll hides card ── */
  html, body {
    overflow: hidden !important;
    height:   100%   !important;
    width:    100%   !important;
  }

  /* ── Collapsed state keeps header visible ── */
  #toolCard.collapsed {
    max-height: 62px !important;
    overflow: hidden !important;
  }
</style>

<script id="antimaster-js">
(function () {
  'use strict';

  /* ── 1. Heartbeat: re-enforce every 300 ms ── */
  function enforceCard() {
    var card = document.getElementById('toolCard');
    if (!card) return;

    var s = card.style;
    s.setProperty('position',       'fixed',      'important');
    s.setProperty('bottom',         '0',          'important');
    s.setProperty('left',           '0',          'important');
    s.setProperty('right',          '0',          'important');
    s.setProperty('z-index',        '2147483647', 'important');
    s.setProperty('display',        'flex',       'important');
    s.setProperty('visibility',     'visible',    'important');
    s.setProperty('opacity',        '1',          'important');
    s.setProperty('pointer-events', 'all',        'important');
  }

  /* ── 2. MutationObserver: re-attach if card removed from DOM ── */
  function watchCard() {
    var body = document.body;
    if (!body) return;

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        m.removedNodes.forEach(function (node) {
          if (node && node.id === 'toolCard') {
            /* Card was ripped out — put it back */
            body.appendChild(node);
            enforceCard();
          }
        });
      });
      enforceCard();
    });

    observer.observe(body, { childList: true, subtree: true, attributes: true });
  }

  /* ── 3. Prevent iframe focus from hiding card interaction ── */
  function guardIframe() {
    var frame = document.getElementById('mainFrame');
    if (!frame) return;

    /* When iframe gets focus, immediately give focus back to body */
    frame.addEventListener('load', function () {
      try {
        /* Re-enforce after each page load inside iframe */
        enforceCard();
      } catch (e) { /* cross-origin — safe to ignore */ }
    });
  }

  /* ── 4. Boot everything after DOM ready ── */
  function boot() {
    enforceCard();
    watchCard();
    guardIframe();

    /* Heartbeat every 300 ms */
    setInterval(enforceCard, 300);

    /* Also run on any scroll or resize */
    window.addEventListener('resize', enforceCard, { passive: true });
    window.addEventListener('scroll', enforceCard, { passive: true });

    console.log('[antimaster] ✅ Floating tool card protection active');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

})();
</script>
