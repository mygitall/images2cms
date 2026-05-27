<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
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

// 识别后台管理 session，避免被前台 session 覆盖
if (!empty($_COOKIE['IMAGES20_ADMIN'])) {
    session_name('IMAGES20_ADMIN');
}
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

function recordAudit($pdo, $adminId, $action, $targetType, $targetId, $detail) {
    try {
        $pdo->prepare('INSERT INTO admin_audit (admin_id, action, target_type, target_id, detail) VALUES (?,?,?,?,?)')
            ->execute([$adminId, $action, $targetType, $targetId, $detail]);
    } catch (\Throwable $e) {}
}

// ========== 用户管理 ==========
if ($action === 'users') {
    if ($method === 'GET') {
        $search = trim($_GET['search'] ?? '');
        $sql = "SELECT u.id, u.username, u.role, u.balance, u.notes, u.created_at,
                (SELECT l.ip FROM login_logs l WHERE l.user_id = u.id ORDER BY l.created_at DESC LIMIT 1) AS last_ip
                FROM users u";
        if ($search) {
            $stmt = $pdo->prepare($sql . " WHERE u.username LIKE ? ORDER BY u.id");
            $stmt->execute(['%' . $search . '%']);
        } else {
            $stmt = $pdo->query($sql . ' ORDER BY u.id');
        }
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
        // 先记用户名用于审计
        $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        recordAudit($pdo, $user['id'], 'delete_user', 'user', $id,
            '删除用户: ' . ($targetUser['username'] ?? 'ID:' . $id));
        ok();
    }
}

// ========== 图片管理 ==========
if ($action === 'images') {
    if ($method === 'GET') {
        $page    = max(1, intval($_GET['page'] ?? 1));
        $limit   = 20;
        $offset  = ($page - 1) * $limit;
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if ($user_id > 0) {
            $total = $pdo->prepare('SELECT COUNT(*) FROM gen_images WHERE user_id = ?');
            $total->execute([$user_id]);
            $total = $total->fetchColumn();
            $stmt  = $pdo->prepare(
                'SELECT i.*, u.username FROM gen_images i
                 JOIN users u ON i.user_id = u.id
                 WHERE i.user_id = ?
                 ORDER BY i.created_at DESC LIMIT :l OFFSET :o'
            );
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':o', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $total = $pdo->query('SELECT COUNT(*) FROM gen_images')->fetchColumn();
            $stmt  = $pdo->prepare(
                'SELECT i.*, u.username FROM gen_images i
                 JOIN users u ON i.user_id = u.id
                 ORDER BY i.created_at DESC LIMIT :l OFFSET :o'
            );
            $stmt->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':o', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        $list = $stmt->fetchAll();

        ok(['list' => $list, 'total' => intval($total), 'page' => $page, 'pages' => ceil($total / $limit)]);
    }

    if ($method === 'POST') {
        // 批量删除
        $ids = $input['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) err('ids 参数缺失');
        $config = require __DIR__ . '/../config.php';
        $saveDir = rtrim($config['save_dir'], '/');
        $deleted = 0;
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id <= 0) continue;
            // 先查文件名，删除物理文件
            $img = $pdo->prepare('SELECT filename FROM gen_images WHERE id = ?');
            $img->execute([$id]);
            $row = $img->fetch();
            if ($row) {
                // 尝试用户子目录 + 根目录
                $stmt2 = $pdo->prepare('SELECT u.username FROM gen_images i JOIN users u ON i.user_id = u.id WHERE i.id = ?');
                $stmt2->execute([$id]);
                $userRow = $stmt2->fetch();
                $filePath = '';
                if ($userRow) {
                    $filePath = $saveDir . '/' . $userRow['username'] . '/' . $row['filename'];
                    if (!file_exists($filePath)) $filePath = $saveDir . '/' . $row['filename'];
                } else {
                    $filePath = $saveDir . '/' . $row['filename'];
                }
                if (file_exists($filePath)) @unlink($filePath);
            }
            $pdo->prepare('DELETE FROM gen_images WHERE id = ?')->execute([$id]);
            $deleted++;
        }
        ok(['count' => $deleted]);
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

// ========== 单张图片详情 ==========
if ($action === 'image_detail') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT i.*, u.username FROM gen_images i JOIN users u ON i.user_id = u.id WHERE i.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) err('图片不存在', 404);
    ok($row);
}

// ========== 配置管理（多 Profile）==========
if ($action === 'config') {
    $configFile = __DIR__ . '/../config.php';

    if ($method === 'GET') {
        $config = require $configFile;
        $profiles = [];
        foreach ($config['profiles'] ?? [] as $name => $p) {
            $key = $p['api_key'] ?? '';
            $profiles[$name] = [
                'name'     => $name,
                'key_masked' => $key ? substr($key,0,8).'****'.substr($key,-4) : '(未设置)',
                'base_url' => $p['base_url'] ?? '',
            ];
        }
        ok(['active' => $config['active'] ?? 'default', 'profiles' => $profiles]);
    }

    if ($method === 'POST') {
        $action2 = $input['action'] ?? 'save'; // save | switch | add | delete
        $config  = require $configFile;

        if ($action2 === 'add') {
            $name = trim($input['name'] ?? '');
            if (!$name) err('名称不能为空');
            $config['profiles'][$name] = ['api_key' => '', 'base_url' => ''];
            $auditDetail = '添加配置: ' . $name;
        } elseif ($action2 === 'delete') {
            $name = $input['name'] ?? '';
            if ($name === 'default') err('不能删除默认配置');
            unset($config['profiles'][$name]);
            if ($config['active'] === $name) $config['active'] = 'default';
            $auditDetail = '删除配置: ' . $name;
        } elseif ($action2 === 'switch') {
            $name = $input['name'] ?? 'default';
            if (!isset($config['profiles'][$name])) err('配置不存在');
            $config['active'] = $name;
            $auditDetail = '切换配置: ' . $name;
        } elseif ($action2 === 'save') {
            $name = $input['name'] ?? 'default';
            $newKey = trim($input['api_key'] ?? '');
            $newUrl = trim($input['base_url'] ?? '');
            if (!isset($config['profiles'][$name])) $config['profiles'][$name] = [];
            if ($newKey) $config['profiles'][$name]['api_key'] = $newKey;
            if ($newUrl) $config['profiles'][$name]['base_url'] = $newUrl;
            $auditDetail = '保存配置: ' . $name . ($newKey ? ' (更新Key)' : '') . ($newUrl ? ' (更新URL)' : '');
        }

        $export = var_export($config, true);
        file_put_contents($configFile, "<?php\nreturn {$export};\n");
        recordAudit($pdo, $user['id'], 'config_change', 'config', 0, $auditDetail ?? '');
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

// ========== API 调用日志 ==========
if ($action === 'api_logs') {
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = 30;
    $offset = ($page - 1) * $limit;
    $total = $pdo->query('SELECT COUNT(*) FROM api_logs')->fetchColumn();
    $stmt  = $pdo->prepare(
        'SELECT l.*, u.username FROM api_logs l
         LEFT JOIN users u ON l.user_id = u.id
         ORDER BY l.created_at DESC LIMIT :l OFFSET :o'
    );
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    ok(['list' => $stmt->fetchAll(), 'total' => intval($total), 'page' => $page, 'pages' => ceil($total / $limit)]);
}

// ========== 用户详情 ==========
if ($action === 'user_detail') {
    $uid = intval($_GET['uid'] ?? 0);
    $u = $pdo->prepare('SELECT id, username, password, role, created_at FROM users WHERE id = ?');
    $u->execute([$uid]);
    $userInfo = $u->fetch();
    if (!$userInfo) err('用户不存在', 404);

    $lastIP = $pdo->prepare('SELECT ip FROM login_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $lastIP->execute([$uid]);

    $todayLogins = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $todayLogins->execute([$uid]);

    $totalLogins = $pdo->prepare('SELECT COUNT(*) FROM login_logs WHERE user_id = ?');
    $totalLogins->execute([$uid]);

    ok([
        'username'        => $userInfo['username'],
        'password_hash'   => $userInfo['password'],
        'role'            => $userInfo['role'],
        'created_at'      => $userInfo['created_at'],
        'last_ip'         => $lastIP->fetchColumn() ?: '暂无',
        'today_logins'    => intval($todayLogins->fetchColumn()),
        'total_logins'    => intval($totalLogins->fetchColumn()),
    ]);
}

// ========== 充值记录 ==========
if ($action === 'recharge_logs') {
    $stmt = $pdo->query(
        "SELECT b.*, u.username FROM balance_logs b
         JOIN users u ON b.user_id = u.id
         WHERE b.type = 'topup'
         ORDER BY b.created_at DESC LIMIT 100"
    );
    $list = $stmt->fetchAll();

    $summary = $pdo->query(
        "SELECT
            COALESCE(SUM(amount),0) AS total,
            COALESCE(SUM(CASE WHEN DATE(created_at)=CURDATE() THEN amount ELSE 0 END),0) AS today,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount ELSE 0 END),0) AS week7,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END),0) AS month30
         FROM balance_logs WHERE type='topup'"
    )->fetch();

    ok(['list' => $list, 'summary' => $summary]);
}

// ========== 功能开关 ==========
if ($action === 'features') {
    $configFile = __DIR__ . '/../config.php';
    $config = require $configFile;

    if ($method === 'GET') {
        ok($config['features'] ?? []);
    }
    if ($method === 'POST') {
        $key = $input['key'] ?? '';
        $val = $input['value'] ?? false;
        // 布尔值保持 bool，字符串保持 string
        if ($val === true || $val === false || $val === 'true' || $val === 'false') {
            $val = ($val === true || $val === 'true');
        }
        if (!isset($config['features'])) $config['features'] = [];
        $config['features'][$key] = $val;
        $export = var_export($config, true);
        file_put_contents($configFile, "<?php\nreturn {$export};\n");
        ok();
    }
}

// ========== 统计仪表盘 ==========
if ($action === 'stats') {
    // 每日生成量（最近30天）
    $daily = $pdo->query(
        "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM gen_images
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at) ORDER BY day"
    )->fetchAll();

    // 模型使用分布
    $models = $pdo->query(
        "SELECT model, COUNT(*) AS cnt FROM gen_images
         WHERE model != '' GROUP BY model ORDER BY cnt DESC LIMIT 10"
    )->fetchAll();

    // 用户活跃度（最近30天）
    $users = $pdo->query(
        "SELECT u.username, COUNT(*) AS cnt FROM gen_images i
         JOIN users u ON i.user_id = u.id
         WHERE i.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY i.user_id ORDER BY cnt DESC LIMIT 10"
    )->fetchAll();

    // 今日统计
    $today = $pdo->query(
        "SELECT COUNT(*) AS total,
                COUNT(DISTINCT user_id) AS active_users
         FROM gen_images WHERE DATE(created_at) = CURDATE()"
    )->fetch();

    // 总览
    $overview = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM gen_images) AS total_images,
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM gen_images WHERE deleted_at IS NOT NULL) AS deleted_images,
            (SELECT COUNT(*) FROM gen_images WHERE DATE(created_at) = CURDATE()) AS today_images"
    )->fetch();

    // 访问统计
    $visits = [];
    try {
        // 每日访问（近30天）
        $visitsDaily = $pdo->query(
            "SELECT visit_date AS day, SUM(visit_count) AS cnt FROM page_visits
             WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY visit_date ORDER BY day"
        )->fetchAll();

        // 今日访问
        $visitsToday = $pdo->query(
            "SELECT COALESCE(SUM(visit_count), 0) AS cnt FROM page_visits WHERE visit_date = CURDATE()"
        )->fetchColumn();

        // 昨日访问
        $visitsYesterday = $pdo->query(
            "SELECT COALESCE(SUM(visit_count), 0) AS cnt FROM page_visits
             WHERE visit_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
        )->fetchColumn();

        // 总访问
        $visitsTotal = $pdo->query("SELECT COALESCE(SUM(visit_count), 0) FROM page_visits")->fetchColumn();

        // 近7天
        $visits7d = $pdo->query(
            "SELECT COALESCE(SUM(visit_count), 0) FROM page_visits
             WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        )->fetchColumn();

        // 某天查询（支持 ?date=2026-05-27）
        $dateParam = $_GET['date'] ?? '';
        $visitsDate = null;
        if ($dateParam && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
            $visitsDate = $pdo->query(
                "SELECT COALESCE(SUM(visit_count), 0) FROM page_visits WHERE visit_date = '{$dateParam}'"
            )->fetchColumn();
        }

        $visits = [
            'total'       => (int)$visitsTotal,
            'today'       => (int)$visitsToday,
            'yesterday'   => (int)$visitsYesterday,
            'last_7d'     => (int)$visits7d,
            'daily'       => $visitsDaily,
            'date_query'  => $visitsDate,
            'date_param'  => $dateParam
        ];
    } catch (\Throwable $e) {}

    // CSV 导出
    if (($_GET['format'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stats_export_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
        fputcsv($out, ['=== 概览 ===']);
        fputcsv($out, ['总生成量', '今日生成', '总用户数', '已删图片']);
        fputcsv($out, [$overview['total_images']??0, $overview['today_images']??0, $overview['total_users']??0, $overview['deleted_images']??0]);
        fputcsv($out, ['']);
        fputcsv($out, ['=== 每日生成量 ===']);
        fputcsv($out, ['日期', '数量']);
        foreach ($daily as $d) fputcsv($out, [$d['day'], $d['cnt']]);
        fputcsv($out, ['']);
        fputcsv($out, ['=== 用户活跃度 ===']);
        fputcsv($out, ['用户名', '生成数量']);
        foreach ($users as $u) fputcsv($out, [$u['username'], $u['cnt']]);
        fputcsv($out, ['']);
        fputcsv($out, ['=== 模型使用分布 ===']);
        fputcsv($out, ['模型', '数量']);
        foreach ($models as $m) fputcsv($out, [$m['model'], $m['cnt']]);
        fputcsv($out, ['']);
        fputcsv($out, ['=== 访问统计 ===']);
        fputcsv($out, ['总访问', '今日', '昨日', '近7天']);
        fputcsv($out, [$visits['total']??0, $visits['today']??0, $visits['yesterday']??0, $visits['last_7d']??0]);
        fclose($out);
        exit;
    }

    ok([
        'daily'   => $daily,
        'models'  => $models,
        'users'   => $users,
        'today'   => $today,
        'overview'=> $overview,
        'visits'  => $visits
    ]);
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

// ========== 更新用户备注 ==========
if ($action === 'update_note') {
    $uid   = intval($input['user_id'] ?? 0);
    $notes = mb_substr(trim($input['notes'] ?? ''), 0, 500);
    $pdo->prepare('UPDATE users SET notes = ? WHERE id = ?')->execute([$notes, $uid]);
    ok(['notes' => $notes]);
}

// ========== 批量加积分 ==========
if ($action === 'batch_topup') {
    $user_ids = $input['user_ids'] ?? [];
    $amount   = floatval($input['amount'] ?? 0);
    $reason   = trim($input['reason'] ?? '批量加积分');
    if (empty($user_ids)) err('请选择用户');
    if ($amount <= 0) err('金额必须大于 0');
    $count = 0;
    foreach ($user_ids as $uid) {
        $uid = intval($uid);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([$amount, $uid]);
            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
            $stmt->execute([$uid]);
            $newBal = $stmt->fetchColumn();
            $pdo->prepare('INSERT INTO balance_logs (user_id, amount, type, reason, balance_after) VALUES (?,?,?,?,?)')
                ->execute([$uid, $amount, 'topup', $reason, $newBal]);
            $pdo->commit();
            $count++;
        } catch (\Throwable $e) {
            $pdo->rollBack();
        }
    }
    recordAudit($pdo, $user['id'], 'batch_topup', 'users', 0,
        '批量加积分: ' . $count . ' 位用户, 每人 ¥' . number_format($amount, 2) . ', 原因: ' . $reason);
    ok(['count' => $count, 'amount' => $amount]);
}

// ========== 审计日志 ==========
if ($action === 'audit_logs') {
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;
    $total = $pdo->query('SELECT COUNT(*) FROM admin_audit')->fetchColumn();
    $stmt = $pdo->prepare(
        'SELECT a.*, u.username AS admin_name FROM admin_audit a
         LEFT JOIN users u ON a.admin_id = u.id
         ORDER BY a.created_at DESC LIMIT :l OFFSET :o'
    );
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    ok(['list' => $stmt->fetchAll(), 'total' => intval($total), 'page' => $page, 'pages' => ceil($total / $limit)]);
}

// ========== 通知数据 ==========
if ($action === 'notifications') {
    $newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $failedApi = $pdo->query("SELECT COUNT(*) FROM api_logs WHERE status = 'error' AND DATE(created_at) = CURDATE()")->fetchColumn();
    $recentErrors = $pdo->query(
        "SELECT l.*, u.username FROM api_logs l
         LEFT JOIN users u ON l.user_id = u.id
         WHERE l.status = 'error'
         ORDER BY l.created_at DESC LIMIT 20"
    )->fetchAll();
    $recentRegs = $pdo->query(
        "SELECT id, username, created_at FROM users WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 20"
    )->fetchAll();
    ok([
        'new_users_today' => intval($newUsers),
        'failed_api_today' => intval($failedApi),
        'total_events' => intval($newUsers) + intval($failedApi),
        'recent_errors' => $recentErrors,
        'recent_registrations' => $recentRegs
    ]);
}

err('未知 action');
