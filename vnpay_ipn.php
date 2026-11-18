<?php
// Tắt toàn bộ lỗi HTML để VNPay nhận định dạng JSON thuần
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config/db.php';

$vnp_HashSecret = "XNBXMACXYDEFABGHIJKLMNOPQRSTUVWX"; // Chuỗi bí mật Sandbox

$inputData = array();
$returnData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'];
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
$order_code = $inputData['vnp_TxnRef'];
$vnp_Amount = $inputData['vnp_Amount'] / 100; // Đưa về VNĐ chuẩn

try {
    // 1. Kiểm tra chữ ký
    if ($secureHash == $vnp_SecureHash) {
        // 2. Lấy thông tin đơn hàng từ DB
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = :code");
        $stmt->execute([':code' => $order_code]);
        $order = $stmt->fetch();

        if ($order) {
            // 3. Kiểm tra số tiền
            if ((int)$order['total_amount'] === (int)$vnp_Amount) {
                // 4. Kiểm tra trạng thái đơn hàng (Đảm bảo chưa được cập nhật trước đó)
                if ($order['payment_status'] === 'pending') {
                    
                    if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
                        // Thanh toán thành công
                        $status = 'paid';
                    } else {
                        // Thanh toán thất bại / lỗi
                        $status = 'failed';
                    }
                    
                    // Cập nhật trạng thái thanh toán vào DB
                    $update_stmt = $pdo->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
                    $update_stmt->execute([':status' => $status, ':id' => $order['id']]);

                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success';
                } else {
                    $returnData['RspCode'] = '02';
                    $returnData['Message'] = 'Order already confirmed';
                }
            } else {
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'Invalid amount';
            }
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
    } else {
        $returnData['RspCode'] = '97';
        $returnData['Message'] = 'Invalid signature';
    }
} catch (Exception $e) {
    $returnData['RspCode'] = '99';
    $returnData['Message'] = 'Unknow error';
}

// Trả JSON về cho hệ thống VNPay
header('Content-Type: application/json');
echo json_encode($returnData);
?>