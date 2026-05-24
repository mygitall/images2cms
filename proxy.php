<?php
/**
 * 通用 API 代理
 *
 * 前端请求 → proxy.php → 真实 API（自动附加 Key）
 *
 * 用法：
 *   GET  proxy.php?path=/v1/models
 *   POST proxy.php?path=/v1/chat/completions   (JSON body)
 *   POST proxy.php?path=/v1/images/edits        (multipart body)
 */

$config = require __DIR__ . '/config.php';

$apiKey  = trim($config['api_key'] ?? '');
$baseUrl = rtrim($config['base_url'] ?? 'https://api.tokln.com', '/');
$targetPath = $_GET['path'] ?? '';

// ---- 参数校验 ----
if (empty($targetPath)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '缺少 path 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($apiKey)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '请先在 config.php 中填写 api_key'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- 构建目标 URL ----
$targetUrl = $baseUrl . $targetPath;

// 如果原请求有 query string（除了 path），追加上去
$originalQuery = $_GET;
unset($originalQuery['path']);
if (!empty($originalQuery)) {
    $targetUrl .= '?' . http_build_query($originalQuery);
}

$method = $_SERVER['REQUEST_METHOD'];

// ---- cURL 配置 ----
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);          // 返回响应头+体
curl_setopt($ch, CURLOPT_TIMEOUT, 600);          // 10 分钟超时（生图较慢）

$headers = ['Authorization: Bearer ' . $apiKey];

if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'multipart/form-data') !== false) {
        // ---- 处理 multipart（图生图 /v1/images/edits）----
        $postFields = [];
        foreach ($_POST as $key => $value) {
            $postFields[$key] = $value;
        }
        foreach ($_FILES as $key => $fileInfo) {
            if (is_array($fileInfo['name'])) {
                // 数组型字段，如 image[]
                foreach ($fileInfo['name'] as $i => $name) {
                    if ($fileInfo['error'][$i] === UPLOAD_ERR_OK) {
                        $postFields[$key . '[' . $i . ']'] = new CURLFile(
                            $fileInfo['tmp_name'][$i],
                            $fileInfo['type'][$i],
                            $name
                        );
                    }
                }
            } else {
                if ($fileInfo['error'] === UPLOAD_ERR_OK) {
                    $postFields[$key] = new CURLFile(
                        $fileInfo['tmp_name'],
                        $fileInfo['type'],
                        $fileInfo['name']
                    );
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    } else {
        // ---- JSON 或其他格式：原样转发 body ----
        $rawBody = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        if ($contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
    }
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// ---- 执行请求 ----
$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '代理请求失败: ' . $curlError], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- 拆分响应头和体 ----
$responseHeaders = substr($response, 0, $headerSize);
$responseBody    = substr($response, $headerSize);

// ---- 设置响应状态码 ----
http_response_code($httpCode);

// ---- 转发 Content-Type ----
if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $responseHeaders, $matches)) {
    header('Content-Type: ' . trim($matches[1]));
}

// ---- 输出响应体 ----
echo $responseBody;
