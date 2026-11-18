<?php
// File: admin/reports.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

// XỬ LÝ XUẤT CSV KHI BẤM NÚT EXPORT (Sử dụng dấu ; làm phân cách cột để tương thích tuyệt vời với Excel tiếng Việt)
if (!empty($_GET['export'])) {
    $type = $_GET['export'];
    $filename = "bao_cao_{$type}_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM để Excel đọc không bị lỗi font tiếng Việt

    if ($type === 'revenue') {
        fputcsv($out, ['Ngày', 'Số đơn hoàn thành', 'Tổng doanh thu (VNĐ)'], ';');
        $rows = $pdo->query("SELECT DATE(created_at), COUNT(*), SUM(total_amount) FROM orders WHERE order_status='completed' GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC")->fetchAll(PDO::FETCH_NUM);
        foreach ($rows as $r) {
            fputcsv($out, [$r[0], $r[1], number_format((float)$r[2], 0, '', '')], ';');
        }
    } elseif ($type === 'inventory') {
        fputcsv($out, ['Mã SKU', 'Tên sản phẩm', 'Giá gốc', 'Giá bán', 'Tồn kho', 'Trạng thái'], ';');
        $rows = $pdo->query("SELECT sku, name, price_original, price_sale, stock, status FROM products ORDER BY stock ASC")->fetchAll(PDO::FETCH_NUM);
        foreach ($rows as $r) {
            fputcsv($out, $r, ';');
        }
    }
    fclose($out);
    exit;
}

// 1. Thống kê tổng quan báo cáo
$totalRevAll = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status='completed'")->fetchColumn();
$totalOrdersCompleted = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='completed'")->fetchColumn();
$totalStockCount = (int)$pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status='active'")->fetchColumn();

// 2. Thống kê số lượng đơn hàng theo trạng thái
$statusStats = $pdo->query("SELECT order_status, COUNT(*) AS total, COALESCE(SUM(total_amount),0) AS amount FROM orders GROUP BY order_status")->fetchAll();

// 3. Báo cáo doanh thu theo tháng
$monthlyRev = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS orders_count, SUM(total_amount) AS revenue FROM orders WHERE order_status='completed' GROUP BY month ORDER BY month DESC")->fetchAll();

// 4. Báo cáo sản phẩm tồn kho thấp (Cần nhập thêm)
$lowStockProducts = $pdo->query("SELECT sku, name, price_sale, stock FROM products WHERE stock <= 5 AND status='active' ORDER BY stock ASC LIMIT 10")->fetchAll();

/**
 * Hàm hiển thị nhãn trạng thái tiếng Việt kèm màu sắc
 */
function renderStatusBadge(string $status): string {
    $map = [
        'pending'    => ['text' => 'Chờ xác nhận',    'bg' => '#fef3c7', 'color' => '#d97706'],
        'confirmed'  => ['text' => 'Đã xác nhận',     'bg' => '#e0f2fe', 'color' => '#0284c7'],
        'processing' => ['text' => 'Đang chuẩn bị',   'bg' => '#ede9fe', 'color' => '#7c3aed'],
        'shipping'   => ['text' => 'Đang giao hàng',  'bg' => '#ccfbf1', 'color' => '#0d9488'],
        'completed'  => ['text' => 'Hoàn thành',      'bg' => '#dcfce7', 'color' => '#16a34a'],
        'cancelled'  => ['text' => 'Đã hủy',          'bg' => '#fee2e2', 'color' => '#dc2626'],
    ];
    $info = $map[$status] ?? ['text' => $status, 'bg' => '#f1f5f9', 'color' => '#475569'];
    return '<span style="background-color: ' . $info['bg'] . '; color: ' . $info['color'] . '; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block;">' . e($info['text']) . '</span>';
}

renderAdminHeader('Báo cáo & Thống kê hệ thống', $admin);
?>

<!-- KHU VỰC THẺ TỔNG QUAN (KPI CARDS) -->
<div class="grid-4" style="margin-bottom: 20px;">
    <div class="kpi" style="border-left-color: #16a34a;">
        <h4>TỔNG DOANH THU (HOÀN THÀNH)</h4>
        <p style="color: #16a34a;"><?= number_format($totalRevAll, 0, ',', '.') ?> đ</p>
    </div>
    <div class="kpi" style="border-left-color: #2563eb;">
        <h4>ĐƠN HÀNG THÀNH CÔNG</h4>
        <p><?= number_format($totalOrdersCompleted) ?> đơn</p>
    </div>
    <div class="kpi" style="border-left-color: #0d9488;">
        <h4>TỔNG TỒN KHO</h4>
        <p><?= number_format($totalStockCount) ?> sản phẩm</p>
    </div>
    <div class="kpi" style="border-left-color: #dc2626;">
        <h4>CẢNH BÁO SẮP HẾT HÀNG</h4>
        <p style="color: #dc2626;"><?= $lowStockCount ?> sản phẩm</p>
    </div>
</div>

<!-- KHU VỰC NÚT XUẤT FILE EXCEL / CSV -->
<div class="card" style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px dashed #cbd5e1;">
    <div>
        <h3 style="font-size: 16px; color: #0f172a; margin-bottom: 4px;">📥 Xuất dữ liệu Báo cáo ra tệp CSV (Excel)</h3>
        <p style="font-size: 13px; color: #64748b;">Tải xuống tệp CSV chuẩn định dạng UTF-8 để mở trực tiếp bằng Microsoft Excel hoặc Google Sheets.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="reports.php?export=revenue" class="btn btn-success" style="padding: 10px 16px; font-weight: 600;">📊 Xuất Báo Cáo Doanh Thu</a>
        <a href="reports.php?export=inventory" class="btn" style="background: #0d9488; padding: 10px 16px; font-weight: 600;">📦 Xuất Báo Cáo Tồn Kho</a>
    </div>
</div>

<!-- HAI BẢNG THỐNG KÊ CHI TIẾT -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    
    <!-- Bảng 1: Trạng thái đơn hàng -->
    <div class="card">
        <h3 style="margin-bottom: 12px; font-size: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📊 Thống kê đơn hàng theo trạng thái</h3>
        <table>
            <thead>
                <tr>
                    <th>Trạng thái đơn</th>
                    <th>Số lượng</th>
                    <th style="text-align: right;">Tổng giá trị</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($statusStats as $s): ?>
                <tr>
                    <td><?= renderStatusBadge($s['order_status']) ?></td>
                    <td><strong><?= (int)$s['total'] ?></strong> đơn</td>
                    <td style="text-align: right; color: #dc2626; font-weight: bold;"><?= number_format((float)$s['amount'], 0, ',', '.') ?> đ</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bảng 2: Doanh thu theo tháng -->
    <div class="card">
        <h3 style="margin-bottom: 12px; font-size: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📅 Doanh thu theo tháng (Đơn hoàn thành)</h3>
        <table>
            <thead>
                <tr>
                    <th>Tháng</th>
                    <th>Đơn thành công</th>
                    <th style="text-align: right;">Doanh thu</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($monthlyRev)): ?>
                <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">Chưa có dữ liệu doanh thu theo tháng</td></tr>
            <?php else: ?>
                <?php foreach ($monthlyRev as $m): ?>
                    <tr>
                        <td><strong>Tháng <?= e($m['month']) ?></strong></td>
                        <td><?= (int)$m['orders_count'] ?> đơn</td>
                        <td style="text-align: right; color: #16a34a; font-weight: bold;"><?= number_format((float)$m['revenue'], 0, ',', '.') ?> đ</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- BẢNG CẢNH BÁO TỒN KHO THẤP -->
<div class="card" style="margin-top: 20px;">
    <h3 style="margin-bottom: 12px; font-size: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; color: #dc2626;">⚠️ Danh sách sản phẩm sắp hết hàng cần nhập thêm (Tồn kho &le; 5)</h3>
    <table>
        <thead>
            <tr>
                <th>Mã SKU</th>
                <th>Tên sản phẩm</th>
                <th>Giá bán</th>
                <th>Số lượng tồn kho hiện tại</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lowStockProducts)): ?>
            <tr><td colspan="5" style="text-align: center; color: #16a34a; padding: 20px;">🎉 Tuyệt vời! Hiện không có sản phẩm nào sắp hết hàng.</td></tr>
        <?php else: ?>
            <?php foreach ($lowStockProducts as $p): ?>
                <tr>
                    <td><code><?= e($p['sku']) ?></code></td>
                    <td><strong><?= e($p['name']) ?></strong></td>
                    <td><?= number_format((float)$p['price_sale'], 0, ',', '.') ?> đ</td>
                    <td><span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-weight: bold;"><?= (int)$p['stock'] ?> máy</span></td>
                    <td>
                        <a href="products.php?q=<?= urlencode($p['sku']) ?>" class="btn" style="font-size: 12px; padding: 5px 10px;">Nhập thêm kho</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php renderAdminFooter(); ?>