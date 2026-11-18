</main>

<!-- ======================================================================= -->
<!-- POPUP QUẢNG CÁO CỠ LỚN GIỮA MÀN HÌNH KHI MỚI VÀO WEB (CÓ NÚT ĐÓNG Ở GÓC) -->
<!-- ======================================================================= -->
<style>
    /* Thiết kế Modal Quảng cáo giữa màn hình */
    #welcomePromoModal .modal-content {
        border: none;
        border-radius: 24px;
        overflow: hidden;
        background: linear-gradient(135deg, #1a0004 0%, #7a000e 45%, #d70018 100%);
        color: #fff;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55);
        position: relative;
    }

    /* Nút đóng chữ X nổi bật ở góc phải trên cùng */
    .promo-close-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 2px solid rgba(255, 255, 255, 0.6);
        color: #fff;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1060;
        transition: all 0.25s ease;
    }
    .promo-close-btn:hover {
        background: #fff;
        color: #d70018;
        transform: rotate(90deg) scale(1.1);
    }

    .promo-badge-mega {
        display: inline-block;
        background: linear-gradient(90deg, #fde047, #f59e0b);
        color: #111827;
        font-weight: 800;
        font-size: 0.8rem;
        padding: 6px 16px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
    }

    .voucher-ticket {
        background: rgba(255, 255, 255, 0.95);
        color: #111827;
        border-radius: 16px;
        padding: 14px 18px;
        border: 2px dashed #d70018;
        transition: transform 0.2s ease;
    }
    .voucher-ticket:hover {
        transform: scale(1.02);
    }

    /* Nút nổi góc trái dưới để mở lại quảng cáo bất cứ lúc nào */
    .floating-promo-trigger {
        position: fixed;
        bottom: 24px;
        left: 24px;
        z-index: 1020;
        background: linear-gradient(135deg, #d70018, #ff416c);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 10px 18px;
        font-weight: 700;
        font-size: 0.88rem;
        box-shadow: 0 8px 24px rgba(215, 0, 24, 0.4);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
    }
    .floating-promo-trigger:hover {
        transform: translateY(-3px) scale(1.03);
        color: #fff;
    }

    /* Footer chuyên nghiệp */
    .pro-footer {
        background-color: #111827;
        color: #9ca3af;
        font-size: 0.9rem;
    }
    .pro-footer h6 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 1.1rem;
        text-transform: uppercase;
        font-size: 0.88rem;
        letter-spacing: 0.5px;
    }
    .pro-footer ul li {
        margin-bottom: 0.6rem;
    }
    .pro-footer a {
        color: #9ca3af;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .pro-footer a:hover {
        color: #fff;
    }
    .payment-pill {
        background: #1f2937;
        border: 1px solid #374151;
        color: #e5e7eb;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<!-- Modal Quảng cáo Giữa Màn Hình -->
<div class="modal fade" id="welcomePromoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- Nút Đóng (X) ở góc trên bên phải -->
            <button type="button" class="promo-close-btn" data-bs-dismiss="modal" aria-label="Đóng quảng cáo" title="Đóng quảng cáo">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="row g-0 align-items-center">
                <!-- Cột hình ảnh Banner bên trái -->
                <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4 text-center" 
                     style="background: radial-gradient(circle at center, rgba(255,255,255,0.18) 0%, transparent 70%); min-height: 420px;">
                    <div class="mt-2">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-crown me-1"></i>ĐỘC QUYỀN ONLINE
                        </span>
                    </div>
                    <div class="my-auto py-3">
                        <img src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=600&q=80" 
                             alt="Siêu Sale Điện Thoại" 
                             class="img-fluid rounded-4 shadow-lg border border-2 border-warning" 
                             style="max-height: 250px; object-fit: cover;">
                    </div>
                    <div class="small text-white-50">
                        * Áp dụng cho tất cả dòng máy iPhone, Samsung, Xiaomi
                    </div>
                </div>

                <!-- Cột Nội dung Khuyến mãi & Mã giảm giá bên phải -->
                <div class="col-md-7 p-4 p-lg-5">
                    <span class="promo-badge-mega mb-3">
                        <i class="fa-solid fa-fire-flame-curved me-1"></i>ĐẠI TIỆC CÔNG NGHỆ 2026
                    </span>
                    <h2 class="fw-extrabold mb-2" style="font-weight: 800; line-height: 1.2;">
                        SIÊU SALE GIỮA THÁNG <br>
                        <span class="text-warning">GIẢM ĐẾN 50%</span>
                    </h2>
                    <p class="text-white-50 mb-4 small">
                        Tặng ngay gói bảo hành VIP 1 đổi 1 trong 30 ngày & Voucher giảm trực tiếp vào đơn hàng hôm nay!
                    </p>

                    <!-- Hộp mã giảm giá có nút Sao chép -->
                    <div class="voucher-ticket mb-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-danger mb-1">GIẢM NGAY 10%</span>
                            <div class="fw-bold fs-5 text-dark">MÃ: <span id="promoCodeText" class="text-danger">KHUYENMAI10</span></div>
                            <small class="text-muted">Áp dụng cho mọi đơn hàng tại Phone Store</small>
                        </div>
                        <button type="button" class="btn btn-dark fw-bold px-3 py-2 rounded-3" onclick="copyVoucherCode()">
                            <i class="fa-regular fa-copy me-1"></i><span id="copyBtnText">Sao chép</span>
                        </button>
                    </div>

                    <!-- Nút kêu gọi hành động -->
                    <div class="d-grid gap-2 mt-4">
                        <a href="index.php#all-products" class="btn btn-warning btn-lg fw-bold text-dark rounded-pill shadow" onclick="closePromoModal()">
                            <i class="fa-solid fa-bolt me-2"></i>MUA SẮM NGAY
                        </a>
                    </div>

                    <!-- Tùy chọn không hiện lại trong phiên -->
                    <div class="form-check mt-3 d-flex justify-content-center align-items-center gap-2">
                        <input class="form-check-input mt-0" type="checkbox" id="dontShowAgainCheck">
                        <label class="form-check-label small text-white-50" for="dontShowAgainCheck" style="cursor: pointer;">
                            Không tự động hiện lại trong phiên duyệt web này
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nút nổi góc dưới bên trái giúp mở lại Quảng cáo bất cứ lúc nào -->
<button type="button" class="floating-promo-trigger" onclick="openPromoModal()" title="Xem ưu đãi & lấy mã giảm giá">
    <i class="fa-solid fa-gift fa-bounce text-warning"></i>
    <span>Ưu đãi 50%</span>
</button>

<!-- ======================================================================= -->
<!-- CHÂN TRANG (FOOTER) CHUẨN THƯƠNG MẠI ĐIỆN TỬ CHUYÊN NGHIỆP              -->
<!-- ======================================================================= -->
<footer class="pro-footer pt-5 pb-4 mt-auto">
    <div class="container">
        <div class="row g-4 pb-4 border-bottom border-secondary border-opacity-25">
            <!-- Cột 1: Thông tin thương hiệu -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="bg-danger text-white rounded-3 px-2 py-1 fw-bold"><i class="fa-solid fa-mobile-screen-button"></i></span>
                    <span class="text-white fw-bold fs-5">PHONE STORE</span>
                </div>
                <p class="small mb-3">
                    Hệ thống bán lẻ điện thoại di động, máy tính bảng và phụ kiện chính hãng uy tín hàng đầu. Cam kết 100% sản phẩm nguyên seal, bảo hành chính hãng toàn quốc.
                </p>
                <div class="small text-white mb-1"><i class="fa-solid fa-location-dot text-danger me-2"></i>Địa chỉ: Quận Hai Bà Trưng, TP. Hà Nội</div>
                <div class="small text-white mb-1"><i class="fa-solid fa-phone text-success me-2"></i>Tổng đài miễn phí: <strong>1800.6868</strong> (8:00 - 22:00)</div>
                <div class="small text-white"><i class="fa-solid fa-envelope text-warning me-2"></i>Email: support@phonestore.vn</div>
            </div>

            <!-- Cột 2: Chính sách -->
            <div class="col-lg-2 col-md-6">
                <h6>Chính sách hỗ trợ</h6>
                <ul class="list-unstyled small">
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Chính sách bảo hành</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>1 đổi 1 trong 30 ngày</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Chính sách trả góp 0%</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Giao hàng hỏa tốc 2h</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Bảo mật thông tin</a></li>
                </ul>
            </div>

            <!-- Cột 3: Hướng dẫn khách hàng -->
            <div class="col-lg-3 col-md-6">
                <h6>Dịch vụ khách hàng</h6>
                <ul class="list-unstyled small">
                    <li><a href="cart.php"><i class="fa-solid fa-angle-right me-2"></i>Giỏ hàng & Thanh toán</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Hướng dẫn quét mã VietQR</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Thu cũ đổi mới trợ giá</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Tra cứu hóa đơn điện tử</a></li>
                    <li><a href="#"><i class="fa-solid fa-angle-right me-2"></i>Câu hỏi thường gặp (FAQ)</a></li>
                </ul>
            </div>

            <!-- Cột 4: Phương thức thanh toán -->
            <div class="col-lg-3 col-md-6">
                <h6>Chấp nhận thanh toán</h6>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="payment-pill"><i class="fa-solid fa-qrcode text-info"></i> VietQR 24/7</span>
                    <span class="payment-pill"><i class="fa-solid fa-wallet text-danger"></i> Ví MoMo</span>
                    <span class="payment-pill"><i class="fa-brands fa-cc-visa text-primary"></i> Visa / Master</span>
                    <span class="payment-pill"><i class="fa-solid fa-money-bill-wave text-success"></i> Tiền mặt (COD)</span>
                </div>
                <h6>Kết nối với chúng tôi</h6>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 small text-secondary">
            <div>© <?= date('Y') ?> <strong>Phone Store</strong>. Hệ thống bán lẻ điện thoại & phụ kiện chính hãng.</div>
            <div>Đã thông báo Bộ Công Thương <i class="fa-solid fa-circle-check text-primary ms-1"></i></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let promoModalInstance = null;

    document.addEventListener("DOMContentLoaded", function () {
        const modalEl = document.getElementById('welcomePromoModal');
        if (!modalEl) return;

        promoModalInstance = new bootstrap.Modal(modalEl);

        // Kiểm tra xem hiện tại có đang ở Trang chủ (index.php) hay không
        const path = window.location.pathname;
        const isHomePage = path.endsWith('/phone_store/') || path.endsWith('/index.php') || path === '/';

        // Tự động bật Popup Quảng cáo giữa màn hình khi vào Trang chủ (nếu chưa tích "Không hiện lại")
        if (isHomePage && sessionStorage.getItem('hide_phone_store_promo') !== '1') {
            setTimeout(() => {
                promoModalInstance.show();
            }, 350);
        }

        // Lưu trạng thái nếu người dùng tích vào ô "Không tự động hiện lại"
        modalEl.addEventListener('hide.bs.modal', function () {
            const dontShowCheck = document.getElementById('dontShowAgainCheck');
            if (dontShowCheck && dontShowCheck.checked) {
                sessionStorage.setItem('hide_phone_store_promo', '1');
            }
        });
    });

    function openPromoModal() {
        if (promoModalInstance) {
            promoModalInstance.show();
        }
    }

    function closePromoModal() {
        if (promoModalInstance) {
            promoModalInstance.hide();
        }
    }

    function copyVoucherCode() {
        const code = document.getElementById('promoCodeText').innerText;
        navigator.clipboard.writeText(code).then(() => {
            const btnText = document.getElementById('copyBtnText');
            btnText.innerText = 'Đã chép!';
            setTimeout(() => {
                btnText.innerText = 'Sao chép';
            }, 2000);
        });
    }
</script>
</body>
</html>