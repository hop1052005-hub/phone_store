<?php
// File: admin/products.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

// XỬ LÝ FORM THÊM / SỬA / XÓA SẢN PHẨM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_product') {
            $id            = (int)($_POST['id'] ?? 0);
            $categoryId    = (int)$_POST['category_id'];
            $brandId       = (int)$_POST['brand_id'];
            $sku           = trim($_POST['sku']);
            $name          = trim($_POST['name']);
            $slug          = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . strtolower($sku);
            $priceOriginal = (float)$_POST['price_original'];
            $priceSale     = (float)$_POST['price_sale'];
            $stock         = (int)$_POST['stock'];
            $description   = trim($_POST['description'] ?? '');
            $status        = $_POST['status'] ?? 'active';
            $imageUrl      = trim($_POST['image_url'] ?? '');

            // Kiểm tra dữ liệu trước khi lưu
            if ($name === '' || $sku === '' || $priceOriginal < 0 || $priceSale < 0 || $stock < 0) {
                throw new RuntimeException('Dữ liệu sản phẩm không hợp lệ (Tên, SKU không được trống; Giá và Tồn kho không được âm).');
            }

            // Kiểm tra nếu có upload file ảnh từ máy tính
            if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['image_file']['size'] > 3 * 1024 * 1024) {
                    throw new RuntimeException('Ảnh tải lên không được vượt quá 3MB.');
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($_FILES['image_file']['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    throw new RuntimeException('Chỉ hỗ trợ ảnh JPG, PNG, WEBP.');
                }
                $uploadDir = __DIR__ . '/../assets/images/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFile = 'prod_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newFile);
                $imageUrl = 'assets/images/uploads/' . $newFile;
            }

            // Đóng gói thông số kỹ thuật + màu sắc + dung lượng thành JSON cho cột specs
            $specsArray = [
                'Màn hình'  => trim($_POST['spec_screen'] ?? ''),
                'Chip'      => trim($_POST['spec_chip'] ?? ''),
                'RAM'       => trim($_POST['spec_ram'] ?? ''),
                'Bộ nhớ'    => trim($_POST['spec_storage'] ?? ''),
                'Màu sắc'   => trim($_POST['spec_color'] ?? ''),
                'Pin'       => trim($_POST['spec_battery'] ?? ''),
            ];
            $specsJson = json_encode($specsArray, JSON_UNESCAPED_UNICODE);

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET category_id=?, brand_id=?, sku=?, name=?, price_original=?, price_sale=?, stock=?, image=?, description=?, specs=?, status=?
                    WHERE id=?
                ");
                $stmt->execute([$categoryId, $brandId, $sku, $name, $priceOriginal, $priceSale, $stock, $imageUrl, $description, $specsJson, $status, $id]);
                $_SESSION['flash_msg'] = 'Đã cập nhật sản phẩm thành công.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (category_id, brand_id, sku, name, slug, price_original, price_sale, stock, image, description, specs, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$categoryId, $brandId, $sku, $name, $slug, $priceOriginal, $priceSale, $stock, $imageUrl, $description, $specsJson, $status]);
                $_SESSION['flash_msg'] = 'Đã thêm sản phẩm mới thành công.';
            }
        }

        if ($action === 'delete_product') {
            $id = (int)$_POST['product_id'];
            // Kiểm tra sản phẩm đã có trong đơn hàng chưa
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmtCheck->execute([$id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                // Không xóa -> Chuyển sang ngừng kinh doanh
                $pdo->prepare("UPDATE products SET status = 'discontinued' WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = 'Sản phẩm đang được tham chiếu trong đơn hàng -> Đã chuyển sang trạng thái Ngừng kinh doanh!';
            } else {
                $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = 'Đã xóa sản phẩm chưa có giao dịch.';
            }
        }
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }
    header('Location: products.php');
    exit;
}

// TÌM KIẾM VÀ LỌC SẢN PHẨM
$keyword = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$brandFilter = (int)($_GET['brand'] ?? 0);

$sql = "SELECT p.*, c.name AS cat_name, b.name AS brand_name 
        FROM products p
        JOIN categories c ON c.id = p.category_id
        JOIN brands b ON b.id = p.brand_id
        WHERE 1=1";
$params = [];
if ($keyword !== '') {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}
if ($catFilter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catFilter;
}
if ($brandFilter > 0) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $brandFilter;
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$brands     = $pdo->query("SELECT * FROM brands")->fetchAll();

// Nếu bấm Sửa
$editProd = null;
$editSpecs = [];
if (!empty($_GET['edit'])) {
    $stmtE = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmtE->execute([(int)$_GET['edit']]);
    $editProd = $stmtE->fetch();
    if ($editProd && !empty($editProd['specs'])) {
        $editSpecs = json_decode($editProd['specs'], true) ?: [];
    }
}

renderAdminHeader('Quản lý Sản phẩm', $admin);
?>

<!-- FORM THÊM / SỬA SẢN PHẨM -->
<div class="card">
    <h3><?= $editProd ? '✏️ Chỉnh sửa sản phẩm #' . (int)$editProd['id'] : '➕ Thêm sản phẩm mới' ?></h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-top:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_product">
        <input type="hidden" name="id" value="<?= (int)($editProd['id'] ?? 0) ?>">

        <div>
            <label>Tên sản phẩm *</label><br>
            <input type="text" name="name" required style="width:100%" value="<?= e($editProd['name'] ?? '') ?>">
        </div>
        <div>
            <label>Mã SKU *</label><br>
            <input type="text" name="sku" required style="width:100%" value="<?= e($editProd['sku'] ?? '') ?>">
        </div>
        <div>
            <label>Danh mục & Thương hiệu *</label><br>
            <div style="display:flex; gap:6px;">
                <select name="category_id" style="width:50%">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editProd['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="brand_id" style="width:50%">
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($editProd['brand_id'] ?? 0) == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label>Giá gốc (VNĐ) *</label><br>
            <input type="number" name="price_original" required style="width:100%" value="<?= e($editProd['price_original'] ?? 0) ?>">
        </div>
        <div>
            <label>Giá khuyến mãi (VNĐ) *</label><br>
            <input type="number" name="price_sale" required style="width:100%" value="<?= e($editProd['price_sale'] ?? 0) ?>">
        </div>
        <div>
            <label>Tồn kho & Trạng thái *</label><br>
            <div style="display:flex; gap:6px;">
                <input type="number" name="stock" required style="width:50%" value="<?= e($editProd['stock'] ?? 10) ?>">
                <select name="status" style="width:50%">
                    <option value="active" <?= ($editProd['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang bán</option>
                    <option value="hidden" <?= ($editProd['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Tạm ẩn</option>
                    <option value="discontinued" <?= ($editProd['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Ngừng KD</option>
                </select>
            </div>
        </div>

        <!-- Thông số kỹ thuật, dung lượng, màu sắc -->
        <div><label>Màn hình</label><input type="text" name="spec_screen" style="width:100%" value="<?= e($editSpecs['Màn hình'] ?? '') ?>"></div>
        <div><label>Chip xử lý</label><input type="text" name="spec_chip" style="width:100%" value="<?= e($editSpecs['Chip'] ?? '') ?>"></div>
        <div><label>RAM & Bộ nhớ (Dung lượng)</label>
            <div style="display:flex;gap:6px;">
                <input type="text" name="spec_ram" placeholder="RAM (VD: 8 GB)" style="width:50%" value="<?= e($editSpecs['RAM'] ?? '') ?>">
                <input type="text" name="spec_storage" placeholder="Dung lượng (256 GB)" style="width:50%" value="<?= e($editSpecs['Bộ nhớ'] ?? '') ?>">
            </div>
        </div>
        <div><label>Màu sắc & Pin</label>
            <div style="display:flex;gap:6px;">
                <input type="text" name="spec_color" placeholder="Màu sắc" style="width:50%" value="<?= e($editSpecs['Màu sắc'] ?? '') ?>">
                <input type="text" name="spec_battery" placeholder="Pin (5000 mAh)" style="width:50%" value="<?= e($editSpecs['Pin'] ?? '') ?>">
            </div>
        </div>

        <div>
            <label>Link ảnh URL (hoặc tải file bên cạnh)</label><br>
            <input type="text" name="image_url" style="width:100%" value="<?= e($editProd['image'] ?? '') ?>">
        </div>
        <div>
            <label>Hoặc Upload ảnh mới từ máy</label><br>
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" style="width:100%">
        </div>

        <div style="grid-column: span 3;">
            <label>Mô tả sản phẩm</label><br>
            <textarea name="description" rows="2" style="width:100%"><?= e($editProd['description'] ?? '') ?></textarea>
        </div>

        <div style="grid-column: span 3;">
            <button type="submit" class="btn"><?= $editProd ? 'Lưu cập nhật' : 'Thêm sản phẩm' ?></button>
            <?php if ($editProd): ?><a href="products.php" class="btn btn-warning">Hủy sửa</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- BỘ LỌC & DANH SÁCH SẢN PHẨM -->
<div class="card">
    <form method="GET" style="display:flex; gap:10px; margin-bottom:12px;">
        <input type="text" name="q" placeholder="Tìm tên hoặc mã SKU..." value="<?= e($keyword) ?>">
        <select name="cat">
            <option value="0">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="brand">
            <option value="0">-- Tất cả thương hiệu --</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $brandFilter == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Lọc / Tìm kiếm</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Ảnh</th><th>SKU</th><th>Tên sản phẩm</th><th>Danh mục / Hãng</th>
                <th>Giá gốc</th><th>Giá KM</th><th>Tồn kho</th><th>Trạng thái</th><th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><img src="<?= e($p['image']) ?>" alt="" style="width:45px;height:45px;object-fit:cover;border-radius:4px;"></td>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><strong><?= e($p['name']) ?></strong></td>
                <td><?= e($p['cat_name']) ?> / <?= e($p['brand_name']) ?></td>
                <td><del><?= number_format((float)$p['price_original'], 0, ',', '.') ?>đ</del></td>
                <td style="color:#dc2626;font-weight:bold;"><?= number_format((float)$p['price_sale'], 0, ',', '.') ?>đ</td>
                <td><?= (int)$p['stock'] ?></td>
                <td><span class="badge"><?= e($p['status']) ?></span></td>
                <td>
                    <a href="products.php?edit=<?= $p['id'] ?>" class="btn">Sửa</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa hoặc chuyển ngừng kinh doanh sản phẩm này?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger">Xóa/Ngừng KD</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php renderAdminFooter(); ?>