<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>JAI Club — Prediction Tool</title>
<link rel="stylesheet" href="help.css">
</head>
<body>

<!-- iframe: fills top area, pointer-events none so tool card always clickable -->
<iframe id="mainFrame" src="https://jaiclub.app/#/login"
        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
        allowfullscreen></iframe>

<!-- FLOATING TOOL CARD — always on top, fixed bottom -->
<div id="toolCard" class="tool-card">

  <!-- HEADER / DRAG PILL -->
  <div class="tc-header" id="tcHeader">
    <div class="tc-pill"></div>
    <div class="tc-head-row">
      <div class="tc-logo">
        <span class="tc-logo-icon">⚙️</span>
        <span class="tc-logo-text">JAI <span class="tc-logo-accent">Prediction Tool</span></span>
      </div>
      <div class="tc-status-chip">
        <span class="chip-dot" id="chipDot"></span>
        <span id="chipText">Connecting...</span>
      </div>
      <button class="tc-toggle" id="tcToggle" title="Collapse/Expand">▼</button>
    </div>
  </div>

  <!-- SCROLLABLE BODY -->
  <div class="tc-body" id="tcBody">

    <!-- P1: LOGIN -->
    <div class="tc-panel" id="panelLogin">
      <div class="state-card login-card">
        <div class="state-icon">🔐</div>
        <div class="state-title">Server Connected.. Login Now</div>
        <div class="state-sub">Please login on the site above</div>
        <div class="state-hint">After login, tap <b>Home</b> button below ↓</div>
      </div>
    </div>

    <!-- P2: HOME -->
    <div class="tc-panel" id="panelHome" style="display:none">
      <div class="state-card success-card">
        <div class="state-icon">✅</div>
        <div class="state-title">Login Done.. Successful</div>
        <div class="state-sub">Now start Tool</div>
        <div class="state-hint">Tap <b>WinGo</b> below to open the game ↓</div>
      </div>
    </div>

    <!-- P3: WINGO SETUP -->
    <div class="tc-panel" id="panelSetup" style="display:none">
      <div class="setup-card">
        <div class="setup-row">
          <span class="setup-dot d1"></span>
          <span class="setup-lbl">Setup.. Tool</span>
        </div>
        <div class="setup-row">
          <span class="setup-dot d2"></span>
          <span class="setup-lbl" id="setupTxt">Server Getting..</span>
        </div>
      </div>
    </div>

    <!-- P4: TREND ENTRY -->
    <div class="tc-panel" id="panelTrend" style="display:none">
      <div class="trend-header">
        <p class="trend-title">Manually enter Last 10 trends</p>
        <p class="trend-sub">Our system takes time and gives you an accurate result.</p>
      </div>
      <div class="bs-row">
        <button class="bs-btn big-btn"   id="btnBig"   onclick="T.pick('Big')">
          <span class="bs-label">Big</span><span class="bs-tag">MB</span>
        </button>
        <button class="bs-btn small-btn" id="btnSmall" onclick="T.pick('Small')">
          <span class="bs-label">Small</span><span class="bs-tag">MS</span>
        </button>
      </div>
      <div class="sel-badge" id="selBadge">← Select Big or Small first</div>
      <div class="slots-grid" id="slotsGrid"></div>
      <div class="prog-wrap">
        <div class="prog-top">
          <span class="prog-lbl" id="progLbl">0 / 10 entered</span>
          <span class="prog-pct" id="progPct">0%</span>
        </div>
        <div class="prog-track"><div class="prog-fill" id="progFill"></div></div>
      </div>
    </div>

    <!-- P5: ANALYZING -->
    <div class="tc-panel" id="panelAnalyzing" style="display:none">
      <div class="ana-card">
        <div class="ana-spinner"></div>
        <div class="ana-title">All Trends fetched.. Wait</div>
        <div class="ana-steps">
          <div class="ana-step" id="as1">⏳ Loading Python engine...</div>
          <div class="ana-step" id="as2">⏳ Running Calcute.php analysis...</div>
          <div class="ana-step" id="as3">⏳ Cross-checking patterns...</div>
          <div class="ana-step" id="as4">⏳ Compiling final prediction...</div>
        </div>
      </div>
    </div>

    <!-- P6: RESULT -->
    <div class="tc-panel" id="panelResult" style="display:none">
      <div class="res-badge">🎯 Prediction Result</div>
      <div class="res-cards">
        <div class="res-card res-main" id="rcSize">
          <div class="rc-lbl">NEXT SIZE</div>
          <div class="rc-val" id="rSize">—</div>
        </div>
        <div class="res-card">
          <div class="rc-lbl">NUMBER</div>
          <div class="rc-val" id="rNum">—</div>
        </div>
        <div class="res-card">
          <div class="rc-lbl">COLOR</div>
          <div class="rc-val" id="rCol">—</div>
        </div>
      </div>
      <div class="acc-wrap">
        <div class="acc-row">
          <span class="acc-lbl">Accuracy</span>
          <span class="acc-pct" id="accPct">0%</span>
        </div>
        <div class="acc-track"><div class="acc-fill" id="accFill"></div></div>
      </div>
      <div class="sig-list" id="sigList"></div>
      <button class="retry-btn" onclick="T.retry()">🔄 &nbsp;New Prediction</button>
    </div>

  </div><!-- /tc-body -->

  <!-- BOTTOM NAV -->
  <div class="tc-nav">
    <button class="nav-btn active" id="nb-login" onclick="T.goLogin()">
      <span class="nb-ico">🔐</span><span class="nb-lbl">Login</span>
    </button>
    <button class="nav-btn" id="nb-home" onclick="T.goHome()">
      <span class="nb-ico">🏠</span><span class="nb-lbl">Home</span>
    </button>
    <button class="nav-btn" id="nb-wingo" onclick="T.goWingo()">
      <span class="nb-ico">🎮</span><span class="nb-lbl">WinGo</span>
    </button>
  </div>

</div><!-- /toolCard -->

<script src="Pyton-get-result.js"></script>

<!-- ANTIMASTER: force-show floating card, always last so it overrides everything -->
<?php include 'antimaster.php'; ?>

</body>
</html>
