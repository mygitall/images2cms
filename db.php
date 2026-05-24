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

// 自动清理 30 天前的旧日志（每次 5% 概率执行）
if (mt_rand(1, 20) === 1) {
    try { $pdo->exec("DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"); } catch (\Throwable $e) {}
    try { $pdo->exec("DELETE FROM login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"); } catch (\Throwable $e) {}
}
