<?php
/**
 * 认证 API：注册 / 登录 / 登出 / 当前用户
 */

require_once __DIR__ . '/../db.php';
session_start();

$action = $_GET['action'] ?? '';

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
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (strlen($username) < 2 || strlen($username) > 50) jsonOut(['error' => '用户名 2-50 位'], 400);
    if (strlen($password) < 4) jsonOut(['error' => '密码至少 4 位'], 400);

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
    jsonOut($_SESSION['user']);
}

// ========== 登出 ==========
if ($action === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    jsonOut(['ok' => true]);
}

// ========== 获取当前用户 ==========
if ($action === 'me') {
    jsonOut($_SESSION['user'] ?? null);
}

jsonOut(['error' => '未知 action'], 400);
