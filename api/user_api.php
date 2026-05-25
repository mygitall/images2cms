<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user) { http_response_code(401); echo json_encode(['error'=>'未登录']); exit; }

$action = $_GET['action'] ?? '';
header('Content-Type: application/json; charset=utf-8');

// 余额 + 统计
if ($action === 'balance') {
    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $balance = floatval($stmt->fetchColumn() ?: 0);

    $total = $pdo->prepare('SELECT COUNT(*) FROM gen_images WHERE user_id = ?');
    $total->execute([$user['id']]);

    $spent = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM balance_logs WHERE user_id = ? AND type='deduct'");
    $spent->execute([$user['id']]);

    echo json_encode([
        'balance' => $balance,
        'total_generated' => intval($total->fetchColumn()),
        'total_spent' => floatval($spent->fetchColumn()),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 余额变动记录
if ($action === 'balance_logs') {
    $stmt = $pdo->prepare('SELECT * FROM balance_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

// 管理员充值
if ($action === 'topup') {
    if ($user['role'] !== 'admin') { echo json_encode(['error'=>'需要管理员权限']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $uid = intval($input['user_id'] ?? 0);
    $amt = floatval($input['amount'] ?? 0);
    if ($uid <= 0 || $amt <= 0) { echo json_encode(['error'=>'参数错误']); exit; }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
        $st->execute([$uid]);
        $oldBal = floatval($st->fetchColumn() ?: 0);
        $newBal = $oldBal + $amt;
        $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?')->execute([$newBal, $uid]);
        $pdo->prepare('INSERT INTO balance_logs (user_id, amount, type, reason, balance_after) VALUES (?,?,?,?,?)')
            ->execute([$uid, $amt, 'topup', '管理员充值', $newBal]);
        $pdo->commit();
        echo json_encode(['ok'=>true, 'balance'=>$newBal]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

// 管理员重置密码
if ($action === 'reset_pw') {
    if ($user['role'] !== 'admin') { echo json_encode(['error'=>'需要管理员权限']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $uid = intval($input['user_id'] ?? 0);
    $pw  = $input['new_password'] ?? '';
    if ($uid <= 0 || strlen($pw) < 4) { echo json_encode(['error'=>'参数错误']); exit; }
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
        ->execute([password_hash($pw, PASSWORD_BCRYPT), $uid]);
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['error' => '未知 action']);
