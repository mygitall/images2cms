<?php
/**
 * 管理后台 API（需要 admin 权限）
 * GET  /api/admin.php?action=users       — 用户列表
 * POST /api/admin.php?action=users       — 创建用户
 * DELETE /api/admin.php?action=users&id=X  — 删除用户
 *
 * GET  /api/admin.php?action=images      — 图片列表（分页）
 * DELETE /api/admin.php?action=images&id=X — 删除图片记录
 *
 * GET  /api/admin.php?action=config      — 获取当前 API Key（脱敏）
 * POST /api/admin.php?action=config      — 更新 API Key
 */

require_once __DIR__ . '/../db.php';
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '需要管理员权限'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json; charset=utf-8');

function ok($data = null) { echo json_encode($data ?: ['ok' => true], JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE); exit; }

// ========== 用户管理 ==========
if ($action === 'users') {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id');
        ok($stmt->fetchAll());
    }

    if ($method === 'POST') {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'user';
        if (strlen($username) < 2) err('用户名至少 2 位');
        if (strlen($password) < 4) err('密码至少 4 位');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) err('用户名已存在', 409);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?,?,?)')
            ->execute([$username, $hash, $role]);
        ok(['id' => $pdo->lastInsertId()]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        if ($id === $user['id']) err('不能删除自己');
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        ok();
    }
}

// ========== 图片管理 ==========
if ($action === 'images') {
    if ($method === 'GET') {
        $page  = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = $pdo->query('SELECT COUNT(*) FROM gen_images')->fetchColumn();
        $stmt  = $pdo->prepare(
            'SELECT i.*, u.username FROM gen_images i
             JOIN users u ON i.user_id = u.id
             ORDER BY i.created_at DESC LIMIT :l OFFSET :o'
        );
        $stmt->bindValue(':l', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':o', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        ok(['list' => $list, 'total' => intval($total), 'page' => $page, 'pages' => ceil($total / $limit)]);
    }

    if ($method === 'DELETE') {
        $id = intval($_GET['id'] ?? 0);
        // 同时删除物理文件
        $img = $pdo->prepare('SELECT filename FROM gen_images WHERE id = ?');
        $img->execute([$id]);
        $row = $img->fetch();
        if ($row) {
            $config = require __DIR__ . '/../config.php';
            $filePath = rtrim($config['save_dir'], '/') . '/' . $row['filename'];
            if (file_exists($filePath)) unlink($filePath);
        }
        $pdo->prepare('DELETE FROM gen_images WHERE id = ?')->execute([$id]);
        ok();
    }
}

// ========== 配置管理 ==========
if ($action === 'config') {
    $configFile = __DIR__ . '/../config.php';

    if ($method === 'GET') {
        $config = require $configFile;
        $key = $config['api_key'] ?? '';
        $masked = $key ? substr($key, 0, 8) . '****' . substr($key, -4) : '(未设置)';
        ok(['api_key_masked' => $masked, 'base_url' => $config['base_url'] ?? '']);
    }

    if ($method === 'POST') {
        $newKey   = trim($input['api_key'] ?? '');
        $newUrl   = trim($input['base_url'] ?? '');
        $config   = require $configFile;
        if ($newKey) $config['api_key'] = $newKey;
        if ($newUrl) $config['base_url'] = $newUrl;

        $export = var_export($config, true);
        file_put_contents($configFile, "<?php\nreturn {$export};\n");
        ok();
    }
}

// ========== 用户限制 ==========
if ($action === 'limits') {
    if ($method === 'GET') {
        $uid = intval($_GET['uid'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, username, daily_limit, total_limit FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        ok($stmt->fetch() ?: null);
    }
    if ($method === 'POST') {
        $uid   = intval($input['user_id'] ?? 0);
        $daily = $input['daily_limit'] === '' || $input['daily_limit'] === null ? null : intval($input['daily_limit']);
        $total = $input['total_limit'] === '' || $input['total_limit'] === null ? null : intval($input['total_limit']);
        $pdo->prepare('UPDATE users SET daily_limit = ?, total_limit = ? WHERE id = ?')
            ->execute([$daily, $total, $uid]);
        ok();
    }
}

// ========== 全量操作日志（admin 专用）==========
if ($action === 'all_logs') {
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // 合并图片生成 + 用户注册 + 软删除事件
    $union = "
        SELECT i.id, i.user_id, u.username, i.filename, i.prompt, i.model, i.created_at AS time, '生成' AS action, i.deleted_at
        FROM gen_images i JOIN users u ON i.user_id = u.id
        UNION ALL
        SELECT u.id, u.id AS user_id, u.username, '' AS filename, '用户注册' AS prompt, '' AS model, u.created_at AS time, '注册' AS action, NULL AS deleted_at
        FROM users u
        UNION ALL
        SELECT i.id + 1000000 AS id, i.user_id, u.username, i.filename, CONCAT('删除图片') AS prompt, i.model, i.deleted_at AS time, '删除' AS action, i.deleted_at
        FROM gen_images i JOIN users u ON i.user_id = u.id WHERE i.deleted_at IS NOT NULL
    ";

    $totalStmt = $pdo->query("SELECT COUNT(*) FROM ({$union}) AS t");
    $total = $totalStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT * FROM ({$union}) AS t ORDER BY time DESC LIMIT :l OFFSET :o"
    );
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    ok(['list' => $stmt->fetchAll(), 'total' => intval($total), 'page' => $page, 'pages' => ceil($total / $limit)]);
}

// ========== 用户操作日志 ==========
if ($action === 'user_logs') {
    $uid = intval($_GET['uid'] ?? 0);
    $limit = intval($_GET['limit'] ?? 100);
    $onlyLatest = ($_GET['latest'] ?? '') === '1';

    $sql = 'SELECT id, filename, prompt, model, created_at, deleted_at FROM gen_images WHERE user_id = ? ORDER BY created_at DESC';
    if ($onlyLatest) $sql .= ' LIMIT 1';
    else $sql .= ' LIMIT ' . min($limit, 500);

    $stmt = $pdo->prepare($sql);
    if ($onlyLatest) $stmt->execute([$uid]);
    else $stmt->execute([$uid]);
    ok($stmt->fetchAll());
}

// ========== 排名统计 ==========
if ($action === 'ranking') {
    $stmt = $pdo->query(
        'SELECT u.username, COUNT(*) AS cnt FROM gen_images i
         JOIN users u ON i.user_id = u.id
         GROUP BY i.user_id ORDER BY cnt DESC'
    );
    ok($stmt->fetchAll());
}

err('未知 action');
