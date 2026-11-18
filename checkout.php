<?php
require_once __DIR__ . '/config/db.php';

// 1. CHẶN KHÁCH CHƯA ĐĂNG NHẬP
$user = get_logged_in_user();
if (!$user) {
    $_SESSION['flash_message'] = "Bạn cần đăng nhập hoặc đăng ký tài khoản để tiến hành đặt hàng!";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Tính tổng tiền
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
if (isset($_SESSION['coupon'])) {
    $cp = $_SESSION['coupon'];
    if ($subtotal >= $cp['min_order_amount']) {
        if ($cp['discount_type'] === 'percent') {
            $discount = ($subtotal * $cp['discount_value']) / 100;
        } else {
            $discount = $cp['discount_value'];
        }
    }
}
$grand_total = max(0, $subtotal - $discount);

// Xử lý tạo đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $note = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';

    // Chỉ cho phép đúng 3 phương thức thanh toán
    if (!in_array($payment_method, ['COD', 'BANK_QR', 'VISA'])) {
        $_SESSION['flash_message'] = "Phương thức thanh toán không hợp lệ!";
        $_SESSION['flash_type'] = "danger";
    } elseif (empty($customer_name) || empty($customer_phone) || empty($shipping_address)) {
        $_SESSION['flash_message'] = "Vui lòng điền đầy đủ thông tin nhận hàng!";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Kiểm tra tồn kho
            foreach ($_SESSION['cart'] as $item) {
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id FOR UPDATE");
                $stmt->execute([':id' => $item['id']]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock < $item['quantity']) {
                    throw new Exception("Sản phẩm '" . $item['name'] . "' không đủ số lượng trong kho!");
                }
            }

            // Tạo mã đơn hàng duy nhất
            $order_code = 'ORD' . date('YmdHis') . rand(10, 99);

            // Lưu bảng orders
            $stmt = $pdo->prepare("INSERT INTO orders (order_code, user_id, customer_name, customer_phone, shipping_address, note, subtotal, discount_amount, total_amount, payment_method) 
                                   VALUES (:code, :uid, :name, :phone, :addr, :note, :subtotal, :discount, :total, :pay_method)");
            $stmt->execute([
                ':code' => $order_code,
                ':uid' => $user['id'],
                ':name' => $customer_name,
                ':phone' => $customer_phone,
                ':addr' => $shipping_address,
                ':note' => $note,
                ':subtotal' => $subtotal,
                ':discount' => $discount,
                ':total' => $grand_total,
                ':pay_method' => $payment_method
            ]);
            $order_id = $pdo->lastInsertId();

            // Lưu chi tiết đơn hàng & Trừ tồn kho
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total_price) 
                                        VALUES (:oid, :pid, :pname, :price, :qty, :total)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

            foreach ($_SESSION['cart'] as $item) {
                $stmt_item->execute([
                    ':oid' => $order_id,
                    ':pid' => $item['id'],
                    ':pname' => $item['name'],
                    ':price' => $item['price'],
                    ':qty' => $item['quantity'],
                    ':total' => $item['price'] * $item['quantity']
                ]);

                $stmt_stock->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['id']
                ]);
            }

            $pdo->commit();

            // Xóa giỏ hàng & Mã giảm giá
            unset($_SESSION['cart'], $_SESSION['coupon']);

            // 1. NẾU CHỌN THẺ VISA -> CHUYỂN SANG CỔNG THANH TOÁN VISA
            if ($payment_method === 'VISA') {
                header("Location: visa_gateway.php?code=" . $order_code);
                exit;
            }

            // 2. NẾU CHỌN QR NGÂN HÀNG -> KHỞI TẠO BỘ ĐẾM 180 GIÂY
            if ($payment_method === 'BANK_QR') {
                $_SESSION['qr_expires'][$order_code] = time() + 180;
                header("Location: order-success.php?code=" . $order_code);
                exit;
            }

            // 3. NẾU CHỌN COD -> CHUYỂN TỚI TRANG THÀNH CÔNG
            header("Location: order-success.php?code=" . $order_code);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_message'] = "Lỗi đặt hàng: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h3 class="fw-bold mb-4"><i class="fa-solid fa-credit-card me-2"></i>Thanh toán đơn hàng</h3>

<form action="checkout.php" method="POST">
    <div class="row">
        <!-- Thông tin giao hàng -->
        <div class="col-md-7 mb-4">
            <div class="bg-white p-4 rounded shadow-sm border">
                <h5 class="fw-bold mb-3">Thông tin người nhận</h5>
                <div class="mb-3">
                    <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                    <input type="tel" name="customer_phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
                    <textarea name="shipping_address" class="form-control" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú đơn hàng (không bắt buộc)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="VD: Giao hàng vào giờ hành chính"></textarea>
                </div>

                <h5 class="fw-bold mt-4 mb-3">Phương thức thanh toán</h5>
                
                <div class="form-check p-3 border rounded mb-3">
                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_cod" value="COD" checked>
                    <label class="form-check-label fw-bold" for="pay_cod">
                        <i class="fa-solid fa-truck-fast text-secondary me-2"></i>Thanh toán khi nhận hàng (COD)
                    </label>
                </div>
                
                <div class="form-check p-3 border rounded mb-3">
                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_qr" value="BANK_QR">
                    <label class="form-check-label fw-bold text-primary" for="pay_qr">
                        <i class="fa-solid fa-qrcode me-2"></i>Chuyển khoản Ngân hàng (Quét mã QR)
                    </label>
                </div>
                
                <div class="form-check p-3 border rounded">
                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_visa" value="VISA">
                    <label class="form-check-label fw-bold text-dark" for="pay_visa">
                        <i class="fa-brands fa-cc-visa text-primary fs-5 me-2"></i>Thanh toán bằng thẻ quốc tế Visa / MasterCard
                    </label>
                </div>

            </div>
        </div>

        <!-- Tóm tắt đơn hàng -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold">
                    Sản phẩm đơn hàng
                </div>
                <div class="card-body">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <strong class="d-block text-truncate" style="max-width: 200px;"><?= htmlspecialchars($item['name']) ?></strong>
                                <small class="text-muted">SL: <?= $item['quantity'] ?> x <?= format_vnd($item['price']) ?></small>
                            </div>
                            <span class="fw-bold"><?= format_vnd($item['price'] * $item['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between mt-3">
                        <span>Tạm tính:</span>
                        <span><?= format_vnd($subtotal) ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between text-success">
                            <span>Mã giảm giá:</span>
                            <span>-<?= format_vnd($discount) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                        <strong class="fs-4 text-danger"><?= format_vnd($grand_total) ?></strong>
                    </div>

                    <button type="submit" class="btn btn-primary-custom btn-lg w-100">Xác nhận đặt hàng</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>