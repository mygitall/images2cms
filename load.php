<?php
/**
 * 从服务器加载已保存的图片
 * GET: load.php?file=xxx.png
 */

$config  = require __DIR__ . '/config.php';
$saveDir = rtrim($config['save_dir'], '/');
$file    = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '缺少 file 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 安全检查
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '文件名包含非法字符'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filePath = $saveDir . '/' . $file;

if (!file_exists($filePath)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '文件不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 根据扩展名设置 MIME 类型
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mimeTypes = [
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
$mime = $mimeTypes[$ext] ?? 'image/png';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000');
readfile($filePath);
