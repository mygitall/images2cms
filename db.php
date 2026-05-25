<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$dbHost = 'localhost';
$dbPort = '3306';
$dbName = 'root';
$dbUser = 'root';
$dbPass = 'root';

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

// 自动清理 30 天前的旧日志（每次 5% 概率执行）
if (mt_rand(1, 20) === 1) {
    try { $pdo->exec("DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"); } catch (\Throwable $e) {}
    try { $pdo->exec("DELETE FROM login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"); } catch (\Throwable $e) {}
}
