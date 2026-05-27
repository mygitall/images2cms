<?php
require_once __DIR__ . '/../api/_lib/helpers.php';

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'root';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? 'root';

try {
    if (!extension_loaded('pdo_mysql')) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['error' => '未安装 PDO MySQL 扩展'], JSON_UNESCAPED_UNICODE));
    }
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['error' => 'DB error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// 余额表
try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `balance` DECIMAL(10,2) DEFAULT 0.00"); } catch (\Throwable $e) {}
try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `notes` VARCHAR(500) DEFAULT ''"); } catch (\Throwable $e) {}
$pdo->exec("
  CREATE TABLE IF NOT EXISTS `balance_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `amount`      DECIMAL(10,2) NOT NULL,
    `type`        VARCHAR(20) DEFAULT 'deduct',
    `reason`      VARCHAR(255) DEFAULT '',
    `balance_after` DECIMAL(10,2) DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS `page_visits` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `page`        VARCHAR(50) DEFAULT 'index',
    `ip`          VARCHAR(45) DEFAULT '',
    `visit_date`  DATE NOT NULL,
    `visit_count` INT DEFAULT 1,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_page_date_ip` (`page`, `visit_date`, `ip`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 自动清理旧日志（每次 5% 概率执行，保留天数从配置读取，默认 30）
if (mt_rand(1, 20) === 1) {
    $config = require __DIR__ . '/config.php';
    $retentionDays = intval($config['features']['log_retention_days'] ?? 30);
    try { $pdo->exec("DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL {$retentionDays} DAY)"); } catch (\Throwable $e) {}
    try { $pdo->exec("DELETE FROM login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL {$retentionDays} DAY)"); } catch (\Throwable $e) {}
}

$pdo->exec("
  CREATE TABLE IF NOT EXISTS admin_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(50),
    target_type VARCHAR(30),
    target_id INT,
    detail TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
