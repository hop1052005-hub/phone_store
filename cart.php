<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Thao tác giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Thêm vào giỏ
    if ($action === 'add') {
        $product_id = (int)$_POST['product_id'];
        $quantity = max(1, (int)$_POST['quantity']);

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $stock = isset($prod['stock']) ? (int)$prod['stock'] : 999;
            if ($stock >= $quantity) {
                // Tự động nhận diện cột giá bán thực tế trong CSDL
                $price = (float)($prod['price'] ?? $prod['sale_price'] ?? $prod['price_sale'] ?? $prod['discount_price'] ?? 0);

                if (isset($_SESSION['cart'][$product_id])) {
                    $new_qty = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                    if ($new_qty <= $stock) {
                        $_SESSION['cart'][$product_id]['quantity'] = $new_qty;
                    } else {
                        $_SESSION['flash_message'] = "Số lượng vượt quá tồn kho còn lại!";
                        $_SESSION['flash_type'] = "warning";
                    }
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'id' => $prod['id'],
                        'name' => $prod['name'] ?? $prod['product_name'] ?? 'Sản phẩm',
                        'price' => $price,
                        'image' => $prod['image'] ?? $prod['thumbnail'] ?? '',
                        'quantity' => $quantity,
                        'stock' => $stock
                    ];
                }
                $_SESSION['flash_message'] = "Đã thêm sản phẩm vào giỏ hàng thành công!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Sản phẩm đã vượt quá số lượng tồn kho!";
                $_SESSION['flash_type'] = "danger";
            }
        }
        header("Location: cart.php");
        exit;
    }

    // 2. Cập nhật số lượng
    if ($action === 'update') {
        if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $pid => $qty) {
                $pid = (int)$pid;
                $qty = (int)$qty;
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$pid]);
                } else {
                    if (isset($_SESSION['cart'][$pid])) {
                        $max_stock = $_SESSION['cart'][$pid]['stock'] ?? 999;
                        $_SESSION['cart'][$pid]['quantity'] = min($qty, $max_stock);
                    }
                }
            }
        }
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['coupon']);
        }
        $_SESSION['flash_message'] = "Đã cập nhật giỏ hàng!";
        $_SESSION['flash_type'] = "info";
        header("Location: cart.php");
        exit;
    }

    // 3. Áp dụng mã giảm giá
    if ($action === 'apply_coupon') {
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        
        // Tính tạm tính trước khi check mã
        $subtotal_check = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal_check += $item['price'] * $item['quantity'];
        }

        // Truy vấn linh hoạt mã giảm giá
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            $_SESSION['flash_message'] = "Mã giảm giá không tồn tại!";
            $_SESSION['flash_type'] = "danger";
        } elseif (isset($coupon['status']) && $coupon['status'] == 0) {
            $_SESSION['flash_message'] = "Mã giảm giá đã bị khóa!";
            $_SESSION['flash_type'] = "danger";
        } else {
            // Kiểm tra hạn sử dụng nếu cột tồn tại
            $is_expired = false;
            if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
                $is_expired = true;
            } elseif (!empty($coupon['expiry_date']) && strtotime($coupon['expiry_date']) < time()) {
                $is_expired = true;
            }

            if ($is_expired) {
                $_SESSION['flash_message'] = "Mã giảm giá đã hết hạn sử dụng!";
                $_SESSION['flash_type'] = "danger";
            } else {
                $min_order = (float)($coupon['min_order_amount'] ?? $coupon['min_order_value'] ?? 0);
                if ($subtotal_check < $min_order) {
                    $_SESSION['flash_message'] = "Đơn hàng phải đạt tối thiểu " . format_vnd($min_order) . " để dùng mã này!";
                    $_SESSION['flash_type'] = "warning";
                } else {
                    $discount_type = strtolower($coupon['discount_type'] ?? $coupon['type'] ?? 'percent');
                    $discount_value = (float)($coupon['discount_value'] ?? $coupon['value'] ?? 0);

                    $_SESSION['coupon'] = [
                        'code' => $coupon['code'],
                        'discount_type' => in_array($discount_type, ['percent', '%']) ? 'percent' : 'fixed',
                        'discount_value' => $discount_value,
                        'min_order_amount' => $min_order
                    ];
                    $_SESSION['flash_message'] = "Áp dụng mã giảm giá (" . $coupon['code'] . ") thành công!";
                    $_SESSION['flash_type'] = "success";
                }
            }
        }
        header("Location: cart.php");
        exit;
    }
}

// Xóa 1 sản phẩm khỏi giỏ
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$remove_id]);
    if (empty($_SESSION['cart'])) {
        unset($_SESSION['coupon']);
    }
    $_SESSION['flash_message'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
    $_SESSION['flash_type'] = "info";
    header("Location: cart.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Tính toán tổng tiền & phân bổ giảm giá trực tiếp vào từng sản phẩm
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
if (isset($_SESSION['coupon']) && $subtotal > 0) {
    $cp = $_SESSION['coupon'];
    if ($subtotal >= ($cp['min_order_amount'] ?? 0)) {
        if ($cp['discount_type'] === 'percent') {
            $discount = ($subtotal * $cp['discount_value']) / 100;
        } else {
            $discount = $cp['discount_value'];
        }
        $discount = min($subtotal, $discount);
    } else {
        unset($_SESSION['coupon']);
    }
}
$grand_total = max(0, $subtotal - $discount);
?>

<h3 class="fw-bold mb-4"><i class="fa-solid fa-cart-shopping me-2"></i>Giỏ hàng của bạn</h3>

<?php if (empty($_SESSION['cart'])): ?>
    <div class="bg-white p-5 text-center rounded shadow-sm border">
        <i class="fa-solid fa-basket-shopping text-muted fs-1 mb-3"></i>
        <h5>Giỏ hàng đang trống</h5>
        <p class="text-muted">Hãy chọn sản phẩm bạn yêu thích và thêm vào giỏ ngay nhé!</p>
        <a href="index.php" class="btn btn-primary-custom">Tiếp tục mua sắm</a>
    </div>
<?php else: ?>
    <form action="cart.php" method="POST">
        <input type="hidden" name="action" value="update">
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th style="width: 120px;">Số lượng</th>
                                <th>Thành tiền</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $item): 
                                $line_total = $item['price'] * $item['quantity'];
                                // Tính phần giảm giá phân bổ cho từng sản phẩm
                                $item_discount = ($subtotal > 0 && $discount > 0) ? round(($line_total / $subtotal) * $discount) : 0;
                                $item_final_total = max(0, $line_total - $item_discount);
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" style="width: 50px; height: 50px; object-fit: contain;" class="me-2 rounded border">
                                            <?php endif; ?>
                                            <div>
                                                <a href="product.php?id=<?= $item['id'] ?>" class="text-decoration-none fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></a>
                                                <?php if ($item_discount > 0): ?>
                                                    <div><span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 0.72rem;"><i class="fa-solid fa-tag me-1"></i>Giảm (<?= htmlspecialchars($_SESSION['coupon']['code']) ?>): -<?= format_vnd($item_discount) ?></span></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= format_vnd($item['price']) ?></td>
                                    <td>
                                        <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?? 99 ?>" class="form-control form-control-sm text-center">
                                    </td>
                                    <td>
                                        <?php if ($item_discount > 0): ?>
                                            <del class="text-muted small d-block"><?= format_vnd($line_total) ?></del>
                                            <strong class="text-danger"><?= format_vnd($item_final_total) ?></strong>
                                        <?php else: ?>
                                            <strong class="text-danger"><?= format_vnd($line_total) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="cart.php?remove=<?= $item['id'] ?>" class="text-danger fs-5" onclick="return confirm('Bạn muốn xóa sản phẩm này?')"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua hàng</a>
                    <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-rotate me-1"></i> Cập nhật giỏ hàng</button>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng & Mã giảm giá -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Mã giảm giá</h5>
                        <div class="input-group">
                            <input type="text" name="coupon_code" class="form-control" placeholder="Nhập mã (VD: KHUYENMAI10)" value="<?= htmlspecialchars($_SESSION['coupon']['code'] ?? '') ?>">
                            <button type="submit" name="action" value="apply_coupon" class="btn btn-dark">Áp dụng</button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Tổng đơn hàng</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= format_vnd($subtotal) ?></strong>
                        </div>
                        <?php if ($discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Giảm giá (<?= htmlspecialchars($_SESSION['coupon']['code']) ?>):</span>
                                <strong>-<?= format_vnd($discount) ?></strong>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                            <strong class="fs-4 text-danger"><?= format_vnd($grand_total) ?></strong>
                        </div>
                        <a href="checkout.php" class="btn btn-primary-custom btn-lg w-100">Tiến hành đặt hàng</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>