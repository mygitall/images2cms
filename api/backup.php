<?php
/**
 * 数据备份 API
 * GET ?action=full      — 导出全部（JSON）
 * GET ?action=tables    — 列出可备份的表
 * GET ?action=single&t=xxx — 导出单表
 */

require_once __DIR__ . '/../db.php';
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
    'gen_images' => '图片记录',
    'users'      => '用户管理',
    'login_logs' => '登录日志',
    'api_logs'   => '操作记录',
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
    // 加上 config.php
    $all['config'] = [require __DIR__ . '/../config.php'];
    exportJson($all, 'full_backup_' . date('Ymd-His') . '.json');
}

// ====== 删除所有数据 ======
if ($action === 'delete_all') {
    if (($input['confirm'] ?? '') !== 'YES_DELETE_ALL') {
        http_response_code(400);
        echo json_encode(['error' => '需要确认参数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('TRUNCATE TABLE gen_images');
    $pdo->exec('TRUNCATE TABLE login_logs');
    $pdo->exec('TRUNCATE TABLE api_logs');
    $pdo->exec('TRUNCATE TABLE users');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    // 重建默认管理员
    $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 导入 JSON 数据 ======
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
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    // 支持完整备份或单表备份
    $data = isset($json['gen_images']) ? $json : ['gen_images' => $json];

    $tableMap = ['gen_images' => 'gen_images', 'users' => 'users', 'login_logs' => 'login_logs', 'api_logs' => 'api_logs'];
    foreach ($tableMap as $key => $table) {
        if (!empty($data[$key]) && is_array($data[$key])) {
            foreach ($data[$key] as $row) {
                if (!is_array($row)) continue;
                $cols = array_keys($row);
                $vals = array_values($row);
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $colNames = '`' . implode('`,`', $cols) . '`';
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO `{$table}` ({$colNames}) VALUES ({$placeholders})");
                    $stmt->execute($vals);
                    $imported++;
                } catch (Exception $e) {}
            }
        }
    }

    // 导入 config
    if (!empty($data['config'][0])) {
        $export = var_export($data['config'][0], true);
        file_put_contents(__DIR__ . '/../config.php', "<?php\nreturn {$export};\n");
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'imported' => $imported], JSON_UNESCAPED_UNICODE);
    exit;
}

function exportJson($data, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
