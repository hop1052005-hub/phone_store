<?php
require_once __DIR__ . '/config/db.php';

$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = :code");
$stmt->execute([':code' => $code]);
$order = $stmt->fetch();

if (!$order || $order['payment_method'] !== 'VISA') {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $stmt_up = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_code = :code");
        $stmt_up->execute([':code' => $code]);
        header("Location: order-success.php?code=" . urlencode($code));
        exit;
    }

    if ($action === 'pay') {
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_holder = trim($_POST['card_holder'] ?? '');
        $expiry = trim($_POST['expiry'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');
        $otp = trim($_POST['otp'] ?? '');

        // Kiểm tra định dạng thẻ Visa/MasterCard và mã OTP 3D-Secure thử nghiệm
        if (strlen($card_number) < 13 || strlen($card_number) > 19 || empty($card_holder) || empty($expiry) || strlen($cvv) !== 3) {
            $error = "Thông tin thẻ không hợp lệ. Vui lòng kiểm tra lại số thẻ, ngày hết hạn và CVV!";
        } elseif ($otp !== '123456') {
            $error = "Mã xác thực 3D-Secure OTP không chính xác (Mã test mặc định: 123456)!";
        } else {
            // Chỉ cập nhật trạng thái đơn hàng, TUYỆT ĐỐI KHÔNG lưu số thẻ và CVV
            $stmt_up = $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed' WHERE order_code = :code");
            $stmt_up->execute([':code' => $code]);
            header("Location: order-success.php?code=" . urlencode($code));
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-brands fa-cc-visa me-2 fs-4"></i>Cổng Thanh Toán Thẻ Quốc Tế</h5>
                <span class="badge bg-light text-dark">3D-Secure Sandbox</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between bg-light p-3 rounded border mb-3">
                    <div>
                        <small class="text-muted d-block">Mã đơn hàng</small>
                        <strong><?= htmlspecialchars($order['order_code']) ?></strong>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Số tiền thanh toán</small>
                        <strong class="text-danger fs-5"><?= format_vnd($order['total_amount']) ?></strong>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="alert alert-warning small py-2 mb-3">
                    <strong>Thẻ Visa dùng để Test:</strong><br>
                    - Số thẻ: <code>4111 1111 1111 1111</code> | Ngày hết hạn: <code>12/28</code> | CVV: <code>123</code> | OTP: <code>123456</code>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="pay">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Số thẻ (Card Number)</label>
                        <input type="text" name="card_number" class="form-control" placeholder="4111 1111 1111 1111" value="4111 1111 1111 1111" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên in trên thẻ (Cardholder Name)</label>
                        <input type="text" name="card_holder" class="form-control text-uppercase" placeholder="NGUYEN VAN A" value="PHAM QUANG HOP" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Ngày hết hạn (MM/YY)</label>
                            <input type="text" name="expiry" class="form-control" placeholder="12/28" value="12/28" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Mã bảo mật (CVV)</label>
                            <input type="password" name="cvv" class="form-control" maxlength="3" placeholder="123" value="123" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">Mã xác thực 3D-Secure OTP</label>
                        <input type="text" name="otp" class="form-control border-primary" placeholder="Nhập 123456" value="123456" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold mb-2">
                        <i class="fa-solid fa-lock me-1"></i> Xác nhận Thanh toán <?= format_vnd($order['total_amount']) ?>
                    </button>
                </form>

                <form method="POST">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-outline-secondary w-100">Hủy giao dịch</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>