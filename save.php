<?php
/**
 * 保存图片到服务器
 * POST: { url: "https://..." } 或 { image: "data:image/png;base64,..." }  + filename
 */

$config   = require __DIR__ . '/config.php';
$saveDir  = rtrim($config['save_dir'], '/');
$url      = $_POST['url']      ?? '';
$image    = $_POST['image']    ?? '';
$filename = $_POST['filename'] ?? '';

if ((empty($image) && empty($url)) || empty($filename)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '缺少 image/url 或 filename 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '文件名包含非法字符'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

$savePath = $saveDir . '/' . $filename;

// 优先从 URL 下载（PHP 无 CORS 限制）
if (!empty($url) && preg_match('#^https?://#', $url)) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $binary = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $binary === false || strlen($binary) === 0) {
        http_response_code(502);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => '下载图片失败, HTTP ' . $httpCode], JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    // base64 解码
    if (preg_match('#^data:image/\w+;base64,(.+)$#', $image, $m)) {
        $binary = base64_decode($m[1]);
    } else {
        $binary = base64_decode($image);
    }
    if ($binary === false) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'base64 解码失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$written = file_put_contents($savePath, $binary);

if ($written === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '写入文件失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success'  => true,
    'filename' => $filename,
    'size'     => $written,
    'path'     => $savePath,
], JSON_UNESCAPED_UNICODE);
