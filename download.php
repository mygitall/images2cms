<?php
/**
 * 批量下载图片为 ZIP
 * POST { files: ["a.png","b.png"], user: "username" }
 */

$config  = require __DIR__ . '/config.php';
$saveDir = rtrim($config['save_dir'], '/');
$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$files   = $input['files'] ?? [];
$user    = $input['user']  ?? '';

if (empty($files)) {
    http_response_code(400);
    echo '[]';
    exit;
}

// 构建文件路径列表
$paths = [];
foreach ($files as $f) {
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $f)) continue;
    // 先查用户子目录
    if ($user && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}\-]+$/u', $user)) {
        $p = "{$saveDir}/{$user}/{$f}";
        if (file_exists($p)) { $paths[] = $p; continue; }
    }
    // 再查根目录
    $p = "{$saveDir}/{$f}";
    if (file_exists($p)) $paths[] = $p;
}

if (empty($paths)) {
    http_response_code(404);
    echo '[]';
    exit;
}

// 单文件直接下载
if (count($paths) === 1) {
    $ext = strtolower(pathinfo($paths[0], PATHINFO_EXTENSION));
    $mime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'][$ext] ?? 'image/png';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($paths[0]) . '"');
    readfile($paths[0]);
    exit;
}

// 多文件打包 ZIP
$zipName = 'images-' . date('Ymd-His') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo '[]';
    exit;
}

foreach ($paths as $p) {
    $zip->addFile($p, basename($p));
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
