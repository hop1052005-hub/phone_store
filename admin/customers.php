<?php
// File: admin/customers.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int)$_POST['user_id'];
    if ($userId === (int)$admin['id']) {
        $_SESSION['flash_err'] = 'Không thể tự khóa tài khoản Admin đang đăng nhập!';
    } else {
        $pdo->prepare("UPDATE users SET status = IF(status='active','locked','active') WHERE id = ? AND role != 'admin'")
            ->execute([$userId]);
        $_SESSION['flash_msg'] = 'Đã cập nhật trạng thái tài khoản khách hàng.';
    }
    header('Location: customers.php');
    exit;
}

// Tuyệt đối không SELECT cột password
$customers = $pdo->query("
    SELECT u.id, u.fullname, u.phone, u.email, u.address, u.role, u.status, u.created_at,
           COUNT(o.id) AS total_orders, COALESCE(SUM(o.total_amount), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.order_status != 'cancelled'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

renderAdminHeader('Quản lý Khách hàng', $admin);
?>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Họ tên</th><th>SĐT & Email</th><th>Địa chỉ</th>
                <th>Số đơn đã mua</th><th>Tổng chi tiêu</th><th>Vai trò</th><th>Trạng thái</th><th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><strong><?= e($c['fullname']) ?></strong></td>
                <td><?= e($c['phone']) ?><br><small><?= e($c['email']) ?></small></td>
                <td><?= e($c['address']) ?></td>
                <td><?= (int)$c['total_orders'] ?> đơn</td>
                <td><?= number_format((float)$c['total_spent'], 0, ',', '.') ?> đ</td>
                <td><span class="badge"><?= e($c['role']) ?></span></td>
                <td><span class="badge"><?= e($c['status']) ?></span></td>
                <td>
                    <?php if ($c['role'] !== 'admin'): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn <?= $c['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>">
                                <?= $c['status'] === 'active' ? 'Khóa TK' : 'Mở khóa' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderAdminFooter(); ?>