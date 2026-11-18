<?php
require_once __DIR__ . '/config/db.php';
$user = get_logged_in_user();

if (!$user) {
    $_SESSION['flash_message'] = "Vui lòng đăng nhập để xem lịch sử đơn hàng!";
    $_SESSION['flash_type'] = "warning";
    header("Location: login.php");
    exit;
}

// Xử lý Hủy đơn hàng (khi đơn chưa xác nhận)
if (isset($_POST['cancel_order_id'])) {
    $order_id = (int)$_POST['cancel_order_id'];
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid AND order_status = 'pending' FOR UPDATE");
        $stmt->execute([':id' => $order_id, ':uid' => $user['id']]);
        $order = $stmt->fetch();

        if ($order) {
            // Hoàn lại kho số lượng sản phẩm
            $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :oid");
            $stmt_items->execute([':oid' => $order_id]);
            $items = $stmt_items->fetchAll();

            $stmt_restore = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
            foreach ($items as $it) {
                $stmt_restore->execute([':qty' => $it['quantity'], ':pid' => $it['product_id']]);
            }

            // Cập nhật trạng thái đơn thành cancelled
            $stmt_cancel = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = :id");
            $stmt_cancel->execute([':id' => $order_id]);

            $pdo->commit();
            $_SESSION['flash_message'] = "Đã hủy đơn hàng thành công và hoàn trả số lượng vào kho!";
            $_SESSION['flash_type'] = "success";
        } else {
            $pdo->rollBack();
            $_SESSION['flash_message'] = "Đơn hàng không thể hủy do đã qua bước xử lý!";
            $_SESSION['flash_type'] = "danger";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Lỗi khi hủy đơn: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: orders.php");
    exit;
}

// Lấy danh sách đơn hàng của User
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC");
$stmt->execute([':uid' => $user['id']]);
$user_orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h3 class="fw-bold mb-4"><i class="fa-solid fa-box-archive me-2"></i>Lịch sử đơn hàng của bạn</h3>

<?php if (empty($user_orders)): ?>
    <div class="bg-white p-5 text-center rounded shadow-sm border">
        <i class="fa-solid fa-receipt text-muted fs-1 mb-3"></i>
        <h5>Bạn chưa có đơn hàng nào</h5>
        <a href="products.php" class="btn btn-primary-custom mt-2">Mua sắm ngay</a>
    </div>
<?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm border">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_orders as $ord): ?>
                    <tr>
                        <td><strong class="text-danger"><?= htmlspecialchars($ord['order_code']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                        <td class="fw-bold"><?= format_vnd($ord['total_amount']) ?></td>
                        <td><?= htmlspecialchars($ord['payment_method']) ?></td>
                        <td>
                            <?php 
                            $status_map = [
                                'pending' => '<span class="badge bg-warning text-dark">Chờ xác nhận</span>',
                                'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
                                'shipping' => '<span class="badge bg-primary">Đang giao hàng</span>',
                                'completed' => '<span class="badge bg-success">Đã giao thành công</span>',
                                'cancelled' => '<span class="badge bg-secondary">Đã hủy</span>'
                            ];
                            echo $status_map[$ord['order_status']] ?? $ord['order_status'];
                            ?>
                        </td>
                        <td>
                            <?php if ($ord['order_status'] === 'pending'): ?>
                                <form action="orders.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                                    <input type="hidden" name="cancel_order_id" value="<?= $ord['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hủy đơn</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Không thể hủy</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>