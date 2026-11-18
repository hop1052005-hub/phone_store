<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = :phone");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // KIỂM TRA TRẠNG THÁI: Nếu tài khoản đã bị admin khóa
        if (isset($user['status']) && $user['status'] === 'locked') {
            $_SESSION['flash_message'] = "Tài khoản của bạn đã bị khóa bởi quản trị viên!";
            $_SESSION['flash_type'] = "danger";
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'address' => $user['address'],
                'role' => $user['role'],
                'status' => $user['status']
            ];
            $_SESSION['flash_message'] = "Đăng nhập thành công!";
            $_SESSION['flash_type'] = "success";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['flash_message'] = "Số điện thoại hoặc mật khẩu không chính xác!";
        $_SESSION['flash_type'] = "danger";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="bg-white p-4 rounded shadow-sm border">
            <h4 class="fw-bold mb-4 text-center">Đăng nhập khách hàng</h4>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Nhập số điện thoại" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Đăng nhập</button>
            </form>
            <div class="text-center mt-3">
                <span>Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>