<?php
if (file_exists(__DIR__ . '/../config.php')) {
    $__cfg = require __DIR__ . '/../config.php';
    if (empty($__cfg['installed'])) { header('Location: ../install.php'); exit; }
} else {
    header('Location: ../install.php'); exit;
}
session_name('IMAGES20_ADMIN');
session_start();
$user = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';
?><!doctype html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <base href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'; ?>">
  <title>后台管理 — Image Studio</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #f5f5f5;
      --card-bg: rgba(255,255,255,0.7);
      --card-border: rgba(0,0,0,0.06);
      --text: #1a1a1a;
      --text-secondary: #666;
      --text-tertiary: #999;
      --danger: #dc2626;
      --success: #16a34a;
      --popup-bg: #fff;
      --popup-border: rgba(0,0,0,0.08);
      --accent-bg: rgba(0,0,0,0.03);
      --font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", sans-serif;
    }
    [data-theme="dark"] {
      --bg: #1a1a1a;
      --card-bg: rgba(255,255,255,0.04);
      --card-border: rgba(255,255,255,0.08);
      --text: #eee;
      --text-secondary: #999;
      --text-tertiary: #777;
      --danger: #f87171;
      --success: #4ade80;
      --popup-bg: #2a2a2a;
      --popup-border: rgba(255,255,255,0.1);
      --accent-bg: rgba(255,255,255,0.04);
    }
    [data-theme="dark"] ::-webkit-scrollbar { width: 6px; }
    [data-theme="dark"] ::-webkit-scrollbar-track { background: #1a1a1a; }
    [data-theme="dark"] ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    [data-theme="dark"] input:not(.login-box input), [data-theme="dark"] select, [data-theme="dark"] textarea {
      background: #2a2a2a; color: #eee; border-color: rgba(255,255,255,0.12);
    }
    [data-theme="dark"] .tabs { background: rgba(255,255,255,0.04); }
    [data-theme="dark"] .tabs button.active { box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
    [data-theme="dark"] .btn-ghost { color: #aaa; border-color: rgba(255,255,255,0.12); }
    [data-theme="dark"] .btn-ghost:hover { border-color: rgba(255,255,255,0.25); color: #eee; }
    [data-theme="dark"] .login-box input { background: #2a2a2a; color: #eee; }
    [data-theme="dark"] .stat-box { background: #2a2a2a; }
    [data-theme="dark"] .dialog-content { background: #2a2a2a; }
    [data-theme="dark"] .dialog-btn-cancel { background: rgba(255,255,255,0.08); color: #ccc; }

    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
    body {
      font-family: var(--font); background: var(--bg); color: var(--text);
      min-height: 100vh; -webkit-font-smoothing: antialiased;
    }
    .app { max-width: 1240px; margin: 0 auto; padding: 40px 24px; }

    /* Header */
    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 36px; flex-wrap: wrap; gap: 16px; }
    .header h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.03em; }
    .btn {
      padding: 8px 18px; border-radius: 100px; font-size: 13px; font-weight: 600;
      font-family: var(--font); cursor: pointer; border: none; background: var(--text); color: #fff;
    }
    .btn:hover { opacity: 0.85; }
    .btn-ghost {
      padding: 8px 16px; border-radius: 100px; font-size: 12px; font-weight: 500;
      font-family: var(--font); cursor: pointer; background: transparent;
      color: var(--text-secondary); border: 1px solid var(--card-border);
    }
    .btn-ghost:hover { border-color: rgba(0,0,0,0.15); color: var(--text); }
    .btn-danger { background: var(--danger); color: #fff; border: none; cursor: pointer; font-family: var(--font); }

    /* Login */
    .login-box {
      max-width: 380px; margin: 60px auto; background: var(--card-bg);
      border: 1px solid var(--card-border); border-radius: 18px; padding: 32px;
    }
    .login-box h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; }
    .login-box input {
      width: 100%; padding: 10px 14px; border: 1px solid var(--card-border);
      border-radius: 10px; font-size: 14px; font-family: var(--font); outline: none;
      margin-bottom: 10px;
    }
    .login-box input:focus { border-color: rgba(0,0,0,0.2); }
    .login-box .switch { text-align: center; margin-top: 12px; font-size: 12px; color: var(--text-tertiary); }
    .login-box .switch a { color: var(--text-secondary); cursor: pointer; text-decoration: underline; }

    /* Panel (shown after login) */
    .panel { display: none; }
    .panel.active { display: block; }

    .user-info { display: flex; align-items: center; gap: 12px; }
    .user-tag { padding: 6px 14px; border-radius: 100px; font-size: 13px; font-weight: 500; background: rgba(0,0,0,0.04); border: 1px solid var(--card-border); }
    .header a { color: var(--text-secondary); text-decoration: none; font-size: 13px; }
    .header a:hover { color: var(--text); }

    .tabs { display: flex; gap: 4px; margin-bottom: 24px; background: rgba(0,0,0,0.03); border-radius: 10px; padding: 4px; width: fit-content; }
    .tabs button { padding: 8px 18px; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: var(--font); background: transparent; color: var(--text-tertiary); }
    .tabs button.active { background: var(--popup-bg); color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

    .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 18px; padding: 24px; margin-bottom: 16px; }
    .card h2 { font-size: 16px; font-weight: 600; margin-bottom: 16px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px 12px; font-size: 13px; border-bottom: 1px solid var(--card-border); }
    th { color: var(--text-tertiary); font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }

    input:not(.login-box input), select {
      padding: 8px 12px; border: 1px solid var(--card-border); border-radius: 8px;
      font-size: 13px; font-family: var(--font); background: var(--popup-bg); outline: none;
    }
    .inline-form { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
    .stat-row { display: flex; gap: 12px; flex-wrap: nowrap; overflow-x: auto; }
    .stat-box { flex: 1; min-width: 100px; text-align: center; background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 14px; }
    .stat-box .num { font-size: 22px; font-weight: 700; }
    .stat-box .label { font-size: 10px; color: var(--text-tertiary); margin-top: 2px; }

    .msg {
      position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
      padding: 16px 32px; border-radius: 14px; font-size: 15px; font-weight: 500;
      z-index: 9999; display: none; text-align: center;
      animation: msgIn 0.25s ease;
    }
    .msg.show { display: block; }
    .msg.ok { background: #1a1a1a; color: #fff; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
    .msg.err { background: var(--danger); color: #fff; box-shadow: 0 8px 32px rgba(220,38,38,0.3); }
    @keyframes msgIn { from { opacity: 0; transform: translate(-50%,-50%) scale(0.9); } to { opacity: 1; transform: translate(-50%,-50%) scale(1); } }

    .config-display { font-size: 14px; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; word-break: break-all; }
    .config-display code { font-size: 13px; }
    .pagination { display: flex; gap: 8px; margin-top: 16px; align-items: center; }
    .pagination span { font-size: 13px; color: var(--text-secondary); }

    /* Dialog */
    .dialog-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9998; align-items: center; justify-content: center; }
    .dialog-content { background: var(--popup-bg); border: 1px solid var(--card-border); border-radius: 16px; padding: 28px; max-width: 400px; width: 90%; }
    .dialog-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
    .dialog-input { width: 100%; padding: 10px 14px; border: 1px solid var(--card-border); border-radius: 10px; font-size: 14px; font-family: var(--font); margin-bottom: 12px; outline: none; }
    .dialog-input:focus { border-color: rgba(0,0,0,0.2); }
    .dialog-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 4px; }
    .dialog-btn { padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 500; cursor: pointer; font-family: var(--font); border: none; }
    .dialog-btn-cancel { background: rgba(0,0,0,0.06); }
    .dialog-btn-confirm { background: var(--text); color: #fff; }

    .err-msg { color: var(--danger); font-size: 13px; margin-bottom: 10px; text-align: center; }

    @media (max-width: 768px) {
      .app { padding: 12px 8px; }
      .card { padding: 16px; }
      .tabs { overflow-x: auto; white-space: nowrap; width: 100%; }
      table { font-size: 11px; }
      th, td { padding: 6px 4px; }
      th:nth-child(4), td:nth-child(4) { max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .header { flex-direction: column; align-items: flex-start; }
      .user-info { flex-wrap: wrap; }
      .stat-row { flex-wrap: wrap; }
      .stat-box { min-width: 60px; padding: 8px; }
    }
  </style>
</head>
<body>
  <div class="app">
    <!-- ====== 登录界面 ====== -->
    <div class="login-box" id="login-box">
      <h2 id="login-title">登录后台</h2>
      <div class="err-msg" id="login-error" style="display:none"></div>
      <form onsubmit="doLogin();return false" style="margin:0">
      <input id="login-username" type="text" placeholder="用户名" autocomplete="username">
      <input id="login-password" type="password" placeholder="密码" autocomplete="current-password">
      <button class="btn" id="login-submit" type="submit" style="width:100%;margin-top:4px;">登录</button>
      </form>
      <div class="switch">没有账号？<a id="login-switch">去前台注册</a></div>
    </div>

    <!-- ====== 管理面板 ====== -->
    <div class="panel <?= $isAdmin ? 'active' : '' ?>" id="admin-panel">
      <div class="header">
        <h1>Image Studio 后台 <span id="api-warning-badge" style="display:none;background:var(--danger);color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;margin-left:8px;font-weight:600;vertical-align:middle">API异常</span></h1>
        <div class="user-info">
          <span class="user-tag" id="admin-username"></span>
          <a href="../index.php">回前台</a>
          <button class="btn-ghost" style="font-size:16px;padding:4px 10px" id="admin-theme-toggle" title="切换主题">&#9788;</button>
          <button class="btn-ghost" onclick="doLogout()">退出</button>
        </div>
      </div>

      <div class="tabs">
        <button class="active" data-tab="images">图片记录</button>
        <button data-tab="users">用户管理</button>
        <button data-tab="stats">用量统计</button>
        <button data-tab="backup">数据备份</button>
        <button data-tab="recharge">充值记录</button>
        <button data-tab="toggles">开关</button>
        <button data-tab="apilog">API 日志</button>
        <button data-tab="logs">操作记录</button>
        <button data-tab="config">API 配置</button>
        <button data-tab="audit">审计日志</button>
        <button data-tab="notifications">通知<span class="notif-badge" id="notif-badge" style="display:none;background:var(--danger);color:#fff;font-size:10px;padding:1px 6px;border-radius:10px;margin-left:4px;font-weight:600">0</span></button>
      </div>

      <div class="msg" id="msg"></div>

      <div class="card tab-content" id="tab-images">
        <h2>生成图片记录
          <button class="btn-ghost" style="margin-left:12px" onclick="showRanking()">由高到低</button>
          <button class="btn-danger" style="padding:4px 12px;font-size:11px;margin-left:8px" onclick="batchDeleteImages()">批量删除</button>
          <select id="image-user-filter" onchange="loadImages()" style="margin-left:8px">
            <option value="">全部用户</option>
          </select>
        </h2>
        <div id="ranking-box" style="display:none;margin-bottom:16px;padding:12px;background:rgba(0,0,0,0.02);border-radius:10px"></div>
        <table><thead><tr><th style="width:30px"><input type="checkbox" id="check-all-images" onchange="toggleCheckAll(this)" title="全选"></th><th>ID</th><th style="width:50px">缩略图</th><th>用户</th><th>文件名</th><th>提示词</th><th>模型</th><th>时间</th><th>操作</th></tr></thead>
          <tbody id="images-tbody"><tr><td colspan="9" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        <div class="pagination" id="images-pager"></div>
      </div>

      <div class="card tab-content" id="tab-users" style="display:none">
        <h2>用户列表</h2>
        <div class="inline-form" style="margin-bottom:12px;justify-content:space-between">
          <form onsubmit="createUser();return false" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
          <input id="new-username" placeholder="用户名" autocomplete="off"><input id="new-password" placeholder="密码" type="password" autocomplete="new-password">
          <select id="new-role"><option value="user">普通用户</option><option value="admin">管理员</option></select>
          <button class="btn" type="submit">添加用户</button>
          </form>
          <div style="display:flex;gap:8px;align-items:center">
          <input id="user-search" placeholder="搜索用户名..." style="width:200px" oninput="searchUsers()">
          <button class="btn" onclick="openBatchTopup()" style="font-size:12px;padding:8px 14px;white-space:nowrap">批量加积分</button>
          </div>
        </div>
        <table><thead><tr><th>ID</th><th>用户名</th><th>角色</th><th>余额</th><th>备注</th><th>最近生图</th><th>最近IP</th><th>最近操作</th><th>注册时间</th><th>操作</th></tr></thead>
          <tbody id="users-tbody"><tr><td colspan="10" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
      </div>

      <div class="card tab-content" id="tab-logs" style="display:none">
        <h2>操作记录</h2>
        <div id="logs-content" style="background:var(--popup-bg);border-radius:16px;padding:24px;max-height:70vh;overflow-y:auto;border:1px solid var(--card-border)">
          <table style="width:100%"><thead><tr><th>操作</th><th>用户</th><th>详情</th><th>模型</th><th>时间</th><th>状态</th></tr></thead>
            <tbody id="logs-tbody"><tr><td colspan="6" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        </div>
      </div>

      <div class="card tab-content" id="tab-apilog" style="display:none">
        <h2>API 调用日志</h2>
        <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);max-height:65vh;overflow-y:auto">
          <table style="width:100%"><thead><tr><th>时间</th><th>用户</th><th>接口</th><th>耗时</th><th>状态码</th><th>结果</th></tr></thead>
            <tbody id="apilog-tbody"><tr><td colspan="6" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        </div>
      </div>

      <div class="card tab-content" id="tab-backup" style="display:none">
        <h2>数据备份</h2>
        <div style="display:flex;flex-direction:column;gap:10px">
          <div style="background:var(--popup-bg);border-radius:10px;padding:14px 16px;border:2px solid #1a1a1a">
            <div style="display:flex;align-items:center;justify-content:space-between">
              <div><div style="font-weight:600;font-size:14px">全部备份</div><div style="font-size:11px;color:var(--text-tertiary)">包含图片记录、用户、日志、配置等所有数据</div></div>
              <a class="btn" href="../api/backup.php?action=full" target="_blank" style="text-decoration:none;font-size:13px;padding:8px 18px">导出全部</a>
            </div>
          </div>
          <div id="single-backups" style="display:flex;flex-direction:column;gap:8px">加载中...</div>
          <div style="display:flex;gap:10px;margin-top:8px">
            <div style="flex:1;background:var(--popup-bg);border-radius:10px;padding:14px;border:1px solid var(--card-border)">
              <div style="font-weight:600;font-size:13px;margin-bottom:6px">导入数据</div>
              <input type="file" id="import-file" accept=".json" style="font-size:12px;margin-bottom:6px">
              <button class="btn" style="font-size:12px;padding:6px 14px" onclick="importData()">执行导入</button>
            </div>
            <div style="flex:1;background:var(--popup-bg);border-radius:10px;padding:14px;border:1px solid #fecaca">
              <div style="font-weight:600;font-size:13px;color:var(--danger);margin-bottom:6px">删除所有数据</div>
              <button class="btn-danger" style="padding:6px 14px;font-size:12px" onclick="deleteAllData()">全部删除</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card tab-content" id="tab-toggles" style="display:none">
        <h2>前台功能开关</h2>
        <div id="toggles-container" style="display:flex;flex-direction:column;gap:8px">加载中...</div>
      </div>

      <div class="card tab-content" id="tab-recharge" style="display:none">
        <h2>充值记录</h2>
        <div class="stat-row" id="recharge-stats" style="margin-bottom:16px"></div>
        <table><thead><tr><th>ID</th><th>用户</th><th>金额</th><th>说明</th><th>时间</th></tr></thead>
          <tbody id="recharge-tbody"><tr><td colspan="5" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
      </div>

      <div class="card tab-content" id="tab-stats" style="display:none">
        <h2>用量统计仪表盘 <a class="btn" href="../api/admin.php?action=stats&format=csv" target="_blank" style="font-size:12px;padding:6px 14px;text-decoration:none;margin-left:12px">导出CSV</a></h2>
        <div id="stats-overview" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap"></div>
        <div id="visits-overview" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap"></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px;margin-top:10px">
          <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:8px">访问时段分布</h3>
            <div id="visit-hours" style="max-height:200px;overflow-y:auto"></div>
          </div>
          <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:8px">访问 Top IP</h3>
            <div id="visit-ips" style="max-height:200px;overflow-y:auto"></div>
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap">
          <span style="font-size:13px;color:var(--text-secondary)">查询某天访问：</span>
          <input type="date" id="visit-date-picker" style="padding:6px 10px;border:1px solid var(--card-border);border-radius:8px;font-size:13px;background:var(--popup-bg);color:var(--text)">
          <button class="btn" style="font-size:12px;padding:6px 14px" onclick="queryVisitDate()">查询</button>
          <span id="visit-date-result" style="font-size:13px;color:var(--success);margin-left:8px"></span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px">
          <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">每日生成量（近30天）</h3>
            <canvas id="chart-daily" height="120"></canvas>
          </div>
          <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">用户活跃度（近30天）</h3>
            <canvas id="chart-users" height="120"></canvas>
          </div>
          <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">模型使用分布</h3>
            <canvas id="chart-models" height="120"></canvas>
          </div>
        </div>
        <div style="background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);margin-top:10px;grid-column:1/-1">
          <h3 style="font-size:12px;font-weight:600;margin-bottom:8px">模型使用详情</h3>
          <table><thead><tr><th>模型</th><th>总生成量</th><th>占比</th></tr></thead>
            <tbody id="model-detail-tbody"><tr><td colspan="3">加载中...</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="card tab-content" id="tab-audit" style="display:none">
        <h2>审计日志</h2>
        <table><thead><tr><th>时间</th><th>管理员</th><th>操作</th><th>目标</th><th>详情</th></tr></thead>
          <tbody id="audit-tbody"><tr><td colspan="5" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        <div class="pagination" id="audit-pager"></div>
      </div>

      <div class="card tab-content" id="tab-notifications" style="display:none">
        <h2>通知中心</h2>
        <div id="notif-content" style="background:var(--popup-bg);border-radius:16px;padding:24px;max-height:70vh;overflow-y:auto;border:1px solid var(--card-border)">加载中...</div>
      </div>

      <div class="card tab-content" id="tab-config" style="display:none">
        <h2>API Key 配置 <button class="btn-ghost" style="margin-left:12px;font-size:11px" onclick="addProfile()">+ 添加配置</button></h2>
        <div id="profiles-container" style="display:flex;flex-direction:column;gap:10px">加载中...</div>
      </div>
    </div>

    <!-- 限制设置弹窗 -->
    <div class="dialog-overlay" id="limit-dialog">
      <div class="dialog-content">
        <div class="dialog-title">设置限制 — <span id="limit-username"></span></div>
        <label style="font-size:12px;color:var(--text-tertiary)">每日生成上限（留空=不限）</label>
        <input class="dialog-input" id="limit-daily" type="number" min="0" placeholder="例如：10">
        <label style="font-size:12px;color:var(--text-tertiary)">总生成上限（留空=不限）</label>
        <input class="dialog-input" id="limit-total" type="number" min="0" placeholder="例如：100">
        <div class="dialog-actions">
          <button class="dialog-btn dialog-btn-cancel" onclick="closeLimitDialog()">取消</button>
          <button class="dialog-btn dialog-btn-confirm" id="limit-save">保存</button>
        </div>
      </div>
    </div>

    <!-- 图片预览弹窗 -->
    <div id="preview-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;flex-direction:column" onclick="closePreview()">
      <span id="preview-counter" style="color:#fff;font-size:13px;margin-bottom:8px;z-index:1"></span>
      <div style="display:flex;align-items:center;gap:16px">
        <button style="background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;width:44px;height:44px;border-radius:50%;cursor:pointer;flex-shrink:0" onclick="previewPrev(event)">&#8249;</button>
        <img id="preview-img" src="" style="max-width:80vw;max-height:85vh;object-fit:contain;border-radius:4px;cursor:default" onclick="event.stopPropagation()">
        <button style="background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;width:44px;height:44px;border-radius:50%;cursor:pointer;flex-shrink:0" onclick="previewNext(event)">&#8250;</button>
      </div>
      <button style="position:absolute;top:20px;right:24px;background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:24px;width:44px;height:44px;border-radius:50%;cursor:pointer" onclick="closePreview()">&#10005;</button>
    </div>
  </div>

  <script>
    let currentUserId = <?= $isAdmin ? $user['id'] : 'null' ?>;

    const msgEl = document.getElementById('msg');
    function showMsg(text, type) {
      msgEl.textContent = text; msgEl.className = 'msg show ' + type;
      setTimeout(() => msgEl.classList.remove('show'), 3000);
    }

    // ====== 登录 ======
    const loginBox = document.getElementById('login-box');
    const adminPanel = document.getElementById('admin-panel');
    const loginError = document.getElementById('login-error');

    async function doLogin() {
      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value.trim();
      if (!username || !password) { loginError.textContent = '请填写用户名和密码'; loginError.style.display = ''; return; }
      loginError.style.display = 'none';

      const submitBtn = document.getElementById('login-submit');
      submitBtn.disabled = true; submitBtn.textContent = '登录中...';
      try {
        const res = await fetch('../api/auth.php?action=login&admin=1', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });
        const data = await res.json();
        if (data.error) { loginError.textContent = data.error; loginError.style.display = ''; return; }
        if (data.role !== 'admin') { loginError.textContent = '需要管理员权限（当前角色：' + (data.role || '未知') + '）'; loginError.style.display = ''; return; }
        currentUserId = data.id;
        document.getElementById('admin-username').textContent = data.username + ' · 管理员';
        loginBox.style.display = 'none';
        adminPanel.classList.add('active');
        loadImages();
      } finally {
        submitBtn.disabled = false; submitBtn.textContent = '登录';
      }
    }

    async function doLogout() {
      await fetch('../api/auth.php?action=logout&admin=1', { method: 'POST' });
      location.reload();
    }

    document.getElementById('login-submit').addEventListener('click', doLogin);
    document.getElementById('login-password').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') doLogin();
    });
    document.getElementById('login-switch').addEventListener('click', () => {
      location.href = '../index.php';
    });

    let imagesPage = 1;

    // 已登录 → 直接显示面板
    // 主题切换（优先 localStorage，其次系统偏好，默认 light）
    function getSystemTheme() {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    function applyTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('app-theme', theme);
      const toggleBtn = document.getElementById('admin-theme-toggle');
      if (toggleBtn) toggleBtn.innerHTML = theme === 'dark' ? '&#9789;' : '&#9788;';
    }
    const savedTheme = localStorage.getItem('app-theme');
    const initialTheme = savedTheme || getSystemTheme();
    applyTheme(initialTheme);
    document.getElementById('admin-theme-toggle').addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme');
      applyTheme(cur === 'dark' ? 'light' : 'dark');
    });

    <?php if ($isAdmin): ?>
    document.getElementById('login-box').style.display = 'none';
    adminPanel.classList.add('active');
    document.getElementById('admin-username').textContent = '<?= htmlspecialchars($user['username']) ?> · 管理员';

    loadImages();
    loadUserFilter();
    <?php endif; ?>

    // ====== 标签页 ======
    document.querySelectorAll('.tabs button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.getElementById('tab-' + btn.dataset.tab).style.display = '';
        if (btn.dataset.tab === 'images') { loadImages(); loadUserFilter(); }
        if (btn.dataset.tab === 'users') loadUsers();
        if (btn.dataset.tab === 'logs') loadAllLogs();
        if (btn.dataset.tab === 'stats') loadStats();
        if (btn.dataset.tab === 'apilog') loadApiLogs();
        if (btn.dataset.tab === 'backup') loadBackups();
        if (btn.dataset.tab === 'toggles') loadToggles();
        if (btn.dataset.tab === 'recharge') loadRecharge();
        if (btn.dataset.tab === 'config') loadConfig();
        if (btn.dataset.tab === 'audit') loadAuditLogs();
        if (btn.dataset.tab === 'notifications') { loadNotifications(); loadNotificationBadge(); }
      });
    });

    // ====== 图片列表 ======
    async function loadImages(page = 1) {
      imagesPage = page;
      const uid = document.getElementById('image-user-filter')?.value || '';
      let url = `../api/admin.php?action=images&page=${page}`;
      if (uid) url += '&user_id=' + encodeURIComponent(uid);
      const res = await fetch(url);
      const data = await res.json();
      allImages = data.list || [];
      const tbody = document.getElementById('images-tbody');
      if (!data.list || !data.list.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="color:var(--text-tertiary)">暂无记录</td></tr>';
      } else {
        tbody.innerHTML = data.list.map((r, i) => `
          <tr>
            <td><input type="checkbox" class="img-check" value="${r.id}" onchange="updateCheckAll()"></td>
            <td>${r.id}</td>
            <td style="width:50px"><img src="../load.php?file=${encodeURIComponent(r.filename)}&user=${encodeURIComponent(r.username)}&thumb=1" style="width:40px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer" onclick="showImageDetail(${r.id})" onerror="this.style.display='none'" loading="lazy" title="点击查看详情"></td>
            <td style="cursor:pointer" onclick="showUserDetail(${r.user_id})">${esc(r.username)}</td>
            <td style="font-family:monospace;font-size:12px;cursor:pointer;text-decoration:underline;color:#3b82f6" onclick="previewImage('${esc(r.filename)}','${esc(r.username)}',${i})">${esc(r.filename)}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;text-decoration:underline;color:#3b82f6" onclick="showImageDetail(${r.id})">${esc(r.prompt||'')}</td>
            <td style="font-size:12px">${esc(r.model||'')}</td>
            <td style="font-size:12px">${r.created_at||''}</td>
            <td><button class="btn-danger" style="padding:4px 10px;font-size:11px;border-radius:6px" onclick="deleteImage(${r.id})">删除</button></td>
          </tr>`).join('');
      }
      const pager = document.getElementById('images-pager');
      const totalFiltered = data.total || 0;
      pager.innerHTML = `
        <button class="btn-ghost" ${data.page <= 1 ? 'disabled' : ''} onclick="loadImages(${data.page - 1})">上一页</button>
        <span>第 ${data.page} / ${data.pages || 1} 页 · 共 ${totalFiltered} 条</span>
        <button class="btn-ghost" ${data.page >= data.pages ? 'disabled' : ''} onclick="loadImages(${data.page + 1})">下一页</button>`;
      document.getElementById('check-all-images').checked = false;
    }

    // ====== 加载用户筛选下拉框 ======
    async function loadUserFilter() {
      var sel = document.getElementById('image-user-filter');
      if (!sel) return;
      var currentVal = sel.value;
      try {
        var res = await fetch('../api/admin.php?action=users');
        var users = await res.json();
        sel.innerHTML = '<option value="">全部用户</option>' +
          users.map(function(u) {
            return '<option value="' + u.id + '">' + esc(u.username) + '</option>';
          }).join('');
        sel.value = currentVal;
      } catch (e) {}
    }

    async function deleteImage(id) {
      if (!confirm('确定删除？也会删除服务器上的图片文件')) return;
      await fetch(`../api/admin.php?action=images&id=${id}`, { method: 'DELETE' });
      showMsg('已删除', 'ok'); loadImages(imagesPage);
    }

    // ====== 批量删除 ======
    function toggleCheckAll(el) {
      document.querySelectorAll('.img-check').forEach(cb => cb.checked = el.checked);
    }
    function updateCheckAll() {
      var all = document.querySelectorAll('.img-check');
      var checked = document.querySelectorAll('.img-check:checked');
      document.getElementById('check-all-images').checked = all.length > 0 && checked.length === all.length;
    }
    async function batchDeleteImages() {
      var checked = document.querySelectorAll('.img-check:checked');
      if (checked.length === 0) { showMsg('请先勾选要删除的图片', 'err'); return; }
      if (!confirm('确定批量删除 ' + checked.length + ' 张图片？也会删除服务器上的图片文件')) return;
      var ids = Array.from(checked).map(cb => parseInt(cb.value));
      var res = await fetch('../api/admin.php?action=images', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: ids })
      });
      var data = await res.json();
      showMsg('已删除 ' + data.count + ' 张图片', 'ok');
      loadImages(imagesPage);
    }

    // ====== 图片详情弹窗 ======
    async function showImageDetail(id) {
      var r = allImages.find(function(img) { return img.id === id; });
      if (!r) {
        var res = await fetch('../api/admin.php?action=image_detail&id=' + id);
        r = await res.json();
        if (r.error) { showMsg(r.error, 'err'); return; }
        r.username = r.username || '';
      }
      var imgUrl = '../load.php?file=' + encodeURIComponent(r.filename) + '&user=' + encodeURIComponent(r.username);
      var overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center';
      overlay.innerHTML = '<div style="background:var(--popup-bg);border-radius:16px;padding:24px;max-width:90vw;max-height:90vh;overflow-y:auto;border:1px solid var(--card-border);display:flex;flex-wrap:wrap;gap:20px">'
        + '<div style="flex:1;min-width:250px;max-width:500px">'
        + '<img src="' + imgUrl + '" style="width:100%;max-height:70vh;object-fit:contain;border-radius:8px">'
        + '</div>'
        + '<div style="flex:1;min-width:250px;max-width:400px">'
        + '<h3 style="margin-bottom:12px;font-size:16px">图片详情 #' + r.id + '</h3>'
        + '<table style="width:100%">'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px;width:60px">提示词</td><td style="font-size:13px;word-break:break-all;line-height:1.5">' + esc(r.prompt||'无') + '</td></tr>'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px">模型</td><td style="font-size:13px">' + esc(r.model||'未知') + '</td></tr>'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px">分辨率</td><td style="font-size:13px">' + esc(r.resolution||'—') + '</td></tr>'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px">用户</td><td style="font-size:13px">' + esc(r.username||'') + '</td></tr>'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px">文件名</td><td style="font-size:12px;font-family:monospace;word-break:break-all">' + esc(r.filename||'') + '</td></tr>'
        + '<tr><td style="color:var(--text-tertiary);padding:4px 0;font-size:12px">创建时间</td><td style="font-size:12px">' + (r.created_at||'') + '</td></tr>'
        + '</table>'
        + '<div style="margin-top:12px;display:flex;gap:8px">'
        + '<a href="' + imgUrl + '" target="_blank" class="btn-ghost" style="text-decoration:none;display:inline-block">查看原图</a>'
        + '<button class="btn-danger" style="padding:6px 14px;font-size:12px;border-radius:8px" id="detail-delete-btn">删除</button>'
        + '</div></div></div>';
      document.body.appendChild(overlay);
      overlay.querySelector('#detail-delete-btn').onclick = async function() {
        if (!confirm('确定删除？也会删除服务器上的图片文件')) return;
        await fetch('../api/admin.php?action=images&id=' + id, { method: 'DELETE' });
        overlay.remove();
        showMsg('已删除', 'ok'); loadImages(imagesPage);
      };
      overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
    }

    // ====== 用户列表 ======
    function searchUsers() {
      loadUsers(document.getElementById('user-search').value);
    }
    async function loadUsers(search = '') {
      const res = await fetch('../api/admin.php?action=users' + (search ? '&search=' + encodeURIComponent(search) : ''));
      const list = await res.json();
      const tbody = document.getElementById('users-tbody');
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="10" style="color:var(--text-tertiary)">暂无用户</td></tr>';
      } else {
        // 并行加载每个用户的最新操作
        const logs = await Promise.all(list.map(u =>
          fetch(`../api/admin.php?action=user_logs&uid=${u.id}&latest=1`).then(r => r.json()).catch(() => [])
        ));
        tbody.innerHTML = list.map((u, i) => {
          const latest = logs[i]?.[0];
          const logText = latest
            ? `<span style="font-size:11px">${esc(latest.filename||'')}${latest.deleted_at ? ' <span style="color:var(--danger)">已删</span>' : ''}</span><br><span style="font-size:10px;color:var(--text-tertiary)">${latest.created_at||''}</span>`
            : '<span style="color:var(--text-tertiary);font-size:11px">暂无</span>';
          const lastGen = latest?.created_at || '-';
          const notesHtml = u.notes
            ? `<span style="cursor:pointer;font-size:12px" onclick="editNotes(${u.id}, this)">${esc(u.notes)}</span>`
            : `<span style="cursor:pointer;font-size:12px;color:var(--text-tertiary)" onclick="editNotes(${u.id}, this)">-</span>`;
          return `
          <tr>
            <td>${u.id}</td><td>${esc(u.username)}</td>
            <td>${u.role === 'admin' ? '管理员' : '用户'}</td>
            <td style="cursor:pointer;color:var(--success);font-weight:500" onclick="editBalance(${u.id},'${esc(u.username)}',${parseFloat(u.balance||0).toFixed(2)})" title="点击修改余额">¥${parseFloat(u.balance||0).toFixed(2)}</td>
            <td>${notesHtml}</td>
            <td style="font-size:12px">${lastGen}</td>
            <td style="font-size:11px;font-family:monospace">${esc(u.last_ip||'-')}</td>
            <td>${logText} <button class="btn-ghost" style="padding:1px 6px;font-size:10px;margin-left:4px" onclick="viewUserLogs(${u.id},'${esc(u.username)}')">查看全部</button></td>
            <td style="font-size:12px">${u.created_at||''}</td>
            <td>
              <button class="btn-ghost" style="padding:4px 8px;font-size:11px;margin-right:4px" onclick="openTopupDialog(${u.id},'${esc(u.username)}')">充值</button>
          <button class="btn-ghost" style="padding:4px 8px;font-size:11px;margin-right:4px" onclick="openLimitDialog(${u.id},'${esc(u.username)}')">限制</button>
          <button class="btn-ghost" style="padding:4px 8px;font-size:11px;margin-right:4px" onclick="resetUserPw(${u.id},'${esc(u.username)}')">改密</button>
              ${u.id !== currentUserId ? '<button class="btn-danger" style="padding:4px 10px;font-size:11px;border-radius:6px" onclick="deleteUser('+u.id+')">删除</button>' : ''}
            </td>
          </tr>`;
        }).join('');
      }
    }

    async function viewUserLogs(uid, username) {
      const res = await fetch(`../api/admin.php?action=user_logs&uid=${uid}`);
      const list = await res.json();
      const html = list.length ? list.map(r => `
        <tr>
          <td style="font-size:11px">${esc(r.filename)}</td>
          <td style="font-size:11px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.prompt||'')}</td>
          <td style="font-size:11px">${r.created_at||''}</td>
          <td style="font-size:11px">${r.deleted_at ? '<span style="color:var(--danger)">已删除</span>' : '<span style="color:var(--success)">正常</span>'}</td>
        </tr>`).join('') : '<tr><td colspan="4" style="color:var(--text-tertiary)">暂无记录</td></tr>';
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center';
      overlay.innerHTML = `
        <div style="background:var(--popup-bg);border-radius:16px;padding:24px;max-width:700px;width:90%;max-height:80vh;overflow-y:auto">
          <h2 style="margin-bottom:16px">${esc(username)} 的操作记录（共${list.length}条）</h2>
          <table><thead><tr><th>文件名</th><th>提示词</th><th>时间</th><th>状态</th></tr></thead><tbody>${html}</tbody></table>
          <div style="text-align:right;margin-top:16px"><button class="btn" id="close-logs">关闭</button></div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelector('#close-logs').onclick = () => overlay.remove();
      overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    }

    async function createUser() {
      const username = document.getElementById('new-username').value.trim();
      const password = document.getElementById('new-password').value.trim();
      const role = document.getElementById('new-role').value;
      if (!username || !password) return showMsg('请填写用户名和密码', 'err');
      const res = await fetch('../api/admin.php?action=users', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, role })
      });
      const data = await res.json();
      if (data.error) { showMsg(data.error, 'err'); return; }
      showMsg('用户已创建', 'ok'); loadUsers();
      document.getElementById('new-username').value = '';
      document.getElementById('new-password').value = '';
    }

    function editBalance(uid, username, current) {
      var val = prompt('修改「'+username+'」的余额（当前 ¥'+current.toFixed(2)+'）：', current.toFixed(2));
      if (val === null || isNaN(parseFloat(val))) return;
      fetch('../api/user_api.php?action=set_balance', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({user_id:uid, balance:parseFloat(val), reason:'管理员手动调整'})
      }).then(r => r.json()).then(d => {
        if (d.ok) showMsg('余额已更新为 ¥'+d.balance.toFixed(2), 'ok'), loadUsers();
        else showMsg(d.error||'失败','err');
      });
    }

    function editNotes(uid, el) {
      const current = (el.textContent === '-' || el.textContent === '') ? '' : el.textContent;
      const input = document.createElement('input');
      input.value = current;
      input.maxLength = 500;
      input.style.cssText = 'padding:3px 6px;border:1px solid var(--card-border);border-radius:4px;font-size:12px;width:120px;font-family:var(--font)';
      el.replaceWith(input);
      input.focus();
      input.addEventListener('blur', async () => {
        const notes = input.value.trim();
        const span = document.createElement('span');
        span.textContent = notes || '-';
        span.style.cssText = notes ? 'cursor:pointer;font-size:12px' : 'cursor:pointer;font-size:12px;color:var(--text-tertiary)';
        span.onclick = () => editNotes(uid, span);
        input.replaceWith(span);
        await fetch('../api/admin.php?action=update_note', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body:JSON.stringify({user_id:uid, notes})
        });
      });
      input.addEventListener('keydown', e => { if (e.key === 'Enter') input.blur(); });
    }

    // ====== 批量加积分 ======
    let batchUserData = [];
    async function openBatchTopup() {
      const res = await fetch('../api/admin.php?action=users');
      batchUserData = await res.json();
      const listHtml = batchUserData.map(u => `
        <label style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;border-bottom:1px solid var(--card-border)">
          <input type="checkbox" class="batch-user-cb" value="${u.id}" checked>
          <span style="flex:1">${esc(u.username)}</span>
          <span style="color:var(--text-tertiary);font-size:11px">余额: ¥${parseFloat(u.balance||0).toFixed(2)}</span>
        </label>`).join('');
      const overlay = document.createElement('div');
      overlay.id = 'batch-topup-overlay';
      overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center';
      overlay.innerHTML = `<div style="background:var(--popup-bg);border-radius:16px;padding:28px;max-width:480px;width:90%;max-height:85vh;display:flex;flex-direction:column">
        <h2 style="margin-bottom:16px;font-size:16px;font-weight:600">批量加积分</h2>
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px">
          <input type="checkbox" id="batch-select-all" checked onchange="document.querySelectorAll('.batch-user-cb').forEach(cb=>cb.checked=this.checked)">
          <label for="batch-select-all" style="font-size:12px;font-weight:500">全选</label>
        </div>
        <div id="batch-user-list" style="max-height:300px;overflow-y:auto;margin-bottom:16px">${listHtml}</div>
        <div style="display:flex;gap:8px;margin-bottom:12px">
          <input id="batch-amount" type="number" step="0.01" min="0.01" placeholder="金额（元）" style="flex:1;padding:8px 12px;border:1px solid var(--card-border);border-radius:8px;font-size:13px;font-family:var(--font)">
          <input id="batch-reason" placeholder="原因" value="批量加积分" style="flex:1;padding:8px 12px;border:1px solid var(--card-border);border-radius:8px;font-size:13px;font-family:var(--font)">
        </div>
        <div class="err-msg" id="batch-error" style="display:none"></div>
        <div style="display:flex;gap:12px;justify-content:flex-end">
          <button class="dialog-btn dialog-btn-cancel" onclick="document.getElementById('batch-topup-overlay').remove()">取消</button>
          <button class="dialog-btn dialog-btn-confirm" id="batch-confirm">确认加积分</button>
        </div>
      </div>`;
      document.body.appendChild(overlay);
      overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
      overlay.querySelector('#batch-confirm').addEventListener('click', async () => {
        const user_ids = Array.from(overlay.querySelectorAll('.batch-user-cb:checked')).map(cb => parseInt(cb.value));
        const amount = parseFloat(overlay.querySelector('#batch-amount').value);
        const reason = overlay.querySelector('#batch-reason').value.trim() || '批量加积分';
        const errEl = overlay.querySelector('#batch-error');
        if (!user_ids.length) { errEl.textContent = '请至少选择一个用户'; errEl.style.display = ''; return; }
        if (!amount || amount <= 0) { errEl.textContent = '金额必须大于 0'; errEl.style.display = ''; return; }
        errEl.style.display = 'none';
        const res2 = await fetch('../api/admin.php?action=batch_topup', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body:JSON.stringify({user_ids, amount, reason})
        });
        const d = await res2.json();
        if (d.error) { errEl.textContent = d.error; errEl.style.display = ''; return; }
        showMsg('已为 ' + d.count + ' 位用户各加 ¥' + amount.toFixed(2), 'ok');
        overlay.remove();
        loadUsers();
      });
      overlay.addEventListener('keydown', e => { if (e.key === 'Escape') overlay.remove(); });
    }

    function resetUserPw(uid, username) {
      var pw = prompt('为用户「'+username+'」设置新密码（至少4位）：');
      if (!pw || pw.length < 4) { showMsg('密码至少4位', 'err'); return; }
      fetch('../api/user_api.php?action=reset_pw', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({user_id:uid, new_password:pw})
      }).then(r => r.json()).then(d => {
        if (d.ok) showMsg('密码已重置为: '+pw, 'ok'); else showMsg(d.error||'失败','err');
      });
    }

    function openTopupDialog(uid, username) {
      var amount = prompt('为用户「'+username+'」充值金额（元）：', '1.00');
      if (!amount || isNaN(parseFloat(amount))) return;
      fetch('../api/user_api.php?action=topup', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({user_id:uid, amount:parseFloat(amount)})
      }).then(r => r.json()).then(d => {
        if (d.ok) showMsg('充值成功', 'ok'); else showMsg(d.error||'失败','err');
      });
    }

    async function deleteUser(id) {
      if (!confirm('确定删除该用户？会同时删除其所有图片记录')) return;
      await fetch(`../api/admin.php?action=users&id=${id}`, { method: 'DELETE' });
      showMsg('已删除', 'ok'); loadUsers();
    }

    // ====== API 配置（多 Profile）=====
    async function loadConfig() {
      const res = await fetch('../api/admin.php?action=config');
      const data = await res.json();
      const container = document.getElementById('profiles-container');
      const active = data.active || 'default';
      const profiles = data.profiles || {};
      container.innerHTML = Object.entries(profiles).map(([name, p]) => `
        <div style="background:var(--popup-bg);border-radius:10px;padding:14px 16px;border:2px solid ${name === active ? '#1a1a1a' : 'var(--card-border)'}">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <span style="font-weight:600;font-size:14px">${esc(name)} ${name === active ? '<span style="font-size:10px;color:#fff;background:#1a1a1a;padding:2px 8px;border-radius:100px;margin-left:6px">当前</span>' : ''}</span>
            <div>
              ${name !== active ? '<button class="btn-ghost" style="font-size:10px;padding:3px 10px" onclick="switchProfile(\''+esc(name)+'\')">启用</button>' : ''}
              ${name !== 'default' ? '<button class="btn-danger" style="font-size:10px;padding:3px 10px;margin-left:4px" onclick="deleteProfile(\''+esc(name)+'\')">删除</button>' : ''}
            </div>
          </div>
          <div style="font-size:12px;color:var(--text-tertiary)">Key: ${esc(p.key_masked)}</div>
          <div style="font-size:12px;color:var(--text-tertiary)">URL: ${esc(p.base_url)}</div>
          <div style="margin-top:8px;display:flex;gap:6px">
            <input id="key-${esc(name)}" placeholder="新 Key" style="flex:1;font-size:11px">
            <input id="url-${esc(name)}" placeholder="新 URL" style="flex:1;font-size:11px">
            <button class="btn" style="font-size:11px;padding:4px 12px" onclick="saveProfile('${esc(name)}')">保存</button>
          </div>
        </div>`).join('');
    }

    async function saveProfile(name) {
      const api_key = document.getElementById('key-'+name)?.value?.trim() || '';
      const base_url = document.getElementById('url-'+name)?.value?.trim() || '';
      if (!api_key && !base_url) return showMsg('至少填写一项', 'err');
      await fetch('../api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'save', name, api_key, base_url })
      });
      showMsg('已保存', 'ok'); loadConfig();
    }
    async function switchProfile(name) {
      await fetch('../api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'switch', name })
      });
      showMsg('已切换到: '+name, 'ok'); loadConfig();
    }
    async function addProfile() {
      const name = prompt('新配置名称（如：备用Key、国内线路）：');
      if (!name) return;
      await fetch('../api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'add', name })
      });
      showMsg('已添加', 'ok'); loadConfig();
    }
    async function deleteProfile(name) {
      if (!confirm('确定删除「'+name+'」？')) return;
      await fetch('../api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'delete', name })
      });
      showMsg('已删除', 'ok'); loadConfig();
    }

    // ====== 限制设置 ======
    let limitUserId = null;
    async function openLimitDialog(uid, username) {
      limitUserId = uid;
      document.getElementById('limit-username').textContent = username;
      document.getElementById('limit-daily').value = '';
      document.getElementById('limit-total').value = '';
      document.getElementById('limit-dialog').style.display = 'flex';
      try {
        const res = await fetch(`../api/admin.php?action=limits&uid=${uid}`);
        const data = await res.json();
        if (data) {
          if (data.daily_limit !== null) document.getElementById('limit-daily').value = data.daily_limit;
          if (data.total_limit !== null) document.getElementById('limit-total').value = data.total_limit;
        }
      } catch (_) {}
    }
    function closeLimitDialog() { document.getElementById('limit-dialog').style.display = 'none'; }
    document.getElementById('limit-dialog').addEventListener('click', e => { if (e.target.id === 'limit-dialog') closeLimitDialog(); });
    document.getElementById('limit-save').addEventListener('click', async () => {
      const daily = document.getElementById('limit-daily').value.trim();
      const total = document.getElementById('limit-total').value.trim();
      await fetch('../api/admin.php?action=limits', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: limitUserId, daily_limit: daily || null, total_limit: total || null })
      });
      showMsg('限制已保存', 'ok');
      closeLimitDialog();
    });

    // ====== 全量操作记录 ======
    let logsPage = 1;
    async function loadAllLogs(page = 1) {
      logsPage = page;
      const res = await fetch(`../api/admin.php?action=all_logs&page=${page}`);
      const data = await res.json();
      const tbody = document.getElementById('logs-tbody');
      if (!data.list || !data.list.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:var(--text-tertiary)">暂无记录</td></tr>';
      } else {
        tbody.innerHTML = data.list.map(r => {
          const actionColor = r.action === '注册' ? '#3b82f6' : r.action === '删除' ? 'var(--danger)' : 'var(--success)';
          const detail = r.action === '注册' ? '新用户注册' : r.action === '删除' ? `删除: ${esc(r.filename||'')}` : esc(r.filename||'');
          return `<tr>
            <td><span style="font-size:11px;color:${actionColor};font-weight:500">${r.action}</span></td>
            <td>${esc(r.username)}</td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px">${detail}</td>
            <td style="font-size:11px">${esc(r.model||'-')}</td>
            <td style="font-size:11px">${r.time||''}</td>
            <td>${r.deleted_at ? '<span style="color:var(--danger);font-size:11px">已删除</span>' : '<span style="color:var(--success);font-size:11px">正常</span>'}</td>
          </tr>`;
        }).join('');
      }
      const container = document.getElementById('logs-content');
      let pager = container.querySelector('.pager-row');
      if (data.pages > 1) {
        if (!pager) { pager = document.createElement('div'); pager.className = 'pager-row'; container.appendChild(pager); }
        pager.style.cssText = 'display:flex;align-items:center;justify-content:center;gap:8px;margin-top:16px';
        pager.innerHTML = `
          <button class="btn-ghost" ${data.page <= 1 ? 'disabled' : ''} id="logs-prev">上一页</button>
          <span style="font-size:12px;color:var(--text-tertiary)">${data.page} / ${data.pages} · 共${data.total}条</span>
          <button class="btn-ghost" ${data.page >= data.pages ? 'disabled' : ''} id="logs-next">下一页</button>`;
        pager.querySelector('#logs-prev')?.addEventListener('click', () => loadAllLogs(data.page - 1));
        pager.querySelector('#logs-next')?.addEventListener('click', () => loadAllLogs(data.page + 1));
      } else if (pager) { pager.remove(); }
    }

    // ====== API 日志 ======
    let apiLogPage = 1;
    async function loadApiLogs(page = 1) {
      apiLogPage = page;
      const res = await fetch(`../api/admin.php?action=api_logs&page=${page}`);
      const data = await res.json();
      const tbody = document.getElementById('apilog-tbody');
      if (!data.list || !data.list.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:var(--text-tertiary)">暂无日志</td></tr>';
      } else {
        tbody.innerHTML = data.list.map(r => {
          const durationColor = r.duration_ms > 30000 ? 'var(--danger)' : r.duration_ms > 10000 ? '#f59e0b' : 'var(--success)';
          return `<tr>
            <td style="font-size:11px">${(r.created_at||'').replace(' ','<br>')}</td>
            <td style="font-size:11px">${esc(r.username||'-')}</td>
            <td style="font-size:11px;font-family:monospace;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.endpoint||'')}</td>
            <td style="font-size:11px;color:${durationColor}">${r.duration_ms > 1000 ? (r.duration_ms/1000).toFixed(1)+'s' : r.duration_ms+'ms'}</td>
            <td style="font-size:11px">${r.http_code||''}</td>
            <td><span style="font-size:11px;color:${r.status==='error'?'var(--danger)':'var(--success)'}">${r.status==='error'?'失败':'成功'}</span></td>
          </tr>`;
        }).join('');
      }
      // 翻页
      const pagerEl = document.getElementById('apilog-pager');
      if (data.pages > 1) {
        let html = `<button class="btn-ghost" ${data.page<=1?'disabled':''} id="apilog-prev">上一页</button>
          <span style="font-size:12px;color:var(--text-tertiary)">${data.page}/${data.pages} · ${data.total}条</span>
          <button class="btn-ghost" ${data.page>=data.pages?'disabled':''} id="apilog-next">下一页</button>`;
        if (!pagerEl) { const p = document.createElement('div'); p.id = 'apilog-pager'; p.style.cssText = 'text-align:center;margin-top:12px'; p.innerHTML = html; tbody.parentElement.parentElement.appendChild(p); }
        else pagerEl.innerHTML = html;
        setTimeout(() => {
          document.getElementById('apilog-prev')?.addEventListener('click', () => loadApiLogs(data.page-1));
          document.getElementById('apilog-next')?.addEventListener('click', () => loadApiLogs(data.page+1));
        }, 0);
      } else if (pagerEl) pagerEl.remove();
    }

    async function deleteAllData() {
      const a = prompt('确定删除所有数据？输入 确认删除 后点确定');
      if (a !== '确认删除') { showMsg('已取消', 'err'); return; }
      const b = prompt('再次确认：输入 DELETE');
      if (b !== 'DELETE') { showMsg('已取消', 'err'); return; }
      const res = await fetch('../api/backup.php?action=delete_all', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ confirm: 'YES_DELETE_ALL' })
      });
      if (res.ok) { showMsg('已删除所有数据，admin 账号已重置为 admin/admin123', 'ok'); }
      else showMsg('删除失败', 'err');
    }

    async function importData() {
      const file = document.getElementById('import-file').files[0];
      if (!file) { showMsg('请先选择 JSON 文件', 'err'); return; }
      const fd = new FormData(); fd.append('file', file);
      const res = await fetch('../api/backup.php?action=import', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) { showMsg(`导入完成，${data.imported} 条`, 'ok'); }
      else showMsg(data.error || '导入失败', 'err');
    }

    async function loadBackups() {
      const res = await fetch('../api/backup.php?action=tables');
      const tables = await res.json();
      const container = document.getElementById('single-backups');
      container.innerHTML = Object.entries(tables).map(([t, label]) => `
        <div style="background:var(--popup-bg);border-radius:10px;padding:10px 14px;border:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:13px;font-weight:500">${label}</span>
          <a class="btn-ghost" style="font-size:11px;text-decoration:none" href="../api/backup.php?action=single&t=${t}" target="_blank">导出</a>
        </div>`).join('');
    }

    // ====== 功能开关 ======
    const toggleDefs = {
      show_folder_card:  { label: '保存位置卡片', desc: '前台右侧「图片存储」卡片' },
      show_presets:      { label: '快捷工具',     desc: '前台快捷场景 & 提示词库' },
      disable_register:  { label: '禁止注册',     desc: '开启后新用户无法注册', hasMsg: true, msgKey: 'register_block_msg', msgDefault: '暂时停止注册' },
      banned_ips:        { label: 'IP 黑名单',    desc: '禁止这些IP注册（逗号分隔）', hasInput: true, inputKey: 'banned_ips_list', inputPlaceholder: '192.168.1.1, 10.0.0.5', isTextarea: true },
      daily_reg_limit:   { label: '每日注册上限', desc: '每天最多注册数（0=不限）', hasInput: true, inputKey: 'daily_reg_max', inputPlaceholder: '0', isNumber: true },
      global_daily_limit:{ label: '全局每日生图上限', desc: '所有用户每天合计最多生图数（0=不限）', hasInput: true, inputKey: 'global_daily_max', inputPlaceholder: '0', isNumber: true },
      global_total_limit:{ label: '全局总生图上限', desc: '所有用户合计最多生图数（0=不限）', hasInput: true, inputKey: 'global_total_max', inputPlaceholder: '0', isNumber: true },
      new_user_free:    { label: '新用户免费生图数', desc: '注册后每人可免费生图次数（默认1）', hasInput: true, inputKey: 'new_user_free_count', inputPlaceholder: '1', isNumber: true },
      log_retention_days: { label: '日志保留天数', desc: 'API日志和操作记录保留天数（默认30）', hasInput: true, inputKey: 'log_retention_days', inputPlaceholder: '30', isNumber: true },
    };
    async function loadToggles() {
      const res = await fetch('../api/admin.php?action=features');
      const features = await res.json();
      const container = document.getElementById('toggles-container');
      container.innerHTML = Object.entries(toggleDefs).map(([key, def]) => {
        const on = features[key] !== false;
        const msgVal = def.hasMsg ? (features[def.msgKey] || def.msgDefault) : '';
        return `<div style="background:var(--popup-bg);border-radius:8px;padding:12px 16px;border:1px solid var(--card-border)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:${def.hasMsg ? '10px' : '0'}">
            <div><div style="font-weight:500;font-size:14px">${def.label}</div><div style="font-size:11px;color:var(--text-tertiary)">${def.desc}</div></div>
            <button id="tg-${key}" style="width:52px;height:28px;border-radius:14px;border:none;cursor:pointer;transition:background .2s;background:${on?'#1a1a1a':'#ddd'};position:relative;flex-shrink:0" onclick="toggleFeature('${key}',${!on})">
              <span style="position:absolute;top:3px;left:${on?'27px':'3px'};width:22px;height:22px;border-radius:50%;background:var(--popup-bg);transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
            </button>
          </div>
          ${def.hasMsg ? `<div style="display:flex;gap:6px"><input id="msg-${def.msgKey}" value="${esc(msgVal)}" placeholder="${def.msgDefault}" style="flex:1;font-size:12px"><button class="btn" style="font-size:11px;padding:4px 12px" onclick="saveFeatureMsg('${def.msgKey}')">保存</button></div>` : ''}
          ${def.hasInput ? `<div style="display:flex;gap:6px">${def.isTextarea ? `<textarea id="inp-${def.inputKey}" placeholder="${def.inputPlaceholder}" style="flex:1;font-size:12px;min-height:40px;resize:vertical">${esc(features[def.inputKey]||'')}</textarea>` : `<input id="inp-${def.inputKey}" value="${esc(features[def.inputKey]||'')}" placeholder="${def.inputPlaceholder}" style="flex:1;font-size:12px" type="${def.isNumber?'number':'text'}" min="0">`}<button class="btn" style="font-size:11px;padding:4px 12px" onclick="saveInputFeature('${def.inputKey}')">保存</button></div>` : ''}
        </div>`;
      }).join('');
    }
    async function toggleFeature(key, val) {
      await fetch('../api/admin.php?action=features', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key, value: val })
      });
      loadToggles();
    }
    async function saveFeatureMsg(key) {
      const val = document.getElementById('msg-'+key)?.value?.trim() || '';
      await fetch('../api/admin.php?action=features', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key, value: val || 'no_msg' })
      });
      showMsg('已保存', 'ok'); loadToggles();
    }
    async function saveInputFeature(key) {
      const el = document.getElementById('inp-'+key);
      const val = el?.value?.trim() || '';
      await fetch('../api/admin.php?action=features', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key, value: val || '0' })
      });
      showMsg('已保存', 'ok'); loadToggles();
    }

    async function loadRecharge() {
      const res = await fetch('../api/admin.php?action=recharge_logs');
      const data = await res.json();
      const list = data.list || [];
      const s = data.summary || {};
      document.getElementById('recharge-stats').innerHTML = [
        { n:'¥'+parseFloat(s.total).toFixed(2), l:'累计充值' },
        { n:'¥'+parseFloat(s.today).toFixed(2), l:'今日充值' },
        { n:'¥'+parseFloat(s.week7).toFixed(2), l:'7天内' },
        { n:'¥'+parseFloat(s.month30).toFixed(2), l:'30天内' },
      ].map(d => '<div class="stat-box"><div class="num" style="font-size:22px;color:var(--success)">'+d.n+'</div><div class="label">'+d.l+'</div></div>').join('');
      const tbody = document.getElementById('recharge-tbody');
      tbody.innerHTML = list.length
        ? list.map(r => `<tr><td>${r.id}</td><td>${esc(r.username)}</td><td style="color:var(--success);font-weight:500">+¥${parseFloat(r.amount).toFixed(2)}</td><td style="font-size:11px">${esc(r.reason||'')}</td><td style="font-size:11px">${r.created_at||''}</td></tr>`).join('')
        : '<tr><td colspan="5" style="color:var(--text-tertiary)">暂无记录</td></tr>';
    }

    // ====== 统计仪表盘 ======
    let statsCharts = {};
    async function loadStats() {
      const res = await fetch('../api/admin.php?action=stats');
      const data = await res.json();
      console.log('[stats]', data);

      // 等待容器可见后渲染
      await new Promise(r => requestAnimationFrame(r));

      // 概览卡片
      const ov = data.overview || {};
      document.getElementById('stats-overview').innerHTML = [
        { label: '总生成量', value: ov.total_images || 0 },
        { label: '今日生成', value: ov.today_images || 0 },
        { label: '总用户数', value: ov.total_users || 0 },
        { label: '已删图片', value: ov.deleted_images || 0 },
        { label: 'API 状态', value: (data.api_health?.status === 'ok' ? '正常' : data.api_health?.status === 'invalid' ? 'Key无效' : '异常'), extra: data.api_health?.status === 'ok' ? 'var(--success)' : 'var(--danger)' },
        { label: '今日API调用', value: (data.api_stats?.total || 0) },
        { label: '今日失败', value: (data.api_stats?.errors || 0), extra: (data.api_stats?.errors > 0 ? 'var(--danger)' : '') },
      ].map(d => `<div style="flex:1;min-width:80px;background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);text-align:center">
        <div style="font-size:22px;font-weight:700;${d.extra ? 'color:' + d.extra : ''}">${d.value}</div>
        <div style="font-size:10px;color:var(--text-tertiary);margin-top:2px">${d.label}</div>
      </div>`).join('');

      // API 状态警告徽章
      const badge = document.getElementById('api-warning-badge');
      if (badge) {
        if (data.api_health?.status === 'error' || data.api_health?.status === 'invalid') {
          badge.style.display = '';
          badge.textContent = data.api_health?.status === 'invalid' ? 'API Key无效' : 'API异常';
        } else {
          badge.style.display = 'none';
        }
      }

      // 访问统计卡片
      const v = data.visits || {};
      document.getElementById('visits-overview').innerHTML = [
        { label: '总访问次数', value: v.total || 0 },
        { label: '今日访问', value: v.today || 0 },
        { label: '昨日访问', value: v.yesterday || 0 },
        { label: '近7天访问', value: v.last_7d || 0 },
      ].map(d => `<div style="flex:1;min-width:80px;background:var(--popup-bg);border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);text-align:center">
        <div style="font-size:22px;font-weight:700;color:var(--success)">${d.value}</div>
        <div style="font-size:10px;color:var(--text-tertiary);margin-top:2px">${d.label}</div>
      </div>`).join('');

      // 访问时段分布
      const hoursData = v.hours || [];
      const hoursEl = document.getElementById('visit-hours');
      if (hoursEl) {
        if (hoursData.length) {
          const maxHr = Math.max(...hoursData.map(h => parseInt(h.cnt)));
          hoursEl.innerHTML = hoursData.map(h => {
            const pct = maxHr > 0 ? (parseInt(h.cnt) / maxHr * 100).toFixed(0) : 0;
            return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:11px">
              <span style="width:36px;color:var(--text-tertiary);text-align:right">${String(h.hr).padStart(2,'0')}:00</span>
              <span style="flex:1;height:14px;background:rgba(59,130,246,0.1);border-radius:3px;overflow:hidden">
                <span style="display:block;height:100%;background:#3b82f6;border-radius:3px;width:${pct}%;min-width:2px"></span>
              </span>
              <span style="width:28px;color:var(--text-secondary);text-align:right;font-weight:500">${h.cnt}</span>
            </div>`;
          }).join('');
        } else {
          hoursEl.innerHTML = '<div style="color:var(--text-tertiary);font-size:11px">暂无数据</div>';
        }
      }

      // Top IP
      const ipsData = v.top_ips || [];
      const ipsEl = document.getElementById('visit-ips');
      if (ipsEl) {
        if (ipsData.length) {
          ipsEl.innerHTML = ipsData.map((r, i) => {
            const color = i < 3 ? 'var(--danger)' : 'var(--text-secondary)';
            return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;font-size:11px">
              <span style="width:18px;color:var(--text-tertiary);text-align:right;font-weight:600">${i+1}</span>
              <span style="flex:1;font-family:monospace;font-size:10px">${esc(r.ip)}</span>
              <span style="color:${color};font-weight:500">${r.cnt}</span>
            </div>`;
          }).join('');
        } else {
          ipsEl.innerHTML = '<div style="color:var(--text-tertiary);font-size:11px">暂无数据</div>';
        }
      }

      // 销毁旧图表
      Object.values(statsCharts).forEach(c => c.destroy());
      statsCharts = {};

      if (!document.getElementById('chart-daily') || typeof Chart === 'undefined') {
        console.warn('Chart.js 未加载，跳过图表');
        return;
      }

      // 每日生成量折线图
      const dailyLabels = (data.daily || []).map(r => r.day.slice(5));
      const dailyData  = (data.daily || []).map(r => r.cnt);
      const colors = ['#3b82f6','#ef4444','#f59e0b','#10b981','#8b5cf6','#ec4899','#06b6d4','#f97316','#84cc16','#6366f1'];

      statsCharts.daily = new Chart(document.getElementById('chart-daily'), {
        type: 'line',
        data: {
          labels: dailyLabels,
          datasets: [{ label: '生成量', data: dailyData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 5, pointBackgroundColor: '#3b82f6' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
      });

      const userLabels = (data.users || []).map(r => r.username);
      const userData   = (data.users || []).map(r => r.cnt);
      statsCharts.users = new Chart(document.getElementById('chart-users'), {
        type: 'bar',
        data: {
          labels: userLabels,
          datasets: [{ label: '张数', data: userData, backgroundColor: colors, borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
      });

      const modelLabels = (data.models || []).map(r => r.model);
      const modelData   = (data.models || []).map(r => r.cnt);
      statsCharts.models = new Chart(document.getElementById('chart-models'), {
        type: 'doughnut',
        data: {
          labels: modelLabels,
          datasets: [{ data: modelData, backgroundColor: colors }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
      });

      // 模型使用详情表
      const totalModels = (data.models || []).reduce((s, r) => s + parseInt(r.cnt), 0);
      const modelTbody = document.getElementById('model-detail-tbody');
      if (modelTbody && (data.models || []).length) {
        modelTbody.innerHTML = data.models.map(r => {
          const pct = totalModels > 0 ? (parseInt(r.cnt) / totalModels * 100).toFixed(1) : '0.0';
          return `<tr><td style="font-size:12px">${esc(r.model||'未知')}</td><td style="font-size:12px;font-weight:500">${r.cnt}</td><td style="font-size:12px;color:var(--text-secondary)">${pct}%</td></tr>`;
        }).join('');
      } else if (modelTbody) {
        modelTbody.innerHTML = '<tr><td colspan="3" style="color:var(--text-tertiary)">暂无数据</td></tr>';
      }
    }

    async function showUserDetail(uid) {
      const res = await fetch(`../api/admin.php?action=user_detail&uid=${uid}`);
      const u = await res.json();
      if (u.error) { showMsg(u.error, 'err'); return; }
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center';
      overlay.innerHTML = `<div style="background:var(--popup-bg);border-radius:16px;padding:28px;max-width:420px;width:90%">
        <h2 style="margin-bottom:16px">用户详情</h2>
        <table style="width:100%">
          <tr><td style="color:var(--text-tertiary);padding:4px 0">用户名</td><td>${esc(u.username)}</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">密码(哈希)</td><td style="font-size:11px;word-break:break-all">${esc(u.password_hash)}</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">角色</td><td>${u.role==='admin'?'管理员':'用户'}</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">最后登录IP</td><td style="font-family:monospace">${esc(u.last_ip)}</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">今日登录</td><td>${u.today_logins} 次</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">总登录</td><td>${u.total_logins} 次</td></tr>
          <tr><td style="color:var(--text-tertiary);padding:4px 0">注册时间</td><td style="font-size:12px">${u.created_at||''}</td></tr>
        </table>
        <div style="text-align:right;margin-top:16px"><button class="btn" id="detail-close">关闭</button></div>
      </div>`;
      document.body.appendChild(overlay);
      overlay.querySelector('#detail-close').onclick = () => overlay.remove();
      overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    }

    async function showRanking() {
      const box = document.getElementById('ranking-box');
      if (box.style.display !== 'none') { box.style.display = 'none'; return; }
      box.innerHTML = '加载中...'; box.style.display = '';
      try {
        const res = await fetch('../api/admin.php?action=ranking');
        const list = await res.json();
        const max = list[0]?.cnt || 1;
        box.innerHTML = '<div style="font-weight:600;margin-bottom:8px">用户生成排名</div>' +
          list.map((r, i) => `
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;font-size:13px">
              <span style="color:var(--text-tertiary);width:20px">${i + 1}</span>
              <span style="flex:1">${esc(r.username)}</span>
              <span style="color:var(--text-secondary)">${r.cnt} 张</span>
              <span style="flex:2;height:6px;border-radius:3px;background:rgba(0,0,0,0.06)">
                <span style="display:block;height:100%;border-radius:3px;background:var(--text);width:${(r.cnt / max * 100).toFixed(0)}%"></span>
              </span>
            </div>`).join('');
      } catch (e) { box.innerHTML = '加载失败'; }
    }

    async function queryVisitDate() {
      const date = document.getElementById('visit-date-picker').value;
      if (!date) return;
      const res = await fetch(`../api/admin.php?action=stats&date=${date}`);
      const data = await res.json();
      const cnt = data.visits?.date_query ?? 0;
      document.getElementById('visit-date-result').textContent =
        `${date} 访问次数：${cnt}`;
    }

    function esc(s) {
      const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
    }

    let allImages = [];
    let previewIdx = -1;

    function previewImage(filename, username, idx) {
      if (idx !== undefined) previewIdx = idx; else previewIdx = allImages.findIndex(r => r.filename === filename);
      if (previewIdx < 0) previewIdx = 0;
      const r = allImages[previewIdx];
      if (!r) return;
      const url = `../load.php?file=${encodeURIComponent(r.filename)}&user=${encodeURIComponent(r.username)}`;
      document.getElementById('preview-img').src = url;
      document.getElementById('preview-counter').textContent = `${previewIdx + 1} / ${allImages.length}`;
      document.getElementById('preview-overlay').style.display = 'flex';
    }
    function previewPrev(e) { e.stopPropagation(); if (previewIdx > 0) previewImage(null, null, previewIdx - 1); }
    function previewNext(e) { e.stopPropagation(); if (previewIdx < allImages.length - 1) previewImage(null, null, previewIdx + 1); }
    function closePreview() {
      document.getElementById('preview-overlay').style.display = 'none';
      document.getElementById('preview-img').src = '';
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closePreview();
    });

    // ====== 审计日志 ======
    let auditPage = 1;
    async function loadAuditLogs(page = 1) {
      auditPage = page;
      const res = await fetch(`../api/admin.php?action=audit_logs&page=${page}`);
      const data = await res.json();
      const tbody = document.getElementById('audit-tbody');
      if (!data.list || !data.list.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:var(--text-tertiary)">暂无审计记录</td></tr>';
      } else {
        const actionLabels = { delete_user:'删除用户', batch_topup:'批量加积分', config_change:'修改配置', topup:'充值', set_balance:'调整余额' };
        tbody.innerHTML = data.list.map(r => `<tr>
          <td style="font-size:11px">${r.created_at||''}</td>
          <td style="font-size:11px">${esc(r.admin_name||'ID:'+r.admin_id)}</td>
          <td><span style="font-size:11px;background:rgba(0,0,0,0.04);padding:2px 8px;border-radius:4px">${actionLabels[r.action] || r.action}</span></td>
          <td style="font-size:11px">${esc(r.target_type||'')} #${r.target_id}</td>
          <td style="font-size:11px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.detail||'')}</td>
        </tr>`).join('');
      }
      const pager = document.getElementById('audit-pager');
      pager.innerHTML = data.pages > 1
        ? `<button class="btn-ghost" ${data.page <= 1 ? 'disabled' : ''} onclick="loadAuditLogs(${data.page - 1})">上一页</button>
           <span style="font-size:12px;color:var(--text-tertiary)">第 ${data.page} / ${data.pages} 页 · 共 ${data.total} 条</span>
           <button class="btn-ghost" ${data.page >= data.pages ? 'disabled' : ''} onclick="loadAuditLogs(${data.page + 1})">下一页</button>`
        : '';
    }

    // ====== 通知中心 ======
    async function loadNotifications() {
      const container = document.getElementById('notif-content');
      container.innerHTML = '<div style="color:var(--text-tertiary)">加载中...</div>';
      try {
        const res = await fetch('../api/admin.php?action=notifications');
        const d = await res.json();
        let html = '';
        // 今日新注册
        html += `<div style="margin-bottom:20px"><h3 style="font-size:13px;font-weight:600;margin-bottom:8px">今日新注册用户 (${d.new_users_today})</h3>`;
        if (d.recent_registrations && d.recent_registrations.length) {
          html += '<table><thead><tr><th>用户名</th><th>注册时间</th></tr></thead><tbody>' +
            d.recent_registrations.map(r => `<tr><td style="font-size:12px">${esc(r.username)}</td><td style="font-size:11px">${r.created_at||''}</td></tr>`).join('') +
            '</tbody></table>';
        } else {
          html += '<div style="font-size:12px;color:var(--text-tertiary)">今日暂无新注册</div>';
        }
        html += '</div>';
        // 今日失败 API
        html += `<div style="margin-bottom:20px"><h3 style="font-size:13px;font-weight:600;margin-bottom:8px">今日 API 调用失败 (${d.failed_api_today})</h3>`;
        if (d.recent_errors && d.recent_errors.length) {
          html += '<table><thead><tr><th>时间</th><th>用户</th><th>接口</th><th>状态码</th></tr></thead><tbody>' +
            d.recent_errors.map(r => `<tr><td style="font-size:11px">${r.created_at||''}</td><td style="font-size:11px">${esc(r.username||'-')}</td><td style="font-size:11px;font-family:monospace">${esc(r.endpoint||'')}</td><td style="font-size:11px;color:var(--danger)">${r.http_code||''}</td></tr>`).join('') +
            '</tbody></table>';
        } else {
          html += '<div style="font-size:12px;color:var(--text-tertiary)">今日暂无失败调用</div>';
        }
        html += '</div>';
        container.innerHTML = html;
      } catch(e) {
        container.innerHTML = '<div style="color:var(--danger);font-size:13px">加载失败</div>';
      }
    }

    async function loadNotificationBadge() {
      try {
        const res = await fetch('../api/admin.php?action=notifications');
        const d = await res.json();
        const badge = document.getElementById('notif-badge');
        const total = (d.total_events || 0);
        if (total > 0) {
          badge.textContent = total;
          badge.style.display = '';
        } else {
          badge.style.display = 'none';
        }
      } catch(e) {}
    }

    // 页面加载时自动刷一次通知角标
    if (currentUserId) { setTimeout(loadNotificationBadge, 2000); }
  </script>
</body>
</html>
