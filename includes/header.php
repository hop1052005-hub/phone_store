<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// KIỂM TRA NGẦM: Nếu tài khoản khách đang đăng nhập nhưng bị admin khóa giữa chừng
if (isset($_SESSION['user']['id'])) {
    $stmt_check_status = $pdo->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmt_check_status->execute([$_SESSION['user']['id']]);
    $current_user_status = $stmt_check_status->fetchColumn();

    if ($current_user_status === 'locked' || $current_user_status === false) {
        unset($_SESSION['user']);
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

// Đếm tổng số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['quantity'] ?? 1);
    }
}

// Lấy danh sách danh mục cho thanh menu
$nav_categories = [];
try {
    $stmt_cat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $nav_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    $nav_categories = array_filter($nav_categories, function ($c) {
        return !isset($c['status']) || $c['status'] === 'active';
    });
} catch (Exception $e) {
    $nav_categories = [];
}

// Lấy danh sách các hãng (thương hiệu) điện thoại có trong shop
$nav_brands = [];
try {
    $stmt_brand = $pdo->query("SELECT * FROM brands ORDER BY id ASC");
    $nav_brands = $stmt_brand->fetchAll(PDO::FETCH_ASSOC);
    $nav_brands = array_filter($nav_brands, function ($b) {
        return !isset($b['status']) || $b['status'] === 'active';
    });
} catch (Exception $e) {
    $nav_brands = [
        ['name' => 'Apple'],
        ['name' => 'Samsung'],
        ['name' => 'Xiaomi'],
        ['name' => 'OPPO'],
        ['name' => 'vivo'],
        ['name' => 'realme'],
    ];
}

// Kiểm tra thông tin đăng nhập
$user_logged = $_SESSION['user'] ?? null;
$user_display_name = $user_logged['fullname'] ?? $user_logged['name'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? null;
$is_admin = ($user_logged['role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Store - Hệ Thống Bán Lẻ Điện Thoại Chính Hãng</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-red: #d70018;
            --primary-red-dark: #b30014;
            --dark-nav: #1e2125;
            --bg-light-gray: #f4f6f8;
            --card-radius: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-light-gray);
            color: #212529;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Thanh thông báo chạy trên cùng */
        .top-promo-bar {
            background: linear-gradient(90deg, #111827, #1f2937, #111827);
            color: #f9fafb;
            font-size: 0.82rem;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .top-promo-badge {
            background-color: #fbbf24;
            color: #111827;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.72rem;
            margin-right: 6px;
            text-transform: uppercase;
        }

        /* Header chính */
        .main-header {
            background: linear-gradient(135deg, #d70018 0%, #b80014 100%);
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 4px 20px rgba(215, 0, 24, 0.25);
        }

        .brand-logo {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            color: #fff !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        /* Thanh tìm kiếm */
        .search-wrapper {
            position: relative;
            max-width: 520px;
            width: 100%;
        }
        .search-wrapper .form-control {
            border-radius: 50px;
            padding: 10px 55px 10px 20px;
            border: none;
            font-size: 0.92rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .search-wrapper .form-control:focus {
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.45);
        }
        .search-wrapper .btn-search {
            position: absolute;
            right: 4px;
            top: 4px;
            bottom: 4px;
            border-radius: 50px;
            background: #ffc107;
            color: #111;
            border: none;
            width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .search-wrapper .btn-search:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }

        /* Các nút trên Header */
        .header-action-btn {
            color: #fff;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.88rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .header-action-btn:hover {
            background: rgba(255, 255, 255, 0.24);
            color: #fff;
            transform: translateY(-1px);
        }

        /* Thanh danh mục dưới Header */
        .category-navbar {
            background-color: var(--dark-nav);
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: relative;
            z-index: 1025;
            overflow: visible !important;
        }
        .category-navbar .container,
        .category-navbar .nav,
        .category-navbar .nav-item {
            overflow: visible !important;
        }
        .category-navbar .nav-link {
            color: #e5e7eb !important;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 10px 16px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .category-navbar .nav-link:hover,
        .category-navbar .nav-link.active {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Bảng Menu Hãng Điện Thoại */
        .css-dropdown {
            position: relative;
            display: inline-block;
        }
        .css-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #ffffff;
            width: 320px;
            max-height: none !important;
            overflow: visible !important;
            box-shadow: 0 14px 32px rgba(0,0,0,0.22);
            border-radius: 14px;
            z-index: 9999;
            padding: 14px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .css-dropdown-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .css-dropdown-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }
        .css-dropdown-content a {
            color: #1f2937 !important;
            padding: 9px 12px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            background: #f8fafc !important;
            border: 1px solid #f1f5f9;
            transition: all 0.15s ease;
        }
        .css-dropdown-content .css-dropdown-header a {
            background: transparent !important;
            border: none;
            padding: 4px 6px;
        }
        .css-dropdown-content a:hover {
            background-color: #fef2f2 !important;
            border-color: #fecaca;
            color: #d70018 !important;
            transform: translateY(-1px);
        }
        .css-dropdown:hover .css-dropdown-content,
        .css-dropdown.show .css-dropdown-content {
            display: block;
        }

        /* Thẻ Sản Phẩm */
        .product-card, .card {
            border-radius: var(--card-radius);
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.28s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1) !important;
            border-color: rgba(215, 0, 24, 0.25);
        }
        .product-img-wrap {
            position: relative;
            padding: 20px;
            background: #fff;
            text-align: center;
            overflow: hidden;
            border-top-left-radius: var(--card-radius);
            border-top-right-radius: var(--card-radius);
        }
        .product-img-wrap img {
            height: 200px;
            width: 100%;
            object-fit: contain;
            transition: transform 0.35s ease;
        }
        .product-card:hover .product-img-wrap img {
            transform: scale(1.07);
        }

        .discount-ribbon {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, #d70018, #ff416c);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            box-shadow: 0 3px 8px rgba(215, 0, 24, 0.3);
            z-index: 2;
        }
        .installment-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            z-index: 2;
        }

        .btn-primary-custom, .btn-danger {
            background: linear-gradient(135deg, #d70018 0%, #b80014 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.25s ease;
        }
        .btn-primary-custom:hover, .btn-danger:hover {
            background: linear-gradient(135deg, #e6001a 0%, #990010 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(215, 0, 24, 0.3);
        }
    </style>
</head>
<body>

<!-- 1. Thanh thông báo khuyến mãi trên cùng -->
<div class="top-promo-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="top-promo-badge"><i class="fa-solid fa-bolt me-1"></i>HOT</span>
            <span>Nhập mã <strong>KHUYENMAI10</strong> giảm ngay 10% cho mọi đơn hàng điện thoại & phụ kiện!</span>
        </div>
        <div class="d-flex gap-4 text-white-50">
            <span><i class="fa-solid fa-truck-fast text-warning me-1"></i>Giao nhanh 2h nội thành</span>
            <span><i class="fa-solid fa-shield-halved text-info me-1"></i>Bảo hành chính hãng 12 tháng</span>
            <span><i class="fa-solid fa-phone-volume text-success me-1"></i>Hotline: <strong class="text-white">1800.6868</strong></span>
        </div>
    </div>
</div>

<!-- 2. Header chính -->
<header class="main-header py-3">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <a href="index.php" class="brand-logo">
                <span class="brand-logo-icon"><i class="fa-solid fa-mobile-screen-button"></i></span>
                <span>PHONE STORE</span>
            </a>

            <form action="index.php" method="GET" class="search-wrapper mx-auto">
                <input type="text" name="search" class="form-control" 
                       placeholder="Bạn tìm điện thoại gì? (VD: iPhone 16, Samsung S24...)" 
                       value="<?= htmlspecialchars($_GET['search'] ?? $_GET['q'] ?? '') ?>">
                <button type="submit" class="btn-search" title="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="header-action-btn d-none d-xl-inline-flex" onclick="openPromoModal()">
                    <i class="fa-solid fa-gift text-warning"></i>
                    <span>Săn Voucher</span>
                </button>

                <a href="cart.php" class="header-action-btn position-relative">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Giỏ hàng</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="badge rounded-pill bg-warning text-dark fw-bold ms-1">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ($user_display_name || isset($_SESSION['user_id']) || isset($_SESSION['user'])): ?>
                    <div class="dropdown">
                        <a href="#" class="header-action-btn bg-white text-dark border-0 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user text-danger"></i>
                            <span><?= htmlspecialchars($user_display_name ?? 'Tài khoản') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                            <?php if ($is_admin): ?>
                                <li><a class="dropdown-item fw-semibold text-danger" href="admin/index.php"><i class="fa-solid fa-gauge-high me-2"></i>Quản trị hệ thống</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <?php if (file_exists(__DIR__ . '/../my_orders.php')): ?>
                                <li><a class="dropdown-item" href="my_orders.php"><i class="fa-solid fa-box-open me-2 text-primary"></i>Đơn hàng của tôi</a></li>
                            <?php elseif (file_exists(__DIR__ . '/../orders.php')): ?>
                                <li><a class="dropdown-item" href="orders.php"><i class="fa-solid fa-box-open me-2 text-primary"></i>Đơn hàng của tôi</a></li>
                            <?php endif; ?>
                            <?php if (file_exists(__DIR__ . '/../profile.php')): ?>
                                <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-gear me-2 text-secondary"></i>Thông tin cá nhân</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="header-action-btn bg-white text-dark border-0">
                        <i class="fa-regular fa-user text-danger"></i>
                        <span>Đăng nhập</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- 3. Thanh điều hướng Danh mục sản phẩm -->
<nav class="category-navbar py-1 mb-4">
    <div class="container">
        <ul class="nav align-items-center flex-wrap py-1">
            <li class="nav-item">
                <a class="nav-link <?= empty($_GET['category_id']) && empty($_GET['category']) && empty($_GET['search']) ? 'active fw-bold' : '' ?>" href="index.php">
                    <i class="fa-solid fa-house me-1"></i>TRANG CHỦ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php#all-products">
                    <i class="fa-solid fa-border-all me-1"></i>TẤT CẢ SẢN PHẨM
                </a>
            </li>

            <li class="nav-item">
                <div class="css-dropdown">
                    <a class="nav-link <?= (isset($_GET['category_id']) && (int)$_GET['category_id'] === 1) ? 'active fw-bold text-warning' : '' ?>" 
                       href="index.php?category_id=1" 
                       onclick="this.parentElement.classList.toggle('show'); return false;">
                        <i class="fa-solid fa-mobile-screen me-1"></i>Điện thoại di động <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.65rem;"></i>
                    </a>
                    <div class="css-dropdown-content">
                        <div class="css-dropdown-header">
                            <a href="index.php?category_id=1" class="fw-bold text-danger">
                                <i class="fa-solid fa-mobile-button me-1"></i>Tất cả hãng điện thoại
                            </a>
                        </div>
                        <div class="css-dropdown-grid">
                            <?php foreach ($nav_brands as $brand): 
                                $brandName = $brand['name'];
                                $brandIcon = (strtolower($brandName) === 'apple') ? 'fa-brands fa-apple fs-5 text-dark' : 'fa-solid fa-mobile-screen-button text-danger';
                            ?>
                                <a href="index.php?category_id=1&search=<?= urlencode($brandName) ?>">
                                    <i class="<?= $brandIcon ?>"></i>
                                    <span><?= htmlspecialchars($brandName) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </li>

            <?php if (!empty($nav_categories)): ?>
                <?php foreach ($nav_categories as $cat): ?>
                    <?php if ((int)$cat['id'] === 1) continue; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($_GET['category_id']) && (int)$cat['id'] === (int)$_GET['category_id']) ? 'active fw-bold text-warning' : '' ?>" 
                           href="index.php?category_id=<?= (int)$cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
    document.addEventListener('click', function(event) {
        var dropdowns = document.querySelectorAll('.css-dropdown.show');
        dropdowns.forEach(function(dd) {
            if (!dd.contains(event.target)) {
                dd.classList.remove('show');
            }
        });
    });
</script>

<main class="container mb-5 flex-grow-1">
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'success') ?> alert-dismissible fade show shadow-sm rounded-3 border-0 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>