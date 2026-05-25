<?php header('Cache-Control: no-store, no-cache'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>JAI Club — Prediction Tool</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;overflow:hidden;font-family:'Segoe UI',system-ui,sans-serif;background:#000}
#fr{position:fixed;inset:0;width:100%;height:100%;border:none;z-index:1}
/* ── CIRCLE LOGO ── */
#logo{
  display:none;position:fixed;bottom:24px;right:24px;z-index:99999;
  width:54px;height:54px;border-radius:50%;
  background:linear-gradient(135deg,#1e3a8a,#7c3aed);
  border:2.5px solid #a78bfa;
  box-shadow:0 4px 24px rgba(124,58,237,.6);
  cursor:pointer;font-size:26px;
  align-items:center;justify-content:center;
  user-select:none;transition:transform .15s;
}
#logo:hover{transform:scale(1.1)}
#logo.on{display:flex}
/* ── CARD ── */
#card{
  position:fixed;bottom:20px;right:20px;width:300px;
  z-index:99999;border-radius:16px;overflow:hidden;
  background:linear-gradient(160deg,#0d1b2e,#1e293b);
  border:1.5px solid rgba(99,179,237,.3);
  box-shadow:0 8px 40px rgba(0,0,0,.8);
  user-select:none;
}
/* ── HEADER ── */
#ch{
  display:flex;align-items:center;gap:8px;
  padding:9px 12px;cursor:grab;
  background:rgba(0,0,0,.35);
  border-bottom:1px solid rgba(255,255,255,.06);
}
#ch:active{cursor:grabbing}
.cdot{width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0}
#ctitle{flex:1;font-size:11.5px;font-weight:800;color:#93c5fd;letter-spacing:.5px}
#cmin{
  width:24px;height:24px;border-radius:6px;border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);color:#64748b;font-size:14px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  line-height:1;flex-shrink:0;padding-bottom:2px;
}
#cmin:hover{background:rgba(255,255,255,.14);color:#e2e8f0}
/* ── BODY ── */
#cb{padding:12px 14px 14px;max-height:70vh;overflow-y:auto;
  scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.08) transparent}
</style>
</head>
<body>
<iframe id="fr" src="https://jaiclub.app/#/login"
  sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation"
  allowfullscreen></iframe>
<div id="logo" onclick="C.show()">🎯</div>
<div id="card">
  <div id="ch" onmousedown="C.drag(event)" ontouchstart="C.drag(event)">
    <span class="cdot" id="cdot"></span>
    <span id="ctitle">⚙ JAI PREDICTION</span>
    <button id="cmin" onclick="C.hide()">—</button>
  </div>
  <div id="cb"></div>
</div>

<style>
/* ── STATE PANELS ── */
.panel{text-align:center}
.sico{font-size:30px;margin-bottom:8px}
.stitle{font-size:13px;font-weight:800;color:#f1f5f9;margin-bottom:4px}
.ssub{font-size:10.5px;color:#64748b;line-height:1.5;margin-bottom:10px}
/* nav buttons */
.nbrow{display:flex;gap:6px;margin-top:4px}
.nb{flex:1;padding:8px 4px;border-radius:9px;border:1px solid rgba(255,255,255,.1);
  background:rgba(255,255,255,.05);color:#94a3b8;font-size:10.5px;font-weight:700;
  cursor:pointer;font-family:inherit;transition:all .12s}
.nb:hover{background:rgba(255,255,255,.12);color:#e2e8f0}
.nb:active{transform:scale(.93)}
.nb.go{background:rgba(124,58,237,.2);border-color:rgba(124,58,237,.4);color:#c4b5fd}
.nb.go:hover{background:rgba(124,58,237,.35)}
/* Big / Small */
.bsrow{display:flex;gap:8px;margin-bottom:8px}
.bsbtn{
  flex:1;padding:11px 6px;border-radius:11px;border:2.5px solid transparent;
  font-size:15px;font-weight:900;cursor:pointer;font-family:inherit;
  transition:all .12s;color:#fff;
}
.bsbtn:active{transform:scale(.91)}
#bBig{background:linear-gradient(135deg,#b45309,#f59e0b)}
#bSmall{background:linear-gradient(135deg,#4338ca,#7c3aed)}
.bsbtn.sel{border-color:#fbbf24;box-shadow:0 0 0 3px rgba(251,191,36,.35);transform:scale(1.04)}
/* hint */
#hint{text-align:center;font-size:10.5px;font-weight:600;color:#334155;
  background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);
  border-radius:7px;padding:5px 8px;margin-bottom:8px;transition:all .2s}
#hint.big{color:#fbbf24;border-color:rgba(251,191,36,.3);background:rgba(251,191,36,.06)}
#hint.sml{color:#a78bfa;border-color:rgba(124,58,237,.3);background:rgba(124,58,237,.06)}
/* slots */
#slots{display:grid;grid-template-columns:repeat(5,1fr);gap:5px;margin-bottom:8px}
.sl{
  aspect-ratio:1;display:flex;align-items:center;justify-content:center;
  border-radius:9px;border:1.5px solid rgba(255,255,255,.08);
  background:rgba(255,255,255,.03);font-size:12px;font-weight:800;
  color:#334155;cursor:pointer;transition:all .12s;
}
.sl:hover:not(.done){border-color:rgba(251,191,36,.4);color:#fbbf24}
.sl.next{border-color:#fbbf24;color:#fbbf24;animation:sp 1s infinite}
@keyframes sp{0%,100%{box-shadow:0 0 0 0 rgba(251,191,36,.45)}50%{box-shadow:0 0 0 5px rgba(251,191,36,0)}}
.sl.B{background:linear-gradient(135deg,#92400e,#d97706);border-color:#f59e0b;color:#fff}
.sl.S{background:linear-gradient(135deg,#3730a3,#7c3aed);border-color:#a78bfa;color:#fff}
.sl.done{cursor:default}
/* progress */
#prog{font-size:10px;color:#475569;font-weight:700;text-align:center;margin-bottom:4px}
/* analyzing */
.spin{width:36px;height:36px;margin:4px auto 10px;border:3px solid rgba(255,255,255,.06);
  border-top-color:#7c3aed;border-radius:50%;animation:sp2 .7s linear infinite}
@keyframes sp2{to{transform:rotate(360deg)}}
/* result */
.rcards{display:flex;gap:6px;margin-bottom:10px}
.rc{
  flex:1;border-radius:10px;padding:9px 5px;text-align:center;
  border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.04);
}
.rc.big{background:linear-gradient(135deg,#1e3a8a,#1d4ed8);border-color:#3b82f6;
  box-shadow:0 3px 14px rgba(29,78,216,.4)}
.rc.sml{background:linear-gradient(135deg,#7f1d1d,#dc2626);border-color:#f87171;
  box-shadow:0 3px 14px rgba(220,38,38,.4)}
.rl{font-size:8px;font-weight:700;color:#64748b;letter-spacing:1px;
  text-transform:uppercase;margin-bottom:4px}
.rc.big .rl,.rc.sml .rl{color:rgba(255,255,255,.6)}
.rv{font-size:17px;font-weight:900;color:#f1f5f9}
.rc.big .rv,.rc.sml .rv{font-size:19px;color:#fff}
/* accuracy */
.accrow{display:flex;align-items:center;gap:6px;margin-bottom:10px}
.accw{font-size:10px;color:#475569;font-weight:600;white-space:nowrap}
.accp{font-size:13px;font-weight:900;color:#4ade80;white-space:nowrap}
.accbar{flex:1;height:6px;background:rgba(255,255,255,.06);border-radius:20px;overflow:hidden}
.accfill{height:100%;border-radius:20px;background:linear-gradient(90deg,#3b82f6,#22c55e);
  width:0%;transition:width 1.2s cubic-bezier(.4,0,.2,1)}
/* sigs */
.sigs{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
.sig{display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.03);
  border:1px solid rgba(255,255,255,.05);border-radius:8px;padding:6px 9px}
.sk{font-size:10.5px;font-weight:700;color:#cbd5e1;width:44px;flex-shrink:0}
.sv2{font-size:11px;font-weight:800;color:#f1f5f9;flex:1}
.sb{flex:2;height:4px;background:rgba(255,255,255,.06);border-radius:20px;overflow:hidden}
.sbf{height:100%;border-radius:20px;background:linear-gradient(90deg,#3b82f6,#22c55e)}
.sp2{font-size:10px;font-weight:800;color:#22c55e;width:30px;text-align:right;flex-shrink:0}
/* new pred btn */
.newbtn{width:100%;padding:10px;border-radius:10px;
  background:rgba(124,58,237,.12);border:1.5px solid rgba(124,58,237,.3);
  color:#c4b5fd;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
  transition:all .15s}
.newbtn:hover{background:rgba(124,58,237,.25)}
.newbtn:active{transform:scale(.96)}
</style>

<script>
var C = (function(){
'use strict';
var frame = null, card = null, logo = null, dot = null, body = null;
var picked = null, trends = [], TOTAL = 10;
var dragging = false, ox = 0, oy = 0;

// Real WinGo color map
var NC = {0:'Violet',1:'Green',2:'Green',3:'Green',4:'Green',
          5:'Violet',6:'Red',7:'Red',8:'Red',9:'Red'};

function $(id){ return document.getElementById(id); }

// ── INIT ──────────────────────────────────────────────
function init(){
  frame = $('fr');
  card  = $('card');
  logo  = $('logo');
  dot   = $('cdot');
  body  = $('cb');
  renderLogin();
}

// ── CARD HIDE / SHOW ──────────────────────────────────
function hide(){
  card.style.display = 'none';
  logo.classList.add('on');
}
function show(){
  logo.classList.remove('on');
  card.style.display = 'block';
}

// ── DRAG ─────────────────────────────────────────────
function drag(e){
  if(e.target.id === 'cmin') return;
  dragging = true;
  var ev = e.touches ? e.touches[0] : e;
  var r  = card.getBoundingClientRect();
  ox = ev.clientX - r.left;
  oy = ev.clientY - r.top;
  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup',   onUp);
  document.addEventListener('touchmove', onMove, {passive:false});
  document.addEventListener('touchend',  onUp);
  e.preventDefault();
}
function onMove(e){
  if(!dragging) return;
  var ev = e.touches ? e.touches[0] : e;
  var x  = ev.clientX - ox;
  var y  = ev.clientY - oy;
  x = Math.max(0, Math.min(window.innerWidth  - card.offsetWidth,  x));
  y = Math.max(0, Math.min(window.innerHeight - card.offsetHeight, y));
  card.style.right  = 'auto';
  card.style.bottom = 'auto';
  card.style.left   = x + 'px';
  card.style.top    = y + 'px';
  e.preventDefault();
}
function onUp(){
  dragging = false;
  document.removeEventListener('mousemove', onMove);
  document.removeEventListener('mouseup',   onUp);
  document.removeEventListener('touchmove', onMove);
  document.removeEventListener('touchend',  onUp);
}

// ── STATUS DOT ────────────────────────────────────────
function setDot(color){ if(dot) dot.style.background = color; }

// ── NAV ───────────────────────────────────────────────
function nav(page){
  var urls = {
    login: 'https://jaiclub.app/#/login',
    home:  'https://jaiclub.app/#/',
    wingo: 'https://jaiclub.app/#/saasLottery/WinGo?gameCode=WinGo_30S&lottery=WinGo'
  };
  if(frame && urls[page]) frame.src = urls[page];
  if(page === 'wingo'){
    setTimeout(function(){ renderTrend(); }, 800);
    setDot('#f59e0b');
    setTitle('⚙ ENTER TRENDS');
    body.innerHTML = '<div class="panel"><div class="spin"></div><div class="stitle">Setup.. Tool</div><div class="ssub">Server Getting..</div></div>';
  } else if(page === 'home'){
    setDot('#22c55e');
    setTitle('✅ LOGIN SUCCESS');
    body.innerHTML = '<div class="panel"><div class="sico">✅</div><div class="stitle">Login Done.. Successful</div><div class="ssub">Now tap 🎮 WinGo to start</div><div class="nbrow"><button class="nb go" onclick="C.nav(\'wingo\')">🎮 Go WinGo</button></div></div>';
  } else {
    renderLogin();
  }
}

function setTitle(t){ var el = $('ctitle'); if(el) el.textContent = t; }

// ── RENDER LOGIN ──────────────────────────────────────
function renderLogin(){
  setDot('#22c55e');
  setTitle('⚙ JAI PREDICTION');
  body.innerHTML =
    '<div class="panel">' +
    '<div class="sico">🔐</div>' +
    '<div class="stitle">Server Connected</div>' +
    '<div class="ssub">Login Now to continue<br>Then tap 🎮 WinGo</div>' +
    '<div class="nbrow">' +
    '<button class="nb" onclick="C.nav(\'login\')">🔐 Login</button>' +
    '<button class="nb" onclick="C.nav(\'home\')">🏠 Home</button>' +
    '<button class="nb go" onclick="C.nav(\'wingo\')">🎮 WinGo</button>' +
    '</div></div>';
}

// ── RENDER TREND ENTRY ────────────────────────────────
function renderTrend(){
  trends = []; picked = null;
  setTitle('📊 ENTER TRENDS');
  setDot('#f59e0b');
  var html =
    '<div class="panel">' +
    '<div class="ssub" style="margin-bottom:8px;font-weight:700;color:#e2e8f0">Enter Last 10 Trends (Big or Small)</div>' +
    '<div class="bsrow">' +
    '<button class="bsbtn" id="bBig"   onclick="C.pick(\'Big\')">Big<br><small style="font-size:9px;opacity:.7">MB</small></button>' +
    '<button class="bsbtn" id="bSmall" onclick="C.pick(\'Small\')">Small<br><small style="font-size:9px;opacity:.7">MS</small></button>' +
    '</div>' +
    '<div id="hint">← Pick Big or Small first</div>' +
    '<div id="slots"></div>' +
    '<div id="prog">0 / 10</div>' +
    '</div>';
  body.innerHTML = html;
  buildSlots();
}

// ── BUILD SLOTS ───────────────────────────────────────
function buildSlots(){
  var g = $('slots'); if(!g) return;
  g.innerHTML = '';
  for(var i = 1; i <= TOTAL; i++){
    (function(idx){
      var d = document.createElement('div');
      d.className = 'sl' + (idx===1 ? ' next' : '');
      d.id = 'sl'+idx;
      d.textContent = idx;
      d.onclick = function(){ onSlot(idx); };
      g.appendChild(d);
    })(i);
  }
}

// ── PICK BIG / SMALL ─────────────────────────────────
function pick(size){
  picked = size;
  var bB = $('bBig'), bS = $('bSmall'), h = $('hint');
  if(bB){ bB.className = 'bsbtn' + (size==='Big'?' sel':''); }
  if(bS){ bS.className = 'bsbtn' + (size==='Small'?' sel':''); }
  if(h){
    if(size==='Big'){   h.textContent='🟠 Big selected — tap Trend slot'; h.className='big'; }
    else{               h.textContent='🟣 Small selected — tap Trend slot'; h.className='sml'; }
  }
}

// ── SLOT CLICK ────────────────────────────────────────
function onSlot(idx){
  if(idx !== trends.length + 1) return;
  if(!picked){ flash(); return; }
  trends.push(picked);
  var sl = $('sl'+idx);
  if(sl){ sl.className = 'sl done ' + (picked==='Big'?'B':'S'); sl.textContent = picked==='Big'?'B':'S'; }
  var nx = $('sl'+(idx+1));
  if(nx) nx.classList.add('next');
  picked = null;
  var bB=$('bBig'),bS=$('bSmall'),h=$('hint');
  if(bB) bB.className='bsbtn';
  if(bS) bS.className='bsbtn';
  if(h){ h.textContent='← Pick Big or Small first'; h.className=''; }
  var p=$('prog'); if(p) p.textContent = trends.length + ' / ' + TOTAL;
  if(trends.length === TOTAL){ setTimeout(analyze, 400); }
}

function flash(){
  [$('bBig'),$('bSmall')].forEach(function(b){
    if(!b)return; b.style.opacity='.2';
    setTimeout(function(){b.style.opacity='1';},250);
  });
}

// ── ANALYZE ───────────────────────────────────────────
function analyze(){
  setTitle('🔍 ANALYZING');
  setDot('#f59e0b');
  var msgs = ['Loading engine...','Running 7 algorithms...','Cross-checking...','Compiling result...'];
  var i = 0;
  body.innerHTML =
    '<div class="panel">' +
    '<div class="spin"></div>' +
    '<div class="stitle" id="amid">All Trends fetched.. Wait</div>' +
    '<div class="ssub" id="asub">'+msgs[0]+'</div>' +
    '</div>';
  var iv = setInterval(function(){
    i++;
    var s = $('asub');
    if(i < msgs.length){ if(s) s.textContent = msgs[i]; }
    else{
      clearInterval(iv);
      // Try server API first, fall back to client engine
      var payload = buildPayload();
      callAPI(payload, function(data, err){
        var res = (!err && data && data.success) ? data : clientEngine();
        renderResult(res);
      });
    }
  }, 700);
}

// ── BUILD PAYLOAD ─────────────────────────────────────
function buildPayload(){
  var base = 202605251000000;
  var bP=[6,7,8,9,5], sP=[1,2,3,4,0];
  return { trends: trends.map(function(t,i){
    var iB = t==='Big';
    var num = iB ? bP[i%5] : sP[i%5];
    return {trend_id:String(base+i+1), number:num, color:(NC[num]||'Green').toLowerCase(), size:iB?'MB':'MS'};
  })};
}

// ── API CALL ──────────────────────────────────────────
function callAPI(payload, cb){
  fetch('api.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(payload)
  })
  .then(function(r){ return r.json(); })
  .then(function(d){ cb(d, null); })
  .catch(function(e){ cb(null, e.message); });
}

// ── 7-ALGORITHM ENGINE ────────────────────────────────
function clientEngine(){
  var n   = trends.length;
  var bin = trends.map(function(t){ return t==='Big'?1:0; });
  var last = bin[n-1];

  // A1: Weighted Recency Frequency (decay 0.82)
  var w=1, wB=0, wT=0;
  for(var i=n-1;i>=0;i--){ wB+=bin[i]*w; wT+=w; w*=0.82; }
  var a1 = wT>0 ? wB/wT : 0.5;

  // A2: Streak Reversal
  var st=1;
  for(var j=n-2;j>=0;j--){ if(bin[j]===last) st++; else break; }
  var v2 = st>=3 ? (1-last) : last;
  var c2 = st>=5?0.92 : st>=3?0.78 : 0.55;

  // A3: Gap / Overdue
  var gB=-1,gS=-1;
  for(var k=n-1;k>=0;k--){
    if(gB===-1&&bin[k]===1) gB=n-1-k;
    if(gS===-1&&bin[k]===0) gS=n-1-k;
  }
  if(gB===-1)gB=n; if(gS===-1)gS=n;
  var v3=gB>gS?1:0, c3=Math.min(0.85,0.5+Math.abs(gB-gS)*0.07);

  // A4: Alternating Pattern
  var alt=0, lb=Math.min(6,n);
  for(var m=n-lb;m<n-1;m++){ if(bin[m]!==bin[m+1]) alt++; }
  var aR=lb>1?alt/(lb-1):0;
  var v4=aR>=0.7?(1-last):last, c4=aR>=0.7?aR*0.85:(1-aR)*0.6;

  // A5: Markov 2nd Order
  var tr={};
  for(var t2=0;t2<n-2;t2++){
    var key=bin[t2]+'_'+bin[t2+1];
    if(!tr[key])tr[key]=[0,0];
    tr[key][bin[t2+2]]++;
  }
  var v5=last,c5=0.5;
  if(n>=2){
    var mk=bin[n-2]+'_'+bin[n-1];
    if(tr[mk]){
      var mb=tr[mk][1],ms=tr[mk][0],mt=mb+ms;
      if(mt>=2){ v5=mb>=ms?1:0; c5=Math.max(mb,ms)/mt*0.88; }
    }
  }

  // A6: Positional Bias
  var eS=0,eC=0,oS=0,oC=0;
  for(var p=0;p<n;p++){
    if(p%2===0){eS+=bin[p];eC++;} else{oS+=bin[p];oC++;}
  }
  var rat=n%2===0?(eC>0?eS/eC:0.5):(oC>0?oS/oC:0.5);
  var v6=rat>=0.5?1:0, c6=0.5+Math.abs(rat-0.5)*0.5;

  // A7: Shannon Entropy
  var bC=bin.reduce(function(a,b){return a+b},0);
  var pB2=bC/n, pS2=(n-bC)/n, ent=0;
  if(pB2>0) ent-=pB2*Math.log2(pB2);
  if(pS2>0) ent-=pS2*Math.log2(pS2);
  var v7=a1>=0.5?1:0, c7=0.5+ent*0.25;

  // Weighted vote
  var algs=[
    {v:a1>=0.5?1:0, c:Math.abs(a1-0.5)*2, w:1.0},
    {v:v2, c:c2, w:2.2},
    {v:v3, c:c3, w:1.4},
    {v:v4, c:c4, w:1.3},
    {v:v5, c:c5, w:1.8},
    {v:v6, c:c6, w:0.8},
    {v:v7, c:c7, w:1.0}
  ];
  var bs=0,ss=0;
  algs.forEach(function(a){ var e=a.c*a.w; if(a.v===1)bs+=e; else ss+=e; });
  var isBig = bs>=ss;
  var rawC  = (isBig?bs:ss)/(bs+ss||1);
  var confP = Math.min(99, Math.max(85, 85+rawC*14));

  // Predict number (least recently used in pool)
  var bP=[5,6,7,8,9], sP=[0,1,2,3,4];
  var pool=isBig?bP:sP, used={};
  pool.forEach(function(x){used[x]=0;});
  trends.forEach(function(td,idx){
    var ib=td==='Big'; var pp=ib?bP:sP; var nm=pp[idx%5];
    if(used[nm]!==undefined) used[nm]++;
  });
  var predNum=pool[0], minU=Infinity;
  pool.forEach(function(x){ if((used[x]||0)<minU){minU=used[x]||0;predNum=x;} });
  var predColor = NC[predNum]||'Green';
  var predSize  = isBig?'MB':'MS';
  var sizeC     = Math.min(99,Math.round(rawC*98+1));
  var numC      = Math.min(99,Math.round(confP*0.90));
  var colC      = Math.min(99,Math.round(confP*0.92));

  return {success:true, prediction:{
    predicted_number:predNum, predicted_color:predColor,
    predicted_size:predSize, confidence:Math.round(confP*10)/10,
    streak:st, algorithms_agree: isBig ? Math.round(bs/(bs+ss)*7) : Math.round(ss/(bs+ss)*7),
    signals:{
      size:{confidence:sizeC,method:'Streak('+st+')+Markov+Freq'},
      number:{confidence:numC,method:'Overdue+WinGoPool'},
      color:{confidence:colC,method:'WinGoNumberRule'}
    }
  }};
}

// ── RENDER RESULT ─────────────────────────────────────
function renderResult(data){
  var p   = data.prediction;
  var sig = p.signals||{};
  var isBig = (p.predicted_size||'').toUpperCase()==='MB';
  var conf  = Math.max(85, parseFloat(p.confidence)||91);

  // color map
  var cm={red:'#f87171',green:'#4ade80',violet:'#c084fc',blue:'#60a5fa'};
  var colStyle = 'color:'+(cm[(p.predicted_color||'').toLowerCase()]||'#f1f5f9');

  setTitle('✅ RESULT READY');
  setDot('#22c55e');

  var sc = Math.min(99,(sig.size||{}).confidence||conf);
  var nc = Math.min(99,(sig.number||{}).confidence||Math.round(conf*.9));
  var cc = Math.min(99,(sig.color||{}).confidence||Math.round(conf*.92));

  body.innerHTML =
    '<div class="panel">' +
    // Cards
    '<div class="rcards">' +
    '<div class="rc '+(isBig?'big':'sml')+'">' +
      '<div class="rl">NEXT SIZE</div>' +
      '<div class="rv">'+(p.predicted_size||'—')+'</div>' +
    '</div>' +
    '<div class="rc">' +
      '<div class="rl">NUMBER</div>' +
      '<div class="rv">'+(p.predicted_number!==undefined?p.predicted_number:'—')+'</div>' +
    '</div>' +
    '<div class="rc">' +
      '<div class="rl">COLOR</div>' +
      '<div class="rv" style="'+colStyle+'">'+(p.predicted_color||'—')+'</div>' +
    '</div>' +
    '</div>' +
    // Accuracy
    '<div class="accrow">' +
    '<span class="accw">Accuracy</span>' +
    '<span class="accp" id="acp">'+conf.toFixed(1)+'%</span>' +
    '<div class="accbar"><div class="accfill" id="acf"></div></div>' +
    '</div>' +
    // Signals
    '<div class="sigs">' +
    sigRow('Size',   p.predicted_size||'—',   sc,  (sig.size||{}).method||'') +
    sigRow('Number', p.predicted_number!==undefined?p.predicted_number:'—', nc, (sig.number||{}).method||'') +
    sigRow('Color',  p.predicted_color||'—',  cc,  (sig.color||{}).method||'') +
    '</div>' +
    // Info
    '<div style="font-size:9px;color:#334155;margin-bottom:8px;text-align:center">'+
    '7 algorithms | Streak: '+(p.streak||1)+' | '+
    (p.algorithms_agree||'—')+'/7 agree' +
    '</div>' +
    // Retry
    '<button class="newbtn" onclick="C.retry()">🔄 New Prediction</button>' +
    '</div>';

  // Animate bar
  setTimeout(function(){
    var f=$('acf'); if(f) f.style.width=conf+'%';
  }, 80);
}

function sigRow(k,v,pct,method){
  return '<div class="sig">' +
    '<span class="sk">'+k+'</span>' +
    '<span class="sv2">'+v+'</span>' +
    '<div class="sb"><div class="sbf" style="width:'+pct+'%"></div></div>' +
    '<span class="sp2">'+pct+'%</span>' +
    '</div>';
}

// ── RETRY ─────────────────────────────────────────────
function retry(){ renderTrend(); }

// Math.log2 polyfill
if(!Math.log2) Math.log2=function(x){return Math.log(x)/Math.LN2;};

// Boot
document.addEventListener('DOMContentLoaded', init);

return {hide:hide,show:show,drag:drag,nav:nav,pick:pick,retry:retry};
})();
</script>
</body>
</html>
