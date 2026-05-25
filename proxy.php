<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
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

$config  = require __DIR__ . '/config.php';
$active  = $config['active'] ?? 'default';
$profile = $config['profiles'][$active] ?? $config['profiles']['default'] ?? [];

$apiKey  = trim($profile['api_key'] ?? '');
$baseUrl = rtrim($profile['base_url'] ?? '', '/');
if (empty($baseUrl)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '未配置 Base URL，请在后台设置'], JSON_UNESCAPED_UNICODE);
    exit;
}
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

// 浏览器关闭后继续执行（不中断生图）
ignore_user_abort(true);

// ---- cURL 配置 ----
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);          // 返回响应头+体
curl_setopt($ch, CURLOPT_TIMEOUT, 900);          // 15 分钟超时（生图较慢）
curl_setopt($ch, CURLOPT_ENCODING, '');           // 自动解压 gzip/deflate

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
$startTime = microtime(true);
$response  = curl_exec($ch);
$duration  = round((microtime(true) - $startTime) * 1000);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlError = curl_error($ch);
curl_close($ch);

// ---- 记录日志 ----
try {
    require_once __DIR__ . '/db.php';
    if (session_status() === PHP_SESSION_NONE) session_start();
    $uid = $_SESSION['user']['id'] ?? null;
    $isErr = ($curlError || $httpCode >= 400);
    $stmt = $pdo->prepare(
        'INSERT INTO api_logs (user_id, endpoint, method, status, http_code, duration_ms, error_msg)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $uid,
        $targetPath,
        $method,
        $isErr ? 'error' : 'success',
        $httpCode ?: 502,
        $duration,
        $curlError ?: ($isErr ? 'HTTP ' . $httpCode : null)
    ]);
} catch (Exception $e) { /* 日志记录失败不影响主流程 */ }

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

// ---- 自动保存生成的图片（关闭浏览器也不丢失）----
$isImageEndpoint = (stripos($targetPath, 'generateContent') !== false || stripos($targetPath, 'images/generations') !== false || stripos($targetPath, 'chat/completions') !== false);
if ($uid && $isImageEndpoint && $httpCode >= 200 && $httpCode < 400 && !empty($responseBody)) {
    $saved = false;
    $saveFilename = '';

    try {
        $config = require __DIR__ . '/config.php';
        $saveDir = rtrim($config['save_dir'], '/');
        $username = $_SESSION['user']['username'] ?? '';

        // 尝试解析响应中的图片
        $data = @json_decode($responseBody, true);

        if ($data) {
            // OpenAI images 格式: { data: [{ url, b64_json }] }
            $imgUrl = $data['data'][0]['url'] ?? '';
            $imgB64 = $data['data'][0]['b64_json'] ?? '';
            if (!$imgUrl) {
                // OpenAI chat 格式: choices[0].message.content (可能包含 image_url)
                $content = $data['choices'][0]['message']['content'] ?? '';
                if (is_array($content)) {
                    foreach ($content as $c) {
                        if ($c['type'] === 'image_url' && !empty($c['image_url']['url'])) {
                            $imgUrl = $c['image_url']['url']; break;
                        }
                    }
                }
            }

            // Gemini 格式: candidates[0].content.parts (inline_data)
            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            foreach ($parts as $p) {
                if (!empty($p['inline_data']['data'])) {
                    $imgB64 = $p['inline_data']['data']; break;
                }
            }

            $ext = 'png';
            if (strpos(($imgUrl ?: $imgB64), '.jpg') !== false || strpos(($imgUrl ?: $imgB64), 'jpeg') !== false) $ext = 'jpg';

            $saveFilename = 'auto-' . date('YmdHis') . '-' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

            // 保存到 user 子目录
            if ($username && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}\-]+$/u', $username)) {
                $userDir = $saveDir . '/' . $username;
                if (!is_dir($userDir)) mkdir($userDir, 0755, true);

                if ($imgUrl) {
                    $ch = curl_init($imgUrl);
                    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true, CURLOPT_ENCODING => '']);
                    $binary = curl_exec($ch);
                    curl_close($ch);
                    if ($binary) { file_put_contents($userDir . '/' . $saveFilename, $binary); $saved = true; }
                } elseif ($imgB64) {
                    $binary = base64_decode($imgB64);
                    if ($binary) { file_put_contents($userDir . '/' . $saveFilename, $binary); $saved = true; }
                }
            }

            // 写入 MySQL 记录
            if ($saved && isset($pdo)) {
                $prompt = '';
                if (!empty($data['choices'][0]['message']['content'])) {
                    $mc = $data['choices'][0]['message']['content'];
                    if (is_array($mc)) {
                        foreach ($mc as $ci) { if ($ci['type'] === 'text') { $prompt = $ci['text']; break; } }
                    } elseif (is_string($mc)) { $prompt = $mc; }
                }
                $parts2 = $data['candidates'][0]['content']['parts'] ?? $data['contents'][0]['parts'] ?? [];
                foreach ($parts2 as $p2) { if (!empty($p2['text'])) { $prompt = $p2['text']; break; } }

                $stmt2 = $pdo->prepare('INSERT INTO gen_images (user_id, filename, prompt, model) VALUES (?, ?, ?, ?)');
                $stmt2->execute([$uid, $saveFilename, $prompt ?: '', '']);
            }
        }
    } catch (\Throwable $e) {
        // 保存失败不影响主流程
    }
}

// ---- 输出响应体 ----
echo $responseBody;
