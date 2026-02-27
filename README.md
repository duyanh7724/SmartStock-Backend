# 📦 SmartStock - Backend API (HUTECH Capstone Project)

Đây là hệ thống API RESTful phục vụ cho dự án **SmartStock - Ứng dụng quản lý kho thông minh**. Dự án được phát triển nhằm tối ưu hóa quy trình vận hành kho bãi cho các doanh nghiệp vừa và nhỏ.

## 🚀 Công nghệ sử dụng
* **Ngôn ngữ:** PHP 8.x.
* **Cơ sở dữ liệu:** MySQL (Thiết kế chuẩn hóa 3NF).
* **Kiến trúc:** RESTful API (Mô hình Client-Server).
* **Dịch vụ tích hợp:** Firebase Authentication & Realtime Database, VietQR API.

## 🛠 Tính năng chính
* **Xác thực đa cấp:** Đăng nhập bằng tài khoản nội bộ (Admin/Staff) hoặc Google (Customer).
* **Quản lý kho thông minh:** Thực hiện các thao tác CRUD sản phẩm, danh mục và nhà cung cấp.
* **Quy trình đơn hàng bài bản:** Theo dõi trạng thái đơn hàng (Chờ duyệt, Đã duyệt, Hủy) và phê duyệt dựa trên ảnh xác thực giao dịch.
* **Tự động hóa hoàn kho:** Hệ thống tự động hủy đơn hàng và hoàn lại số lượng tồn kho nếu không được phê duyệt sau 28 giờ.
* **Bảo mật hệ thống:** Mật khẩu được băm (Hash), API yêu cầu xác thực Bearer Token và lưu trữ nhật ký hoạt động.

## 📊 Cấu trúc cơ sở dữ liệu
Hệ thống quản lý các thực thể chính bao gồm: Người dùng (users), Sản phẩm (product), Đơn hàng (customer_orders), Thông tin ngân hàng (bank_info) và Nhật ký hệ thống.

---
**Sinh viên thực hiện:** Nguyễn Duy Anh (MSSV: 2280600075).
**Giảng viên hướng dẫn:** Võ Hoàng Khang.
