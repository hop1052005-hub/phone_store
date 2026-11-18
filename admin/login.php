<?php
// File: admin/login.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    if (!ini_get('session.auto_start')) {
        session_name('AdminSession');
    }
    session_start();
}

require_once __DIR__ . '/auth_admin.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'locked') {
            $error = 'Tài khoản này đã bị khóa.';
        } elseif ($user['role'] !== 'admin') {
            $error = 'Tài khoản khách hàng không có quyền đăng nhập vào khu vực Quản trị!';
        } else {
            // Hủy session cũ và tạo mới hoàn toàn sạch sẽ
            $_SESSION = array();
            session_destroy();
            
            if (!ini_get('session.auto_start')) {
                session_name('AdminSession');
            }
            session_start();
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['fullname']  = $user['fullname'];
            
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Email hoặc mật khẩu không chính xác.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Quản trị - Phone Store</title>
    <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; background:#0f172a; font-family:sans-serif; margin:0; }
        .login-box { background:#fff; padding:32px; border-radius:10px; width:360px; box-shadow:0 10px 25px rgba(0,0,0,0.3); }
        h2 { margin-top:0; color:#0f172a; }
        input { width:100%; padding:10px; margin:8px 0 16px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box; }
        button { width:100%; padding:10px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }
        .err { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:12px; font-size:14px; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>🔐 Đăng nhập Admin</h2>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <label>Email quản trị</label>
        <input type="email" name="email" required placeholder="admin@gmail.com">
        <label>Mật khẩu</label>
        <input type="password" name="password" required placeholder="••••••••">
        <button type="submit">Đăng nhập hệ thống</button>
    </form>
</div>
</body>
</html>