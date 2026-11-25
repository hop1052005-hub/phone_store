<div align="center">
  <h1>📱 Phone Store — Website Bán Điện Thoại Trực Tuyến</h1>
  <p><strong>Online Mobile Phone Store Web Application built with PHP &amp; MySQL</strong></p>
  <p>
    <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+" />
    <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL / MariaDB" />
    <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5.3" />
    <img src="https://img.shields.io/badge/Chart.js-Dashboard-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white" alt="Chart.js" />
  </p>
</div>

---

## 📖 Giới thiệu | Overview

**Phone Store** là ứng dụng web thương mại điện tử bán điện thoại xây dựng bằng **PHP thuần (PDO)** và **MySQL/MariaDB**, hỗ trợ đầy đủ quy trình mua sắm của khách hàng, hệ thống quản trị (Admin Panel) tách biệt phiên đăng nhập, cùng nhiều phương thức thanh toán trực tuyến và công cụ giả lập để kiểm thử nội bộ.

> **Phone Store** is a full-stack e-commerce web application for selling mobile phones, built with pure **PHP (PDO)** and **MySQL/MariaDB**, featuring a customer storefront, an isolated admin dashboard, inventory & coupon management, and multiple payment gateways.

---

## ✨ Tính năng nổi bật | Key Features

### 🛍️ Trang khách hàng (Storefront)
- **Duyệt & tìm kiếm sản phẩm:** Hiển thị sản phẩm nổi bật, lọc theo danh mục, thương hiệu, khoảng giá và sắp xếp linh hoạt (`index.php`, `products.php`).
- **Chi tiết sản phẩm & biến thể:** Xem hình ảnh, thông số, lựa chọn biến thể màu sắc/dung lượng và đánh giá từ khách hàng (`product-detail.php`).
- **Tài khoản khách hàng:** Đăng ký, đăng nhập, đăng xuất và tự động kiểm tra trạng thái khóa tài khoản (`register.php`, `login.php`, `logout.php`).
- **Giỏ hàng & mã giảm giá:** Thêm/cập nhật/xóa sản phẩm trong giỏ, áp dụng mã giảm giá (coupon) (`cart.php`).
- **Đặt hàng & kiểm tra tồn kho:** Xác thực số lượng tồn kho khi thanh toán, lưu lịch sử đơn hàng và theo dõi trạng thái đơn (`checkout.php`, `order-success.php`, `orders.php`).

### 💳 Phương thức thanh toán (Payment Gateways)
- **COD:** Thanh toán tiền mặt khi nhận hàng.
- **Chuyển khoản ngân hàng (VietQR + SePay IPN):** Tự động tạo mã VietQR (`img.vietqr.io`), nhận webhook tự động qua `api_bank_ipn.php` và kiểm tra trạng thái thanh toán thời gian thực (`check_payment.php`). Có sẵn trình giả lập chuyển khoản nội bộ (`bank_simulator.php`) để test trên localhost.
- **VNPay Sandbox:** Tích hợp luồng thanh toán và xử lý callback/IPN (`vnpay_return.php`, `vnpay_ipn.php`).
- **Visa Gateway (Mô phỏng):** Mô phỏng giao diện thanh toán bằng thẻ quốc tế (`visa_gateway.php`).

### 🛠️ Trang quản trị (Admin Panel)
- **Bảo mật phiên riêng biệt:** Sử dụng `session_name('AdminSession')` tách biệt hoàn toàn với phiên đăng nhập của khách hàng (`admin/login.php`, `admin/auth_admin.php`).
- **Dashboard trực quan:** Thống kê KPI, biểu đồ doanh thu bằng **Chart.js**, cảnh báo sản phẩm sắp hết hàng và danh sách bán chạy (`admin/index.php`).
- **Quản lý toàn diện:**
  - Sản phẩm & biến thể (`admin/products.php`)
  - Danh mục & thương hiệu (`admin/categories_brands.php`)
  - Mã giảm giá (`admin/coupons.php`)
  - Khách hàng (`admin/customers.php`)
  - Đánh giá sản phẩm (`admin/reviews.php`)
- **Quản lý đơn hàng & in hóa đơn:** Xem chi tiết, cập nhật trạng thái đơn hàng và xuất mẫu in hóa đơn (`admin/orders.php`, `admin/order-detail.php`, `admin/print-order.php`).
- **Báo cáo thống kê:** Tổng hợp doanh thu và hiệu quả bán hàng (`admin/reports.php`).

---

## 🧰 Công nghệ sử dụng | Tech Stack

| Thành phần | Công nghệ / Thư viện |
| :--- | :--- |
| **Backend** | PHP 8.2+ (PDO, `utf8mb4`, Session tách biệt Storefront & Admin) |
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5.3.2, Font Awesome 6.5.1, Google Fonts (`Inter`) |
| **Biểu đồ** | Chart.js (Dashboard doanh thu & báo cáo) |
| **Cơ sở dữ liệu** | MySQL / MariaDB (`utf8mb4_general_ci`) |
| **Thanh toán** | VietQR (`img.vietqr.io`), SePay Webhook IPN, VNPay Sandbox, Visa Simulator |

---

## 📁 Cấu trúc thư mục | Project Structure

```text
phone_store/
├── admin/                      # Trang quản trị (Admin Panel)
│   ├── auth_admin.php          # Kiểm tra phiên đăng nhập Admin
│   ├── categories_brands.php   # Quản lý danh mục & thương hiệu
│   ├── coupons.php             # Quản lý mã giảm giá
│   ├── customers.php           # Quản lý khách hàng
│   ├── index.php               # Dashboard KPI & biểu đồ doanh thu
│   ├── login.php / logout.php  # Đăng nhập / đăng xuất Admin
│   ├── order-detail.php        # Chi tiết đơn hàng
│   ├── orders.php              # Danh sách & cập nhật đơn hàng
│   ├── print-order.php         # Mẫu in hóa đơn
│   ├── products.php            # Quản lý sản phẩm
│   ├── reports.php             # Báo cáo thống kê
│   └── reviews.php             # Quản lý đánh giá
├── config/
│   └── db.php                  # Cấu hình kết nối PDO MySQL
├── database/
│   └── phone_store_db.sql      # File SQL khởi tạo 18 bảng dữ liệu
├── includes/
│   ├── header.php              # Header dùng chung + kiểm tra khóa tài khoản
│   └── footer.php              # Footer dùng chung
├── .env.example                # Mẫu biến môi trường (DB & SePay Webhook)
├── index.php                   # Trang chủ
├── products.php                # Danh sách, lọc & tìm kiếm sản phẩm
├── product-detail.php          # Chi tiết sản phẩm
├── cart.php                    # Giỏ hàng & áp dụng coupon
├── checkout.php                # Trang thanh toán
├── order-success.php           # Xác nhận đặt hàng & QR thanh toán
├── orders.php                  # Lịch sử đơn hàng của khách
├── login.php / register.php    # Đăng nhập / đăng ký khách hàng
├── logout.php                  # Đăng xuất khách hàng
├── api_bank_ipn.php            # Webhook nhận giao dịch từ SePay
├── bank_simulator.php          # Trình giả lập chuyển khoản test local
├── check_payment.php           # API polling kiểm tra trạng thái thanh toán
├── vnpay_ipn.php               # Xử lý IPN từ VNPay
├── vnpay_return.php            # Trang trả về sau khi thanh toán VNPay
└── visa_gateway.php            # Cổng thanh toán thẻ Visa mô phỏng
```

---

## 🗄️ Cơ sở dữ liệu | Database Schema

File `database/phone_store_db.sql` tạo cơ sở dữ liệu `phone_store_db` gồm **18 bảng** chuẩn hóa:

- **Người dùng:** `users`, `addresses`, `wishlists`
- **Sản phẩm:** `categories`, `brands`, `products`, `product_variants`, `product_images`, `reviews`
- **Giỏ hàng & Khuyến mãi:** `carts`, `cart_items`, `coupons`, `coupon_usages`
- **Đơn hàng & Thanh toán:** `orders`, `order_items`, `order_status_history`, `payments`, `shipments`

---

## 🚀 Hướng dẫn cài đặt & chạy dự án | Installation

### 1. Yêu cầu hệ thống
- **XAMPP** hoặc **Laragon** (Apache + PHP **8.2+** + MySQL / MariaDB).

### 2. Tải mã nguồn
Clone dự án vào thư mục web root (ví dụ `htdocs` của XAMPP):

```bash
git clone https://github.com/hop1052005-hub/phone_store.git
```

### 3. Khởi tạo cơ sở dữ liệu
1. Mở **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Tạo cơ sở dữ liệu mới tên `phone_store_db` với bảng mã `utf8mb4_general_ci`.
3. Chọn tab **Import** và nạp file `database/phone_store_db.sql`.

### 4. Cấu hình kết nối
- Kiểm tra thông tin kết nối database trong `config/db.php` và `admin/auth_admin.php` (mặc định: host `localhost` / `127.0.0.1`, database `phone_store_db`, user `root`, mật khẩu trống).
- *(Tùy chọn)* Sao chép `.env.example` thành `.env` để cấu hình `SEPAY_WEBHOOK_URL` nếu bạn muốn test webhook SePay thực tế.

### 5. Khởi chạy & truy cập
Bật **Apache** và **MySQL**, sau đó mở trình duyệt:

- **Trang khách hàng:** `http://localhost/phone_store/`
- **Trang quản trị (Admin):** `http://localhost/phone_store/admin/`
- **Giả lập chuyển khoản (Test local):** `http://localhost/phone_store/bank_simulator.php`

---

## 👨‍💻 Tác giả | Author

- **Phạm Quang Hợp** — IT Student @ HUBT
- **GitHub:** [@hop1052005-hub](https://github.com/hop1052005-hub)
- **Email:** [hop1052005@gmail.com](mailto:hop1052005@gmail.com)
