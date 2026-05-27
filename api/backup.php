<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
/**
 * 数据备份 API
 * GET ?action=full      — 导出全部（JSON）
 * GET ?action=tables    — 列出可备份的表
 * GET ?action=single&t=xxx — 导出单表
 */

require_once __DIR__ . '/../db.php';

if (!empty($_COOKIE['IMAGES20_ADMIN'])) {
    session_name('IMAGES20_ADMIN');
}
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => '需要管理员权限'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// 表名 → 中文名映射
$tables = [
    'gen_images'     => '图片记录',
    'users'          => '用户管理',
    'login_logs'     => '登录日志',
    'api_logs'       => 'API调用日志',
    'balance_logs'   => '积分流水',
    'case_favorites' => '案例收藏',
    'page_visits'    => '访问统计',
    'admin_audit'    => '管理员审计',
];

if ($action === 'tables') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($tables, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'single') {
    $t = $_GET['t'] ?? '';
    if (!isset($tables[$t])) { http_response_code(400); echo '[]'; exit; }
    $data = $pdo->query("SELECT * FROM `{$t}`")->fetchAll();
    exportJson($data, $t . '_' . date('Ymd-His') . '.json');
}

if ($action === 'full') {
    $all = [];
    foreach ($tables as $t => $label) {
        $all[$t] = $pdo->query("SELECT * FROM `{$t}`")->fetchAll();
    }
    // config 脱敏：不要导出 api_key、base_url、save_dir 敏感信息
    $cfg = require __DIR__ . '/../config.php';
    if (isset($cfg['profiles'])) {
        foreach ($cfg['profiles'] as &$p) {
            unset($p['api_key'], $p['base_url']);
        }
    }
    unset($cfg['save_dir']);
    $all['config'] = [$cfg];

    // 导出 .env 设置（脱敏：去掉数据库密码）
    $envFile = __DIR__ . '/../../.env';
    $all['env'] = '';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        // 移除 DB_PASS 和含 key/secret 的行
        $envContent = preg_replace('/^(DB_PASS|DB_USER|DB_NAME|DB_HOST|DB_PORT|CIYUAN_API_KEY|SUPABASE_SERVICE_ROLE_KEY|STRIPE_SECRET_KEY|STRIPE_WEBHOOK_SECRET|GOOGLE_ANALYTICS_CLIENT_SECRET|GOOGLE_ANALYTICS_REFRESH_TOKEN|GOOGLE_ANALYTICS_PRIVATE_KEY)=.*$/m', '# $1=*** 已脱敏，导入后需手动填写', $envContent);
        $all['env'] = $envContent;
    }

    exportJson($all, 'full_backup_' . date('Ymd-His') . '.json');
}

// ====== 删除所有数据 ======
if ($action === 'delete_all') {
    if (($input['confirm'] ?? '') !== 'YES_DELETE_ALL') {
        http_response_code(400);
        echo json_encode(['error' => '需要确认参数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // 清空全部 8 张表
    $allTables = ['gen_images','users','login_logs','api_logs','balance_logs','case_favorites','page_visits','admin_audit'];
    foreach ($allTables as $t) {
        try { $pdo->exec("DELETE FROM `{$t}`"); } catch (\Throwable $e) {}
    }
    // 重建默认管理员
    $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 导入 JSON 数据（完整迁移：先清空再导入） ======
if ($action === 'import') {
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => '请上传 JSON 文件'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $json = json_decode(file_get_contents($file['tmp_name']), true);
    if (!$json) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON 格式无效'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $imported = 0;
    $errors = [];

    // 支持完整备份或单表备份
    $data = isset($json['gen_images']) ? $json : ['gen_images' => $json];

    $tableMap = [
        'gen_images' => 'gen_images', 'users' => 'users',
        'login_logs' => 'login_logs', 'api_logs' => 'api_logs',
        'balance_logs' => 'balance_logs', 'case_favorites' => 'case_favorites',
        'page_visits' => 'page_visits', 'admin_audit' => 'admin_audit'
    ];

    $pdo->beginTransaction();
    try {
        // 先清空有数据的表
        foreach ($tableMap as $key => $table) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                try { $pdo->exec("DELETE FROM `{$table}`"); } catch (\Throwable $e) {}
            }
        }

        // 逐行导入，保留原始 ID
        foreach ($tableMap as $key => $table) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                foreach ($data[$key] as $row) {
                    if (!is_array($row)) continue;
                    $cols = array_keys($row);
                    $vals = array_values($row);
                    $placeholders = implode(',', array_fill(0, count($cols), '?'));
                    $colNames = '`' . implode('`,`', $cols) . '`';
                    try {
                        $stmt = $pdo->prepare("INSERT INTO `{$table}` ({$colNames}) VALUES ({$placeholders})");
                        $stmt->execute($vals);
                        $imported++;
                    } catch (\Throwable $e) {
                        $errors[] = "{$table} 行导入失败: " . $e->getMessage();
                    }
                }
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => '导入失败: ' . $e->getMessage(), 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 导入 config
    if (!empty($data['config'][0])) {
        $export = var_export($data['config'][0], true);
        file_put_contents(__DIR__ . '/../config.php', "<?php\nreturn {$export};\n");
    }

    // 导入 .env（仅恢复未被注释的行）
    if (!empty($data['env'])) {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $existing = file_get_contents($envFile);
            // 只替换未脱敏的行（以 # 开头的跳过）
            $lines = explode("\n", $data['env']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                $eq = strpos($line, '=');
                if ($eq === false) continue;
                $key = trim(substr($line, 0, $eq));
                // 只更新非敏感 key
                if (in_array($key, ['APP_URL','CIYUAN_BASE_URL','VITE_SUPABASE_URL','VITE_SUPABASE_ANON_KEY','SUPER_ADMIN_EMAILS','VITE_GA_MEASUREMENT_ID','GA4_PROPERTY_ID','GOOGLE_ANALYTICS_CLIENT_ID','GOOGLE_ANALYTICS_CLIENT_EMAIL','GOOGLE_ANALYTICS_REDIRECT_URI'])) {
                    $existing = preg_replace("/^{$key}=.*$/m", $line, $existing);
                }
            }
            file_put_contents($envFile, $existing);
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'imported' => $imported, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

function exportJson($data, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
