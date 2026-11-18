<?php
// File: check_payment.php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    echo json_encode(['status' => 'error']);
    exit;
}

// 1. Kiểm tra trạng thái trong bảng orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'not_found']);
    exit;
}

$isPaid = false;

// Kiểm tra nếu cột payment_status (nếu có) đã chuyển sang 'paid' hoặc 'completed'
if (isset($order['payment_status']) && in_array($order['payment_status'], ['paid', 'completed', 'success'], true)) {
    $isPaid = true;
}

// Kiểm tra nếu order_status đã được duyệt/xác nhận hoặc hoàn thành
if (isset($order['order_status']) && in_array($order['order_status'], ['confirmed', 'processing', 'shipping', 'completed'], true)) {
    $isPaid = true;
}

// 2. Kiểm tra thêm trong bảng payments
if (!$isPaid) {
    try {
        $stmtPay = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $stmtPay->execute([$order_id]);
        $payment = $stmtPay->fetch(PDO::FETCH_ASSOC);
        if ($payment) {
            $payState = $payment['status'] ?? $payment['payment_status'] ?? '';
            if (in_array($payState, ['paid', 'completed', 'success'], true)) {
                $isPaid = true;
            }
        }
    } catch (Exception $e) {
        // Bỏ qua nếu cấu trúc cột bảng payments khác
    }
}

echo json_encode([
    'status' => $isPaid ? 'paid' : 'pending'
]);