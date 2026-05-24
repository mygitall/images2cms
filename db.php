<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
/**
 * 数据库连接 & 表初始化
 *
 * ===== 部署时修改下面 5 个变量 =====
 */
$dbHost = 'localhost';
$dbPort = '3306';
$dbName = 'root';
$dbUser = 'root';
$dbPass = 'root';

try {
    if (!extension_loaded('pdo_mysql')) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['error' => '服务器未安装 PDO MySQL 扩展，请联系空间商'], JSON_UNESCAPED_UNICODE));
    }
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['error' => 'DB error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ---- 自动建表 ----
$pdo->exec("
  CREATE TABLE IF NOT EXISTS `users` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `username`    VARCHAR(50)  NOT NULL UNIQUE,
    `password`    VARCHAR(255) NOT NULL,
    `role`        VARCHAR(10)  DEFAULT 'user',
    `daily_limit` INT          DEFAULT NULL,
    `total_limit` INT          DEFAULT NULL,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS `gen_images` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          NOT NULL,
    `filename`    VARCHAR(255) NOT NULL,
    `prompt`      TEXT,
    `model`       VARCHAR(100),
    `aspect`      VARCHAR(20),
    `resolution`  VARCHAR(10),
    `deleted_at`  DATETIME     DEFAULT NULL,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS `login_logs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT          NOT NULL,
    `ip`         VARCHAR(45)  DEFAULT '',
    `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_time` (`user_id`, `created_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS `api_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          DEFAULT NULL,
    `endpoint`    VARCHAR(255) NOT NULL,
    `method`      VARCHAR(10)  DEFAULT 'POST',
    `status`      VARCHAR(20)  DEFAULT 'success',
    `http_code`   INT          DEFAULT 200,
    `duration_ms` INT          DEFAULT 0,
    `error_msg`   TEXT,
    `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 创建/修复默认管理员
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE username = ?');
$stmt->execute(['admin']);
$admin = $stmt->fetch();
if (!$admin) {
    $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
} elseif ($admin['role'] !== 'admin') {
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')
        ->execute(['admin', $admin['id']]);
}
