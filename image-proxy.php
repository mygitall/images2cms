<?php
/**
 * CORS 图片代理 —— 前端跨域无法加载 api.tokln.com 的图片时，通过本代理获取
 * GET: image-proxy.php?url=https://api.tokln.com/...
 */

$url = $_GET['url'] ?? '';

if (empty($url) || !preg_match('#^https?://#', $url)) {
    http_response_code(400);
    exit;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || empty($body)) {
    http_response_code(502);
    exit;
}

// CORS 头 + 缓存
header('Access-Control-Allow-Origin: *');
header('Content-Type: ' . ($contentType ?: 'image/png'));
header('Cache-Control: public, max-age=3600');
echo $body;
