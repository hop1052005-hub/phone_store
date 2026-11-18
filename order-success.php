<?php
require_once __DIR__ . '/config/db.php';

$code =$_GET['code'] ?? '';

// Lấy thông tin đơn hàng
$stmt =$pdo->prepare("SELECT * FROM orders WHERE order_code = :code");
$stmt->execute([':code' =>$code]);
$order =$stmt->fetch();

if (!$order) {
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='alert alert-danger text-center mt-5'>Không tìm thấy thông tin đơn hàng!</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 1. XỬ LÝ API AJAX TRẢ VỀ DỮ LIỆU JSON SẠCH (ĐẶT LÊN TRƯỚC CẢ HEADER)
if (isset($_GET['ajax_check'])) {
    // Xóa sạch mọi bộ đệm đầu ra trước đó để đảm bảo không dính HTML thừa
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    
    $isPaid = false;
    if ($order['payment_status'] === 'paid') {$isPaid = true;
    }
    if (in_array($order['order_status'], ['confirmed', 'processing', 'shipping', 'completed'], true)) {$isPaid = true;
    }
    
    if (!$isPaid) {
        try {
            $stmtPay =$pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
            $stmtPay->execute([$order['id']]);
            $payment =$stmtPay->fetch();
            if ($payment) {$payStatus = $payment['status'] ?? $payment['payment_status'] ?? '';
                if (in_array($payStatus, ['paid', 'completed', 'success'], true)) {$isPaid = true;
                }
            }
        } catch (Exception $e) {}
    }

    echo json_encode([
        'payment_status' => $isPaid ? 'paid' : $order['payment_status']
    ]);
    exit;
}

// ==========================================
// CẤU HÌNH TÀI KHOẢN NGÂN HÀNG NHẬN TIỀN
// ==========================================
$my_bank_id = 'MB'; 
$my_account_no = '0867725235';$my_account_name = 'PHAM QUANG HOP'; 

$qr_amount = (int)$order['total_amount'];
$qr_info = urlencode($order['order_code']);
$base_qr_url = "https://img.vietqr.io/image/{$my_bank_id}-{$my_account_no}-compact2.png?amount={$qr_amount}&addInfo={$qr_info}&accountName=" . urlencode($my_account_name);

// Kiểm tra hiển thị màn hình thành công
$showPaid = false;
if ($order['payment_status'] === 'paid' || in_array($order['order_status'], ['confirmed', 'processing', 'shipping', 'completed'], true)) {$showPaid = true;
}

// Kiểm tra hiển thị màn hình QR
$showQr = false;
if ($order['payment_method'] === 'BANK_QR' &&$order['payment_status'] === 'pending' && !in_array($order['order_status'], ['confirmed', 'processing', 'shipping', 'completed'], true)) {$showQr = true;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white p-4 text-center rounded shadow-sm border mx-auto mt-3" style="max-width: 680px;">
    
    <!-- 1. MÀN HÌNH ĐÃ THANH TOÁN THÀNH CÔNG -->
    <div id="paid-section" style="<?= $showPaid ? '' : 'display: none;' ?>">
        <i class="fa-solid fa-circle-check text-success display-1 mb-3"></i>
        <h3 class="fw-bold text-success">Thanh toán & Đặt hàng thành công!</h3>
        <p class="text-muted">Hệ thống đã nhận được khoản thanh toán cho đơn hàng <strong class="text-danger"><?= htmlspecialchars($order['order_code']) ?></strong>.</p>
    </div>

    <!-- 2. MÀN HÌNH THANH TOÁN THẤT BẠI -->
    <?php if ($order['payment_status'] === 'failed'): ?>
        <div id="failed-section">
            <i class="fa-solid fa-circle-xmark text-danger display-1 mb-3"></i>
            <h3 class="fw-bold text-danger">Thanh toán thất bại!</h3>
            <p class="text-muted">Giao dịch thanh toán cho đơn hàng <strong class="text-danger"><?= htmlspecialchars($order['order_code']) ?></strong> không thành công.</p>
            <?php if ($order['payment_method'] === 'VISA'): ?>
                <a href="visa_gateway.php?code=<?= urlencode($order['order_code']) ?>" class="btn btn-warning fw-bold mb-3">Thử thanh toán lại bằng thẻ Visa</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 3. MÀN HÌNH QUÉT QR NGÂN HÀNG -->
    <?php if ($showQr): ?>
        <div id="qr-pending-section">
            <h4 class="fw-bold text-primary mb-2"><i class="fa-solid fa-qrcode me-2"></i>Quét mã QR để chuyển khoản</h4>
            <p class="text-muted small mb-3">Hệ thống sẽ tự động xác nhận ngay sau khi bạn chuyển khoản thành công.</p>

            <div class="alert alert-info border-info text-start">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center mb-3 mb-md-0">
                        <img id="qr-image" src="<?= $base_qr_url ?>" alt="Mã VietQR" 
                             class="img-fluid rounded border shadow-sm p-2 bg-white" style="max-width: 220px;">
                    </div>

                    <div class="col-md-7">
                        <p class="mb-2">Ngân hàng: <strong class="text-uppercase"><?= htmlspecialchars($my_bank_id) ?> Bank</strong></p>
                        <p class="mb-2">Chủ tài khoản: <strong><?= htmlspecialchars($my_account_name) ?></strong></p>
                        <p class="mb-2">Số tài khoản: <strong class="text-primary fs-5"><?= htmlspecialchars($my_account_no) ?></strong></p>
                        <p class="mb-2">Số tiền: <strong class="text-danger fs-5"><?= format_vnd($order['total_amount']) ?></strong></p>
                        <p class="mb-2">Nội dung CK: <strong class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($order['order_code']) ?></strong></p>
                        <hr>
                        <div class="d-flex align-items-center text-primary small mb-2" id="waiting-status">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            <span>Đang chờ tín hiệu từ ngân hàng...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($order['payment_status'] === 'pending' && !$showPaid): ?>
        <!-- 4. MÀN HÌNH ĐẶT HÀNG COD -->
        <i class="fa-solid fa-circle-check text-success display-1 mb-3"></i>
        <h3 class="fw-bold text-success">Đặt hàng thành công!</h3>
        <p class="text-muted">Mã đơn hàng: <strong class="text-danger"><?= htmlspecialchars($order['order_code']) ?></strong> (Thanh toán khi nhận hàng - COD)</p>
    <?php endif; ?>

    <!-- Tóm tắt đơn hàng -->
    <div class="p-3 bg-light rounded text-start my-3 border">
        <p class="mb-1"><strong>Mã đơn hàng:</strong> <span class="text-danger fw-bold"><?= htmlspecialchars($order['order_code']) ?></span></p>
        <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($order['customer_name']) ?> - <?= htmlspecialchars($order['customer_phone']) ?></p>
        <p class="mb-1"><strong>Địa chỉ giao:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
        <p class="mb-1"><strong>Phương thức:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p class="mb-0"><strong>Tổng tiền:</strong> <span class="text-danger fw-bold"><?= format_vnd($order['total_amount']) ?></span></p>
    </div>

    <div class="d-flex justify-content-center gap-2 mt-4">
        <a href="index.php" class="btn btn-primary-custom px-4">Về trang chủ</a>
        <a href="orders.php" class="btn btn-outline-secondary px-4">Lịch sử đơn hàng</a>
    </div>
</div>

<?php if ($showQr): ?>
<script>
const orderCode = <?= json_encode($order['order_code']) ?>;

const pollPayment = setInterval(() => {
    fetch(`order-success.php?code=${orderCode}&ajax_check=1`)
        .then(res => res.json())
        .then(data => {
            if (data.payment_status === 'paid') {
                clearInterval(pollPayment);
                const qrSection = document.getElementById('qr-pending-section');
                if (qrSection) qrSection.style.display = 'none';
                document.getElementById('paid-section').style.display = 'block';
            }
        })
        .catch(err => console.error('Lỗi kiểm tra thanh toán:', err));
}, 2000);
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>