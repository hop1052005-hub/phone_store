<?php
// File: admin/orders.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

$search        = trim($_GET['q'] ?? '');
$statusFilter  = $_GET['status'] ?? '';
$paymentFilter = $_GET['payment_method'] ?? '';
$dateFilter    = $_GET['date'] ?? '';

$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter !== '') {
    $sql .= " AND order_status = ?";
    $params[] = $statusFilter;
}
if ($paymentFilter !== '') {
    $sql .= " AND payment_method = ?";
    $params[] = $paymentFilter;
}
if ($dateFilter !== '') {
    $sql .= " AND DATE(created_at) = ?";
    $params[] = $dateFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

function renderOrderStatusBadge(string $status): string {
    $map = [
        'pending'    => ['text' => 'Chờ xác nhận',    'bg' => '#fef3c7', 'color' => '#d97706'],
        'confirmed'  => ['text' => 'Đã xác nhận',     'bg' => '#e0f2fe', 'color' => '#0284c7'],
        'processing' => ['text' => 'Đang chuẩn bị',   'bg' => '#ede9fe', 'color' => '#7c3aed'],
        'shipping'   => ['text' => 'Đang giao hàng',  'bg' => '#ccfbf1', 'color' => '#0d9488'],
        'completed'  => ['text' => 'Hoàn thành',      'bg' => '#dcfce7', 'color' => '#16a34a'],
        'cancelled'  => ['text' => 'Đã hủy',          'bg' => '#fee2e2', 'color' => '#dc2626'],
    ];
    $info = $map[$status] ?? ['text' => $status, 'bg' => '#f1f5f9', 'color' => '#475569'];
    return '<span style="background-color: ' . $info['bg'] . '; color: ' . $info['color'] . '; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">' . e($info['text']) . '</span>';
}

renderAdminHeader('Quản lý Đơn hàng', $admin);
?>

<div class="card">
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
        <input type="text" name="q" placeholder="Mã đơn, Tên hoặc SĐT khách..." value="<?= e($search) ?>">
        <select name="status">
            <option value="">-- Mọi trạng thái --</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Đang chuẩn bị</option>
            <option value="shipping" <?= $statusFilter === 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
        </select>
        <input type="text" name="payment_method" placeholder="PT thanh toán (COD, BANK_QR...)" value="<?= e($paymentFilter) ?>">
        <input type="date" name="date" value="<?= e($dateFilter) ?>">
        <button type="submit" class="btn">Lọc đơn hàng</button>
    </form>

    <div style="margin-bottom: 15px;">
        <button type="button" id="btnPrintBatch" class="btn" style="background: #0284c7; color: #fff; font-weight: 600;">
            🖨️ In các đơn hợp lệ đã chọn (Hàng loạt)
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"></th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>SĐT</th>
                <th>Tổng tiền</th>
                <th>Thanh toán</th>
                <th>Trạng thái đơn</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 20px;">Không tìm thấy đơn hàng nào.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $o): 
                $method = strtoupper(trim($o['payment_method']));
                // Kiểm tra điều kiện được phép in: Không bị hủy VÀ phương thức là COD hoặc có chứa QR / Chuyển khoản (BANK_QR, VNPAY, MOMO, PAYOS,...)
                $isAllowedMethod = (strpos($method, 'COD') !== false || strpos($method, 'QR') !== false || strpos($method, 'BANK') !== false || strpos($method, 'VNPAY') !== false || strpos($method, 'MOMO') !== false || strpos($method, 'PAYOS') !== false);
                $isCancelled = ($o['order_status'] === 'cancelled');
                
                // Chỉ cho phép in nếu không hủy và đúng phương thức hợp lệ
                $canPrint = (!$isCancelled && $isAllowedMethod);
            ?>
                <tr style="<?= !$canPrint ? 'background-color: #fafafa; opacity: 0.8;' : '' ?>">
                    <td style="text-align: center;">
                        <?php if ($canPrint): ?>
                            <input type="checkbox" class="order-checkbox" value="<?= (int)$o['id'] ?>">
                        <?php else: ?>
                            <span style="color: #cbd5e1;" title="Không đủ điều kiện in">✕</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($o['order_code']) ?></strong></td>
                    <td><?= e($o['customer_name']) ?></td>
                    <td><?= e($o['customer_phone']) ?></td>
                    <td style="color:#dc2626;font-weight:bold;"><?= number_format((float)$o['total_amount'], 0, ',', '.') ?> đ</td>
                    <td><?= e($o['payment_method']) ?> (<?= e($o['payment_status']) ?>)</td>
                    <td><?= renderOrderStatusBadge($o['order_status']) ?></td>
                    <td><?= e($o['created_at']) ?></td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn" style="padding: 5px 10px; font-size: 12px;">Xem & Duyệt</a>
                            
                            <?php if ($canPrint): ?>
                                <a href="print-order.php?id=<?= (int)$o['id'] ?>" target="_blank" class="btn" style="background: #0284c7; color: #fff; padding: 5px 10px; font-size: 12px; text-decoration: none;" title="In phiếu giao hàng">
                                    🖨️ In đơn
                                </a>
                            <?php else: ?>
                                <span style="font-size: 11px; color: #dc2626; font-weight: 600; background: #fee2e2; padding: 4px 8px; border-radius: 4px;" title="Chỉ cho phép in đơn COD hoặc QR hợp lệ">Không hỗ trợ in</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('input.order-checkbox');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        const btnPrintBatch = document.getElementById('btnPrintBatch');
        if (btnPrintBatch) {
            btnPrintBatch.addEventListener('click', function() {
                let selectedIds = [];
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        selectedIds.push(cb.value);
                    }
                });

                if (selectedIds.length === 0) {
                    alert('Vui lòng tích chọn ít nhất một đơn hàng hợp lệ cần in!');
                    return;
                }

                window.open('print-order.php?ids=' + selectedIds.join(','), '_blank');
            });
        }
    });
</script>

<?php renderAdminFooter(); ?>