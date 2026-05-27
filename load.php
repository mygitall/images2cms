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

// 查用户子目录（兼容主 app 和 images20 两种 uploads 路径）
$found = false;
if (!empty($user) && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}\-]+$/u', $user)) {
    // 主 app 的 uploads 路径
    $mainUploads = dirname($saveDir) . '/htdocs/uploads/' . $user . '/' . $file;
    // images20 自己的 uploads
    $img20Uploads = $saveDir . '/' . $user . '/' . $file;
    foreach ([$mainUploads, $img20Uploads] as $fp) {
        if (file_exists($fp)) { $filePath = $fp; $found = true; break; }
    }
}

if (!$found) {
    $filePath = $saveDir . '/' . $file;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    header('Content-Type: image/png');
    // 返回 1x1 透明 PNG，避免前端报错
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

// 缩略图模式：生成 80x80 缩略图
$thumb = ($_GET['thumb'] ?? '') === '1';
if ($thumb && in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
    $src = null;
    switch ($ext) {
        case 'jpeg': case 'jpg': $src = @imagecreatefromjpeg($filePath); break;
        case 'png': $src = @imagecreatefrompng($filePath); break;
        case 'gif': $src = @imagecreatefromgif($filePath); break;
        case 'webp': $src = @imagecreatefromwebp($filePath); break;
    }
    if ($src) {
        $w = imagesx($src);
        $h = imagesy($src);
        $tw = 80; $th = 80;
        if ($w > $h) { $nw = $tw; $nh = max(1, intval($h * ($tw / $w))); }
        else { $nh = $th; $nw = max(1, intval($w * ($th / $h))); }
        $thumbImg = imagecreatetruecolor($tw, $th);
        imagealphablending($thumbImg, false);
        imagesavealpha($thumbImg, true);
        $transparent = imagecolorallocatealpha($thumbImg, 0, 0, 0, 127);
        imagefill($thumbImg, 0, 0, $transparent);
        imagealphablending($thumbImg, true);
        $dx = intval(($tw - $nw) / 2);
        $dy = intval(($th - $nh) / 2);
        imagecopyresampled($thumbImg, $src, $dx, $dy, 0, 0, $nw, $nh, $w, $h);
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=31536000');
        imagepng($thumbImg);
        imagedestroy($src);
        imagedestroy($thumbImg);
        exit;
    }
}

$mimeTypes = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'];
$mime = $mimeTypes[$ext] ?? 'image/png';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000');
readfile($filePath);
