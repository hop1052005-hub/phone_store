<?php
require_once __DIR__ . '/config/db.php';

// Nhận tham số tìm kiếm, lọc danh mục, hãng, khoảng giá & sắp xếp
$search = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$price_range = $_GET['price_range'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Xây dựng câu lệnh SQL
$sql = "SELECT p.*, b.name AS brand_name, c.name AS category_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

// Tách riêng tên tham số :search_name và :search_brand để tránh lỗi PDO
if ($search !== '') {
    $sql .= " AND (p.name LIKE :search_name OR b.name LIKE :search_brand)";
    $kw = '%' . str_replace('+', ' ', $search) . '%';
    $params[':search_name'] = $kw;
    $params[':search_brand'] = $kw;
}

if ($category_id > 0) {
    $sql .= " AND p.category_id = :cat_id";
    $params[':cat_id'] = $category_id;
}

if ($brand_id > 0) {
    $sql .= " AND p.brand_id = :brand_id";
    $params[':brand_id'] = $brand_id;
}

// Bộ lọc theo khoảng giá
switch ($price_range) {
    case 'under_2m':
        $sql .= " AND p.price_sale < 2000000";
        break;
    case '2m_5m':
        $sql .= " AND p.price_sale BETWEEN 2000000 AND 5000000";
        break;
    case '5m_10m':
        $sql .= " AND p.price_sale BETWEEN 5000000 AND 10000000";
        break;
    case '10m_20m':
        $sql .= " AND p.price_sale BETWEEN 10000000 AND 20000000";
        break;
    case 'above_20m':
        $sql .= " AND p.price_sale > 20000000";
        break;
}

// Sắp xếp
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price_sale ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price_sale DESC";
        break;
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $fallbackStmt = $pdo->prepare("SELECT p.*, b.name AS brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.name LIKE ? ORDER BY id DESC");
    $fallbackStmt->execute(['%' . $search . '%']);
    $products = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .hero-carousel .carousel-item {
        height: 360px;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
    }
    .hero-carousel .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        filter: brightness(0.68);
    }
    .hero-caption {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px 68px;
        color: #fff;
        background: linear-gradient(90deg, rgba(0,0,0,0.82) 0%, rgba(0,0,0,0.35) 65%, transparent 100%);
    }
    /* Thu gọn nút chuyển slide 2 bên để không đè lên chữ */
    .hero-carousel .carousel-control-prev,
    .hero-carousel .carousel-control-next {
        width: 42px;
        height: 42px;
        background: rgba(0, 0, 0, 0.45);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.85;
    }
    .hero-carousel .carousel-control-prev {
        left: 12px;
    }
    .hero-carousel .carousel-control-next {
        right: 12px;
    }
    .hero-carousel .carousel-control-prev:hover,
    .hero-carousel .carousel-control-next:hover {
        background: #d70018;
        opacity: 1;
    }
    .side-promo-card {
        height: 172px;
        border-radius: 18px;
        overflow: hidden;
        position: relative;
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 22px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        transition: transform 0.25s ease;
    }
    .side-promo-card:hover {
        transform: translateY(-4px);
    }
    .flash-sale-banner {
        background: linear-gradient(90deg, #d70018 0%, #ff416c 50%, #d70018 100%);
        border-radius: 16px;
        padding: 16px 24px;
        color: #fff;
        box-shadow: 0 10px 25px rgba(215, 0, 24, 0.2);
    }
    .timer-box {
        background: #111827;
        color: #fde047;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 8px;
        min-width: 40px;
        text-align: center;
        display: inline-block;
    }
    .policy-box {
        background: #fff;
        border-radius: 16px;
        padding: 18px;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.25s ease;
    }
    .policy-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.06);
    }
    .policy-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(215, 0, 24, 0.08);
        color: #d70018;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .filter-toolbar {
        background: #ffffff;
        border-radius: 14px;
        padding: 12px 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        border: 1px solid rgba(0,0,0,0.05);
    }
</style>

<!-- 1. CỤM HERO BANNER (6 SLIDE HÌNH NGANG CHUẨN QUẢNG CÁO) -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div id="mainHeroCarousel" class="carousel slide hero-carousel shadow-sm" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="3"></button>
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="4"></button>
                <button type="button" data-bs-target="#mainHeroCarousel" data-bs-slide-to="5"></button>
            </div>

            <div class="carousel-inner rounded-4">
                <!-- Slide 1: iPhone (Hình ngang) -->
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=1200&h=500&q=80" alt="iPhone Pro Max">
                    <div class="hero-caption">
                        <span class="badge bg-warning text-dark fw-bold mb-2" style="width: fit-content;">SIÊU PHẨM MỚI NHẤT</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">iPhone Series Chính Hãng VN/A</h2>
                        <p class="mb-3 text-white-50">Giảm thẳng đến 4.5 Triệu • Thu cũ đổi mới trợ giá cao nhất</p>
                        <div>
                            <a href="index.php?category_id=1&brand_id=1#all-products" class="btn btn-danger px-4 py-2 rounded-pill fw-bold">Mua ngay <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Samsung (Hình ngang) -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=1200&h=500&q=80" alt="Samsung Galaxy Ultra">
                    <div class="hero-caption">
                        <span class="badge bg-info text-dark fw-bold mb-2" style="width: fit-content;">QUYỀN NĂNG GALAXY AI</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">Samsung Galaxy Ultra 5G</h2>
                        <p class="mb-3 text-white-50">Tặng tai nghe Galaxy Buds • Trả góp 0% lãi suất duyệt nhanh</p>
                        <div>
                            <a href="index.php?category_id=1&brand_id=2#all-products" class="btn btn-warning text-dark px-4 py-2 rounded-pill fw-bold">Khám phá ngay <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Xiaomi (Hình ngang cụm Camera Flagship) -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1585060544812-6b45742d762f?auto=format&fit=crop&w=1200&h=500&q=80" alt="Xiaomi 14 Ultra">
                    <div class="hero-caption">
                        <span class="badge bg-danger text-white fw-bold mb-2" style="width: fit-content;">TUYỆT TÁC NHIẾP ẢNH LEICA</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">Xiaomi 14 Ultra 5G Flagship</h2>
                        <p class="mb-3 text-white-50">Chip Snapdragon 8 Gen 3 cực mạnh • Sạc siêu tốc HyperCharge 120W</p>
                        <div>
                            <a href="index.php?category_id=1&brand_id=3#all-products" class="btn btn-light text-dark px-4 py-2 rounded-pill fw-bold">Săn deal ngay <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Slide 4: OPPO (Hình ngang bộ đôi Smartphone hiện đại) -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1556656793-08538906a9f8?auto=format&fit=crop&w=1200&h=500&q=80" alt="OPPO Find X8 & Reno12">
                    <div class="hero-caption">
                        <span class="badge bg-success text-white fw-bold mb-2" style="width: fit-content;">CHUYÊN GIA CHÂN DUNG AI</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">OPPO Find X8 & Reno12 5G</h2>
                        <p class="mb-3 text-white-50">Camera Hasselblad xóa vật thể AI • Pin Silicon-Carbon siêu bền</p>
                        <div>
                            <a href="index.php?category_id=1&brand_id=4#all-products" class="btn btn-danger px-4 py-2 rounded-pill fw-bold">Trải nghiệm ngay <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Slide 5: vivo & realme (Hình ngang trải nghiệm Gaming/Màn hình ngang) -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=1200&h=500&q=80" alt="vivo X100 Pro & realme GT 6">
                    <div class="hero-caption">
                        <span class="badge bg-warning text-dark fw-bold mb-2" style="width: fit-content;">CHIẾN THẦN HIỆU NĂNG</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">vivo X100 Pro & realme GT 6</h2>
                        <p class="mb-3 text-white-50">Màn hình 144Hz siêu sáng 6000 nits • Bảo hành VIP 1 đổi 1 trong 30 ngày</p>
                        <div>
                            <a href="index.php?category_id=1&brand_id=5#all-products" class="btn btn-warning text-dark px-4 py-2 rounded-pill fw-bold">Xem chi tiết <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Slide 6: Tai nghe & Âm thanh (Hình ngang chuẩn Studio) -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&h=500&q=80" alt="Tai nghe Chính Hãng">
                    <div class="hero-caption">
                        <span class="badge bg-primary text-white fw-bold mb-2" style="width: fit-content;">ĐẠI TIỆC ÂM THANH</span>
                        <h2 class="fw-extrabold display-6 mb-2" style="font-weight: 800;">AirPods 4 & Galaxy Buds3 Pro</h2>
                        <p class="mb-3 text-white-50">Chống ồn chủ động ANC đỉnh cao • Giảm thêm 20% khi mua kèm điện thoại</p>
                        <div>
                            <a href="index.php?category_id=2#all-products" class="btn btn-danger px-4 py-2 rounded-pill fw-bold">Mua phụ kiện <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#mainHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" style="width: 1.25rem; height: 1.25rem;"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" style="width: 1.25rem; height: 1.25rem;"></span>
            </button>
        </div>
    </div>

    <div class="col-lg-4 d-flex flex-column justify-content-between gap-3">
        <div class="side-promo-card" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);">
            <span class="badge bg-warning text-dark mb-2" style="width: fit-content;">ƯU ĐÃI SINH VIÊN</span>
            <h5 class="fw-bold mb-1">Giảm thêm 500.000đ</h5>
            <p class="small text-white-50 mb-2">Cho học sinh - sinh viên khi mua điện thoại & phụ kiện</p>
            <a href="javascript:void(0)" onclick="openPromoModal()" class="text-warning small fw-bold text-decoration-none">
                Lấy mã giảm giá ngay <i class="fa-solid fa-chevron-right ms-1"></i>
            </a>
        </div>

        <div class="side-promo-card" style="background: linear-gradient(135deg, #064e3b 0%, #047857 60%, #10b981 100%);">
            <span class="badge bg-light text-dark mb-2" style="width: fit-content;">THANH TOÁN VIETQR</span>
            <h5 class="fw-bold mb-1">Quét QR - Duyệt Tự Động</h5>
            <p class="small text-white-50 mb-2">Xác nhận đơn hàng tức thì chỉ trong 3 giây qua SePay</p>
            <a href="#all-products" class="text-warning small fw-bold text-decoration-none">
                Trải nghiệm ngay <i class="fa-solid fa-chevron-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- 2. BỐN THẺ CAM KẾT DỊCH VỤ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="policy-box d-flex align-items-center gap-3 h-100">
            <div class="policy-icon flex-shrink-0"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h6 class="fw-bold mb-0" style="font-size: 0.92rem;">Hàng chính hãng 100%</h6>
                <small class="text-muted">Bảo hành 12 tháng</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="policy-box d-flex align-items-center gap-3 h-100">
            <div class="policy-icon flex-shrink-0"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
                <h6 class="fw-bold mb-0" style="font-size: 0.92rem;">Giao hàng siêu tốc</h6>
                <small class="text-muted">Miễn phí toàn quốc</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="policy-box d-flex align-items-center gap-3 h-100">
            <div class="policy-icon flex-shrink-0"><i class="fa-solid fa-arrows-rotate"></i></div>
            <div>
                <h6 class="fw-bold mb-0" style="font-size: 0.92rem;">1 đổi 1 trong 30 ngày</h6>
                <small class="text-muted">Nếu có lỗi phần cứng</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="policy-box d-flex align-items-center gap-3 h-100">
            <div class="policy-icon flex-shrink-0"><i class="fa-solid fa-headset"></i></div>
            <div>
                <h6 class="fw-bold mb-0" style="font-size: 0.92rem;">Hỗ trợ 24/7</h6>
                <small class="text-muted">Tư vấn tận tâm</small>
            </div>
        </div>
    </div>
</div>

<!-- 3. FLASH SALE -->
<div class="flash-sale-banner d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-bolt-lightning fs-3 text-warning"></i>
        <div>
            <h5 class="fw-extrabold mb-0" style="font-weight: 800;">FLASH SALE GIÁ SỐC HÔM NAY</h5>
            <small class="text-white-50">Săn ngay điện thoại chính hãng giá tốt nhất thị trường</small>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="small fw-semibold me-1">Kết thúc sau:</span>
        <span class="timer-box" id="fs-hours">04</span> :
        <span class="timer-box" id="fs-mins">45</span> :
        <span class="timer-box" id="fs-secs">59</span>
    </div>
</div>

<!-- 4. THANH TIÊU ĐỀ, LỌC KHOẢNG GIÁ & SẮP XẾP -->
<div id="all-products" class="filter-toolbar mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h4 class="fw-extrabold text-danger mb-0" style="font-weight: 800; font-size: 1.25rem;">
            <i class="fa-solid fa-fire me-2"></i>
            <?= $search !== '' ? 'TÌM KIẾM: "' . htmlspecialchars($search) . '"' : 'SẢN PHẨM NỔI BẬT' ?>
        </h4>

        <form action="index.php" method="GET" class="d-flex flex-wrap align-items-center gap-2 ms-auto">
            <?php if ($search !== ''): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <?php if ($category_id > 0): ?>
                <input type="hidden" name="category_id" value="<?= $category_id ?>">
            <?php endif; ?>
            <?php if ($brand_id > 0): ?>
                <input type="hidden" name="brand_id" value="<?= $brand_id ?>">
            <?php endif; ?>

            <!-- Lọc theo khoảng giá -->
            <select name="price_range" class="form-select form-select-sm rounded-pill px-3" style="width: 160px;" onchange="this.form.submit()">
                <option value="">-- Mức giá --</option>
                <option value="under_2m" <?= $price_range === 'under_2m' ? 'selected' : '' ?>>Dưới 2 triệu</option>
                <option value="2m_5m" <?= $price_range === '2m_5m' ? 'selected' : '' ?>>2 - 5 triệu</option>
                <option value="5m_10m" <?= $price_range === '5m_10m' ? 'selected' : '' ?>>5 - 10 triệu</option>
                <option value="10m_20m" <?= $price_range === '10m_20m' ? 'selected' : '' ?>>10 - 20 triệu</option>
                <option value="above_20m" <?= $price_range === 'above_20m' ? 'selected' : '' ?>>Trên 20 triệu</option>
            </select>

            <!-- Sắp xếp -->
            <div class="d-flex align-items-center gap-1 bg-light p-1 rounded-pill border">
                <a href="index.php?sort=newest<?= $search ? '&search='.urlencode($search) : '' ?><?= $category_id ? '&category_id='.$category_id : '' ?><?= $brand_id ? '&brand_id='.$brand_id : '' ?><?= $price_range ? '&price_range='.$price_range : '' ?>" 
                   class="btn btn-sm <?= $sort === 'newest' ? 'btn-dark' : 'btn-light' ?> rounded-pill px-3 py-1" style="font-size: 0.82rem;">Mới nhất</a>
                <a href="index.php?sort=price_asc<?= $search ? '&search='.urlencode($search) : '' ?><?= $category_id ? '&category_id='.$category_id : '' ?><?= $brand_id ? '&brand_id='.$brand_id : '' ?><?= $price_range ? '&price_range='.$price_range : '' ?>" 
                   class="btn btn-sm <?= $sort === 'price_asc' ? 'btn-dark' : 'btn-light' ?> rounded-pill px-3 py-1" style="font-size: 0.82rem;">Giá thấp - cao</a>
                <a href="index.php?sort=price_desc<?= $search ? '&search='.urlencode($search) : '' ?><?= $category_id ? '&category_id='.$category_id : '' ?><?= $brand_id ? '&brand_id='.$brand_id : '' ?><?= $price_range ? '&price_range='.$price_range : '' ?>" 
                   class="btn btn-sm <?= $sort === 'price_desc' ? 'btn-dark' : 'btn-light' ?> rounded-pill px-3 py-1" style="font-size: 0.82rem;">Giá cao - thấp</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="bg-white p-5 rounded-4 text-center shadow-sm border">
        <i class="fa-solid fa-box-open display-3 text-muted mb-3"></i>
        <h5>Không tìm thấy sản phẩm phù hợp!</h5>
        <a href="index.php" class="btn btn-danger rounded-pill px-4 mt-2">Xem tất cả sản phẩm</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($products as$p): 
            $price_sale = (float)$p['price_sale'];
            $price_original = (float)$p['price_original'];
            $discount_pct = ($price_original > $price_sale &&$price_original > 0) 
                ? round((($price_original - $price_sale) /$price_original) * 100) 
                : 0;

            $detail_link = 'product-detail.php?id=' . (int)$p['id'];
            
            $desc_text =$p['description'] ?? '';
            $chip_info = (stripos($desc_text, 'Snapdragon') !== false) ? 'Snapdragon 5G' : ((stripos($desc_text, 'Apple') !== false || stripos($desc_text, 'iPhone') !== false) ? 'Apple A-Series Bionic' : 'Octa-core High Performance');
            $ram_info = (stripos($desc_text, '8GB') !== false) ? '8GB / 128GB' : ((stripos($desc_text, '12GB') !== false) ? '12GB / 256GB' : '6GB / 128GB');$battery_info = '5000 mAh, Sạc nhanh 45W';
        ?>
            <div class="col">
                <div class="card product-card h-100 shadow-sm bg-white position-relative">
                    <div class="product-img-wrap">
                        <?php if ($discount_pct > 0): ?>
                            <span class="discount-ribbon">Giảm <?= $discount_pct ?>%</span>
                        <?php endif; ?>
                        <span class="installment-badge">Trả góp 0%</span>
                        <a href="<?= $detail_link ?>">
                            <img src="<?= htmlspecialchars($p['image']) ?>" 
                                 alt="<?= htmlspecialchars($p['name']) ?>" 
                                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=500&q=80';">
                        </a>
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <?php if (!empty($p['brand_name'])): ?>
                            <small class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.72rem;">
                                <?= htmlspecialchars($p['brand_name']) ?>
                            </small>
                        <?php endif; ?>

                        <a href="<?= $detail_link ?>" class="text-decoration-none text-dark">
                            <h6 class="fw-bold mb-2" style="min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($p['name']) ?>
                            </h6>
                        </a>

                        <div class="mb-2">
                            <span class="text-danger fw-extrabold fs-5" style="font-weight: 800;"><?= format_vnd($price_sale) ?></span>
                            <?php if ($price_original >$price_sale): ?>
                                <del class="text-muted small ms-2"><?= format_vnd($price_original) ?></del>
                            <?php endif; ?>
                        </div>

                        <!-- Checkbox So sánh -->
                        <div class="form-check mb-3 bg-light p-2 rounded-2 d-flex align-items-center justify-content-between">
                            <label class="form-check-label small fw-semibold text-secondary" for="compare_<?= $p['id'] ?>" style="cursor: pointer;">
                                <i class="fa-solid fa-scale-balanced me-1 text-primary"></i> So sánh cấu hình
                            </label>
                            <input class="form-check-input compare-checkbox m-0" type="checkbox" value="<?= (int)$p['id'] ?>" id="compare_<?= $p['id'] ?>" onchange="toggleCompare(this, <?= htmlspecialchars(json_encode($p['name'])) ?>, '<?= htmlspecialchars($p['image']) ?>', '<?= format_vnd($price_sale) ?>', '<?=$chip_info ?>', '<?= $ram_info ?>', '<?= $battery_info ?>')">
                        </div>

                        <form action="cart.php" method="POST" class="mt-auto">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary-custom w-100 py-2">
                                <i class="fa-solid fa-cart-plus me-1"></i> Thêm vào giỏ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- THANH NỔI SO SÁNH NHANH -->
<div id="compareBar" class="fixed-bottom bg-dark text-white p-3 shadow-lg border-top border-danger" style="display: none; z-index: 1050; backdrop-filter: blur(10px); background: rgba(17, 24, 39, 0.92) !important;">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 overflow-auto" id="compareItemsList">
            <span class="fw-bold text-warning"><i class="fa-solid fa-scale-balanced me-2"></i>So sánh (<span id="compareCount">0</span>/3):</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning fw-bold text-dark px-4 py-2" onclick="openCompareModal()">
                Xem bảng so sánh chi tiết
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="clearCompare()">Xóa tất cả</button>
        </div>
    </div>
</div>

<!-- MODAL BẢNG SO SÁNH CẤU HÌNH -->
<div class="modal fade" id="compareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-scale-balanced me-2 text-warning"></i>Bảng So Sánh Hiệu Năng & Thông Số Điện Thoại</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr id="compareTableHeader">
                                <th style="width: 22%;">Tiêu chí</th>
                            </tr>
                        </thead>
                        <tbody id="compareTableBody">
                            <tr><th class="text-start bg-light">Hình ảnh & Tên</th></tr>
                            <tr><th class="text-start bg-light">Mức giá bán</th></tr>
                            <tr><th class="text-start bg-light"><i class="fa-solid fa-microchip text-danger me-1"></i> Hiệu năng Chip xử lý</th></tr>
                            <tr><th class="text-start bg-light"><i class="fa-solid fa-memory text-primary me-1"></i> RAM & Bộ nhớ trong</th></tr>
                            <tr><th class="text-start bg-light"><i class="fa-solid fa-battery-full text-success me-1"></i> Dung lượng Pin & Sạc</th></tr>
                            <tr><th class="text-start bg-light"><i class="fa-solid fa-star text-warning me-1"></i> Đánh giá người dùng</th></tr>
                            <tr><th class="text-start bg-light">Thao tác mua</th></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedCompare = [];

    function toggleCompare(checkbox, name, image, price, chip, ram, battery) {
        const id = checkbox.value;
        if (checkbox.checked) {
            if (selectedCompare.length >= 3) {
                alert("Bạn chỉ có thể chọn tối đa 3 sản phẩm để so sánh thông số!");
                checkbox.checked = false;
                return;
            }
            selectedCompare.push({ id, name, image, price, chip, ram, battery });
        } else {
            selectedCompare = selectedCompare.filter(item => item.id !== id);
        }
        updateCompareBar();
    }

    function updateCompareBar() {
        const bar = document.getElementById('compareBar');
        const list = document.getElementById('compareItemsList');
        const count = document.getElementById('compareCount');
        
        count.innerText = selectedCompare.length;
        list.innerHTML = `<span class="fw-bold text-warning"><span class="fa-solid fa-scale-balanced me-2"></span>So sánh (<span id="compareCount">${selectedCompare.length}</span>/3):</span>`;
        
        selectedCompare.forEach(item => {
            let chip = document.createElement('div');
            chip.className = 'badge bg-light text-dark p-2 d-flex align-items-center gap-2 border';
            chip.innerHTML = `<img src="${item.image}" style="width:30px;height:30px;object-fit:contain;"> <span class="text-truncate" style="max-width:120px;">${item.name}</span> <i class="fa-solid fa-xmark text-danger" style="cursor:pointer;" onclick="removeCompare('${item.id}')"></i>`;
            list.appendChild(chip);
        });

        if (selectedCompare.length > 0) {
            bar.style.display = 'block';
        } else {
            bar.style.display = 'none';
        }
    }

    function removeCompare(id) {
        selectedCompare = selectedCompare.filter(item => item.id !== id);
        let cb = document.getElementById('compare_' + id);
        if (cb) cb.checked = false;
        updateCompareBar();
    }

    function clearCompare() {
        selectedCompare.forEach(item => {
            let cb = document.getElementById('compare_' + item.id);
            if (cb) cb.checked = false;
        });
        selectedCompare = [];
        updateCompareBar();
    }

    function openCompareModal() {
        if (selectedCompare.length === 0) return;

        let headerRow = document.getElementById('compareTableHeader');
        let bodyRows = document.getElementById('compareTableBody').children;

        let headerHTML = '<th style="width: 22%;">Tiêu chí</th>';
        selectedCompare.forEach(item => {
            headerHTML += `<th><img src="${item.image}" style="height:70px;object-fit:contain;" class="mb-1"><br><strong class="small">${item.name}</strong></th>`;
        });
        headerRow.innerHTML = headerHTML;

        let imgNameHTML = '<th class="text-start bg-light">Sản phẩm</th>';
        selectedCompare.forEach(item => {
            imgNameHTML += `<td><a href="product-detail.php?id=${item.id}" class="text-decoration-none fw-bold text-dark">${item.name}</a></td>`;
        });
        bodyRows[0].innerHTML = imgNameHTML;

        let priceHTML = '<th class="text-start bg-light">Mức giá</th>';
        selectedCompare.forEach(item => {
            priceHTML += `<td class="text-danger fw-bold fs-5">${item.price}</td>`;
        });
        bodyRows[1].innerHTML = priceHTML;

        let chipHTML = '<th class="text-start bg-light">Hiệu năng Chip xử lý</th>';
        selectedCompare.forEach(item => {
            chipHTML += `<td><span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">${item.chip}</span></td>`;
        });
        bodyRows[2].innerHTML = chipHTML;

        let ramHTML = '<th class="text-start bg-light">RAM & Bộ nhớ trong</th>';
        selectedCompare.forEach(item => {
            ramHTML += `<td><strong>${item.ram}</strong></td>`;
        });
        bodyRows[3].innerHTML = ramHTML;

        let batteryHTML = '<th class="text-start bg-light">Dung lượng Pin & Sạc</th>';
        selectedCompare.forEach(item => {
            batteryHTML += `<td>${item.battery}</td>`;
        });
        bodyRows[4].innerHTML = batteryHTML;

        let ratingHTML = '<th class="text-start bg-light">Đánh giá chung</th>';
        selectedCompare.forEach(item => {
            ratingHTML += `<td><span class="text-warning"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span> (4.9/5)</td>`;
        });
        bodyRows[5].innerHTML = ratingHTML;

        let actionHTML = '<th class="text-start bg-light">Thao tác mua</th>';
        selectedCompare.forEach(item => {
            actionHTML += `<td><a href="product-detail.php?id=${item.id}" class="btn btn-sm btn-danger w-100 fw-bold">Xem chi tiết</a></td>`;
        });
        bodyRows[6].innerHTML = actionHTML;

        let myModal = new bootstrap.Modal(document.getElementById('compareModal'));
        myModal.show();
    }

    let totalSeconds = 4 * 3600 + 45 * 60 + 59;
    setInterval(() => {
        if (totalSeconds <= 0) totalSeconds = 5 * 3600;
        totalSeconds--;
        const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        const elH = document.getElementById('fs-hours');
        const elM = document.getElementById('fs-mins');
        const elS = document.getElementById('fs-secs');
        if (elH && elM && elS) {
            elH.innerText = h;
            elM.innerText = m;
            elS.innerText = s;
        }
    }, 1000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>