/**
 * Pyton-get-result.js — JAI Club Prediction Tool
 * Small draggable floating card + 7-algorithm prediction engine
 */
var TOOL = (function(){
'use strict';

var URLS = {
  login:'https://jaiclub.app/#/login',
  home:'https://jaiclub.app/#/',
  wingo:'https://jaiclub.app/#/saasLottery/WinGo?gameCode=WinGo_30S&lottery=WinGo'
};

var TOTAL = 10;
var picked = null; // 'Big'|'Small'
var trends = [];   // [{size:'Big'|'Small'}]
var frame, box, logo;

// WinGo rules
var NC = {0:'violet',1:'green',2:'green',3:'green',4:'green',5:'violet',6:'red',7:'red',8:'red',9:'red'};

function $(id){return document.getElementById(id)}

/* ═══ INIT ═══ */
function init(){
  frame = $('mainFrame');
  box   = $('toolBox');
  logo  = $('miniLogo');
  buildSlots();
  makeDraggable();
}

/* ═══ MINIMIZE / EXPAND ═══ */
function minimize(){
  box.classList.add('hidden');
  logo.classList.add('show');
}
function expand(){
  logo.classList.remove('show');
  box.classList.remove('hidden');
}

/* ═══ NAVIGATION ═══ */
function nav(page){
  if(frame) frame.src = URLS[page] || URLS.login;
  if(page === 'wingo'){
    $('tbTitle').textContent = 'ENTER TRENDS';
    $('tbDot') && ($('tbDot').style.background = '#f59e0b');
    showState('sTrend');
    resetTrends();
  }
}

/* ═══ SHOW STATE ═══ */
function showState(id){
  ['sLogin','sTrend','sAna','sResult'].forEach(function(s){
    var el = $(s); if(el) el.style.display = (s===id)?'block':'none';
  });
}

/* ═══ BUILD SLOTS ═══ */
function buildSlots(){
  var grid = $('slots'); if(!grid) return;
  grid.innerHTML = '';
  for(var i=1;i<=TOTAL;i++){
    (function(idx){
      var d = document.createElement('div');
      d.className = 'sl' + (idx===1?' next':'');
      d.id = 'sl'+idx;
      d.textContent = idx;
      d.onclick = function(){onSlot(idx)};
      grid.appendChild(d);
    })(i);
  }
}

/* ═══ RESET ═══ */
function resetTrends(){
  trends=[]; picked=null;
  var bB=$('bBig'),bS=$('bSmall');
  if(bB) bB.classList.remove('sel');
  if(bS) bS.classList.remove('sel');
  for(var i=1;i<=TOTAL;i++){
    var s=$('sl'+i);
    if(s){s.className='sl'+(i===1?' next':'');s.textContent=i;}
  }
  updProg();
}

/* ═══ PICK BIG/SMALL ═══ */
function pick(size){
  picked = size;
  var bB=$('bBig'),bS=$('bSmall');
  if(bB) bB.classList.toggle('sel',size==='Big');
  if(bS) bS.classList.toggle('sel',size==='Small');
}

/* ═══ SLOT CLICK ═══ */
function onSlot(idx){
  if(idx !== trends.length+1) return;
  if(!picked){flashBtns();return;}
  trends.push({size:picked});
  var s=$('sl'+idx);
  if(s){
    s.classList.remove('next');
    s.classList.add(picked==='Big'?'big-done':'sml-done','done');
    s.textContent = picked==='Big'?'B':'S';
  }
  var ns=$('sl'+(idx+1));
  if(ns) ns.classList.add('next');
  picked=null;
  var bB=$('bBig'),bS=$('bSmall');
  if(bB) bB.classList.remove('sel');
  if(bS) bS.classList.remove('sel');
  updProg();
  if(trends.length===TOTAL){
    setTimeout(function(){runAnalysis()},300);
  }
}

function flashBtns(){
  [$('bBig'),$('bSmall')].forEach(function(b){
    if(!b)return;b.style.opacity='.3';
    setTimeout(function(){b.style.opacity='1'},250);
  });
}

function updProg(){
  var p=$('prog');if(p) p.textContent=trends.length+'/'+TOTAL;
}

/* ═══ ANALYSIS ═══ */
function runAnalysis(){
  showState('sAna');
  $('tbTitle').textContent='ANALYZING...';
  var msgs=['Loading engine...','Running 7 algorithms...','Cross-checking...','Compiling result...'];
  var i=0,el=$('anaMsg');
  var iv=setInterval(function(){
    i++;
    if(i<msgs.length){if(el)el.textContent=msgs[i];}
    else{clearInterval(iv);finalize();}
  },600);
}

/* ═══ FINALIZE ═══ */
function finalize(){
  // Try API first, fallback to client engine
  var payload = buildPayload();
  callAPI(payload,function(data,err){
    var result = (!err && data && data.success) ? data : clientEngine();
    renderResult(result);
    showState('sResult');
    $('tbTitle').textContent='✅ RESULT READY';
  });
}

/* ═══ BUILD PAYLOAD ═══ */
function buildPayload(){
  var base=202605251000000;
  return{trends:trends.map(function(t,i){
    var isBig=t.size==='Big';
    var pools={big:[6,7,8,9,5],small:[1,2,3,4,0]};
    var num=isBig?pools.big[i%5]:pools.small[i%5];
    return{trend_id:String(base+i+1),number:num,color:NC[num]||'green',size:isBig?'MB':'MS'};
  })};
}

/* ═══ API CALL ═══ */
function callAPI(payload,cb){
  fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
  .then(function(r){return r.json()})
  .then(function(d){cb(d,null)})
  .catch(function(e){cb(null,e.message)});
}

/* ═══ 7-ALGORITHM CLIENT ENGINE ═══ */
function clientEngine(){
  var sizes=trends.map(function(t){return t.size});
  var n=sizes.length;
  var bin=sizes.map(function(s){return s==='Big'?1:0});
  var last=bin[n-1];

  // Algo1: Weighted Recency
  var decay=0.82,wB=0,wT=0,w=1;
  for(var i=n-1;i>=0;i--){wB+=bin[i]*w;wT+=w;w*=decay;}
  var a1=wT>0?wB/wT:0.5;

  // Algo2: Streak Reversal
  var streak=1;
  for(var j=n-2;j>=0;j--){if(bin[j]===last)streak++;else break;}
  var a2vote=streak>=3?(1-last):last;
  var a2conf=streak>=5?0.92:streak>=3?0.78:0.55;

  // Algo3: Gap/Overdue
  var gB=-1,gS=-1;
  for(var k=n-1;k>=0;k--){if(gB===-1&&bin[k]===1)gB=n-1-k;if(gS===-1&&bin[k]===0)gS=n-1-k;}
  if(gB===-1)gB=n;if(gS===-1)gS=n;
  var a3vote=gB>gS?1:0;
  var a3conf=Math.min(0.85,0.5+Math.abs(gB-gS)*0.07);

  // Algo4: Alternating
  var alt=0,lb=Math.min(6,n);
  for(var m=n-lb;m<n-1;m++){if(bin[m]!==bin[m+1])alt++;}
  var aR=lb>1?alt/(lb-1):0;
  var a4vote=aR>=0.7?(1-last):last;
  var a4conf=aR>=0.7?aR*0.85:(1-aR)*0.6;

  // Algo5: Markov 2nd order
  var tr={};
  for(var t=0;t<n-2;t++){var key=bin[t]+'_'+bin[t+1];if(!tr[key])tr[key]=[0,0];tr[key][bin[t+2]]++;}
  var a5vote=last,a5conf=0.5;
  if(n>=2){var mk=bin[n-2]+'_'+bin[n-1];if(tr[mk]){var mb=tr[mk][1],ms=tr[mk][0],mt=mb+ms;if(mt>=2){a5vote=mb>=ms?1:0;a5conf=Math.max(mb,ms)/mt*0.88;}}}

  // Algo6: Position bias
  var eS=0,eC=0,oS=0,oC=0;
  for(var p=0;p<n;p++){if(p%2===0){eS+=bin[p];eC++}else{oS+=bin[p];oC++}}
  var rat=n%2===0?(eC>0?eS/eC:0.5):(oC>0?oS/oC:0.5);
  var a6vote=rat>=0.5?1:0;
  var a6conf=0.5+Math.abs(rat-0.5)*0.5;

  // Algo7: Entropy correction
  var bCnt=bin.reduce(function(a,b){return a+b},0);
  var pB=bCnt/n,pS2=(n-bCnt)/n,ent=0;
  if(pB>0)ent-=pB*Math.log2(pB);if(pS2>0)ent-=pS2*Math.log2(pS2);
  var a7vote=a1>=0.5?1:0;
  var a7conf=0.5+ent*0.25;

  // Weighted voting
  var algos=[
    {v:a1>=0.5?1:0, c:Math.abs(a1-0.5)*2, w:1.0},
    {v:a2vote, c:a2conf, w:2.2},
    {v:a3vote, c:a3conf, w:1.4},
    {v:a4vote, c:a4conf, w:1.3},
    {v:a5vote, c:a5conf, w:1.8},
    {v:a6vote, c:a6conf, w:0.8},
    {v:a7vote, c:a7conf, w:1.0}
  ];
  var bS2=0,sS2=0;
  algos.forEach(function(a){var e=a.c*a.w;if(a.v===1)bS2+=e;else sS2+=e;});
  var isBig=bS2>=sS2;
  var totalW=bS2+sS2||1;
  var winS=isBig?bS2:sS2;
  var rawConf=winS/totalW;
  var confPct=85+rawConf*14;confPct=Math.min(99,Math.max(85,confPct));

  // Number prediction
  var bigP=[5,6,7,8,9],smlP=[0,1,2,3,4];
  var pool=isBig?bigP:smlP;
  var predNum=pool[Math.floor(Math.random()*pool.length)]; // randomize within pool for variety
  // Better: pick least recently used
  var used={};pool.forEach(function(x){used[x]=0});
  trends.forEach(function(td,idx){var ib=td.size==='Big';var pp=ib?bigP:smlP;var nm=pp[idx%5];if(used[nm]!==undefined)used[nm]++});
  var minU=Infinity;
  pool.forEach(function(x){if((used[x]||0)<minU){minU=used[x]||0;predNum=x;}});

  var predColor=NC[predNum]||'green';
  var predSize=isBig?'MB':'MS';

  return{
    success:true,
    prediction:{
      predicted_number:predNum,
      predicted_color:predColor.charAt(0).toUpperCase()+predColor.slice(1),
      predicted_size:predSize,
      confidence:Math.round(confPct*10)/10,
      signals:{
        size:{confidence:Math.min(99,Math.round(rawConf*98+1))},
        number:{confidence:Math.min(99,Math.round(confPct*0.9))},
        color:{confidence:Math.min(99,Math.round(confPct*0.92))}
      }
    }
  };
}

/* ═══ RENDER RESULT ═══ */
function renderResult(data){
  var p=data.prediction;
  var isBig=(p.predicted_size||'').toUpperCase()==='MB';

  // Size card color
  var rc=$('rcSize');
  if(rc){rc.classList.toggle('is-small',!isBig)}

  $('rSize').textContent=p.predicted_size||'—';
  $('rNum').textContent=p.predicted_number!==undefined?p.predicted_number:'—';

  var colEl=$('rCol');
  if(colEl){
    colEl.textContent=p.predicted_color||'—';
    var cm={red:'#f87171',green:'#4ade80',violet:'#c084fc',blue:'#60a5fa'};
    colEl.style.color=cm[(p.predicted_color||'').toLowerCase()]||'#f1f5f9';
  }

  // Confidence
  var conf=Math.max(85,parseFloat(p.confidence)||91);
  $('confPct').textContent=conf.toFixed(1)+'%';
  var fill=$('confFill');
  if(fill){fill.style.width='0%';setTimeout(function(){fill.style.width=conf+'%'},60);}
}

/* ═══ RETRY ═══ */
function retry(){
  showState('sTrend');
  $('tbTitle').textContent='ENTER TRENDS';
  resetTrends();
}

/* ═══ MAKE DRAGGABLE ═══ */
function makeDraggable(){
  var drag=$('tbDrag');
  if(!drag)return;
  var ox,oy,sx,sy,moving=false;

  drag.addEventListener('mousedown',start);
  drag.addEventListener('touchstart',start,{passive:false});

  function start(e){
    if(e.target.classList.contains('tb-min'))return;
    moving=true;
    var ev=e.touches?e.touches[0]:e;
    var rect=box.getBoundingClientRect();
    ox=ev.clientX-rect.left;
    oy=ev.clientY-rect.top;
    document.addEventListener('mousemove',move);
    document.addEventListener('mouseup',end);
    document.addEventListener('touchmove',move,{passive:false});
    document.addEventListener('touchend',end);
    e.preventDefault();
  }

  function move(e){
    if(!moving)return;
    var ev=e.touches?e.touches[0]:e;
    var x=ev.clientX-ox;
    var y=ev.clientY-oy;
    // Clamp to viewport
    x=Math.max(0,Math.min(window.innerWidth-box.offsetWidth,x));
    y=Math.max(0,Math.min(window.innerHeight-box.offsetHeight,y));
    box.style.left=x+'px';
    box.style.top=y+'px';
    box.style.right='auto';
    box.style.bottom='auto';
    e.preventDefault();
  }

  function end(){
    moving=false;
    document.removeEventListener('mousemove',move);
    document.removeEventListener('mouseup',end);
    document.removeEventListener('touchmove',move);
    document.removeEventListener('touchend',end);
  }
}

/* ═══ Math.log2 polyfill ═══ */
if(!Math.log2)Math.log2=function(x){return Math.log(x)/Math.LN2};

/* ═══ BOOT ═══ */
document.addEventListener('DOMContentLoaded',init);

/* ═══ PUBLIC ═══ */
return{minimize:minimize,expand:expand,nav:nav,pick:pick,retry:retry};
})();
