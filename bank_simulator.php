<?php
require_once __DIR__ . '/config/db.php';

// Lấy danh sách các đơn hàng QR đang chờ thanh toán
$stmt = $pdo->query("SELECT * FROM orders WHERE payment_method = 'BANK_QR' AND payment_status = 'pending' ORDER BY id DESC");
$pending_orders = $stmt->fetchAll();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_code = trim($_POST['order_code']);
    $amount = (int)$_POST['amount'];

    // Giả lập bắn gói tin JSON giống hệt SePay vào file webhook_bank.php
    $payload = json_encode([
        'gateway' => 'MBBank',
        'transferAmount' => $amount,
        'content' => 'CHUYEN TIEN DON HANG ' . $order_code
    ]);

    $ch = curl_init("http://localhost/phone_store/webhook_bank.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res = curl_exec($ch);
    curl_close($ch);

    $msg = "Đã bắn tín hiệu thành công cho đơn hàng <strong>$order_code</strong>!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Bank Sandbox - App Ngân Hàng Ảo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white p-3">
    <div class="container" style="max-width: 450px;">
        <div class="card bg-primary text-white shadow border-0 rounded-4 p-3 mb-3">
            <h5 class="fw-bold mb-1">🏦 MB BANK SANDBOX (APP ẢO)</h5>
            <small>Số dư khả dụng: <strong>999.999.999đ (Tiền test)</strong></small>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>

        <div class="card text-dark rounded-4 p-3 shadow">
            <h6 class="fw-bold mb-3">Danh sách mã QR đang chờ quét:</h6>
            <?php if (empty($pending_orders)): ?>
                <p class="text-muted small">Chưa có đơn hàng QR nào đang chờ thanh toán. Hãy tạo đơn mới trên website trước.</p>
            <?php else: ?>
                <?php foreach ($pending_orders as $ord): ?>
                    <form method="POST" class="border rounded p-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>Mã đơn:</strong>
                            <span class="badge bg-warning text-dark"><?= htmlspecialchars($ord['order_code']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Số tiền:</strong>
                            <span class="text-danger fw-bold"><?= format_vnd($ord['total_amount']) ?></span>
                        </div>
                        <input type="hidden" name="order_code" value="<?= htmlspecialchars($ord['order_code']) ?>">
                        <input type="hidden" name="amount" value="<?= (int)$ord['total_amount'] ?>">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            💸 Xác nhận Chuyển khoản (Test)
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="bank_simulator.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">🔄 Làm mới danh sách</a>
        </div>
    </div>
</body>
</html>