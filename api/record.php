<?php
/**
 * 记录生成图片 + 检查限制
 * GET  ?action=check  → 检查当前用户是否达到限制
 * POST { filename, prompt, model, aspect, resolution } → 记录
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

// ====== 检查限制 ======
if (($_GET['action'] ?? '') === 'check') {
    $stmt = $pdo->prepare('SELECT daily_limit, total_limit FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $limits = $stmt->fetch();

    $result = ['can_generate' => true];

    if ($limits) {
        // 检查总限制
        if ($limits['total_limit'] !== null) {
            $cnt = $pdo->prepare('SELECT COUNT(*) FROM gen_images WHERE user_id = ?');
            $cnt->execute([$user['id']]);
            $total = $cnt->fetchColumn();
            $result['total_count'] = intval($total);
            $result['total_limit'] = intval($limits['total_limit']);
            if ($total >= $limits['total_limit']) {
                $result['can_generate'] = false;
                $result['reason'] = '已达到总生成上限（' . $limits['total_limit'] . ' 张）';
            }
        }

        // 检查每日限制
        if ($limits['daily_limit'] !== null && $result['can_generate']) {
            $cnt = $pdo->prepare(
                'SELECT COUNT(*) FROM gen_images WHERE user_id = ? AND DATE(created_at) = CURDATE()'
            );
            $cnt->execute([$user['id']]);
            $today = $cnt->fetchColumn();
            $result['today_count'] = intval($today);
            $result['daily_limit'] = intval($limits['daily_limit']);
            if ($today >= $limits['daily_limit']) {
                $result['can_generate'] = false;
                $result['reason'] = '已达到今日生成上限（' . $limits['daily_limit'] . ' 张）';
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 记录生成 ======
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$pdo->prepare(
    'INSERT INTO gen_images (user_id, filename, prompt, model, aspect, resolution)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([
    $user['id'],
    $input['filename']     ?? '',
    $input['prompt']       ?? '',
    $input['model']        ?? '',
    $input['aspect']       ?? '',
    $input['resolution']   ?? '',
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
