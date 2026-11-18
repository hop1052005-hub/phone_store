<?php
require_once __DIR__ . '/config/db.php';

$vnp_HashSecret = "XNBXMACXYDEFABGHIJKLMNOPQRSTUVWX"; // Phải giống HashSecret ở checkout.php
$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$order_code = $_GET['vnp_TxnRef'];

if ($secureHash == $vnp_SecureHash) {
    if ($_GET['vnp_ResponseCode'] == '00') {
        // Giao dịch thành công -> Cập nhật trạng thái thanh toán
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_code = :code");
        $stmt->execute([':code' => $order_code]);
        
        $_SESSION['flash_message'] = "Thanh toán VNPay thành công!";
        $_SESSION['flash_type'] = "success";
    } else {
        // Giao dịch thất bại / Bị hủy
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_code = :code");
        $stmt->execute([':code' => $order_code]);
        
        $_SESSION['flash_message'] = "Thanh toán VNPay bị hủy hoặc thất bại!";
        $_SESSION['flash_type'] = "danger";
    }
} else {
    $_SESSION['flash_message'] = "Chữ ký VNPay không hợp lệ (Bảo mật)!";
    $_SESSION['flash_type'] = "danger";
}

// Chuyển hướng về trang chi tiết đơn thành công
header("Location: order-success.php?code=" . $order_code);
exit;