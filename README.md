# 📦 SmartStock - Backend API (HUTECH Capstone Project)

Đây là hệ thống API RESTful phục vụ cho dự án **SmartStock - Ứng dụng quản lý kho thông minh**. Dự án được phát triển bởi **Nguyễn Duy Anh** (MSSV: 2280600075)
## 🚀 Công nghệ sử dụng
* [cite_start]**Ngôn ngữ:** PHP 8.x [cite: 316]
* [cite_start]**Cơ sở dữ liệu:** MySQL (Thiết kế chuẩn hóa 3NF) [cite: 220, 276]
* [cite_start]**Kiến trúc:** RESTful API (Client-Server) [cite: 195, 398]
* [cite_start]**Tích hợp:** Firebase (Authentication & Cloud Messaging), VietQR API [cite: 301, 399]

## 🛠 Tính năng chính
* [cite_start]**Quản lý người dùng:** Xác thực đa cấp, đăng nhập bằng tài khoản nội bộ hoặc Google qua Firebase[cite: 322, 323, 399].
* [cite_start]**Quản lý kho:** Thực hiện các thao tác CRUD sản phẩm, danh mục, nhà cung cấp[cite: 335, 389].
* [cite_start]**Xử lý đơn hàng:** Quy trình duyệt đơn hàng thông minh, hỗ trợ xác thực thanh toán qua ảnh chụp màn hình[cite: 266, 402].
* [cite_start]**Thanh toán VietQR:** Sinh mã QR thanh toán động giúp chính xác hóa thông tin chuyển khoản[cite: 378, 381].
* [cite_start]**Tự động hóa:** Tự động hủy đơn hàng và hoàn kho nếu không được phê duyệt sau 28 giờ[cite: 273, 274].
* [cite_start]**Bảo mật:** Mã hóa mật khẩu một chiều (Hash) và sử dụng Bearer Token cho các API nhạy cảm[cite: 253, 326].

## 📋 Cấu trúc cơ sở dữ liệu
Hệ thống quản lý các bảng chính:
* [cite_start]`users`: Thông tin người dùng và phân quyền (Admin, Staff, Customer)[cite: 281].
* [cite_start]`product`: Quản lý tồn kho sản phẩm[cite: 283].
* [cite_start]`customer_orders`: Theo dõi đơn hàng và trạng thái thanh toán[cite: 285].
* [cite_start]`bank_info`: Cấu hình nhận diện VietQR[cite: 287].

## 🔗 Liên kết liên quan
* **Frontend (Flutter):** [Link tới Repo Frontend của bạn]
© 2025 - Nguyễn Duy Anh - HUTECH University
