<?php
// File: admin/index.php
declare(strict_types=1);

// 1. Khai báo session riêng biệt cho khu vực quản trị ngay dòng đầu tiên
session_name('AdminSession');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Nạp file xác thực admin
require_once __DIR__ . '/auth_admin.php';

// 3. Kiểm tra quyền admin độc lập (tự động chuyển hướng về login.php nếu chưa đăng nhập hoặc không phải admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Lấy thông tin admin từ session nếu hàm checkAdminAuth yêu cầu
$admin = [
    'id' => $_SESSION['user_id'] ?? 0,
    'fullname' => $_SESSION['fullname'] ?? 'Quản trị viên',
    'role' => $_SESSION['user_role'] ?? 'admin'
];

$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate   = $_GET['to']   ?? date('Y-m-d');

// 1. Tổng doanh thu theo khoảng thời gian (đơn hàng completed)
$stmtRev = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM orders 
    WHERE order_status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
");
$stmtRev->execute([$fromDate, $toDate]);
$totalRevenue = (float)$stmtRev->fetchColumn();

// 2. Các chỉ số tổng hợp
$totalOrders    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$totalProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status != 'discontinued'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// 3. Sản phẩm sắp hết hàng (stock <= 5)
$lowStockProducts = $pdo->query("
    SELECT sku, name, price_sale, stock 
    FROM products 
    WHERE stock <= 5 AND status = 'active' 
    ORDER BY stock ASC LIMIT 8
")->fetchAll();

// 4. Sản phẩm bán chạy nhất
$bestSellers = $pdo->query("
    SELECT p.sku, p.name, SUM(oi.quantity) AS total_sold, SUM(oi.total_price) AS revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    WHERE o.order_status != 'cancelled'
    GROUP BY p.id, p.sku, p.name
    ORDER BY total_sold DESC LIMIT 5
")->fetchAll();

// 5. Dữ liệu biểu đồ doanh thu theo ngày
$stmtChart = $pdo->prepare("
    SELECT DATE(created_at) AS day, SUM(total_amount) AS rev
    FROM orders
    WHERE order_status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$stmtChart->execute([$fromDate, $toDate]);
$chartData = $stmtChart->fetchAll();

renderAdminHeader('Bảng điều khiển (Dashboard)', $admin);
?>

<div class="card">
    <form method="GET" style="display:flex; gap:12px; align-items:center;">
        <strong>Lọc doanh thu:</strong>
        Từ <input type="date" name="from" value="<?= e($fromDate) ?>">
        Đến <input type="date" name="to" value="<?= e($toDate) ?>">
        <button type="submit" class="btn">Xem chỉ số</button>
    </form>
</div>

<div class="grid-4">
    <div class="kpi">
        <h4>DOANH THU (<?= e($fromDate) ?> -> <?= e($toDate) ?>)</h4>
        <p><?= number_format($totalRevenue, 0, ',', '.') ?> đ</p>
    </div>
    <div class="kpi">
        <h4>TỔNG ĐƠN HÀNG / CHỜ XỬ LÝ</h4>
        <p><?= $totalOrders ?> <span style="font-size:14px;color:#d97706;">(<?= $pendingOrders ?> chờ duyệt)</span></p>
    </div>
    <div class="kpi">
        <h4>TỔNG SỐ SẢN PHẨM</h4>
        <p><?= $totalProducts ?></p>
    </div>
    <div class="kpi">
        <h4>TỔNG SỐ KHÁCH HÀNG</h4>
        <p><?= $totalCustomers ?></p>
    </div>
</div>

<div class="card">
    <h3>Biểu đồ doanh thu theo ngày</h3>
    <canvas id="revChart" height="80"></canvas>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
    <div class="card">
        <h3>⚠️ Sản phẩm sắp hết hàng (Tồn kho từ 5 trở xuống)</h3>
        <table>
            <thead><tr><th>Mã SKU</th><th>Tên sản phẩm</th><th>Giá bán</th><th>Tồn kho</th></tr></thead>
            <tbody>
            <?php foreach ($lowStockProducts as $p): ?>
                <tr>
                    <td><code><?= e($p['sku']) ?></code></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= number_format((float)$p['price_sale'], 0, ',', '.') ?> đ</td>
                    <td><strong style="color:#dc2626;"><?= (int)$p['stock'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>🔥 Sản phẩm bán chạy</h3>
        <table>
            <thead><tr><th>SKU</th><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach ($bestSellers as $b): ?>
                <tr>
                    <td><code><?= e($b['sku']) ?></code></td>
                    <td><?= e($b['name']) ?></td>
                    <td><?= (int)$b['total_sold'] ?></td>
                    <td><?= number_format((float)$b['revenue'], 0, ',', '.') ?> đ</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('revChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($chartData, 'day')) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode(array_map('floatval', array_column($chartData, 'rev'))) ?>,
            backgroundColor: '#2563eb'
        }]
    }
});
</script>

<?php renderAdminFooter(); ?>