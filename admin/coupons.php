<?php
// File: admin/coupons.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $code      = strtoupper(trim($_POST['code']));
        $type      = $_POST['discount_type'];
        $val       = (float)$_POST['discount_value'];
        $minOrder  = (float)$_POST['min_order_amount'];
        $limit     = (int)$_POST['usage_limit'];
        $expiresAt = $_POST['expires_at'];

        $stmt = $pdo->prepare("
            INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, usage_limit, expires_at, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE discount_type=VALUES(discount_type), discount_value=VALUES(discount_value),
                                    min_order_amount=VALUES(min_order_amount), usage_limit=VALUES(usage_limit), expires_at=VALUES(expires_at)
        ");
        $stmt->execute([$code, $type, $val, $minOrder, $limit, $expiresAt]);
        $_SESSION['flash_msg'] = 'Đã lưu cấu hình mã giảm giá.';
    }
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE coupons SET status = IF(status='active','paused','active') WHERE id = ?")->execute([$id]);
        $_SESSION['flash_msg'] = 'Đã đổi trạng thái mã giảm giá.';
    }
    header('Location: coupons.php');
    exit;
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
renderAdminHeader('Quản lý Khuyến mãi (Coupons)', $admin);
?>
<div class="card">
    <h3>➕ Tạo / Cập nhật Mã giảm giá</h3>
    <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="text" name="code" required placeholder="Mã (VD: SALE200K)">
        <select name="discount_type">
            <option value="fixed">Giảm tiền mặt (VNĐ)</option>
            <option value="percent">Giảm theo %</option>
        </select>
        <input type="number" name="discount_value" required placeholder="Giá trị giảm">
        <input type="number" name="min_order_amount" required placeholder="Đơn tối thiểu">
        <input type="number" name="usage_limit" required placeholder="Giới hạn lượt dùng" value="100">
        <input type="datetime-local" name="expires_at" required>
        <button type="submit" class="btn">Lưu mã</button>
    </form>

    <table>
        <thead><tr><th>Mã</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th><th>Đã dùng / Giới hạn</th><th>Hạn dùng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $cp): ?>
            <tr>
                <td><strong><?= e($cp['code']) ?></strong></td>
                <td><?= e($cp['discount_type']) ?></td>
                <td><?= number_format((float)$cp['discount_value'], 0, ',', '.') ?></td>
                <td><?= number_format((float)$cp['min_order_amount'], 0, ',', '.') ?> đ</td>
                <td><?= (int)$cp['used_count'] ?> / <?= (int)$cp['usage_limit'] ?></td>
                <td><?= e($cp['expires_at']) ?></td>
                <td><span class="badge"><?= e($cp['status']) ?></span></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $cp['id'] ?>">
                        <button type="submit" class="btn btn-warning"><?= $cp['status'] === 'active' ? 'Tạm dừng' : 'Kích hoạt' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderAdminFooter(); ?>