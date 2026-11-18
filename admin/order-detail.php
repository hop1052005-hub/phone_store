<?php
// File: admin/order-detail.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

$orderId = (int)($_GET['id'] ?? 0);

/**
 * Hàm chuẩn hóa hiển thị nhãn trạng thái đơn hàng bằng tiếng Việt có màu sắc trực quan
 */
function renderOrderStatusBadge(string $status): string {
    $map = [
        'pending'    => ['text' => 'Chờ xác nhận',    'bg' => '#fef3c7', 'color' => '#d97706'], // Vàng cam
        'confirmed'  => ['text' => 'Đã xác nhận',     'bg' => '#e0f2fe', 'color' => '#0284c7'], // Xanh dương nhạt
        'processing' => ['text' => 'Đang chuẩn bị',   'bg' => '#ede9fe', 'color' => '#7c3aed'], // Tím
        'shipping'   => ['text' => 'Đang giao hàng',  'bg' => '#ccfbf1', 'color' => '#0d9488'], // Xanh ngọc
        'completed'  => ['text' => 'Hoàn thành',      'bg' => '#dcfce7', 'color' => '#16a34a'], // Xanh lá
        'cancelled'  => ['text' => 'Đã hủy',          'bg' => '#fee2e2', 'color' => '#dc2626'], // Đỏ
    ];
    $info = $map[$status] ?? ['text' => $status, 'bg' => '#f1f5f9', 'color' => '#475569'];
    return '<span style="background-color: ' . $info['bg'] . '; color: ' . $info['color'] . '; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">' . e($info['text']) . '</span>';
}

// XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG VỚI TRANSACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $newStatus     = $_POST['new_status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';
    $note          = trim($_POST['note'] ?? '');

    $allowedFlow = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping'   => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        $ord = $stmt->fetch();
        if (!$ord) {
            throw new RuntimeException('Đơn hàng không tồn tại.');
        }

        $oldStatus = $ord['order_status'];
        if ($newStatus !== $oldStatus) {
            if (!in_array($newStatus, $allowedFlow[$oldStatus] ?? [], true)) {
                throw new RuntimeException("Không thể chuyển trạng thái từ trạng thái hiện tại sang trạng thái mới này.");
            }

            // Nếu hủy đơn -> Hoàn trả lại tồn kho vào bảng products
            if ($newStatus === 'cancelled') {
                $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmtItems->execute([$orderId]);
                foreach ($stmtItems->fetchAll() as $item) {
                    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                        ->execute([(int)$item['quantity'], (int)$item['product_id']]);
                }
            }

            // Nếu hoàn thành đơn -> Tự động đánh dấu đã thanh toán
            if ($newStatus === 'completed') {
                $paymentStatus = 'paid';
            }

            // Lưu lịch sử trạng thái
            $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, ?, ?)")
                ->execute([$orderId, $oldStatus, $newStatus, $admin['id'], $note ?: 'Admin cập nhật trạng thái']);
        }

        $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?")
            ->execute([$newStatus, $paymentStatus, $orderId]);

        $pdo->commit();
        $_SESSION['flash_msg'] = 'Đã cập nhật trạng thái đơn hàng thành công.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash_err'] = $e->getMessage();
    }
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    exit('Không tìm thấy đơn hàng.');
}

$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

$stmtHist = $pdo->prepare("
    SELECT h.*, u.fullname 
    FROM order_status_history h 
    LEFT JOIN users u ON u.id = h.changed_by 
    WHERE h.order_id = ? ORDER BY h.created_at DESC
");
$stmtHist->execute([$orderId]);
$histories = $stmtHist->fetchAll();

renderAdminHeader('Chi tiết đơn hàng #' . $order['order_code'], $admin);
?>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <div class="card">
        <h3>Thông tin người nhận & Sản phẩm</h3>
        <p style="margin:8px 0;"><strong>Khách hàng:</strong> <?= e($order['customer_name']) ?> - <strong>SĐT:</strong> <?= e($order['customer_phone']) ?></p>
        <p style="margin:8px 0;"><strong>Địa chỉ giao:</strong> <?= e($order['shipping_address']) ?></p>
        <p style="margin:8px 0;"><strong>Ghi chú:</strong> <?= e($order['note'] ?: 'Không có') ?></p>

        <table>
            <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['product_name']) ?></td>
                    <td><?= number_format((float)$it['price'], 0, ',', '.') ?> đ</td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><strong><?= number_format((float)$it['total_price'], 0, ',', '.') ?> đ</strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align:right; margin-top:16px; line-height:1.8;">
            Tạm tính: <?= number_format((float)$order['subtotal'], 0, ',', '.') ?> đ<br>
            Giảm giá: -<?= number_format((float)$order['discount_amount'], 0, ',', '.') ?> đ<br>
            Phí vận chuyển: <?= number_format((float)$order['shipping_fee'], 0, ',', '.') ?> đ<br>
            <strong style="font-size:18px;color:#dc2626;">Tổng cộng: <?= number_format((float)$order['total_amount'], 0, ',', '.') ?> đ</strong>
        </div>
    </div>

    <div class="card">
        <h3>Xử lý trạng thái đơn hàng</h3>
        <form method="POST" style="display:flex; flex-direction:column; gap:12px; margin-top:12px;">
            <?= csrf_field() ?>
            <label>Trạng thái hiện tại: <?= renderOrderStatusBadge($order['order_status']) ?></label>
            
            <label>Chuyển sang trạng thái mới:</label>
            <select name="new_status">
                <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Đang chuẩn bị hàng</option>
                <option value="shipping" <?= $order['order_status'] === 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>Giao thành công (Hoàn thành)</option>
                <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Hủy đơn hàng</option>
            </select>

            <label>Trạng thái thanh toán</label>
            <select name="payment_status">
                <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Chưa thanh toán (pending)</option>
                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán (paid)</option>
                <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Thất bại (failed)</option>
            </select>

            <label>Ghi chú lịch sử / Lý do hủy</label>
            <input type="text" name="note" placeholder="Nhập ghi chú xử lý...">
            <button type="submit" class="btn btn-success">Cập nhật đơn hàng</button>
        </form>

        <h4 style="margin-top:24px;">Lịch sử thay đổi trạng thái</h4>
        <ul style="font-size:13px; margin-top:8px; padding-left:18px; line-height:1.7;">
            <?php foreach ($histories as $h): ?>
                <li style="margin-bottom: 6px;">
                    [<?= e($h['created_at']) ?>]<br>
                    <?= renderOrderStatusBadge($h['old_status']) ?> &rarr; <?= renderOrderStatusBadge($h['new_status']) ?><br>
                    <small style="color: #64748b;">Bởi: <?= e($h['fullname'] ?? 'Admin') ?> | Ghi chú: <?= e($h['note']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php renderAdminFooter(); ?>