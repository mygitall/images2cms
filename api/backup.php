<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
/**
 * 数据备份 API — SQL 格式，支持大数据
 * GET  ?action=full       — 导出全部
 * GET  ?action=tables     — 列出表
 * GET  ?action=single&t=X — 导出单表
 * POST ?action=import     — 导入 SQL 文件
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
$method = $_SERVER['REQUEST_METHOD'];

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

// ========== 导出单表（SQL） ==========
if ($action === 'single') {
    $t = $_GET['t'] ?? '';
    if (!isset($tables[$t])) { http_response_code(400); echo '[]'; exit; }
    exportSQL([$t => $tables[$t]], $t . '_' . date('Ymd-His') . '.sql');
}

// ========== 导出全部（SQL） ==========
if ($action === 'full') {
    exportSQL($tables, 'full_backup_' . date('Ymd-His') . '.sql');
}

// ========== 导入 SQL ==========
if ($action === 'import') {
    @set_time_limit(600);
    @ini_set('memory_limit', '512M');

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => '请上传 SQL 文件'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = file_get_contents($file['tmp_name']);
    if (!$sql) {
        http_response_code(400);
        echo json_encode(['error' => '文件为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $imported = 0;
    $errors = [];

    // 按行流式解析：遇到 INSERT 语句直接执行，不等所有行读完
    $lines = explode("\n", $sql);
    $current = '';
    $insertCount = 0;

    $pdo->beginTransaction();
    try {
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] === '-' || substr($trimmed, 0, 2) === '/*') continue;

            $current .= $line . "\n";

            // 检测完整 SQL 语句（以分号结尾）
            if (substr(rtrim($current), -1) === ';') {
                $stmt = trim($current);
                $current = '';

                $upper6 = strtoupper(substr($stmt, 0, 6));
                if ($upper6 === 'INSERT') {
                    try {
                        $pdo->exec($stmt);
                        $insertCount++;
                        $imported = $insertCount;
                    } catch (\Throwable $e) {
                        $errors[] = substr($stmt, 0, 80) . '... — ' . $e->getMessage();
                    }
                } elseif (preg_match('/^(DROP|CREATE|TRUNCATE|SET\s+FOREIGN|SET\s+NAMES)/i', $stmt)) {
                    try { $pdo->exec($stmt); } catch (\Throwable $e) {}
                }
            }
        }

        // 处理最后未结束的语句
        $stmt = trim($current);
        if (!empty($stmt)) {
            $upper6 = strtoupper(substr($stmt, 0, 6));
            if ($upper6 === 'INSERT') {
                try { $pdo->exec($stmt); $imported++; } catch (\Throwable $e) {}
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
    }

    // 处理 __CONFIG__ 和 __ENV__
    if (preg_match('/\/\*__CONFIG__\*\/(.*?)\/\*__ENDCONFIG__\*\//s', $sql, $m)) {
        $cfg = json_decode($m[1], true);
        if ($cfg) {
            $export = var_export($cfg, true);
            file_put_contents(__DIR__ . '/../config.php', "<?php\nreturn {$export};\n");
        }
    }
    if (preg_match('/\/\*__ENV__\*\/(.*?)\/\*__ENDENV__\*\//s', $sql, $m)) {
        $envData = json_decode($m[1], true);
        if ($envData && !empty($envData['keys'])) {
            $envFile = __DIR__ . '/../../.env';
            if (file_exists($envFile)) {
                $existing = file_get_contents($envFile);
                foreach ($envData['keys'] as $key => $val) {
                    $existing = preg_replace("/^{$key}=.*$/m", "{$key}={$val}", $existing);
                }
                file_put_contents($envFile, $existing);
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'imported' => $imported, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 删除所有数据 ======
if ($action === 'delete_all') {
    if (($input['confirm'] ?? '') !== 'YES_DELETE_ALL') {
        http_response_code(400);
        echo json_encode(['error' => '需要确认参数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $t => $label) {
        try { $pdo->exec("TRUNCATE TABLE `{$t}`"); } catch (\Throwable $e) {}
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== 导出 SQL 函数 ==========
function exportSQL($tables, $filename) {
    global $pdo;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = "-- Image Studio 数据备份\n-- 时间: " . date('Y-m-d H:i:s') . "\n-- PHP: " . phpversion() . "\n\n";
    $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    // 导出表结构 + 数据
    foreach ($tables as $t => $label) {
        try {
            $out .= "-- ---------- {$label} (`{$t}`) ----------\n\n";
            $out .= "DROP TABLE IF EXISTS `{$t}`;\n";

            // CREATE TABLE
            $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM);
            if ($create) {
                $out .= $create[1] . ";\n\n";
            }

            $out .= "TRUNCATE TABLE `{$t}`;\n\n";

            // INSERT 批量导出（每批 50 行），用游标避免内存溢出
            $rows = $pdo->query("SELECT * FROM `{$t}`");
            $batch = [];
            $count = 0;
            $colsBuilt = '';

            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                if (empty($colsBuilt)) $colsBuilt = '`' . implode('`,`', array_keys($row)) . '`';
                $vals = array_map(function ($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                $batch[] = '(' . implode(',', $vals) . ')';
                $count++;

                if (count($batch) >= 50) {
                    $out .= "INSERT INTO `{$t}` ({$colsBuilt}) VALUES\n" . implode(",\n", $batch) . ";\n\n";
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $out .= "INSERT INTO `{$t}` ({$colsBuilt}) VALUES\n" . implode(",\n", $batch) . ";\n\n";
            }

            $out .= "\n";
        } catch (\Throwable $e) {
            $out .= "-- 导出 {$t} 失败: " . $e->getMessage() . "\n\n";
        }
    }

    // 导出 config（脱敏后嵌入注释）
    $cfg = require __DIR__ . '/../config.php';
    if (isset($cfg['profiles'])) {
        foreach ($cfg['profiles'] as &$p) {
            unset($p['api_key'], $p['base_url']);
        }
    }
    unset($cfg['save_dir']);
    $out .= "/*__CONFIG__*/" . json_encode($cfg, JSON_UNESCAPED_UNICODE) . "/*__ENDCONFIG__*/\n";

    // 导出 .env 非敏感设置
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $envKeys = [];
        $safeKeys = ['APP_URL','CIYUAN_BASE_URL','VITE_SUPABASE_URL','VITE_SUPABASE_ANON_KEY',
            'SUPER_ADMIN_EMAILS','VITE_GA_MEASUREMENT_ID','GA4_PROPERTY_ID',
            'GOOGLE_ANALYTICS_CLIENT_ID','GOOGLE_ANALYTICS_CLIENT_EMAIL','GOOGLE_ANALYTICS_REDIRECT_URI'];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if (in_array($key, $safeKeys)) {
                $envKeys[$key] = $val;
            }
        }
        if (!empty($envKeys)) {
            $out .= "/*__ENV__*/" . json_encode(['keys' => $envKeys], JSON_UNESCAPED_UNICODE) . "/*__ENDENV__*/\n";
        }
    }

    $out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

    echo $out;
    exit;
}
