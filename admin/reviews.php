<?php
// File: admin/reviews.php
declare(strict_types=1);
require_once __DIR__ . '/auth_admin.php';
$admin = checkAdminAuth($pdo);

// Xử lý Xóa đánh giá khi Admin bấm nút Xóa
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $reviewId = (int)($_GET['id'] ?? 0);
    if ($reviewId > 0) {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmtDel->execute([$reviewId]);
            $_SESSION['flash_msg'] = 'Đã xóa đánh giá thành công!';
        } catch (Exception $e) {
            $_SESSION['flash_msg'] = 'Không thể xóa đánh giá này.';
        }
    }
    header('Location: reviews.php');
    exit;
}

// Lấy danh sách đánh giá kèm chính xác tên fullname từ bảng users theo đúng cấu trúc SQL của bạn
$reviews = [];
try {
    $stmtReviews = $pdo->query("
        SELECT r.*, p.name AS product_name, u.fullname AS reviewer_name 
        FROM reviews r
        JOIN products p ON p.id = r.product_id
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ");
    $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reviews = [];
}

renderAdminHeader('Quản lý Đánh giá Sản phẩm', $admin);
?>
<div class="card">
    <p style="color:#64748b;margin-bottom:12px;"><em>Quy tắc: Quản trị viên chỉ được phép xóa các đánh giá vi phạm tiêu chuẩn, không được chỉnh sửa nội dung đánh giá của khách hàng.</em></p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Khách hàng</th>
                <th>Số sao</th>
                <th>Nội dung đánh giá</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($reviews)): ?>
            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">Chưa có đánh giá nào từ khách hàng.</td></tr>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e($r['product_name'] ?? '') ?></td>
                    <td><strong style="color: #2563eb;"><?= e($r['reviewer_name'] ?? 'Khách vãng lai') ?></strong></td>
                    <td><?= (int)($r['rating'] ?? 5) ?> ⭐</td>
                    <td><?= e($r['comment'] ?? '') ?></td>
                    <td><span class="badge"><?= e($r['status'] ?? 'visible') ?></span></td>
                    <td>
                        <a href="reviews.php?action=delete&id=<?= (int)$r['id'] ?>" 
                           class="btn btn-danger" 
                           style="padding: 6px 12px; font-size: 12px; font-weight: 600; background: #dc2626; color: #fff; text-decoration: none; border-radius: 6px; display: inline-block;"
                           onclick="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đánh giá này không?');">
                            <i class="fa-solid fa-trash-can me-1"></i> Xóa đánh giá
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php renderAdminFooter(); ?>