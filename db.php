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
