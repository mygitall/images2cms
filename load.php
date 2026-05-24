<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
/**
 * 从服务器加载已保存的图片（支持用户子目录）
 * GET: load.php?file=xxx.png&user=username
 */

$config  = require __DIR__ . '/config.php';
$saveDir = rtrim($config['save_dir'], '/');
$file    = $_GET['file'] ?? '';
$user    = $_GET['user'] ?? '';

if (empty($file)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '缺少 file 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '文件名包含非法字符'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 先查用户子目录，找不到则查根目录
$rootDir = $saveDir;
if (!empty($user) && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}\-]+$/u', $user)) {
    $filePath = $saveDir . '/' . $user . '/' . $file;
    if (file_exists($filePath)) {
        $found = true;
    }
}

if (empty($found)) {
    $filePath = $rootDir . '/' . $file;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    header('Content-Type: image/png');
    // 返回 1x1 透明 PNG，避免前端报错
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimeTypes = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'];
$mime = $mimeTypes[$ext] ?? 'image/png';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000');
readfile($filePath);
