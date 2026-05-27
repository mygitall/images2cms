<?php
/**
 * 认证 API：注册 / 登录 / 登出 / 当前用户
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';

// 后台管理使用独立 session，避免被前台登录影响
if (!empty($_GET['admin'])) {
    session_name('IMAGES20_ADMIN');
}
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? '';

// ---- 频率限制（30秒内最多5次登录/注册）----
if (in_array($action, ['login', 'register'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $cacheFile = sys_get_temp_dir() . '/rate_' . md5($ip . $action);
    $now = time();
    $attempts = @json_decode(@file_get_contents($cacheFile), true) ?: ['ts' => 0, 'count' => 0];
    if ($now - $attempts['ts'] < 30 && $attempts['count'] >= 5) {
        jsonOut(['error' => '操作太频繁，请30秒后再试'], 429);
    }
    if ($now - $attempts['ts'] >= 30) { $attempts = ['ts' => $now, 'count' => 1]; }
    else { $attempts['count']++; }
    @file_put_contents($cacheFile, json_encode($attempts));
}

// ---- JSON 输入 ----
$input = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json; charset=utf-8');

function jsonOut($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== 注册 ==========
if ($action === 'register') {
    $config = require __DIR__ . '/../config.php';
    $features = $config['features'] ?? [];

    // 禁止注册
    if (!empty($features['disable_register'])) {
        $msg = $features['register_block_msg'] ?? '暂时停止注册';
        jsonOut(['error' => $msg], 403);
    }

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (strlen($username) < 2 || strlen($username) > 50) jsonOut(['error' => '用户名 2-50 位'], 400);
    if (strlen($password) < 4) jsonOut(['error' => '密码至少 4 位'], 400);

    // IP 黑名单
    $bannedIPs = array_map('trim', explode(',', $features['banned_ips_list'] ?? ''));
    $clientIP  = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($bannedIPs[0]) && in_array($clientIP, $bannedIPs)) {
        jsonOut(['error' => '当前网络环境不支持注册'], 403);
    }

    // 每日注册上限
    $dailyMax = intval($features['daily_reg_max'] ?? 0);
    if ($dailyMax > 0) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        if ($cnt >= $dailyMax) jsonOut(['error' => '今日注册名额已满，请明天再试'], 403);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) jsonOut(['error' => '用户名已存在'], 409);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)')->execute([$username, $hash]);

    $_SESSION['user'] = ['id' => $pdo->lastInsertId(), 'username' => $username, 'role' => 'user'];
    jsonOut($_SESSION['user']);
}

// ========== 登录 ==========
if ($action === 'login') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonOut(['error' => '用户名不存在: ' . $username], 401);
    }
    if (!password_verify($password, $user['password'])) {
        jsonOut(['error' => '密码错误'], 401);
    }

    $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];

    // 记录登录日志（优先获取公网 IP）
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    try {
        $ch = curl_init('https://api.ipify.org');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_ENCODING => '']);
        $pubIP = trim(curl_exec($ch) ?: '');
        curl_close($ch);
        if (filter_var($pubIP, FILTER_VALIDATE_IP)) $ip = $pubIP . ' / ' . $ip;
    } catch (Exception $e) {}

    try {
        $pdo->prepare('INSERT INTO login_logs (user_id, ip) VALUES (?, ?)')
            ->execute([$user['id'], $ip]);
    } catch (Exception $e) {}

    jsonOut($_SESSION['user']);
}

// ========== 登出 ==========
if ($action === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    jsonOut(['ok' => true]);
}

// ========== 修改密码 ==========
if ($action === 'changepw') {
    $user = $_SESSION['user'] ?? null;
    if (!$user) jsonOut(['error' => '未登录'], 401);
    $oldPw = $input['old_password'] ?? '';
    $newPw = $input['new_password'] ?? '';
    if (strlen($newPw) < 4) jsonOut(['error' => '新密码至少 4 位'], 400);

    $stmt = $pdo->prepare('SELECT username, password FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row) jsonOut(['error' => '用户不存在'], 404);

    if (!password_verify($oldPw, $row['password'])) {
        // 检查是否需要重新哈希（兼容旧版加密）
        if ($oldPw === $row['password'] || md5($oldPw) === $row['password']) {
            // 旧明文/MD5 密码，直接更新为 bcrypt
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($newPw, PASSWORD_BCRYPT), $user['id']]);
            jsonOut(['ok' => true]);
        }
        jsonOut(['error' => '原密码错误（用户：' . $row['username'] . '）'], 403);
    }
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($newPw, PASSWORD_BCRYPT), $user['id']]);
    jsonOut(['ok' => true]);
}

// ========== 获取当前用户 ==========
if ($action === 'me') {
    jsonOut($_SESSION['user'] ?? null);
}

jsonOut(['error' => '未知 action'], 400);
