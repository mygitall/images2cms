<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 已安装则跳走
if (file_exists(__DIR__ . '/config.php')) {
    $cfg = require __DIR__ . '/config.php';
    if (!empty($cfg['installed'])) {
        header('Location: index.php');
        exit;
    }
}

$step  = intval($_POST['step'] ?? 1);
$error = '';
$ok    = '';

// ====== 环境检测函数 ======
function checkExt($name) { return extension_loaded($name); }
function checkPhpVer($min) { return version_compare(PHP_VERSION, $min, '>='); }

$env = [
    ['PHP 版本 ≥ 7.0',  checkPhpVer('7.0'),     PHP_VERSION],
    ['PDO 扩展',         checkExt('pdo'),         ''],
    ['PDO MySQL',        checkExt('pdo_mysql'),   ''],
    ['cURL 扩展',        checkExt('curl'),        ''],
    ['OpenSSL',          checkExt('openssl'),     ''],
    ['GD/图像处理',       checkExt('gd'),          ''],
    ['JSON 扩展',        checkExt('json'),        ''],
    ['Session',          checkExt('session'),     ''],
    ['文件上传',          ini_get('file_uploads'), ''],
    ['config.php 可写',  is_writable(__DIR__),    ''],
];

$allPass = !in_array(false, array_column($env, 1));

// ====== Step 2: 处理数据库配置 ======
if ($step >= 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = [
        'host' => $_POST['db_host'] ?? 'localhost',
        'port' => $_POST['db_port'] ?? '3306',
        'name' => $_POST['db_name'] ?? '',
        'user' => $_POST['db_user'] ?? '',
        'pass' => $_POST['db_pass'] ?? '',
    ];

    if ($step === 2) {
        try {
            $test = new PDO(
                "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4",
                $db['user'], $db['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // 尝试创建数据库（如果不存在）
            $test->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $ok = '数据库连接成功';
        } catch (\Throwable $e) {
            $error = '数据库连接失败：' . $e->getMessage();
            $step = 2;
        }
    }

    // ====== Step 3: 建表 + 创建管理员 ======
    if ($step >= 3 && !$error) {
        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
                $db['user'], $db['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );

            // 建表
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL, `role` VARCHAR(10) DEFAULT 'user',
                `daily_limit` INT DEFAULT NULL, `total_limit` INT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `gen_images` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
                `filename` VARCHAR(255) NOT NULL, `prompt` TEXT, `model` VARCHAR(100),
                `aspect` VARCHAR(20), `resolution` VARCHAR(10),
                `deleted_at` DATETIME DEFAULT NULL, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `login_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
                `ip` VARCHAR(45) DEFAULT '', `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user_time` (`user_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `api_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT DEFAULT NULL,
                `endpoint` VARCHAR(255) NOT NULL, `method` VARCHAR(10) DEFAULT 'POST',
                `status` VARCHAR(20) DEFAULT 'success', `http_code` INT DEFAULT 200,
                `duration_ms` INT DEFAULT 0, `error_msg` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `balance_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL, `type` VARCHAR(20) DEFAULT 'deduct',
                `reason` VARCHAR(255) DEFAULT '', `balance_after` DECIMAL(10,2) DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 兼容旧表：补全可能缺失的字段
            $fixCols = [
                ['users',     'role',         "VARCHAR(10) DEFAULT 'user'"],
                ['users',     'daily_limit',  'INT DEFAULT NULL'],
                ['users',     'total_limit',  'INT DEFAULT NULL'],
                ['gen_images','deleted_at',   'DATETIME DEFAULT NULL'],
            ];
            foreach ($fixCols as $fc) {
                try { $pdo->exec("ALTER TABLE `{$fc[0]}` ADD COLUMN `{$fc[1]}` {$fc[2]}"); } catch (\Throwable $e) {}
            }
            // 修复空 role
            $pdo->exec("UPDATE `users` SET `role` = 'user' WHERE `role` IS NULL OR `role` = ''");

            // 写 db.php
            $dbCode = "<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

\$dbHost = '{$db['host']}';
\$dbPort = '{$db['port']}';
\$dbName = '{$db['name']}';
\$dbUser = '{$db['user']}';
\$dbPass = '{$db['pass']}';

try {
    if (!extension_loaded('pdo_mysql')) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['error' => '未安装 PDO MySQL 扩展'], JSON_UNESCAPED_UNICODE));
    }
    \$pdo = new PDO(
        \"mysql:host={\$dbHost};port={\$dbPort};dbname={\$dbName};charset=utf8mb4\",
        \$dbUser, \$dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (\\Throwable \$e) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['error' => 'DB error: ' . \$e->getMessage()], JSON_UNESCAPED_UNICODE));
}
";
            file_put_contents(__DIR__ . '/db.php', $dbCode);

            // 写 config.php
            $adminUser = $_POST['admin_user'] ?? 'admin';
            $adminPass = $_POST['admin_pass'] ?? 'admin123';
            $cfgCode = "<?php
return array (
  'active' => 'default',
  'profiles' => array (
    'default' => array (
      'api_key' => '',
      'base_url' => '',
    ),
  ),
  'save_dir' => __DIR__ . '/uploads',
  'features' => array (
    'show_folder_card' => true,
    'show_presets' => true,
    'disable_register' => false,
  ),
  'installed' => true,
);
";
            file_put_contents(__DIR__ . '/config.php', $cfgCode);

            // 确保 uploads 目录存在
            if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);

            // 创建/更新管理员
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$adminUser]);
            if ($stmt->fetch()) {
                $pdo->prepare('UPDATE users SET password = ?, role = ? WHERE username = ?')
                    ->execute([password_hash($adminPass, PASSWORD_BCRYPT), 'admin', $adminUser]);
            } else {
                $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
                    ->execute([$adminUser, password_hash($adminPass, PASSWORD_BCRYPT), 'admin']);
            }

            $ok = '安装完成！';
        } catch (\Throwable $e) {
            $error = '安装失败：' . $e->getMessage();
            $step = 3;
        }
    }
}
?>
<!doctype html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Image Studio — 安装向导</title>
<style>
:root { --bg:#f5f5f7; --card-bg:#fff; --card-border:rgba(0,0,0,0.06); --text:#1a1a1a; --text2:#666; --text3:#999; --green:#16a34a; --red:#dc2626; --blue:#3b82f6; --font:-apple-system,BlinkMacSystemFont,"SF Pro Display","Inter",sans-serif; }
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:var(--font); background:var(--bg); color:var(--text); min-height:100vh; display:flex; align-items:center; justify-content:center; }
.card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px; padding:36px; max-width:540px; width:90%; }
h1 { font-size:24px; font-weight:700; letter-spacing:-0.02em; margin-bottom:4px; }
.sub { color:var(--text3); font-size:14px; margin-bottom:24px; }
.check { display:flex; align-items:center; gap:8px; padding:6px 0; font-size:13px; }
.check .icon { width:18px; text-align:center; }
label { display:block; font-size:12px; color:var(--text2); margin:8px 0 4px; text-transform:uppercase; letter-spacing:0.04em; }
input { width:100%; padding:10px 14px; border:1px solid var(--card-border); border-radius:10px; font-size:14px; font-family:var(--font); outline:none; }
input:focus { border-color:rgba(0,0,0,0.2); }
.btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 24px; border-radius:100px; font-size:14px; font-weight:600; font-family:var(--font); cursor:pointer; border:none; background:var(--text); color:#fff; }
.btn:hover { opacity:0.85; }
.btn:disabled { opacity:0.3; cursor:not-allowed; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
.msg { padding:10px 14px; border-radius:8px; font-size:13px; margin-top:12px; }
.msg.err { background:rgba(220,38,38,0.08); color:var(--red); }
.msg.ok  { background:rgba(22,163,74,0.08); color:var(--green); }
.spin { display:inline-block; animation:spin 0.8s linear infinite; } @keyframes spin { to { transform:rotate(360deg); } }
</style>
</head>
<body>
<div class="card">
  <h1>Image Studio 安装</h1>
  <p class="sub" id="step-label">第 <?= $step ?> 步 / 3</p>

  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="msg ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <form method="post" id="install-form">
    <input type="hidden" name="step" value="<?= $step ?>">

    <!-- Step 1: 环境检测 -->
    <?php if ($step === 1): ?>
      <?php foreach ($env as $e): ?>
        <div class="check"><span class="icon"><?= $e[1] ? '✅' : '❌' ?></span> <?= $e[0] ?> <?= $e[2] ? "<span style='color:var(--text3);font-size:11px'>{$e[2]}</span>" : '' ?></div>
      <?php endforeach; ?>
      <div class="btn-row">
        <button type="submit" class="btn" name="step" value="2" <?= !$allPass ? 'disabled' : '' ?>>下一步</button>
        <?php if (!$allPass): ?><span style="color:var(--red);font-size:12px;align-self:center">请先解决红色问题</span><?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Step 2: 数据库配置 -->
    <?php if ($step >= 2 && $step <= 3): ?>
      <label>数据库主机</label><input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
      <label>端口</label><input name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
      <label>数据库名</label><input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="如：images_db">
      <label>用户名</label><input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>">
      <label>密码</label><input name="db_pass" type="password" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
      <div class="btn-row">
        <?php if ($step === 2): ?><button type="submit" class="btn" name="step" value="2">测试连接</button><?php endif; ?>
        <?php if ($ok && $step === 2): ?><button type="submit" class="btn" name="step" value="3">继续</button><?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Step 3: 管理员 -->
    <?php if ($step >= 3 && !$error): ?>
      <label>管理员用户名</label><input name="admin_user" value="admin">
      <label>管理员密码</label><input name="admin_pass" type="password" value="admin123">
      <label>确认密码</label><input name="admin_pass2" type="password" value="admin123">
      <div class="btn-row">
        <button type="submit" class="btn" name="step" value="3" onclick="return checkPass()">完成安装</button>
      </div>
    <?php endif; ?>
  </form>

  <?php if ($ok === '安装完成！'): ?>
    <div style="text-align:center;margin-top:16px">
      <a href="index.php" class="btn" style="text-decoration:none">进入首页</a>
    </div>
  <?php endif; ?>
</div>

<script>
function checkPass() {
  var p1 = document.querySelector('[name=admin_pass]').value;
  var p2 = document.querySelector('[name=admin_pass2]').value;
  if (p1 !== p2) { alert('两次密码不一致'); return false; }
  if (p1.length < 4) { alert('密码至少4位'); return false; }
  return true;
}
</script>
</body>
</html>
