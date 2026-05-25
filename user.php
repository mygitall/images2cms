<?php
session_start();
if (file_exists(__DIR__ . '/config.php')) {
    $__cfg = require __DIR__ . '/config.php';
    if (empty($__cfg['installed'])) { header('Location: install.php'); exit; }
} else { header('Location: install.php'); exit; }
$user = $_SESSION['user'] ?? null;
if (!$user) { header('Location: index.php'); exit; }
?><!doctype html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<base href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'; ?>">
<title>个人中心 — Image Studio</title>
<style>
:root {
  --bg:#f5f5f7; --card-bg:rgba(255,255,255,0.7); --card-border:rgba(0,0,0,0.06);
  --text:#1a1a1a; --text2:#666; --text3:#999; --danger:#dc2626; --success:#16a34a;
  --font:-apple-system,BlinkMacSystemFont,"SF Pro Display","Inter",sans-serif;
}
[data-theme="dark"] {
  --bg:#1a1a1a; --card-bg:rgba(255,255,255,0.04); --card-border:rgba(255,255,255,0.08);
  --text:#eee; --text2:#999; --text3:#777; --danger:#f87171; --success:#4ade80;
}
* { box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; }
body { font-family:var(--font); background:var(--bg); color:var(--text); min-height:100vh; -webkit-font-smoothing:antialiased; overflow-x:hidden; }

/* Header */
.topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; position:sticky; top:0; z-index:10; background:var(--bg); border-bottom:1px solid var(--card-border); }
.topbar h1 { font-size:18px; font-weight:700; }
.topbar .user-tag { font-size:12px; color:var(--text2); }
.topbar a { color:var(--text2); text-decoration:none; font-size:13px; }

/* ====== Desktop: sidebar layout ====== */
.app { max-width:1100px; margin:0 auto; padding:32px 20px; display:flex; gap:24px; }
.sidebar { width:200px; flex-shrink:0; display:flex; flex-direction:column; gap:4px; }
.sidebar button {
  padding:10px 14px; border-radius:8px; font-size:14px; font-family:var(--font);
  color:var(--text2); cursor:pointer; border:none; background:transparent;
  text-align:left; transition:all 0.15s;
}
.sidebar button:hover { background:var(--card-bg); color:var(--text); }
.sidebar button.active { background:var(--text); color:var(--bg); font-weight:600; }
.content { flex:1; min-width:0; }
.card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:24px; margin-bottom:16px; }
.card h2 { font-size:17px; font-weight:600; margin-bottom:16px; }
.stat-row { display:flex; gap:12px; flex-wrap:nowrap; margin-bottom:16px; overflow-x:auto; }
.stat-box { flex:1; min-width:100px; background:rgba(0,0,0,0.02); border-radius:12px; padding:18px; text-align:center; }
.stat-box .num { font-size:28px; font-weight:700; }
.stat-box .label { font-size:10px; color:var(--text3); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em; }
table { width:100%; border-collapse:collapse; }
th,td { text-align:left; padding:8px 10px; font-size:13px; border-bottom:1px solid var(--card-border); }
th { color:var(--text3); font-weight:500; font-size:11px; text-transform:uppercase; letter-spacing:0.04em; }
.panel { display:none; }
.panel.active { display:block; }

/* ====== Mobile: bottom tabs ====== */
.mobile-tabs { display:none; position:fixed; bottom:0; left:0; right:0; background:var(--bg); border-top:1px solid var(--card-border); z-index:10; padding:6px 8px; padding-bottom:max(6px, env(safe-area-inset-bottom)); justify-content:space-around; }
.mobile-tabs button {
  flex:1; padding:8px 4px; border:none; background:transparent; color:var(--text3);
  font-size:11px; font-family:var(--font); cursor:pointer; display:flex;
  flex-direction:column; align-items:center; gap:3px; border-radius:8px; transition:all 0.15s;
}
.mobile-tabs button .icon { font-size:20px; }
.mobile-tabs button.active { color:var(--text); font-weight:600; }

@media (max-width:768px) {
  .app { flex-direction:column; padding:0 0 80px 0; }
  .sidebar { display:none; }
  .topbar { display:flex; }
  .mobile-tabs { display:flex; }
  .card { border-radius:12px; padding:16px; margin:0 12px 12px; }
  .card h2 { font-size:15px; margin-bottom:12px; }
  .stat-box { padding:14px 10px; }
  .stat-box .num { font-size:22px; }
  table { font-size:12px; }
  th,td { padding:6px 8px; }
}
@media (min-width:769px) {
  .topbar { display:none; }
  .mobile-tabs { display:none; }
}
</style>
</head>
<body>

<!-- Top bar (mobile only) -->
<div class="topbar">
  <a href="index.php">← 返回</a>
  <span class="user-tag"><?= htmlspecialchars($user['username']) ?></span>
  <span></span>
</div>

<!-- Desktop sidebar -->
<div class="app">
  <div class="sidebar">
    <span style="font-size:11px;color:var(--text3);padding:0 14px;margin-bottom:8px"><?= htmlspecialchars($user['username']) ?></span>
    <button class="active" data-panel="dashboard">数据看板</button>
    <button data-panel="logs">使用日志</button>
    <button data-panel="balance">余额状态</button>
    <a href="index.php" style="display:block;padding:10px 14px;color:var(--text3);text-decoration:none;font-size:13px;margin-top:16px">← 返回首页</a>
  </div>

  <div class="content">
    <div class="panel active" id="panel-dashboard">
      <div class="card"><h2>数据看板</h2>
        <div class="stat-row" id="stats-box">加载中...</div>
      </div>
    </div>
    <div class="panel" id="panel-logs">
      <div class="card"><h2>API 调用记录</h2>
        <div style="overflow-x:auto">
          <table><thead><tr><th>时间</th><th>接口</th><th>耗时</th><th>状态</th></tr></thead>
            <tbody id="logs-tbody"><tr><td colspan="4" style="color:var(--text3)">加载中...</td></tr></tbody></table>
        </div>
      </div>
    </div>
    <div class="panel" id="panel-balance">
      <div class="card"><h2>余额 & 配额</h2>
        <div id="balance-content" style="color:var(--text2)">加载中...</div>
      </div>
    </div>
  </div>
</div>

<!-- Mobile bottom tabs -->
<div class="mobile-tabs">
  <button class="active" data-panel="dashboard"><span class="icon">&#128200;</span>看板</button>
  <button data-panel="logs"><span class="icon">&#128196;</span>日志</button>
  <button data-panel="balance"><span class="icon">&#128176;</span>余额</button>
</div>

<script>
var currentUser = <?= json_encode($user) ?>;

function switchPanel(name) {
  document.querySelectorAll('.sidebar button, .mobile-tabs button').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('[data-panel="'+name+'"]').forEach(b => b.classList.add('active'));
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  if (name === 'dashboard') loadDashboard();
  if (name === 'logs') loadLogs();
  if (name === 'balance') loadBalance();
}

document.querySelectorAll('.sidebar button, .mobile-tabs button').forEach(btn => {
  btn.addEventListener('click', () => switchPanel(btn.dataset.panel));
});

// Swipe support on mobile
var touchStartX = 0;
document.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; });
document.addEventListener('touchend', e => {
  var diff = touchStartX - e.changedTouches[0].clientX;
  if (Math.abs(diff) < 50 || window.innerWidth > 768) return;
  var panels = ['dashboard','logs','balance'];
  var cur = panels.findIndex(p => document.getElementById('panel-'+p).classList.contains('active'));
  if (diff > 0 && cur < panels.length - 1) switchPanel(panels[cur + 1]);
  if (diff < 0 && cur > 0) switchPanel(panels[cur - 1]);
});

var theme = localStorage.getItem('app-theme') || 'light';
document.documentElement.setAttribute('data-theme', theme);

async function loadDashboard() {
  try {
    var res = await fetch('api/history.php?page=1');
    var data = await res.json();
    var total = data.total || 0;
    var res2 = await fetch('api/admin.php?action=api_logs&page=1');
    var logData = await res2.json();
    var todayLogs = 0, avgDuration = 0;
    if (logData.list) {
      var today = new Date().toISOString().slice(0,10);
      todayLogs = logData.list.filter(l => l.created_at && l.created_at.startsWith(today)).length;
      var durs = logData.list.filter(l => l.duration_ms > 0).map(l => l.duration_ms);
      avgDuration = durs.length ? Math.round(durs.reduce((a,b)=>a+b,0) / durs.length) : 0;
    }
    document.getElementById('stats-box').innerHTML = [
      { n: total, l: '总生成' },
      { n: todayLogs, l: '今日调用' },
      { n: avgDuration ? (avgDuration/1000).toFixed(1)+'s' : '-', l: '平均耗时' },
    ].map(s => '<div class="stat-box"><div class="num">'+s.n+'</div><div class="label">'+s.l+'</div></div>').join('');
  } catch(e) {}
}

async function loadLogs() {
  try {
    var res = await fetch('api/admin.php?action=api_logs&page=1');
    var data = await res.json();
    var tbody = document.getElementById('logs-tbody');
    if (!data.list || !data.list.length) {
      tbody.innerHTML = '<tr><td colspan="4" style="color:var(--text3)">暂无记录</td></tr>';
    } else {
      tbody.innerHTML = data.list.map(r => '<tr><td style="font-size:11px">'+(r.created_at||'').slice(5,16)+'</td><td style="font-size:11px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+(r.endpoint||'').split('/').pop()+'</td><td style="font-size:11px">'+(r.duration_ms>1000?(r.duration_ms/1000).toFixed(1)+'s':r.duration_ms+'ms')+'</td><td style="font-size:11px;color:'+(r.status==='error'?'var(--danger)':'var(--success)')+'">'+(r.status==='error'?'失败':'成功')+'</td></tr>').join('');
    }
  } catch(e) {}
}

async function loadBalance() {
  try {
    var r1 = await fetch('api/user_api.php?action=balance');
    var bal = await r1.json();
    var r2 = await fetch('api/user_api.php?action=balance_logs');
    var logs = await r2.json();
    document.getElementById('balance-content').innerHTML =
      '<div class="stat-row">'+
        '<div class="stat-box"><div class="num" style="color:'+(bal.balance>0?'var(--success)':'var(--danger)')+'">¥'+(bal.balance||0).toFixed(2)+'</div><div class="label">当前余额</div></div>'+
        '<div class="stat-box"><div class="num">'+(bal.total_generated||0)+'</div><div class="label">累计生成</div></div>'+
        '<div class="stat-box"><div class="num">¥'+(bal.total_spent||0).toFixed(2)+'</div><div class="label">累计消费</div></div>'+
      '</div>'+
      '<div style="font-size:12px;color:var(--text3);margin-bottom:8px">每张 ¥0.09</div>'+
      '<table><thead><tr><th>时间</th><th>金额</th><th>说明</th><th>余额</th></tr></thead><tbody>'+
      (logs.length ? logs.map(l => '<tr><td style="font-size:11px">'+(l.created_at||'').slice(5,16)+'</td><td style="color:'+(l.amount>0?'var(--success)':'var(--danger)')+'">'+(l.amount>0?'+':'')+'¥'+Math.abs(l.amount).toFixed(2)+'</td><td style="font-size:11px">'+(l.reason||'')+'</td><td style="font-size:11px">¥'+parseFloat(l.balance_after).toFixed(2)+'</td></tr>').join('') : '<tr><td colspan="4" style="color:var(--text3)">暂无记录</td></tr>')+
      '</tbody></table>';
  } catch(e) {}
}

loadDashboard();
</script>
</body>
</html>
