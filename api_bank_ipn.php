<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$content = $data['content'] ?? $data['description'] ?? '';
$amount  = (float)($data['transferAmount'] ?? 0);

$order = null;
if (preg_match('/(ORD\d+)/i', $content, $matches)) {
    $order_code = strtoupper($matches[1]);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
    $stmt->execute([$order_code]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($order && $amount >= (float)$order['total_amount']) {
    $order_id = $order['id'];

    $stmtUp = $pdo->prepare("
        UPDATE orders 
        SET payment_status = 'paid',
            order_status = CASE WHEN order_status = 'pending' THEN 'confirmed' ELSE order_status END
        WHERE id = ?
    ");
    $stmtUp->execute([$order_id]);

    echo json_encode(['success' => true, 'message' => 'Thành công']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Không khớp đơn hàng']);