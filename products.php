<?php
require_once __DIR__ . '/includes/header.php';

// Tiếp nhận các thông số lọc từ URL (GET)
$query = trim($_GET['q'] ?? '');
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$price_range = $_GET['price_range'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Xây dựng câu truy vấn SQL động an toàn với PDO
$sql = "SELECT p.*, b.name as brand_name FROM products p JOIN brands b ON p.brand_id = b.id WHERE 1=1";
$params = [];

if ($query !== '') {
    $sql .= " AND (p.name LIKE :q OR p.description LIKE :q)";
    $params[':q'] = "%$query%";
}

if ($brand_id > 0) {
    $sql .= " AND p.brand_id = :brand_id";
    $params[':brand_id'] = $brand_id;
}

// Lọc theo khoảng giá
switch ($price_range) {
    case 'under-2m':
        $sql .= " AND p.price_sale < 2000000";
        break;
    case '2m-4m':
        $sql .= " AND p.price_sale BETWEEN 2000000 AND 4000000";
        break;
    case '4m-7m':
        $sql .= " AND p.price_sale BETWEEN 4000000 AND 7000000";
        break;
    case '7m-13m':
        $sql .= " AND p.price_sale BETWEEN 7000000 AND 13000000";
        break;
    case '13m-20m':
        $sql .= " AND p.price_sale BETWEEN 13000000 AND 20000000";
        break;
    case 'above-20m':
        $sql .= " AND p.price_sale > 20000000";
        break;
}

// Sắp xếp
switch ($sort) {
    case 'price-asc':
        $sql .= " ORDER BY p.price_sale ASC";
        break;
    case 'price-desc':
        $sql .= " ORDER BY p.price_sale DESC";
        break;
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productsList = $stmt->fetchAll();
?>

<div class="row">
    <!-- Bộ Lọc Bên Trai (Sidebar Filter) -->
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fa-solid fa-filter me-2"></i>Bộ lọc sản phẩm
            </div>
            <div class="card-body">
                <form action="products.php" method="GET">
                    <?php if ($query): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                    <?php endif; ?>

                    <!-- Lọc thương hiệu -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thương hiệu</label>
                        <select name="brand" class="form-select">
                            <option value="0">-- Tất cả hãng --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Lọc theo giá -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mức giá</label>
                        <select name="price_range" class="form-select">
                            <option value="">-- Tất cả giá --</option>
                            <option value="under-2m" <?= $price_range == 'under-2m' ? 'selected' : '' ?>>Dưới 2 triệu</option>
                            <option value="2m-4m" <?= $price_range == '2m-4m' ? 'selected' : '' ?>>2 - 4 triệu</option>
                            <option value="4m-7m" <?= $price_range == '4m-7m' ? 'selected' : '' ?>>4 - 7 triệu</option>
                            <option value="7m-13m" <?= $price_range == '7m-13m' ? 'selected' : '' ?>>7 - 13 triệu</option>
                            <option value="13m-20m" <?= $price_range == '13m-20m' ? 'selected' : '' ?>>13 - 20 triệu</option>
                            <option value="above-20m" <?= $price_range == 'above-20m' ? 'selected' : '' ?>>Trên 20 triệu</option>
                        </select>
                    </div>

                    <!-- Sắp xếp -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sắp xếp theo</label>
                        <select name="sort" class="form-select">
                            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price-asc" <?= $sort == 'price-asc' ? 'selected' : '' ?>>Giá từ thấp đến cao</option>
                            <option value="price-desc" <?= $sort == 'price-desc' ? 'selected' : '' ?>>Giá từ cao đến thấp</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100"><i class="fa-solid fa-check me-1"></i> Áp dụng bộ lọc</button>
                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Xóa bộ lọc</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách Sản phẩm bên phải -->
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm border">
            <div>Tìm thấy <strong class="text-danger"><?= count($productsList) ?></strong> sản phẩm</div>
        </div>

        <?php if (empty($productsList)): ?>
            <div class="alert alert-warning text-center py-5">
                <i class="fa-solid fa-triangle-exclamation fs-1 mb-3"></i>
                <h5>Không tìm thấy sản phẩm phù hợp</h5>
                <p>Vui lòng thử lại với các tiêu chí hoặc từ khóa lọc khác.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                <?php foreach ($productsList as $p): 
                    $discount_pct = round((($p['price_original'] - $p['price_sale']) / $p['price_original']) * 100);
                ?>
                    <div class="col">
                        <div class="card h-100 product-card position-relative bg-white">
                            <?php if ($discount_pct > 0): ?>
                                <span class="badge-discount">Giảm <?= $discount_pct ?>%</span>
                            <?php endif; ?>
                            <a href="product-detail.php?id=<?= $p['id'] ?>">
                                <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title fw-bold text-dark text-truncate">
                                    <a href="product-detail.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($p['name']) ?></a>
                                </h6>
                                <div class="mt-auto">
                                    <div class="text-danger fw-bold fs-5"><?= format_vnd($p['price_sale']) ?></div>
                                    <?php if ($p['price_original'] > $p['price_sale']): ?>
                                        <small class="text-muted text-decoration-line-through"><?= format_vnd($p['price_original']) ?></small>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary-custom w-100">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>