<?php
// File: admin/categories_brands.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type   = $_POST['type'] ?? ''; // 'category' hoặc 'brand'
    $action = $_POST['action'] ?? '';
    $table  = ($type === 'brand') ? 'brands' : 'categories';
    $fkCol  = ($type === 'brand') ? 'brand_id' : 'category_id';

    try {
        if ($action === 'save') {
            $id     = (int)($_POST['id'] ?? 0);
            $name   = trim($_POST['name']);
            $slug   = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $status = $_POST['status'] ?? 'active';

            if ($id > 0) {
                $pdo->prepare("UPDATE {$table} SET name=?, slug=?, status=? WHERE id=?")->execute([$name, $slug, $status, $id]);
            } else {
                $pdo->prepare("INSERT INTO {$table} (name, slug, status) VALUES (?, ?, ?)")->execute([$name, $slug, $status]);
            }
            $_SESSION['flash_msg'] = 'Đã lưu thông tin thành công.';
        }

        if ($action === 'toggle_or_delete') {
            $id = (int)$_POST['id'];
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE {$fkCol} = ?");
            $stmtCheck->execute([$id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $pdo->prepare("UPDATE {$table} SET status = IF(status='active','hidden','active') WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = 'Mục này đang có sản phẩm tham chiếu -> Đã chuyển sang chế độ Ẩn/Hiện an toàn.';
            } else {
                $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = 'Đã xóa mục chưa có sản phẩm.';
            }
        }
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }
    header('Location: categories_brands.php');
    exit;
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS total_prod FROM categories c")->fetchAll();
$brands     = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS total_prod FROM brands b")->fetchAll();

renderAdminHeader('Quản lý Danh mục & Thương hiệu', $admin);
?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
    <!-- QUẢN LÝ DANH MỤC -->
    <div class="card">
        <h3>📁 Danh mục sản phẩm</h3>
        <form method="POST" style="display:flex; gap:8px; margin:12px 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="category">
            <input type="hidden" name="action" value="save">
            <input type="text" name="name" required placeholder="Tên danh mục mới...">
            <select name="status"><option value="active">Hiện</option><option value="hidden">Ẩn</option></select>
            <button type="submit" class="btn">Thêm</button>
        </form>
        <table>
            <thead><tr><th>ID</th><th>Tên danh mục</th><th>Số SP</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= (int)$c['total_prod'] ?></td>
                    <td><span class="badge"><?= e($c['status']) ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="category">
                            <input type="hidden" name="action" value="toggle_or_delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-warning"><?= $c['total_prod'] > 0 ? 'Ẩn/Hiện' : 'Xóa' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- QUẢN LÝ THƯƠNG HIỆU -->
    <div class="card">
        <h3>🏷️ Thương hiệu (Hãng)</h3>
        <form method="POST" style="display:flex; gap:8px; margin:12px 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="brand">
            <input type="hidden" name="action" value="save">
            <input type="text" name="name" required placeholder="Tên thương hiệu mới...">
            <select name="status"><option value="active">Hiện</option><option value="hidden">Ẩn</option></select>
            <button type="submit" class="btn">Thêm</button>
        </form>
        <table>
            <thead><tr><th>ID</th><th>Thương hiệu</th><th>Số SP</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
            <tbody>
            <?php foreach ($brands as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= e($b['name']) ?></td>
                    <td><?= (int)$b['total_prod'] ?></td>
                    <td><span class="badge"><?= e($b['status']) ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="brand">
                            <input type="hidden" name="action" value="toggle_or_delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-warning"><?= $b['total_prod'] > 0 ? 'Ẩn/Hiện' : 'Xóa' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminFooter(); ?>