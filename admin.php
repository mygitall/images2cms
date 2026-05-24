<?php
session_start();
$user = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';
?><!doctype html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>后台管理 — Image Studio</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #f5f5f7;
      --card-bg: rgba(255,255,255,0.7);
      --card-border: rgba(0,0,0,0.06);
      --text: #1a1a1a;
      --text-secondary: #666;
      --text-tertiary: #999;
      --danger: #dc2626;
      --success: #16a34a;
      --font: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
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
    .tabs button.active { background: #fff; color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

    .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 18px; padding: 24px; margin-bottom: 16px; }
    .card h2 { font-size: 16px; font-weight: 600; margin-bottom: 16px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px 12px; font-size: 13px; border-bottom: 1px solid var(--card-border); }
    th { color: var(--text-tertiary); font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }

    input:not(.login-box input), select {
      padding: 8px 12px; border: 1px solid var(--card-border); border-radius: 8px;
      font-size: 13px; font-family: var(--font); background: #fff; outline: none;
    }
    .inline-form { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }

    .msg { padding: 8px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 12px; display: none; }
    .msg.show { display: block; }
    .msg.ok { background: rgba(22,163,74,0.08); color: var(--success); }
    .msg.err { background: rgba(220,38,38,0.08); color: var(--danger); }

    .config-display { font-size: 14px; padding: 12px; background: rgba(0,0,0,0.02); border-radius: 8px; word-break: break-all; }
    .config-display code { font-size: 13px; }
    .pagination { display: flex; gap: 8px; margin-top: 16px; align-items: center; }
    .pagination span { font-size: 13px; color: var(--text-secondary); }

    /* Dialog */
    .dialog-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9998; align-items: center; justify-content: center; }
    .dialog-content { background: #fff; border: 1px solid var(--card-border); border-radius: 16px; padding: 28px; max-width: 400px; width: 90%; }
    .dialog-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; }
    .dialog-input { width: 100%; padding: 10px 14px; border: 1px solid var(--card-border); border-radius: 10px; font-size: 14px; font-family: var(--font); margin-bottom: 12px; outline: none; }
    .dialog-input:focus { border-color: rgba(0,0,0,0.2); }
    .dialog-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 4px; }
    .dialog-btn { padding: 10px 20px; border-radius: 100px; font-size: 14px; font-weight: 500; cursor: pointer; font-family: var(--font); border: none; }
    .dialog-btn-cancel { background: rgba(0,0,0,0.06); }
    .dialog-btn-confirm { background: var(--text); color: #fff; }

    .err-msg { color: var(--danger); font-size: 13px; margin-bottom: 10px; text-align: center; }

    @media (max-width: 768px) {
      .app { padding: 20px 14px; }
      .card { padding: 16px; }
    }
  </style>
</head>
<body>
  <div class="app">
    <!-- ====== 登录界面 ====== -->
    <div class="login-box" id="login-box">
      <h2 id="login-title">登录后台</h2>
      <div class="err-msg" id="login-error" style="display:none"></div>
      <input id="login-username" type="text" placeholder="用户名">
      <input id="login-password" type="password" placeholder="密码">
      <button class="btn" id="login-submit" style="width:100%;margin-top:4px;">登录</button>
      <div class="switch">没有账号？<a id="login-switch">去前台注册</a></div>
    </div>

    <!-- ====== 管理面板 ====== -->
    <div class="panel <?= $isAdmin ? 'active' : '' ?>" id="admin-panel">
      <div class="header">
        <h1>Image Studio 后台</h1>
        <div class="user-info">
          <span class="user-tag" id="admin-username"></span>
          <a href="index.php">回前台</a>
          <button class="btn-ghost" onclick="doLogout()">退出</button>
        </div>
      </div>

      <div class="tabs">
        <button class="active" data-tab="images">图片记录</button>
        <button data-tab="users">用户管理</button>
        <button data-tab="stats">用量统计</button>
        <button data-tab="apilog">API 日志</button>
        <button data-tab="logs">操作记录</button>
        <button data-tab="config">API 配置</button>
      </div>

      <div class="msg" id="msg"></div>

      <div class="card tab-content" id="tab-images">
        <h2>生成图片记录 <button class="btn-ghost" style="margin-left:12px" onclick="showRanking()">由高到低</button></h2>
        <div id="ranking-box" style="display:none;margin-bottom:16px;padding:12px;background:rgba(0,0,0,0.02);border-radius:10px"></div>
        <table><thead><tr><th>ID</th><th>用户</th><th>文件名</th><th>提示词</th><th>模型</th><th>时间</th><th>操作</th></tr></thead>
          <tbody id="images-tbody"><tr><td colspan="7" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        <div class="pagination" id="images-pager"></div>
      </div>

      <div class="card tab-content" id="tab-users" style="display:none">
        <h2>用户列表</h2>
        <div class="inline-form" style="margin-bottom:16px">
          <input id="new-username" placeholder="用户名"><input id="new-password" placeholder="密码" type="password">
          <select id="new-role"><option value="user">普通用户</option><option value="admin">管理员</option></select>
          <button class="btn" onclick="createUser()">添加用户</button>
        </div>
        <table><thead><tr><th>ID</th><th>用户名</th><th>角色</th><th>最近操作</th><th>注册时间</th><th>操作</th></tr></thead>
          <tbody id="users-tbody"><tr><td colspan="6" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
      </div>

      <div class="card tab-content" id="tab-logs" style="display:none">
        <h2>操作记录</h2>
        <div id="logs-content" style="background:#fff;border-radius:16px;padding:24px;max-height:70vh;overflow-y:auto;border:1px solid var(--card-border)">
          <table style="width:100%"><thead><tr><th>操作</th><th>用户</th><th>详情</th><th>模型</th><th>时间</th><th>状态</th></tr></thead>
            <tbody id="logs-tbody"><tr><td colspan="6" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        </div>
      </div>

      <div class="card tab-content" id="tab-apilog" style="display:none">
        <h2>API 调用日志</h2>
        <div style="background:#fff;border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);max-height:65vh;overflow-y:auto">
          <table style="width:100%"><thead><tr><th>时间</th><th>用户</th><th>接口</th><th>耗时</th><th>状态码</th><th>结果</th></tr></thead>
            <tbody id="apilog-tbody"><tr><td colspan="6" style="color:var(--text-tertiary)">加载中...</td></tr></tbody></table>
        </div>
      </div>

      <div class="card tab-content" id="tab-stats" style="display:none">
        <h2>用量统计仪表盘</h2>
        <div id="stats-overview" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap"></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px">
          <div style="background:#fff;border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">每日生成量（近30天）</h3>
            <canvas id="chart-daily" height="120"></canvas>
          </div>
          <div style="background:#fff;border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">用户活跃度（近30天）</h3>
            <canvas id="chart-users" height="120"></canvas>
          </div>
          <div style="background:#fff;border-radius:8px;padding:10px 14px;border:1px solid var(--card-border)">
            <h3 style="font-size:12px;font-weight:600;margin-bottom:6px">模型使用分布</h3>
            <canvas id="chart-models" height="120"></canvas>
          </div>
        </div>
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
    <div id="preview-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out" onclick="closePreview()">
      <img id="preview-img" src="" style="max-width:95vw;max-height:95vh;object-fit:contain;border-radius:4px">
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
        const res = await fetch('api/auth.php?action=login', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });
        const data = await res.json();
        if (data.error) { loginError.textContent = data.error; loginError.style.display = ''; return; }
        if (data.role !== 'admin') { loginError.textContent = '需要管理员权限'; loginError.style.display = ''; return; }
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
      await fetch('api/auth.php?action=logout', { method: 'POST' });
      location.reload();
    }

    document.getElementById('login-submit').addEventListener('click', doLogin);
    document.getElementById('login-password').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') doLogin();
    });
    document.getElementById('login-switch').addEventListener('click', () => {
      location.href = 'index.php';
    });

    let imagesPage = 1;

    // 已登录 → 直接显示面板
    <?php if ($isAdmin): ?>
    document.getElementById('login-box').style.display = 'none';
    adminPanel.classList.add('active');
    document.getElementById('admin-username').textContent = '<?= htmlspecialchars($user['username']) ?> · 管理员';
    loadImages();
    <?php endif; ?>

    // ====== 标签页 ======
    document.querySelectorAll('.tabs button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.getElementById('tab-' + btn.dataset.tab).style.display = '';
        if (btn.dataset.tab === 'images') loadImages();
        if (btn.dataset.tab === 'users') loadUsers();
        if (btn.dataset.tab === 'logs') loadAllLogs();
        if (btn.dataset.tab === 'stats') loadStats();
        if (btn.dataset.tab === 'apilog') loadApiLogs();
        if (btn.dataset.tab === 'config') loadConfig();
      });
    });

    // ====== 图片列表 ======
    async function loadImages(page = 1) {
      imagesPage = page;
      const res = await fetch(`api/admin.php?action=images&page=${page}`);
      const data = await res.json();
      const tbody = document.getElementById('images-tbody');
      if (!data.list || !data.list.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="color:var(--text-tertiary)">暂无记录</td></tr>';
      } else {
        tbody.innerHTML = data.list.map(r => `
          <tr>
            <td>${r.id}</td><td>${esc(r.username)}</td>
            <td style="font-family:monospace;font-size:12px;cursor:pointer;text-decoration:underline;color:#3b82f6" onclick="previewImage('${esc(r.filename)}','${esc(r.username)}')">${esc(r.filename)}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.prompt||'')}</td>
            <td style="font-size:12px">${esc(r.model||'')}</td>
            <td style="font-size:12px">${r.created_at||''}</td>
            <td><button class="btn-danger" style="padding:4px 10px;font-size:11px;border-radius:6px" onclick="deleteImage(${r.id})">删除</button></td>
          </tr>`).join('');
      }
      const pager = document.getElementById('images-pager');
      pager.innerHTML = `
        <button class="btn-ghost" ${data.page <= 1 ? 'disabled' : ''} onclick="loadImages(${data.page - 1})">上一页</button>
        <span>第 ${data.page} / ${data.pages || 1} 页 · 共 ${data.total} 条</span>
        <button class="btn-ghost" ${data.page >= data.pages ? 'disabled' : ''} onclick="loadImages(${data.page + 1})">下一页</button>`;
    }

    async function deleteImage(id) {
      if (!confirm('确定删除？也会删除服务器上的图片文件')) return;
      await fetch(`api/admin.php?action=images&id=${id}`, { method: 'DELETE' });
      showMsg('已删除', 'ok'); loadImages(imagesPage);
    }

    // ====== 用户列表 ======
    async function loadUsers() {
      const res = await fetch('api/admin.php?action=users');
      const list = await res.json();
      const tbody = document.getElementById('users-tbody');
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:var(--text-tertiary)">暂无用户</td></tr>';
      } else {
        // 并行加载每个用户的最新操作
        const logs = await Promise.all(list.map(u =>
          fetch(`api/admin.php?action=user_logs&uid=${u.id}&latest=1`).then(r => r.json()).catch(() => [])
        ));
        tbody.innerHTML = list.map((u, i) => {
          const latest = logs[i]?.[0];
          const logText = latest
            ? `<span style="font-size:11px">${esc(latest.filename||'')}${latest.deleted_at ? ' <span style="color:var(--danger)">已删</span>' : ''}</span><br><span style="font-size:10px;color:var(--text-tertiary)">${latest.created_at||''}</span>`
            : '<span style="color:var(--text-tertiary);font-size:11px">暂无</span>';
          return `
          <tr>
            <td>${u.id}</td><td>${esc(u.username)}</td>
            <td>${u.role === 'admin' ? '管理员' : '用户'}</td>
            <td>${logText} <button class="btn-ghost" style="padding:1px 6px;font-size:10px;margin-left:4px" onclick="viewUserLogs(${u.id},'${esc(u.username)}')">查看全部</button></td>
            <td style="font-size:12px">${u.created_at||''}</td>
            <td>
              <button class="btn-ghost" style="padding:4px 8px;font-size:11px;margin-right:4px" onclick="openLimitDialog(${u.id},'${esc(u.username)}')">限制</button>
              ${u.id !== currentUserId ? '<button class="btn-danger" style="padding:4px 10px;font-size:11px;border-radius:6px" onclick="deleteUser('+u.id+')">删除</button>' : ''}
            </td>
          </tr>`;
        }).join('');
      }
    }

    async function viewUserLogs(uid, username) {
      const res = await fetch(`api/admin.php?action=user_logs&uid=${uid}`);
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
        <div style="background:#fff;border-radius:16px;padding:24px;max-width:700px;width:90%;max-height:80vh;overflow-y:auto">
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
      const res = await fetch('api/admin.php?action=users', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, role })
      });
      const data = await res.json();
      if (data.error) { showMsg(data.error, 'err'); return; }
      showMsg('用户已创建', 'ok'); loadUsers();
      document.getElementById('new-username').value = '';
      document.getElementById('new-password').value = '';
    }

    async function deleteUser(id) {
      if (!confirm('确定删除该用户？会同时删除其所有图片记录')) return;
      await fetch(`api/admin.php?action=users&id=${id}`, { method: 'DELETE' });
      showMsg('已删除', 'ok'); loadUsers();
    }

    // ====== API 配置（多 Profile）=====
    async function loadConfig() {
      const res = await fetch('api/admin.php?action=config');
      const data = await res.json();
      const container = document.getElementById('profiles-container');
      const active = data.active || 'default';
      const profiles = data.profiles || {};
      container.innerHTML = Object.entries(profiles).map(([name, p]) => `
        <div style="background:#fff;border-radius:10px;padding:14px 16px;border:2px solid ${name === active ? '#1a1a1a' : 'var(--card-border)'}">
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
      await fetch('api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'save', name, api_key, base_url })
      });
      showMsg('已保存', 'ok'); loadConfig();
    }
    async function switchProfile(name) {
      await fetch('api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'switch', name })
      });
      showMsg('已切换到: '+name, 'ok'); loadConfig();
    }
    async function addProfile() {
      const name = prompt('新配置名称（如：备用Key、国内线路）：');
      if (!name) return;
      await fetch('api/admin.php?action=config', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'add', name })
      });
      showMsg('已添加', 'ok'); loadConfig();
    }
    async function deleteProfile(name) {
      if (!confirm('确定删除「'+name+'」？')) return;
      await fetch('api/admin.php?action=config', {
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
        const res = await fetch(`api/admin.php?action=limits&uid=${uid}`);
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
      await fetch('api/admin.php?action=limits', {
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
      const res = await fetch(`api/admin.php?action=all_logs&page=${page}`);
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
      const res = await fetch(`api/admin.php?action=api_logs&page=${page}`);
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

    // ====== 统计仪表盘 ======
    let statsCharts = {};
    async function loadStats() {
      const res = await fetch('api/admin.php?action=stats');
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
      ].map(d => `<div style="flex:1;min-width:80px;background:#fff;border-radius:8px;padding:10px 14px;border:1px solid var(--card-border);text-align:center">
        <div style="font-size:22px;font-weight:700">${d.value}</div>
        <div style="font-size:10px;color:var(--text-tertiary);margin-top:2px">${d.label}</div>
      </div>`).join('');

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
    }

    async function showRanking() {
      const box = document.getElementById('ranking-box');
      if (box.style.display !== 'none') { box.style.display = 'none'; return; }
      box.innerHTML = '加载中...'; box.style.display = '';
      try {
        const res = await fetch('api/admin.php?action=ranking');
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

    function esc(s) {
      const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
    }

    function previewImage(filename, username) {
      const url = `load.php?file=${encodeURIComponent(filename)}&user=${encodeURIComponent(username)}`;
      document.getElementById('preview-img').src = url;
      document.getElementById('preview-overlay').style.display = 'flex';
    }
    function closePreview() {
      document.getElementById('preview-overlay').style.display = 'none';
      document.getElementById('preview-img').src = '';
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closePreview();
    });
  </script>
</body>
</html>
