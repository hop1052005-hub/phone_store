<?php
// File: product-detail.php
require_once __DIR__ . '/config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// XỬ LÝ GỬI ĐÁNH GIÁ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? $_SESSION['id'] ?? 0;
    
    if (empty($userId)) {
        $_SESSION['flash_message'] = 'Vui lòng đăng nhập tài khoản để gửi đánh giá sản phẩm!';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }
        if ($comment === '') {
            $_SESSION['flash_message'] = 'Vui lòng nhập nội dung đánh giá của bạn.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            try {
                $stmtRevIns = $pdo->prepare("
                    INSERT INTO reviews (product_id, user_id, rating, comment, status, created_at)
                    VALUES (?, ?, ?, ?, 'visible', NOW())
                ");
                $stmtRevIns->execute([$id, $userId, $rating, $comment]);
                $_SESSION['flash_message'] = 'Cảm ơn bạn đã gửi đánh giá sản phẩm thành công!';
                $_SESSION['flash_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['flash_message'] = 'Lỗi lưu đánh giá: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
    }
    header("Location: product-detail.php?id=" . $id);
    exit;
}

require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->prepare("SELECT p.*, b.name as brand_name, c.name as category_name 
                       FROM products p 
                       JOIN brands b ON p.brand_id = b.id 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Sản phẩm không tồn tại!</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$specs = json_decode($product['specs'] ?? '{}', true);

// Lấy danh sách đánh giá hiển thị (Bao gồm cả kiểm tra trạng thái active/visible hoặc hiển thị tất cả nếu chưa có cột status)
$reviewsList = [];
try {
    $stmtReviews = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.fullname, u.name, u.username, 'Thành viên') AS reviewer_name 
        FROM reviews r 
        LEFT JOIN users u ON u.id = r.user_id 
        WHERE r.product_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmtReviews->execute([$id]);
    $reviewsList = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmtReviewsAlt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
        $stmtReviewsAlt->execute([$id]);
        $reviewsList = $stmtReviewsAlt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reviewsList as &$rev) {
            $rev['reviewer_name'] = 'Khách hàng';
        }
        unset($rev);
    } catch (Exception $ex) {
        $reviewsList = [];
    }
}

// Tính trung bình số sao
$avgRating = 0;
if (!empty($reviewsList)) {
    $totalStars = array_sum(array_column($reviewsList, 'rating'));
    $avgRating = round($totalStars / count($reviewsList), 1);
}

$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']) || !empty($_SESSION['id']);
?>

<div class="bg-white p-4 rounded shadow-sm border mb-4">
    <div class="row">
        <!-- Ảnh Sản phẩm -->
        <div class="col-md-5 text-center mb-3">
            <img src="<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded border p-3" style="max-height: 400px; object-fit: contain;" alt="<?= htmlspecialchars($product['name']) ?>" referrerpolicy="no-referrer">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-7">
            <h3 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h3>
            <p class="text-muted mb-2">Thương hiệu: <strong class="text-dark"><?= htmlspecialchars($product['brand_name']) ?></strong> | Mã SP: <code><?= htmlspecialchars($product['sku']) ?></code></p>
            
            <?php if (!empty($reviewsList)): ?>
                <div class="mb-2 text-warning">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa-solid fa-star <?= $i <= round($avgRating) ? '' : 'text-muted opacity-25' ?>"></i>
                    <?php endfor; ?>
                    <span class="text-dark fw-bold ms-1"><?= $avgRating ?>/5</span>
                    <span class="text-muted small ms-1">(<?= count($reviewsList) ?> đánh giá)</span>
                </div>
            <?php endif; ?>

            <div class="p-3 bg-light rounded my-3">
                <div class="d-flex align-items-baseline gap-3">
                    <span class="fs-2 fw-bold text-danger"><?= format_vnd($product['price_sale']) ?></span>
                    <?php if ($product['price_original'] > $product['price_sale']): ?>
                        <span class="text-muted text-decoration-line-through fs-5"><?= format_vnd($product['price_original']) ?></span>
                        <span class="badge bg-danger">Tiết kiệm <?= format_vnd($product['price_original'] - $product['price_sale']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tình trạng hàng -->
            <p><strong>Trạng thái:</strong> 
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success fs-6"><i class="fa-solid fa-check-circle me-1"></i>Còn hàng (<?= $product['stock'] ?> sản phẩm)</span>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6"><i class="fa-solid fa-times-circle me-1"></i>Hết hàng</span>
                <?php endif; ?>
            </p>

            <!-- Form Thêm Giỏ Hàng -->
            <?php if ($product['stock'] > 0): ?>
                <form action="cart.php" method="POST" class="mt-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="row align-items-center g-3 mb-3">
                        <div class="col-auto">
                            <label class="fw-bold">Số lượng:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control text-center" style="width: 80px;">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom btn-lg flex-grow-1"><i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ hàng</button>
                    </div>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg w-100 disabled" disabled>Sản phẩm tạm hết hàng</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Thông số kỹ thuật & Mô tả -->
<div class="row">
    <div class="col-md-7 mb-4">
        <div class="bg-white p-4 rounded shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2">Mô tả sản phẩm</h5>
            <div class="lh-lg">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-5 mb-4">
        <div class="bg-white p-4 rounded shadow-sm border mb-4">
            <h5 class="fw-bold border-bottom pb-2">Thông số kỹ thuật</h5>
            <?php if (!empty($specs)): ?>
                <table class="table table-striped table-bordered mb-0">
                    <tbody>
                        <?php foreach ($specs as $key => $val): ?>
                            <tr>
                                <th class="bg-light" style="width: 40%;"><?= htmlspecialchars($key) ?></th>
                                <td><?= htmlspecialchars($val) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Đang cập nhật thông số.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- KHU VỰC ĐÁNH GIÁ VÀ BÌNH LUẬN SẢN PHẨM -->
<div class="bg-white p-4 rounded shadow-sm border mb-5">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-comments me-2 text-danger"></i>Đánh giá & Nhận xét sản phẩm</h4>

    <!-- Danh sách đánh giá -->
    <div class="mb-4">
        <?php if (empty($reviewsList)): ?>
            <p class="text-muted fst-italic">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên đánh giá!</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($reviewsList as $rev): ?>
                    <div class="list-group-item border-0 border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong><i class="fa-solid fa-circle-user text-secondary me-1"></i><?= htmlspecialchars($rev['reviewer_name'] ?? 'Khách hàng') ?></strong>
                            <small class="text-muted"><?= htmlspecialchars($rev['created_at']) ?></small>
                        </div>
                        <div class="text-warning mb-2" style="font-size: 0.85rem;">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fa-solid fa-star <?= $s <= (int)$rev['rating'] ? '' : 'text-muted opacity-25' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form viết đánh giá: Bắt buộc đăng nhập -->
    <div class="card bg-light border-0 p-4 rounded-3">
        <h5 class="fw-bold mb-3">Viết đánh giá của bạn</h5>
        <?php if ($isLoggedIn): ?>
            <form method="POST">
                <input type="hidden" name="action" value="submit_review">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Chất lượng sản phẩm (Số sao):</label>
                    <select name="rating" class="form-select" style="max-width: 220px;">
                        <option value="5">⭐⭐⭐⭐⭐ (5/5 - Tuyệt vời)</option>
                        <option value="4">⭐⭐⭐⭐ (4/5 - Rất tốt)</option>
                        <option value="3">⭐⭐⭐ (3/5 - Bình thường)</option>
                        <option value="2">⭐⭐ (2/5 - Tạm được)</option>
                        <option value="1">⭐ (1/5 - Kém)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nhận xét chi tiết:</label>
                    <textarea name="comment" rows="3" class="form-control" placeholder="Hãy chia sẻ cảm nhận của bạn về sản phẩm này nhé..." required></textarea>
                </div>
                <button type="submit" class="btn btn-danger px-4 fw-bold">Gửi đánh giá</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning mb-0 text-center py-3">
                <i class="fa-solid fa-lock me-2"></i>Bạn cần phải <a href="login.php" class="text-danger fw-bold text-decoration-underline">Đăng nhập tài khoản</a> thì mới có thể viết đánh giá cho sản phẩm này!
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>