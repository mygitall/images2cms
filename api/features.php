<?php
/**
 * 读取功能开关（公开接口，无需登录）
 */
$config   = require __DIR__ . '/../config.php';
$features = $config['features'] ?? [];
header('Content-Type: application/json; charset=utf-8');
echo json_encode($features, JSON_UNESCAPED_UNICODE);
