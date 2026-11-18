<?php
// File: admin/print-order.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

$order_ids_raw = $_GET['ids'] ?? $_GET['id'] ?? '';
if (empty($order_ids_raw)) {
    die("<h3 style='text-align:center; margin-top:50px; color:red;'>Không tìm thấy mã đơn hàng cần in!</h3>");
}

$id_array = array_filter(array_map('intval', explode(',', $order_ids_raw)));
if (empty($id_array)) {
    die("<h3 style='text-align:center; margin-top:50px; color:red;'>Mã đơn hàng không hợp lệ!</h3>");
}

$inClause = implode(',', array_fill(0, count($id_array), '?'));
$stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE id IN ($inClause)");
$stmtOrders->execute($id_array);
$orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    die("<h3 style='text-align:center; margin-top:50px; color:red;'>Không tìm thấy dữ liệu đơn hàng trong hệ thống!</h3>");
}

// BẢO MẬT: Kiểm tra từng đơn hàng, chỉ cho phép in khi thỏa mãn điều kiện
foreach ($orders as $ord) {
    if (isset($ord['order_status']) && $ord['order_status'] === 'cancelled') {
        die("<h3 style='text-align:center; margin-top:50px; color:#dc2626;'>Lỗi: Không thể in phiếu giao hàng cho đơn hàng đã bị hủy (Mã đơn: " . htmlspecialchars($ord['order_code']) . ")!</h3>");
    }

    $method = strtoupper(trim($ord['payment_method'] ?? ''));
    $isValidMethod = (strpos($method, 'COD') !== false || strpos($method, 'QR') !== false || strpos($method, 'BANK') !== false || strpos($method, 'VNPAY') !== false || strpos($method, 'MOMO') !== false || strpos($method, 'PAYOS') !== false);
    
    if (!$isValidMethod) {
        die("<h3 style='text-align:center; margin-top:50px; color:#dc2626;'>Lỗi: Đơn hàng " . htmlspecialchars($ord['order_code']) . " sử dụng phương thức thanh toán không hỗ trợ in hóa đơn giao hàng!</h3>");
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Phiếu Giao Hàng - Phone Store</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif, Arial, sans-serif;
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            box-sizing: border-box;
            page-break-after: always;
        }
        .invoice-box:last-child {
            page-break-after: avoid;
        }
        .header-table, .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .shop-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            color: #d70018;
            margin: 0;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
            margin: 0;
        }
        .info-section {
            margin: 20px 0;
            padding: 12px;
            background: #f9f9f9;
            border: 1px dashed #999;
            border-radius: 6px;
        }
        .details-table th, .details-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        .details-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .text-end {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .invoice-box { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align: center; margin-bottom: 25px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #d70018; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
            🖨️ In lại hóa đơn này
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin-left: 10px;">
            ✖ Đóng cửa sổ
        </button>
    </div>

    <?php foreach ($orders as $ord): 
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$ord['id']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td>
                    <h2 class="shop-title">PHONE STORE</h2>
                    <p style="margin: 4px 0; font-size: 13px;">Hệ Thống Bán Lẻ Điện Thoại Chính Hãng</p>
                    <p style="margin: 4px 0; font-size: 13px;">Hotline: 1800.6868</p>
                </td>
                <td class="text-end">
                    <h2 class="invoice-title">PHIẾU GIAO HÀNG</h2>
                    <p style="margin: 4px 0; font-size: 14px; font-weight: bold;">Mã đơn: <?= htmlspecialchars($ord['order_code']) ?></p>
                    <p style="margin: 4px 0; font-size: 13px; color: #555;">Ngày đặt: <?= htmlspecialchars($ord['created_at']) ?></p>
                </td>
            </tr>
        </table>

        <div class="info-section">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 55%; vertical-align: top;">
                        <p style="margin: 4px 0;"><strong>👤 Người nhận:</strong> <?= htmlspecialchars($ord['customer_name']) ?></p>
                        <p style="margin: 4px 0;"><strong>📞 Điện thoại:</strong> <?= htmlspecialchars($ord['customer_phone']) ?></p>
                        <p style="margin: 4px 0;"><strong>📍 Địa chỉ giao:</strong> <?= htmlspecialchars($ord['shipping_address']) ?></p>
                    </td>
                    <td style="width: 45%; vertical-align: top;">
                        <p style="margin: 4px 0;"><strong>💳 Thanh toán:</strong> <?= htmlspecialchars($ord['payment_method']) ?> 
                            (<?= $ord['payment_status'] === 'paid' ? '<span style="color:green; font-weight:bold;">ĐÃ THANH TOÁN</span>' : '<span style="color:red; font-weight:bold;">CHƯA THANH TOÁN (COD)</span>' ?>)
                        </p>
                        <p style="margin: 4px 0;"><strong>📝 Ghi chú:</strong> <?= !empty($ord['note']) ? htmlspecialchars($ord['note']) : 'Không có' ?></p>
                        <p style="margin: 4px 0;"><strong>🚚 Đơn vị vận chuyển:</strong> Giao Hàng Nhanh (GHN)</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 6%;" class="text-center">STT</th>
                    <th style="width: 54%;">Tên sản phẩm</th>
                    <th style="width: 10%;" class="text-center">SL</th>
                    <th style="width: 15%;" class="text-end">Đơn giá</th>
                    <th style="width: 15%;" class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $stt = 1; foreach ($items as $item): ?>
                <tr>
                    <td class="text-center"><?= $stt++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= (int)$item['quantity'] ?></td>
                    <td class="text-end"><?= number_format((float)$item['price'], 0, ',', '.') ?>đ</td>
                    <td class="text-end"><?= number_format((float)$item['total_price'], 0, ',', '.') ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="text-align: right;">
                    <p style="margin: 4px 0;">Tạm tính: <strong><?= number_format((float)$ord['subtotal'], 0, ',', '.') ?>đ</strong></p>
                    <?php if ((float)($ord['discount_amount'] ?? 0) > 0): ?>
                        <p style="margin: 4px 0; color: #d70018;">Giảm giá Voucher: <strong>-<?= number_format((float)$ord['discount_amount'], 0, ',', '.') ?>đ</strong></p>
                    <?php endif; ?>
                    <p style="margin: 4px 0;">Phí vận chuyển: <strong><?= number_format((float)($ord['shipping_fee'] ?? 0), 0, ',', '.') ?>đ</strong></p>
                    <p style="margin: 6px 0; font-size: 17px; font-weight: bold; color: #d70018;">
                        TỔNG TIỀN THU COD: <?= number_format((float)$ord['total_amount'], 0, ',', '.') ?>đ
                    </p>
                </td>
            </tr>
        </table>

        <div style="margin-top: 40px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 30%;">
                <p style="margin: 0; font-weight: bold;">Người lập đơn</p>
                <p style="margin: 4px 0; font-size: 12px; color: #666;">(Ký, ghi rõ họ tên)</p>
            </div>
            <div style="text-align: center; width: 30%;">
                <p style="margin: 0; font-weight: bold;">Shipper / Giao nhận</p>
                <p style="margin: 4px 0; font-size: 12px; color: #666;">(Ký, ghi rõ họ tên)</p>
            </div>
            <div style="text-align: center; width: 30%;">
                <p style="margin: 0; font-weight: bold;">Khách hàng</p>
                <p style="margin: 4px 0; font-size: 12px; color: #666;">(Ký xác nhận nhận hàng)</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</body>
</html>