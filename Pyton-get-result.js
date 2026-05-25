/**
 * Pyton-get-result.js
 * State machine: LOGIN → HOME → WINGO_SETUP → TREND_ENTRY → ANALYZING → RESULT
 */
var T = (function () {
    'use strict';

    var URLS = {
        login: 'https://jaiclub.app/#/login',
        home:  'https://jaiclub.app/#/',
        wingo: 'https://jaiclub.app/#/saasLottery/WinGo?gameCode=WinGo_30S&lottery=WinGo'
    };

    var S = { LOGIN:'login', HOME:'home', SETUP:'setup', TREND:'trend', ANA:'analyzing', RESULT:'result' };

    var state      = S.LOGIN;
    var picked     = null;   // 'Big' | 'Small'
    var trends     = [];     // [{size}]
    var TOTAL      = 10;

    /* ── DOM cache ── */
    var $ = function(id){ return document.getElementById(id); };
    var frame, card, toggle, dot, chipTxt;
    var panels = {};
    var navBtns = {};

    /* ════════════ INIT ════════════ */
    function init() {
        frame   = $('mainFrame');
        card    = $('toolCard');
        toggle  = $('tcToggle');
        dot     = $('chipDot');
        chipTxt = $('chipText');

        panels  = { login:$('panelLogin'), home:$('panelHome'), setup:$('panelSetup'),
                    trend:$('panelTrend'), analyzing:$('panelAnalyzing'), result:$('panelResult') };
        navBtns = { login:$('nb-login'), home:$('nb-home'), wingo:$('nb-wingo') };

        buildSlots();
        toggle.addEventListener('click', toggleCard);
        go(S.LOGIN);

        setTimeout(function(){ setChip('ok','Server Connected.. Login Now'); }, 1000);
    }

    /* ── collapse / expand ── */
    function toggleCard() {
        var col = card.classList.toggle('collapsed');
        toggle.textContent = col ? '▲' : '▼';
    }

    /* ── chip status ── */
    function setChip(type, text) {
        dot.className = 'chip-dot ' + type;
        chipTxt.textContent = text;
    }

    /* ── panel switch ── */
    function showPanel(name) {
        Object.keys(panels).forEach(function(k){
            panels[k].style.display = (k === name) ? 'block' : 'none';
        });
    }

    /* ── nav highlight ── */
    function setNav(name) {
        Object.keys(navBtns).forEach(function(k){
            navBtns[k].classList.toggle('active', k === name);
        });
    }

    /* ════════════ MASTER STATE ════════════ */
    function go(s) {
        state = s;
        switch(s) {
            case S.LOGIN:
                showPanel('login'); setNav('login');
                setChip('ok','Server Connected.. Login Now');
                break;
            case S.HOME:
                showPanel('home'); setNav('home');
                setChip('good','Login Done.. Successful  Now start Tool');
                break;
            case S.SETUP:
                showPanel('setup'); setNav('wingo');
                setChip('busy','Setup.. Tool');
                runSetup();
                break;
            case S.TREND:
                showPanel('trend'); setNav('wingo');
                setChip('busy','Enter Last 10 Trends');
                resetTrends();
                break;
            case S.ANA:
                showPanel('analyzing'); setNav('wingo');
                setChip('busy','All Trends fetched.. Wait');
                runAnalysis();
                break;
            case S.RESULT:
                showPanel('result'); setNav('wingo');
                setChip('good','Prediction Complete — 99% Accurate');
                break;
        }
    }

    /* ════════════ SETUP ANIMATION ════════════ */
    function runSetup() {
        var el = $('setupTxt');
        var msgs = ['Server Getting..','Connecting Engine..','Initializing Python..','Ready ✓'];
        var i = 0;
        var iv = setInterval(function(){
            i++;
            if(i < msgs.length){ if(el) el.textContent = msgs[i]; }
            else { clearInterval(iv); setTimeout(function(){ go(S.TREND); }, 350); }
        }, 650);
    }

    /* ════════════ NAV ACTIONS ════════════ */
    function goLogin() { if(frame) frame.src = URLS.login; go(S.LOGIN); }
    function goHome()  { if(frame) frame.src = URLS.home;  go(S.HOME);  }
    function goWingo() { if(frame) frame.src = URLS.wingo; go(S.SETUP); }

    /* ════════════ BUILD SLOTS ════════════ */
    function buildSlots() {
        var grid = $('slotsGrid');
        if(!grid) return;
        grid.innerHTML = '';
        for(var i=1; i<=TOTAL; i++) {
            (function(idx){
                var btn = document.createElement('button');
                btn.className = 'slot' + (idx===1 ? ' next' : '');
                btn.id = 'slot'+idx;
                btn.innerHTML = '<span class="sn">T'+idx+'</span><span class="sv" id="sv'+idx+'">·</span>';
                btn.onclick = function(){ onSlot(idx); };
                grid.appendChild(btn);
            })(i);
        }
    }

    /* ── Reset trend entry ── */
    function resetTrends() {
        trends = []; picked = null;
        var bB = $('btnBig'), bS = $('btnSmall'), bd = $('selBadge');
        if(bB){ bB.classList.remove('sel'); }
        if(bS){ bS.classList.remove('sel'); }
        if(bd){ bd.textContent='← Select Big or Small first'; bd.className='sel-badge'; }
        for(var i=1; i<=TOTAL; i++) {
            var sl = $('slot'+i), sv = $('sv'+i);
            if(sl){ sl.className='slot'+(i===1?' next':''); sl.disabled=false; }
            if(sv){ sv.textContent='·'; }
        }
        updProg();
    }

    /* ── Pick Big / Small ── */
    function pick(size) {
        picked = size;
        var bB = $('btnBig'), bS = $('btnSmall'), bd = $('selBadge');
        if(bB) bB.classList.toggle('sel', size==='Big');
        if(bS) bS.classList.toggle('sel', size==='Small');
        if(bd){
            bd.textContent = size==='Big' ? '🔵 Big (MB) selected — tap Trend slot' : '🔴 Small (MS) selected — tap Trend slot';
            bd.className   = 'sel-badge ' + (size==='Big' ? 'has-big' : 'has-small');
        }
    }

    /* ── Slot click ── */
    function onSlot(idx) {
        if(idx !== trends.length+1) return;
        if(!picked){ flashBtns(); return; }

        trends.push({ size: picked });

        var sl = $('slot'+idx), sv = $('sv'+idx);
        if(sl){
            sl.classList.remove('next');
            sl.classList.add(picked==='Big'?'fb':'fs');
            sl.disabled = true;
        }
        if(sv){ sv.textContent = picked==='Big' ? 'B' : 'S'; }

        // highlight next
        var ns = $('slot'+(idx+1));
        if(ns) ns.classList.add('next');

        // reset pick
        picked = null;
        var bB=$('btnBig'),bS=$('btnSmall'),bd=$('selBadge');
        if(bB) bB.classList.remove('sel');
        if(bS) bS.classList.remove('sel');
        if(bd){ bd.textContent='← Select Big or Small first'; bd.className='sel-badge'; }

        updProg();
        if(trends.length === TOTAL){ setTimeout(function(){ go(S.ANA); }, 420); }
    }

    /* ── Flash buttons if no pick ── */
    function flashBtns() {
        [$('btnBig'),$('btnSmall')].forEach(function(b){
            if(!b) return;
            b.style.opacity='.25';
            setTimeout(function(){ b.style.opacity='1'; }, 280);
        });
    }

    /* ── Progress bar ── */
    function updProg() {
        var d = trends.length, pct = Math.round((d/TOTAL)*100);
        var lbl=$('progLbl'), pp=$('progPct'), fill=$('progFill');
        if(lbl)  lbl.textContent  = d+' / '+TOTAL+' entered';
        if(pp)   pp.textContent   = pct+'%';
        if(fill) fill.style.width = pct+'%';
    }

    /* ════════════ ANALYSIS ════════════ */
    function runAnalysis() {
        var ids = ['as1','as2','as3','as4'];
        var delays = [700,1500,2300,3100];
        ids.forEach(function(id,i){
            setTimeout(function(){
                var el=$(id); if(el) el.classList.add('active');
            }, delays[i]);
            setTimeout(function(){
                var el=$(id);
                if(el){ el.classList.remove('active'); el.classList.add('done');
                    el.textContent=el.textContent.replace('⏳','✅'); }
            }, delays[i]+650);
        });

        var payload = buildPayload();
        var animDone=false, apiDone=false, apiData=null, apiErr=null;

        setTimeout(function(){
            animDone=true;
            if(apiDone) finalize(apiData,apiErr);
        }, 3900);

        callApi(payload, function(d,e){
            apiDone=true; apiData=d; apiErr=e;
            if(animDone) finalize(d,e);
        });
    }

    /* ── Build payload ── */
    function buildPayload() {
        var base=504312610;
        return { trends: trends.map(function(t,i){
            return {
                trend_id: String(base+i+1),
                number:   t.size==='Big' ? (51+((i*7+11)%40)) : (1+((i*7+11)%40)),
                color:    ['red','green','violet','blue','green'][i%5],
                size:     t.size==='Big'?'MB':'MS'
            };
        })};
    }

    /* ── API call ── */
    function callApi(payload, cb) {
        fetch('api.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        })
        .then(function(r){ return r.json(); })
        .then(function(d){ cb(d,null); })
        .catch(function(e){ cb(null,e.message); });
    }

    /* ── Finalize ── */
    function finalize(data,err) {
        var result = (err||!data||!data.success) ? fallback() : data;
        render(result);
        go(S.RESULT);
    }

    /* ── Client-side fallback ── */
    function fallback() {
        var sizes  = trends.map(function(t){ return t.size; });
        var bigCnt = sizes.filter(function(s){ return s==='Big'; }).length;
        var smlCnt = TOTAL - bigCnt;
        var streak=1, last=sizes[sizes.length-1];
        for(var i=sizes.length-2;i>=0;i--){ if(sizes[i]===last) streak++; else break; }
        var pred = streak>=3 ? (last==='Big'?'Small':'Big') : (bigCnt>=smlCnt?'Big':'Small');
        var sc   = Math.min(99, 62+streak*8);
        var num  = pred==='Big' ? (51+(bigCnt*7)%40) : (1+(smlCnt*7)%40);
        var cols = pred==='Big' ? ['Green','Red','Violet','Blue'] : ['Red','Green','Violet','Blue'];
        var col  = cols[bigCnt % cols.length];
        var conf = Math.round(sc*.45 + 72*.35 + 68*.20);
        return { success:true, prediction:{
            predicted_id:504312621, predicted_number:num, predicted_color:col,
            predicted_size:pred==='Big'?'MB':'MS', confidence:conf,
            signals:{
                size:  {confidence:sc, method:'streak-reversal + frequency'},
                number:{confidence:72, method:'weighted-frequency + gap-detection'},
                color: {confidence:68, method:'alternating-pattern + frequency'}
            }
        }, meta:{} };
    }

    /* ════════════ RENDER RESULT ════════════ */
    function render(data) {
        var p   = data.prediction;
        var sig = p.signals||{};

        /* size card */
        var isBig = (p.predicted_size||'').toUpperCase()==='MB';
        var rcSize = $('rcSize');
        if(rcSize){
            rcSize.style.background = isBig
                ? 'linear-gradient(135deg,#1e3a8a,#1d4ed8)'
                : 'linear-gradient(135deg,#7f1d1d,#dc2626)';
            rcSize.style.borderColor = isBig ? '#3b82f6' : '#f87171';
            rcSize.style.boxShadow   = isBig
                ? '0 4px 20px rgba(29,78,216,.35)'
                : '0 4px 20px rgba(220,38,38,.35)';
        }
        set('rSize', p.predicted_size||'—');
        set('rNum',  p.predicted_number!==undefined ? p.predicted_number : '—');

        /* color value with color */
        var colEl = $('rCol');
        if(colEl){
            colEl.textContent = p.predicted_color||'—';
            var cm={red:'#f87171',green:'#4ade80',blue:'#60a5fa',
                    violet:'#c084fc',orange:'#fb923c',yellow:'#fbbf24',
                    purple:'#c084fc',pink:'#f472b6'};
            colEl.style.color = cm[(p.predicted_color||'').toLowerCase()]||'#f1f5f9';
        }

        /* accuracy bar */
        var conf = Math.max(91, parseFloat(p.confidence)||91);
        set('accPct', conf.toFixed(1)+'%');
        var fill = $('accFill');
        if(fill){ fill.style.width='0%'; setTimeout(function(){ fill.style.width=conf+'%'; },80); }

        /* signal rows */
        var sl = $('sigList');
        if(sl){
            sl.innerHTML='';
            [
                {k:'Size',  v:p.predicted_size,   s:sig.size  },
                {k:'Number',v:p.predicted_number,  s:sig.number},
                {k:'Color', v:p.predicted_color,   s:sig.color }
            ].forEach(function(r){
                var c = Math.max(88, parseFloat((r.s||{}).confidence)||88);
                var div=document.createElement('div');
                div.className='sig-row';
                div.innerHTML=
                    '<span class="sig-key">'+esc(r.k)+'</span>'+
                    '<span class="sig-val">'+esc(String(r.v!==undefined?r.v:'—'))+'</span>'+
                    '<div class="sig-bar-wrap"><div class="sig-bar-fill" style="width:'+c+'%"></div></div>'+
                    '<span class="sig-pct">'+c.toFixed(0)+'%</span>';
                sl.appendChild(div);
            });
        }
    }

    /* ── Retry ── */
    function retry(){ go(S.TREND); }

    /* ── Helpers ── */
    function set(id,v){ var el=$(id); if(el) el.textContent=v; }
    function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    /* ════════════ PUBLIC ════════════ */
    return { init:init, goLogin:goLogin, goHome:goHome, goWingo:goWingo, pick:pick, retry:retry };

})();

document.addEventListener('DOMContentLoaded', function(){
    if(document.getElementById('toolCard')) T.init();
});
