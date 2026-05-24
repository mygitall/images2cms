<?php
/**
 * 获取当前用户的历史生成记录
 * GET /api/history.php
 */

require_once __DIR__ . '/../db.php';
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '未登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 软删除 ======
if (($_GET['action'] ?? '') === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $pdo->prepare('UPDATE gen_images SET deleted_at = NOW() WHERE id = ? AND user_id = ?')
        ->execute([$id, $user['id']]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$page  = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare('SELECT id, filename, prompt, model, aspect, resolution, created_at FROM gen_images WHERE user_id = :uid AND deleted_at IS NULL ORDER BY created_at DESC LIMIT :l OFFSET :o');
$stmt->bindValue(':uid', $user['id'], PDO::PARAM_INT);
$stmt->bindValue(':l',   $limit,      PDO::PARAM_INT);
$stmt->bindValue(':o',   $offset,     PDO::PARAM_INT);
$stmt->execute();
$list = $stmt->fetchAll();

$total = $pdo->prepare('SELECT COUNT(*) FROM gen_images WHERE user_id = ?');
$total->execute([$user['id']]);
$total = $total->fetchColumn();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'list'  => $list,
    'total' => intval($total),
    'page'  => $page,
    'pages' => ceil($total / $limit),
], JSON_UNESCAPED_UNICODE);
