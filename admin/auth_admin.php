<?php
// File: admin/auth_admin.php
declare(strict_types=1);

// Đặt tên session riêng cho khu vực admin TRƯỚC KHI gọi session_start()
if (session_status() === PHP_SESSION_NONE) {
    if (!ini_get('session.auto_start')) {
        session_name('AdminSession');
    }
    session_start();
}

// 1. Kết nối tới database phone_store_db của bạn
$host = '127.0.0.1';
$db   = 'phone_store_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Lỗi kết nối cơ sở dữ liệu.');
}

// 2. Hàm chống XSS
function e($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// 3. Hàm CSRF Token bảo vệ các form POST
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Lỗi bảo mật CSRF: Phiên gửi biểu mẫu không hợp lệ.');
    }
}

// 4. Kiểm tra đăng nhập & Phân quyền Admin phía Server
function checkAdminAuth(PDO $pdo): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, fullname, email, phone, role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    // Nếu không tồn tại, bị khóa, hoặc là khách hàng thông thường -> Chặn ngay lập tức
    if (!$currentUser || $currentUser['status'] === 'locked' || $currentUser['role'] !== 'admin') {
        http_response_code(403);
        exit('<div style="font-family:sans-serif;text-align:center;margin-top:80px;">
                <h2 style="color:#dc2626;">403 - Truy cập bị từ chối</h2>
                <p>Khách hàng thông thường không được phép truy cập khu vực Quản trị.</p>
                <a href="../index.php">Quay về Trang chủ mua sắm</a> | <a href="login.php">Đăng nhập Admin</a>
              </div>');
    }
    return $currentUser;
}

// 5. Hàm hiển thị Header + Sidebar Admin gọn đẹp
function renderAdminHeader(string $title, array $admin): void {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title><?= e($title) ?> - Admin Panel</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
            body { display: flex; min-height: 100vh; background: #f1f5f9; color: #1e293b; }
            .sidebar { width: 250px; background: #0f172a; color: #fff; padding: 20px 0; flex-shrink: 0; }
            .sidebar h2 { padding: 0 20px 20px; border-bottom: 1px solid #1e293b; font-size: 18px; color: #38bdf8; }
            .sidebar a { display: block; color: #cbd5e1; text-decoration: none; padding: 12px 20px; font-size: 14px; }
            .sidebar a:hover { background: #1e293b; color: #fff; }
            .main { flex: 1; padding: 24px; overflow-x: auto; }
            .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: #fff; padding: 16px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
            .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; }
            .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
            .kpi { background: #fff; padding: 18px; border-radius: 8px; border-left: 4px solid #2563eb; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
            .kpi h4 { font-size: 13px; color: #64748b; margin-bottom: 8px; }
            .kpi p { font-size: 22px; font-weight: 700; color: #0f172a; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 14px; }
            th, td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
            th { background: #f8fafc; font-weight: 600; }
            input, select, textarea { padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
            .btn { display: inline-block; padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; background: #2563eb; color: #fff; }
            .btn-danger { background: #dc2626; }
            .btn-warning { background: #d97706; }
            .btn-success { background: #16a34a; }
            .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: #e2e8f0; }
            .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; background: #dcfce7; color: #166534; }
            .alert-err { background: #fee2e2; color: #991b1b; }
        </style>
    </head>
    <body>
        <aside class="sidebar">
            <h2>📱 ADMIN PHONE STORE</h2>
            <a href="index.php">📊 Dashboard</a>
            <a href="products.php">📦 Quản lý Sản phẩm</a>
            <a href="categories_brands.php">🏷️ Danh mục & Thương hiệu</a>
            <a href="orders.php">🛒 Quản lý Đơn hàng</a>
            <a href="customers.php">👥 Quản lý Khách hàng</a>
            <a href="coupons.php">🎟️ Quản lý Khuyến mãi</a>
            <a href="reviews.php">⭐ Quản lý Đánh giá</a>
            <a href="reports.php">📈 Báo cáo & Xuất CSV</a>
            <a href="../index.php" style="border-top:1px solid #1e293b;margin-top:10px;">🌐 Xem Web Khách Hàng</a>
            <a href="logout.php" style="color:#f87171;">🚪 Đăng xuất</a>
        </aside>
        <main class="main">
            <div class="topbar">
                <h1><?= e($title) ?></h1>
                <div>Xin chào Admin: <strong><?= e($admin['fullname']) ?></strong></div>
            </div>
            <?php if (!empty($_SESSION['flash_msg'])): ?>
                <div class="alert"><?= e($_SESSION['flash_msg']); unset($_SESSION['flash_msg']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['flash_err'])): ?>
                <div class="alert alert-err"><?= e($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
            <?php endif; ?>
    <?php
}

function renderAdminFooter(): void {
    echo '</main></body></html>';
}