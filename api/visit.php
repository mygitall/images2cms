<?php
/**
 * 访问追踪 API — 记录前台页面访问
 * POST /api/visit.php  { page: 'index' }
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$page  = trim($input['page'] ?? 'index');
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';
$today = date('Y-m-d');

try {
    $pdo->prepare(
        "INSERT INTO page_visits (page, ip, visit_date, visit_count) VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE visit_count = visit_count + 1"
    )->execute([$page, $ip, $today]);
} catch (\Throwable $e) {}

echo json_encode(['ok' => true]);
