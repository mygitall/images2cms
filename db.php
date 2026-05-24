<?php
/**
 * 数据库连接 & 表初始化
 */

$dbHost = 'localhost';
$dbPort = '3306';
$dbName = 'root';
$dbUser = 'root';
$dbPass = 'root';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '数据库连接失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- 自动建表 ----
$pdo->exec("
  CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)  NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('user','admin') DEFAULT 'user',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS `gen_images` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `filename`    VARCHAR(255) NOT NULL,
    `prompt`      TEXT,
    `model`       VARCHAR(100),
    `aspect`      VARCHAR(20),
    `resolution`  VARCHAR(10),
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 添加限制字段（如果不存在）
foreach (['daily_limit INT DEFAULT NULL', 'total_limit INT DEFAULT NULL'] as $col) {
    try { $pdo->exec("ALTER TABLE `users` ADD COLUMN {$col}"); } catch (PDOException $e) {}
}
// gen_images 软删除字段
try { $pdo->exec("ALTER TABLE `gen_images` ADD COLUMN `deleted_at` DATETIME DEFAULT NULL"); } catch (PDOException $e) {}

// 登录日志表
$pdo->exec("
  CREATE TABLE IF NOT EXISTS `login_logs` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `ip`         VARCHAR(45) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_time` (`user_id`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// API 调用日志表
$pdo->exec("
  CREATE TABLE IF NOT EXISTS `api_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT DEFAULT NULL,
    `endpoint`    VARCHAR(255) NOT NULL,
    `method`      VARCHAR(10) DEFAULT 'POST',
    `status`      VARCHAR(20) DEFAULT 'success',
    `http_code`   INT DEFAULT 200,
    `duration_ms` INT DEFAULT 0,
    `error_msg`   TEXT,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 创建默认管理员（admin / admin123）
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute(['admin']);
if (!$stmt->fetch()) {
    $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
}
