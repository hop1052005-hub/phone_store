<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = trim($_POST['address']);

    if ($password !== $confirm_password) {
        $_SESSION['flash_message'] = "Mật khẩu xác nhận không khớp!";
        $_SESSION['flash_type'] = "danger";
    } else {
        // Kiểm tra SĐT hoặc Email tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone OR email = :email");
        $stmt->execute([':phone' => $phone, ':email' => $email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "Số điện thoại hoặc Email đã được đăng ký!";
            $_SESSION['flash_type'] = "danger";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, phone, email, password, address) VALUES (:fullname, :phone, :email, :pass, :addr)");
            $stmt->execute([
                ':fullname' => $fullname,
                ':phone' => $phone,
                ':email' => $email,
                ':pass' => $hashed_password,
                ':addr' => $address
            ]);

            $_SESSION['flash_message'] = "Đăng ký tài khoản thành công! Vui lòng đăng nhập.";
            $_SESSION['flash_type'] = "success";
            header("Location: login.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="bg-white p-4 rounded shadow-sm border">
            <h4 class="fw-bold mb-4 text-center">Đăng ký tài khoản</h4>
            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Đăng ký ngay</button>
            </form>
            <div class="text-center mt-3">
                <span>Đã có tài khoản? <a href="login.php">Đăng nhập</a></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>